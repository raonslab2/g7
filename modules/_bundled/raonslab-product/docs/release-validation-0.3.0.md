# 0.3.0 RC 준비 검증 기록

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

이번 단계는 product 0.3.0 metadata/build RC 준비이며 main 전달·module update·runtime 배포가 아니다.
AI Workspace 파일은 포함하거나 수정하지 않는다.

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
| Broad / core / full / browser / runtime | SKIPPED | final RC 준비 범위 밖이며 실행하지 않음 |

## Q&A gate

이전 독립 read-only QA가 확인한 provenance 밖 답글 rollback, delete/restore 알림, topology와 동시 실행
방어 findings는 safety fix에 반영했고 final combined source의 focused regression은 PASS했다. Q&A blocker의
코드 수정·영향 회귀 gate는 PASS이며, fixed candidate에 대한 독립 QA와 delivery preflight 승인은 여전히
main delivery의 선행 조건이다. 이 RC에서는 Q&A 명령을 production에 실행하지 않는다.

## Asset identity

- module manifest SHA-256: `6bfc66881e75f880c4a6c7b33b99e40e13ea29930bd182484423a182bf107bf7`
- components SHA-256: `53fbe696e470b9ef60554927db9274168e68f39284cc5597d9925efe0bc3baf6`
- JS SHA-256: `c1a80064f3838958d3780dadd1929f5e87ee15bc1bbf2d82fb5daf1847a7d981`
- CSS SHA-256: `b492a71c96928667e5851310f79d9f5a7c648ad402d8894f8557869e2da19ce3`

## 공개 상담 gate

HTTP 검수 환경에서는 접수를 열지 않는다. 운영 도메인·TLS와 trusted proxy, 개인정보 문안/버전,
정책 HTTPS URL, 보관·파기 정책, 개인정보 담당 연락처, private board 접근 통제와 백업·복구가 승인되고
검증되기 전까지 `intake_enabled=false`를 유지한다.
