# 그누보드7 RAON 트래블랩 템플릿 — 에이전트 가이드

> 이 문서는 이 템플릿을 수정하는 에이전트·확장개발자를 위한 것입니다. 도입 검토·운영 관점은 [README.md](README.md) 를 보세요.

## TL;DR (5초 요약)

```text
1. 유형: 템플릿 (raonslab-travel_lab, type=user, v0.1.0) — 여행 고객 화면(탐색·출발일/인원·장바구니·상담 요청(테스트)·고객센터). 데이터는 별도 모듈 `raonslab-travel_lab` 의 API(/api/modules/raonslab-travel_lab)에서 온다
2. 확장 방식: 훅 없음 — 확장점은 레이아웃의 data_sources 선언 · composite 3종(ScenicArt·PriceTag·StatusBadge) · 전용 핸들러 2개(travelLab*)
3. 건드리면 안 되는 것: 가격·금액을 요청 body 에 싣기, 오류 시 멱등 키 재생성, 통화 하드코딩, 레이아웃에 목업 응답, 외부 CDN·제3자 여행 사이트 자산
4. 작업 위치: `templates/_bundled/raonslab-travel_lab` — 활성 디렉토리 직접 수정 금지
5. 반영: `php artisan template:update raonslab-travel_lab --force`
```

## 1. 이 확장은 무엇인가

<!-- @intent START -->
**여행 상품 고객 화면 템플릿**입니다. 별도로 빌드되는 모듈 `raonslab-travel_lab` 이 여행 도메인
API 를 제공하고, 이 템플릿은 그 API 를 `data_sources` · `apiCall` 로 불러 화면만 그립니다 —
서버 코드는 없습니다.

도메인 대응이 이 템플릿을 읽는 열쇠입니다. **여행 상품 = 이커머스 상품(product id)**,
**출발편 = 이커머스 상품 옵션(ProductOption)**, **인원 = 장바구니 수량(Cart.quantity)** 입니다.
그래서 manifest 가 여행 모듈과 함께 `sirsoft-ecommerce`(>=1.2.1) · `sirsoft-board`(>=1.1.2) ·
`sirsoft-page`(>=1.1.2) 를 의존으로 선언합니다.

화면은 홈(`/`·`/travel`) · 검색(`/travel/search`) · 상품 상세 · 여행 장바구니 · 상담 요청 목록/상세 ·
고객센터(공지·FAQ·1:1 문의) · 로그인과 오류 6종입니다. 장바구니·상담 요청은 로그인 전용
(`auth_required: true`)이고, 고객센터는 공개이되 1:1 문의만 화면 안에서 로그인을 요구합니다.

**설계 원칙**

- **상담 요청은 시뮬레이션입니다.** 상태(`TEST_INQUIRY` · `UNDER_REVIEW` · `TEST_ACCEPTED` ·
  `DECLINED` · `CANCELLED`)는 전부 테스트용이며 실제 예약·결제·환불은 일어나지 않습니다.
  장바구니 화면은 테스트 고지 확인 체크박스를 켜야 요청 버튼이 열립니다.
- **가격은 서버가 계산합니다.** 장바구니 담기는 `departure_id` · `quantity` 만, 상담 요청은
  `cart_ids` · `contact` · `idempotency_key` 만 보냅니다.
- **멱등 키로 중복 요청을 막습니다.** 키는 장바구니 로드 시 확보하고 성공 시에만 폐기합니다
  (흐름은 3절, 상세는 [docs/handlers.md](docs/handlers.md)).
- **통화를 전제하지 않습니다.** 금액은 `PriceTag` 가 서버의 `currency_code` 로 포맷합니다.
- **자체 제공 자산만 씁니다.** 이미지 대신 원작 인라인 SVG(`ScenicArt`), 아이콘은 동봉한
  Font Awesome, 글꼴은 시스템 글꼴 스택 — 외부 CDN 요청이 없습니다.
- 밝은 단일 테마(`dark_mode: false`), 390–1440px 반응형입니다.

**의도적으로 하지 않는 것**: 실제 예약·결제·환불 · 다크 모드 · 서버 코드(라우트·모델) ·
레이아웃 안의 목업 응답 · 제3자 여행 사이트의 이미지·문구·로고 차용 · 확장 오버라이드.
<!-- @intent END -->

## 2. 디렉토리 지도

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

## 3. 핵심 흐름

<!-- @intent START -->
서버 코드가 없으므로 흐름은 **라우트 → 레이아웃 → 데이터소스/apiCall → 여행 모듈 API** 입니다.
모든 여행 API 는 표준 base `/api/modules/raonslab-travel_lab` (v1 계약) 아래에 있고, 로그인
전용 호출은 데이터소스·`apiCall` 모두 `auth_mode: "required"` 로 Sanctum Bearer 토큰을 싣습니다.

**1. 탐색 → 상세 → 장바구니 담기**

- `travel/home`: `facets` · `featured_trips`(`GET /catalog?sort=recommended&per_page=6`) ·
  `departing_trips`(`sort=departure_asc&per_page=3`).
- `travel/search`: `GET /catalog` 에 URL 쿼리(`q` · `region` · `theme` · `date_from` · `date_to` ·
  `min_price` · `max_price` · `sort` · `page` · `per_page`)를 그대로 하달 → `data.data` +
  `data.pagination`. 필터·정렬·페이지 이동은 `navigate` + `mergeQuery: true` 로 URL 을 바꿉니다.
  `facets` 의 실제 공개 응답은 `data.region` / `data.theme` (단수형) enum 문자열 배열입니다. 홈·검색 진입과 카드·상세 enum 표시는 템플릿 `travel.facets.{region|theme}.{code}` 언어키로 번역합니다. plural 필드를 가정하지 않습니다.
- `travel/product`: `GET /catalog/{id}` + `GET /catalog/{id}/departures`. 출발편과 인원을 고르고
  `POST /cart` 에 `{departure_id, quantity}` 만 보냅니다 — 금액은 보내지 않습니다.

**2. 장바구니 → 상담 요청(테스트)**

- `travel/cart` 의 `cart` 데이터소스(`GET /cart`) `onSuccess` 가 `travelLabEnsureInquiryKey` 를
  `cartIds` · `quantities` 로 부릅니다. 같은 id:인원 묶음이면 `sessionStorage`
  (`raon_travel_inquiry_key`)의 키를 재사용하고, 바뀌면 새 키를 만들어 `_global.travelInquiryKey`
  에 둡니다.
- 인원 변경은 `PATCH /cart/{id}`, 삭제는 `_modal_cart_remove` 모달의 `DELETE /cart/{id}`.
  변경 뒤 `cart` 를 다시 불러오면 지문이 바뀌어 새 키가 됩니다.
- 요청 버튼은 항목 존재 · 테스트 고지 확인(`ackTest`) · 이름 · 키 확보가 모두 갖춰져야 열립니다.
  `POST /inquiries` body 는 `{cart_ids, contact{name, phone?}, idempotency_key}`.
- 성공 시에만 `travelLabClearInquiryKey` → 헤더 배지(`travel_cart_badge`) 재조회 →
  `/travel/requests/{id}` 로 이동. 실패 시에는 `submitting` 해제와 토스트뿐이며 **키는 그대로**
  라 재시도가 같은 키를 실어 서버가 중복을 막습니다.

**3. 요청 확인·취소와 고객센터**

- `travel/requests`(`GET /inquiries`) · `travel/request_detail`(`GET /inquiries/{id}`).
  취소는 `_modal_request_cancel` 모달의 `POST /inquiries/{id}/cancel`. 상태는 `StatusBadge` 가
  `travel.status.*` 문구로 표시합니다.
- `travel/help`: `GET /support/notices` · `/support/faqs` 는 공개, `/support/questions`
  (목록·작성) · `/support/questions/{id}` 는 로그인 전용.

**로그인**: `auth/login` 은 `guest_only` 이고, 2단계 인증이 켜진 사이트가 돌려주는
`two_factor_required` 형태의 200 을 받으면 코드 입력 단계로 전환해 `loginTwoFactor` 로 마칩니다.
<!-- @intent END -->

## 4. 확장점

<!-- @generated:extension-points-summary START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 확장점 | 수 | 상세 |
|---|---|---|
| 제공 컴포넌트 | 29개 | [제공 컴포넌트](docs/components.md#제공-컴포넌트) |
| 레이아웃 | 17개 | [레이아웃 목록](docs/layouts.md#레이아웃-목록) |
| 전용 핸들러 | 2개 | [템플릿 전용 핸들러](docs/handlers.md#템플릿-전용-핸들러) |
| 확장 오버라이드 | 0개 | [확장 오버라이드](docs/layouts.md#확장-오버라이드) |
<!-- @generated:extension-points-summary END -->

<!-- @intent START -->
템플릿이라 발행·구독하는 훅이 없습니다. 이 템플릿을 고치지 않고 동작을 바꾸는 길은 **데이터를
주는 쪽**입니다.

- **여행 모듈 API 응답**이 화면을 결정합니다. 바인딩은 일부러 관대하게 짜여 있어(예: 장바구니
  항목 제목 `item.title ?? item.product?.title`, 출발편 `data` 배열 또는 `data.data`) 모듈 쪽
  응답 형태의 작은 차이는 흡수하지만, 계약 밖으로 벗어나면 화면이 조용히 빕니다.
- **지역·테마 목록**은 `GET /facets` 가 정합니다 — 레이아웃에 지역명을 하드코딩하지 않습니다.
- **상태 문구**는 `lang/{ko,en}.json` 의 `travel.status.*` 가 정하고, 모르는 상태값은
  `travel.status.unknown` 으로 내려갑니다.
- 다른 확장이 이 템플릿 화면에 조각을 끼워 넣을 때는 `components.json` 에 있는 29개 컴포넌트만
  쓸 수 있습니다.
<!-- @intent END -->

## 5. 수정 시 동반 의무

- [ ] `_bundled` 에서만 수정하고 `php artisan template:update raonslab-travel_lab --force` 로 반영
- [ ] manifest version 상향 시 `package.json` · `package-lock.json` 동기화 + CHANGELOG 기재
- [ ] 레이아웃 JSON 변경 시 빌드 없이 update 만 — 신규 Tailwind 클래스는 빌드된 CSS 에 존재하는지 확인
- [ ] TSX/TS 변경 시 `--production` 재빌드 후 `dist/` 커밋 (sourceMappingURL 잔존 금지)
- [ ] 다국어 키 추가 시 ko·en 동시 반영 + 번들 ja 언어팩 증분 동기화
- [ ] 여행 모듈(`raonslab-travel_lab`) API 계약이 바뀌면 바인딩을 함께 고치고, 의존 버전 제약(`dependencies.modules`)을 상향
- [ ] 요청 body 에 가격·금액 필드를 추가하지 않는다 — `__tests__/layouts/contract.test.ts` 가 차단한다
- [ ] 멱등 키 흐름(장바구니 로드 시 확보 · 성공 시에만 폐기)을 바꾸면 `__tests__/components/inquiryKey.test.ts` 와 계약 테스트를 함께 갱신
- [ ] 목록 클러스터(검색·요청 목록) 이동은 `mergeQuery: true` 리터럴, 의도적 리셋은 `audit:allow` 주석
- [ ] 컴포넌트를 추가·삭제했다면 소스 · `src/index.ts` export · `template.json` 레지스트리 · `components.json` 을 함께 갱신
- [ ] 레이아웃에 새 Tailwind 클래스를 썼다면 재빌드 — `@source "../../layouts"` 스캔이 빌드 시점에만 돈다
- [ ] 외부 CDN·제3자 이미지 URL 을 추가하지 않는다 — 아이콘은 `dist/vendor/` 동봉본, 그림은 `ScenicArt`
- [ ] `data_source` 를 늘렸다면 [`docs/editor-spec.md`](docs/editor-spec.md) 확인 — 편집기 스펙이 없어 그 자리가 편집기 캔버스에서 빈 화면이 된다

## 6. 금지 패턴

<!-- @intent START -->
| 금지 | 올바른 사용 | 이유 |
|---|---|---|
| 장바구니·상담 요청 body 에 `price` · `amount` · `total` 등 금액 필드 | `departure_id` · `quantity` / `cart_ids` · `contact` · `idempotency_key` 만 | 금액은 서버가 출발편 기준으로 계산한다. 클라이언트 금액은 신뢰할 수 없다 |
| `onError` 에서 멱등 키 재생성(`travelLabEnsureInquiryKey` 재호출·`travelLabClearInquiryKey`) | 오류 시 키를 건드리지 않고, 폐기는 성공 `onSuccess` 에서만 | 네트워크 오류는 서버 접수 여부를 모른다 — 새 키로 재시도하면 같은 요청이 두 번 접수된다 |
| 금액 뒤에 `원` · `円` 을 붙이거나 통화 코드 리터럴 | `PriceTag` 에 서버 `currency_code` 전달 | 통화는 설정이 정한다. 단위만 틀린 금액이 오류 없이 나간다 |
| 앱 내 이동에 `A href` | `Button` + `navigate` (`params.path`) | SPA 라우팅·목록 컨텍스트(`mergeQuery`) 보존 |
| 레이아웃에 목업 응답·하드코딩 상품 데이터 | 표준 base `/api/modules/raonslab-travel_lab` 데이터소스 | 계약 테스트가 차단 — 목업은 운영에서 거짓 화면이 된다 |
| `mergeQuery: "{{조건}}"` 표현식 | boolean 리터럴 `true`, 리셋은 `audit:allow` 주석 | 분기마다 목록 상태 보존 여부가 갈린다 |
| 제3자 여행 사이트의 사진·문구·로고 차용, 외부 CDN 자산 | `ScenicArt` 원작 SVG · 동봉 Font Awesome · 시스템 글꼴 | 저작권 · 폐쇄망에서 화면 기능이 조용히 사라짐 |
| 상담 요청을 실제 예약·결제로 안내 | "테스트" 고지와 `ackTest` 확인 유지 | 모든 상태가 시뮬레이션이다 |
| 활성 디렉토리(`templates/raonslab-travel_lab`) 직접 수정 | `_bundled` 수정 후 `template:update --force` | 다음 update 에서 덮어써진다 |
<!-- @intent END -->

## 7. 테스트 실행

<!-- @generated:test-commands START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 종류 | 개수 | 위치 |
|---|---|---|
| PHPUnit | 0개 | — |
| Vitest | 7개 | `vitest.config.ts` |
| Playwright | 0개 | — |
| 시나리오 매니페스트 | 1개 | `tests/scenarios` |

```bash
# Vitest (확장 디렉토리에서) (PowerShell)
cd templates/_bundled/raonslab-travel_lab && powershell -Command "npm run test:run -- <대상>"

```

무필터 전체 실행은 금지되어 있습니다 — 변경 범위에 걸리는 대상만 지정해 실행합니다.
<!-- @generated:test-commands END -->

## 8. 문서 목차

<!-- @generated:docs-index START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 문서 | 내용 | 상태 |
|---|---|---|
| [docs/README.md](docs/README.md) | 문서 통합 목차와 실측 집계 | ✅ |
| [docs/architecture.md](docs/architecture.md) | 설계 의도·계층 지도·디렉토리 맵 | ✅ |
| [docs/components.md](docs/components.md) | 템플릿이 제공하는 컴포넌트 | ✅ |
| [docs/layouts.md](docs/layouts.md) | 레이아웃 목록과 라우트 매핑 | ✅ |
| [docs/handlers.md](docs/handlers.md) | 템플릿 전용 핸들러와 부트스트랩 | ✅ |
| [docs/editor-spec.md](docs/editor-spec.md) | 레이아웃 편집기에 선언한 팔레트·컨트롤·샘플 데이터 | ✅ |
| [CHANGELOG.md](CHANGELOG.md) | 변경 이력 | ✅ |
<!-- @generated:docs-index END -->
