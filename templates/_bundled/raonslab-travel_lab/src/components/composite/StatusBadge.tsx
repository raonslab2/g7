import React from 'react';

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

const TONES: Record<string, string> = {
  TEST_INQUIRY: 'bg-raon-50 text-raon-700 ring-raon-200',
  UNDER_REVIEW: 'bg-amber-50 text-amber-700 ring-amber-200',
  TEST_ACCEPTED: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  DECLINED: 'bg-rose-50 text-rose-700 ring-rose-200',
  CANCELLED: 'bg-slate-100 text-slate-600 ring-slate-200',
};

const translate = (key: string): string => {
  const G7Core = (window as any).G7Core;
  return G7Core?.t?.(key) ?? key;
};

export const StatusBadge: React.FC<StatusBadgeProps> = ({
  status,
  labelPrefix = 'travel.status',
  className = '',
  'data-testid': testId,
}) => {
  const code = typeof status === 'string' ? status.toUpperCase() : '';
  const known = code in TONES;
  const key = `${labelPrefix}.${known ? code.toLowerCase() : 'unknown'}`;
  const label = translate(key);
  const tone = known ? TONES[code] : 'bg-slate-100 text-slate-600 ring-slate-200';

  return (
    <span
      className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${tone} ${className}`}
      data-status={code || 'UNKNOWN'}
      data-testid={testId}
    >
      <span aria-hidden="true" className="h-1.5 w-1.5 rounded-full bg-current opacity-70" />
      {label === key ? code || '-' : label}
    </span>
  );
};

StatusBadge.displayName = 'StatusBadge';
