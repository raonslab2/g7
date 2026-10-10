# W04 recipe smoke — read-only evidence intake

## Decision

**ACCEPT, bounded to the independently recorded recipe/native TEST installation, installed-kernel smoke, unchanged standalone MySQL fixture, exact measured restoration and lease-release evidence.** No blocking inconsistency was found in the public source/evidence comparisons below. This is evidence acceptance, not a new execution result, user permission, whole-product approval, hosted CI or canonical Validation receipt.

Reviewed result: CODEX Request `req_bed1c2288b1946de95782fe1a4acff75`, local evidence commit `0e634566f8fb0e06d0eb8f6ce13471a5b3ba4e12`. Its parent is exactly product `992f9a65ac3f8957e5ec618f21072dc499053810`, tree `db85f35c5f42165802ec7ba6b02bc8a147a12c39`. Review read only Git-versioned report/helper/JSON evidence. No child environment file, private backup, raw private log, credential or database was read; no helper, SQL, service or product test was executed. Only this intake document was written. Lead owns evidence cherry-pick, source integration and Git delivery.

The intake reviewer previously authored the three cache-boundary assertions in `LiveMysqlTest` and other test/repair scopes; this review is **not an independent audit of those authored source changes**. The child Request's independently recorded runtime execution and this read-only consistency check remain distinct.

## Commit/source ownership and bindings

- `git diff-tree` gives **97 changed paths**, exactly equal to all 97 entries in `w04-recipe-final/owned-changed-paths.txt`, with no duplicates or paths outside `docs/symphony/W04_RECIPE_SMOKE_FINAL.md` and `docs/symphony/w04-recipe-final/**`.
- All **6 product source pins** in `evidence/intake/source.json` match both the fixed product Git blobs and the evidence-commit blobs. All **21 helper pins** match the actual committed helper bytes. Product code was not modified by the child evidence commit.
- Installed-source evidence contains 31 travel API routes, a native installed module entry point, six class origins and two template manifest origins. All **8 class/template hashes plus the module-entry hash** match their bundled counterparts at product `992f9a65…`. This is the recorded reflection/hash evidence, not a newly observed installed filesystem scan.
- The child's prior readonly reviewer reported 96 staged paths, and the initial publication scan reported 93 paths. Its final review explicitly identifies the subsequent review receipt/manifest additions. Those older denominators are not represented as a review of 97 paths; this intake compares the final actual 97-path commit.

## Published migration helper and cache boundaries

Read `empty-migrate.php`, `bootstrap-check.php`, `fresh-install.php`, `guarded-artisan.php`, `runtime-bootstrap.php`, and the fixed product's `scripts/travel-lab/migration-bootstrap.php`.

`empty-migrate.php` requires the published helper and actually calls `travelLabInitialMigrationEnvironment($pdo, $env, $root)`. It does not duplicate the helper body or manually replace the returned cache setting. It checks genuinely empty TEST, incomplete installation and no installed extension directories before the call. The returned array must equal the original environment with **only `CACHE_STORE=database` changed to `array`**. Only that first migration subprocess applies it.

Recorded bootstrap checks comprise **7 nonempty** and **12 empty** cases, all PASS. They reject wrong schema/account fixture, DB URL, non-database cache, environment priority, lock connection, completed empty install and installed module/template/plugin directories. Genuine empty input changes only `CACHE_STORE`; nonempty and the TEST-only partial table without cache return the entire input unchanged. Before/after full inventory digests agree for each guard group. The wrong-account negative changes an input fixture, not actual account/grants; SQL-object-only creation and live account/grant mutation negatives were not executed.

The normal child `.env` policy remains local/database and its ordinary `.env.testing` remains testing/array, according to guarded source and published evidence; their private contents were not inspected. Subsequent native settings, seed, extension lifecycle and kernel adapters call `w04Effective` with normal database-cache policy. Kernel evidence identifies actual `Illuminate\Cache\DatabaseStore`, counter and lock connections both targeting **`req81_travel_lab_test`**, with no limiter override. The scoped TEST account/transport is recorded as `req81_travel@127.0.0.1`, TCP `127.0.0.1:3306`; this review neither connects nor changes those resources.

The recorded native lifecycle has **18 zero-exit commands plus the separate first migration**: wipe, migration, native settings/core seed, four native module install/activate pairs, two template install/activate pairs, empty-only native `ShippingTypeSeeder`, and explicit travel sample/support provisioning. Per-module vendor modes are re-read after native installation/activation and remain bundled. Default products/travel products are zero; the lifecycle does not seed commerce sample orders/payments. The newly installed measured schema was 112 tables / 1,587 rows, which is distinct from the restoration baseline.

## Installed kernel and unchanged standalone MySQL fixture

The child installed-kernel helper boots the native installed application, performs no manual provider/route mounts, and uses the real HTTP kernel's `handle`/`terminate` path. Its **37 recorded request statuses all match their expected statuses**, including **5 native POST login 200 responses**. These are in-process native HTTP-kernel calls, not a standalone public-index server or browser/network run.

The source and result records agree on native server price 24,000, cart/inquiry 201, identical-id replay 200, non-super administrator review/test acceptance, owner status/cancel with reserved capacity returning to zero, foreign inquiry 404/member-admin 403, private question owner edit/native admin answer, foreign and manager denial, persistent notices/FAQ, and zero orders/payment/notification-log rows. Native product deletion/option guards also execute directly through installed ProductService. The result does not certify new contention/load, restart, attachment-byte or broad regression cases.

Standalone `scripts/travel-lab/LiveMysqlTest.php` is unchanged at SHA-256 `6a9f3b6a273489b292fb3eebbe4986300e78a2e5065c2acc2c3e2a648bee6f7d`. Its first child attempt has **FAIL_HARNESS_BEFORE_TESTS / exit 2 / zero tests / 0.104 s** retained: native bootstrap include scope overwrote the child entrypoint's generic `$path`. The final child entrypoint uses request-specific variables and restores only its own verified TEST `.env` bytes/mode before Laravel boot; original root guards and ordinary array fixture remain intact.

The corrected attempt records **PASS / exit 0 / 1 test / 34 assertions**, PHPUnit 15.100 s, child wall 15.241 s. Its safe stdout explicitly contains `OK (1 test, 34 assertions)`. The fixture calls the existing database throttle-cache trait after migration and actually executes the three added assertions: DatabaseStore, counter database TEST, lock database TEST. Original pricing/person-count/tamper/idempotency/foreign-user/admin-denial/cancel checks remain in the fixed source. This fixture's explicit bundled-route mounting is separate from installed discovery above; it is not 34 tests or a new native installed `ModuleTestCase` suite.

The earlier product cache-fixture failure and atomic-worker missing-stderr **UNKNOWN** are historical evidence, not erased or retrospectively diagnosed by this successful independent run.

## Exact restoration and TEST release

Reviewed `inventory.php`, `snapshot.php`, `guard.php`, `release.php` and public manifests. The measured baseline is **55 tables / 104 rows**, not an assumed earlier 128 tables / 639 rows. Native measurement serializes every row with explicit NULL/binary-safe values, sorts all row encodings with duplicates retained, hashes full row content and `SHOW CREATE TABLE` for every table, and rejects unsupported additional objects. The public inventories expose counts/hashes only. Private original and safety dump retention is recorded; their bytes were not read.

For **original installation**, **first MySQL harness failure**, and **corrected MySQL smoke**, public `before.json` and `after.json` are exactly equal, including complete table sets, per-table row hashes/counts and DDL hashes. Independently recomputing their compact JSON SHA-256 gives:

`ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`

All three restore results record native wipe/import exits 0, import PID gone, exact equality and retained backups. The harness-failure restoration preserves child exit 2; corrected smoke preserves child exit 0. Source removes the pending BLOCKED marker only after equality/import checks, while retaining original/safety backups. The original dump predates explicit dump-PID metadata; this documented gap is not converted into an observed original dump PID. Later imports/dumps and scanner/guard sequencing have separate metadata.

The separate final-before-release inventory equals the release inventory. Recomputed aggregate full/row/DDL digests agree with the release record. That record states **TEST RELEASE `2026-10-09T18:42:38+00:00`**, original equality true, TEST connections after PDO close 0, own native processes excluding observer 0, HTTP processes 0/port closed, competing process/FD/lease 0 and unreadable PIDs none. The command ledger retains **40/40 child records with PID gone**; its latest command timestamp is 18:41:50 UTC, before release. Six original/safety backup retention entries remain. These are historical recorded observations, not a claim that this reviewer observed current process state or authorizes reusing the lease.

## Publication and limits

All 97 final committed paths are text files. A bounded scan found no environment/private-key/raw SQL dump/`__pycache__`/`.pyc` publication paths and no credential/token/private-key/raw SQL-row payload candidates in those committed blobs. SQL statement source literals and `.sql.result.json` metadata are not raw dump publication. The published report/statuses/inventories carry counts/hashes, not contact bodies or credential values. This is a source/text check, not access to private logs/backups or a guarantee beyond the inspected commit.

| Gate | Intake status |
| --- | --- |
| Fixed-source bindings / exact final 97 paths / helper/source comparisons | ACCEPT |
| Published first-migrate helper, native TEST lifecycle, installed kernel 37 calls | ACCEPT recorded bounded independent evidence |
| Unchanged standalone MySQL 1/34 and first harness failure preservation | ACCEPT recorded bounded independent evidence |
| Whole measured 55/104 table/row/DDL equality and recorded release | ACCEPT recorded bounded independent evidence |
| Historical 128/639 preservation | NOT_PROVEN, unchanged |
| Whole public setup/provisioning/account rotation/wizard/public wrapper | NOT_RUN, not waived |
| Native rollback/replay/forced recovery fallback in this child | NOT_RUN; four control-flow scenarios are not native MySQL fallback |
| Restart/env-loss/broad frontend/module regression/attachment HTTP repair | NOT_RUN in this recipe result |
| Lead 22/2230, guards 27, Pint/work-order 89 | INHERITED only; not child execution counts |
| Hosted CI / canonical Validation / remote integration/deployment | Not granted by this intake; separate lead gates |

No new product defect is established by this intake. Lead should preserve both the independent execution and its negative/NOT_RUN boundaries when integrating, and obtain any remaining fixed-version native/CI/Validation checks separately.

## Reviewed artifact hashes

| Artifact at evidence commit | SHA-256 |
| --- | --- |
| `W04_RECIPE_SMOKE_FINAL.md` | `032151e754ccdea49403f9ccec0585db9725d2fa89c681d42b998f9f149c512a` |
| `owned-changed-paths.txt` | `6f99c150dc40aea2ed85eac667e10ea22730929ed1f952dca31e1ff07b5cd271` |
| `evidence/intake/source.json` | `19e17b2f61bafbf0f0ffbaedb4d798b47f0d52a56f579ae92a70a9306c182afb` |
| `evidence/release/result.json` | `9fa070bb0f3cce5a93675f8e6398eb1d3b244e9a814e570f9383fd4717bd967f` |
