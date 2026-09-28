import { describe, expect, it } from 'vitest';
import { resolveHomeMeta, resolveModuleAssetUrl } from './homePage';

describe('홈 문서 메타', () => {
  it('번역된 제목·설명을 돌려준다', () => {
    const dict: Record<string, string> = { 'home.meta_title': '제목', 'home.meta_description': '설명' };
    expect(resolveHomeMeta((key) => dict[key])).toEqual({ title: '제목', description: '설명' });
  });

  it('번역 키가 풀리지 않으면 적용하지 않는다(빈 키 노출 방지)', () => {
    expect(resolveHomeMeta((key) => `raonslab-product.${key}`)).toBeNull();
  });
});

describe('모듈 동봉 이미지 주소', () => {
  const PATH = 'resources/assets/cases/mobile-stock-public-case-01-new-paper-account-390x844.png';

  it('코어 자산 API 가 만든 same-origin 주소를 그대로 쓴다(확장자·쿼리 모드 모두)', () => {
    expect(resolveModuleAssetUrl({ module: (id, p) => `/api/modules/assets/${id}/${p}` }, PATH)).toBe(`/api/modules/assets/raonslab-product/${PATH}`);
    expect(resolveModuleAssetUrl({ module: (id, p) => `/api/modules/assets/${id}?file=${encodeURIComponent(p)}` }, PATH)).toContain('?file=');
  });

  it('코어 API 가 없거나 이상한 값을 주면 주소를 만들지 않는다', () => {
    expect(resolveModuleAssetUrl(undefined, PATH)).toBeNull();
    expect(resolveModuleAssetUrl({ module: () => 'https://evil.example/x.png' }, PATH)).toBeNull();
    expect(resolveModuleAssetUrl({ module: () => '//evil.example/x.png' }, PATH)).toBeNull();
    expect(resolveModuleAssetUrl({ module: () => { throw new Error('x'); } }, PATH)).toBeNull();
    expect(resolveModuleAssetUrl({ module: (id, p) => `/api/modules/assets/${id}/${p}` }, '../secret.png')).toBeNull();
  });
});
