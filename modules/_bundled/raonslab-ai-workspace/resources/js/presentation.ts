export const TERMINAL_STATES = new Set(['COMPLETED', 'FAILED', 'CANCELLED', 'INTERRUPTED']);

type IdempotencyCrypto = {
    randomUUID?: () => string;
    getRandomValues?: (values: Uint8Array) => Uint8Array;
};

/**
 * Request idempotency key that also works on a plain-HTTP review ingress.
 * `crypto.randomUUID()` is secure-context-only in some browsers, while
 * `getRandomValues()` remains available. The final Math.random branch is a
 * compatibility fallback for an idempotency identifier, not a credential.
 */
export function createIdempotencyKey(
    source: IdempotencyCrypto | null = typeof globalThis.crypto === 'undefined'
        ? null
        : globalThis.crypto as unknown as IdempotencyCrypto,
): string {
    if (typeof source?.randomUUID === 'function') {
        return source.randomUUID();
    }

    const bytes = new Uint8Array(16);
    if (typeof source?.getRandomValues === 'function') {
        source.getRandomValues(bytes);
    } else {
        for (let index = 0; index < bytes.length; index += 1) {
            bytes[index] = Math.floor(Math.random() * 256);
        }
    }

    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (value) => value.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

export function stateLabel(state: unknown): string {
    const labels: Record<string, string> = {
        ACCEPTED: '접수됨',
        QUEUED: '대기 중',
        STARTING: '준비 중',
        RUNNING: '실행 중',
        WAITING_USER: '사용자 입력 필요',
        CANCELLING: '취소 중',
        COMPLETED: '완료',
        FAILED: '실패',
        CANCELLED: '취소됨',
        INTERRUPTED: '중단됨',
    };
    const normalized = String(state ?? '').toUpperCase();
    return labels[normalized] ?? (normalized || '상태 확인 중');
}

export function eventLabel(event: Record<string, unknown>): string {
    const raw = String(event.event_type ?? event.type ?? event.status ?? '').toUpperCase();
    const payload = typeof event.payload === 'object' && event.payload !== null
        ? event.payload as Record<string, unknown>
        : {};
    const persistedState = String(payload.state ?? '').toUpperCase();
    const labels: Record<string, string> = {
        REQUEST_CREATED: '요청이 접수되었습니다.',
        ACCEPTED: '요청이 접수되었습니다.',
        QUEUED: '실행 순서를 기다리고 있습니다.',
        STARTING: '실행 환경을 준비하고 있습니다.',
        RUNNING: 'AI가 작업을 수행하고 있습니다.',
        WAITING_USER: '계속하려면 사용자 입력이 필요합니다.',
        COMPLETED: '작업이 완료되었습니다.',
        FAILED: '작업을 완료하지 못했습니다.',
        CANCELLED: '작업이 취소되었습니다.',
        INTERRUPTED: '작업이 중단되었습니다.',
    };
    if (raw === 'REQUEST.STATE' && persistedState) {
        return `${stateLabel(persistedState)} 상태로 변경되었습니다.`;
    }
    if (raw === 'REQUEST.ACCEPTED') return labels.ACCEPTED;
    if (raw === 'REQUEST.COMPLETED') return labels.COMPLETED;
    if (raw === 'REQUEST.FAILED') return labels.FAILED;
    return labels[raw] ?? '작업 상태가 갱신되었습니다.';
}

export function titleOf(request: Record<string, unknown>): string {
    const explicit = String(request.title ?? '').trim();
    if (explicit) return explicit;
    const prompt = String(request.prompt ?? request.original_prompt ?? '').trim();
    return prompt ? prompt.slice(0, 72) : 'AI 작업';
}
