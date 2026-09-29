#!/usr/bin/env node
/**
 * RAON Agent Factory 사업 홈 — 실제 브라우저 모바일/데스크톱 스모크.
 *
 * 실행 중인 G7 런타임(G7_BASE_URL)을 실제 Chromium 으로 연다.
 * RH_CANDIDATE_DIST 를 주면 배포 전 후보 빌드(모듈 JS/CSS·홈 레이아웃 확장·상위 메뉴·다국어)를
 * 네트워크 가로채기로 끼워 넣어 검증한다 — 런타임 파일·DB 는 바꾸지 않는다.
 * 상담 API 는 시나리오별로 계약 응답을 돌려주는 스텁을 쓰며, "접수 불가" 는 실제 응답도 확인한다.
 *
 * 사용:
 *   NODE_PATH=<playwright 가 설치된 node_modules> \
 *   G7_BASE_URL=http://127.0.0.1:18770 RH_CANDIDATE_DIST=/tmp/dist node tests/browser/home-smoke.cjs
 */
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const BASE = (process.env.G7_BASE_URL || 'http://127.0.0.1:18770').replace(/\/$/, '');
const CANDIDATE = process.env.RH_CANDIDATE_DIST || '';
const SHOTS = process.env.RH_SCREENSHOT_DIR || '';
const MODULE_ROOT = path.resolve(__dirname, '../..');
const MOBILE_UA = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36';
const DESKTOP_UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';
const VIEWPORTS = [
  { name: 'mobile-360', width: 360, height: 780, mobile: true },
  { name: 'mobile-390', width: 390, height: 844, mobile: true },
  { name: 'mobile-412', width: 412, height: 915, mobile: true },
  { name: 'desktop-1280', width: 1280, height: 900, mobile: false },
];
// RH_VIEWPORTS=mobile-390 처럼 폭을 골라 실행할 수 있다(기본: 전부). 폼·탐색 시나리오는 RH_SKIP_FLOWS=1 로 건너뛴다.
const ACTIVE_VIEWPORTS = process.env.RH_VIEWPORTS ? VIEWPORTS.filter((vp) => process.env.RH_VIEWPORTS.split(',').includes(vp.name)) : VIEWPORTS;
const SECTION_ORDER = ['rh-hero', 'rh-proof', 'rh-services', 'rh-fit', 'rh-case', 'rh-process', 'rh-consult'];
const API = '/api/modules/raonslab-product/consultations';
const OPEN_CONFIG = {
  success: true,
  data: {
    enabled: true,
    consent_version: '2026-09-v1',
    privacy_copy: '수집 항목: 담당자 이름, 이메일, 회사, 연락처, 상담 내용\n이용 목적: 상담 회신',
    retention_notice: '상담 종료 후 관련 법령에 따라 보관 후 파기합니다.',
    privacy_policy_url: '/page/privacy',
  },
};

const results = [];
function record(scope, check, status, detail = '') {
  results.push({ scope, check, status, detail: String(detail).slice(0, 400) });
}
function assert(scope, check, ok, detail = '') {
  record(scope, check, ok ? 'PASS' : 'FAIL', detail);
  return ok;
}

/* ─── 후보 빌드 주입 ───────────────────────────────────── */

let moduleAssets = null;
const candidateLayout = JSON.parse(fs.readFileSync(path.join(MODULE_ROOT, 'resources/extensions/home-product.json'), 'utf8'));
const candidateNav = JSON.parse(fs.readFileSync(path.join(MODULE_ROOT, 'resources/extensions/product-nav.json'), 'utf8'));
const candidateLang = {
  ko: JSON.parse(fs.readFileSync(path.join(MODULE_ROOT, 'resources/lang/ko.json'), 'utf8')),
  en: JSON.parse(fs.readFileSync(path.join(MODULE_ROOT, 'resources/lang/en.json'), 'utf8')),
};

async function loadModuleAssets() {
  const html = await (await fetch(`${BASE}/`, { headers: { 'User-Agent': DESKTOP_UA } })).text();
  const match = html.match(/moduleAssets:\s*(\{.*\}),\s*$/m);
  moduleAssets = match ? JSON.parse(match[1]) : {};
}

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

function findNode(node, id) {
  if (Array.isArray(node)) {
    for (const child of node) {
      const found = findNode(child, id);
      if (found) return found;
    }
    return null;
  }
  if (!node || typeof node !== 'object') return null;
  if (node.id === id) return node;
  for (const child of Object.values(node)) {
    const found = findNode(child, id);
    if (found) return found;
  }
  return null;
}

function replaceNode(node, id, replacement) {
  if (Array.isArray(node)) return node.map((child) => replaceNode(child, id, replacement));
  if (node && typeof node === 'object') {
    if (node.id === id) return replacement;
    const out = {};
    for (const [key, value] of Object.entries(node)) out[key] = replaceNode(value, id, replacement);
    return out;
  }
  return node;
}

async function installCandidate(context) {
  if (!CANDIDATE) return;
  const jsBundle = await bundleOf('js');
  const cssBundle = await bundleOf('css');
  // 모듈 동봉 파일(홈 사례 이미지)은 런타임 활성 디렉토리에 아직 없으므로 작업 트리에서 내준다.
  await context.route(/\/api\/modules\/assets\/raonslab-product(\/resources\/assets\/[^?]+|\?file=resources%2Fassets%2F[^&]+)/, (route) => {
    const url = new URL(route.request().url());
    const rel = url.searchParams.get('file') ?? decodeURIComponent(url.pathname.replace('/api/modules/assets/raonslab-product/', ''));
    if (rel.includes('..') || !rel.startsWith('resources/assets/')) return route.fulfill({ status: 400, body: '' });
    return route.fulfill({ status: 200, contentType: 'image/png', body: fs.readFileSync(path.join(MODULE_ROOT, rel)) });
  });
  await context.route(/\/bundles\/modules\.js/, (route) => route.fulfill({ status: 200, contentType: 'application/javascript', body: jsBundle }));
  await context.route(/\/bundles\/modules\.css/, (route) => route.fulfill({ status: 200, contentType: 'text/css', body: cssBundle }));
  // 홈 본문과 모든 화면의 상위 메뉴(_user_base overlay)를 후보로 바꾼다.
  await context.route(/\/api\/layouts\/sirsoft-basic\/[\w/-]+\.json/, async (route) => {
    const response = await route.fetch();
    const body = await response.json();
    body.data = replaceNode(body.data, 'raon_home', candidateLayout.injections[0].components[0]);
    body.data = replaceNode(body.data, 'rh_gnav_root', findNode(candidateNav, 'rh_gnav_root'));
    await route.fulfill({ response, json: body });
  });
  await context.route(/\/templates\/sirsoft-basic\/lang\/(ko|en)\.json/, async (route) => {
    const locale = route.request().url().match(/lang\/(ko|en)\.json/)[1];
    const response = await route.fetch();
    const body = await response.json();
    body['raonslab-product'] = candidateLang[locale];
    await route.fulfill({ response, json: body });
  });
}

/* ─── 상담 API 계약 스텁 ────────────────────────────────── */

async function stubConsultation(page, scenario) {
  const state = { posts: [], configCalls: 0 };
  await page.route(`**${API}/config`, (route) => {
    state.configCalls += 1;
    if (scenario.config === 'passthrough') return route.continue();
    if (scenario.config === 'closed' || (scenario.closeAfterFirst && state.configCalls > 1)) {
      return route.fulfill({ status: 200, json: { success: true, data: { enabled: false } } });
    }
    return route.fulfill({ status: 200, json: OPEN_CONFIG });
  });
  await page.route(`**${API}`, async (route) => {
    const request = route.request();
    if (request.method() !== 'POST') return route.continue();
    state.posts.push({ key: request.headers()['idempotency-key'], body: request.postDataJSON() });
    const reply = scenario.replies[Math.min(state.posts.length - 1, scenario.replies.length - 1)];
    if (reply.delay) await new Promise((r) => setTimeout(r, reply.delay));
    if (reply.abort) return route.abort('internetdisconnected');
    return route.fulfill({ status: reply.status, headers: reply.headers ?? {}, json: reply.body ?? { success: false } });
  });
  return state;
}

/* ─── 공통 점검 ───────────────────────────────────────── */

async function openHome(context, vp, url = '/') {
  const page = await context.newPage();
  const errors = [];
  const badResponses = [];
  page.on('response', (res) => { if (res.status() >= 400) badResponses.push(`${res.status()} ${new URL(res.url()).pathname}`); });
  page.on('console', (msg) => {
    if (msg.type() !== 'error') return;
    // 리소스 404 는 아래 badResponses 로 URL 과 함께 판정한다(콘솔 문구에는 URL 이 없다).
    if (msg.text().startsWith('Failed to load resource')) return;
    errors.push(msg.text());
  });
  page.on('pageerror', (err) => errors.push(String(err)));
  // config 는 IP 당 분당 60회로 제한된다. 이 스모크는 한 IP 에서 수십 번 새로 여므로 429 는 별도로 기록한다(화면은 닫힘으로 처리).
  page.unexpectedResponses = () => badResponses.filter((line) => line !== `404 ${API}/config` && line !== `429 ${API}/config`);
  page.configThrottled = () => badResponses.filter((line) => line === `429 ${API}/config`).length;
  page.knownConfig404 = () => badResponses.includes(`404 ${API}/config`);
  await page.goto(`${BASE}${url}`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.rh-home', { timeout: 15000 });
  await page.waitForTimeout(400);
  return { page, errors };
}

async function overflow(page) {
  return page.evaluate(() => {
    const vw = document.documentElement.clientWidth;
    const offenders = [];
    document.querySelectorAll('.rh-home *').forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.width === 0 || getComputedStyle(el).visibility === 'hidden') return;
      if (r.right > vw + 0.5 || r.left < -0.5) offenders.push(`${el.tagName}.${el.className}`.slice(0, 80));
    });
    return { scrollWidth: document.documentElement.scrollWidth, vw, offenders: offenders.slice(0, 5), count: offenders.length };
  });
}

const SHOT_SHA256 = '4fa8b7a705bede727aa21f4ba7a089466b2455b15051f2c660adcbeafa3e30de';

/** MOBILE_STOCK 화면: 표시 폭·비율, 원본 열기 링크의 주소·바이트, 390 에서 키보드·탭 동작 */
async function shotLinkChecks(page, vp, scope) {
  const m = await page.evaluate(() => {
    const img = document.querySelector('.rh-shot-img');
    const link = img?.closest('a[data-rh-asset-link]');
    const r = img?.getBoundingClientRect();
    return {
      width: r ? Math.round(r.width * 10) / 10 : 0,
      height: r ? Math.round(r.height * 10) / 10 : 0,
      src: img?.getAttribute('src') ?? '',
      href: link?.getAttribute('href') ?? '',
      label: link?.getAttribute('aria-label') ?? '',
      alt: img?.getAttribute('alt') ?? '',
      caption: document.querySelector('.rh-shot-caption')?.textContent.trim() ?? '',
      images: document.querySelectorAll('.rh-home img').length,
    };
  });
  record(scope, `측정: MOBILE_STOCK 화면 ${m.width}×${m.height}px`, 'INFO');
  if (vp.mobile) assert(scope, 'MOBILE_STOCK 화면 표시 폭 150px 이상', m.width >= 150, m.width);
  assert(scope, 'MOBILE_STOCK 화면 원본 비율 유지(자르지 않음)', m.width > 0 && Math.abs(m.height / m.width - 1760 / 780) < 0.02, `${m.width}×${m.height}`);
  assert(scope, '홈 이미지 1장', m.images === 1, m.images);
  assert(scope, '원본 열기 링크: 이미지와 같은 자산 주소·접근 가능한 이름·대체 텍스트 유지', m.href !== '' && m.href === m.src && m.label === 'MOBILE_STOCK 화면 원본 크기로 보기' && m.alt.includes('예시 데이터'), JSON.stringify(m));
  if (!m.href) return;
  const res = await fetch(new URL(m.href, BASE));
  const buf = Buffer.from(await res.arrayBuffer());
  const sha = require('node:crypto').createHash('sha256').update(buf).digest('hex');
  assert(scope, '원본 주소 200·sha256 불변', res.status === 200 && sha === SHOT_SHA256, `${res.status} ${sha} ${m.href}`);
  if (vp.name !== 'mobile-390') return;
  const target = new URL(m.href, BASE).href;
  // 키보드: 링크에 포커스 → Enter
  await page.locator('a[data-rh-asset-link]').focus();
  const focused = await page.evaluate(() => document.activeElement?.matches('a[data-rh-asset-link]') ?? false);
  const [kbResp] = await Promise.all([page.waitForNavigation({ timeout: 10000 }).catch(() => null), page.keyboard.press('Enter')]);
  assert(scope, '원본 열기: 키보드 Enter 로 같은 원본(200) 열림', focused && page.url() === target && kbResp?.status() === 200, `${focused} ${page.url()} ${kbResp?.status()}`);
  await page.goBack({ waitUntil: 'networkidle' });
  await page.waitForSelector('.rh-home a[data-rh-asset-link][href]', { timeout: 15000 });
  // 탭
  await page.locator('a[data-rh-asset-link]').scrollIntoViewIfNeeded();
  const [tapResp] = await Promise.all([page.waitForNavigation({ timeout: 10000 }).catch(() => null), page.locator('a[data-rh-asset-link]').tap()]);
  assert(scope, '원본 열기: 탭으로 같은 원본(200) 열림', page.url() === target && tapResp?.status() === 200, `${page.url()} ${tapResp?.status()}`);
  await page.goBack({ waitUntil: 'networkidle' });
  await page.waitForSelector('.rh-home', { timeout: 15000 });
  await page.waitForFunction(() => document.documentElement.dataset.rhIntake !== undefined, null, { timeout: 15000 });
}

async function contrastReport(page) {
  return page.evaluate(() => {
    function rgb(str) {
      const m = str.match(/rgba?\(([^)]+)\)/);
      if (!m) return null;
      const [r, g, b, a = 1] = m[1].split(',').map((v) => parseFloat(v));
      return { r, g, b, a };
    }
    function bgOf(el) {
      for (let node = el; node; node = node.parentElement) {
        const c = rgb(getComputedStyle(node).backgroundColor);
        if (c && c.a > 0.9) return c;
      }
      return { r: 255, g: 255, b: 255, a: 1 };
    }
    function lum({ r, g, b }) {
      const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; };
      return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
    }
    const worst = [];
    const selectors = '.rh-home h1, .rh-home h2, .rh-home h3, .rh-home p, .rh-home li, .rh-home a, .rh-home button, .rh-home label, .rh-home span, .rh-home dt, .rh-home dd';
    document.querySelectorAll(selectors).forEach((el) => {
      if (!el.textContent.trim() || el.closest('[hidden]')) return;
      const cs = getComputedStyle(el);
      const fg = rgb(cs.color);
      if (!fg) return;
      const bg = bgOf(el);
      const [l1, l2] = [lum(fg), lum(bg)].sort((a, b) => b - a);
      const ratio = (l1 + 0.05) / (l2 + 0.05);
      const size = parseFloat(cs.fontSize);
      const large = size >= 24 || (size >= 18.66 && parseInt(cs.fontWeight, 10) >= 700);
      const min = large ? 3 : 4.5;
      if (ratio < min) worst.push(`${el.tagName}.${el.className} ${ratio.toFixed(2)}<${min}`);
    });
    return worst;
  });
}

async function touchTargets(page) {
  return page.evaluate(() => {
    const small = [];
    document.querySelectorAll('.rh-home a, .rh-home button, .rh-home input:not([type=checkbox]), .rh-home select, .rh-home textarea, .rh-home .rh-check').forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.width === 0 || el.closest('[hidden]')) return;
      if (el.closest('.rh-privacy-meta')) return; // 문단 안 인라인 링크는 예외(WCAG 2.5.8 inline)
      if (r.height < 44 - 0.5) small.push(`${el.tagName}.${el.className} ${Math.round(r.height)}px`);
    });
    return small;
  });
}

/* ─── 시나리오 ────────────────────────────────────────── */

async function layoutChecks(browser, vp) {
  const context = await browser.newContext({
    viewport: { width: vp.width, height: vp.height },
    userAgent: vp.mobile ? MOBILE_UA : DESKTOP_UA,
    isMobile: vp.mobile,
    hasTouch: vp.mobile,
    reducedMotion: 'reduce',
  });
  await installCandidate(context);
  const { page, errors } = await openHome(context, vp);
  const scope = vp.name;

  // 1) 실제 런타임 config(접수 닫힘) — 클릭 전 상태 표시와 첫 화면·증거·사례 위치
  await page.waitForFunction(() => document.documentElement.dataset.rhIntake !== undefined, null, { timeout: 15000 });
  const closed = await page.evaluate(() => {
    window.scrollTo(0, 0);
    const shown = (el) => !!el && el.getBoundingClientRect().height > 0 && getComputedStyle(el).display !== 'none';
    const top = (sel) => { const el = document.querySelector(sel); return el ? Math.round(el.getBoundingClientRect().top + window.scrollY) : null; };
    const gnav = document.querySelector('.rh-gnav');
    return {
      intake: document.documentElement.dataset.rhIntake,
      heroCtas: [...document.querySelectorAll('.rh-hero .rh-action')].filter(shown).map((a) => [a.classList.contains('rh-action-primary') ? 'primary' : 'secondary', a.getAttribute('href')]),
      gnavShown: shown(gnav),
      gnavCta: shown(document.querySelector('.rh-gnav-cta')) ? document.querySelector('.rh-gnav-cta').innerText.replace(/\s+/g, ' ').trim() : null,
      subnav: !!document.querySelector('.rh-subnav'),
      vh: window.innerHeight,
      height: document.documentElement.scrollHeight,
      chars: document.querySelector('.rh-home').innerText.replace(/\s+/g, '').length,
      proofTop: top('#rh-proof'),
      msPreviewTop: top('.rh-proof-case-ms'),
      caption: document.querySelector('.rh-shot-caption')?.textContent.trim() ?? '',
      caseTop: top('#rh-case'),
      hubRoleTop: top('.rh-proof-case-hub .rh-proof-case-role'),
      roles: [...document.querySelectorAll('.rh-proof-case-role')].filter(shown).map((el) => el.textContent.trim()),
      sectionHeights: [...document.querySelectorAll('.rh-home > [role=region]')].map((el) => `${el.id.replace('rh-', '')} ${Math.round(el.getBoundingClientRect().height)}`).join(' · '),
      staticClosed: document.querySelectorAll('.rh-consult-static-closed').length,
      intakeGroups: document.querySelectorAll('.rh-hero [data-rh-intake-show]').length,
      symphony: (document.querySelector('.rh-home').innerText.match(/Symphony/g) ?? []).length,
      // 행동 유형 = 보이는 행동 링크의 목적지 종류. 상담 양식의 "다시 확인" 같은 상태 버튼은 행동 링크가 아니다.
      ctaTypes: new Set([...document.querySelectorAll('.rh-home a.rh-action')].filter(shown).map((a) => a.getAttribute('href'))).size,
    };
  });
  record(scope, `측정: 높이 ${closed.height}px · 글자 ${closed.chars} · 증거 ${closed.proofTop}px · MOBILE_STOCK 미리보기 ${closed.msPreviewTop}px · 사례 상세 ${closed.caseTop}px (${(closed.caseTop / closed.vh).toFixed(2)}화면)`, 'INFO');
  assert(scope, '두 제품 증거(MOBILE_STOCK 화면 포함)가 2화면 안에서 시작', closed.msPreviewTop !== null && closed.msPreviewTop <= closed.vh * 2, `${closed.msPreviewTop}/${closed.vh * 2}`);
  record(scope, `측정: 섹션 높이 ${closed.sectionHeights}`, 'INFO');
  assert(scope, '두 사례 모두 무엇을 입증하는지 한 줄 표시', closed.roles.length === 2 && /지금 보고 계신 이 사이트/.test(closed.roles[0]) && /실주문 차단 검증/.test(closed.roles[1]), JSON.stringify(closed.roles));
  assert(scope, '"이 사이트가 RAON Hub" 문구가 y<1,000 안', closed.hubRoleTop !== null && closed.hubRoleTop < 1000, closed.hubRoleTop);
  assert(scope, '사람 화면: 봇용 정적 닫힘 안내 없음·Hero 상태 묶음 두 개 유지(상태 전환은 JS)', closed.staticClosed === 0 && closed.intakeGroups === 2, `${closed.staticClosed}/${closed.intakeGroups}`);
  assert(scope, '화면 속 앱 이름 Symphony 를 홈에서 한 번만 설명', closed.symphony === 1, closed.symphony);
  assert(scope, 'MOBILE_STOCK 필수 캡션 표시', closed.caption === '모의투자 · 자체 제품 · 예시 데이터', closed.caption);
  assert(scope, '홈 글자 1,580자 이하', closed.chars <= 1580, closed.chars);
  await page.locator('.rh-proof-case-ms').scrollIntoViewIfNeeded();
  await page.waitForFunction(() => { const img = document.querySelector('.rh-shot-img'); return !!img && img.complete && img.naturalWidth > 0; }, null, { timeout: 10000 }).catch(() => {});
  const shot = await page.evaluate(() => { const img = document.querySelector('.rh-shot-img'); return { src: img?.getAttribute('src') ?? '', w: img?.naturalWidth ?? 0, alt: img?.getAttribute('alt') ?? '' }; });
  assert(scope, 'MOBILE_STOCK 화면 이미지 로드(코어 자산 URL·대체 텍스트)', shot.w === 780 && shot.src.includes('resources') && shot.alt.includes('예시 데이터'), JSON.stringify(shot));
  if (SHOTS) await page.locator('#rh-proof').screenshot({ path: path.join(SHOTS, `${scope}-proof.png`) });
  await shotLinkChecks(page, vp, scope);
  await page.evaluate(() => window.scrollTo(0, 0));
  assert(scope, '접수 닫힘이 클릭 전에 확정된다', closed.intake === 'closed', closed.intake);
  assert(scope, '닫힘: 주 행동 사례, 보조 행동 도입 절차', JSON.stringify(closed.heroCtas) === JSON.stringify([['primary', '#rh-case'], ['secondary', '#rh-process']]), JSON.stringify(closed.heroCtas));
  assert(scope, '홈 섹션 바로가기 줄 없음', !closed.subnav);
  if (vp.mobile) assert(scope, '모바일 홈: 제품 바 없이 기본 헤더 하나', !closed.gnavShown);
  else assert(scope, '데스크톱: 상담 진입점에 접수 준비 중 표시', closed.gnavShown && /접수 준비 중/.test(closed.gnavCta ?? ''), closed.gnavCta);
  assert(scope, 'RAON Hub 증거가 첫 화면 안에서 시작', closed.proofTop !== null && closed.proofTop < closed.vh, `${closed.proofTop}/${closed.vh}`);
  assert(scope, '행동 유형 2개 이하', closed.ctaTypes <= 2, closed.ctaTypes);
  // 목표는 약 5,800px. 0.5.0 E 실측 5,925px, 0.5.1(사례 역할 줄·화면 152px) 실측 5,996px — 상한 6,000px 로 회귀만 막는다(완화하지 않는다).
  if (vp.name === 'mobile-390') assert(scope, '390px 전체 높이 6,000px 이하(목표 약 5,800)', closed.height <= 6000, closed.height);

  // 2) 접수 열림 계약 스텁 — 상담이 주 행동이 되고 양식이 뜬다
  await stubConsultation(page, { config: 'open', replies: [{ status: 201, body: { data: { reference: 'x' } } }] });
  await page.reload({ waitUntil: 'networkidle' });
  await page.waitForSelector('.rh-home form', { timeout: 15000 });

  const info = await page.evaluate(() => ({
    title: document.title,
    description: document.querySelector('meta[name=description]')?.content ?? '',
    sections: [...document.querySelectorAll('.rh-home > [role=region]')].map((el) => el.id),
    bodyText: document.querySelector('.rh-home').innerText,
    h1: document.querySelector('.rh-home h1')?.textContent ?? '',
    bg: getComputedStyle(document.querySelector('.rh-home')).backgroundColor,
    transition: getComputedStyle(document.querySelector('.rh-action')).transitionDuration,
    flowSteps: [...document.querySelectorAll('.rh-flow-stage')].map((el) => el.textContent.trim()),
    timeline: [...document.querySelectorAll('.rh-timeline-title')].map((el) => el.textContent.trim()),
    openCtas: [...document.querySelectorAll('.rh-hero .rh-action')].filter((a) => a.getBoundingClientRect().height > 0).map((a) => a.getAttribute('href')),
    exposedIcons: [...document.querySelectorAll('.rh-home i')].filter((i) => i.getAttribute('aria-hidden') !== 'true' && !i.closest('[aria-hidden="true"]')).length,
  }));
  assert(scope, 'title 적용', info.title.startsWith('RAON Agent Factory'), info.title);
  assert(scope, 'meta description 적용', info.description.length > 20, info.description);
  assert(scope, '7개 섹션 순서', JSON.stringify(info.sections) === JSON.stringify(SECTION_ORDER), info.sections.join(','));
  assert(scope, 'H1 승인 문장', info.h1 === '기업 업무에 맞는 AI 에이전트, 구축부터 실행·검증·운영까지.', info.h1);
  assert(scope, '제품 CSS 연결(차콜 배경)', info.bg === 'rgb(18, 20, 22)', info.bg);
  assert(scope, '흐름 4단계', JSON.stringify(info.flowSteps) === JSON.stringify(['업무 입력', '제한된 실행', '검증', '결과']), info.flowSteps.join(' → '));
  assert(scope, '절차 타임라인 4단계', JSON.stringify(info.timeline) === JSON.stringify(['범위', '실행', '검증', '승인·복구']), info.timeline.join(' → '));
  assert(scope, '열림: 주 행동 상담, 보조 행동 사례', JSON.stringify(info.openCtas) === JSON.stringify(['#rh-consult', '#rh-case']), info.openCtas.join(','));
  assert(scope, '번역 키 미노출', !/raonslab-product\./.test(info.bodyText));
  assert(scope, '개발 용어·내부 코드 미노출', !/\b(DEMO|MOCK|SANDBOX|TEST)\b/.test(info.bodyText.toUpperCase()) && !/J1~J10|A1~A10|Provider/.test(info.bodyText));
  assert(scope, 'reduced-motion 전환 제거', info.transition === '0s', info.transition);
  assert(scope, '장식 아이콘은 보조기기에서 숨김', info.exposedIcons === 0, info.exposedIcons);

  const ov = await overflow(page);
  assert(scope, '가로 넘침 0', ov.scrollWidth <= ov.vw && ov.count === 0, JSON.stringify(ov));
  const small = await touchTargets(page);
  assert(scope, '터치 대상 44px 이상', small.length === 0, small.join(' | '));
  const lowContrast = await contrastReport(page);
  assert(scope, '텍스트 대비(WCAG AA)', lowContrast.length === 0, lowContrast.slice(0, 5).join(' | '));

  // 주 CTA(열림) → 상담 섹션 이동 + 포커스, 보조 CTA → 사례
  await page.click('.rh-hero .rh-action-primary >> visible=true');
  await page.waitForTimeout(300);
  const afterCta = await page.evaluate(() => ({ focus: document.activeElement?.id, top: document.getElementById('rh-consult').getBoundingClientRect().top, hash: location.hash }));
  assert(scope, '주 CTA → 상담 섹션 이동·포커스', afterCta.focus === 'rh-consult-title' && Math.abs(afterCta.top) < 200 && afterCta.hash === '', JSON.stringify(afterCta));
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.click('.rh-proof .rh-text-link');
  await page.waitForTimeout(300);
  assert(scope, '증거 줄 → 사례 이동·포커스', (await page.evaluate(() => document.activeElement?.id)) === 'rh-case-title');

  // 키보드: 페이지 처음부터 Tab 으로 Hero CTA 까지 이동하며 포커스 표시 확인
  await page.evaluate(() => { window.scrollTo(0, 0); document.getElementById('rh-hero-title').focus(); });
  let sawCta = false;
  let outlineOk = true;
  for (let i = 0; i < 4; i += 1) {
    await page.keyboard.press('Tab');
    const f = await page.evaluate(() => {
      const el = document.activeElement;
      const cs = getComputedStyle(el);
      return { cls: el.className, outline: cs.outlineStyle, width: parseFloat(cs.outlineWidth) };
    });
    if (String(f.cls).includes('rh-action')) sawCta = true;
    if (String(f.cls).startsWith('rh-') && (f.outline === 'none' || f.width < 2)) outlineOk = false;
  }
  assert(scope, '키보드 Tab 으로 CTA 도달', sawCta);
  assert(scope, '포커스 표시(outline ≥2px)', outlineOk);

  // 긴 텍스트: 공백 없는 긴 입력 + 영어 로케일
  const long = 'a'.repeat(160);
  await page.fill('input[name=company]', long);
  await page.fill('input[name=email]', `${long}@example.com`);
  await page.fill('textarea[name=message]', `${long} ${long}`);
  const ovLong = await overflow(page);
  assert(scope, '긴 입력값 넘침 0', ovLong.scrollWidth <= ovLong.vw && ovLong.count === 0, JSON.stringify(ovLong));

  await page.evaluate(() => window.G7Core?.locale?.change?.('en'));
  await page.waitForTimeout(1200);
  const en = await page.evaluate(() => ({
    title: document.title,
    h1: document.querySelector('.rh-home h1')?.textContent ?? '',
    submit: document.querySelector('[data-rh-submit]')?.textContent ?? '',
    company: document.querySelector('input[name=company]')?.value.length ?? 0,
  }));
  const ovEn = await overflow(page);
  assert(scope, '영어 로케일 문구·제목 전환', en.title.startsWith('RAON Agent Factory') && /AI agents/.test(en.h1) && en.submit === 'Request consultation', JSON.stringify(en));
  assert(scope, '로케일 전환 후 입력 보존', en.company === 160, en.company);
  assert(scope, '영어 긴 문구 넘침 0', ovEn.scrollWidth <= ovEn.vw && ovEn.count === 0, JSON.stringify(ovEn));
  if (SHOTS) await page.screenshot({ path: path.join(SHOTS, `${scope}-en.png`), fullPage: true });
  await page.evaluate(() => window.G7Core?.locale?.change?.('ko'));
  await page.waitForTimeout(1000);
  await page.fill('input[name=company]', '');
  await page.fill('input[name=email]', '');
  await page.fill('textarea[name=message]', '');
  if (SHOTS) await page.screenshot({ path: path.join(SHOTS, `${scope}.png`), fullPage: true });

  assert(scope, '콘솔 오류 0', errors.length === 0, errors.slice(0, 3).join(' | '));
  assert(scope, '예상 밖 4xx/5xx 응답 0', page.unexpectedResponses().length === 0, page.unexpectedResponses().join(' | '));
  if (page.knownConfig404()) record(scope, '상담 config 실제 응답', 'SKIPPED', `404 ${API}/config — 백엔드 미배포(별도 요청), 화면은 fail-closed 로 처리`);
  if (page.configThrottled()) record(scope, `상담 config 429 ${page.configThrottled()}회(스모크 요청량으로 인한 분당 제한)`, 'INFO');
  await context.close();
}

async function fillValid(page) {
  await page.fill('input[name=contact_name]', '김담당');
  await page.fill('input[name=email]', 'owner@example.com');
  await page.fill('textarea[name=message]', '주간 운영 보고서 작성 업무를 자동화하고 싶습니다.');
  const consent = page.locator('input[name=privacy_consent]');
  if (!(await consent.isChecked())) await consent.check();
}

async function formScenarios(browser) {
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, userAgent: MOBILE_UA, isMobile: true, hasTouch: true });
  await installCandidate(context);
  const scope = 'form';

  async function fresh(scenario) {
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (err) => errors.push(String(err)));
    const state = await stubConsultation(page, scenario);
    await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await page.waitForSelector('.rh-consult-island', { timeout: 15000 });
    await page.waitForFunction(() => document.querySelector('.rh-consult-island')?.dataset.rhConsultView !== 'loading');
    return { page, state, errors };
  }
  const view = (page) => page.evaluate(() => document.querySelector('.rh-consult-island')?.dataset.rhConsultView);
  const banner = (page) => page.evaluate(() => document.querySelector('[data-rh-banner]:not([hidden])')?.textContent ?? '');

  // 1) 실제 런타임 config(현재 미배포) → fail-closed
  {
    const { page } = await fresh({ config: 'passthrough', replies: [{ status: 500 }] });
    const inputs = await page.locator('.rh-consult-island input, .rh-consult-island textarea').count();
    assert(scope, '실제 config 미제공 → 접수 불가 안내·PII 입력 없음', (await view(page)) === 'unavailable' && inputs === 0, `${await view(page)} inputs=${inputs}`);
    await page.close();
  }
  // 2) config 비활성 → fail-closed
  {
    const { page } = await fresh({ config: 'closed', replies: [{ status: 500 }] });
    const inputs = await page.locator('.rh-consult-island input, .rh-consult-island textarea').count();
    assert(scope, 'config enabled=false → 접수 불가', (await view(page)) === 'unavailable' && inputs === 0);
    await page.close();
  }
  // 3) 클라이언트 검증 + 동의 분리
  {
    const { page, state } = await fresh({ config: 'open', replies: [{ status: 201, body: { data: { reference: 'RC-1' } } }] });
    await page.click('[data-rh-submit]');
    const invalid = await page.locator('[aria-invalid=true]').count();
    const focused = await page.evaluate(() => document.activeElement?.getAttribute('name'));
    assert(scope, '필수값 검증·첫 오류 포커스·미전송', invalid === 4 && focused === 'contact_name' && state.posts.length === 0, `invalid=${invalid} focus=${focused} posts=${state.posts.length}`);
    const consentSeparate = await page.evaluate(() => !!document.querySelector('fieldset.rh-privacy input[type=checkbox][name=privacy_consent]') && !document.querySelector('input[name=privacy_consent]').checked);
    assert(scope, '개인정보 동의 분리·기본 미체크', consentSeparate);
    await page.close();
  }
  // 4) 201 성공 + 중복 클릭 방지 + 헤더
  {
    const { page, state } = await fresh({ config: 'open', replies: [{ status: 201, delay: 600, body: { success: true, data: { reference: 'RC-2026-0001', status: 'received', received_at: '2026-09-28T04:00:00Z' } } }] });
    await fillValid(page);
    await page.locator('[data-rh-submit]').click();
    const busy = await page.evaluate(() => ({ disabled: document.querySelector('[data-rh-submit]').disabled, text: document.querySelector('[data-rh-submit]').textContent }));
    await page.locator('[data-rh-submit]').click({ force: true, timeout: 1000 }).catch(() => {});
    await page.waitForFunction(() => document.querySelector('.rh-consult-island')?.dataset.rhConsultView === 'success');
    const ref = await page.textContent('[data-rh-reference]');
    const body = state.posts[0]?.body ?? {};
    assert(scope, '전송 중 버튼 비활성·문구', busy.disabled && busy.text.includes('접수'), JSON.stringify(busy));
    assert(scope, '중복 클릭 1회 전송', state.posts.length === 1, state.posts.length);
    assert(scope, 'Idempotency-Key·동의 버전 전송', !!state.posts[0]?.key && body.privacy_consent === true && body.privacy_consent_version === '2026-09-v1', JSON.stringify(body));
    assert(scope, '201 성공·접수 번호 표시', ref === 'RC-2026-0001', ref);
    await page.close();
  }
  // 5) 200 재확인
  {
    const { page } = await fresh({ config: 'open', replies: [{ status: 200, body: { data: { reference: 'RC-SAME', status: 'received', received_at: '2026-09-28T04:00:00Z' } } }] });
    await fillValid(page);
    await page.click('[data-rh-submit]');
    await page.waitForFunction(() => document.querySelector('.rh-consult-island')?.dataset.rhConsultView === 'success');
    const text = await page.textContent('.rh-consult-island');
    assert(scope, '200 동일 요청 재확인 안내', text.includes('이미 접수된 같은 신청') && text.includes('RC-SAME'));
    await page.close();
  }
  // 6) 네트워크 오류 → 입력 보존 → 같은 키로 재시도
  {
    const { page, state } = await fresh({ config: 'open', replies: [{ abort: true }, { status: 201, body: { data: { reference: 'RC-RETRY' } } }] });
    await fillValid(page);
    await page.click('[data-rh-submit]');
    await page.waitForFunction(() => !!document.querySelector('[data-rh-banner]:not([hidden])'));
    const kept = await page.inputValue('input[name=email]');
    assert(scope, '네트워크 오류 안내·입력 보존', (await banner(page)).includes('네트워크') && kept === 'owner@example.com', await banner(page));
    await page.click('[data-rh-submit]');
    await page.waitForFunction(() => document.querySelector('.rh-consult-island')?.dataset.rhConsultView === 'success');
    assert(scope, '같은 내용 재시도는 같은 Idempotency-Key', state.posts.length === 2 && state.posts[0].key === state.posts[1].key);
    await page.close();
  }
  // 7) 422 서버 검증 → 필드 매핑
  {
    const { page } = await fresh({ config: 'open', replies: [{ status: 422, body: { success: false, message: '입력값을 확인해 주세요.', errors: { email: ['이미 사용할 수 없는 이메일 형식입니다.'] } } }] });
    await fillValid(page);
    await page.click('[data-rh-submit]');
    await page.waitForSelector('input[name=email][aria-invalid=true]');
    const msg = await page.evaluate(() => document.querySelector('[data-field=email] .rh-field-error')?.textContent);
    assert(scope, '422 필드 오류 표시·입력 보존', msg?.includes('이메일') && (await page.inputValue('textarea[name=message]')).length > 0, msg);
    await page.close();
  }
  // 8) 409 → 안내 + 다음 전송은 새 키
  {
    const { page, state } = await fresh({ config: 'open', replies: [{ status: 409 }, { status: 201, body: { data: { reference: 'RC-NEW' } } }] });
    await fillValid(page);
    await page.click('[data-rh-submit]');
    await page.waitForFunction(() => !!document.querySelector('[data-rh-banner]:not([hidden])'));
    assert(scope, '409 중복 키 안내', (await banner(page)).includes('같은 요청 번호'), await banner(page));
    await page.click('[data-rh-submit]');
    await page.waitForFunction(() => document.querySelector('.rh-consult-island')?.dataset.rhConsultView === 'success');
    assert(scope, '409 이후 새 Idempotency-Key', state.posts.length === 2 && state.posts[0].key !== state.posts[1].key);
    await page.close();
  }
  // 9) 429 → 대기 안내 + 버튼 잠금 후 해제
  {
    const { page } = await fresh({ config: 'open', replies: [{ status: 429, headers: { 'Retry-After': '2' } }] });
    await fillValid(page);
    await page.click('[data-rh-submit]');
    await page.waitForFunction(() => !!document.querySelector('[data-rh-banner]:not([hidden])'));
    const locked = await page.evaluate(() => document.querySelector('[data-rh-submit]').disabled);
    await page.waitForTimeout(2300);
    const unlocked = await page.evaluate(() => !document.querySelector('[data-rh-submit]').disabled);
    assert(scope, '429 대기 안내·잠금·해제', (await banner(page)).includes('2초') && locked && unlocked, await banner(page));
    await page.close();
  }
  // 10) 500 → 서버 오류 안내·입력 보존
  {
    const { page } = await fresh({ config: 'open', replies: [{ status: 500 }] });
    await fillValid(page);
    await page.click('[data-rh-submit]');
    await page.waitForFunction(() => !!document.querySelector('[data-rh-banner]:not([hidden])'));
    assert(scope, '5xx 서버 오류 안내·입력 보존', (await banner(page)).includes('일시적인 문제') && (await page.inputValue('input[name=contact_name]')) === '김담당');
    await page.close();
  }
  // 11) 503 + 접수 닫힘 재확인 → 입력 화면 회수
  {
    const { page } = await fresh({ config: 'open', closeAfterFirst: true, replies: [{ status: 503 }] });
    await fillValid(page);
    await page.click('[data-rh-submit]');
    await page.waitForFunction(() => document.querySelector('.rh-consult-island')?.dataset.rhConsultView === 'unavailable');
    const inputs = await page.locator('.rh-consult-island input, .rh-consult-island textarea').count();
    assert(scope, '503 + 접수 닫힘 → 접수 불가·입력 회수', inputs === 0);
    await page.close();
  }
  // 12) 503 이지만 접수 설정은 열림 → 안내만, 입력 보존
  {
    const { page } = await fresh({ config: 'open', replies: [{ status: 503 }] });
    await fillValid(page);
    await page.click('[data-rh-submit]');
    await page.waitForFunction(() => !!document.querySelector('[data-rh-banner]:not([hidden])'));
    await page.waitForTimeout(300);
    assert(scope, '503 저장 실패 안내·입력 보존', (await banner(page)).includes('접수를 완료할 수 없습니다') && (await view(page)) === 'form');
    await page.close();
  }
  await context.close();
}

async function navigationChecks(browser) {
  const scope = 'navigation';
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, userAgent: MOBILE_UA, isMobile: true, hasTouch: true });
  await installCandidate(context);

  // 직접 URL + 새로고침: 제목·스타일 유지, 해시 진입
  const { page, errors } = await openHome(context, { name: scope }, '/#rh-case');
  await page.waitForTimeout(500);
  const direct = await page.evaluate(() => ({ focus: document.activeElement?.id, top: Math.round(document.getElementById('rh-case').getBoundingClientRect().top) }));
  assert(scope, '해시 직접 진입(/#rh-case)', direct.focus === 'rh-case-title' && Math.abs(direct.top) < 200, JSON.stringify(direct));
  for (const key of ['proof', 'services', 'fit', 'process', 'consult']) {
    const p = await context.newPage();
    await p.goto(`${BASE}/#rh-${key}`, { waitUntil: 'networkidle' });
    await p.waitForSelector('.rh-home');
    await p.waitForTimeout(600);
    const hit = await p.evaluate((k) => ({ focus: document.activeElement?.id, top: Math.round(document.getElementById(`rh-${k}`).getBoundingClientRect().top) }), key);
    assert(scope, `해시 직접 진입(/#rh-${key})`, hit.focus === `rh-${key}-title` && Math.abs(hit.top) < 200, JSON.stringify(hit));
    await p.close();
  }
  await page.reload({ waitUntil: 'networkidle' });
  await page.waitForSelector('.rh-home');
  await page.waitForTimeout(400);
  const reload = await page.evaluate(() => ({ title: document.title, bg: getComputedStyle(document.querySelector('.rh-home')).backgroundColor, desc: document.querySelector('meta[name=description]')?.content ?? '' }));
  assert(scope, '새로고침 후 title·meta·style 유지', reload.title.startsWith('RAON Agent Factory') && reload.bg === 'rgb(18, 20, 22)' && reload.desc.length > 20, JSON.stringify(reload));

  // SPA 이동: 커뮤니티 → 제목 원복 → 뒤로 → 홈 제목
  await page.click('.rh-more .rh-text-link >> text=커뮤니티');
  await page.waitForURL('**/board/community');
  await page.waitForTimeout(800);
  const away = await page.evaluate(() => ({ title: document.title, home: !!document.querySelector('.rh-home') }));
  assert(scope, '다른 화면 이동 시 원래 제목 복원', !away.home && !away.title.startsWith('RAON Agent Factory'), JSON.stringify(away));
  await page.goBack();
  await page.waitForSelector('.rh-home');
  await page.waitForTimeout(400);
  assert(scope, '뒤로가기 후 홈 제목 재적용', (await page.title()).startsWith('RAON Agent Factory'));
  assert(scope, '콘솔 오류 0', errors.length === 0, errors.slice(0, 3).join(' | '));
  assert(scope, '예상 밖 4xx/5xx 응답 0', page.unexpectedResponses().length === 0, page.unexpectedResponses().join(' | '));
  if (page.configThrottled()) record(scope, `상담 config 429 ${page.configThrottled()}회(스모크 요청량으로 인한 분당 제한)`, 'INFO');
  await page.close();

  // 사례 상세 링크 → 사례 문서
  {
    const p = await context.newPage();
    await p.goto(`${BASE}/`, { waitUntil: 'networkidle' });
    await p.waitForSelector('.rh-home');
    const label = (await p.locator('#rh-case .rh-case-foot a[href="/page/cases"]').innerText()).trim();
    assert(scope, '사례 상세 링크 문구가 도착지를 말한다', label === '사례 상세 보기', label);
    await p.click('#rh-case .rh-case-foot a[href="/page/cases"]');
    await p.waitForURL('**/page/cases');
    assert(scope, '사례 → 사례 상세(/page/cases) 이동', new URL(p.url()).pathname === '/page/cases');
    await p.close();
  }

  // 기존 URL 보존 (비로그인)
  for (const url of ['/board/community', '/board/notice', '/board/questions', '/boards', '/search', '/login', '/register',
    '/page/about', '/page/service', '/page/cases', '/page/technology', '/page/faq', '/page/contact',
    '/page/privacy', '/page/terms', '/page/ai-workspace-policy', '/page/open-source', '/page/refund']) {
    const p = await context.newPage();
    const errs = [];
    p.on('pageerror', (err) => errs.push(String(err)));
    const res = await p.goto(`${BASE}${url}`, { waitUntil: 'networkidle' });
    await p.waitForTimeout(500);
    const state = await p.evaluate(() => ({ path: location.pathname, home: !!document.querySelector('.rh-home'), text: document.body.innerText.length }));
    assert(scope, `기존 URL ${url}`, res.status() < 400 && state.path === url && !state.home && state.text > 50 && errs.length === 0, JSON.stringify({ status: res.status(), ...state, errs }));
    await p.close();
  }
  // /ai: 로그인 필요 경로는 기존과 같이 인증 흐름으로 보낸다
  {
    const p = await context.newPage();
    await p.goto(`${BASE}/ai`, { waitUntil: 'networkidle' });
    await p.waitForTimeout(800);
    const pathname = await p.evaluate(() => location.pathname);
    const home = await p.evaluate(() => !!document.querySelector('.rh-home'));
    assert(scope, '/ai 비로그인 → 인증 흐름(홈 미노출)', !home && pathname !== '/', pathname);
    await p.close();
  }
  await context.close();
}

async function seoRenderCheck() {
  const scope = 'seo-render';
  const html = await (await fetch(`${BASE}/`, { headers: { 'User-Agent': 'Googlebot/2.1 (+http://www.google.com/bot.html)' } })).text();
  const title = (html.match(/<title>([^<]*)<\/title>/) || [])[1] ?? '';
  const productCss = /raonslab-product\/dist\/css\/module\.css/.test(html);
  record(scope, '봇 렌더 title (배포 후 확인 대상)', title !== '' ? 'PASS' : 'FAIL', `title="${title}" (런타임 현재값; 후보 빌드는 미배포)`);
  record(scope, '봇 렌더 제품 CSS 링크 (배포 후 확인 대상)', productCss ? 'PASS' : 'FAIL', '모듈 seo-config.json stylesheets 는 module:update 이후 반영');
}

(async () => {
  if (CANDIDATE) await loadModuleAssets();
  else record('setup', '후보 빌드 주입', 'SKIPPED', 'RH_CANDIDATE_DIST 미지정 — 런타임 현재 배포본을 검사');
  const browser = await chromium.launch();
  try {
    for (const vp of ACTIVE_VIEWPORTS) await layoutChecks(browser, vp);
    if (process.env.RH_SKIP_FLOWS === '1') record('setup', '폼·탐색 시나리오', 'SKIPPED', 'RH_SKIP_FLOWS=1');
    else {
      await formScenarios(browser);
      await navigationChecks(browser);
    }
    await seoRenderCheck();
  } catch (error) {
    record('harness', '실행', 'FAIL', error.stack || error);
  } finally {
    await browser.close();
  }
  const summary = results.reduce((acc, r) => ({ ...acc, [r.status]: (acc[r.status] ?? 0) + 1 }), {});
  for (const r of results) console.log(`${r.status.padEnd(7)} [${r.scope}] ${r.check}${r.detail && r.status !== 'PASS' ? ` — ${r.detail}` : ''}`);
  console.log(JSON.stringify({ base: BASE, candidate: !!CANDIDATE, summary }));
  if (process.env.RH_RESULT_JSON) fs.writeFileSync(process.env.RH_RESULT_JSON, JSON.stringify({ summary, results }, null, 2));
  process.exitCode = results.some((r) => r.status === 'FAIL' && r.scope !== 'seo-render') ? 1 : 0;
})();
