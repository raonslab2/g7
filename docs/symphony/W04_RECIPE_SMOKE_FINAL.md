# W04 recipe smoke — fixed-target native TEST final

**범위 한정 product gate PASS.** 게시된 empty migration helper를 직접 호출한 native 설치, 실제 설치 kernel workflow, 변경하지 않은 standalone `LiveMysqlTest` **1 test / 34 assertions PASS**, 전체 TEST baseline 복원을 확인했다. 첫 MySQL entrypoint 시도는 자체 harness 오류로 테스트 전에 exit2였으며 보존했다. 공개 setup 전체·계정 provisioning·installer wizard·공식 Validation·hosted CI는 **NOT_RUN(실행0, 면제 아님)**이다.

Request `req_bed1c2288b1946de95782fe1a4acff75`. origin `feat/g7-travel-lab-c7ae42d1` fetch 후 정확히 **`992f9a65ac3f8957e5ec618f21072dc499053810` / tree `db85f35c5f42165802ec7ba6b02bc8a147a12c39`**를 checkout했다. 기본6853 사용0, 제품 코드 작성/수정0. 변경은 본 보고서와 `w04-recipe-final/**` 자체 helper/안전한 evidence뿐이다. [source pins](w04-recipe-final/evidence/intake/source.json), [정확한 변경 경로](w04-recipe-final/owned-changed-paths.txt)를 제공한다. 로컬 evidence Git SHA는 최종 handoff에 기록하며 product source SHA로 대신하지 않는다. push/PR/merge/deploy/공식 자식 실행0이다.

## 경계·독립 측정

루트 AGENTS와 board/page/ecommerce/travel/admin/user-template 관련 가이드, deploy README, W04_NATIVE_INSTALL_FINAL, W04_INSTALL_FINAL_INTAKE, W04_INSTALL_BOOTSTRAP_REVIEW, W04_ATOMIC_WORKER_DIAGNOSTIC, FINAL_REPORT 및 기존 helper pack을 읽고 검토했다. Native readonly reviewer는 fixed product/자체 helper를 검토했으며 구현/SQL/runtime 검증을 하지 않았다. 공식 Validation이 아니다.

새로 측정한 현재 TEST는 **55 tables / 104 rows**, 전체 digest `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`였다. Historical17:53:26 값을 가정하지 않고 [새 preflight](w04-recipe-final/evidence/intake/measured-preflight.json)의 실제 inventory를 backup/복원 기준으로 삼았다. 전체 rows(NULL/binary/중복 포함)/DDL/objects0를 측정하고 original 및 restore 전 safety dump를 private0700/0600으로 보존했다. Views/routines/events/triggers가 있었다면 BLOCK한다; 실제 모두0. 기존128/639 보존은 **NOT_PROVEN**이다.

모든 실제 DB 연결은 TCP `127.0.0.1:3306`, `req81_travel@127.0.0.1`, **`req81_travel_lab_test`**다. 부모 `.env.testing`의 명시 허용된 최소 TEST 필드만 최초 guarded process에서 읽었다. 부모 APP `.env`/DB/service18871/env/auth/platform credentials/config 접근·변경0. Root SQL/account/grants/schema 이름 변경0. 자체 ignored0600 `.env`는 local/database, `.env.testing`은 testing/array다. mysql/database/cache/cache_locks alias는 유지했다.

Native process의 `proc_close` 완료·PID 소멸·process/FD guard 후 다음 단계/복원을 진행했다. TEST processlist는 scoped account PDO로 확인했고 `/proc` metadata만 읽었다. 경쟁 TEST 연결·관련 foreign/own native process/FD/lease/unreadable0. 발견 시 backup 유지·이 scope만 BLOCK하며 kill/retry loop는 없다. 최초 original dump는 PID-recording 보완 전에 저장되어 개별 dump PID field가 없다. 당시 proc_close·stable inventory·SQL scanner·다음 파괴적 단계 전 process guard를 확인했다. 이후 snapshots/imports는 PID-gone/FD metadata도 기록한다.

Own root Composer dependencies는 `COMPOSER_DISABLE_NETWORK=1 composer install --prefer-dist --no-scripts --no-interaction` exit0으로 복구했다. Native modules는 offline `--vendor-mode=bundled`; 실제 네 모듈 모두 bundled다. Laravel RequestSending blocker, loopback discard proxy, array mail/sync queue/local storage를 적용했다. Payment/booking/mail/SMS/provider 실제 호출·payment plugin 설치0. PHP loopback service는 필요 없어 시작0; 부모18871은 건드리지 않았다.

## 실제 실행 결과

명령은 [README](w04-recipe-final/README.md), 각 native command/PID/exit/시간/출력해시는 [ledger](w04-recipe-final/evidence/commands/ledger.json)에 있다. 다른 Request/partial/이전 결과를 합쳐 unique-test 총수로 꾸미지 않았다.

| 실행 | 상태 / exit | 실제 수·시간·근거 |
|---|---|---|
| Published helper on nonempty TEST | PASS / 0 | 7 safe fixtures; 잘못된 schema/account/env 거부, nonempty env 동일; [result](w04-recipe-final/evidence/intake/bootstrap-nonempty.json) |
| Published helper on empty TEST | PASS / 0 | 12 fixtures; completed/installed modules/templates/plugins 거부, partial-no-cache 동일, empty만 CACHE_STORE 변경; [result](w04-recipe-final/evidence/intake/bootstrap-empty.json) |
| Bundled dependency check | PASS / 0 | ecommerce archive `713f9857…`, package1; silent vendor copy/Composer fallback 없음 |
| Native first migrate | PASS / 0 | 게시 함수 verbatim 호출, empty0/completedfalse/installed extensions0; 7.046s, 첫 process만 array; [proof](w04-recipe-final/evidence/install/empty-core-bootstrap.json) |
| Native install lifecycle | PASS / 0 | 46.292s; wipe 포함18 명령 + 별도 first migrate1; [commands](w04-recipe-final/evidence/install/commands.json) |
| Installed ProductService / HTMLPurifier | PASS / 0 | 0.779s; 합성 상품2 ko/en create/update·악성 markup 제거, installed HTMLPurifier4.19.0; [proof](w04-recipe-final/evidence/install/html-product.json) |
| Actual installed native HTTP kernel | PASS / 0 | **37 requests / 5 native logins**, 4.360s, native routes31/origins 확인; [result](w04-recipe-final/evidence/install/http-result.json), [statuses](w04-recipe-final/evidence/install/http-statuses.json) |
| Standalone MySQL trial1 | **FAIL_HARNESS / 2** | **0 tests**, 0.104s; native include의 `$path`가 자체 readback 변수를 덮어씀; [failure](w04-recipe-final/evidence/mysql-smoke/native-summary.json) |
| Standalone MySQL corrected entrypoint | **PASS / 0** | **1 test / 34 assertions**, PHPUnit15.100s / child wall15.241s; 제품 미수정; [full safe summary](w04-recipe-final/evidence/mysql-smoke-fixed/native-summary.json) |
| Recovery failure control-flow | PASS_CONTROL_FLOW_ONLY / 0 | 4 scenarios, 0.037s; native MySQL rollback/fallback run 아님; [result](w04-recipe-final/evidence/intake/recovery-control-flow.json) |
| Owned helper syntax/diff | PASS / 0 | PHP lint / Python compile / git diff --check; 제품 Pint/frontend/build 새 실행 아님 |

실제 설치는 first migrate→settings:install→native DatabaseSeeder→board/page/ecommerce/travel install+activate→admin/user templates install+activate→empty ShippingTypeSeeder→explicit travel sample/support였다. 직후 **112 tables / 1,587 rows**, digest `a4911ff2e4090e20824476256a2be4300df3f9993ee3eaa93e3021d9d9635872` ([installed](w04-recipe-final/evidence/install/installed.json)). Default 설치에서 commerce/travel products0, native admin1을 확인했다. ShippingTypeSeeder는 empty일 때만 호출했으며 commerce sample orders/payments seed는 실행하지 않았다. Probe에서도 orders/payments/notification logs0이다.

First migrate helper body를 복제/manual array override로 대체하지 않았다. 실제 반환값은 **CACHE_STORE 하나만 database→array**였고 persisted `.env` database / ordinary `.env.testing` array는 그대로다. Nonempty와 TEST-only partial-table(no-cache)에서 반환값 전체가 입력과 동일했다. Wrong account negative는 env username fixture다; 실제 account/grants 변경 negative와 SQL-object-only fixture 생성은 **NOT_RUN**, live CURRENT_USER와 objects0는 실제 확인했다.

정상 settings/seed/module/template/native kernel은 effective database cache다. [Kernel proof](w04-recipe-final/evidence/install/kernel-cache-proof.json)의 실제 DatabaseStore counter/lock DB 모두 TEST다. 설치 autoload/routes/Service/Listener/role/menu/template origin을 확인했으며 manual provider/route mount는 없다. Cart→inquiry201/replay200→비-super admin UNDER_REVIEW/TEST_ACCEPTED→owner read/cancel, foreign inquiry404/member admin403, private question owner/edit/answer/foreign404, server price24,000·reserved2→cancel0을 확인했다. Broad seed-rerun/load/restart는 반복하지 않았다.

Standalone LiveMysqlTest hash `6a9f3b6a273489b292fb3eebbe4986300e78a2e5065c2acc2c3e2a648bee6f7d`는 전후 동일하다. 자체 entrypoint는 자체 TEST `.env`만 root DB-name comparison 동안 숨기고 bytes/0600을 Laravel 전에 복원했다. 원래 root guards/cache-file aliases/ordinary array를 변경하지 않았다. Trait가 native quota용 DB cache를 선택하여 **새 3 assertions(DatabaseStore, counter TEST DB, lock TEST DB)** 및 원래 price/person/tamper/permission/idempotency/admin denial/cancel assertions를 모두 실행했다. Fixture bundled-route mounting과 installed discovery proof는 별도다.

## 복원·실패 보존·TEST RELEASE

각 suite 후 원본 복원과 별도 guarded 재측정 뒤 다음 destructive suite를 시작했다. Own installed copies/generated caches는 original equality 후 private safety로 이동했다. 자체 completion flags만 false로 복구하며 완료된 keys/credentials/account를 재생성하지 않았다. Trial1은 전체 snapshot 복원 뒤 자체 변수 이름을 `w04q` 전용으로 고쳐 새 snapshot에서 재실행했다. 제품 변경/실패 assertion 삭제/array-store admission 허용 없음. Trial1 raw stdout/stderr/exit2와 역사적 product1/8FAIL은 그대로 보존한다.

| 독립 snapshot | whole row/DDL/objects digest | native restore exit / 시간 |
|---|---|---|
| original install suite | `ded72a53…` → **같음** | wipe0/import0, 1.625s; [restore](w04-recipe-final/evidence/original/restore-summary.json) |
| mysql-smoke harness failure | `ded72a53…` → **같음** | child2 유지, wipe0/import0, 1.726s; [restore](w04-recipe-final/evidence/mysql-smoke/restore-summary.json) |
| mysql-smoke-fixed | `ded72a53…` → **같음** | child0/wipe0/import0, 1.608s; [restore](w04-recipe-final/evidence/mysql-smoke-fixed/restore-summary.json) |

[최종 독립 재측정](w04-recipe-final/evidence/measure/final-independent-before-release.json), [release](w04-recipe-final/evidence/release/result.json): **TEST RELEASE `2026-10-09T18:42:38+00:00`**. Baseline55/104/full digest `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e` 완전 동일. Token/모든 rows까지 baseline과 같아 자체 시험 token/user/contact/inquiry/cache row를 남기지 않았다. PDO 종료 후 TEST connections0, own native PHP/mysql0(observer 제외 후 실제 handle도 종료), HTTP processes0/start0/18876 closed, competing process/FD/lease0/unreadable0, pending own BLOCKED0. Original/safety dumps는 private로 보존했다. RELEASE 후 SQL/runtime tests0이다.

## 남은 gate·전달

| Gate | 현재 판단 |
|---|---|
| Published helper + native empty TEST lifecycle + installed kernel + unchanged MySQL fixture | **범위 한정 PASS**; 자체 bootstrap trial1 failure 유지 |
| Whole public setup/provisioning/account rotation/installer wizard | **NOT_RUN**. APP+TEST account/grants provision/rotation 때문. 실제 setup source wiring은 helper가 migrate에만, 이후 settings/seed는 원래 DB env임을 독립 검토 |
| Exact measured backup/restore + TEST release | **PASS** |
| Native rollback/replay/forced fallback | **NOT_RUN 이번 Request**; 새 helper의 실제 native wipe/import/full-equality는 실행. 이전 normal/fault(exit1) recovery는 inherited historical evidence |
| Whole public run.php wrapper/direct public-index service/restart/env-loss/frontend build/broad118·154 suite | **NOT_RUN**; kernel proof를 전체 wizard/server PASS로 확대하지 않음 |
| Lead22/2230, guards27/Pint/work-validator89 | **INHERITED**, 이번 실행 수 아님 |
| Formal Validation / hosted CI | **NOT_RUN / 0**, 면제 아님 |
| Historical atomic worker missing stderr | **UNKNOWN 유지**; 새 fixed-target 결과로 과거 원인을 추정하지 않음 |

기존 보고서와 `w04-native-final/evidence`는 변경하지 않았다. 공개 evidence에는 plaintext credentials/.env/SQL dump/token/contact screenshot/PII가 없다. Raw logs는 ignored0600으로 보존하고 Git에는 status/hash/count/timing/helper만 전달한다. Native source review/이번 runtime evidence를 공식 Validation receipt로 주장하지 않는다. Lead가 로컬 evidence commit을 수집·integration해야 하며 원격 통합/배포 완료를 주장하지 않는다.
