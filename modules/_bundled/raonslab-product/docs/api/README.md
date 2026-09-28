# RAON Agent Factory 상담 API

모든 응답은 G7 `ResponseHelper`의 `{success, data, message, error}` envelope를 사용합니다. 공개 접수는
HTTPS·same-origin·승인된 개인정보 설정을 모두 충족할 때만 열리며, 현재 HTTP 검수 환경에서는
`intake_enabled=false`가 정상입니다.

## GET `/api/modules/raonslab-product/consultations/config`

현재 요청에서 개인정보 접수가 가능한지와 승인된 동의 문안을 반환합니다. 인증은 필요하지 않으며
분당 60회 제한을 적용합니다.

`data`는 `intake_enabled`, `consent_version`, `privacy_copy`, `privacy_policy_url`, `privacy_links`,
`privacy_contact`, `retention_notice`를 포함합니다. 설정이 없거나 URL이 신뢰 가능한 HTTPS가 아니면
접수는 닫히고 승인되지 않은 값을 만들어 반환하지 않습니다.

## POST `/api/modules/raonslab-product/consultations`

`Idempotency-Key` 헤더와 동일 HTTPS origin을 요구하며 분당 10회 제한을 적용합니다.

| 필드 | 필수 | 제한 |
| --- | --- | --- |
| `contact_name` | 예 | 문자열, 최대 100자 |
| `email` | 예 | RFC 이메일, 최대 254자 |
| `message` | 예 | 문자열, 최대 5,000자 |
| `privacy_consent` | 예 | accepted |
| `privacy_consent_version` | 예 | 현재 config 버전과 일치, 최대 100자 |
| `company` | 아니오 | 문자열, 최대 160자 |
| `phone` | 아니오 | 문자열, 최대 40자 |
| `service_interest` | 아니오 | 문자열, 최대 120자 |

성공 응답의 `data`는 `reference`, `status`, `received_at`을 포함합니다. 최초 저장은 201, 동일 키·동일
payload 재시도는 200, 동일 키·다른 payload는 409입니다. validation은 422, rate limit은 429,
접수 비활성·저장 실패는 503입니다. HTTP 또는 cross-origin 요청은 저장 전에 차단됩니다.

신규 상담은 설정된 `raon-consultations` board가 공개 비활성·항상 비밀·관리자 전용이고 알림이 꺼진
상태일 때만 공식 `PostService`로 저장됩니다. 검색 listener는 이 board를 fail-closed로 비색인합니다.
기존 전용 상담 테이블에 행이 있으면 자동 이관하지 않고 접수를 닫습니다.

## 기존 product admin API

아래 endpoint는 인증·admin 경계 뒤에서 410을 반환하고 공식 board admin 경로와 legacy read-only 상태를
안내합니다.

- `GET /api/modules/raonslab-product/admin/consultations`
- `GET /api/modules/raonslab-product/admin/consultations/{reference}`
- `POST /api/modules/raonslab-product/admin/consultations/{reference}/notes`
- `PATCH /api/modules/raonslab-product/admin/consultations/{reference}/status`

신규 상담 운영은 `/admin/board/raon-consultations`와 `sirsoft-board`의 기존 admin API·권한 계약을
사용합니다. 본 모듈은 해당 API를 복제하지 않습니다.
