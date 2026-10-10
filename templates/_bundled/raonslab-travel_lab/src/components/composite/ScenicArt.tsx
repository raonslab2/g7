import React, { useId } from 'react';

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
export type ScenicVariant =
  | 'coast'
  | 'mountain'
  | 'city'
  | 'island'
  | 'forest'
  | 'desert'
  | 'snow'
  | 'lake';

export const SCENIC_VARIANTS: ScenicVariant[] = [
  'coast',
  'mountain',
  'city',
  'island',
  'forest',
  'desert',
  'snow',
  'lake',
];

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

interface Palette {
  skyTop: string;
  skyBottom: string;
  sun: string;
  far: string;
  mid: string;
  near: string;
  accent: string;
}

const PALETTES: Record<ScenicVariant, Palette> = {
  coast: { skyTop: '#bdeff0', skyBottom: '#fff1dc', sun: '#ffb25c', far: '#7fcfd0', mid: '#2fb3b0', near: '#f3dcb4', accent: '#ffffff' },
  mountain: { skyTop: '#c9e6ff', skyBottom: '#f6fbff', sun: '#ffd27a', far: '#a9c4e3', mid: '#6f93bd', near: '#3f6d63', accent: '#ffffff' },
  city: { skyTop: '#ffd9c4', skyBottom: '#fff5ea', sun: '#ff8a5c', far: '#f2b9a6', mid: '#c97f7f', near: '#5a5f7a', accent: '#ffe7a3' },
  island: { skyTop: '#9fe3f2', skyBottom: '#e9fbff', sun: '#ffe08a', far: '#5cc8d8', mid: '#1aa7b5', near: '#47a36b', accent: '#2c7a4b' },
  forest: { skyTop: '#d6f1df', skyBottom: '#f7fff4', sun: '#fff0a6', far: '#9fd3ad', mid: '#58a874', near: '#2f6f4c', accent: '#1f5138' },
  desert: { skyTop: '#ffe2b8', skyBottom: '#fff8ec', sun: '#ff9a52', far: '#f5c48c', mid: '#e8a464', near: '#c97a3f', accent: '#5b8a4a' },
  snow: { skyTop: '#d9e8ff', skyBottom: '#ffffff', sun: '#ffe7c2', far: '#c4d6ef', mid: '#9db6db', near: '#f4f8ff', accent: '#6b8bb8' },
  lake: { skyTop: '#cfe1ff', skyBottom: '#fff4f0', sun: '#ffb7a1', far: '#9bb7d9', mid: '#6c95c4', near: '#4e8c7a', accent: '#a7d3f0' },
};

/**
 * 시드에서 장면을 결정적으로 고른다.
 */
export function resolveScenicVariant(variant?: string | null, seed?: number | string | null): ScenicVariant {
  if (variant && (SCENIC_VARIANTS as string[]).includes(variant)) {
    return variant as ScenicVariant;
  }
  const text = String(seed ?? '0');
  let hash = 0;
  for (let i = 0; i < text.length; i += 1) {
    hash = (hash * 31 + text.charCodeAt(i)) >>> 0;
  }
  return SCENIC_VARIANTS[hash % SCENIC_VARIANTS.length];
}

function renderLandscape(variant: ScenicVariant, p: Palette): React.ReactNode {
  switch (variant) {
    case 'coast':
      return (
        <>
          <path d="M0 200 Q120 170 240 192 T480 184 L480 300 L0 300 Z" fill={p.far} />
          <path d="M0 222 Q140 204 260 220 T480 214 L480 300 L0 300 Z" fill={p.mid} />
          <path d="M0 258 Q160 236 300 252 Q400 262 480 246 L480 300 L0 300 Z" fill={p.near} />
          <path d="M40 232 q30 -6 60 0 M180 238 q30 -6 60 0 M330 230 q30 -6 60 0" stroke={p.accent} strokeWidth="3" fill="none" strokeLinecap="round" opacity="0.8" />
          <path d="M392 252 l0 -46 M392 206 q-22 2 -30 14 M392 206 q22 0 32 12 M392 206 q-8 -14 -26 -16 M392 206 q12 -14 28 -12" stroke="#2f6f4c" strokeWidth="4" fill="none" strokeLinecap="round" />
        </>
      );
    case 'mountain':
      return (
        <>
          <path d="M0 220 L90 120 L170 210 L260 96 L360 212 L420 160 L480 206 L480 300 L0 300 Z" fill={p.far} />
          <path d="M260 96 L290 132 L272 128 L258 142 L242 124 Z" fill={p.accent} opacity="0.9" />
          <path d="M90 120 L112 146 L98 142 L86 152 L74 138 Z" fill={p.accent} opacity="0.9" />
          <path d="M0 246 L120 176 L220 238 L330 170 L480 246 L480 300 L0 300 Z" fill={p.mid} />
          <path d="M0 270 Q120 248 240 266 T480 262 L480 300 L0 300 Z" fill={p.near} />
        </>
      );
    case 'city':
      return (
        <>
          <path d="M0 230 L0 170 L30 170 L30 140 L60 140 L60 186 L90 186 L90 120 L126 120 L126 196 L160 196 L160 150 L196 150 L196 104 L230 104 L230 180 L262 180 L262 132 L300 132 L300 196 L338 196 L338 158 L370 158 L370 116 L404 116 L404 190 L440 190 L440 150 L480 150 L480 230 Z" fill={p.far} />
          <path d="M0 252 L0 206 L44 206 L44 180 L80 180 L80 214 L120 214 L120 170 L150 170 L150 226 L200 226 L200 188 L236 188 L236 220 L280 220 L280 176 L320 176 L320 214 L360 214 L360 192 L400 192 L400 226 L440 226 L440 200 L480 200 L480 252 Z" fill={p.mid} />
          <g fill={p.accent} opacity="0.85">
            <rect x="100" y="132" width="8" height="8" /><rect x="112" y="150" width="8" height="8" /><rect x="206" y="118" width="8" height="8" /><rect x="216" y="140" width="8" height="8" /><rect x="380" y="130" width="8" height="8" /><rect x="380" y="152" width="8" height="8" />
          </g>
          <path d="M0 262 L480 262 L480 300 L0 300 Z" fill={p.near} />
          <path d="M20 280 h80 M150 284 h60 M260 280 h90 M390 284 h70" stroke={p.accent} strokeWidth="3" strokeLinecap="round" opacity="0.6" />
        </>
      );
    case 'island':
      return (
        <>
          <path d="M0 206 L480 206 L480 300 L0 300 Z" fill={p.far} />
          <path d="M0 236 Q120 226 240 236 T480 232 L480 300 L0 300 Z" fill={p.mid} />
          <ellipse cx="300" cy="212" rx="110" ry="22" fill="#f6e2b8" />
          <path d="M226 210 Q270 160 314 170 Q350 176 380 210 Z" fill={p.near} />
          <path d="M318 184 q-4 -30 6 -54 M324 130 q-26 -2 -40 14 M324 130 q24 -6 42 6 M324 130 q-8 -18 -30 -20 M324 130 q14 -16 34 -12" stroke={p.accent} strokeWidth="4" fill="none" strokeLinecap="round" />
          <path d="M60 256 q24 -6 48 0 M380 262 q24 -6 48 0" stroke="#ffffff" strokeWidth="3" fill="none" strokeLinecap="round" opacity="0.7" />
        </>
      );
    case 'forest':
      return (
        <>
          <path d="M0 214 Q120 182 240 206 T480 196 L480 300 L0 300 Z" fill={p.far} />
          <g fill={p.mid}>
            <path d="M40 236 L64 176 L88 236 Z" /><path d="M110 240 L140 166 L170 240 Z" /><path d="M300 238 L330 170 L360 238 Z" /><path d="M400 236 L424 180 L448 236 Z" />
          </g>
          <path d="M0 250 Q160 226 320 246 T480 240 L480 300 L0 300 Z" fill={p.near} />
          <g fill={p.accent}>
            <path d="M190 262 L222 184 L254 262 Z" /><path d="M236 266 L262 204 L288 266 Z" />
          </g>
        </>
      );
    case 'desert':
      return (
        <>
          <path d="M0 220 Q140 176 260 214 T480 204 L480 300 L0 300 Z" fill={p.far} />
          <path d="M0 246 Q120 214 250 242 T480 236 L480 300 L0 300 Z" fill={p.mid} />
          <path d="M0 270 Q200 250 480 268 L480 300 L0 300 Z" fill={p.near} />
          <path d="M120 240 l0 -40 M120 214 q-14 0 -14 -14 M120 208 q14 0 14 -12" stroke={p.accent} strokeWidth="7" fill="none" strokeLinecap="round" />
          <path d="M360 230 l0 -30 M360 212 q12 0 12 -10" stroke={p.accent} strokeWidth="6" fill="none" strokeLinecap="round" />
        </>
      );
    case 'snow':
      return (
        <>
          <path d="M0 214 L100 140 L190 206 L290 120 L390 200 L480 156 L480 300 L0 300 Z" fill={p.far} />
          <path d="M0 246 L140 186 L250 238 L370 180 L480 236 L480 300 L0 300 Z" fill={p.mid} />
          <path d="M0 264 Q160 244 300 258 T480 254 L480 300 L0 300 Z" fill={p.near} />
          <g fill={p.accent}>
            <path d="M80 262 L96 226 L112 262 Z" /><path d="M104 266 L118 236 L132 266 Z" /><path d="M396 262 L410 230 L424 262 Z" />
          </g>
          <g fill="#ffffff" opacity="0.9">
            <circle cx="60" cy="60" r="3" /><circle cx="150" cy="90" r="2.5" /><circle cx="230" cy="50" r="3" /><circle cx="340" cy="80" r="2.5" /><circle cx="420" cy="46" r="3" />
          </g>
        </>
      );
    case 'lake':
    default:
      return (
        <>
          <path d="M0 196 L110 132 L200 190 L300 126 L400 186 L480 150 L480 214 L0 214 Z" fill={p.far} />
          <path d="M0 214 L480 214 L480 300 L0 300 Z" fill={p.accent} />
          <path d="M0 214 L110 268 L200 222 L300 274 L400 226 L480 252 L480 214 Z" fill={p.far} opacity="0.35" />
          <path d="M60 238 h60 M220 252 h80 M360 240 h50" stroke="#ffffff" strokeWidth="3" strokeLinecap="round" opacity="0.7" />
          <path d="M0 282 Q120 270 240 282 T480 280 L480 300 L0 300 Z" fill={p.near} />
        </>
      );
  }
}

/**
 * 원작 풍경 일러스트 컴포넌트
 */
export const ScenicArt: React.FC<ScenicArtProps> = ({
  variant,
  seed,
  title,
  className = '',
  'data-testid': testId,
}) => {
  const resolved = resolveScenicVariant(variant ?? null, seed ?? null);
  const palette = PALETTES[resolved];
  const gradientId = `raon-sky-${useId().replace(/[^a-zA-Z0-9_-]/g, '')}`;
  const decorative = !title;

  return (
    <svg
      viewBox="0 0 480 300"
      preserveAspectRatio="xMidYMid slice"
      className={className}
      role={decorative ? undefined : 'img'}
      aria-hidden={decorative ? true : undefined}
      aria-label={decorative ? undefined : title}
      data-variant={resolved}
      data-testid={testId}
      xmlns="http://www.w3.org/2000/svg"
    >
      <defs>
        <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor={palette.skyTop} />
          <stop offset="100%" stopColor={palette.skyBottom} />
        </linearGradient>
      </defs>
      <rect x="0" y="0" width="480" height="300" fill={`url(#${gradientId})`} />
      <circle cx="380" cy="84" r="34" fill={palette.sun} opacity="0.9" />
      <path d="M60 72 q18 -14 36 0 q14 -10 28 0 M150 50 q12 -9 24 0 q10 -7 20 0" stroke="#ffffff" strokeWidth="5" fill="none" strokeLinecap="round" opacity="0.85" />
      {renderLandscape(resolved, palette)}
    </svg>
  );
};

ScenicArt.displayName = 'ScenicArt';
