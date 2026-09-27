import { describe, expect, it } from 'vitest';
import { shouldApplyDarkDefault } from './theme';

describe('제품 기본 테마', () => {
  it('사용자 선택이 없을 때만 다크 기본값을 적용한다', () => {
    expect(shouldApplyDarkDefault(null)).toBe(true);
    expect(shouldApplyDarkDefault('light')).toBe(false);
    expect(shouldApplyDarkDefault('dark')).toBe(false);
    expect(shouldApplyDarkDefault('auto')).toBe(false);
  });
});
