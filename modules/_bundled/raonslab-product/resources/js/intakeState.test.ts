import { describe, expect, it } from 'vitest';
import { currentIntakeState, intakeStateOf, needsIntakeState, publishIntakeState } from './intakeState';

function fakeRoot(): HTMLElement {
  return { dataset: {} } as unknown as HTMLElement;
}

function fakeDocument(dataset: Record<string, string>, hasGate: boolean): Document {
  return { documentElement: { dataset }, querySelector: () => (hasGate ? {} : null) } as unknown as Document;
}

describe('상담 접수 상태(클릭 전 표시)', () => {
  it('해석된 설정이 있을 때만 열림이다(fail-closed)', () => {
    expect(intakeStateOf({ consentVersion: 'v1' })).toBe('open');
    expect(intakeStateOf(null)).toBe('closed');
    expect(intakeStateOf(undefined)).toBe('closed');
  });

  it('확인 전(속성 없음)과 알 수 없는 값은 닫힘으로 읽는다', () => {
    const root = fakeRoot();
    expect(currentIntakeState(root)).toBe('closed');
    root.dataset.rhIntake = 'maybe';
    expect(currentIntakeState(root)).toBe('closed');
    publishIntakeState('open', root);
    expect(currentIntakeState(root)).toBe('open');
    publishIntakeState('closed', root);
    expect(root.dataset.rhIntake).toBe('closed');
  });

  it('상태별 요소가 있고 아직 확인하지 않았을 때만 확인을 요청한다', () => {
    expect(needsIntakeState(fakeDocument({}, true))).toBe(true);
    expect(needsIntakeState(fakeDocument({}, false))).toBe(false);
    expect(needsIntakeState(fakeDocument({ rhIntake: 'closed' }, true))).toBe(false);
  });
});
