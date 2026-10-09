# W04 캠페인 실제 native 설치 — 독립 최종 검증

**요청 범위 전체 PASS 아님: zero-default-Pages 요구는 FAIL, 캠페인 installed lifecycle/권한/발행/버전/실물 DB 캐시 및 TEST 보존은 범위 한정 PASS.** 제품 작성자가 아닌 `req_812d0334c2064f5d88339114e7954235`가 자기 worktree에서 실행했다. 제품 수정·push·PR·merge·deploy·공식 자식 Request는 0이다. Hosted CI / canonical Validation은 **NOT_RUN**이다.

검증 원본은 **`d059d735cd17ed3c88dd27cca5a9f83f00042011`**, tree **`6dc9381b1d50196c81867ef3f9e31ca86611f410`**이다. 기존 origin `feat/g7-travel-lab-c7ae42d1`를 fetch하고 이 commit을 명시적으로 detached checkout했다. 초기 default `6853f40d`는 시험하지 않았다. 저자 SQLite175/2936, template160, admin12, 과거992 recipe37HTTP/MySQL1/34는 이번 실행에 합산하지 않는다. 원래 campaign implementation의 canonical FAILED 상태도 바꾸지 않는다.

## 범위와 새 baseline

TCP `127.0.0.1:3306`, `req81_travel_lab_test`, `req81_travel@127.0.0.1`만 사용했다. 부모 `.env.testing`에서 allowlist DB 필드만 선택하여 자체 `.env`(local/database)와 `.env.testing`(testing/array)에 0600으로 저장했다. 부모 APP `.env`, APP schema, service18871, auth handoff, 다른 Request의 private backup 및 플랫폼 credentials는 읽거나 변경하지 않았다. Account/grant/schema-name/capacity 변경은 없다.

새 원본 측정은 **55 tables / 104 rows**, full digest **`ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`**다. 과거 release 수치를 가정하지 않고 전체 테이블을 다시 읽었다. NULL/바이너리 base64/중복을 보존하는 정렬 row hash, SHOW CREATE TABLE hash, views/triggers/routines/events inventory0를 측정했다. 원본 dump와 모든 restore 직전 safety dump를 자기 ignored0700/0600에 보존했다. 지원되지 않는 SQL objects가 있으면 mutation 전에 BLOCK하는 정책이다; 이번 원본 objects0를 실제 확인했다.

최종 [독립 측정](w04-campaign-install-final/evidence/measure/final-independent-before-release.json), [release](w04-campaign-install-final/evidence/release/result.json): **TEST RELEASE `2026-10-09T20:02:30+00:00`**, 전체 rows/DDL/objects 원본과 정확히 동일. PDO 종료 후 TEST connections0, own native PHP/mysql0(observer 제외), own HTTP0, port18879 closed, competing process/FD/locks0, unreadable0, pending recovery0이다. RELEASE 후 SQL/runtime tests0이다.

## 실제 발견: 기본 설치 Page 6개

첫 fresh native install은 기본 Page 수 검사에서 **exit255 / FAIL**했다. 제품 파일은 그대로였다. 실제 값은 users1/products0/travel_products0/**pages6**이다. 원본 실패와 복원 증거는 [attempt1](w04-campaign-install-final/evidence/install-attempt1-failed/default-install.json), [원본 복원](w04-campaign-install-final/evidence/original/result.json)에 보존한다.

재현: empty TEST에서 unchanged published migration helper → native migrate → settings:install → DatabaseSeeder → `module:install/activate sirsoft-board,sirsoft-page,sirsoft-ecommerce,raonslab-travel_lab`(install은 전부 `--vendor-mode=bundled`) → `SELECT COUNT(*) FROM g7_pages` = **6**. Campaign provision 명령은 아직 실행하지 않은 상태다.

직접 원인은 `modules/_bundled/sirsoft-page/module.php::getSeeders()`의 unconditional `PageSeeder`. `database/seeders/PageSeeder.php`는 `terms`, `privacy`, `refund`, `about`, `faq`, `contact`와 version1을 만든다. Native Page의 의도된 기본 문서이지만 사용자의 문자 그대로의 **zero Pages** 기준과 불일치한다. Travel default/sample/support는 campaign Pages0이며 native6을 추가로 늘리지 않는다. Lead는 이 요구와 native 기본 동작을 판단해야 한다. 검증자가 native seed를 제거하거나 zero 검사 기준을 PASS로 완화하지 않았다.

원본 복원 후 별도 install2/install3 snapshot으로 진단을 계속했다. 자체 harness는 zero-page gate를 명시 **FAIL**로 저장하고 나머지 캠페인 검증만 계속한다([gate](w04-campaign-install-final/evidence/install/zero-page-contract.json)). 이는 whole install/wizard 승인이나 제품 수정이 아니다.

## 실행과 경계

정확한 명령/exit/PID/시간/hash는 [README](w04-campaign-install-final/README.md), [native ledger](w04-campaign-install-final/evidence/commands/ledger.json), [source pins](w04-campaign-install-final/evidence/intake/source-pins.json)에 있다. 반복 install/probe를 unique test 합계로 꾸미지 않는다.

| 실행 | 실제 결과 | 근거/제한 |
|---|---|---|
| Empty first migrate | PASS / exit0 | unchanged `scripts/travel-lab/migration-bootstrap.php`의 실제 함수를 직접 호출. Empty12 guard fixtures, 첫 migrate process만 array. Persisted ordinary runtime database / testing array 유지. |
| Native bundled lifecycle | 실행 exit0, **zero Pages gate FAIL** | settings/DatabaseSeeder/4 modules + 2 templates install/activate, ShippingTypeSeeder empty branch, 명시 travel sample/support. Vendor mode 모두 bundled; Composer fallback/vendor 수동 copy0. |
| Default/sample/support | FAIL zero-total-Pages / PASS no campaign default | native6, campaign0, default commerce/travel products0. Sample synthetic commerce만; commerce orders/payments sample seed0. |
| Installed HTMLPurifier/ProductService | PASS / exit0 | install2에서 installed HTMLPurifier4.19.0, synthetic product2 ko/en create/update 악성 script/events/javascript 제거. [proof](w04-campaign-install-final/evidence/install-attempt2/html-product.json). PageBody 정책과 별개다. |
| Original transaction installed Kernel | PASS / exit0 | final **37 HTTP / 5 native login**, manual route/provider mounting0, installed routes33/menus4/source pins. [result](w04-campaign-install-final/evidence/install/http-result.json). Earlier same-target run도 별도로 보존. |
| Campaign installed Kernel | **PASS_BOUNDED / exit0** | final **31 HTTP / 93 recorded checks**. [result](w04-campaign-install-final/evidence/campaign/result.json). 체크 이름 map의 수이며 PHPUnit assertions 수가 아니다. |
| Physical SEO DB cache | PASS_BOUNDED | 실제 SeoCacheManager/CoreCacheDriver/database store. Update/unpublish/restore/publish 각각 travel home/list/detail/native alias 4개 캐시를 넣고 native hooks 후 제거 확인. [cache](w04-campaign-install-final/evidence/campaign/cache.json). Recorder mock 아님; stale browser HTML PASS 아님. |
| Own actual public-index service restart | PASS_BOUNDED | port18879 두 start/stop, native campaign HTTP200/version3/body hash 동일. Own proc_open child만 SIGTERM15로 종료; parent preview untouched. [restart](w04-campaign-install-final/evidence/service/restart.json). |
| Own env loss | PASS_GUARD_ONLY | 자체 `.env` 유실 시 guard가 app boot 전에 차단, 원본 bytes/0600 복원, TEST 전체 동일. [guard](w04-campaign-install-final/evidence/service/env-loss-guard.json). Product env-loss/wizard/recovery는 NOT_RUN. |
| UNCHANGED LiveMysqlTest.php | **PASS 1 / 34**, exit0 | PHPUnit18.991s, native DatabaseStore/counter/lock 모두 TEST. [summary](w04-campaign-install-final/evidence/mysql-live/summary.json). Fixture 수동 bundled routes와 installed discovery 증거는 분리한다. |
| Affected Page regression | PASS **123 / 375**, exit0 | PageService/PageController/PageVersionScope/PublicPageController/PageSitemapIndex. PHPUnit65.581s, deprecations2 유지. [summary](w04-campaign-install-final/evidence/page-regression/summary.json). Native MySQL fixture, installed/browser 증거로 확대하지 않는다. |
| Board secret comment regression | PASS **8 / 17**, exit0 | PHPUnit31.551s. [final](w04-campaign-install-final/evidence/board-regression/summary-final.json); transient restoration BLOCK 이력 보존. |
| Core PermissionHelperScope | PASS **11 / 14**, exit0 | PHPUnit27.743s, [summary](w04-campaign-install-final/evidence/core-regression/summary.json). |
| PageBody/campaign UI source tests | PASS **20 / 20**, exit0 | jsdom; DOMPurify3.4.14 offline isolated dependency, root Vitest4.1.8. [summary](w04-campaign-install-final/evidence/frontend/result.json). API transport/layout mock; real browser/network PASS 아님. |
| Last travel migration rollback/replay | PASS_BOUNDED corrected explicit path | Native down/up exit0, table112→111→112, inquiry_events removed/recreated. [native](w04-campaign-install-final/evidence/migration-roundtrip/native-result.json). Populated module data roundtrip/forced fallback NOT_RUN. Whole original baseline subsequently exactly restored. |
| Owned helper syntax/product diff | PASS | PHP/Python syntax checks 합계26; original product tracked paths unchanged. [syntax](w04-campaign-install-final/evidence/intake/helper-syntax.json). |

LiveMysqlTest SHA-256 **`6a9f3b6a273489b292fb3eebbe4986300e78a2e5065c2acc2c3e2a648bee6f7d`**는 전후 동일하다. 자기 TEST `.env`만 root DB-name comparison 중 숨기고 Laravel boot 전에 bytes/0600으로 복원했다. 원본 testing bootstrap의 distinct-DB guard, cache aliases, ordinary array fixture는 변경하지 않았다. Product test 수정·assertion 삭제·skip 추가0이다.

## 실제 campaign 계약

Provision command는 기존 native installer actor를 명시 지정했다. Command를 위한 actor 자동 생성·role 확장·token 생성0이다. Flag off, confirm 없음, actor 없음/미지/member 권한 없음은 exit1이고 Page/version inventory 변화0이다. First provision이 정확한 두 slug를 native PageService/version1로 만들고, 두 번째 실행은 전체 attributes가 동일했다. Native edit/version2→draft 후에도 재실행이 ID/body/version/publication/published_at을 보존했다. 기존 actor object와 빈 guard의 Auth restoration을 확인했다.

Guest/admin 모두 draft detail404/list 제외, native Page admin preview는 별도로200, 존재하는 비registry `about`와 미지 slug404, preview query422였다. List10/detail13 field는 creator/updater/ID/version history/attachments/abilities/preview/SEO 비밀 필드를 노출하지 않았다. 두 고정 catalog filter로 실제 catalog API를 호출했다. Native update→snapshot2, publish toggle→version 불변, restore→new version3/draft 유지, republish→원본 본문/공개를 확인했다. Native audit와 sitemap resource index의 발행 추가/draft 제거도 실측했다.

권한 시험은 TEST 안의 제한된 self-scope synthetic fixture role/user를 native services로 만들었다. 실제 native list foreign 제외, detail403, publish403, no-create403을 확인했다. 기존 role을 넓히지 않았고 provision actor에게 권한을 추가하지 않았다. 일반 member native admin403/guest401도 Kernel에서 확인했다. 모든 fixture role/user/token은 원본 복원으로 제거했다.

Installed Service/listener/route를 reflection/native registrar와 bundled hash로 확인했다. 실제37HTTP는 cart/inquiry201, replay200, 비super admin UNDER_REVIEW/TEST_ACCEPTED, owner read/cancel, foreign404, private native board answers, server price24,000, reserved2→0을 검증했다. Orders/payments/notification_logs0이다. Mailarray/queuesync/local storage/mysql-fulltext, discard proxy `127.0.0.1:9`, Laravel outbound request blocker, COMPOSER_DISABLE_NETWORK를 유지했다. Native version-check의 block/error도 숨기지 않았다. 외부 payment/booking/provider/mail/SMS 실연계는 실행하지 않았다.

PageBody는 전용 DOMPurify instance/서식 태그만/no attributes, text escaped children, mode transition, remote img/link/style/script 제거의 source와 focused tests를 확인했다. 실브라우저의 remote-asset fetch0는 **NOT_RUN**이다. Native 상품 HTMLPurifier가 허용하는 https links/images와 여행 본문의 엄격한 정책을 혼동하지 않는다.

## 원본 실패와 안전 회복

- Install1: pages6 때문에 exit255. 전체 TEST 복원 후 install2를 실행했다.
- Campaign install2: 25번째 HTTP에서 login500. Reused Kernel의 default guard가 Sanctum RequestGuard여서 `attempt`가 없었다. 자체 request helper만 새 cookie-free client에 맞게 web guard를 reset했다. 원본 failure/check map을 유지하고 전체 TEST 복원 후 install3에서 재실행했다. Product/Auth source 수정0이다.
- Rollback trial1: exit0이어도 `Migration not found`, inventory 불변이었다. 원본의 잘못된 PASS label을 유지하고 [assessment](w04-campaign-install-final/evidence/rollback/assessment.json)에서 **FAIL_HARNESS_NO_ROLLBACK**으로 바로잡았다. 이후 별도 snapshot에서 올바른 `--path`로 실제 down/up을 실행했다.
- Board 복원: wipe 후 foreign ancestor PID3650359 FD12를 감지해 import를 BLOCK하고 backup을 유지했다. Metadata의 FD 소멸이라는 state change를 확인한 후 fresh exclusivity로 복원했다. No kills/맹목적인 retry loop. 원본 BLOCKED와 [resolution](w04-campaign-install-final/evidence/intake/recovery-resolution.json)을 유지한다.
- Migration 복원: 자체 read-only PHP vendor-check가 회복 처리와 겹쳐 own-process guard가 measurement를 BLOCK했다. 검증자의 보조 명령 병렬 실행 실수다. 보조 process exit0/PID 소멸 후 fresh guarded restore로 완료했다. 원본 BLOCKED와 [clearance](w04-campaign-install-final/evidence/intake/migration-recovery-clearance.json)를 유지한다.
- Template npm ci: missing peers(EUSAGE), legacy mode의 offline muggle-string cache miss, root graph의 @dnd-kit metadata cache miss를 유지한다. 제품 lock은 수정하지 않았다. DOMPurify exact3.4.14만 자체 ignored isolated dependency로 cache에서 설치해 source tests20을 실행했다. 새 production build는 **NOT_RUN**이다. Resume-driver 편집의 IndentationError는 DB 작업 전 CLI harness error였고 수정 후 core만 실행했다.

각 completed destructive snapshot의 [after/result](w04-campaign-install-final/evidence/)는 새 측정 원본55/104/full digest와 동일하다. Original/install2/3/MySQL/Page/Board/core/migration 모든 dump와 safety를 private로 보존한다. Opaque row hash 외의 SQL/data/config/token/contact/credentials/raw logs는 Git에 넣지 않는다.

## 남은 gate와 전달

**NOT_RUN:** real browser390/1440/native editor controls/alias rendering/keyboard/error-retry/remote-request 관측, whole public setup/run wrapper/account provisioning/installer wizard, new production build, external Scout/index, native product env-loss recovery, forced-fallback/populated rollback, hosted CI, canonical Validation. Native Kernel/source/jsdom 결과로 대체하지 않는다. 이번 bounded runtime PASS는 zero-Pages FAIL을 해소하지 않는다.

중요 성과는 이 보고서, [자체 helpers](w04-campaign-install-final/README.md), sanitized evidence다. Original/safety/private config는 자기 worktree의 ignored `storage/framework/testing/w04c-*`(0700/0600)에 보존하고 공개하지 않는다. Disposable private 경로는 durable Git evidence를 대체하지 않는다. Source/build/own generated cache hash와 실행/복원/release receipts를 local evidence commit에 저장한다. Lead의 commit intake/integration이 필요하며 이 Request는 remote integration/deployment 완료를 주장하지 않는다.
