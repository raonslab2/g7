# Travel Lab 0.1.0 장바구니·테스트 문의 API

이 문서는 `src/routes/workflow.php`, FormRequest, Resource, 실제 커머스 서비스 계약을 기준으로 작성했다.
통합 담당자는 `api.php`에서 이 파일을 require한 뒤 `php artisan api:docgen --scope=module:raonslab-travel_lab` 및 `ext:docgen`을 실행하여 생성 목차에 연결한다. 이 분리 작업트리에는 manifest/provider/domain이 없으므로 그 통합 명령의 실행 결과를 주장하지 않는다.

기준 소스: `6853f40d58acbf53a2f29cbb9dd422cc439047a9`.
API 접두사: `/api/modules/raonslab-travel_lab`.
인증은 실제 Sanctum Bearer 토큰이며, 모든 엔드포인트에 `Authorization: Bearer <token>`과 `Accept: application/json`을 보낸다.
관리자 API는 관리자 역할과 해당 `admin` 타입 권한을 함께 요구한다.

이 API가 생성하는 것은 테스트 문의와 모의 정원 점유다. 주문·결제·실예약·환불·메일·SMS·외부 공급자 연동·스케줄은 생성하지 않는다.
여행 상품 식별자는 기존 ecommerce Product ID, 출발 옵션은 ProductOption ID, 인원은 Cart.quantity다.

## 엔드포인트

라우트명에는 `api.modules.raonslab-travel_lab.` 접두사가 자동으로 붙는다.

| 메서드 | 상대 URI | 라우트명 | 요청 | 성공 |
|---|---|---|---|---|
| GET | `/cart` | `cart.index` | 없음 | 200 TravelCartResource |
| POST | `/cart` | `cart.store` | departure_id, quantity | 201 TravelCartResource |
| PATCH | `/cart/{cart}` | `cart.update` | quantity | 200 TravelCartResource |
| DELETE | `/cart/{cart}` | `cart.destroy` | 없음 | 200 TravelCartResource |
| GET | `/inquiries` | `inquiries.index` | page?, per_page? | 200 InquiryCollection |
| POST | `/inquiries` | `inquiries.store` | cart_ids, contact, idempotency_key 또는 헤더 | 201 InquiryResource (재전송도 201) |
| GET | `/inquiries/{inquiry}` | `inquiries.show` | 없음 | 200 InquiryResource |
| POST | `/inquiries/{inquiry}/cancel` | `inquiries.cancel` | 없음 | 200 InquiryResource |
| GET | `/admin/inquiries` | `admin.inquiries.index` | page?, per_page? | 200 AdminInquiryCollection |
| GET | `/admin/inquiries/{inquiry}` | `admin.inquiries.show` | 없음 | 200 AdminInquiryResource |
| PATCH | `/admin/inquiries/{inquiry}` | `admin.inquiries.update` | status, admin_note? | 200 AdminInquiryResource |

경로 ID는 양의 정수로 취급한다. 다른 사용자의 카트/문의와 일반 커머스 카트 ID는 여행 변경 대상으로 접근할 수 없다.
다른 사용자의 리소스는 404로 응답하고, 관리자 권한/스코프 부족은 403이다.

## 공통 응답·입력 규칙

컨트롤러의 업무 응답은 ResponseHelper 봉투를 사용한다. 미인증 401은 기존 코어 Sanctum 예외 처리의 `{"message":"..."}` 응답을 그대로 사용한다.

```json
{"success":true,"message":"성공했습니다.","data":{}}
```

```json
{"success":false,"message":"입력값을 확인해 주세요.","errors":{"quantity":["최소 인원은 1명입니다."]}}
```

문법/필드 검증은 422, 변경된 재고/출발일/상품 상태/계산 결과/상태 전이/멱등 키 충돌은 409, 인증 없음은 401이다.
입력에 정의되지 않은 **모든 최상위 필드**를 422로 거절한다. 가격·통화·수량 스냅샷을 클라이언트가 넣어서 바꿀 수 없다.
예: `price`, `unit_price`, `line_total`, `total_amount`, `currency_code`, `product_id`, `product_option_id`, `user_id`, `cart_key`는 임의 추가할 수 없다.
문의 POST에는 `quantity`도 허용하지 않는다. 서버가 잠근 Cart.quantity만 인원으로 사용한다.
contact 하위에는 name, phone만 허용한다.

## 장바구니 요청·응답

`quantity`는 필수 정수, 최소 1, 최대 `config('sirsoft-ecommerce.cart.max_quantity', 99)`다.
POST는 같은 출발 옵션의 기존 수량에 **추가**하고, PATCH는 지정 수량으로 **교체**한다.
POST `departure_id`는 필수 양의 정수다. 기존 라인과의 누적 수량도 옵션 재고와 잔여 모의 정원을 초과할 수 없다.
게시된 여행 상품, 판매·전시 중인 커머스 상품, 활성 옵션과 출발일, 미래 출발일, 출발일 이후/당일 귀국일을 확인한다.
오늘 출발하는 항목은 이용 불가다. 날짜 비교는 앱의 현재 날짜/시간대 기준이다.

요청 예시:

```http
GET /api/modules/raonslab-travel_lab/cart
Authorization: Bearer <token>
```

```http
POST /api/modules/raonslab-travel_lab/cart
Content-Type: application/json
Authorization: Bearer <token>

{"departure_id":17,"quantity":2}
```

```http
PATCH /api/modules/raonslab-travel_lab/cart/81
Content-Type: application/json
Authorization: Bearer <token>

{"quantity":3}
```

```http
DELETE /api/modules/raonslab-travel_lab/cart/81
Authorization: Bearer <token>
```

네 메서드 모두 변경 후 현재 여행 카트와 서버 계산을 반환한다. DELETE 후 마지막 항목이면 items는 빈 배열이다.
일반 커머스 상품은 항목·합계에서 제외하며 그대로 보존한다. getCartWithCalculation의 원본 items는 전체 커머스 카트이므로 응답에 그대로 노출하지 않는다.

```json
{
  "success": true,
  "message": "성공했습니다.",
  "data": {
    "items": [{
      "id":81,"departure_id":17,"product_id":32,"product_option_id":44,
      "quantity":2,"product_name":{"ko":"테스트 여행","en":"Test Travel"},
      "departure_date":"2026-11-01","return_date":"2026-11-04",
      "unit_price":12000,"line_total":24000,"available":true,"unavailable_reason":null
    }],
    "totals":{"subtotal":24000,"total_discount":0,"total_shipping":0,"final_amount":24000},
    "currency_code":"KRW"
  }
}
```

위 예시는 totals의 핵심 필드만 발췌했다. 실제 totals는 커머스 Summary.toArray() 전체이며 할인·배송비·세금·포맷 문자열·통화 변환 메타를 포함할 수 있다.

| 응답 필드 | 형식·의미 |
|---|---|
| items[].id | 커머스 Cart ID |
| departure_id / product_id / product_option_id | 출발일 / 기존 상품 / 옵션 ID |
| quantity | 서버 카트의 인원 |
| product_name | 현재 커머스 상품 다국어 JSON |
| departure_date / return_date | YYYY-MM-DD |
| unit_price / line_total | 커머스 ItemCalculation.unitPrice / finalAmount; 이용 불가 시 null |
| available | 현재 문의 가능 여부 |
| unavailable_reason | null, departure_unavailable, capacity_unavailable, unsupported_cart |
| totals | 이용 가능한 여행 라인에 대한 커머스 Summary.toArray() |
| currency_code | CurrencyConversionService.getDefaultCurrency()의 기본 통화 |

이용 불가 항목은 조회에 남고 합계에서는 제외된다. 따라서 여러 오래된 항목도 하나씩 삭제할 수 있다.
추가옵션 선택이 있는 라인과 같은 옵션을 공유하는 여러 카트 라인은 계산의 옵션 ID 매핑이 모호하므로 unsupported_cart로 격리한다.
문의 생성에는 이러한 라인을 사용할 수 없다. 가격을 로컬에서 다시 계산하거나 옵션의 표시용 selling_price를 사용하지 않는다.
`line_total`은 품목 최종 금액이며 배송비는 총계에 포함되므로 총계와 품목 합계가 다를 수 있다.

## 문의 생성·멱등성

POST `/inquiries` 입력:

| 필드 | 검증 |
|---|---|
| cart_ids | 필수 배열, 1~100개, 중복 없는 양의 정수 |
| contact.name | 필수 문자열, 최대 100자 |
| contact.phone | 선택, null 또는 최대 40자 문자열 |
| idempotency_key | 필드 또는 Idempotency-Key 헤더로 필수, 8~128자, 첫 글자 영문/숫자, 이후 영문/숫자/`.`/`_`/`:`/`-` |

헤더와 필드를 함께 보내면 두 값이 정확히 같아야 한다(다르면 422).
정렬된 cart_ids와 연락처를 해시하므로 cart_ids 순서만 바뀐 재전송은 동일한 요청이다.
동일 사용자·키·payload는 카트가 이미 삭제되었거나 문의가 취소된 뒤에도 **같은 문의 ID와 저장 스냅샷**을 반환한다.
그 문의의 현재 status가 반환되며 상태를 되돌리지 않는다. 연락처/선택 ID가 바뀐 키 재사용은 409다.
phone 생략과 phone:null은 다른 payload로 취급한다. 사용자마다 키 namespace가 독립적이다.
실패로 트랜잭션이 롤백된 키는 새 문의 생성에 재사용할 수 있다.

```http
POST /api/modules/raonslab-travel_lab/inquiries
Authorization: Bearer <token>
Idempotency-Key: travel-submit-001
Content-Type: application/json

{"cart_ids":[81],"contact":{"name":"김여행","phone":"010-0000-0000"}}
```

또는 같은 키를 본문 `idempotency_key`에 넣는다.

```json
{
  "success":true,"message":"성공했습니다.",
  "data":{
    "id":5,"status":"test_inquiry","total_amount":"24000.00","currency_code":"KRW",
    "contact":{"name":"김여행","phone":"010-0000-0000"},
    "items":[{"id":7,"departure_id":17,"product_id":32,"product_option_id":44,
      "quantity":2,"unit_price":"12000.00","line_total":"24000.00",
      "product_name":{"ko":"테스트 여행","en":"Test Travel"},"departure_date":"2026-11-01"}],
    "created_at":"2026-10-09 10:00:00","updated_at":"2026-10-09 10:00:00","is_owner":true
  }
}
```

생성 트랜잭션은 사용자 행 잠금 → 같은 키 문의 locking read → 선택 카트 ID 오름차순 잠금 → 출발일 ID 오름차순 잠금 → 상품/옵션 ID 오름차순 잠금 → 여행 공개 상태 확인 순서다.
동시 같은 키 호출은 사용자 잠금에서 직렬화되고 기존 완료 기록을 읽는다. DB의 UNIQUE(user_id,idempotency_key)는 반드시 유지한다.
잠금 안에서 옵션 활성·재고·상품 상태·날짜·정원을 재확인하고 커머스 계산을 다시 수행한다.
정원은 조건부 UPDATE(`reserved + quantity <= capacity`)로 점유한다. 불변 스냅샷 생성 후 선택 카트만 커머스 서비스로 삭제한다.
삭제 개수가 다르거나 어느 단계에서 예외가 나면 문의·모든 정원·카트 변경이 함께 롤백된다.
같은 사용자의 Travel Lab 변경은 사용자 잠금을 공유한다. 서로 다른 사용자는 출발일 잠금과 정원 조건을 공유한다.
Commerce stock 자체는 줄이지 않으며 모의 정원만 점유한다.

## 사용자 문의 조회·취소

```http
GET /api/modules/raonslab-travel_lab/inquiries?page=1&per_page=20
Authorization: Bearer <token>
```

```json
{"success":true,"message":"성공했습니다.","data":{"data":[{"id":5,"status":"test_inquiry","total_amount":"24000.00","currency_code":"KRW","contact":{"name":"김여행"},"items":[],"is_owner":true}],"pagination":{"current_page":1,"per_page":20,"total":1,"last_page":1}}}
```

목록 예시는 문의 행/페이지 메타의 핵심 필드를 발췌했다. 실제 목록의 각 행은 위 InquiryResource 전체이며 pagination은 G7 BaseApiCollection의 표준 메타다.
page는 선택 정수 최소 1, per_page는 선택 정수 1~100(기본20). 자신의 문의만 최신 ID순으로 반환한다.

```http
GET /api/modules/raonslab-travel_lab/inquiries/5
Authorization: Bearer <token>
```

200 응답 data는 위 문의 생성의 InquiryResource와 동일하다. 가격·상품명·출발일은 저장 스냅샷이며 현재 상품 수정으로 바뀌지 않는다.
고객 응답에는 admin_note, payload_hash, idempotency_key, user_id를 노출하지 않는다.

```http
POST /api/modules/raonslab-travel_lab/inquiries/5/cancel
Authorization: Bearer <token>
```

200 응답은 동일 InquiryResource에서 status가 cancelled로 바뀐다. 재취소도 200이며 정원을 중복 반환하지 않는다.
취소는 카트를 복원하지 않는다.

## 관리자 문의 조회·변경

GET 두 엔드포인트는 `raonslab-travel_lab.inquiries.read`, PATCH는 `raonslab-travel_lab.inquiries.update` 권한이 필요하다.
세 엔드포인트 모두 `auth:sanctum`, AdminBaseController의 `admin` 미들웨어를 사용한다.
서비스도 관리자 역할·admin 타입 권한을 확인한다. 목록은 PermissionHelper의 유효 스코프를 적용하고 상세/변경은 소유자 스코프를 다시 확인한다.

```http
GET /api/modules/raonslab-travel_lab/admin/inquiries?page=1&per_page=20
Authorization: Bearer <admin-token>
```

200 응답은 사용자 목록과 같은 구조에 각 행 `user_id`, `admin_note`, `abilities.can_update` 및 목록 `abilities.can_update`가 추가된다.

```json
{"success":true,"message":"성공했습니다.","data":{"data":[{"id":5,"status":"test_inquiry","user_id":2,"admin_note":null,"abilities":{"can_update":true}}],"pagination":{"current_page":1,"total":1,"per_page":20,"last_page":1},"abilities":{"can_update":true}}}
```

```http
GET /api/modules/raonslab-travel_lab/admin/inquiries/5
Authorization: Bearer <admin-token>
```

200 응답은 위 InquiryResource 전체에 user_id/admin_note/abilities.can_update가 추가된 AdminInquiryResource다.

```http
PATCH /api/modules/raonslab-travel_lab/admin/inquiries/5
Authorization: Bearer <admin-token>
Content-Type: application/json

{"status":"under_review","admin_note":"테스트 일정 검토 중"}
```

status는 InquiryStatus enum 값 필수, admin_note는 선택 null/문자열 최대 2,000자다.
200 응답은 변경 후 AdminInquiryResource다. admin_note 생략 또는 null은 기존 메모를 유지한다.
동일 상태로의 재전송은 메모를 포함하여 no-op다. 상태가 실제로 바뀔 때 비null admin_note를 저장한다.

| 현재 | 다음 상태 |
|---|---|
| TEST_INQUIRY (`test_inquiry`) | UNDER_REVIEW, DECLINED, CANCELLED |
| UNDER_REVIEW (`under_review`) | TEST_ACCEPTED, DECLINED, CANCELLED |
| TEST_ACCEPTED (`test_accepted`) | CANCELLED |
| DECLINED (`declined`) | 없음 |
| CANCELLED (`cancelled`) | 없음 |

모든 상태에서 같은 상태로의 재전송은 허용한다. TEST_ACCEPTED도 실 예약 확정이 아니며 취소 가능하다.
거절/취소는 문의 행과 출발일 행 잠금 안에서 상태와 reserved를 함께 변경하므로 한 번만 정원이 반환된다.
reserved 부족 등 불일치면 409이며 이미 반환한 다른 출발일의 정원과 메모·상태도 모두 롤백된다.

## 통합 담당자의 필수 연결

이 작업의 소유 범위는 workflow 서비스·저장소·컨트롤러·요청·리소스·라우트·테스트·이 문서다.
manifest/provider/model/enum/migration/shared api.php/시더/템플릿/운영 DB는 변경하지 않았다.

1. provider 바인딩:

```php
$this->app->bind(\Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowCartRepositoryInterface::class,
    \Modules\Raonslab\TravelLab\Repositories\WorkflowCartRepository::class);
$this->app->bind(\Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowInquiryRepositoryInterface::class,
    \Modules\Raonslab\TravelLab\Repositories\WorkflowInquiryRepository::class);
```

2. 기존 모듈 `src/routes/api.php` 안에서 `require __DIR__.'/workflow.php';`를 한 번 수행한다. 별도 prefix를 이중으로 붙이지 않는다.
3. read/update 권한 모두 `type=admin`, `owner_key=user_id`, `resource_route_key=inquiry`로 선언한다. 이 메타가 없으면 G7 scope helper가 스코프를 적용하지 않는다.
4. 계약 v1의 Inquiry/InquiryItem fillable과 status(enum)/contact(array)/product_name(array)/금액(decimal:2)/날짜 cast를 유지한다. reserved는 모의 정원이다.
5. InquiryStatus case는 TEST_INQUIRY/UNDER_REVIEW/TEST_ACCEPTED/DECLINED/CANCELLED. enum backing 값은 위 API 예시와 맞춘다.
6. 코어 >=7.0.11, ecommerce >=1.2.1, board/page >=1.1.2 제약과 0.1.0 manifest/CHANGELOG는 통합 담당자가 소유한다.
7. `src/lang/{ko,en}/workflow.php`에 아래 키를 추가한다. 실패 응답은 ResponseHelper가 이 네임스페이스 키를 번역한다. 기본 검증은 코어 번역을 사용한다.

| 키 | ko | en |
|---|---|---|
| not_found | 요청한 여행 항목을 찾을 수 없습니다. | The travel item was not found. |
| unauthorized | 로그인이 필요합니다. | Authentication is required. |
| forbidden | 이 문의에 접근할 권한이 없습니다. | You cannot access this inquiry. |
| departure_unavailable | 현재 이용할 수 없는 출발일입니다. | This departure is unavailable. |
| capacity_unavailable | 선택 인원에 대한 정원 또는 재고가 부족합니다. | Capacity or stock is insufficient for this quantity. |
| unsupported_cart | 추가옵션 또는 중복 옵션 항목은 여행 문의에 사용할 수 없습니다. | Additional selections or duplicate option lines cannot be used for travel inquiries. |
| calculation_changed | 선택 항목과 서버 계산 결과가 일치하지 않습니다. | The selected items no longer match the server calculation. |
| cart_changed | 선택한 장바구니 항목이 변경되었습니다. | The selected cart items have changed. |
| idempotency_conflict | 같은 멱등 키를 다른 요청에 사용할 수 없습니다. | The idempotency key was used for a different request. |
| idempotency_mismatch | 헤더와 본문의 멱등 키가 다릅니다. | Header and body idempotency keys differ. |
| invalid_transition | 허용되지 않는 테스트 문의 상태 변경입니다. | This test inquiry transition is not allowed. |
| capacity_inconsistent | 모의 정원 기록이 일치하지 않아 변경할 수 없습니다. | Simulated capacity records are inconsistent. |
| commerce_rejected | 커머스 장바구니 정책에 따라 요청이 거절되었습니다. | The commerce cart policy rejected this request. |
| unsupported_field | 이 입력 필드는 허용되지 않습니다. | This input field is not allowed. |

8. 합본에서 실제 도메인 모델/마이그레이션을 사용해 워크플로 테스트를 재실행하고 API/확장 문서 생성 목차를 검증한다.

## 테스트 근거·한계

`tests/scenarios/workflow.yaml`이 시나리오 매니페스트다. 실행 명령:

```bash
php vendor/bin/phpunit modules/_bundled/raonslab-travel_lab/tests/Feature/TravelWorkflowTest.php
vendor/bin/pint --test modules/_bundled/raonslab-travel_lab/src modules/_bundled/raonslab-travel_lab/tests
```

실제 커머스 서비스·계산·저장소·Sanctum Bearer 인증을 쓰고 DB는 메모리 SQLite로 고정한다.
이 분리 작업트리에 없는 도메인은 tests/Fixtures에만 조건부 계약 모델 4개와 enum 1개, 테스트용 여행 스키마로 제공한다.
통합된 실제 모델/마이그레이션이 있으면 그쪽을 우선 사용한다. 이는 도메인 구현이나 독립 Validation PASS를 대신하지 않는다.
SQLite의 전역 index 명 중복을 테스트 전용 grammar로 한정하며, 관계없는 MySQL generated-column 적립 제약 마이그레이션 1개는 SQLite 테스트에서 제외한다.
MySQL의 실제 동시 잠금/같은 키 경합/정원 경합 검증은 이 SQLite 실행의 PASS 범위에 포함되지 않으며 **NOT_RUN**이다.

## 구현 근거와 검토 결과

기존 구현 확인은 `modules/_bundled/sirsoft-ecommerce/src/Services/CartService.php`의 실제 bulkAddToCart/updateQuantity/deleteItems/getCartWithCalculation 및 `src/DTO/ItemCalculation.php`, `src/DTO/Summary.php`, `src/Services/CurrencyConversionService.php`를 사용했다. 핵심 원본 계약은 다음과 같다.

```php
$this->commerce->getCartWithCalculation($userId, null, [], 0, $selectedCartIds);
// ItemCalculation: productId, productOptionId, quantity, unitPrice, finalAmount
// Summary: finalAmount는 배송비를 포함한다.
$this->currency->getDefaultCurrency();
```

선택 계산 결과와 전체 items의 차이, 옵션 selling_price가 가격 SSoT가 아니라는 점, 옵션 활성 검사가 커머스 카트에 없다는 점을 확인해 어댑터에서 처리했다. 구현 순서는 계약 저장소/잠금 → 커머스 카트 어댑터 → 원자적 문의/전이 → FormRequest/Resource/11개 라우트 → 실제 커머스·Bearer 테스트였다.

최종 개발 테스트는 **48개 테스트·373개 assertion PASS**, 소스·테스트 Pint PASS, 매니페스트 17개 흐름·52개 효과 마커 PASS다. 내부 검토에서 발견한 오래된 카트 삭제 롤백 및 롤백/스코프 테스트 빈틈을 수정했다. 고정된 최종 소스 diff의 내부 검토는 차단 사항 없음으로 종료했다. 상세 실행 근거와 정확한 변경 경로는 `tests/WORKFLOW_EVIDENCE.md`에 보관한다.
