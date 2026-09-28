#!/usr/bin/env node
/**
 * RAON 정보·정책 상위 메뉴와 안내 문서 — 실제 브라우저 단일 fixture.
 *
 * 실행 중인 G7 런타임(G7_BASE_URL)을 실제 Chromium 으로 연다. 배포 전 후보는 네트워크 가로채기로 끼워 넣는다 —
 * 런타임 파일·DB 는 바꾸지 않는다.
 *  - 모듈 JS/CSS 번들: RH_CANDIDATE_DIST 빌드 결과
 *  - 라우트: 서버 병합 결과(정적 게시 또는 API routes.json)에 resources/routes/user.json 을 모듈 규칙(layout 접두사)대로 덧붙인다
 *  - 레이아웃: _user_base 를 상속한 실제 서버 병합본(page/show)의 본문 슬롯에 후보 문서를 넣고,
 *    모든 사용자 레이아웃에 product-nav overlay(메뉴 prepend_child · 푸터 inject_props)를 적용한다
 *  - 다국어: 모듈 ko/en
 *
 * 점검: ko/en × 360/390/412/1280 에서 직접 URL·새로고침·현재 위치·넘침·44px·키보드(Enter/Space/Arrow/Escape/Tab)·
 *       터치·바깥 클릭·SPA 이동, 기존 Community/Notice/Q&A/Search/Login 경계와 상담 fail-closed(PII 입력 0).
 *
 * 사용:
 *   NODE_PATH=<playwright node_modules> G7_BASE_URL=http://127.0.0.1:18770 RH_CANDIDATE_DIST=/tmp/rh-candidate \
 *     node tests/browser/info-policy-smoke.cjs
 */
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const BASE = (process.env.G7_BASE_URL || 'http://127.0.0.1:18770').replace(/\/$/, '');
const CANDIDATE = process.env.RH_CANDIDATE_DIST || '';
const SHOTS = process.env.RH_SCREENSHOT_DIR || '';
const MODULE_ROOT = path.resolve(__dirname, '../..');
const REPO_ROOT = path.resolve(MODULE_ROOT, '../../..');
const MOBILE_UA = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36';
const DESKTOP_UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';
const VIEWPORTS = [
  { name: '360', width: 360, height: 780, mobile: true },
  { name: '390', width: 390, height: 844, mobile: true },
  { name: '412', width: 412, height: 915, mobile: true },
  { name: '1280', width: 1280, height: 900, mobile: false },
];
const LOCALES = ['ko', 'en'];

const readJson = (p) => JSON.parse(fs.readFileSync(p, 'utf8'));
const userRoutes = readJson(path.join(MODULE_ROOT, 'resources/routes/user.json')).routes;
const navExtension = readJson(path.join(MODULE_ROOT, 'resources/extensions/product-nav.json'));
const lang = { ko: readJson(path.join(MODULE_ROOT, 'resources/lang/ko.json')), en: readJson(path.join(MODULE_ROOT, 'resources/lang/en.json')) };
const baseSources = new Set(readJson(path.join(REPO_ROOT, 'templates/_bundled/sirsoft-basic/layouts/_user_base.json')).data_sources.map((s) => s.id));
const PAGES = userRoutes.map((r) => ({ path: r.path.slice(1), layout: r.layout, doc: readJson(path.join(MODULE_ROOT, `resources/layouts/user/${r.layout}.json`)) }));

const results = [];
function assert(scope, check, ok, detail = '') {
  results.push({ scope, check, status: ok ? 'PASS' : 'FAIL', detail: String(detail).slice(0, 300) });
  return ok;
}

/* ─── 후보 주입 ───────────────────────────────────────── */

function mapNode(node, visit) {
  if (Array.isArray(node)) return node.map((child) => mapNode(child, visit));
  if (node && typeof node === 'object') {
    const out = {};
    for (const [key, value] of Object.entries(node)) out[key] = mapNode(value, visit);
    return visit(out);
  }
  return node;
}

/** product-nav overlay 를 서버 병합 레이아웃에 적용한다(모듈 활성 시 서버가 하는 일과 같은 위치). */
function applyNavOverlay(data) {
  const [menu, footer] = navExtension.injections;
  return mapNode(data, (node) => {
    if (node.id === 'main_content_area' && !(node.children ?? []).some((c) => c.id === 'rh_gnav_root')) {
      return { ...node, children: [...menu.components, ...(node.children ?? [])] };
    }
    if (node.id === 'footer') return { ...node, props: { ...node.props, ...footer.props } };
    return node;
  });
}

let moduleAssets = {};
async function bundleOf(kind) {
  const entries = Object.entries(moduleAssets).sort((a, b) => (a[1].priority ?? 0) - (b[1].priority ?? 0));
  const parts = [];
  for (const [id, asset] of entries) {
    if (id === 'raonslab-product') {
      parts.push(fs.readFileSync(path.join(CANDIDATE, kind === 'js' ? 'js/module.iife.js' : 'css/module.css'), 'utf8'));
    } else if (asset[kind]) {
      const res = await fetch(`${BASE}${asset[kind]}`);
      if (res.ok) parts.push(await res.text());
    }
  }
  return parts.join(kind === 'js' ? '\n;\n' : '\n');
}

async function installCandidate(context) {
  const html = await (await fetch(`${BASE}/`, { headers: { 'User-Agent': DESKTOP_UA } })).text();
  const match = html.match(/moduleAssets:\s*(\{.*\}),\s*$/m);
  moduleAssets = match ? JSON.parse(match[1]) : {};
  const jsBundle = await bundleOf('js');
  const cssBundle = await bundleOf('css');
  const shell = await (await fetch(`${BASE}/api/layouts/sirsoft-basic/page/show.json`)).json();

  await context.route(/\/bundles\/modules\.js/, (route) => route.fulfill({ status: 200, contentType: 'application/javascript', body: jsBundle }));
  await context.route(/\/bundles\/modules\.css/, (route) => route.fulfill({ status: 200, contentType: 'text/css', body: cssBundle }));
  await context.route(/\/templates\/sirsoft-basic\/routes(\.json|\/json)/, async (route) => {
    const response = await route.fetch();
    const body = await response.json();
    const extra = userRoutes.map((r) => ({ ...r, layout: `raonslab-product.${r.layout}`, source: { kind: 'module', identifier: 'raonslab-product' } }));
    body.data.routes = [...body.data.routes.filter((r) => !r.layout?.startsWith('raonslab-product.rh_')), ...extra];
    await route.fulfill({ response, json: body });
  });
  await context.route(/\/layouts\/sirsoft-basic\/.+?(\.json|\/json)/, async (route) => {
    const url = new URL(route.request().url());
    const name = decodeURIComponent(url.pathname.replace(/^.*\/layouts\/sirsoft-basic\//, '').replace(/(\.json|\/json)$/, ''));
    const candidate = PAGES.find((p) => `raonslab-product.${p.layout}` === name);
    if (candidate) {
      const data = JSON.parse(JSON.stringify(shell.data));
      data.layout_name = name;
      data.meta = { ...data.meta, ...candidate.doc.meta, is_base: false };
      data.data_sources = (data.data_sources ?? []).filter((s) => baseSources.has(s.id));
      const filled = mapNode(data, (node) => (node.id === 'main_content' ? { ...node, children: candidate.doc.slots.content } : node));
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ ...shell, data: applyNavOverlay(filled) }) });
    }
    const response = await route.fetch();
    if (!response.ok()) return route.fulfill({ response });
    const body = await response.json();
    body.data = applyNavOverlay(body.data);
    return route.fulfill({ response, json: body });
  });
  await context.route(/\/templates\/sirsoft-basic\/lang\/(ko|en)\.json/, async (route) => {
    const locale = route.request().url().match(/lang\/(ko|en)\.json/)[1];
    const response = await route.fetch();
    const body = await response.json();
    body['raonslab-product'] = lang[locale];
    await route.fulfill({ response, json: body });
  });
}

/* ─── 공통 ───────────────────────────────────────── */

function watch(page) {
  const errors = [];
  const bad = [];
  page.on('response', (res) => {
    const url = new URL(res.url());
    // 서버에 아직 등록되지 않은 후보 라우트의 문서 요청(SPA 셸 404)은 fixture 한계로 따로 기록한다.
    if (res.status() >= 400 && !(res.request().resourceType() === 'document' && PAGES.some((p) => url.pathname.endsWith(p.path)))) {
      bad.push(`${res.status()} ${url.pathname}`);
    }
  });
  page.on('console', (msg) => { if (msg.type() === 'error' && !msg.text().startsWith('Failed to load resource')) errors.push(msg.text()); });
  page.on('pageerror', (err) => errors.push(String(err)));
  return { errors, bad };
}

async function waitDoc(page, key) {
  await page.waitForSelector(`.rh-doc[data-rh-page="${key}"] h1`, { timeout: 15000 });
  await page.waitForSelector('.rh-gnav [data-rh-menu-trigger="info"]', { timeout: 15000 });
  await page.waitForTimeout(250);
}

async function layoutAudit(page) {
  return page.evaluate(() => {
    const vw = document.documentElement.clientWidth;
    const offenders = [];
    document.querySelectorAll('.rh-doc *, .rh-gnav *').forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.width === 0 || el.closest('[hidden]')) return;
      if (r.right > vw + 0.5 || r.left < -0.5) offenders.push(`${el.tagName}.${String(el.className).slice(0, 40)}`);
    });
    const small = [];
    document.querySelectorAll('.rh-gnav a, .rh-gnav button, .rh-doc a, .rh-doc button').forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.width === 0 || el.closest('[hidden]')) return;
      if (r.height < 43.5) small.push(`${el.tagName}.${String(el.className).slice(0, 30)}=${Math.round(r.height)}`);
    });
    const text = document.body.innerText;
    return {
      scrollWidth: document.documentElement.scrollWidth,
      vw,
      offenders: offenders.slice(0, 5),
      small: small.slice(0, 5),
      rawKeys: (text.match(/raonslab-product\.[a-z_.]+|\$t:[a-z]/g) ?? []).slice(0, 5),
      h1: document.querySelector('.rh-doc h1')?.textContent?.trim() ?? '',
      title: document.title,
      lang: document.documentElement.lang,
      current: [...document.querySelectorAll('.rh-doc .rh-side-link[aria-current="page"], .rh-gnav a[aria-current="page"]')].map((a) => a.getAttribute('href')),
      activeGroup: [...document.querySelectorAll('.rh-gnav [data-rh-menu-trigger][data-current="true"]')].map((b) => b.dataset.rhMenuTrigger),
      mainPadding: getComputedStyle(document.getElementById('main_content') ?? document.body).paddingLeft,
      headBleed: (() => { const h = document.querySelector('.rh-doc-head')?.getBoundingClientRect(); return h ? Math.round(h.left) === 0 && Math.round(h.right) >= vw - 1 : false; })(),
      piiInputs: document.querySelectorAll('input[type=email], input[type=tel], input[name*=phone], input[name*=email]').length,
    };
  });
}

const menuState = (page, group) => page.evaluate((g) => {
  const t = document.querySelector(`[data-rh-menu-trigger="${g}"]`);
  const p = document.getElementById(`rh-gnav-${g}-panel`);
  const r = p?.getBoundingClientRect();
  return {
    expanded: t?.getAttribute('aria-expanded'),
    hidden: p ? p.hidden || getComputedStyle(p).display === 'none' : null,
    inView: r ? r.left >= -0.5 && r.right <= document.documentElement.clientWidth + 0.5 : null,
    triggerHeight: Math.round(t?.getBoundingClientRect().height ?? 0),
    focus: document.activeElement?.getAttribute('href') || document.activeElement?.id || document.activeElement?.tagName,
    outline: t ? getComputedStyle(t).outlineStyle : null,
  };
}, group);

/* ─── 시나리오 ─────────────────────────────────── */

async function runViewport(browser, vp, locale) {
  const scope = `${locale}-${vp.name}`;
  const context = await browser.newContext({
    viewport: { width: vp.width, height: vp.height },
    userAgent: vp.mobile ? MOBILE_UA : DESKTOP_UA,
    isMobile: vp.mobile,
    hasTouch: vp.mobile,
  });
  if (CANDIDATE) await installCandidate(context);
  const page = await context.newPage();
  const { errors, bad } = watch(page);

  // 언어 선택(엔진 API) 후 모든 문서를 직접 URL 로 연다.
  await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
  await page.evaluate((l) => window.G7Core?.locale?.change?.(l), locale);
  await page.waitForTimeout(600);

  for (const p of PAGES) {
    const key = p.doc.slots.content[0].props['data-rh-page'];
    await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle' });
    await waitDoc(page, key);
    const a = await layoutAudit(page);
    const group = p.path.split('/')[1];
    assert(scope, `direct ${p.path}`, a.h1.length > 0 && a.rawKeys.length === 0, `h1=${a.h1} raw=${a.rawKeys}`);
    assert(scope, `locale ${p.path}`, locale === 'en' ? /^[\x20-\x7E’“”·—]+$/.test(a.h1) : /[가-힣]/.test(a.h1), a.h1);
    assert(scope, `current ${p.path}`, a.current.length >= 1 && a.current.every((href) => href === p.path) && a.activeGroup.join() === group, `${a.current} / ${a.activeGroup}`);
    assert(scope, `overflow ${p.path}`, a.scrollWidth <= a.vw && a.offenders.length === 0, `${a.scrollWidth}/${a.vw} ${a.offenders}`);
    assert(scope, `44px ${p.path}`, a.small.length === 0, a.small.join(' '));
    assert(scope, `full-bleed ${p.path}`, a.headBleed && a.mainPadding === '0px', `pad=${a.mainPadding}`);
    if (SHOTS && (p.path === '/info/services' || p.path === '/policy/privacy')) {
      await page.screenshot({ path: path.join(SHOTS, `${scope}${p.path.replace(/\//g, '_')}.png`), fullPage: false });
    }
  }

  // 새로고침: 같은 문서·현재 위치 유지
  await page.goto(`${BASE}/policy/ai-workspace`, { waitUntil: 'networkidle' });
  await waitDoc(page, 'ai_policy');
  await page.reload({ waitUntil: 'networkidle' });
  await waitDoc(page, 'ai_policy');
  const reloaded = await layoutAudit(page);
  assert(scope, 'reload keeps page + current', reloaded.current.every((h) => h === '/policy/ai-workspace') && reloaded.activeGroup.join() === 'policy');

  // 메뉴: 초기 닫힘
  const init = [await menuState(page, 'info'), await menuState(page, 'policy')];
  assert(scope, 'menus start closed', init.every((s) => s.expanded === 'false' && s.hidden === true));
  assert(scope, 'trigger 44px', init.every((s) => s.triggerHeight >= 44), init.map((s) => s.triggerHeight).join());

  const trigger = (g) => page.locator(`[data-rh-menu-trigger="${g}"]`);
  if (!vp.mobile) {
    await trigger('info').hover();
    await page.waitForTimeout(300);
    assert(scope, 'hover does not open', (await menuState(page, 'info')).expanded === 'false');
  }

  // 클릭/터치로 열기 → 다른 메뉴 열면 앞 메뉴 닫힘 → 바깥 탭으로 닫힘
  if (vp.mobile) await trigger('info').tap(); else await trigger('info').click();
  let s = await menuState(page, 'info');
  assert(scope, `${vp.mobile ? 'tap' : 'click'} opens`, s.expanded === 'true' && s.hidden === false && s.inView, JSON.stringify(s));
  const panelAudit = await layoutAudit(page);
  assert(scope, 'open panel no overflow + 44px', panelAudit.scrollWidth <= panelAudit.vw && panelAudit.offenders.length === 0 && panelAudit.small.length === 0, `${panelAudit.offenders} ${panelAudit.small}`);
  if (SHOTS) await page.screenshot({ path: path.join(SHOTS, `${scope}_menu-open.png`) });
  if (vp.mobile) await trigger('policy').tap(); else await trigger('policy').click();
  assert(scope, 'one menu at a time', (await menuState(page, 'info')).expanded === 'false' && (await menuState(page, 'policy')).expanded === 'true');
  if (vp.mobile) await page.touchscreen.tap(Math.round(vp.width / 2), vp.height - 12); else await page.mouse.click(vp.width - 5, vp.height - 5);
  assert(scope, 'outside close', (await menuState(page, 'policy')).expanded === 'false');

  // 키보드: Enter 열기, ArrowDown 첫 항목, Escape 닫고 버튼 복귀, Space 토글, Tab 이탈 닫힘
  await trigger('info').focus();
  await page.keyboard.press('Enter');
  s = await menuState(page, 'info');
  assert(scope, 'Enter opens', s.expanded === 'true');
  await page.keyboard.press('ArrowDown');
  s = await menuState(page, 'info');
  assert(scope, 'ArrowDown focuses first item', s.focus === '/info/services', s.focus);
  await page.keyboard.press('Escape');
  s = await menuState(page, 'info');
  assert(scope, 'Escape closes + returns focus', s.expanded === 'false' && s.hidden && s.focus === 'rh-gnav-info-trigger', JSON.stringify(s));
  assert(scope, 'focus-visible outline', s.outline !== 'none', s.outline);
  await page.keyboard.press(' ');
  assert(scope, 'Space opens', (await menuState(page, 'info')).expanded === 'true');
  await page.keyboard.press(' ');
  assert(scope, 'Space closes', (await menuState(page, 'info')).expanded === 'false');
  await page.keyboard.press('Enter');
  for (let i = 0; i < 4; i += 1) await page.keyboard.press('Tab');
  assert(scope, 'Tab out closes', (await menuState(page, 'info')).expanded === 'false');

  // 하위 링크 선택 → SPA 이동(전체 새로고침 없음), 메뉴 닫힘, 현재 위치 갱신
  await page.evaluate(() => { window.__rhNoReload = true; });
  if (vp.mobile) await trigger('policy').tap(); else await trigger('policy').click();
  const target = page.locator('#rh-gnav-policy-panel a[href="/policy/open-source"]');
  if (vp.mobile) await target.tap(); else await target.click();
  await waitDoc(page, 'oss');
  const spa = await page.evaluate(() => ({ kept: window.__rhNoReload === true, path: location.pathname }));
  const after = await layoutAudit(page);
  assert(scope, 'submenu link SPA navigate', spa.kept && spa.path === '/policy/open-source', JSON.stringify(spa));
  assert(scope, 'menu closed after navigate + current updated', (await menuState(page, 'policy')).expanded === 'false' && after.current.every((h) => h === '/policy/open-source') && after.activeGroup.join() === 'policy');

  // 문서 목차: 해시 변경 없이 제목으로 이동·포커스
  await page.locator('.rh-toc-link').nth(1).click();
  await page.waitForTimeout(500);
  const toc = await page.evaluate(() => ({ hash: location.hash, focus: document.activeElement?.id }));
  assert(scope, 'toc jump keeps URL + focuses heading', toc.hash === '' && /^rh-doc-.+-title$/.test(toc.focus ?? ''), JSON.stringify(toc));

  // 푸터: 정보·정책 링크가 새 페이지로 간다
  const footerLabels = await page.evaluate(() => [...document.querySelectorAll('#footer button, footer button')].map((b) => b.textContent.trim()));
  const expectFooter = [lang[locale].nav.info_services, lang[locale].nav.policy_privacy, lang[locale].nav.policy_oss];
  assert(scope, 'footer lists info/policy pages', expectFooter.every((l) => footerLabels.includes(l)), footerLabels.join('|'));
  await page.evaluate((label) => [...document.querySelectorAll('footer button')].find((b) => b.textContent.trim() === label)?.click(), lang[locale].nav.info_principles);
  await waitDoc(page, 'principles');
  assert(scope, 'footer link navigates', new URL(page.url()).pathname === '/info/principles');

  // 기존 경계: 홈·게시판·검색·로그인·AI·상담 fail-closed
  await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.rh-home', { timeout: 15000 });
  await page.waitForTimeout(800);
  const home = await layoutAudit(page);
  assert(scope, 'home keeps visual sections + nav', await page.evaluate(() => ['rh-hero', 'rh-services', 'rh-cases', 'rh-process', 'rh-tech', 'rh-consult'].every((id) => document.getElementById(id)) && !!document.querySelector('.rh-gnav')), '');
  assert(scope, 'home overflow + PII 0', home.scrollWidth <= home.vw && home.piiInputs === 0, `${home.scrollWidth}/${home.vw} pii=${home.piiInputs}`);
  if (vp.name === '390' || vp.name === '1280') {
    for (const url of ['/board/community', '/board/notice', '/board/questions', '/search', '/login']) {
      const res = await page.goto(`${BASE}${url}`, { waitUntil: 'networkidle' });
      assert(scope, `existing ${url}`, (res?.status() ?? 0) < 400 && !(await page.$('.rh-doc')), String(res?.status()));
    }
    await page.goto(`${BASE}/ai`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    assert(scope, '/ai guest → /login', new URL(page.url()).pathname.endsWith('/login'), page.url());
  }

  assert(scope, 'no console/page errors', errors.length === 0, errors.slice(0, 3).join(' | '));
  // 상담 config 429 는 누적 요청의 환경 rate limit(0.2.1 기록과 같은 관측)이라 한도를 바꾸지 않고 따로 기록한다.
  const throttled = bad.filter((line) => line === '429 /api/modules/raonslab-product/consultations/config');
  if (throttled.length) results.push({ scope, check: 'consultation config 429', status: 'OBSERVED', detail: `${throttled.length}x` });
  const unexpected = bad.filter((line) => !throttled.includes(line));
  assert(scope, 'no unexpected 4xx/5xx', unexpected.length === 0, unexpected.slice(0, 5).join(', '));
  await context.close();
}

(async () => {
  if (SHOTS) fs.mkdirSync(SHOTS, { recursive: true });
  const browser = await chromium.launch();
  try {
    for (const locale of LOCALES) for (const vp of VIEWPORTS) await runViewport(browser, vp, locale);
  } catch (error) {
    assert('fixture', 'uncaught', false, error.stack);
  } finally {
    await browser.close();
  }
  const fail = results.filter((r) => r.status === 'FAIL');
  for (const r of fail) console.log(`FAIL [${r.scope}] ${r.check} — ${r.detail}`);
  for (const r of results.filter((x) => x.status === 'OBSERVED')) console.log(`OBSERVED [${r.scope}] ${r.check} — ${r.detail}`);
  console.log(JSON.stringify({ total: results.length, pass: results.filter((r) => r.status === 'PASS').length, fail: fail.length, observed: results.filter((r) => r.status === 'OBSERVED').length }));
  process.exit(fail.length ? 1 : 0);
})();
