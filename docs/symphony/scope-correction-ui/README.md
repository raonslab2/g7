# 공개 벤치마킹 검토본 — UI 잔여 검증

현재 승인된 비운영 앱 `http://127.0.0.1:18871/travel`을 Chromium 실제 브라우저에서 확인했다. 이 주소는 **loopback**이며 외부 공개 미리보기 주소가 아니다. PC [1440 화면](home-1440.png), 모바일 [390 화면](home-390.png), [상품 상세 1440](product-1440.png), [상품 상세 390](product-390.png)를 제공한다. RAON 자체 문구·합성 여행상품·원작 SVG를 사용한다. 실제 예약·결제는 연결하지 않는다.

검증 원본은 `888f6b2d052b3b189fb1ff322b1e117e281e8e4d`이다. 검증자가 제품 소스를 수정하지 않았다. `git diff 5783e6ba124061bdfae639cdaf9b1c14a83cdf03 HEAD -- app routes resources public modules templates config lang bootstrap`는 비어 있어 이전 독립 Retry/Page권한/실제 빈 기획전 증거의 runtime 소스를 그대로 인수한다. 기존 보고서와 실패 기록을 바꾸지 않는다.

| 실제 브라우저 검증 | 390 | 1440 |
|---|---:|---:|
| 공개 홈, 합성 상품 native GET, 화면 촬영 | PASS | PASS |
| 실제 메뉴 → 기획전 → 필터 검색 → 상품 상세 → 브라우저 뒤로가기 | PASS | PASS |
| 서버의 실제 빈 검색 → 필터 초기화 → 상품 회복 | PASS | PASS |
| catalog GET 중단 → 오류 → Retry → native 200 → 오류 사라짐, reload 없음 | PASS | PASS |
| 고객센터 메뉴 → 브라우저 뒤로가기 → 홈 | PASS | PASS |

최종 공개 검증은 **10/10 PASS**, 각 폭 한 context다. [원본 JSON](public-readonly.json), [검증 스크립트](public-readonly.mjs). 오류와 회복 화면은 [390 오류](catalog-error-390.png)/[회복](catalog-recovered-390.png), [1440 오류](catalog-error-1440.png)/[회복](catalog-recovered-1440.png)이다. pageerrors 0, 외부 요청 시도 0, 도메인 POST/PUT/PATCH/DELETE 0. mock 성공 응답을 쓰지 않고 실패시 GET만 중단한 후 중단을 제거했다. 재시도는 실제 API 응답이다.

관리자 ActionMenu의 과거 1440 intermittent FAIL은 첫 pointer와 post-goBack keyboard를 같은 assertion으로 묶어 어느 단계가 실패했는지 판별하지 못했다. 이번에는 기존 첫 문의 행 하나를 각 폭의 동일 로그인 context에서 읽어 `initial-pointer → 상세 → goBack`, `post-back-keyboard → 상세 → goBack`, `post-second-back-pointer → 상세 → goBack`를 분리했다. 1440 4회×3단계=12/12, 390 2회×3단계=6/6, 합계 **18/18 PASS**. [단계·신뢰 입력·좌표·스크롤 기록](admin-history-readonly.json), [스크립트](admin-history-readonly.mjs).

이는 **이번 행·context·횟수에서 재현되지 않았다는 bounded 결과**다. 과거 FAIL을 PASS로 치환하거나 모든 행의 간헐적 실패가 수정되었다고 주장하지 않는다. `ActionMenu.tsx`는 메뉴를 portal로 렌더링하고 effect에서 위치를 계산하며 capture scroll/resize에 닫힌다. 스크롤 경쟁은 가능한 진단 가설이나 과거 기록으로 인과를 확정할 수 없다. 현 재현 근거로 모든 관리자 화면에 쓰이는 공유 ActionMenu의 제품 수정은 권고하지 않는다. 원래 문의 ID, 동시 스크롤, 모든 viewport 경계에서의 재현은 NOT_RUN이다. Escape가 메뉴를 닫는다고 주장하지 않는다.

관리자 검증은 승인된 generated lab `.env`의 격리 marker·APP_URL·installer auth 두 값을 프로세스 안에서만 읽었다. own native login을 각 폭 한 번 실행하고 발급된 own token만 native logout **200**, 같은 token의 `/api/auth/user` 재조회 **401**을 두 context 모두 확인했다. token·계정 값·연락처·응답 본문은 로그/화면/파일로 저장하지 않았다. fixture·역할·계정·상품·문의·Page·DB·서비스·다른 token의 변경은 없다. pageerrors/차단된 외부 요청 모두 0.

처음 공개 검증 harness는 `trip-card`/`campaign-card` 다중 locator를 `.first()` 없이 기다려 strict-mode 실패했고 390의 접힌 filter-reset을 바로 누르려 했다. 해당 실행을 중단하고 locator와 모바일 필터 열기만 정정했다. 처음 두 strict 실패와 중단으로 인한 후속 실패 원본은 [attempt1-harness](attempt1-harness/public-readonly.json)에 유지한다. 제품 결함으로 판정하지 않는다. 수정한 harness의 완주 실행이 최종 10/10이다.

served engine·여행 IIFE/CSS를 응답 body SHA256으로 source와 비교하여 각 폭 모두 일치했다. [자산 대응표](asset-binding.json): engine `738ee97c6eebc33bc75d29f24397daabb54f124bede254689ca68a18fdb64a0b`, IIFE `8941442d1a921c2f366063359e39f79ed3a719bce77a174b6c0aa5657b691d63`, CSS `8b372bcc681e6b78fbac301ddb274e75703f3e50a9e76d0472243c23c571d207`.

공개 screenshot의 email pattern은 DOM에서 마스킹 후 OCR 검사한다. 전체 텍스트·JSON·스크립트와 PNG OCR의 email/token pattern 및 승인된 installer 값에 대한 exact scan 결과는 [개인정보 검사](privacy-manifest.json), 전체 파일 SHA256은 [manifest](sha256-manifest.json)에 있다. 개인정보·인증정보 원문은 포함하지 않는다.

이 결과는 native subagent의 독립 잔여 UI 확인이다. 신규 업무 DB 저장/동시성/재기동은 이 검증에서 NOT_RUN이며 lead가 별도 증거를 인수한다. hosted CI, 공식 Validation, Git 통합, 운영 배포 PASS를 뜻하지 않는다. Commit/push/integration은 lead가 소유한다.
