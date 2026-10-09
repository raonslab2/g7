import { default as React } from 'react';
import { EditorAttrs } from '../../types';
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
export declare const PAGE_BODY_ALLOWED_TAGS: readonly ["p", "br", "h1", "h2", "h3", "h4", "h5", "h6", "strong", "b", "em", "i", "ul", "ol", "li", "blockquote", "code", "pre"];
/**
 * 서식 전용 정책으로 HTML 을 정제한다 (정제기를 쓸 수 없으면 빈 문자열 — fail-closed).
 */
export declare function sanitizePageBodyHtml(html: string): string;
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
export declare const PageBody: React.FC<PageBodyProps>;
