import { describe, expect, it } from 'vitest';
import { eventLabel, stateLabel, TERMINAL_STATES, titleOf } from './presentation';

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
});
