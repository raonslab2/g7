/**
 * @file cart.test.tsx
 * @description 여행 장바구니 — 항목·합계 · 인원 변경 · 빼기 모달 · 상담 요청(테스트) 제출과 멱등 키 재사용
 *
 * @vitest-environment jsdom
 */
import { describe, it, expect, afterEach, beforeAll, vi } from 'vitest';
import { createLayoutTest, screen, waitFor } from '@core/template-engine/__tests__/utils/layoutTestUtils';
import { registerTemplateComponents, loadLayout, flatten, translations, API_BASE } from '../helpers/travelTestKit';

const cartResponse = {
  success: true,
  data: {
    items: [
      { id: 11, departure_id: 71, product_id: 7, title: '제주 오름 산책 3일', departure_date: '2026-11-02', return_date: '2026-11-04', quantity: 2, unit_price: 420000, line_total: 840000, currency_code: 'KRW', remaining: 2 },
      { id: 12, departure_id: 81, product_id: 8, title: '부산 바다 미식 2일', departure_date: '2026-11-20', return_date: '2026-11-21', quantity: 1, unit_price: 310000, currency_code: 'KRW' },
    ],
    totals: { quantity: 3, amount: 1150000 },
    currency_code: 'KRW',
  },
};

let registry: any;
beforeAll(() => {
  registry = registerTemplateComponents();
});
let t: ReturnType<typeof createLayoutTest> | undefined;
afterEach(() => {
  t?.cleanup();
  t = undefined;
});

const layout = flatten(loadLayout('travel/cart.json'));
const member = (extra: Record<string, any> = {}) => ({
  _global: { currentUser: { id: 1, uuid: 'u-1', name: '라온' }, travelInquiryKey: 'raon-key-1', ...extra },
});

async function renderCart(state = member()) {
  t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialState: state });
  t.mockApi('cart', { response: cartResponse });
  await t.render();
  return t;
}

const inquiryCalls = () =>
  vi.mocked(globalThis.fetch).mock.calls.filter(([url, init]) => String(url).endsWith(`${API_BASE}/inquiries`) && (init as any)?.method === 'POST');

describe('travel/cart', () => {
  it('담긴 출발편 · 인원 · 예상 합계를 그린다', async () => {
    await renderCart();
    expect(screen.getAllByTestId('cart-item')).toHaveLength(2);
    expect(screen.getByText('제주 오름 산책 3일')).toBeInTheDocument();
    expect(screen.getByTestId('cart-total').textContent).toContain('₩1,150,000');
    const qty = screen.getAllByTestId('cart-qty').map((n) => n.textContent);
    expect(qty).toEqual(['2명', '1명']);
    expect(screen.getAllByTestId('cart-qty-decrease')[1]).toBeDisabled();
    expect(screen.getAllByTestId('cart-qty-increase')[0]).toBeDisabled();
    expect(screen.getByTestId('simulation-notice')).toBeInTheDocument();
  });

  it('빈 장바구니는 둘러보기 안내', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialState: member() });
    t.mockApi('cart', { response: { success: true, data: { items: [], totals: { quantity: 0, amount: 0 } } } });
    await t.render();
    expect(screen.getByTestId('cart-empty')).toBeInTheDocument();
    expect(screen.queryByTestId('inquiry-form')).toBeNull();
  });

  it('불러오기 실패 시 다시 시도', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialState: member(), initialData: { _dataSourceErrors: { cart: { status: 500, message: 'x' } } } });
    t.mockApi('cart', { response: null });
    await t.render();
    expect(screen.getByTestId('cart-error')).toBeInTheDocument();
    expect(screen.getByTestId('cart-retry')).toBeInTheDocument();
  });

  it('인원 변경은 PATCH {quantity} 만 보낸다', async () => {
    await renderCart();
    await t!.user.click(screen.getAllByTestId('cart-qty-decrease')[0]);
    await waitFor(() => {
      const call = vi.mocked(globalThis.fetch).mock.calls.find(([url, init]) => String(url).endsWith(`${API_BASE}/cart/11`) && (init as any)?.method === 'PATCH');
      expect(call).toBeDefined();
      expect(JSON.parse(String((call![1] as any).body))).toEqual({ quantity: 1 });
    });
  });

  it('빼기는 확인 모달을 연다', async () => {
    await renderCart();
    await t!.user.click(screen.getAllByTestId('cart-remove')[1]);
    // openModal 은 엔진 빌트인 — 전역 modalStack 에 모달 id 가 쌓인다
    await waitFor(() => expect([...t!.getModalStack(), ...JSON.stringify(t!.getState()._global).match(/travel_cart_remove_modal/g) ?? []]).toContain('travel_cart_remove_modal'));
    expect(t!.getState()._global.travelCartRemoveTarget).toMatchObject({ id: 12 });
  });

  it('테스트 고지 확인 전에는 보낼 수 없고, 확인 후 cart_ids · contact · 멱등 키만 보낸다', async () => {
    await renderCart();
    const submit = screen.getByTestId('submit-inquiry');
    expect(submit).toBeDisabled();
    await t!.user.click(screen.getByTestId('ack-test'));
    await waitFor(() => expect(screen.getByTestId('submit-inquiry')).not.toBeDisabled());
    await t!.user.click(screen.getByTestId('submit-inquiry'));
    await waitFor(() => expect(inquiryCalls().length).toBe(1));
    expect(JSON.parse(String((inquiryCalls()[0][1] as any).body))).toEqual({
      cart_ids: [11, 12],
      contact: { name: '라온', phone: null },
      idempotency_key: 'raon-key-1',
    });
  });

  it('네트워크 오류 후 다시 보내도 같은 멱등 키를 싣는다', async () => {
    await renderCart();
    const original = globalThis.fetch;
    let attempt = 0;
    globalThis.fetch = vi.fn(async (url: any, init: any) => {
      if (String(url).endsWith(`${API_BASE}/inquiries`) && init?.method === 'POST') {
        attempt += 1;
        if (attempt === 1) throw new TypeError('Failed to fetch');
      }
      return (original as any)(url, init);
    }) as any;
    try {
      await t!.user.click(screen.getByTestId('ack-test'));
      await waitFor(() => expect(screen.getByTestId('submit-inquiry')).not.toBeDisabled());
      await t!.user.click(screen.getByTestId('submit-inquiry'));
      await waitFor(() => expect(attempt).toBe(1));
      await waitFor(() => expect(screen.getByTestId('submit-inquiry')).not.toBeDisabled());
      expect(t!.getState()._global.travelInquiryKey).toBe('raon-key-1');
      await t!.user.click(screen.getByTestId('submit-inquiry'));
      await waitFor(() => expect(attempt).toBe(2));
      const keys = vi.mocked(globalThis.fetch).mock.calls
        .filter(([url, init]) => String(url).endsWith(`${API_BASE}/inquiries`) && (init as any)?.method === 'POST')
        .map(([, init]) => JSON.parse(String((init as any).body)).idempotency_key);
      expect(keys).toEqual(['raon-key-1', 'raon-key-1']);
    } finally {
      globalThis.fetch = original;
    }
  });

  it('멱등 키가 아직 없으면 보낼 수 없다', async () => {
    await renderCart(member({ travelInquiryKey: null }));
    await t!.user.click(screen.getByTestId('ack-test'));
    expect(screen.getByTestId('submit-inquiry')).toBeDisabled();
  });
});

describe('partials/travel/_modal_cart_remove', () => {
  it('확인 시 DELETE /cart/{id} 를 보낸다', async () => {
    const modal = loadLayout('partials/travel/_modal_cart_remove.json');
    t = createLayoutTest({ version: '1', layout_name: 'm', components: modal.children } as any, {
      componentRegistry: registry, translations, locale: 'ko', initialState: { _global: { travelCartRemoveTarget: { id: 12, title: '부산 바다 미식 2일' } } },
    });
    await t.render();
    expect(screen.getByText(/부산 바다 미식 2일/)).toBeInTheDocument();
    await t.user.click(screen.getByTestId('cart-remove-confirm'));
    await waitFor(() => {
      const call = vi.mocked(globalThis.fetch).mock.calls.find(([url, init]) => String(url).endsWith(`${API_BASE}/cart/12`) && (init as any)?.method === 'DELETE');
      expect(call).toBeDefined();
    });
  });
});
