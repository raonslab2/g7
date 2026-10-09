/**
 * @file product.test.tsx
 * @description 여행 상세 — 일정 · 출발일 선택 · 인원 · 장바구니 담기(가격 미전송) · 비회원 로그인 유도 · 404/오류
 *
 * @vitest-environment jsdom
 */
import { describe, it, expect, afterEach, beforeAll, vi } from 'vitest';
import { createLayoutTest, screen, waitFor } from '@core/template-engine/__tests__/utils/layoutTestUtils';
import { registerTemplateComponents, loadLayout, flatten, translations, API_BASE } from '../helpers/travelTestKit';

const product = {
  id: 7,
  title: '제주 오름 산책 3일',
  region: 'jeju',
  region_label: '제주',
  theme: 'nature',
  duration_days: 3,
  summary: '천천히 걷는 오름 여행',
  itinerary: [
    { day: 1, title: '도착 · 해안 산책', description: '공항 도착 후 해안길 산책' },
    { day: 2, title: '오름 트레킹' },
  ],
  from_price: 420000,
  currency_code: 'KRW',
  image_url: null,
};
const departures = {
  success: true,
  data: [
    { id: 71, product_id: 7, product_option_id: 701, departure_date: '2026-11-02', return_date: '2026-11-04', available: 2, unit_price: 420000, currency_code: 'KRW' },
    { id: 72, product_id: 7, product_option_id: 702, departure_date: '2026-11-09', return_date: '2026-11-11', available: 0, unit_price: 450000, currency_code: 'KRW' },
  ],
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

const layout = flatten(loadLayout('travel/product.json'));
const member = { _global: { currentUser: { id: 1, uuid: 'u-1', name: '라온' } } };

describe('travel/product', () => {
  it('일정 · 출발일 · 남은 좌석 · 마감을 그린다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '7' }, initialState: member });
    t.mockApi('product', { response: { success: true, data: product } });
    t.mockApi('departures', { response: departures });
    await t.render();
    expect(screen.getByTestId('product-title').textContent).toBe('제주 오름 산책 3일');
    expect(screen.getByText('도착 · 해안 산책')).toBeInTheDocument();
    expect(screen.getByText('오름 트레킹')).toBeInTheDocument();
    const options = screen.getAllByTestId('departure-option');
    expect(options).toHaveLength(2);
    expect(screen.getByText('2석 남음')).toBeInTheDocument();
    expect(screen.getByText('마감')).toBeInTheDocument();
    expect(options[1]).toBeDisabled();
    expect(screen.getByTestId('add-to-cart')).toBeDisabled();
    expect(screen.getByTestId('simulation-notice')).toBeInTheDocument();
  });

  it('출발일·인원을 고르면 예상 금액이 바뀌고, 좌석 수를 넘길 수 없다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '7' }, initialState: member });
    t.mockApi('product', { response: { success: true, data: product } });
    t.mockApi('departures', { response: departures });
    await t.render();
    await t.user.click(screen.getAllByTestId('departure-option')[0]);
    await waitFor(() => expect(screen.getByTestId('estimate').textContent).toContain('₩420,000'));
    await t.user.click(screen.getByTestId('people-increase'));
    await waitFor(() => expect(screen.getByTestId('people-value').textContent).toBe('2명'));
    expect(screen.getByTestId('estimate').textContent).toContain('₩840,000');
    expect(screen.getByTestId('people-increase')).toBeDisabled();
    expect(screen.getByTestId('add-to-cart')).not.toBeDisabled();
  });

  it('장바구니 담기는 departure_id · quantity 만 보낸다 (가격 미전송)', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '7' }, initialState: member });
    t.mockApi('product', { response: { success: true, data: product } });
    t.mockApi('departures', { response: departures });
    await t.render();
    const fetchSpy = vi.mocked(globalThis.fetch);
    await t.user.click(screen.getAllByTestId('departure-option')[0]);
    await t.user.click(screen.getByTestId('add-to-cart'));
    await waitFor(() => {
      const call = fetchSpy.mock.calls.find(([url, init]) => String(url).includes(`${API_BASE}/cart`) && (init as any)?.method === 'POST');
      expect(call).toBeDefined();
      expect(JSON.parse(String((call![1] as any).body))).toEqual({ departure_id: 71, quantity: 1 });
    });
  });

  it('비회원에게는 로그인 후 돌아오는 버튼을 보여준다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '7' } });
    t.mockApi('product', { response: { success: true, data: product } });
    t.mockApi('departures', { response: departures });
    await t.render();
    expect(screen.queryByTestId('add-to-cart')).toBeNull();
    await t.user.click(screen.getByTestId('login-to-add'));
    expect(t.getNavigationHistory().some((p) => p.startsWith('/login') && p.includes('redirect'))).toBe(true);
  });

  it('출발일이 없으면 안내 문구', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '7' }, initialState: member });
    t.mockApi('product', { response: { success: true, data: product } });
    t.mockApi('departures', { response: { success: true, data: [] } });
    await t.render();
    expect(screen.getByText('지금은 예정된 출발일이 없어요. 고객센터로 문의해 주세요.')).toBeInTheDocument();
  });

  it('없는 여행(404)은 화면 안에서 안내하고, 그 외 오류는 다시 시도를 준다', async () => {
    t = createLayoutTest(layout, {
      componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '999' },
      initialData: { _dataSourceErrors: { product: { status: 404, message: 'not found' } } },
    });
    t.mockApi('product', { response: null });
    t.mockApi('departures', { response: null });
    await t.render();
    expect(screen.getByTestId('product-not-found')).toBeInTheDocument();
    t.cleanup();

    t = createLayoutTest(layout, {
      componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '7' },
      initialData: { _dataSourceErrors: { product: { status: 500, message: 'boom' } } },
    });
    t.mockApi('product', { response: null });
    t.mockApi('departures', { response: null });
    await t.render();
    expect(screen.getByTestId('product-error')).toBeInTheDocument();
    expect(screen.getByTestId('product-retry')).toBeInTheDocument();
  });
});
