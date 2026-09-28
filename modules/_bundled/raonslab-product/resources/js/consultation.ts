/**
 * 구축 상담 접수 — DOM 과 무관한 순수 로직.
 *
 * 서버 계약(GET config / POST consultations)은 백엔드가 소유한다. 이 파일은 그 응답을
 * 해석해 화면 상태를 정하고, 알 수 없는 응답은 항상 "접수 불가"(fail-closed)로 떨어뜨린다.
 */

export const CONSULTATION_API = '/api/modules/raonslab-product/consultations';

export type JsonObject = Record<string, unknown>;

export interface ServiceOption {
  value: string;
  label: string | null;
}

export interface IntakeConfig {
  consentVersion: string;
  privacyCopy: string;
  privacyPolicyUrl: string | null;
  privacyContact: string;
  retentionNotice: string;
  serviceOptions: ServiceOption[];
}

export const DEFAULT_SERVICE_OPTIONS: ServiceOption[] = [
  { value: 'pilot', label: null },
  { value: 'build', label: null },
  { value: 'operate', label: null },
];

function asObject(value: unknown): JsonObject | null {
  return value !== null && typeof value === 'object' && !Array.isArray(value) ? (value as JsonObject) : null;
}

function asText(value: unknown): string {
  return typeof value === 'string' ? value.trim() : '';
}

function firstText(source: JsonObject, keys: string[]): string {
  for (const key of keys) {
    const text = asText(source[key]);
    if (text !== '') return text;
  }
  return '';
}

/**
 * 절대 https 주소만 링크로 허용한다. 서버와 같은 규칙이다 — 상대 경로·다른 스킴·
 * 사용자 정보가 붙은 주소는 링크로 만들지 않는다(서버가 이미 막지만 방어를 겹친다).
 */
export function safePolicyUrl(raw: string): string | null {
  if (raw === '' || !/^https:\/\//i.test(raw) || /[\s\\]/.test(raw)) return null;
  try {
    const url = new URL(raw);
    if (url.protocol !== 'https:' || url.hostname === '' || url.username !== '' || url.password !== '') return null;
    return url.href;
  } catch {
    return null;
  }
}

function parseServiceOptions(value: unknown): ServiceOption[] {
  if (!Array.isArray(value)) return DEFAULT_SERVICE_OPTIONS;
  const options: ServiceOption[] = [];
  for (const item of value) {
    if (typeof item === 'string' && item.trim() !== '') {
      options.push({ value: item.trim(), label: null });
      continue;
    }
    const entry = asObject(item);
    const optionValue = entry ? asText(entry.value ?? entry.id ?? entry.key) : '';
    if (entry && optionValue !== '') {
      const label = asText(entry.label ?? entry.name);
      options.push({ value: optionValue, label: label === '' ? null : label });
    }
  }
  return options.length > 0 ? options : DEFAULT_SERVICE_OPTIONS;
}

/**
 * config 응답 본문을 해석한다. 접수가 명시적으로 열려 있고 동의 문안과 버전이 모두 있을
 * 때만 설정을 돌려주며, 그 외에는 null(= PII 입력을 받지 않음)이다.
 */
export function parseIntakeConfig(body: unknown): IntakeConfig | null {
  const root = asObject(body);
  if (!root) return null;
  const data = asObject(root.data) ?? root;
  const intake = asObject(data.intake) ?? data;

  const enabled = intake.enabled ?? intake.intake_enabled ?? data.enabled;
  if (enabled !== true) return null;

  const privacy = asObject(intake.privacy) ?? asObject(data.privacy) ?? {};
  const merged: JsonObject = { ...intake, ...privacy };

  const consentVersion = firstText(merged, ['privacy_consent_version', 'consent_version', 'version']);
  const privacyCopy = firstText(merged, ['privacy_copy', 'copy', 'text', 'privacy_text']);
  if (consentVersion === '' || privacyCopy === '') return null;

  return {
    consentVersion,
    privacyCopy,
    privacyPolicyUrl: safePolicyUrl(firstText(merged, ['privacy_policy_url', 'policy_url', 'url'])),
    privacyContact: firstText(merged, ['privacy_contact', 'contact']),
    retentionNotice: firstText(merged, ['retention_notice', 'retention']),
    serviceOptions: parseServiceOptions(intake.service_interests ?? data.service_interests ?? intake.services),
  };
}

export interface ConsultationDraft {
  contact_name: string;
  email: string;
  company: string;
  phone: string;
  service_interest: string;
  message: string;
  privacy_consent: boolean;
}

export const EMPTY_DRAFT: ConsultationDraft = {
  contact_name: '',
  email: '',
  company: '',
  phone: '',
  service_interest: '',
  message: '',
  privacy_consent: false,
};

export type FieldName = keyof ConsultationDraft;
export type FieldErrorCode = 'required' | 'email' | 'consent';
export type FieldErrors = Partial<Record<FieldName, FieldErrorCode | string>>;

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/** 서버 검증 전 최소 확인. 서버 422 가 최종 판정이다. */
export function validateDraft(draft: ConsultationDraft): FieldErrors {
  const errors: FieldErrors = {};
  if (draft.contact_name.trim() === '') errors.contact_name = 'required';
  if (draft.email.trim() === '') errors.email = 'required';
  else if (!EMAIL_PATTERN.test(draft.email.trim())) errors.email = 'email';
  if (draft.message.trim() === '') errors.message = 'required';
  if (!draft.privacy_consent) errors.privacy_consent = 'consent';
  return errors;
}

export function buildPayload(draft: ConsultationDraft, consentVersion: string): JsonObject {
  const payload: JsonObject = {
    contact_name: draft.contact_name.trim(),
    email: draft.email.trim(),
    message: draft.message.trim(),
    privacy_consent: true,
    privacy_consent_version: consentVersion,
  };
  for (const key of ['company', 'phone', 'service_interest'] as const) {
    const value = draft[key].trim();
    if (value !== '') payload[key] = value;
  }
  return payload;
}

/** 키 순서와 무관한 결정적 지문. 같은 내용이면 같은 Idempotency-Key 를 재사용한다. */
export function payloadFingerprint(payload: JsonObject): string {
  return JSON.stringify(Object.keys(payload).sort().map((key) => [key, payload[key]]));
}

export interface IdempotencySlot {
  key: string;
  fingerprint: string;
}

/**
 * 같은 내용의 재시도(네트워크 오류·429·5xx 후)는 같은 키를 쓰고, 내용이 바뀌면 새 키를 만든다.
 * 그래야 재전송이 중복 접수가 되지 않고, 수정된 내용이 409 로 막히지 않는다.
 */
export function resolveIdempotencyKey(
  current: IdempotencySlot | null,
  fingerprint: string,
  generate: () => string,
): IdempotencySlot {
  if (current && current.fingerprint === fingerprint) return current;
  return { key: generate(), fingerprint };
}

export function createIdempotencyKey(): string {
  const cryptoApi = (globalThis as { crypto?: Crypto }).crypto;
  if (cryptoApi && typeof cryptoApi.randomUUID === 'function') return cryptoApi.randomUUID();
  const bytes = new Uint8Array(16);
  if (cryptoApi && typeof cryptoApi.getRandomValues === 'function') cryptoApi.getRandomValues(bytes);
  else for (let i = 0; i < bytes.length; i += 1) bytes[i] = Math.floor(Math.random() * 256);
  return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
}

export interface Receipt {
  reference: string;
  status: string;
  receivedAt: string;
}

export type SubmitOutcome =
  | { kind: 'success'; replay: boolean; receipt: Receipt }
  | { kind: 'validation'; errors: FieldErrors; message: string }
  | { kind: 'duplicate' }
  | { kind: 'throttle'; retryAfterSeconds: number | null }
  | { kind: 'disabled' }
  | { kind: 'server' };

const FIELD_NAMES: FieldName[] = ['contact_name', 'email', 'company', 'phone', 'service_interest', 'message', 'privacy_consent'];

function parseServerErrors(body: JsonObject | null): FieldErrors {
  const raw = asObject(body?.errors);
  const errors: FieldErrors = {};
  if (!raw) return errors;
  for (const [key, value] of Object.entries(raw)) {
    const field = (key === 'privacy_consent_version' ? 'privacy_consent' : key) as FieldName;
    if (!FIELD_NAMES.includes(field) || errors[field]) continue;
    const message = Array.isArray(value) ? asText(value[0]) : asText(value);
    errors[field] = message === '' ? 'required' : message;
  }
  return errors;
}

export function parseRetryAfter(header: string | null): number | null {
  if (!header) return null;
  const seconds = Number.parseInt(header, 10);
  if (Number.isFinite(seconds) && seconds >= 0) return Math.min(seconds, 600);
  const date = Date.parse(header);
  if (Number.isNaN(date)) return null;
  return Math.min(Math.max(0, Math.ceil((date - Date.now()) / 1000)), 600);
}

/** POST 응답을 화면 상태로 분류한다. 계약 밖의 응답은 성공으로 취급하지 않는다. */
export function classifySubmitResponse(status: number, body: unknown, retryAfter: string | null): SubmitOutcome {
  const root = asObject(body);
  if (status === 200 || status === 201) {
    const data = asObject(root?.data) ?? root ?? {};
    const reference = asText(data.reference);
    if (reference === '') return { kind: 'server' };
    return {
      kind: 'success',
      replay: status === 200,
      receipt: { reference, status: asText(data.status), receivedAt: asText(data.received_at) },
    };
  }
  if (status === 409) return { kind: 'duplicate' };
  if (status === 422) {
    return { kind: 'validation', errors: parseServerErrors(root), message: asText(root?.message) };
  }
  if (status === 429) return { kind: 'throttle', retryAfterSeconds: parseRetryAfter(retryAfter) };
  // 503 이라도 서버가 "접수 닫힘" 사유를 명시한 경우만 닫힘으로 본다. 사유가 없는 503·5xx 는
  // 저장 여부가 불확실한 일시 장애로 보고 입력과 키를 지켜 같은 키로 재시도하게 한다.
  if (status === 503 && asText(asObject(root?.errors)?.reason) === INTAKE_DISABLED_REASON) return { kind: 'disabled' };
  return { kind: 'server' };
}

export const INTAKE_DISABLED_REASON = 'intake_disabled';

/** config 조회 결과. 'closed' 는 서버가 닫힘을 확인해 준 것이고, 'error' 는 확인하지 못한 것이다. */
export type ConfigLoad =
  | { state: 'open'; config: IntakeConfig }
  | { state: 'closed' }
  | { state: 'error' };

export function classifyConfigLoad(httpOk: boolean, body: unknown): ConfigLoad {
  if (!httpOk || asObject(body) === null) return { state: 'error' };
  const config = parseIntakeConfig(body);
  return config ? { state: 'open', config } : { state: 'closed' };
}

export interface RecoveryPlan {
  /** 작성 중인 입력을 그대로 둔다 */
  keepDraft: boolean;
  /** 같은 Idempotency-Key 를 다음 전송에 다시 쓴다 */
  keepKey: boolean;
  /** 입력 화면을 거두고 "접수 불가" 화면으로 바꾼다 */
  closeForm: boolean;
}

/**
 * 전송 결과 뒤 입력·키를 어떻게 다룰지 정한다.
 *
 * 저장 여부가 불확실한 응답(네트워크 오류·5xx·429) 뒤에는 입력과 키를 모두 지킨다 —
 * 같은 키로 재시도하면 서버가 이미 저장한 접수를 200 으로 돌려주므로 중복이 생기지 않는다.
 * 입력을 비우는 것은 성공했거나, 서버가 닫힘을 명시하고 config 재확인으로도 닫힘이 확인된
 * 경우뿐이다(그때는 저장되지 않았음이 확실하다).
 */
export function planRecovery(kind: SubmitOutcome['kind'] | 'network', recheck: ConfigLoad['state'] | null = null): RecoveryPlan {
  if (kind === 'success') return { keepDraft: false, keepKey: false, closeForm: false };
  if (kind === 'duplicate') return { keepDraft: true, keepKey: false, closeForm: false };
  if (kind === 'disabled' && recheck === 'closed') return { keepDraft: false, keepKey: false, closeForm: true };
  return { keepDraft: true, keepKey: true, closeForm: false };
}

export interface SubmitGate {
  submitting: boolean;
  view: string;
  hasConfig: boolean;
  throttleUntil: number;
}

/** 전송 중(연속 클릭)·접수 불가·대기 시간 중에는 새 전송을 시작하지 않는다. */
export function canSubmit(gate: SubmitGate, now: number): boolean {
  return !gate.submitting && gate.view === 'form' && gate.hasConfig && gate.throttleUntil <= now;
}
