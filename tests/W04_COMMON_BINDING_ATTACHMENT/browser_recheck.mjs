// W04 common-binding independent NONAUTHOR recheck at fixed fc54b6ae (repaired core evaluator + board 1.1.3).
// Native UI + own synthetic API fixture only. No source/config/SQL/cache changes; no handler/state patching.
// Derived from tests/W04_ADMIN_DIAGNOSTIC/diagnose.mjs (author diagnosis, preserved unchanged).
// Usage: node browser_recheck.mjs <parent_root> <private_state_dir> [--phase=name]
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { createRequire } from 'node:module';

const [parent, stateDir] = process.argv.slice(2);
const require = createRequire(path.join(parent, 'package.json'));
const { chromium, expect } = require('@playwright/test');

const REVIEW_SHA = 'fc54b6ae091cd6cef2d0fabc48d2ec4fe4fdba8c';
const ENGINE_SHA = 'b8cf27fab58e108b1509c379a5f4d0860a21a90bc591bb34b48a94d6e56cb76d';
const ADMIN_COMPONENTS_SHA = 'd25eb47e1308a83a456b17fe86953fec8fefc46a38769c0c225be72b0028e6c9';
const accessPath = path.join(parent, 'storage/framework/testing/travel-live-review-fb40eddf4a39/access.json');
if (fs.lstatSync(accessPath).isSymbolicLink() || (fs.statSync(accessPath).mode & 0o077) || (fs.statSync(path.dirname(accessPath)).mode & 0o077)) throw Error('Protected scoped access required');
if (fs.statSync(stateDir).mode & 0o077) throw Error('Private state dir must be 0700');
const access = JSON.parse(fs.readFileSync(accessPath));
if (access.base_url !== 'http://127.0.0.1:18871' || !(Date.parse(access.expires_at) > Date.now() + 10 * 60 * 1000)) throw Error('Scoped runtime binding mismatch or near expiry');
const base = access.base_url, travel = '/api/modules/raonslab-travel_lab', shop = '/api/modules/sirsoft-ecommerce';
const phase = process.argv.find(x => x.startsWith('--phase='))?.slice(8) ?? 'run1';
if (!/^[a-z0-9-]+$/.test(phase)) throw Error('Invalid phase');
const outDir = path.join(path.dirname(new URL(import.meta.url).pathname), 'evidence');
const output = path.join(outDir, 'browser-' + phase + '.json');
if (fs.existsSync(output)) throw Error('Preserve existing evidence phase');
const ledgerPath = path.join(stateDir, 'browser-ledger-' + phase + '.json');
const PRIVATE = ['member', 'other_member', 'admin'].flatMap(r => [access[r].email, access[r].password, access[r].bearer_token]).filter(Boolean);
const issued = [];
const scrub = v => { let s = JSON.stringify(v); for (const p of [...PRIVATE, ...issued].sort((a, b) => b.length - a.length)) s = s.split(p).join('[private]'); return JSON.parse(s.replace(/[\w.+-]+@[\w-]+\.[\w.-]+/g, '[masked-email]').replace(/\d+\|[A-Za-z0-9]{20,}/g, '[masked-token]')); };
const results = { review_sha: REVIEW_SHA, handoff_source_sha: access.source_sha, phase, started: new Date().toISOString(), assets: [], cases: [], menu: [], smoke: [], validation: [], ownership: [], tokens: [], fixture: null, cleanup: [], harness_errors: [] };
const hash = value => crypto.createHash('sha256').update(value).digest('hex');
const write = () => { fs.writeFileSync(output, JSON.stringify(scrub(results), null, 2) + '\n'); fs.writeFileSync(ledgerPath, JSON.stringify({ fixture: results.fixture, tokenHashes: issued.map(hash) })); fs.chmodSync(ledgerPath, 0o600); };
const browser = await chromium.launch({ executablePath: '/home/ubuntu/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome' });
results.chromium = browser.version();
const req = await browser.newPage();
let fixture;

async function api(endpoint, method = 'GET', data, role = 'admin') {
  const headers = { Accept: 'application/json', 'Accept-Language': 'ko' };
  if (role) headers.Authorization = 'Bearer ' + access[role].bearer_token;
  const r = await req.request.fetch(base + endpoint, { method, headers, ...(data === undefined ? {} : { data }) });
  let body; try { body = await r.json(); } catch { body = null; }
  return { status: r.status(), body };
}
async function required(endpoint, method, data, expected) {
  const r = await api(endpoint, method, data);
  if (r.status !== expected) throw Error('Native fixture API status ' + r.status + ' for ' + endpoint + '; fields ' + Object.keys(r.body?.errors ?? {}).join(','));
  return r.body.data;
}
async function login(p, role = 'admin') {
  await p.goto(base + '/login');
  await p.locator('#travel-login-email').fill(access[role].email);
  await p.locator('#travel-login-password').fill(access[role].password);
  const wait = p.waitForResponse(r => new URL(r.url()).pathname === '/api/auth/login' && r.request().method() === 'POST');
  await p.locator('form button[type=submit]').click();
  const r = await wait; expect(r.status()).toBe(200);
  const data = (await r.json()).data;
  const token = data.token ?? data.access_token;
  if (typeof token !== 'string' || !token) throw Error('Missing native issued token');
  issued.push(token); write(); await expect(p).not.toHaveURL(/\/login/);
}
// Observation only: capture-phase listeners that read native global state and rendered label; nothing is patched.
async function instrument(p) {
  await p.evaluate(() => {
    window.__w04cb = { events: [] };
    const snap = () => { const g = window.G7Core?.state?.get?.()?.travelDepartureEdit; return g ? { is_active: g.is_active, capacity: g.capacity, departure_date: g.departure_date, return_date: g.return_date, product_option_id: g.product_option_id } : null; };
    const label = () => document.querySelector('[data-testid=departure_status_select]')?.textContent?.trim() ?? null;
    const h = e => { const t = e.target instanceof Element ? e.target : null; window.__w04cb.events.push({ t: performance.now(), type: e.type, trusted: e.isTrusted, testId: t?.closest('[data-testid]')?.getAttribute('data-testid') ?? null, role: t?.closest('[role]')?.getAttribute('role') ?? null, state: snap(), renderedStatusLabel: label() }); };
    for (const type of ['pointerdown', 'touchstart', 'mousedown', 'mouseup', 'click', 'change']) document.addEventListener(type, h, true);
    window.__w04cb.stop = () => { for (const type of ['pointerdown', 'touchstart', 'mousedown', 'mouseup', 'click', 'change']) document.removeEventListener(type, h, true); };
  });
}
const trace = p => p.evaluate(() => window.__w04cb?.events ?? []);
const BASELINE = () => ({ product_option_id: fixture.optionIds[0], departure_date: '2026-12-29', return_date: '2026-12-30', capacity: 4 });

async function prepareFixture() {
  const source = await required(shop + '/admin/products/70', 'GET', undefined, 200);
  if ((source.options ?? []).length < 2) throw Error('Reference product lacks two option value sets');
  const marker = 'W04CB' + Date.now();
  const opt = (o, i) => ({ option_code: marker + '-' + i, option_name: { ko: '합성 재검증 ' + i, en: 'Synthetic recheck ' + i }, option_values: o.option_values, list_price: 20000, selling_price: 12000 + i * 1000, stock_quantity: 10, is_default: i === 0, is_active: true });
  const product = await required(shop + '/admin/products', 'POST', { name: { ko: marker + ' 독립 재검증', en: marker + ' independent recheck' }, product_code: marker, category_ids: [26], list_price: 20000, selling_price: 12000, stock_quantity: 20, sales_status: source.sales_status, display_status: 'hidden', tax_status: source.tax_status, shipping_policy_id: 6, has_options: true, option_groups: source.option_groups, use_stock_management: true, options: [opt(source.options[0], 0), opt(source.options[1], 1)] }, 201);
  fixture = { productId: product.id, title: marker + ' 독립 재검증', departureId: null, optionIds: [] };
  results.fixture = { productId: product.id, marker, scope: 'ONE newly owned hidden synthetic product with 2 options; API preparation only (not UI CREATE); category26/policy6 referenced read-only' }; write();
  await required(travel + '/admin/catalog', 'POST', { product_id: product.id, region: 'jeju', theme: 'nature', duration_days: 2, summary: { ko: '합성 재검증', en: 'Synthetic recheck' }, itinerary: [], published: false }, 201);
  const read = await required(shop + '/admin/products/' + product.id, 'GET', undefined, 200);
  fixture.optionIds = read.options.map(o => o.id);
  results.fixture.optionIds = fixture.optionIds;
  const departure = await required(travel + '/admin/catalog/' + product.id + '/departures', 'POST', { ...BASELINE(), is_active: true }, 200);
  fixture.departureId = departure.id; results.fixture.departureId = departure.id; write();
}

async function openEditor(p, pageNumber, rowDate) {
  await p.goto(base + '/admin/travel-lab?per_page=48&page=' + pageNumber);
  const card = p.locator('.admin-card').filter({ has: p.getByText(fixture.title, { exact: true }) });
  await expect(card).toBeVisible({ timeout: 15000 });
  const btn = card.locator('#departure_row').filter({ hasText: rowDate }).getByRole('button', { name: /출발일 설정/ });
  if (p.viewportSize().width === 390) await btn.tap(); else await btn.click();
  await expect(p.getByTestId('departure_status_select')).toBeVisible();
  await p.waitForLoadState('networkidle');
}
const act = async (p, loc) => (p.viewportSize().width === 390 ? loc.tap() : loc.click());
async function chooseStatus(p, label) {
  await act(p, p.getByTestId('departure_status_select'));
  await act(p, p.getByRole('option', { name: label, exact: true }));
  // Read immediately after the option click resolves; deliberately no waiting for the rendered label.
  return p.evaluate(() => ({ at: performance.now(), nativeGlobalActive: G7Core.state.get().travelDepartureEdit?.is_active, renderedStatusLabel: document.querySelector('[data-testid=departure_status_select]')?.textContent?.trim() }));
}
async function saveAndRead(p) {
  const w = p.waitForResponse(r => ['PUT', 'POST'].includes(r.request().method()) && new URL(r.url()).pathname.startsWith(travel + '/admin/catalog/' + fixture.productId + '/departures'));
  await act(p, p.getByTestId('departure_save'));
  const response = await w;
  const body = response.request().postDataJSON();
  let returned = null; try { returned = (await response.json()).data; } catch {}
  const list = await required(travel + '/admin/catalog/' + fixture.productId + '/departures', 'GET', undefined, 200);
  const read = list.find(x => x.id === fixture.departureId);
  return { method: response.request().method(), http: response.status(), request: body, returned: returned && { is_active: returned.is_active, capacity: returned.capacity, departure_date: returned.departure_date, return_date: returned.return_date, product_option_id: returned.product_option_id }, readback: read && { is_active: read.is_active, capacity: read.capacity, departure_date: read.departure_date, return_date: read.return_date, product_option_id: read.product_option_id, reserved: read.reserved } };
}
async function reset(active, extra = {}) {
  await required(travel + '/admin/catalog/' + fixture.productId + '/departures/' + fixture.departureId, 'PUT', { ...BASELINE(), is_active: active, ...extra }, 200);
}
function saveEvent(tr) { const ev = tr.filter(e => e.testId === 'departure_save'); return { first: ev[0] ?? null, types: ev.map(e => e.type), allTrusted: ev.every(e => e.trusted) }; }

async function departureChecks(width) {
  const c = await browser.newContext({ viewport: { width, height: 1000 }, hasTouch: width === 390, isMobile: width === 390, locale: 'ko-KR' }), p = await c.newPage();
  await login(p);
  let pageNumber;
  for (let n = 1; n <= 10 && !pageNumber; n++) {
    const catalog = await required(travel + '/admin/catalog?per_page=48&page=' + n, 'GET', undefined, 200);
    if (catalog.data.some(x => Number(x.id) === Number(fixture.productId))) pageNumber = n;
  }
  if (!pageNumber) throw Error('Own fixture not in bounded catalog pagination');
  const only = process.argv.find(x => x.startsWith('--cases='))?.slice(8)?.split(',');
  const run = async (name, fn) => {
    if (only && !only.includes(name)) return;
    try { const r = await fn(); results.cases.push({ width, name, ...r }); }
    catch (e) { results.harness_errors.push({ width, name, error: String(e.message).slice(0, 400) }); }
    await p.evaluate(() => window.__w04cb?.stop?.()).catch(() => {}); write();
  };
  for (const [name, from, toLabel, expectActive] of [['rapid-true-to-false', true, '접수 중지', false], ['rapid-false-to-true', false, '접수 가능', true], ['rapid-true-to-false-repeat', true, '접수 중지', false]]) {
    await run(name, async () => {
      await reset(from); await openEditor(p, pageNumber, '2026-12-29'); await instrument(p);
      const selected = await chooseStatus(p, toLabel);
      const saved = await saveAndRead(p);
      const tr = await trace(p);
      const se = saveEvent(tr);
      return { selected, saveFirstEvent: se.first, saveEventTypes: se.types, allSaveEventsTrusted: se.allTrusted,
        staleRenderAtSave: se.first ? se.first.renderedStatusLabel !== toLabel && se.first.state?.is_active === expectActive : null,
        ...saved, pass: saved.http === 200 && saved.request?.is_active === expectActive && saved.readback?.is_active === expectActive };
    });
  }
  await run('rapid-edit-option-number-date-capacity-status', async () => {
    await reset(true); await openEditor(p, pageNumber, '2026-12-29'); await instrument(p);
    const target = { product_option_id: fixture.optionIds[0], departure_date: width === 390 ? '2027-01-05' : '2027-01-12', return_date: width === 390 ? '2027-01-07' : '2027-01-14', capacity: width === 390 ? 6 : 7, is_active: false };
    // Existing departure: option reference is rendered disabled (native option_immutable contract); observe only.
    const optionInputDisabled = await p.getByTestId('departure_product_option_id_input').isDisabled();
    await p.getByTestId('departure_departure_date_input').fill(target.departure_date);
    await p.getByTestId('departure_return_date_input').fill(target.return_date);
    const cap = p.getByTestId('departure_capacity_input');
    await act(p, cap); await p.keyboard.press('ControlOrMeta+a'); await p.keyboard.type(String(target.capacity));
    // capacity commits on change (blur) caused by opening the status select; status chosen last, Save immediately.
    const selected = await chooseStatus(p, '접수 중지');
    const saved = await saveAndRead(p);
    const se = saveEvent(await trace(p));
    const keys = Object.keys(target);
    return { target, optionInputDisabled, selected, saveFirstEvent: se.first, allSaveEventsTrusted: se.allTrusted, ...saved,
      requestMatches: Object.fromEntries(keys.map(k => [k, saved.request?.[k] === target[k]])),
      readbackMatches: Object.fromEntries(keys.map(k => [k, String(saved.readback?.[k]).slice(0, 10) === String(target[k])])),
      pass: saved.http === 200 && keys.every(k => saved.request?.[k] === target[k] && String(saved.readback?.[k]).slice(0, 10) === String(target[k])) };
  });
  await run('rapid-new-departure-option-select-status', async () => {
    await reset(true);
    await p.goto(base + '/admin/travel-lab?per_page=48&page=' + pageNumber);
    const card = p.locator('.admin-card').filter({ has: p.getByText(fixture.title, { exact: true }) });
    await expect(card).toBeVisible({ timeout: 15000 });
    await act(p, card.getByTestId('new_departure_button').or(card.locator('#new_departure_button')).first());
    await expect(p.getByTestId('departure_product_option_select')).toBeVisible();
    await p.waitForLoadState('networkidle'); await instrument(p);
    const target = { product_option_id: fixture.optionIds[1], departure_date: width === 390 ? '2027-02-02' : '2027-02-09', return_date: width === 390 ? '2027-02-03' : '2027-02-10', capacity: 3, is_active: false };
    await act(p, p.getByTestId('departure_product_option_select'));
    await act(p, p.getByRole('option', { name: new RegExp(' · ' + target.product_option_id + ' · ') }));
    await p.getByTestId('departure_departure_date_input').fill(target.departure_date);
    await p.getByTestId('departure_return_date_input').fill(target.return_date);
    const cap = p.getByTestId('departure_capacity_input');
    await act(p, cap); await p.keyboard.press('ControlOrMeta+a'); await p.keyboard.type(String(target.capacity));
    const selected = await chooseStatus(p, '접수 중지');
    const w = p.waitForResponse(r => r.request().method() === 'POST' && new URL(r.url()).pathname === travel + '/admin/catalog/' + fixture.productId + '/departures');
    await act(p, p.getByTestId('departure_save'));
    const response = await w; const body = response.request().postDataJSON(); let created = null, message = null; try { const j = await response.json(); created = j.data; message = response.status() >= 400 ? j.message : null; } catch {}
    if (created?.id) { (fixture.extraDepartureIds ??= []).push(created.id); results.fixture.extraDepartureIds = fixture.extraDepartureIds; write(); }
    const list = await required(travel + '/admin/catalog/' + fixture.productId + '/departures', 'GET', undefined, 200);
    const read = list.find(x => x.id === created?.id);
    const se = saveEvent(await trace(p));
    const keys = Object.keys(target);
    return { target, selected, saveFirstEvent: se.first, allSaveEventsTrusted: se.allTrusted, method: 'POST', http: response.status(), message, request: body, createdId: created?.id ?? null,
      readback: read && { is_active: read.is_active, capacity: read.capacity, departure_date: read.departure_date, return_date: read.return_date, product_option_id: read.product_option_id, reserved: read.reserved },
      pass: [200, 201].includes(response.status()) && keys.every(k => body?.[k] === target[k] && String(read?.[k]).slice(0, 10) === String(target[k])) };
  });
  await run('rapid-metadata-duration-region-unpublished', async () => {
    const before = await required(travel + '/admin/catalog/' + fixture.productId, 'GET', undefined, 200);
    await p.goto(base + '/admin/travel-lab?per_page=48&page=' + pageNumber);
    const card = p.locator('.admin-card').filter({ has: p.getByText(fixture.title, { exact: true }) });
    await expect(card).toBeVisible({ timeout: 15000 });
    const g = p.waitForResponse(r => r.request().method() === 'GET' && new URL(r.url()).pathname === travel + '/admin/catalog/' + fixture.productId);
    await act(p, card.getByTestId('travel_edit_metadata')); await g;
    await expect(p.getByTestId('travel_metadata_duration_days')).toBeVisible(); await p.waitForLoadState('networkidle'); await instrument(p);
    const target = { duration_days: before.duration_days === 3 ? 4 : 3, region: before.region === 'gangwon' ? 'busan' : 'gangwon', published: false };
    const dd = p.getByTestId('travel_metadata_duration_days');
    await act(p, dd); await p.keyboard.press('ControlOrMeta+a'); await p.keyboard.type(String(target.duration_days));
    const LABEL = { gangwon: '강원', busan: '부산', '0': '게시 중지' };
    const pick = async (testId, value) => { const el = p.getByTestId(testId); const tag = await el.evaluate(e => e.tagName); if (tag === 'SELECT') { await el.selectOption(value); return 'native-select'; } await act(p, el); await act(p, p.getByRole('option', { name: LABEL[value], exact: true })); return 'composite'; };
    const regionKind = await pick('travel_metadata_region', target.region);
    const pubKind = await pick('travel_metadata_published', '0');
    const latest = await p.evaluate(() => { const m = G7Core.state.get().travelMetadataEdit; return { duration_days: m?.duration_days, region: m?.region, published: m?.published }; });
    const w = p.waitForResponse(r => r.request().method() === 'PATCH' && new URL(r.url()).pathname === travel + '/admin/catalog/' + fixture.productId);
    await act(p, p.getByTestId('travel_metadata_save_patch'));
    const response = await w; const body = response.request().postDataJSON();
    const after = await required(travel + '/admin/catalog/' + fixture.productId, 'GET', undefined, 200);
    return { target, regionKind, pubKind, latestGlobalBeforeSave: latest, method: 'PATCH', http: response.status(), request: { duration_days: body?.duration_days, region: body?.region, published: body?.published },
      readback: { duration_days: after.duration_days, region: after.region, published: after.published },
      pass: response.status() === 200 && body?.duration_days === target.duration_days && body?.region === target.region && body?.published === false && after.duration_days === target.duration_days && after.region === target.region && after.published === false };
  });
  // Server validation preserved: return before departure via native UI -> 422, readback unchanged.
  await run('validation-return-before-departure', async () => {
    await reset(true); await openEditor(p, pageNumber, '2026-12-29'); await instrument(p);
    await p.getByTestId('departure_return_date_input').fill('2026-12-20');
    const saved = await saveAndRead(p);
    const errorFields = null;
    return { ...saved, errorFields, pass: saved.http === 422 && saved.readback?.return_date?.slice(0, 10) === '2026-12-30' && saved.readback?.is_active === true };
  });
  await run('validation-capacity-zero', async () => {
    await reset(true); await openEditor(p, pageNumber, '2026-12-29'); await instrument(p);
    const cap = p.getByTestId('departure_capacity_input');
    await act(p, cap); await p.keyboard.press('ControlOrMeta+a'); await p.keyboard.type('0');
    const saved = await saveAndRead(p);
    return { ...saved, pass: saved.http === 422 && saved.readback?.capacity === 4 };
  });
  // Modal-only screenshot of own synthetic fixture (no actor/contact region).
  try {
    await reset(true); await openEditor(p, pageNumber, '2026-12-29');
    const shot = path.join(outDir, 'departure-modal-' + width + '-' + phase + '.png');
    await p.getByTestId('departure_status_select').locator('xpath=ancestor::*[@role="dialog"][1]').screenshot({ path: shot }).catch(async () => p.locator('#departure_modal_body').screenshot({ path: shot }));
    results.screenshots = [...(results.screenshots ?? []), path.basename(shot)];
  } catch (e) { results.harness_errors.push({ width, name: 'screenshot', error: String(e.message).slice(0, 300) }); }
  await c.close();
}

async function menuCheck(width, inquiryId, label) {
  const c = await browser.newContext({ viewport: { width, height: 1000 }, hasTouch: width === 390, isMobile: width === 390, locale: 'ko-KR' }), p = await c.newPage();
  await login(p);
  let pageNumber;
  for (let n = 1; n <= 10 && !pageNumber; n++) {
    const r = await api(travel + '/admin/inquiries?page=' + n + '&per_page=20'); expect(r.status).toBe(200);
    if ((r.body.data.data ?? []).some(x => x.id === inquiryId)) pageNumber = n;
  }
  if (!pageNumber) { results.menu.push({ width, label, inquiryId, error: 'not in bounded admin pagination' }); await c.close(); return; }
  const modes = width === 390 ? ['tap', 'keyboard'] : ['locator-pointer', 'coordinate-pointer', 'keyboard'];
  for (const mode of modes) {
    const rec = { width, label, inquiryId, pageNumber, mode };
    try {
      await p.goto(base + '/admin/travel-lab/inquiries?page=' + pageNumber);
      await p.waitForLoadState('networkidle');
      const code = 'TL-' + String(inquiryId).padStart(8, '0');
      // Innermost visible container holding both the reference code and a button (table row at PC, card at mobile).
      const row = p.locator('tr, li, div').filter({ visible: true }).filter({ has: p.getByText(code, { exact: true }) }).filter({ has: p.getByRole('button') }).last();
      await expect(row).toBeVisible({ timeout: 10000 });
      rec.containerTag = await row.evaluate(e => e.tagName);
      const trigger = row.getByRole('button').last();
      await trigger.scrollIntoViewIfNeeded();
      const box = await trigger.boundingBox(); rec.trigger = box;
      if (mode === 'locator-pointer') await trigger.click();
      else if (mode === 'tap') await trigger.tap();
      else if (mode === 'coordinate-pointer') { await p.mouse.move(box.x + box.width / 2, box.y + box.height / 2); await p.mouse.down(); await p.mouse.up(); }
      else { await trigger.focus(); await p.keyboard.press('Enter'); }
      const menu = p.locator('[class*="z-[9999]"]').filter({ hasText: /^상세 보기$/ });
      rec.menuImmediately = await menu.count();
      const item = menu.getByText('상세 보기', { exact: true });
      if (mode === 'tap') await item.tap({ timeout: 2500 });
      else if (mode === 'coordinate-pointer') { const mb = await item.boundingBox({ timeout: 2500 }); await p.mouse.move(mb.x + mb.width / 2, mb.y + mb.height / 2); await p.mouse.down(); await p.mouse.up(); }
      else if (mode === 'keyboard') await (width === 390 ? item.tap({ timeout: 2500 }) : item.click({ timeout: 2500 })); // menu opened by Enter on trigger; item chosen by pointer as in the original harness
      else await item.click({ timeout: 2500 });
      await expect(p).toHaveURL(new RegExp('/inquiries/' + inquiryId + '(?:\\?|$)'), { timeout: 4000 });
      rec.detail = true;
    } catch (e) { rec.detail = false; rec.error = String(e.message).split('\n')[0].slice(0, 300); }
    results.menu.push(rec); write();
  }
  await c.close();
}

async function smoke() {
  const c = await browser.newContext({ viewport: { width: 390, height: 900 }, hasTouch: true, isMobile: true, locale: 'ko-KR' }), p = await c.newPage();
  const consoleErrors = []; p.on('pageerror', e => consoleErrors.push(String(e.message).slice(0, 200)));
  await login(p, 'member');
  const memberToken = issued[issued.length - 1];
  for (const route of ['/travel', '/travel/cart', '/travel/requests']) {
    const resp = await p.goto(base + route); await p.waitForLoadState('networkidle');
    const text = (await p.locator('body').innerText()).length;
    results.smoke.push({ check: 'member-ui-page', route, http: resp?.status(), renderedTextLength: text, onLogin: /\/login/.test(p.url()) });
  }
  const h = t => ({ Accept: 'application/json', ...(t ? { Authorization: 'Bearer ' + t } : {}) });
  for (const [label, url, token] of [['member-cart', travel + '/cart', memberToken], ['guest-cart', travel + '/cart', null], ['member-admin-catalog', travel + '/admin/catalog', memberToken], ['member-auth-user', '/api/auth/user', memberToken]]) {
    const r = await req.request.get(base + url, { headers: h(token) });
    let shape = null; if (label === 'member-cart' && r.status() === 200) { const b = await r.json(); shape = { itemCount: Array.isArray(b.data?.items) ? b.data.items.length : (Array.isArray(b.data) ? b.data.length : null) }; }
    results.smoke.push({ check: 'api', label, http: r.status(), shape });
  }
  results.smoke.push({ check: 'member-ui-pageerrors', count: consoleErrors.length, sample: consoleErrors.slice(0, 3) });
  await c.close();
}

async function ownership() {
  const u = travel + '/admin/catalog/' + fixture.productId + '/departures/' + fixture.departureId;
  const body = { ...BASELINE(), is_active: true };
  for (const role of ['member', 'other_member', null]) { const r = await api(u, 'PUT', body, role); results.ownership.push({ actor: role ?? 'guest', method: 'PUT', http: r.status }); }
  const after = (await required(travel + '/admin/catalog/' + fixture.productId + '/departures', 'GET', undefined, 200)).find(x => x.id === fixture.departureId);
  results.ownership.push({ readbackAfterForeignAttempts: { is_active: after.is_active, capacity: after.capacity } });
}

try {
  for (const [url, expected] of [['/build/core/template-engine.min.js', ENGINE_SHA], ['/api/templates/assets/sirsoft-admin_basic/js/components.iife.js', ADMIN_COMPONENTS_SHA]]) {
    const r = await req.request.get(base + url); const observed = hash(await r.body());
    results.assets.push({ path: url, expected, observed, http: r.status(), match: r.status() === 200 && observed === expected });
    if (r.status() !== 200 || observed !== expected) throw Error('Served asset mismatch: BLOCKED');
  }
  write();
  const reuseId = process.argv.find(x => x.startsWith('--reuse-product='))?.slice(16);
  if (reuseId) {
    if (!/^\d+$/.test(reuseId)) throw Error('Invalid reuse id');
    const product = await required(shop + '/admin/products/' + reuseId, 'GET', undefined, 200);
    if (!product.product_code.startsWith('W04CB')) throw Error('Refuse unrelated fixture');
    const meta = await required(travel + '/admin/catalog/' + reuseId, 'GET', undefined, 200);
    const dep = meta.departures.find(x => x.departure_date.slice(0, 10) === '2026-12-29');
    if (!dep || meta.departures.some(x => x.reserved !== 0)) throw Error('Own fixture missing or reserved');
    fixture = { productId: Number(reuseId), title: product.name.ko, departureId: dep.id, optionIds: product.options.map(o => o.id), extraDepartureIds: meta.departures.filter(x => x.id !== dep.id).map(x => x.id) };
    results.fixture = { productId: fixture.productId, departureId: dep.id, optionIds: fixture.optionIds, reuseOwnEarlierRecheckFixtureOnly: true }; write();
  } else await prepareFixture();
  const widths = (process.argv.find(x => x.startsWith('--widths='))?.slice(9) ?? '1440,390').split(',').map(Number);
  results.widths = widths;
  for (const wdt of widths) await departureChecks(wdt);
  await reset(false);
  await ownership(); write();
  if (process.argv.includes('--departure-only')) throw Object.assign(Error('departure-only phase: menu/smoke intentionally NOT_RUN'), { name: 'ScopedStop' });
  // Menu: newest own member inquiry (read-only) and original report inquiry150.
  const own = await api(travel + '/inquiries?per_page=20', 'GET', undefined, 'member');
  const ownIds = (own.body?.data?.data ?? []).map(x => x.id);
  const q150 = await api(travel + '/inquiries/150', 'GET', undefined, 'member');
  results.menuTargets = { ownListHttp: own.status, ownCount: ownIds.length, inquiry150OwnerReadHttp: q150.status };
  if (ownIds.length) { await menuCheck(1440, ownIds[0], 'own-latest'); await menuCheck(390, ownIds[0], 'own-latest'); }
  if (q150.status === 200) { await menuCheck(1440, 150, 'original-report-150'); await menuCheck(390, 150, 'original-report-150'); }
  await smoke();
} catch (e) {
  results.failure = { name: e.name, message: String(e.message).slice(0, 450) };
} finally {
  if (fixture?.departureId) {
    const r = await api(travel + '/admin/catalog/' + fixture.productId + '/departures/' + fixture.departureId, 'PUT', { ...BASELINE(), is_active: false });
    const list = await api(travel + '/admin/catalog/' + fixture.productId + '/departures');
    for (const d of (list.body?.data ?? []).filter(x => x.id !== fixture.departureId && x.is_active)) {
      const x = await api(travel + '/admin/catalog/' + fixture.productId + '/departures/' + d.id, 'PUT', { product_option_id: d.product_option_id, departure_date: d.departure_date.slice(0, 10), return_date: d.return_date.slice(0, 10), capacity: d.capacity, is_active: false });
      results.cleanup.push({ extraDepartureId: d.id, inactiveHttp: x.status });
    }
    const read = await api(travel + '/admin/catalog/' + fixture.productId);
    results.cleanup.push({ departureInactiveHttp: r.status, allOwnDeparturesInactiveReserved0: !!read.body?.data?.departures?.every(x => !x.is_active && x.reserved === 0), departures: read.body?.data?.departures?.length });
  }
  if (fixture) {
    const unpublish = await api(travel + '/admin/catalog/' + fixture.productId, 'PATCH', { published: false });
    const hidden = await api(shop + '/admin/products/bulk-update', 'PATCH', { ids: [fixture.productId], bulk_changes: { display_status: 'hidden' } });
    const read = await api(shop + '/admin/products/' + fixture.productId);
    const meta = await api(travel + '/admin/catalog/' + fixture.productId);
    results.cleanup.push({ productId: fixture.productId, unpublishedHttp: unpublish.status, publishedRead: meta.body?.data?.published ?? null, hiddenHttp: hidden.status, hiddenRead: read.body?.data?.display_status === 'hidden', retainedSynthetic: true });
  }
  for (const token of issued) {
    const h = { Accept: 'application/json', Authorization: 'Bearer ' + token };
    const logout = await req.request.post(base + '/api/auth/logout', { headers: h, data: {} });
    const q = await req.request.get(base + '/api/auth/user', { headers: h });
    results.tokens.push({ hash: hash(token), logout: logout.status(), authAfter: q.status() });
  }
  for (const role of ['member', 'other_member', 'admin']) { const r = await api('/api/auth/user', 'GET', undefined, role); results.tokens.push({ supplied: role, authUserHttp: r.status }); }
  results.finished = new Date().toISOString(); write();
  await browser.close();
}
console.log(JSON.stringify(scrub({ cases: results.cases.map(r => ({ w: r.width, n: r.name, http: r.http, req: r.request?.is_active, read: r.readback?.is_active, stale: r.staleRenderAtSave, pass: r.pass })), menu: results.menu.map(m => [m.width, m.label, m.mode, m.detail, m.error]), smoke: results.smoke, ownership: results.ownership, harness: results.harness_errors, failure: results.failure, tokens: results.tokens.map(t => [t.logout ?? t.supplied, t.authAfter ?? t.authUserHttp]), cleanup: results.cleanup })));
