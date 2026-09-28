import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const root = resolve(__dirname, '..');
const layout = JSON.parse(readFileSync(resolve(root, 'extensions/home-product.json'), 'utf8'));
const ko = JSON.parse(readFileSync(resolve(root, 'lang/ko.json'), 'utf8'));
const en = JSON.parse(readFileSync(resolve(root, 'lang/en.json'), 'utf8'));

type Node = { id?: string; name?: string; text?: string; props?: Record<string, unknown>; children?: Node[]; actions?: Array<{ handler: string; params?: { path?: string } }> };

const byId = (id: string): Node | undefined => nodes.find((n) => n.id === id);
const classOf = (n?: Node): string => String(n?.props?.className ?? '');
const childrenWith = (n: Node | undefined, cls: string): Node[] => (n?.children ?? []).filter((c) => classOf(c).split(' ').includes(cls));
const textsUnder = (n?: Node): string[] => { const out: string[] = []; if (n) walk(n, (x) => { if (x.text) out.push(x.text); }); return out; };

function walk(node: Node, visit: (n: Node) => void): void {
  visit(node);
  node.children?.forEach((child) => walk(child, visit));
}

const home: Node = layout.injections[0].components[0];
const nodes: Node[] = [];
walk(home, (n) => nodes.push(n));

function flatten(obj: Record<string, unknown>, prefix = ''): Record<string, string> {
  return Object.entries(obj).reduce<Record<string, string>>((acc, [key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key;
    if (value && typeof value === 'object') Object.assign(acc, flatten(value as Record<string, unknown>, path));
    else acc[path] = String(value);
    return acc;
  }, {});
}

describe('사업 홈 레이아웃 확장', () => {
  it('홈 콘텐츠 호스트만 교체한다', () => {
    expect(layout.target_layout).toBe('home');
    expect(layout.injections).toHaveLength(1);
    expect(layout.injections[0]).toMatchObject({ target_id: 'main_content', position: 'replace' });
  });

  it('hero → 문제 → 서비스 → 사례 → 절차 → 기술 → 상담 순서로 구성된다', () => {
    const sectionIds = (home.children ?? []).map((child) => child.props?.id).filter(Boolean);
    expect(sectionIds).toEqual(['rh-hero', 'rh-problem', 'rh-services', 'rh-cases', 'rh-process', 'rh-tech', 'rh-consult']);
  });

  it('IA 바로가기는 실제 섹션을 가리킨다(빈 메뉴 없음)', () => {
    const jumps = nodes.filter((n) => n.props?.['data-rh-jump']).map((n) => n.props?.href);
    expect(new Set(jumps)).toEqual(new Set(['#rh-services', '#rh-cases', '#rh-process', '#rh-tech', '#rh-consult']));
    const ids = new Set(nodes.map((n) => n.props?.id));
    for (const href of jumps) expect(ids.has(String(href).slice(1))).toBe(true);
  });

  it('주 CTA 는 상담, 보조 CTA 는 사례로 이동한다', () => {
    const primary = nodes.find((n) => n.id === 'raon_home_cta_primary');
    const secondary = nodes.find((n) => n.id === 'raon_home_cta_secondary');
    expect(primary?.props?.href).toBe('#rh-consult');
    expect(secondary?.props?.href).toBe('#rh-cases');
    expect(ko.home.cta_primary).toBe('AI 에이전트 구축 상담');
    expect(ko.home.cta_secondary).toBe('자체 구현 사례 보기');
  });

  it('등록된 기본 컴포넌트만 사용한다(미등록 컴포넌트는 렌더되지 않는다)', () => {
    const manifest = JSON.parse(readFileSync(resolve(root, '../../../../templates/_bundled/sirsoft-basic/components.json'), 'utf8'));
    const registered = new Set((manifest.components.basic as Array<{ name: string }>).map((c) => c.name));
    const unknown = nodes.map((n) => n.name).filter((name) => !registered.has(String(name)));
    expect(unknown).toEqual([]);
  });

  it('컴포넌트 id 가 고유하다', () => {
    const ids = nodes.map((n) => n.id);
    expect(new Set(ids).size).toBe(ids.length);
  });

  it('상담 양식 호스트는 자식 없이 선언된다(모듈 에셋이 소유)', () => {
    const host = nodes.find((n) => n.props?.['data-rh-consult']);
    expect(host).toBeDefined();
    expect(host?.children).toBeUndefined();
    expect(host?.text).toBeUndefined();
  });

  it('기존 Community·검색·AI 작업공간 이동을 보존한다', () => {
    const paths = nodes.flatMap((n) => n.actions ?? []).filter((a) => a.handler === 'navigate').map((a) => a.params?.path);
    expect(paths).toEqual(expect.arrayContaining(['/board/community', '/board/notice', '/board/questions', '/search', '/ai']));
    const ai = nodes.find((n) => n.actions?.some((a) => a.params?.path === '/ai'));
    expect((ai as Record<string, unknown>)?.if).toBe('{{_global.currentUser?.uuid}}');
  });

  it('사용된 모든 번역 키가 ko/en 양쪽에 있다', () => {
    const koFlat = flatten(ko);
    const enFlat = flatten(en);
    const used = JSON.stringify(layout).match(/\$t:raonslab-product\.[\w.]+/g) ?? [];
    for (const ref of used) {
      const key = ref.replace('$t:raonslab-product.', '');
      expect(koFlat[key], key).toBeTruthy();
      expect(enFlat[key], key).toBeTruthy();
    }
    expect(Object.keys(koFlat).sort()).toEqual(Object.keys(enFlat).sort());
  });
});

describe('시각 구조', () => {
  const key = (k: string) => `$t:raonslab-product.home.${k}`;

  it('hero 흐름 도식은 업무 입력 → 에이전트 실행 → 검증 → 운영 결과 노드 파이프라인이다', () => {
    const flow = byId('raon_home_flow');
    expect(flow?.props).toMatchObject({ role: 'group', 'aria-labelledby': 'rh-flow-label' });
    const steps = childrenWith(byId('raon_home_flow_list'), 'rh-flow-step');
    const stages = steps.map((step) => textsUnder(step).find((t) => t.includes('visual_stage_')));
    expect(stages).toEqual(['input', 'run', 'verify', 'result'].map((s) => key(`visual_stage_${s}`)));
    const glyphs = steps.map((step) => classOf(childrenWith(step, 'rh-flow-node')[0]?.children?.[0]));
    expect(glyphs).toEqual(['input', 'run', 'verify', 'result'].map((s) => `rh-glyph rh-glyph-${s}`));
    for (const step of steps) {
      expect(childrenWith(step, 'rh-flow-node')[0]?.props?.['aria-hidden']).toBe('true');
      expect(textsUnder(step).some((t) => /flow_\w+_copy$/.test(t))).toBe(true);
    }
  });

  it('상태 레인은 통과만 운영 결과까지 가고, 미검증은 멈추며, 실패는 되돌림 루프로 돌아간다', () => {
    const rows = byId('raon_home_flow_rows');
    expect(rows?.props?.['aria-hidden']).toBe('true');
    const marks = childrenWith(rows, 'rh-run-row').map((row) => (row.children ?? []).map((m) => classOf(m).replace('rh-mark rh-mark-', '')));
    expect(marks).toEqual([
      ['done', 'done', 'pass', 'out'],
      ['done', 'done', 'open', 'none'],
      ['done', 'done', 'fail', 'none'],
    ]);
    expect(childrenWith(rows, 'rh-flow-loop')).toHaveLength(1);
    const legend = textsUnder(byId('raon_home_flow_legend'));
    expect(legend).toEqual(['pass', 'fail', 'open'].map((s) => key(`visual_status_${s}`)));
    // 도식에는 숫자 문구가 없다(근거 없는 수치 금지)
    expect(textsUnder(byId('raon_home_flow')).filter((t) => !t.startsWith('$t:'))).toEqual([]);
  });

  it('hero 는 copy 가 먼저, 흐름 도식이 뒤에 온다(작은 화면 첫 화면 순서)', () => {
    const grid = byId('raon_home_hero_inner');
    expect(grid?.children?.map((c) => c.id)).toEqual(['raon_home_hero_copy', 'raon_home_flow']);
  });

  it('아이콘은 모두 장식으로 숨기고 FontAwesome solid 이름만 쓴다', () => {
    const icons = nodes.filter((n) => n.name === 'Icon');
    expect(icons.length).toBeGreaterThanOrEqual(20);
    for (const icon of icons) {
      expect(icon.props?.['aria-hidden'], icon.id).toBe('true');
      expect(String(icon.props?.name)).toMatch(/^fa-[a-z-]+$/);
      expect(classOf(icon)).not.toMatch(/\bw-\d|\bh-\d/);
    }
  });

  it('서비스는 실증 → 구축 → 운영 단계 트랙이며 범위 표시가 1→3 으로 넓어진다', () => {
    const stages = childrenWith(byId('raon_home_services_list'), 'rh-stage');
    expect(stages.map((s) => s.id)).toEqual(['raon_home_service_pilot', 'raon_home_service_build', 'raon_home_service_operate']);
    stages.forEach((stage, index) => {
      const rail = childrenWith(stage, 'rh-stage-rail')[0];
      expect(rail?.props?.['aria-hidden']).toBe('true');
      const scope = nodes.find((n) => n.id === `${stage.id}_scope`);
      expect(classOf(scope)).toContain(`rh-scope-${index + 1}`);
      expect(scope?.props?.['aria-hidden']).toBe('true');
    });
  });

  it.each(['stock', 'hub'])('사례 %s 는 문제·구현·검증·한계 근거 레인을 순서대로 가진다', (name) => {
    const lanes = childrenWith(byId(`raon_home_case_${name}_evidence`), 'rh-lane');
    expect(lanes.map((l) => classOf(l).split(' ')[1])).toEqual(['rh-lane-problem', 'rh-lane-build', 'rh-lane-verified', 'rh-lane-limit']);
    const texts = textsUnder(byId(`raon_home_case_${name}`));
    for (const field of ['summary', 'problem', 'scope', 'flow', 'verified', 'unverified', 'fit']) {
      expect(texts, field).toContain(key(`case_${name}_${field}`));
    }
    for (const lane of lanes) expect(lane.children?.[0]?.children?.[0]?.props?.['aria-hidden'], lane.id).toBe('true');
    expect(textsUnder(lanes[2])).toContain(key(`case_${name}_verified`));
    expect(textsUnder(lanes[3])).toContain(key(`case_${name}_unverified`));
  });

  it('도입 절차 타임라인은 5단계이고 번호 표지는 장식이다', () => {
    const steps = childrenWith(byId('raon_home_process_list'), 'rh-process-step');
    expect(steps).toHaveLength(5);
    for (const step of steps) expect(childrenWith(step, 'rh-process-node')[0]?.props?.['aria-hidden']).toBe('true');
  });

  it('새 문구는 시각 전용 단계 이름 키만 추가한다', () => {
    const visualKeys = Object.keys(ko.home).filter((k) => k.startsWith('visual_'));
    expect(visualKeys.sort()).toEqual([
      'visual_stage_input', 'visual_stage_result', 'visual_stage_run', 'visual_stage_verify',
      'visual_status_fail', 'visual_status_open', 'visual_status_pass',
    ]);
  });
});

describe('사용자 문구 정책', () => {
  const copies = [flatten(ko), flatten(en)].flatMap((dict) => Object.values(dict));

  it('개발 용어를 노출하지 않는다', () => {
    for (const copy of copies) expect(copy.toUpperCase()).not.toMatch(/\b(DEMO|MOCK|SANDBOX|TEST)\b/);
  });

  it('가격·기간·절감률·매출·추천사 표현을 쓰지 않는다', () => {
    for (const copy of copies) {
      expect(copy).not.toMatch(/\d+\s*%|₩|\$\s?\d|만원|억원|원\/|개월|주 만에|weeks?\b|months?\b|ROI|매출|절감|revenue|testimonial|추천사/i);
    }
  });

  it('MOBILE_STOCK 은 실거래·수익·고객 납품을 주장하지 않는다', () => {
    expect(ko.home.case_stock_unverified).toMatch(/실제 증권 거래/);
    expect(`${ko.home.case_stock_summary}${ko.home.case_stock_verified}`).not.toMatch(/수익률|실거래 지원|고객사/);
  });

  it('G7 사례는 검증 근거와 미검증 범위를 함께 적는다', () => {
    for (const token of ['J1~J10 PASS', 'A1~A10 PASS', '360/390/412', '후속 지시 PASS', '코어 수정 0건']) {
      expect(ko.home.case_hub_verified).toContain(token);
    }
    expect(ko.home.case_hub_unverified).toMatch(/상용 트래픽/);
  });
});
