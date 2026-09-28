/**
 * 구축 상담 양식 — 홈 레이아웃이 선언한 호스트(`[data-rh-consult]`) 안에서만 동작한다.
 *
 * 호스트는 레이아웃 JSON 이 자식 없이 선언하므로 React 가 내부 DOM 을 재조정하지 않는다.
 * 사용자 입력은 textContent/value 로만 다루며 innerHTML 에 넣지 않는다.
 */
import {
  buildPayload,
  classifySubmitResponse,
  CONSULTATION_API,
  type ConsultationDraft,
  createIdempotencyKey,
  EMPTY_DRAFT,
  type FieldErrors,
  type FieldName,
  type IdempotencySlot,
  type IntakeConfig,
  parseIntakeConfig,
  payloadFingerprint,
  type Receipt,
  resolveIdempotencyKey,
  validateDraft,
} from './consultation';
import { currentLocale, t } from './i18n';
import { intakeStateOf, needsIntakeState, publishIntakeState } from './intakeState';

type View = 'loading' | 'unavailable' | 'form' | 'success';
type BannerKind = 'error' | 'warn' | 'ok';

interface Island {
  host: HTMLElement;
  root: HTMLElement;
  view: View;
  locale: string;
  config: IntakeConfig | null;
  errors: FieldErrors;
  banner: { kind: BannerKind; text: string; items?: string[] } | null;
  submitting: boolean;
  throttleUntil: number;
  receipt: Receipt | null;
  replay: boolean;
  nonce: number;
}

const CONFIG_TIMEOUT_MS = 10_000;
const SUBMIT_TIMEOUT_MS = 20_000;
const MOUNTED = 'rhConsultMounted';

// SPA 이동 뒤 돌아와도 작성 중인 내용이 남도록 탭 메모리에만 보관한다(저장소에 쓰지 않음).
let sharedDraft: ConsultationDraft = { ...EMPTY_DRAFT };
let sharedSlot: IdempotencySlot | null = null;
const islands = new Set<Island>();

function el<K extends keyof HTMLElementTagNameMap>(
  tag: K,
  attrs: Record<string, string> = {},
  text?: string,
): HTMLElementTagNameMap[K] {
  const node = document.createElement(tag);
  for (const [name, value] of Object.entries(attrs)) node.setAttribute(name, value);
  if (text !== undefined) node.textContent = text;
  return node;
}

function requestHeaders(json: boolean): Record<string, string> {
  const headers: Record<string, string> = { Accept: 'application/json', 'Accept-Language': currentLocale() };
  if (json) headers['Content-Type'] = 'application/json';
  try {
    const token = window.localStorage.getItem('auth_token');
    if (token) headers.Authorization = `Bearer ${token}`;
  } catch {
    // 저장소 접근이 막힌 환경에서는 비로그인 요청으로 보낸다.
  }
  const xsrf = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
  if (xsrf) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf.split('=').slice(1).join('='));
  return headers;
}

async function fetchWithTimeout(url: string, init: RequestInit, timeoutMs: number): Promise<Response> {
  const controller = new AbortController();
  const timer = window.setTimeout(() => controller.abort(), timeoutMs);
  try {
    return await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...init, signal: controller.signal });
  } finally {
    window.clearTimeout(timer);
  }
}

async function fetchConfig(): Promise<IntakeConfig | null> {
  try {
    const response = await fetchWithTimeout(`${CONSULTATION_API}/config`, { headers: requestHeaders(false) }, CONFIG_TIMEOUT_MS);
    if (!response.ok) return null;
    return parseIntakeConfig(await response.json().catch(() => null), window.location.origin);
  } catch {
    return null;
  }
}

let configInFlight: Promise<IntakeConfig | null> | null = null;

/** 동시에 들어온 확인은 한 요청으로 합치고, 결과를 화면의 접수 상태 표시에 반영한다. */
function loadConfig(): Promise<IntakeConfig | null> {
  configInFlight ??= fetchConfig().then((config) => {
    configInFlight = null;
    publishIntakeState(intakeStateOf(config));
    return config;
  });
  return configInFlight;
}

function formatReceivedAt(value: string): string {
  if (value === '') return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  try {
    return new Intl.DateTimeFormat(currentLocale(), { dateStyle: 'medium', timeStyle: 'short' }).format(date);
  } catch {
    return date.toISOString();
  }
}

function errorText(error: FieldErrors[FieldName]): string {
  if (error === 'required' || error === 'email' || error === 'consent') return t(`consult.error_${error}`);
  return String(error ?? '');
}

/* ─── 화면 ───────────────────────────────────────────── */

function stateBlock(island: Island, titleKey: string, copyKey: string): HTMLElement {
  const block = el('div', { class: 'rh-state' });
  const title = el('h3', { class: 'rh-state-title', tabindex: '-1' }, t(titleKey));
  block.append(title, el('p', { class: 'rh-state-copy' }, t(copyKey)));
  island.root.replaceChildren(block);
  return block;
}

function renderLoading(island: Island): void {
  const block = el('div', { class: 'rh-state', role: 'status' });
  block.append(el('p', { class: 'rh-state-copy' }, t('consult.loading')));
  island.root.replaceChildren(block);
}

function renderUnavailable(island: Island): void {
  const block = stateBlock(island, 'consult.unavailable_title', 'consult.unavailable_copy');
  const retry = el('button', { type: 'button', class: 'rh-action' }, t('consult.unavailable_retry'));
  retry.addEventListener('click', () => void start(island));
  block.append(retry);
}

function renderSuccess(island: Island): void {
  const receipt = island.receipt;
  const block = stateBlock(island, 'consult.success_title', 'consult.success_copy');
  block.setAttribute('data-rh-consult-state', 'success');
  if (island.replay) block.insertBefore(el('p', { class: 'rh-alert', 'data-kind': 'ok' }, t('consult.success_replay')), block.children[1]);
  if (receipt) {
    const list = el('dl', { class: 'rh-receipt' });
    list.append(el('dt', {}, t('consult.success_reference')), el('dd', { 'data-rh-reference': '' }, receipt.reference));
    const receivedAt = formatReceivedAt(receipt.receivedAt);
    if (receivedAt !== '') list.append(el('dt', {}, t('consult.success_received_at')), el('dd', {}, receivedAt));
    block.append(list);
  }
  const again = el('button', { type: 'button', class: 'rh-action' }, t('consult.success_new'));
  again.addEventListener('click', () => {
    island.receipt = null;
    island.replay = false;
    island.banner = null;
    island.errors = {};
    island.view = 'form';
    render(island);
    island.root.querySelector<HTMLElement>('input, textarea')?.focus();
  });
  block.append(again);
}

interface FieldSpec {
  name: Exclude<FieldName, 'privacy_consent'>;
  labelKey: string;
  required: boolean;
  type?: string;
  autocomplete?: string;
  helpKey?: string;
}

const FIELD_SPECS: FieldSpec[] = [
  { name: 'contact_name', labelKey: 'consult.field_name', required: true, autocomplete: 'name' },
  { name: 'email', labelKey: 'consult.field_email', required: true, type: 'email', autocomplete: 'email' },
  { name: 'company', labelKey: 'consult.field_company', required: false, autocomplete: 'organization' },
  { name: 'phone', labelKey: 'consult.field_phone', required: false, type: 'tel', autocomplete: 'tel' },
  { name: 'service_interest', labelKey: 'consult.field_service', required: false },
  { name: 'message', labelKey: 'consult.field_message', required: true, helpKey: 'consult.field_message_hint' },
];

function fieldId(island: Island, name: string): string {
  return `rh-consult-${island.nonce}-${name}`;
}

function buildControl(island: Island, spec: FieldSpec): HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement {
  const id = fieldId(island, spec.name);
  if (spec.name === 'message') {
    const area = el('textarea', { id, name: spec.name, class: 'rh-input', rows: '6' });
    area.value = sharedDraft.message;
    return area;
  }
  if (spec.name === 'service_interest') {
    const select = el('select', { id, name: spec.name, class: 'rh-input' });
    select.append(el('option', { value: '' }, t('consult.field_service_none')));
    for (const option of island.config?.serviceOptions ?? []) {
      const label = option.label ?? t(`consult.service_${option.value}`);
      select.append(el('option', { value: option.value }, label.startsWith('raonslab-product.') ? option.value : label));
    }
    select.value = sharedDraft.service_interest;
    return select;
  }
  const input = el('input', { id, name: spec.name, class: 'rh-input', type: spec.type ?? 'text' });
  if (spec.autocomplete) input.setAttribute('autocomplete', spec.autocomplete);
  if (spec.name === 'email') input.setAttribute('inputmode', 'email');
  input.value = sharedDraft[spec.name];
  return input;
}

function buildField(island: Island, spec: FieldSpec): HTMLElement {
  const wrap = el('div', { class: 'rh-field', 'data-field': spec.name });
  const label = el('label', { class: 'rh-label', for: fieldId(island, spec.name) }, t(spec.labelKey));
  if (spec.required) {
    label.append(el('span', { 'aria-hidden': 'true' }, ' *'));
  } else {
    label.append(el('span', { class: 'rh-label-extra' }, `(${t('consult.optional')})`));
  }
  const control = buildControl(island, spec);
  if (spec.required) {
    control.setAttribute('aria-required', 'true');
  }
  const describedBy: string[] = [];
  wrap.append(label, control);
  if (spec.helpKey) {
    const help = el('p', { class: 'rh-field-help', id: `${fieldId(island, spec.name)}-help` }, t(spec.helpKey));
    describedBy.push(help.id);
    wrap.append(help);
  }
  const error = el('p', { class: 'rh-field-error', id: `${fieldId(island, spec.name)}-error`, hidden: '' });
  wrap.append(error);
  if (describedBy.length > 0) control.setAttribute('aria-describedby', describedBy.join(' '));
  control.addEventListener('input', () => {
    sharedDraft = { ...sharedDraft, [spec.name]: control.value };
    if (island.errors[spec.name]) {
      delete island.errors[spec.name];
      updateStatus(island);
    }
  });
  return wrap;
}

function buildPrivacy(island: Island): HTMLElement {
  const config = island.config as IntakeConfig;
  const fieldset = el('fieldset', { class: 'rh-privacy', 'data-field': 'privacy_consent' });
  fieldset.append(el('legend', {}, t('consult.privacy_title')));
  const copyId = fieldId(island, 'privacy-copy');
  fieldset.append(el('p', { class: 'rh-privacy-copy', id: copyId, tabindex: '0' }, config.privacyCopy));

  const meta = el('div', { class: 'rh-privacy-meta' });
  if (config.retentionNotice !== '') meta.append(el('p', {}, config.retentionNotice));
  if (config.privacyContact !== '') meta.append(el('p', {}, `${t('consult.privacy_contact')}: ${config.privacyContact}`));
  if (config.privacyPolicyUrl) {
    const link = el('a', { href: config.privacyPolicyUrl, target: '_blank', rel: 'noopener noreferrer' }, t('consult.privacy_policy_link'));
    const line = el('p');
    line.append(link);
    meta.append(line);
  }
  meta.append(el('p', {}, `${t('consult.privacy_version')}: ${config.consentVersion}`));
  fieldset.append(meta);

  const checkId = fieldId(island, 'privacy_consent');
  const label = el('label', { class: 'rh-check', for: checkId });
  const checkbox = el('input', { id: checkId, name: 'privacy_consent', type: 'checkbox', 'aria-required': 'true', 'aria-describedby': copyId });
  checkbox.checked = sharedDraft.privacy_consent;
  checkbox.addEventListener('change', () => {
    sharedDraft = { ...sharedDraft, privacy_consent: checkbox.checked };
    if (island.errors.privacy_consent) {
      delete island.errors.privacy_consent;
      updateStatus(island);
    }
  });
  label.append(checkbox, el('span', {}, t('consult.privacy_consent')));
  fieldset.append(label, el('p', { class: 'rh-field-error', id: `${checkId}-error`, hidden: '' }));
  return fieldset;
}

function renderForm(island: Island): void {
  const form = el('form', { class: 'rh-form', novalidate: '', 'aria-label': t('consult.form_label') });
  const banner = el('div', { class: 'rh-alert', 'data-rh-banner': '', tabindex: '-1', hidden: '' });
  form.append(banner, el('p', { class: 'rh-form-hint' }, t('consult.required_hint')));

  const [name, email, company, phone, service, message] = FIELD_SPECS.map((spec) => buildField(island, spec));
  const rowA = el('div', { class: 'rh-form-row' });
  rowA.append(name, email);
  const rowB = el('div', { class: 'rh-form-row' });
  rowB.append(company, phone);
  form.append(rowA, rowB, service, message, buildPrivacy(island));

  const submit = el('button', { type: 'submit', class: 'rh-action rh-action-primary rh-submit', 'data-rh-submit': '' }, t('consult.submit'));
  form.append(submit);
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    void submitForm(island);
  });
  island.root.replaceChildren(form);
  updateStatus(island);
}

/** 폼을 다시 만들지 않고 오류·배너·버튼 상태만 갱신한다(포커스와 입력 유지). */
function updateStatus(island: Island): void {
  const form = island.root.querySelector('form');
  if (!form) return;

  for (const name of [...FIELD_SPECS.map((spec) => spec.name), 'privacy_consent'] as FieldName[]) {
    const control = form.querySelector<HTMLElement>(`#${fieldId(island, name)}`);
    const error = form.querySelector<HTMLElement>(`#${fieldId(island, name)}-error`);
    if (!control || !error) continue;
    const message = island.errors[name] ? errorText(island.errors[name]) : '';
    const described = (control.getAttribute('aria-describedby') ?? '').split(' ').filter((id) => id !== '' && id !== error.id);
    if (message !== '') {
      error.textContent = message;
      error.hidden = false;
      control.setAttribute('aria-invalid', 'true');
      described.push(error.id);
    } else {
      error.textContent = '';
      error.hidden = true;
      control.removeAttribute('aria-invalid');
    }
    if (described.length > 0) control.setAttribute('aria-describedby', described.join(' '));
    else control.removeAttribute('aria-describedby');
  }

  const banner = form.querySelector<HTMLElement>('[data-rh-banner]');
  if (banner) {
    if (island.banner) {
      banner.dataset.kind = island.banner.kind;
      banner.setAttribute('role', island.banner.kind === 'error' ? 'alert' : 'status');
      banner.replaceChildren(el('p', {}, island.banner.text));
      if (island.banner.items && island.banner.items.length > 0) {
        const list = el('ul');
        for (const item of island.banner.items) list.append(el('li', {}, item));
        banner.append(list);
      }
      banner.hidden = false;
    } else {
      banner.hidden = true;
      banner.replaceChildren();
    }
  }

  const submit = form.querySelector<HTMLButtonElement>('[data-rh-submit]');
  if (submit) {
    const throttled = island.throttleUntil > Date.now();
    submit.disabled = island.submitting || throttled;
    submit.setAttribute('aria-busy', island.submitting ? 'true' : 'false');
    submit.textContent = t(island.submitting ? 'consult.submitting' : 'consult.submit');
  }
  form.setAttribute('aria-busy', island.submitting ? 'true' : 'false');
  form.querySelectorAll<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>('input, textarea, select').forEach((control) => {
    if (control instanceof HTMLSelectElement || (control instanceof HTMLInputElement && control.type === 'checkbox')) {
      control.disabled = island.submitting;
    } else {
      control.readOnly = island.submitting;
    }
  });
}

function render(island: Island): void {
  island.locale = currentLocale();
  island.root.dataset.rhConsultView = island.view;
  if (island.view === 'loading') renderLoading(island);
  else if (island.view === 'unavailable') renderUnavailable(island);
  else if (island.view === 'success') renderSuccess(island);
  else renderForm(island);
}

function focusFirst(island: Island, selector: string): void {
  island.root.querySelector<HTMLElement>(selector)?.focus();
}

/* ─── 동작 ───────────────────────────────────────────── */

async function start(island: Island): Promise<void> {
  island.view = 'loading';
  render(island);
  const config = await loadConfig();
  if (!islands.has(island)) return;
  island.config = config;
  island.view = config ? 'form' : 'unavailable';
  render(island);
}

function labelFor(name: FieldName): string {
  if (name === 'privacy_consent') return t('consult.privacy_title');
  const spec = FIELD_SPECS.find((item) => item.name === name);
  return spec ? t(spec.labelKey) : name;
}

function showErrors(island: Island, errors: FieldErrors, fallbackMessage = ''): void {
  island.errors = errors;
  const items = (Object.keys(errors) as FieldName[]).map((name) => `${labelFor(name)}: ${errorText(errors[name])}`);
  island.banner = { kind: 'error', text: items.length > 0 || fallbackMessage === '' ? t('consult.error_summary') : fallbackMessage, items };
  updateStatus(island);
  const first = (Object.keys(errors) as FieldName[])[0];
  if (first) focusFirst(island, `#${fieldId(island, first)}`);
  else focusFirst(island, '[data-rh-banner]');
}

async function submitForm(island: Island): Promise<void> {
  if (island.submitting || island.view !== 'form' || !island.config) return;
  if (island.throttleUntil > Date.now()) return;

  const clientErrors = validateDraft(sharedDraft);
  if (Object.keys(clientErrors).length > 0) {
    showErrors(island, clientErrors);
    return;
  }

  const payload = buildPayload(sharedDraft, island.config.consentVersion);
  sharedSlot = resolveIdempotencyKey(sharedSlot, payloadFingerprint(payload), createIdempotencyKey);
  const slot = sharedSlot;

  island.submitting = true;
  island.errors = {};
  island.banner = null;
  updateStatus(island);

  let outcome: ReturnType<typeof classifySubmitResponse> | { kind: 'network' };
  try {
    const response = await fetchWithTimeout(CONSULTATION_API, {
      method: 'POST',
      headers: { ...requestHeaders(true), 'Idempotency-Key': slot.key },
      body: JSON.stringify(payload),
    }, SUBMIT_TIMEOUT_MS);
    const body = await response.json().catch(() => null);
    outcome = classifySubmitResponse(response.status, body, response.headers.get('Retry-After'));
  } catch {
    outcome = { kind: 'network' };
  }

  island.submitting = false;
  if (!islands.has(island)) return;

  switch (outcome.kind) {
    case 'success':
      sharedDraft = { ...EMPTY_DRAFT };
      sharedSlot = null;
      island.receipt = outcome.receipt;
      island.replay = outcome.replay;
      island.errors = {};
      island.banner = null;
      island.view = 'success';
      render(island);
      focusFirst(island, '.rh-state-title');
      return;
    case 'validation':
      showErrors(island, outcome.errors, outcome.message);
      return;
    case 'duplicate':
      // 같은 키에 다른 내용이 묶여 있다 — 다음 신청은 새 키로 보낸다.
      sharedSlot = null;
      island.banner = { kind: 'warn', text: t('consult.error_duplicate') };
      break;
    case 'throttle': {
      const seconds = outcome.retryAfterSeconds ?? 30;
      island.throttleUntil = Date.now() + seconds * 1000;
      const wait = t('consult.error_throttle_wait').replace(':seconds', String(seconds));
      island.banner = { kind: 'warn', text: t('consult.error_throttle'), items: [wait] };
      window.setTimeout(() => {
        if (!islands.has(island)) return;
        island.throttleUntil = 0;
        updateStatus(island);
      }, seconds * 1000 + 50);
      break;
    }
    case 'disabled': {
      island.banner = { kind: 'error', text: t('consult.error_disabled') };
      updateStatus(island);
      focusFirst(island, '[data-rh-banner]');
      // 접수가 닫혔는지 다시 확인하고, 닫혔으면 입력 화면을 거둔다(fail-closed).
      const config = await loadConfig();
      if (!islands.has(island)) return;
      if (!config) {
        sharedDraft = { ...EMPTY_DRAFT };
        sharedSlot = null;
        island.config = null;
        island.view = 'unavailable';
        render(island);
        focusFirst(island, '.rh-state-title');
      }
      return;
    }
    case 'network':
      island.banner = { kind: 'error', text: t('consult.error_network') };
      break;
    default:
      island.banner = { kind: 'error', text: t('consult.error_server') };
  }
  updateStatus(island);
  focusFirst(island, '[data-rh-banner]');
}

/* ─── 수명주기 ───────────────────────────────────────── */

let nonceSeed = 0;

function mount(host: HTMLElement): void {
  host.dataset[MOUNTED] = 'true';
  const root = el('div', { class: 'rh-consult-island' });
  host.replaceChildren(root);
  nonceSeed += 1;
  const island: Island = {
    host,
    root,
    view: 'loading',
    locale: currentLocale(),
    config: null,
    errors: {},
    banner: null,
    submitting: false,
    throttleUntil: 0,
    receipt: null,
    replay: false,
    nonce: nonceSeed,
  };
  islands.add(island);
  void start(island);
}

/** DOM 변화마다 호출: 새 호스트를 붙이고, 사라진 호스트를 정리하고, 로케일 변경을 반영한다. */
export function syncConsultationIslands(): void {
  for (const island of islands) {
    if (!island.host.isConnected) {
      islands.delete(island);
      continue;
    }
    if (island.locale !== currentLocale() && !island.submitting) render(island);
  }
  document.querySelectorAll<HTMLElement>('[data-rh-consult]').forEach((host) => {
    if (host.dataset[MOUNTED] !== 'true') mount(host);
  });
  // 상담 양식이 없는 화면(상위 메뉴만 있는 문서 화면)에서도 접수 상태를 한 번 확인한다.
  if (configInFlight === null && needsIntakeState()) void loadConfig();
}
