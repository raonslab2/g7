# Consultations API 레퍼런스

> **소유**: module `raonslab-product` · **생성**: `php artisan api:docgen` 골격을 기반으로 0.2.2 계약을 보강했습니다.

모든 응답은 G7 JSON envelope를 사용한다. 공개 접수는 same-origin, HTTPS, 승인된 개인정보 설정과
`Idempotency-Key`가 모두 충족될 때만 열린다. 관리자 API의 PII는 read/manage RBAC 안에서만 반환된다.

### GET /api/modules/raonslab-product/consultations/config
<!-- @generated:start:api.modules.raonslab-product.consultations.config -->
- **라우트명**: `api.modules.raonslab-product.consultations.config`
- **인증/권한**: 공개 (인증 불필요), `throttle:60,1`

**요청 파라미터**: 없음

**요청 예시**

```http
GET /api/modules/raonslab-product/consultations/config HTTP/1.1
Host: api.example.com
Accept: application/json
```

**응답 필드** (`data` 내부)

| 필드 | 타입 | 설명 |
| --- | --- | --- |
| intake_enabled | boolean | 실제 요청이 HTTPS이고 모든 공개 설정이 승인된 경우에만 `true` |
| consent_version | string | 승인된 개인정보 동의 문안 버전. 미승인 시 빈 문자열 |
| privacy_copy | string | 개인정보 수집·이용 문안. 미승인 시 빈 문자열 |
| privacy_policy_url | string | 신뢰 가능한 HTTPS 절대 URL만 게시 |
| privacy_links | array | 게시 가능한 개인정보 링크 목록 |
| privacy_contact | string | 승인된 담당 연락처. 안전하지 않은 URL이 포함되면 빈 문자열 |
| retention_notice | string | 승인된 보관·파기 안내 |

**응답 예시**

```json
{
  "success": true,
  "data": {
    "intake_enabled": false,
    "consent_version": "",
    "privacy_copy": "",
    "privacy_policy_url": "",
    "privacy_links": [],
    "privacy_contact": "",
    "retention_notice": ""
  }
}
```

**에러 응답**

| 상태코드 | 발생 조건 |
| --- | --- |
| 429 | 분당 config 조회 한도 초과 |
<!-- @generated:end -->

**설명**: HTTP 검수 환경과 설정 미완료 상태에서는 정상적으로 `200`과 `intake_enabled=false`를 반환한다.

### POST /api/modules/raonslab-product/consultations
<!-- @generated:start:api.modules.raonslab-product.consultations.store -->
- **라우트명**: `api.modules.raonslab-product.consultations.store`
- **인증/권한**: 공개, same-origin middleware, HTTPS fail-closed, `throttle:10,1`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 제한 | 설명 |
| --- | --- | --- | --- | --- | --- |
| Idempotency-Key | header | string | 예 | 16~200자, `[A-Za-z0-9._:-]` | 같은 payload 재시도 시 같은 키 사용 |
| contact_name | body | string | 예 | 최대 100자 | 담당자 이름 |
| email | body | email | 예 | 최대 254자 | 회신 이메일 |
| message | body | string | 예 | 최대 5000자 | 상담 내용 |
| privacy_consent | body | boolean | 예 | accepted | 개인정보 수집 동의 |
| privacy_consent_version | body | string | 예 | 현재 config 버전과 동일 | 동의 문안 버전 |
| company | body | string | 아니오 | 최대 160자 | 조직명 |
| phone | body | string | 아니오 | 최대 40자 | 연락처 |
| service_interest | body | string | 아니오 | 최대 120자 | 관심 서비스 |

**요청 예시**

```http
POST /api/modules/raonslab-product/consultations HTTP/1.1
Host: api.example.com
Origin: https://api.example.com
Accept: application/json
Content-Type: application/json
Idempotency-Key: synthetic-example-key-0001

{"contact_name":"Synthetic Visitor","email":"synthetic@example.test","message":"합성 상담 요청입니다.","privacy_consent":true,"privacy_consent_version":"approved-version"}
```

**성공 응답 필드** (`data` 내부)

| 필드 | 타입 | 설명 |
| --- | --- | --- |
| reference | string | 공개 접수 식별자 |
| status | string | 최초 상태 `NEW` |
| received_at | string|null | 저장 시각 ISO 8601 |

`201`은 새 저장, `200`은 같은 키·같은 payload의 재시도다. 저장 성공 전에는 성공 응답을 반환하지 않는다.

**에러 응답**

| 상태코드 | 발생 조건 | 추가 필드 |
| --- | --- | --- |
| 403 | Origin 누락 또는 다른 origin | — |
| 409 | 같은 Idempotency-Key에 다른 payload | — |
| 422 | 헤더·본문 검증 실패 | `errors.{field}` |
| 429 | 분당 접수 한도 초과 | `Retry-After` header |
| 500 | 저장 여부가 불확실한 일시 장애 | `errors.reason=temporary_failure`, `retryable=true`, `incident_id` |
| 503 | HTTPS 또는 승인 설정 미완료로 접수 닫힘 | `errors.reason=intake_disabled`, `retryable=false` |
<!-- @generated:end -->

**설명**: `500`, 네트워크 오류와 `429` 뒤에는 입력과 키를 보존해 같은 키로 재시도한다. `503 intake_disabled`는 config 재확인 후에만 양식을 닫는다.

### GET /api/modules/raonslab-product/admin/consultations
<!-- @generated:start:api.modules.raonslab-product.admin.consultations.index -->
- **라우트명**: `api.modules.raonslab-product.admin.consultations.index`
- **인증/권한**: `auth:sanctum` + `admin` + `permission:admin,raonslab-product.consultations.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값/제한 | 설명 |
| --- | --- | --- | --- | --- | --- |
| status | query | string | 아니오 | `NEW`, `CONTACTED`, `QUALIFIED`, `CLOSED` | 상태 필터 |
| page | query | integer | 아니오 | 1 이상 | 페이지 |
| per_page | query | integer | 아니오 | 1~100 | 페이지당 건수, 기본 20 |

**응답 필드** (`data` 내부)

| 필드 | 타입 | 설명 |
| --- | --- | --- |
| data | array | 상담 목록. reference, 연락 PII, 관심 서비스, 상태, 메일 상태, 시각 포함 |
| meta | object | current_page, last_page, per_page, total, from, to |
| abilities.can_manage | boolean | 현재 관리자가 메모·상태 변경 권한을 가졌는지 서버 판정 |

**에러 응답**

| 상태코드 | 발생 조건 |
| --- | --- |
| 401 | 인증되지 않음 |
| 403 | admin 또는 read 권한 없음 |
| 422 | query 검증 실패 |
<!-- @generated:end -->

**설명**: 범위 밖 페이지는 `200`, 빈 `data`, 유지된 `meta.total`로 응답해 전체 빈 목록과 구분한다.

### GET /api/modules/raonslab-product/admin/consultations/{consultation}
<!-- @generated:start:api.modules.raonslab-product.admin.consultations.show -->
- **라우트명**: `api.modules.raonslab-product.admin.consultations.show`
- **인증/권한**: `auth:sanctum` + `admin` + `permission:admin,raonslab-product.consultations.read`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 설명 |
| --- | --- | --- | --- | --- |
| consultation | path | string | 예 | 상담 reference |

**응답 필드** (`data` 내부)

목록 필드에 `next_status`, phone, message, privacy_consent_version, privacy_consented_at,
close_outcome, mail_attempted_at, mail_sent_at, history, `abilities.can_manage`를 더한다.
`next_status`는 서버가 허용하는 다음 상태 하나이며 `CLOSED`에서는 `null`이다.

**에러 응답**

| 상태코드 | 발생 조건 |
| --- | --- |
| 401 | 인증되지 않음 |
| 403 | admin 또는 read 권한 없음 |
| 404 | reference가 존재하지 않음 |
<!-- @generated:end -->

**설명**: history에는 생성·메모·상태 변경 이력과 actor가 포함되며 관리자 권한 경계 밖에는 노출하지 않는다.

### POST /api/modules/raonslab-product/admin/consultations/{consultation}/notes
<!-- @generated:start:api.modules.raonslab-product.admin.consultations.notes.store -->
- **라우트명**: `api.modules.raonslab-product.admin.consultations.notes.store`
- **인증/권한**: `auth:sanctum` + `admin` + `permission:admin,raonslab-product.consultations.manage`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 제한 | 설명 |
| --- | --- | --- | --- | --- | --- |
| consultation | path | string | 예 | — | 상담 reference |
| note | body | string | 예 | 최대 2000자 | 암호화 저장되는 내부 메모 |

성공 시 갱신된 상세 데이터와 `abilities.can_manage`를 반환한다.

**에러 응답**

| 상태코드 | 발생 조건 |
| --- | --- |
| 401 | 인증되지 않음 |
| 403 | admin 또는 manage 권한 없음 |
| 404 | reference가 존재하지 않음 |
| 422 | note 검증 실패 |
<!-- @generated:end -->

**설명**: 메모는 일반 게시판·검색·알림·AI payload로 전달하지 않는다.

### PATCH /api/modules/raonslab-product/admin/consultations/{consultation}/status
<!-- @generated:start:api.modules.raonslab-product.admin.consultations.status.update -->
- **라우트명**: `api.modules.raonslab-product.admin.consultations.status.update`
- **인증/권한**: `auth:sanctum` + `admin` + `permission:admin,raonslab-product.consultations.manage`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값/제한 | 설명 |
| --- | --- | --- | --- | --- | --- |
| consultation | path | string | 예 | — | 상담 reference |
| status | body | string | 예 | `NEW`, `CONTACTED`, `QUALIFIED`, `CLOSED` | 서버의 `next_status`와 같아야 함 |
| close_outcome | body | string | 조건부 | 최대 160자 | `CLOSED`에서 필수, 그 외 금지 |

상태는 `NEW → CONTACTED → QUALIFIED → CLOSED` 순서만 허용한다. 성공 시 갱신된 상세 데이터,
새 `next_status`, `abilities.can_manage`와 이력을 반환한다.

**에러 응답**

| 상태코드 | 발생 조건 |
| --- | --- |
| 401 | 인증되지 않음 |
| 403 | admin 또는 manage 권한 없음 |
| 404 | reference가 존재하지 않음 |
| 422 | 순서를 건너뛴 상태 변경, close_outcome 또는 body 검증 실패 |
<!-- @generated:end -->

**설명**: 클라이언트가 전이 규칙을 만들지 않고 서버의 `next_status`만 사용한다.
