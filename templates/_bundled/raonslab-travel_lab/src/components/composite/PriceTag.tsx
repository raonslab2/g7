import React from 'react';

/**
 * PriceTag — 통화 인지 금액 표기
 *
 * 금액과 통화 코드는 서버 응답에서 그대로 받는다. 통화 기호·소수 자릿수는
 * `Intl.NumberFormat` 이 통화 코드로 결정하므로 특정 통화(원/円 등)를 전제하지 않는다.
 * 통화 코드가 없거나 잘못되면 숫자만 표기한다(단위를 지어내지 않는다).
 */
export interface PriceTagProps {
  /** 금액 (숫자 또는 숫자 문자열) */
  amount?: number | string | null;
  /** ISO 4217 통화 코드 (서버 응답 currency_code) */
  currency?: string | null;
  /** 금액 앞 짧은 문구 (예: "1인") */
  prefix?: string;
  /** 금액 뒤 짧은 문구 (예: "부터") */
  suffix?: string;
  /** 금액이 없을 때 표기 */
  emptyText?: string;
  className?: string;
  amountClassName?: string;
  'data-testid'?: string;
}

const resolveLocale = (): string | undefined => {
  const G7Core = (window as any).G7Core;
  const fromCore = G7Core?.locale?.current?.() ?? G7Core?.state?.get?.('_global.locale');
  if (typeof fromCore === 'string' && fromCore) return fromCore;
  if (typeof document !== 'undefined' && document.documentElement.lang) {
    return document.documentElement.lang;
  }
  return undefined;
};

/**
 * 금액을 통화 단위로 포맷한다. 해석 불가하면 null.
 */
export function formatMoney(amount: unknown, currency?: string | null, locale?: string): string | null {
  if (amount === null || amount === undefined || amount === '') return null;
  const value = typeof amount === 'number' ? amount : Number(amount);
  if (!Number.isFinite(value)) return null;
  if (currency && /^[A-Za-z]{3}$/.test(currency)) {
    try {
      return new Intl.NumberFormat(locale, { style: 'currency', currency: currency.toUpperCase() }).format(value);
    } catch {
      // 알 수 없는 통화 코드 — 숫자 + 코드로 내려간다
      return `${new Intl.NumberFormat(locale).format(value)} ${currency.toUpperCase()}`;
    }
  }
  return new Intl.NumberFormat(locale).format(value);
}

export const PriceTag: React.FC<PriceTagProps> = ({
  amount,
  currency,
  prefix,
  suffix,
  emptyText = '-',
  className = '',
  amountClassName = '',
  'data-testid': testId,
}) => {
  const formatted = formatMoney(amount, currency, resolveLocale());

  return (
    <span className={className} data-testid={testId}>
      {prefix ? <span className="mr-1 text-xs font-normal opacity-75">{prefix}</span> : null}
      <span className={amountClassName}>{formatted ?? emptyText}</span>
      {suffix && formatted ? <span className="ml-0.5 text-xs font-normal opacity-75">{suffix}</span> : null}
    </span>
  );
};

PriceTag.displayName = 'PriceTag';
