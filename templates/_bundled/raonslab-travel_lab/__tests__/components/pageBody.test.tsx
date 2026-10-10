/**
 * @file pageBody.test.tsx
 * @description PageBody 서식 전용 정제 — 실제 DOMPurify(jsdom), 대역 정제기 없음
 *
 * @vitest-environment jsdom
 */
import React from 'react';
import { describe, it, expect, afterEach, vi } from 'vitest';
import { render, cleanup } from '@testing-library/react';
import DOMPurify from 'dompurify';
import { PageBody, sanitizePageBodyHtml, PAGE_BODY_ALLOWED_TAGS } from '../../src/components/composite/PageBody';

afterEach(() => cleanup());

const ATTACK = [
  '<p onclick="alert(1)" style="color:red" class="x" id="y" data-track="1" aria-label="z" title="t">문단</p>',
  '<script>window.__pwned = 1</script>',
  '<img src="https://evil.example/pixel.png" onerror="alert(1)">',
  '<a href="javascript:alert(1)">자바스크립트 링크</a>',
  '<a href="https://evil.example/">외부 링크</a>',
  '<iframe src="https://evil.example/"></iframe>',
  '<svg><script>alert(1)</script><image href="https://evil.example/a.png"/></svg>',
  '<style>body{background:url(https://evil.example/bg.png)}</style>',
  '<video src="https://evil.example/v.mp4" poster="https://evil.example/p.png"></video>',
  '<form action="https://evil.example/"><input name="q"><button>보내기</button></form>',
  '<div style="background-image:url(https://evil.example/x.png)">배경</div>',
  '<math><mi xlink:href="javascript:alert(1)">m</mi></math>',
  '<h2>소제목</h2><ul><li><strong>굵게</strong> <em>기울임</em></li></ul><blockquote>인용</blockquote><pre><code>코드</code></pre>',
].join('');

describe('sanitizePageBodyHtml (strict formatting-only)', () => {
  it('서식 태그만 남기고 속성·링크·미디어·스크립트·스타일·폼·SVG 를 모두 제거한다', () => {
    const out = sanitizePageBodyHtml(ATTACK);
    const box = document.createElement('div');
    box.innerHTML = out;
    const tags = new Set(Array.from(box.querySelectorAll('*')).map((el) => el.tagName.toLowerCase()));
    tags.forEach((tag) => expect(PAGE_BODY_ALLOWED_TAGS as readonly string[]).toContain(tag));
    box.querySelectorAll('*').forEach((el) => expect(el.attributes.length, el.outerHTML).toBe(0));
    expect(out).not.toMatch(/https?:|javascript:|on\w+=|style|<a\b|<img|<script|<iframe|<svg|<video|<form|<input|<button|<math/i);
    expect(box.textContent).toContain('자바스크립트 링크'); // 링크는 글자만 남는다
    expect(box.textContent).toContain('외부 링크');
    expect(box.textContent).not.toContain('window.__pwned');
    expect(box.querySelector('h2')?.textContent).toBe('소제목');
    expect(box.querySelector('li strong')?.textContent).toBe('굵게');
    expect((window as any).__pwned).toBeUndefined();
  });

  it('전역 DOMPurify 훅·설정으로 정책을 넓힐 수 없다 (전용 인스턴스)', () => {
    DOMPurify.setConfig({ ALLOWED_TAGS: ['img', 'a'], ALLOWED_ATTR: ['src', 'href'] });
    DOMPurify.addHook('afterSanitizeAttributes', (node: Element) => node.setAttribute?.('data-injected', '1'));
    try {
      const out = sanitizePageBodyHtml('<p>a</p><img src="https://evil.example/x.png"><a href="https://evil.example">b</a>');
      expect(out).toBe('<p>a</p>b');
    } finally {
      DOMPurify.removeAllHooks();
      DOMPurify.clearConfig();
    }
  });
});

describe('PageBody 렌더', () => {
  it('text 모드는 HTML 을 해석하지 않고 줄바꿈·공백을 보존한다', () => {
    const { getByTestId } = render(<PageBody content={'<b>굵게 아님</b>\n  둘째 줄'} contentMode="text" />);
    const el = getByTestId('page-body');
    expect(el.querySelector('b')).toBeNull();
    expect(el.textContent).toBe('<b>굵게 아님</b>\n  둘째 줄');
    expect(el.className).toContain('whitespace-pre-wrap');
    expect(el.getAttribute('data-content-mode')).toBe('text');
  });

  it('알 수 없는 모드는 text 로 취급한다', () => {
    const { getByTestId } = render(<PageBody content="<i>x</i>" contentMode="markdown" />);
    expect(getByTestId('page-body').querySelector('i')).toBeNull();
  });

  it('html 모드 DOM 에 원격 자원을 요청할 수 있는 요소·속성이 남지 않는다', () => {
    const { container } = render(<PageBody content={ATTACK} contentMode="html" />);
    expect(container.querySelector('img,iframe,video,audio,source,svg,image,a,style,script,link,object,embed,form')).toBeNull();
    expect(container.querySelectorAll('[src],[href],[style],[poster],[srcset],[action]')).toHaveLength(0);
    expect(container.innerHTML).not.toContain('evil.example');
  });

  it('빈 값 → 텍스트 → HTML → 빈 값 전환에서 훅 순서 오류 없이 다시 그린다', () => {
    const errors = vi.spyOn(console, 'error').mockImplementation(() => {});
    const { rerender, queryByTestId } = render(<PageBody content="" contentMode="html" />);
    expect(queryByTestId('page-body')).toBeNull();
    rerender(<PageBody content={'첫 줄\n둘째 줄'} contentMode="text" />);
    expect(queryByTestId('page-body')?.textContent).toBe('첫 줄\n둘째 줄');
    rerender(<PageBody content="<p>문단 <em>강조</em></p>" contentMode="html" />);
    expect(queryByTestId('page-body')?.querySelector('em')?.textContent).toBe('강조');
    rerender(<PageBody content="<p>문단</p>" contentMode="text" />);
    expect(queryByTestId('page-body')?.textContent).toBe('<p>문단</p>');
    rerender(<PageBody content={null} contentMode="html" />);
    expect(queryByTestId('page-body')).toBeNull();
    expect(errors).not.toHaveBeenCalled();
    errors.mockRestore();
  });

  it('긴 본문도 그대로 렌더하고 줄바꿈 단위를 잃지 않는다', () => {
    const long = Array.from({ length: 400 }, (_, i) => `줄 ${i} ${'가'.repeat(60)}`).join('\n');
    const { getByTestId } = render(<PageBody content={long} contentMode="text" />);
    expect(getByTestId('page-body').textContent).toBe(long);
    expect(getByTestId('page-body').className).toContain('break-words');
  });

  it('purifyConfig 같은 확장 prop 을 받아도 정책은 그대로다', () => {
    const props: any = { content: '<img src="https://evil.example/x.png"><p>ok</p>', contentMode: 'html', purifyConfig: { ALLOWED_TAGS: ['img'], ALLOWED_ATTR: ['src'] } };
    const { container } = render(<PageBody {...props} />);
    expect(container.querySelector('img')).toBeNull();
    expect(container.textContent).toBe('ok');
  });
});
