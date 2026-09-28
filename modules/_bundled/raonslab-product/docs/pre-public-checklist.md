# 온라인 상담 공개 전 체크리스트

현재 공개 검수 환경은 HTTP이므로 접수는 `disabled`가 정답이다. 아래 항목을 모두 승인·검증하기 전에는
`RAON_CONSULTATION_INTAKE_ENABLED`를 `true`로 바꾸지 않는다.

- [ ] 운영 도메인 확정
- [ ] TLS 인증서와 HTTPS 강제, reverse proxy 신뢰 범위 검증
- [ ] 개인정보 수집·이용 문안과 버전 승인
- [ ] 개인정보처리방침 HTTPS URL 승인
- [ ] 보관 기간·파기 정책 및 화면 문구 승인
- [ ] 개인정보 담당 연락처 승인
- [ ] 상담 알림 수신 메일과 mail transport 승인·시험
- [ ] APP_KEY 기반 멱등성 HMAC, private board 백업·복구·접근 통제 점검
- [ ] rate limit, same-origin, 201/200/409/422/429/503 외부 경로 재검증
- [ ] private consultation board의 admin 역할·비밀글 권한 배정과 퇴직·변경 회수 절차 확인
- [ ] 실제 고객 데이터 없이 합성 데이터로 저장·조회·삭제·복구 훈련
- [ ] 상담 board가 공개 비활성·항상 비밀인지, 일반 게시판·검색·알림·AI 요청에 PII가 섞이지 않는지 재검증

승인된 대체 연락처가 없다면 화면에 전화번호나 이메일을 임의로 추가하지 않는다.
