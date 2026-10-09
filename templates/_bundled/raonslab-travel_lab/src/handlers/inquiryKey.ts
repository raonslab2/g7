/**
 * 상담 요청(테스트) 멱등 키 핸들러
 *
 * 같은 요청을 다시 보내는 경우(네트워크 오류 후 재시도·새로고침)에는 **같은 키**를 쓰고,
 * 요청이 성공했거나 담은 출발편 묶음이 바뀌면 **새 키**를 쓴다. 키는 세션 저장소에
 * 출발편 묶음 지문과 함께 보관해 새로고침 뒤에도 재시도가 같은 키를 싣는다.
 *
 * 네트워크 오류 시에는 이 모듈의 어떤 핸들러도 부르지 않는다 — 키를 새로 만들지 않는 것이
 * 서버 측 중복 차단의 전제다.
 *
 * 결과는 전역 상태 `_global.travelInquiryKey` 에 둔다(레이아웃 apiCall body 가 읽는다).
 */

const STORAGE_KEY = 'raon_travel_inquiry_key';
const STATE_KEY = 'travelInquiryKey';

interface StoredKey {
  key: string;
  fingerprint: string;
}

interface HandlerAction {
  handler: string;
  params?: Record<string, any>;
}

const logger = ((window as any).G7Core?.createLogger?.('Handler:TravelInquiryKey')) ?? {
  log: (...args: unknown[]) => console.log('[Handler:TravelInquiryKey]', ...args),
  warn: (...args: unknown[]) => console.warn('[Handler:TravelInquiryKey]', ...args),
  error: (...args: unknown[]) => console.error('[Handler:TravelInquiryKey]', ...args),
};

/**
 * 장바구니 항목 id(와 인원) 목록에서 순서 무관 지문을 만든다.
 *
 * 인원이 바뀌면 서버가 받는 요청 내용도 바뀌므로 다른 지문(= 새 키)이 된다.
 */
export function fingerprintCartIds(ids: unknown, quantities?: unknown): string {
  if (!Array.isArray(ids)) return '';
  const qty = Array.isArray(quantities) ? quantities : [];
  return ids
    .map((id, index) => ({ id: Number(id), quantity: Number(qty[index] ?? 0) || 0 }))
    .filter((entry) => Number.isInteger(entry.id) && entry.id > 0)
    .sort((a, b) => a.id - b.id)
    .map((entry) => (qty.length ? `${entry.id}:${entry.quantity}` : String(entry.id)))
    .join(',');
}

/**
 * 충돌 가능성이 무시할 만한 키를 만든다.
 */
export function generateInquiryKey(): string {
  const cryptoApi = (globalThis as any).crypto;
  if (cryptoApi?.randomUUID) {
    return `raon-${cryptoApi.randomUUID()}`;
  }
  const bytes = new Uint8Array(16);
  if (cryptoApi?.getRandomValues) {
    cryptoApi.getRandomValues(bytes);
  } else {
    for (let i = 0; i < bytes.length; i += 1) bytes[i] = Math.floor(Math.random() * 256);
  }
  return `raon-${Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('')}`;
}

function readStored(): StoredKey | null {
  try {
    const raw = window.sessionStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (typeof parsed?.key === 'string' && typeof parsed?.fingerprint === 'string') {
      return parsed as StoredKey;
    }
  } catch {
    // 손상·차단된 저장소 — 메모리 상태만으로 동작한다
  }
  return null;
}

function writeStored(value: StoredKey | null): void {
  try {
    if (value) {
      window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(value));
    } else {
      window.sessionStorage.removeItem(STORAGE_KEY);
    }
  } catch {
    // 저장소를 쓸 수 없으면 전역 상태만 유지한다
  }
}

function setGlobalKey(key: string | null): void {
  (window as any).G7Core?.state?.set?.({ [STATE_KEY]: key });
}

/**
 * 출발편 묶음(cartIds)에 대응하는 키를 확보한다. 같은 묶음이면 기존 키를 재사용한다.
 *
 * params.cartIds: number[], params.quantities?: number[] (cartIds 와 같은 순서)
 */
export function ensureInquiryKeyHandler(action: HandlerAction): string | null {
  const fingerprint = fingerprintCartIds(action?.params?.cartIds, action?.params?.quantities);
  if (!fingerprint) {
    setGlobalKey(null);
    return null;
  }

  const stored = readStored();
  if (stored && stored.fingerprint === fingerprint) {
    setGlobalKey(stored.key);
    return stored.key;
  }

  const key = generateInquiryKey();
  writeStored({ key, fingerprint });
  setGlobalKey(key);
  logger.log('New inquiry key prepared for cart set', fingerprint);
  return key;
}

/**
 * 요청이 성공적으로 접수된 뒤 키를 폐기한다 — 다음 요청은 새 키를 받는다.
 */
export function clearInquiryKeyHandler(): void {
  writeStored(null);
  setGlobalKey(null);
}
