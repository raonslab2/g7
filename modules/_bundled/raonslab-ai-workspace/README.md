# RAON AI 작업공간

G7 로그인 사용자가 AI_GCS V2의 canonical Request를 제출하고, 저장된 이벤트와 결과를 확인하며, 같은 요청에 후속 지시를 보낼 수 있게 하는 공식 Module입니다.

경계는 `G7 UI → AiWorkspaceService → AiGcsV2Adapter → AI_GCS V2 Request API`입니다. 이 모듈은 AI_GCS/AgentOpt의 DB, SQLite, 파일시스템, Provider 세션을 직접 읽지 않습니다.

## 런타임 설정

`.env.product.example`의 `G7_AIGCS_V2_*` 항목을 별도 G7 환경 파일에 설정합니다. `G7_AIGCS_V2_PROXY_TOKEN`은 서버 측에서만 사용되며 브라우저 응답이나 번들에 포함되지 않습니다.

## 개발 검증

```bash
php artisan module:build raonslab-ai-workspace --production
php artisan test modules/_bundled/raonslab-ai-workspace/tests
cd modules/_bundled/raonslab-ai-workspace && npm run test:run
```
