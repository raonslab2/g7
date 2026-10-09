# 여행 기획전 (native 페이지 고정 두 슬롯)

> 진입점: [AGENTS.md](../AGENTS.md) · API: [api/campaigns.md](api/campaigns.md) · 시나리오: [tests/scenarios/campaigns.yaml](../tests/scenarios/campaigns.yaml) · 증거: [tests/CAMPAIGN_EVIDENCE.md](../tests/CAMPAIGN_EVIDENCE.md)

## 무엇을 하는가

기획전 문서는 **native `sirsoft-page` Page 두 개**입니다. 제목·본문·발행·버전·활동 로그·SEO/사이트맵은 전부 페이지 모듈이 소유하고, 이 모듈은 다음만 소유합니다.

| 소유 | 내용 |
|---|---|
| 레지스트리 `config/campaigns.php` | 키·slug·테마/정렬 enum·아트 변형. `CampaignRegistry` 가 파일을 직접 읽어 정확히 두 슬롯만 허용합니다(런타임 `config()` 덮어쓰기 무시) |
| 공개 투영 `GET /campaigns`, `GET /campaigns/{slug}` | `CampaignPageRepositoryInterface` → native `PageService::getPublishedPageBySlug($slug, false)` (리터럴 false, 관리자도 동일) |
| 관리자 어댑터 `/admin/travel-lab/campaigns` | native `GET /api/modules/sirsoft-page/admin/pages` 결과를 레지스트리 두 slug 와 정확 일치로만 매핑, native 편집·상세(버전·복원)·발행 API 로 연결 |
| 명시 프로비저닝 `raonslab-travel_lab:campaigns-provision` | 없는 슬롯만 `PageService::createPage` 로 합성 생성, 기존 Page 는 무조건 건너뜀 |
| SEO 리스너 `InvalidateTravelCampaignSeoCache` | 두 slug 의 native 훅에서 여행 홈·기획전 목록·상세·`/page/{slug}` 캐시를 비움 |

| 슬롯 | Page slug | 카탈로그 필터 |
|---|---|---|
| autumn-escape | `travel-lab-campaign-autumn-escape` | `theme=nature&sort=recommended` |
| weekend-reset | `travel-lab-campaign-weekend-reset` | `theme=wellness&sort=recommended` |

네 테마(자연·문화·도시·웰니스) 탐색은 기존 카탈로그/facets/검색이 그대로 맡습니다. 테마 가이드 Page·분류 CRUD 는 만들지 않습니다.

## 공개 투영 계약

- 목록 `data.items`: 레지스트리 순서의 **발행 슬롯만**. 필드 `slug, kind=campaign, theme, title, excerpt, current_version, published_at, path, catalog_query, art_variant`. 본문은 싣지 않고 서버가 만든 평문 발췌(태그·엔티티 제거, 140자)만 싣습니다.
- 상세: 위 필드 + `content`(현재 로케일, native fallback) · `content_mode` · `updated_at`. 작성자·수정자·버전 목록·첨부·서명 URL·`seo_meta`·미리보기 플래그는 없습니다.
- 미발행·부재·레지스트리 밖(접두사가 같아도)·형식 불일치 slug 는 비회원·회원·페이지 관리자 모두 같은 404 입니다. 목록에 `slug`·`slugs`·`search`·`q`·`prefix`·`ids`·`filters`·`published`·`preview` 를 보내면 422, 상세에 `preview` 를 보내면 422 입니다.
- 미들웨어: `TravelOptionalSanctum` → `TravelThrottleRequests::with(600, 1, 'travel-lab-campaign-public:')`. 기존 문의·장바구니·지원 버킷은 공유하지 않습니다. 응답 캐시는 없습니다.
- slug 를 바꾸거나 Page 를 지우면 그 문서는 슬롯에서 떨어져 나가 기획전 목록에서 사라지고 이전 상세는 404 입니다. 요청 경로·기본 시드가 다시 만들지 않습니다.

## 관리자 어댑터

`permissions: ["sirsoft-page.pages.read"]`, `_admin_base`. 데이터소스는 native 목록에 `filters[0][field]=slug&filters[0][operator]=starts_with&filters[0][value]=travel-lab-campaign-&per_page=100` 을 보냅니다. `PageListRequest` 가 허용하는 문법이지만 **native 저장소는 operator 를 무시하고 `LIKE %값%` 으로 찾습니다** — 그래서 화면은 두 slug 와 정확히 같은 행만 쓰고, 부분일치 다른 행은 표시하지 않습니다. 결과가 여러 페이지이고 슬롯이 보이지 않으면 다음 결과 페이지를 안내하며, 보이지 않음은 "이 계정 범위에서 보이지 않음"이지 전역 부재가 아닙니다.

- 만들기: `/admin/pages/create?slug=<슬롯>` — native 폼이 `form.slug` 를 채웁니다. native 폼 기본 `content_mode` 는 html 이므로 첫 본문은 편집기의 HTML 사용을 끄고 텍스트로 저장하라는 안내를 둡니다(쿼리가 모드를 바꾸지는 않습니다). 같은 slug 가 이미 있으면 native 422 가 그대로 보입니다.
- 편집·상세(버전·복원): `/admin/pages/{native id}/edit`, `/admin/pages/{native id}`.
- 발행/발행 중지: 행의 `abilities.can_update` 가 참일 때만 native `PATCH .../admin/pages/{id}/publish {published}`. native 403/422/오류 메시지는 토스트로 그대로 보입니다. 새 쓰기 API·Page 모델 직접 쓰기는 없습니다.
- 비회원 401, 페이지 권한 없음 403(여행 catalog 권한은 대체 불가), self 스코프 관리자는 남의 Page 를 목록에서 보지 못하고 상세·발행 403.

## 명시 프로비저닝

```bash
TRAVEL_LAB_ISOLATED=1 TRAVEL_LAB_CAMPAIGN_PROVISIONING=1 \
  php artisan raonslab-travel_lab:campaigns-provision --lab-confirm --actor=<관리자 사용자 ID>
```

모든 검사는 쓰기 전에 끝납니다: 설정 플래그(기본 false) · `--lab-confirm` · `mail.default=array` · `queue.default=sync` · `scout.driver=mysql-fulltext` · 기본 디스크 local · `--actor` 가 native `UserRepositoryInterface::findById` 로 찾은 사용자이며 `sirsoft-page.pages.read`·`create` 관리자 권한 보유. actor 는 실행 범위에서만 기본 guard 에 설정되고 이전 상태로 복원되며 토큰은 만들지 않습니다. 기존 Page 는 초안·편집본·버전·발행 상태를 포함해 건드리지 않고, 확인과 생성 사이에 같은 slug 가 생기면 덮어쓰지 않고 오류로 멈춥니다. 생성분은 ko/en 합성 문구(가격·링크·이미지 없음), `content_mode=text`, `published=true` 입니다. native 버전 스냅샷·활동 로그·SEO/사이트맵 리스너(사이트맵 잡 포함)는 그대로 실행됩니다 — "잡 없음"이 아닙니다. 기본 설치·업데이트·`--sample` 시드는 Page 를 만들지 않습니다.

## 고객 화면 (템플릿 소유)

`/travel/campaigns` · `/travel/campaigns/:slug` · `/page/:slug`(native 사이트맵 별칭, 같은 404 규칙). 상세는 본문(`PageBody`)·버전과, 상세 응답의 `catalog_query` 로 기존 `GET /catalog` 를 불러 실제 상품 카드를 보여 주고 상품 상세·장바구니로 이어집니다. 금액은 카탈로그 서버 응답뿐이며 기획전 본문에서 값을 읽지 않습니다. 템플릿 측 계약은 템플릿 `tests/scenarios/campaigns.yaml` 을 보세요.
