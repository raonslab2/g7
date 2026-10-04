# 실제 화면 캡처 기록

모든 캡처는 2026-10-04 이 제작 요청 안에서 수행했다. 운영 API·DB·Request 를 만들거나 바꾸지 않았다.

## 1. AI_GCS V2 — `capture-ai-gcs.mjs`

- 대상: AgentOpt 런타임에 **현재 배포된 Web 빌드 그대로**(`release.json` web_sha `f6dd9a92bc06562ab9dba310206497715256976e`, backend `794ec782…`)를 로컬 정적 서버로 띄웠다. 빌드 산출물은 수정하지 않았다.
- `/api/v1/*` 응답만 Playwright `page.route` 로 **안전 데모 데이터**로 대체했다. 운영 API·쿠키·토큰을 쓰지 않으며 네트워크는 로컬 서버로만 나간다.
- 데모 데이터 구조는 실제 검증 실행(`ai_gcs_v2/docs/control-plane-20261004/evidence/closure.json`)을 따른다. 상세는 [`../scene-list.md`](../scene-list.md) “데모 데이터의 근거”.
- 뷰포트: 모바일 390×844 @3x, 데스크톱 1440×900 @1.5x, `ko-KR`, `Asia/Seoul`, 시계 고정 2026-10-04 15:20 KST.
- 화면 변경은 단 하나: 스크롤 위치에 따라 떠오르는 “↓ 맨 아래로” 이동 버튼(`.conversation-jump`)을 가렸다. 다른 요소·문구·상태는 제품이 그린 그대로다.
- 이 호스트에 기호 글꼴이 없어 결과물 아이콘(⑂ ⛓)이 빈 상자로 그려지던 문제를, 제품이 아니라 **캡처 호스트에 Noto Sans Symbols/Symbols2(OFL)** 를 사용자 글꼴로 추가해 해결했다.
- 페이지 예외: 20개 캡처 모두 0건([`ai-gcs-capture-manifest.json`](ai-gcs-capture-manifest.json) `page_errors`).
- 강조 박스 좌표는 캡처 순간 DOM 의 `getBoundingClientRect()` 값이며 합성기가 이 매니페스트를 그대로 읽는다. 예외 1건: S3a 데스크톱 상단 탭 강조는 캡처 화면 실측 근사값.
- 운영 통계 화면(`09-statistics-*`)은 캡처했지만 영상·저장소에서 제외했다 — 데모 데이터로 계산된 비율이 실제 성과처럼 보일 수 있다.

실행(저장소 밖 작업 디렉토리에서):

```bash
cp -r ~/.agentopt-v2/runtime/current/frontend web
node capture-ai-gcs.mjs web captures   # playwright 1.55 필요
```

## 2. G7 / RAON Hub 공개 사이트 — `capture-g7.mjs`

- `G7_PUBLIC_URL` 의 공개 홈을 **GET 요청만으로** 캡처(로그 `methods: ["GET"]`). 로그인·폼 제출·쓰기 없음. 진행 중이던 G7 복원 작업의 Page/DB 에 접근하지 않았다.
- 일반 브라우저 User-Agent 를 쓴다. 헤드리스 기본 UA 는 G7 SEO 렌더러가 봇으로 판정해 정적 SEO HTML(스타일 없음)을 돌려주므로 실제 방문자 화면과 다르다.
- 데스크톱 1440×900 @1.5x 전체 페이지, 모바일 390×844 @3x 상단 7,200px.
- 공개 URL(주소)은 영상 프레임 어디에도 나오지 않는다(브라우저 프레임 주소창에는 `RAON Hub` 만 표시).

## 3. MOBILE_STOCK — 기존 공개 사례 증거 재사용

- 출처: `mobile-stock/docs/evidence/screenshots/mobile-stock-public-case-0{1,3,4,5}-*.png` (공개 사례 증거 `MOBILE_STOCK_PUBLIC_CASE_EVIDENCE_20260929.md`). 새로 실행하거나 주문하지 않았다.
- 화면 하단 띠 “모의투자 · 자체 제품 · 예시 데이터”가 원본에 포함되어 있다. 종목명(예: 삼성전자 · 005930)은 모의 데이터의 텍스트이며 로고가 아니다.

## 민감정보 점검 (프레임·파일)

| 점검 | 결과 |
|---|---|
| Request ID / Work ID | 화면 텍스트에 `req_` 없음(20개 캡처 DOM 텍스트 검사). 데모 ID 는 응답에만 있고 화면 미노출 |
| 사용자 ID·이메일 | 데모 `demo-user`, 이메일 패턴 0건 |
| 서버 주소·SSH·credential·token·cookie | AI_GCS 는 로컬 정적 서버 + 모킹이라 운영 주소·쿠키 없음. G7 공개 URL 은 프레임 미노출 |
| Git SHA·저장소명 | AI_GCS V2 저장소(비공개)의 커밋·릴리스 식별자 일부만 노출(`30954e6f50aa`, `794ec782-f6dd9a92`). 자격증명·내부 주소가 아니며 저장소명은 영상 프레임에 나오지 않는다 |
| 개인 데이터 | MOBILE_STOCK 공개 사례는 예시 계정/모의 데이터 |

최종 영상 프레임 검사 결과는 [`../README.md`](../README.md) “검증” 절에 기록한다.
