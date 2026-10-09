# Travel Lab runtime evidence

Request: `req_81ac33cac94046b9a2249cd14c0d00ba`.
Initial bootstrap owner: lead-owned native `commerce_contract`; subsequent live
runtime harness owner: native `inheritance_capacity`. Official child worktrees
are not modified. Input research SHA: `6853f40d58acbf53a2f29cbb9dd422cc439047a9`.

## Scope and installation

Only this request checkout and newly created local databases are used. Native
business-site directories, DBs, services, Page/member/contact data, platform
credentials and external deployment remain untouched. Preview is loopback
`127.0.0.1:18871`, distinct from other ongoing work.

Scripts: `scripts/travel-lab/{setup,environment,run,extensions,smoke,guard-test}.php`.
Reproduction: [`deploy/travel-lab/README.md`](../../deploy/travel-lab/README.md).
Example: `.env.travel-lab.example` has empty secrets. Actual `.env` / `.env.testing`
are generated, Git ignored, mode 0600. No passwords or tokens are evidence.

Available G7 commands were verified from installed Artisan help on 2026-10-09:
`migrate`, `db:seed`, `settings:install`, `module:install`, `module:activate`,
`module:seed`, `template:install`, `template:activate` and update commands.
There is no G7 application `install` CLI. The harness composes these official
commands; it creates no scheduler, worker, Request DB, or alternate installer.

Scoped local DBs: `req81_travel_lab` and `req81_travel_lab_test`; scoped local
user `req81_travel`. The user receives database-level grants only. MariaDB admin
is invoked with local socket authentication; production `.env` and platform
credential/config files are never used. Input credentials are generated locally
and passed to mysql through stdin, not command arguments or logs.

Core `DatabaseSeeder` invokes `AdminUserSeeder`, whose code deletes all users.
The harness runs that seed only when `g7_users` is empty. Rerun preserves users.
Travel module seed is separate and must be proven repeatable on its final SHA.

## Initial verification, 2026-10-09

| Check | Result | Evidence/limit |
|---|---|---|
| PHP/MariaDB available | PASS | PHP 8.3.6; MariaDB 10.11.14; sudo local socket works |
| Scoped DB/user setup | PASS | New lab/test schemas; connection uses scoped user |
| Core migration + settings + administrator seed | PASS | Official commands complete; 13 settings categories; one synthetic admin |
| Setup rerun | PASS | Nothing to migrate; 13 settings skipped; existing admin preserved |
| Effective local Laravel configuration | PASS | Live `DATABASE()` = `req81_travel_lab`; mail=array, queue=sync, storage=local |
| Effective test Laravel configuration | PASS | Live `DATABASE()` = `req81_travel_lab_test`; same egress settings |
| Existing focused core PHPUnit | PASS | InstallerContextTest: 8 tests / 8 assertions |
| Harness isolation negative checks | PASS | 15 cases; foreign DB/host/user/port, URL/socket, mail/queue, cache/runtime overrides blocked |
| Loopback HTTP bootstrap | PASS | `run.php preview`; `GET http://127.0.0.1:18871/up` returns HTTP 200; process stopped after smoke |
| Travel artifact installation | BLOCKED | New bundled `raonslab-travel_lab` module/template pending; no partial extension install |
| Full travel transaction E2E/security/concurrency | NOT_RUN | Requires domain/UI implementation and independent fixed-SHA verification |
| Frontend build/types/regression | NOT_RUN | Lead owns final relevant frontend gates |
| Remote publication/integration/official Validation | NOT_RUN | Lead owns Git Delivery and official independent validation |

The harness locks mail/queue/storage via `G7_ENV_PRIORITY=true`: G7's settings
provider otherwise overwrites environment values after settings installation.
The runner validates both DB targets, host/port/user/prefix, rejects URL/socket
overrides and installer/config caches, strips inherited connection overrides,
and requires focused test selection. Native bootstrap uses the repository's
test guard and extension allowlist convention; no root PHPUnit config was edited.
The explicit `--testsuite=Installation` gate and final Travel Lab test directory
are also allowed. Initial PHPUnit elapsed time was 2.223 seconds; 15 harness
checks took about 1.13 seconds. No simultaneous broad suites were launched.

This document records initial environment evidence, not completed Travel Lab,
operating load certification, customer-approved design, or 110-page delivery.
# Hardening after independent preflight review

The original runtime implementation passed15 isolation cases at the initial
fixed revision. Independent review found that local storage was verified by
effective smoke but not rejected before every command, and same-process smoke
retained rejected inherited variables even though the subprocess runner passed
only its sanitized environment. The lead added a failing external-storage case
(exit255), required FILESYSTEM_DISK=local, and removed rejected inherited keys
from putenv/ENV/SERVER adapters before smoke bootstrap. Corrected suite:16 PASS;
dev/test smoke with unusable local DB_URL injections PASS; Pint PASS. The current
G7 effective database smoke already passed the DB_URL injection before this
cleanup, so no foreign-DB connection is claimed. See W00_RUNTIME_REVIEW.md for
independent revision-bound results, not universal isolation or product PASS.

## W01 actual installation and implementation checks, 2026-10-09

These are implementation self-checks after the four official child results were
integrated into the parent checkout. They are **not independent Validation**.
Recorded HEAD is `2114703d208f260c5a63515b3a4c899afff1d46b` plus uncommitted
integrated/runtime edits; the results must not be attributed to HEAD alone or
treated as a fixed final release. Root owns subsequent fixed-SHA publication,
independent security/browser checks and final integration regression.

| Command/check | Result | Bounded actual evidence |
|---|---|---|
| `setup.php` with existing lab | PASS | Existing users preserved; scoped authentication; core migration/settings incremental |
| `extensions.php` initial installation | FAIL, recovered | MySQL1059 exposed an overlong departure index, then native permission registration exposed missing description; lead fixed bundled schema/manifest |
| `recover-partial.php` | PASS | Before any travel migration record existed, exactly2 empty travel tables removed reverse-FK order; no FK guard disabled; applied/populated recovery is refused |
| Native forced travel install + `extensions.php` | PASS | Board/Page/ecommerce/travel active, admin/travel templates installed;10 travel permissions/3 menus; explicit `--sample` plus gated support provisioning |
| Native generated config-cache boundary | PASS | Native lifecycle created a cache; ordinary smoke refused it; lab-only cleanup now runs in lifecycle `finally`, validates DB/egress markers and refuses symlink/installer override |
| `guard-test.php` | PASS |20 cases, including generated-cache cleanup and unmarked/foreign-DB/symlink preservation; fixture only, no DB calls |
| Dev/test `smoke.php` after install/recovery | PASS | Real named schema and array-mail/sync/local-storage configuration; inherited connection sanitizer retained |
| `run.php test scripts/travel-lab/LiveMysqlTest.php` | PASS |1 test/30 assertions,1.480s; root G7 TestCase + real MySQL + actual HTTP routes/native commerce/services/Sanctum; server price24000, tamper422, duplicate replay, owner403/404, cancel/replay no double release, zero orders/payments |
| `live-api-responses.php` | PASS |11 actual **installed root HTTP kernel** replies: catalog/detail/departures/cart/inquiry/user+admin detail/notices/FAQ/private question list/cancel; no route-manual mount for this runtime check |
| `live-concurrency.php` | PASS |2 PHP processes and2 distinct MySQL connections per scenario, direct actual service calls; last-seat200/409, same-key200/200 single allocation, cancel/decline200/409 release once, stock-edit200/waiting-submit409 |
| `live-seed-rerun.php` | PASS | Repeat explicit sample/provision leaves exact existing users/products/options/travel/boards/posts row counts and SHA256 digests unchanged |
| `live-recovery.php` | PASS | Testing schema only: private dump,2 travel migrations rollback/forward, restore; exact8 selected-table digests preserved including inquiry/items/events; private SQL/options deleted |
| `live-env-recovery.php` | PASS | Real ignored env loss reproduced against retained dedicated MySQL account; fresh local DB/admin credentials recovered; existing4 user IDs and travel record digests preserved; APP_KEY/session replacement explicit |
| `live-review-access.php` | PASS | Unique synthetic member/other-member/admin via native UserService/role-ceiling checks; four-hour Sanctum tokens and random passwords only in ignored0600 file within0700 directory |
| Bundled-only final module/template resync | PASS | Official `--force --source=bundled`; module also `--vendor-mode=bundled`; GitHub-first source discovery bypassed with supported flag |
| Preview restart persistence | PASS | Own app preview stopped/restarted; exact3 inquiries/3 items/27 departures/5 events digests unchanged; fresh `/up`200; DB server was not restarted |
| Preview HTTP + reviewer roles | PASS | Loopback `/up`, `/`, actual catalog all200; unique native reviewer admin inquiry list200/member cart200; these are HTTP smoke checks, not visual E2E |
| Browser / HTTP concurrency / fixed-SHA final regression | NOT_RUN here | Lead/official independent reviewers own those gates; direct service concurrency remains distinct from HTTP/browser concurrency |

The test harness seeds native ecommerce **installation** reference data (including
sequences), not ecommerce sample orders. Its first attempt failed because migration
alone does not initialize native sequences; adding the declared non-sample native
seeder resolved that test-environment omission. Module domain tests retain their
canonical SQLite `-c` bootstrap; native board/support tests use the guarded root
MySQL test context. These are separate suites, not interchangeable PASS labels.

Raw logs remain ignored in `storage/logs/travel-live-*.log`. Safe API payloads are
initially in ignored `storage/framework/testing/travel-live-api-responses.json`;
they contain synthetic response bodies only, no authorization headers, passwords
or tokens. The lead can publish those bounded payloads as product evidence after
review. Private review-access JSON must never be copied into evidence/Git.

Portable sanitized evidence is now in
[`deploy/travel-lab/evidence/runtime-self-checks.json`](../../deploy/travel-lab/evidence/runtime-self-checks.json)
and [`actual-api-responses.json`](../../deploy/travel-lab/evidence/actual-api-responses.json).
It includes runtime source SHA256s, working-tree status, distinct connection IDs
and before/after record digests. Latest bundled-resynced service-race replay also
PASSed all four scenarios. Historical results are not silently rebound to a
future integration SHA. Private SQL dumps, envs and reviewer secrets are excluded.

Only `req81_travel_lab_test` is rolled back/restored. The app DB retains synthetic
test history; race fixture products are unpublished afterward. A populated/applied
travel installation is never reset by the partial-install helper. Generated env
recovery has no customer-encryption/session continuity claim: it is a synthetic
lab recovery procedure before preview/reviewer credentials are issued.

## W03 bounded recovery/package repairs

W03-R01's failure branch now performs `DB::purge()` and exact equality of the
entire original eight-selected-table `$before` digest after fallback import.
Only verified equality clears `$damaged` and permits snapshot deletion. Failed
import, digest mismatch or digest query retain the private mode-0700 directory
and mode-0600 dump/options; exception diagnostics report class only, not raw
SQL or secrets. A verified fallback still exits 1 for the original failed gate.
The original W03 review and evidence remain unchanged.

The new `scripts/travel-lab/recovery-failure-test.php` executes the product's
catch/finally text with import/DB/digest stubs and synthetic private files. Four
branches cover verified restore, mismatch, import failure and digest-query
failure. It fails against the original `28ada286` recovery source and passes
against the repaired source. This is control-flow evidence, **not actual MySQL
restore**, fresh install or independent Validation. No shared DB/environment or
preview was touched in this repair.

W03-R02/R03 reproduction instructions now state the locked Node engine
`^20.19.0 || >=22.12.0`, PHP extensions including the SQLite test driver, local
SQL clients/admin prerequisite, and production template build/no-sourcemap
setting. The official subsequent `template:build --production` and bundled
template update path are documented. Actual fresh dependency/build execution is
not inferred from a documentation correction. Scoped hashes, commands and pending
actual/independent gates are recorded in [W03_RECOVERY_REPAIR.md](W03_RECOVERY_REPAIR.md).


W03 support recovery measured TEST55tables104rows and restored exact initial digest at5e3c04bc; earlier128tables639rows preservation is UNKNOWN following terminalFAILED support hardening. APP and own preview were untouched by that child. Final fresh install is still NOT_RUN and must use an exclusive TEST snapshot with actual starting table set. Preview now has four fork workers plus main accepting process; no measured overlapping contention PASS yet.


## W04 lead runtime/recovery delta

See evidence/W04_REPAIR/preview-restart.json and env-loss-recovery.json: actual request-owned restart preserved four travel-table digests; actual marked env-loss regeneration preserved userIDs/travelrecords and new local credentials worked. Only synthetic lab administrator password/scopedSQLaccount/APP_KEY rotated. Core seed skipped existing users; setup guards reject foreign grants. Testschema data was not intentionally changed; historical128/639 preservation remains NOT_PROVEN. New reviewaccess is issued after fixedcheckpoint using current ignoredenv, never old passwords.

Transient req81-travel-lab-preview.service disappears after a fullstop; recreation uses the same assigned workdir/User=ubuntu/loopback18871/4PHPworkers. HTTP200 verified after recreation; no productionunit restarted. Source198 selected installed module/template runtimefiles match; coreinstaller/manifest gates bind separately. Alllead evidence is bounded, not independent finalValidation. EmptyTEST installation/real product HTML/completebaseline restore must rerun after repaired fixedSHA.
