# 변경 이력

## [0.2.3] - 2026-09-28

### Changed

- MOBILE_STOCK 사례를 행 번호와 상태 태그가 있는 요청 로그 화면으로, RAON Hub 사례를 기반 버전 표지와 네 구간 상태 스트립이 붙은 근거 레일로 표시해 두 사례의 문제·구현·검증·한계를 한눈에 구분할 수 있도록 개선
- 서비스 단계 노드를 실증·구축·운영 상태에 따라 다르게 표시하고 산출물에 체크 표지를 추가
- 도입 절차 각 단계에 아이콘과 순번 표지를 붙이고, 검증 단계에 통과·실패·미검증 분기 표지를 추가

### Compatibility

- 새 문구·번역 키·수치 없이 기존 사실 문구만 사용하며, 장식 요소는 보조기술에서 숨김
- 상담 backend/admin·공개 접수 fail-closed 판정, SEO hook, G7/AI route 계약은 0.2.2와 동일
- 공개 Service/Contract/Repository/Model/Route 변경이 없는 visual-only 변경이라 소비 모듈 최소 버전 제약은 유지

## [0.2.2] - 2026-09-28

### Changed

- 공개 상담 config와 접수 판정에 실제 요청의 HTTPS 여부, 공개 사이트 HTTPS URL, 개인정보처리방침 HTTPS URL, 안전한 개인정보 연락처 검사를 추가
- 접수 닫힘(`503`, `intake_disabled`)과 저장 여부가 불확실한 일시 장애(`500`, `temporary_failure`)를 구분하고, 불확실한 실패 뒤 입력과 Idempotency-Key를 보존
- 관리자 목록 응답에 페이지 메타와 서버 판정 `abilities.can_manage`를 추가하고, 상세 응답에 순차 전이를 위한 `next_status`를 추가
- 관리자 목록·상세 화면에 로딩, 오류, 빈 결과, 범위 밖 페이지, read-only, 전송 중 상태를 명시

### Fixed

- 알림 또는 커밋 이후 재조회 실패가 이미 저장된 상담을 실패 응답으로 바꾸지 않도록 저장과 후처리 경계를 분리
- 필터·페이지 이동 시 목록 query를 보존하고 키보드로 상세 화면을 열 수 있도록 관리자 화면 계약 보강

### Security

- 신뢰하지 않은 forwarded scheme, HTTP·상대·스크립트성·userinfo 포함 URL로 공개 접수가 열리지 않도록 fail-closed 강화
- 저장 장애 로그에는 PII·예외 메시지·trace 대신 incident ID와 예외 클래스만 기록

### Compatibility

- 기존 상담 route와 migration은 유지하며 새 migration은 없음
- 공개 API 응답에 오류 사유, pagination, abilities, next status를 추가한 하위 호환 보강으로 소비 확장 최소 버전 제약 변경 없음

## [0.2.1] - 2026-09-28

### Changed

- hero 업무 흐름을 입력·실행·검증·운영 결과 노드와 상태 레인, 실패 되돌림 루프로 시각화
- 자체 구현 사례를 문제·구현·검증·한계가 연결된 근거 레일로 재구성
- 서비스 단계와 도입 절차를 반응형 연결 도식으로 정리하고 카드 반복을 줄여 정보 계층 개선
- 360/390/412px 및 desktop에서 같은 흐름 관계를 유지하도록 반응형 스타일과 구조 계약 보강

### Compatibility

- 상담 backend/admin, SEO hook, public fail-closed 설정과 기존 G7/AI route 계약은 변경하지 않음
- 공개 Service/Contract/Repository/Model/Route 변경이 없는 visual-only release라 소비 모듈 최소 버전 제약은 유지

## [0.2.0] - 2026-09-28

### Added

- RAON Agent Factory 사업 홈에 세 가지 상품, 자체 구현 사례, 도입 절차, 기술·검증 원칙, 구축 상담 진입점을 추가
- 동일 출처와 멱등성 키를 요구하는 상담 접수 API 및 개인정보 암호화 저장 추가
- 상담 목록·상세·내부 메모·순차 상태 변경 이력과 read/manage 관리자 권한 추가

### Changed

- 온라인 개인정보 접수는 동의 문안, 정책 URL, 보관 안내, 개인정보 담당 정보가 모두 승인된 경우에만 열리도록 fail-closed 기본값 적용
- 모듈 SEO 설정을 통해 제품 CSS를 봇 렌더에도 연결하고 홈 title·description을 화면 수명주기에 맞게 적용
- `raonslab-ai-workspace`가 소비하던 기존 제품 모듈 표면은 변경하지 않은 additive 기능이므로 해당 모듈의 최소 의존 버전은 유지

### Fixed

- 제품 홈을 대체하는 layout에 module-owned SEO title/description을 연결해 봇 렌더도 빈 title로 남지 않도록 수정

### Security

- 상담 개인정보를 일반 게시판·검색·알림·AI 흐름과 분리하고, 저장 성공 전 성공 응답을 금지

## 0.1.2 - 2026-09-28

- production build의 source map을 opt-in으로 전환해 배포 자산 404 제거

## 0.1.1 - 2026-09-28

- 로그인 사용자의 AI 작업공간 진입점 추가
- 제품 화면에서 upstream attribution과 사용하지 않는 상거래 진입점 정리

## 0.1.0 - 2026-09-28

- RAON Hub 브랜드 홈 Layout Extension 추가
- 사용자 선택을 보존하는 다크 우선 기본값 추가
- 모바일 360/390/412px 대응 제품 스타일 추가
