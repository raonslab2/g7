import '../css/main.css';
import { eventLabel, stateLabel, TERMINAL_STATES, titleOf } from './presentation';

type JsonObject = Record<string, any>;

const API = '/api/modules/raonslab-ai-workspace/requests';
let activeAbort: AbortController | null = null;
let activeRoot: HTMLElement | null = null;
let renderNonce = 0;
const eventHistory = new Map<string, JsonObject[]>();

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

async function api(path: string, init: RequestInit = {}): Promise<any> {
    const response = await fetch(`${API}${path}`, {
        credentials: 'include',
        cache: 'no-store',
        ...init,
        headers: { ...authHeaders(Boolean(init.body)), ...(init.headers ?? {}) },
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const message = payload.message ?? payload.error?.message ?? `요청 실패 (${response.status})`;
        throw new Error(String(message));
    }
    return payload.data ?? payload;
}

function navigate(path: string): void {
    const core = (window as any).G7Core;
    if (core?.dispatch) core.dispatch({ handler: 'navigate', params: { path } });
    else window.location.assign(path);
}

function setNotice(root: HTMLElement, message: string, type: 'error' | 'info' = 'info'): void {
    const target = root.querySelector<HTMLElement>('[data-rai-notice]');
    if (!target) return;
    target.textContent = message;
    target.dataset.kind = type;
    target.hidden = false;
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
        <section class="rai-hero"><div><span class="rai-eyebrow">RAON AI</span><h1>AI 작업공간</h1><p>요청부터 결과와 후속 지시까지 하나의 작업 흐름으로 이어집니다.</p></div><span class="rai-connection" data-rai-connection>연결 확인 중</span></section>
        <div class="rai-notice" data-rai-notice hidden></div>
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
        const connection = root.querySelector<HTMLElement>('[data-rai-connection]')!;
        connection.textContent = submit.disabled ? '현재 실행 불가' : '서비스 연결됨';
        connection.dataset.ready = submit.disabled ? 'false' : 'true';
        if (submit.disabled) setNotice(root, project?.reason ?? '현재 사용할 수 있는 AI 실행 환경이 없습니다.', 'error');
        renderHistory(root, history.requests ?? []);
    } catch (error) {
        if (nonce !== renderNonce) return;
        root.querySelector<HTMLElement>('[data-rai-connection]')!.textContent = '연결 실패';
        renderHistory(root, []);
        setNotice(root, error instanceof Error ? error.message : 'AI 서비스 상태를 확인할 수 없습니다.', 'error');
    }

    root.querySelector('[data-rai-compose]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget as HTMLFormElement;
        const submit = root.querySelector<HTMLButtonElement>('[data-rai-submit]')!;
        const data = new FormData(form);
        const [provider, profile] = String(data.get('lane') ?? '').split(':', 2);
        submit.disabled = true;
        submit.textContent = '제출 중…';
        try {
            const request = await api('', {
                method: 'POST',
                body: JSON.stringify({
                    provider,
                    profile,
                    prompt: String(data.get('prompt') ?? ''),
                    attachment_ids: [],
                    idempotency_key: crypto.randomUUID(),
                }),
            });
            navigate(`/ai/requests/${encodeURIComponent(request.request_id)}`);
        } catch (error) {
            setNotice(root, error instanceof Error ? error.message : '요청을 제출하지 못했습니다.', 'error');
            submit.disabled = false;
            submit.textContent = '요청 보내기';
        }
    });
    root.querySelector('[data-rai-refresh]')?.addEventListener('click', async () => {
        try { renderHistory(root, (await api('')).requests ?? []); }
        catch (error) { setNotice(root, error instanceof Error ? error.message : '목록을 갱신하지 못했습니다.', 'error'); }
    });
}

function renderHistory(root: HTMLElement, requests: JsonObject[]): void {
    const list = root.querySelector<HTMLElement>('[data-rai-list]');
    if (!list) return;
    list.innerHTML = requests.length ? requests.map(requestListItem).join('') : '<div class="rai-empty"><strong>아직 요청이 없습니다.</strong><span>첫 작업을 요청하면 진행 상태와 결과가 여기에 쌓입니다.</span></div>';
    list.querySelectorAll<HTMLElement>('[data-request-id]').forEach((button) => button.addEventListener('click', () => navigate(`/ai/requests/${encodeURIComponent(button.dataset.requestId ?? '')}`)));
}

function resultText(request: JsonObject): string {
    const value = request.final_result ?? request.result ?? request.error;
    if (typeof value === 'string') return value;
    if (value == null) return '';
    if (typeof value.text === 'string') return value.text;
    if (typeof value.message === 'string') return value.message;
    return JSON.stringify(value, null, 2);
}

async function renderDetail(root: HTMLElement, requestId: string, nonce: number): Promise<void> {
    root.innerHTML = `<main class="rai-shell"><button class="rai-back" type="button" data-rai-back>← 작업 목록</button><div class="rai-notice" data-rai-notice hidden></div><section class="rai-detail" data-rai-detail><div class="rai-loading">요청 상태를 불러오는 중입니다.</div></section></main>`;
    root.querySelector('[data-rai-back]')?.addEventListener('click', () => navigate('/ai'));
    const load = async (): Promise<JsonObject | null> => {
        try {
            const request = await api(`/${encodeURIComponent(requestId)}`);
            if (nonce !== renderNonce) return null;
            paintDetail(root, request, requestId);
            return request;
        } catch (error) {
            setNotice(root, error instanceof Error ? error.message : '요청을 불러오지 못했습니다.', 'error');
            return null;
        }
    };
    const first = await load();
    if (!first || nonce !== renderNonce) return;
    bindFollowUp(root, requestId, load);
    if (!TERMINAL_STATES.has(String(first.state))) streamEvents(root, requestId, nonce, load);
}

function paintDetail(root: HTMLElement, request: JsonObject, requestId: string): void {
    const state = String(request.state ?? request.status ?? '');
    const output = resultText(request);
    const question = request.question ? (typeof request.question === 'string' ? request.question : JSON.stringify(request.question, null, 2)) : '';
    root.querySelector<HTMLElement>('[data-rai-detail]')!.innerHTML = `<div class="rai-detail-head"><div><span class="rai-kicker">REQUEST</span><h1>${escapeHtml(titleOf(request))}</h1><p>${escapeHtml(formatDate(request.created_at))} · ${escapeHtml(request.provider ?? '')}${request.profile ? ` / ${escapeHtml(request.profile)}` : ''}</p></div><span class="rai-state rai-state-${escapeHtml(state.toLowerCase())}">${escapeHtml(stateLabel(state))}</span></div>
        <div class="rai-detail-grid"><section class="rai-panel"><h2>요청 내용</h2><pre>${escapeHtml(request.prompt ?? request.original_prompt ?? '')}</pre></section><section class="rai-panel"><h2>진행 이벤트</h2><div class="rai-events" data-rai-events></div></section></div>
        ${question ? `<section class="rai-panel rai-attention"><h2>사용자 입력 필요</h2><pre>${escapeHtml(question)}</pre></section>` : ''}
        <section class="rai-panel rai-result"><h2>결과</h2>${output ? `<pre>${escapeHtml(output)}</pre>` : '<div class="rai-empty"><span>완료되면 이곳에 결과가 표시됩니다.</span></div>'}</section>
        <form class="rai-followup" data-rai-followup><label>${state === 'WAITING_USER' ? '답변 또는 계속할 지시' : '같은 요청에 후속 지시'}<textarea name="text" rows="4" required placeholder="기존 맥락을 이어서 요청할 내용을 입력하세요."></textarea></label><button class="rai-primary" type="submit">${state === 'WAITING_USER' || state === 'INTERRUPTED' ? '이어서 실행' : '후속 지시 보내기'}</button><input type="hidden" name="resume" value="${state === 'WAITING_USER' || state === 'INTERRUPTED' ? '1' : '0'}"></form>`;
    paintEvents(root, eventHistory.get(requestId) ?? []);
}

function bindFollowUp(root: HTMLElement, requestId: string, reload: () => Promise<JsonObject | null>): void {
    const bind = () => {
        const form = root.querySelector<HTMLFormElement>('[data-rai-followup]');
        if (!form || form.dataset.bound === 'true') return;
        form.dataset.bound = 'true';
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const data = new FormData(form);
            const button = form.querySelector<HTMLButtonElement>('button')!;
            button.disabled = true;
            try {
                const endpoint = data.get('resume') === '1' ? 'resume' : 'messages';
                await api(`/${encodeURIComponent(requestId)}/${endpoint}`, {
                    method: 'POST',
                    body: JSON.stringify({ text: String(data.get('text') ?? ''), attachment_ids: [], idempotency_key: crypto.randomUUID() }),
                });
                await reload();
                bind();
            } catch (error) {
                setNotice(root, error instanceof Error ? error.message : '후속 지시를 보내지 못했습니다.', 'error');
                button.disabled = false;
            }
        });
    };
    bind();
    new MutationObserver(bind).observe(root, { childList: true, subtree: true });
}

async function streamEvents(root: HTMLElement, requestId: string, nonce: number, reload: () => Promise<JsonObject | null>): Promise<void> {
    // 새 화면에서는 0부터 재생해 서버에 저장된 이벤트를 권위 있는 이력으로 사용한다.
    // 연결이 끊어진 뒤에는 같은 루프의 cursor를 유지해 누락 없이 이어 받는다.
    let after = 0;
    eventHistory.set(requestId, []);
    activeAbort?.abort();
    activeAbort = new AbortController();
    const signal = activeAbort.signal;
    let retries = 0;
    while (!signal.aborted && nonce === renderNonce) {
        try {
            const response = await fetch(`${API}/${encodeURIComponent(requestId)}/events?after=${after}`, { credentials: 'include', cache: 'no-store', headers: { ...authHeaders(), Accept: 'text/event-stream' }, signal });
            if (!response.ok || !response.body) throw new Error(`이벤트 연결 실패 (${response.status})`);
            retries = 0;
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            while (!signal.aborted) {
                const { value, done } = await reader.read();
                buffer += decoder.decode(value ?? new Uint8Array(), { stream: !done });
                const blocks = buffer.split(/\r?\n\r?\n/);
                buffer = blocks.pop() ?? '';
                for (const block of blocks) {
                    const id = block.split(/\r?\n/).find((line) => line.startsWith('id:'))?.slice(3).trim();
                    const lines = block.split(/\r?\n/).filter((line) => line.startsWith('data:')).map((line) => line.slice(5).trimStart());
                    if (!lines.length) continue;
                    const event = JSON.parse(lines.join('\n')) as JsonObject;
                    const sequence = Number(event.sequence ?? id ?? 0);
                    if (Number.isSafeInteger(sequence) && sequence > after) {
                        after = sequence;
                        const history = eventHistory.get(requestId) ?? [];
                        eventHistory.set(requestId, [...history, event].slice(-100));
                        const current = await reload();
                        paintEvents(root, eventHistory.get(requestId) ?? []);
                        bindFollowUp(root, requestId, reload);
                        if (current && TERMINAL_STATES.has(String(current.state))) return;
                    }
                }
                if (done) break;
            }
        } catch (error) {
            if (signal.aborted) return;
            retries += 1;
            setNotice(root, `이벤트 연결을 복구하고 있습니다. (${retries})`, 'info');
        }
        await new Promise((resolve) => setTimeout(resolve, Math.min(1000 * 2 ** retries, 8000)));
    }
}

function paintEvents(root: HTMLElement, history: JsonObject[]): void {
    const events = root.querySelector<HTMLElement>('[data-rai-events]');
    if (!events) return;
    events.innerHTML = '';
    if (!history.length) {
        events.innerHTML = '<div class="rai-event"><span></span><p>서버에 저장된 이벤트에 연결하는 중입니다.</p></div>';
        return;
    }
    [...history].reverse().forEach((event) => appendEvent(events, event));
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
    if (!root || root === activeRoot) return;
    activeAbort?.abort();
    activeRoot = root;
    const nonce = ++renderNonce;
    const requestId = root.dataset.requestId ?? '';
    if (requestId) renderDetail(root, requestId, nonce);
    else renderIndex(root, nonce);
}

new MutationObserver(mount).observe(document.documentElement, { childList: true, subtree: true });
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
else mount();
