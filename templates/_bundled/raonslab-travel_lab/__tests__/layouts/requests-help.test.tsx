/**
 * @file requests-help.test.tsx
 * @description 내 상담 요청 목록/상세(상태·취소) · 고객센터(공지/FAQ/1:1 문의) · 베이스 · 로그인 · 오류 레이아웃
 *
 * @vitest-environment jsdom
 */
import { describe, it, expect, afterEach, beforeAll, vi } from 'vitest';
import { createLayoutTest, screen, waitFor } from '@core/template-engine/__tests__/utils/layoutTestUtils';
import { registerTemplateComponents, loadLayout, flatten, translations, API_BASE } from '../helpers/travelTestKit';

let registry: any;
beforeAll(() => {
  registry = registerTemplateComponents();
  (window as any).scrollTo = () => {};
});
let t: ReturnType<typeof createLayoutTest> | undefined;
afterEach(() => {
  t?.cleanup();
  t = undefined;
});
const member = { _global: { currentUser: { id: 1, uuid: 'u-1', name: '라온' } } };
const inquiry = (id: number, status: string) => ({
  id,
  status,
  can_cancel: ['TEST_INQUIRY', 'UNDER_REVIEW', 'TEST_ACCEPTED'].includes(status),
  created_at: '2026-10-09T10:00:00+09:00',
  contact: { name: '라온', phone: null },
  items: [{ id: 1, product_id: 7, product_name: { ko: '제주 오름 산책 3일', en: 'Jeju Walk' }, departure_date: '2026-11-02', return_date: '2026-11-04', quantity: 2, unit_price: 420000, currency_code: 'KRW' }],
  total_amount: 840000,
  currency_code: 'KRW',
});

describe('travel/requests', () => {
  const layout = flatten(loadLayout('travel/requests.json'));

  it('요청 카드와 상태 배지를 그린다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialState: member });
    t.mockApi('inquiries', { response: { success: true, data: { data: [inquiry(3, 'TEST_INQUIRY'), inquiry(2, 'DECLINED')], pagination: { current_page: 1, last_page: 1, has_more_pages: false, total: 2 } } } });
    await t.render();
    expect(screen.getAllByTestId('request-card')).toHaveLength(2);
    expect(screen.getAllByTestId('request-status').map((b) => b.getAttribute('data-status'))).toEqual(['TEST_INQUIRY', 'DECLINED']);
    await t.user.click(screen.getAllByTestId('request-card')[0]);
    expect(t.getNavigationHistory().some((p) => p.includes('/travel/requests/3'))).toBe(true);
  });

  it('긴 상품명과 요청 ID를 보존하고 키보드로 상세를 연다', async () => {
    const title = 'SyntheticUnbrokenProductTitleForWidthRegression0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialState: member });
    t.mockApi('inquiries', { response: { success: true, data: {
      data: [{ ...inquiry(100, 'TEST_ACCEPTED'), first_product_name: title }],
      pagination: { current_page: 1, last_page: 1, total: 1 },
    } } });
    await t.render();
    const card = screen.getByTestId('request-card');
    expect(card).toHaveAttribute('type', 'button');
    expect(card).toHaveTextContent('요청 #100');
    expect(screen.getByTestId('request-status')).toHaveAttribute('data-status', 'TEST_ACCEPTED');
    const productTitle = screen.getByText(title);
    expect(productTitle.className).not.toMatch(/line-clamp|truncate/);
    card.focus();
    await t.user.keyboard('{Enter}');
    expect(t.getNavigationHistory().some((p) => p.includes('/travel/requests/100'))).toBe(true);
  });

  it('요청이 없으면 장바구니로 안내', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', initialState: member });
    t.mockApi('inquiries', { response: { success: true, data: { data: [], pagination: { current_page: 1, last_page: 1, total: 0 } } } });
    await t.render();
    expect(screen.getByTestId('requests-empty')).toBeInTheDocument();
  });
});

describe('travel/request_detail', () => {
  const layout = flatten(loadLayout('travel/request_detail.json'));

  it.each([
    ['TEST_INQUIRY', true],
    ['UNDER_REVIEW', true],
    ['TEST_ACCEPTED', true],
    ['DECLINED', false],
    ['CANCELLED', false],
  ])('%s 상태 — 취소 버튼 노출=%s', async (status, cancellable) => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '3' }, initialState: member });
    t.mockApi('inquiry', { response: { success: true, data: inquiry(3, status) } });
    await t.render();
    expect(screen.getByTestId('detail-status').getAttribute('data-status')).toBe(status);
    expect(!!screen.queryByTestId('cancel-request')).toBe(cancellable);
    expect(screen.getByTestId('simulation-notice')).toBeInTheDocument();
  });

  it('서버가 can_cancel 을 주면 그 판정을 따른다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '3' }, initialState: member });
    t.mockApi('inquiry', { response: { success: true, data: { ...inquiry(3, 'UNDER_REVIEW'), can_cancel: false } } });
    await t.render();
    expect(screen.queryByTestId('cancel-request')).toBeNull();
  });

  it('없는 요청(404)은 목록으로 안내', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '9' }, initialState: member, initialData: { _dataSourceErrors: { inquiry: { status: 404, message: 'x' } } } });
    t.mockApi('inquiry', { response: null });
    await t.render();
    expect(screen.getByTestId('request-not-found')).toBeInTheDocument();
  });

  it('취소 확인 모달은 POST /inquiries/{id}/cancel 을 보낸다', async () => {
    const modal = loadLayout('partials/travel/_modal_request_cancel.json');
    t = createLayoutTest({ version: '1', layout_name: 'm', components: modal.children } as any, { componentRegistry: registry, translations, locale: 'ko', initialState: { _global: { travelCancelTargetId: 3 } } });
    await t.render();
    await t.user.click(screen.getByTestId('cancel-confirm'));
    await waitFor(() => {
      const call = vi.mocked(globalThis.fetch).mock.calls.find(([url, init]) => String(url).endsWith(`${API_BASE}/inquiries/3/cancel`) && (init as any)?.method === 'POST');
      expect(call).toBeDefined();
    });
  });
});

describe('travel/help', () => {
  const layout = flatten(loadLayout('travel/help.json'));

  it('공지 탭(기본) — 항목을 펼친다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko' });
    t.mockApi('notices', { response: { success: true, data: { data: [{ id: 1, title: '가을 시즌 안내', created_at: '2026-10-01' }], meta: { current_page: 1, last_page: 1 } } } });
    await t.render();
    expect(screen.getByTestId('help-panel-notices')).toBeInTheDocument();
    expect(screen.queryByTestId('help-panel-faq')).toBeNull();
    const originalFetch = globalThis.fetch;
    globalThis.fetch = vi.fn(async (url: any, init: any) => String(url).endsWith('/support/notices/1') ? new Response(JSON.stringify({success:true,data:{id:1,title:'가을 시즌 안내',content:'가을 일정이 열렸어요'}}),{status:200,headers:{'Content-Type':'application/json'}}) : (originalFetch as any)(url,init));
    await t.user.click(screen.getByTestId('notice-item'));
    await waitFor(() => expect(screen.getByText('가을 일정이 열렸어요')).toBeInTheDocument());
  });

  it('FAQ 탭 — 검색어로 거른다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', queryParams: { tab: 'faq' } });
    t.mockApi('faqs', { response: { success: true, data: { data: [{ id: 1, title: '인원 변경은 어떻게 하나요?' }, { id: 2, title: '결제는 언제 하나요?' }] } } });
    await t.render();
    expect(screen.getAllByTestId('faq-item')).toHaveLength(2);
    await t.user.type(screen.getByTestId('faq-search'), '결제');
    await waitFor(() => expect(screen.getAllByTestId('faq-item')).toHaveLength(1));
  });

  it('1:1 문의 탭 — 비회원은 로그인 안내', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', queryParams: { tab: 'questions' } });
    await t.render();
    expect(screen.getByTestId('questions-login')).toBeInTheDocument();
    expect(screen.queryByTestId('question-form')).toBeNull();
  });

  it('1:1 문의 탭 — 회원은 문의를 보내고 내역·답변 상태를 본다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', queryParams: { tab: 'questions' }, initialState: member });
    t.mockApi('questions', { response: { success: true, data: { data: [{ id: 5, title: '단체 여행 문의', created_at: '2026-10-08', answers_count: 1 }, { id: 6, title: '아이 동반', created_at: '2026-10-09', answers_count: 0 }], meta: { current_page: 1, last_page: 1 } } } });
    await t.render();
    expect(screen.getAllByTestId('question-item')).toHaveLength(2);
    expect(screen.getByText('답변 완료')).toBeInTheDocument();
    expect(screen.getByText('답변 대기')).toBeInTheDocument();
    expect(screen.getByTestId('question-submit')).toBeDisabled();
    await t.user.type(screen.getByTestId('question-title'), '일정 문의');
    await t.user.type(screen.getByTestId('question-content'), '11월 출발 가능할까요?');
    await waitFor(() => expect(screen.getByTestId('question-submit')).not.toBeDisabled());
    await t.user.click(screen.getByTestId('question-submit'));
    await waitFor(() => {
      const call = vi.mocked(globalThis.fetch).mock.calls.find(([url, init]) => String(url).endsWith(`${API_BASE}/support/questions`) && (init as any)?.method === 'POST');
      expect(call).toBeDefined();
      expect(JSON.parse(String((call![1] as any).body))).toEqual({ title: '일정 문의', content: '11월 출발 가능할까요?' });
    });
  });

  it('탭 전환은 고객센터 경로로 이동한다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko' });
    t.mockApi('notices', { response: { success: true, data: { data: [] } } });
    await t.render();
    await t.user.click(screen.getByTestId('help-tab-faq'));
    expect(t.getNavigationHistory().some((p) => p.startsWith('/travel/help') && p.includes('tab=faq'))).toBe(true);
  });

  it('비공개 문의 상세는 실제 answers 배열의 답변 본문을 그린다', async () => {
    t = createLayoutTest(layout, { componentRegistry: registry, translations, locale: 'ko', queryParams: { tab: 'questions', question: '5' }, initialState: member });
    t.mockApi('questions', { response: { success: true, data: { data: [], meta: { current_page: 1, last_page: 1 } } } });
    t.mockApi('question_detail', { response: { success: true, data: {
      id: 5, title: '합성 비공개 문의', content: '출발 일정 확인 요청', answers_count: 1,
      answers: [{ id: 10, is_author: false, content: '시험 일정은 관리자 화면에서 확인했습니다.', created_at: '2026-10-09' }],
    } } });
    await t.render();
    expect(screen.getByText('출발 일정 확인 요청')).toBeInTheDocument();
    expect(screen.getByText('시험 일정은 관리자 화면에서 확인했습니다.')).toBeInTheDocument();
  });
});

describe('_user_base · auth/login · errors', () => {
  it('베이스: 테스트 고지 · 비회원 로그인 버튼 · 탭바', async () => {
    const base = loadLayout('_user_base.json');
    t = createLayoutTest({ version: '1', layout_name: 'base', data_sources: base.data_sources, components: base.components } as any, { componentRegistry: registry, translations, locale: 'ko' });
    t.mockApi('travel_cart_badge', { response: { success: true, data: { items: [] } } });
    t.mockApi('current_user', { response: { data: null } });
    await t.render();
    expect(screen.getByTestId('test-notice-bar')).toBeInTheDocument();
    expect(screen.getByTestId('header-login')).toBeInTheDocument();
    expect(screen.queryByTestId('header-logout')).toBeNull();
    ['home', 'search', 'cart', 'requests', 'help'].forEach((k) => expect(screen.getByTestId(`tab-${k}`)).toBeInTheDocument());
  });

  it('베이스: 회원은 로그아웃과 장바구니 개수 배지', async () => {
    const base = loadLayout('_user_base.json');
    t = createLayoutTest({ version: '1', layout_name: 'base', data_sources: base.data_sources, components: base.components } as any, { componentRegistry: registry, translations, locale: 'ko', initialState: member });
    t.mockApi('travel_cart_badge', { response: { success: true, data: { items: [{ id: 1 }, { id: 2 }] } } });
    await t.render();
    expect(screen.getByTestId('header-logout')).toBeInTheDocument();
    expect(screen.getByTestId('cart-badge').textContent).toBe('2');
  });

  it('로그인: 이메일·비밀번호 폼과 제출 버튼', async () => {
    t = createLayoutTest(flatten(loadLayout('auth/login.json')), { componentRegistry: registry, translations, locale: 'ko' });
    await t.render();
    expect(screen.getByTestId('login-email')).toBeInTheDocument();
    expect(screen.getByTestId('login-password')).toBeInTheDocument();
    expect(screen.getByTestId('login-submit')).toHaveAttribute('type', 'submit');
    expect(screen.queryByTestId('login-2fa-code')).toBeNull();
  });

  it('로그인: 2단계 인증 응답 상태면 인증번호 입력으로 바뀐다', async () => {
    t = createLayoutTest(flatten(loadLayout('auth/login.json')), { componentRegistry: registry, translations, locale: 'ko' });
    await t.render();
    // 진입 시 init_actions 가 2단계 상태를 비운 뒤, 로그인 응답이 two_factor_required 를 실은 상황
    t.setState('twoFactor', { required: true, challenge_id: 'c1', code: '' }, 'global');
    await t.rerender();
    expect(screen.getByTestId('login-2fa-code')).toBeInTheDocument();
    expect(screen.queryByTestId('login-email')).toBeNull();
  });

  it.each(['401', '403', '404', '500', '503', 'maintenance'])('오류 %s 화면은 제목과 홈 버튼을 갖는다', async (code) => {
    t = createLayoutTest(flatten(loadLayout(`errors/${code}.json`)), { componentRegistry: registry, translations, locale: 'ko' });
    await t.render();
    expect(screen.getByTestId('error-title').textContent?.length).toBeGreaterThan(3);
    await t.user.click(screen.getByTestId('error-home'));
    expect(t.getNavigationHistory()).toContain('/travel');
  });
});
