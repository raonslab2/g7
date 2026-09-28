/**
 * 정보·정책 상위 메뉴와 안내 문서 화면 보조 동작.
 *
 * - 상위 메뉴는 hover 가 아니라 disclosure 버튼(click·Enter·Space·터치)으로만 열린다.
 *   Escape·바깥 클릭·포커스 이탈·하위 링크 선택 시 닫고, aria-expanded 와 패널 hidden 을 함께 맞춘다.
 * - 현재 주소에 해당하는 링크에 aria-current="page", 그 그룹 버튼에 data-current 를 단다(직접 진입·새로고침 포함).
 * - 문서 목차(`a[data-rh-anchor]`)는 해시 변경 없이 스크롤·포커스만 옮긴다(라우터 재진입 방지).
 * - 모바일 문서 메뉴(`[data-rh-docnav-toggle]`)는 기본 닫힘 disclosure 다. Escape 는 닫고 토글로 포커스를 돌린다.
 * - 모바일 드로어 문서 섹션(`a.rh-drawer-link`)도 같은 현재 위치 표시를 받는다.
 * - 문서 분류는 resources/taxonomy/info-policy.json 단일 출처에서 읽는다.
 */

import taxonomy from '../taxonomy/info-policy.json';

const TRIGGER = '[data-rh-menu-trigger]';
const DOCNAV_TOGGLE = '[data-rh-docnav-toggle]';

export type ProductPageGroup = 'info' | 'policy';

const PAGE_GROUPS: Record<string, ProductPageGroup> = Object.fromEntries(
  taxonomy.groups.flatMap((group) => group.items.map((item) => [item.slug, group.key as ProductPageGroup])),
);

/** G7의 clean URL에서 비교에 불필요한 끝 슬래시만 제거한다. */
export function normalizePath(pathname: string): string {
  return pathname.replace(/\/+$/, '') || '/';
}

export function productPageGroup(pathname: string): 'info' | 'policy' | null {
  const match = normalizePath(pathname).match(/^\/page\/([a-z0-9-]+)$/);
  return match ? PAGE_GROUPS[match[1]] ?? null : null;
}

function panelOf(trigger: HTMLElement): HTMLElement | null {
  const id = trigger.getAttribute('aria-controls');
  return id ? document.getElementById(id) : null;
}

function setOpen(trigger: HTMLElement, open: boolean): void {
  trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  const panel = panelOf(trigger);
  if (panel) panel.hidden = !open;
}

function openTriggers(): HTMLElement[] {
  return Array.from(document.querySelectorAll<HTMLElement>(`${TRIGGER}[aria-expanded="true"]`));
}

export function closeAllMenus(except?: HTMLElement): void {
  openTriggers().forEach((trigger) => {
    if (trigger !== except) setOpen(trigger, false);
  });
}

function menuLinks(trigger: HTMLElement): HTMLAnchorElement[] {
  return Array.from(panelOf(trigger)?.querySelectorAll<HTMLAnchorElement>('a[href]') ?? []);
}

export function toggleMenu(trigger: HTMLElement, open?: boolean): void {
  const next = open ?? trigger.getAttribute('aria-expanded') !== 'true';
  closeAllMenus(trigger);
  setOpen(trigger, next);
}

/** 모바일 문서 메뉴의 열림 상태를 aria-expanded 와 패널 hidden 에 함께 반영한다. */
export function setDocnavOpen(toggle: HTMLElement, open: boolean): void {
  setOpen(toggle, open);
}

function openDocnavToggles(): HTMLElement[] {
  return Array.from(document.querySelectorAll<HTMLElement>(`${DOCNAV_TOGGLE}[aria-expanded="true"]`));
}

export function closeDocnav(): void {
  openDocnavToggles().forEach((toggle) => setOpen(toggle, false));
}

function onClick(event: MouseEvent): void {
  const target = event.target as Element | null;
  const docnavToggle = target?.closest?.<HTMLElement>(DOCNAV_TOGGLE);
  if (docnavToggle) {
    event.preventDefault();
    setDocnavOpen(docnavToggle, docnavToggle.getAttribute('aria-expanded') !== 'true');
    return;
  }
  if (target?.closest?.('.rh-docnav-panel a')) closeDocnav();

  const trigger = target?.closest?.<HTMLElement>(TRIGGER);
  if (trigger) {
    event.preventDefault();
    toggleMenu(trigger);
    return;
  }

  // 하위 링크 선택(SPA 이동) 또는 메뉴 바깥 클릭이면 닫는다.
  if (target?.closest?.('.rh-gnav-panel a') || !target?.closest?.('.rh-gnav-group')) closeAllMenus();

  const anchor = target?.closest?.<HTMLAnchorElement>('a[data-rh-anchor]');
  if (anchor && !event.defaultPrevented && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
    if (jumpToAnchor(anchor.dataset.rhAnchor ?? '')) event.preventDefault();
  }
}

function onKeydown(event: KeyboardEvent): void {
  const target = event.target as HTMLElement | null;
  if (event.key === 'Escape') {
    const docnav = openDocnavToggles();
    if (docnav.length > 0) {
      closeDocnav();
      docnav[0].focus();
      event.preventDefault();
      return;
    }

    const open = openTriggers();
    if (open.length === 0) return;
    const focusInside = open.find((trigger) => trigger.closest('.rh-gnav-group')?.contains(target));
    closeAllMenus();
    (focusInside ?? open[0]).focus();
    event.preventDefault();
    return;
  }

  if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
  const trigger = target?.matches?.(TRIGGER) ? target : null;
  if (trigger) {
    toggleMenu(trigger, true);
    const links = menuLinks(trigger);
    (event.key === 'ArrowDown' ? links[0] : links[links.length - 1])?.focus();
    event.preventDefault();
    return;
  }

  const group = target?.closest?.('.rh-gnav-group');
  const owner = group?.querySelector<HTMLElement>(TRIGGER);
  if (!owner || !target?.closest?.('.rh-gnav-panel')) return;
  const links = menuLinks(owner);
  const index = links.indexOf(target as HTMLAnchorElement);
  const next = links[(index + (event.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length];
  next?.focus();
  event.preventDefault();
}

/** 키보드 포커스가 메뉴 그룹 밖으로 나가면 그 메뉴를 닫는다. */
function onFocusout(event: FocusEvent): void {
  const group = (event.target as Element | null)?.closest?.('.rh-gnav-group');
  if (!group) return;
  const next = event.relatedTarget as Node | null;
  if (next && group.contains(next)) return;
  const trigger = group.querySelector<HTMLElement>(TRIGGER);
  if (trigger && next) setOpen(trigger, false);
}

function prefersReducedMotion(): boolean {
  return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
}

export function jumpToAnchor(id: string): boolean {
  const section = id ? document.getElementById(id) : null;
  if (!section) return false;
  section.scrollIntoView({ behavior: (prefersReducedMotion() ? 'instant' : 'smooth') as ScrollBehavior, block: 'start' });
  const heading = document.getElementById(`${id}-title`);
  (heading ?? section).focus({ preventScroll: true });
  return true;
}

let lastPath: string | null = null;

/** DOM 변화마다 호출된다. 현재 위치 표시를 맞추고, 주소가 바뀌었으면 열린 메뉴를 닫는다. */
export function syncProductNav(): void {
  const path = normalizePath(window.location.pathname);
  if (lastPath !== null && lastPath !== path) {
    closeAllMenus();
    closeDocnav();
  }
  lastPath = path;

  document.querySelectorAll<HTMLAnchorElement>('.rh-gnav a[data-rh-nav-path], a.rh-side-link[data-rh-nav-path], a.rh-drawer-link[data-rh-nav-path]').forEach((link) => {
    const current = link.dataset.rhNavPath === path && path !== '/';
    if (current) {
      if (link.getAttribute('aria-current') !== 'page') link.setAttribute('aria-current', 'page');
    } else if (link.hasAttribute('aria-current')) {
      link.removeAttribute('aria-current');
    }
  });

  document.querySelectorAll<HTMLElement>('.rh-gnav-group[data-rh-menu]').forEach((group) => {
    const trigger = group.querySelector<HTMLElement>(TRIGGER);
    if (!trigger) return;
    const active = productPageGroup(path) === group.dataset.rhMenu;
    if (active) trigger.dataset.current = 'true';
    else delete trigger.dataset.current;
  });
}

export function installProductNav(): void {
  document.addEventListener('click', onClick);
  document.addEventListener('keydown', onKeydown);
  document.addEventListener('focusout', onFocusout);
  window.addEventListener('popstate', () => syncProductNav());
}
