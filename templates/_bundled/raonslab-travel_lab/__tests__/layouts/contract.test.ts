/**
 * @file contract.test.ts
 * @description 레이아웃·라우트·다국어·매니페스트 정적 계약 — RAON Travel Lab
 *
 * 렌더 테스트가 볼 수 없는 축(전 레이아웃 전수)을 고정한다.
 *
 * @vitest-environment jsdom
 */
import fs from 'fs';
import path from 'path';
import { describe, it, expect } from 'vitest';
import templateJson from '../../template.json';
import routesJson from '../../routes.json';
import componentsManifest from '../../components.json';
import ko from '../../lang/ko.json';
import en from '../../lang/en.json';
import pkg from '../../package.json';
import * as Template from '../../src/index';
import { API_BASE, LAYOUT_ROOT, TEMPLATE_ROOT, listLayoutFiles, findAll, walk, hasLangKey } from '../helpers/travelTestKit';

const layouts = listLayoutFiles().map((file) => ({
  file: path.relative(LAYOUT_ROOT, file),
  json: JSON.parse(fs.readFileSync(file, 'utf-8')),
}));
const allJson = layouts.map((l) => l.json);

function flatKeys(obj: Record<string, any>, prefix = ''): string[] {
  return Object.entries(obj).flatMap(([k, v]) =>
    v && typeof v === 'object' ? flatKeys(v, `${prefix}${k}.`) : [`${prefix}${k}`],
  );
}

function strings(node: any): string[] {
  const out: string[] = [];
  walk(node, (n) => {
    Object.values(n).forEach((v) => {
      if (typeof v === 'string') out.push(v);
    });
  });
  return out;
}

describe('매니페스트 · 버전', () => {
  it('식별자·버전·의존 제약이 요청 계약과 같다', () => {
    expect(templateJson.identifier).toBe('raonslab-travel_lab');
    expect(templateJson.version).toBe('0.1.1');
    expect(pkg.version).toBe(templateJson.version);
    expect(templateJson.g7_version).toBe('>=7.0.12');
    expect(templateJson.dependencies.modules).toMatchObject({
      'raonslab-travel_lab': '>=0.1.1',
      'sirsoft-ecommerce': '>=1.2.1',
      'sirsoft-board': '>=1.1.2',
      'sirsoft-page': '>=1.1.2',
    });
  });

  it('선언한 자산 파일이 실제로 있다', () => {
    [...templateJson.assets.css, ...templateJson.assets.js].forEach((rel) => {
      expect(fs.existsSync(path.join(TEMPLATE_ROOT, rel)), rel).toBe(true);
    });
    templateJson.externals.forEach((ext: any) => {
      expect(fs.existsSync(path.join(TEMPLATE_ROOT, 'dist', ext.asset)), ext.asset).toBe(true);
    });
  });

  it('components.json 의 모든 컴포넌트가 번들 export 로 존재한다 (전역 RaonslabTravelLab)', () => {
    (['basic', 'composite', 'layout'] as const).forEach((type) => {
      (componentsManifest as any).components[type].forEach((meta: any) => {
        expect(meta.name).toMatch(/^[A-Z][A-Za-z0-9]*$/);
        expect(meta.type).toBe(type);
        expect((Template as any)[meta.name], meta.name).toBeTruthy();
        expect(fs.existsSync(path.join(TEMPLATE_ROOT, meta.path)), meta.path).toBe(true);
      });
    });
  });

  it('레이아웃이 쓰는 컴포넌트는 모두 매니페스트에 있다', () => {
    const declared = new Set(
      (['basic', 'composite', 'layout'] as const).flatMap((t) => (componentsManifest as any).components[t].map((m: any) => m.name)),
    );
    allJson.forEach((json) =>
      findAll(json, (n) => typeof n.name === 'string' && typeof n.type === 'string' && ['basic', 'composite', 'layout'].includes(n.type)).forEach((n) => {
        expect(declared.has(n.name), `${n.name}`).toBe(true);
      }),
    );
  });

  it('dist 번들에 sourceMappingURL 이 남지 않는다', () => {
    const js = fs.readFileSync(path.join(TEMPLATE_ROOT, 'dist/js/components.iife.js'), 'utf-8');
    expect(js).not.toContain('sourceMappingURL');
    expect(js).toContain('RaonslabTravelLab');
  });
});

describe('라우트', () => {
  const routes = (routesJson as any).routes as any[];
  const byPath = Object.fromEntries(routes.map((r) => [r.path, r]));

  it('요청된 여행 경로를 모두 제공한다', () => {
    ['/travel', '/travel/search', '/travel/products/:id', '/travel/cart', '/travel/requests', '/travel/requests/:id', '/travel/help', '/login'].forEach((p) => {
      expect(byPath[p], p).toBeDefined();
    });
  });

  it('라우트가 가리키는 레이아웃 파일이 있다', () => {
    routes.forEach((r) => expect(fs.existsSync(path.join(LAYOUT_ROOT, `${r.layout}.json`)), r.layout).toBe(true));
  });

  it('장바구니·상담 요청은 로그인 필요, 탐색·고객센터는 공개', () => {
    ['/travel/cart', '/travel/requests', '/travel/requests/:id'].forEach((p) => expect(byPath[p].auth_required).toBe(true));
    ['/travel', '/travel/search', '/travel/products/:id', '/travel/help', '/login'].forEach((p) => expect(byPath[p].auth_required).toBe(false));
    expect(byPath['/login'].guest_only).toBe(true);
  });

  it('오류 레이아웃 6종이 모두 있다', () => {
    Object.values(templateJson.error_config.layouts).forEach((rel) => {
      expect(fs.existsSync(path.join(LAYOUT_ROOT, `${rel}.json`)), rel).toBe(true);
    });
  });
});

describe('다국어', () => {
  it('ko/en 키 집합이 같다', () => {
    expect(flatKeys(en).sort()).toEqual(flatKeys(ko).sort());
  });

  it('레이아웃·라우트가 참조하는 $t: 키가 ko/en 모두에 있다', () => {
    const refs = new Set<string>();
    [...allJson, routesJson].forEach((json) =>
      strings(json).forEach((s) => {
        for (const m of s.matchAll(/\$t:([a-zA-Z_][a-zA-Z0-9_.\-]*)/g)) refs.add(m[1].replace(/\.$/, ''));
      }),
    );
    expect(refs.size).toBeGreaterThan(100);
    refs.forEach((key) => {
      expect(hasLangKey(ko, key), `ko:${key}`).toBe(true);
      expect(hasLangKey(en, key), `en:${key}`).toBe(true);
    });
  });

  it('상태 배지 문구가 5개 상태 + unknown 모두 있다', () => {
    ['test_inquiry', 'under_review', 'test_accepted', 'declined', 'cancelled', 'unknown'].forEach((s) => {
      expect(hasLangKey(ko, `travel.status.${s}`)).toBe(true);
    });
  });
});

describe('데이터 소스 · API', () => {
  const dataSources = layouts.flatMap((l) => (l.json.data_sources ?? []).map((ds: any) => ({ ...ds, file: l.file })));
  const apiCalls = allJson.flatMap((json) => findAll(json, (n) => n.handler === 'apiCall'));

  it('모든 데이터 소스에 label_key 가 있다', () => {
    dataSources.forEach((ds) => expect(ds.label_key, `${ds.file}:${ds.id}`).toMatch(/^\$t:editor\.data_source\./));
  });

  it('여행 API 는 표준 base 만 쓴다 (하드코딩 목업 응답 없음)', () => {
    dataSources.forEach((ds) => {
      expect(ds.type).toBe('api');
      expect(ds.endpoint === '/api/auth/user' || ds.endpoint.startsWith(`${API_BASE}/`), ds.endpoint).toBe(true);
      expect(ds.mock ?? ds.mockResponse ?? ds.static_data).toBeUndefined();
    });
    apiCalls.forEach((a) => expect(String(a.target).startsWith(`${API_BASE}/`), a.target).toBe(true));
  });

  it('로그인 전용 API 는 auth_mode=required 로 호출한다', () => {
    const authEndpoints = [/\/cart/, /\/inquiries/, /\/support\/questions/, /^\/api\/auth\/user$/];
    dataSources
      .filter((ds) => authEndpoints.some((re) => re.test(ds.endpoint)))
      .forEach((ds) => expect(ds.auth_mode, `${ds.file}:${ds.id}`).toBe('required'));
    apiCalls.filter(a => authEndpoints.some(re => re.test(a.target))).forEach((a) => expect(a.auth_mode).toBe('required'));
    apiCalls.filter(a => /\/support\/(notices|faqs)\//.test(a.target)).forEach(a => expect(a.auth_mode).toBe('optional'));
  });

  it('신뢰할 수 없는 가격을 서버로 보내지 않는다', () => {
    apiCalls.forEach((a) => {
      const body = JSON.stringify(a.params?.body ?? {});
      expect(body).not.toMatch(/price|amount|total/i);
    });
  });

  it('target/onSuccess/onError 는 액션 최상위에 둔다', () => {
    apiCalls.forEach((a) => {
      expect(a.params?.target).toBeUndefined();
      expect(a.params?.onSuccess).toBeUndefined();
      expect(a.params?.onError).toBeUndefined();
      expect(a.onError, a.target).toBeDefined();
    });
  });

  it('카탈로그 쿼리는 계약의 필터·정렬 키를 그대로 하달한다', () => {
    const search = layouts.find((l) => l.file === 'travel/search.json')!.json;
    const catalog = search.data_sources.find((d: any) => d.id === 'catalog');
    expect(catalog.endpoint).toBe(`${API_BASE}/catalog`);
    expect(Object.keys(catalog.params).sort()).toEqual(
      ['date_from', 'date_to', 'max_price', 'min_price', 'page', 'per_page', 'q', 'region', 'sort', 'theme'].sort(),
    );
    const sortSelect = findAll(search, (n) => n.props?.['data-testid'] === 'sort-select')[0];
    expect(sortSelect.props.options.map((o: any) => o.value)).toEqual(['recommended', 'price_asc', 'price_desc', 'departure_asc']);
    // 정렬 변경은 같은 목록 안 — 조건 승계 + 첫 페이지로
    expect(sortSelect.actions[0].params).toMatchObject({ path: '/travel/search', mergeQuery: true, query: { page: '' } });
    const pager = findAll(search, (n) => n.name === 'Pagination')[0];
    expect(pager.actions[0].params).toMatchObject({ mergeQuery: true, query: { page: '{{$args[0]}}' } });
    // 필터 적용은 모든 조건 키를 싣고 페이지를 비운다
    const apply = findAll(search, (n) => n.props?.['data-testid'] === 'filter-apply')[0];
    const nav = apply.actions[0].actions.find((a: any) => a.handler === 'navigate');
    expect(nav.params.mergeQuery).toBe(true);
    expect(Object.keys(nav.params.query).sort()).toEqual(['date_from', 'date_to', 'max_price', 'min_price', 'page', 'q', 'region', 'theme']);
  });
});

describe('상담 요청(테스트) 멱등성', () => {
  const cart = layouts.find((l) => l.file === 'travel/cart.json')!.json;
  const submit = findAll(cart, (n) => n.handler === 'apiCall' && String(n.target).endsWith('/inquiries'))[0];

  it('요청 body 는 cart_ids · contact · idempotency_key 만 담는다', () => {
    expect(Object.keys(submit.params.body).sort()).toEqual(['cart_ids', 'contact', 'idempotency_key']);
    expect(submit.params.body.idempotency_key).toBe('{{_local.travelInquiryPayload?.idempotency_key}}');
  });

  it('키는 장바구니 로드 시 확보되고(묶음 지문), 성공 시에만 폐기된다', () => {
    const cartDs = cart.data_sources.find((d: any) => d.id === 'cart');
    expect(cartDs.onSuccess.map((a: any) => a.handler)).toContain('travelLabEnsureInquiryKey');
    expect(submit.onSuccess.map((a: any) => a.handler)).toContain('travelLabClearInquiryKey');
    expect(JSON.stringify(submit.onError)).not.toContain('travelLab');
    expect(JSON.stringify(submit)).not.toContain('travelLabEnsureInquiryKey');
  });

  it('키가 없거나 테스트 고지 확인 전이면 보낼 수 없다', () => {
    const btn = findAll(cart, (n) => n.props?.['data-testid'] === 'submit-inquiry')[0];
    expect(btn.props.disabled).toContain('!_global.travelInquiryKey');
    expect(btn.props.disabled).toContain('_local.ackTest !== true');
    expect(btn.props.disabled).toContain('_local.submitting');
  });
});

describe('핸들러·바인딩 금지 패턴', () => {
  const BANNED_HANDLERS = ['api', 'nav', 'setLocalState', 'showToast'];

  it('금지 핸들러 이름을 쓰지 않는다', () => {
    allJson.forEach((json) =>
      findAll(json, (n) => typeof n.handler === 'string').forEach((n) => expect(BANNED_HANDLERS).not.toContain(n.handler)),
    );
  });

  it('$response / 이벤트 $value / iteration item·index 키를 쓰지 않는다', () => {
    allJson.forEach((json) => {
      const text = JSON.stringify(json);
      expect(text).not.toContain('$response');
      expect(text).not.toMatch(/\{\{\s*\$value/);
      findAll(json, (n) => n.iteration).forEach((n) => {
        expect(n.iteration.item_var).toBeTruthy();
        expect(n.iteration.item).toBeUndefined();
        expect(n.iteration.index).toBeUndefined();
      });
    });
  });

  it('navigate 는 path 를 쓰고 mergeQuery 는 boolean 리터럴이다', () => {
    allJson.forEach((json) =>
      findAll(json, (n) => n.handler === 'navigate').forEach((n) => {
        expect(n.params?.url ?? n.params?.href ?? n.params?.to).toBeUndefined();
        expect(n.params?.path, JSON.stringify(n)).toBeTruthy();
        expect(n.params.path).not.toBe('back');
        expect(n.params.path).not.toContain('?redirect');
        if ('mergeQuery' in n.params) expect(typeof n.params.mergeQuery).toBe('boolean');
        if (n.params.mergeQuery === true) expect(Array.isArray(n.params.query)).toBe(false);
      }),
    );
  });

  it('홈·탭 전환 등 의도적 리셋 이동은 audit:allow 주석을 남긴다', () => {
    allJson.forEach((json) =>
      findAll(json, (n) => n.handler === 'navigate' && n.params?.query && n.params.mergeQuery !== true && n.params.path !== '/login').forEach((n) => {
        expect(String(n.comment ?? ''), JSON.stringify(n.params)).toContain('audit:allow layout-list-context-navigate-merge-query');
      }),
    );
  });

  it('모든 버튼은 type 과 동작(actions 또는 submit)을 갖는다', () => {
    allJson.forEach((json) =>
      findAll(json, (n) => n.name === 'Button').forEach((n) => {
        expect(['button', 'submit']).toContain(n.props?.type);
        if (n.props.type === 'button') expect(Array.isArray(n.actions) && n.actions.length > 0, JSON.stringify(n.props)).toBe(true);
      }),
    );
  });

  it('외부 CDN 자산·HTML 태그 원시 요소를 레이아웃에 쓰지 않는다', () => {
    allJson.forEach((json) => {
      const text = JSON.stringify(json);
      expect(text).not.toMatch(/https?:\/\/[^"]+\.(js|css|woff2?|ttf)/);
      findAll(json, (n) => typeof n.name === 'string' && ['basic', 'composite', 'layout'].includes(n.type)).forEach((n) => expect(n.name).toMatch(/^[A-Z]/));
    });
  });

  it('pagination 은 last_page 를 1 로 접지 않는다', () => {
    allJson.forEach((json) => {
      const text = JSON.stringify(json);
      expect(text).not.toMatch(/last_page \?\? 1\b/);
      expect(text).not.toMatch(/last_page \|\| 1\b/);
    });
  });

  it('베이스 레이아웃은 토스트 호스트·로그인/로그아웃·콘텐츠 슬롯을 갖는다', () => {
    const base = layouts.find((l) => l.file === '_user_base.json')!.json;
    expect(base.components[0]).toMatchObject({ name: 'Toast', props: { toasts: '{{_global.toasts}}' } });
    expect(findAll(base, (n) => n.handler === 'logout').length).toBeGreaterThan(0);
    findAll(base,(n)=>n.handler==='logout').forEach((action)=>expect(action.onSuccess?.[0]).toEqual({handler:'travelLabClearInquiryKey'}));
    expect(findAll(base, (n) => n.handler === 'navigate' && n.params?.path === '/login').length).toBeGreaterThan(0);
    expect(findAll(base, (n) => n.slot === 'content').length).toBe(1);
    expect(findAll(base, (n) => n.props?.['data-testid'] === 'test-notice-bar').length).toBe(1);
  });

  it('modals 의 partial 이 존재하고 Modal id 를 갖는다', () => {
    allJson.forEach((json) =>
      (json.modals ?? []).forEach((m: any) => {
        const file = path.join(LAYOUT_ROOT, m.partial);
        expect(fs.existsSync(file), m.partial).toBe(true);
        const partial = JSON.parse(fs.readFileSync(file, 'utf-8'));
        expect(partial.name).toBe('Modal');
        expect(partial.id).toBeTruthy();
        const openers = allJson.flatMap((j) => findAll(j, (n) => n.handler === 'openModal' && n.target === partial.id));
        expect(openers.length, partial.id).toBeGreaterThan(0);
      }),
    );
  });
});
