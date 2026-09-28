# 0.3.0 RC 및 delivery 검증 기록

## 입력과 범위

- exact base: `d8b86787579d8cf581953535e241954fc2b61a52`
- visual pass 2: `d9ed88c2`
- 정보·정책 화면 7종: `0c5107d1`
- Q&A 콘텐츠 명령: `55296f80`
- Q&A safety fix 원본: `4ea924edbb3952b072e040c18ce1f1f21111f719` (parent `be8430c2bc5adfa64a0af4dfa78789eae3954d2e`)
- final RC exact base: `f755a59f1c0e439b2808ace00ef2ec4e17b5e4f2`
- Q&A safety fix 통합 commit: `301f10d3328bab68dcc2c30af461354b05af9bf2`
- private board 상담: `2fa51a32`, lifecycle 보강 `d8b86787`
- integration owner: Request `req_7130b85d2da44007867b9b9322c97140`

RC `e1aabd12209a103a3991450524585cc9c1790eaa`까지 product 0.3.0 metadata/build/Q&A safety를
통합한 뒤 독립 QA와 delivery preflight 승인을 받아 main 및 G7 product runtime에 전달했다.
AI Workspace 파일과 AI_GCS/AgentOpt/queue/scheduler는 포함·수정·운영하지 않았다.

## 포함 기능

- 홈 visual pass 2의 서비스·사례·절차 미니 화면과 근거 레일
- 서비스·사례·원칙 3개 정보 화면과 개인정보·커뮤니티·AI 작업공간·오픈소스 4개 정책 화면
- 기존 `questions` board에 합성 질문 8개와 depth-1 답변 8개를 적용하는 provenance 기반 명령
- 신규 상담을 G7 공식 `PostService`로 공개 비활성·항상 비밀·관리자 전용 board에 저장하는 경로
- 0.2.1 설치에서 상담 board를 준비하는 `Upgrade_0_2_2`

## 현재 검증 상태

| 항목 | 상태 | 근거 |
| --- | --- | --- |
| 0.3.0 metadata/lock/components | PASS | module/composer/package/package-lock/components 일치, composer lock validate PASS |
| Official production build / assets | REUSED | Q&A fix가 PHP/JSON-only이므로 f755에서 정확히 1회 만든 production asset의 blob/hash identity를 보존하고 재빌드하지 않음 |
| Upgrade discovery/range | PASS | 실제 `Module::upgrades()`와 `ModuleManager` 0.2.1→0.3.0 range가 0.2.2를 선택하고 실제 DataMigration 실행; provisioner만 no-DB fake로 격리 |
| 기존 migration immutable | PASS | origin/main과 SHA-256 동일: `8a1f8968db5218343b93247cd1185f14df54c49d3b48a2370f72b8373b1b3a09` |
| AI Workspace diff | PASS | exact base 대비 product 경로만 수정 |
| HTTP consultation fail-closed | PASS | `http://127.0.0.1:18770` config 200, `intake_enabled=false` |
| Consultation focused regression | PASS | 계약 범위 12 tests + product layer 3 tests = 15/15, 102 assertions; final combined source에서 정확히 1회 |
| Q&A focused regression | PASS | 11/11 tests, 220 assertions; final combined source에서 정확히 1회 |
| Product module Vitest | PASS | 5 files, 92/92 tests; final combined source에서 정확히 1회 |
| PHP lint / JSON / diff / version | PASS | 변경 PHP 4개 syntax, fixture JSON, `git diff --check`, 0.3.0 metadata/locks/components 일치 |
| Listener / upgrade selection probe | PASS | consultation search listener + Q&A notification suppression + Q&A command 보존, 실제 0.2.1→0.3.0 range에서 Upgrade_0_2_2 선택·실행(no-DB fake) |
| Independent fixed-candidate QA | PASS | source tree `301f10d3328bab68dcc2c30af461354b05af9bf2`; `e1aabd12`는 문서-only descendant |
| Delivery preflight | PASS | exact RC `e1aabd12209a103a3991450524585cc9c1790eaa` 전달 승인 |
| Post-deploy critical smoke | PASS | 단일 실제 Chromium 실행 46/46; local/external URL, 메뉴, overflow, 기존 route, FAQ, private 상담 경계, `/ai` auth 확인 |
| Broad / core / full browser | SKIPPED | post-deploy에서는 승인된 critical smoke만 실행 |

## Q&A gate

이전 독립 read-only QA가 확인한 provenance 밖 답글 rollback, delete/restore 알림, topology와 동시 실행
방어 findings는 safety fix에 반영했다. final combined source의 focused regression, fixed-candidate 독립 QA,
delivery preflight가 모두 PASS했으며 아래의 직렬 dry-run/apply/idempotent re-apply 결과로 production 적용을
검증했다.

## Asset identity

- module manifest SHA-256: `6bfc66881e75f880c4a6c7b33b99e40e13ea29930bd182484423a182bf107bf7`
- components SHA-256: `53fbe696e470b9ef60554927db9274168e68f39284cc5597d9925efe0bc3baf6`
- JS SHA-256: `c1a80064f3838958d3780dadd1929f5e87ee15bc1bbf2d82fb5daf1847a7d981`
- CSS SHA-256: `b492a71c96928667e5851310f79d9f5a7c648ad402d8894f8557869e2da19ce3`

bundled와 active module의 위 네 파일은 byte identity가 일치한다. Q&A fix는 PHP/JSON-only라 delivery에서
production build를 반복하지 않았다.

## Backup과 복구 리허설

- 변경 전 source: `75096bc270a6d7b02b6f4569c0854358ac86dcea`
- 공식 oneshot: `g7-product-backup.service`, result `success`, exit `0`
- archive: `/var/backups/g7-product/g7-product-20260928T085009Z.tar.gz`
- archive SHA-256: `5b813c7590b4e787c612da040279dc4cfa5a6a3f67d2103a7e39c86e5991314e`
- inner `SHA256SUMS`: `database.sql.gz`, `migration-status.txt`, `persistent-files.tar.gz`, `source-sha.txt` 모두 PASS
- 격리 복구 DB: `g7_product_restore_req_7130b85d_20260928T085420`; base tables 111, legacy 상담 0행
- 복구 시 `questions`: board 3, active, 전체 1/active 0/soft-deleted 1/root 1/reply 0; 기존 ID 2
- 임시 DB drop 및 `/tmp/g7-product-restore.*` 제거 후 잔여 DB/디렉터리 0

운영 `g7_product` DB 위에는 restore하지 않았다.

## Main과 module lifecycle

- 이전 main/rollback source: `75096bc270a6d7b02b6f4569c0854358ac86dcea`
- delivery RC: `e1aabd12209a103a3991450524585cc9c1790eaa`
- origin/main fast-forward 후 `/home/mrdev/git/g7`을 `pull --ff-only`로 동기화
- `mrdev`로 `/usr/bin/php8.3 artisan module:update raonslab-product --force` 실행
- lifecycle 결과: active `0.2.1 → 0.3.0`, `Upgrade_0_2_2` 실행
- consultation board: ID 78, slug `raon-consultations`, inactive, `secret_mode=always`, file upload disabled
- legacy consultation 0행, local/external config 모두 `intake_enabled=false`
- 신규 route와 asset이 즉시 fresh하여 g7-product-fpm/web reload 불필요; 두 서비스 active

## Q&A production 적용

- dry-run: `create=16 update=0 restore=0 unchanged=0 duplicates=0`
- 첫 apply: `created=16 updated=0 restored=0 unchanged=0 duplicates=0`
- 두 번째 apply: `created=0 updated=0 restored=0 unchanged=16 duplicates=0`
- question/answer IDs: `74/75`, `76/77`, `78/79`, `80/81`, `82/83`, `84/85`, `86/87`, `88/89`
- 최종: provenance 16행, root 8, depth-1 reply 8, 모두 기존 `RAON Hub 관리자` ID 1 소유
- 기존 unrelated soft-deleted post는 ID 2 한 건 그대로 유지
- notification baseline/final: `g7_notifications=5`, `g7_notification_logs=10`; provenance 적용 중 신규 알림 0

Rollback이 필요하면 먼저 `/usr/bin/php8.3 artisan raonslab-product:qa-content --rollback --dry-run`으로
범위를 확인한 뒤 `--rollback --force`를 사용한다. 검증이 PASS했으므로 rollback은 실행하지 않았다.

## Post-deploy critical smoke

실제 smoke는 한 번 실행해 46/46 PASS했다. 사전 harness 호출 한 번은 Playwright CommonJS import 오류로
Chromium 시작 전 종료되어 검사를 실행하지 않았고, import 수정 뒤 아래 범위를 단일 실행했다.

- local `127.0.0.1:18770`와 external `203.245.29.156:58770`: 홈 + 정보/정책 7개 direct URL 모두 200
- 360/390/412 홈 horizontal overflow 0, title/meta 연결, PII input 0
- 390 touch 메뉴 open/close, desktop keyboard Enter/ArrowDown/Escape와 focus 복귀
- login/register/community/Q&A list/detail/search 200, FAQ marker와 정확한 질문 8 + 답변 8 노출
- consultation board metadata 404, list/detail 401, search 0건
- `/ai` guest는 `/login?redirect=%2Fai`로 이동
- external 390 render/overflow/PII input 0

## 공개 상담 gate

HTTP 검수 환경에서는 접수를 열지 않는다. 운영 도메인·TLS와 trusted proxy, 개인정보 문안/버전,
정책 HTTPS URL, 보관·파기 정책, 개인정보 담당 연락처, private board 접근 통제와 백업·복구가 승인되고
검증되기 전까지 `intake_enabled=false`를 유지한다.
