#!/usr/bin/env node
/** Focused runtime smoke for RAON native sirsoft-page documents. */
const { chromium, request } = require('playwright');

const BASE = (process.env.G7_BASE_URL || 'http://127.0.0.1:18770').replace(/\/$/, '');
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
const results = [];
const record = (scope, check, ok, detail = '') => results.push({ scope, check, status: ok ? 'PASS' : 'FAIL', detail: String(detail).slice(0, 300) });

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
}

async function inspectNativePage(page, viewport, slug, locale, errors) {
  errors.length = 0;
  const path = `/page/${slug}`;
  const localeQuery = locale === 'ko' ? '' : `?locale=${locale}`;
  const response = await page.goto(`${BASE}${path}${localeQuery}`, { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForSelector('.rh-native-breadcrumb', { timeout: 15000 });
  const state = await page.evaluate((expectedSlug) => {
    const canonical = document.querySelector('link[rel="canonical"]')?.getAttribute('href') || '';
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
      canonical,
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
  record(scope, 'canonical native URL', state.canonical.endsWith(path), state.canonical);
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
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    const api = await request.newContext({ baseURL: BASE });
    await inspectConsultationConfig(api);

    for (const [slug, legacy] of PAGES) {
      const response = await api.get(`${legacy}?from=smoke`, { maxRedirects: 0 });
      record(`redirect/${slug}`, 'legacy URL is 301', response.status() === 301, response.status());
      inspectRedirectLocation(
        `redirect/${slug}`,
        response.headers().location,
        `/page/${slug}?from=smoke`,
        'location preserves query and targets canonical',
      );

      const localized = await api.get(`/en${legacy}?from=smoke&locale=ko`, { maxRedirects: 0 });
      record(`redirect/en/${slug}`, 'localized legacy URL is 301', localized.status() === 301, localized.status());
      inspectRedirectLocation(
        `redirect/en/${slug}`,
        localized.headers().location,
        `/page/${slug}?from=smoke&locale=en`,
        'localized canonical uses locale query',
      );
    }
    await api.dispose();

    for (const viewport of VIEWPORTS) await inspectViewport(browser, viewport);
  } finally {
    await browser.close();
  }

  console.table(results);
  const failed = results.filter((result) => result.status === 'FAIL');
  console.log(JSON.stringify({ pass: results.length - failed.length, fail: failed.length, failed }, null, 2));
  process.exitCode = failed.length ? 1 : 0;
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
