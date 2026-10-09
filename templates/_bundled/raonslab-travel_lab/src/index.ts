/**
 * RAON Travel Lab — 사용자 템플릿 진입점
 *
 * 여행 상품 탐색 · 출발일/인원 선택 · 여행 장바구니 · 상담 요청(테스트) 화면을 그리는 컴포넌트 묶음.
 * 코어 ComponentRegistry 는 components.json 매니페스트를 읽어 전역 `RaonslabTravelLab`
 * 에서 같은 이름의 export 를 찾아 등록한다 — export 이름이 곧 레이아웃 계약이다.
 */

const logger = ((window as any).G7Core?.createLogger?.('Template:raonslab-travel_lab')) ?? {
  log: (...args: unknown[]) => console.log('[Template:raonslab-travel_lab]', ...args),
  warn: (...args: unknown[]) => console.warn('[Template:raonslab-travel_lab]', ...args),
  error: (...args: unknown[]) => console.error('[Template:raonslab-travel_lab]', ...args),
};

import './styles/main.css';

// Basic
export { Div, type DivProps } from './components/basic/Div';
export { Span, type SpanProps } from './components/basic/Span';
export { P, type PProps } from './components/basic/P';
export { H1, type H1Props } from './components/basic/H1';
export { H2, type H2Props } from './components/basic/H2';
export { H3, type H3Props } from './components/basic/H3';
export { H4, type H4Props } from './components/basic/H4';
export { A, type AProps } from './components/basic/A';
export { Button, type ButtonProps } from './components/basic/Button';
export { Img, type ImgProps } from './components/basic/Img';
export { Input, type InputProps } from './components/basic/Input';
export { Select, type SelectProps } from './components/basic/Select';
export { Option, type OptionProps } from './components/basic/Option';
export { Textarea, type TextareaProps } from './components/basic/Textarea';
export { Label, type LabelProps } from './components/basic/Label';
export { Form, type FormProps } from './components/basic/Form';
export { Icon, type IconProps } from './components/basic/Icon';
export { Ul, type UlProps } from './components/basic/Ul';
export { Li, type LiProps } from './components/basic/Li';
export { Nav, type NavProps } from './components/basic/Nav';
export { Section, type SectionProps } from './components/basic/Section';
export { Hr, type HrProps } from './components/basic/Hr';
export { Svg, type SvgProps } from './components/basic/Svg';

// Composite
export { Toast, type ToastProps } from './components/composite/Toast';
export { Modal, type ModalProps } from './components/composite/Modal';
export { Pagination, type PaginationProps } from './components/composite/Pagination';
export { ScenicArt, type ScenicArtProps } from './components/composite/ScenicArt';
export { PriceTag, type PriceTagProps } from './components/composite/PriceTag';
export { StatusBadge, type StatusBadgeProps } from './components/composite/StatusBadge';

import templateMetadata from '../template.json';
import { handlerMap } from './handlers';

export { templateMetadata };

/**
 * 템플릿 초기화 — ActionDispatcher 가 준비될 때까지 100ms 간격 최대 50회 재시도 후 핸들러 등록.
 * 로케일 전환 시 재등록을 위해 handlerMap 을 전역에도 노출한다.
 */
export function initTemplate(): void {
  if (typeof window === 'undefined') return;
  (window as any).G7TemplateHandlers = handlerMap;

  let retryCount = 0;
  const maxRetries = 50;

  const registerHandlers = () => {
    // A delayed attempt must stop once the rendering environment is disposed.
    if (typeof window === 'undefined') return;
    const actionDispatcher = (window as any).G7Core?.getActionDispatcher?.();
    if (actionDispatcher) {
      Object.entries(handlerMap).forEach(([name, handler]) => {
        actionDispatcher.registerHandler(name, handler);
      });
      logger.log(`${Object.keys(handlerMap).length} handler(s) registered`);
      return;
    }
    retryCount += 1;
    if (retryCount <= maxRetries) {
      setTimeout(registerHandlers, 100);
    } else {
      logger.error('ActionDispatcher not available — travel handlers not registered');
    }
  };

  if (document.readyState === 'complete') {
    registerHandlers();
  } else {
    window.addEventListener('load', registerHandlers);
  }
}

initTemplate();
