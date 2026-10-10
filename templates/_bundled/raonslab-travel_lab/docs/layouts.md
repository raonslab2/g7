# RAON 트래블랩 — 레이아웃

> 레이아웃 목록과 라우트 매핑 · 진입점: [AGENTS.md](../AGENTS.md)

## 레이아웃 목록

<!-- @generated:layouts START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
레이아웃 19개 (루트: `layouts`).

| 그룹 | 개수 |
|---|---|
| `(root)` | 1개 |
| `auth` | 1개 |
| `errors` | 6개 |
| `partials` | 2개 |
| `travel` | 9개 |

| 레이아웃 | 그룹 | 종류 | extends |
|---|---|---|---|
| `_user_base` | `(root)` | partial | - |
| `login` | `auth` | 화면 | `_user_base` |
| `401` | `errors` | 화면 | `_user_base` |
| `403` | `errors` | 화면 | `_user_base` |
| `404` | `errors` | 화면 | `_user_base` |
| `500` | `errors` | 화면 | `_user_base` |
| `503` | `errors` | 화면 | `_user_base` |
| `maintenance` | `errors` | 화면 | `_user_base` |
| `_modal_cart_remove` | `partials` | partial | - |
| `_modal_request_cancel` | `partials` | partial | - |
| `campaign_detail` | `travel` | 화면 | `_user_base` |
| `campaigns` | `travel` | 화면 | `_user_base` |
| `cart` | `travel` | 화면 | `_user_base` |
| `help` | `travel` | 화면 | `_user_base` |
| `home` | `travel` | 화면 | `_user_base` |
| `product` | `travel` | 화면 | `_user_base` |
| `request_detail` | `travel` | 화면 | `_user_base` |
| `requests` | `travel` | 화면 | `_user_base` |
| `search` | `travel` | 화면 | `_user_base` |
<!-- @generated:layouts END -->

<!-- @intent START -->
**모든 화면 레이아웃이 `_user_base` 를 상속합니다.** 베이스가 `current_user` · `travel_cart_badge`
(헤더 장바구니 개수, `auth_mode: required`) 데이터소스, `Toast` 호스트, 로그인/로그아웃, `content`
슬롯을 가지므로 자식 레이아웃에서 `toast` 가 실제로 보입니다. `extends` 없는 독립 레이아웃을 만들면
토스트 호스트가 없어 알림이 조용히 사라집니다.

- `travel/*` 7개가 본 화면입니다. 데이터는 전부 `/api/modules/raonslab-travel_lab` 데이터소스에서
  오며, 레이아웃에 목업 응답을 두지 않습니다.
- `partials/travel/_modal_cart_remove` · `_modal_request_cancel` 은 `cart` · `request_detail` 의
  `modals` 로 들어가는 확인 모달입니다. 모달은 별도 컨텍스트라 부모 `_local` 대신
  `_global.travelCartRemoveTarget` · `_global.travelCancelTargetId` 로 대상을 넘깁니다.
- `auth/login` 은 2단계 인증 사이트가 돌려주는 `two_factor_required` 형태의 200 을 판별해 코드
  입력 단계로 전환하고 `loginTwoFactor` 로 마칩니다 — 이 분기를 지우면 그 사이트에서 로그인이
  불가능해집니다.
- 오류 6종(401 · 403 · 404 · 500 · 503 · maintenance)은 `template.json` 의 `error_config` 가
  가리키는 최소 구성입니다. 401 에서 로그인 리다이렉트를 직접 구현하지 않습니다(코어 가드가 처리).

**고객센터 바인딩은 가정에 기대고 있습니다.** `travel/help` 는 공지·FAQ·문의 응답을 게시판 글
Resource 형태(`title` · `content`(`content_plain` 우선) · `created_at`)로, 답변을
`answer.content` → `replies[0].content` → `comments[0].content`, 답변 여부를
`is_answered` → `answered` → `replies_count` 순으로 읽습니다. 최종 모듈이 다르면 이 파일의
바인딩을 고칩니다.

레이아웃 JSON 만 고쳤다면 빌드 없이 `php artisan template:update raonslab-travel_lab --force`
로 반영합니다. 단 새 Tailwind 클래스를 썼다면 재빌드가 필요합니다.
<!-- @intent END -->

## 라우트 매핑

<!-- @generated:layout-map START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 경로 | 레이아웃 | 이름 |
|---|---|---|
| `/` | `travel/home` | - |
| `/travel` | `travel/home` | - |
| `/travel/search` | `travel/search` | - |
| `/travel/products/:id` | `travel/product` | - |
| `/travel/campaigns` | `travel/campaigns` | - |
| `/travel/campaigns/:slug` | `travel/campaign_detail` | - |
| `/page/:slug` | `travel/campaign_detail` | - |
| `/travel/cart` | `travel/cart` | - |
| `/travel/requests` | `travel/requests` | - |
| `/travel/requests/:id` | `travel/request_detail` | - |
| `/travel/help` | `travel/help` | - |
| `/login` | `auth/login` | - |
<!-- @generated:layout-map END -->

<!-- @intent START -->
**공개 / 로그인 전용의 경계**가 이 표의 핵심입니다. 홈 · 검색 · 상품 상세 · 고객센터는
`auth_required: false`, 장바구니 · 상담 요청 목록/상세는 `auth_required: true` 입니다.
고객센터는 공지·FAQ 를 누구에게나 보여주고, 1:1 문의 영역만 화면 안에서 로그인을 안내합니다
(문의 데이터소스는 `auth_mode: required`). 상품 상세의 "장바구니 담기" 도 로그인 전용 API 를
부릅니다. `/login` 은 `guest_only` 라 로그인한 사용자는 들어오지 않습니다.

`/` 와 `/travel` 이 같은 `travel/home` 을 가리킵니다 — 이 템플릿을 활성화하면 사이트 첫 화면이
여행 홈이 됩니다.

검색(`/travel/search`)과 상담 요청 목록(`/travel/requests`)은 **목록 클러스터**입니다. 필터·정렬·
페이지 이동과 상세 왕복은 `navigate` + `mergeQuery: true`(리터럴)로 URL 쿼리를 보존하고, 홈에서
새 검색으로 들어가는 것처럼 의도적으로 리셋하는 이동은 `audit:allow
layout-list-context-navigate-merge-query` 주석을 남깁니다. 앱 내 이동은 `A href` 가 아니라
`Button` + `navigate` 입니다.

라우트를 바꾼 뒤에는 `template:update --force` 로 반영하고, 계약 테스트의 "라우트" 묶음(경로
존재 · 레이아웃 파일 존재 · 인증 경계)을 함께 갱신합니다.
<!-- @intent END -->

## 확장 오버라이드

<!-- @generated:template-overrides START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_오버라이드하는 레이아웃 확장 조각이 없습니다._
<!-- @generated:template-overrides END -->

<!-- @intent START -->
없습니다. 이 템플릿은 다른 확장이 제공한 레이아웃 조각을 대체하지 않습니다.

여행 화면에 필요한 것은 전부 이 템플릿의 `travel/*` 레이아웃이 여행 모듈 API 를 직접 불러
그립니다. 이커머스·게시판·페이지 모듈은 의존으로 선언되어 있지만, 그 모듈들의 방문자 화면 조각을
이 템플릿 안에 끼워 쓰지 않습니다.

나중에 오버라이드를 둔다면 `extensions/{확장}/` 에 원본과 같은 이름의 파일을 둡니다. 원본이 바뀌어도
사본은 따라가지 않으므로 그 확장을 업그레이드할 때마다 동작을 확인해야 합니다.
<!-- @intent END -->
