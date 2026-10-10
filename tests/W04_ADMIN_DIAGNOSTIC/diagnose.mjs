// Scoped author diagnosis: native UI and own API fixture only. No source/config/SQL changes.
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { chromium, expect } from '@playwright/test';

const accessPath = 'storage/framework/testing/travel-live-review-fb40eddf4a39/access.json';
if (fs.lstatSync(accessPath).isSymbolicLink() || (fs.statSync(accessPath).mode & 0o077) || (fs.statSync(path.dirname(accessPath)).mode & 0o077)) throw Error('Protected scoped access required');
const access = JSON.parse(fs.readFileSync(accessPath));
const sha = 'fa5523175ac494cfbd13bbf89bf06b3ec91835a6';
if (access.source_sha !== sha || access.base_url !== 'http://127.0.0.1:18871' || access.db !== 'req81_travel_lab' || !(Date.parse(access.expires_at) > Date.now())) throw Error('Scoped runtime binding mismatch');
const base = access.base_url, travel = '/api/modules/raonslab-travel_lab';
const reuse = process.argv.includes('--reuse-own-fixture');
const phase = process.argv.find(x => x.startsWith('--phase='))?.slice(8) ?? (reuse ? 'followup' : 'initial');
if (!/^[a-z0-9-]+$/.test(phase)) throw Error('Invalid evidence phase');
const output = 'tests/W04_ADMIN_DIAGNOSTIC/' + (phase === 'initial' ? 'evidence.json' : phase + '-evidence.json');
if (fs.existsSync(output)) throw Error('Preserve existing evidence phase');
const results = { source_sha: sha, started: new Date().toISOString(), results: [], tokens: [], fixture: null, cleanup: [], limitations: ['Author diagnosis, not independent release verification', 'No SQL inventory or full runtime dependency measurement'] };
const hash = value => crypto.createHash('sha256').update(value).digest('hex');
const write = () => fs.writeFileSync(output, JSON.stringify(results, null, 2) + '\n');
const browser = await chromium.launch({ executablePath: '/home/ubuntu/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome' });
const issued = [];
let fixture;
const req = await browser.newPage();
async function api(endpoint, method = 'GET', data, role = 'admin') {
  const r = await req.request.fetch(base + endpoint, { method, headers: { Accept: 'application/json', Authorization: 'Bearer ' + access[role].bearer_token }, ...(data === undefined ? {} : { data }) });
  let body; try { body = await r.json(); } catch { body = null; }
  return { status: r.status(), body };
}
async function required(endpoint, method, data, expected) {
  const r = await api(endpoint, method, data);
  if (r.status !== expected) throw Error('Native fixture API status ' + r.status + ' for ' + endpoint + '; validation fields ' + Object.keys(r.body?.errors ?? {}).join(','));
  return r.body.data;
}
async function login(p) {
  await p.goto(base + '/login');
  await p.locator('#travel-login-email').fill(access.admin.email);
  await p.locator('#travel-login-password').fill(access.admin.password);
  const wait = p.waitForResponse(r => new URL(r.url()).pathname === '/api/auth/login' && r.request().method() === 'POST');
  await p.locator('form button[type=submit]').click();
  const r = await wait; expect(r.status()).toBe(200);
  const data = (await r.json()).data;
  const token = data.token ?? data.access_token;
  if (typeof token !== 'string' || !token) throw Error('Missing native issued token');
  issued.push(token); await expect(p).not.toHaveURL(/\/login/);
}
async function instrumentation(p) {
  await p.evaluate(() => {
    window.__adminDiag = { events: [], menus: [] };
    const state = () => ({ active: window.G7Core?.state?.get?.()?.travelDepartureEdit?.is_active ?? null, departureId: window.G7Core?.state?.get?.()?.travelDepartureEdit?.id ?? null });
    const menu = () => [...document.querySelectorAll('[class*="z-[9999]"]')].filter(e => e.textContent?.trim() === '상세 보기').map(e => { const r = e.getBoundingClientRect(); return { top: r.top, bottom: r.bottom, left: r.left, right: r.right }; });
    const handler = e => {
      const target = e.target instanceof Element ? e.target : null;
      window.__adminDiag.events.push({ t: performance.now(), type: e.type, tag: target?.tagName ?? 'window', testId: target?.closest('[data-testid]')?.getAttribute('data-testid') ?? null, id: target?.closest('[id]')?.id ?? null, trusted: e.isTrusted, key: ['Enter','Escape'].includes(e.key) ? e.key : undefined, scrollY, state: state(), menus: menu() });
    };
    for (const type of ['pointerdown','mousedown','mouseup','click','keydown','scroll']) document.addEventListener(type, handler, true);
    const observer = new MutationObserver(() => { const value = JSON.stringify(menu()); if (value !== window.__adminDiag.lastMenu) { window.__adminDiag.lastMenu = value; window.__adminDiag.menus.push({ t: performance.now(), menus: menu(), scrollY }); } });
    observer.observe(document.body, { childList: true, subtree: true });
    window.__adminDiag.stop = () => { for (const type of ['pointerdown','mousedown','mouseup','click','keydown','scroll']) document.removeEventListener(type, handler, true); observer.disconnect(); };
  });
}
async function diagnostic(p) {
  return p.evaluate(() => ({ events: window.__adminDiag.events, menus: window.__adminDiag.menus, viewport: innerWidth, height: innerHeight, scrollY }));
}
async function menuCheck() {
  const own = await api(travel + '/inquiries?per_page=20', 'GET', undefined, 'member'); expect(own.status).toBe(200);
  const ownIds = new Set((own.body.data.data ?? []).map(x => x.id));
  const requestedId = process.argv.find(x => x.startsWith('--menu-id='))?.slice(10);
  if (requestedId) {
    if (!/^\d+$/.test(requestedId)) throw Error('Invalid own menu identity');
    const q = await api(travel + '/inquiries/' + requestedId, 'GET', undefined, 'member');
    expect(q.status).toBe(200); ownIds.clear(); ownIds.add(Number(requestedId));
  }
  let target, pageNumber;
  for (let n = 1; n <= 10 && !target; n++) {
    const r = await api(travel + '/admin/inquiries?page=' + n + '&per_page=20'); expect(r.status).toBe(200);
    target = (r.body.data.data ?? []).find(x => ownIds.has(x.id)); pageNumber = n;
  }
  if (!target) throw Error('No own returned inquiry in bounded admin pagination');
  const c = await browser.newContext({ viewport: { width: 1440, height: 1000 }, locale: 'ko-KR' }), p = await c.newPage();
  await login(p);
  for (const mode of ['locator-pointer', 'coordinate-pointer', 'keyboard']) {
    await p.goto(base + '/admin/travel-lab/inquiries?page=' + pageNumber);
    const row = p.locator('tr').filter({ has: p.getByText('TL-' + String(target.id).padStart(8, '0'), { exact: true }) });
    await expect(row).toBeVisible(); await p.waitForLoadState('networkidle');
    const trigger = row.getByRole('button').last();
    await trigger.scrollIntoViewIfNeeded();
    await instrumentation(p);
    const before = await trigger.boundingBox();
    if (mode === 'locator-pointer') await trigger.click();
    else if (mode === 'coordinate-pointer') { await p.mouse.move(before.x + before.width / 2, before.y + before.height / 2); await p.mouse.down(); await p.mouse.up(); }
    else { await trigger.focus(); await p.keyboard.press('Enter'); }
    const menu = p.locator('[class*="z-[9999]"]').filter({ hasText: /^상세 보기$/ });
    const immediateCount = await menu.count();
    let detail = false, timeout = false;
    try {
      if (mode === 'coordinate-pointer' && immediateCount) {
        const box = await menu.boundingBox();
        if (box.y + box.height / 2 >= 1000) throw Error('Menu target below viewport');
        await p.mouse.move(box.x + box.width / 2, box.y + box.height / 2); await p.mouse.down(); await p.mouse.up();
      } else await menu.getByText('상세 보기', { exact: true }).click({ timeout: 2500 });
      await expect(p).toHaveURL(new RegExp('/inquiries/' + target.id + '(?:\\?|$)'), { timeout: 2500 }); detail = true;
    } catch { timeout = true; }
    results.results.push({ scenario: 'readonly-own-inquiry-menu', mode, inquiryId: target.id, pageNumber, trigger: before, menuImmediately: immediateCount, detail, timeout, trace: await diagnostic(p).catch(() => null) }); write();
    await p.evaluate(() => window.__adminDiag?.stop?.()).catch(() => {});
  }
  await c.close();
}
async function prepareFixture() {
  const source = await required('/api/modules/sirsoft-ecommerce/admin/products/70', 'GET', undefined, 200);
  const marker = 'W04DIAG' + Date.now();
  const product = await required('/api/modules/sirsoft-ecommerce/admin/products', 'POST', { name: { ko: marker + ' 독립 상태 진단', en: marker + ' state diagnostic' }, product_code: marker, category_ids: [26], list_price: 20000, selling_price: 12000, stock_quantity: 10, sales_status: source.sales_status, display_status: 'hidden', tax_status: source.tax_status, shipping_policy_id: 6, has_options: true, option_groups: source.option_groups, use_stock_management: true, options: [{ option_code: marker, option_name: { ko: '합성 진단', en: 'Synthetic diagnostic' }, option_values: source.options[0].option_values, list_price: 20000, selling_price: 12000, stock_quantity: 10, is_default: true, is_active: true }] }, 201);
  fixture = { productId: product.id, title: marker + ' 독립 상태 진단', departureId: null, optionId: null };
  results.fixture = { productId: product.id, scope: 'ONE newly owned hidden synthetic product; API preparation only; no prior fixture activation' }; write();
  await required(travel + '/admin/catalog', 'POST', { product_id: product.id, region: 'jeju', theme: 'nature', duration_days: 2, summary: { ko: '합성 상태 진단', en: 'Synthetic state diagnostic' }, itinerary: [], published: false }, 201);
  const read = await required('/api/modules/sirsoft-ecommerce/admin/products/' + product.id, 'GET', undefined, 200);
  fixture.optionId = read.options[0].id;
  const departure = await required(travel + '/admin/catalog/' + product.id + '/departures', 'POST', { product_option_id: fixture.optionId, departure_date: '2026-12-29', return_date: '2026-12-30', capacity: 4, is_active: true }, 200);
  fixture.departureId = departure.id; results.fixture.departureId = departure.id; write();
}
async function toggleCheck(width) {
  const c = await browser.newContext({ viewport: { width, height: 1000 }, hasTouch: width === 390, isMobile: width === 390, locale: 'ko-KR' }), p = await c.newPage();
  await login(p);
  let pageNumber;
  for (let n = 1; n <= 10 && !pageNumber; n++) {
    const catalog = await required(travel + '/admin/catalog?per_page=48&page=' + n, 'GET', undefined, 200);
    if (catalog.data.some(x => Number(x.id) === Number(fixture.productId))) pageNumber = n;
  }
  if (!pageNumber) throw Error('Own fixture not in bounded catalog pagination');
  const modes = process.argv.includes('--render-control-only') ? ['settled-render-control'] : ['immediate-pointer', 'settled-state-control'];
  for (const mode of modes) {
    await required(travel + '/admin/catalog/' + fixture.productId + '/departures/' + fixture.departureId, 'PUT', { product_option_id: fixture.optionId, departure_date: '2026-12-29', return_date: '2026-12-30', capacity: 4, is_active: true }, 200);
    await p.goto(base + '/admin/travel-lab?per_page=48&page=' + pageNumber);
    const card = p.locator('.admin-card').filter({ has: p.getByText(fixture.title, { exact: true }) });
    await expect(card).toBeVisible();
    await card.locator('#departure_row').filter({ hasText: '2026-12-29' }).getByRole('button', { name: /출발일 설정/ }).click();
    await expect(p.getByTestId('departure_status_select')).toBeVisible();
    await instrumentation(p);
    await p.getByTestId('departure_status_select').click();
    const choose = p.getByRole('option', { name: '접수 중지', exact: true });
    if (width === 390) await choose.tap(); else await choose.click();
    const selected = await p.evaluate(() => ({ at: performance.now(), active: G7Core.state.get().travelDepartureEdit?.is_active, label: document.querySelector('[data-testid=departure_status_select]')?.textContent }));
    if (mode === 'settled-state-control') await expect.poll(() => p.evaluate(() => G7Core.state.get().travelDepartureEdit?.is_active)).toBe(false);
    if (mode === 'settled-render-control') await expect(p.getByTestId('departure_status_select')).toContainText('접수 중지');
    const w = p.waitForResponse(r => r.request().method() === 'PUT' && new URL(r.url()).pathname === travel + '/admin/catalog/' + fixture.productId + '/departures/' + fixture.departureId);
    const save = p.getByTestId('departure_save'); if (width === 390) await save.tap(); else await save.click();
    const response = await w; const body = response.request().postDataJSON(); const returned = (await response.json()).data;
    const traced = await diagnostic(p); const read = await required(travel + '/admin/catalog/' + fixture.productId + '/departures', 'GET', undefined, 200);
    results.results.push({ scenario: 'own-departure-status-save', width, mode, selected, http: response.status(), request: body, returnedActive: returned?.is_active, readActive: read.find(x => x.id === fixture.departureId)?.is_active, trace: traced }); write();
    await p.evaluate(() => window.__adminDiag?.stop?.()).catch(() => {});
  }
  await c.close();
}
try {
  for (const [url, expected] of [
    ['/build/core/template-engine.min.js', 'be2d01f3be637afd127f9c62c2fed35cc3de2cabd5da14a7734635ae1798a414'],
    ['/api/templates/assets/sirsoft-admin_basic/js/components.iife.js', 'd25eb47e1308a83a456b17fe86953fec8fefc46a38769c0c225be72b0028e6c9'],
  ]) {
    const r = await req.request.get(base + url); const observed = hash(await r.body());
    (results.assets ??= []).push({ path: url, expectedSHA256: expected, observedSHA256: observed, match: r.status() === 200 && observed === expected });
    if (r.status() !== 200 || observed !== expected) throw Error('Frozen runtime asset mismatch');
  }
  if (process.argv.includes('--menu-only')) await menuCheck();
  else if (!reuse) { await menuCheck(); await prepareFixture(); }
  else {
    const prior = JSON.parse(fs.readFileSync('tests/W04_ADMIN_DIAGNOSTIC/evidence.json'));
    const id = prior.fixture?.productId;
    if (!Number.isSafeInteger(id)) throw Error('Missing own earlier fixture identity');
    const product = await required('/api/modules/sirsoft-ecommerce/admin/products/' + id, 'GET', undefined, 200);
    if (!product.product_code.startsWith('W04DIAG')) throw Error('Refuse unrelated fixture');
    const meta = await required(travel + '/admin/catalog/' + id, 'GET', undefined, 200);
    const dep = meta.departures.find(x => x.id === prior.fixture.departureId);
    if (!dep || dep.reserved !== 0) throw Error('Own fixture missing or reserved');
    fixture = { productId: id, departureId: dep.id, optionId: dep.product_option_id, title: product.name.ko };
    results.fixture = { ...prior.fixture, reuseOwnEarlierDiagnosticOnly: true }; write();
  }
  if (!process.argv.includes('--menu-only')) { await toggleCheck(1440); await toggleCheck(390); }
} catch (e) {
  // No raw API response/DOM/contact/credential is exposed in this diagnostic error.
  results.failure = { name: e.name, message: String(e.message).replace(/[\w.+-]+@[\w.-]+\.[\w-]+/g, '[masked]').slice(0, 450) };
} finally {
  if (fixture) {
    if (fixture.departureId) {
      const r = await api(travel + '/admin/catalog/' + fixture.productId + '/departures/' + fixture.departureId, 'PUT', { product_option_id: fixture.optionId, departure_date: '2026-12-29', return_date: '2026-12-30', capacity: 4, is_active: false });
      const read = await api(travel + '/admin/catalog/' + fixture.productId);
      results.cleanup.push({ ownDepartureInactive: r.status === 200 && read.body.data?.departures.every(x => !x.is_active && x.reserved === 0), productId: fixture.productId });
    }
    const unpublish = await api(travel + '/admin/catalog/' + fixture.productId, 'PATCH', { published: false });
    const hidden = await api('/api/modules/sirsoft-ecommerce/admin/products/bulk-update', 'PATCH', { ids: [fixture.productId], bulk_changes: { display_status: 'hidden' } });
    const read = await api('/api/modules/sirsoft-ecommerce/admin/products/' + fixture.productId);
    results.cleanup.push({ productId: fixture.productId, unpublishedHttp: unpublish.status, hiddenHttp: hidden.status, hiddenRead: read.body.data?.display_status === 'hidden', retainedSynthetic: true });
  }
  for (const token of issued) {
    const h = { Accept: 'application/json', Authorization: 'Bearer ' + token };
    const logout = await req.request.post(base + '/api/auth/logout', { headers: h, data: {} });
    const q = await req.request.get(base + '/api/auth/user', { headers: h });
    results.tokens.push({ hash: hash(token), logout: logout.status(), authAfter: q.status() });
  }
  results.finished = new Date().toISOString(); write();
  await browser.close();
}
console.log(JSON.stringify({ cases: results.results.map(r => ({ scenario: r.scenario, mode: r.mode, width: r.width, detail: r.detail, requestActive: r.request?.is_active, readActive: r.readActive })), failure: results.failure, issued: results.tokens.length, cleanup: results.cleanup }));
