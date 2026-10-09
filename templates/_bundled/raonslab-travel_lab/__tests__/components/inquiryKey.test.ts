/**
 * @file inquiryKey.test.ts
 * @description 상담 요청 멱등 키 핸들러 — 재시도는 같은 키, 성공·묶음 변경은 새 키
 *
 * @vitest-environment jsdom
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
  ensureInquiryKeyHandler,
  clearInquiryKeyHandler,
  fingerprintCartIds,
  generateInquiryKey,
  prepareInquiryHandler,
} from '../../src/handlers/inquiryKey';
import { handlerMap } from '../../src/handlers';

let globalState: Record<string, any>;

beforeEach(() => {
  window.sessionStorage.clear();
  globalState = {};
  (window as any).G7Core = { state: { set: (u: Record<string, any>) => Object.assign(globalState, u) } };
  clearInquiryKeyHandler();
});

const ensure = (cartIds: unknown, quantities?: unknown) =>
  ensureInquiryKeyHandler({ handler: 'travelLabEnsureInquiryKey', params: { cartIds, quantities } });

describe('fingerprintCartIds', () => {
  it('순서와 무관하다', () => {
    expect(fingerprintCartIds([3, 1, 2], [1, 1, 1])).toBe(fingerprintCartIds([1, 2, 3], [1, 1, 1]));
  });
  it('인원이 바뀌면 지문이 바뀐다', () => {
    expect(fingerprintCartIds([1, 2], [1, 2])).not.toBe(fingerprintCartIds([1, 2], [1, 3]));
  });
  it('잘못된 값은 버린다', () => {
    expect(fingerprintCartIds(['x', -1, 0, 5])).toBe('5');
    expect(fingerprintCartIds(null)).toBe('');
  });
});

describe('ensure / clear', () => {
  it('같은 묶음을 다시 확보하면(= 네트워크 오류 후 재시도) 같은 키', () => {
    const first = ensure([10, 11], [2, 1]);
    const retry = ensure([11, 10], [1, 2]);
    expect(first).toBeTruthy();
    expect(retry).toBe(first);
    expect(globalState.travelInquiryKey).toBe(first);
  });

  it('새로고침 후에도(세션 저장소) 같은 묶음이면 같은 키', () => {
    const first = ensure([7], [1]);
    globalState = {};
    expect(ensure([7], [1])).toBe(first);
  });

  it('묶음이나 인원이 바뀌면 새 키', () => {
    const a = ensure([1], [1]);
    const b = ensure([1, 2], [1, 1]);
    const c = ensure([1, 2], [2, 1]);
    expect(b).not.toBe(a);
    expect(c).not.toBe(b);
  });

  it('성공 후 clear 하면 같은 묶음이라도 다음 요청은 새 키', () => {
    const before = ensure([5], [2]);
    clearInquiryKeyHandler();
    expect(globalState.travelInquiryKey).toBeNull();
    const after = ensure([5], [2]);
    expect(after).not.toBe(before);
  });

  it('연락처를 포함한 네트워크 재시도와 새로고침은 동일 키·본문을 복원한다', () => {
    const action = { handler: 'travelLabEnsureInquiryKey', params: { cartIds: [5], quantities: [2], contact: { name: ' 라온 ', phone: '01000000000' } } };
    const submitted = ensureInquiryKeyHandler(action);
    expect(ensure([5], [2])).toBe(submitted);
    expect(globalState.travelInquiryContact).toEqual({ name: '라온', phone: '01000000000' });
    expect(ensureInquiryKeyHandler(action)).toBe(submitted);
    const edited = ensureInquiryKeyHandler({ ...action, params: { ...action.params, contact: { name: '라온', phone: '01011111111' } } });
    expect(edited).not.toBe(submitted);
  });

  it('빈 장바구니는 키를 만들지 않는다 (요청 버튼 비활성)', () => {
    expect(ensure([], [])).toBeNull();
    expect(globalState.travelInquiryKey).toBeNull();
  });

  it('저장소 쓰기만 차단되어도 현재 탭의 네트워크 재시도는 같은 키다', () => {
    const write = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new DOMException('quota', 'QuotaExceededError'); });
    try {
      const first = ensure([5], [2]);
      expect(ensure([5], [2])).toBe(first);
    } finally {
      write.mockRestore();
    }
  });

  it('생성 키는 충분히 길고 서로 다르다', () => {
    const keys = new Set(Array.from({ length: 50 }, () => generateInquiryKey()));
    expect(keys.size).toBe(50);
    keys.forEach((k) => expect(k.length).toBeGreaterThanOrEqual(20));
  });

  it('handlerMap 은 travelLab 접두사 핸들러만 노출한다', () => {
    expect(Object.keys(handlerMap).sort()).toEqual(['travelLabClearInquiryKey', 'travelLabEnsureInquiryKey', 'travelLabPrepareInquiry']);
  });
});

describe('durable inquiry preparation', () => {
  const action = { handler: 'travelLabPrepareInquiry', params: { cartIds: [5], quantities: [2], contact: { name: ' 고객 ', phone: '01000000000' } } };
  it('captures the exact normalized body, rather than a stale render key', () => {
    const renderKey = ensure([5], [2]);
    const body = prepareInquiryHandler(action)!;
    expect(body.idempotency_key).not.toBe(renderKey);
    expect(body).toEqual({cart_ids:[5],contact:{name:'고객',phone:'01000000000'},idempotency_key:globalState.travelInquiryKey});
    expect(prepareInquiryHandler(action)).toEqual(body);
  });
  it('recovers the same body after a consumed cart reload; success clears it', () => {
    const body = prepareInquiryHandler(action);
    globalState = {};
    expect(ensure([], [])).toBe(body!.idempotency_key);
    expect(globalState.travelInquiryPending).toEqual(body);
    expect(prepareInquiryHandler({handler:'travelLabPrepareInquiry',params:{cartIds:[],quantities:[]}})).toEqual(body);
    clearInquiryKeyHandler();
    expect(ensure([], [])).toBeNull();
    expect(globalState.travelInquiryPending).toBeNull();
  });
});

it('does not restore another logged-in member’s pending contact or body', () => {
  prepareInquiryHandler({handler:'travelLabPrepareInquiry',params:{ownerId:1,cartIds:[5],quantities:[1],contact:{name:'Owner one',phone:null}}});
  expect(ensureInquiryKeyHandler({handler:'travelLabEnsureInquiryKey',params:{ownerId:2,cartIds:[]}})).toBeNull();
  expect(globalState.travelInquiryPending).toBeNull();
  expect(globalState.travelInquiryContact).toBeNull();
});

it('an acknowledged cart deletion clears uncertain pending recovery before a fresh cart load', () => {
  prepareInquiryHandler({handler:'travelLabPrepareInquiry',params:{ownerId:1,cartIds:[5],quantities:[1],contact:{name:'Owner',phone:null}}});
  expect(ensureInquiryKeyHandler({handler:'travelLabEnsureInquiryKey',params:{ownerId:1,cartChanged:true,cartIds:[]}})).toBeNull();
  expect(globalState.travelInquiryPending).toBeNull();
  expect(ensure([])).toBeNull();
});

describe('persisted stale storage after write-only failures', () => {
  const action = { handler: 'travelLabPrepareInquiry', params: { ownerId: 'member-one', cartIds: [5], quantities: [2], contact: { name: 'Customer', phone: '01000000000' } } };
  it('an existing persisted cart key does not shadow the prepared body after setItem fails', () => {
    const initial = ensureInquiryKeyHandler({ handler: 'travelLabEnsureInquiryKey', params: { ownerId: 'member-one', cartIds: [5], quantities: [2] } });
    const write = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new DOMException('quota', 'QuotaExceededError'); });
    try {
      const body = prepareInquiryHandler(action);
      expect(body).not.toBeNull();
      expect(body!.contact).toEqual({ name: 'Customer', phone: '01000000000' });
      expect(body!.idempotency_key).not.toBe(initial);
      expect(prepareInquiryHandler(action)).toEqual(body);
      expect(globalState.travelInquiryPending).toEqual(body);
      expect(JSON.parse(window.sessionStorage.getItem('raon_travel_inquiry_key')!).key).toBe(initial);
    } finally { write.mockRestore(); }
  });
  it('the latest changed contact stays authoritative over an earlier persisted request', () => {
    const initial = prepareInquiryHandler(action)!;
    const editedAction = { ...action, params: { ...action.params, contact: { name: 'Customer', phone: '01011111111' } } };
    const write = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new DOMException('quota', 'QuotaExceededError'); });
    try {
      const changed = prepareInquiryHandler(editedAction)!;
      expect(changed.contact.phone).toBe('01011111111');
      expect(changed.idempotency_key).not.toBe(initial.idempotency_key);
      expect(prepareInquiryHandler(editedAction)).toEqual(changed);
      expect(ensureInquiryKeyHandler({ handler: 'travelLabEnsureInquiryKey', params: { ownerId: 'member-one', cartIds: [] } })).toBe(changed.idempotency_key);
      expect(globalState.travelInquiryPending).toEqual(changed);
    } finally { write.mockRestore(); }
  });
  it('a failed clear leaves a tombstone instead of resurrecting the persisted pending request', () => {
    const initial = prepareInquiryHandler(action)!;
    const remove = vi.spyOn(Storage.prototype, 'removeItem').mockImplementation(() => { throw new DOMException('denied', 'SecurityError'); });
    try {
      clearInquiryKeyHandler();
      expect(globalState.travelInquiryPending).toBeNull();
      expect(ensureInquiryKeyHandler({ handler: 'travelLabEnsureInquiryKey', params: { ownerId: 'member-one', cartIds: [] } })).toBeNull();
      expect(prepareInquiryHandler({ handler: 'travelLabPrepareInquiry', params: { ownerId: 'member-one', cartIds: [] } })).toBeNull();
      expect(JSON.parse(window.sessionStorage.getItem('raon_travel_inquiry_key')!).key).toBe(initial.idempotency_key);
    } finally { remove.mockRestore(); }
    const next = prepareInquiryHandler(action)!;
    expect(next.idempotency_key).not.toBe(initial.idempotency_key);
    expect(prepareInquiryHandler(action)).toEqual(next);
  });
});
