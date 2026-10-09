# RAON 트래블랩 — 아키텍처

> 설계 의도와 계층 구조 · 진입점: [AGENTS.md](../AGENTS.md)

## 설계 의도

<!-- @intent START -->
목표는 **여행 도메인 고객 화면을 서버 코드 없이** 그리는 것입니다. 여행 데이터·규칙은 별도
모듈 `raonslab-travel_lab` 이 소유하고(API base `/api/modules/raonslab-travel_lab`, v1 계약),
그 모듈은 다시 이커머스 위에 올라갑니다 — **여행 = 상품(product id) · 출발편 = 상품 옵션
(ProductOption) · 인원 = 장바구니 수량(Cart.quantity)**. 템플릿은 이 대응을 몰라도 되도록
모듈 API 만 봅니다.

설계 결정과 그 이유:

- **금액은 표시만 한다.** 장바구니 담기·상담 요청 body 에 금액 필드가 없습니다. 서버가
  출발편 기준으로 계산하며, 클라이언트가 보낸 금액은 신뢰할 근거가 없기 때문입니다.
  표시는 `PriceTag` 가 서버의 `currency_code` 로 하므로 특정 통화를 전제하지 않습니다.
- **상담 요청은 시뮬레이션이다.** 다섯 상태(`TEST_INQUIRY` · `UNDER_REVIEW` · `TEST_ACCEPTED` ·
  `DECLINED` · `CANCELLED`) 모두 실제 예약·결제·환불과 무관합니다. 화면은 테스트 고지 확인
  (`ackTest`)을 요청 조건으로 둡니다.
- **중복 요청은 멱등 키로 막는다.** 키 수명주기를 레이아웃 표현식이 아니라 전용 핸들러 2개에
  둔 이유는, 세션 저장소에 지문과 함께 보관해 **새로고침 뒤 재시도도 같은 키**를 싣기 위해서입니다.
- **바인딩은 관대하게.** 모듈이 별도로 개발되므로 응답 형태의 작은 차이(`item.title` /
  `item.product.title`, 출발편 `data` 배열 / `data.data`, facets 객체 / 문자열)를 대체 경로로
  흡수합니다. 대신 계약 밖이면 화면이 조용히 빕니다.
- **구동 자산은 자체 제공.** 이미지 대신 원작 인라인 SVG(`ScenicArt`, raonslab 저작 · MIT),
  Font Awesome 은 `dist/vendor/` 동봉, 글꼴은 시스템 스택 — 외부 CDN 요청이 없습니다.
- **밝은 단일 테마**(`features.dark_mode: false`), 390–1440px 반응형.

**의도적으로 하지 않는 것**: 실제 예약·결제·환불 · 다크 모드 · 서버 코드 · 레이아웃 목업 응답 ·
제3자 여행 사이트 자산 차용 · 확장 오버라이드.
<!-- @intent END -->

## 계층 지도

<!-- @intent START -->
```
template.json          manifest — type: user · dark_mode: false · 컴포넌트 레지스트리 · 오류 레이아웃 6종
                       externals: fontawesome (dist/vendor/font-awesome/6.4.0, 동봉)
routes.json            /, /travel, /travel/search, /travel/products/:id, /travel/help  (공개)
                       /travel/cart, /travel/requests, /travel/requests/:id           (auth_required)
                       /login                                                          (guest_only)
     │
layouts/_user_base.json      공통 뼈대 — current_user · travel_cart_badge 데이터소스, Toast 호스트, content 슬롯
     │  extends
     ├─ travel/{home,search,product,cart,requests,request_detail,help}.json
     │      └─ modals: partials/travel/_modal_cart_remove · _modal_request_cancel
     ├─ auth/login.json         login → (two_factor_required 200) → loginTwoFactor
     └─ errors/{401,403,404,500,503,maintenance}.json
     │
     │  data_sources / apiCall (auth_mode: required → Sanctum Bearer)
     ▼
/api/modules/raonslab-travel_lab/{catalog, facets, cart, inquiries, support/*}   ← 별도 모듈
     │
src/index.ts           컴포넌트 export(전역 RaonslabTravelLab) + initTemplate()
src/handlers/          travelLabEnsureInquiryKey · travelLabClearInquiryKey (sessionStorage + _global.travelInquiryKey)
src/components/        basic 23 · composite 6 (Toast·Modal·Pagination ← sirsoft-basic, ScenicArt·PriceTag·StatusBadge 고유)
src/styles/main.css    Tailwind v4 — @source "../../layouts" 로 레이아웃 JSON 스캔 → dist/css/components.css
dist/                  커밋되는 빌드 산출물 (js/css/vendor)
__tests__/             components(단위·핸들러) · layouts(실 컴포넌트 렌더 + 정적 계약 테스트)
```

**서버 코드가 없습니다.** 이 템플릿만으로는 모든 화면이 비어 있고, manifest 가 여행 모듈과
이커머스·게시판·페이지 모듈을 의존으로 선언하는 이유입니다.

**레이아웃 JSON 은 vite 모듈 그래프 밖**이라 `main.css` 의 `@source "../../layouts"` 로 스캔
대상을 선언합니다. 레이아웃에 새 Tailwind 클래스를 쓰면 빌드를 다시 해야 CSS 에 들어갑니다.

빌드는 `cd templates/_bundled/raonslab-travel_lab && G7_BUILD_SOURCEMAP=0 npm run build` 또는
`php artisan template:build raonslab-travel_lab --production`, 반영은
`php artisan template:update raonslab-travel_lab --force` 입니다. 테스트는
`cd templates/_bundled/raonslab-travel_lab && npx vitest run` — 컴포넌트·핸들러 단위, 실제
컴포넌트로 렌더하는 레이아웃 테스트, 매니페스트·라우트·다국어·API·멱등성을 고정하는 정적 계약
테스트(`__tests__/layouts/contract.test.ts`)로 나뉩니다.
<!-- @intent END -->

## 디렉토리

<!-- @generated:directory-map START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 경로 | 역할 | 수정 시 필요한 절차 |
|---|---|---|
| `template.json` | manifest (버전 SSoT) | version 변경 시 package.json·package-lock.json 동기화 |
| `routes.json` | 라우트 → 레이아웃 매핑 | `php artisan template:update raonslab-travel_lab --force` |
| `layouts/` | 레이아웃 JSON | `php artisan template:update raonslab-travel_lab --force` (빌드 불필요) |
| `src/components/` | React 컴포넌트 | `php artisan template:build` → `php artisan template:update raonslab-travel_lab --force` |
| `src/handlers/` | 템플릿 전용 액션 핸들러 | `php artisan template:build` → `php artisan template:update raonslab-travel_lab --force` |
| `dist/` | 커밋되는 빌드 산출물 | `--production` 으로 재빌드 (sourceMappingURL 잔존 금지) |
| `tests/` | 테스트 | 변경 범위만 필터 실행 |
| `CHANGELOG.md` | 변경 이력 | 버전 상향 시 항목 추가 (미기재 시 버전 상향 불가) |
| `components.json` | 편집기 컴포넌트 선언 (레이아웃 저작자가 읽는 props 계약) | `php artisan template:update raonslab-travel_lab --force` |
| `docs/` | 개발자 문서 | 표면 변경 시 `php artisan ext:docgen` 재실행 |
| `lang/` | 다국어 | 키 추가 시 ko·en 동시 반영 + 번들 ja 팩 동기화 |
<!-- @generated:directory-map END -->
