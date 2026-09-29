# 0.5.1 릴리스 노트 — HOME V2.1 증거·안내 보정 (모듈 부분)

기준: GNUBOARD7 `origin/main` `51e1d550`(raonslab-product 0.5.0 제품 기준선 `664c36ba` + 감사 문서 커밋 3개).
입력: `docs/g7/audit/HOME_V2_VISUAL_BUSINESS_AUDIT.md` 의 F1–F4. 이 릴리스는 **모듈이 소유한 F1·F2·F3(홈 링크 문구)** 만 담는다.

## 이 릴리스(모듈)에 들어간 것

| ID | 변경 | 위치 |
|---|---|---|
| F1 | 사례마다 "무엇을 입증하는가" 한 줄(`rh-proof-case-role`). RAON Hub: "지금 보고 계신 이 사이트입니다. 기존 서비스에 AI 요청·결과·후속 지시 흐름을 확장했습니다." MOBILE_STOCK: "업무 흐름 구현과 실주문 차단 검증을 보여 주는 자체 개발 사례입니다." | 홈 레이아웃 확장, ko/en |
| F1 순증 억제 | RAON Hub 종류 줄(`proof_hub_kind`)을 역할 줄로 교체하고, 역할 줄과 겹치는 "확장 방식" 칸(`proof_extend_*`)을 뺐다. 사실 줄은 실제 실행·검증·범위 세 칸. 두 사례 검증 범위 이동 링크는 MOBILE_STOCK 본문 끝(화면 이미지 옆 빈 세로 공간)으로 옮겼다 | 홈 레이아웃 확장, ko/en |
| F2 | 같은 이미지 1장(바이트·캡션·PROVENANCE 불변)을 휴대폰 이미지 열 9.5rem(152px)·데스크톱 10rem(160px)으로 키웠다. 비율 390/880(=780/1760) 유지, 자르지 않음. 이미지를 링크(`a[data-rh-asset-link]`)로 감싸 탭·키보드로 같은 자산 원본을 연다. href 는 `homePage.ts` 가 안쪽 이미지와 같은 `G7Core.asset.module` 주소로 채우며, 주소를 못 만들면 href 를 두지 않는다. 뷰어 의존성 없음. 접근 가능한 이름 "MOBILE_STOCK 화면 원본 크기로 보기", 기존 alt·캡션 유지 | 레이아웃, CSS, JS, ko/en |
| F2 | 화면 속 앱 이름을 홈에서 한 번 설명: MOBILE_STOCK 종류 줄 "모바일 모의투자 앱 · 화면 속 앱 이름 Symphony" | ko/en |
| F3(홈) | 사례 영역 `/page/cases` 링크 문구 "기술 근거 보기" → "사례 상세 보기"(en "See case details"). 도착지 불변 | ko/en |

주장 경계는 그대로다: 자체 구축·고객 납품 아님, 모의투자·예시 데이터, 검증됨/아직 미검증 병기. AI 분석·실거래·투자 성과·외부 고객 운영·전체 자율 개발을 주장하지 않는다(증거 결합·레이아웃 테스트가 금지 표현을 검사).

## 이 릴리스에 들어가지 않은 것 (독립·대기)

- **Page 콘텐츠(F3 cases·service·contact 다음 행동, F2 cases H2 Symphony 설명)** — native Page 는 PageService 경유 변경이며 CODEX 소유다. 이 모듈 릴리스와 독립적으로 버전이 올라간다. 모듈 0.5.1 은 Page 본문·DB 를 바꾸지 않는다.
- **F4 SEO/정적 표면의 닫힘 불일치** — CODEX 가 재현·최소 수정 계약을 돌려주면 후속으로 통합한다(아래 연결 지점). 이 릴리스에서 F4 동작은 바뀌지 않았다.
- 상담 접수는 닫힘 그대로다(`intake_enabled=false`). 연락처·대체 채널을 만들지 않았다.

## F4 통합 연결 지점(조사만, 미변경)

- 봇 응답은 `resources/seo-config.json` 의 `allowed_attrs`(현재 `aria-labelledby`, `aria-hidden`) 밖 속성을 떨어뜨린다. 그래서 Hero 두 행동 묶음의 `data-rh-intake-show` 가 빠지고 두 벌이 함께 나온다. 코어는 `allowed_attrs` 를 합집합으로 병합한다(`SeoConfigMerger`).
- 사람 화면의 상태 표시는 `main.css` 의 `html:not([data-rh-intake='open']) [data-rh-intake-show='open']` 규칙과 `intakeState.ts` 가 소유한다. 상담 패널 호스트(`[data-rh-consult]`)는 자식 없이 선언되고 `consultationForm.ts` 가 채우므로 봇 응답에서는 비어 있다.
- 따라서 후보 수정 표면은 모듈 안의 `seo-config.json`·`home-product.json`(Hero 행동·상담 호스트 정적 닫힘 문구)·`main.css`·`consultationForm.ts`·ko/en 이다. 코어 SEO 렌더러는 건드리지 않는다.

## 실측 (후보 주입 실제 Chromium, 런타임 `127.0.0.1:18770` = `664c36ba`/0.5.0)

| 항목 | 0.5.0 | 0.5.1 |
|---|---|---|
| 390 전체 높이 | 5,925px | 5,996px (상한 6,000 유지, 완화 없음) |
| 360 / 412 / 1280 전체 높이 | 6,093 / 5,877 / 4,937px | 6,142 / 5,948 / 4,930px |
| 390 MOBILE_STOCK 화면 표시 크기 | 100×226px | 152×343px (1280: 160×361px) |
| 390 "이 사이트가 RAON Hub" 줄 위치 | 없음 | 증거 섹션 695px 안(y<1,000 검사 통과) |
| 390 MOBILE_STOCK 미리보기 시작 | 1,083px | 1,093px (2화면 안) |
| 390 홈 글자(공백 제외) | 1,441 | 1,487 (상한 1,580) |
| 홈 이미지 수 | 1 | 1 |

원본 열기: 이미지 `src` 와 같은 주소, 런타임 응답 200, sha256 `4fa8b7a705bede727aa21f4ba7a089466b2455b15051f2c660adcbeafa3e30de`. 390 에서 키보드 포커스 + Enter, 탭 모두 같은 원본으로 이동.

## 검증

| 항목 | 결과 |
|---|---|
| 모듈 Vitest(`npm run test:run`) | PASS 104/104 |
| 분류 드리프트(`node scripts/taxonomy.mjs --check`) | PASS |
| 결정적 빌드(재빌드 = 커밋 dist, 기준선 소스 재빌드 = 기준선 dist) | PASS |
| 홈 스모크 390 전체 경로(폼·탐색 포함) | PASS 98 · FAIL 0 |
| 홈 스모크 360/412/1280(레이아웃·영향 요소) | PASS 130 · FAIL 0 |
| PHPUnit | 미실행 — 요청 worktree 에 vendor 없음, PHP 변경 없음 |
| 배포 런타임 | 미배포 후보 |

`raonslab-ai-workspace` 의 `raonslab-product` 의존 제약은 공개 API·Service·라우트 변경이 없어 그대로 둔다(홈 표현만 변경).

## 배포·롤백

- 반영: `module:build raonslab-product --production` 결과가 커밋 dist 와 같은지 확인 → `module:update raonslab-product --force`.
- 롤백: 이 커밋을 되돌린 dist(0.5.0)로 `module:update raonslab-product --force`. DB·마이그레이션 변경 없음.
