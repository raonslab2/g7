# 고정 소스 공개 브라우저 차이 검증

**공개 브라우저 범위: 16/16 PASS, exit 0.** 비운영 RAON Travel Lab의 기존 구현을 인증 없는 Chromium 브라우저로 재검증했다. 제품 수정·로그인·토큰 사용·DB 접근·fixture 쓰기·서비스 재시작·Git 게시·배포는 하지 않았다. 운영·실고객 납품 또는 전체 릴리스 PASS를 의미하지 않는다.

검증 소스는 `e1c765d3d06b4d0e1cdd39ea32b85549de171745`이고, 원 독립 검증 소스 `5783e6ba124061bdfae639cdaf9b1c14a83cdf03`와 `app routes resources public modules templates config lang bootstrap` 차이는 0개다. 실행 전후 현재 worktree의 해당 경로도 고정 소스와 차이 0개였다. 실행은 2026-10-10 **14:37:40.155–14:38:57.302 UTC** / **23:37:40–23:38:57 KST**에 이루어졌다.

대상은 승인된 비운영 loopback `http://127.0.0.1:18871`뿐이다. 외부 HTTP와 GET/HEAD 외 요청은 차단하도록 구성했고 실제 차단 대상 요청은 0개였다. 새 anonymous context 2개, viewport 390×1000(touch/tap)와 1440×1000(mouse/click), Playwright 1.60.0, Chromium 156.0.8078.4를 사용했다. pageerrors 0, mutating HTTP 0이다. 실패 통제는 폭당 catalog GET 2회를 abort하며 성공 응답을 만들거나 대체하지 않았다.

| 검증 | 390 | 1440 |
|---|---|---|
| Core engine·travel IIFE/CSS·admin IIFE/CSS 실제 HTTP 200 body와 고정 Git SHA256 동일 | PASS 5/5 | PASS 5/5 |
| 실제 홈·합성 상품과 가로 넘침 없음 | PASS | PASS |
| 실제 메뉴 → 기획전 → 필터된 catalog → 상품/일정/비운영 고지 → browser back | PASS | PASS |
| 실제 무결과 검색 → Reset → 상품 회복 | PASS | PASS |
| 실패한 Retry → 오류 유지·상품 0·reload 없음 | PASS | PASS |
| 통제 제거 후 Retry → 실제 native 200·상품 표시·catalog 오류 영역 숨김·reload 없음 | PASS | PASS |
| 공개 Help → browser back | PASS | PASS |
| 공개 GET/HEAD 경계·runtime pageerror 없음 | PASS | PASS |

실패 통제군과 성공 통제군의 `performance.timeOrigin`과 window marker는 동일했다. 성공 후 실제 catalog 카드 8개와 오류 영역 숨김을 확인했다. **관찰 사항:** 이전 실패의 `Failed to fetch` toast 2개는 회복 직후 화면에 잠시 남았다. 이는 원 CLAUDE 보고서에도 남아 있는 관찰 사항이다. catalog 오류 영역이 HTTP 200 이후 남는 결함은 이번 공개 Retry에서 재현되지 않았다. toast 소멸 시간을 별도로 측정하거나 제품을 수정하지 않았다.

실제 무결과 검색은 query 기반 검색 0건이다. 원 검증의 실제 빈 **기획전 catalog** 또는 기본 native **Page 문서 6개**와 혼동하지 않는다. admin 자산의 GET/hash 비교는 새로운 관리자 UI 검증이 아니다.

원 [CLAUDE 브라우저 보고서](../../W04_RETRY_BROWSER_CLOSURE.md)의 native Page Retry, readonly/self 권한, 실제 빈 기획전, 인증 문의/지원·관리자 동선은 그대로 인수한다. 해당 원본의 1440 screenshot guard FAIL, ActionMenu intermittent FAIL, token-ledger 재조회 NOT_RUN, supplied-token BLOCKED와 기타 한계를 변경하지 않았다. 이번 scope에서 admin/Page 인증, MySQL 경쟁·중복·영속성·재시작·복원, 전체 14행 계약, SEO/cache, layout-editor는 **NOT_RUN**이다. 공식 Validation 영수증과 hosted CI 또는 통합 승인을 부여하지 않는다.

화면은 새로 촬영한 14개다. input/textarea를 마스킹했고 DOM email/phone 패턴을 제거한 후 촬영했다. [모바일 홈](home-390.png), [데스크톱 상품](product-1440.png), [모바일 실패 Retry](catalog-retry-still-error-390.png), [데스크톱 회복](catalog-recovered-1440.png), [모바일 무결과](empty-search-390.png)를 제공한다. 모바일 홈, 모바일 실패 Retry, 데스크톱 회복 화면을 육안 확인했고 전체 14 PNG에 영어 OCR의 email/phone/JWT/Bearer-value 패턴 검사를 적용했다. private credential 값을 읽지 않았으므로 모르는 정확한 secret 값까지 검사했다고 주장하지 않는다.

정확한 실행 결과·시간·요청·source/served 해시는 [public-delta.json](public-delta.json), 명령 로그는 [execution.log](execution.log), 실행 스크립트는 [public-delta.mjs](public-delta.mjs), 공개 증거 privacy 검사 결과는 [privacy-scan.json](privacy-scan.json)이다. 각 파일 SHA256은 [MANIFEST.sha256](MANIFEST.sha256)에 기록한다.

재현은 기존 [비운영 설치 절차](../../W04_FRESH_INSTALL_RECIPE.md)와 격리 지침에 따라 **본인 소유 비운영 loopback**이 같은 고정 소스를 제공할 때만 수행한다. 이 스크립트는 실행/설치/DB를 자체 생성하지 않는다. 기존 도구 기본값은 `PLAYWRIGHT_MODULE=/tmp/w04-4d7d-pw/node_modules/playwright/index.mjs`, `CHROMIUM_EXECUTABLE=/home/ubuntu/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome`, `TRAVEL_LAB_BASE_URL=http://127.0.0.1:18871`이다. 새 격리 실행 환경에서는 Playwright 1.60.0 및 해당 Chromium을 설치하고 이 세 환경변수로 본인 소유 도구와 loopback 경로를 지정한다. URL은 credential 없는 HTTP(S) origin만 허용하며 hostname은 `127.0.0.1`, `localhost`, `[::1]`로 제한한다. 페이지 요청은 같은 origin GET/HEAD만 허용하고 asset GET은 redirect를 따르지 않는다. 패키지 hash와 source/served 바인딩을 다시 확인한다. 운영 환경을 이 URL로 연결하지 않는다.

위 환경변수 설정과 redirect 방어는 실제 16개 검증 실행 **이후** 재현성 보완으로 추가했다. 실제 실행 당시 [harness 원본](executed-public-delta.mjs)을 동일 SHA256으로 보존했다. 실행 당시 harness SHA256은 `39ed51d3549cebbcee71b9b6a6869534417b41bdf62c6654c5c3171aff5e64a7`이다. 설정 보완 후에는 `node --check docs/symphony/final-gate-recovery/browser/public-delta.mjs` **exit 0**만 실행했다([로그](syntax-check.log)). 브라우저 결과와 원 captures는 다시 실행하거나 덮어쓰지 않았다. 이전 검증은 동일 기본 도구/URL을 사용했고 10개 asset 모두 직접 200이었다.

실행 명령은 저장소 root 기준이다.

```sh
node docs/symphony/final-gate-recovery/browser/public-delta.mjs > docs/symphony/final-gate-recovery/browser/execution.log 2>&1
python3 docs/symphony/final-gate-recovery/browser/privacy-scan.py
node --check docs/symphony/final-gate-recovery/browser/public-delta.mjs
```

첫 명령은 이미 존재하는 증거를 덮어쓰므로 재현 시 별도 출력 디렉터리로 변경하여 원 기록을 보존한다. 최종 publication scan은 `python3 docs/symphony/final-gate-recovery/browser/privacy-scan.py --reuse-unchanged-ocr`로 새 텍스트 파일을 검사하고 hash가 같은 기존 14 PNG의 완료 OCR 결과만 재사용했다. **21개 파일 PASS, 패턴 검출 0, exit 0**이다. 원 독립 검증 및 부정 결과 파일은 수정하지 않았다. Git 통합은 총괄이 수행한다.
