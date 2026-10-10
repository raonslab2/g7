import { default as templateMetadata } from '../template.json';
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
export { Toast, type ToastProps } from './components/composite/Toast';
export { Modal, type ModalProps } from './components/composite/Modal';
export { Pagination, type PaginationProps } from './components/composite/Pagination';
export { ScenicArt, type ScenicArtProps } from './components/composite/ScenicArt';
export { PriceTag, type PriceTagProps } from './components/composite/PriceTag';
export { StatusBadge, type StatusBadgeProps } from './components/composite/StatusBadge';
export { PageBody, type PageBodyProps } from './components/composite/PageBody';
export { templateMetadata };
/**
 * 템플릿 초기화 — ActionDispatcher 가 준비될 때까지 100ms 간격 최대 50회 재시도 후 핸들러 등록.
 * 로케일 전환 시 재등록을 위해 handlerMap 을 전역에도 노출한다.
 */
export declare function initTemplate(): void;
