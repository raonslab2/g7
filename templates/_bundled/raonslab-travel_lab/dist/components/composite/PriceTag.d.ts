import { default as React } from 'react';
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
/**
 * 금액을 통화 단위로 포맷한다. 해석 불가하면 null.
 */
export declare function formatMoney(amount: unknown, currency?: string | null, locale?: string): string | null;
export declare const PriceTag: React.FC<PriceTagProps>;
