/**
 * @file admin-travel-lab-layouts.test.tsx
 * @description 여행 연구소 관리자 레이아웃(카탈로그/출발일, 문의 목록·상세, 고객지원 허브) 계약 + 렌더 검증
 *
 * @scenario case=admin_layout_contract|admin_render
 * @effects admin_permissions_declared|list_context_preserved|server_owned_transitions|native_board_admin_links|no_price_editing
 *
 * @vitest-environment jsdom
 */

import { describe, it, expect, afterEach } from 'vitest';
import fs from 'fs';
import path from 'path';
import React from 'react';

import {
    createLayoutTest,
    createMockComponentRegistryWithBasics,
    screen,
} from '@core/template-engine/__tests__/utils/layoutTestUtils';

const ROOT = path.resolve(__dirname, '../../../');
const LAYOUTS = path.join(ROOT, 'layouts/admin');
const API = '/api/modules/raonslab-travel_lab';

const read = (rel: string): any => JSON.parse(fs.readFileSync(path.join(LAYOUTS, rel), 'utf8'));
const FILES = {
    catalog: 'admin_travel_lab_catalog.json',
    list: 'admin_travel_lab_inquiry_list.json',
    detail: 'admin_travel_lab_inquiry_detail.json',
    support: 'admin_travel_lab_support.json',
};
const ko = JSON.parse(fs.readFileSync(path.join(ROOT, 'lang/partial/ko/admin.json'), 'utf8'));
const en = JSON.parse(fs.readFileSync(path.join(ROOT, 'lang/partial/en/admin.json'), 'utf8'));
const routes = JSON.parse(fs.readFileSync(path.join(ROOT, 'routes.json'), 'utf8'));

/** 기본 컴포넌트 화이트리스트 (HTML 태그 직접 사용 금지 규칙) */
const ALLOWED = new Set(['Div', 'Span', 'P', 'H1', 'H2', 'Button', 'Icon', 'Label', 'Input', 'Select', 'Textarea', 'DataGrid', 'Modal']);

function walk(node: unknown, visit: (n: any) => void): void {
    if (Array.isArray(node)) {
        node.forEach((c) => walk(c, visit));
        return;
    }
    if (!node || typeof node !== 'object') return;
    visit(node);
    Object.values(node as Record<string, unknown>).forEach((v) => walk(v, visit));
}

function collect(json: unknown, pred: (n: any) => boolean): any[] {
    const out: any[] = [];
    walk(json, (n) => pred(n) && out.push(n));
    return out;
}

function lookup(dict: any, key: string): unknown {
    return key.split('.').reduce((d: any, p) => (d && typeof d === 'object' ? d[p] : undefined), dict);
}

describe('여행 연구소 관리자 레이아웃 계약', () => {
    it.each(Object.entries(FILES))('%s: 권한 선언·_admin_base 상속·기본 컴포넌트만 사용', (key, file) => {
        const json = read(file);
        const expected: Record<string, string> = {
            catalog: 'raonslab-travel_lab.catalog.read',
            list: 'raonslab-travel_lab.inquiries.read',
            detail: 'raonslab-travel_lab.inquiries.read',
            support: 'raonslab-travel_lab.support.read',
        };
        expect(json.permissions).toEqual([expected[key]]);
        expect(json.extends).toBe('_admin_base');
        const names = collect(json, (n) => typeof n.name === 'string' && (n.type === 'basic' || n.type === 'composite')).map((n) => n.name);
        expect(names.filter((n) => !ALLOWED.has(n))).toEqual([]);
    });

    it('모든 번역 키가 ko/en 양쪽에 존재한다', () => {
        const text = Object.values(FILES).map((f) => fs.readFileSync(path.join(LAYOUTS, f), 'utf8')).join('\n');
        const keys = [...new Set([...text.matchAll(/raonslab-travel_lab\.admin\.([A-Za-z0-9_.]+)/g)].map((m) => m[1]))];
        expect(keys.length).toBeGreaterThan(20);
        expect(keys.filter((k) => typeof lookup(ko, k) !== 'string')).toEqual([]);
        expect(keys.filter((k) => typeof lookup(en, k) !== 'string')).toEqual([]);
    });

    it('Icon 은 w-N h-N 이 아니라 text-* 크기를 쓴다', () => {
        const icons = Object.values(FILES).flatMap((f) => collect(read(f), (n) => n.name === 'Icon'));
        expect(icons.length).toBeGreaterThan(0);
        icons.forEach((i) => expect(String(i.props?.className ?? '')).not.toMatch(/\bw-\d/));
    });

    it('데이터소스와 쓰기 경로는 모듈 API prefix 의 문서화된 엔드포인트만 쓴다', () => {
        expect(read(FILES.catalog).data_sources[0].endpoint).toBe(`${API}/admin/catalog`);
        expect(read(FILES.list).data_sources[0].endpoint).toBe(`${API}/admin/inquiries`);
        expect(read(FILES.detail).data_sources[0].endpoint).toBe(`${API}/admin/inquiries/{{route?.id}}`);

        const departurePatch = collect(read(FILES.catalog), (n) => n.handler === 'apiCall');
        expect(departurePatch).toHaveLength(1);
        expect(departurePatch[0].target).toBe(`${API}/admin/departures/{{_global.travelDepartureEdit?.id}}`);
        expect(departurePatch[0].params.method).toBe('PATCH');
        // 가격은 이커머스 소유 — 출발일 PATCH 본문에 가격 필드가 없다
        expect(Object.keys(departurePatch[0].params.body).sort()).toEqual(['capacity', 'status']);

        const inquiryPatch = collect(read(FILES.detail), (n) => n.handler === 'apiCall');
        expect(inquiryPatch).toHaveLength(1);
        expect(inquiryPatch[0].target).toBe(`${API}/admin/inquiries/{{route.id}}`);
        expect(inquiryPatch[0].params.method).toBe('PATCH');
        expect(Object.keys(inquiryPatch[0].params.body).sort()).toEqual(['admin_note', 'status']);
        [...departurePatch, ...inquiryPatch].forEach((a) => {
            expect(a.onError.some((x: any) => x.handler === 'toast')).toBe(true);
            expect(a.onSuccess.some((x: any) => x.handler === 'toast')).toBe(true);
        });
    });

    it('상태 전환 버튼은 서버가 준 allowed_transitions 만 반복한다 (클라이언트 전이표 없음)', () => {
        const buttons = collect(read(FILES.detail), (n) => n.id === 'transition_button');
        expect(buttons).toHaveLength(1);
        expect(buttons[0].iteration.source).toBe('{{inquiry?.data?.allowed_transitions ?? []}}');
    });

    it('문의 목록 클러스터 navigate 는 목록 상태를 보존한다 (mergeQuery: true)', () => {
        const navs = [FILES.list, FILES.detail].flatMap((f) =>
            collect(read(f), (n) => n.handler === 'navigate' && typeof n.params?.path === 'string'
                && n.params.path.startsWith('/admin/travel-lab/inquiries')
                && !String(n.comment ?? '').includes('audit:allow')),
        );
        expect(navs.length).toBeGreaterThanOrEqual(3);
        navs.forEach((n) => expect(n.params.mergeQuery).toBe(true));
        // 의도적 비병합 이동에는 audit:allow 사유가 남아 있다
        const resets = Object.values(FILES).flatMap((f) => collect(read(f), (n) => n.handler === 'navigate' && n.params?.mergeQuery === false));
        resets.forEach((n) => expect(String(n.comment ?? '')).toContain('audit:allow layout-list-context-navigate-merge-query'));
    });

    it('고객지원 허브는 그누보드7 게시판 관리자 화면으로 연결하고 상품은 이커머스 관리로 연결한다', () => {
        const paths = collect(read(FILES.support), (n) => n.handler === 'navigate').map((n) => n.params.path);
        expect(paths).toEqual(expect.arrayContaining([
            '/admin/board/travel-lab-notices', '/admin/board/travel-lab-faqs', '/admin/board/travel-lab-questions',
        ]));
        const product = collect(read(FILES.catalog), (n) => n.handler === 'navigate' && String(n.params?.path).startsWith('/admin/ecommerce/'));
        expect(product[0].params.path).toBe('/admin/ecommerce/products/{{trip.product_code}}/edit');
    });

    it('routes.json 이 관리자 경로를 레이아웃에 연결한다', () => {
        const map = Object.fromEntries(routes.routes.map((r: any) => [r.path, r.layout]));
        expect(map).toMatchObject({
            '*/admin/travel-lab': 'admin_travel_lab_catalog',
            '*/admin/travel-lab/inquiries': 'admin_travel_lab_inquiry_list',
            '*/admin/travel-lab/inquiries/:id': 'admin_travel_lab_inquiry_detail',
            '*/admin/travel-lab/support': 'admin_travel_lab_support',
        });
        routes.routes.forEach((r: any) => {
            expect(r.auth_required).toBe(true);
            expect(fs.existsSync(path.join(LAYOUTS, `${r.layout}.json`))).toBe(true);
        });
    });
});

function registry() {
    const reg = createMockComponentRegistryWithBasics();
    reg.register('basic', 'Icon', ({ name }: any) => React.createElement('i', { 'data-icon': name }));
    reg.register('composite', 'DataGrid', ({ data }: any) =>
        React.createElement('div', { 'data-testid': 'datagrid' }, (data ?? []).map((r: any) => React.createElement('div', { key: r.id }, r.reference))),
    );
    return reg;
}

describe('여행 연구소 관리자 레이아웃 렌더', () => {
    let utils: ReturnType<typeof createLayoutTest> | null = null;
    afterEach(() => {
        utils?.cleanup();
        utils = null;
        window.history.replaceState({}, '', '/');
    });

    it('문의 상세는 서버가 허용한 다음 상태 버튼만 렌더한다', async () => {
        utils = createLayoutTest(read(FILES.detail), {
            componentRegistry: registry() as never,
            routeParams: { id: '7' },
            locale: 'ko',
            initialData: {
                inquiry: { data: {
                    id: 7, reference: 'LAB-0007', status: 'TEST_INQUIRY', product_name: '합성 제주 3일',
                    departure_label: '2026-11-01', party_size: 2, message: '합성 문의',
                    allowed_transitions: ['UNDER_REVIEW', 'DECLINED'], abilities: { can_update: true },
                } },
            },
        });
        utils.mockApi('inquiry', { response: { data: {
            id: 7, reference: 'LAB-0007', status: 'TEST_INQUIRY', allowed_transitions: ['UNDER_REVIEW', 'DECLINED'], abilities: { can_update: true },
        } } });
        await utils.render();

        const buttons = Array.from(document.querySelectorAll('#transition_buttons button')).map((b) => b.textContent ?? '');
        expect(buttons).toHaveLength(2);
        expect(buttons[0]).toMatch(/UNDER_REVIEW|검토 중/);
        expect(buttons[1]).toMatch(/DECLINED|거절/);
        expect(buttons.join(' ')).not.toMatch(/TEST_ACCEPTED|테스트 수락/);
        expect(document.querySelector('#inquiry_save_button')?.hasAttribute('disabled')).toBe(false);
    });

    it('목록 상세 이동이 현재 목록 쿼리를 상세 URL 로 나른다', async () => {
        window.history.replaceState({}, '', '/admin/travel-lab/inquiries?page=3&status=UNDER_REVIEW');
        utils = createLayoutTest(
            { version: '1.0.0', layout_name: 'probe', data_sources: [], components: [{ type: 'basic', name: 'Div' }] } as never,
            { componentRegistry: registry() as never, locale: 'ko' },
        );
        await utils.render();
        const grid = collect(read(FILES.list), (n) => n.id === 'inquiries_data_grid')[0];
        const view = grid.actions.find((a: any) => a.event === 'onRowAction').cases.view;
        await utils.triggerAction({ type: 'click', ...view, params: { ...view.params, path: '/admin/travel-lab/inquiries/7' } } as never);
        const dest = utils.getNavigationHistory()[0];
        const q = new URLSearchParams(dest.slice(dest.indexOf('?')));
        expect(dest.startsWith('/admin/travel-lab/inquiries/7')).toBe(true);
        expect(q.get('page')).toBe('3');
        expect(q.get('status')).toBe('UNDER_REVIEW');
    });
});
