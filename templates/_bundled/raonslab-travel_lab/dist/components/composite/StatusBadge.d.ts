import { default as React } from 'react';
/**
 * StatusBadge — 상담 요청(테스트) 상태 배지
 *
 * 상태값은 서버 Enum 문자열(TEST_INQUIRY 등)을 그대로 받는다. 문구는 템플릿 다국어
 * `{labelPrefix}.{status 소문자}` 키로 해석한다. 모르는 상태값은 `{labelPrefix}.unknown`
 * 키로 내려가고, 번역 사전 자체가 없을 때만 상태 코드를 그대로 보여준다.
 */
export interface StatusBadgeProps {
    status?: string | null;
    /** 다국어 키 접두사 */
    labelPrefix?: string;
    className?: string;
    'data-testid'?: string;
}
export declare const StatusBadge: React.FC<StatusBadgeProps>;
