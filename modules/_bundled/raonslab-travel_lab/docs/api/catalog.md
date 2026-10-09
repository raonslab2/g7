# 여행 카탈로그 API

격리 SQLite DB와 실제 HTTP 커널에서 수집한 응답입니다. 운영 서버 실측이 아닙니다.

[계약과 사용 예시](../domain-api.md) · [격리 생성기](../../tests/generate-domain-docs.php)


### GET /api/modules/raonslab-travel_lab/admin/catalog
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.catalog.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.catalog.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\AdminCatalogController@index`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.catalog.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| q | query | string | 아니오 | max 200 | 검색어 (부분 일치) |
| region | query | string | 아니오 | max 50 | 지역/권역 |
| theme | query | string | 아니오 | max 50 | 여행 테마 코드 |
| date_from | query | date | 아니오 | — | 조회 기간 시작일 |
| date_to | query | date | 아니오 | — | 조회 기간 종료일 |
| min_price | query | number | 아니오 | min 0, max 9999999999999999.99 | 출발 단가 하한 (0 이상) |
| max_price | query | number | 아니오 | min 0, max 9999999999999999.99 | 출발 단가 상한 (하한 이상) |
| sort | query | string | 아니오 | recommended, price_asc, price_desc, departure_asc | 정렬 모드 (기본 recommended) |
| per_page | query | integer | 아니오 | min 1, max 48 | 페이지당 항목 수 |
| page | query | integer | 아니오 | min 1, max 1000 | 조회할 페이지 번호 (1부터 시작) |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/admin/catalog HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드 + `data.pagination`._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| title | string | `제주 바다 여행` | 제목 |
| region | string | `jeju` | 지역 코드 |
| theme | string | `nature` | 여행 테마 코드 |
| duration_days | integer | `3` | 여행 일수 |
| summary | string | `바다 산책` | 현재 로케일 요약 (입력은 ko/en 객체) |
| itinerary | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day/title 및 선택 description의 다국어 일정 배열 |
| from_price | integer | `100000` | 응답에 포함된 적격 출발 중 최저 이커머스 단가 |
| currency_code | string | `KRW` | 이커머스 상품 통화 코드 |
| image_url | null | `null` | image URL |
| departures | array | `[{"id":1,"product_id":1,"product_option_id":1,"departure_…` | 동일 상품의 출발 일정 배열 |
| product_code | string | `TRAVEL-TEST-d0efd1e114` | 네이티브 커머스 상품 코드. API 경로와 편집 링크에는 숫자 product ID를 사용합니다. |
| published | boolean | `true` | 여행 공개 여부 |
| summary_translations | object | `{"ko":"바다 산책","en":"Sea walk"}` | 원본 다국어 요약 |
| itinerary_translations | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day 및 ko/en title, 선택 description 번역을 보존한 여행 일정표. |
| title_translations | object | `{"ko":"제주 바다 여행","en":"Jeju sea journey"}` | 이커머스 원본 다국어 상품명 |
| options | array | `[{"id":1,"option_name":"출발","stock_quantity":20,"is_activ…` | 네이티브 옵션 id·로케일 이름·stock_quantity·is_active. 출발 연결은 이 id를 선택하며 가격은 커머스가 관리합니다. |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 조회했습니다.",
    "data": {
        "data": [
            {
                "id": 1,
                "title": "제주 바다 여행",
                "region": "jeju",
                "theme": "nature",
                "duration_days": 3,
                "summary": "바다 산책",
                "itinerary": [
                    {
                        "day": 1,
                        "title": {
                            "ko": "바다",
                            "en": "Sea"
                        }
                    }
                ],
                "from_price": 100000,
                "currency_code": "KRW",
                "image_url": null,
                "departures": [
                    {
                        "id": 1,
                        "product_id": 1,
                        "product_option_id": 1,
                        "departure_date": "2026-11-01",
                        "return_date": "2026-11-03",
                        "available": 20,
                        "unit_price": 100000,
                        "currency_code": "KRW",
                        "capacity": 20,
                        "reserved": 0,
                        "is_active": true
                    }
                ],
                "product_code": "TRAVEL-TEST-f4ad0419fd",
                "published": true,
                "summary_translations": {
                    "ko": "바다 산책",
                    "en": "Sea walk"
                },
                "itinerary_translations": [
                    {
                        "day": 1,
                        "title": {
                            "ko": "바다",
                            "en": "Sea"
                        }
                    }
                ],
                "title_translations": {
                    "ko": "제주 바다 여행",
                    "en": "Jeju sea journey"
                },
                "options": [
                    {
                        "id": 1,
                        "option_name": "출발",
                        "stock_quantity": 20,
                        "is_active": true
                    },
                    {
                        "id": 3,
                        "option_name": "문서 출발",
                        "stock_quantity": 20,
                        "is_active": true
                    }
                ]
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 12,
            "from": 1,
            "to": 1,
            "has_more_pages": false,
            "last_page": 1,
            "total": 1,
            "total_relation": "exact",
            "total_is_exact": true,
            "result_cap": 10000
        },
        "abilities": {
            "can_update": true
        }
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.catalog.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.


### POST /api/modules/raonslab-travel_lab/admin/catalog
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.catalog.store -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.catalog.store`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\AdminCatalogController@store`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.catalog.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| region | body | string | 예 | — | 지역/권역 |
| theme | body | string | 예 | — | 모듈 themes 설정에 포함된 테마 코드. |
| duration_days | body | integer | 예 | min 1, max 365 | 여행 일수(1..365). 실제 항공·숙박 일정 연동을 의미하지 않습니다. |
| summary | body | array | 예 | — | 입력은 ko/en 번역 객체, 공개 응답은 현재 로케일의 요약 문자열. |
| summary.ko | body | string | 아니오 | max 2000 | 한국어 요약. summary를 보낼 때 필수입니다. |
| summary.en | body | string | 아니오 | max 2000 | 영문 요약. summary를 보낼 때 필수입니다. |
| itinerary | body | array | 아니오 | max 365 | day와 번역 title, 선택 description의 배열. 미입력 등록은 빈 배열; 관리 UI의 유효 JSON 문자열도 허용합니다. |
| published | body | boolean | 아니오 | — | 발행 여부 (발행된 항목만 필터) |
| product_id | body | integer | 예 | — | product 식별자 |

**요청 예시**

```http
POST /api/modules/raonslab-travel_lab/admin/catalog HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
    "product_id": 2,
    "region": "jeju",
    "theme": "nature",
    "duration_days": 3,
    "summary": {
        "ko": "합성 문서 상품",
        "en": "Synthetic documentation trip"
    },
    "itinerary": [
        {
            "day": 1,
            "title": {
                "ko": "바다",
                "en": "Sea"
            }
        }
    ]
}
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `2` | 기본 키 (내부 식별자) |
| title | string | `제주 바다 여행` | 제목 |
| region | string | `jeju` | 모듈 regions 설정에 포함된 여행 지역 코드. |
| theme | string | `nature` | 모듈 themes 설정에 포함된 테마 코드. |
| duration_days | integer | `3` | 여행 일수(1..365). 실제 항공·숙박 일정 연동을 의미하지 않습니다. |
| summary | string | `합성 문서 상품` | 입력은 ko/en 번역 객체, 공개 응답은 현재 로케일의 요약 문자열. |
| itinerary | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day와 번역 title, 선택 description의 배열. 미입력 등록은 빈 배열; 관리 UI의 유효 JSON 문자열도 허용합니다. |
| from_price | null | `null` | 가용 인원이 있는 출발 옵션의 최소 네이티브 단가. 관리 대상에 가용 일정이 없으면 null입니다. |
| currency_code | string | `KRW` | 네이티브 상품 통화 코드; 응답 예시 KRW는 합성 상품 설정입니다. |
| image_url | null | `null` | image URL |
| departures | array | `[]` | 출발 일정 및 원본 옵션 가격. 공개 상세는 미래 활성 매진 일정도 available=0으로 표시합니다. |
| product_code | string | `TRAVEL-TEST-0a3cfcf977` | 네이티브 커머스 상품 코드. API 경로와 편집 링크에는 숫자 product ID를 사용합니다. |
| published | boolean | `false` | 여행 게시 여부. 새 등록은 생략 시 false이며 커머스 판매/표시와 가용 출발도 공개 조건입니다. |
| summary_translations | object | `{"ko":"합성 문서 상품","en":"Synthetic documentation trip"}` | 편집용 ko/en 요약 번역 원본. |
| itinerary_translations | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day 및 ko/en title, 선택 description 번역을 보존한 여행 일정표. |
| title_translations | object | `{"ko":"제주 바다 여행","en":"Jeju sea journey"}` | 네이티브 상품명의 번역 원본; 이 여행 API에서 상품명/가격을 수정하지 않습니다. |
| options | array | `[{"id":2,"option_name":"출발","stock_quantity":20,"is_activ…` | 네이티브 옵션 id·로케일 이름·stock_quantity·is_active. 출발 연결은 이 id를 선택하며 가격은 커머스가 관리합니다. |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 201
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 저장했습니다.",
    "data": {
        "id": 2,
        "title": "제주 바다 여행",
        "region": "jeju",
        "theme": "nature",
        "duration_days": 3,
        "summary": "합성 문서 상품",
        "itinerary": [
            {
                "day": 1,
                "title": {
                    "ko": "바다",
                    "en": "Sea"
                }
            }
        ],
        "from_price": null,
        "currency_code": "KRW",
        "image_url": null,
        "departures": [],
        "product_code": "TRAVEL-TEST-0a3cfcf977",
        "published": false,
        "summary_translations": {
            "ko": "합성 문서 상품",
            "en": "Synthetic documentation trip"
        },
        "itinerary_translations": [
            {
                "day": 1,
                "title": {
                    "ko": "바다",
                    "en": "Sea"
                }
            }
        ],
        "title_translations": {
            "ko": "제주 바다 여행",
            "en": "Jeju sea journey"
        },
        "options": [
            {
                "id": 2,
                "option_name": "출발",
                "stock_quantity": 20,
                "is_active": true
            }
        ]
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.catalog.update`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 기존 커머스 상품에 여행 메타데이터만 등록합니다. 성공201, 중복409; 상품·옵션·금액·주문은 변경하지 않습니다. 자세한 계약은 [domain-api.md](../domain-api.md)를 참조하십시오.


### GET /api/modules/raonslab-travel_lab/admin/catalog/candidates
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.catalog.candidates -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.catalog.candidates`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\AdminCatalogController@candidates`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.catalog.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| q | query | string | 아니오 | max 200 | 검색어 (부분 일치) |
| per_page | query | integer | 아니오 | min 1, max 48 | 페이지당 항목 수 |
| page | query | integer | 아니오 | min 1, max 10000 | 조회할 페이지 번호 (1부터 시작) |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/admin/catalog/candidates HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드 + `data.pagination`._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `17` | 기본 키 (내부 식별자) |
| product_code | string | `W03-P-1791542298261` | 네이티브 커머스 상품 코드. API 경로와 편집 링크에는 숫자 product ID를 사용합니다. |
| title | string | `W03-P-1791542298261 합성 상품` | 제목 |
| shipping_policy_ready | boolean | `true` | 비기본 활성 정책, 활성 KR 설정, 모든 국가 설정의 무료/추가료없음/기본료0/외부 API없음 여부. 거래 시 서버가 다시 검증합니다. |
| options | array | `[{"id":33,"option_name":"W03 합성 출발 옵션","stock_quantity":5…` | 네이티브 옵션 id·로케일 이름·stock_quantity·is_active. 출발 연결은 이 id를 선택하며 가격은 커머스가 관리합니다. |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 조회했습니다.",
    "data": {
        "data": [],
        "pagination": {
            "current_page": 1,
            "per_page": 12,
            "from": null,
            "to": null,
            "has_more_pages": false,
            "last_page": 1,
            "total": 0,
            "total_relation": "exact",
            "total_is_exact": true,
            "result_cap": 10000
        }
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.catalog.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** 미등록 네이티브 상품을 검색해 여행 등록 후보와 원본 옵션을 선택합니다. per_page는 최대48이며 등록 성공 후 해당 후보는 목록에서 빠집니다. 자세한 계약은 [domain-api.md](../domain-api.md)를 참조하십시오.


### GET /api/modules/raonslab-travel_lab/admin/catalog/{product}
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.catalog.show -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.catalog.show`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\AdminCatalogController@show`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.catalog.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| product | path | string | 예 | — | 대상 product의 식별자 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/admin/catalog/1 HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| title | string | `제주 바다 여행` | 제목 |
| region | string | `jeju` | 모듈 regions 설정에 포함된 여행 지역 코드. |
| theme | string | `nature` | 모듈 themes 설정에 포함된 테마 코드. |
| duration_days | integer | `3` | 여행 일수(1..365). 실제 항공·숙박 일정 연동을 의미하지 않습니다. |
| summary | string | `바다 산책` | 입력은 ko/en 번역 객체, 공개 응답은 현재 로케일의 요약 문자열. |
| itinerary | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day와 번역 title, 선택 description의 배열. 미입력 등록은 빈 배열; 관리 UI의 유효 JSON 문자열도 허용합니다. |
| from_price | integer | `100000` | 가용 인원이 있는 출발 옵션의 최소 네이티브 단가. 관리 대상에 가용 일정이 없으면 null입니다. |
| currency_code | string | `KRW` | 네이티브 상품 통화 코드; 응답 예시 KRW는 합성 상품 설정입니다. |
| image_url | null | `null` | image URL |
| departures | array | `[{"id":1,"product_id":1,"product_option_id":1,"departure_…` | 출발 일정 및 원본 옵션 가격. 공개 상세는 미래 활성 매진 일정도 available=0으로 표시합니다. |
| product_code | string | `TRAVEL-TEST-d0efd1e114` | 네이티브 커머스 상품 코드. API 경로와 편집 링크에는 숫자 product ID를 사용합니다. |
| published | boolean | `true` | 여행 게시 여부. 새 등록은 생략 시 false이며 커머스 판매/표시와 가용 출발도 공개 조건입니다. |
| summary_translations | object | `{"ko":"바다 산책","en":"Sea walk"}` | 편집용 ko/en 요약 번역 원본. |
| itinerary_translations | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day 및 ko/en title, 선택 description 번역을 보존한 여행 일정표. |
| title_translations | object | `{"ko":"제주 바다 여행","en":"Jeju sea journey"}` | 네이티브 상품명의 번역 원본; 이 여행 API에서 상품명/가격을 수정하지 않습니다. |
| options | array | `[{"id":1,"option_name":"출발","stock_quantity":20,"is_activ…` | 네이티브 옵션 id·로케일 이름·stock_quantity·is_active. 출발 연결은 이 id를 선택하며 가격은 커머스가 관리합니다. |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 조회했습니다.",
    "data": {
        "id": 1,
        "title": "제주 바다 여행",
        "region": "jeju",
        "theme": "nature",
        "duration_days": 3,
        "summary": "바다 산책",
        "itinerary": [
            {
                "day": 1,
                "title": {
                    "ko": "바다",
                    "en": "Sea"
                }
            }
        ],
        "from_price": 100000,
        "currency_code": "KRW",
        "image_url": null,
        "departures": [
            {
                "id": 1,
                "product_id": 1,
                "product_option_id": 1,
                "departure_date": "2026-11-01",
                "return_date": "2026-11-03",
                "available": 20,
                "unit_price": 100000,
                "currency_code": "KRW",
                "capacity": 20,
                "reserved": 0,
                "is_active": true
            }
        ],
        "product_code": "TRAVEL-TEST-d0efd1e114",
        "published": true,
        "summary_translations": {
            "ko": "바다 산책",
            "en": "Sea walk"
        },
        "itinerary_translations": [
            {
                "day": 1,
                "title": {
                    "ko": "바다",
                    "en": "Sea"
                }
            }
        ],
        "title_translations": {
            "ko": "제주 바다 여행",
            "en": "Jeju sea journey"
        },
        "options": [
            {
                "id": 1,
                "option_name": "출발",
                "stock_quantity": 20,
                "is_active": true
            },
            {
                "id": 3,
                "option_name": "문서 출발",
                "stock_quantity": 20,
                "is_active": true
            }
        ]
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.catalog.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** 관리자가 비공개 여부와 관계없이 숫자 상품 ID로 여행 번역 메타데이터와 네이티브 옵션을 조회합니다. 없는 매핑은404입니다. 자세한 계약은 [domain-api.md](../domain-api.md)를 참조하십시오.


### PATCH /api/modules/raonslab-travel_lab/admin/catalog/{product}
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.catalog.update -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.catalog.update`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\AdminCatalogController@update`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.catalog.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| product | path | string | 예 | — | 대상 product의 식별자 |
| region | body | string | 아니오 | — | 지역/권역 |
| theme | body | string | 아니오 | — | 여행 테마 코드 |
| duration_days | body | integer | 아니오 | min 1, max 365 | 여행 일수 |
| summary | body | array | 아니오 | — | 현재 로케일 요약 (입력은 ko/en 객체) |
| summary.ko | body | string | 아니오 | max 2000 | 한국어 요약 |
| summary.en | body | string | 아니오 | max 2000 | 영문 요약 |
| itinerary | body | array | 아니오 | max 365 | day/title 및 선택 description의 다국어 일정 배열 |
| published | body | boolean | 아니오 | — | 발행 여부 (발행된 항목만 필터) |

**요청 예시**

```http
PATCH /api/modules/raonslab-travel_lab/admin/catalog/1 HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
    "published": true
}
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| title | string | `제주 바다 여행` | 제목 |
| region | string | `jeju` | 지역 코드 |
| theme | string | `nature` | 여행 테마 코드 |
| duration_days | integer | `3` | 여행 일수 |
| summary | string | `바다 산책` | 현재 로케일 요약 (입력은 ko/en 객체) |
| itinerary | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day/title 및 선택 description의 다국어 일정 배열 |
| from_price | integer | `100000` | 응답에 포함된 적격 출발 중 최저 이커머스 단가 |
| currency_code | string | `KRW` | 이커머스 상품 통화 코드 |
| image_url | null | `null` | image URL |
| departures | array | `[{"id":1,"product_id":1,"product_option_id":1,"departure_…` | 동일 상품의 출발 일정 배열 |
| product_code | string | `TRAVEL-TEST-d0efd1e114` | 네이티브 커머스 상품 코드. API 경로와 편집 링크에는 숫자 product ID를 사용합니다. |
| published | boolean | `true` | 여행 공개 여부 |
| summary_translations | object | `{"ko":"바다 산책","en":"Sea walk"}` | 원본 다국어 요약 |
| itinerary_translations | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day 및 ko/en title, 선택 description 번역을 보존한 여행 일정표. |
| title_translations | object | `{"ko":"제주 바다 여행","en":"Jeju sea journey"}` | 이커머스 원본 다국어 상품명 |
| options | array | `[{"id":1,"option_name":"출발","stock_quantity":20,"is_activ…` | 네이티브 옵션 id·로케일 이름·stock_quantity·is_active. 출발 연결은 이 id를 선택하며 가격은 커머스가 관리합니다. |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 저장했습니다.",
    "data": {
        "id": 1,
        "title": "제주 바다 여행",
        "region": "jeju",
        "theme": "nature",
        "duration_days": 3,
        "summary": "바다 산책",
        "itinerary": [
            {
                "day": 1,
                "title": {
                    "ko": "바다",
                    "en": "Sea"
                }
            }
        ],
        "from_price": 100000,
        "currency_code": "KRW",
        "image_url": null,
        "departures": [
            {
                "id": 1,
                "product_id": 1,
                "product_option_id": 1,
                "departure_date": "2026-11-01",
                "return_date": "2026-11-03",
                "available": 20,
                "unit_price": 100000,
                "currency_code": "KRW",
                "capacity": 20,
                "reserved": 0,
                "is_active": true
            }
        ],
        "product_code": "TRAVEL-TEST-d0efd1e114",
        "published": true,
        "summary_translations": {
            "ko": "바다 산책",
            "en": "Sea walk"
        },
        "itinerary_translations": [
            {
                "day": 1,
                "title": {
                    "ko": "바다",
                    "en": "Sea"
                }
            }
        ],
        "title_translations": {
            "ko": "제주 바다 여행",
            "en": "Jeju sea journey"
        },
        "options": [
            {
                "id": 1,
                "option_name": "출발",
                "stock_quantity": 20,
                "is_active": true
            },
            {
                "id": 3,
                "option_name": "문서 출발",
                "stock_quantity": 20,
                "is_active": true
            }
        ]
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.catalog.update`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.


### GET /api/modules/raonslab-travel_lab/admin/catalog/{product}/departures
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.catalog.departures.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.catalog.departures.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\AdminCatalogController@departures`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.catalog.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| product | path | string | 예 | — | 대상 product의 식별자 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/admin/catalog/1/departures HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| product_id | integer | `1` | product 식별자 (연관 리소스 참조) |
| product_option_id | integer | `1` | product option 식별자 (연관 리소스 참조) |
| departure_date | string | `2026-11-01` | 출발 날짜 YYYY-MM-DD |
| return_date | string | `2026-11-03` | 귀환 날짜 YYYY-MM-DD |
| available | integer | `20` | 테스트 잔여 인원 capacity-reserved |
| unit_price | integer | `100000` | 이커머스 옵션 현재 단가 (입력 금지) |
| currency_code | string | `KRW` | 이커머스 상품 통화 코드 |
| capacity | integer | `20` | 테스트 정원 (확보 인원 이상, 옵션 재고 이하) |
| reserved | integer | `0` | 테스트 확보 인원 (입력 금지) |
| is_active | boolean | `true` | active 여부 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 조회했습니다.",
    "data": [
        {
            "id": 1,
            "product_id": 1,
            "product_option_id": 1,
            "departure_date": "2026-11-01",
            "return_date": "2026-11-03",
            "available": 20,
            "unit_price": 100000,
            "currency_code": "KRW",
            "capacity": 20,
            "reserved": 0,
            "is_active": true
        }
    ]
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.catalog.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.


### POST /api/modules/raonslab-travel_lab/admin/catalog/{product}/departures
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.catalog.departures.store -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.catalog.departures.store`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\AdminCatalogController@storeDeparture`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.catalog.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| product | path | string | 예 | — | 대상 product의 식별자 |
| product_option_id | body | integer | 예 | — | product option 식별자 |
| departure_date | body | date | 예 | — | departure 날짜 |
| return_date | body | date | 예 | — | return 날짜 |
| capacity | body | integer | 예 | min 1, max 100000 | 테스트 정원 (확보 인원 이상, 옵션 재고 이하) |
| is_active | body | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |

**요청 예시**

```http
POST /api/modules/raonslab-travel_lab/admin/catalog/1/departures HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
    "product_option_id": 3,
    "departure_date": "2026-11-01",
    "return_date": "2026-11-03",
    "capacity": 20,
    "is_active": true
}
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `2` | 기본 키 (내부 식별자) |
| product_id | integer | `1` | product 식별자 (연관 리소스 참조) |
| product_option_id | integer | `2` | product option 식별자 (연관 리소스 참조) |
| departure_date | string | `2026-11-01` | 출발 날짜 YYYY-MM-DD |
| return_date | string | `2026-11-03` | 귀환 날짜 YYYY-MM-DD |
| available | integer | `20` | 테스트 잔여 인원 capacity-reserved |
| unit_price | integer | `120000` | 이커머스 옵션 현재 단가 (입력 금지) |
| currency_code | string | `KRW` | 이커머스 상품 통화 코드 |
| capacity | integer | `20` | 테스트 정원 (확보 인원 이상, 옵션 재고 이하) |
| reserved | integer | `0` | 테스트 확보 인원 (입력 금지) |
| is_active | boolean | `true` | active 여부 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 저장했습니다.",
    "data": {
        "id": 2,
        "product_id": 1,
        "product_option_id": 2,
        "departure_date": "2026-11-01",
        "return_date": "2026-11-03",
        "available": 20,
        "unit_price": 120000,
        "currency_code": "KRW",
        "capacity": 20,
        "reserved": null,
        "is_active": true
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.catalog.update`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 409 | Conflict | 재고·정원·옵션·사용 중 날짜 불변조건 충돌 (POST/PUT) |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.


### PUT /api/modules/raonslab-travel_lab/admin/catalog/{product}/departures/{departure}
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.catalog.departures.update -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.catalog.departures.update`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\AdminCatalogController@updateDeparture`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.catalog.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| product | path | string | 예 | — | 대상 product의 식별자 |
| departure | path | string | 예 | — | 대상 departure의 식별자 |
| product_option_id | body | integer | 예 | — | product option 식별자 |
| departure_date | body | date | 예 | — | departure 날짜 |
| return_date | body | date | 예 | — | return 날짜 |
| capacity | body | integer | 예 | min 1, max 100000 | 테스트 정원 (확보 인원 이상, 옵션 재고 이하) |
| is_active | body | boolean | 아니오 | — | 활성 여부 (true 활성 / false 비활성) |

**요청 예시**

```http
PUT /api/modules/raonslab-travel_lab/admin/catalog/1/departures/1 HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
    "product_option_id": 1,
    "departure_date": "2026-11-01",
    "return_date": "2026-11-03",
    "capacity": 20,
    "is_active": true
}
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| product_id | integer | `1` | product 식별자 (연관 리소스 참조) |
| product_option_id | integer | `1` | product option 식별자 (연관 리소스 참조) |
| departure_date | string | `2026-11-01` | 출발 날짜 YYYY-MM-DD |
| return_date | string | `2026-11-03` | 귀환 날짜 YYYY-MM-DD |
| available | integer | `20` | 테스트 잔여 인원 capacity-reserved |
| unit_price | integer | `100000` | 이커머스 옵션 현재 단가 (입력 금지) |
| currency_code | string | `KRW` | 이커머스 상품 통화 코드 |
| capacity | integer | `20` | 테스트 정원 (확보 인원 이상, 옵션 재고 이하) |
| reserved | integer | `0` | 테스트 확보 인원 (입력 금지) |
| is_active | boolean | `true` | active 여부 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 저장했습니다.",
    "data": {
        "id": 1,
        "product_id": 1,
        "product_option_id": 1,
        "departure_date": "2026-11-01",
        "return_date": "2026-11-03",
        "available": 20,
        "unit_price": 100000,
        "currency_code": "KRW",
        "capacity": 20,
        "reserved": 0,
        "is_active": true
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.catalog.update`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 409 | Conflict | 재고·정원·옵션·사용 중 날짜 불변조건 충돌 (POST/PUT) |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.


### GET /api/modules/raonslab-travel_lab/catalog
<!-- @generated:start:api.modules.raonslab-travel_lab.catalog.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.catalog.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\CatalogController@index`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| q | query | string | 아니오 | max 200 | 검색어 (부분 일치) |
| region | query | string | 아니오 | max 50 | 지역/권역 |
| theme | query | string | 아니오 | max 50 | 여행 테마 코드 |
| date_from | query | date | 아니오 | — | 조회 기간 시작일 |
| date_to | query | date | 아니오 | — | 조회 기간 종료일 |
| min_price | query | number | 아니오 | min 0, max 9999999999999999.99 | 출발 단가 하한 (0 이상) |
| max_price | query | number | 아니오 | min 0, max 9999999999999999.99 | 출발 단가 상한 (하한 이상) |
| sort | query | string | 아니오 | recommended, price_asc, price_desc, departure_asc | 정렬 모드 (기본 recommended) |
| per_page | query | integer | 아니오 | min 1, max 48 | 페이지당 항목 수 |
| page | query | integer | 아니오 | min 1, max 1000 | 조회할 페이지 번호 (1부터 시작) |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/catalog HTTP/1.1
Host: api.example.com
Accept: application/json
```

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드 + `data.pagination`._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| title | string | `제주 바다 여행` | 제목 |
| region | string | `jeju` | 지역 코드 |
| theme | string | `nature` | 여행 테마 코드 |
| duration_days | integer | `3` | 여행 일수 |
| summary | string | `바다 산책` | 현재 로케일 요약 (입력은 ko/en 객체) |
| itinerary | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day/title 및 선택 description의 다국어 일정 배열 |
| from_price | integer | `100000` | 응답에 포함된 적격 출발 중 최저 이커머스 단가 |
| currency_code | string | `KRW` | 이커머스 상품 통화 코드 |
| image_url | null | `null` | image URL |
| departures | array | `[{"id":1,"product_id":1,"product_option_id":1,"departure_…` | 동일 상품의 출발 일정 배열 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 조회했습니다.",
    "data": {
        "data": [
            {
                "id": 1,
                "title": "제주 바다 여행",
                "region": "jeju",
                "theme": "nature",
                "duration_days": 3,
                "summary": "바다 산책",
                "itinerary": [
                    {
                        "day": 1,
                        "title": {
                            "ko": "바다",
                            "en": "Sea"
                        }
                    }
                ],
                "from_price": 100000,
                "currency_code": "KRW",
                "image_url": null,
                "departures": [
                    {
                        "id": 1,
                        "product_id": 1,
                        "product_option_id": 1,
                        "departure_date": "2026-11-01",
                        "return_date": "2026-11-03",
                        "available": 20,
                        "unit_price": 100000,
                        "currency_code": "KRW"
                    },
                    {
                        "id": 2,
                        "product_id": 1,
                        "product_option_id": 2,
                        "departure_date": "2026-11-01",
                        "return_date": "2026-11-03",
                        "available": 20,
                        "unit_price": 120000,
                        "currency_code": "KRW"
                    }
                ]
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 12,
            "from": 1,
            "to": 1,
            "has_more_pages": false,
            "last_page": 1,
            "total": 1,
            "total_relation": "exact",
            "total_is_exact": true,
            "result_cap": 10000
        }
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.


### GET /api/modules/raonslab-travel_lab/catalog/{product}
<!-- @generated:start:api.modules.raonslab-travel_lab.catalog.show -->
- **라우트명**: `api.modules.raonslab-travel_lab.catalog.show`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\CatalogController@show`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| product | path | string | 예 | — | 대상 product의 식별자 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/catalog/1 HTTP/1.1
Host: api.example.com
Accept: application/json
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| title | string | `제주 바다 여행` | 제목 |
| region | string | `jeju` | 지역 코드 |
| theme | string | `nature` | 여행 테마 코드 |
| duration_days | integer | `3` | 여행 일수 |
| summary | string | `바다 산책` | 현재 로케일 요약 (입력은 ko/en 객체) |
| itinerary | array | `[{"day":1,"title":{"ko":"바다","en":"Sea"}}]` | day/title 및 선택 description의 다국어 일정 배열 |
| from_price | integer | `100000` | 응답에 포함된 적격 출발 중 최저 이커머스 단가 |
| currency_code | string | `KRW` | 이커머스 상품 통화 코드 |
| image_url | null | `null` | image URL |
| departures | array | `[{"id":1,"product_id":1,"product_option_id":1,"departure_…` | 동일 상품의 출발 일정 배열 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 조회했습니다.",
    "data": {
        "id": 1,
        "title": "제주 바다 여행",
        "region": "jeju",
        "theme": "nature",
        "duration_days": 3,
        "summary": "바다 산책",
        "itinerary": [
            {
                "day": 1,
                "title": {
                    "ko": "바다",
                    "en": "Sea"
                }
            }
        ],
        "from_price": 100000,
        "currency_code": "KRW",
        "image_url": null,
        "departures": [
            {
                "id": 1,
                "product_id": 1,
                "product_option_id": 1,
                "departure_date": "2026-11-01",
                "return_date": "2026-11-03",
                "available": 20,
                "unit_price": 100000,
                "currency_code": "KRW"
            },
            {
                "id": 2,
                "product_id": 1,
                "product_option_id": 2,
                "departure_date": "2026-11-01",
                "return_date": "2026-11-03",
                "available": 20,
                "unit_price": 120000,
                "currency_code": "KRW"
            }
        ]
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.


### GET /api/modules/raonslab-travel_lab/catalog/{product}/departures
<!-- @generated:start:api.modules.raonslab-travel_lab.catalog.departures -->
- **라우트명**: `api.modules.raonslab-travel_lab.catalog.departures`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\CatalogController@departures`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| product | path | string | 예 | — | 대상 product의 식별자 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/catalog/1/departures HTTP/1.1
Host: api.example.com
Accept: application/json
```

**응답 필드** (`data` 내부)

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| product_id | integer | `1` | product 식별자 (연관 리소스 참조) |
| product_option_id | integer | `1` | product option 식별자 (연관 리소스 참조) |
| departure_date | string | `2026-11-01` | 출발 날짜 YYYY-MM-DD |
| return_date | string | `2026-11-03` | 귀환 날짜 YYYY-MM-DD |
| available | integer | `20` | 테스트 잔여 인원 capacity-reserved |
| unit_price | integer | `100000` | 이커머스 옵션 현재 단가 (입력 금지) |
| currency_code | string | `KRW` | 이커머스 상품 통화 코드 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 조회했습니다.",
    "data": [
        {
            "id": 1,
            "product_id": 1,
            "product_option_id": 1,
            "departure_date": "2026-11-01",
            "return_date": "2026-11-03",
            "available": 20,
            "unit_price": 100000,
            "currency_code": "KRW"
        },
        {
            "id": 2,
            "product_id": 1,
            "product_option_id": 2,
            "departure_date": "2026-11-01",
            "return_date": "2026-11-03",
            "available": 20,
            "unit_price": 120000,
            "currency_code": "KRW"
        }
    ]
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.


### GET /api/modules/raonslab-travel_lab/facets
<!-- @generated:start:api.modules.raonslab-travel_lab.catalog.facets -->
- **라우트명**: `api.modules.raonslab-travel_lab.catalog.facets`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\CatalogController@facets`
- **인증/권한**: 공개 (인증 불필요)

**요청 파라미터**

_요청 파라미터 없음._

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/facets HTTP/1.1
Host: api.example.com
Accept: application/json
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| region | array | `["jeju"]` | 지역 코드 |
| theme | array | `["nature"]` | 여행 테마 코드 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "여행 카탈로그를 조회했습니다.",
    "data": {
        "region": [
            "jeju"
        ],
        "theme": [
            "nature"
        ]
    }
}
```

**에러 응답**

빈 분류 배열은 정상200입니다. 서버 장애는 코어 오류 응답을 따릅니다. 상품 단건404와 필터422는 해당 카탈로그 엔드포인트에 적용됩니다.

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.
