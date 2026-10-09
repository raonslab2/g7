/**
 * RAON Travel Lab 템플릿 전용 핸들러 맵
 *
 * 핸들러 이름은 다른 템플릿·모듈과 충돌하지 않도록 `travelLab` 접두사를 쓴다.
 */
import { ensureInquiryKeyHandler, clearInquiryKeyHandler, prepareInquiryHandler } from './inquiryKey';

export const handlerMap: Record<string, (...args: any[]) => any> = {
  travelLabEnsureInquiryKey: ensureInquiryKeyHandler,
  travelLabPrepareInquiry: prepareInquiryHandler,
  travelLabClearInquiryKey: clearInquiryKeyHandler,
};

export { ensureInquiryKeyHandler, clearInquiryKeyHandler, prepareInquiryHandler };
