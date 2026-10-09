# RAON Travel Lab — 고객 UI 설계 (템플릿 `raonslab-travel_lab` 0.1.0)

> 작업 단위: G7 Travel Lab `work-20261009-g7-symphony-max-child-c7ae42d1` (parent req_81ac33cac94046b9a2249cd14c0d00ba)
> 범위: 고객 템플릿 + 여행 모듈 관리자 레이아웃/언어. 모듈 백엔드·루트 의존성은 별도 오너. 2026-10-09 UI 통합 보정은 실제 Resource/Request/route 기준으로 수행했다.

## 1. 방향

- **밝은(LIGHT) 단일 테마**. 바다빛 청록(`raon-*`) + 노을 포인트(`dusk-*`) + 모래빛 배경(`sand-*`) + 잉크 본문(`ink-*`).
  Tailwind v4 `@theme` 로 정의(`src/styles/main.css`). 외부 웹폰트 없이 시스템 한글 글꼴 스택.
- 고객 문구는 여행 언어로 쓰고, 구현 용어는 쓰지 않는다. 예외는 **거래 테스트 고지** 하나 —
  전 화면 상단 띠, 상세·장바구니·요청 화면의 안내 박스, 요청 전 확인 체크박스, 푸터에서 반복해
  "실제 예약·결제·환불이 일어나지 않는다" 를 분명히 한다.
- 참고: lottetour.com/welcome 은 정보 구조(히어로 검색 → 지역/테마 → 기획전 → 상품 레일)만 읽기 전용으로
  짧게 참고했다. 문구·이미지·로고·색·레이아웃 코드는 가져오지 않았다.

## 2. 화면 · 경로

| 경로 | 레이아웃 | 로그인 | 핵심 |
|---|---|---|---|
| `/`, `/travel` | `travel/home` | - | 원작 일러스트 히어로 + 검색(검색어·지역) · 지역 칩(`/facets`) · 테마 카드 · 기획전 배너 · 추천/출발 임박 레일 · 진행 3단계 · 고객센터 안내 |
| `/travel/search` | `travel/search` | - | 필터(검색어·지역·테마·출발일 범위·예산 범위) + 정렬 4종 + 페이지 이동. 조건 SSoT 는 주소 query |
| `/travel/products/:id` | `travel/product` | - (담기는 로그인) | 일정 타임라인 · 출발일(좌석/마감) · 인원 스테퍼(좌석 상한) · 예상 금액 · 담기 / 비회원은 로그인 후 복귀 |
| `/travel/cart` | `travel/cart` | 필수 | 출발편 카드(인원 PATCH · 빼기 모달) · 합계 · 연락처 · 테스트 고지 확인 · 상담 요청(테스트) 제출 |
| `/travel/requests` | `travel/requests` | 필수 | 요청 카드 + 상태 배지 + 페이지 이동 |
| `/travel/requests/:id` | `travel/request_detail` | 필수 | 상태 타임라인(모의) · 결과 안내 · 항목 · 취소 모달 |
| `/travel/help` | `travel/help` | 문의 탭만 | `?tab=notices|faq|questions` — 공지 아코디언 · FAQ 즉시 검색 · 1:1 문의 작성/내역/상세(`?question=`) |
| `/login` | `auth/login` | guest_only | 이메일/비밀번호 + 2단계 인증(`two_factor_required` 200 응답) |
| 오류 | `errors/{401,403,404,500,503,maintenance}` | - | 일러스트 + 홈/뒤로/다시 불러오기 |

공통 베이스 `_user_base`: 전역 Toast 호스트(최상단) · 테스트 고지 띠 · 헤더(브랜드·메뉴·장바구니 개수 배지·로그인/로그아웃) ·
모바일 드로어 · 콘텐츠 슬롯 · 푸터 · 모바일 하단 탭바(5). 모달은 각 페이지 `modals` partial + `openModal`/`closeModal`.

## 3. 상태 설계 (모든 흐름)

| 상태 | 판정 | 화면 |
|---|---|---|
| 로딩 | `ds === undefined && !_dataSourceErrors?.ds` (progressive 소스는 도착 전 undefined) | 카드/줄 스켈레톤 `aria-busy` |
| 오류 | `!ds && _dataSourceErrors?.ds` | 안내 + **다시 시도**(`refetchDataSource`) |
| 404 | `_dataSourceErrors?.ds?.status === 404` | 상세·요청 상세에서 "찾을 수 없음" + 이동 버튼 |
| 빈 결과 | 응답 도착 + 목록 0 | 다음 행동 버튼(초기화 / 둘러보기 / 장바구니로) |
| 처리 중 | `_local.adding / submitting / busyId`, `_global.travelCartRemoving / travelCancelling` | 버튼 비활성 + 문구 변경 |

## 4. API 소비 계약 (병합된 실제 모듈 Resource 기준)

Base `/api/modules/raonslab-travel_lab`. `ResponseHelper` 외부 봉투의 `data` 아래를 소비한다.

- 카탈로그 목록 `data.data[]` / `data.pagination`, 상세 `data`; `title`, `summary` 는 현재 언어 문자열. `itinerary[].title/description` 은 번역 객체로 표시한다. 지역 jeju/gangwon/busan/seoul, 테마 nature/culture/city/wellness.
- 출발일 `id` 는 여행 `Departure.id` 이며 `product_option_id` 와 다르다. `available` 은 서버가 상품 옵션 재고·정원·보류 인원에서 계산한 인원 상한. 고객은 서버 `unit_price` 를 표시하고, 담기는 `{departure_id, quantity}` 만 보낸다.
- 장바구니 `data.items[]`: `product_name{ko,en}`, `quantity`, `unit_price`, `line_total`, `available`(bool), `remaining_capacity`, `unavailable_reason`. 합계는 실제 이커머스 Summary의 `data.totals.final_amount`. 이용 불가 항목은 접수를 차단하고 제거할 수 있다.
- 접수 `POST /inquiries {cart_ids, contact{name,phone|null}, idempotency_key}`. 내 요청 `data.data[]` / `data.pagination`, 상세 항목 `product_name{ko,en}` · `contact`. `can_cancel === true` 에서만 취소를 표시한다. `TEST_ACCEPTED` 도 서버가 허용하면 취소 가능하다.
- 상태는 `TEST_INQUIRY, UNDER_REVIEW, TEST_ACCEPTED, DECLINED, CANCELLED` 대문자 enum. 시험 요청일 뿐 실제 예약 확정이나 결제가 아니다.
- 지원 게시판 목록 `data.data[]` / `data.meta`; 목록은 본문을 주지 않으므로 공지·FAQ를 펼칠 때 `GET /support/{notices|faqs}/{id}` 에서 `data.content` 를 가져온다. 비공개 문의 상세는 `data.content`, `data.answers[][].content`, 목록 답변 여부는 `answers_count` 로 표시한다.
- 공개 카탈로그/공지/FAQ는 선택 인증, 장바구니·내 요청·개인 문의 및 관리자 쓰기는 `auth_mode: required` (Sanctum Bearer). 브라우저 표시 권한은 서버 권한 검증을 대체하지 않는다.

### 관리자 실제 계약

`/admin/travel-lab/catalog` 은 `AdminCatalogResource.title/product_code/published/departures` 와 Collection `abilities.can_update` 를 소비한다. 공개 여부는 `PATCH /admin/catalog/{product} {published}`. 가격·옵션 원본은 기존 `/admin/ecommerce/products/{product_code}/edit` 로 이동한다. 여행 UI에서 가격을 보내지 않는다.

기존 출발일 수정은 `PUT /admin/catalog/{product}/departures/{departure}`; 추가는 동일 nested 경로 `POST`. 전체 몸체는 `{product_option_id,departure_date,return_date,capacity,is_active}`. 옵션 ID는 기존 출발일에서 서버값을 유지하며 신규 출발일은 이커머스에 먼저 만든 합성 옵션 ID를 입력한다. `reserved` 는 읽기 전용, 정원 최솟값은 기존 보류 인원 이상. 옵션 소속/재고/날짜 및 동시 변경은 서버가 재검증한다.

요청 관리자 상세는 `allowed_transitions` 와 `abilities.can_update` 모두 충족해야 상태 처리를 활성화한다. 공지·FAQ·개인문의 콘텐츠는 기존 G7 관리자 게시판으로 이동한다. 각 API 가격/재고/권한/영속성의 독립 검증은 백엔드 및 브라우저 검증 담당 범위다.

## 5. 멱등 키

`travelLabEnsureInquiryKey` 는 cart id:인원 묶음의 순서 무관 지문을 저장한다. 최초 접수 직전 정규화한 이름/전화도 지문에 넣는다. 같은 묶음·연락처의 네트워크 재시도와 새로고침은 같은 키를 사용하고, 인원·연락처를 바꾸면 새 키를 사용한다. 새로고침 데이터 갱신은 제출 시 연락처를 복원하며 제출 성공 때만 키를 비운다. `onError` 에서는 키를 교체하지 않는다. 저장소가 차단되면 현재 탭 메모리에서 재사용하고, 새로고침 영속성을 보장할 수 없다.

## 6. 반응형

390px: 단일 열 · 햄버거 드로어 · 하단 탭바(본문 `pb-24`) · 필터 접기/펼치기. 1440px: `max-w-6xl` 컨테이너 · 상단 메뉴 ·
검색 좌측 고정 필터(`lg:w-72`) · 상세/장바구니/요청 우측 고정 패널(`lg:sticky`).
기존 UI child는 정적/모의 응답 미리보기 하네스의 13개 화면 상태 × 390/1440 렌더를 보고했다. 이 기록은 병합된 실제 DB/API 버전의 브라우저 E2E PASS가 아니다. 해당 독립 고정 SHA 검증은 별도 담당자가 수행한다.

## 7. 원작 자산 · 라이선스

- 풍경 일러스트 `ScenicArt` 8종(coast·mountain·city·island·forest·desert·snow·lake): raonslab 이 벡터 도형으로 직접 작성,
  인라인 SVG, MIT. 상품 이미지가 없으면 상품 id 로 결정적 선택.
- 기본 컴포넌트·Toast·Modal·Pagination 은 sirsoft-basic(MIT) 에서 가져옴. Font Awesome 6.4.0 동봉(외부 CDN 없음).

## 8. UI 통합 검증 (2026-10-09 UTC)

템플릿 로컬 어댑터 테스트는 실제 G7 레이아웃 엔진과 병합된 Resource 필드 모양의 합성 fixture를 사용한다. 이는 HTTP/API/DB/권한/독립 E2E 검증을 대체하지 않는다. 관리자 카탈로그 게시 PATCH(가격 미전송), 출발일 nested PUT(실제 Departure.id + 전체 body), 고객 가격 합계·인원 상한·멱등 재시도·연락처 변경·본문 상세 GET·답변 배열·enum 취소 계약을 확인한다.

개발 의존성은 템플릿 디렉터리에서 `npm ci --legacy-peer-deps --ignore-scripts --no-audit --no-fund --cache /tmp/g7-travel-template-npm-cache` 로 설치한다. 제공된 lockfile은 peer 자동 설치 방식으로 만들지 않아 동일 `--legacy-peer-deps` 옵션이 필요하다. 루트 React/G7 test resolver를 유지하며 루트 의존성·전역 npm 설정을 수정하지 않는다. 생성 npm 캐시는 Git에 포함하지 않는다.

최종 결과는 lead 검증/체크포인트의 SHA와 별도 연결한다. 이 파일의 로컬 작업 상태를 제품 PASS로 집계하지 않는다.

| 명령 / 검사 | 결과 | 범위 / 근거 |
|---|---|---|
| `npm run test:run` | PASS — 8 files / 110 tests | 2026-10-09 09:42 UTC, 실제 레이아웃 엔진 + 합성 Resource 형태 fixture. 관리자 catalog/departure/status 전이 3 tests 포함 |
| `npm run type-check` | PASS | 템플릿 TypeScript. 루트 dependency 변경 없음 |
| `G7_BUILD_SOURCEMAP=0 npm run build` | PASS | 35 modules, JS 37.51kB / CSS 47.03kB, 선언 파일·production bundle 재생성 |
| scoped `git diff --check` | PASS | 소유 파일 whitespace |
| 실제 API 응답 캡처 / 설치된 앱 브라우저 E2E | NOT_RUN — 별도 lead/검증 담당 | 이 어댑터 테스트에서 HTTP/DB/독립 검증 PASS를 주장하지 않음 |
| 관리자 코어 PHP layout rules / 루트 routing parity | 별도 lead 확인 | 공유 DB/루트 파일은 이 UI 작업이 수정하지 않음 |

Vitest jsdom에서 G7 navigation의 `window.scrollTo` 미구현 경고가 있으나 테스트 실패는 없다. 실제 브라우저 콘솔 확인은 별도로 필요하다. 브라우저 live API 캡처는 lead의 격리 런타임에서 수행한다.

빌드 SHA256: `dist/js/components.iife.js` = `f5c3f6ab50e8f87a75938ae4ac09fa1d1f987402c60b40424c87fd9d3db08d06`, `dist/css/components.css` = `ac16d21afc15e5dd6a77479496c9f43e36c23c020c1016aaf379cbd524a7a255`. npm 캐시는 삭제했고 Git 산출물에서 제외했다. native 작업은 commit/push/PR/배포하지 않았으며 lead가 같은 작업 브랜치의 의미 있는 체크포인트로 발행한다.

## 9. 설치된 실제 HTTP 응답의 UI replay (구현 담당자 점검)

2026-10-09 09:45 UTC의 root HTTP kernel 캡처 11 endpoints를 템플릿 `__tests__/fixtures/installed-http-responses.json` 에 의미 변경 없이 minify하여 보존했다. 설치된 격리 합성 DB의 응답만 포함하며 headers/token/env/운영 데이터는 없다. 원본 캡처 SHA256 `9e18260528f8a92ba182ae6f4bfe6c080fe4d0a205b310d33999b2b202358d27`, fixture SHA256 `b4570f8e6dc21a11641a155d785fd7ee5db7aa51b7d5fa82a4a254b09418fcdb`.

캡처 metadata `source_sha=2114703d208f260c5a63515b3a4c899afff1d46b`, `source_state=WORKING_TREE_IMPLEMENTER_CHECK` 는 HEAD 기준점 + 미커밋 구현 상태를 뜻한다. 고정 검증 SHA나 독립 Validation PASS가 아니다. `__tests__/layouts/installed-response.test.tsx` 는 이 응답들을 변형하지 않고 실제 G7 renderer에 replay한다. 제품 상세/출발일, 실제 Summary 금액 장바구니, 소유자 요청/취소의 enum·decimal 금액, 관리자 연락처/허용 전이, 공지·FAQ의 본문 없는 목록, 빈 비공개 문의를 확인한다. 설치 앱을 새로 호출하거나 DB를 수정하지 않는다.

직접 capture 기반 공지/FAQ 상세 본문, 답변이 있는 비공개 문의 상세, 관리자 카탈로그, 요청 목록 확인은 **NOT_RUN** — 해당 응답이 캡처에 없다. 기존 합성 Resource adapter 검사와 구분하며 캡처에 없는 replies/answers를 실제 확인했다고 주장하지 않는다. UI 소스 변경이 없어 이미 일치하는 production bundle은 다시 빌드하지 않았다.

결과: `npm run test:run -- __tests__/layouts/installed-response.test.tsx` **PASS — 1 file / 8 tests** (2026-10-09 09:49 UTC). 원본과 fixture JSON semantic equality PASS (11 responses). 기존 110개 테스트 실행과 이번 8개 capture replay 실행은 각각 기록하며, 독립 브라우저/고정 통합 SHA 검증은 별도다. 관리자 PHP 코어 layout rules는 lead로부터 최종 **4/4 PASS (1.411s)** 보고를 받았다.

## 10. 실제 브라우저에서 발견한 facets 계약 수정

2026-10-09 09:56 UTC, lead의 실제 홈 화면 점검은 지역/테마가 빠지고 카드에 `jeju/nature` 등 코드가 보이는 결함을 발견했다. 공개 loopback GET `/facets` 를 직접 읽어 캡처한 결과는 `data.region` / `data.theme` enum 문자열 배열인데 초기 UI가 plural `regions/themes` 를 가정했던 것이 원인이다. 실제 API 어댑터 가정을 끝까지 확인하지 못한 구현 결함으로 기록한다.

홈 지역칩/테마 카드·검색 필터는 단수형 응답을 소비하도록 수정했다. `travel.facets.region.{code}` / `travel.facets.theme.{code}` 언어키와 G7 `$t()` 로 한국어/영어를 모두 표시하며 홈/검색 카드·상품 상세 태그도 같은 번역을 사용한다. 코드는 API 필터/URL 값으로 유지한다. 별도 컴포넌트를 추가하거나 가격 계약을 변경하지 않는다.

실제 공개 응답 fixture `__tests__/fixtures/installed-facets-response.json` 는 합성 격리 API read-only 캡처다. `installed-response.test.tsx` 는 실제 facets + 카탈로그 캡처로 ko/en 각각 지역/테마 항목 수·번역 카드 표시·지역 검색 URL 진입을 확인한다. affected 검사 `home-search.test.tsx` + `installed-response.test.tsx`: **2 files/17 tests PASS**. 최종 타입검사·production build PASS (35 modules).

업데이트 전 public Chromium 실제 홈은 region chips=0/theme cards=0/pageErrors=[]로 결함을 재확인했다. Runtime 설치 반영은 lead 소유로 분리했으며 이 UI 작업이 DB나 서비스 설정을 변경하지 않는다. 반영 후 공개 브라우저 결과는 아래에 별도 기록한다. 구현자 확인이며 고정 SHA 독립 검증 PASS는 아니다.

최종 facets 수정 버전: `npm run test:run` **PASS — 9 files / 120 tests** (2026-10-09 10:01:45 UTC), unhandled errors 0. 첫 전체 실행은 assertions 120 PASS이나 환경 폐기 후 bootstrap retry의 `window is not defined` 1건으로 실패했다. 재시도 함수에 환경 폐기 guard를 넣고 재실행하여 확인했다. 타입검사·production build PASS, JS 37.54kB / CSS 47.03kB. 최종 JS SHA256 `48b73b8e173b058a593360d89000a310eaffabd7e0dfd2ea28641ddce15e6a4b` (section8의 이전 digest를 대체), actual facets capture SHA256 `16f546ca60e0e453662e47280cf9b3f4aeca61a77ff5edee77a1806a7b09c8b5`.

설치 반영 후 author public-browser 점검 (Chromium 1223, guest/read-only, loopback18871): 1440px·390px 모두 지역칩 4개(부산/강원/제주/서울), 테마 4개(도시/문화/자연/쉼·힐링). 제주 칩 클릭은 `/travel/search?region=jeju` 로 이동하고 실제 로딩이 끝난 뒤 상품 2개를 표시한다. 검색·상세에 raw enum code 노출 0, 상세 제주/자연 태그 정상, 검색 지역 드롭다운 제주 선택지 1개(모바일은 portal가 표시될 때까지 명시 대기 후 확인). 가로 넘침 0, pageErrors 0. guest 화면 개발 gear selector 관측 0 및 실제 홈/검색 화면 이미지로 확인했다.

비식별 실행 화면: `deploy/travel-lab/evidence/screens/facets-{home|search|detail}-{1440|390}.png` (6 screenshots), 측정 `facets-public-browser.json`. 실제 SPA는 URL 변경 뒤에도 잠시 progressive skeleton을 표시하므로 `catalog-results`/로딩 종료를 명시 대기해 측정했다. 첫 순간 카드 9개를 결과로 오인한 초안 캡처를 실제 완료 시점의 카드 2개 화면으로 덮어써 정정했다. 이 확인은 현재 미커밋 설치 버전의 구현자 진단이며 전체 예약·권한·재기동·고정 SHA 독립 검증을 대체하지 않는다.
