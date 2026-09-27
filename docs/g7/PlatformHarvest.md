# Platform Harvest

G7에서 실제 설치·제품화·브라우저/AI E2E에 사용한 구조만 평가했다. 이 문서는 후보 판정이며 AI_GCS V2/AgentOpt V2 변경 승인이 아니다.

| 후보 | G7 실제 증거 | 판정 | 이유 |
|---|---|---|---|
| JSON/Layout engine | home, auth, board, admin, AI layout 실제 render | KEEP | React/Laravel template 계약에 강하게 결합됨 |
| Component registry | product/AI layout component 조립 | KEEP | G7 화면 편집 문제를 해결하나 provider runtime 문제와 다름 |
| Module lifecycle | RAON 2개 module install/update/permission/migration | ADAPT | 원자적 stage/backup 개념은 유용하지만 PHP package 복사는 이식 불가 |
| Plugin/template lifecycle | 공식 template/plugin 설치와 dependency gate | KEEP | G7 extension distribution 문제 |
| Hook/Event mechanism | board validation/search extension 사용 확인 | REJECT | V2에 일반 hook bus를 넣으면 계약과 디버깅이 복잡해짐 |
| Auth/RBAC pattern | user/admin 403, board role permission E2E | ADAPT | 명시적 permission catalog 검증은 유용, G7 table 모델은 이식하지 않음 |
| Notification abstraction | comment → author notification → read E2E | KEEP | community product 기능이며 orchestration event와 의미가 다름 |
| Queue abstraction | DB queue worker와 notification persistence | REJECT | AgentOpt의 현재 execution/event 모델을 대체할 이유 없음 |
| Update/rollback | module 0.1.0→0.1.1, backup integrity, merge simulation | ADAPT | preflight/evidence 원칙만 적용 가능 |
| Configuration management | config cache에서 secret file fallback 실증 | PORT | server adapter용 file-backed secret resolver 패턴은 framework 중립적 가치가 있음 |
| External request ownership mapping | G7 user UUID↔request ID, 타 사용자 차단 | PORT | DB 공유 없이 multi-tenant client가 request를 소유하는 문제를 실제 해결 |
| Persisted SSE replay UI | sequence 1–51, reconnect, 동일 request follow-up | PORT | 다른 V2 client UI에도 같은 cursor/result UX가 반복됨 |
| Source identity validation | exact SHA/worktree/Agent.Tools release binding | ADAPT | 이미 V2에 존재하며 G7 pack recipe로 확장하면 충분 |
| Admin information architecture | 768px dashboard render | KEEP | 제품 운영 UX이며 AgentOpt core 책임이 아님 |

## PORT 후보의 제한

### File-backed service secret resolver

G7에서는 Laravel config cache가 module의 runtime `env()` 값을 기본값으로 고정하는 문제를 해결했다. 이식 시 공통 orchestration 계층이 아니라 server-side adapter library/helper 범위만 검토한다.

### External request ownership mapping

G7 DB에는 remote request의 최소 식별자와 G7 owner만 저장한다. remote event/result는 복제하지 않는다. 여러 client product에 반복될 때만 adapter SDK/contract sample로 일반화하며 AgentOpt canonical Request schema는 바꾸지 않는다.

### Persisted SSE replay UI

cursor 0 replay, reconnect cursor continuation, terminal stop, same-request follow-up가 실제 browser flow에서 가치가 확인됐다. 공유 대상은 frontend client utility이며 새로운 progress 계산이나 recovery state machine이 아니다.

## 결론

V2 core를 단순하게 유지한다. lifecycle/hook/layout을 통째로 이식하지 않고, PORT 3건도 별도 승인 뒤 작은 adapter/client helper로만 검토한다.

