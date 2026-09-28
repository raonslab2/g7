import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { normalizePath } from './productNav';

const root = resolve(__dirname, '..');
const read = (path: string) => JSON.parse(readFileSync(path, 'utf8'));
const routes = read(resolve(root, 'routes/user.json')).routes as Array<{ path: string; layout: string; auth_required: boolean; meta: { title: string } }>;
const nav = read(resolve(root, 'extensions/product-nav.json'));
const ko = read(resolve(root, 'lang/ko.json'));
const en = read(resolve(root, 'lang/en.json'));
const templateRoutes = read(resolve(root, '../../../../templates/_bundled/sirsoft-basic/routes.json')).routes as Array<{ path: string }>;
const manifest = read(resolve(root, '../../../../templates/_bundled/sirsoft-basic/components.json'));
const registered = new Set((manifest.components.basic as Array<{ name: string }>).map((c) => c.name));

type Node = { id?: string; name?: string; text?: string; props?: Record<string, unknown>; children?: Node[]; actions?: Array<{ handler: string; params?: { path?: string } }> };

function walk(node: Node, visit: (n: Node) => void): void {
  visit(node);
  node.children?.forEach((child) => walk(child, visit));
}
const collect = (node: Node): Node[] => { const out: Node[] = []; walk(node, (n) => out.push(n)); return out; };

function flatten(obj: Record<string, unknown>, prefix = ''): Record<string, string> {
  return Object.entries(obj).reduce<Record<string, string>>((acc, [key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key;
    if (value && typeof value === 'object') Object.assign(acc, flatten(value as Record<string, unknown>, path));
    else acc[path] = String(value);
    return acc;
  }, {});
}
const koFlat = flatten(ko);
const enFlat = flatten(en);

/** 코어 Router.matchPattern 과 같은 규칙(별표+슬래시 = 선택 언어 prefix, 콜론 파라미터 = 한 세그먼트). */
function matches(pattern: string, pathname: string): boolean {
  let regex = pattern.replace(/:([^/]+)/g, '([^/]+)');
  if (regex.startsWith('*/')) regex = '(?:/[^/]+)?' + regex.slice(1);
  return new RegExp(`^${regex.replace(/\//g, '\\/')}$`).test(pathname);
}

const PAGES = {
  info: ['/info/services', '/info/cases', '/info/principles'],
  policy: ['/policy/privacy', '/policy/community', '/policy/ai-workspace', '/policy/open-source'],
};
const ALL = [...PAGES.info, ...PAGES.policy];
const layouts = Object.fromEntries(routes.map((r) => [r.path.slice(1), read(resolve(root, `layouts/user/${r.layout}.json`))]));
const gnav: Node = nav.injections[0].components[0];
const gnavNodes = collect(gnav);

function translationKeys(value: unknown): string[] {
  return (JSON.stringify(value).match(/\$t:raonslab-product\.[a-z0-9_.]+/g) ?? []).map((k) => k.slice('$t:raonslab-product.'.length));
}

describe('정보·정책 라우트', () => {
  it('정보 3개·정책 4개 공개 라우트를 선언한다', () => {
    expect(routes.map((r) => r.path.slice(1))).toEqual(ALL);
    for (const route of routes) {
      expect(route.auth_required).toBe(false);
      expect(route.path.startsWith('*/')).toBe(true);
    }
  });

  it('템플릿 라우트(먼저 매칭)에 가려지지 않는다 — 직접 URL·새로고침이 이 레이아웃으로 열린다', () => {
    for (const path of ALL) {
      for (const localized of [path, `/ko${path}`, `/en${path}`]) {
        const shadow = templateRoutes.find((r) => !r.path.includes('{{') && matches(r.path, localized));
        expect(shadow?.path, localized).toBeUndefined();
        expect(routes.some((r) => matches(r.path, localized))).toBe(true);
      }
    }
  });

  it('기존 Community/Notice/Q&A/Search/Login/AI/Admin URL 을 가로채지 않는다', () => {
    const existing = ['/', '/board/community', '/board/notice', '/board/questions', '/search', '/login', '/register', '/ai', '/admin/consultations', '/page/privacy', '/page/terms'];
    for (const url of existing) expect(routes.filter((r) => matches(r.path, url)).map((r) => r.path), url).toEqual([]);
  });

  it('각 라우트의 레이아웃은 _user_base 를 상속하고 layout_name 이 일치한다', () => {
    for (const route of routes) {
      const layout = layouts[route.path.slice(1)];
      expect(layout.layout_name).toBe(route.layout);
      expect(layout.extends).toBe('_user_base');
      expect(Array.isArray(layout.slots.content)).toBe(true);
    }
  });
});

describe('정보·정책 상위 메뉴(product-nav)', () => {
  it('_user_base 헤더 아래 메뉴와 푸터 링크만 주입한다(코어·템플릿 파일 무변경)', () => {
    expect(nav.target_layout).toBe('_user_base');
    expect(nav.injections.map((i: { target_id: string; position: string }) => [i.target_id, i.position])).toEqual([
      ['main_content_area', 'prepend_child'],
      ['footer', 'inject_props'],
    ]);
  });

  it('상위 메뉴는 hover 가 아니라 aria-expanded disclosure 버튼이다', () => {
    for (const group of ['info', 'policy'] as const) {
      const trigger = gnavNodes.find((n) => n.props?.['data-rh-menu-trigger'] === group);
      const panel = gnavNodes.find((n) => n.props?.id === `rh-gnav-${group}-panel`);
      expect(trigger?.name).toBe('Button');
      expect(trigger?.props).toMatchObject({ type: 'button', 'aria-expanded': 'false', 'aria-controls': `rh-gnav-${group}-panel`, id: `rh-gnav-${group}-trigger` });
      expect(panel?.props?.hidden).toBe(true);
      const links = collect(panel as Node).filter((n) => n.name === 'A').map((n) => n.props?.href);
      expect(links).toEqual(PAGES[group]);
    }
  });

  it('메뉴·문서의 모든 내부 링크는 실제 라우트 또는 기존 URL 로 간다(빈 링크 없음)', () => {
    const known = new Set([...ALL, '/', '/#rh-consult']);
    const hrefs = [gnav, ...Object.values(layouts).map((l) => l.slots.content[0])]
      .flatMap((n) => collect(n)).filter((n) => n.name === 'A').map((n) => String(n.props?.href));
    for (const href of hrefs) {
      if (href.startsWith('#')) continue;
      expect(known.has(href), href).toBe(true);
    }
    for (const node of [gnav, ...Object.values(layouts).map((l) => l.slots.content[0])].flatMap((n) => collect(n))) {
      for (const action of node.actions ?? []) {
        expect(action.handler).toBe('navigate');
        expect(action.params?.path).toBe(node.props?.href);
      }
    }
  });

  it('푸터 정보·정책 링크는 새 페이지로, 커뮤니티 링크는 기존 URL 로 간다', () => {
    const groups = nav.injections[1].props.linkGroups as Array<{ title: string; links: Array<{ href: string }> }>;
    expect(groups.map((g) => g.links.map((l) => l.href))).toEqual([
      ['/', '/board/notice', '/board/questions', '/board/community', '/search'],
      PAGES.info,
      PAGES.policy,
    ]);
  });
});

describe('정보·정책 문서 레이아웃', () => {
  for (const path of ALL) {
    const page: Node = layouts[path].slots.content[0];
    const nodes = collect(page);
    const group = path.split('/')[1] as 'info' | 'policy';

    it(`${path}: 현재 위치(breadcrumb·하위 메뉴)가 자기 자신을 가리킨다`, () => {
      expect(nodes.filter((n) => n.name === 'Span' && n.props?.['aria-current'] === 'page')).toHaveLength(1);
      const side = nodes.filter((n) => String(n.props?.className).includes('rh-side-link'));
      expect(side.map((n) => n.props?.href)).toEqual(PAGES[group]);
      expect(side.filter((n) => n.props?.['aria-current'] === 'page').map((n) => n.props?.href)).toEqual([path]);
    });

    it(`${path}: 목차는 실제 섹션과 제목을 가리킨다`, () => {
      const ids = new Set(nodes.map((n) => n.props?.id));
      const anchors = nodes.filter((n) => n.props?.['data-rh-anchor']);
      expect(anchors.length).toBeGreaterThanOrEqual(3);
      for (const anchor of anchors) {
        const id = String(anchor.props?.['data-rh-anchor']);
        expect(anchor.props?.href).toBe(`#${id}`);
        expect(ids.has(id)).toBe(true);
        expect(ids.has(`${id}-title`)).toBe(true);
      }
    });

    it(`${path}: 등록된 기본 컴포넌트만, 메뉴와 겹치지 않는 고유 id 로 쓴다`, () => {
      expect(nodes.map((n) => n.name).filter((name) => !registered.has(String(name)))).toEqual([]);
      const ids = [...nodes, ...gnavNodes].map((n) => n.id);
      expect(new Set(ids).size).toBe(ids.length);
      const domIds = [...nodes, ...gnavNodes].map((n) => n.props?.id).filter(Boolean);
      expect(new Set(domIds).size).toBe(domIds.length);
      expect(nodes.filter((n) => n.name === 'H1')).toHaveLength(1);
    });
  }

  it('등록된 기본 컴포넌트만 메뉴에 쓴다', () => {
    expect(gnavNodes.map((n) => n.name).filter((name) => !registered.has(String(name)))).toEqual([]);
  });
});

describe('정보·정책 번역과 사실 경계', () => {
  const used = new Set([...translationKeys(nav), ...translationKeys(routes), ...Object.values(layouts).flatMap((l) => translationKeys(l))]);

  it('사용하는 모든 번역 키가 ko/en 에 비어 있지 않게 있다', () => {
    for (const key of used) {
      expect(koFlat[key]?.trim(), `ko ${key}`).toBeTruthy();
      expect(enFlat[key]?.trim(), `en ${key}`).toBeTruthy();
    }
  });

  it('ko/en 키 집합이 같다', () => {
    expect(Object.keys(enFlat).sort()).toEqual(Object.keys(koFlat).sort());
  });

  it('승인되지 않은 연락처·주소·금액·보관 기간을 만들어 넣지 않는다', () => {
    const copy = [...used].map((key) => `${koFlat[key]}\n${enFlat[key]}`).join('\n');
    expect(copy).not.toMatch(/[\w.+-]+@[\w-]+\.[\w.]+/);
    expect(copy).not.toMatch(/\d{2,4}-\d{3,4}-\d{4}/);
    expect(copy).not.toMatch(/(\d+\s*(원|만원|억|개월|일간|년간)|₩|\$\s?\d|\d+\s*(days|months|years))/);
    expect(copy).not.toMatch(/사업자등록번호|대표자|Registration No/);
  });

  it('미확정 정책은 승인 대기로 표시하고 확정 약관처럼 가장하지 않는다', () => {
    for (const path of PAGES.policy) {
      const nodes = collect(layouts[path].slots.content[0]);
      const chips = nodes.filter((n) => String(n.props?.className).includes('rh-chip'));
      expect(chips.some((n) => n.props?.['data-status'] === 'pending'), path).toBe(true);
      expect(chips.some((n) => n.text === '$t:raonslab-product.doc.status_draft'), path).toBe(true);
    }
    expect(koFlat['doc.status_draft']).toContain('확정 문서 아님');
    expect(koFlat['privacy.lede']).toContain('확정된 개인정보처리방침이 아닙니다');
  });

  it('구현 사례는 내부 사례임을 먼저 밝히고 미검증 범위를 함께 보인다', () => {
    const nodes = collect(layouts['/info/cases'].slots.content[0]);
    const main = nodes.find((n) => n.props?.className === 'rh-doc-main');
    expect(main?.children?.[0]?.props?.className).toBe('rh-callout');
    expect(koFlat['cases.lede']).toContain('내부 사례');
    expect(nodes.filter((n) => n.props?.['data-kind'] === 'unverified')).toHaveLength(2);
  });

  it('AI 작업공간 정책은 보류 상태와 가입·실행 승인 분리를 먼저 보인다', () => {
    const nodes = collect(layouts['/policy/ai-workspace'].slots.content[0]);
    expect(nodes.some((n) => n.text === '$t:raonslab-product.doc.status_hold')).toBe(true);
    expect(koFlat['ai_policy.lede']).toContain('회원가입과 AI 실행 승인은 서로 다른 단계');
  });

  it('기술 원칙은 고객 언어를 먼저, 내부 제품명은 마지막 절에 둔다', () => {
    const page: Node = layouts['/info/principles'].slots.content[0];
    const sections = collect(page).filter((n) => String(n.props?.id ?? '').startsWith('rh-doc-') && n.props?.role === 'region');
    expect(sections.at(-1)?.props?.id).toBe('rh-doc-internal');
    const earlier = sections.slice(0, -1).flatMap((s) => translationKeys(s)).map((k) => koFlat[k]).join(' ');
    expect(earlier).not.toMatch(/AgentOpt|AI_GCS|raonslab-/);
  });
});

describe('현재 위치 경로 정규화', () => {
  it('끝 슬래시와 언어 prefix 를 제거한다', () => {
    expect(normalizePath('/info/services/')).toBe('/info/services');
    expect(normalizePath('/ko/policy/privacy')).toBe('/policy/privacy');
    expect(normalizePath('/en-US/info/cases')).toBe('/info/cases');
    expect(normalizePath('/board/notice')).toBe('/board/notice');
    expect(normalizePath('/')).toBe('/');
  });
});
