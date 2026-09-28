# GNUBOARD7 프로젝트 트러블슈팅

이 문서는 RAON Hub 운영에서 재발 가능성이 확인된 사례만 기록한다. 현재 기준은 main/runtime
`390cdc7a379e1f2b9c8e3991b241edcc59dbdd71`, `raonslab-product 0.4.0`이며 CASE 5~8은 source `raonslab-product 0.4.1` 변경과 함께 기록했다.

## CASE 1 — Native Page 배포 뒤 legacy route 404

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-001` / 2026-09-28 KST |
| symptom | `module:update` 성공 및 active route 파일 존재 뒤에도 `/info/*`, `/policy/*`, locale 호환 경로 14개가 404였다. 두 번째 delivery smoke는 전체 `380 PASS / 73 FAIL`이었다. |
| wrong initial assumption | route/controller 코드 또는 설정 파일 자체가 잘못됐거나 `module:update` 내부 route rebuild만으로 새 provider route가 항상 반영된다고 보았다. |
| actual cause | update 프로세스가 파일 교체 전에 이전 `ProductServiceProvider` class를 이미 로드했다. `ModuleManager::reloadModule()`은 `module.php`만 다시 읽고, 같은 PHP 프로세스의 route rebuild는 이미 로드된 provider class를 새 파일로 재선언하지 못해 compatibility route 없는 cache를 만들었다. |
| evidence | `app/Extension/ModuleManager.php`, `app/Support/RouteCacheHelper.php`, `modules/_bundled/raonslab-product/src/Providers/ProductServiceProvider.php`, `modules/_bundled/raonslab-product/src/routes/compatibility.php`; 실패 직후 route 부재와 최종 fresh-process `route:list` 14개 및 실제 301을 대조했다. |
| resolution | update 종료 뒤 각각 새 PHP 프로세스로 `/usr/bin/php8.3 artisan route:clear`, `/usr/bin/php8.3 artisan route:cache`를 실행하고 `g7-product-fpm.service`만 graceful reload한다. full smoke 전 `route:list` 14개와 실제 legacy GET 1개의 same-origin exact 301을 fail-fast로 확인한다. |
| prevention rule | root web route를 추가·변경한 module delivery는 update 프로세스의 cache 결과를 신뢰하지 않는다. fresh route lifecycle, 격리 FPM reload, CLI+HTTP preflight가 모두 통과하기 전 browser suite를 시작하지 않는다. 다른 G7/AI/nginx/queue service는 재시작하지 않는다. |
| related regression test | `modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`; `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`; `modules/_bundled/raonslab-product/tests/Feature/ProductLayerContractTest.php`; `tests/Feature/Extension/ExtensionRouteCacheInvalidationTest.php`; `tests/Feature/Api/Admin/ModuleUpdateTest.php` |
| related commit/request_id | feature `b59b45f49c1e13f31ecab4229678c79bac2295cf`; final main `390cdc7a379e1f2b9c8e3991b241edcc59dbdd71`; `req_83dcac7eb8cd454db7146f270f0cb1bd` |

## CASE 2 — canonical 검증의 false failure

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-002` / 2026-09-28 KST |
| symptom | normal human SPA 31개 검사에서 `link[rel=canonical]`이 없어 모두 실패했다. |
| wrong initial assumption | native Page 또는 product layout overlay가 모든 human DOM에 canonical element를 삽입해야 하며, 부재는 SEO 회귀라고 판단했다. |
| actual cause | G7의 authoritative canonical surface는 `SeoMiddleware`가 bot 요청에 반환하는 server-rendered HTML이다. 일반 사용자 요청은 SPA를 받고 human DOM canonical은 공식 계약이 아니다. |
| evidence | `docs/backend/seo-system.md`, `app/Seo/SeoMiddleware.php`, `app/Seo/SeoRenderer.php`, `resources/views/seo.blade.php`, `templates/_bundled/sirsoft-basic/layouts/page/show.json`; 동일 `/page/privacy`에서 human SPA canonical 없음과 Googlebot HTML의 exact same-origin canonical을 확인했다. |
| resolution | responsive human DOM 검증과 SEO 검증을 분리했다. human context는 Page UI·presentation·overflow를 검사하고, 저호출 bot check는 7개 Page의 HTML 200, `X-SEO-Cache`, same-origin 및 exact canonical을 검사한다. product/core에 duplicate canonical을 추가하지 않는다. |
| prevention rule | SEO acceptance는 구현이 소유한 authoritative response surface에서 검증한다. bot/server 계약을 human SPA DOM에 투영하거나 false failure를 제품 변경으로 보정하지 않는다. |
| related regression test | `modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`; `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`; `modules/_bundled/sirsoft-page/tests/Feature/User/PublicPageControllerTest.php`; `tests/Feature/Seo/SeoMiddlewareTest.php`; `tests/Feature/Seo/SeoPageRenderingTest.php` |
| related commit/request_id | feature `b59b45f49c1e13f31ecab4229678c79bac2295cf`; final main `390cdc7a379e1f2b9c8e3991b241edcc59dbdd71`; `req_83dcac7eb8cd454db7146f270f0cb1bd` |

## CASE 3 — Native Page rollback과 직렬 retry

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-003` / 2026-09-28 KST |
| symptom | 첫 delivery는 429, 다음 gate는 absolute `Location` 비교 실패, 두 번째 delivery는 legacy 404와 human canonical 실패를 냈다. 최종 delivery만 committed smoke `451 PASS / 0 FAIL`이었다. |
| wrong initial assumption | 각 smoke 실패를 곧바로 동일한 제품 결함으로 묶거나, 실패 뒤 재실행해 PASS를 만들 수 있다고 보았다. |
| actual cause | 첫 429는 viewport/page마다 31개 SPA context를 만든 smoke burst였다. Laravel의 absolute redirect는 정상이고 relative 문자열 비교가 테스트 결함이었다. 두 번째 legacy 404는 CASE 1의 실제 delivery lifecycle 결함이었지만 human canonical 부재는 CASE 2의 false assumption이었다. |
| evidence | context를 viewport당 하나로 줄인 `09719936`, absolute URL을 origin+pathname+query로 검증한 `571b934e`, route/SEO preflight를 추가한 `b59b45f4`; source rollback `6201e320`, `52677b3f`; 최종 main `390cdc7a`에서 route 14개와 smoke `451/0`. |
| resolution | acceptance 실패 즉시 source와 공식 module lifecycle을 0.3.1로 되돌리고 route cache/FPM 상태까지 baseline으로 복구했다. 결함 유형별로 smoke만 최소 수정한 뒤 독립 gate와 단일 직렬 delivery를 다시 수행했다. |
| prevention rule | HTTP status, content type, redirect origin/target, route activation, throttle을 분리 진단한다. smoke는 1회만 실행하고 실패를 숨기지 않는다. 제품/runtime 결함과 assertion·request-shape 결함을 구분한 뒤에도 acceptance가 실패하면 rollback한다. |
| related regression test | `modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`; `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`; `modules/_bundled/raonslab-product/tests/Unit/NativePageBootstrapperTest.php`; `modules/_bundled/raonslab-product/tests/Feature/NativePageBootstrapCommandTest.php`; `modules/_bundled/sirsoft-page/tests/Unit/Services/PageServiceTest.php`; `modules/_bundled/sirsoft-page/tests/Feature/Admin/PageControllerTest.php` |
| related commit/request_id | rollbacks `6201e320`, `52677b3f`; feature lineage `09719936` → `571b934e` → `b59b45f4`; final main `390cdc7a`; implementation `req_83dcac7eb8cd454db7146f270f0cb1bd`, independent gate `req_9460a4c28982425bb8ca2c5937179528` |

## CASE 4 — Provider 실행 slot 대기

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-PROVIDER-001` / 2026-09-28 KST |
| symptom | Provider 요청이 즉시 실행되지 않고 `QUEUED`, `waiting_reason=execution_slot` 상태에 머물렀다. |
| wrong initial assumption | 대기를 G7 제품 코드 실패나 유실된 요청으로 보고 새 request를 중복 제출해야 한다고 판단했다. |
| actual cause | `PROVIDER_CAPACITY` 범주의 queue/slot 가용성 문제다. 제품 소스·runtime 결함이 아니며 request truth는 AgentOpt V2 Request/event 상태에 있다. |
| evidence | Production Request 기록에서 `req_9460a4c28982425bb8ca2c5937179528`은 sequence 2·301·425·463·586·610에서 `execution_slot` 대기 후 같은 request의 sequence 682에서 COMPLETED됐다. `req_65edcf3409dd460a94f739b76360def8`도 sequence 2 대기 후 sequence 22에서 COMPLETED됐다. 두 ID 모두 request row 1개와 기존 idempotency key 1개를 유지했다. |
| resolution | request ID와 event cursor를 유지해 대기·resume/follow-up 결과를 관찰한다. 새 request를 중복 제출하지 않으며 재전송이 필요해도 동일 작업에는 원래 idempotency key를 사용한다. |
| prevention rule | 코드 진단 전에 Request state, `waiting_reason`, provider availability를 확인한다. `execution_slot`은 `PROVIDER_CAPACITY`로 별도 분류하고 terminal evidence가 나오기 전 제품 실패로 기록하지 않는다. |
| related regression test | G7 제품 회귀 대상 아님. Adapter 경계는 `modules/_bundled/raonslab-ai-workspace/tests/Feature/AiAdapterContractTest.php`, 사용자 상태 표시는 `modules/_bundled/raonslab-ai-workspace/resources/js/presentation.test.ts`로 고정한다. |
| related commit/request_id | product commit 없음; Production evidence `req_9460a4c28982425bb8ca2c5937179528`, `req_65edcf3409dd460a94f739b76360def8` |

## CASE 5 — 설치 시더 샘플 Page 4종이 운영 문서로 노출

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-004` / 2026-09-28 KST |
| symptom | `/page/about`은 "그누보드7 소개"(G7 홍보·기술 스택), `/page/contact`·`/page/refund`는 `[… 입력하세요.]` 자리표시자, `/page/faq`는 G7 회원 FAQ를 발행 상태로 공개했다. 모바일 드로어·검색봇 footer·sitemap이 네 URL을 계속 링크한다. |
| wrong initial assumption | 0.4.0 native Page 전환이 공개 문서 전체를 정리했으므로 남은 Page는 RAON 소유 문서라고 보았다. |
| actual cause | `sirsoft-page` 설치 시 `PageSeeder`가 6개 샘플(terms/privacy/refund/about/faq/contact)을 `published=true`, `current_version=1`로 만든다. 0.4.0은 7개 RAON slug만 다뤘고 terms·privacy만 전환해 나머지 4개가 원문 v1로 남았다. |
| evidence | `modules/_bundled/sirsoft-page/database/seeders/PageSeeder.php`; 공개 API `/api/modules/sirsoft-page/pages/{about,faq,contact,refund}` 200·제목 원문; 기술 감사의 v1 의미 지문 4개가 `PageSeeder` 원문 재계산값과 정확히 일치(`InfoPageRemediator::SOURCE_FINGERPRINTS`, `InfoPageRemediationCommandTest::untouched_seeder_samples_match_the_audited_source_fingerprints`). |
| resolution | URL은 유지하고 제자리 교체한다. `raonslab-product:remediate-info-pages`가 승인된 content pack v1(파일 SHA-256·감사 base_commit 대조)·활성 최고 관리자 actor를 받아 한 외부 transaction에서 4행을 잠그고, v1·발행·원문 지문이 모두 맞을 때만 `PageService::updatePage()`로 v2를 만든다. 하나라도 어긋나면 전부 중단하고, 재실행은 `already_applied`로 끝난다. 본문은 저장소에 두지 않는다. |
| prevention rule | 설치 시더가 만드는 공개 데이터는 "제품 소유 아님"으로 간주하고 발행 전 전수 분류한다. 제품 분류(taxonomy)는 Page 목록이 아니라 명시적 slug 집합으로 두고, 분류에 든 slug는 배포 smoke가 200·샘플 문구 부재를 확인한다. |
| related regression test | `modules/_bundled/raonslab-product/tests/Feature/InfoPageRemediationCommandTest.php`; `modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`(`no sample/placeholder wording in document body`) |
| related commit/request_id | 이 문서를 포함한 0.4.1 source commit; `req_04b635bdc600403cbf7a3795159ce168`; 기술 감사 `req_3f95be490cb44061be39e563598fe52d` |

## CASE 6 — FAQ·메일 문구가 확인되지 않은 운영 약속을 공개

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-005` / 2026-09-28 KST |
| symptom | 샘플 FAQ가 "가입 후 이메일로 인증 메일이 발송", "비밀번호 재설정 링크가 발송", "평일 오전 9시~오후 6시, 1~2 영업일 내 처리"를 안내했다. 연결된 `/page/contact`는 자리표시자였다. |
| wrong initial assumption | G7 기본 FAQ 문구는 플랫폼 기본 동작 설명이라 사실이라고 보았다. |
| actual cause | 문구는 시더 예시다. 런타임 유효 mailer는 `smtp`지만 관리자 메일 설정에 SMTP host·계정이 비어 있어 실제 발송 경로가 검증되지 않았고, 운영 시간·처리 기한은 승인된 근거가 없다. 가입 인증은 코어 설정 토글이 아니라 본인인증 정책(`core.auth.signup_after_create`, provider `g7:core.mail`)에 달려 있다. |
| evidence | `modules/_bundled/sirsoft-page/database/seeders/PageSeeder.php`의 FAQ 원문; 런타임 `config('mail.default')=smtp`, `storage/app/settings/mail.json`의 host 미설정(값 비공개로 존재 여부만 확인); `storage/app/settings/identity.json`의 `default_provider=g7:core.mail`; `config/core.php`의 `core.auth.signup_*` 정책 정의. |
| resolution | FAQ·문의 문서는 메일 발송·운영 시간·응답 기한을 약속하지 않는다. 공개 문의 경로는 확인된 `/board/questions`만 안내하고, 상담 접수는 `intake_enabled=false` 동안 닫혀 있음을 밝힌다. 메일 발송은 SMTP 설정과 실제 발송 리허설이 끝난 뒤에만 문구에 넣는다. |
| prevention rule | 사용자 약속(메일 발송, 응답 시간, 운영 시간, 가격, SLA)은 런타임 설정과 실제 동작 증거가 있을 때만 공개한다. 교체 명령은 `입력하세요`·`DEMO/MOCK/SANDBOX/TEST` 문구를 거부하고, smoke는 운영 시간·영업일 문구를 실패로 본다. |
| related regression test | `InfoPageRemediationCommandTest::invalid_payloads_and_actors_fail_before_any_write`, `apply_requires_the_approved_whole_file_sha256`; `info-policy-smoke.cjs`(`SAMPLE_TEXT`) |
| related commit/request_id | 0.4.1 source commit; `req_04b635bdc600403cbf7a3795159ce168` |

## CASE 7 — CMS 분류를 여러 파일에 하드코딩

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-006` / 2026-09-28 KST |
| symptom | 정보·정책 문서 목록이 `native-page.json`(조건식 3곳·side nav), `product-nav.json`(드롭다운·footer), `productNav.ts`(현재 그룹)에 각각 적혀 있었고 7개 slug만 알았다. about/faq/contact/refund는 문서 표현·현재 위치가 빠져 stock 카드로 보였고, 데스크톱 문서 메뉴는 float + `overflow:hidden` 부모 때문에 sticky가 동작하지 않고 본문 일부만 옆에 붙었다. |
| wrong initial assumption | Page가 DB 정본이므로 메뉴도 Page 목록에서 자동 생성하거나, 몇 개 slug를 각 파일에 추가하면 충분하다고 보았다. |
| actual cause | `sirsoft-page`는 공개 목록 API를 두지 않으며 G7 Menu는 관리자 sidebar 전용이다. 분류(그룹·순서·짧은 라벨·설명)는 제품 소유 데이터인데 단일 출처 없이 복제돼 드리프트가 생겼다. |
| evidence | `docs/extension/menus.md`; `modules/_bundled/sirsoft-page/AGENTS.md`; 0.4.0 `native-page.json`·`product-nav.json`·`productNav.ts`; 360/390/412/1280 human DOM 측정(0.4.0: 1280에서 스크롤 후 문서 메뉴 top 음수). |
| resolution | `resources/taxonomy/info-policy.json`을 단일 출처로 두고 `scripts/taxonomy.mjs`가 extension JSON의 분류 종속 부분을 생성한다. `productNav.ts`와 browser smoke는 같은 JSON을 읽는다. 문서 메뉴는 grid 오른쪽 열(DOM은 본문 뒤) + 실제 sticky, 모바일은 기본 닫힘 disclosure다. |
| prevention rule | 배포 전 구 런타임에 새 smoke를 돌리면 새 표현 부재로 실패하는 것이 정상(`EXPECTED_FAIL_PREDEPLOY`)이며 반복 실행하지 않는다. smoke는 표현 부재를 짧은 고정 대기(기본 3초) 뒤 FAIL로 기록하고 진행한다. 분류를 바꿀 때는 JSON만 고치고 `npm run taxonomy:sync`를 실행한다. vitest 드리프트 테스트가 커밋된 extension JSON과 생성 결과의 바이트 불일치를 실패로 본다. Page 제목·본문·SEO는 계속 Page DB가 정본이다. |
| related regression test | `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`; `modules/_bundled/raonslab-product/tests/Feature/ProductLayerContractTest.php`; `info-policy-smoke.cjs` |
| related commit/request_id | 0.4.1 source commit; `req_04b635bdc600403cbf7a3795159ce168` |

## CASE 8 — footer linkGroups "미적용" 오진(HeadlessChrome UA)

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-007` / 2026-09-28 KST |
| symptom | 감사용 Playwright가 `/`, `/page/service`, `/board/questions`에서 G7 기본 footer(회사소개/자주 묻는 질문/문의하기 · 이용약관/개인정보처리방침/취소·반품·교환)를 보았고, Footer fiber에서 `linkGroups` prop을 찾지 못했다. |
| wrong initial assumption | product `footer` `inject_props` 주입이 런타임에서 누락되거나 Footer 번들이 prop을 무시한다고 보았다. |
| actual cause | Playwright 기본 UA에 `HeadlessChrome`이 들어 있어 `SeoMiddleware`가 봇으로 판정하고 서버 렌더 HTML을 준다. 그 HTML의 footer는 템플릿 `seo-config.json`의 고정 `footer_nav` 렌더 모드이며 React가 없어 fiber도 없다. 일반 Chrome UA의 human SPA footer는 product `linkGroups`를 정상 렌더한다. 모듈 `seo-config.json`은 템플릿 설정보다 먼저 병합돼(`SeoConfigMerger`: 모듈 → 플러그인 → 템플릿 최종 우선) 모듈이 `footer_nav`를 덮을 수 없다. |
| evidence | `app/Seo/SeoConfigMerger.php`; `templates/_bundled/sirsoft-basic/seo-config.json`(`footer_nav`, `header_nav`, `component_map.Footer`); 같은 URL을 HeadlessChrome UA와 일반 Chrome UA로 요청해 서버 HTML `<header class="block">`/SPA DOM 차이를 대조. |
| resolution | human 검증은 명시한 일반 Chrome UA로만 한다(smoke는 UA 고정 + `HeadlessChrome/` 금지 계약). 봇 footer·모바일 드로어 불일치는 기본 템플릿 확장 지점 부재로 분류하고 템플릿을 직접 패치하지 않는다(`docs/g7/audit/INITIALIZATION_AUDIT_2026-09-28.md`의 제안 참조). |
| prevention rule | UI 결함 판정 전 응답 표면(bot SSR vs human SPA)을 먼저 확정한다. CASE 2와 같은 원리: 검증은 구현이 소유한 표면에서 한다. |
| related regression test | `info-policy-smoke.cjs`(`normal browser UA renders human app DOM`, `footer link text and href follow taxonomy`); `infoPolicy.test.ts`(smoke UA 계약) |
| related commit/request_id | 0.4.1 source commit; 오진 출처 `req_9160cea72adc49aabbcbcc3f5edab73a`; 진단 `req_04b635bdc600403cbf7a3795159ce168` |

## CASE 9 — 같은 target layout 의 overlay 파일 2개가 설치 시 서로 덮어씀

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-008` / 2026-09-28 KST |
| symptom | `raonslab-product 0.4.1` 배포 gate 5(content gate off smoke)가 **1116 PASS / 140 FAIL**. 모든 문서·viewport에서 상위 정보·정책 드롭다운이 비고(`{"info":[],"policy":[]}`) footer가 G7 기본 링크로 돌아갔다. 문서 표현·우측/모바일 메뉴·통화 숨김은 적용됐다. |
| wrong initial assumption | 한 모듈이 같은 `_user_base`에 overlay 파일을 여러 개 둘 수 있고 `priority`는 적용 순서만 정한다고 보았다. 배포 전 브라우저 시뮬레이션도 파일마다 따로 적용해 1208 PASS / 0 FAIL을 냈다. |
| actual cause | `LayoutExtensionService`는 `template_layout_extensions` 행을 (template, 확장 종류, `target_name`, 출처 종류, 출처 id) 키로 하나만 둔다. `product-nav.json`(priority 30: 상위 메뉴·footer)과 `public-commerce-chrome.json`(priority 400: 통화 교체)이 같은 `_user_base`를 target으로 해 나중 파일이 앞 행 내용을 통째로 덮어썼다. 배포 뒤 행은 `_user_base prio=400 inj=1 gnav=0`. |
| evidence | 배포 후 served `page/show.json`에서 `rh_gnav_root` 0·`linkGroups` 0·`data-rh-commerce-suppressed` 1; `LayoutExtension` 행 조회(모듈당 target별 1행); `app/Services/LayoutExtensionService.php`의 `withTrashed()->where($attributes)` 키. |
| resolution | fail-fast 롤백: revert `4d9856dc`(트리 = `390cdc7a`) push → runtime `pull --ff-only` → build(git status 0) → `module:update` 0.4.1→0.4.0 → 새 프로세스 route clear/cache → `g7-product-fpm`만 graceful reload → 14 routes·301 → 0.4.0 smoke **451 / 0**. Page DB 교체(6단계)는 실행 전이라 DB 변경 없음. 수정: 통화 교체 injection을 유일한 `_user_base` overlay인 `product-nav.json`에 합치고(생성기 소유) priority 400(> 이커머스 320)으로 올리고 `public-commerce-chrome.json`을 삭제. |
| prevention rule | 모듈의 overlay manifest는 target layout마다 정확히 하나. 같은 target에 필요한 injection은 한 파일에 모으고 priority는 그 파일 전체에 대해 정한다. 배포 전 시뮬레이션은 설치 계약(`persistedOverlays`)이 고른 manifest만 적용하고 중복 target이면 시작하지 않는다. |
| related regression test | `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`(`overlay 저장 계약(모듈 + target layout 당 1행)` — 실패 후보 재현 포함); `scripts/taxonomy.mjs --check`(중복 target이면 exit 1); `tests/browser/overlay-simulation.cjs` |
| related commit/request_id | 실패 후보 `c64085b2`; 롤백 `4d9856dc`; 복원 `3996c8a0`; 수정 = 이 CASE를 포함한 commit; `req_04b635bdc600403cbf7a3795159ce168` |

## 권위 경로

| 영역 | repository-relative authoritative paths |
|---|---|
| Module route lifecycle | `docs/extension/extension-update-system.md`; `docs/extension/module-routing.md`; `app/Extension/ModuleManager.php`; `app/Support/RouteCacheHelper.php`; `modules/_bundled/raonslab-product/src/Providers/ProductServiceProvider.php`; `modules/_bundled/raonslab-product/src/routes/compatibility.php` |
| Page/SEO | `modules/_bundled/sirsoft-page/AGENTS.md`; `modules/_bundled/sirsoft-page/docs/api/pages.md`; `modules/_bundled/sirsoft-page/src/Services/PageService.php`; `modules/_bundled/sirsoft-page/src/routes/api.php`; `docs/backend/seo-system.md`; `app/Seo/SeoMiddleware.php`; `app/Seo/SeoRenderer.php`; `resources/views/seo.blade.php`; `templates/_bundled/sirsoft-basic/layouts/page/show.json` |
| Frontend/layout | `docs/extension/layout-extensions.md`; `docs/frontend/responsive-layout.md`; `docs/frontend/layout-testing.md`; `modules/_bundled/raonslab-product/resources/extensions/native-page.json`; `modules/_bundled/raonslab-product/resources/css/main.css` |
| Validation | `docs/testing-guide.md`; `docs/testing/e2e-testing.md`; `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`; `modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`; `modules/_bundled/raonslab-product/tests/Feature/ProductLayerContractTest.php`; `modules/_bundled/sirsoft-page/tests/Unit/Services/PageServiceTest.php`; `modules/_bundled/sirsoft-page/tests/Feature/Admin/PageControllerTest.php`; `tests/Feature/Extension/ExtensionRouteCacheInvalidationTest.php`; `tests/Feature/Seo/SeoMiddlewareTest.php` |

## G7 AI 개발 도구

| 자산 | 실제 repository path | 상태 |
|---|---|---|
| Agent guides | `AGENTS.md`; `modules/_bundled/raonslab-product/AGENTS.md`; `modules/_bundled/sirsoft-page/AGENTS.md` | source available. 이 learning gate에서 읽어 적용했으며 별도 연결 개념은 없다. |
| G7 Skills 문서 | `docs/ai-tools/skills/`; 진입점 `docs/ai-tools/README.md` | source available. 현재 Provider의 설치 skill 목록에는 등록되지 않았고 이 요청에서 live skill로 호출하지 않았다. |
| G7 validation/test MCP | `docs/ai-tools/agents/src/mcp/g7-tools-server.ts`; validators `docs/ai-tools/agents/src/mcp/tools/` | source available. 현재 요청의 callable tool 목록에 연결되지 않았고 MCP로 호출하지 않았다. 검증은 직접 repository 명령으로 수행했다. |
| G7 DevTools MCP | `docs/ai-tools/agents/src/mcp/g7-devtools-server.ts`; 안내 `docs/ai-tools/devtools/README.md` | source available. repository에 연결 설정을 추가하지 않았고 이 요청에서 Connected 상태나 호출을 확인하지 않았다. |
| Browser DevTools runtime | `resources/js/core/devtools/`; `routes/devtools.php`; `public/build/core/devtools.min.js` | source/build asset available. production debug dump를 활성화하거나 수집하지 않았다. |
| G7 agent workflow | `docs/ai-tools/agents/src/coordinator/Coordinator.ts`; `docs/ai-tools/agents/src/mcp/index.ts` | source available. 이 요청의 orchestrator로 실행하거나 MCP endpoint를 게시하지 않았다. |

이 요청(`req_04b635bdc600403cbf7a3795159ce168`)에서도 위 G7 AI 도구는 경로만 source-review했고 연결·호출하지 않았다. 검증은 repository의 vitest·phpunit·Playwright smoke를 직접 실행했다.

AgentOpt/Agent.Tools는 request truth, 실행 조율과 프로젝트 context를 담당한다. 위 G7 도구는 저장소 규칙에
맞춘 구현·검증 보조 수단이며 대체 orchestrator가 아니다. 파일 존재는 연결 증거가 아니므로 MCP는 실제
등록·health·호출 evidence가 있을 때만 connected로 기록한다.
