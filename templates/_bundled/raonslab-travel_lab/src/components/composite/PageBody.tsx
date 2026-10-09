import React, { useMemo } from 'react';
import DOMPurify from 'dompurify';
import { Div } from '../basic/Div';
import type { EditorAttrs } from '../../types';

/**
 * PageBody — native 페이지(캠페인) 본문을 서식 전용으로 안전하게 그린다.
 *
 * - `contentMode="text"`: 문자열을 React 자식으로만 렌더(자동 이스케이프) + 줄바꿈/공백 보존.
 * - `contentMode="html"`: DOMPurify 로 정제하되 **서식 태그만** 허용하고 속성은 하나도 남기지 않는다.
 *   링크는 글자만 남고(href 없음), 이미지·미디어·iframe·폼·SVG·style·script 는 제거된다.
 *   본문이 외부 자원을 요청하는 경로가 원리상 없다.
 * - 정제 정책은 이 파일의 상수가 단일 출처다. 레이아웃·API·편집기 props 로 넓힐 수 없다
 *   (purifyConfig 같은 확장 prop 을 받지 않는다).
 * - 전역 DOMPurify 인스턴스에 다른 코드가 훅을 달아도 영향받지 않도록 전용 인스턴스를 쓴다.
 * - 훅 호출 순서는 빈 값/텍스트/HTML 과 모드 전환에 관계없이 항상 같다.
 */
export const PAGE_BODY_ALLOWED_TAGS = [
  'p', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
  'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'blockquote', 'code', 'pre',
] as const;

const PURIFY_CONFIG = Object.freeze({
  ALLOWED_TAGS: [...PAGE_BODY_ALLOWED_TAGS],
  ALLOWED_ATTR: [] as string[],
  ALLOW_DATA_ATTR: false,
  ALLOW_ARIA_ATTR: false,
  ALLOW_UNKNOWN_PROTOCOLS: false,
  KEEP_CONTENT: true,
  RETURN_DOM: false,
  RETURN_DOM_FRAGMENT: false,
  RETURN_TRUSTED_TYPE: false,
});

type Purifier = ReturnType<typeof DOMPurify>;
let privatePurifier: Purifier | null = null;

function purifier(): Purifier | null {
  if (typeof window === 'undefined') return null;
  if (!privatePurifier) {
    // 공유 기본 인스턴스가 아니라 이 컴포넌트 전용 인스턴스 (전역 addHook/setConfig 격리).
    privatePurifier = DOMPurify(window);
  }
  return privatePurifier;
}

/**
 * 서식 전용 정책으로 HTML 을 정제한다 (정제기를 쓸 수 없으면 빈 문자열 — fail-closed).
 */
export function sanitizePageBodyHtml(html: string): string {
  const instance = purifier();
  if (!instance || !instance.isSupported) return '';
  return String(instance.sanitize(html, { ...PURIFY_CONFIG, ALLOWED_TAGS: [...PURIFY_CONFIG.ALLOWED_TAGS], ALLOWED_ATTR: [] }));
}

export interface PageBodyProps {
  /** 본문 원문 (서버 응답의 현재 로케일 content) */
  content?: string | null;
  /** 'text' | 'html' — 그 밖의 값은 text 로 취급(가장 안전한 경로) */
  contentMode?: string | null;
  className?: string;
  id?: string;
  'data-testid'?: string;
  editorAttrs?: EditorAttrs;
}

const TEXT_CLASSES = 'whitespace-pre-wrap break-words text-base leading-relaxed text-ink-700';
const HTML_CLASSES =
  'break-words text-base leading-relaxed text-ink-700 ' +
  '[&_p]:my-3 [&_h1]:mt-6 [&_h1]:mb-3 [&_h1]:text-2xl [&_h1]:font-bold [&_h2]:mt-6 [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-bold ' +
  '[&_h3]:mt-5 [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-bold [&_h4]:mt-4 [&_h4]:font-semibold [&_strong]:font-bold [&_b]:font-bold ' +
  '[&_ul]:my-3 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:my-3 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:my-1 ' +
  '[&_blockquote]:my-4 [&_blockquote]:border-l-4 [&_blockquote]:border-raon-200 [&_blockquote]:pl-4 [&_blockquote]:text-ink-500 ' +
  '[&_code]:rounded [&_code]:bg-slate-100 [&_code]:px-1 [&_pre]:my-3 [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:bg-slate-100 [&_pre]:p-4';

export const PageBody: React.FC<PageBodyProps> = ({
  content,
  contentMode,
  className = '',
  id,
  editorAttrs,
  'data-testid': testId = 'page-body',
}) => {
  const raw = typeof content === 'string' ? content : '';
  const isHtml = contentMode === 'html';

  // 모든 경로에서 같은 순서로 호출 (빈 값·모드 전환 시 훅 순서 불변)
  const sanitized = useMemo(() => (isHtml && raw.trim() !== '' ? sanitizePageBodyHtml(raw) : ''), [isHtml, raw]);

  if (raw.trim() === '') {
    return null;
  }

  if (!isHtml) {
    return (
      <Div id={id} className={`${TEXT_CLASSES} ${className}`.trim()} data-testid={testId} data-content-mode="text" {...editorAttrs}>
        {raw}
      </Div>
    );
  }

  return (
    <Div
      id={id}
      className={`${HTML_CLASSES} ${className}`.trim()}
      data-testid={testId}
      data-content-mode="html"
      dangerouslySetInnerHTML={{ __html: sanitized }}
      {...editorAttrs}
    />
  );
};
