# RAON Agent Factory Home Product Audit V1

**결론 — 현재 Home은 내부 구축 역량을 정직하게 설명하지만, 실제 제품 증거와 작동하는 상담 경로가 부족해 “검토용 기술 소개”에는 합격, “고객 전환 Home”에는 아직 부적합하다.**

기준은 GNUBOARD7 `57f75c859fedafd498df804554704c2060913091`, `raonslab-product 0.4.2`이다. 제품 소스·런타임·DB 변경 없이 두 감사 입력을 종합했다. 사업 진실 판정은 [business/evidence audit](HOME_BUSINESS_EVIDENCE_AUDIT_INPUT.md)의 **38개 주장 = VERIFIED 10 / PARTIALLY_VERIFIED 13 / UNVERIFIED 8 / MARKETING_ONLY 7**을 따른다. UX 실측·상세 근거는 [UX/visual audit](HOME_UX_VISUAL_AUDIT_INPUT.md)을 따른다.

## Audit artifacts

아래 파일은 **현재 화면의 감사 캡처와 제안 와이어프레임일 뿐, 구현되었거나 승인된 vNext UI가 아니다.** 와이어프레임의 MOBILE_STOCK과 Hero B는 본 종합 권고에 의해 각각 증거 대기·별도 승인 조건이다.

| Artifact | Scope |
|---|---|
| [390px Home 01](home-v1/390-home-01.png) | 현재 런타임: 헤더·Hero·문제·서비스 |
| [390px Home 02](home-v1/390-home-02.png) | 현재 런타임: 사례 |
| [390px Home 03](home-v1/390-home-03.png) | 현재 런타임: 절차·기술·상담·푸터 |
| [1280px desktop Home](home-v1/desktop-home.png) | 현재 런타임 전체 화면 |
| [390px vNext wireframe](home-v1/390-vnext-wireframe.svg) | 구조 제안용 감사 산출물; 구현 화면 아님 |

## [current Home diagnosis]

- 첫 화면은 “AI 에이전트 구축” 정체성은 전달하지만, 무엇을 살 수 있고 실제 제품이 무엇인지는 늦게 나온다.
- 3단 헤더, 반복 카드와 중복 설명 때문에 모바일은 `9,226px`, `10.9 screens`까지 늘어난다.
- RAON Hub는 내부 실증 근거가 있으나 실제 UI 증거가 없고, MOBILE_STOCK은 현재 baseline에서 공개 증거로 쓸 수 없다.
- 주 CTA는 의도적으로 닫힌 상담 화면으로 끝나므로, 현재는 사례·증거를 primary action으로 삼아야 한다.

## First-screen five-question result

| Buyer question | Current result | vNext answer |
|---|---:|---|
| 무슨 회사인가 | PASS | 한 업무를 기존 시스템에 연결·실행·검증하는 구축 서비스 |
| 무엇을 살 수 있나 | FAIL | Hero 바로 아래에 실증 / 구축 / 운영·개선 3개 범위 |
| 일반 AI 사용과 무엇이 다른가 | PARTIAL | 경쟁사 무능 주장이 아니라 RAON의 연결·권한·검증 책임 |
| 실제 제품이 있나 | FAIL | 검증된 RAON Hub 증거를 첫 스크롤 안에 배치 |
| 어디서 상담하나 | PARTIAL | 닫힘 상태를 클릭 전에 표시; 승인 전에는 사례가 primary |

## Before → After

| Before | After |
|---|---|
| G7 bar + product bar + section bar | 판매 목적의 1-line header; 상세 탐색은 drawer/footer |
| 추상 pipeline과 상담 CTA 3회 | 작은 A pipeline + RAON Hub evidence strip + 상태 인지 CTA |
| 문제·서비스·절차·기술이 반복 설명 | 구매 범위 → 실행 통제 → 증거 순서로 역할 분리 |
| 두 텍스트 사례, MOBILE_STOCK 우선 | RAON Hub 단독 공개 proof; MOBILE_STOCK은 evidence pending |
| “상담” 클릭 뒤 접수 불가 발견 | 접수 불가를 클릭 전에 표시하고 사례/적합성 확인으로 유도 |

현재 Home은 공백 제외 **2,578 characters**다. 목표는 **1,230–1,580 characters, 39–52% reduction**이다. 사실·미검증 범위는 유지하고, 반복·내부 용어·원시 검증 코드를 줄인다. 현재 390px 높이는 **9,226px / 10.9 screens**이며 목표는 사례가 2 screens 안에 나타나는 약 5,300–5,800px다.

## KEEP 5

1. “업무 한 개 실증”을 첫 구매 단위로 둔다.
2. “고객 납품이 아닌 내부 자체 구축”이라는 정직한 경계를 유지한다.
3. 통과·실패·미검증 구분과 상용 부하·장기 운영·외부 고객 미검증을 유지한다.
4. 기존 시스템 계약 우선, 권한 경계, 코어 변경 최소화 원칙을 유지한다.
5. 상담 준비 정보인 반복 업무·연결 시스템/데이터·결과 기준을 유지한다.

## [remove] REMOVE / COMPRESS — 5

1. MOBILE_STOCK 공개 proof와 관련 제품 문구는 authoritative source/version/tests/safe visual이 결합될 때까지 제거한다.
2. “대화형 AI는 답만 준다”와 같은 경쟁사 incapability 표현을 제거한다.
3. 모바일 section shortcut bar와 중복 “커뮤니티와 안내” 줄을 제거한다.
4. 장식용 progress bars, fake terminal chrome, unexplained `J1–J10` / `A1–A10`을 Home에서 제거한다.
5. 문제 3 cards + 서비스 9 checks + 절차 5 descriptions + 기술 5 cards의 반복을 압축한다.

## [visualize] VISUALIZE — 5

1. 현재는 작은 A pipeline으로 input → scoped execution → verification → result만 보여 준다.
2. RAON Hub의 “unchanged G7 core + two extensions” 구조를 한 장으로 보여 준다.
3. source/version, passed/failed/unverified, remaining limits를 담은 verification receipt를 보여 준다.
4. pilot / build / operate의 input·deliverable·exclusion을 3-row comparison으로 보여 준다.
5. API·owner·data·permission·success criterion의 consultation-fit checklist를 보여 준다.

## MOVE TO DETAIL 5

1. request worktree, native session, Provider, persisted-event mechanics.
2. `J1–J10` / `A1–A10` 정의와 raw validation commands.
3. GNUBOARD7/module versions와 zero-core-diff inventory.
4. backup/rollback, retention, monitoring과 infrastructure topology.
5. 역할별 권한, 관리자 운영, 개인정보·정책 전문과 전체 미검증 목록.

## [new Home structure] — one compact visual

```text
[1 Hero: purchase truth + A-lite]
            ↓
[2 RAON Hub proof strip] → [3 What you can buy: pilot / build / operate]
            ↓
[4 Fit & difference: system · permission · success criteria]
            ↓
[5 Case evidence: problem → work → verified / unverified]
            ↓
[6 Process + trust: scope → run → verify → approve/recover]
            ↓
[7 State-aware CTA + approved truth footer]
```

## Persona 5 / 15 / 30 / 60 second summary

| Persona | 5 sec | 15 sec | 30 sec | 60 sec / consultation reason |
|---|---|---|---|---|
| SME owner | AI 구축 업체 | 업무 1개부터 시작 | 구매 3단계와 RAON proof 이해 | 자기 업무의 적합성·범위를 확인하려 상담; ROI 보장은 없음 |
| CTO / dev lead | 통합·검증 서비스 | 시스템/권한 경계 확인 | G7 확장·검증 evidence 확인 | API·identity·data boundary와 acceptance evidence를 논의 |
| SI / operations lead | 구축→운영 흐름 | 승인·책임 분리 확인 | 검증·복구·미검증 경계 확인 | 운영 책임, change approval, support 범위를 정하기 위해 상담 |
| AI-curious non-expert | “AI가 실제 업무 수행” | 한 업무 pilot 이해 | before/after와 결과 확인 방식 이해 | 어떤 업무가 가능한지 판단하기 위해 상담; 현재는 접수 불가 |

## Hero options A / B / C

| Option | Decision | Reason |
|---|---|---|
| A — compact pipeline | **Recommend now** | 새 공개 자산 없이 실행·검증 개념을 설명하며 private data 위험이 없다 |
| B — synthetic, de-identified real UI | **Recommend only after approval** | 실제 제품 존재를 가장 잘 증명하지만, 공개용 합성 계정·가명 요청·식별자 제거와 별도 승인이 선행되어야 한다 |
| C — parallel Provider view | Do not use on Home | 내부 운영 구조와 공급사 용어가 구매자 가치보다 앞선다 |

## [recommended Hero] — one option

**A-lite를 현재 권고한다.**

> **반복 업무 하나를, 기존 시스템에서 실제로 실행되는 AI 흐름으로.**
>
> 연결할 데이터와 권한을 먼저 정하고, 요청·진행·결과와 테스트·화면 검증을 함께 남깁니다.
>
> Primary: **검증된 RAON Hub 사례 보기** · Secondary: **온라인 상담 준비 상태 확인**

승인된 synthetic/de-identified UI proof가 준비된 뒤에만 B로 교체한다. 현재 private AI_GCS, admin, request-detail screenshot은 publish하지 않는다.

## Scorecard: current V1 vs proposed vNext (1–5)

| Exact criterion | V1 | vNext expected |
|---|---:|---:|
| 5-second comprehension | 3 | 5 |
| business clarity | 3 | 5 |
| differentiation | 3 | 4 |
| visual explanation | 4 | 5 |
| actual product evidence | 2 | 4 |
| CTA focus | 2 | 4 |
| mobile readability | 3 | 4 |
| trust | 3 | 4 |
| text density | 2 | 4 |
| tech/business balance | 3 | 5 |

`CTA focus`는 접수 승인 전 최대 4다. `actual product evidence`와 `trust`도 외부 고객 성공 사례가 생기기 전에는 내부 RAON Hub 증거의 범위를 넘지 않는다.

## Exact Q1–Q6

1. **Q1 — picture-only understanding:** PARTIAL. 현재 그림은 흐름만 설명하고 구매 대상·실제품은 보여 주지 못한다. A-lite + RAON proof strip으로 개선하되, B 승인 전에는 실제 UI로 오인시키지 않는다.
2. **Q2 — can buyer imagine own task:** NO today. vNext의 fit checklist와 한 업무 pilot 입출력이 있어야 자기 업무를 대입할 수 있다.
3. **Q3 — real products visible:** NO. 제품명과 텍스트는 보이지만 공개 가능한 실제 UI 증거는 없다. RAON Hub만 검증된 내부 proof이고 MOBILE_STOCK은 evidence pending이다.
4. **Q4 — too much reading:** YES. 2,578 characters, 9,226px, 10.9 screens이며 사례만 Home 텍스트의 38%다.
5. **Q5 — technical-doc vs business-site feeling:** 현재는 Hero 이후 technical documentation 쪽으로 기운다. 구매 범위·적합성·산출물을 앞세우고 내부 용어를 detail로 보내야 한다.
6. **Q6 — enough reason to consult:** NEED exists, PATH does not. 범위·연결·권한 판단을 상담할 이유는 있으나 현재 private intake와 승인 연락 채널이 없어 전환 이유가 행동으로 이어지지 않는다.

## [expected scope] — P0 / P1 only

### P0 — truth gates

1. 접수 비활성 동안 evidence/case를 primary로 하고 “상담 불가”를 클릭 전에 표시한다.
2. MOBILE_STOCK은 증거 결합 전 Home/public proof에서 제외한다.
3. public HTTP, canonical/OG/sitemap local-host 문제를 customer launch 전에 해소한다.
4. `/page/technology`의 “AI 연동 보류”와 Home의 active-workspace claim을 하나의 승인된 사실로 맞춘다.
5. competitor incapability를 주장하지 않고 RAON의 implementation responsibility로 차별화한다.

### P1 — evidence-led sales flow

1. 7-section flow와 39–52% text reduction을 적용한다.
2. RAON Hub를 첫 proof로 올리고 dated verification receipt를 제공한다.
3. pilot/build/operate의 inputs, deliverables, customer responsibilities와 exclusions를 명시한다.
4. 상세 기술·검증·정책은 existing detail pages로 보낸다.
5. 별도 승인된 synthetic/de-identified proof가 준비되면 Hero A를 B로 교체한다.

## Gate summary

| Gate | Status |
|---|---|
| Audit synthesis candidate | PASS — docs/artifacts only |
| Public customer-acquisition Home | NO-GO — consultation, domain/TLS/metadata/legal truth gates open |
| MOBILE_STOCK public proof | NO-GO — authoritative evidence not bound |
| Hero B / private AI screenshot | NO-GO — synthetic de-identified proof and approval absent |
| Product implementation or main delivery | OUT OF SCOPE |
