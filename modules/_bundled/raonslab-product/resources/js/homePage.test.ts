import { describe, expect, it } from 'vitest';
import { resolveHomeMeta } from './homePage';

describe('홈 문서 메타', () => {
  it('번역된 제목·설명을 돌려준다', () => {
    const dict: Record<string, string> = { 'home.meta_title': '제목', 'home.meta_description': '설명' };
    expect(resolveHomeMeta((key) => dict[key])).toEqual({ title: '제목', description: '설명' });
  });

  it('번역 키가 풀리지 않으면 적용하지 않는다(빈 키 노출 방지)', () => {
    expect(resolveHomeMeta((key) => `raonslab-product.${key}`)).toBeNull();
  });
});
