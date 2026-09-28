# 변경 이력

## 0.1.5 - 2026-09-28

- 장시간 요청에서 저장된 이벤트 순번을 기준으로 재연결하고 중복 이벤트를 제거해 진행 이력을 보존
- 같은 요청의 후속 메시지와 중단 상태의 재개를 구분하고 입력 초안·포커스를 상태 갱신 중에도 유지
- 구조화된 사용자 질문과 허용된 결과 요약만 표시하고 Provider 내부 이벤트·오류 상세·비밀·경로를 사용자 화면에서 차단
- request/list/detail/messages/resume/capabilities 응답을 고객용 allowlist DTO로 축소하고 SSE를 허용된 이벤트 필드로 재구성해 원시 Provider payload가 브라우저 경계를 넘지 않도록 보강
- 네트워크·서버 오류 뒤 같은 사용자 의도에는 Idempotency-Key를 재사용하고 입력 변경 또는 성공 뒤에만 키를 교체
- 새 turn 실행 중 이전 turn의 결과를 현재 결과로 표시하지 않고, 상태 갱신 중 disclosure·키보드 focus 상태를 보존
- 상태 변화 live region, 최소 터치 영역과 reduced-motion 대안을 추가
- 네트워크·권한·서비스 오류를 안정된 사용자 메시지와 재시도 동작으로 구분
- 인증·사용 권한 미들웨어, 요청 소유권 선확인, AI_GCS V2 HTTP Adapter 경계는 유지하며 migration은 추가하지 않음
- 공개 Service/Contract/Repository/Route 시그니처 변경과 소비 확장이 없어 다른 확장의 최소 버전 제약은 유지

## 0.1.4 - 2026-09-28

- 평문 HTTP 검수 주소에서 `crypto.randomUUID()`가 없는 브라우저를 위한 idempotency UUID fallback 추가

## 0.1.3 - 2026-09-28

- G7 7.0.11 사용자 라우트 규약에 맞춰 AI 작업공간 라우트를 `resources/routes/user.json`으로 이동
- `/ai`와 `/ai/requests/:request_id`가 SPA catch-all에서 실제 등록 라우트로 판정되도록 회귀 테스트 추가

## 0.1.2 - 2026-09-28

- production build의 source map을 opt-in으로 전환해 배포 자산 404 제거

## 0.1.1 - 2026-09-28

- 페이지 진입 시 persisted event를 처음부터 재생하고 reconnect cursor로 연속성 유지
- 상태 갱신 중 이벤트 이력이 사라지지 않도록 화면 렌더링 보강
- Provider 응답의 사용자 결과 텍스트만 우선 표시
- 설정 캐시 환경에서도 동작하는 server-side secret file 지원

## 0.1.0 - 2026-09-28

- AI_GCS V2 Request API 서버 측 Adapter 추가
- G7 사용자 UUID 기반 identity mapping과 요청 소유권 저장 추가
- 요청 제출, 목록, 상세, cursor 기반 SSE 재연결, 결과, 후속 지시, resume 추가
- 모바일 우선 사용자 AI 작업공간 화면 추가
