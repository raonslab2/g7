/** Native layout-engine rendering against the merged Resources' actual shape.
 * These adapter tests are not live API/E2E or permission validation. */
import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import fs from 'node:fs';
import path from 'node:path';
import { createLayoutTest, screen, waitFor } from '@core/template-engine/__tests__/utils/layoutTestUtils';
import { registerTemplateComponents, flatten, findAll, TEMPLATE_ROOT } from '../helpers/travelTestKit';

const moduleRoot = path.resolve(TEMPLATE_ROOT, '../../../modules/_bundled/raonslab-travel_lab');
const loadAdmin = (name: string) => JSON.parse(fs.readFileSync(path.join(moduleRoot, 'resources/layouts/admin', `${name}.json`), 'utf8'));
const translations = { 'raonslab-travel_lab': { admin: JSON.parse(fs.readFileSync(path.join(moduleRoot, 'resources/lang/partial/ko/admin.json'), 'utf8')) } };
let registry: any;
let test: ReturnType<typeof createLayoutTest> | undefined;
beforeAll(() => { registry = registerTemplateComponents(); });
afterEach(() => { test?.cleanup(); test = undefined; });

describe('native travel admin catalog', () => {
  it('catalog interactions do not wait for the optional unfetched candidate selector', () => {
    const layout=loadAdmin('admin_travel_lab_catalog');
    expect(layout.data_sources.find((d:any)=>d.id==='travel_candidates').auto_fetch).toBe(false);
    const blur=findAll(layout,(n:any)=>!!n.blur_until_loaded).map((n:any)=>n.blur_until_loaded);
    expect(blur).toEqual([{enabled:true,data_sources:['catalog']}]);
  });

  it('candidate fetch page size satisfies the actual backend FormRequest boundary', () => {
    const candidateSource=loadAdmin('admin_travel_lab_catalog').data_sources.find((d:any)=>d.id==='travel_candidates');
    const backendRequest=fs.readFileSync(path.join(moduleRoot,'src/Http/Requests/CatalogCandidatesRequest.php'),'utf8');
    const pageRules=backendRequest.match(/'per_page'\s*=>\s*\[([^\]]+)\]/)?.[1];
    expect(pageRules).toBeDefined();
    const maximum=Number(pageRules!.match(/max:(\d+)/)?.[1]);
    const minimum=Number(pageRules!.match(/min:(\d+)/)?.[1]);
    expect(Number.isFinite(maximum)).toBe(true);
    expect(candidateSource.params.per_page).toBeGreaterThanOrEqual(minimum);
    expect(candidateSource.params.per_page).toBeLessThanOrEqual(maximum);
    expect(candidateSource.endpoint).toBe('/api/modules/raonslab-travel_lab/admin/catalog/candidates');
  });

  it('actual product title, reserved count and pagination render; publication PATCH sends no prices', async () => {
    test = createLayoutTest(flatten(loadAdmin('admin_travel_lab_catalog')), { componentRegistry: registry, translations, locale: 'ko' });
    test.mockApi('catalog', { response: { success: true, data: {
      data: [{ id: 7, product_code: 'SYNTH-007', title: '합성 제주 산책', published: true, departures: [{ id: 71, product_option_id: 901, departure_date: '2026-11-01', return_date: '2026-11-03', capacity: 20, reserved: 3, is_active: true }] }],
      pagination: { current_page: 1, last_page: 2, total: 21 }, abilities: { can_update: true },
    } } });
    await test.render();
    // Match the actual public SDK: get() returns global contents directly, not a wrapper.
    (window as any).G7Core.state.get = () => test!.getState()._global;
    expect(screen.getByText('합성 제주 산책')).toBeInTheDocument();
    expect(screen.getByText('정원 20 · 문의 인원 3')).toBeInTheDocument();
    expect(screen.getByText('다음')).toBeInTheDocument();
    await test.user.click(screen.getByText('게시 중지'));
    await waitFor(() => {
      const call = vi.mocked(globalThis.fetch).mock.calls.find(([url, init]) => String(url).endsWith('/admin/catalog/7') && init?.method === 'PATCH');
      expect(call).toBeDefined();
      expect(JSON.parse(String(call![1]!.body))).toEqual({ published: false });
    });
  });

  it('departure editor submits Departure.id and full native PUT date/capacity/active contract', async () => {
    const modal = findAll(loadAdmin('admin_travel_lab_catalog'), n => n.id === 'travel_departure_modal')[0];
    test = createLayoutTest({ version: '1.0.0', layout_name: 'departure_editor', components: modal.children }, {
      componentRegistry: registry, translations, locale: 'ko', initialState: { _global: { travelDepartureEdit: {
        id: 71, product_id: 7, product_option_id: 901, departure_date: '2026-11-01', return_date: '2026-11-03', capacity: 20, reserved: 3, is_active: true,
      } } },
    });
    await test.render();
    // Match the actual public SDK: get() returns global contents directly, not a wrapper.
    (window as any).G7Core.state.get = () => test!.getState()._global;
    expect(screen.getByTestId('departure_product_option_id_input')).toBeDisabled();
    await test.user.click(screen.getByTestId('departure_save'));
    await waitFor(() => {
      const call = vi.mocked(globalThis.fetch).mock.calls.find(([url, init]) => String(url).endsWith('/admin/catalog/7/departures/71') && init?.method === 'PUT');
      expect(call).toBeDefined();
      expect(JSON.parse(String(call![1]!.body))).toEqual({ product_option_id: 901, departure_date: '2026-11-01', return_date: '2026-11-03', capacity: 20, is_active: true });
    });
  });

  it('new departure selects a real native product option and POSTs its ID', async () => {
    const modal=findAll(loadAdmin('admin_travel_lab_catalog'), n => n.id==='travel_departure_modal')[0];
    test=createLayoutTest({version:'1.0.0',layout_name:'new_departure',components:modal.children},{componentRegistry:registry,translations,locale:'ko',initialState:{_global:{travelDepartureEdit:{id:null,product_id:19,product_option_id:null,departure_date:'2026-11-01',return_date:'2026-11-02',capacity:3,reserved:0,is_active:true,options:[{id:903,option_name:'검증 출발',stock_quantity:5,is_active:true},{id:904,option_name:'비활성 옵션',stock_quantity:9,is_active:false}]}}}});
    await test.render();
    // Match the actual public SDK: get() returns global contents directly, not a wrapper.
    (window as any).G7Core.state.get = () => test!.getState()._global;
    expect(screen.queryByTestId('departure_product_option_id_input')).toBeNull();
    await test.user.click(screen.getByTestId('departure_product_option_select').querySelector('button')!);
    expect(screen.queryByRole('option',{name:/비활성 옵션/})).toBeNull();
    await test.user.click(screen.getByRole('option',{name:/검증 출발/}));
    await test.rerender();
    await test.user.click(screen.getByTestId('departure_save'));
    await waitFor(() => {const call=vi.mocked(globalThis.fetch).mock.calls.find(([url,init]) => String(url).endsWith('/admin/catalog/19/departures') && init?.method==='POST');expect(call).toBeDefined();expect(JSON.parse(String(call![1]!.body))).toEqual({product_option_id:903,departure_date:'2026-11-01',return_date:'2026-11-02',capacity:3,is_active:true});});
  });

  it('new travel registration posts native product identity and full translated metadata, without prices', async () => {
    const modal = findAll(loadAdmin('admin_travel_lab_catalog'), n => n.id === 'travel_metadata_modal')[0];
    const itinerary = [{day:1,title:{ko:'첫날',en:'Day one'},description:{ko:'합성 일정',en:'Synthetic itinerary'}}];
    test = createLayoutTest({version:'1.0.0',layout_name:'metadata_registration',components:modal.children}, {
      componentRegistry:registry,translations,locale:'ko',initialState:{_global:{travelMetadataExisting:false,travelMetadataEdit:{product_id:19,region:'jeju',theme:'nature',duration_days:2,summary_ko:'합성 소개',summary_en:'Synthetic summary',itinerary_json:JSON.stringify(itinerary),published:false}}},
      initialData:{travel_candidates:{data:{data:[{id:19,product_code:'OWN19',title:'검증 상품'}],pagination:{current_page:1,last_page:1}}}}
    });
    await test.render();
    // Match the actual public SDK: get() returns global contents directly, not a wrapper.
    (window as any).G7Core.state.get = () => test!.getState()._global;
    expect(screen.getByTestId('travel_metadata_itinerary_json')).toHaveValue(JSON.stringify(itinerary));
    await test.user.click(screen.getByTestId('travel_metadata_save_post'));
    await waitFor(() => {
      const call = vi.mocked(globalThis.fetch).mock.calls.find(([url,init]) => String(url).endsWith('/admin/catalog') && init?.method==='POST');
      expect(call).toBeDefined();
      expect(JSON.parse(String(call![1]!.body))).toEqual({product_id:19,region:'jeju',theme:'nature',duration_days:2,summary:{ko:'합성 소개',en:'Synthetic summary'},itinerary:JSON.stringify(itinerary),published:false});
    });
  });

  it('existing metadata PATCH omits product_id and exposes server validation errors', async () => {
    const modal = findAll(loadAdmin('admin_travel_lab_catalog'), n => n.id === 'travel_metadata_modal')[0];
    test = createLayoutTest({version:'1.0.0',layout_name:'metadata_update',components:modal.children}, {componentRegistry:registry,translations,locale:'ko',initialState:{_global:{travelMetadataExisting:true,travelMetadataEdit:{product_id:19,region:'jeju',theme:'nature',duration_days:2,summary_ko:'수정',summary_en:'Updated',itinerary_json:'broken-json',published:false}}}});
    await test.render();
    // Match the actual public SDK: get() returns global contents directly, not a wrapper.
    (window as any).G7Core.state.get = () => test!.getState()._global;
    const fetchOriginal=globalThis.fetch;
    globalThis.fetch=vi.fn(async (url:any,init:any) => {
      if(String(url).endsWith('/admin/catalog/19') && init?.method==='PATCH')return new Response(JSON.stringify({success:false,message:'일정 JSON을 확인하세요.',errors:{itinerary:['Invalid JSON']}}),{status:422,headers:{'Content-Type':'application/json'}});
      return fetchOriginal(url,init);
    }) as any;
    await test.user.click(screen.getByTestId('travel_metadata_save_patch'));
    await waitFor(() => expect(test!.getState()._global.travelMetadataError).toBe('일정 JSON을 확인하세요.'));
    // The core harness exposes global state but has no live global React subscription.
    await test.rerender();
    expect(screen.getByTestId('travel_metadata_error')).toHaveTextContent('일정 JSON을 확인하세요.');
    const call=vi.mocked(globalThis.fetch).mock.calls.find(([url,init]) => String(url).endsWith('/admin/catalog/19') && init?.method==='PATCH');
    expect(JSON.parse(String(call![1]!.body))).not.toHaveProperty('product_id');
    expect(screen.getByTestId('travel_metadata_save_patch')).not.toBeDisabled();
  });

  it('admin inquiry selects only a server allowed uppercase transition and preserves its note', async () => {
    test = createLayoutTest(flatten(loadAdmin('admin_travel_lab_inquiry_detail')), {
      componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '52' },
    });
    test.mockApi('inquiry', { response: { success: true, data: {
      id: 52, reference: 'TL-00000052', status: 'TEST_INQUIRY', first_product_name: '합성 제주 산책', total_quantity: 2,
      contact: { name: '시험 신청자', phone: '010-0000-0000' }, admin_note: '기존 시험 메모',
      allowed_transitions: ['UNDER_REVIEW', 'DECLINED', 'CANCELLED'], abilities: { can_update: true },
    } } });
    await test.render();
    // Match the actual public SDK: get() returns global contents directly, not a wrapper.
    (window as any).G7Core.state.get = () => test!.getState()._global;
    expect(screen.getByText('시험 신청자')).toBeInTheDocument();
    expect(screen.getByText('테스트 접수')).toBeInTheDocument();
    const save = document.getElementById('inquiry_save_button')!;
    expect(save).toBeDisabled();
    expect(screen.queryByText('테스트 수락')).toBeNull();
    await test.user.click(screen.getByText('검토 중'));
    // Core helper stores global updates without a React subscription; refresh its renderer.
    await test.rerender();
    await waitFor(() => expect(document.getElementById('inquiry_save_button')).not.toBeDisabled());
    await test.user.click(document.getElementById('inquiry_save_button')!);
    await waitFor(() => {
      const call = vi.mocked(globalThis.fetch).mock.calls.find(([url, init]) => String(url).endsWith('/admin/inquiries/52') && init?.method === 'PATCH');
      expect(call).toBeDefined();
      expect(JSON.parse(String(call![1]!.body))).toEqual({ status: 'UNDER_REVIEW', admin_note: '기존 시험 메모' });
    });
  });
});
