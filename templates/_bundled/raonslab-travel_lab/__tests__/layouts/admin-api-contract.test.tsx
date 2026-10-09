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
  it('actual product title, reserved count and pagination render; publication PATCH sends no prices', async () => {
    test = createLayoutTest(flatten(loadAdmin('admin_travel_lab_catalog')), { componentRegistry: registry, translations, locale: 'ko' });
    test.mockApi('catalog', { response: { success: true, data: {
      data: [{ id: 7, product_code: 'SYNTH-007', title: '합성 제주 산책', published: true, departures: [{ id: 71, product_option_id: 901, departure_date: '2026-11-01', return_date: '2026-11-03', capacity: 20, reserved: 3, is_active: true }] }],
      pagination: { current_page: 1, last_page: 2, total: 21 }, abilities: { can_update: true },
    } } });
    await test.render();
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
    expect(screen.getByTestId('departure_product_option_id_input')).toBeDisabled();
    await test.user.click(screen.getByTestId('departure_save'));
    await waitFor(() => {
      const call = vi.mocked(globalThis.fetch).mock.calls.find(([url, init]) => String(url).endsWith('/admin/catalog/7/departures/71') && init?.method === 'PUT');
      expect(call).toBeDefined();
      expect(JSON.parse(String(call![1]!.body))).toEqual({ product_option_id: 901, departure_date: '2026-11-01', return_date: '2026-11-03', capacity: 20, is_active: true });
    });
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
