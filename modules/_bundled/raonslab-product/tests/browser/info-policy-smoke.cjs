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
const results = [];
const record = (scope, check, ok, detail = '') => results.push({ scope, check, status: ok ? 'PASS' : 'FAIL', detail: String(detail).slice(0, 300) });

async function inspectPage(browser, viewport, slug, locale = 'ko') {
  const context = await browser.newContext({ viewport, locale: locale === 'ko' ? 'ko-KR' : 'en-US' });
  await context.addInitScript((value) => localStorage.setItem('g7_locale', value), locale);
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });

  const path = `${locale === 'ko' ? '' : '/en'}/page/${slug}`;
  const response = await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForSelector('.rh-native-breadcrumb', { timeout: 15000 });
  const state = await page.evaluate((expectedSlug) => {
    const canonical = document.querySelector('link[rel="canonical"]')?.getAttribute('href') || '';
    const current = [...document.querySelectorAll('.rh-native-side [aria-current="page"]')]
      .map((node) => node.getAttribute('href'));
    return {
      title: document.querySelector('h1')?.textContent?.trim() || '',
      contentLength: document.querySelector('.rh-native-content')?.textContent?.trim().length || 0,
      hardcodedBodyPresent: Boolean(document.querySelector('.rh-doc[data-rh-page]')),
      horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
      canonical,
      current,
      sidePosition: getComputedStyle(document.querySelector('.rh-native-side')).position,
      links: [...document.querySelectorAll('.rh-gnav a, footer a')].map((node) => node.getAttribute('href')).filter(Boolean),
      expected: `/page/${expectedSlug}`,
    };
  }, slug);
  const scope = `${locale}/${viewport.width}/${slug}`;
  record(scope, 'HTTP 200', response?.status() === 200, response?.status());
  record(scope, 'native title/content render', state.title.length > 0 && state.contentLength > 100, `${state.title}:${state.contentLength}`);
  record(scope, 'single native presentation', !state.hardcodedBodyPresent, 'legacy rh-doc shell absent');
  record(scope, 'no horizontal overflow', !state.horizontalOverflow);
  record(scope, 'canonical native URL', state.canonical.endsWith(path), state.canonical);
  record(scope, 'side current link', state.current.includes(`/page/${slug}`), state.current.join(','));
  record(scope, 'responsive side navigation', viewport.width <= 412 ? state.sidePosition === 'static' : state.sidePosition === 'sticky', state.sidePosition);
  record(scope, 'nav/footer contain no legacy document URL', !state.links.some((href) => /^\/(info|policy)\//.test(href)), state.links.filter((href) => /^\/(info|policy)\//.test(href)).join(','));
  record(scope, 'no browser errors', errors.length === 0, errors.join(' | '));
  await context.close();
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    const api = await request.newContext({ baseURL: BASE });
    for (const [slug, legacy] of PAGES) {
      const response = await api.get(`${legacy}?from=smoke`, { maxRedirects: 0 });
      record(`redirect/${slug}`, 'legacy URL is 301', response.status() === 301, response.status());
      record(`redirect/${slug}`, 'location preserves query and targets canonical', response.headers().location === `/page/${slug}?from=smoke`, response.headers().location);

      const localized = await api.get(`/en${legacy}`, { maxRedirects: 0 });
      record(`redirect/en/${slug}`, 'localized legacy URL is 301', localized.status() === 301, localized.status());
      record(`redirect/en/${slug}`, 'localized canonical target', localized.headers().location === `/en/page/${slug}`, localized.headers().location);
    }
    await api.dispose();

    for (const viewport of VIEWPORTS) {
      for (const [slug] of PAGES) await inspectPage(browser, viewport, slug, 'ko');
    }
    for (const slug of ['service', 'privacy', 'terms']) await inspectPage(browser, VIEWPORTS[1], slug, 'en');

    const regression = await browser.newPage();
    for (const path of ['/', '/board/community', '/board/questions', '/login', '/ai']) {
      const response = await regression.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
      record(`regression${path}`, 'critical route remains reachable', (response?.status() || 500) < 500, response?.status());
    }
    const intake = await regression.evaluate(async () => {
      const response = await fetch('/api/modules/raonslab-product/consultations/config');
      const body = await response.json();
      return { status: response.status, enabled: body?.data?.enabled };
    });
    record('regression/consultation', 'public intake remains fail-closed', intake.status === 200 && intake.enabled === false, JSON.stringify(intake));
    await regression.close();
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
