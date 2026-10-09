# W04 독립 비작성자 실제 브라우저 재검증

전체 결과 **FAIL**. 390px와 1440px에서 고객·관리자 실제 native 여정은 완료했지만, 390px `/travel/requests` 목록의 `scrollWidth=412`가 viewport390을 초과한다. 정리 후 다시 측정한 목록은424px로 넘침이 유지됐다. 기능 성공이 이 화면 결함이나 미실행 계약 효과를 상쇄하지 않는다. 이 검증은 공식 Validation PASS가 아니다.

검증 요청 `req_7e41235d7eeb44a0b4efe254bc678748`. 대상 [기존 PR2](https://github.com/raonslab2/g7/pull/2), 공개 브랜치 `feat/g7-travel-lab-c7ae42d1`. fetch 후 detached checkout의 실제 HEAD/FETCH_HEAD는 **598a89fff702d51c1405f1a5952d95ab1d2651f4**, tree **9e00273bdf18d6a713343755aac54f44a9b032b4**였다. 기본 HEAD6853은 실행 대상이 아니었다. 모든 제품 브라우저 실행은598에서 이루어졌다. 검증 결과 커밋은 이 SHA의 증거 전용 자손이며 제품 파일을 변경하지 않는다. 출력 커밋은 이 문서를 포함한 로컬 `verify/w04-browser-7e41235d`의 `git log -1 --format=%H`로 식별한다. push/merge/deploy 및 공식 child Request는 0회다.

root/module/template AGENTS, SCORE v4/WAVE/CAPACITY/RUNTIME, W04_UI_REPAIR와 비작성자 REVIEW, W04_SUPPORT_THROTTLE_REPAIR, W03_BROWSER_INTERRUPTION_INTAKE 및 전체 `tests/scenarios/travel-lab.yaml`을 읽었다. parent 소스는 열거·비교만 했다. root APP 환경·플랫폼 자격증명은 읽지 않았고 parent 파일·서비스·스키마·다른 검증자 fixture를 수정하지 않았다. 새 fixture는 이 검증자 전용 actor와 IDs만 사용했다.

## 실행과 고정 소스 결합

parent 제공 `http://127.0.0.1:18871`, isolatedAPP `req81_travel_lab`, HTTP workers4. Node v22.23.3, 자체 checkout의 `@playwright/test`와 bundled headless Chromium, ko-KR. 인증 시나리오는390×1000(hasTouch) 및1440×1000; 공개390 시나리오는390×844(isMobile/hasTouch). 모바일 공개 탐색과 최종 인증 로그인·로그아웃·navigation·관리자 Next는 실제 tap, desktop은 mouse click으로 실행했다. 초기 인증 단계390의 mouse 결과를 별도 touch 재검증으로 보완했다.

허용된 fresh native3role access만 테스트 프로세스 내부에서 읽고 파일0600/상위0700, sourceSHA, isolatedDB, loopback, 만료를 검증했다. native login UI와 실제 Bearer 토큰을 사용했다. 자격증명·연락처·UUID는 Git/로그/스크린샷에 보존하지 않았다. 테스트 access의 수명이 끝나면 새 접근을 요구하도록 실패하며 우회하지 않는다.

[source-binding.json](evidence/W04_BROWSER_RECHECK/source-binding.json)은 lead의198-file 선택 감사와 독립된 전체 열거다. travel module164 + template133 =297 tracked 경로와 실제 installed 디렉토리를 비교했고 추가 파일0. 실행 source/manifests/layouts/dist를 포함한290개가 일치한다. 전체297 중7개는 불일치하므로 `extension_all_equal=false`를 유지했다: module AGENTS/CHANGELOG/README, template AGENTS/README, author `w03-ui-boundary-repair.json` 및 `triage-ui-boundaries.mjs`. 실행 파일 불일치는 없다. app/routes/resources/bootstrap/app.php/config/app.php1700개 모두 일치하고 app/routes/resources 추가 파일0. native ecommerce/board/page의 src/layouts/dist/manifests1126개도 모두 일치한다. 전체 vendor·runtime state·설정 비밀 파일까지 감사했다는 뜻은 아니다.

실제 HTTP200 자산의 SHA256:

| 자산 | SHA256 | 결합 |
|---|---|---|
| core template-engine | `be2d01f3be637afd127f9c62c2fed35cc3de2cabd5da14a7734635ae1798a414` | fixed Git blob 일치 |
| travel JS | `9cc99d8d8db0161cfc5f537531eb16f00d1a7ca272fd0b31824823185f46b390` | fixed Git dist 일치 |
| travel CSS | `2750062e8f93d635d9f126f6249852fe370725e3d7abd77d6c09cf58c6507529` | fixed Git dist 일치 |
| font CSS | `ef54af5cfb27524385f24b7c9447f0cd62b009dff57ba52bc84c6d440974201a` | fixed native AssetCssUrlRewriter로 실제 URL 변환을 독립 재현, 일치 |
| module bundle JS | `a69dc60260eef16feb33b5433426d974164fcc5a9b15d3577a710a9212b9f2ce` | 실제 served hash 기록; 전체 결합 재빌드 미실행 |

font raw Git hash가 served hash와 다른 것은 실제 cache-version URL 변환이며 [secondary-assets.json](evidence/W04_BROWSER_RECHECK/secondary-assets.json)에서 순수 native 변환으로 재현했다. APP boot/env 접근이나 parent 쓰기는 하지 않았다.

## 실제 양폭 결과

아래 PASS는 해당 UI/HTTP 관찰 범위다. fixture setup·cleanup·재시도 행을 독립 테스트 총수로 합산하지 않는다.

| 범위 |390|1440| 실제 증거 |
|---|---|---|---|
| 홈 지역/테마/제주 검색, 날짜·가격 필터/정렬/초기화, 상세 일정/출발/인원, empty/error/loading/retry/navigation |PASS|PASS|public-results: 폭당17개 고유 공개 시나리오, 전체34PASS |
|13개 자체 상품의 >12 결과, 포인터 Next/page2/reload/query 유지 |PASS|PASS|41–53, 12+1 결과; fixture native adminAPI setup은 adminUIcreatePASS로 계산하지 않음 |
| KR FREE0원 배송정책→자체 상품/옵션 native 관리자 UI 생성 |PASS|PASS|policy4/5, product39/40. category23/24만 API setup |
| native 상품 숫자 edit 링크, 가격/stock 저장, 여행 metadata 등록, 출발편 native write/read/고객 표시 |PASS|PASS|13000/9000원 옵션; focus/fill/blur 후 Save. 초과capacity409, 과거/역전date422 |
| native admin Next와 숫자 상품 edit 실제 pointer hit |PASS|PASS|admin-pointer-departure, touch-persistence; 실제 출발 history date409 및 허용 capacity 저장200 |
| cart 증감·remove 취소/확인·empty, price 미전송, 서버 계산 문의201 |PASS|PASS|quantity2×13000=26000, forged price/total/amount422, 정상 native retry201 |
| admin native 목록/detail UNDER_REVIEW/note/TEST_ACCEPTED→owner logout/relogin/reload/cancel |PASS|PASS|inquiry91/92; 실제 감사 이벤트4개, 계산 snapshot, 반복취소200/이벤트 증가0 |
| admin native 목록 ActionMenu/detail/note/DECLINED→owner reload/terminal guards |PASS|PASS|99/100, native label ‘진행 어려움’, DECLINED, accept/cancel409 |
| upstream201 응답만 유실→동일key/body retry200, consumed-cart reload 복구 |PASS|PASS|실제 upstream 응답 후 abort; API·스냅샷·감사 이벤트 재조회 |
| upstream 전 abort→편집contact 새key201 |PASS|PASS|첫 요청 미전달, 수정 입력·새 intent 확인 |
| sessionStorage quota memory fallback |PASS|PASS|실제 quotaHits3씩, retry200 |
| live SDK UUID 대 stale datasource/account switch의 과거 pending/contact 제거 |PASS|PASS|native logout/other login, 타인cartempty, 기존 pending 재주입도 live owner로 제거; UUID 원문 없음 |
| native notice/FAQ create/edit/public read; private question create/edit/422 입력유지/cancel/answer/requery/otherowner404 |PASS|PASS|지원 최종 폭당6개; actual201/200/422/404, cancel0PATCH |
| guest 보호화면 native login redirect, guestadmin401/memberadmin403, 외국 owner cart/question/inquiry404 |PASS|PASS|own HTTP tamper는 UI 실행 뒤 별도 security probe로 표시 |
| 문의 목록 가로 넘침 |**FAIL**|PASS|390→412px, 최종424px; my-requests-390.png와 overflow-reproducer |
| 선택된 native editor4 datasource previews |NOT_RUN|NOT_RUN|catalog/candidates/inquiries/inquiry 각각 렌더링 미완료. JSON editor entry read-only 관찰만으로 PASS 판정하지 않음 |
| optional localStorage quota fallback |NOT_RUN|NOT_RUN|원본 recovery PASS marker는 response-loss 성공을 포함하나 quotaHits0. storage fallback 증거는 아님 |
| private attachment 접근 |NOT_RUN|NOT_RUN|travel 질문 UI에 자체 첨부를 생성하지 않았음. generic board attachment gate까지 통과했다는 주장 없음 |

[my-requests-390.png](evidence/W04_BROWSER_RECHECK/my-requests-390.png)은 실제 카드가 viewport를 넘치는 원본 마스킹 화면이다. 재현은 native member login→`/travel/requests`→응답 렌더링 대기→`document.documentElement.scrollWidth` 측정이다. 제품 수정은 하지 않았다. 실제 pageerror는 최종 여정/지원/복구와 공개 검사에서0이었다. console의 고의404/abort와 검증용422/409는 metrics에 보존하며 무조건 오류0으로 표시하지 않는다. 일부 중간 admin-next PNG는 실제 loading 프레임이고 최종 렌더링 증거는 `touch-admin-next-*`다.

## 원본 실패 보존과 범위 해석

attempt1 `req_52b83af0b2d1425094bb4b9e24edd166`은 INTERRUPTED이며 부분32PASS6FAIL4BLOCKED4NOT_RUN을 최종 결과로 사용하지 않았다. old checkout은 script/선별 JSON 읽기만 수행했다. selected6 MJS를 검토해 이 checkout에서598/access/UI에 맞게 수정했다. old scripts 실행0, old writes0, old PNG 복사0. 공개 script도 별도 read-only source reuse다. [provenance.json](evidence/W04_BROWSER_RECHECK/provenance.json)에 이전/현재 source 해시와 변경 이유를 기록했다.

원래 policy/critical admin/pointer/private-edit/isolation 실패는 실제 새 fixture/native UI로 재실행했다. 이번 시도의 초기 실패 JSON도 삭제하지 않았다. shipping selector 혼동, offscreen pointer hit 검사, native Input blur 누락, inquiry history가 있는 date변경409, empty title의 disabledSave, maxlength가 자른 입력, list ActionMenu portal, locale label ‘거절’ 대 ‘진행 어려움’, 초기 featured response 잘못 포착은 harness 오류로 분류했다. 보정 후 실제 응답/상태를 다시 검증했다. 마지막 locale assertion의 원본 access-decline FAIL은 `decline-followup`이 같은99/100에 대해 보완하며 삭제하지 않는다. 390 overflow는 harness 보정 후에도 남은 제품 FAIL이다. 실제 PATCH422는 native edit dispatch 후 자체 body의 controlled overlength tamper로 서버에 전달했고 실제422를 받았다; fake422 응답을 주입하지 않았다.

catalogue 원본 `priorBoundaryEvidence`의 오래된 basename은 스크립트에서 `catalogue-input-harness.json`으로 바로잡았다. 원본 JSON은 수정하지 않았으며 실제 own departure201/등록201의 초기 기록과 후속 native readback을 함께 읽어야 한다. 공개1440 hero initialPASS의 잘못 복사된 reason은 aggregation 문구만 교정하고 원본 initial execution을 유지했다.

전체14시나리오와 모든 effects/axes/cart/intake 단계는 [required-matrix.json](evidence/W04_BROWSER_RECHECK/required-matrix.json)에 원본 YAML과 함께 그대로 보존했다. 원본 contract inputSHA6853 메타데이터는 실제 testedHEAD598과 구분한다. 시나리오의 일부 효과만 검증했으면 전체 status를 NOT_RUN으로 유지한다.

| 전체 계약 시나리오 |390|1440| 남은 범위 |
|---|---|---|---|
|TR-CATALOG-001|PASS|PASS|실제 공개 UI/응답 |
|TR-CART-001|NOT_RUN|NOT_RUN|UI cart PASS; 비여행 제외/order-payment SQL 미실행 |
|TR-INQUIRY-001|NOT_RUN|NOT_RUN|201/가격/snapshot/audit PASS; 독립 SQL 예약 원자성/dispatch 감사 미실행 |
|TR-IDEMPOTENCY-001|NOT_RUN|NOT_RUN|동일ID/금액/감사 중복 없음 PASS; 독립 SQL 예약 불변 미실행 |
|TR-IDEMPOTENCY-002|NOT_RUN|NOT_RUN|changed-key body409 PASS; 즉시 전체 cart/inventory 전후 감사 미실행 |
|TR-CONCURRENCY-001|NOT_RUN|NOT_RUN|다른 검증자와 별도 actor; 독립 DB last-seat race 미실행 |
|TR-ADMIN-001|PASS|PASS|native allowed transition/audit/reload/test-only label |
|TR-RELEASE-001|NOT_RUN|NOT_RUN|취소/거절/반복/최종 reserved0 PASS; stock 불변/once SQL 미실행 |
|TR-ACCESS-001|NOT_RUN|NOT_RUN|native actor gates PASS; 자체 첨부 없음 |
|TR-SUPPORT-001|NOT_RUN|NOT_RUN|native persistence/owner/admin PASS; mail/SMS dispatch DB 미실행 |
|TR-RESTART-001|NOT_RUN|NOT_RUN|logout/relogin/reload PASS; parent process restart 금지 |
|TR-MIGRATION-001|NOT_RUN|NOT_RUN|parent schema/seed/rollback 변경 금지 |
|TR-UI-001|FAIL|PASS|mobile 목록 overflow |
|TR-REGRESSION-001|NOT_RUN|NOT_RUN|제품 full build/typecheck/regression 및 CI 미실행 |

실제 결제/예약 확정 UI나 API는 실행하지 않았으며 모든 문의의 test-only 안내/상태를 확인했다. SQL order/payment/외부발송 테이블까지 독립 감사했다고 주장하지 않는다. lead SQLite152/2469, template139, UI author75+8PASS는 인계 사실이고 이 검증의 실행 수에 합산하지 않는다. hosted CI0runs는 사용자 제공 상태로 NOT_RUN, canonical Validation unavailable은 BLOCKED다.

## 자체 fixture 정리와 개인정보

native admin/query로 정확한 자체 IDs만 정리했다. product39–53 총15개 hidden+unpublished,17개 departure inactive/reserved0. 문의24개 중22 CANCELLED,99/100은 의도적 DECLINED terminal로 닫혔다. native CANCELLED 전이409라 두 개를 강제 변경하지 않았다. member/other_member cart 둘 다0; 자체 cart ledger34 IDs를 재조회했다. posts18 softdeleted(공지6/FAQ6/private question6), 질문 owner404. policy2–5 네 개와 category21–24 네 개 inactive. 옵션 stock0/capacity4의 정리409는 자체 상품39/40의 옵션 재고만 native admin UI로 복원한 뒤 비활성화·예약0·게시중지를 재조회해 해결했다. 상품 숨김은 native API exact-own setup/cleanup 범위이고 admin UI 생성 증거와 구분한다.

초기 토큰 장부76개와 후속8개는 native logout 후 `/api/auth/user`401 재조회로 폐기를 확인했다. 최종 overflow 재현의 알려진 selector 실패1개와 정상 재현2개 토큰도 native401로 재조회했다. 확인된 폐기 합계는 **87개**이며 `overflow-token-count.json`에 추가3개를 기록했다. private0600 장부는 검증 후 제거하며 Git에 넣지 않는다. 초기 지원 검증 프로세스가 종료될 때 토큰6개를 보존하지 못해 그 폐기는 **BLOCKED**. 현재 권한에는 그 토큰만 열거하는 API가 없고 broad user/token 삭제나 account rotation은 수행하지 않았다. 이6개는 lead가 scope를 확인할 필요가 있는 남은 정리 항목이다.

attempt1의 정확한 owned 문의20개 CANCELLED, 상품2개 hidden/public404, posts6개 deleted는 새 admin actualGET으로28개 재조회했다. prior25cart/oldissuedtoken은 fresh distinct actor로 조회 불가하여 BLOCKED, prior10category 최종 requery는 NOT_RUN이다. 다른 검증자나 seed fixture는 수정하지 않았다.

실제 새 PNG만 보존한다. input/textarea/연락처/UUID 대상 visual mask를 적용했고 전체 이미지 contact sheets 및 공개25개 개별검토, 추가 최종 화면을 육안 확인했다. JSON parse/정확 credential 값/UUID/email/contact 검사와 Tesseract OCR을 수행했다. OCR은 육안 mask 검토의 보조다. 초기 OCR 환경의 과도한 OpenMP 지연을 자체 검증 프로세스만 중단하고 `OMP_THREAD_LIMIT=1`로 재실행했다; 그 미완료 기록도 NOT_RUN으로 보존했다. 과거100PNG를 가져와 맹목적으로 게시하지 않았다. 현재 PNG는121개이며 원본117개와 추가4개에 대해 OCR·visual 검토했다. 최종 report와 검증 코드도 정확 credential 값 검사에 포함했다. PNG/JSON/script/report 해시는 최종 `sha256-manifest.json`에 기록한다.

## 명령·시간·실행 수

`git fetch origin feat/g7-travel-lab-c7ae42d1`, `git checkout --detach 598a89fff702d51c1405f1a5952d95ab1d2651f4`, `npm ci --ignore-scripts --no-audit --no-fund`(약9초, exit0) 후 `node tests/W04_RECHECK/*.mjs`를 역할/fixture 순서대로 개별 실행했다. creation→catalogue→journey/recovery/support→public followups→cleanup→read-only audit 순서가 필요하며 만료·정리된 fixture로 전체 glob 재실행하면 안 된다. source binding은 `python3 tests/W04_RECHECK/source-binding.py`, packaging은 `python3 tests/W04_RECHECK/package-evidence.py`. 진단 sheet는 무시된 자체 testing 저장소에만 만들었다.

공개 initial/hero/pagination/soldout 실행시간은79.682+4.613+17.496+8.577=110.368초. final admin-pointer38.846초, access70.779초, decline-followup8.654초, touch47.305초, cleanup36.601초. 초기 admin/catalogue/journey/recovery/support는 프로세스 elapsed 필드가 없어 마지막 JSON mtime와 run timestamp의 근사치만 [timings.json](evidence/W04_BROWSER_RECHECK/timings.json)에 표시한다. 실패 stage도 완료 exit0인 harness가 있으므로 exit0을 전체PASS로 해석하지 않는다. public initial exit1, targeted followup exit0; 초기 지원 검증 프로세스 종료는 실패/정리 BLOCKED로 기록했다.

실제 native subagent는 **1개**(`/root/public_ui`), peak root+subagent2. 공개 read-only UI와 고정 script/manifest 독립 review만 맡겼다. root는 인증/admin/지원/복구/자체 cleanup을 맡았다. official child0, 별도PC0, capacity/config 변경0. APP workers4는 agent 수가 아니다. public review는34고유PASS/30manifest hashes/primary asset 결합을 확인하고1440 reason 문구P3를 지적해 수정했다. 이 내부 review도 공식 Validation을 부여하지 않는다.
