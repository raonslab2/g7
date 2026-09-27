# AI_GCS V2 Adapter

## 실제 경계

```text
G7 authenticated user
  -> RAON AI Workspace UI
  -> AiWorkspaceService
  -> AiGcsV2Adapter
  -> isolated AgentOpt V2 Request API 127.0.0.1:18771
  -> canonical Request
  -> CODEX / CODEX_1 native provider session
  -> persisted events/result
  -> Adapter ownership gate
  -> G7 UI
```

G7은 AgentOpt DB, SQLite 파일, request/event table, workspace, provider session 파일을 직접 읽지 않는다. 모듈 contract test가 금지된 storage access 문자열과 route auth middleware를 검사한다.

## G7 구현 위치

| 책임 | 위치 |
|---|---|
| application service | `modules/_bundled/raonslab-ai-workspace/src/Services/AiWorkspaceService.php` |
| outbound adapter | `modules/_bundled/raonslab-ai-workspace/src/Services/AiGcsV2Adapter.php` |
| ownership mapping | module `raonslab_ai_requests` migration/repository |
| API/SSE controller | module `src/Http/Controllers/Api` |
| UI/event presentation | module `resources/js` |
| server configuration | module `config/ai.php`, `.env.product.example` |

## API 범위

- `GET /api/v1/projects?details=true`
- `GET /api/v1/providers`
- `GET /api/v1/session`
- `POST /api/v1/requests`
- `GET /api/v1/requests`
- `GET /api/v1/requests/{id}`
- `GET /api/v1/requests/{id}/events?after={cursor}`
- `POST /api/v1/requests/{id}/messages`
- `POST /api/v1/requests/{id}/resume`

G7 route는 `auth:sanctum`과 module permission을 통과한 뒤 이 API를 호출한다. 현재 upstream contract에 없는 기능은 만들지 않는다.

## Identity와 secret

- service identity: 전용 proxy token
- audit identity: `X-GCS-Authenticated-User: gnuboard7:{user_uuid}`
- operator: `g7-adapter`
- project: 서버 고정 `GNUBOARD7`
- G7 ownership: `(user_id, user_uuid, request_id)` mapping
- secret: `/etc/g7-product/ai-gcs-v2-token`, browser/DB/Git에 미포함

Laravel config cache 이후에도 secret이 `env()` 호출에 의존하지 않도록 server-side secret file fallback을 사용한다. 다른 production credential은 재사용하지 않는다.

## 격리 AgentOpt V2

| 자원 | 값 |
|---|---|
| service | `g7-agentopt-v2.service` |
| listen | `127.0.0.1:18771` |
| DB | `/var/lib/g7-agentopt/data/agentopt-v2.sqlite3` |
| workspace | `/var/lib/g7-agentopt/workspaces` |
| attachment | `/var/lib/g7-agentopt/attachments` |
| runtime env | `/etc/g7-agentopt-v2/runtime.env` |
| project | `GNUBOARD7`, root `/home/mrdev/git/g7` |
| provider | `CODEX / CODEX_1` |
| Agent.Tools release | `agent-tools-2.1.3+g03a4f82dfa87` (최초 검증 binding) |

빈 user allowlist는 이 전용 runtime에서 trusted proxy identity를 허용한다는 현재 AgentOpt 계약이며, proxy secret은 browser에 전달되지 않는다. 다른 project worktree와 request worktree를 공유하지 않는다.

## SSE와 결과

새 화면은 cursor 0부터 persisted event를 재생하고, 같은 연결 루프에서 마지막 sequence를 유지하여 reconnect한다. 최근 100개 event를 화면에 유지한다. `request.state` payload에서는 허용된 state만 사용자 label로 변환한다. provider result object는 `text` 또는 `message`를 우선 표시하여 내부 turn payload를 기본 노출하지 않는다.

## 실제 closed loop

- request: `req_9f8edf18486a41ab870053ac629f9de8`
- submit: HTTP 202
- first state: `STARTING -> RUNNING -> COMPLETED`
- persisted event sample: sequence 1–51 연속
- follow-up: HTTP 202, 동일 request ID 유지
- follow-up state: `RUNNING -> COMPLETED`
- terminal result: 존재 확인
- G7/AgentOpt 재시작 뒤 G7 history에서 request 1건 재조회 성공

