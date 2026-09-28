#!/usr/bin/env node
/** Focused runtime smoke for RAON native sirsoft-page documents. */
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
  const response = await page.goto(`${BASE}${path}${localeQuery}`, { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForSelector('.rh-native-breadcrumb', { timeout: 15000 });
  const state = await page.evaluate((expectedSlug) => {
    const current = [...document.querySelectorAll('.rh-native-side [aria-current="page"]')]
      .map((node) => node.getAttribute('href'));
    const breadcrumb = document.querySelector('.rh-native-breadcrumb');
    const presentationHost = breadcrumb?.parentElement;
    const content = document.querySelector('#page_html_content');
    const hostAfter = presentationHost ? getComputedStyle(presentationHost, '::after') : null;
    const contentStyle = content ? getComputedStyle(content) : null;
    return {
      title: document.querySelector('h1')?.textContent?.trim() || '',
      contentLength: document.querySelector('#page_html_content')?.textContent?.trim().length || 0,
      hardcodedBodyPresent: Boolean(document.querySelector('.rh-doc[data-rh-page]')),
      humanAppDom: !navigator.userAgent.includes('HeadlessChrome') && Boolean(document.querySelector('#page_content_card')),
      horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
      current,
      sidePosition: getComputedStyle(document.querySelector('.rh-native-side')).position,
      presentationAnchorApplied: Boolean(
        breadcrumb && presentationHost
        && presentationHost.querySelector(':scope > .rh-native-breadcrumb') === breadcrumb
      ),
      clearfixApplied: hostAfter?.display === 'block' && hostAfter?.clear === 'both',
      contentWrapApplied: contentStyle?.overflowWrap === 'break-word',
      links: [...document.querySelectorAll('.rh-gnav a, footer a')].map((node) => node.getAttribute('href')).filter(Boolean),
      expected: `/page/${expectedSlug}`,
    };
  }, slug);
  const scope = `${locale}/${viewport.width}/${slug}`;
  record(scope, 'HTTP 200', response?.status() === 200, response?.status());
  record(scope, 'normal browser UA renders human app DOM', state.humanAppDom);
  record(scope, 'native title/content render', state.title.length > 0 && state.contentLength > 100, `${state.title}:${state.contentLength}`);
  record(scope, 'single native presentation', !state.hardcodedBodyPresent, 'legacy rh-doc shell absent');
  record(scope, 'breadcrumb anchors RAON presentation DOM', state.presentationAnchorApplied);
  record(scope, 'presentation clearfix survives responsive props', state.clearfixApplied);
  record(scope, 'native content wrapping survives responsive props', state.contentWrapApplied);
  record(scope, 'no horizontal overflow', !state.horizontalOverflow);
  record(scope, 'side current link', state.current.includes(`/page/${slug}`), state.current.join(','));
  record(scope, 'responsive side navigation', viewport.width <= 412 ? state.sidePosition === 'static' : state.sidePosition === 'sticky', state.sidePosition);
  record(scope, 'nav/footer contain no legacy document URL', !state.links.some((href) => /^\/(info|policy)\//.test(href)), state.links.filter((href) => /^\/(info|policy)\//.test(href)).join(','));
  record(scope, 'no browser errors', errors.length === 0, errors.join(' | '));
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
    for (const [slug] of PAGES) await inspectNativePage(page, viewport, slug, 'ko', errors);

    if (viewport.width === 390) {
      await page.evaluate(() => localStorage.setItem('g7_locale', 'en'));
      for (const slug of ['service', 'privacy', 'terms']) {
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
      for (const [slug] of PAGES) await inspectBotSeo(api, slug);

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
