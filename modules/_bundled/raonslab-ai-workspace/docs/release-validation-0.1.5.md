# 0.1.5 사전 전달 검증 기록

## 입력과 변경 경계

- baseline: `origin/main` `75096bc270a6d7b02b6f4569c0854358ac86dcea`
- provider implementation: Request `req_7a07c03f17544c37a6b56ac683f6cd29`, commit `895c2c2a8154b365ac6e29525a9100f008f4ef33`
- independent QA: Request `req_b7230833280548bf87f8962eab4205aa` (결과 대기 중)
- integration preparation: Request `req_7130b85d2da44007867b9b9322c97140`

변경은 `modules/_bundled/raonslab-ai-workspace`의 고객용 작업 화면, 표현·보안 경계 테스트,
생성 자산 및 0.1.5 metadata/changelog에 한정한다. `raonslab-product`, G7 core,
AI_GCS V2와 AgentOpt 소스 및 runtime은 변경하지 않는다. migration 추가는 없다.

## 검토 계약

- API는 `auth:sanctum`과 `permission:user,raonslab-ai-workspace.requests.use`를 유지한다.
- detail, events, messages, resume는 외부 Adapter 호출 전에 G7 소유권을 확인한다.
- 후속 지시는 같은 request의 `/messages`, 중단 복구는 같은 request의 `/resume` 계약을 사용한다.
- AI 실행은 서버 소유 자격 증명을 사용하는 AI_GCS V2 HTTP Adapter로만 수행한다.
- AgentOpt DB·request 파일·Provider session에 직접 접근하지 않는다.
- upstream 오류 상세와 원시 이벤트 payload는 사용자 화면에 전달하지 않는다.

## 후보 검증

| 항목 | 상태 | 근거 |
|---|---|---|
| Provider 구현 evidence | REUSED | exact `895c2c2`; 독립 QA 결과는 아직 확정 전 |
| Integration focused frontend | PASS | 변경된 `index.test.ts`, `presentation.test.ts`: 12/12 |
| Adapter/ownership contract | PASS | `AiAdapterContractTest.php`: 6 tests / 22 assertions |
| Production asset build | PASS | 0.1.5 integrated source에서 공식 `module:build --production` 1회 |
| Generated component metadata | PASS | `components.json` 0.1.5, component 0개 |
| Broad/full regression | SKIPPED | 후보 준비 단계 최적화 지시에 따라 실행하지 않음 |
| Runtime browser/deploy | SKIPPED | 독립 QA 승인 전 production delivery 금지 |

- generated JS SHA-256: `177ebc38951306bc361ead440f9e1858c340fe1c06a5859e660f98a93d4f0510`
- generated CSS SHA-256: `9374f8be2d80dd5badfd3778977ece979d994c3c25a5c1f2c95b5c10b9017ef8`

## 전달 게이트

이 문서는 integration candidate 준비 기록이다. 독립 QA가 exact provider commit 검토 결과를
전달하기 전에는 main fast-forward, `module:update`, runtime 배포, 서비스 reload/restart를 수행하지 않는다.
