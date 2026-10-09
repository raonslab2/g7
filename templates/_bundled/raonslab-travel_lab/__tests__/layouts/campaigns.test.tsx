/**
 * @file campaigns.test.tsx
 * @description 기획전(native 페이지 고정 두 슬롯) 고객 화면 — 홈 카드 · 목록 · 상세(본문/버전/테마 여행) 상태
 *
 * 응답 모양은 여행 모듈 CampaignApiTest 가 실제 native PageService 로 고정한 계약과 같다.
 *
 * @vitest-environment jsdom
 */
import { describe, it, expect, afterEach, beforeAll } from 'vitest';
import { createLayoutTest, screen } from '@core/template-engine/__tests__/utils/layoutTestUtils';
import routesJson from '../../routes.json';
import { registerTemplateComponents, loadLayout, flatten, translations, findAll, API_BASE } from '../helpers/travelTestKit';

const AUTUMN = 'travel-lab-campaign-autumn-escape';
const WEEKEND = 'travel-lab-campaign-weekend-reset';
const item = (slug: string, extra: Record<string, any> = {}) => ({
  slug,
  kind: 'campaign',
  theme: slug === AUTUMN ? 'nature' : 'wellness',
  title: slug === AUTUMN ? '가을 숲 기획전' : '주말 리셋 기획전',
  excerpt: '숲길을 천천히 걷는 일정',
  current_version: 3,
  published_at: '2026-10-09 21:00:00',
  path: `/travel/campaigns/${slug}`,
  catalog_query: { theme: slug === AUTUMN ? 'nature' : 'wellness', sort: 'recommended' },
  art_variant: slug === AUTUMN ? 'forest' : 'lake',
  ...extra,
});
const list = (items: any[]) => ({ success: true, message: 'ok', data: { items } });
const detail = (extra: Record<string, any> = {}) => ({ success: true, message: 'ok', data: { ...item(AUTUMN), content: '첫 줄\n둘째 줄', content_mode: 'text', updated_at: '2026-10-09 21:00:00', ...extra } });
const trip = (id: number) => ({ id, title: `숲 여행 ${id}`, region: 'jeju', theme: 'nature', duration_days: 2, summary: '요약', itinerary: [], from_price: 120000, currency_code: 'KRW', image_url: null, departures: [] });
const trips = (rows: any[]) => ({ success: true, data: { data: rows, pagination: { current_page: 1, per_page: 6, last_page: 1, total: rows.length, has_more_pages: false } } });
const homeBase = (t: any) => {
  t.mockApi('facets', { response: { success: true, data: { region: [], theme: [] } } });
  t.mockApi('featured_trips', { response: trips([]) });
  t.mockApi('departing_trips', { response: trips([]) });
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

describe('travel/home 기획전', () => {
  const layout = flatten(loadLayout('travel/home.json'));

  it('발행된 기획전만 카드로 그리고 카드는 서버 path 로 이동한다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko' });
    homeBase(t);
    t.mockApi('campaigns', { response: list([item(AUTUMN), item(WEEKEND)]) });
    await t.render();
    expect(screen.getAllByTestId('campaign-card')).toHaveLength(2);
    expect(screen.getByText('가을 숲 기획전')).toBeInTheDocument();
    await t.user.click(screen.getAllByTestId('campaign-card')[1]);
    expect(t.getNavigationHistory().some((p) => p.includes(`/travel/campaigns/${WEEKEND}`))).toBe(true);
  });

  it('정적 계절 배너 문구가 남아 있지 않다 (성공 대체 문구 없음)', () => {
    const text = JSON.stringify(loadLayout('travel/home.json'));
    expect(text).not.toContain('campaign_eyebrow');
    expect(text).not.toContain('campaign_cta');
    expect((translations as any).travel.home.campaign_title).toBeUndefined();
  });

  it('발행된 기획전이 없으면 섹션을 숨긴다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko' });
    homeBase(t);
    t.mockApi('campaigns', { response: list([]) });
    await t.render();
    expect(screen.queryByTestId('home-campaigns')).toBeNull();
    expect(screen.queryByTestId('campaign-card')).toBeNull();
  });

  it('응답 전 로딩, 오류 시 다시 시도', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialData: { campaigns: undefined } });
    homeBase(t);
    t.mockApi('campaigns', { response: undefined });
    await t.render();
    expect(screen.getByTestId('home-campaigns-loading')).toBeInTheDocument();
    t.cleanup();

    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialData: { _dataSourceErrors: { campaigns: { status: 500, message: 'x' } } } });
    homeBase(t);
    t.mockApi('campaigns', { response: null });
    await t.render();
    expect(screen.getByTestId('home-campaigns-error')).toBeInTheDocument();
    expect(screen.getByTestId('home-campaigns-retry')).toBeInTheDocument();
    expect(screen.queryByTestId('campaign-card')).toBeNull();
  });
});

describe('travel/campaigns 목록', () => {
  const layout = flatten(loadLayout('travel/campaigns.json'));

  it('목록·빈 상태·오류가 서로 다르다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko' });
    t.mockApi('campaigns', { response: list([item(AUTUMN)]) });
    await t.render();
    expect(screen.getAllByTestId('campaign-card')).toHaveLength(1);
    expect(screen.queryByTestId('campaigns-empty')).toBeNull();
    t.cleanup();

    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko' });
    t.mockApi('campaigns', { response: list([]) });
    await t.render();
    expect(screen.getByTestId('campaigns-empty')).toBeInTheDocument();
    expect(screen.getByText('지금 진행 중인 기획전이 없어요')).toBeInTheDocument();
    expect(screen.queryByTestId('campaigns-error')).toBeNull();
    t.cleanup();

    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialData: { _dataSourceErrors: { campaigns: { status: 503, message: 'x' } } } });
    t.mockApi('campaigns', { response: null });
    await t.render();
    expect(screen.getByTestId('campaigns-error')).toBeInTheDocument();
    expect(screen.queryByTestId('campaigns-empty')).toBeNull();
  });
});

describe('travel/campaign_detail', () => {
  const raw = loadLayout('travel/campaign_detail.json');
  const layout = flatten(raw);

  it('저장된 본문·버전·서버 고정 테마로 이동하는 CTA 와 실제 여행 카드를 그린다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { slug: AUTUMN }, initialData: { campaign_trips: trips([trip(7)]) } });
    t.mockApi('campaign', { response: detail() });
    await t.render();
    expect(screen.getByTestId('campaign-title').textContent).toBe('가을 숲 기획전');
    expect(screen.getByTestId('campaign-version').textContent).toContain('3');
    expect(screen.getByTestId('campaign-body').textContent).toBe('첫 줄\n둘째 줄');
    expect(screen.getAllByTestId('trip-card')).toHaveLength(1);
    await t.user.click(screen.getByTestId('campaign-search-cta'));
    const last = t.getNavigationHistory().pop() ?? '';
    expect(last).toContain('/travel/search');
    expect(last).toContain('theme=nature');
    await t.user.click(screen.getByTestId('trip-card'));
    expect(t.getNavigationHistory().some((p) => p.includes('/travel/products/7'))).toBe(true);
  });

  it('HTML 본문은 서식만 남고 스크립트·이미지·링크 주소가 렌더되지 않는다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { slug: AUTUMN }, initialData: { campaign_trips: trips([]) } });
    t.mockApi('campaign', { response: detail({ content_mode: 'html', content: '<p onclick="x()">숲 <strong>산책</strong></p><img src="https://evil.example/a.png"><a href="javascript:alert(1)">링크</a><script>alert(1)</script>' }) });
    await t.render();
    const body = screen.getByTestId('campaign-body');
    expect(body.querySelector('strong')?.textContent).toBe('산책');
    expect(body.querySelector('img,a,script,[onclick]')).toBeNull();
    expect(body.textContent).toContain('링크');
  });

  it('테마 여행이 없으면 빈 상태와 전체 여행 이동, 응답 전이면 로딩', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { slug: AUTUMN }, initialData: { campaign_trips: trips([]) } });
    t.mockApi('campaign', { response: detail() });
    await t.render();
    expect(screen.getByTestId('campaign_trips-empty')).toBeInTheDocument();
    expect(screen.getByTestId('campaign-body')).toBeInTheDocument();
    t.cleanup();

    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { slug: AUTUMN } });
    t.mockApi('campaign', { response: detail() });
    await t.render();
    expect(screen.getByTestId('campaign_trips-loading')).toBeInTheDocument();
    t.cleanup();

    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { slug: AUTUMN }, initialData: { campaign_trips: null, _dataSourceErrors: { campaign_trips: { status: 500, message: 'x' } } } });
    t.mockApi('campaign', { response: detail() });
    await t.render();
    expect(screen.getByTestId('campaign_trips-error')).toBeInTheDocument();
    expect(screen.getByTestId('campaign_trips-retry')).toBeInTheDocument();
  });

  it('404 는 안내 화면, 그 밖의 오류는 다시 시도 — 서로 다르다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { slug: 'travel-lab-campaign-gone' }, initialData: { _dataSourceErrors: { campaign: { status: 404, message: 'x' } } } });
    t.mockApi('campaign', { response: null });
    await t.render();
    expect(screen.getByTestId('campaign-not-found')).toBeInTheDocument();
    expect(screen.queryByTestId('campaign-error')).toBeNull();
    expect(screen.queryByTestId('campaign-detail')).toBeNull();
    t.cleanup();

    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { slug: AUTUMN }, initialData: { _dataSourceErrors: { campaign: { status: 500, message: 'x' } } } });
    t.mockApi('campaign', { response: null });
    await t.render();
    expect(screen.getByTestId('campaign-error')).toBeInTheDocument();
    expect(screen.getByTestId('campaign-retry')).toBeInTheDocument();
    expect(screen.queryByTestId('campaign-not-found')).toBeNull();
    t.cleanup();

    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { slug: AUTUMN }, initialData: { campaign: undefined } });
    t.mockApi('campaign', { response: undefined });
    await t.render();
    expect(screen.getByTestId('campaign-loading')).toBeInTheDocument();
  });

  it('카탈로그 필터는 상세 응답의 catalog_query 에서만 오고, 상세 성공 뒤에만 조회한다', () => {
    const ds = Object.fromEntries(raw.data_sources.map((d: any) => [d.id, d]));
    expect(ds.campaign.endpoint).toBe(`${API_BASE}/campaigns/{{route.slug}}`);
    expect(ds.campaign.auth_mode).toBe('optional');
    expect(ds.campaign_trips.endpoint).toBe(`${API_BASE}/catalog`);
    expect(ds.campaign_trips.auto_fetch).toBe(false);
    expect(Object.keys(ds.campaign_trips.params).sort()).toEqual(['per_page', 'sort', 'theme']);
    expect(ds.campaign_trips.params.theme).toContain('_global.travelCampaignCatalog');
    const chain = ds.campaign.onSuccess.actions;
    expect(chain[0]).toMatchObject({ handler: 'setState', params: { target: 'global', travelCampaignCatalog: '{{response.data.data.catalog_query ?? null}}' } });
    expect(chain[1]).toMatchObject({ handler: 'refetchDataSource', params: { dataSourceId: 'campaign_trips' } });
    // 기획전 쪽 조회·이동은 금액을 싣지 않는다 (금액 표시는 재사용한 카탈로그 카드의 서버 PriceTag 뿐)
    expect(JSON.stringify(raw.data_sources)).not.toMatch(/price|discount|amount/i);
    const cta = findAll(raw, (n) => n.props?.['data-testid'] === 'campaign-search-cta')[0];
    expect(Object.keys(cta.actions[0].params.query).sort()).toEqual(['sort', 'theme']);
  });

  it('/travel/campaigns/:slug 와 /page/:slug 가 같은 레지스트리 제한 소비자를 쓴다', () => {
    const routes = (routesJson as any).routes as any[];
    const by = Object.fromEntries(routes.map((r) => [r.path, r]));
    expect(by['/travel/campaigns'].layout).toBe('travel/campaigns');
    expect(by['/travel/campaigns/:slug'].layout).toBe('travel/campaign_detail');
    expect(by['/page/:slug'].layout).toBe('travel/campaign_detail');
    ['/travel/campaigns', '/travel/campaigns/:slug', '/page/:slug'].forEach((p) => expect(by[p].auth_required).toBe(false));
  });

  it('모든 기획전 버튼은 키보드로 접근 가능한 버튼이다', () => {
    [raw, loadLayout('travel/campaigns.json')].forEach((json) =>
      findAll(json, (n) => n.name === 'Button').forEach((b) => {
        expect(b.props.type).toBe('button');
        expect(b.actions?.length).toBeGreaterThan(0);
      }),
    );
  });
});
