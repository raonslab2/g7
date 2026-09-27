# 변경 이력

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
