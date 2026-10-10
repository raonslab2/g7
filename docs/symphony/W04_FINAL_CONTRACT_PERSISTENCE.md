# W04 final integrated contract / persistence — independent nonauthor

Request `req_95d0024e7b144a5bb903cc7daad1fb5f`; new task key `w04-final-contract-persistence`. Reviewed exact commit **5783e6ba124061bdfae639cdaf9b1c14a83cdf03**, tree **361f9a142572f8a6c0c28326c461a233a92aec4e**, fetched from the existing `feat/g7-travel-lab-c7ae42d1` branch. Default6853 was replaced before inspection. Product source was read-only. No APP DB access, parent APP environment/handoff, provider/payment operation, foreign process termination, official child, push, PR, merge or deployment occurred.

## Decision and evidence boundary

**PASS, bounded, with the initial dump instrumentation limit recorded below:** actual native installed fourteen-row contract coverage, four sustained MySQL HTTP barriers, real own service stop/start/relogin preserving inquiry/private native answer/campaign, populated migration recovery including injected primary-import failure, unchanged native MySQL fixture and selected private-support regressions. Final TEST release is recorded below. This is independent Request evidence, **not a canonical Validation receipt or release approval**. Hosted CI: **0 / NOT_RUN**; canonical Validation: **NOT_RUN, no receipt**. Original earlier negative reports remain unchanged, including the bounded canonical FAILED result. This verifier does not reopen completed task keys or count historical passes as new.

Evidence directory: [w04-final-contract-persistence](w04-final-contract-persistence/). [summary.json](w04-final-contract-persistence/evidence/summary.json), [command ledger](w04-final-contract-persistence/evidence/commands.json), [preserved failures](w04-final-contract-persistence/evidence/preserved-failures.json), and [release](w04-final-contract-persistence/evidence/release/result.json) are sanitized; dumps, environments, credentials, tokens, question/answer bodies and detailed exception logs remain private, ignored and retained under this Request's `storage/framework/testing/w04p-*`.

## Intake, installation and source binding

Read root/module AGENTS, `W04_RETRY_FINAL_REVIEW_CONTRACT`, `W04_SECURITY_FINAL` server matrix, atomic-contention report/helpers, campaign native-install/browser reports and intakes, and the module domain/support source. The advertised `helpers/` subdirectory does not exist; reviewed published helpers are flat in `w04-campaign-install-final/`. Adapted copies live only in this report namespace. No old run output was reused as an executed result.

At **21:23:41 UTC**, independently inventoried **55 tables / 104 rows**, full digest **ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e**. The equality with the prior measurement was observed afresh. Full ordered table inventory includes counts, NULL-distinct base64 cell encoding, sorted duplicate-preserving row digests, native SHOW CREATE hashes, and absence of views/triggers/routines/events. Scope: exact `req81_travel_lab_test`, `req81_travel@127.0.0.1`; fresh connection/process/FD/lock checks and private original dump with checksum, completion trailer and SQL-scope validation. No destructive action preceded that backup.

Only the minimum DB/local guard values from the authorized parent `.env.testing` were used. Own0600 `.env`/`.env.testing` use TEST for all DB/cache/lock paths, local/mysql-fulltext, G7 environment priority, array mail, sync queue, array session and own lab/cache markers. The original same-DB-name test guard is intact: own guarded testing entrypoint temporarily hides only its verified TEST `.env` for that comparison and restores exact bytes/0600 **before app boot**. No production guard was disabled.

Observer/prepared/import SQL asserts SELECT DATABASE and account before each execution. Native application SQL is guarded through Laravel execution callbacks; fixture guard runs inside Laravel's existing query/reconnect handling. **Procedural limit:** the initial two original/populated dumps used the reviewed schema-bound native `mysqldump` client, whose internal statements were not individually interposed. Later native SHOW CREATE/full-row dumps use the per-statement PDO guard; the retained fresh-baseline34 guarded backup has the same complete55/104 original inventory. Both original and subsequent backups remain retained. This distinction is explicit; the original client is not retrospectively claimed to have per-statement instrumentation.

Actual native empty-install lifecycle completed18 recorded commands plus native empty core migration/bootstrap checks: settings, DatabaseSeeder, four bundled modules and two templates installed/activated, positive installed/bundled HTMLPurifier dependency/class check, empty shipping-reference native seeder, explicit travel `--sample` and support provisioning. **Default native basic Pages6, campaign Pages0, native/travel products0** were measured before explicit sample/provisioning. Basic Pages remain6 after sample/support. The historical zero-total-Page failure is unchanged and was not treated as a user requirement. Campaign provisioning explicitly used the native command and permitted installer actor; customer/admin test actors were created through native UserService, with an ordinary non-super admin. Commerce matrix fixtures were made through native admin APIs; no order/payment table writes or manufactured price snapshots.

Own server `127.0.0.1:18880` uses the actual installed extension autoload/providers/routes and Laravel Kernel. No manually registered/fake APIs. Each actual HTTP request gets a fresh process request context; default web/Sanctum guards and array session are explicitly reset. Four PHP workers permit real overlapping connections. Installed runtime cache is native DatabaseStore with both counter/lock connections **TEST**, `cache`/`cache_locks`, own prefix. Native33 API routes and role permissions were recorded before/after; after own `route:cache`, cached route hash is `79e7d25def84fd5f833aec0bdc7851af2405e062c3cd3999cb31a0f676dc582c` and the resolved route/middleware list is identical. Egress layers are the explicit local proxy deny endpoint, native HTTP RequestSending rejection and effective safe adapters; this is not a claim of a host-wide firewall or external-inbox monitoring.

[Whole product manifests](w04-final-contract-persistence/evidence/source-before.json) bind **11,091 tracked product files** to Git and compare before/after byte equality; aggregate SHA256 **a375b6d869a1faf4a6d083120b11feb556572401fb89e3ae2415e66c6a38d310**. Installed reflection origins match the bundled source for native commerce/board/Page and travel services. TemplateApp source SHA256 **62e89b9dedb93c932a92e1b01944cffb11e88cf0c5cd5a79dc41ca8835f5b76e**; candidate compiled core artifact **738ee97c6eebc33bc75d29f24397daabb54f124bede254689ca68a18fdb64a0b**. This verifies the disk artifact; APP/browser served-asset/UI proof belongs to the separate browser scope.24 relevant inquiry/cart/throttle files are byte-identical to prior atomic source; quota load was not rerun or counted as a new pass.

## Actual installed fourteen-row server contracts

New HTTP/SQL results: [matrix](w04-final-contract-persistence/evidence/matrix/), [effects inventories](w04-final-contract-persistence/evidence/effects/). Fourteen requirements are covered by grouped named probes; grouped probes are not fourteen PHPUnit tests. Across fixtures, matrix, races, persistence and two native-login sets, **159 real HTTP checks** were executed; per-phase counts remain separate from the10/137 source-test total.

| # | Executed contract | Result |
|---|---|---|
|1|Native cart/public unit = product12000 + adjustment0|PASS12000, native SQL agrees|
|2|Native admin option adjustment700, IDs preserved|PASS cart12700|
|3|Qty2 × unit12700; native calculation/saved item/inquiry|PASS25400 KRW; later adjustment900 gives current cart12900 while submitted12700/25400 stays frozen|
|4|POST/PATCH qty0, negative, fraction, string, huge; overcapacity; unknown departure|PASS422 invalid inputs,409 overcap,404 unknown; no unintended cart row/reservation|
|5|Client price/unit/total/user_id and inquiry/admin money tamper|PASS422; unchanged legitimate rows|
|6|Real KST today/past/reverse dates; reserved/unit fields; member admin departure|PASS422, member403; actual KST date recorded separately from status|
|7|Other cart patch/delete/submit, other inquiry show/cancel|PASS404/404/409; foreign cart kept; inquiry404/404|
|8|Reservation quantity|PASS reserved0→2 for qty2|
|9|Fullcapacity and extra member|PASS reserved3=cap3; extra add409; all race invariants reserved≤cap|
|10|Cancel/repeat and native admin decline/repeat|PASS cancel3→0→0; decline extra1→0, repeated200, exactly2 events (submit+terminal); race release once|
|11|TEST_INQUIRY→UNDER_REVIEW→TEST_ACCEPTED; terminal invalid; member admin|PASS200/200,3 events; backtransition409; member list/patch403|
|12|Same key/payload replay; changed payload|PASS200 same ID, changed409; overlap one inquiry/item/event|
|13|Whole native orders/payments/order_options/temp_orders before/after|PASS all0, exact row+DDL hashes unchanged across fixtures/matrix/races/support/persistence|
|14|Effective array/sync/local and job/mail/notification side effects|PASS effective runtime recorded; jobs/failed_jobs/mail_send_logs/notification_logs/notifications all0 with exact unchanged row/DDL hashes; no external provider/fee/notification operation initiated|

## Four actual MySQL overlap barriers

[Race records](w04-final-contract-persistence/evidence/races/t3-races-212857.json). FOR UPDATE is read-only on own native row; release/transition writes use native HTTP/services. Both HTTP responses remained pending until the held lock was released. The same distinct connections were present in first/last samples and their pending locking reads advanced≥3s. Scoped account could not access data_lock_waits/INNODB_TRX: **engine lock graph NOT_RUN**, not invented. Correlation rests on native pending query metadata and response timing. Server-wide Innodb_deadlocks was6 before/through/after each case; that is bounded observation, not a global/no-future-deadlock claim.

| Case | Distinct pending connections | Sustained seconds | Actual result / invariant |
|---|---|---:|---|
|Last seat, two members|138349,138350|3.319|201/409; reserved=capacity1|
|Same member/key|138370,138372|3.442|201/200 same ID;1 inquiry,1 item,1 event,qty1/reserved1; replay200, changed409. Second request transitively waits on native user row.|
|Owner cancel vs admin decline|138399,138401|3.410|200/409, CANCELLED; reserved1→0→0; repeated cancel200;2 events|
|Capacity lowering vs submitqty2|138428,138430|3.403|submit201, lowering409; reserved=capacity2, no below-reserved capacity|

## Required actual process restart persistence

[Service lifecycle](w04-final-contract-persistence/evidence/service/) and [persistence](w04-final-contract-persistence/evidence/persistence/). First process group988869 started21:26:18, stopped **21:30:55**, port closed and all own live processes0. New group1012326 started **21:31:12**, three native relogins issued distinct new tokens. Owner inquiry7 stayed **TEST_ACCEPTED**,1 item/qty1,3 events, reserved1; native private question/administrator answer stayed readable by owner, foreign404, guest401, foreign list excluded it. Published campaign body and **version2** stayed equal. Full native inquiry/items/events/question/comment/Page/version row digests also match before/after. Second group stopped **21:31:42**, port closed/live processes0. No APP unit restart. This closes the inquiry/support/campaign persistence gap left by the historical Page-only restart.

## Migration, native regressions, failures and exact recovery

Populated snapshot:112 tables/1768 rows, digest `84e16b64c6a36cc1fa16bb3f1e8ed97de8ece748786746bf1bb965268a16de39`. Native explicit-path final travel migration down/up:112→111→112, exits0/0, initially8 inquiries/15 events. Down drops event rows/calculation snapshots; replay creates an empty events table. **Migration alone does not preserve old event data.** Validated whole-dump restoration then injected primary import exit73 after one session SET and zero table mutations in the already guarded-wiped TEST; fallback independently revalidated the retained dump and exited0. Whole112/1768 rows/DDL/objects recovered exactly. [Populated recovery](w04-final-contract-persistence/evidence/populated-recovery/).

| Actual unchanged source fixture | Native result | Whole snapshot/restore |
|---|---|---|
|LiveMysqlTest required fresh-baseline branch|**1 test /34 assertions**,15.418s, exit0; file SHA2566a9f3b6a273489b292fb3eebbe4986300e78a2e5065c2acc2c3e2a648bee6f7d|exact55/104|
|Selected Support API / W03 privacy hardening|**9 tests /103 assertions**,20.164s, exit0; auth-required, ownership, native answer, dual permissions, self/restricted scope, native audited edit/no notifications|exact112/1768|

Unique source cases: **10 tests /137 assertions** using final required LiveMysql run plus9 distinct support cases. Earlier same LiveMysql run on populated DB passed1/33 because its empty-user seeder branch is skipped; **not added** to unique counts. Its initial harness error was1 test/0 assertions/1 error, exit2; exact restore succeeded. Installed HTTP counts and named check counts are separately recorded; no sum with inherited175/2936, Page123/375, Board8/17, core11/14 or earlier1/34. Whole SQLite/Page/frontend reruns were unnecessary for this read-only backend proof and remain NOT_RUN here. Source fixtures' native route mounting differs from installed runtime discovery and is not substituted for installed API proof.

Preserved own failures: initial PDO statement self-reference held the observer connection (snapshot saved, no installation); early pre-provider SQL callback binding error (wipe not performed); original26MB dump parser was stopped **only its own PID** after slow suffix copying, exit15 /163.191s recorded, no DB writes, original backup retained. Corrected offset parser completed12.942s; fresh exclusivity and exact saved inventory were verified before continuing. First native fixture guard bypassed Laravel's lost-connection recovery by running too early; corrected guard retains identity checks inside native query/reconnect handling. Original source/test files were never patched. The synthetic primary-import exit73 is separately labeled intentional, not hidden as success.

Foreign ancestor FD/locks detected at21:31:55/21:33:02 caused a write pause. No foreign kill/backup read. Once naturally gone, **fresh** exclusive inventory/preflight at21:33:35 preceded resumption. Final release also preserves any transient FD block and requires a new successful preflight, rather than silently accepting it.

## Final TEST release

**TEST RELEASE: 2026-10-09 21:46:19 UTC.** Final release values and backup integrity are authoritative in [release/result.json](w04-final-contract-persistence/evidence/release/result.json). Original full inventory **55/104, digest ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e** was restored exactly after every final fixture. No old128/639 preservation claim. Original and safety dumps retained privately. Release records its UTC timestamp, exact environment/source binding, zero competing TEST connections, zero own native processes/FD/locks, and **18880 closed**. Publication hygiene scans public artifacts for actual own secrets/private bodies; no private dump/environment/token material is included. The initial scan flagged generated Python bytecode and a literal that matched the published synthetic campaign body. Only owned generated bytecode was removed and future helper content became generated; initial FAIL is retained, subsequent scan found0 violations. TEST release first encountered another transient foreign FD/lock; the failed preflight remains recorded, and release was issued only after a new successful exclusive preflight.

Native setup/env-loss wizard/UI, host-wide egress firewall monitoring, external Scout/providers, whole unchanged SQLite/Page/frontend suites, hosted CI and canonical Validation remain **NOT_RUN** here; none is waived by these local results.

Only this report and its evidence/helper namespace enter the local evidence commit. The lead owns independent intake, Git publication/integration and final release gates.
