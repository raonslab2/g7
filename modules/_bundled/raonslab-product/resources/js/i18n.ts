/** 모듈 다국어 키(`raonslab-product.*`)를 코어 번역기로 해석합니다. */
const NAMESPACE = 'raonslab-product';

interface G7CoreLike {
  t?: (key: string, params?: Record<string, string | number>) => string;
  locale?: { current?: () => string };
}

function core(): G7CoreLike | undefined {
  return (window as unknown as { G7Core?: G7CoreLike }).G7Core;
}

export function t(key: string): string {
  const fullKey = `${NAMESPACE}.${key}`;
  const translate = core()?.t;
  if (typeof translate !== 'function') return fullKey;
  try {
    return translate(fullKey) ?? fullKey;
  } catch {
    return fullKey;
  }
}

export function currentLocale(): string {
  try {
    return core()?.locale?.current?.() || document.documentElement.lang || 'ko';
  } catch {
    return document.documentElement.lang || 'ko';
  }
}
