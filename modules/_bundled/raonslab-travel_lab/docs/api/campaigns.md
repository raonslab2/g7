# 여행 기획전 API

격리 SQLite DB 와 실제 HTTP 커널·native PageService 로 수집한 응답입니다(발행 1건 + 초안 1건). 운영 서버 실측이 아닙니다.

고정 두 슬롯(`config/campaigns.php`)만 열거합니다. 미발행·부재·레지스트리 밖 slug 는 비회원·회원·페이지 관리자 모두 404 이며 미리보기는 없습니다. 목록 조회에 `slug`·`slugs`·`search`·`q`·`prefix`·`ids`·`filters`·`published`·`preview` 를 보내면 422 입니다. 선택 인증 뒤 캠페인 전용 600/분 제한(`travel-lab-campaign-public:`)을 씁니다.

[계약과 사용 예시](../campaigns.md) · [격리 생성기](../../tests/generate-campaign-docs.php)


### GET /api/modules/raonslab-travel_lab/campaigns
<!-- @generated:start:api.modules.raonslab-travel_lab.campaigns.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.campaigns.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\CampaignController@index`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

_요청 파라미터 없음._

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/campaigns HTTP/1.1
Host: api.example.com
Accept: application/json
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| items | array | `[{"slug":"travel-lab-campaign-autumn-escape","kind":"camp…` | 발행된 슬롯 목록 (레지스트리 순서, 최대 2건, 페이지네이션 없음) |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "Campaigns loaded.",
    "data": {
        "items": [
            {
                "slug": "travel-lab-campaign-autumn-escape",
                "kind": "campaign",
                "theme": "nature",
                "title": "Synthetic autumn campaign",
                "excerpt": "Synthetic documentation body. No prices, links or images.",
                "current_version": 1,
                "published_at": "2026-10-09 21:00:00",
                "path": "/travel/campaigns/travel-lab-campaign-autumn-escape",
                "catalog_query": {
                    "theme": "nature",
                    "sort": "recommended"
                },
                "art_variant": "forest"
            }
        ]
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 422 | Unprocessable Entity | `slug`·`slugs`·`search`·`q`·`prefix`·`ids`·`filters`·`published`·`preview` 중 하나라도 보낸 경우 (대상 선택 불가) |

<!-- @generated:end -->

**설명** 홈 기획전 카드와 `/travel/campaigns` 목록이 씁니다. 발행된 슬롯만 담으며 초안·부재 슬롯은 빠집니다. 빈 배열은 정상 응답(진행 중 기획전 없음)이고 대체 문구를 만들지 않습니다.


### GET /api/modules/raonslab-travel_lab/campaigns/{slug}
<!-- @generated:start:api.modules.raonslab-travel_lab.campaigns.show -->
- **라우트명**: `api.modules.raonslab-travel_lab.campaigns.show`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\CampaignController@show`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| slug | path | string | 예 | — | URL 친화 식별자 (slug) |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/campaigns/{slug} HTTP/1.1
Host: api.example.com
Accept: application/json
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| slug | string | `travel-lab-campaign-autumn-escape` | URL 친화 식별자 (slug) |
| kind | string | `campaign` | 항상 campaign |
| theme | string | `nature` | 슬롯의 여행 테마 enum (nature/wellness) |
| title | string | `Synthetic autumn campaign` | 제목 |
| excerpt | string | `Synthetic documentation body. No pric…` | 본문에서 만든 평문 발췌 (태그·엔티티 제거, 최대 140자) |
| current_version | integer | `1` | native Page 현재 버전 번호 |
| published_at | string | `2026-10-09 21:00:00` | published 일시 |
| path | string | `/travel/campaigns/travel-lab-campaign…` | 고객 상세 경로 |
| catalog_query | object | `{"theme":"nature","sort":"recommended"}` | 기존 카탈로그 API 로 그대로 넘기는 서버 고정 필터 |
| art_variant | string | `forest` | `art` 값의 표시 변형 키 (UI 배지 색상/스타일) |
| content | string | `Synthetic documentation body. No pric…` | 본문 내용 |
| content_mode | string | `text` | text 또는 html |
| updated_at | string | `2026-10-09 21:00:00` | 최종 수정 일시 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "Campaign loaded.",
    "data": {
        "slug": "travel-lab-campaign-autumn-escape",
        "kind": "campaign",
        "theme": "nature",
        "title": "Synthetic autumn campaign",
        "excerpt": "Synthetic documentation body. No prices, links or images.",
        "current_version": 1,
        "published_at": "2026-10-09 21:00:00",
        "path": "/travel/campaigns/travel-lab-campaign-autumn-escape",
        "catalog_query": {
            "theme": "nature",
            "sort": "recommended"
        },
        "art_variant": "forest",
        "content": "Synthetic documentation body.\nNo prices, links or images.",
        "content_mode": "text",
        "updated_at": "2026-10-09 21:00:00"
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** `/travel/campaigns/{slug}` 와 `/page/{slug}` 별칭이 씁니다. 레지스트리 slug 이고 발행 상태일 때만 200 이며, 그 밖은 열람자와 무관하게 404 입니다. `catalog_query` 로 기존 `GET /catalog` 를 불러 실제 상품·금액을 보여 줍니다.
