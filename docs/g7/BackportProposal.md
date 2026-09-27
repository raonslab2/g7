# GNUBOARD7 Backport 제안

이 문서는 Phase 6 gate다. 아래 항목은 제안일 뿐이며 이번 작업에서 `ai_gcs_v2`, `agentOpt_v2`, `agent-tools` production source/runtime에는 반영하지 않는다.

## BP-01 File-backed service secret resolver

| 항목 | 내용 |
|---|---|
| source feature | config-cache-safe server secret file fallback |
| G7 위치 | `raonslab-ai-workspace/config/ai.php`, `AiGcsV2Adapter::client()` |
| usage evidence | cached config 상태에서 G7 capability/history HTTP 200 |
| solved problem | framework config cache 뒤 service credential 누락 |
| V2 대응 문제 | 향후 다른 web adapter의 secret 주입 일관성 |
| 판정 | PORT 후보 |
| 예상 repository/file | 별도 adapter SDK 또는 integration example; 미정 |
| API/DB/migration 영향 | 없음 |
| runtime 영향 | secret file read 1회/request 또는 cached resolver |
| rollback | env-only resolver로 되돌림 |
| regression risk | file permission/path 오구성 시 fail-closed 503 |
| benefit/cost | secret 비노출과 배포 안정성 / 작은 helper와 contract test |

## BP-02 External request ownership mapping

| 항목 | 내용 |
|---|---|
| source feature | client-local `(user, remote request_id)` mapping |
| G7 위치 | AI workspace migration/repository/service |
| usage evidence | submit/detail/follow-up/restart, 타 사용자 ownership gate |
| solved problem | AI DB 공유 없이 G7 사용자별 request 접근 제어 |
| V2 대응 문제 | 여러 product client에서 동일 ownership boundary 반복 가능 |
| 판정 | PORT 후보 |
| 예상 repository/file | 공통 adapter contract/example repository; core 미정 |
| API 영향 | 현재 Request API 불변 |
| DB 영향 | client product DB에만 mapping table |
| migration | 각 client에서 선택적 |
| runtime 영향 | detail/events 전 local ownership lookup |
| rollback | client module 제거와 mapping table rollback |
| regression risk | mapping 유실 시 remote request 조회 불가; 권한 우회보다 fail-closed 우선 |
| benefit/cost | DB 결합 제거와 audit identity / client별 migration 비용 |

## BP-03 Persisted SSE client utility

| 항목 | 내용 |
|---|---|
| source feature | cursor replay/reconnect/terminal stop/follow-up UI |
| G7 위치 | `raonslab-ai-workspace/resources/js/index.ts` |
| usage evidence | persisted sequence 1–51, reconnect, same request follow-up 완료 |
| solved problem | 장시간 요청의 refresh/reconnect 이벤트 누락 |
| V2 대응 문제 | 다른 web client가 같은 event contract를 반복 구현 |
| 판정 | PORT 후보 |
| 예상 repository/file | 향후 V2 web client package; 현재 미정 |
| API/DB/migration 영향 | 없음, 기존 cursor API 사용 |
| runtime 영향 | 최대 100개 client-side event history |
| rollback | 각 client의 기존 SSE consumer로 복귀 |
| regression risk | duplicate cursor, terminal race, reconnect storm |
| benefit/cost | 동일 UX와 contract 재사용 / frontend package test 비용 |

## ADAPT/KEEP/REJECT 요약

- ADAPT: module lifecycle preflight/rollback, permission catalog consistency check, source identity validation.
- KEEP: JSON layout, component registry, notification, admin IA, G7 plugin/template lifecycle.
- REJECT: 범용 hook bus, queue abstraction 이식, G7 orchestration/worker 계층 신설.

## Gate

예상 AI_GCS/AgentOpt 변경 파일은 승인 전 확정하지 않는다. API compatibility, DB migration, runtime rollout이 필요한 설계도 시작하지 않는다. Phase 6은 사용자가 각 후보를 명시적으로 승인한 별도 작업에서만 수행한다.

