/**
 * 상담 요청(테스트) 멱등 키 핸들러
 *
 * 같은 요청을 다시 보내는 경우(네트워크 오류 후 재시도·새로고침)에는 **같은 키**를 쓰고,
 * 요청이 성공했거나 담은 출발편 묶음·인원·연락처가 바뀌면 **새 키**를 쓴다. 키는 세션 저장소에
 * 출발편 묶음 지문과 함께 보관해 새로고침 뒤에도 재시도가 같은 키를 싣는다.
 *
 * 네트워크 오류 시에는 이 모듈의 어떤 핸들러도 부르지 않는다 — 키를 새로 만들지 않는 것이
 * 서버 측 중복 차단의 전제다.
 *
 * 결과는 전역 상태 `_global.travelInquiryKey` 에 둔다(레이아웃 apiCall body 가 읽는다).
 */
interface HandlerAction {
    handler: string;
    params?: Record<string, any>;
}
/**
 * 장바구니 항목 id(와 인원) 목록에서 순서 무관 지문을 만든다.
 *
 * 인원이 바뀌면 서버가 받는 요청 내용도 바뀌므로 다른 지문(= 새 키)이 된다.
 */
export declare function fingerprintCartIds(ids: unknown, quantities?: unknown): string;
/**
 * 충돌 가능성이 무시할 만한 키를 만든다.
 */
export declare function generateInquiryKey(): string;
/**
 * 출발편 묶음(cartIds)에 대응하는 키를 확보한다. 같은 묶음이면 기존 키를 재사용한다.
 *
 * params.cartIds: number[], params.quantities?: number[] (cartIds 와 같은 순서), params.contact?: {name,phone}
 */
export declare function ensureInquiryKeyHandler(action: HandlerAction): string | null;
/**
 * 요청이 성공적으로 접수된 뒤 키를 폐기한다 — 다음 요청은 새 키를 받는다.
 */
export declare function clearInquiryKeyHandler(): void;
export {};
