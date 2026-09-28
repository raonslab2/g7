# 온라인 상담 공개 접수 활성화 절차

상담 접수의 기술 경로(공개 폼 → 비공개 상담 게시판 → 관리자 처리)는 구현·배포되어 있다.
공개 접수만 **운영자 승인 대기**로 닫혀 있으며, 이 문서는 무엇을 승인하면 열 수 있는지 정리한다.

## 현재 상태 (0.3.1)

| 구분 | 상태 |
| --- | --- |
| 공개 상담 폼·접수 API | 구현됨, `intake_enabled=false` 로 닫힘 (HTTP 검수 주소) |
| 저장소 | `raon-consultations` 비공개 게시판 — 비활성, 항상 비밀, 관리자 전용, 파일 업로드·알림·검색 색인 차단 |
| 관리자 처리 | 관리자 > **사업 상담** 메뉴 → `/admin/board/raon-consultations` |
| 알림 | 관리자 화면 확인만 사용. 메일 발송 없음 (`MAIL_MAILER=log` 는 발송이 아니다) |
| 합성 검증 | `raonslab-product:consultation-rehearsal` (트랜잭션 롤백, 잔여 0 확인) |

## 관리자 처리 방법

- 접수 1건 = 비밀 게시글 1건. 제목은 접수번호(`RAON-…`), 본문은 접수 내용(JSON)이다.
- 처리 상태는 게시글 **분류**로 관리한다: `NEW` → `CONTACTED` → `QUALIFIED` → `CLOSED`.
- 처리 메모는 게시글 **댓글**로 남긴다 (관리자만 읽기·쓰기 가능).
- 파기 요청이나 보관 기간 만료 시에는 게시글을 삭제한다. 삭제된 게시글은 일일 백업 보관 주기가 지나면 백업에서도 사라진다.

## 승인 대기 항목

아래 값은 운영·법적 결정이므로 코드가 만들지 않는다. 모두 승인되면 운영 `.env` 에 설정한다.

| `.env` 키 | 내용 | 결정 주체 |
| --- | --- | --- |
| `APP_URL` | 확정 운영 도메인의 `https://` 주소 (임시 터널 주소 금지) | 운영자 |
| — | TLS 인증서, HTTPS 강제, `TRUSTED_PROXIES` 신뢰 범위 | 운영자 |
| `RAON_CONSULTATION_CONSENT_VERSION` | 동의 문안 버전 식별자 (예: 시행일 기반) | 개인정보 책임자 |
| `RAON_CONSULTATION_PRIVACY_COPY` | 수집·이용 동의 문안 (아래 초안 참고) | 개인정보 책임자 |
| `RAON_CONSULTATION_PRIVACY_POLICY_URL` | 개인정보처리방침 `https://` 주소 | 개인정보 책임자 |
| `RAON_CONSULTATION_PRIVACY_CONTACT` | 개인정보 보호 담당 연락처 | 개인정보 책임자 |
| `RAON_CONSULTATION_RETENTION_NOTICE` | 보관 기간·파기 안내 문구 | 개인정보 책임자 |
| `RAON_CONSULTATION_INTAKE_ENABLED` | 위 항목 전부 승인 뒤 마지막에 `true` | 운영자 |

## 수집·이용 동의 문안 초안

`[승인 필요: …]` 자리는 확인된 값으로만 채운다. 채우지 않은 채 공개하지 않는다.

> [승인 필요: 회사명]은 구축 상담 요청에 답변하기 위해 아래와 같이 개인정보를 수집·이용합니다.
>
> - 수집 항목: (필수) 이름, 이메일, 상담 내용 / (선택) 회사명, 연락처, 관심 서비스
> - 이용 목적: 상담 요청 확인, 답변 및 후속 안내
> - 보관 기간: [승인 필요: 보관 기간] 보관 후 지체 없이 파기하며, 요청 시 즉시 파기합니다.
> - 동의를 거부할 수 있으며, 거부 시 온라인 상담 접수가 제한됩니다.
> - 문의: [승인 필요: 개인정보 보호 담당 연락처]

## 활성화 순서

1. 운영 도메인에 TLS를 적용하고 `APP_URL` 을 `https://` 로 바꾼다.
2. 승인된 값을 `.env` 에 넣되 `RAON_CONSULTATION_INTAKE_ENABLED` 는 아직 `false` 로 둔다.
3. `php artisan config:cache` 후 `php artisan raonslab-product:consultation-readiness` 에서 `enabled_flag` 만 남는지 확인한다 (설정값은 출력되지 않는다).
4. `php artisan raonslab-product:consultation-rehearsal` 가 전부 PASS 인지 확인한다.
5. `RAON_CONSULTATION_INTAKE_ENABLED=true` → `config:cache` → 외부 HTTPS 주소에서 config `intake_enabled=true`, 폼 노출, HTTP 주소에서는 여전히 닫힘을 확인한다.
6. 문제 시 `RAON_CONSULTATION_INTAKE_ENABLED=false` 와 `config:cache` 로 즉시 닫는다.
