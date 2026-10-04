# RAON Agent Factory 홍보영상 V1 — 장면 구성 (Scene List)

- 러닝타임: **88.0초** (30fps, 16:9 1920×1080 / 9:16 1080×1920)
- 핵심 메시지: **대화는 단순하게. 실행은 강력하게. 증거는 명확하게.**
- 메인 카피: **AI Agent를 만드는 공장. 실제로 일하게 만드는 운영 플랫폼.**
- 타이밍 원천: [`render/timeline.json`](render/timeline.json) — 자막·내레이션·렌더가 같은 값을 쓴다.

## 소재 구분 기준

| 표기 | 의미 | 화면 표시 |
|---|---|---|
| **실제 UI** | 실제 배포된 AI_GCS V2 Web 빌드(web `f6dd9a92`)를 그대로 렌더한 화면. `/api/v1` 응답만 안전 데모 데이터로 대체 | 좌상단 태그 `실제 화면 · … · 데모 데이터` |
| **실제 공개 사이트** | G7 / RAON Hub 공개 페이지를 2026-10-04 GET 요청만으로 캡처 | `실제 공개 사이트 · 2026-10-04 캡처 · 자체 구축` |
| **실제 제품 공개 사례** | MOBILE_STOCK 저장소의 공개 사례 증거 스크린샷(모의투자) | `공개 사례 화면 · 모의투자 · 실거래 미지원 · 예시 데이터` |
| **구조 도식** | 현재 canonical 실행 구조를 그대로 그린 도식(새 체계 아님) | 하단 주석 `현재 운영 구조 그대로` |
| **추상 모션** | 실제 제품 화면이 아닌 코드 렌더 모션 그래픽(생성형 영상 대체 슬롯) | 제품 UI 요소를 쓰지 않음 |

비율: 실제 UI·공개 사이트·제품 화면 57.5초 + 실제 구조 도식 7.5초 = **약 74%**, 추상 모션 23초 = **약 26%**.

## 데모 데이터의 근거

AI_GCS 장면의 위임 구조는 2026-10-04 실제 검증된 운영 실행을 따른다
(AI_GCS V2 저장소(비공개) `docs/control-plane-20261004/evidence/closure.json`):
총괄 CODEX 1건 + 위임 4건(`backend-review` CODEX, `ux-review` CLAUDE, `cross-api` CODEX, `cross-ux` CLAUDE),
같은 네이티브 세션 복귀, CLAUDE 자식의 집중 테스트 6 PASS·빌드 PASS, 원격 브랜치 push,
런타임 release `794ec782-f6dd9a92`. 화면의 commit `30954e6f…`, release, web/backend SHA 는 그 실제 값이다.
Request ID·사용자 ID·시각은 데모 값이며 화면에 Request ID 는 노출되지 않는다.
제목·결과 문장은 데모용으로 축약했다.

## 장면

| # | 구간 | 장면 | 소재 | 화면 내용 | 전달 메시지 |
|---|---|---|---|---|---|
| S1 | 0.0–7.0 | 문제 제시 | 추상 모션 | 흩어진 답변·로그 조각(“답변 완료”, “배포됐나요?”, “테스트는?”)이 떠다니다 흐려짐 | AI는 답하지만, 일이 끝났는지는 확인되지 않는다 |
| S2 | 7.0–16.0 | 브랜드 | 추상 모션 + 워드마크 | 조립 라인(레인)을 따라 움직이는 점 → `RAON / AGENT FACTORY` → 메인 카피 2줄 | AI Agent를 만드는 공장, 실제로 일하게 만드는 운영 플랫폼 |
| S3a | 16.0–22.0 | 내 작업 | 실제 UI (데스크톱 1440) | AI_GCS 내 작업 목록, 상단 탭(내 작업·지시함·프로젝트·운영 통계) 강조 | 대화하듯 한 문장으로 |
| S3b | 21.5–28.0 | 새 요청 | 실제 UI (모바일 390) | “무엇을 맡길까요?” — 프로젝트 → Provider → 요청 원문 순서로 강조 | 원문 그대로 Provider에 전달 |
| S3c | 27.5–33.0 | 내 작업·지시함·프로젝트 | 실제 UI (모바일 ×3) | 세 화면 나란히 | 진행 상황은 한곳에서 |
| S4a | 33.0–40.5 | 실행 구조 | 구조 도식 | AI_GCS → AgentOpt → CODEX / CLAUDE → Evidence → Git | 접수에서 Git까지 한 경로 |
| S4b | 40.0–51.0 | 멀티 Provider | 실제 UI (모바일 390) | 협업 진행: CODEX·CLAUDE 실행 중 → 4/4 종료, 교차 검토 강조 | 역할을 나누고 서로 검토한다 |
| S5a | 51.0–58.5 | 결과물 | 실제 UI (모바일 390) | 결과물 카드 → Git·통합 → 운영 반영 순서로 강조 | 완료와 Git 통합과 운영 반영은 다르다 |
| S5b | 58.0–67.0 | 관측 증거 | 실제 UI (데스크톱 1440, 상세 패널 확대) | 명령·테스트 PASS, 로컬 commit SHA, GitHub 원격 ref·main 확인 | Provider의 말이 아니라 관측된 값으로 |
| S6a | 67.0–74.0 | G7 · RAON Hub | 실제 공개 사이트 | 데스크톱 홈 스크롤 + 모바일 홈 | 이 흐름으로 만든 실제 사이트 |
| S6b | 73.5–80.0 | MOBILE_STOCK | 실제 제품 공개 사례 | 새 Paper 계정 → 모의 매수 확인 → 체결 영수증 | 같은 방식으로 개발 중인 모의투자 앱 |
| S7 | 80.0–88.0 | 엔딩 | 모션 + 워드마크 | `RAON / AGENT FACTORY` + 엔딩 카피 3줄 + `AI_GCS V2 · AgentOpt V2 · CODEX · CLAUDE` | 대화는 단순하게. 실행은 강력하게. 증거는 명확하게. |

## 역할 표현 대조 (실제 구조와 일치 여부)

| 대상 | 영상에서의 표현 | 근거 |
|---|---|---|
| AI_GCS V2 | 대화형 지시 · 접수 · 결과 확인. 의미를 분류하거나 다시 쓰지 않음 | `ai_gcs_v2/README.md` — “The Web does not classify user meaning, invent execution state…” |
| AgentOpt V2 | Provider 실행 · 이벤트 기록 · 서버 관측(evidence) | `docs/control-plane-20261004/README.md` — “AgentOpt owns execution truth” |
| CODEX / CLAUDE | 총괄이 위임한 역할별 실행, 요청별 격리 작업공간, 결과는 같은 총괄 대화로 복귀 | 같은 문서 “Contract”, closure.json `same_parent_native_session: true` |
| Validation | Provider 완료 ≠ 검증. 테스트·커밋·원격 ref·운영 receipt 를 각각 표시 | `src/app.ts` resultCard/deliveryTruth — “COMPLETED is never shown as verified or deployed” |
| Git | `REMOTE_REF_VERIFIED` + `main_ref_verified` 일 때만 “main 원격 ref 확인” | `src/app.ts` deliveryTruth |
| 운영 반영 | 배포 receipt 가 현재 런타임과 일치할 때만 “서비스 반영 확인” | `src/runtime-delivery.ts` |
| G7 / RAON Hub | 자체 구축 사이트, 외부 고객 운영은 아직 미검증 | 사이트 본문 “내부 자체 구축 · 외부 고객 운영은 아직 미검증” |
| MOBILE_STOCK | 모의투자 앱, 실거래 미지원 | 공개 사례 화면 하단 “모의투자 · 자체 제품 · 예시 데이터” |
| CYBER_DEFENSE | 사용하지 않음(미래/실험 프로젝트) | 작업지시서 범위 |

## 사용하지 않은 것

- 고객 수, 매출, 성공률, 비용 절감률, 기업 도입 실적, 보안 성과 — 없음.
- AI_GCS 운영 통계 화면 — 데모 데이터로 계산된 비율(예: 완료율)이 실제 성과로 오인될 수 있어 영상에서 제외했다(탭 이름만 노출).
- 타사 로고·공개 인물·출처 불명 소재 — 없음. 폰트는 저장소 동봉 Pretendard(OFL)·Noto Sans CJK(OFL).
