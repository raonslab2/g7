import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { afterEach, describe, expect, it, vi } from 'vitest';
// @ts-expect-error — 생성 스크립트는 빌드 대상이 아닌 Node ESM 이다.
import {
  applyProductNav,
  buildMobileDrawerInjection,
  buildNativePage,
  CURRENCY_SUPPRESSION_INJECTION,
  ECOMMERCE_CURRENCY_PRIORITY,
  extensionManifests,
  generated,
  MOBILE_DRAWER_SECTION_ID,
  MOBILE_DRAWER_TARGET,
  persistedOverlays,
  serialize,
  taxonomySlugs,
  USER_BASE_OVERLAY_PRIORITY,
} from '../../scripts/taxonomy.mjs';
import taxonomy from '../taxonomy/info-policy.json';
import { closeDocnav, installProductNav, normalizePath, productPageGroup, setDocnavOpen, syncProductNav } from './productNav';

const root = resolve(__dirname, '..');
const readText = (path: string) => readFileSync(path, 'utf8');
const readJson = (path: string) => JSON.parse(readText(path));
const nav = readJson(resolve(root, 'extensions/product-nav.json'));
const nativePage = readJson(resolve(root, 'extensions/native-page.json'));
const ko = readJson(resolve(root, 'lang/ko.json'));
const en = readJson(resolve(root, 'lang/en.json'));
const moduleManifest = readJson(resolve(root, '../module.json'));
const componentManifest = readJson(resolve(root, '../components.json'));
const composerManifest = readJson(resolve(root, '../composer.json'));
const packageManifest = readJson(resolve(root, '../package.json'));
const compatibilityRoutes = readText(resolve(root, '../src/routes/compatibility.php'));
const redirectController = readText(resolve(root, '../src/Http/Controllers/LegacyPageRedirectController.php'));
const mainCss = readText(resolve(root, 'css/main.css'));
const indexTs = readText(resolve(root, 'js/index.ts'));
const browserSmoke = readText(resolve(root, '../tests/browser/info-policy-smoke.cjs'));
const viteConfig = readText(resolve(root, '../vite.config.ts'));
const repoRoot = resolve(root, '../../../..');
const stockUserBase = readJson(resolve(repoRoot, 'templates/_bundled/sirsoft-basic/layouts/_user_base.json'));

type Node = {
  id?: string;
  name?: string;
  if?: string;
  text?: string;
  props?: Record<string, unknown>;
  children?: Node[];
  actions?: Array<{ handler: string; params?: { path?: string } }>;
};

function walk(value: unknown, visit: (node: Node) => void): void {
  if (Array.isArray(value)) {
    value.forEach((item) => walk(item, visit));
    return;
  }
  if (!value || typeof value !== 'object') return;
  const node = value as Node;
  if (node.name || node.props || node.actions) visit(node);
  Object.values(value as Record<string, unknown>).forEach((item) => walk(item, visit));
}

const collect = (value: unknown): Node[] => {
  const nodes: Node[] = [];
  walk(value, (node) => nodes.push(node));
  return nodes;
};

const findById = (value: unknown, id: string): Node | undefined => collect(value).find((node) => node.id === id);
const hrefs = (value: unknown): string[] => collect(value)
  .filter((node) => node.name === 'A')
  .map((node) => String(node.props?.href ?? ''));

function flatten(obj: Record<string, unknown>, prefix = ''): Record<string, string> {
  return Object.entries(obj).reduce<Record<string, string>>((result, [key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key;
    if (value && typeof value === 'object') Object.assign(result, flatten(value as Record<string, unknown>, path));
    else result[path] = String(value);
    return result;
  }, {});
}

const APPROVED = {
  info: ['about', 'service', 'cases', 'technology', 'faq', 'contact'],
  policy: ['privacy', 'terms', 'ai-workspace-policy', 'open-source', 'refund'],
};
const paths = (slugs: string[]) => slugs.map((slug) => `/page/${slug}`);
const ALL_PATHS = paths([...APPROVED.info, ...APPROVED.policy]);
const LEGACY = {
  '/info/services': '/page/service',
  '/info/cases': '/page/cases',
  '/info/principles': '/page/technology',
  '/policy/privacy': '/page/privacy',
  '/policy/community': '/page/terms',
  '/policy/ai-workspace': '/page/ai-workspace-policy',
  '/policy/open-source': '/page/open-source',
};

describe('정보·정책 분류 단일 출처', () => {
  it('승인된 IA 순서(정보 6 · 정책 5)를 그대로 담는다', () => {
    expect(taxonomy.groups.map((group) => group.key)).toEqual(['info', 'policy']);
    expect(taxonomy.groups[0].items.map((item) => item.slug)).toEqual(APPROVED.info);
    expect(taxonomy.groups[1].items.map((item) => item.slug)).toEqual(APPROVED.policy);
    expect(taxonomySlugs(taxonomy)).toHaveLength(11);
  });

  it('커밋된 extension JSON 은 생성기 결과와 바이트 단위로 같다(드리프트 금지)', () => {
    for (const [path, content] of Object.entries(generated() as Record<string, string>)) {
      expect(readText(path), path).toBe(content);
    }
    expect(serialize(buildNativePage(taxonomy))).toBe(readText(resolve(root, 'extensions/native-page.json')));
    expect(applyProductNav(nav, taxonomy)).toEqual(nav);
  });

  it('상위 드롭다운 두 목록이 분류 순서를 따른다', () => {
    expect(hrefs(findById(nav, 'rh_gnav_info_list'))).toEqual(paths(APPROVED.info));
    expect(hrefs(findById(nav, 'rh_gnav_policy_list'))).toEqual(paths(APPROVED.policy));
    for (const node of collect(findById(nav, 'rh_gnav_list')).filter((item) => item.name === 'A' && item.props?.href !== '/#rh-consult')) {
      expect(node.props?.['data-rh-nav-path']).toBe(node.props?.href);
      expect(node.actions?.[0]?.params?.path).toBe(node.props?.href);
    }
  });

  it('product footer linkGroups 가 같은 분류·라벨을 쓰고 커뮤니티 그룹은 유지된다', () => {
    const footer = nav.injections.find((injection: { target_id: string }) => injection.target_id === 'footer');
    const groups = footer.props.linkGroups as Array<{ title: string; links: Array<{ label: string; href: string }> }>;
    expect(groups.map((group) => group.title)).toEqual([
      '$t:raonslab-product.footer.community',
      '$t:raonslab-product.nav.info',
      '$t:raonslab-product.nav.policy',
    ]);
    expect(groups[0].links.map((link) => link.href)).toEqual(['/', '/board/notice', '/board/questions', '/board/community', '/search']);
    expect(groups[1].links.map((link) => link.href)).toEqual(paths(APPROVED.info));
    expect(groups[2].links.map((link) => link.href)).toEqual(paths(APPROVED.policy));
    const dropdownLabels = collect(findById(nav, 'rh_gnav_list'))
      .filter((node) => node.props?.className === 'rh-gnav-link-title')
      .map((node) => node.text);
    expect([...groups[1].links, ...groups[2].links].map((link) => link.label)).toEqual(dropdownLabels);
  });

  it('문서 메뉴(데스크톱 우측·모바일 disclosure)가 11개 문서를 같은 순서로 담는다', () => {
    expect(hrefs(findById(nativePage, 'rh_native_page_side_navigation'))).toEqual(ALL_PATHS);
    expect(hrefs(findById(nativePage, 'rh_native_page_docnav_panel'))).toEqual(ALL_PATHS);
    expect(productPageGroup('/page/about')).toBe('info');
    expect(productPageGroup('/page/refund')).toBe('policy');
  });

  it('모든 라벨·설명 키가 ko/en 에 있고 두 언어의 키 집합이 같다', () => {
    const koFlat = flatten(ko);
    const enFlat = flatten(en);
    expect(Object.keys(enFlat).sort()).toEqual(Object.keys(koFlat).sort());
    const keys = taxonomy.groups.flatMap((group) => [group.label, ...group.items.flatMap((item) => [item.label, item.description])]);
    for (const key of [...keys, 'native_page.menu_toggle', 'native_page.navigation_label', 'native_page.breadcrumb_label']) {
      expect(koFlat[key], key).toBeTruthy();
      expect(enFlat[key], key).toBeTruthy();
    }
    // 그룹 이름과 항목 이름이 겹치지 않는다(en 의 "About" 그룹 ↔ About 항목 혼동 방지)
    expect(enFlat['nav.info']).not.toBe(enFlat['nav.info_about']);
  });
});

describe('page/show overlay 계약', () => {
  const injections = nativePage.injections as Array<{ target_id: string; position: string; components?: Node[]; props?: Record<string, string> }>;
  const condition = `{{['about','service','cases','technology','faq','contact','privacy','terms','ai-workspace-policy','open-source','refund'].includes(page?.data?.slug)}}`;

  it('공식 확장 지점만 쓰고, 우측 메뉴는 DOM 상 본문 뒤(append_child)에 둔다', () => {
    expect(nativePage.target_layout).toBe('page/show');
    expect(injections.map((item) => [item.target_id, item.position])).toEqual([
      ['page_content_card', 'inject_props'],
      ['page_content_card', 'prepend_child'],
      ['page_html_content', 'prepend'],
      ['page_content_card', 'append_child'],
    ]);
    expect(JSON.stringify(nativePage)).not.toContain('"content":');
    expect(injections.some((injection) => injection.target_id === 'page_html_content' && injection.position === 'inject_props')).toBe(false);
  });

  it('breadcrumb·모바일 메뉴·우측 메뉴가 11개 slug 전부에서 같은 조건으로 켜진다', () => {
    expect(injections[1].components?.[0].id).toBe('rh_native_page_breadcrumb');
    expect(injections[2].components?.[0].id).toBe('rh_native_page_docnav');
    expect(injections[3].components?.[0].id).toBe('rh_native_page_side_navigation');
    for (const index of [1, 2, 3]) expect(injections[index].components?.[0].if).toBe(condition);
    expect(injections[0].props?.className).toContain(condition.slice(2, -2));
  });

  it('breadcrumb 은 홈 / 그룹 / 현재 문서 순이고 그룹은 slug 로 판정한다', () => {
    const crumbs = injections[1].components?.[0].children ?? [];
    expect(crumbs.filter((node) => node.props?.className === 'rh-crumb-group').map((node) => node.text)).toEqual([
      '$t:raonslab-product.nav.info',
      '$t:raonslab-product.nav.policy',
    ]);
    expect(crumbs.at(-1)?.props?.['aria-current']).toBe('page');
  });

  it('모바일 문서 메뉴는 기본 닫힘 disclosure 계약을 갖는다', () => {
    const toggle = findById(nativePage, 'rh_native_page_docnav_toggle');
    const panel = findById(nativePage, 'rh_native_page_docnav_panel');
    expect(toggle?.name).toBe('Button');
    expect(toggle?.props).toMatchObject({
      type: 'button',
      'aria-expanded': 'false',
      'aria-controls': 'rh-docnav-panel',
      'data-rh-docnav-toggle': 'true',
    });
    expect(panel?.name).toBe('Nav');
    expect(panel?.props).toMatchObject({ id: 'rh-docnav-panel', hidden: true });
    for (const list of collect(panel).filter((node) => node.name === 'Ul')) {
      expect(String(list.props?.['aria-labelledby'])).toMatch(/^rh-docnav-mobile-(info|policy)-label$/);
    }
  });

  it('CSS 는 우측 grid·sticky·모바일 disclosure·목록 표식·발행일 숨김을 정의한다', () => {
    expect(mainCss).toContain('grid-template-columns: minmax(0, 1fr) 15rem;');
    expect(mainCss).toContain('grid-row: 1 / -1;');
    expect(mainCss).toMatch(/> \.rh-native-side \{\n\s+position: sticky;/);
    expect(mainCss).not.toMatch(/\.rh-native-side \{[^}]*float:/);
    expect(mainCss).not.toMatch(/:has\(> \.rh-native-breadcrumb\) \{[^}]*overflow: hidden/);
    expect(mainCss).toContain('.rh-docnav-panel[hidden] {\n  display: none !important;');
    expect(mainCss).toMatch(/\.rh-docnav-toggle \{[^}]*min-height: 48px;/);
    expect(mainCss).toContain(':is(.rh-doc-list, .rh-flowline, .rh-status-list, .rh-rail, .rh-facts, .rh-side-list)');
    expect(mainCss).toContain('#main_content :has(> .rh-native-breadcrumb) > p {\n  display: none;');
  });
});

describe('overlay 저장 계약(모듈 + target layout 당 1행)', () => {
  const manifests = extensionManifests() as Array<{ file: string; content: { target_layout?: string } }>;

  it('이 모듈의 extension manifest 는 target layout 마다 정확히 하나다', () => {
    const targets = manifests.map((manifest) => manifest.content.target_layout);
    expect(new Set(targets).size, targets.join(',')).toBe(targets.length);
    expect(persistedOverlays(manifests).overwritten).toEqual([]);
    expect(manifests.map((manifest) => manifest.file).sort()).toEqual(['home-product.json', 'native-page.json', 'product-nav.json']);
    expect(existsSync(resolve(root, 'extensions/public-commerce-chrome.json'))).toBe(false);
  });

  it('실패한 0.4.1 후보(같은 _user_base 에 파일 2개)를 덮어쓰기로 판정한다', () => {
    const failedCandidate = [
      { file: 'product-nav.json', content: { target_layout: '_user_base', priority: 30, injections: nav.injections.slice(0, 2) } },
      {
        file: 'public-commerce-chrome.json',
        content: { target_layout: '_user_base', priority: 400, injections: [CURRENCY_SUPPRESSION_INJECTION] },
      },
      { file: 'native-page.json', content: nativePage },
    ];
    const { byTarget, overwritten } = persistedOverlays(failedCandidate);
    expect(overwritten).toEqual([{ target: '_user_base', lost: 'product-nav.json', kept: 'public-commerce-chrome.json' }]);
    // 운영에서 관찰한 결과와 같다: 저장된 _user_base 행에는 통화 교체만 남고 상위 메뉴·footer 가 사라졌다.
    expect(byTarget.get('_user_base').content.injections.map((injection: { target_id: string }) => injection.target_id))
      .toEqual(['header_currency_inject_anchor']);
  });

  it('배포 전 브라우저 시뮬레이션은 같은 저장 계약만 적용하고 중복 target 이면 거부한다', () => {
    const simulation = readText(resolve(root, '../tests/browser/overlay-simulation.cjs'));
    expect(simulation).toContain('persistedOverlays(extensionManifests())');
    expect(simulation).toContain('simulation refused: duplicate target layout');
    expect(simulation).toContain("overlays.get(target)?.content");
  });
});

describe('공개 쇼핑·통화 노출 억제', () => {
  it('단일 _user_base overlay 가 상위 메뉴·footer·통화 교체를 모두 담고 이커머스(320) 뒤에 적용된다', () => {
    expect(nav.target_layout).toBe('_user_base');
    expect(nav.priority).toBe(USER_BASE_OVERLAY_PRIORITY);
    expect(nav.priority).toBeGreaterThan(ECOMMERCE_CURRENCY_PRIORITY);
    expect(nav.injections.map((injection: { target_id: string; position: string }) => [injection.target_id, injection.position])).toEqual([
      ['main_content_area', 'prepend_child'],
      ['footer', 'inject_props'],
      ['mobile_header_left', 'replace'],
      ['desktop_header', 'inject_props'],
      ['header_currency_inject_anchor', 'replace'],
      ['mobile_nav_drawer', 'append_child'],
    ]);
    const currency = nav.injections.find((i: { target_id: string }) => i.target_id === 'header_currency_inject_anchor');
    expect(currency.components).toHaveLength(1);
    expect(currency.components[0].id).toBe('header_currency_inject_anchor');
    expect(currency.components[0].children).toBeUndefined();
    expect(currency).toEqual(CURRENCY_SUPPRESSION_INJECTION);
  });

  it('Powered by 그누보드7 표기는 숨기지 않는다', () => {
    expect(indexTs).not.toContain("startsWith('Powered by')");
    expect(indexTs).not.toContain("querySelectorAll('#footer p')");
    expect(indexTs).toContain('data-testid="nav-shop"');
  });
});

describe('모바일 문서 메뉴 키보드 계약', () => {
  type FakeElement = {
    attrs: Record<string, string>;
    hidden: boolean;
    focused: boolean;
    getAttribute: (name: string) => string | null;
    setAttribute: (name: string, value: string) => void;
    focus: () => void;
    closest: (selector: string) => FakeElement | null;
    matches: (selector: string) => boolean;
  };

  function element(attrs: Record<string, string>): FakeElement {
    const node: FakeElement = {
      attrs,
      hidden: true,
      focused: false,
      getAttribute: (name) => node.attrs[name] ?? null,
      setAttribute: (name, value) => { node.attrs[name] = value; },
      focus: () => { node.focused = true; },
      closest: (selector) => (selector === '[data-rh-docnav-toggle]' && 'data-rh-docnav-toggle' in node.attrs ? node : null),
      matches: () => false,
    };
    return node;
  }

  afterEach(() => vi.unstubAllGlobals());

  function stubDocument(toggle: FakeElement, panel: FakeElement) {
    const listeners: Record<string, (event: unknown) => void> = {};
    vi.stubGlobal('window', { addEventListener: vi.fn(), location: { pathname: '/page/faq' } });
    vi.stubGlobal('document', {
      addEventListener: (type: string, handler: (event: unknown) => void) => { listeners[type] = handler; },
      getElementById: (id: string) => (id === 'rh-docnav-panel' ? panel : null),
      querySelectorAll: (selector: string) => (
        selector === '[data-rh-docnav-toggle][aria-expanded="true"]' && toggle.attrs['aria-expanded'] === 'true' ? [toggle] : []
      ),
    });
    return listeners;
  }

  it('열기·닫기가 aria-expanded 와 패널 hidden 을 함께 맞춘다', () => {
    const toggle = element({ 'aria-controls': 'rh-docnav-panel', 'aria-expanded': 'false', 'data-rh-docnav-toggle': 'true' });
    const panel = element({ id: 'rh-docnav-panel' });
    stubDocument(toggle, panel);

    setDocnavOpen(toggle as unknown as HTMLElement, true);
    expect(toggle.attrs['aria-expanded']).toBe('true');
    expect(panel.hidden).toBe(false);

    closeDocnav();
    expect(toggle.attrs['aria-expanded']).toBe('false');
    expect(panel.hidden).toBe(true);
  });

  it('클릭은 토글하고 Escape 는 닫은 뒤 토글로 포커스를 돌린다', () => {
    const toggle = element({ 'aria-controls': 'rh-docnav-panel', 'aria-expanded': 'false', 'data-rh-docnav-toggle': 'true' });
    const panel = element({ id: 'rh-docnav-panel' });
    const listeners = stubDocument(toggle, panel);
    installProductNav();

    const preventDefault = vi.fn();
    listeners.click({ target: toggle, preventDefault });
    expect(toggle.attrs['aria-expanded']).toBe('true');
    expect(panel.hidden).toBe(false);

    listeners.keydown({ key: 'Escape', target: panel, preventDefault });
    expect(toggle.attrs['aria-expanded']).toBe('false');
    expect(panel.hidden).toBe(true);
    expect(toggle.focused).toBe(true);
    expect(preventDefault).toHaveBeenCalledTimes(2);
  });
});

describe('모바일 드로어 문서 섹션', () => {
  const drawerInjections = nav.injections.filter((injection: { target_id: string }) => injection.target_id === MOBILE_DRAWER_TARGET);
  const section = findById(nav, MOBILE_DRAWER_SECTION_ID) as Node;
  const textOf = (value: unknown) => collect(value).map((node) => node.text ?? '').join(' ');
  const stockDrawer = findById(stockUserBase, 'mobile_nav_drawer') as Node;
  const stockChildren = (stockDrawer?.children ?? []) as Array<Node & { iteration?: unknown }>;

  it('생성기가 드로어 끝에 소유 섹션 하나만 append_child 한다(드리프트 포함)', () => {
    expect(drawerInjections).toHaveLength(1);
    expect(drawerInjections[0]).toEqual(buildMobileDrawerInjection(taxonomy));
    expect(drawerInjections[0].position).toBe('append_child');
    expect(drawerInjections[0].components).toHaveLength(1);
    expect(section.name).toBe('Nav');
    expect(section.props).toMatchObject({ 'aria-label': '$t:raonslab-product.nav.label', 'data-rh-mobile-drawer-docs': 'true' });
    expect(section.if).toBeUndefined();
    expect(collect(nav).filter((node) => node.id === MOBILE_DRAWER_SECTION_ID)).toHaveLength(1);
  });

  it('11개 문서를 정보·정책 순서로 담고 ko/en 라벨 키를 쓴다', () => {
    expect(hrefs(section)).toEqual(ALL_PATHS);
    const labels = collect(section).filter((node) => node.name === 'P');
    expect(labels.map((node) => node.text)).toEqual(['$t:raonslab-product.nav.info', '$t:raonslab-product.nav.policy']);
    const lists = collect(section).filter((node) => node.name === 'Ul');
    expect(lists.map((node) => node.props?.['aria-labelledby'])).toEqual(['rh-drawer-docs-info-label', 'rh-drawer-docs-policy-label']);
    expect(labels.map((node) => node.props?.id)).toEqual(['rh-drawer-docs-info-label', 'rh-drawer-docs-policy-label']);
    expect(hrefs(lists[0])).toEqual(paths(APPROVED.info));
    expect(hrefs(lists[1])).toEqual(paths(APPROVED.policy));
    const koFlat = flatten(ko);
    const enFlat = flatten(en);
    for (const text of collect(section).map((node) => node.text).filter(Boolean)) {
      const key = String(text).replace('$t:raonslab-product.', '');
      expect(koFlat[key], key).toBeTruthy();
      expect(enFlat[key], key).toBeTruthy();
    }
  });

  it('링크는 href·현재 위치 표식 경로를 갖고, 선택 시 드로어를 닫은 뒤 같은 경로로 이동한다', () => {
    const links = collect(section).filter((node) => node.name === 'A') as Array<Node & { actions: Array<{ handler: string; actions: Array<{ handler: string; params: Record<string, unknown> }> }> }>;
    expect(links).toHaveLength(11);
    for (const link of links) {
      expect(link.props?.className).toBe('rh-drawer-link');
      expect(link.props?.['data-rh-nav-path']).toBe(link.props?.href);
      expect(link.actions[0].handler).toBe('sequence');
      expect(link.actions[0].actions).toEqual([
        { handler: 'setState', params: { target: 'global', mobileMenuOpen: false } },
        { handler: 'navigate', params: { path: link.props?.href } },
      ]);
    }
  });

  it('현재 문서 링크에만 aria-current="page" 를 단다', () => {
    const fake = (path: string) => {
      const attrs: Record<string, string> = path === '/page/privacy' ? {} : { 'aria-current': 'page' };
      return {
        dataset: { rhNavPath: path },
        attrs,
        getAttribute: (name: string) => attrs[name] ?? null,
        setAttribute: (name: string, value: string) => { attrs[name] = value; },
        hasAttribute: (name: string) => name in attrs,
        removeAttribute: (name: string) => { delete attrs[name]; },
      };
    };
    const links = ALL_PATHS.map(fake);
    const selectors: string[] = [];
    vi.stubGlobal('window', { location: { pathname: '/page/privacy/' } });
    vi.stubGlobal('document', {
      querySelectorAll: (selector: string) => {
        selectors.push(selector);
        return selector.includes('a.rh-drawer-link[data-rh-nav-path]') ? links : [];
      },
    });
    syncProductNav();
    vi.unstubAllGlobals();
    expect(selectors[0]).toContain('a.rh-drawer-link[data-rh-nav-path]');
    expect(links.filter((link) => link.attrs['aria-current'] === 'page').map((link) => link.dataset.rhNavPath)).toEqual(['/page/privacy']);
  });

  it('번들 sirsoft-basic 드로어의 마지막 두 섹션은 무조건 렌더되는 쇼핑 → 정보/정책이다(아니면 숨김 규칙 무효)', () => {
    expect(stockDrawer, 'sirsoft-basic _user_base 에 mobile_nav_drawer 가 없다').toBeTruthy();
    expect(stockChildren.length).toBeGreaterThanOrEqual(3);
    const [shop, info] = stockChildren.slice(-2);
    expect(textOf(shop)).toContain('$t:user.nav.shop');
    expect(textOf(shop)).not.toContain('$t:user.footer.info');
    expect(textOf(info)).toContain('$t:user.footer.info');
    expect(textOf(info)).toContain('$t:user.footer.policy');
    expect(textOf(info)).not.toContain('$t:user.nav.shop');
    for (const node of [shop, info]) {
      expect(node.if).toBeUndefined();
      expect(node.iteration).toBeUndefined();
      expect(node.name).toBe('Div');
    }
    // 숨기지 않을 섹션(언어·회원·게시판)은 그 앞에 있다
    const preserved = stockChildren.slice(0, -2);
    expect(preserved.some((node) => node.id === 'mobile_drawer_prefs')).toBe(true);
    expect(preserved.some((node) => textOf(node).includes('$t:user.nav.boards'))).toBe(true);
    expect(preserved.some((node) => textOf(node).includes('$t:auth.login'))).toBe(true);
    expect(preserved.some((node) => textOf(node).includes('{{_global.currentUser?.name}}'))).toBe(true);
  });

  it('다른 번들 확장은 드로어에 주입하지 않아 소유 섹션 바로 앞이 템플릿 섹션으로 유지된다', () => {
    const offenders: string[] = [];
    for (const kind of ['modules', 'plugins']) {
      const base = resolve(repoRoot, kind, '_bundled');
      for (const id of readdirSync(base)) {
        const dir = resolve(base, id, 'resources/extensions');
        if (!existsSync(dir)) continue;
        for (const file of readdirSync(dir).filter((name) => name.endsWith('.json'))) {
          if (kind === 'modules' && id === 'raonslab-product') continue;
          if (readText(resolve(dir, file)).includes(`"${MOBILE_DRAWER_TARGET}"`)) offenders.push(`${id}/${file}`);
        }
      }
    }
    expect(offenders).toEqual([]);
  });

  it('CSS 숨김 규칙은 #mobile_nav_drawer 직계 자식 중 소유 섹션 바로 앞 두 칸만 대상으로 한다', () => {
    const rules = [...mainCss.matchAll(/([^{}]+)\{([^{}]*)\}/g)]
      .filter((match) => match[1].includes('data-rh-mobile-drawer-docs') || match[1].includes('#mobile_nav_drawer'));
    const selectors = rules.flatMap((match) => match[1].replace(/\/\*[\s\S]*?\*\//g, '').split(',').map((item) => item.trim()));
    expect(selectors).toEqual([
      '#mobile_nav_drawer > :has(+ [data-rh-mobile-drawer-docs])',
      '#mobile_nav_drawer > :has(+ * + [data-rh-mobile-drawer-docs])',
    ]);
    expect(rules[0][2]).toContain('display: none !important;');
    // 드로어 섹션 스타일은 소유 클래스 아래로만 한정한다
    for (const match of mainCss.matchAll(/([^{}]+)\{/g)) {
      if (!match[1].includes('rh-drawer-')) continue;
      for (const selector of match[1].replace(/\/\*[\s\S]*?\*\//g, '').split(',').map((item) => item.trim())) {
        expect(selector, selector).toMatch(/^(html\.dark )?\.rh-drawer-docs\b/);
      }
    }
    expect(mainCss).toMatch(/\.rh-drawer-docs \.rh-drawer-link \{[^}]*min-height: 44px;[^}]*overflow-wrap: anywhere;/);
  });

  it('오프라인 시뮬레이션은 이전 소유 노드를 걷어 내고 같은 저장 계약으로 다시 적용한다', () => {
    const simulation = readText(resolve(root, '../tests/browser/overlay-simulation.cjs'));
    expect(simulation).toContain("'rh_mobile_drawer_docs'");
    const drawerSimulation = readText(resolve(root, '../tests/browser/mobile-drawer-simulation.cjs'));
    expect(drawerSimulation).toContain('patchMergedLayout(body, overlays)');
    expect(drawerSimulation).toContain("only stock shop + info/policy hidden");
  });
});

describe('기존 계약 유지', () => {
  it('본문을 담았던 product route/layout/translation은 제거된 상태를 유지한다', () => {
    expect(existsSync(resolve(root, 'routes/user.json'))).toBe(false);
    for (const name of [
      'rh_info_services.json', 'rh_info_cases.json', 'rh_info_principles.json',
      'rh_policy_privacy.json', 'rh_policy_community.json', 'rh_policy_ai_workspace.json', 'rh_policy_open_source.json',
    ]) expect(existsSync(resolve(root, `layouts/user/${name}`)), name).toBe(false);

    for (const key of ['doc', 'services', 'cases', 'principles', 'privacy', 'community', 'ai_policy', 'oss']) {
      expect(ko).not.toHaveProperty(key);
      expect(en).not.toHaveProperty(key);
    }
    for (const legacy of Object.keys(LEGACY)) expect(hrefs(nav), legacy).not.toContain(legacy);
  });

  it('모듈 버전 메타데이터가 함께 움직이고 sirsoft-page 계약을 명시한다', () => {
    expect(moduleManifest.version).toBe('0.5.2');
    expect(componentManifest.version).toBe(moduleManifest.version);
    expect(composerManifest.version).toBe(moduleManifest.version);
    expect(packageManifest.version).toBe(moduleManifest.version);
    expect(moduleManifest.dependencies.modules['sirsoft-page']).toBe('>=1.1.2');
    expect(viteConfig).toContain('emptyOutDir: false');
  });

  it('기존 URL 7개를 native slug로 명시적으로 매핑하고 locale 경로도 받는다', () => {
    for (const [legacy, canonical] of Object.entries(LEGACY)) {
      expect(compatibilityRoutes).toContain(`'${legacy.slice(1)}' => '${canonical.slice('/page/'.length)}'`);
    }
    expect(compatibilityRoutes).toContain("Route::get('/{locale}/'.$legacyPath");
    expect(compatibilityRoutes).toContain("config('app.supported_locales'");
    expect(redirectController).toContain('redirect()->to($target, 301)');
    expect(redirectController).toContain("$query['locale'] = $locale");
    expect(redirectController).not.toContain("$prefix.'/page/'");
  });

  it('runtime smoke 는 일반 Chrome UA 로 human DOM 을 보고 footer 문구·href 와 11개 문서를 확인한다', () => {
    expect(browserSmoke.match(/browser\.newContext\(/g)).toHaveLength(1);
    expect(browserSmoke).toContain("require('../../resources/taxonomy/info-policy.json')");
    expect(browserSmoke).toContain('for (const slug of DOC_SLUGS) await inspectNativePage(page, viewport, slug');
    expect(browserSmoke).toMatch(/desktop: 'Mozilla\/5\.0 \(Windows NT 10\.0; Win64; x64\)[^']*Chrome\/[^']*'/);
    expect(browserSmoke).not.toContain('HeadlessChrome/');
    expect(browserSmoke).toContain("'footer link text and href follow taxonomy'");
    expect(browserSmoke).toContain("'Powered by attribution remains'");
    expect(browserSmoke).toContain("'Escape closes document menu and returns focus'");
    // 표현 부재는 짧은 고정 대기 뒤 FAIL 로 기록하고 계속 진행한다(구 런타임에서 페이지마다 15초 대기 금지).
    expect(browserSmoke).toContain("waitForSelector('.rh-native-breadcrumb', { timeout: PRESENTATION_WAIT_MS })");
    expect(browserSmoke).toContain("'RAON document presentation is applied'");
    expect(browserSmoke).not.toContain("waitForSelector('.rh-native-breadcrumb', { timeout: 15000 })");
    expect(browserSmoke.indexOf('await inspectConsultationConfig(api)')).toBeLessThan(
      browserSmoke.indexOf('await inspectLegacyRedirect(api, firstSlug, firstLegacy)'),
    );
    expect(browserSmoke).toContain("['artisan', 'route:list', '--name=raonslab-product.compatibility', '--json']");
    expect(browserSmoke).toContain('Googlebot/2.1');
    expect(browserSmoke).toContain("headers['x-seo-cache']");
    expect(browserSmoke).not.toContain('await response.json()');
  });
});

describe('현재 위치 경로 정규화', () => {
  it('clean URL의 끝 슬래시를 제거하고 canonical group을 판정한다', () => {
    expect(normalizePath('/page/service/')).toBe('/page/service');
    expect(normalizePath('/board/notice')).toBe('/board/notice');
    expect(productPageGroup('/page/technology')).toBe('info');
    expect(productPageGroup('/page/terms')).toBe('policy');
    expect(productPageGroup('/page/contact')).toBe('info');
    expect(productPageGroup('/board/notice')).toBeNull();
    expect(productPageGroup('/page/unknown')).toBeNull();
  });
});
