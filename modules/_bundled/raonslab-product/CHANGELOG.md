# 변경 이력

## [0.4.0] - 2026-09-28

### Changed

- 정보·정책 문서 7종의 제목·본문·발행·SEO·수정시각·version 원본을 `sirsoft-page >=1.1.2`로 전환
- 승인된 외부 Page JSON을 받아 누락 slug만 공식 `PageService`로 생성하고 기존 관리자 편집본은 항상 보존하는 재현 가능 bootstrap 명령 추가
- product navigation과 footer를 canonical `/page/{slug}`로 연결하고 native `page/show` 확장 지점에서 RAON breadcrumb·side navigation·dark neutral presentation 유지
- `/info/*`, `/policy/*` 및 locale prefix 호환 URL은 query string을 보존하는 301로 canonical Page에 연결

### Removed

- product 소스가 중복 소유하던 문서 layout 7개, 공개 route manifest와 ko/en 본문·SEO 번역 키 제거

### Compatibility

- G7 Menu는 관리자 sidebar 전용이고 public user template이 소비하지 않으므로 public taxonomy는 product navigation에 유지
- 미설치 상태인 `sirsoft-gdpr`는 변경하지 않음. 설치 시 기본 `privacy_policy_slug=privacy`가 canonical `/page/privacy`를 소비하는 latent 계약만 검증
- `raonslab-ai-workspace`, 상담·board 공개 API에는 변경이 없으며 상담 intake 기본값은 계속 비활성

## [0.3.1] - 2026-09-28

### Added

- 공개 상담 접수를 열기 전 남은 승인 항목을 설정값 노출 없이 점검하는 준비 상태 점검 명령 추가
- 비식별 합성 데이터로 상담 저장·재전송·중복 충돌·관리자 상태 변경을 확인하고 흔적 없이 되돌리는 리허설 명령 추가

### Fixed

- 관리자 "사업 상담" 메뉴가 더 이상 쓰지 않는 화면을 열어 상담이 없는 것처럼 보이던 문제를 비공개 상담 게시판으로 연결하도록 수정
- 예전 상담 관리 화면 주소로 들어오면 빈 목록 대신 상담 게시판으로 안내하도록 수정

## [0.3.0] - 2026-09-28

### Added

- 서비스·사례·원칙과 개인정보·커뮤니티·AI 작업공간·오픈소스 정책을 설명하는 공개 정보·정책 화면 7종 추가
- 기존 `questions` 게시판에 합성 운영 안내 8개 질문과 depth-1 답변 8개를 적용·갱신·롤백하는 provenance 기반 Q&A 콘텐츠 명령 추가
- 기존 0.2.1 설치가 상담 전용 비활성 private board를 준비하도록 `Upgrade_0_2_2` lifecycle step 추가

### Changed

- visual pass 2에서 상품 사례·서비스·도입 절차를 코드 네이티브 미니 화면과 근거 레일로 구분
- 신규 상담 저장을 별도 product 테이블에서 G7 공식 `PostService`와 항상 비밀·관리자 전용인 비활성 board로 전환
- 기존 product 상담 admin API를 410 안내로 전환하고 상담 운영 화면을 공식 board admin으로 일원화

### Fixed

- Q&A rollback이 provenance가 소유하지 않은 답글·댓글·첨부·신고가 있으면 아무 행도 변경하지 않고 중단하도록 보강
- Q&A apply/rollback의 provenance 한정 알림 억제, topology 검증과 동시 실행 잠금으로 중복·오염을 fail-closed 처리

### Security

- 상담 board의 공개 활성화·일반 사용자 권한·검색 색인·게시글 알림·파일 업로드를 차단하고, 분류 실패 시에도 검색 비색인을 우선
- 기존 상담 테이블에 데이터가 있거나 HTTPS·동의·정책·담당자·보관 설정이 완전하지 않으면 공개 접수를 fail-closed 처리

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
