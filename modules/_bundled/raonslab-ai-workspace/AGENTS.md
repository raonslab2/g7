# RAON AI 작업공간 모듈 지침

- AI 연동은 `AiGcsV2Adapter`의 공식 HTTP API 경계만 사용한다.
- AI_GCS/AgentOpt DB, SQLite, 파일시스템, Provider 세션을 직접 읽지 않는다.
- 브라우저에 service credential을 내리지 않는다. 인증 사용자는 서버에서 G7 UUID로 매핑한다.
- 모든 request detail/event/follow-up 접근은 G7 소유권 테이블을 먼저 확인한다.
- 서버가 제공하지 않은 진척률, ETA, 상태를 생성하지 않는다.
- Provider 내부 명령이나 원시 event payload는 일반 화면에 노출하지 않는다.
- 변경 시 PHP contract/adapter 테스트와 frontend unit/build를 함께 실행한다.
