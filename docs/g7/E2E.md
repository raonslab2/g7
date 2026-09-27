# GNUBOARD7 E2E 증거

검증일은 2026-09-28 KST다. 원본 screenshot/result는 Git에 넣지 않고 ignored `storage/app/evidence`에 보관한다. 아래 결과는 source, API response, DB state, service state, browser 실행 결과를 함께 판정한 것이다.

## Native journey

| Journey | 결과 | 증거 |
|---|---|---|
| J1 Landing → Register → Login | PASS | 실제 신규 user 생성, logout 후 재로그인 |
| J2 Profile update/persistence | PASS | nickname/country 저장 후 재조회 |
| J3 Board CRUD | PASS | community post create/detail/update/delete |
| J4 Attachment | PASS | text attachment HTTP 201, hash download HTTP 200 |
| J5 Permission/RBAC | PASS | user admin API 403, unauthenticated write 401 |
| J6 Notification | PASS | admin comment로 author notification 생성, read 처리 |
| J7 Search | PASS | unique post keyword 검색 결과 확인 |
| J8 Admin | PASS | admin API와 768px dashboard render |
| J9 Restart persistence | PASS | 전용 4개 service 재시작 후 user 3, board 3, AI request 1, HTTP 200 |
| J10 Mobile | PASS | 360/390/412px horizontal overflow 없음 |

제품 board `community`, `notice`, `questions`는 영속 데이터로 유지했다. E2E post/comment는 CRUD 완료 후 삭제했다.

## Browser render

Chromium이 다음 화면을 실제로 열고 full-page screenshot을 남겼다.

- home: 360, 390, 412, 1440
- login/register: 390
- boards/community/post: 390
- profile/notifications/search: 390
- AI request detail: 390
- admin dashboard: 768

최초 캡처의 console 404는 배포에서 제외된 source map을 가리키는 build 주석이었다. production sourcemap을 opt-in으로 바꾸고 재검증해 제거했다.

## AI Adapter journey

| Journey | 결과 |
|---|---|
| A1 authenticated submit | PASS, HTTP 202 |
| A2 G7 mapping persist | PASS, G7 table 1건 |
| A3 running/events | PASS, state 전이와 SSE event 확인 |
| A4 reconnect/cursor | PASS, sequence 1–51 연속 replay |
| A5 completion/result | PASS, terminal `COMPLETED`, result 존재 |
| A6 same-request follow-up | PASS, 동일 request ID |
| A7 provider failure UI | contract/presentation test PASS |
| A8 unavailable UI | adapter exception/notice path test PASS |
| A9 permission mismatch | ownership gate와 401/403 검증 PASS |
| A10 G7 restart/history | PASS, restart 후 request count 1 |

## Test 결과

- RAON product/adapter PHP: 5 tests, 12 assertions PASS
- AI workspace Vitest: 3 tests PASS
- production asset build: PASS
- Agent.Tools targeted project validation: 30 PASS
- Agent.Tools full regression: 159 PASS
- Agent.Tools G7 validation recipes: 13 PASS
- G7 AgentOpt binding: `agent-tools-2.1.3+ga07f40b3370e`
- external fixed ingress: `http://203.245.29.156:58770/` HTTP 200
- local health: `http://127.0.0.1:18770/` HTTP 200

## Backup/rollback evidence

- archive: `/var/backups/g7-product/g7-product-20260927T172026Z.tar.gz`
- archive SHA-256: PASS
- nested DB gzip integrity: PASS
- `.env`/`storage/app` payload listing: PASS
- recorded source SHA: `761ad01a41d0bbbe69e1c96ea768b52a40eae472`
- upstream merge-tree simulation against `upstream/main`: conflict marker 0

복구 rehearsal은 production DB를 덮어쓰지 않고 archive/dump/file/source metadata의 복원 가능성을 검증했다. 실제 overwrite restore는 irreversible operation이므로 수행하지 않았다.

## 알려진 항목

- P1: 고정 public `58770`은 HTTP다. 정식 공개 전 TLS ingress가 필요하다.
- P2: free HTTPS tunnel hostname은 재연결 시 변경된다.
- P2: `/api/user/profile` permission identifier가 설치 DB에 없었고 공식 호환 `/api/me`는 정상이다. core를 수정하지 않고 upstream 후보로 기록한다.
- P2: npm audit 15건은 lockfile 자동 변경 없이 별도 dependency update로 처리한다.
