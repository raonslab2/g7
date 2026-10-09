# Inquiries API 레퍼런스

> **소유**: module `raonslab-travel_lab` · **생성**: `php artisan api:docgen` (native 골격·부분 실측 + 사람이 작성한 소스 계약). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## 계약과 증거 구분

- 모듈 전체 native `api:docgen` 생성 대상은 31개 라우트이며, 자동 실측은 11건이며 트랜잭션 후 rollback한 POST도 포함한다. 나머지 20건의 기본 프로브 생략은 전체 기능 성공 판정이 아니다.
- `@probed` 표시는 lead가 수집한 실제 응답으로, 기본 자동 프로브와 별도 쓰기 응답 캡처를 포함한다. 출력의 `...`는 생성기의 축약 표시이며 실제 API 필드가 아니다.
- 실측하지 못한 응답의 표·예시는 Controllers/FormRequests/Resources와 계약 테스트에서 작성한 **합성 계약 예시**로 표시했다. 전체 31개 라이브 PASS나 독립 Validation을 주장하지 않는다.
- 재생성은 사람이 채운 설명·예시를 보존하는 native 경로를 사용한다. 설치된 API와 고정 검증 SHA의 일치·재검증은 별도 증거로 판단한다.

---


### GET /api/modules/raonslab-travel_lab/admin/inquiries
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.inquiries.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.inquiries.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Admin\InquiryController@index`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.inquiries.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| page | query | integer | 아니오 | min 1 | 조회할 페이지 번호 (1부터 시작) |
| per_page | query | integer | 아니오 | min 1, max 100 | 페이지당 항목 수 |
| status | query | string | 아니오 | InquiryStatus enum | 상태 필터 (해당 상태의 항목만 조회) |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/admin/inquiries?page=1&per_page=1&status=TEST_INQUIRY HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드 + `data.pagination`._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `26` | 기본 키 (내부 식별자) |
| reference | string | `TL-00000026` | TL-접두사와 8자리 문의 ID로 구성한 참조 번호 |
| status | string | `CANCELLED` | InquiryStatus 시험 상태 값; 이 리소스는 status_label/status_variant를 제공하지 않음 |
| allowed_transitions | array | `[]` | 현재 시험 상태에서 허용되는 다음 상태 배열; 호출 권한은 별도 검사 |
| can_cancel | boolean | `false` | 작성자이고 현재 상태에서 취소가 허용되는지 |
| first_product_name | string | `제주 바다와 오름 (테스트)` | 첫 품목 스냅샷의 현재 언어 상품명 |
| total_quantity | integer | `1` | 접수 품목 인원 수 합계 |
| product_name | string | `제주 바다와 오름 (테스트)` | first_product_name과 같은 화면 요약 상품명 |
| departure_label | string | `2026-11-01` | 첫 품목의 출발일 YYYY-MM-DD |
| party_size | integer | `1` | total_quantity와 같은 화면 요약 인원 |
| requester_name | string | `Synthetic recovery 1440 edited-contac…` | 정규화된 contact.name |
| total_amount | string | `189000.00` | 접수 당시 native 계산기로 확정한 시험 금액(소수 문자열) |
| currency_code | string | `KRW` | 서버가 결정한 통화 코드 |
| contact | object | `{"name":"Synthetic recovery 1440 edited-contact 179154530…` | 연락처 객체. 허용 키는 name과 선택적 phone뿐 |
| items | array | `[{"id":26,"departure_id":1,"product_id":1,"product_option…` | 현재 사용자 여행 Cart 행과 서버 계산 결과 |
| created_at | string | `2026-10-09 20:28:23` | 생성 일시 |
| updated_at | string | `2026-10-09 20:28:24` | 최종 수정 일시 |
| is_owner | boolean | `false` | 현재 인증 사용자가 이 리소스의 소유자인지 여부 (BaseApiResource 표준 메타) |
| abilities | object | `{"can_update":true,"can_cancel":false}` | 현재 사용자가 이 리소스에 수행 가능한 작업 불리언 맵 (can_update, can_delete 등 — 권한 맵 기반) |
| user_id | integer | `19` | user 식별자 (연관 리소스 참조) |
| admin_note | string | `W03-1791542300168 synthetic note` | 관리자 전용 메모; 사용자 API에는 제공하지 않음 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "성공적으로 처리되었습니다.",
    "data": {
        "data": [
            {
                "id": 26,
                "reference": "TL-00000026",
                "status": "CANCELLED",
                "allowed_transitions": [],
                "can_cancel": false,
                "first_product_name": "제주 바다와 오름 (테스트)",
                "total_quantity": 1,
                "product_name": "제주 바다와 오름 (테스트)",
                "departure_label": "2026-11-01",
                "party_size": 1,
                "requester_name": "Synthetic recovery 1440 edited-contact 17915453028",
                "total_amount": "189000.00",
                "currency_code": "KRW",
                "contact": {
                    "name": "Synthetic recovery 1440 edited-contact 17915453028",
                    "phone": "000-0000-0001"
                },
                "items": [
                    {
                        "id": 26,
                        "departure_id": 1,
                        "product_id": 1,
                        "product_option_id": 1,
                        "quantity": 1,
                        "unit_price": "189000.00",
                        "line_total": "189000.00",
                        "product_name": {
                            "ko": "제주 바다와 오름 (테스트)",
                            "en": "Jeju sea and volcanic hills (test)"
                        },
                        "departure_date": "2026-11-01"
                    }
                ],
                "created_at": "2026-10-09 20:28:23",
                "updated_at": "2026-10-09 20:28:24",
                "is_owner": false,
                "abilities": {
                    "can_update": true,
                    "can_cancel": false
                },
                "user_id": 19,
                "admin_note": null
            },
            {
                "id": 25,
                "reference": "TL-00000025",
                "status": "CANCELLED",
                "allowed_transitions": [],
                "can_cancel": false,
                "first_product_name": "제주 바다와 오름 (테스트)",
                "total_quantity": 1,
                "product_name": "제주 바다와 오름 (테스트)",
                "departure_label": "2026-11-01",
                "party_size": 1,
                "requester_name": "Synthetic recovery 1440 reload 1791545296945",
                "total_amount": "189000.00",
                "currency_code": "KRW",
                "contact": {
                    "name": "Synthetic recovery 1440 reload 1791545296945",
                    "phone": "000-0000-0000"
                },
                "items": [
                    {
                        "id": 25,
                        "departure_id": 1,
                        "product_id": 1,
                        "product_option_id": 1,
                        "quantity": 1,
                        "unit_price": "189000.00",
                        "line_total": "189000.00",
                        "product_name": {
                            "ko": "제주 바다와 오름 (테스트)",
                            "en": "Jeju sea and volcanic hills (test)"
                        },
                        "departure_date": "2026-11-01"
                    }
                ],
                "created_at": "2026-10-09 20:28:17",
                "updated_at": "2026-10-09 20:28:20",
                "is_owner": false,
                "abilities": {
                    "can_update": true,
                    "can_cancel": false
                },
                "user_id": 19,
                "admin_note": null
            },
            "... (총 25건 중 2건 표시)"
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 25,
            "from": 1,
            "to": 25,
            "has_more_pages": true,
            "last_page": 2,
            "total": 26
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
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.inquiries.read`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |

<!-- @generated:end -->

**설명** 관리자 read 권한의 유효 스코프 안에서 접수를 ID 역순으로 조회한다. 사용자 API와 달리 user_id·admin_note 및 collection abilities를 제공하며 calculation_snapshot/events는 목록에 포함하지 않는다.


### GET /api/modules/raonslab-travel_lab/admin/inquiries/{inquiry}
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.inquiries.show -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.inquiries.show`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Admin\InquiryController@show`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.inquiries.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| inquiry | path | integer | 예 | 숫자 ID | 대상 inquiry의 식별자 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/admin/inquiries/{inquiry} HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id / reference | integer / string | 내부 문의 ID / TL-로 시작하는 화면용 참조 번호 |
| status | string | TEST_INQUIRY, UNDER_REVIEW, TEST_ACCEPTED, DECLINED, CANCELLED 중 하나; 모두 시험 상태 |
| allowed_transitions | array<string> | 현재 상태에서 enum이 허용하는 다음 상태; 호출자 권한은 별도 검사 |
| can_cancel | boolean | 작성자이고 현재 상태가 취소를 허용할 때만 true |
| first_product_name / product_name | string/null | 첫 품목 스냅샷의 현재 언어 상품명; 두 필드는 같은 요약값 |
| total_quantity / party_size | integer | 모든 품목 quantity 합계; 두 필드는 같은 요약값 |
| departure_label | string/null | 첫 품목 출발일 YYYY-MM-DD |
| requester_name | string/null | 정규화된 contact.name |
| total_amount | decimal string | 접수 당시 native 계산기로 확정한 시험 금액 |
| currency_code | string | 접수 당시 통화 코드 |
| contact | object | name과 선택적 phone만 있는 서버 정규화 연락처 |
| items | array | 영속 품목 스냅샷. 이후 카탈로그 변경에도 금액·상품명·날짜 보존 |
| items[].id / items[].departure_id / items[].product_id / items[].product_option_id | integer | 문의 품목·출발일·native 상품·옵션 ID |
| items[].quantity | integer | 잠금된 Cart에서 읽은 접수 인원 |
| items[].unit_price / items[].line_total | decimal string | 접수 당시 서버 단가·행 금액 |
| items[].product_name | object | 접수 당시 상품명 번역 객체 |
| items[].departure_date | string/null | 접수 당시 출발일 YYYY-MM-DD |
| created_at / updated_at | string/null | 사용자 시간대 기준 생성·변경 일시 |
| is_owner | boolean | 현재 인증 사용자가 문의 작성자인지 |
| abilities.can_cancel | boolean | can_cancel과 같은 현재 사용자 취소 가능 여부 |
| user_id | integer | 작성자 내부 ID; 관리자 리소스만 제공 |
| admin_note | string/null | 관리자 메모. 사용자 문의 API에는 제공하지 않음 |
| abilities.can_update | boolean | 현재 사용자의 inquiries.update 권한 메타; 실제 변경은 스코프도 검사 |
| calculation_snapshot | object | 관리자 상세/변경에 포함되는 native 계산 결과·currency_code·shipping_country. 목록에는 제외 |
| events | array | 관리자 상세/변경에 포함되는 상태·메모 변경 감사 기록. 목록에는 제외 |
| events[].id / events[].actor_id | integer | 이벤트 ID / 실행자 ID |
| events[].from_status / events[].to_status | string/null / string | 이전/다음 시험 상태. 최초 접수 from_status는 null |
| events[].note | string/null | 해당 변경 메모 |
| events[].created_at | string/null | 이벤트 생성 시각 ISO8601 |

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
    "id": 5,
    "reference": "TL-00000005",
    "status": "TEST_INQUIRY",
    "allowed_transitions": [
      "UNDER_REVIEW",
      "DECLINED",
      "CANCELLED"
    ],
    "can_cancel": false,
    "first_product_name": "합성 여행",
    "total_quantity": 2,
    "product_name": "합성 여행",
    "departure_label": "2026-11-01",
    "party_size": 2,
    "requester_name": "Synthetic member",
    "total_amount": "24000.00",
    "currency_code": "KRW",
    "contact": {
      "name": "Synthetic member"
    },
    "items": [
      {
        "id": 9,
        "departure_id": 3,
        "product_id": 2,
        "product_option_id": 6,
        "quantity": 2,
        "unit_price": "12000.00",
        "line_total": "24000.00",
        "product_name": {
          "ko": "합성 여행",
          "en": "Synthetic journey"
        },
        "departure_date": "2026-11-01"
      }
    ],
    "created_at": "2026-10-09 17:55:06",
    "updated_at": "2026-10-09 17:55:06",
    "is_owner": false,
    "abilities": {
      "can_update": true,
      "can_cancel": false
    },
    "user_id": 7,
    "admin_note": "합성 테스트 검토 메모",
    "calculation_snapshot": {
      "currency_code": "KRW",
      "shipping_country": "KR",
      "summary": {
        "subtotal": 24000,
        "final_amount": 24000
      }
    },
    "events": [
      {
        "id": 1,
        "actor_id": 7,
        "from_status": null,
        "to_status": "TEST_INQUIRY",
        "note": null,
        "created_at": "2026-10-09T08:55:06+00:00"
      }
    ]
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.inquiries.read`)이 없는 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |

<!-- @generated:end -->

**설명** 관리자 read 권한과 대상 문의 스코프를 모두 검사한다. 전체 금액 스냅샷과 상태·메모 이벤트를 확인하는 상세 API. 권한/스코프 실패는 403, 존재하지 않으면 404.


### PATCH /api/modules/raonslab-travel_lab/admin/inquiries/{inquiry}
<!-- @generated:start:api.modules.raonslab-travel_lab.admin.inquiries.update -->
- **라우트명**: `api.modules.raonslab-travel_lab.admin.inquiries.update`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Admin\InquiryController@update`
- **인증/권한**: `auth:sanctum` + `permission:raonslab-travel_lab.inquiries.update`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| inquiry | path | integer | 예 | 숫자 ID | 대상 inquiry의 식별자 |
| status | body | string | 예 | InquiryStatus enum | 요청 시험 상태. InquiryStatus enum과 현재 상태의 허용 전이를 모두 검사 |
| admin_note | body | string | 아니오 | max 2000 | 관리자 전용 메모; 사용자 API에는 제공하지 않음 |

**요청 예시**

```http
PATCH /api/modules/raonslab-travel_lab/admin/inquiries/{inquiry} HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
    "status": "UNDER_REVIEW",
    "admin_note": "합성 테스트 검토 메모"
}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id / reference | integer / string | 내부 문의 ID / TL-로 시작하는 화면용 참조 번호 |
| status | string | TEST_INQUIRY, UNDER_REVIEW, TEST_ACCEPTED, DECLINED, CANCELLED 중 하나; 모두 시험 상태 |
| allowed_transitions | array<string> | 현재 상태에서 enum이 허용하는 다음 상태; 호출자 권한은 별도 검사 |
| can_cancel | boolean | 작성자이고 현재 상태가 취소를 허용할 때만 true |
| first_product_name / product_name | string/null | 첫 품목 스냅샷의 현재 언어 상품명; 두 필드는 같은 요약값 |
| total_quantity / party_size | integer | 모든 품목 quantity 합계; 두 필드는 같은 요약값 |
| departure_label | string/null | 첫 품목 출발일 YYYY-MM-DD |
| requester_name | string/null | 정규화된 contact.name |
| total_amount | decimal string | 접수 당시 native 계산기로 확정한 시험 금액 |
| currency_code | string | 접수 당시 통화 코드 |
| contact | object | name과 선택적 phone만 있는 서버 정규화 연락처 |
| items | array | 영속 품목 스냅샷. 이후 카탈로그 변경에도 금액·상품명·날짜 보존 |
| items[].id / items[].departure_id / items[].product_id / items[].product_option_id | integer | 문의 품목·출발일·native 상품·옵션 ID |
| items[].quantity | integer | 잠금된 Cart에서 읽은 접수 인원 |
| items[].unit_price / items[].line_total | decimal string | 접수 당시 서버 단가·행 금액 |
| items[].product_name | object | 접수 당시 상품명 번역 객체 |
| items[].departure_date | string/null | 접수 당시 출발일 YYYY-MM-DD |
| created_at / updated_at | string/null | 사용자 시간대 기준 생성·변경 일시 |
| is_owner | boolean | 현재 인증 사용자가 문의 작성자인지 |
| abilities.can_cancel | boolean | can_cancel과 같은 현재 사용자 취소 가능 여부 |
| user_id | integer | 작성자 내부 ID; 관리자 리소스만 제공 |
| admin_note | string/null | 관리자 메모. 사용자 문의 API에는 제공하지 않음 |
| abilities.can_update | boolean | 현재 사용자의 inquiries.update 권한 메타; 실제 변경은 스코프도 검사 |
| calculation_snapshot | object | 관리자 상세/변경에 포함되는 native 계산 결과·currency_code·shipping_country. 목록에는 제외 |
| events | array | 관리자 상세/변경에 포함되는 상태·메모 변경 감사 기록. 목록에는 제외 |
| events[].id / events[].actor_id | integer | 이벤트 ID / 실행자 ID |
| events[].from_status / events[].to_status | string/null / string | 이전/다음 시험 상태. 최초 접수 from_status는 null |
| events[].note | string/null | 해당 변경 메모 |
| events[].created_at | string/null | 이벤트 생성 시각 ISO8601 |

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
    "id": 5,
    "reference": "TL-00000005",
    "status": "UNDER_REVIEW",
    "allowed_transitions": [
      "TEST_ACCEPTED",
      "DECLINED",
      "CANCELLED"
    ],
    "can_cancel": false,
    "first_product_name": "합성 여행",
    "total_quantity": 2,
    "product_name": "합성 여행",
    "departure_label": "2026-11-01",
    "party_size": 2,
    "requester_name": "Synthetic member",
    "total_amount": "24000.00",
    "currency_code": "KRW",
    "contact": {
      "name": "Synthetic member"
    },
    "items": [
      {
        "id": 9,
        "departure_id": 3,
        "product_id": 2,
        "product_option_id": 6,
        "quantity": 2,
        "unit_price": "12000.00",
        "line_total": "24000.00",
        "product_name": {
          "ko": "합성 여행",
          "en": "Synthetic journey"
        },
        "departure_date": "2026-11-01"
      }
    ],
    "created_at": "2026-10-09 17:55:06",
    "updated_at": "2026-10-09 17:55:06",
    "is_owner": false,
    "abilities": {
      "can_update": true,
      "can_cancel": false
    },
    "user_id": 7,
    "admin_note": "합성 테스트 검토 메모",
    "calculation_snapshot": {
      "currency_code": "KRW",
      "shipping_country": "KR",
      "summary": {
        "subtotal": 24000,
        "final_amount": 24000
      }
    },
    "events": [
      {
        "id": 1,
        "actor_id": 7,
        "from_status": null,
        "to_status": "TEST_INQUIRY",
        "note": null,
        "created_at": "2026-10-09T08:55:06+00:00"
      },
      {
        "id": 2,
        "actor_id": 1,
        "from_status": "TEST_INQUIRY",
        "to_status": "UNDER_REVIEW",
        "note": "합성 테스트 검토 메모",
        "created_at": "2026-10-09T08:56:06+00:00"
      }
    ]
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 403 | Forbidden | 요구 권한(`raonslab-travel_lab.inquiries.update`)이 없는 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 409 | Conflict | 가용 인원·native 계산 정책·선택 Cart·멱등 본문 또는 허용 상태 전이 충돌 |

<!-- @generated:end -->

**설명** 상태와 선택적 관리자 메모를 영속 변경한다. TEST_INQUIRY→UNDER_REVIEW/DECLINED/CANCELLED, UNDER_REVIEW→TEST_ACCEPTED/DECLINED/CANCELLED, TEST_ACCEPTED→CANCELLED만 허용한다. 동일 상태의 새 메모는 이벤트를 남기며 동일 메모 재전송은 중복 쓰기를 하지 않는다. 거절·취소는 모의 배정을 한 번 해제한다. TEST_ACCEPTED도 실제 예약 확정이 아니다.


### GET /api/modules/raonslab-travel_lab/inquiries
<!-- @generated:start:api.modules.raonslab-travel_lab.inquiries.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.inquiries.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\InquiryController@index`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| page | query | integer | 아니오 | min 1 | 조회할 페이지 번호 (1부터 시작) |
| per_page | query | integer | 아니오 | min 1, max 100 | 페이지당 항목 수 |
| status | query | string | 아니오 | InquiryStatus enum | 상태 필터 (해당 상태의 항목만 조회) |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/inquiries?page=1&per_page=1&status=TEST_INQUIRY HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드 + `data.pagination`._

_아래 표는 소스 계약에서 작성했다. 생성 시 실제 관측 목록은 비어 있었으며 그 응답 예시는 그대로 보존한다._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id / reference | integer / string | 내부 문의 ID / TL-로 시작하는 화면용 참조 번호 |
| status | string | TEST_INQUIRY, UNDER_REVIEW, TEST_ACCEPTED, DECLINED, CANCELLED 중 하나; 모두 시험 상태 |
| allowed_transitions | array<string> | 현재 상태에서 enum이 허용하는 다음 상태; 호출자 권한은 별도 검사 |
| can_cancel | boolean | 작성자이고 현재 상태가 취소를 허용할 때만 true |
| first_product_name / product_name | string/null | 첫 품목 스냅샷의 현재 언어 상품명; 두 필드는 같은 요약값 |
| total_quantity / party_size | integer | 모든 품목 quantity 합계; 두 필드는 같은 요약값 |
| departure_label | string/null | 첫 품목 출발일 YYYY-MM-DD |
| requester_name | string/null | 정규화된 contact.name |
| total_amount | decimal string | 접수 당시 native 계산기로 확정한 시험 금액 |
| currency_code | string | 접수 당시 통화 코드 |
| contact | object | name과 선택적 phone만 있는 서버 정규화 연락처 |
| items | array | 영속 품목 스냅샷. 이후 카탈로그 변경에도 금액·상품명·날짜 보존 |
| items[].id / items[].departure_id / items[].product_id / items[].product_option_id | integer | 문의 품목·출발일·native 상품·옵션 ID |
| items[].quantity | integer | 잠금된 Cart에서 읽은 접수 인원 |
| items[].unit_price / items[].line_total | decimal string | 접수 당시 서버 단가·행 금액 |
| items[].product_name | object | 접수 당시 상품명 번역 객체 |
| items[].departure_date | string/null | 접수 당시 출발일 YYYY-MM-DD |
| created_at / updated_at | string/null | 사용자 시간대 기준 생성·변경 일시 |
| is_owner | boolean | 현재 인증 사용자가 문의 작성자인지 |
| abilities.can_cancel | boolean | can_cancel과 같은 현재 사용자 취소 가능 여부 |

목록 봉투 내부 필드:

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| data | array | 위 InquiryResource 항목 배열 |
| pagination | object | current_page, per_page, from, to, has_more_pages, last_page, total. 빈 목록 from/to는 null |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "성공적으로 처리되었습니다.",
    "data": {
        "data": [],
        "pagination": {
            "current_page": 1,
            "per_page": 25,
            "from": null,
            "to": null,
            "has_more_pages": false,
            "last_page": 1,
            "total": 0
        }
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |

<!-- @generated:end -->

**설명** 인증 사용자 본인의 접수만 ID 역순으로 조회한다. 상품명·출발일·금액은 접수 당시 영속 스냅샷이며 카탈로그 변경과 독립적이다. 페이지 기본값은 1, per_page 기본값은 20.


### POST /api/modules/raonslab-travel_lab/inquiries
<!-- @generated:start:api.modules.raonslab-travel_lab.inquiries.store -->
- **라우트명**: `api.modules.raonslab-travel_lab.inquiries.store`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\InquiryController@store`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| cart_ids | body | array<integer> | 예 | 1–100개, 양의 정수, distinct | 본인 Cart ID 배열; 인원 수는 저장된 Cart에서 읽음 |
| contact | body | object | 예 | name, phone만 | 연락처 객체. 허용 키는 name과 선택적 phone뿐 |
| contact.name | body | string | 예 | max 100 | 대상의 이름/명칭 |
| contact.phone | body | string/null | 아니오 | max 40 | 전화번호 |
| idempotency_key | body | string | 예 | 8–100자; 영숫자로 시작, 이후 영숫자·._:- | 동일 사용자 재시도 식별 키. 동일 본문 재전송은 기존 문의 200 반환 |

**요청 예시**

```http
POST /api/modules/raonslab-travel_lab/inquiries HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
  "cart_ids": [
    8
  ],
  "contact": {
    "name": "Synthetic member",
    "phone": null
  },
  "idempotency_key": "synthetic-request-001"
}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id / reference | integer / string | 내부 문의 ID / TL-로 시작하는 화면용 참조 번호 |
| status | string | TEST_INQUIRY, UNDER_REVIEW, TEST_ACCEPTED, DECLINED, CANCELLED 중 하나; 모두 시험 상태 |
| allowed_transitions | array<string> | 현재 상태에서 enum이 허용하는 다음 상태; 호출자 권한은 별도 검사 |
| can_cancel | boolean | 작성자이고 현재 상태가 취소를 허용할 때만 true |
| first_product_name / product_name | string/null | 첫 품목 스냅샷의 현재 언어 상품명; 두 필드는 같은 요약값 |
| total_quantity / party_size | integer | 모든 품목 quantity 합계; 두 필드는 같은 요약값 |
| departure_label | string/null | 첫 품목 출발일 YYYY-MM-DD |
| requester_name | string/null | 정규화된 contact.name |
| total_amount | decimal string | 접수 당시 native 계산기로 확정한 시험 금액 |
| currency_code | string | 접수 당시 통화 코드 |
| contact | object | name과 선택적 phone만 있는 서버 정규화 연락처 |
| items | array | 영속 품목 스냅샷. 이후 카탈로그 변경에도 금액·상품명·날짜 보존 |
| items[].id / items[].departure_id / items[].product_id / items[].product_option_id | integer | 문의 품목·출발일·native 상품·옵션 ID |
| items[].quantity | integer | 잠금된 Cart에서 읽은 접수 인원 |
| items[].unit_price / items[].line_total | decimal string | 접수 당시 서버 단가·행 금액 |
| items[].product_name | object | 접수 당시 상품명 번역 객체 |
| items[].departure_date | string/null | 접수 당시 출발일 YYYY-MM-DD |
| created_at / updated_at | string/null | 사용자 시간대 기준 생성·변경 일시 |
| is_owner | boolean | 현재 인증 사용자가 문의 작성자인지 |
| abilities.can_cancel | boolean | can_cancel과 같은 현재 사용자 취소 가능 여부 |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다._

```http
HTTP/1.1 201
```

```json
{
  "success": true,
  "message": "성공적으로 처리되었습니다.",
  "data": {
    "id": 5,
    "reference": "TL-00000005",
    "status": "TEST_INQUIRY",
    "allowed_transitions": [
      "UNDER_REVIEW",
      "DECLINED",
      "CANCELLED"
    ],
    "can_cancel": true,
    "first_product_name": "합성 여행",
    "total_quantity": 2,
    "product_name": "합성 여행",
    "departure_label": "2026-11-01",
    "party_size": 2,
    "requester_name": "Synthetic member",
    "total_amount": "24000.00",
    "currency_code": "KRW",
    "contact": {
      "name": "Synthetic member"
    },
    "items": [
      {
        "id": 9,
        "departure_id": 3,
        "product_id": 2,
        "product_option_id": 6,
        "quantity": 2,
        "unit_price": "12000.00",
        "line_total": "24000.00",
        "product_name": {
          "ko": "합성 여행",
          "en": "Synthetic journey"
        },
        "departure_date": "2026-11-01"
      }
    ],
    "created_at": "2026-10-09 17:55:06",
    "updated_at": "2026-10-09 17:55:06",
    "is_owner": true,
    "abilities": {
      "can_cancel": true
    }
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

<!-- @generated:end -->

**설명** 잠금된 본인 Cart 수량과 native 계산 결과로 TEST_INQUIRY를 원자적으로 생성하고, 모의 정원을 배정한 뒤 선택 Cart를 삭제한다. 새 접수는 201. 같은 사용자/키와 정규화된 동일 본문 재시도는 Cart가 소비되어도 기존 문의를 200으로 반환한다. 같은 키의 다른 본문은 409. 연락처 문자열 trim, null/빈 phone 제거, contact 키·Cart ID 정렬 후 비교한다. Idempotency-Key 헤더로도 키를 전달할 수 있으며 body와 둘 다 있으면 일치해야 한다. 응답 유실 재시도는 키와 본문을 그대로 보존한다. 선택 Cart 타인/삭제/변경은 cart_changed 409이며 상세 열람의 타인 문의 404와 구분한다. 실제 주문·결제·메일·문자는 만들지 않는다.


### GET /api/modules/raonslab-travel_lab/inquiries/{inquiry}
<!-- @generated:start:api.modules.raonslab-travel_lab.inquiries.show -->
- **라우트명**: `api.modules.raonslab-travel_lab.inquiries.show`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\InquiryController@show`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| inquiry | path | integer | 예 | 숫자 ID | 대상 inquiry의 식별자 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/inquiries/{inquiry} HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id / reference | integer / string | 내부 문의 ID / TL-로 시작하는 화면용 참조 번호 |
| status | string | TEST_INQUIRY, UNDER_REVIEW, TEST_ACCEPTED, DECLINED, CANCELLED 중 하나; 모두 시험 상태 |
| allowed_transitions | array<string> | 현재 상태에서 enum이 허용하는 다음 상태; 호출자 권한은 별도 검사 |
| can_cancel | boolean | 작성자이고 현재 상태가 취소를 허용할 때만 true |
| first_product_name / product_name | string/null | 첫 품목 스냅샷의 현재 언어 상품명; 두 필드는 같은 요약값 |
| total_quantity / party_size | integer | 모든 품목 quantity 합계; 두 필드는 같은 요약값 |
| departure_label | string/null | 첫 품목 출발일 YYYY-MM-DD |
| requester_name | string/null | 정규화된 contact.name |
| total_amount | decimal string | 접수 당시 native 계산기로 확정한 시험 금액 |
| currency_code | string | 접수 당시 통화 코드 |
| contact | object | name과 선택적 phone만 있는 서버 정규화 연락처 |
| items | array | 영속 품목 스냅샷. 이후 카탈로그 변경에도 금액·상품명·날짜 보존 |
| items[].id / items[].departure_id / items[].product_id / items[].product_option_id | integer | 문의 품목·출발일·native 상품·옵션 ID |
| items[].quantity | integer | 잠금된 Cart에서 읽은 접수 인원 |
| items[].unit_price / items[].line_total | decimal string | 접수 당시 서버 단가·행 금액 |
| items[].product_name | object | 접수 당시 상품명 번역 객체 |
| items[].departure_date | string/null | 접수 당시 출발일 YYYY-MM-DD |
| created_at / updated_at | string/null | 사용자 시간대 기준 생성·변경 일시 |
| is_owner | boolean | 현재 인증 사용자가 문의 작성자인지 |
| abilities.can_cancel | boolean | can_cancel과 같은 현재 사용자 취소 가능 여부 |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다._

```http
HTTP/1.1 200
```

```json
{
  "success": true,
  "message": "성공적으로 처리되었습니다.",
  "data": {
    "id": 5,
    "reference": "TL-00000005",
    "status": "TEST_INQUIRY",
    "allowed_transitions": [
      "UNDER_REVIEW",
      "DECLINED",
      "CANCELLED"
    ],
    "can_cancel": true,
    "first_product_name": "합성 여행",
    "total_quantity": 2,
    "product_name": "합성 여행",
    "departure_label": "2026-11-01",
    "party_size": 2,
    "requester_name": "Synthetic member",
    "total_amount": "24000.00",
    "currency_code": "KRW",
    "contact": {
      "name": "Synthetic member"
    },
    "items": [
      {
        "id": 9,
        "departure_id": 3,
        "product_id": 2,
        "product_option_id": 6,
        "quantity": 2,
        "unit_price": "12000.00",
        "line_total": "24000.00",
        "product_name": {
          "ko": "합성 여행",
          "en": "Synthetic journey"
        },
        "departure_date": "2026-11-01"
      }
    ],
    "created_at": "2026-10-09 17:55:06",
    "updated_at": "2026-10-09 17:55:06",
    "is_owner": true,
    "abilities": {
      "can_cancel": true
    }
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

**설명** 작성자 소유 문의만 재조회한다. 타인/없는 문의는 동일한 404로 숨긴다. 관리자 메모·계산 스냅샷·이벤트 actor 정보는 이 사용자 리소스에 포함하지 않는다.


### POST /api/modules/raonslab-travel_lab/inquiries/{inquiry}/cancel
<!-- @generated:start:api.modules.raonslab-travel_lab.inquiries.cancel -->
- **라우트명**: `api.modules.raonslab-travel_lab.inquiries.cancel`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\InquiryController@cancel`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| inquiry | path | integer | 예 | 숫자 ID | 대상 inquiry의 식별자 |

**요청 예시**

```http
POST /api/modules/raonslab-travel_lab/inquiries/{inquiry}/cancel HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id / reference | integer / string | 내부 문의 ID / TL-로 시작하는 화면용 참조 번호 |
| status | string | TEST_INQUIRY, UNDER_REVIEW, TEST_ACCEPTED, DECLINED, CANCELLED 중 하나; 모두 시험 상태 |
| allowed_transitions | array<string> | 현재 상태에서 enum이 허용하는 다음 상태; 호출자 권한은 별도 검사 |
| can_cancel | boolean | 작성자이고 현재 상태가 취소를 허용할 때만 true |
| first_product_name / product_name | string/null | 첫 품목 스냅샷의 현재 언어 상품명; 두 필드는 같은 요약값 |
| total_quantity / party_size | integer | 모든 품목 quantity 합계; 두 필드는 같은 요약값 |
| departure_label | string/null | 첫 품목 출발일 YYYY-MM-DD |
| requester_name | string/null | 정규화된 contact.name |
| total_amount | decimal string | 접수 당시 native 계산기로 확정한 시험 금액 |
| currency_code | string | 접수 당시 통화 코드 |
| contact | object | name과 선택적 phone만 있는 서버 정규화 연락처 |
| items | array | 영속 품목 스냅샷. 이후 카탈로그 변경에도 금액·상품명·날짜 보존 |
| items[].id / items[].departure_id / items[].product_id / items[].product_option_id | integer | 문의 품목·출발일·native 상품·옵션 ID |
| items[].quantity | integer | 잠금된 Cart에서 읽은 접수 인원 |
| items[].unit_price / items[].line_total | decimal string | 접수 당시 서버 단가·행 금액 |
| items[].product_name | object | 접수 당시 상품명 번역 객체 |
| items[].departure_date | string/null | 접수 당시 출발일 YYYY-MM-DD |
| created_at / updated_at | string/null | 사용자 시간대 기준 생성·변경 일시 |
| is_owner | boolean | 현재 인증 사용자가 문의 작성자인지 |
| abilities.can_cancel | boolean | can_cancel과 같은 현재 사용자 취소 가능 여부 |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다._

```http
HTTP/1.1 200
```

```json
{
  "success": true,
  "message": "성공적으로 처리되었습니다.",
  "data": {
    "id": 5,
    "reference": "TL-00000005",
    "status": "CANCELLED",
    "allowed_transitions": [],
    "can_cancel": false,
    "first_product_name": "합성 여행",
    "total_quantity": 2,
    "product_name": "합성 여행",
    "departure_label": "2026-11-01",
    "party_size": 2,
    "requester_name": "Synthetic member",
    "total_amount": "24000.00",
    "currency_code": "KRW",
    "contact": {
      "name": "Synthetic member"
    },
    "items": [
      {
        "id": 9,
        "departure_id": 3,
        "product_id": 2,
        "product_option_id": 6,
        "quantity": 2,
        "unit_price": "12000.00",
        "line_total": "24000.00",
        "product_name": {
          "ko": "합성 여행",
          "en": "Synthetic journey"
        },
        "departure_date": "2026-11-01"
      }
    ],
    "created_at": "2026-10-09 17:55:06",
    "updated_at": "2026-10-09 17:55:06",
    "is_owner": true,
    "abilities": {
      "can_cancel": false
    }
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 409 | Conflict | 가용 인원·native 계산 정책·선택 Cart·멱등 본문 또는 허용 상태 전이 충돌 |

<!-- @generated:end -->

**설명** 본문 없이 본인 문의를 CANCELLED로 변경한다. 허용 상태에서만 취소하며 모의 정원을 해제한다. 이미 CANCELLED이면 같은 결과를 반환하고 중복 해제하지 않는다. DECLINED에서 취소로 바꾸려 하면 409. 실제 환불·재고 조정·외부 알림은 없다.


## 공통 접수·권한 계약

- 모든 endpoint는 `auth:sanctum`을 사용한다. 관리자 endpoint는 admin 역할과 `raonslab-travel_lab.inquiries.read` 또는 `.update` 관리자 권한이 필요하고 유효 소유자 스코프를 적용한다. 관리자 대상 스코프 실패는 403; 사용자 타인 상세/취소는 404다.
- 허용되지 않은 top-level 입력은 422이며 제출에 금액·인원·user_id를 받지 않는다. contact는 name/phone만 허용한다. FormRequest 검증 오류는 `errors`에 필드별 배열로 반환한다. 인증/429 봉투는 코어 처리기 계약을 따른다.
- 모든 workflow 요청 120회/분; 접수는 별도 10회/분(재시도 포함), 취소 20회/분, 관리자 변경 60회/분. bucket은 인증 사용자별이며 제한 초과는 429다.
- 잠금/중복 키/모의 정원 배정과 해제는 영속 DB 트랜잭션에서 수행한다. 실제 주문·결제·예약 확정·환불·항공/호텔 연계·메일/SMS는 연결하지 않는다.
- 사용자 응답에는 admin_note·user_id·calculation_snapshot·events가 없다. 관리자 목록은 snapshot/events를 제외하고 상세/변경은 포함한다. 아래 합성 스냅샷 예시는 주요 키 발췌이며 실제 전체 계산 JSON을 대신하지 않는다.

근거: [workflow 계약](workflow.md), [InquiryResource](../../src/Http/Resources/InquiryResource.php), [AdminInquiryResource](../../src/Http/Resources/AdminInquiryResource.php), [SubmitInquiryRequest](../../src/Http/Requests/Workflow/SubmitInquiryRequest.php), [InquiryService](../../src/Services/InquiryService.php), [InquiryStatus](../../src/Enums/InquiryStatus.php).
