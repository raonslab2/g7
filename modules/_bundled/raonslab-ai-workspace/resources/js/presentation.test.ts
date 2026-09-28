import { describe, expect, it } from 'vitest';
import {
    classifyFailure,
    createIdempotencyKey,
    eventLabel,
    followUpEndpoint,
    hasMeaningfulQuestion,
    latestEventSequence,
    questionText,
    resultText,
    stateLabel,
    TERMINAL_STATES,
    titleOf,
} from './presentation';

describe('AI 작업 표현 계층', () => {
    it('서버 상태를 가짜 진척률 없이 사용자 의미로 표시한다', () => {
        expect(stateLabel('RUNNING')).toBe('실행 중');
        expect(stateLabel('WAITING_USER')).toBe('사용자 입력 필요');
        expect(TERMINAL_STATES.has('COMPLETED')).toBe(true);
    });

    it('내부 payload 대신 허용된 이벤트 요약만 노출한다', () => {
        expect(eventLabel({ type: 'RUNNING', payload: { command: 'secret shell' } })).toBe('AI가 작업을 수행하고 있습니다.');
        expect(eventLabel({ type: 'provider.shell', text: 'secret shell' })).toBe('작업 상태가 갱신되었습니다.');
    });

    it('명시 제목이 없으면 prompt의 안전한 앞부분을 사용한다', () => {
        expect(titleOf({ prompt: '문서 구조를 검토해 주세요.' })).toBe('문서 구조를 검토해 주세요.');
    });

    it('브라우저 native randomUUID를 우선 사용한다', () => {
        expect(createIdempotencyKey({ randomUUID: () => 'native-uuid' })).toBe('native-uuid');
    });

    it('평문 HTTP처럼 randomUUID가 없어도 UUID v4 key를 생성한다', () => {
        const key = createIdempotencyKey({
            getRandomValues(values) {
                values.fill(0);
                return values;
            },
        });

        expect(key).toBe('00000000-0000-4000-8000-000000000000');
    });

    it('terminal의 빈 질문은 사용자 입력으로 표시하지 않는다', () => {
        expect(hasMeaningfulQuestion([])).toBe(false);
        expect(hasMeaningfulQuestion({})).toBe(false);
        expect(questionText([])).toBe('');
    });

    it('WAITING_USER의 구조화 질문을 사용자가 읽을 수 있는 문장으로 표시한다', () => {
        const question = {
            method: 'request_user_input',
            params: {
                questions: [
                    { header: '배포 환경', question: '어디에 배포할까요?', options: [{ label: '스테이징', description: '검증 후 반영' }] },
                    { question: '어떤 검증을 실행할까요?' },
                ],
            },
        };

        expect(questionText(question)).toContain('배포 환경\n어디에 배포할까요?');
        expect(questionText(question)).toContain('선택: 스테이징 — 검증 후 반영');
        expect(questionText(question)).toContain('어떤 검증을 실행할까요?');
    });

    it('결과에서 내부 payload를 JSON으로 노출하지 않는다', () => {
        expect(resultText({ final_result: { summary: '완료된 고객 결과', token: 'secret', cwd: '/srv/private' } })).toBe('완료된 고객 결과');
        expect(resultText({ error: { message: '/srv/private에서 secret shell 실패', command: 'rm private' } })).toBe('작업을 완료하지 못했습니다. 안전하게 다시 시도할 수 있습니다.');
        expect(resultText({ result: { token: 'secret', payload: { path: '/srv/private' } } })).toBe('AI가 작업 결과를 반환했지만 표시할 요약이 없습니다.');
    });

    it('후속 지시는 같은 request 계약을 사용하고 중단 복구만 resume한다', () => {
        expect(followUpEndpoint('COMPLETED')).toBe('messages');
        expect(followUpEndpoint('WAITING_USER')).toBe('messages');
        expect(followUpEndpoint('RUNNING')).toBe('messages');
        expect(followUpEndpoint('INTERRUPTED')).toBe('resume');
        expect(followUpEndpoint('FAILED')).toBe('resume');
        expect(followUpEndpoint('CANCELLED')).toBe('resume');
    });

    it('새 turn의 관측을 이전 turn 마지막 순번 다음에서 시작한다', () => {
        expect(latestEventSequence({ status: { last_event_sequence: 42 } })).toBe(42);
        expect(latestEventSequence({ last_event_sequence: 7 })).toBe(7);
        expect(latestEventSequence({ status: 'COMPLETED' })).toBe(0);
    });

    it('API 사용 불가, 권한 거부, 네트워크 실패를 구분한다', () => {
        expect(classifyFailure(0, 'NETWORK_ERROR')).toBe('network');
        expect(classifyFailure(403, 'HTTP_403')).toBe('permission');
        expect(classifyFailure(503, 'AIGCS_UNAVAILABLE')).toBe('service');
        expect(classifyFailure(422, 'VALIDATION_FAILED')).toBe('request');
        expect(classifyFailure(422, 'AIGCS_HTTP_422')).toBe('request');
    });
});
