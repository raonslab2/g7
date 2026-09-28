# 0.1.5 fixed integration 사전 전달 검증 기록

## 입력과 부모 관계

- current main baseline: `75096bc270a6d7b02b6f4569c0854358ac86dcea`
- initial provider implementation: `895c2c2a8154b365ac6e29525a9100f008f4ef33`
- AI Workspace 0.1.5 candidate: `6ebfd4d81684b121a3b277b6813ed43e2985d3f8`
- consultation/product 0.2.2 candidate base: `ba5b87222e005ed560a42db62ba4048a0c352fa7`
- fixed provider commit: `f122f96234f4ea46beb277bf75df5a4f58c80a4c`, parent `895c2c2a8154b365ac6e29525a9100f008f4ef33`
- fixed patch cherry-pick: `a5912c264180285813de8ef0ab27c0321eb09910`
- integration owner: Request `req_7130b85d2da44007867b9b9322c97140`

`ba5b8722`에는 이미 `895c2c2`의 통합 결과와 0.1.5 metadata가 들어 있다. 따라서 이 후보에는
`895c2c2..f122f962` parent diff만 단일 cherry-pick으로 적용했으며 초기 구현을 다시 적용하지 않았다.

## 독립 QA findings와 보완

- upstream request/capability/event 객체가 브라우저 API 경계를 그대로 통과할 수 있던 경계를 고객용 allowlist DTO로 고정했다.
- SSE는 원시 chunk를 전달하지 않고 sequence, 허용된 event type, 시각과 상태만 포함하는 frame으로 재구성한다.
- detail, messages, resume와 목록이 같은 안전한 request 계약을 반환하며 소유권 선확인은 유지한다.
- 불확실한 submit/follow-up 재시도는 같은 사용자 의도에 같은 Idempotency-Key를 사용하고, 입력 변경이나 성공 뒤에만 회전한다.
- 새 turn이 비종료 상태일 때 이전 결과를 현재 결과로 표시하지 않는다.
- 상태 갱신 중 disclosure와 키보드 focus를 보존하고 live region, 터치 영역, reduced-motion 대안을 보강했다.

## 변경 경계와 보존

- fixed patch: `raonslab-ai-workspace`의 customer boundary, SSE, 사용자 UX, 관련 tests와 dist 13개 경로
- integration metadata: 0.1.5 CHANGELOG와 이 검증 기록
- version: module/composer/package/package-lock/components 모두 기존 `0.1.5` 유지
- migration 추가·변경 없음
- `raonslab-product` consultation/visual tree는 exact base `ba5b8722`와 동일
- G7 core, AI_GCS/AgentOpt source와 runtime은 변경하지 않음

## 검증

| 항목 | 상태 | 근거 |
|---|---|---|
| Fixed source/dist tree identity | PASS | integrated `resources/src/tests/dist`가 exact `f122f962`와 동일 |
| Provider Vitest | REUSED PASS | exact fixed source tree, 17 tests PASS |
| Provider PHP contract | REUSED PASS | exact fixed source tree, 10 tests PASS |
| Version consistency | PASS | module/composer/package/package-lock root/components 모두 0.1.5 |
| JSON and diff checks | PASS | JSON parse, Composer validate, `git diff --check`, fixed patch inventory |
| Production asset build | SKIPPED_NOT_REQUIRED | source와 committed dist가 exact `f122f962`로 일치하여 재빌드 조건 없음 |
| Product consultation/visual preservation | PASS | `ba5b8722` 대비 `raonslab-product` diff 없음 |
| Broad/full/browser/runtime | SKIPPED | fixed integration 준비 범위 밖 |
| Independent fixed-candidate QA | PENDING | 승인 전 main push와 runtime deploy 금지 |

- module manifest SHA-256: `770a37f4639d68dee1b05efabd77aa9834c8d52efa20a86bbbb9cf19f424f42f`
- components SHA-256: `c0d62c2fc429009774ac01a2e86d7b4d1c0ad216ee709c2c376fd4ffbd099f27`
- committed JS SHA-256: `bd0cb991d3ca2133b4f31d4de89caa3064825d26dae4c39c7967d4cbea80cc16`
- committed CSS SHA-256: `470d37a57294ffe8f221a93f76ea8a96d559bf768c7f3992ca40a70b7ee73425`

## 전달 게이트

독립 fixed-candidate QA가 이 통합 후보를 승인하기 전에는 main fast-forward, `module:update`,
runtime 배포 또는 서비스 reload/restart를 수행하지 않는다.
