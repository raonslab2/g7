# RAON Travel Lab — 고객 UI 설계 (템플릿 `raonslab-travel_lab` 0.1.0)

> 작업 단위: G7 Travel Lab `work-20261009-g7-symphony-max-child-c7ae42d1` (parent req_81ac33cac94046b9a2249cd14c0d00ba)
> 범위: `templates/_bundled/raonslab-travel_lab/**` — 모듈 백엔드·관리자 화면·루트 의존성은 범위 밖

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

## 4. API 소비 계약 (v1, base `/api/modules/raonslab-travel_lab`, ResponseHelper 봉투)

- `GET /catalog?q&region&theme&date_from&date_to&min_price&max_price&sort&page&per_page` → `data.data[]` + `data.pagination`
  (`last_page` null 허용 — 이전/다음만 노출). 항목: `id(=ecommerce product id), title, region, theme, duration_days, summary,
  itinerary, from_price, currency_code, image_url, departures[]` (`region_label`/`theme_label` 있으면 우선 표시).
- `GET /catalog/{product}` → `data`; `GET /catalog/{product}/departures` → `data[]` 또는 `data.data[]`
  (`id, product_id, product_option_id, departure_date, return_date, available, unit_price, currency_code`).
- `GET /facets` → `data.regions[]`, `data.themes[]` (`{value,label,count?}` 또는 문자열).
- `GET /cart` → `data.items[]` + `data.totals{quantity, amount}` + `data.currency_code`. 항목 키는 관대하게 받음
  (`title|product.title`, `departure_date|departure.departure_date`, `line_total|unit_price×quantity`, 좌석 상한 `remaining|available_seats`).
- `POST /cart {departure_id, quantity}` · `PATCH /cart/{cart} {quantity}` · `DELETE /cart/{cart}` — **가격 미전송**.
- `POST /inquiries {cart_ids, contact{name, phone|null}, idempotency_key}` → 성공 시 `data.id` 로 상세 이동.
- `GET /inquiries`(페이지) · `GET /inquiries/{id}` · `POST /inquiries/{id}/cancel`. 취소 버튼은 `can_cancel` 이 오면 그 값,
  없으면 `TEST_INQUIRY|UNDER_REVIEW` 일 때만.
- 상태: `TEST_INQUIRY, UNDER_REVIEW, TEST_ACCEPTED, DECLINED, CANCELLED` — 모두 시뮬레이션 문구.
- 고객센터(계획 API, **어댑터 가정** — 최종 자식 결과가 다르면 `layouts/travel/help.json` 바인딩만 맞춘다):
  `GET /support/notices`, `GET /support/faqs` → 게시판 Resource 봉투 `data.data[] {id,title,content_plain|content_text|excerpt|content,created_at}`;
  인증 `GET/POST /support/questions {title, content}`, `GET /support/questions/{id}` — 답변은 `answer.content | answer_content | replies[0].content | comments[0].content`,
  답변 여부는 `is_answered | answered | answer | replies_count/comments_count>0`.
- 인증: 데이터 소스·apiCall 모두 `auth_mode: required`(Sanctum Bearer). 별도 인증을 흉내 내지 않는다.

## 5. 멱등 키

`travelLabEnsureInquiryKey`(장바구니 데이터 소스 onSuccess, 인자 `cartIds`+`quantities`) 가 (id:인원) 묶음 지문별로
sessionStorage `raon_travel_inquiry_key` 에 키를 보관하고 `_global.travelInquiryKey` 로 노출한다. 같은 묶음 → 같은 키
(네트워크 오류 재시도·새로고침), 묶음/인원 변경 → 새 키, 제출 성공 → `travelLabClearInquiryKey` 로 폐기. 오류 경로는
키를 만들거나 지우지 않는다. 키가 없으면 제출 버튼은 비활성.

## 6. 반응형

390px: 단일 열 · 햄버거 드로어 · 하단 탭바(본문 `pb-24`) · 필터 접기/펼치기. 1440px: `max-w-6xl` 컨테이너 · 상단 메뉴 ·
검색 좌측 고정 필터(`lg:w-72`) · 상세/장바구니/요청 우측 고정 패널(`lg:sticky`).
검증: 미리보기 하네스(Playwright, Chrome) 로 13개 화면 상태 × 390/1440 렌더 — 콘솔 오류 0, 390px 가로 넘침 0.

## 7. 원작 자산 · 라이선스

- 풍경 일러스트 `ScenicArt` 8종(coast·mountain·city·island·forest·desert·snow·lake): raonslab 이 벡터 도형으로 직접 작성,
  인라인 SVG, MIT. 상품 이미지가 없으면 상품 id 로 결정적 선택.
- 기본 컴포넌트·Toast·Modal·Pagination 은 sirsoft-basic(MIT) 에서 가져옴. Font Awesome 6.4.0 동봉(외부 CDN 없음).
