/**
 * @file travelComponents.test.tsx
 * @description RAON Travel Lab 고유 컴포넌트(ScenicArt / PriceTag / StatusBadge) 단위 테스트
 *
 * @vitest-environment jsdom
 */
import React from 'react';
import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, cleanup } from '@testing-library/react';
import { ScenicArt, resolveScenicVariant, SCENIC_VARIANTS } from '../../src/components/composite/ScenicArt';
import { PriceTag, formatMoney } from '../../src/components/composite/PriceTag';
import { StatusBadge } from '../../src/components/composite/StatusBadge';

afterEach(() => {
  cleanup();
  delete (window as any).G7Core;
});

describe('ScenicArt', () => {
  it('같은 seed 는 항상 같은 장면을 고른다', () => {
    expect(resolveScenicVariant(null, 42)).toBe(resolveScenicVariant(null, 42));
    expect(SCENIC_VARIANTS).toContain(resolveScenicVariant(null, 'abc'));
  });

  it('유효한 variant 는 그대로, 알 수 없는 variant 는 seed 로 대체한다', () => {
    expect(resolveScenicVariant('snow', 1)).toBe('snow');
    expect(SCENIC_VARIANTS).toContain(resolveScenicVariant('unknown-scene', 1));
  });

  it('title 이 없으면 장식(aria-hidden), 있으면 img 역할 + 레이블', () => {
    const { container, rerender } = render(<ScenicArt variant="coast" />);
    expect(container.querySelector('svg')?.getAttribute('aria-hidden')).toBe('true');
    rerender(<ScenicArt variant="coast" title="해변 풍경" />);
    expect(screen.getByRole('img', { name: '해변 풍경' })).toBeInTheDocument();
  });

  it('인스턴스마다 고유한 그라디언트 id 를 쓴다 (한 화면 여러 장 충돌 방지)', () => {
    const { container } = render(<div><ScenicArt variant="lake" /><ScenicArt variant="lake" /></div>);
    const ids = Array.from(container.querySelectorAll('linearGradient')).map((g) => g.id);
    expect(new Set(ids).size).toBe(2);
  });

  it('외부 이미지 URL 을 참조하지 않는다', () => {
    const { container } = render(<ScenicArt variant="city" />);
    expect(container.querySelectorAll('[href], [xlink\\:href]').length).toBe(0);
    expect(container.querySelector('image')).toBeNull();
    expect(container.innerHTML).not.toMatch(/url\((?!#)/);
  });
});

describe('PriceTag', () => {
  it('통화 코드로 기호와 자릿수를 정한다 (특정 통화 전제 없음)', () => {
    expect(formatMoney(1500000, 'KRW', 'ko-KR')).toBe('₩1,500,000');
    expect(formatMoney(1234.5, 'USD', 'en-US')).toBe('$1,234.50');
    expect(formatMoney(14835, 'JPY', 'ja-JP')).toMatch(/14,835/);
  });

  it('통화 코드가 없으면 단위를 지어내지 않고 숫자만', () => {
    expect(formatMoney(3000, null, 'ko-KR')).toBe('3,000');
  });

  it('금액이 없거나 숫자가 아니면 emptyText', () => {
    render(<PriceTag amount={null} currency="KRW" emptyText="출발일을 골라 주세요" />);
    expect(screen.getByText('출발일을 골라 주세요')).toBeInTheDocument();
    expect(formatMoney('abc', 'KRW')).toBeNull();
  });

  it('suffix 는 금액이 있을 때만 붙는다', () => {
    const { rerender } = render(<PriceTag amount={1000} currency="KRW" suffix="부터" data-testid="p" />);
    expect(screen.getByTestId('p').textContent).toContain('부터');
    rerender(<PriceTag amount={null} currency="KRW" suffix="부터" data-testid="p" />);
    expect(screen.getByTestId('p').textContent).not.toContain('부터');
  });
});

describe('StatusBadge', () => {
  const dict: Record<string, string> = {
    'travel.status.test_inquiry': '테스트 접수',
    'travel.status.under_review': '검토 중',
    'travel.status.test_accepted': '테스트 수락',
    'travel.status.declined': '진행 어려움',
    'travel.status.cancelled': '취소됨',
    'travel.status.unknown': '확인 중',
  };
  const installT = () => {
    (window as any).G7Core = { t: (key: string) => dict[key] ?? key };
  };

  it.each([
    ['TEST_INQUIRY', '테스트 접수'],
    ['UNDER_REVIEW', '검토 중'],
    ['TEST_ACCEPTED', '테스트 수락'],
    ['DECLINED', '진행 어려움'],
    ['CANCELLED', '취소됨'],
  ])('%s → %s', (status, label) => {
    installT();
    render(<StatusBadge status={status} data-testid="b" />);
    expect(screen.getByTestId('b').textContent).toContain(label);
    expect(screen.getByTestId('b').getAttribute('data-status')).toBe(status);
  });

  it('모르는 상태는 unknown 문구로 내려간다', () => {
    installT();
    render(<StatusBadge status="SOMETHING_NEW" data-testid="b" />);
    expect(screen.getByTestId('b').textContent).toContain('확인 중');
  });
});
