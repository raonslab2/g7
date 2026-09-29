/**
 * 사업 홈 화면 보조 동작.
 *
 * - 홈이 렌더된 동안 문서 제목·설명을 홈 문구로 맞추고, 다른 화면으로 가면 원래 값으로 되돌린다.
 * - 홈 안의 섹션 이동 링크(`a[data-rh-jump]`)는 해시 변경 없이 스크롤·포커스만 옮긴다(라우터 재진입 방지).
 * - 모듈 동봉 이미지(`img[data-rh-asset]`)의 주소는 `G7Core.asset.module` 로 만든다. 서버의 자산 URL 모드
 *   (확장자 경로 / `?file=` 쿼리)를 코어가 알고 있으므로 이 모듈은 주소를 문자열로 조립하지 않는다.
 * - 원본 열기 링크(`a[data-rh-asset-link]`)는 안쪽 이미지와 같은 주소를 href 로 받는다. 주소를 못 만들면
 *   href 를 두지 않는다(가짜 링크를 만들지 않는다).
 */
import { currentLocale, t } from './i18n';

export interface DocumentMetaSnapshot {
  title: string;
  description: string | null;
}

/** 홈에 적용할 제목·설명. 번역 키가 풀리지 않으면 적용하지 않는다(키 노출 방지). */
export function resolveHomeMeta(translate: (key: string) => string): DocumentMetaSnapshot | null {
  const title = translate('home.meta_title');
  const description = translate('home.meta_description');
  if (title.startsWith('raonslab-product.') || description.startsWith('raonslab-product.')) return null;
  return { title, description };
}

let original: DocumentMetaSnapshot | null = null;
let appliedLocale: string | null = null;

function descriptionTag(create: boolean): HTMLMetaElement | null {
  let tag = document.head.querySelector<HTMLMetaElement>('meta[name="description"]');
  if (!tag && create) {
    tag = document.createElement('meta');
    tag.name = 'description';
    tag.dataset.rhCreated = 'true';
    document.head.append(tag);
  }
  return tag;
}

function applyHomeMeta(): void {
  const locale = currentLocale();
  if (original && appliedLocale === locale && document.title !== original.title) return;
  const meta = resolveHomeMeta(t);
  if (!meta) return;
  if (!original) {
    original = { title: document.title, description: descriptionTag(false)?.content ?? null };
  }
  document.title = meta.title;
  const tag = descriptionTag(true);
  if (tag) tag.content = meta.description ?? '';
  appliedLocale = locale;
}

function restoreMeta(): void {
  if (!original) return;
  document.title = original.title;
  const tag = descriptionTag(false);
  if (tag) {
    if (original.description === null && tag.dataset.rhCreated === 'true') tag.remove();
    else if (original.description !== null) tag.content = original.description;
  }
  original = null;
  appliedLocale = null;
}

function prefersReducedMotion(): boolean {
  return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
}

export function jumpToSection(key: string): boolean {
  const section = document.getElementById(`rh-${key}`);
  if (!section) return false;
  // 템플릿이 html 에 scroll-behavior: smooth 를 두므로 'auto' 로는 애니메이션이 꺼지지 않는다.
  section.scrollIntoView({ behavior: (prefersReducedMotion() ? 'instant' : 'smooth') as ScrollBehavior, block: 'start' });
  const heading = document.getElementById(`rh-${key}-title`);
  (heading ?? section).focus({ preventScroll: true });
  return true;
}

function onDocumentClick(event: MouseEvent): void {
  if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
  const link = (event.target as Element | null)?.closest?.<HTMLAnchorElement>('a[data-rh-jump]');
  if (!link || !link.closest('.rh-home')) return;
  if (jumpToSection(link.dataset.rhJump ?? '')) event.preventDefault();
}

const MODULE_ID = 'raonslab-product';

interface AssetApi {
  module?: (identifier: string, path: string) => string;
}

/** 모듈 루트 기준 경로를 현재 서버 모드의 자산 URL 로 바꾼다. 코어 API 가 없으면 null(이미지를 비워 둔다). */
export function resolveModuleAssetUrl(asset: AssetApi | undefined, path: string): string | null {
  if (!path || path.includes('..') || path.startsWith('/')) return null;
  if (typeof asset?.module !== 'function') return null;
  try {
    const url = asset.module(MODULE_ID, path);
    return typeof url === 'string' && url.startsWith('/') && !url.startsWith('//') ? url : null;
  } catch {
    return null;
  }
}

export function hydrateModuleImages(root: ParentNode = document): void {
  const asset = (window as unknown as { G7Core?: { asset?: AssetApi } }).G7Core?.asset;
  root.querySelectorAll<HTMLImageElement>('.rh-home img[data-rh-asset]').forEach((img) => {
    const url = resolveModuleAssetUrl(asset, img.dataset.rhAsset ?? '');
    if (url && img.getAttribute('src') !== url) img.setAttribute('src', url);
    const link = img.closest<HTMLAnchorElement>('a[data-rh-asset-link]');
    if (link && url && link.getAttribute('href') !== url) link.setAttribute('href', url);
  });
}

let homeSeen = false;

/** DOM 변화마다 호출됩니다. */
export function syncHomePage(): void {
  const onHome = document.querySelector('.rh-home') !== null;
  if (onHome) {
    applyHomeMeta();
    hydrateModuleImages();
    if (!homeSeen) {
      homeSeen = true;
      const hash = window.location.hash.replace(/^#rh-/, '');
      if (hash !== window.location.hash && /^[a-z]+$/.test(hash)) window.setTimeout(() => jumpToSection(hash), 0);
    }
  } else {
    homeSeen = false;
    restoreMeta();
  }
}

export function installHomePage(): void {
  document.addEventListener('click', onDocumentClick);
}
