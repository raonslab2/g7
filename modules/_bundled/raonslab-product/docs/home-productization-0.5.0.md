# 0.5.0 홈 제품화 기록 — 7섹션 · 접수 상태 표시 · 탐색 압축

기준: GNUBOARD7 `c5e2461c`(raonslab-product 0.4.2), 입력은 `docs/g7/audit/HOME_PRODUCT_AUDIT_V1.md` 와 두 감사 입력 문서.
감사 문서는 입력 사실이며 그대로의 명세가 아니다 — 아래는 승인된 설계로 구현한 결과와 실측이다.

## 구조

| # | 섹션 (`id`) | 형태 | 비고 |
|---|---|---|---|
| 1 | Hero (`rh-hero`) | 승인 제목 + 보조 문장 1회 + 4단계 흐름(업무 입력 → 제한된 실행 → 검증 → 결과) | 진행 막대·상태 점·창 장식 없음 |
| 2 | 두 제품 증거 (`rh-proof`) | RAON Hub 사실 줄(실제 실행 / 검증 2026-09-28 / 확장 방식 / 범위) + MOBILE_STOCK 실제 화면 01·캡션·범위 세 줄 (E) | 원시 해시·J/A 코드·명령·공급자/세션 용어 없음 |
| 3 | 구매 범위 (`rh-services`) | 3행: 업무 한 개 실증 / 맞춤 Agent 구축 / 운영·개선 × 입력·결과물·고객 준비 | 가격·기간·ROI·SLA 없음 |
| 4 | 적합성·차이 (`rh-fit`) | 두 열(적합한 업무 / RAON이 맡는 일) + 한 줄 차이 | 다른 AI 도구의 무능 주장 없음 |
| 5 | 사례 (`rh-case`) | 사례별 이름 → (구성 줄·주의 문장) → 검증됨 / 아직 미검증. RAON Hub 는 `/page/cases` 기술 근거로 연결 | 공개 사례 두 개(E) |
| 6 | 절차·신뢰 (`rh-process`) | 범위 → 실행 → 검증 → 승인·복구 타임라인 + 원칙 세 줄 | |
| 7 | 상담 (`rh-consult`) | 준비 사항 + 상담 양식(접수 상태) + 커뮤니티·AI 작업공간(로그인) 링크 | 공지·Q&A·검색은 푸터 |

A–D 에서 MOBILE_STOCK 은 evidence pending 이라 홈에서 뺐다. Wave E 에서 독립 검토된 공개 증거가 결합되어 두 번째 공개 사례로 돌아왔다(아래 "Wave E").

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

## Wave E — MOBILE_STOCK 증거 결합과 두 제품 증거

**출처 결합.** 공개 이미지는 `resources/assets/cases/mobile-stock-public-case-01-new-paper-account-390x844.png` 하나다.
원본은 `raonslab2/mobile-stock` 증거 커밋 `7063bed01e287bbaeaa5b7e3f8c555b39b19ec70` 의
`docs/evidence/screenshots/mobile-stock-public-case-01-new-paper-account-390x844.png`(blob `5f56e1334a18d5a2a6bc08e030f253d1927c6241`,
sha256 `4fa8b7a705bede727aa21f4ba7a089466b2455b15051f2c660adcbeafa3e30de`, 197,799 bytes)이며, 독립 검토 `req_82fc148ab10a4d4195d6f08eb7ad7aa1`
판정은 `PASS_PUBLIC`(제한 범위)이다. 바이트는 모바일 요청 worktree 의 git object 에서 읽었고 운영자 checkout 은 쓰지 않았다.
기록은 같은 디렉토리의 `PROVENANCE.json`, 결합 검사는 `resources/js/evidenceBinding.test.ts` 가 한다(해시·blob·PNG 청크·출처·검토 판정·
디렉토리에 다른 이미지 없음·홈 참조 1개·필수 캡션·범위 밖 주장 금지). 손익이 보이는 05 화면은 쓰지 않는다.

**공개 범위.** 자체 제작 모바일 모의투자 앱 · 새 계정 → 첫 모의 원화 매수 · 로컬 모의 브로커(PAPER, 실거래 비활성) ·
선택형 KIS 연결은 조회 전용 시세이며 주문 경로 없음 · 예시 데이터. 아직 미검증으로 AI 분석 기능, 전체 브라우저 회귀·배포 환경을 적는다.
실거래·증권 주문 연결·실제 KIS 시세·수익·투자 조언·고객 납품·타입 검사 통과는 주장하지 않는다.

**이미지 주소.** 레이아웃은 `src` 없이 `data-rh-asset`(모듈 루트 기준 경로)만 선언하고, `homePage.ts` 가 `G7Core.asset.module` 로 주소를 채운다.
서버의 자산 URL 모드(확장자 경로 / `?file=` 쿼리)를 코어가 정하므로 모듈이 주소를 조립하지 않는다. 코어 API 가 없으면 이미지를 비워 두고 대체 텍스트만 남는다.

**2화면 안의 사례 증거.** 증거 섹션(2번)이 두 제품을 미리 보여 준다. 새 섹션은 만들지 않았다(7섹션 유지).

| 390px | D (`b89e6af2`) | E |
|---|---|---|
| 전체 높이 | 5,223px | 5,925px (목표 약 5,800 을 약 2% 초과) |
| 홈 글자(공백 제외) | 1,174 | 1,441 (상한 1,580 이하) |
| 첫 사례 증거(RAON Hub 사실 줄) | 844px | 851px |
| MOBILE_STOCK 화면 미리보기 | — | 1,083px (≤ 1,688px, 2화면 안) |
| 사례 상세 섹션 | 2,281px | 2,540px |

높이를 줄이려고 사례 상세의 MOBILE_STOCK 구성 줄(증거 섹션 화면과 겹침)을 빼고 휴대폰 여백을 좁혔다. 사실 항목을 더 빼지 않는 한 약 5,800px 은 넘는다.

**`/page/cases` 정합(후속, 이번 턴 범위 밖).** `/page/cases` 본문은 관리자·DB 가 소유하며 아직 과거 MOBILE_STOCK 서술을 담는다.
홈과 맞추려면 승인된 본문으로 PageService(관리자 화면 또는 이 모듈의 운영 명령 계열) 경유로 교체한다. raw SQL 로 고치지 않는다.

## 검증

| 항목 | 결과 |
|---|---|
| 모듈 Vitest(`npm run test:run`) | D: PASS 90/90 · E: PASS 97/97(출처 결합 5 포함) |
| 분류 드리프트(`node scripts/taxonomy.mjs --check`) | PASS |
| 결정적 빌드(`npm run build` 재실행 결과 = 커밋 dist) | PASS |
| 홈 스모크(`tests/browser/home-smoke.cjs`, 후보 주입) | PASS 176 · FAIL 0 · INFO 6(측정 4 · config 429 기록 2) |
| 드로어 계약 시뮬레이션(`mobile-drawer-simulation.cjs`) | PASS(0 failures) |
| PHPUnit `ProductLayerContractTest` | SKIPPED_ENVIRONMENT — 요청 worktree 에 vendor 없음. PHP 변경 없음 |
| 배포 런타임 확인 | UNVERIFIED — 배포 전 후보. 봇 렌더 title·CSS 는 런타임 현재값만 확인 |

`raonslab-ai-workspace` 의 `raonslab-product >=0.1.0` 의존 제약은 공개 API·Service·라우트 변경이 없어(홈 표현만 변경) 그대로 둔다.

## 배포·롤백 순서

1. 다섯 커밋(A–E)을 순서대로 main 에 통합한다(소스 파일이 겹쳐 병렬 통합하지 않는다).
2. 운영 반영: `module:build raonslab-product --production` 결과가 커밋 dist 와 같은지 확인 → `module:update raonslab-product --force`.
3. 확인: 360/390/412/1280 홈 스모크를 후보 주입 없이 실행(MOBILE_STOCK 이미지가 활성 디렉토리에서 200 으로 서빙되는지 포함), `/#rh-case` 등 해시 진입, 안내 문서 11종·커뮤니티·`/ai` 경로.
4. 후속 콘텐츠: `/page/cases` 본문을 PageService 경유로 두 사례 서술에 맞춘다(별도 승인·별도 요청).
5. 롤백: 다섯 커밋을 되돌린 커밋(0.4.2 dist 포함)으로 `module:update raonslab-product --force`. DB 변경·마이그레이션이 없어 데이터 롤백은 없다.
