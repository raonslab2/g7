import '../css/main.css';
import { shouldApplyDarkDefault } from './theme';
import { syncConsultationIslands } from './consultationForm';
import { installHomePage, syncHomePage } from './homePage';
import { installProductNav, syncProductNav } from './productNav';

const COLOR_SCHEME_KEY = 'g7_color_scheme';

/** 사용자가 아직 테마를 고르지 않은 경우에만 제품 기본값인 다크 모드를 적용합니다. */
function applyDarkFirstDefault(): void {
  try {
    if (!shouldApplyDarkDefault(window.localStorage.getItem(COLOR_SCHEME_KEY))) {
      return;
    }

    window.localStorage.setItem(COLOR_SCHEME_KEY, 'dark');
    document.documentElement.dataset.theme = 'dark';
    document.documentElement.classList.add('dark');
  } catch {
    document.documentElement.dataset.theme = 'dark';
    document.documentElement.classList.add('dark');
  }
}

applyDarkFirstDefault();

function markProductRuntime(): void {
  document.body.classList.add('raon-product');
}

/**
 * 온라인 결제를 받지 않는 제품 화면에서 템플릿의 쇼핑·장바구니·주문조회 진입점을 숨깁니다.
 * "Powered by 그누보드7" 표기는 오픈소스 고지로 유지합니다. 통화 선택기는 public-commerce-chrome.json
 * Layout Extension 이 공식 확장 지점에서 제거합니다.
 */
function hideCommerceEntryPoints(): void {
  document.querySelectorAll<HTMLElement>('#mobile_cart_btn, [data-testid="nav-shop"]').forEach((node) => {
    node.hidden = true;
  });

  document.querySelectorAll<HTMLButtonElement>('button').forEach((button) => {
    const label = (button.textContent ?? '').trim();
    const commerceIcon = button.querySelector('.fa-shopping-cart, .fa-shopping-bag');
    if (commerceIcon || ['주문조회', 'Order lookup', 'Order Lookup'].includes(label)) {
      button.hidden = true;
    }
  });
}

let homeSyncQueued = false;

/** 홈 문서 메타·상담 양식·상위 메뉴 현재 위치를 DOM 변화 직후 한 번만 동기화합니다. */
function queueHomeSync(): void {
  if (homeSyncQueued) return;
  homeSyncQueued = true;
  window.requestAnimationFrame(() => {
    homeSyncQueued = false;
    syncHomePage();
    syncConsultationIslands();
    syncProductNav();
  });
}

const observer = new MutationObserver(() => {
  hideCommerceEntryPoints();
  queueHomeSync();
});
observer.observe(document.documentElement, { childList: true, subtree: true });
installHomePage();
installProductNav();

if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', markProductRuntime, { once: true });
} else {
  markProductRuntime();
}

hideCommerceEntryPoints();
queueHomeSync();
