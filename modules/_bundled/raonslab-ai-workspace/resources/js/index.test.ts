// @vitest-environment jsdom

import { afterEach, describe, expect, it, vi } from 'vitest';

type StreamHandle = {
    push: (event: Record<string, unknown>) => void;
    close: () => void;
};

function json(data: unknown, status = 200): Response {
    return new Response(JSON.stringify({ data }), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });
}

function eventStream(): [Response, StreamHandle] {
    const encoder = new TextEncoder();
    let controller!: ReadableStreamDefaultController<Uint8Array>;
    const stream = new ReadableStream<Uint8Array>({
        start(value) { controller = value; },
    });
    return [new Response(stream, { headers: { 'Content-Type': 'text/event-stream' } }), {
        push(event) {
            controller.enqueue(encoder.encode(`id: ${event.sequence}\ndata: ${JSON.stringify(event)}\n\n`));
        },
        close() { controller.close(); },
    }];
}

describe('AI 작업공간 고객 여정', () => {
    afterEach(() => {
        document.body.replaceChildren();
        vi.restoreAllMocks();
        vi.resetModules();
    });

    it('draft, terminal result priority, cursor continuation and event dedupe stay intact', async () => {
        document.body.innerHTML = '<div id="raon_ai_workspace" data-request-id="req-ux"></div>';
        const root = document.getElementById('raon_ai_workspace')!;
        const streams: StreamHandle[] = [];
        const eventUrls: string[] = [];
        const posts: Array<{ url: string; body: Record<string, unknown> }> = [];
        let state = 'RUNNING';
        let lastSequence = 10;
        let finalResult: unknown;
        let detailGets = 0;

        vi.stubGlobal('fetch', vi.fn(async (input: RequestInfo | URL, init: RequestInit = {}) => {
            const url = String(input);
            if (url.endsWith('/events?after=0') || url.endsWith('/events?after=13')) {
                eventUrls.push(url);
                const [response, handle] = eventStream();
                streams.push(handle);
                return response;
            }
            if (url.endsWith('/messages') && init.method === 'POST') {
                posts.push({ url, body: JSON.parse(String(init.body)) });
                state = 'QUEUED';
                return json({
                    request_id: 'req-ux', provider: 'CODEX', profile: 'default', prompt: '긴 원문 '.repeat(101),
                    state, status: { last_event_sequence: 13 }, question: [], final_result: { text: '이전 turn 결과' },
                }, 202);
            }
            if (url.endsWith('/req-ux')) {
                detailGets += 1;
                return json({
                    request_id: 'req-ux', provider: 'CODEX', profile: 'default', prompt: '긴 원문 '.repeat(101),
                    state, status: { last_event_sequence: lastSequence }, question: [], final_result: finalResult,
                    created_at: '2026-09-28T00:00:00Z',
                });
            }
            throw new Error(`Unexpected fetch: ${url}`);
        }));

        await import('./index');
        await vi.waitFor(() => expect(streams).toHaveLength(1));
        const draft = root.querySelector<HTMLTextAreaElement>('[data-rai-followup-text]')!;
        draft.focus();
        draft.value = '이 문장은 상태 갱신 중에도 남아야 합니다.';
        draft.setSelectionRange(8, 8);
        draft.dispatchEvent(new Event('input', { bubbles: true }));
        draft.dispatchEvent(new CompositionEvent('compositionstart', { bubbles: true, data: '한' }));

        streams[0].push({ sequence: 11, event_type: 'RUNNING', payload: { command: 'private shell' } });
        lastSequence = 12;
        streams[0].push({ sequence: 12, event_type: 'RUNNING', payload: { token: 'private token' } });
        await new Promise((resolve) => setTimeout(resolve, 220));
        expect(detailGets).toBe(2);
        expect(draft.isConnected).toBe(true);
        expect(draft.value).toBe('이 문장은 상태 갱신 중에도 남아야 합니다.');
        draft.dispatchEvent(new CompositionEvent('compositionend', { bubbles: true, data: '한' }));
        await vi.waitFor(() => {
            expect(root.querySelector<HTMLTextAreaElement>('[data-rai-followup-text]')?.value).toBe('이 문장은 상태 갱신 중에도 남아야 합니다.');
            expect(document.activeElement).toBe(root.querySelector('[data-rai-followup-text]'));
        });
        expect(root.textContent).not.toContain('private shell');

        const eventDisclosure = root.querySelector<HTMLDetailsElement>('[data-rai-disclosure="events"]')!;
        eventDisclosure.open = true;
        eventDisclosure.querySelector<HTMLElement>('summary')!.focus();
        state = 'COMPLETED';
        lastSequence = 13;
        finalResult = { summary: '고객이 보아야 할 최종 결과', token: 'do-not-render', path: '/srv/private' };
        streams[0].push({ sequence: 13, event_type: 'REQUEST.COMPLETED', payload: { token: 'do-not-render' } });
        await vi.waitFor(() => expect(root.querySelector('[data-rai-result]')?.textContent).toContain('고객이 보아야 할 최종 결과'));

        expect(root.textContent).not.toContain('사용자 입력 필요');
        expect(root.textContent).not.toContain('연결 중');
        expect(root.textContent).not.toContain('do-not-render');
        expect(root.textContent).not.toContain('/srv/private');
        expect(root.querySelector('.rai-detail-head')?.nextElementSibling).toBe(root.querySelector('[data-rai-result]'));
        expect(root.querySelector<HTMLDetailsElement>('[data-rai-disclosure="prompt"]')?.open).toBe(false);
        expect(root.querySelector<HTMLDetailsElement>('[data-rai-disclosure="events"]')?.open).toBe(true);
        expect(document.activeElement).toBe(root.querySelector('[data-rai-focus="events-summary"]'));
        expect(root.hasAttribute('aria-live')).toBe(false);
        expect([...root.querySelectorAll('[aria-live]')].every((item) => item.matches('.rai-state'))).toBe(true);

        const followUp = root.querySelector<HTMLTextAreaElement>('[data-rai-followup-text]')!;
        followUp.value = '같은 요청에서 계속해 주세요.';
        followUp.dispatchEvent(new Event('input', { bubbles: true }));
        followUp.closest('form')!.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        await vi.waitFor(() => expect(streams).toHaveLength(2));
        expect(root.querySelector('[data-rai-result]')?.textContent).not.toContain('이전 turn 결과');

        expect(posts).toHaveLength(1);
        expect(posts[0].url).toContain('/req-ux/messages');
        expect(posts[0].body.text).toBe('같은 요청에서 계속해 주세요.');
        expect(eventUrls[1]).toContain('events?after=13');

        streams[1].push({ sequence: 13, event_type: 'REQUEST.COMPLETED', payload: {} });
        state = 'RUNNING';
        lastSequence = 14;
        finalResult = undefined;
        streams[1].push({ sequence: 14, event_type: 'RUNNING', payload: {} });
        await vi.waitFor(() => expect(root.textContent).toContain('AI가 작업을 수행하고 있습니다.'));

        state = 'COMPLETED';
        lastSequence = 15;
        finalResult = { text: '후속 turn 완료' };
        streams[1].push({ sequence: 15, event_type: 'REQUEST.COMPLETED', payload: {} });
        await vi.waitFor(() => expect(root.querySelector('[data-rai-result]')?.textContent).toContain('후속 turn 완료'));
        expect(root.querySelector('[data-rai-event-count]')?.textContent).toBe('5건');
        streams.forEach((stream) => {
            try { stream.close(); } catch { /* already detached */ }
        });
    });
});
