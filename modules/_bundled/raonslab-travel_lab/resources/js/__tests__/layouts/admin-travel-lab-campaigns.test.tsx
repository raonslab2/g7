/**
 * @file admin-travel-lab-campaigns.test.tsx
 * @description 여행 기획전 관리자 어댑터 — native 페이지 관리자 API/화면으로만 연결하는 계약 + 렌더
 *
 * @scenario case=campaign_admin_adapter
 * @effects page_read_permission|exact_slug_mapping|native_create_prefill|native_edit_detail_links|abilities_gate_publish|scope_absent_not_missing
 *
 * @vitest-environment jsdom
 */
import { describe, it, expect, afterEach } from 'vitest';
import fs from 'fs';
import path from 'path';
import React from 'react';
import { createLayoutTest, createMockComponentRegistryWithBasics } from '@core/template-engine/__tests__/utils/layoutTestUtils';

const ROOT = path.resolve(__dirname, '../../../');
const MODULE_ROOT = path.resolve(ROOT, '..');
const layout = JSON.parse(fs.readFileSync(path.join(ROOT, 'layouts/admin/admin_travel_lab_campaigns.json'), 'utf8'));
const routes = JSON.parse(fs.readFileSync(path.join(ROOT, 'routes.json'), 'utf8'));
const ko = JSON.parse(fs.readFileSync(path.join(ROOT, 'lang/partial/ko/admin.json'), 'utf8'));
const en = JSON.parse(fs.readFileSync(path.join(ROOT, 'lang/partial/en/admin.json'), 'utf8'));
const registryConfig = fs.readFileSync(path.join(MODULE_ROOT, 'config/campaigns.php'), 'utf8');
const AUTUMN = 'travel-lab-campaign-autumn-escape';
const WEEKEND = 'travel-lab-campaign-weekend-reset';

function walk(node: unknown, visit: (n: any) => void): void {
    if (Array.isArray(node)) return node.forEach((c) => walk(c, visit));
    if (!node || typeof node !== 'object') return;
    visit(node);
    Object.values(node as Record<string, unknown>).forEach((v) => walk(v, visit));
}
const collect = (pred: (n: any) => boolean) => {
    const out: any[] = [];
    walk(layout, (n) => pred(n) && out.push(n));
    return out;
};
const lookup = (dict: any, key: string) => key.split('.').reduce((d: any, p) => (d && typeof d === 'object' ? d[p] : undefined), dict);
const registry = () => {
    const reg = createMockComponentRegistryWithBasics();
    reg.register('basic', 'Icon', ({ name }: any) => React.createElement('i', { 'data-icon': name }));
    return reg;
};
const row = (slug: string, extra: Record<string, any> = {}) => ({
    id: slug === AUTUMN ? 11 : 12, slug, title: `제목 ${slug}`, published: true, published_at: '2026-10-09 21:00:00', current_version: 4,
    abilities: { can_create: true, can_update: true, can_delete: false }, ...extra,
});
// Native PageCollection exposes collection abilities beside data/meta; each
// PageResource row independently exposes its own scoped update abilities.
const response = (rows: any[], meta: Record<string, any> = {}, abilities: Record<string, boolean> = { can_create: true, can_update: true, can_delete: false }) => ({ data: { data: rows, meta: { current_page: 1, last_page: 1, per_page: 100, total: rows.length, ...meta }, abilities } });

describe('기획전 관리자 어댑터 계약', () => {
    it('native 페이지 읽기 권한으로 보호되고 _admin_base 를 상속한다', () => {
        expect(layout.permissions).toEqual(['sirsoft-page.pages.read']);
        expect(layout.extends).toBe('_admin_base');
        const route = routes.routes.find((r: any) => r.path === '*/admin/travel-lab/campaigns');
        expect(route).toMatchObject({ layout: 'admin_travel_lab_campaigns', auth_required: true });
        expect(JSON.stringify(layout)).not.toContain('raonslab-travel_lab.catalog');
    });

    it('native 관리자 목록 API 를 PageListRequest 가 허용하는 필터 문법으로 호출한다', () => {
        const ds = layout.data_sources[0];
        expect(ds).toMatchObject({ id: 'campaign_pages', endpoint: '/api/modules/sirsoft-page/admin/pages', method: 'GET', auth_mode: 'required' });
        expect(ds.params['filters[0][field]']).toBe('slug');
        expect(['like', 'eq', 'starts_with', 'ends_with']).toContain(ds.params['filters[0][operator]']);
        expect(ds.params['filters[0][value]']).toBe('travel-lab-campaign-');
        expect(ds.params.per_page).toBeLessThanOrEqual(100);
        expect(ds.errorHandling['403'].handler).toBe('showErrorPage');
    });

    it('화면의 두 슬롯 slug 는 레지스트리와 같고 정확 일치로만 행을 고른다', () => {
        [AUTUMN, WEEKEND].forEach((slug) => {
            expect(registryConfig).toContain(`'slug' => '${slug}'`);
            expect(JSON.stringify(layout)).toContain(`p.slug === '${slug}'`);
        });
        expect(JSON.stringify(layout)).not.toMatch(/startsWith|includes\(/);
    });

    it('쓰기는 native 발행 API 하나뿐이고 abilities.can_update 로 게이트한다', () => {
        const calls = collect((n) => n.handler === 'apiCall');
        expect(calls).toHaveLength(4);
        calls.forEach((c) => {
            expect(c.target).toMatch(/^\/api\/modules\/sirsoft-page\/admin\/pages\/\{\{.*\?\.id\}\}\/publish$/);
            expect(c.params.method).toBe('PATCH');
            expect(Object.keys(c.params.body)).toEqual(['published']);
            expect(c.onError).toBeDefined();
        });
        collect((n) => n.props?.['data-testid'] === 'campaign-slot-publish' || n.props?.['data-testid'] === 'campaign-slot-unpublish')
            .forEach((b) => expect(b.if).toContain('abilities?.can_update === true'));
    });

    it('생성은 slug 를 미리 채운 native 생성 폼, 편집·상세는 native 숫자 ID 화면으로 이동한다', () => {
        const navs = collect((n) => n.handler === 'navigate');
        const creates = navs.filter((n) => n.params.path === '/admin/pages/create');
        expect(creates.map((n) => n.params.query.slug).sort()).toEqual([AUTUMN, WEEKEND]);
        creates.forEach((n) => expect(n.comment).toContain('audit:allow layout-list-context-navigate-merge-query'));
        expect(navs.some((n) => /^\/admin\/pages\/\{\{.*\?\.id\}\}\/edit$/.test(n.params.path))).toBe(true);
        expect(navs.some((n) => /^\/admin\/pages\/\{\{.*\?\.id\}\}$/.test(n.params.path))).toBe(true);
    });

    it('레이아웃이 참조하는 다국어 키가 ko/en 모두에 있다', () => {
        const keys = new Set<string>();
        walk(layout, (n) => Object.values(n).forEach((v) => {
            if (typeof v === 'string') for (const m of v.matchAll(/\$t:raonslab-travel_lab\.admin\.([a-zA-Z0-9_.\-]+)/g)) keys.add(m[1].replace(/\.$/, ''));
        }));
        expect(keys.size).toBeGreaterThan(15);
        keys.forEach((k) => {
            expect(lookup(ko, k), `ko:${k}`).toBeDefined();
            expect(lookup(en, k), `en:${k}`).toBeDefined();
        });
    });
});

describe('기획전 관리자 어댑터 렌더', () => {
    let utils: ReturnType<typeof createLayoutTest> | null = null;
    afterEach(() => {
        utils?.cleanup();
        utils = null;
    });
    const render = async (data: any) => {
        utils = createLayoutTest(layout, { componentRegistry: registry() as never, locale: 'ko', translations: { 'raonslab-travel_lab': { admin: ko } } as never, initialData: { campaign_pages: data } });
        utils.mockApi('campaign_pages', { response: data });
        await utils.render();
    };
    const slot = (key: string) => document.querySelector(`[data-testid="campaign-slot-${key}"]`) as HTMLElement;

    it('정확한 두 slug 행만 쓰고 부분일치 다른 행은 무시한다', async () => {
        await render(response([row(AUTUMN, { published: false, current_version: 7 }), row('x-travel-lab-campaign-decoy', { id: 99 })]));
        const autumn = slot('autumn-escape');
        expect(autumn.querySelector('[data-testid="campaign-slot-title"]')?.textContent).toBe(`제목 ${AUTUMN}`);
        expect(autumn.querySelector('[data-testid="campaign-slot-version"]')?.textContent).toContain('7');
        expect(autumn.querySelector('[data-testid="campaign-slot-publish"]')).not.toBeNull();
        expect(autumn.querySelector('[data-testid="campaign-slot-unpublish"]')).toBeNull();
        expect(autumn.querySelector('[data-testid="campaign-slot-edit"]')).not.toBeNull();
        const weekend = slot('weekend-reset');
        expect(weekend.querySelector('[data-testid="campaign-slot-missing"]')).not.toBeNull();
        expect(weekend.querySelector('[data-testid="campaign-slot-create-hint"]')).not.toBeNull();
        expect(weekend.querySelector('[data-testid="campaign-slot-create"]')).not.toBeNull();
        expect(document.body.textContent).not.toContain('decoy');
    });

    it('행 수정 권한이 없으면 편집·발행 버튼은 숨기고 상세·버전과 안내는 유지한다', async () => {
        await render(response([row(AUTUMN, { abilities: { can_update: false } }), row(WEEKEND, { abilities: { can_update: false } })]));
        expect(document.querySelector('[data-testid="campaign-slot-publish"],[data-testid="campaign-slot-unpublish"]')).toBeNull();
        expect(document.querySelectorAll('[data-testid="campaign-slot-no-update"]')).toHaveLength(2);
        expect(document.querySelectorAll('[data-testid="campaign-slot-edit"]')).toHaveLength(0);
        expect(document.querySelectorAll('[data-testid="campaign-slot-detail"]')).toHaveLength(2);
    });

    it('collection 생성 권한이 false 이면 없는 슬롯의 생성 버튼을 숨긴다', async () => {
        await render(response([], {}, { can_create: false, can_update: true }));
        expect(document.querySelectorAll('[data-testid="campaign-slot-create"]')).toHaveLength(0);
        expect(document.querySelectorAll('[data-testid="campaign-slot-missing"]')).toHaveLength(2);
        expect(document.body.textContent).toContain(ko.campaigns.missing_desc);
    });

    it('collection 생성 권한이 true 이면 두 빈 슬롯의 native 생성 이동을 제공한다', async () => {
        await render(response([], {}, { can_create: true, can_update: false }));
        expect(document.querySelectorAll('[data-testid="campaign-slot-create"]')).toHaveLength(2);
        expect(document.querySelectorAll('[data-testid="campaign-slot-edit"]')).toHaveLength(0);
    });

    it('collection 생성 권한이 없으면 행 can_create 로 생성 권한을 대신하지 않는다', async () => {
        await render(response([row(AUTUMN, { abilities: { can_create: true, can_update: true } })], {}, {}));
        expect(document.querySelectorAll('[data-testid="campaign-slot-create"]')).toHaveLength(0);
        expect(document.querySelectorAll('[data-testid="campaign-slot-edit"]')).toHaveLength(1);
        expect(document.querySelectorAll('[data-testid="campaign-slot-detail"]')).toHaveLength(1);
        expect(slot('weekend-reset').querySelector('[data-testid="campaign-slot-missing"]')).not.toBeNull();
    });

    it('결과가 여러 페이지이고 슬롯이 보이지 않으면 다음 결과 페이지를 안내한다', async () => {
        await render(response([row('x-travel-lab-campaign-decoy', { id: 99 })], { last_page: 2, total: 120 }));
        expect(document.querySelector('[data-testid="campaign-pages-more"]')).not.toBeNull();
        expect(document.querySelectorAll('[data-testid="campaign-slot-missing"]')).toHaveLength(2);
    });
});
