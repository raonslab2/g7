/**
 * @file home-search.test.tsx
 * @description 여행 홈 · 검색 레이아웃 렌더링 — 실제 템플릿 컴포넌트로 로딩/오류/빈/목록 상태
 *
 * @vitest-environment jsdom
 */
import { describe, it, expect, afterEach, beforeAll } from 'vitest';
import { createLayoutTest, screen } from '@core/template-engine/__tests__/utils/layoutTestUtils';
import { registerTemplateComponents, loadLayout, flatten, translations } from '../helpers/travelTestKit';

const trip = (id: number, extra: Record<string, any> = {}) => ({
  id,
  title: `여행 ${id}`,
  region: 'jeju',
  region_label: '제주',
  theme: 'nature',
  theme_label: '자연',
  duration_days: 3,
  summary: `요약 ${id}`,
  itinerary: [],
  from_price: 450000,
  currency_code: 'KRW',
  image_url: null,
  departures: [{ id: id * 10, product_id: id, product_option_id: id * 100, departure_date: '2026-11-02', return_date: '2026-11-04', available: 5, unit_price: 450000, currency_code: 'KRW' }],
  ...extra,
});
const page = (items: any[], pagination: Record<string, any> = {}) => ({
  success: true,
  message: 'ok',
  data: { data: items, pagination: { current_page: 1, per_page: 12, last_page: 1, total: items.length, has_more_pages: false, ...pagination } },
});

let registry: any;
beforeAll(() => {
  registry = registerTemplateComponents();
});

let t: ReturnType<typeof createLayoutTest> | undefined;
afterEach(() => {
  t?.cleanup();
  t = undefined;
});

describe('travel/home', () => {
  const layout = flatten(loadLayout('travel/home.json'));

  it('추천·출발 임박 여행 카드와 지역·테마를 API 응답으로 그린다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko' });
    t.mockApi('facets', { response: { success: true, data: { region: [{ value: 'jeju', label: '제주', count: 4 }, { value: 'busan', label: '부산' }], theme: [{ value: 'nature', label: '자연' }] } } });
    t.mockApi('featured_trips', { response: page([trip(1), trip(2)]) });
    t.mockApi('departing_trips', { response: page([trip(3)]) });
    await t.render();

    expect(screen.getByTestId('home-hero')).toBeInTheDocument();
    expect(screen.getAllByTestId('trip-card')).toHaveLength(3);
    expect(screen.getByText('여행 1')).toBeInTheDocument();
    expect(screen.getAllByText('₩450,000').length).toBeGreaterThan(0);
    expect(screen.getAllByText('제주').length).toBeGreaterThan(0);
    expect(screen.getByText('부산')).toBeInTheDocument();
    expect(screen.getAllByTestId('theme-card')).toHaveLength(1);
  });

  it('여행 목록이 비면 빈 상태 안내', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko' });
    t.mockApi('facets', { response: { success: true, data: { region: [], theme: [] } } });
    t.mockApi('featured_trips', { response: page([]) });
    t.mockApi('departing_trips', { response: page([]) });
    await t.render();
    expect(screen.getAllByText('곧 새로운 여행이 준비돼요')).toHaveLength(2);
  });

  it('응답 전에는 로딩 스켈레톤', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialData: { featured_trips: undefined } });
    t.mockApi('facets', { response: { success: true, data: { region: [], theme: [] } } });
    t.mockApi('departing_trips', { response: page([]) });
    t.mockApi('featured_trips', { response: undefined });
    await t.render();
    expect(screen.getByTestId('featured_trips-loading')).toBeInTheDocument();
  });

  it('카드를 누르면 상세로 이동한다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko' });
    t.mockApi('facets', { response: { success: true, data: { region: [], theme: [] } } });
    t.mockApi('featured_trips', { response: page([trip(7)]) });
    t.mockApi('departing_trips', { response: page([]) });
    await t.render();
    await t.user.click(screen.getByTestId('trip-card'));
    expect(t.getNavigationHistory().some((p) => p.includes('/travel/products/7'))).toBe(true);
  });
});

describe('travel/search', () => {
  const layout = flatten(loadLayout('travel/search.json'));

  it('조건 결과·건수·적용 조건 수를 그린다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', queryParams: { region: 'jeju', sort: 'price_asc' } });
    t.mockApi('facets', { response: { success: true, data: { region: [{ value: 'jeju', label: '제주' }], theme: [] } } });
    t.mockApi('catalog', { response: page([trip(1), trip(2)], { total: 2 }) });
    await t.render();
    expect(screen.getAllByTestId('trip-card')).toHaveLength(2);
    expect(screen.getByTestId('result-count').textContent).toContain('2');
    expect(screen.getByText('조건 1개 적용')).toBeInTheDocument();
    expect(screen.getByTestId('search-filters')).toBeInTheDocument();
  });

  it('결과가 없으면 초기화 버튼이 있는 빈 상태', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', queryParams: { q: '없는여행' } });
    t.mockApi('facets', { response: { success: true, data: { region: [], theme: [] } } });
    t.mockApi('catalog', { response: page([]) });
    await t.render();
    expect(screen.getByTestId('catalog-empty')).toBeInTheDocument();
    expect(screen.getByText('조건 초기화')).toBeInTheDocument();
  });

  it('여러 페이지면 페이지 이동을 노출한다 (총 건수 상한으로 last_page=null 이어도)', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', queryParams: {} });
    t.mockApi('facets', { response: { success: true, data: { region: [], theme: [] } } });
    t.mockApi('catalog', { response: page([trip(1)], { last_page: null, total: null, has_more_pages: true }) });
    await t.render();
    expect(document.querySelector('nav[aria-label], [aria-label="common.pagination"], [aria-label="페이지 이동"]')).not.toBeNull();
  });
});
