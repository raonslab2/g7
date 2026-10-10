# W04 fixed1052 독립 비작성자 최종 브라우저 검증

**지정된 브라우저 범위 PASS. 전체14개 계약은 NOT_RUN 항목을 포함하며 공식 Validation/CI PASS가 아니다.** source1052에서390/1440 실제 고객→관리자→고객 여정, 응답 유실 복구, 지원 회귀를 새로 실행했다. MOBILE-01은 현실적인 긴 상품명·2개 품목·5개 상태가 섞인 실제 문의 목록에서 두 너비 모두 문서/카드/텍스트 가로 넘침 없이 통과했다. 이번 실행에서 새 P1/P2 또는 mobile 제품 FAIL은 발견하지 않았다. native 관리자 상품 **생성**은 기존 active category가 없어 BLOCKED이며 API fixture 준비를 UI 생성 PASS로 계산하지 않는다.

요청 `req_09a99cdcdb1e4e12ba36aa8811f46d92`, parent req81, work `work-20261009-g7-symphony-max-child-c7ae42d1`. 검증 대상은 `origin/feat/g7-travel-lab-c7ae42d1`에서 fetch 후 detached checkout한 **1052e3fb4bc4cccabb51b8c538116c78655f345b**, tree **f18fa2056a031353c889785d768f87824e3083f5**다. 기본 배치6853은 실행 대상이 아니다. 모든 제품 브라우저 실행 전후 actual HEAD는1052였고 최종 커밋은 이 소스의 증거 전용 로컬 자손이다. 커밋은 `git log -1 --format=%H`로 식별하며 push/merge/deploy/official grandchildren은0회다.

## 실행 범위와 소스 결합

root/module/template AGENTS, 관련 인증·액션 navigation·반응형·E2E 가이드, SCORE/CAPACITY/WAVE_STATUS, 이전 [W04_BROWSER_RECHECK.md](W04_BROWSER_RECHECK.md) 전체와 `tests/scenarios/travel-lab.yaml`의14개 시나리오/effects/axes를 읽었다. 허용된 parent installed core/module/template/public 자산과 route/hook cache는 읽기·해시 비교만 했다. parent 서비스/파일/설정/스키마, seed 또는 다른 검증자 fixture, Provider/config/cost/grants를 수정하지 않았다. parent DB env/SQL을 읽지 않았다. 제품 수정0이며 버전/manifest 변경0이다.

실제 preview `http://127.0.0.1:18871`, source1052 installed runtime, Node22 및 자체 checkout의 Playwright headless Chromium/ko-KR. 인증/admin/support/recovery는390×1000 및1440×1000, 공개 검증은390×844 및1440×1000이다. 390은 isMobile/hasTouch이고 실제 tap,1440은 mouse click, 문의 카드는 별도 keyboard Enter를 실행했다. Chromium emulation 결과이며 물리적 모바일 기기 검증은 아니다.

허용된 fresh3role access를 guarded process 안에서만 읽었다. 파일0600/상위0700, sourceSHA/isolatedDB/loopback/유효 만료를 확인했다. native 로그인 UI에서 발급된 모든 토큰을 own0700/0600 ignored ledger에 먼저 기록했다. 제공 handoff bearer는 유지했다. 비밀값/원문 연락처/UUID/SQL은 증거에 저장하지 않는다.

[source-binding.json](evidence/W04_FINAL_BROWSER/source-binding.json)은 독립 native helper가 전후 열거했다. 실행 경로4363개가 전후 모두 Git1052와 일치한다: core1335, travel module105/template104, ecommerce1178, board338, page84, admin template1212, public/build7. runtime 추가 파일0, HTTP200으로 실제 받은11개 자산 모두 fixed Git bytes와 일치하고 전후 동일하다. docs/tests/tooling 제외 목록은 별도 보존했다. 설치되지 않은 optional `sirsoft-basic`463개는 missing이며 broad `source_all_equal=false`를 유지한다. required active runtime binding은 PASS다.

travel JS SHA256 `9cc99d8d8db0161cfc5f537531eb16f00d1a7ca272fd0b31824823185f46b390`, CSS SHA256 `e340b3bfcf22be2ef56f7d11a8f9bf2fc3563439f908628ab044931b463dce66`. core/native component 자산도 같은 전후 결합에 포함된다. route/hook cache는 이름/크기/해시만 읽었고 전후 바이트가 동일했다. credential-bearing cache 전체 내용을 출력하지 않았으며 cache 의미/등록 상태까지 검증했다는 뜻은 아니다.

## 새 actual UI/HTTP 결과

PASS는 아래 관찰 범위에 한정된다. fixture/API 준비, cleanup, 후속 실행을 독립 테스트 총수에 중복 합산하지 않는다.

| 범위 |390|1440| 새 실행 증거 |
|---|---|---|---|
|홈/category/theme/한국어 keyword/date/budget/search reset/4 sorts/empty/error/loading/retry/navigation|PASS|PASS|[public-results](evidence/W04_FINAL_BROWSER/public-results.json), [detail/soldout 후속](evidence/W04_FINAL_BROWSER/public-final-detail-soldout.json); 폭당17개 고유 공개 체크, 총34 |
|13개 자체 결과의12+1 pagination, Next/page2/reload/query 유지|PASS|PASS|own native API products56–68 준비, 실제 guest UI 탐색; API 준비는 관리자 UI 생성으로 계산하지 않음 |
|native 관리자 product/option 새 생성|BLOCKED|BLOCKED|기존24개 category 모두 inactive, native category tree empty. 허용 범위 밖 category/policy 생성 없이 중단, 저장된 상품0 |
|자체 상품 숫자 edit→price/stock 저장, 여행 metadata/일정/publish 등록, 날짜/옵션 출발편 저장/고객 표시|PASS|PASS|API로 준비한 own54/55를 native admin UI로 편집/등록;13000/9000 옵션, focus/fill/blur 및 실제 상태/응답 대기 |
|정상 홈→카테고리→테마→Korean search→detail/date/people/server estimate→cart→문의201|PASS|PASS|[journey](evidence/W04_FINAL_BROWSER/journey.json), 실제 cart201, 증가3/감소2/삭제 취소,2×13000=26000, client price 미전송; inquiry103/104 |
|native admin 상세/note/UNDER_REVIEW/TEST_ACCEPTED→고객 logout/relogin/status/cancel|PASS|PASS|두 실제 여정6단계. 저장 이벤트 TEST_INQUIRY→UNDER_REVIEW→TEST_ACCEPTED→CANCELLED, snapshot/API readback; 반복취소200·이벤트 추가0 |
|cart remove confirm/empty/readd, own quantity0/-1/1.5/999/date/price tamper 및 actor gates|PASS|PASS|[access-decline](evidence/W04_FINAL_BROWSER/access-decline.json); quantity422, foreign cart PATCH/DELETE404, guest401/member-admin403, quantity unchanged |
|가격 변조422→정상 native retry201/server13000, 사용 key의 changed body409, 타인 inquiry read/cancel404|PASS|PASS|native UI dispatch 뒤 own HTTP body만 통제해 real server에 전달; fake422/201 응답 없음 |
|native admin 목록 ActionMenu/detail/DECLINED→고객 reload/terminal guard|PASS|PASS|129/130. 관리자 버튼 ‘거절’, 고객 표시 ‘진행 어려움’; accept409, DECLINED 강제 cancel 안 함 |
|실제 committed201 응답만 유실→즉시 또는 reload retry200/sameID/key/body|PASS|PASS|[recovery 원본](evidence/W04_FINAL_BROWSER/recovery.json), [1440 후속](evidence/W04_FINAL_BROWSER/recovery-1440-final.json); upstream response201 확인 후 abort |
|commit 전 abort→changed contact/newkey201; sessionStorage throw→memory retry200|PASS|PASS|새 intent의 key/body 변경, 실제 quotaHits3씩. optional localStorage fallback은 NOT_RUN |
|logout/account change pending key/body/contact 제거, stale pending 재주입 거부, own saved inquiry 재로그인 읽기|PASS|PASS|[390 owner 후속](evidence/W04_FINAL_BROWSER/recovery-390-owner-final.json),1440 후속. live UUID 원문은 저장하지 않고 hash/boolean만 보존 |
|native notice/FAQ create/edit/public read, private create/edit422 retention/cancel/save/admin answer/foreign404|PASS|PASS|[support](evidence/W04_FINAL_BROWSER/support.json); 폭당6개 체크, posts63–68, cancel0PATCH, answer201. browser maxLength 우회를 위한 own overlength PATCH를 real server로 전달해422 확인 |
|문의 목록 문서/모든 카드/내부 텍스트, full title/ID/status/date/amount, Enter와 tap/click 상세|PASS|PASS|[mobile 검사](evidence/W04_FINAL_BROWSER/mobile-inspection-final.json), [390 geometry](evidence/W04_FINAL_BROWSER/mobile-geometry-390.json), [1440 geometry](evidence/W04_FINAL_BROWSER/mobile-geometry-1440.json) |

MOBILE-01을 위해 own UI로2품목 문의118/119/124/125/126을 새로 만들고 native admin 상태 전이를 실행했다. 최종 측정 각 페이지10개 카드에 TEST_INQUIRY/UNDER_REVIEW/TEST_ACCEPTED/DECLINED/CANCELLED 모두 존재한다. 390의 `innerWidth/document/body=390`, 카드 left16/right374/width358;1440은 모두1440, 카드546폭의2열이다. 모든 card/descendant/text Range의 좌우 경계를 실제 card bounds와 비교했고 full title은 실제 API 문자열과 동일했다. lineClamp none, title scrollHeight=clientHeight, ellipsis 없음, 넘친 내용을 overflow hidden/clip으로 감춘 ancestor 없음. 날짜는 API created_at의 실제16자리 값, ID/status/금액도 서버 행과 대조했다. 두 너비 Enter와 실제 tap/click은 exact inquiry126 상세로 이동했다.

[390 실제 목록](evidence/W04_FINAL_BROWSER/mobile-final-list-390.png), [1440 실제 목록](evidence/W04_FINAL_BROWSER/mobile-final-list-1440.png)은 응답/card count/loading 종료/networkidle/두 animation frame 후 캡처했다. mobile fixed bottom navigation이 full-page 이미지의 중간 행 일부에 겹치는 것은 캡처 특성이다. 이 한 이미지에서 모든 금액이 동시에 보인다고 주장하지 않으며 DOM 전체 text/card geometry와 native 상세 이동 증거를 함께 사용한다.

##14개 계약/effects/axes를 유지한 판정

[required-matrix.json](evidence/W04_FINAL_BROWSER/required-matrix.json)에 원본14개 시나리오의 action/effects,9개 axes의 모든 value,5개 cross-product 정책을 그대로 보존했다. 원본 contract inputSHA6853은 intake provenance이며 실제 실행1052와 구분한다. 각 effect와390/1440 판정을 개별 기록하고 cart/intake 단계 범위를 명시했다. 아래 일부 PASS 효과가 전체 미실행 효과를 대신하지 않는다.

|전체 계약|390|1440|남은 효과/범위|
|---|---|---|---|
|TR-CATALOG-001|PASS|PASS|5개 공개 catalog 효과 실제 실행|
|TR-CART-001|NOT_RUN|NOT_RUN|UI/cart 가격 PASS; nontravel 제외/order-payment SQL 미실행|
|TR-INQUIRY-001|NOT_RUN|NOT_RUN|201/계산 snapshot/API persistence/audit PASS; source item 변경 후 immutable 확인, 예약/transaction 원자성/dispatch SQL 미실행|
|TR-IDEMPOTENCY-001|NOT_RUN|NOT_RUN|sameID/key/body 및 이벤트 중복 없음 관찰; upstream/retry 금액 비교·item cardinality·독립 SQL reserved 불변 미실행|
|TR-IDEMPOTENCY-002|NOT_RUN|NOT_RUN|changed body409 PASS; 즉시 전체 inquiry/cart/inventory 전후 감사 미실행|
|TR-CONCURRENCY-001|NOT_RUN|NOT_RUN|독립 DB last-seat race는 별도 검증자 소유|
|TR-ADMIN-001|PASS|PASS|native allowed transitions/API actor events/durable status/test-only labels|
|TR-RELEASE-001|NOT_RUN|NOT_RUN|cancel/decline/repeated cancel/final API reserved0 PASS; release-once/stock invariance SQL 미실행|
|TR-ACCESS-001|NOT_RUN|NOT_RUN|native401/403/404 PASS; 전체 actor×entrypoint/state/자체 private attachment 미실행|
|TR-SUPPORT-001|NOT_RUN|NOT_RUN|native persistence/private scope/published help PASS; mail/SMS dispatch SQL 미실행|
|TR-RESTART-001|NOT_RUN|NOT_RUN|relogin 관찰 PASS; parent process restart 금지|
|TR-MIGRATION-001|NOT_RUN|NOT_RUN|parent schema/seed/rollback mutation 금지|
|TR-UI-001|NOT_RUN|NOT_RUN|bounded journey/MOBILE-01 PASS; 모든 customer/admin screen×loading/empty/error/retry 미완료|
|TR-REGRESSION-001|NOT_RUN|NOT_RUN|full install/core/commerce/board/build/typecheck/hosted/native CI 미실행|

past/inactive/invalid-date의 모든 cart/intake 조합, option price 변경 후 cart submit, capacity 축소, simultaneous samekey/lastseat race, preview restart 등 미완료 축은 명시적으로 NOT_RUN이다. axes의 PASS는 value별 bounded 관찰이며 모든 cross product는 NOT_RUN이다. editor4 datasource previews, private attachment, localStorage quota도 NOT_RUN. own SQL은 NOT_RUN, canonical Validation receipt/CI는 NOT_RUN이며 author140/template·parent 테스트 수 또는 다른 official/native Request 결과를 가져와 PASS로 계산하지 않는다.

## 초기 실패와 원본598 FAIL 분리

원본 [W04_BROWSER_RECHECK.md](W04_BROWSER_RECHECK.md) 및 그 증거는 수정하지 않았다. source598의390 목록412px, 정리 후424px **FAIL은 역사적 최종 negative로 유지**한다. 이번1052 실행은 새 fixture/실제 browser/전후 installed hash의 별도 결과이며 old PNG 복사0이다. 이전 요청의 토큰/fixture 정리 BLOCKED도 이번 결과로 해결됐다고 주장하지 않는다.

이번 초기 harness 실패도 삭제하지 않았다. active category 선택 가정은 UI create BLOCKED로 판정했다. catalogue loading 전 Next locator, stale capacity/return-date Input state, fixture itinerary ‘1일차’ 대신 ‘합성 첫날’, 미완료 soldout departure 준비, logout 직후 core bootstrap 전 접근, 관리자 ‘거절’ 대 고객 ‘진행 어려움’ selector는 원본 FAIL JSON과 구체적 보정 후속을 함께 보존했다. 최종 read-only actor 확인의 numeric auth id 가정도 실제 UUID resource 규약으로 좁혀 실패를 보존했다. response-loss 중 real429는 고정 native10submit/min 제한이다. 이후 own request-specific65초 window 예산, 실제 요청 응답/DOM count/focus/blur/global field 값 대기로 필요한 실패 단계만 실행했다. source/config/throttle를 바꾸거나 동일한 전체 검사를 반복하지 않았다. [commands.json](evidence/W04_FINAL_BROWSER/commands.json)에 실제 exit/PASS/FAIL/NOT_RUN/BLOCKED를 구분했다.

## 자체 정리, 개인정보, 증거 인계

[cleanup-counts.json](evidence/W04_FINAL_BROWSER/cleanup-counts.json), [cleanup](evidence/W04_FINAL_BROWSER/cleanup.json), [owner-audit](evidence/W04_FINAL_BROWSER/owner-audit.json)은 exact own IDs의 native 재조회다. 문의20개는17 CANCELLED/3 DECLINED(119/129/130),15개 상품54–68 hidden/unpublished/public404,17개 출발편 inactive/reserved0,cart ledger29개 및 member/other_member current cart0,지원 posts6개 deleted/own question404를 확인했다. DECLINED는 합법적 terminal이며 cancel409를 강제로 변경하지 않았다. native cleanup capacity 검증을 위해 own54/55의 두번째 option stock만 UI로6에 복원한 뒤 비활성화했다. category/policy 변경0, 다른 owner/seed 변경0이다. 별도 [actor-audit](evidence/W04_FINAL_BROWSER/actor-audit.json)은 두 정상 문의의 owner→같은 별도 admin actor→owner 순서와 from/to status를 native API로 확인했다. auth resource는 UUID만 노출하므로 admin UUID와 numeric event actorID의 독립 매핑은 NOT_RUN이며 user-management/SQL로 범위를 넓히지 않았다.

새 UI 로그인 토큰55개 모두 distinct ledger에 기록했고 exact native logout200/401 후 `/api/auth/user`401을55개 재조회했다. 각 token은 SHA256만 증거에 남겼다. supplied3handoff tokens는 native auth200 유지 확인했다. early unrecorded0이며 own private ledger는 확인 후 제거했다. broad auth/token/account 수정은 하지 않았다.

모든 PNG는 이번 실행의 actual browser frame이다. input/textarea/email/contact/UUID 마스크를 사용했다. 초기 OCR에서 native-answer1440의 늦게 렌더링된 이메일 누락1장을 발견했고, 검사 중 그 원본을 표시한 실수도 있었다. **그 원본 PNG는 삭제해 커밋에서 제외**했으며 [sanitized 실패](evidence/W04_FINAL_BROWSER/privacy-scan-initial-failed.json)와 파일 SHA256/제외 사유만 남겼다. 후속 source-only mask helper에 bounded DOM text settle을 추가했지만 이미 실행한 화면을 이 새 helper로 재캡처했다고 주장하지 않는다. 초기 whole OCR process exit143는 NOT_RUN으로 보존하고 이후9장 단위 durable screening으로 나눴다.

대표 화면은 직접 육안 확인했다: my-requests390, ordinary-inquiry390, owner-answer390, admin-accepted1440, 최종 mobile lists390/1440 및 최종 회복/지원 validation 화면. 독립 helper도 최종 목록2장을 별도 육안 확인했다. 모든 프레임을 육안 확인했다는 주장은 없다. [privacy-scan-final.json](evidence/W04_FINAL_BROWSER/privacy-scan-final.json)의 exact handoff credential/text/token-pattern 및 전체 retained PNG OCR 검사와 [sha256-manifest.json](evidence/W04_FINAL_BROWSER/sha256-manifest.json)을 함께 읽어야 한다. OCR은 pixel 수준 privacy 보증을 대신하지 않는다. generated .env/vendor/node_modules/private ledger/log/snapshot은 Git에 포함하지 않았다.

주요 실행 순서는 native API fixture 준비→native UI edit/등록→공개/journey/support/recovery→multiitem geometry/access→own cleanup→API owner audit→source after→개인정보/패키징/고정 diff review다. 만료된 access 또는 이미 정리된 fixture로 `*.mjs` glob을 재실행하면 안 된다. [provenance](evidence/W04_FINAL_BROWSER/provenance.json)에 read-only old script 출처와 final own source hash, [timings](evidence/W04_FINAL_BROWSER/timings.json)에 process-recorded elapsed와 interruption 한계를 보존했다.

supported native helper1개 `/root/source_public_review`, peak root+helper2로 disjoint read-only source/asset 비교와 최종 보고/증거 review를 맡겼다. helper Git publication0, official child0, 별도PC0, capacity 변경0. 이 로컬 증거 커밋은 parent lead가 소비할 검토 결과이며 제품 수정·canonical Validation·배포 결과를 포함하지 않는다.

최종 [독립 review 기록](evidence/W04_FINAL_BROWSER/independent-review.json)은 fixed staged tree `7c87709ca9ea1218e04a003a8161ced41ee0dee0`를 대상으로 했다. helper가182개 manifest bytes·177개 역사적 artifact 불변·55개 token401·106개 retained PNG 결합을 확인했다. P3 증거 과장 R1은 retry 금액 비교와 item 중복 효과를 NOT_RUN으로 좁혀 해결했으며 새 제품 P1/P2는 없었다. 이후 변경은 이 기록/보고 acknowledgment/검증 명령 메타데이터 및 manifest 재계산뿐이다.
