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

/** 검증된 RAON Hub 사례 섹션 주소. */
const CASE_HREF = '#rh-cases';

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

  it('hero → RAON Hub 증거 → 구매 범위 → 문제 → 사례 → 절차 → 기술 → 상담 순서로 구성된다', () => {
    const sectionIds = (home.children ?? []).map((child) => child.props?.id).filter(Boolean);
    expect(sectionIds).toEqual(['rh-hero', 'rh-proof', 'rh-services', 'rh-problem', 'rh-cases', 'rh-process', 'rh-tech', 'rh-consult']);
  });

  it('홈 섹션 바로가기 바를 두지 않고, 홈 안의 이동 링크는 실제 섹션을 가리킨다', () => {
    expect(nodes.some((n) => classOf(n).includes('rh-subnav'))).toBe(false);
    const jumps = nodes.filter((n) => n.props?.['data-rh-jump']);
    expect(jumps.length).toBeGreaterThan(0);
    const ids = new Set(nodes.map((n) => n.props?.id));
    for (const link of jumps) {
      expect(link.props?.href).toBe(`#rh-${link.props?.['data-rh-jump']}`);
      expect(ids.has(String(link.props?.href).slice(1)), String(link.props?.href)).toBe(true);
    }
  });

  it('접수 전(기본)에는 사례·도입 절차가, 접수 열림에서만 상담이 주 행동이다', () => {
    const closed = byId('raon_home_actions_closed');
    const open = byId('raon_home_actions_open');
    expect(closed?.props?.['data-rh-intake-show']).toBe('closed');
    expect(open?.props?.['data-rh-intake-show']).toBe('open');
    const hrefs = (n?: Node) => (n?.children ?? []).map((c) => [classOf(c).includes('rh-action-primary') ? 'primary' : 'secondary', c.props?.href]);
    expect(hrefs(closed)).toEqual([['primary', CASE_HREF], ['secondary', '#rh-process']]);
    expect(hrefs(open)).toEqual([['primary', '#rh-consult'], ['secondary', CASE_HREF]]);
    expect(ko.home.cta_case).toBe('검증된 RAON Hub 사례 보기');
    expect(ko.home.cta_process).toBe('도입 절차 보기');
    // 닫힌 상태에서 상담(준비 상태 확인 포함)을 고객 행동으로 내세우지 않는다
    const closedCopy = textsUnder(closed).map((ref) => ko.home[ref.replace('$t:raonslab-product.home.', '')]).join(' ');
    expect(closedCopy).not.toMatch(/상담|준비 상태/);
  });

  it('Hero 제목은 승인된 문장 그대로이고, 보조 문장은 한 번만 쓴다', () => {
    expect(ko.home.title).toBe('기업 업무에 맞는 AI 에이전트, 구축부터 실행·검증·운영까지.');
    expect(ko.home.lede).toBe('반복 업무 하나를, 기존 시스템에서 실제로 실행되는 AI 흐름으로.');
    expect(JSON.stringify(layout).match(/home\.lede\b/g)).toHaveLength(1);
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

  it('hero 흐름 도식은 업무 입력 → 제한된 실행 → 검증 → 결과 4단계뿐이다', () => {
    const flow = byId('raon_home_flow');
    expect(flow?.props).toMatchObject({ role: 'group', 'aria-labelledby': 'rh-flow-label' });
    const steps = childrenWith(byId('raon_home_flow_list'), 'rh-flow-step');
    expect(steps.map((step) => textsUnder(step)[0])).toEqual(['input', 'run', 'verify', 'result'].map((s) => key(`visual_stage_${s}`)));
    expect(['input', 'run', 'verify', 'result'].map((s) => ko.home[`visual_stage_${s}`])).toEqual(['업무 입력', '제한된 실행', '검증', '결과']);
    for (const step of steps) {
      expect(childrenWith(step, 'rh-flow-node')[0]?.props?.['aria-hidden']).toBe('true');
      expect(textsUnder(step)).toHaveLength(2);
    }
    // 진행률 막대·상태 점·창 장식·숫자 문구가 없다
    const classes = nodes.filter((n) => n.id?.startsWith('raon_home_flow')).map(classOf).join(' ');
    expect(classes).not.toMatch(/rh-(run-row|mark|legend|scope|log|flow-bar|flow-marks)/);
    expect(textsUnder(flow).filter((t) => !t.startsWith('$t:'))).toEqual([]);
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

  it('RAON Hub 증거 줄은 Hero 바로 다음이며 실제 실행·검증·확장 방식·범위 네 칸이다', () => {
    expect(home.children?.[1]?.id).toBe('raon_home_proof');
    const items = childrenWith(byId('raon_home_proof_list'), 'rh-proof-item');
    expect(items.map((item) => textsUnder(item))).toEqual(['run', 'verify', 'extend', 'scope'].map((k) => [key(`proof_${k}_label`), key(`proof_${k}_value`)]));
    expect(['run', 'verify', 'extend', 'scope'].map((k) => ko.home[`proof_${k}_label`])).toEqual(['실제 실행', '검증', '확장 방식', '범위']);
    const copy = [ko, en].flatMap((dict) => Object.entries(dict.home).filter(([k]) => k.startsWith('proof_')).map(([, v]) => String(v)));
    for (const text of copy) {
      // 원시 커밋 해시·내부 점검 코드·테스트 명령·공급자/세션 용어를 쓰지 않는다
      expect(text).not.toMatch(/\b[0-9a-f]{7,40}\b|\b[JA]\d+\b|phpunit|vitest|npm |artisan|Provider|session|세션/i);
    }
    expect(ko.home.proof_scope_value).toMatch(/미검증/);
  });

  it('구매 범위는 실증 → 구축 → 운영·개선 3행이고 각 행은 입력·결과물·고객 준비를 한 줄씩 가진다', () => {
    const rows = childrenWith(byId('raon_home_services_list'), 'rh-offer');
    expect(rows.map((row) => row.id)).toEqual(['raon_home_service_pilot', 'raon_home_service_build', 'raon_home_service_operate']);
    expect(['pilot', 'build', 'operate'].map((row) => ko.home[`service_${row}_title`])).toEqual(['업무 한 개 실증', '맞춤 Agent 구축', '운영·개선']);
    for (const row of rows) {
      const facts = childrenWith(row, 'rh-offer-fact');
      expect(facts.map((fact) => textsUnder(fact)[0])).toEqual(['input', 'output', 'prereq'].map((f) => key(`offer_label_${f}`)));
      for (const fact of facts) expect(ko.home[textsUnder(fact)[1].replace('$t:raonslab-product.home.', '')].length).toBeLessThanOrEqual(24);
    }
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

  it('흐름 도식 문구 키는 단계 이름과 한 줄 설명뿐이다', () => {
    const visualKeys = Object.keys(ko.home).filter((k) => k.startsWith('visual_'));
    expect(visualKeys.sort()).toEqual(['visual_stage_input', 'visual_stage_result', 'visual_stage_run', 'visual_stage_verify']);
  });
});

describe('2차 시각 패스 — 사례·서비스·절차의 코드 네이티브 도식', () => {
  const css = readFileSync(resolve(root, 'css/main.css'), 'utf8');
  const pass2 = css.slice(css.indexOf('/* ── 2차 시각 패스'), css.indexOf('@media (prefers-reduced-motion'));
  const literalTexts = (n?: Node) => textsUnder(n).filter((t) => !t.startsWith('$t:'));

  it('두 사례는 서로 다른 미니 화면이다: MOBILE_STOCK 은 요청 로그 터미널, RAON Hub 는 상태 스트립 + 근거 레일', () => {
    expect(classOf(byId('raon_home_case_stock'))).toContain('rh-case-log');
    expect(classOf(byId('raon_home_case_stock_evidence'))).toContain('rh-evidence-log');
    expect(classOf(byId('raon_home_case_hub'))).toContain('rh-case-rail');
    expect(classOf(byId('raon_home_case_hub_evidence'))).toContain('rh-evidence-rail');
    expect(classOf(byId('raon_home_case_hub_evidence'))).not.toContain('rh-evidence-log');

    // 창 머리·상태 스트립은 근거 레인 바로 앞에 놓이는 장식이다
    for (const [caseId, chromeId] of [['stock', 'raon_home_case_stock_bar'], ['hub', 'raon_home_case_hub_strip']]) {
      const children = (byId(`raon_home_case_${caseId}`)?.children ?? []).map((c) => c.id);
      expect(children).toEqual([`raon_home_case_${caseId}_header`, chromeId, `raon_home_case_${caseId}_evidence`]);
      expect(byId(chromeId)?.props?.['aria-hidden']).toBe('true');
    }
    const segments = (byId('raon_home_case_hub_strip_bar')?.children ?? []).map((c) => classOf(c).split(' ')[1]);
    expect(segments).toEqual(['rh-strip-seg-problem', 'rh-strip-seg-build', 'rh-strip-seg-verified', 'rh-strip-seg-limit']);
  });

  it('장식 문구는 기존 사실 문구에 있는 식별자만 쓴다(새 주장·수치 없음)', () => {
    expect(literalTexts(byId('raon_home_case_stock_bar'))).toEqual(['MOBILE_STOCK']);
    expect(literalTexts(byId('raon_home_case_hub_strip'))).toEqual(['G7 7.0.11']);
    expect(ko.home.case_stock_title).toContain('MOBILE_STOCK');
    expect(en.home.case_stock_title).toContain('MOBILE_STOCK');
    expect(ko.home.case_hub_summary).toContain('7.0.11');
    expect(en.home.case_hub_summary).toContain('7.0.11');
    for (const id of ['raon_home_services', 'raon_home_process']) expect(literalTexts(byId(id)), id).toEqual([]);
  });

  it('카드 수는 늘리지 않는다(사례 2 · 레인 4 · 구매 범위 3 · 절차 5)', () => {
    expect(childrenWith(byId('raon_home_cases_list'), 'rh-case')).toHaveLength(2);
    expect(childrenWith(byId('raon_home_services_list'), 'rh-offer')).toHaveLength(3);
    expect(childrenWith(byId('raon_home_process_list'), 'rh-process-step')).toHaveLength(5);
  });

  it('절차 노드는 단계별 아이콘을 가지고, 검증 단계는 통과·실패·미검증 분기, 마지막 단계는 반복을 표시한다', () => {
    const steps = childrenWith(byId('raon_home_process_list'), 'rh-process-step');
    const icons = steps.map((step) => childrenWith(step, 'rh-process-node')[0]?.children?.map((c) => c.props?.name));
    expect(icons).toEqual([['fa-comments'], ['fa-shield-halved'], ['fa-code'], ['fa-list-check'], ['fa-arrows-rotate']]);
    expect(classOf(steps[3])).toContain('rh-process-step-verify');
    const states = childrenWith(steps[3], 'rh-process-states')[0];
    expect(states?.props?.['aria-hidden']).toBe('true');
    expect((states?.children ?? []).map((c) => classOf(c).split(' ')[1])).toEqual(['rh-process-state-pass', 'rh-process-state-fail', 'rh-process-state-open']);
    expect(classOf(steps[4])).toContain('rh-process-step-loop');
  });


  it('새 스타일은 이미지·SVG·glow·그림자·그라디언트·애니메이션 없이 테두리와 상태 색만 쓴다', () => {
    expect(pass2.length).toBeGreaterThan(500);
    expect(pass2).not.toMatch(/gradient|box-shadow|text-shadow|filter\s*:|url\(|<svg|animation|@keyframes|transform:\s*(?:scale|translate[XYZ3]?)\b/i);
    // 색은 모두 기존 테마 토큰을 쓴다(라이트·다크 동시 대응)
    expect(pass2.replace(/\/\*[\s\S]*?\*\//g, '')).not.toMatch(/#[0-9a-f]{3,8}\b|rgba?\(/i);
    expect(css).toContain('@media (prefers-reduced-motion: reduce)');
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

describe('홈 탐색 압축과 상담 접수 상태 표시', () => {
  const css = readFileSync(resolve(root, 'css/main.css'), 'utf8');
  const nav = JSON.parse(readFileSync(resolve(root, 'extensions/product-nav.json'), 'utf8'));
  const findNav = (value: unknown, id: string): Node | null => {
    if (Array.isArray(value)) {
      for (const child of value) {
        const found = findNav(child, id);
        if (found) return found;
      }
      return null;
    }
    if (!value || typeof value !== 'object') return null;
    if ((value as Node).id === id) return value as Node;
    for (const child of Object.values(value)) {
      const found = findNav(child, id);
      if (found) return found;
    }
    return null;
  };

  it('상위 메뉴의 상담 진입점은 클릭 전에 접수 전 상태를 링크 이름 안에 표시한다', () => {
    const cta = findNav(nav, 'rh_gnav_consult');
    expect(cta?.props?.href).toBe('/#rh-consult');
    const state = cta?.children?.find((child) => child.props?.['data-rh-intake-show'] === 'closed');
    expect(state?.text).toBe('$t:raonslab-product.nav.consult_closed');
    expect(ko.nav.consult_closed).toBe('접수 준비 중');
    expect(en.nav.consult_closed).toBeTruthy();
  });

  it('상태 표시는 열림이 확인된 경우에만 닫힘 요소를 숨긴다(기본은 닫힘)', () => {
    expect(css).toContain("html:not([data-rh-intake='open']) [data-rh-intake-show='open'],\nhtml[data-rh-intake='open'] [data-rh-intake-show='closed'] {\n  display: none !important;");
  });

  it('모바일·태블릿 홈에서는 제품 바를 겹쳐 두지 않는다(드로어 문서 섹션이 같은 분류를 담는다)', () => {
    expect(css).toMatch(/@media \(max-width: 1023px\) \{\n {2}body\.raon-product:has\(\.rh-home\) \.rh-gnav \{\n {4}display: none;/);
    expect(css).not.toMatch(/\.rh-subnav/);
  });
});
