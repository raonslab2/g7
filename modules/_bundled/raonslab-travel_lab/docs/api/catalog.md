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
| published | boolean | `true` | 여행 공개 여부 |
| summary_translations | object | `{"ko":"바다 산책","en":"Sea walk"}` | 원본 다국어 요약 |
| title_translations | object | `{"ko":"제주 바다 여행","en":"Jeju sea journey"}` | 이커머스 원본 다국어 상품명 |

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
                "published": true,
                "summary_translations": {
                    "ko": "바다 산책",
                    "en": "Sea walk"
                },
                "title_translations": {
                    "ko": "제주 바다 여행",
                    "en": "Jeju sea journey"
                }
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
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.catalog.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`error.errors` 에 필드별 메시지) |

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.


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
| published | boolean | `true` | 여행 공개 여부 |
| summary_translations | object | `{"ko":"바다 산책","en":"Sea walk"}` | 원본 다국어 요약 |
| title_translations | object | `{"ko":"제주 바다 여행","en":"Jeju sea journey"}` | 이커머스 원본 다국어 상품명 |

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
        "published": true,
        "summary_translations": {
            "ko": "바다 산책",
            "en": "Sea walk"
        },
        "title_translations": {
            "ko": "제주 바다 여행",
            "en": "Jeju sea journey"
        }
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
    "product_option_id": 2,
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

_대표 에러 없음 (공개 조회). <!-- TODO: 도메인 특이 에러가 있으면 보강 -->_

<!-- @generated:end -->

**설명** [도메인·권한·입력·오류 계약](../domain-api.md)을 적용합니다. product는 이커머스 상품 ID입니다.
