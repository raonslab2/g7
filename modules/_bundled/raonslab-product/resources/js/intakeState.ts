/**
 * 온라인 상담 접수 상태를 클릭 전에 화면에 드러낸다.
 *
 * 상태의 출처는 상담 config 응답(`parseIntakeConfig`) 하나다. 해석 결과를 `<html data-rh-intake>` 에 싣고,
 * 레이아웃은 `data-rh-intake-show="open|closed"` 로 상태별 요소를 선언한다(main.css 가 표시를 고른다).
 * 값이 없거나 알 수 없으면 "closed" 로 본다 — 접수가 열렸다고 확인되기 전에는 상담을 주 행동으로 내세우지 않는다.
 */

export type IntakeState = 'open' | 'closed';

/** 레이아웃이 상태별 요소를 선언할 때 쓰는 속성. */
export const INTAKE_SHOW_SELECTOR = '[data-rh-intake-show]';

export function intakeStateOf(config: unknown): IntakeState {
  return config ? 'open' : 'closed';
}

export function publishIntakeState(state: IntakeState, root: HTMLElement = document.documentElement): void {
  if (root.dataset.rhIntake !== state) root.dataset.rhIntake = state;
}

export function currentIntakeState(root: HTMLElement = document.documentElement): IntakeState {
  return root.dataset.rhIntake === 'open' ? 'open' : 'closed';
}

/** 상태별 요소가 화면에 있는데 아직 상태를 확인하지 않았으면 true. */
export function needsIntakeState(doc: Document = document): boolean {
  return doc.documentElement.dataset.rhIntake === undefined && doc.querySelector(INTAKE_SHOW_SELECTOR) !== null;
}

/**
 * 상위 메뉴만 있는 화면에서 페이지를 옮길 때마다 config 를 다시 묻지 않도록 짧게 기억한다(탭 세션 한정).
 * 상담 양식은 이 값을 쓰지 않고 항상 새로 확인한다 — 기억한 값은 표시용이며 접수 판단에 쓰지 않는다.
 */
export const INTAKE_CACHE_KEY = 'rh-intake-state';
export const INTAKE_CACHE_TTL_MS = 120_000;

export function readCachedIntakeState(storage: Pick<Storage, 'getItem'> | null, now: number): IntakeState | null {
  try {
    const raw = storage?.getItem(INTAKE_CACHE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as { state?: unknown; at?: unknown };
    if ((parsed.state !== 'open' && parsed.state !== 'closed') || typeof parsed.at !== 'number') return null;
    if (now - parsed.at < 0 || now - parsed.at > INTAKE_CACHE_TTL_MS) return null;
    return parsed.state;
  } catch {
    return null;
  }
}

export function writeCachedIntakeState(storage: Pick<Storage, 'setItem'> | null, state: IntakeState, now: number): void {
  try {
    storage?.setItem(INTAKE_CACHE_KEY, JSON.stringify({ state, at: now }));
  } catch {
    // 저장소가 막힌 환경에서는 기억하지 않는다.
  }
}
