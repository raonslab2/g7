import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { normalizePath, productPageGroup } from './productNav';

const root = resolve(__dirname, '..');
const readJson = (path: string) => JSON.parse(readFileSync(path, 'utf8'));
const nav = readJson(resolve(root, 'extensions/product-nav.json'));
const nativePage = readJson(resolve(root, 'extensions/native-page.json'));
const ko = readJson(resolve(root, 'lang/ko.json'));
const en = readJson(resolve(root, 'lang/en.json'));
const moduleManifest = readJson(resolve(root, '../module.json'));
const compatibilityRoutes = readFileSync(resolve(root, '../src/routes/compatibility.php'), 'utf8');
const redirectController = readFileSync(resolve(root, '../src/Http/Controllers/LegacyPageRedirectController.php'), 'utf8');
const mainCss = readFileSync(resolve(root, 'css/main.css'), 'utf8');

type Node = {
  name?: string;
  props?: Record<string, unknown>;
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

function flatten(obj: Record<string, unknown>, prefix = ''): Record<string, string> {
  return Object.entries(obj).reduce<Record<string, string>>((result, [key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key;
    if (value && typeof value === 'object') Object.assign(result, flatten(value as Record<string, unknown>, path));
    else result[path] = String(value);
    return result;
  }, {});
}

const PAGE_GROUPS = {
  info: ['/page/service', '/page/cases', '/page/technology'],
  policy: ['/page/privacy', '/page/terms', '/page/ai-workspace-policy', '/page/open-source'],
};
const CANONICAL = [...PAGE_GROUPS.info, ...PAGE_GROUPS.policy];
const LEGACY = {
  '/info/services': '/page/service',
  '/info/cases': '/page/cases',
  '/info/principles': '/page/technology',
  '/policy/privacy': '/page/privacy',
  '/policy/community': '/page/terms',
  '/policy/ai-workspace': '/page/ai-workspace-policy',
  '/policy/open-source': '/page/open-source',
};

describe('native Page 정보·정책 계약', () => {
  it('7개 canonical page 링크를 public navigation과 Page 확장에만 둔다', () => {
    const navHrefs = collect(nav).map((node) => node.props?.href).filter(Boolean);
    const sideHrefs = collect(nativePage).map((node) => node.props?.href).filter(Boolean);

    for (const href of CANONICAL) {
      expect(navHrefs, href).toContain(href);
      expect(sideHrefs, href).toContain(href);
    }
    for (const legacy of Object.keys(LEGACY)) expect(navHrefs, legacy).not.toContain(legacy);
  });

  it('공식 page/show overlay의 breadcrumb를 responsive presentation invariant로 사용한다', () => {
    expect(nativePage.target_layout).toBe('page/show');
    expect(nativePage.injections.map((item: { target_id: string; position: string }) => [item.target_id, item.position])).toEqual([
      ['page_content_card', 'inject_props'],
      ['page_content_card', 'prepend_child'],
      ['page_html_content', 'prepend'],
    ]);
    expect(JSON.stringify(nativePage)).toContain('page?.data?.slug');
    expect(JSON.stringify(nativePage)).not.toContain('"content":');
    expect(JSON.stringify(nativePage.injections[1].components)).toContain('rh-native-breadcrumb');
    expect(nativePage.injections.some((injection: any) => (
      injection.target_id === 'page_html_content' && injection.position === 'inject_props'
    ))).toBe(false);
    expect(mainCss).toContain('body.raon-product #main_content :has(> .rh-native-breadcrumb) {');
    expect(mainCss).toContain('body.raon-product #main_content :has(> .rh-native-breadcrumb)::after {');
    expect(mainCss).toContain('body.raon-product #main_content :has(> .rh-native-breadcrumb) #page_html_content {');
  });

  it('본문을 담았던 product route/layout/translation은 제거된다', () => {
    expect(existsSync(resolve(root, 'routes/user.json'))).toBe(false);
    for (const name of [
      'rh_info_services.json', 'rh_info_cases.json', 'rh_info_principles.json',
      'rh_policy_privacy.json', 'rh_policy_community.json', 'rh_policy_ai_workspace.json', 'rh_policy_open_source.json',
    ]) expect(existsSync(resolve(root, `layouts/user/${name}`)), name).toBe(false);

    for (const key of ['doc', 'services', 'cases', 'principles', 'privacy', 'community', 'ai_policy', 'oss']) {
      expect(ko).not.toHaveProperty(key);
      expect(en).not.toHaveProperty(key);
    }
  });

  it('모듈은 검증한 sirsoft-page 계약을 명시한다', () => {
    expect(moduleManifest.version).toBe('0.4.0');
    expect(moduleManifest.dependencies.modules['sirsoft-page']).toBe('>=1.1.2');
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

  it('ko/en presentation key 집합은 같고 본문 문구는 남지 않는다', () => {
    expect(Object.keys(flatten(en)).sort()).toEqual(Object.keys(flatten(ko)).sort());
    expect(JSON.stringify(ko)).not.toContain('확정된 개인정보처리방침이 아닙니다');
    expect(JSON.stringify(en)).not.toContain('This is not a final privacy policy');
  });
});

describe('현재 위치 경로 정규화', () => {
  it('clean URL의 끝 슬래시를 제거하고 canonical group을 판정한다', () => {
    expect(normalizePath('/page/service/')).toBe('/page/service');
    expect(normalizePath('/board/notice')).toBe('/board/notice');
    expect(productPageGroup('/page/technology')).toBe('info');
    expect(productPageGroup('/page/terms')).toBe('policy');
    expect(productPageGroup('/board/notice')).toBeNull();
  });
});
