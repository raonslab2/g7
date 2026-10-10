import { default as React } from 'react';
/**
 * ScenicArt — RAON Travel Lab 원작 풍경 일러스트 (인라인 SVG)
 *
 * 이 저장소에서 직접 그린 벡터 도형만으로 구성한다(외부 이미지·사진·로고 미사용).
 * 저작자: raonslab / 라이선스: 템플릿과 동일한 MIT. 출처 기록은 LICENSE · docs/symphony/UI_DESIGN.md.
 *
 * 상품 이미지가 없을 때의 대체 표지, 홈 히어로·테마 카드의 배경으로 쓴다.
 * `variant` 를 주지 않으면 `seed`(상품 id 등) 로 결정적으로 장면을 고른다 —
 * 같은 상품은 항상 같은 그림을 받는다.
 */
export type ScenicVariant = 'coast' | 'mountain' | 'city' | 'island' | 'forest' | 'desert' | 'snow' | 'lake';
export declare const SCENIC_VARIANTS: ScenicVariant[];
export interface ScenicArtProps {
    /** 장면 종류 (미지정 시 seed 로 결정) */
    variant?: ScenicVariant | string | null;
    /** 결정적 장면 선택용 시드 (숫자 또는 문자열) */
    seed?: number | string | null;
    /** 접근성 레이블 — 비우면 장식 이미지로 처리 (aria-hidden) */
    title?: string;
    className?: string;
    'data-testid'?: string;
}
/**
 * 시드에서 장면을 결정적으로 고른다.
 */
export declare function resolveScenicVariant(variant?: string | null, seed?: number | string | null): ScenicVariant;
/**
 * 원작 풍경 일러스트 컴포넌트
 */
export declare const ScenicArt: React.FC<ScenicArtProps>;
