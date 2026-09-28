#!/usr/bin/env node
/**
 * Focused runtime smoke for RAON native sirsoft-page documents.
 *
 * - 문서 목록·순서는 resources/taxonomy/info-policy.json 단일 출처에서 읽는다.
 * - human DOM 은 명시한 일반 Chrome UA 로만 본다. HeadlessChrome UA 는 SeoMiddleware 가 봇으로 보고
 *   서버 렌더 HTML(React 없음)을 주므로 human 검증에 쓰면 거짓 실패/거짓 통과가 난다.
 * - 배포 전 구 런타임(0.4.0)에 실행하면 새 표현·샘플 본문 때문에 실패하는 것이 정상이다(EXPECTED_FAIL_PREDEPLOY).
 *   release gate 는 배포 + DB 적용 뒤 1회 실행이다.
 * - G7_SMOKE_CONTENT_GATE=0 이면 about/faq/contact/refund 본문 교체 전 단계로 보고 샘플 문구 검사를 건너뛴다.
 */
const { spawnSync } = require('node:child_process');
const { resolve } = require('node:path');
const { chromium, request } = require('playwright');

const BASE = (process.env.G7_BASE_URL || 'http://127.0.0.1:18770').replace(/\/$/, '');
const REPO_ROOT = resolve(__dirname, '../../../../..');
const PHP_BINARY = process.env.G7_PHP_BINARY || '/usr/bin/php8.3';
const VIEWPORTS = [
  { name: '360', width: 360, height: 780 },
  { name: '390', width: 390, height: 844 },
  { name: '412', width: 412, height: 915 },
  { name: '1280', width: 1280, height: 900 },
];
const TAXONOMY = require('../../resources/taxonomy/info-policy.json');
const DOC_GROUPS = Object.fromEntries(TAXONOMY.groups.map((group) => [group.key, group.items.map((item) => item.slug)]));
const DOC_SLUGS = TAXONOMY.groups.flatMap((group) => group.items.map((item) => item.slug));
const CONTENT_GATE = process.env.G7_SMOKE_CONTENT_GATE !== '0';
const PRESENTATION_WAIT_MS = Number(process.env.G7_SMOKE_PRESENTATION_WAIT_MS || 3000);
const SAMPLE_TEXT = /입력하세요|그누보드7에 오신 것을 환영합니다|평일 오전 9시|영업일 내|\b(DEMO|MOCK|SANDBOX|TEST)\b/;
/** Legacy compatibility routes exist only for the seven 0.4.0 native documents. */
const PAGES = [
  ['service', '/info/services'],
  ['cases', '/info/cases'],
  ['technology', '/info/principles'],
  ['privacy', '/policy/privacy'],
  ['terms', '/policy/community'],
  ['ai-workspace-policy', '/policy/ai-workspace'],
  ['open-source', '/policy/open-source'],
];
const USER_AGENTS = {
  mobile: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36',
  desktop: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
};
const SEO_BOT_USER_AGENT = 'Googlebot/2.1 (+http://www.google.com/bot.html)';
const results = [];
const record = (scope, check, ok, detail = '') => results.push({ scope, check, status: ok ? 'PASS' : 'FAIL', detail: String(detail).slice(0, 300) });

function inspectRouteList() {
  const expectedNames = PAGES.flatMap(([, legacy]) => {
    const suffix = legacy.slice(1).replaceAll('/', '.');
    return [
      `raonslab-product.compatibility.${suffix}`,
      `raonslab-product.compatibility.localized.${suffix}`,
    ];
  });
  const command = spawnSync(
    PHP_BINARY,
    ['artisan', 'route:list', '--name=raonslab-product.compatibility', '--json'],
    { cwd: REPO_ROOT, encoding: 'utf8', timeout: 30000 },
  );
  let routes = [];
  let parseError = '';

  if (command.error) {
    parseError = command.error.message;
  } else if (command.status !== 0) {
    parseError = `exit=${command.status} stderr=${command.stderr.trim() || 'none'}`;
  } else {
    try {
      routes = JSON.parse(command.stdout);
      if (!Array.isArray(routes)) parseError = 'route:list JSON root is not an array';
    } catch (error) {
      parseError = `invalid route:list JSON: ${error instanceof Error ? error.message : String(error)}`;
    }
  }

  const names = Array.isArray(routes) ? routes.map((route) => route.name).filter(Boolean) : [];
  const missing = expectedNames.filter((name) => !names.includes(name));
  const ok = parseError === '' && names.length === expectedNames.length && missing.length === 0;
  record(
    'preflight/routes',
    '14 compatibility routes are active',
    ok,
    parseError || `count=${names.length} missing=${missing.join(',') || 'none'}`,
  );

  return ok;
}

function inspectRedirectLocation(scope, location, expectedPathAndSearch, targetCheck) {
  let resolved = null;
  let parseError = '';

  if (!location) {
    parseError = 'missing Location header';
  } else {
    try {
      resolved = new URL(location, BASE);
    } catch (error) {
      parseError = `invalid Location header: ${error instanceof Error ? error.message : String(error)}`;
    }
  }

  const expectedOrigin = new URL(BASE).origin;
  record(
    scope,
    'location remains same-origin',
    resolved?.origin === expectedOrigin,
    parseError || `actual=${resolved?.origin} expected=${expectedOrigin}`,
  );
  record(
    scope,
    targetCheck,
    resolved !== null && `${resolved.pathname}${resolved.search}` === expectedPathAndSearch,
    parseError || `actual=${resolved?.pathname}${resolved?.search} expected=${expectedPathAndSearch}`,
  );

  return resolved?.origin === expectedOrigin
    && `${resolved.pathname}${resolved.search}` === expectedPathAndSearch;
}

async function inspectLegacyRedirect(api, slug, legacy, localized = false) {
  const scope = localized ? `redirect/en/${slug}` : `redirect/${slug}`;
  const requestPath = localized
    ? `/en${legacy}?from=smoke&locale=ko`
    : `${legacy}?from=smoke`;
  const expected = localized
    ? `/page/${slug}?from=smoke&locale=en`
    : `/page/${slug}?from=smoke`;
  const response = await api.get(requestPath, { maxRedirects: 0 });
  const statusOk = response.status() === 301;
  record(scope, localized ? 'localized legacy URL is 301' : 'legacy URL is 301', statusOk, response.status());
  const locationOk = inspectRedirectLocation(
    scope,
    response.headers().location,
    expected,
    localized ? 'localized canonical uses locale query' : 'location preserves query and targets canonical',
  );

  return statusOk && locationOk;
}

async function inspectBotSeo(api, slug) {
  const path = `/page/${slug}`;
  const response = await api.get(path, { headers: { 'User-Agent': SEO_BOT_USER_AGENT } });
  const status = response.status();
  const headers = response.headers();
  const contentType = headers['content-type'] || '';
  const seoCache = headers['x-seo-cache'] || '';
  const body = await response.text();
  const canonicalTag = (body.match(/<link\b[^>]*>/gi) || [])
    .find((tag) => /\brel\s*=\s*["']canonical["']/i.test(tag));
  const href = canonicalTag?.match(/\bhref\s*=\s*(["'])(.*?)\1/i)?.[2] || '';
  let canonical = null;
  let parseError = '';

  if (!href) {
    parseError = 'missing canonical link in bot/server HTML';
  } else {
    try {
      canonical = new URL(href, BASE);
    } catch (error) {
      parseError = `invalid canonical URL: ${error instanceof Error ? error.message : String(error)}`;
    }
  }

  const scope = `seo/${slug}`;
  const expectedOrigin = new URL(BASE).origin;
  const diagnostic = `status=${status} content-type=${contentType || 'none'} x-seo-cache=${seoCache || 'none'}`;
  record(scope, 'bot/server SEO response is HTML 200', status === 200 && /^text\/html(?:;|$)/i.test(contentType), diagnostic);
  record(scope, 'bot request used authoritative SEO renderer', /^(HIT|MISS)$/.test(seoCache), diagnostic);
  record(scope, 'canonical is present and same-origin', canonical?.origin === expectedOrigin, parseError || `actual=${canonical?.origin} expected=${expectedOrigin}`);
  record(scope, 'canonical targets exact native URL', canonical !== null && `${canonical.pathname}${canonical.search}` === path, parseError || `actual=${canonical?.pathname}${canonical?.search} expected=${path}`);
}

async function inspectNativePage(page, viewport, slug, locale, errors) {
  errors.length = 0;
  const path = `/page/${slug}`;
  const localeQuery = locale === 'ko' ? '' : `?locale=${locale}`;
  const desktop = viewport.width >= 1024;
  const response = await page.goto(`${BASE}${path}${localeQuery}`, { waitUntil: 'networkidle', timeout: 30000 });
  // networkidle 뒤에는 레이아웃이 이미 렌더됐어야 한다. 짧고 고정된 대기만 두고 없으면 FAIL 로 기록한다.
  const presented = await page.waitForSelector('.rh-native-breadcrumb', { timeout: PRESENTATION_WAIT_MS }).then(() => true, () => false);
  record(`${locale}/${viewport.width}/${slug}`, 'RAON document presentation is applied', presented, presented ? '' : 'no .rh-native-breadcrumb');
  if (!presented) return;
  const state = await page.evaluate(({ expectedSlug, groups, sampleSource }) => {
    const visible = (node) => Boolean(node) && getComputedStyle(node).display !== 'none' && node.getBoundingClientRect().height > 0;
    const breadcrumb = document.querySelector('.rh-native-breadcrumb');
    const card = breadcrumb?.parentElement;
    const content = document.querySelector('#page_html_content');
    const side = document.querySelector('.rh-native-side');
    const toggle = document.querySelector('[data-rh-docnav-toggle]');
    const panel = document.getElementById('rh-docnav-panel');
    const contentRect = content?.getBoundingClientRect();
    const sideRect = side?.getBoundingClientRect();
    const toggleRect = toggle?.getBoundingClientRect();
    // Footer 링크는 href 없는 Button(navigate) 이다. 문구는 DOM, 목적지는 렌더된 Footer 의 linkGroups prop 에서 읽는다.
    const footer = document.querySelector('footer');
    const fiberKey = footer && Object.keys(footer).find((key) => key.startsWith('__reactFiber'));
    let fiber = fiberKey ? footer[fiberKey] : null;
    let renderedGroups = null;
    for (let depth = 0; fiber && depth < 20 && !renderedGroups; depth += 1) {
      if (fiber.memoizedProps && Array.isArray(fiber.memoizedProps.linkGroups)) renderedGroups = fiber.memoizedProps.linkGroups;
      fiber = fiber.return;
    }
    const footerGroups = [...document.querySelectorAll('footer h4')].map((heading) => {
      const rendered = renderedGroups?.find((group) => group.title === heading.textContent.trim());
      const texts = [...(heading.parentElement?.querySelectorAll('a, button') ?? [])].map((node) => node.textContent.trim()).filter(Boolean);
      return {
        title: heading.textContent.trim(),
        links: texts.map((text, index) => ({ text, href: rendered?.links?.[index]?.label === text ? rendered.links[index].href : null })),
      };
    });
    const listMarkers = [...(content?.querySelectorAll('ul[class*="rh-"], ol[class*="rh-"]') ?? [])]
      .map((list) => getComputedStyle(list).listStyleType)
      .filter((type) => type !== 'none');
    const dateLines = card ? [...card.querySelectorAll(':scope > p')].filter(visible).length : -1;
    const bodyText = content?.textContent ?? '';
    const raw = document.body.innerText;
    return {
      title: document.querySelector('h1')?.textContent?.trim() || '',
      contentLength: bodyText.trim().length,
      hardcodedBodyPresent: Boolean(document.querySelector('.rh-doc[data-rh-page]')),
      humanAppDom: !navigator.userAgent.includes('HeadlessChrome') && Boolean(document.querySelector('#page_content_card')),
      horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
      presentationAnchorApplied: Boolean(card && card.querySelector(':scope > .rh-native-breadcrumb') === breadcrumb),
      cardDisplay: card ? getComputedStyle(card).display : '',
      contentWrapApplied: content ? getComputedStyle(content).overflowWrap === 'break-word' : false,
      sideVisible: visible(side),
      sidePosition: side ? getComputedStyle(side).position : '',
      sideRightOfContent: Boolean(sideRect && contentRect && sideRect.left >= contentRect.right - 1),
      sideAfterContentInDom: Boolean(side && content && (content.compareDocumentPosition(side) & Node.DOCUMENT_POSITION_FOLLOWING)),
      sideCurrent: [...(side?.querySelectorAll('[aria-current="page"]') ?? [])].map((node) => node.getAttribute('href')),
      sideHrefs: [...(side?.querySelectorAll('a') ?? [])].map((node) => node.getAttribute('href')),
      toggleVisible: visible(toggle),
      toggleHeight: Math.round(toggleRect?.height ?? 0),
      toggleExpanded: toggle?.getAttribute('aria-expanded'),
      panelHidden: panel ? panel.hidden : null,
      panelCurrent: [...(panel?.querySelectorAll('[aria-current="page"]') ?? [])].map((node) => node.getAttribute('href')),
      panelHrefs: [...(panel?.querySelectorAll('a') ?? [])].map((node) => node.getAttribute('href')),
      gapToContent: toggleRect && contentRect ? Math.round(contentRect.top - toggleRect.bottom) : null,
      gnavHrefs: {
        info: [...document.querySelectorAll('#rh-gnav-info-panel a')].map((node) => node.getAttribute('href')),
        policy: [...document.querySelectorAll('#rh-gnav-policy-panel a')].map((node) => node.getAttribute('href')),
      },
      groupCurrent: [...document.querySelectorAll('.rh-gnav-trigger[data-current="true"]')].map((node) => node.dataset.rhMenuTrigger),
      footerGroups,
      poweredBy: [...document.querySelectorAll('footer *')].some((node) => node.childElementCount === 0 && /Powered by/.test(node.textContent)),
      currencySwitcherVisible: [...document.querySelectorAll('[data-testid="currency-switcher"]')].some(visible),
      listMarkers,
      dateLines,
      untranslatedKey: /\$t:|raonslab-product\.(nav|native_page|footer)\./.test(raw),
      sampleText: new RegExp(sampleSource).test(bodyText) ? bodyText.match(new RegExp(sampleSource))[0] : '',
      links: [...document.querySelectorAll('.rh-gnav a, footer a')].map((node) => node.getAttribute('href')).filter(Boolean),
      expectedGroup: Object.keys(groups).find((key) => groups[key].includes(expectedSlug)),
    };
  }, { expectedSlug: slug, groups: DOC_GROUPS, sampleSource: SAMPLE_TEXT.source });

  const scope = `${locale}/${viewport.width}/${slug}`;
  const expected = `/page/${slug}`;
  const allDocs = DOC_SLUGS.map((docSlug) => `/page/${docSlug}`);
  record(scope, 'HTTP 200', response?.status() === 200, response?.status());
  record(scope, 'normal browser UA renders human app DOM', state.humanAppDom);
  record(scope, 'native title/content render', state.title.length > 0 && state.contentLength > 40, `${state.title}:${state.contentLength}`);
  record(scope, 'single native presentation', !state.hardcodedBodyPresent, 'legacy rh-doc shell absent');
  record(scope, 'breadcrumb anchors RAON presentation grid', state.presentationAnchorApplied && state.cardDisplay === 'grid', state.cardDisplay);
  record(scope, 'native content wrapping survives responsive props', state.contentWrapApplied);
  record(scope, 'no horizontal overflow', !state.horizontalOverflow);
  record(scope, 'publication date line hidden', state.dateLines === 0, state.dateLines);
  record(scope, 'content lists have a single marker', state.listMarkers.length === 0, state.listMarkers.join(','));
  record(scope, 'no untranslated taxonomy keys', !state.untranslatedKey);
  record(scope, 'top nav group reflects current document', state.groupCurrent.length === 1 && state.groupCurrent[0] === state.expectedGroup, state.groupCurrent.join(','));
  record(scope, 'top nav dropdowns follow taxonomy', JSON.stringify(state.gnavHrefs) === JSON.stringify({
    info: DOC_GROUPS.info.map((docSlug) => `/page/${docSlug}`),
    policy: DOC_GROUPS.policy.map((docSlug) => `/page/${docSlug}`),
  }), JSON.stringify(state.gnavHrefs));

  if (desktop) {
    record(scope, 'desktop document menu is visible and sticky', state.sideVisible && state.sidePosition === 'sticky', state.sidePosition);
    record(scope, 'desktop document menu is right of content', state.sideRightOfContent);
    record(scope, 'document menu follows content in DOM/tab order', state.sideAfterContentInDom);
    record(scope, 'desktop document menu lists 11 documents in order', JSON.stringify(state.sideHrefs) === JSON.stringify(allDocs), state.sideHrefs.join(','));
    record(scope, 'desktop current link', state.sideCurrent.length === 1 && state.sideCurrent[0] === expected, state.sideCurrent.join(','));
    record(scope, 'mobile disclosure hidden on desktop', !state.toggleVisible);
  } else {
    record(scope, 'mobile document menu toggle is visible and >=44px', state.toggleVisible && state.toggleHeight >= 44, state.toggleHeight);
    record(scope, 'mobile document menu starts closed', state.toggleExpanded === 'false' && state.panelHidden === true, `${state.toggleExpanded}/${state.panelHidden}`);
    record(scope, 'content starts right after the closed menu', state.gapToContent !== null && state.gapToContent >= 0 && state.gapToContent <= 80, state.gapToContent);
    record(scope, 'desktop side menu hidden on mobile', !state.sideVisible);
    record(scope, 'mobile document menu lists 11 documents in order', JSON.stringify(state.panelHrefs) === JSON.stringify(allDocs), state.panelHrefs.join(','));
    record(scope, 'mobile current link', state.panelCurrent.length === 1 && state.panelCurrent[0] === expected, state.panelCurrent.join(','));
  }

  if (locale === 'ko') {
    const info = state.footerGroups.find((group) => group.title === '정보');
    const policy = state.footerGroups.find((group) => group.title === '정책');
    const footerOk = Boolean(info && policy)
      && JSON.stringify(info.links.map((link) => link.href)) === JSON.stringify(DOC_GROUPS.info.map((docSlug) => `/page/${docSlug}`))
      && JSON.stringify(policy.links.map((link) => link.href)) === JSON.stringify(DOC_GROUPS.policy.map((docSlug) => `/page/${docSlug}`))
      && info.links[0].text === 'RAON Agent Factory 소개'
      && policy.links.at(-1).text === '결제·취소·환불 안내'
      && !info.links.some((link) => link.text === '회사소개')
      && !policy.links.some((link) => link.text === '이용약관');
    record(scope, 'footer link text and href follow taxonomy', footerOk, JSON.stringify(state.footerGroups).slice(0, 280));
  }
  record(scope, 'Powered by attribution remains', state.poweredBy);
  record(scope, 'public currency switcher suppressed', !state.currencySwitcherVisible);
  if (CONTENT_GATE) record(scope, 'no sample/placeholder wording in document body', state.sampleText === '', state.sampleText);
  record(scope, 'nav/footer contain no legacy document URL', !state.links.some((href) => /^\/(info|policy)\//.test(href)), state.links.filter((href) => /^\/(info|policy)\//.test(href)).join(','));

  if (!desktop && slug === DOC_SLUGS[0]) await inspectDocnavKeyboard(page, scope);
  record(scope, 'no browser errors', errors.length === 0, errors.join(' | '));
}

async function inspectDocnavKeyboard(page, scope) {
  const toggle = page.locator('[data-rh-docnav-toggle]');
  await toggle.focus();
  await page.keyboard.press('Enter');
  const opened = await page.evaluate(() => ({
    expanded: document.querySelector('[data-rh-docnav-toggle]')?.getAttribute('aria-expanded'),
    hidden: document.getElementById('rh-docnav-panel')?.hidden,
    linkHeights: [...document.querySelectorAll('#rh-docnav-panel a')].map((node) => Math.round(node.getBoundingClientRect().height)),
  }));
  record(scope, 'Enter opens document menu', opened.expanded === 'true' && opened.hidden === false, JSON.stringify(opened).slice(0, 200));
  record(scope, 'document menu links are >=44px tall', opened.linkHeights.length > 0 && opened.linkHeights.every((height) => height >= 44), opened.linkHeights.join(','));
  await page.keyboard.press('Tab');
  await page.keyboard.press('Escape');
  const closed = await page.evaluate(() => ({
    expanded: document.querySelector('[data-rh-docnav-toggle]')?.getAttribute('aria-expanded'),
    hidden: document.getElementById('rh-docnav-panel')?.hidden,
    focusOnToggle: document.activeElement?.hasAttribute('data-rh-docnav-toggle') ?? false,
  }));
  record(scope, 'Escape closes document menu and returns focus', closed.expanded === 'false' && closed.hidden === true && closed.focusOnToggle, JSON.stringify(closed));
}

async function inspectDesktopSticky(page) {
  const scope = 'ko/1280/sticky';
  await page.goto(`${BASE}/page/${DOC_GROUPS.info[1]}`, { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForSelector('.rh-native-side', { timeout: 15000 });
  await page.waitForTimeout(500);
  const result = await page.evaluate(async () => {
    const side = document.querySelector('.rh-native-side');
    const content = document.querySelector('#page_html_content');
    const room = content.getBoundingClientRect().height - side.getBoundingClientRect().height;
    const target = Math.max(0, Math.min(1200, room));
    // SPA 가 로드 직후 스크롤을 되돌릴 수 있으므로 실제로 내려간 뒤에 잰다.
    for (let attempt = 0; attempt < 20 && window.scrollY < target / 2; attempt += 1) {
      window.scrollTo(0, target);
      await new Promise((done) => setTimeout(done, 100));
    }
    return { top: Math.round(side.getBoundingClientRect().top), room: Math.round(room), scrollY: Math.round(window.scrollY) };
  });
  record(
    scope,
    'right document menu stays in view while reading',
    result.room <= 0 || (result.scrollY > 0 && result.top >= 0 && result.top <= 40),
    JSON.stringify(result),
  );
}

async function inspectRegressionRoutes(page) {
  for (const path of ['/', '/board/community', '/board/questions', '/login', '/ai']) {
    const response = await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
    record(`regression${path}`, 'critical route remains reachable', (response?.status() || 500) < 500, response?.status());
  }
}

async function inspectViewport(browser, viewport) {
  const context = await browser.newContext({
    viewport,
    locale: 'ko-KR',
    userAgent: viewport.width <= 412 ? USER_AGENTS.mobile : USER_AGENTS.desktop,
  });
  await context.addInitScript(() => {
    if (!localStorage.getItem('g7_locale')) localStorage.setItem('g7_locale', 'ko');
  });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });

  try {
    for (const slug of DOC_SLUGS) await inspectNativePage(page, viewport, slug, 'ko', errors);
    if (viewport.width === 1280) await inspectDesktopSticky(page);

    if (viewport.width === 390) {
      await page.evaluate(() => localStorage.setItem('g7_locale', 'en'));
      for (const slug of ['about', 'service', 'privacy', 'refund']) {
        await inspectNativePage(page, viewport, slug, 'en', errors);
      }
    }

    if (viewport.width === 1280) await inspectRegressionRoutes(page);
  } finally {
    await context.close();
  }
}

async function inspectConsultationConfig(api) {
  const response = await api.get('/api/modules/raonslab-product/consultations/config');
  const status = response.status();
  const contentType = response.headers()['content-type'] || '';
  const rawBody = await response.text();
  let body = null;
  let parseError = '';

  if (/^application\/json(?:;|$)/i.test(contentType)) {
    try {
      body = JSON.parse(rawBody);
    } catch (error) {
      parseError = error instanceof Error ? error.message : String(error);
    }
  } else {
    parseError = `expected application/json, received ${contentType || 'no content-type'}`;
  }

  const diagnostic = `status=${status} content-type=${contentType || 'none'}`;
  record('regression/consultation', 'config HTTP 200', status === 200, diagnostic);
  record('regression/consultation', 'config response is JSON', parseError === '', parseError || diagnostic);
  record(
    'regression/consultation',
    'public intake remains fail-closed',
    status === 200 && parseError === '' && body?.data?.intake_enabled === false,
    parseError || JSON.stringify({ status, intake_enabled: body?.data?.intake_enabled }),
  );

  return status === 200 && parseError === '' && body?.data?.intake_enabled === false;
}

(async () => {
  const routesActive = inspectRouteList();
  const api = await request.newContext({ baseURL: BASE });
  let browser = null;
  try {
    const consultationReady = await inspectConsultationConfig(api);
    const [firstSlug, firstLegacy] = PAGES[0];
    const firstRedirectActive = await inspectLegacyRedirect(api, firstSlug, firstLegacy);

    if (routesActive && consultationReady && firstRedirectActive) {
      for (const [slug, legacy] of PAGES) {
        if (slug !== firstSlug) await inspectLegacyRedirect(api, slug, legacy);
        await inspectLegacyRedirect(api, slug, legacy, true);
      }
      for (const slug of DOC_SLUGS) await inspectBotSeo(api, slug);

      browser = await chromium.launch({ headless: true });
      for (const viewport of VIEWPORTS) await inspectViewport(browser, viewport);
    }
  } finally {
    await api.dispose();
    if (browser) await browser.close();
  }

  console.table(results);
  const failed = results.filter((result) => result.status === 'FAIL');
  console.log(JSON.stringify({ pass: results.length - failed.length, fail: failed.length, failed }, null, 2));
  process.exitCode = failed.length ? 1 : 0;
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
