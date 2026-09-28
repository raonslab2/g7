import '../css/main.css';
import {
    classifyFailure,
    eventLabel,
    followUpEndpoint,
    isTerminalEvent,
    latestEventSequence,
    questionText,
    resultText,
    stateLabel,
    TERMINAL_STATES,
    titleOf,
    type FailureKind,
    IdempotencyIntent,
} from './presentation';

type JsonObject = Record<string, any>;
type NoticeKind = 'error' | 'info';
type StreamMode = 'live' | 'history' | 'follow-up';

type EventFeed = {
    cursor: number;
    events: Map<number, JsonObject>;
};

class WorkspaceApiError extends Error {
    constructor(
        public readonly status: number,
        public readonly code: string,
        public readonly kind: FailureKind,
    ) {
        super(userErrorMessage(kind, status));
        this.name = 'WorkspaceApiError';
    }
}

const API = '/api/modules/raonslab-ai-workspace/requests';
let activeAbort: AbortController | null = null;
let activeRoot: HTMLElement | null = null;
let activeRouteKey = '';
let renderNonce = 0;
let streamGeneration = 0;
const eventFeeds = new Map<string, EventFeed>();
const detailDrafts = new Map<string, string>();

function escapeHtml(value: unknown): string {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function authHeaders(json = false): Record<string, string> {
    const headers: Record<string, string> = { Accept: 'application/json' };
    const token = localStorage.getItem('auth_token');
    if (token) headers.Authorization = `Bearer ${token}`;
    const xsrf = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='))?.split('=').slice(1).join('=');
    if (xsrf) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf);
    if (json) headers['Content-Type'] = 'application/json';
    return headers;
}

function userErrorMessage(kind: FailureKind, status = 0): string {
    if (kind === 'network') return '네트워크 연결을 확인한 뒤 다시 시도해 주세요.';
    if (kind === 'permission') {
        return status === 401
            ? '로그인이 필요하거나 로그인 세션이 만료되었습니다. 다시 로그인한 뒤 재시도해 주세요.'
            : '이 작업을 실행하거나 요청을 볼 권한이 없습니다.';
    }
    if (kind === 'service') return 'AI 서비스를 현재 사용할 수 없습니다. 잠시 후 다시 시도해 주세요.';
    if (status === 429) return '요청이 많아 잠시 지연되고 있습니다. 잠시 후 다시 시도해 주세요.';
    if (status === 422 || status === 400) return '입력 내용을 확인한 뒤 다시 시도해 주세요.';
    return '요청을 처리하지 못했습니다. 같은 요청으로 다시 시도할 수 있습니다.';
}

function workspaceError(error: unknown): WorkspaceApiError {
    if (error instanceof WorkspaceApiError) return error;
    return new WorkspaceApiError(0, 'NETWORK_ERROR', 'network');
}

async function api(path: string, init: RequestInit = {}): Promise<any> {
    let response: Response;
    try {
        response = await fetch(`${API}${path}`, {
            credentials: 'include',
            cache: 'no-store',
            ...init,
            headers: { ...authHeaders(Boolean(init.body)), ...(init.headers ?? {}) },
        });
    } catch {
        throw new WorkspaceApiError(0, 'NETWORK_ERROR', 'network');
    }

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const code = String(payload.code ?? payload.error?.code ?? `HTTP_${response.status}`);
        throw new WorkspaceApiError(response.status, code, classifyFailure(response.status, code));
    }
    return payload.data ?? payload;
}

function navigate(path: string): void {
    const core = (window as any).G7Core;
    if (core?.dispatch) core.dispatch({ handler: 'navigate', params: { path } });
    else window.location.assign(path);
}

function setNotice(
    root: HTMLElement,
    message: string,
    type: NoticeKind = 'info',
    retry?: () => void,
    source = 'request',
): void {
    const target = root.querySelector<HTMLElement>('[data-rai-notice]');
    if (!target) return;
    target.replaceChildren();
    const copy = document.createElement('span');
    copy.textContent = message;
    target.append(copy);
    if (retry) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'rai-notice-action';
        button.textContent = '다시 시도';
        button.addEventListener('click', retry, { once: true });
        target.append(button);
    }
    target.dataset.kind = type;
    target.dataset.source = source;
    target.hidden = false;
}

function clearNotice(root: HTMLElement, source?: string): void {
    const target = root.querySelector<HTMLElement>('[data-rai-notice]');
    if (!target || (source && target.dataset.source !== source)) return;
    target.hidden = true;
    target.replaceChildren();
    delete target.dataset.source;
}

function formatDate(value: unknown): string {
    if (!value) return '';
    const date = new Date(String(value));
    return Number.isNaN(date.getTime()) ? '' : new Intl.DateTimeFormat('ko-KR', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
}

function requestListItem(request: JsonObject): string {
    const state = String(request.state ?? request.status ?? '');
    const id = String(request.request_id ?? '');
    return `<button class="rai-request" type="button" data-request-id="${escapeHtml(id)}">
        <span class="rai-request-main"><strong>${escapeHtml(titleOf(request))}</strong><small>${escapeHtml(formatDate(request.updated_at ?? request.created_at))}</small></span>
        <span class="rai-state rai-state-${escapeHtml(state.toLowerCase())}">${escapeHtml(stateLabel(state))}</span>
    </button>`;
}

async function renderIndex(root: HTMLElement, nonce: number): Promise<void> {
    root.innerHTML = `<main class="rai-shell">
        <section class="rai-hero"><div><span class="rai-eyebrow">RAON AI</span><h1>AI 작업공간</h1><p>요청부터 결과와 후속 지시까지 하나의 작업 흐름으로 이어집니다.</p></div><span class="rai-connection" data-rai-connection role="status" aria-live="polite">서비스 확인 중</span></section>
        <div class="rai-notice" data-rai-notice role="status" hidden></div>
        <section class="rai-grid">
            <form class="rai-compose" data-rai-compose>
                <div class="rai-section-head"><div><span class="rai-kicker">NEW REQUEST</span><h2>새 작업 요청</h2></div></div>
                <label>실행 환경<select name="lane" data-rai-lane disabled><option>불러오는 중…</option></select></label>
                <label>요청 내용<textarea name="prompt" rows="8" maxlength="1000000" placeholder="필요한 결과와 확인 기준을 구체적으로 알려주세요." required></textarea></label>
                <button class="rai-primary" type="submit" disabled data-rai-submit>요청 보내기</button>
                <p class="rai-help">실제 서버 상태만 표시하며 예상 시간이나 임의 진척률은 만들지 않습니다.</p>
            </form>
            <section class="rai-history"><div class="rai-section-head"><div><span class="rai-kicker">HISTORY</span><h2>최근 작업</h2></div><button class="rai-quiet" type="button" data-rai-refresh>새로고침</button></div><div class="rai-list" data-rai-list><div class="rai-loading">최근 작업을 불러오는 중입니다.</div></div></section>
        </section>
    </main>`;

    const load = async (): Promise<void> => {
        const connection = root.querySelector<HTMLElement>('[data-rai-connection]')!;
        connection.textContent = '서비스 확인 중';
        try {
            const [capabilities, history] = await Promise.all([api('/capabilities'), api('')]);
            if (nonce !== renderNonce) return;
            const project = (capabilities.projects ?? []).find((item: JsonObject) => item.project_id === capabilities.project_id);
            const providers: JsonObject[] = (capabilities.providers ?? []).flatMap((item: JsonObject) =>
                Array.isArray(item.profiles) ? item.profiles.map((profile: JsonObject) => ({ ...item, ...profile })) : [item]
            );
            const available = providers.filter((item) => item.available !== false && item.selectable !== false);
            const select = root.querySelector<HTMLSelectElement>('[data-rai-lane]')!;
            select.innerHTML = available.map((item) => {
                const provider = String(item.provider ?? item.name ?? '').toUpperCase();
                const profile = String(item.profile ?? item.profile_id ?? 'default');
                return `<option value="${escapeHtml(`${provider}:${profile}`)}">${escapeHtml(item.label ?? `${provider} · ${profile}`)}</option>`;
            }).join('');
            select.disabled = available.length === 0;
            const submit = root.querySelector<HTMLButtonElement>('[data-rai-submit]')!;
            submit.disabled = available.length === 0 || project?.available === false || project?.selectable === false;
            connection.textContent = submit.disabled ? '현재 실행 불가' : '서비스 연결됨';
            connection.dataset.ready = submit.disabled ? 'false' : 'true';
            if (submit.disabled) setNotice(root, '현재 사용할 수 있는 AI 실행 환경이 없습니다.', 'error');
            else clearNotice(root);
            renderHistory(root, history.requests ?? history.items ?? []);
        } catch (error) {
            if (nonce !== renderNonce) return;
            const failure = workspaceError(error);
            connection.textContent = failure.kind === 'permission' ? '사용 권한 확인 필요' : failure.kind === 'network' ? '네트워크 연결 실패' : 'AI 서비스 사용 불가';
            connection.dataset.ready = 'false';
            renderHistory(root, []);
            setNotice(root, failure.message, 'error', () => void load());
        }
    };

    const submissionIntent = new IdempotencyIntent();
    let retrySubmission = false;
    const compose = root.querySelector<HTMLFormElement>('[data-rai-compose]');
    compose?.addEventListener('input', () => { submissionIntent.changed(); });
    compose?.addEventListener('change', () => { submissionIntent.changed(); });
    compose?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget as HTMLFormElement;
        const submit = root.querySelector<HTMLButtonElement>('[data-rai-submit]')!;
        const data = new FormData(form);
        const [provider, profile] = String(data.get('lane') ?? '').split(':', 2);
        const prompt = String(data.get('prompt') ?? '');
        const fingerprint = JSON.stringify([provider, profile, prompt]);
        const idempotencyKey = submissionIntent.begin(fingerprint, retrySubmission);
        retrySubmission = false;
        submit.disabled = true;
        submit.textContent = '제출 중…';
        try {
            const request = await api('', {
                method: 'POST',
                body: JSON.stringify({ provider, profile, prompt, attachment_ids: [], idempotency_key: idempotencyKey }),
            });
            submissionIntent.succeeded();
            navigate(`/ai/requests/${encodeURIComponent(request.request_id)}`);
        } catch (error) {
            const failure = workspaceError(error);
            setNotice(root, failure.message, 'error', () => { retrySubmission = true; form.requestSubmit(); });
            submit.disabled = false;
            submit.textContent = '요청 보내기';
        }
    });
    root.querySelector('[data-rai-refresh]')?.addEventListener('click', async () => {
        try {
            renderHistory(root, (await api('')).requests ?? []);
            clearNotice(root);
        } catch (error) {
            const failure = workspaceError(error);
            setNotice(root, failure.message, 'error', () => root.querySelector<HTMLButtonElement>('[data-rai-refresh]')?.click());
        }
    });

    await load();
}

function renderHistory(root: HTMLElement, requests: JsonObject[]): void {
    const list = root.querySelector<HTMLElement>('[data-rai-list]');
    if (!list) return;
    list.innerHTML = requests.length ? requests.map(requestListItem).join('') : '<div class="rai-empty"><strong>아직 요청이 없습니다.</strong><span>첫 작업을 요청하면 진행 상태와 결과가 여기에 쌓입니다.</span></div>';
    list.querySelectorAll<HTMLElement>('[data-request-id]').forEach((button) => button.addEventListener('click', () => navigate(`/ai/requests/${encodeURIComponent(button.dataset.requestId ?? '')}`)));
}

function feedFor(requestId: string): EventFeed {
    const existing = eventFeeds.get(requestId);
    if (existing) return existing;
    const created = { cursor: 0, events: new Map<number, JsonObject>() };
    eventFeeds.set(requestId, created);
    return created;
}

function eventCountLabel(count: number): string {
    return count ? `${count}건` : '아직 없음';
}

function paintDetail(root: HTMLElement, request: JsonObject, requestId: string): void {
    const state = String(request.state ?? request.status ?? '').toUpperCase();
    const output = resultText(request);
    const terminal = TERMINAL_STATES.has(state);
    const storedQuestion = questionText(request.question);
    const waitingQuestion = state === 'WAITING_USER'
        ? (storedQuestion || 'AI가 계속하기 위한 답변을 기다리고 있습니다.')
        : '';
    const prompt = String(request.prompt ?? request.original_prompt ?? '');
    const draft = detailDrafts.get(requestId) ?? '';
    const feed = feedFor(requestId);
    const previousTextarea = root.querySelector<HTMLTextAreaElement>('[data-rai-followup-text]');
    const restoreFocus = previousTextarea === document.activeElement;
    const selectionStart = previousTextarea?.selectionStart ?? draft.length;
    const selectionEnd = previousTextarea?.selectionEnd ?? draft.length;
    const detail = root.querySelector<HTMLElement>('[data-rai-detail]')!;
    const disclosureState = new Map(Array.from(detail.querySelectorAll<HTMLDetailsElement>('[data-rai-disclosure]'))
        .map((item) => [item.dataset.raiDisclosure ?? '', item.open]));
    const active = document.activeElement instanceof HTMLElement && detail.contains(document.activeElement)
        ? document.activeElement.dataset.raiFocus ?? ''
        : '';
    // A prior turn's result can remain in an upstream row while a new turn is
    // running. Only terminal state may present it as the current result.
    const visibleOutput = terminal ? output : '';
    const resultSection = `<section class="rai-panel rai-result" data-rai-result><h2>${terminal ? '결과' : '현재 결과'}</h2>${visibleOutput ? `<pre>${escapeHtml(visibleOutput)}</pre>` : `<div class="rai-empty"><span>${terminal ? '표시할 결과 요약이 없습니다.' : '새 실행이 완료되면 이곳에 결과가 표시됩니다.'}</span></div>`}</section>`;
    const questionSection = waitingQuestion
        ? `<section class="rai-panel rai-attention" aria-labelledby="rai-question-title"><h2 id="rai-question-title">답변이 필요합니다</h2><pre>${escapeHtml(waitingQuestion)}</pre><p>아래 입력창에 답변하면 같은 요청에서 계속됩니다.</p></section>`
        : '';
    const resume = followUpEndpoint(state) === 'resume';
    const label = state === 'WAITING_USER' ? '질문에 답변' : resume ? '중단된 작업에 이어서 지시' : '같은 요청에 후속 지시';
    const buttonLabel = state === 'WAITING_USER' ? '답변 보내기' : resume ? '이어서 실행' : '후속 지시 보내기';

    detail.innerHTML = `<div class="rai-detail-head"><div><span class="rai-kicker">REQUEST</span><h1>${escapeHtml(titleOf(request))}</h1><p>${escapeHtml(formatDate(request.created_at))} · ${escapeHtml(request.provider ?? '')}${request.profile ? ` / ${escapeHtml(request.profile)}` : ''}</p></div><span class="rai-state rai-state-${escapeHtml(state.toLowerCase())}" role="status" aria-live="polite">${escapeHtml(stateLabel(state))}</span></div>
        ${terminal ? resultSection : ''}
        ${questionSection}
        <section class="rai-detail-grid rai-detail-context" aria-label="요청 및 진행 정보">
            <details class="rai-panel rai-disclosure" data-rai-disclosure="prompt"${(disclosureState.get('prompt') ?? (!terminal && prompt.length <= 500)) ? ' open' : ''}><summary data-rai-focus="prompt-summary"><span>요청 원문</span><small>${prompt.length.toLocaleString('ko-KR')}자</small></summary><pre>${escapeHtml(prompt)}</pre></details>
            <details class="rai-panel rai-disclosure" data-rai-disclosure="events"${disclosureState.get('events') ? ' open' : ''}><summary data-rai-focus="events-summary"><span>진행 이력</span><small data-rai-event-count>${eventCountLabel(feed.events.size)}</small></summary><div class="rai-events" data-rai-events></div></details>
        </section>
        ${!terminal ? resultSection : ''}
        <form class="rai-followup" data-rai-followup><label>${label}<textarea name="text" rows="4" required data-rai-followup-text data-rai-focus="followup-text" placeholder="기존 맥락을 이어서 요청할 내용을 입력하세요.">${escapeHtml(draft)}</textarea></label><button class="rai-primary" type="submit" data-rai-focus="followup-submit">${buttonLabel}</button></form>`;
    paintEvents(root, [...feed.events.values()]);

    if (restoreFocus) {
        const next = root.querySelector<HTMLTextAreaElement>('[data-rai-followup-text]');
        next?.focus({ preventScroll: true });
        next?.setSelectionRange(Math.min(selectionStart, next.value.length), Math.min(selectionEnd, next.value.length));
    } else if (active) {
        root.querySelector<HTMLElement>(`[data-rai-focus="${active}"]`)?.focus({ preventScroll: true });
    }
}

async function renderDetail(root: HTMLElement, requestId: string, nonce: number): Promise<void> {
    root.innerHTML = `<main class="rai-shell"><button class="rai-back" type="button" data-rai-back>← 작업 목록</button><div class="rai-notice" data-rai-notice role="status" hidden></div><section class="rai-detail" data-rai-detail><div class="rai-loading">요청 상태를 불러오는 중입니다.</div></section></main>`;
    root.querySelector('[data-rai-back]')?.addEventListener('click', () => navigate('/ai'));
    let currentRequest: JsonObject | null = null;
    let pendingPaint: JsonObject | null = null;
    let composing = false;
    const followUpIntent = new IdempotencyIntent();
    let retryFollowUp = false;

    const applyRequest = (request: JsonObject): void => {
        currentRequest = request;
        if (composing) {
            pendingPaint = request;
            return;
        }
        paintDetail(root, request, requestId);
    };

    const load = async (): Promise<JsonObject | null> => {
        try {
            const request = await api(`/${encodeURIComponent(requestId)}`);
            if (nonce !== renderNonce) return null;
            applyRequest(request);
            clearNotice(root, 'request');
            return request;
        } catch (error) {
            if (nonce !== renderNonce) return null;
            const failure = workspaceError(error);
            setNotice(root, failure.message, 'error', () => void load(), 'request');
            return null;
        }
    };

    root.addEventListener('input', (event) => {
        const target = event.target;
        if (target instanceof HTMLTextAreaElement && target.matches('[data-rai-followup-text]')) {
            detailDrafts.set(requestId, target.value);
            followUpIntent.changed();
        }
    });
    root.addEventListener('compositionstart', (event) => {
        if (event.target instanceof HTMLTextAreaElement && event.target.matches('[data-rai-followup-text]')) composing = true;
    });
    root.addEventListener('compositionend', (event) => {
        if (!(event.target instanceof HTMLTextAreaElement) || !event.target.matches('[data-rai-followup-text]')) return;
        composing = false;
        detailDrafts.set(requestId, event.target.value);
        if (pendingPaint) {
            const request = pendingPaint;
            pendingPaint = null;
            applyRequest(request);
        }
    });

    root.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-rai-followup]') || !currentRequest) return;
        event.preventDefault();
        const textarea = form.querySelector<HTMLTextAreaElement>('[data-rai-followup-text]')!;
        const text = textarea.value.trim();
        if (!text) return;
        detailDrafts.set(requestId, textarea.value);
        const button = form.querySelector<HTMLButtonElement>('button')!;
        const originalLabel = button.textContent ?? '보내기';
        const baseline = Math.max(feedFor(requestId).cursor, latestEventSequence(currentRequest));
        const endpoint = followUpEndpoint(currentRequest.state ?? currentRequest.status);
        const fingerprint = JSON.stringify([endpoint, text]);
        const idempotencyKey = followUpIntent.begin(fingerprint, retryFollowUp);
        retryFollowUp = false;
        stopActiveStream();
        button.disabled = true;
        textarea.disabled = true;
        button.textContent = '접수 확인 중…';
        try {
            const next = await api(`/${encodeURIComponent(requestId)}/${endpoint}`, {
                method: 'POST',
                body: JSON.stringify({ text, attachment_ids: [], idempotency_key: idempotencyKey }),
            });
            if (nonce !== renderNonce) return;
            detailDrafts.delete(requestId);
            followUpIntent.succeeded();
            applyRequest(next);
            clearNotice(root);
            startEventStream(root, requestId, nonce, load, baseline, 'follow-up');
        } catch (error) {
            const failure = workspaceError(error);
            setNotice(root, failure.message, 'error', () => { retryFollowUp = true; form.requestSubmit(); }, 'request');
            button.disabled = false;
            textarea.disabled = false;
            button.textContent = originalLabel;
            textarea.focus();
        }
    });

    const first = await load();
    if (!first || nonce !== renderNonce) return;
    const state = String(first.state ?? first.status ?? '').toUpperCase();
    startEventStream(root, requestId, nonce, load, feedFor(requestId).cursor, TERMINAL_STATES.has(state) ? 'history' : 'live');
}

function stopActiveStream(): void {
    streamGeneration += 1;
    activeAbort?.abort();
    activeAbort = null;
}

function startEventStream(root: HTMLElement, requestId: string, nonce: number, reload: () => Promise<JsonObject | null>, after: number, mode: StreamMode): void {
    stopActiveStream();
    const generation = streamGeneration;
    const controller = new AbortController();
    activeAbort = controller;
    void streamEvents(root, requestId, nonce, reload, Math.max(0, after), mode, generation, controller.signal);
}

async function streamEvents(
    root: HTMLElement,
    requestId: string,
    nonce: number,
    reload: () => Promise<JsonObject | null>,
    initialAfter: number,
    mode: StreamMode,
    generation: number,
    signal: AbortSignal,
): Promise<void> {
    const feed = feedFor(requestId);
    feed.cursor = Math.max(feed.cursor, initialAfter);
    let retries = 0;
    let reloadTimer: number | undefined;
    const queueReload = (): void => {
        if (reloadTimer !== undefined) window.clearTimeout(reloadTimer);
        reloadTimer = window.setTimeout(() => {
            reloadTimer = undefined;
            void reload();
        }, 150);
    };
    while (!signal.aborted && nonce === renderNonce && generation === streamGeneration) {
        try {
            const response = await fetch(`${API}/${encodeURIComponent(requestId)}/events?after=${feed.cursor}`, {
                credentials: 'include', cache: 'no-store', headers: { ...authHeaders(), Accept: 'text/event-stream' }, signal,
            });
            if (!response.ok || !response.body) {
                const code = `HTTP_${response.status}`;
                throw new WorkspaceApiError(response.status, code, classifyFailure(response.status, code));
            }
            retries = 0;
            clearNotice(root, 'stream');
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let eventId = '';
            let data: string[] = [];

            const dispatch = async (): Promise<boolean> => {
                if (!data.length) return false;
                const serialized = data.join('\n');
                data = [];
                let event: JsonObject;
                try {
                    event = JSON.parse(serialized) as JsonObject;
                } catch {
                    return false;
                }
                const sequence = Number(event.sequence ?? eventId ?? 0);
                eventId = '';
                if (!Number.isSafeInteger(sequence) || sequence <= 0) return false;
                feed.cursor = Math.max(feed.cursor, sequence);
                if (feed.events.has(sequence)) return false;
                feed.events.set(sequence, { ...event, sequence });
                if (feed.events.size > 100) feed.events.delete(Math.min(...feed.events.keys()));
                paintEvents(root, [...feed.events.values()]);
                if (!isTerminalEvent(event) || sequence <= initialAfter) {
                    queueReload();
                    return false;
                }
                if (reloadTimer !== undefined) {
                    window.clearTimeout(reloadTimer);
                    reloadTimer = undefined;
                }
                const current = await reload();
                return Boolean(current && TERMINAL_STATES.has(String(current.state ?? current.status ?? '').toUpperCase()));
            };

            while (!signal.aborted) {
                const { value, done } = await reader.read();
                buffer += decoder.decode(value ?? new Uint8Array(), { stream: !done });
                const lines = buffer.split(/\r?\n/);
                buffer = lines.pop() ?? '';
                let terminalObserved = false;
                for (const line of lines) {
                    if (!line) terminalObserved = await dispatch() || terminalObserved;
                    else if (line.startsWith('id:')) eventId = line.slice(3).trim();
                    else if (line.startsWith('data:')) data.push(line.slice(5).trimStart());
                }
                if (terminalObserved) return;
                if (done) {
                    if (buffer.startsWith('data:')) data.push(buffer.slice(5).trimStart());
                    if (await dispatch()) return;
                    break;
                }
            }
            if (mode === 'history') return;
        } catch (error) {
            if (signal.aborted || nonce !== renderNonce || generation !== streamGeneration) return;
            retries += 1;
            const failure = workspaceError(error);
            const message = failure.kind === 'permission'
                ? failure.message
                : '실시간 갱신이 잠시 끊겼습니다. 저장된 이벤트 다음부터 자동으로 다시 시도합니다.';
            setNotice(root, message, failure.kind === 'permission' ? 'error' : 'info', () => {
                startEventStream(root, requestId, nonce, reload, feed.cursor, mode);
            }, 'stream');
            if (failure.kind === 'permission' || mode === 'history') return;
        }
        const wait = Math.min(1000 * 2 ** Math.min(retries, 3), 8000);
        await new Promise<void>((resolve) => {
            const timer = window.setTimeout(resolve, wait);
            signal.addEventListener('abort', () => { window.clearTimeout(timer); resolve(); }, { once: true });
        });
    }
}

function paintEvents(root: HTMLElement, history: JsonObject[]): void {
    const events = root.querySelector<HTMLElement>('[data-rai-events]');
    const count = root.querySelector<HTMLElement>('[data-rai-event-count]');
    if (count) count.textContent = eventCountLabel(history.length);
    if (!events) return;
    events.innerHTML = '';
    if (!history.length) {
        events.innerHTML = '<div class="rai-event rai-event-empty"><span></span><p>저장된 진행 이벤트가 아직 없습니다.</p></div>';
        return;
    }
    [...history].sort((left, right) => Number(right.sequence) - Number(left.sequence)).forEach((event) => appendEvent(events, event));
}

function appendEvent(events: HTMLElement, event: JsonObject): void {
    const row = document.createElement('div');
    row.className = 'rai-event';
    const dot = document.createElement('span');
    const copy = document.createElement('p');
    copy.textContent = eventLabel(event);
    const time = document.createElement('time');
    time.textContent = formatDate(event.created_at);
    row.append(dot, copy, time);
    events.append(row);
}

function mount(): void {
    const root = document.getElementById('raon_ai_workspace');
    if (!root) return;
    const requestId = root.dataset.requestId ?? '';
    const routeKey = requestId || 'index';
    if (root === activeRoot && routeKey === activeRouteKey) return;
    stopActiveStream();
    activeRoot = root;
    activeRouteKey = routeKey;
    const nonce = ++renderNonce;
    if (requestId) void renderDetail(root, requestId, nonce);
    else void renderIndex(root, nonce);
}

new MutationObserver(mount).observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-request-id'] });
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
else mount();
