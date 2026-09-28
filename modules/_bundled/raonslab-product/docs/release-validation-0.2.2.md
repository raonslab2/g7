# 0.2.2 사전 전달 검증 기록

## 입력과 부모 관계

- current main baseline: `75096bc270a6d7b02b6f4569c0854358ac86dcea`
- AI Workspace 0.1.5 integration candidate: `6ebfd4d81684b121a3b277b6813ed43e2985d3f8`
- consultation implementation: `9920be889ac3ee16f0fe1842615c6ca7a9898c50`
- consultation implementation parent: `cb515dd200a23d805708ed7eea87331491f4392f`
- rehearsal cherry-pick: `994efaed` (final candidate SHA는 준비 커밋 후 보고)
- integration owner: Request `req_7130b85d2da44007867b9b9322c97140`

상담 구현 커밋은 오래된 `cb515dd2` 기준이지만 커밋 자체의 17개 변경 경로와
`cb515dd2..6ebfd4d8` 변경 경로의 교집합은 없었다. 리허설은 `6ebfd4d8` 위에 단일 cherry-pick으로
적용했으며 최신 제품 홈 visual과 AI Workspace 0.1.5를 보존한다.

## 변경 경계

- `raonslab-product`: 상담 public/admin source, 관련 focused tests, admin layouts
- `raonslab-product`: 0.2.2 metadata, generated dist/components, CHANGELOG, API 및 release 문서
- generated API discovery index: `docs/backend/api/README.md`
- migration 추가·변경 없음
- G7 core source, `raonslab-product` home visual source, `raonslab-ai-workspace`, AI_GCS/AgentOpt source 변경 없음

## 후보 검증

| 항목 | 상태 | 근거 |
|---|---|---|
| Consultation focused PHP | PASS | 변경된 4개 feature/unit 파일: 46 tests / 323 assertions |
| Consultation focused frontend | PASS | `consultation.test.ts`: 35/35 |
| Production asset build | PASS | 0.2.2 통합 소스에서 공식 `module:build --production` 1회 |
| API reference | PASS | `api:docgen --scope=module:raonslab-product --env=testing`; 6 endpoints 정적 생성 후 계약 보강 |
| Broad/full/browser/runtime | SKIPPED | 리허설 범위 및 테스트 최적화 지시 |
| Independent consultation QA | PENDING | 승인 전 main push와 runtime deploy 금지 |

- module manifest SHA-256: `4ef59f40e6f7a2af6d9794f32c6dc0677e72ac0ce1eff78fb500554e462e23cb`
- generated components SHA-256: `6cf049441222ebf996ab08a5bd1e65ef2617b454271438117dc443589edfbddb`
- generated JS SHA-256: `46e9f99c16d18f53748ee83a87e95ada73934cf69b08b7b4b3cdc6bf6721529f`
- generated CSS SHA-256: `c71ce24e6a11d8842d2de2dced358cbb35dd2864b38802c57cec99dde7dcf476`

## 공개 활성화 게이트

현재 HTTP 검수 환경에서는 `intake_enabled=false`가 정답이다. 운영 도메인과 TLS·신뢰 프록시,
개인정보 문안/버전, 개인정보처리방침 HTTPS URL, 보관·파기 정책, 개인정보 담당 연락처,
알림 수신 메일 및 mail transport가 승인·검증되기 전에는 공개 접수를 활성화하지 않는다.
