/** Installed root HTTP-kernel responses captured by runtime implementer.
 * Renderer replay is an author integration check, not live browser/independent PASS.
 * Fixture source_sha identifies HEAD baseline plus uncommitted working tree.
 * No headers/tokens/env or production data are included. No DB writes occur here.
 */
import { afterEach, beforeAll, describe, expect, it } from 'vitest';
import fs from 'node:fs';
import path from 'node:path';
import { createLayoutTest, screen } from '@core/template-engine/__tests__/utils/layoutTestUtils';
import captured from '../fixtures/installed-http-responses.json';
import facets from '../fixtures/installed-facets-response.json';
import enTranslations from '../../lang/en.json';
import { API_BASE, TEMPLATE_ROOT, flatten, loadLayout, registerTemplateComponents, translations } from '../helpers/travelTestKit';

const actual = (method: string, suffix: string): any => {
  const record = captured.responses.find(r => r.method === method && r.route === API_BASE + suffix);
  if (!record || record.status < 200 || record.status >= 300) throw new Error(`Missing successful capture: ${method} ${suffix}`);
  return record.response;
};
const moduleRoot = path.resolve(TEMPLATE_ROOT, '../../../modules/_bundled/raonslab-travel_lab');
const adminTranslations = { 'raonslab-travel_lab': { admin: JSON.parse(fs.readFileSync(path.join(moduleRoot, 'resources/lang/partial/ko/admin.json'), 'utf8')) } };
let registry: any;
let test: ReturnType<typeof createLayoutTest> | undefined;
beforeAll(() => { registry = registerTemplateComponents(); });
afterEach(() => { test?.cleanup(); test = undefined; });

describe('installed API response replay into shipped renderer', () => {
  it.each([['ko', translations, '제주', '자연'], ['en', enTranslations, 'Jeju', 'Nature']] as const)(
    'actual singular facets render region/theme entry and catalog labels in %s', async (locale, dictionary, region, theme) => {
      test = createLayoutTest(flatten(loadLayout('travel/home.json')), {
        componentRegistry: registry, translations: dictionary, locale,
        initialData: { $templateId: 'test-template', $locale: locale }, initialState: { _global: { locale } },
      });
      test.mockApi('facets', { response: facets.response });
      test.mockApi('featured_trips', { response: actual('GET', '/catalog') });
      test.mockApi('departing_trips', { response: actual('GET', '/catalog') });
      await test.render();
      expect(screen.getAllByTestId('region-chip')).toHaveLength(facets.response.data.region.length);
      expect(screen.getAllByTestId('theme-card')).toHaveLength(facets.response.data.theme.length);
      expect(screen.getAllByText(region).length).toBeGreaterThan(1);
      expect(screen.getAllByText(theme).length).toBeGreaterThan(1);
      const regionChip = screen.getAllByTestId('region-chip').find(n => n.textContent?.includes(region))!;
      await test.user.click(regionChip);
      expect(test.getNavigationHistory().some(url => url.includes('region=jeju'))).toBe(true);
    },
  );
  it('real product/detail departures render localized itinerary and numeric server availability', async () => {
    const product = actual('GET', '/catalog/1');
    const departures = actual('GET', '/catalog/1/departures');
    test = createLayoutTest(flatten(loadLayout('travel/product.json')), { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '1' } });
    test.mockApi('product', { response: product });
    test.mockApi('departures', { response: departures });
    await test.render();
    expect(screen.getByTestId('product-title')).toHaveTextContent(product.data.title);
    expect(screen.getByText(product.data.itinerary[0].title.ko)).toBeInTheDocument();
    expect(screen.getAllByTestId('departure-option')).toHaveLength(departures.data.length);
    expect(screen.getAllByTestId('departure-option')[0]).not.toBeDisabled();
  });

  it('real cart consumes native final_amount and translation object, not client calculated prices', async () => {
    const cart = actual('POST', '/cart');
    test = createLayoutTest(flatten(loadLayout('travel/cart.json')), { componentRegistry: registry, translations, locale: 'ko' });
    test.mockApi('cart', { response: cart });
    await test.render();
    expect(screen.getByText(cart.data.items[0].product_name.ko)).toBeInTheDocument();
    expect(screen.getByTestId('cart-total')).toHaveTextContent('189,000');
    expect(screen.getByTestId('cart-qty-increase')).not.toBeDisabled();
    expect(screen.getByTestId('submit-inquiry')).toBeDisabled();
  });

  it.each([['GET', '/inquiries/3', true], ['POST', '/inquiries/3/cancel', false]] as const)(
    'real %s %s preserves decimal totals, request status and owner cancellation=%s', async (method, route, cancellable) => {
      const inquiry = actual(method, route);
      test = createLayoutTest(flatten(loadLayout('travel/request_detail.json')), { componentRegistry: registry, translations, locale: 'ko', routeParams: { id: '3' } });
      test.mockApi('inquiry', { response: inquiry });
      await test.render();
      expect(screen.getByTestId('detail-status')).toHaveAttribute('data-status', inquiry.data.status);
      expect(screen.getByText(inquiry.data.items[0].product_name.ko)).toBeInTheDocument();
      expect(screen.getAllByText(/189,000/).length).toBeGreaterThan(0);
      expect(Boolean(screen.queryByTestId('cancel-request'))).toBe(cancellable);
    },
  );

  it('real administrator Resource renders requester and the allowed uppercase transition controls', async () => {
    const inquiry = actual('GET', '/admin/inquiries/3');
    const layout = JSON.parse(fs.readFileSync(path.join(moduleRoot, 'resources/layouts/admin/admin_travel_lab_inquiry_detail.json'), 'utf8'));
    test = createLayoutTest(flatten(layout), { componentRegistry: registry, translations: adminTranslations, locale: 'ko', routeParams: { id: '3' } });
    test.mockApi('inquiry', { response: inquiry });
    await test.render();
    expect(screen.getByText(inquiry.data.contact.name)).toBeInTheDocument();
    expect(screen.getByText('검토 중')).toBeInTheDocument();
    expect(screen.queryByText('테스트 수락')).toBeNull();
    expect(document.getElementById('inquiry_save_button')).toBeDisabled();
  });

  it.each([['notices', 'notice-item'], ['faqs', 'faq-item']] as const)('real %s list has no body and renders published titles from data.data', async (channel, testId) => {
    const list = actual('GET', `/support/${channel}`);
    test = createLayoutTest(flatten(loadLayout('travel/help.json')), { componentRegistry: registry, translations, locale: 'ko', queryParams: { tab: channel === 'faqs' ? 'faq' : 'notices' } });
    test.mockApi(channel, { response: list });
    await test.render();
    expect(screen.getAllByTestId(testId)).toHaveLength(list.data.data.length);
    expect(screen.getByText(list.data.data[0].title)).toBeInTheDocument();
  });

  it('real private question collection renders its empty state without assuming replies', async () => {
    test = createLayoutTest(flatten(loadLayout('travel/help.json')), { componentRegistry: registry, translations, locale: 'ko', queryParams: { tab: 'questions' }, initialState: { _global: { currentUser: { id: 1 } } } });
    test.mockApi('questions', { response: actual('GET', '/support/questions') });
    await test.render();
    expect(screen.getByText(translations.travel.help.questions_empty)).toBeInTheDocument();
    expect(screen.queryByTestId('question-item')).toBeNull();
  });
});
