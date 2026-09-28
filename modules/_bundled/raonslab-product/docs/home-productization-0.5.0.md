# 0.5.0 홈 제품화 기록 — 7섹션 · 접수 상태 표시 · 탐색 압축

기준: GNUBOARD7 `c5e2461c`(raonslab-product 0.4.2), 입력은 `docs/g7/audit/HOME_PRODUCT_AUDIT_V1.md` 와 두 감사 입력 문서.
감사 문서는 입력 사실이며 그대로의 명세가 아니다 — 아래는 승인된 설계로 구현한 결과와 실측이다.

## 구조

| # | 섹션 (`id`) | 형태 | 비고 |
|---|---|---|---|
| 1 | Hero (`rh-hero`) | 승인 제목 + 보조 문장 1회 + 4단계 흐름(업무 입력 → 제한된 실행 → 검증 → 결과) | 진행 막대·상태 점·창 장식 없음 |
| 2 | RAON Hub 증거 (`rh-proof`) | 한 줄 증거 줄: 실제 실행 / 검증(2026-09-28) / 확장 방식 / 범위 | 원시 해시·J/A 코드·명령·공급자/세션 용어 없음 |
| 3 | 구매 범위 (`rh-services`) | 3행: 업무 한 개 실증 / 맞춤 Agent 구축 / 운영·개선 × 입력·결과물·고객 준비 | 가격·기간·ROI·SLA 없음 |
| 4 | 적합성·차이 (`rh-fit`) | 두 열(적합한 업무 / RAON이 맡는 일) + 한 줄 차이 | 다른 AI 도구의 무능 주장 없음 |
| 5 | 사례 (`rh-case`) | 구성 줄(기존 G7 서비스 + RAON 확장 + AI 작업공간) → 검증됨 / 아직 미검증 → `/page/cases` | 공개 사례는 RAON Hub 하나 |
| 6 | 절차·신뢰 (`rh-process`) | 범위 → 실행 → 검증 → 승인·복구 타임라인 + 원칙 세 줄 | |
| 7 | 상담 (`rh-consult`) | 준비 사항 + 상담 양식(접수 상태) + 커뮤니티·AI 작업공간(로그인) 링크 | 공지·Q&A·검색은 푸터 |

MOBILE_STOCK 은 원본 소스·버전·테스트·공개 가능한 화면이 결합될 때까지 evidence pending 이며 홈에 나오지 않는다.
`product-brief.md`·`sales-evidence-map.md` 의 사업 기록은 그대로 둔다. 증거가 결합되면 `raon_home_case_list` 에 같은 형태의 항목을 추가한다.

## 상담 접수 상태(클릭 전 표시)

- 출처는 `GET /api/modules/raonslab-product/consultations/config` 하나다. `parseIntakeConfig` 결과를 `<html data-rh-intake="open|closed">` 에 싣는다(`intakeState.ts`).
- 레이아웃은 `data-rh-intake-show="open|closed"` 로 상태별 요소를 선언한다. 확인 전·실패·알 수 없는 값은 닫힘이다.
- 닫힘: Hero 주 행동 "검증된 RAON Hub 사례 보기", 보조 "도입 절차 보기". 상위 메뉴 상담 진입점은 링크 이름 안에 "접수 준비 중"을 표시한다.
- 열림: 주 행동 "AI 에이전트 구축 상담", 보조 "검증된 RAON Hub 사례 보기".
- 상담 양식이 없는 화면(문서 화면)의 메뉴 표시는 탭 세션에 2분간 기억한 값을 쓴다. 상담 양식은 기억값을 쓰지 않고 항상 새로 확인한다.
  config 는 IP 당 분당 60회로 제한되며, 제한에 걸리면 표시는 닫힘으로 떨어진다(PII 수집·접수 활성화 없음).

## 탐색

- 홈 섹션 바로가기 줄을 제거했다.
- 휴대폰·태블릿(템플릿 portable 구간 0~1023px) 홈은 제품 바를 숨기고 기본 헤더 + 드로어 하나로 탐색한다. 정보·정책 문서는 0.4.2 드로어 문서 섹션(같은 분류 단일 출처)이 담는다.
- 데스크톱은 템플릿 헤더(G7 공통: 로그인·검색·게시판 탭) 아래 제품 바 한 줄이 유일한 판매 헤더다. 템플릿 헤더는 확장 지점이 없어 합칠 수 없다(템플릿 수정 범위 밖).

## 실측 (실제 Chromium, 일반 Android/Linux Chrome UA, ko, 2026-09-29)

before 는 런타임 0.4.2 를 그대로, after 는 같은 런타임에 후보 빌드(JS/CSS·홈 레이아웃·상위 메뉴·다국어)를 네트워크 가로채기로 끼운 결과다. 배포 후 런타임 결과가 아니다.

| 폭 | 높이 before → after | 홈 글자(공백 제외) | RAON Hub 증거 시작 | 사례 섹션 시작 |
|---|---|---|---|---|
| 360 | 9,429 → 5,366px | 2,578 → 1,174 | 없음 → 691px (1화면 안) | 2,898 → 2,326px (2.98화면) |
| 390 | 9,225 → 5,223px | 2,578 → 1,174 | 없음 → 695px (1화면 안) | 2,877 → 2,281px (2.70화면) |
| 412 | 8,995 → 5,168px | 2,578 → 1,174 | 없음 → 699px (1화면 안) | 2,855 → 2,226px (2.43화면) |
| 1280 | 5,707 → 4,174px | 2,578 → 1,174 | 없음 → 634px (1화면 안) | 1,987 → 1,983px (2.20화면) |

- 390px 높이는 목표(5,300–5,800)보다 조금 더 줄었다. 글자 수는 목표 하한(1,230)보다 적다 — 사실 항목을 빼지 않고 반복·내부 용어만 걷어 낸 결과이며 글자 수를 채우려고 문장을 늘리지 않았다.
- **사례 섹션 2화면 이내 목표는 달성하지 못했다(390px 2.70화면).** 승인된 순서(Hero → 증거 → 구매 범위 → 적합성 → 사례)에서 앞 네 섹션이 약 2,280px 을 쓴다. 사례로 가는 진입(Hero 주 행동, 증거 줄 "사례 구조 보기")은 첫 화면·첫 스크롤 안에 있다.
- 가로 넘침 0(360/390/412/1280, ko/en, 긴 입력), 콘솔 오류 0, 터치 대상 44px 이상, 텍스트 대비 WCAG AA, reduced-motion 전환 제거.

## 검증

| 항목 | 결과 |
|---|---|
| 모듈 Vitest(`npm run test:run`) | PASS 90/90 |
| 분류 드리프트(`node scripts/taxonomy.mjs --check`) | PASS |
| 결정적 빌드(`npm run build` 재실행 결과 = 커밋 dist) | PASS |
| 홈 스모크(`tests/browser/home-smoke.cjs`, 후보 주입) | PASS 176 · FAIL 0 · INFO 6(측정 4 · config 429 기록 2) |
| 드로어 계약 시뮬레이션(`mobile-drawer-simulation.cjs`) | PASS(0 failures) |
| PHPUnit `ProductLayerContractTest` | SKIPPED_ENVIRONMENT — 요청 worktree 에 vendor 없음. PHP 변경 없음 |
| 배포 런타임 확인 | UNVERIFIED — 배포 전 후보. 봇 렌더 title·CSS 는 런타임 현재값만 확인 |

`raonslab-ai-workspace` 의 `raonslab-product >=0.1.0` 의존 제약은 공개 API·Service·라우트 변경이 없어(홈 표현만 변경) 그대로 둔다.

## 배포·롤백 순서

1. 네 커밋을 순서대로 main 에 통합한다(소스 파일이 겹쳐 병렬 통합하지 않는다).
2. 운영 반영: `module:build raonslab-product --production` 결과가 커밋 dist 와 같은지 확인 → `module:update raonslab-product --force`.
3. 확인: 360/390/412/1280 홈 스모크를 후보 주입 없이 실행, `/#rh-case` 등 해시 진입, 안내 문서 11종·커뮤니티·`/ai` 경로.
4. 롤백: 네 커밋을 되돌린 커밋(0.4.2 dist 포함)으로 `module:update raonslab-product --force`. DB 변경·마이그레이션이 없어 데이터 롤백은 없다.
