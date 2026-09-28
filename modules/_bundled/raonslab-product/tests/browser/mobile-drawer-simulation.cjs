#!/usr/bin/env node
/**
 * 모바일 드로어 문서 섹션 오프라인 시뮬레이션(브라우저·서버·DB 변경 없음).
 *
 * 실행 중인 런타임이 실제로 내려준 병합 layout 응답(`page/show` 는 `_user_base` 를 상속해 한 응답으로 온다)에
 * 이 소스의 overlay 를 설치 계약대로 다시 적용하고(overlay-simulation.cjs 의 patchMergedLayout), 드로어
 * 부분을 jsdom DOM 으로 그린 뒤 빌드된 dist/css/module.css 의 드로어 규칙을 적용해 보이는 섹션을 판정한다.
 *
 * 사용:
 *   G7_BASE_URL=http://127.0.0.1:18770 node tests/browser/mobile-drawer-simulation.cjs
 *   LAYOUT_FILE=/tmp/page-show.json node tests/browser/mobile-drawer-simulation.cjs   (저장해 둔 응답)
 */
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { JSDOM } = require('jsdom');
const { patchMergedLayout, persistedModuleOverlays } = require('./overlay-simulation.cjs');

const MODULE_ROOT = resolve(__dirname, '../..');
const TAG = { Icon: 'i', Avatar: 'span', SlotContainer: 'div' };
const SECTION = '[data-rh-mobile-drawer-docs]';

function find(value, id) {
  if (Array.isArray(value)) {
    for (const child of value) {
      const hit = find(child, id);
      if (hit) return hit;
    }
    return null;
  }
  if (!value || typeof value !== 'object') return null;
  if (value.id === id) return value;
  for (const child of Object.values(value)) {
    const hit = find(child, id);
    if (hit) return hit;
  }
  return null;
}

/** 드로어에서 쓰이는 단순 `if` 표현식만 평가한다. 해석하지 못하면 렌더한다고 본다. */
function visible(node, scope) {
  if (typeof node.if !== 'string') return true;
  const expression = node.if.replace(/^\{\{|\}\}$/g, '');
  try {
    return Boolean(new Function('_global', '$locales', `return (${expression});`)(scope._global, scope.$locales));
  } catch {
    return true;
  }
}

function render(document, node, scope) {
  if (!node || typeof node !== 'object' || !visible(node, scope)) return null;
  const element = document.createElement(TAG[node.name] ?? String(node.name ?? 'div').toLowerCase());
  const props = node.props ?? {};
  const id = props.id ?? node.id;
  if (id) element.id = id;
  for (const [key, value] of Object.entries(props)) {
    if (typeof value !== 'string' && typeof value !== 'boolean') continue;
    if (key === 'className') element.className = String(value);
    else if (key === 'href' || key.startsWith('data-') || key.startsWith('aria-')) element.setAttribute(key, String(value));
  }
  if (typeof node.text === 'string') element.textContent = node.text;
  for (const child of node.children ?? []) {
    const rendered = render(document, child, scope);
    if (rendered) element.append(rendered);
  }
  return element;
}

/** dist CSS 에서 드로어 직계 자식을 숨기는 규칙의 selector 목록. */
function drawerHideSelectors(css) {
  const selectors = [];
  for (const match of css.matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
    if (!match[1].includes('#mobile_nav_drawer') || !/display:\s*none/.test(match[2])) continue;
    selectors.push(...match[1].split(',').map((selector) => selector.trim()).filter(Boolean));
  }
  return selectors;
}

function sectionKind(element) {
  if (element.matches(SECTION)) return 'raon-docs';
  const text = element.textContent ?? '';
  if (element.id === 'mobile_drawer_prefs') return 'prefs';
  if (text.includes('$t:user.footer.info') || text.includes('$t:user.footer.policy')) return 'stock-info-policy';
  if (text.includes('$t:user.nav.shop')) return 'stock-shop';
  if (text.includes('$t:user.nav.boards')) return 'boards';
  if (text.includes('$t:user.nav.home')) return 'home-links';
  if (text.includes('$t:auth.login')) return 'guest';
  if (element.querySelector('input')) return 'search';
  if (text.includes('$t:user.menu')) return 'header';
  if (text.includes('{{_global.currentUser?.name}}')) return 'member';
  return 'other';
}

async function simulate(body, { css, scope }) {
  const overlays = await persistedModuleOverlays();
  const merged = JSON.parse(patchMergedLayout(body, overlays));
  const components = merged.data.components;
  const drawerDef = find(components, 'mobile_nav_drawer');
  if (!drawerDef) throw new Error('simulation: mobile_nav_drawer not found in served layout');
  const { document } = new JSDOM('<!doctype html><body></body>').window;
  const drawer = render(document, drawerDef, scope);
  document.body.append(drawer);
  const selectors = drawerHideSelectors(css);
  const hidden = new Set(selectors.flatMap((selector) => [...document.querySelectorAll(selector)]));
  const sections = [...drawer.children].map((element) => ({ kind: sectionKind(element), hidden: hidden.has(element) }));
  const docs = [...document.querySelectorAll(`${SECTION} a.rh-drawer-link`)];
  const currency = find(components, 'header_currency_inject_anchor');
  return {
    selectors,
    sections,
    sectionCount: document.querySelectorAll(SECTION).length,
    links: docs.map((link) => link.getAttribute('href')),
    lists: [...document.querySelectorAll(`${SECTION} ul`)].map((list) => list.getAttribute('aria-labelledby')),
    gnavPresent: Boolean(find(components, 'rh_gnav_root')),
    footerLinkGroups: find(components, 'footer')?.props?.linkGroups?.length ?? 0,
    currencySuppressed: currency?.props?.['data-rh-commerce-suppressed'] === 'currency' && !(currency.children?.length),
  };
}

async function main() {
  const base = (process.env.G7_BASE_URL || 'http://127.0.0.1:18770').replace(/\/$/, '');
  const body = process.env.LAYOUT_FILE
    ? readFileSync(process.env.LAYOUT_FILE, 'utf8')
    : await (await fetch(`${base}/api/layouts/sirsoft-basic/page/show.json`)).text();
  const css = readFileSync(resolve(MODULE_ROOT, 'dist/css/module.css'), 'utf8');
  const taxonomy = JSON.parse(readFileSync(resolve(MODULE_ROOT, 'resources/taxonomy/info-policy.json'), 'utf8'));
  const expected = taxonomy.groups.flatMap((group) => group.items.map((item) => `/page/${item.slug}`));
  let failures = 0;
  const check = (label, ok, detail = '') => {
    if (!ok) failures += 1;
    console.log(`${ok ? 'PASS' : 'FAIL'} ${label}${detail ? ` — ${detail}` : ''}`);
  };

  for (const [label, scope] of [
    ['guest', { _global: {}, $locales: ['ko', 'en'] }],
    ['member', { _global: { currentUser: { uuid: 'u', name: 'n', email: 'e' } }, $locales: ['ko', 'en'] }],
  ]) {
    const result = await simulate(body, { css, scope });
    const visibleKinds = result.sections.filter((section) => !section.hidden).map((section) => section.kind);
    const hiddenKinds = result.sections.filter((section) => section.hidden).map((section) => section.kind);
    check(`${label}: exactly one RAON drawer section`, result.sectionCount === 1, String(result.sectionCount));
    check(`${label}: 11 taxonomy links in order`, JSON.stringify(result.links) === JSON.stringify(expected), result.links.join(' '));
    check(`${label}: grouped Information/Policy lists`, JSON.stringify(result.lists) === JSON.stringify(['rh-drawer-docs-info-label', 'rh-drawer-docs-policy-label']));
    check(`${label}: only stock shop + info/policy hidden`, JSON.stringify(hiddenKinds) === JSON.stringify(['stock-shop', 'stock-info-policy']), hiddenKinds.join(','));
    check(`${label}: RAON section is last and visible`, visibleKinds.at(-1) === 'raon-docs', visibleKinds.join(','));
    for (const kind of ['prefs', 'boards', 'home-links', label === 'guest' ? 'guest' : 'member']) {
      check(`${label}: ${kind} section preserved`, visibleKinds.includes(kind));
    }
    check(`${label}: desktop nav still injected`, result.gnavPresent);
    check(`${label}: footer linkGroups still injected`, result.footerLinkGroups === 3, String(result.footerLinkGroups));
    check(`${label}: currency selector remains suppressed`, result.currencySuppressed);
  }
  console.log(`${failures === 0 ? 'OK' : 'FAILED'} mobile drawer simulation (${failures} failures)`);
  if (failures > 0) process.exit(1);
}

if (require.main === module) {
  main().catch((error) => {
    console.error(error);
    process.exit(1);
  });
}

module.exports = { simulate, drawerHideSelectors, sectionKind };
