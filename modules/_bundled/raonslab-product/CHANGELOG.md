# 변경 이력

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
