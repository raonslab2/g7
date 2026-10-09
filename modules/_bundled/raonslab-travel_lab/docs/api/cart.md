# Cart API 레퍼런스

> **소유**: module `raonslab-travel_lab` · **생성**: `php artisan api:docgen` (native 골격·부분 실측 + 사람이 작성한 소스 계약). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## 계약과 증거 구분

- 모듈 전체 native `api:docgen` 생성 대상은 31개 라우트이며, 자동 실측은 11건이며 트랜잭션 후 rollback한 POST도 포함한다. 나머지 20건의 기본 프로브 생략은 전체 기능 성공 판정이 아니다.
- `@probed` 표시는 lead가 수집한 실제 응답으로, 기본 자동 프로브와 별도 쓰기 응답 캡처를 포함한다. 출력의 `...`는 생성기의 축약 표시이며 실제 API 필드가 아니다.
- 실측하지 못한 응답의 표·예시는 Controllers/FormRequests/Resources와 계약 테스트에서 작성한 **합성 계약 예시**로 표시했다. 전체 31개 라이브 PASS나 독립 Validation을 주장하지 않는다.
- 재생성은 사람이 채운 설명·예시를 보존하는 native 경로를 사용한다. 설치된 API와 고정 검증 SHA의 일치·재검증은 별도 증거로 판단한다.

---


### GET /api/modules/raonslab-travel_lab/cart
<!-- @generated:start:api.modules.raonslab-travel_lab.cart.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.cart.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\CartController@index`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

_요청 파라미터 없음._

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/cart HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| items | array | 현재 사용자의 실제 ecommerce Cart 중 여행 출발일에 연결된 행; 일반 커머스 행 제외 |
| items[].id | integer | 수정·삭제·접수에 사용할 Cart ID |
| items[].departure_id | integer/null | 출발일 ID; 옵션 ID가 아님. 연결이 사라진 행은 null |
| items[].product_id / items[].product_option_id | integer | 서버가 결정한 실제 커머스 상품·옵션 ID |
| items[].quantity | integer | 현재 저장된 인원 수 |
| items[].remaining_capacity | integer | max(0, min(출발 정원, 옵션 재고) - 모의 배정 인원) |
| items[].product_name | object/null | 커머스 상품명 번역 객체 |
| items[].departure_date / items[].return_date | string/null | 출발일·귀국일 YYYY-MM-DD |
| items[].unit_price / items[].line_total | integer/null | native 계산기의 단가·행 최종 금액; 이용 불가 행은 null |
| items[].available | boolean | 현재 출발일·게시·재고·배송정책 검증 결과 |
| items[].unavailable_reason | string/null | departure_unavailable, capacity_unavailable, shipping_policy_unavailable 또는 unsupported_cart |
| totals | object | sirsoft-ecommerce Summary.toArray 전체 결과; 클라이언트 계산값이 아님 |
| totals.subtotal / totals.final_amount | integer | 이용 가능한 행의 소계·최종 시험 금액 |
| totals.total_shipping / totals.total_discount / totals.points_used | integer | 시험 접수 정책에서 0이어야 하는 배송비·할인·사용 포인트 |
| currency_code | string | 서버 기본 통화 코드, 예: KRW |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다. `totals`/스냅샷의 주요 필드만 발췌했습니다._

```http
HTTP/1.1 200
```

```json
{
  "success": true,
  "message": "성공적으로 처리되었습니다.",
  "data": {
    "items": [
      {
        "id": 8,
        "departure_id": 3,
        "product_id": 2,
        "product_option_id": 6,
        "quantity": 2,
        "remaining_capacity": 5,
        "product_name": {
          "ko": "합성 여행",
          "en": "Synthetic journey"
        },
        "departure_date": "2026-11-01",
        "return_date": "2026-11-03",
        "unit_price": 12000,
        "line_total": 24000,
        "available": true,
        "unavailable_reason": null
      }
    ],
    "totals": {
      "subtotal": 24000,
      "total_shipping": 0,
      "total_discount": 0,
      "points_used": 0,
      "final_amount": 24000
    },
    "currency_code": "KRW"
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 409 | Conflict | 가용 인원·native 계산 정책·선택 Cart·멱등 본문 또는 허용 상태 전이 충돌 |

<!-- @generated:end -->

**설명** 내 여행 장바구니를 native 가격 계산기로 다시 조회한다. 이용 불가 행도 이유와 함께 남겨 삭제를 허용하며 합계에서는 제외한다. 장바구니 담기만으로 정원을 배정하지 않는다.


### POST /api/modules/raonslab-travel_lab/cart
<!-- @generated:start:api.modules.raonslab-travel_lab.cart.store -->
- **라우트명**: `api.modules.raonslab-travel_lab.cart.store`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\CartController@store`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| departure_id | body | integer | 예 | min 1 | departure 식별자 |
| quantity | body | integer | 예 | min 1, max 99 | 여행 인원 수. native Cart 최대 수량(현재 99), 상품 구매 제한 및 잔여 시험 정원을 함께 검사 |

**요청 예시**

```http
POST /api/modules/raonslab-travel_lab/cart HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
    "departure_id": 1,
    "quantity": 1
}
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| items | array | `[{"id":38,"departure_id":1,"product_id":1,"product_option…` | 현재 사용자 여행 Cart 행과 서버 계산 결과 |
| totals | object | `{"subtotal":189000,"subtotal_formatted":"189,000원","produ…` | native ecommerce Summary 전체 합계; 배송·할인·사용 포인트는 시험 정책상 0 |
| currency_code | string | `KRW` | 서버가 결정한 통화 코드 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 201
```

```json
{
    "success": true,
    "message": "성공적으로 처리되었습니다.",
    "data": {
        "items": [
            {
                "id": 38,
                "departure_id": 1,
                "product_id": 1,
                "product_option_id": 1,
                "quantity": 1,
                "...": "(8개 키 생략, 총 13개)"
            }
        ],
        "totals": {
            "subtotal": 189000,
            "subtotal_formatted": "189,000원",
            "product_coupon_discount": 0,
            "product_coupon_discount_formatted": "0원",
            "code_discount": 0,
            "...": "(31개 키 생략, 총 36개)"
        },
        "currency_code": "KRW"
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 409 | Conflict | 가용 인원·native 계산 정책·선택 Cart·멱등 본문 또는 허용 상태 전이 충돌 |
| 404 | Not Found | 출발일 ID가 없는 경우 |

<!-- @generated:end -->

**설명** departure_id는 여행 출발일 ID이며 옵션 ID가 아니다. 동일 옵션 담기는 native Cart 수량에 더한다. 게시·출발일·옵션·정원 및 무료 배송정책을 검사하고 native CartService로 저장한다. 담기 성공은 시험 예약 접수나 실제 예약 확정이 아니다.


### DELETE /api/modules/raonslab-travel_lab/cart/{cart}
<!-- @generated:start:api.modules.raonslab-travel_lab.cart.destroy -->
- **라우트명**: `api.modules.raonslab-travel_lab.cart.destroy`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\CartController@destroy`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| cart | path | integer | 예 | 숫자 ID | 대상 cart의 식별자 |

**요청 예시**

```http
DELETE /api/modules/raonslab-travel_lab/cart/{cart} HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| items | array | 현재 사용자의 실제 ecommerce Cart 중 여행 출발일에 연결된 행; 일반 커머스 행 제외 |
| items[].id | integer | 수정·삭제·접수에 사용할 Cart ID |
| items[].departure_id | integer/null | 출발일 ID; 옵션 ID가 아님. 연결이 사라진 행은 null |
| items[].product_id / items[].product_option_id | integer | 서버가 결정한 실제 커머스 상품·옵션 ID |
| items[].quantity | integer | 현재 저장된 인원 수 |
| items[].remaining_capacity | integer | max(0, min(출발 정원, 옵션 재고) - 모의 배정 인원) |
| items[].product_name | object/null | 커머스 상품명 번역 객체 |
| items[].departure_date / items[].return_date | string/null | 출발일·귀국일 YYYY-MM-DD |
| items[].unit_price / items[].line_total | integer/null | native 계산기의 단가·행 최종 금액; 이용 불가 행은 null |
| items[].available | boolean | 현재 출발일·게시·재고·배송정책 검증 결과 |
| items[].unavailable_reason | string/null | departure_unavailable, capacity_unavailable, shipping_policy_unavailable 또는 unsupported_cart |
| totals | object | sirsoft-ecommerce Summary.toArray 전체 결과; 클라이언트 계산값이 아님 |
| totals.subtotal / totals.final_amount | integer | 이용 가능한 행의 소계·최종 시험 금액 |
| totals.total_shipping / totals.total_discount / totals.points_used | integer | 시험 접수 정책에서 0이어야 하는 배송비·할인·사용 포인트 |
| currency_code | string | 서버 기본 통화 코드, 예: KRW |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다. `totals`/스냅샷의 주요 필드만 발췌했습니다._

```http
HTTP/1.1 200
```

```json
{
  "success": true,
  "message": "성공적으로 처리되었습니다.",
  "data": {
    "items": [],
    "totals": {
      "subtotal": 0,
      "total_shipping": 0,
      "total_discount": 0,
      "points_used": 0,
      "final_amount": 0
    },
    "currency_code": "KRW"
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |

<!-- @generated:end -->

**설명** 현재 사용자 소유 여행 Cart만 삭제하고 남은 장바구니를 반환한다. 타인 행은 404. 모의 배정이나 실제 재고는 변경하지 않는다.


### PATCH /api/modules/raonslab-travel_lab/cart/{cart}
<!-- @generated:start:api.modules.raonslab-travel_lab.cart.update -->
- **라우트명**: `api.modules.raonslab-travel_lab.cart.update`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\CartController@update`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| cart | path | integer | 예 | 숫자 ID | 대상 cart의 식별자 |
| quantity | body | integer | 예 | min 1, max 99 | 여행 인원 수. native Cart 최대 수량(현재 99), 상품 구매 제한 및 잔여 시험 정원을 함께 검사 |

**요청 예시**

```http
PATCH /api/modules/raonslab-travel_lab/cart/{cart} HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
    "quantity": 1
}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| items | array | 현재 사용자의 실제 ecommerce Cart 중 여행 출발일에 연결된 행; 일반 커머스 행 제외 |
| items[].id | integer | 수정·삭제·접수에 사용할 Cart ID |
| items[].departure_id | integer/null | 출발일 ID; 옵션 ID가 아님. 연결이 사라진 행은 null |
| items[].product_id / items[].product_option_id | integer | 서버가 결정한 실제 커머스 상품·옵션 ID |
| items[].quantity | integer | 현재 저장된 인원 수 |
| items[].remaining_capacity | integer | max(0, min(출발 정원, 옵션 재고) - 모의 배정 인원) |
| items[].product_name | object/null | 커머스 상품명 번역 객체 |
| items[].departure_date / items[].return_date | string/null | 출발일·귀국일 YYYY-MM-DD |
| items[].unit_price / items[].line_total | integer/null | native 계산기의 단가·행 최종 금액; 이용 불가 행은 null |
| items[].available | boolean | 현재 출발일·게시·재고·배송정책 검증 결과 |
| items[].unavailable_reason | string/null | departure_unavailable, capacity_unavailable, shipping_policy_unavailable 또는 unsupported_cart |
| totals | object | sirsoft-ecommerce Summary.toArray 전체 결과; 클라이언트 계산값이 아님 |
| totals.subtotal / totals.final_amount | integer | 이용 가능한 행의 소계·최종 시험 금액 |
| totals.total_shipping / totals.total_discount / totals.points_used | integer | 시험 접수 정책에서 0이어야 하는 배송비·할인·사용 포인트 |
| currency_code | string | 서버 기본 통화 코드, 예: KRW |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다. `totals`/스냅샷의 주요 필드만 발췌했습니다._

```http
HTTP/1.1 200
```

```json
{
  "success": true,
  "message": "성공적으로 처리되었습니다.",
  "data": {
    "items": [
      {
        "id": 8,
        "departure_id": 3,
        "product_id": 2,
        "product_option_id": 6,
        "quantity": 2,
        "remaining_capacity": 5,
        "product_name": {
          "ko": "합성 여행",
          "en": "Synthetic journey"
        },
        "departure_date": "2026-11-01",
        "return_date": "2026-11-03",
        "unit_price": 12000,
        "line_total": 24000,
        "available": true,
        "unavailable_reason": null
      }
    ],
    "totals": {
      "subtotal": 24000,
      "total_shipping": 0,
      "total_discount": 0,
      "points_used": 0,
      "final_amount": 24000
    },
    "currency_code": "KRW"
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 409 | Conflict | 가용 인원·native 계산 정책·선택 Cart·멱등 본문 또는 허용 상태 전이 충돌 |

<!-- @generated:end -->

**설명** 저장된 인원을 새 quantity로 교체한 뒤 서버 합계를 반환한다. 타인 Cart는 404, 가용 인원·옵션 또는 native 정책 충돌은 409. 클라이언트 금액을 받지 않는다.


## 공통 가격·상태 제한

- 모든 endpoint는 실제 Sanctum Bearer 토큰을 사용한다. 정의하지 않은 입력 필드(금액·통화·user_id·상품/옵션 ID 포함)는 422다. 가격은 native CartService/계산기가 소유한다.
- 출발일은 여행 사업 시간대 `catalog.business_timezone`(기본 `Asia/Seoul`)의 오늘보다 이후여야 한다. 당일/과거 출발은 `departure_unavailable`이다.
- 상품은 명시적 비기본 활성 무료 배송정책이 필요하며 활성 KR 설정을 포함한 모든 설정에 유료/추가 요금·외부 API가 없어야 한다. 계산 국가는 서버가 KR로 고정하고 원래 컨텍스트를 복원한다. 배송·할인·사용 포인트가 0이 아닌 계산은 409다.
- 모든 Cart/Inquiry 요청은 사용자별 공통 120회/분, Cart 쓰기는 별도 공통 60회/분 제한. 과다 요청은 429다.
- 위 예시의 totals는 핵심 필드 발췌다. 전체 키는 [native Summary](../../../sirsoft-ecommerce/src/DTO/Summary.php)의 `toArray()`를 따른다.

근거: [workflow 계약](workflow.md), [TravelCartResource](../../src/Http/Resources/TravelCartResource.php), [TravelCartService](../../src/Services/TravelCartService.php), [workflow routes](../../src/routes/workflow.php).
