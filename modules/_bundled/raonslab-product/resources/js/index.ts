import '../css/main.css';
import { shouldApplyDarkDefault } from './theme';

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

if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', markProductRuntime, { once: true });
} else {
  markProductRuntime();
}
