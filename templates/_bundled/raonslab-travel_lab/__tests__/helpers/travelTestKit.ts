/**
 * RAON Travel Lab 테스트 공용 도구
 *
 * - 실제 템플릿 컴포넌트를 ComponentRegistry 에 등록 (테스트 전용 대역이 아니라 출하 컴포넌트로 렌더)
 * - extends 를 풀지 않는 createLayoutTest 를 위해 slots.content 를 components 로 평탄화
 * - 레이아웃 JSON 트리 순회 유틸
 */
import React from 'react';
import fs from 'fs';
import path from 'path';
import { ComponentRegistry } from '@core/template-engine/ComponentRegistry';
import * as Template from '../../src/index';
import componentsManifest from '../../components.json';
import ko from '../../lang/ko.json';

export const TEMPLATE_ROOT = path.resolve(__dirname, '../..');
export const LAYOUT_ROOT = path.join(TEMPLATE_ROOT, 'layouts');
export const API_BASE = '/api/modules/raonslab-travel_lab';
export const translations = ko as Record<string, any>;

export function registerTemplateComponents(): ComponentRegistry {
  const registry = ComponentRegistry.getInstance();
  const map: Record<string, any> = {};
  (['basic', 'composite', 'layout'] as const).forEach((type) => {
    ((componentsManifest as any).components[type] ?? []).forEach((meta: any) => {
      const component = (Template as any)[meta.name];
      if (component) {
        map[meta.name] = { component, metadata: { name: meta.name, type } };
      }
    });
  });
  // createLayoutTest 는 루트를 layout 타입 Fragment 로 감싼다
  map.Fragment = { component: ({ children }: { children?: React.ReactNode }) => React.createElement(React.Fragment, null, children), metadata: { name: 'Fragment', type: 'layout' } };
  (registry as any).registry = map;
  return registry;
}

export function loadLayout(rel: string): any {
  return JSON.parse(fs.readFileSync(path.join(LAYOUT_ROOT, rel), 'utf-8'));
}

/** extends 레이아웃의 content 슬롯을 단독 렌더용으로 평탄화 */
export function flatten(layout: any): any {
  return {
    version: layout.version,
    layout_name: layout.layout_name,
    meta: layout.meta,
    data_sources: layout.data_sources,
    initLocal: layout.initLocal,
    init_actions: layout.init_actions,
    components: layout.slots?.content ?? layout.components ?? [],
  };
}

export function listLayoutFiles(dir = LAYOUT_ROOT): string[] {
  const out: string[] = [];
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) out.push(...listLayoutFiles(full));
    else if (entry.name.endsWith('.json')) out.push(full);
  }
  return out.sort();
}

/** 모든 객체 노드를 방문 */
export function walk(node: any, visit: (n: any, parentKey?: string) => void, parentKey?: string): void {
  if (Array.isArray(node)) {
    node.forEach((child) => walk(child, visit, parentKey));
    return;
  }
  if (node && typeof node === 'object') {
    visit(node, parentKey);
    Object.entries(node).forEach(([k, v]) => walk(v, visit, k));
  }
}

export function findAll(node: any, predicate: (n: any) => boolean): any[] {
  const found: any[] = [];
  walk(node, (n) => {
    if (predicate(n)) found.push(n);
  });
  return found;
}

export function hasLangKey(dict: Record<string, any>, key: string): boolean {
  return key.split('.').reduce<any>((cur, part) => (cur && typeof cur === 'object' ? cur[part] : undefined), dict) !== undefined;
}
