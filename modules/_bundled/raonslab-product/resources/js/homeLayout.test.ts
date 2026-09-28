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
const CASE_HREF = '#rh-case';

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

  it('7개 섹션: Hero → RAON Hub 증거 → 구매 범위 → 적합성·차이 → 사례 → 절차·신뢰 → 상담', () => {
    const sectionIds = (home.children ?? []).map((child) => child.props?.id).filter(Boolean);
    expect(sectionIds).toEqual(['rh-hero', 'rh-proof', 'rh-services', 'rh-fit', 'rh-case', 'rh-process', 'rh-consult']);
    expect(home.children).toHaveLength(7);
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

  it('커뮤니티 진입과 로그인 사용자의 AI 작업공간 진입을 보존한다(공지·질문·검색은 푸터가 담는다)', () => {
    const paths = nodes.flatMap((n) => n.actions ?? []).filter((a) => a.handler === 'navigate').map((a) => a.params?.path);
    expect(paths).toEqual(expect.arrayContaining(['/board/community', '/ai', '/page/cases']));
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
    expect(icons.length).toBeGreaterThanOrEqual(4);
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

  it('공개 사례는 RAON Hub 하나이며 구성 → 검증됨 → 아직 미검증 → 기술 근거 순서다', () => {
    const cases = childrenWith(byId('raon_home_case_list'), 'rh-case-item');
    expect(cases.map((c) => c.id)).toEqual(['raon_home_case_hub']);
    expect((cases[0].children ?? []).map((c) => c.id)).toEqual(['raon_home_case_hub_stack', 'raon_home_case_hub_verdict', 'raon_home_case_hub_foot']);
    expect(textsUnder(byId('raon_home_case_hub_stack'))).toEqual(['g7', 'ext', 'ai'].map((k) => key(`case_stack_${k}`)));
    const verdict = (byId('raon_home_case_hub_verdict')?.children ?? []).map((c) => textsUnder(c)[0]);
    expect(verdict).toEqual([key('case_verified_title'), key('case_open_title')]);
    expect([ko.home.case_verified_title, ko.home.case_open_title]).toEqual(['검증됨', '아직 미검증']);
    expect([ko.home.case_verified1, ko.home.case_verified2, ko.home.case_verified3].join(' ')).toMatch(/회원·게시판·검색.*같은 요청.*코어 수정 0건/);
    expect([ko.home.case_open1, ko.home.case_open2].join(' ')).toMatch(/외부 고객.*장기 운영.*상용 부하/);
    const more = byId('raon_home_case_hub_more');
    expect(more?.props?.href).toBe('/page/cases');
    expect(more?.actions?.[0]?.params?.path).toBe('/page/cases');
  });

  it('MOBILE_STOCK 은 증거가 결합되기 전까지 홈에 공개 증거로 나오지 않는다', () => {
    expect(JSON.stringify(layout)).not.toMatch(/MOBILE_STOCK|case_stock/);
    for (const dict of [ko, en]) expect(JSON.stringify(dict.home)).not.toMatch(/MOBILE_STOCK|모의투자|paper-trading/i);
  });

  it('도입 절차는 범위 → 실행 → 검증 → 승인·복구 한 줄 타임라인이고, 신뢰는 원칙 세 줄이다', () => {
    const steps = childrenWith(byId('raon_home_process_list'), 'rh-timeline-step');
    expect(steps.map((step) => ko.home[textsUnder(step)[0].replace('$t:raonslab-product.home.', '')])).toEqual(['범위', '실행', '검증', '승인·복구']);
    for (const step of steps) expect(childrenWith(step, 'rh-timeline-node')[0]?.props?.['aria-hidden']).toBe('true');
    expect(byId('raon_home_trust_list')?.children).toHaveLength(3);
  });

  it('흐름 도식 문구 키는 단계 이름과 한 줄 설명뿐이다', () => {
    const visualKeys = Object.keys(ko.home).filter((k) => k.startsWith('visual_'));
    expect(visualKeys.sort()).toEqual(['visual_stage_input', 'visual_stage_result', 'visual_stage_run', 'visual_stage_verify']);
  });
});

describe('시각 리듬 — 섹션마다 다른 형태', () => {
  const css = readFileSync(resolve(root, 'css/main.css'), 'utf8');
  const homeCss = css.slice(css.indexOf('/* 첫 화면: 문장과'), css.indexOf('/* ─── 정보·정책 상위 메뉴'));

  it('섹션마다 다른 형태를 쓴다: 흐름 · 증거 줄 · 3행 표 · 두 열 목록 · 구성+판정 · 타임라인 · 원칙 줄', () => {
    const shapes = ['rh-flow-list', 'rh-proof-strip', 'rh-offers', 'rh-fit-grid', 'rh-verdict', 'rh-timeline', 'rh-trust-list'];
    for (const shape of shapes) expect(nodes.some((n) => classOf(n).split(' ').includes(shape)), shape).toBe(true);
  });

  it('홈 스타일에는 이미지·SVG·glow·그림자·애니메이션이 없고 색은 테마 토큰만 쓴다', () => {
    // 이번 판에서 새로 만든 형태 블록만 본다(상담 양식 패널의 기존 그림자는 대상이 아니다)
    const body = homeCss.slice(homeCss.indexOf('/* 요청 → 결과 흐름'), homeCss.indexOf('/* 라벨·값 한 쌍'))
      + homeCss.slice(homeCss.indexOf('/* ── 적합성·사례·절차'), homeCss.indexOf('@media (prefers-reduced-motion'));
    expect(body.length).toBeGreaterThan(2000);
    expect(body).not.toMatch(/gradient|box-shadow|text-shadow|filter\s*:|url\(|<svg|animation|@keyframes/i);
    expect(body.replace(/\/\*[\s\S]*?\*\//g, '')).not.toMatch(/#[0-9a-f]{3,8}\b|rgba?\(/i);
    expect(css).toContain('@media (prefers-reduced-motion: reduce)');
  });

  it('사라진 장식(진행 막대·가짜 창 머리·상태 레인·섹션 바로가기)의 스타일이 남지 않는다', () => {
    expect(css).not.toMatch(/\.rh-(subnav|log-bar|log-dots|strip-seg|run-row|scope-seg|lane|issue|principle|process-state)\b/);
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

  it('다른 AI 도구의 무능을 주장하지 않고 RAON의 구축 책임으로 차별화한다', () => {
    for (const dict of [ko, en]) {
      const text = JSON.stringify(dict.home);
      expect(text).not.toMatch(/ChatGPT|Claude|Copilot|Gemini|대화형 AI는|chat-style AI/i);
    }
    expect(ko.home.fit_note).toMatch(/RAON은 그 구축을 맡습니다/);
  });

  it('홈 문구에는 원시 점검 코드·내부 모듈 ID·공급자 용어를 쓰지 않는다(상세는 사례 문서)', () => {
    for (const dict of [ko, en]) {
      const text = JSON.stringify(dict.home);
      expect(text).not.toMatch(/\b[JA]\d+\b|J1~J10|A1~A10|raonslab-product|raonslab-ai-workspace|Provider|\b[0-9a-f]{12,40}\b/);
    }
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
