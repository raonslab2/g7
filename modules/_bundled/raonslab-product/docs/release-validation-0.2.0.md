# 0.2.0 릴리스 검증 기록

## 입력과 범위

- baseline: `origin/main` `460d45a247fc5dbdf6194ea0d4a3fc445c33fb59`
- backend/admin: Request `req_a30d92279a0f4971a2f7035047edfbdb`, commit `3f4c1006c9f28230a84aaa52c24d08bdfc87078c`
- public homepage: Request `req_dc31fb01ab554bc0927c48f75301cda0`, commit `5de15afa65fddbaae20f0968767f3742ab34ccb7`
- integration owner: Request `req_7130b85d2da44007867b9b9322c97140`
- integration implementation SHA: `8d6d21610ec1178469fdc53059a669b7e3ea87b3`

변경 경계는 `modules/_bundled/raonslab-product`뿐이다. 코어 patch는 허용하지 않는다.

## 검증 상태

| 항목 | 상태 | 근거 |
|---|---|---|
| Backend module | PASS | 통합 후 20 tests / 119 assertions (초기 입력 19/113 포함) |
| Frontend Vitest | PASS | 39/39 |
| Production build | PASS | 0.2.0 source에서 공식 `module:build raonslab-product --production`; dist/components 재생성 |
| Candidate Chromium | PASS | 136 PASS; 배포 전 config 404는 4 SKIPPED, live SEO 2건은 배포 전 예상 미통과로 분리 기록 |
| 권한 관련 회귀 | PASS | `PermissionMiddlewareTest` 41 tests / 74 assertions |
| 설치 smoke | PASS | `composer test-smoke` 2 tests / 29 assertions |
| 대표 broad 회귀 | PARTIAL | 12건 중 10 PASS, 1 `SKIPPED_ENVIRONMENT`(GD 미설치로 image fixture 생성 불가), 1 `SKIPPED`(공용 admin login endpoint 선언) |
| 공개 download 단건 | PASS | 비로그인 공개 다운로드 1 test / 1 assertion |
| 기존 AI Adapter | PASS | frontend 5/5, backend 4 tests / 11 assertions |
| migration down/up | PASS | 보호된 testing DB에서 product migration rollback 후 재적용, 최종 batch 2/Ran 확인 |
| 정적 gate | PASS | JSON, PHP lint, `git diff --check`, module-only patch inventory, 정규식 secret scan, Composer validation |
| 상담 실제 public 접수 | SKIPPED | HTTP 공개 검수 환경이므로 의도적으로 disabled |
| restart persistence | UNVERIFIED | 실제 고객/production DB를 사용하지 않고 protected test DB까지만 검증 |

## 상담 시나리오 판정

| 시나리오 | 상태 | 근거 |
|---|---|---|
| 정상 생성과 저장 후 성공 표시 | PASS | backend 201 및 candidate browser |
| validation/동의/길이 제한 | PASS | backend FormRequest와 browser 시나리오 |
| 연속 클릭 | PASS | candidate browser 단일 요청 보장 |
| 동일 Idempotency-Key 재시도 | PASS | backend 200 replay 및 browser 시나리오 |
| key 충돌 | PASS | backend/browser 409 |
| DB 실패 | PASS | backend 503, 성공 오표시 없음 |
| 메일 미설정/실패 | PASS | 저장 성공과 메일 best-effort 경계 검증 |
| admin 조회/상세/메모/상태/이력 | PASS | backend feature tests |
| read/manage RBAC와 401/403 | PASS | read-only 조회 허용 및 mutation 403 추가 검증 |
| 실제 프로세스 재시작 후 persistence | UNVERIFIED | production 데이터 생성 금지; test DB 모델 재조회 범위만 PASS |

## 테스트 실행 최적화 기록

동일 source SHA, 명령, 환경의 기존 PASS는 재사용했다. broad/full 회귀는 release candidate 직전 1회만 수행했고,
GD가 없는 환경에서 `UploadedFile::fake()->image()`가 실패한 건은 PASS로 바꾸지 않고
`SKIPPED_ENVIRONMENT`로 기록했다. 테스트 자체나 관련 source가 바뀌지 않은 영역은 중복 실행하지 않았다.

main SHA, runtime module/asset identity, 실제 URL, rollback archive는 배포 후 완료 보고에 기록한다.
