# W03 support artifact recovery and bounded verification

Request: `req_360fde09455c407f9fa817a3c3767351`; parent remains `req_81ac33cac94046b9a2249cd14c0d00ba`.

The former CLAUDE request remains canonical **FAILED**. This review recovered its Git artifact without regenerating/cancelling that request. Original target: `3d0f7a99d012c6f50c462fa3ee73d80b8dc93478`, tree `34ac977027dab613e1dc3ffb1ab03a140b1156fc`. The object existed locally; own checkout is isolated branch `recovery/req360-w03-support`. The target is an **unpublished dependency** per the user's supplied status; no local remote-tracking branch contained it. No fetch, push, PR, merge, deployment, official child Request, or canonical Validation receipt was attempted/claimed. The original final report/untracked evidence is unavailable in Git.

All 12 committed support paths and support documentation were reviewed. Parent Catalog/UI/guards working changes are outside this target. Spring, APP schema, preview, parent settings, root guard and common files were not changed. Ordinary marked parent `.env.testing` was copied privately (0600) to own `.env.testing` and own `.env`; APP credentials were not copied. Dependencies were copied into own vendor; source origin is pinned to own `_bundled` paths.

## Findings and authored fix

**Original security result: FAIL.** Travel-only manager cannot read/update foreign questions, and native board+travel grants are required, but owner scope could still be bypassed when a restricted grant lacks native owner metadata. `PermissionHelper::checkScopeAccess()` returns true without `owner_key`/`resource_route_key`. This affects travel `support.read/update` and board `admin.posts.write`. Added regression on original service code observed foreign GET **200 where 404 was required**: `scope-red/native.txt` (1 test, 2 assertions, 1 failure).

**Authored fix check is separate from original nonauthor review.** Source checkpoint `df063262693f5b9c95f11bc9a404171f0dfb6b97`, tree `7fc6eb8a8876fa7b1551315785a22c1fc49b419e`, explicitly checks question `user_id` against every required grant's effective scope, while retaining native helper checks. `self` requires the owner; `role` requires a shared native role through repository lookup; unknown restricted scope denies. Authors retain access to their own questions. Restricted lists remain owner-only. This change is confined to the assigned support service/repository contract/repository/test/docs/scenario files.

New tests exercise absent owner metadata on all three affected grants under self/role scopes, native board role-peer detail/PATCH versus foreign denial, self PATCH, and each of the 16 permission keys with foreign role, empty roles and missing identifier. Provisioning rejects tampering without repairing operator changes. Existing tests verify `use_comment`/`use_reply`, native `PostService::updatePost` hooks and persisted `post.update` activity, secret retention and blocked notifications/mail.

One supported native helper performed read-only review at the fixed source SHA above, without authoring, DB access, tests, external calls or publication. No new blocking defect was found. Its review is recorded in `w03-support/evidence/readonly-review.json`; this is internal source/evidence review, not official independent Validation. The lead's separate CLAUDE security verification of the new fixed SHA remains outside this request.

## TEST state and recovery

First read-only measurement found **55 tables / 104 rows**, zero other observable TEST connections. No PHPUnit/Artisan-test/mysqldump/former failed runner was observed before testing. Earlier independent **128 tables / 639 rows** was supplied as historical context only; it differs from current state. **Original baseline preservation is NOT_PROVEN.** No attempt was made to reconstruct it or claim it restored.

Before every native RefreshDatabase run, the wrapper validates the marked TEST environment and exclusive connection observation, takes a whole current TEST mysqldump into a private 0700 directory/0600 file, checks successful dump completion and stable table/row/DDL digests, and writes a BLOCKED marker before allowing tests. Row hashes use sorted base64-encoded cell arrays, preserve null separately, and include duplicate rows. Restore removes the current TEST table set and imports the validated snapshot into the same allowlisted TEST schema. It compares exact table names, all row counts/digests, and DDL hashes, including auto-increment state. APP is never connected.

The initial wrapper retained a PDO connection during native tests. Automatic restore did not finish; its snapshot/BLOCKED were retained, and explicit TEST-only recovery restored exact current state. Native `Tests/TestCase.php::killStaleTestingConnections()` kills other same-TEST connections; this explains the parked PDO failure by source/process evidence, but the terminal exception was not captured. The corrected wrapper releases PDO before its child and reconnects before restoration. See `original-recovery-controlflow.json` and original restore evidence. Later automatic restorations succeeded.

Final restoration status and per-class counts are recorded below. Successful snapshots may be removed from the disposable worktree once exact recovery is confirmed. On failure the script retains the private snapshot and BLOCKED marker and aborts; neither credentials nor row snapshots are committed. Durable Git evidence contains hashes/counts, commands, output and outcome, not private environment values.

The own TEST-only bootstrap adapts exact committed `fe3b2f23cc54d452a17ae552e9613b5d37525ede:docs/symphony/w03/test-bootstrap.php`: it only changes the four reflected source classes to support classes. Identical own TEST `.env` is hidden during root name comparison and restored byte-for-byte/0600 before Laravel boot, including on bootstrap exit. The root guard and shared settings are untouched.

## Bounded commands and results

All paths below are relative to the own checkout; detailed command arrays, exit codes and JUnit are committed under `w03-support/evidence/`. Only one DB test process ran at a time. No unrelated broad suite or frontend build was run; host initial load was 18.10/14.44/17.67.

```sh
php docs/symphony/w03-support/test-db.php measure
php docs/symphony/w03-support/test-db.php run php vendor/bin/phpunit -c docs/symphony/w03-support/evidence/native.xml --do-not-cache-result
php docs/symphony/w03-support/test-db.php restore
W03_RECOVERY_LABEL=scope-red php docs/symphony/w03-support/test-db.php run php vendor/bin/phpunit -c docs/symphony/w03-support/evidence/native.xml --filter restricted_grants_without_owner_metadata --do-not-cache-result
W03_RECOVERY_LABEL=scope-green php docs/symphony/w03-support/test-db.php run php vendor/bin/phpunit -c docs/symphony/w03-support/evidence/support-fixed.xml --filter 'restricted_grants_without_owner_metadata|native_board_role_scope|all_sixteen' --do-not-cache-result --log-junit docs/symphony/w03-support/evidence/scope-green/junit.xml
W03_RECOVERY_LABEL=auth-isolated php docs/symphony/w03-support/test-db.php run php vendor/bin/phpunit -c docs/symphony/w03-support/evidence/auth.xml --do-not-cache-result --log-junit docs/symphony/w03-support/evidence/auth-isolated/junit.xml
W03_RECOVERY_LABEL=support-final php docs/symphony/w03-support/test-db.php run php vendor/bin/phpunit -c docs/symphony/w03-support/evidence/support-fixed.xml --do-not-cache-result --log-junit docs/symphony/w03-support/evidence/support-final/junit.xml
php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --list-tests
```

The original mixed batch executed **104 tests / 328 assertions**, with **14 setup errors** all in `GatePermissionTest` (duplicate preexisting `admin` role after board scope tests). The other 90 tests passed. This original mixed batch is **FAIL**, not overall PASS. Auth was rerun once in a separate native process after confirming that isolation condition: **14 tests / 40 assertions PASS**, exact TEST restore. No core test was altered. The red security regression intentionally failed; the authored green subset then passed **3 tests / 277 assertions**, exact restore.

Final authored-fix results: **93 support/board/scope tests / 607 assertions PASS**, plus **14 isolated auth tests / 40 assertions PASS**: **107 distinct selected native tests / 647 assertions**, zero failures/errors/skips. All five native run/recovery snapshots restored the same exact **55 tables / 104 rows**, including every row and DDL hash. Final independent read-only measurement equals the first current-state measurement and observes no other TEST connection. Private snapshots were removed only after success; own environment files remain identical regular 0600 TEST files. See `final-results.json`, `final-hygiene.json` and per-batch JUnit.


| Final native class | Tests | Assertions | Result |
| --- | ---: | ---: | --- |
| `W03SupportHardeningTest` | 10 | 345 | PASS |
| `TravelSupportApiTest` | 7 | 90 | PASS |
| `TravelSupportNotificationTest` | 2 | 15 | PASS |
| `TravelSupportProvisionerTest` | 5 | 48 | PASS |
| `BoardActivityLogListenerTest` | 42 | 78 | PASS |
| `PermissionScopeTest` | 16 | 17 | PASS |
| `PermissionHelperScopeTest` | 11 | 14 | PASS |
| `GatePermissionTest` | 14 | 40 | PASS |

Canonical module SQLite configuration **PASS (configuration/list-only)**: it excludes all four native MySQL support classes including W03; the `--list-tests` result confirms absence. The SQLite suite itself is **NOT_RUN** in this bounded support recovery scope. Changed source Pint check and Git diff whitespace check pass; see saved output.

## Search contract and limits

Actual native source was inspected: `Post::shouldBeSearchable()` considers only whether driver differs from `mysql-fulltext`; `searchIndexShouldBeUpdated()` invokes the actual board filter. Scout ImportCommand calls `makeAllSearchable()`; SearchableScope filters by `shouldBeSearchable()` and bypasses the board update filter. Native `SearchPostsListener` uses active searchable boards and read permissions. The module rejects Questions requests with 503 and provisioning with 409 for every configured driver other than `mysql-fulltext`, including collection/null drivers. This strict restriction is the supported fail-closed contract.

**Actual external engine: NOT_RUN.** Original W03's CollectionEngine recording test is a local spy contract test, not Meilisearch/Algolia/network verification. It demonstrates native restore removal and bulk-import exposure with that recording engine. No fake protection hook or global Post-model override was added. For existing private rows, changing to an external driver and running `scout:import` can still export content. A queued `MakeSearchable` job directly invokes engine update and can reindex after synchronous removal; the unsupported queue guarantee/comment and `unsearchable()` documentation mismatch were corrected to describe `unsearchableSync()` accurately. No external containment PASS is claimed.

This verifies the isolated support artifact and authored repair only. Final product/provider integration, parent working Catalog/UI/guard changes, preview end-to-end behavior, external engines, release CI, deployment and canonical Validation are **NOT_RUN / NOT_CLAIMED** here. No version/manifest/common-file change was made; the lead owns eventual integration/versioning. Exact target paths/source hashes, private-env booleans, tool versions and source-SHA binding are in `source-binding.json`.

Authored support files (exact):

- `modules/_bundled/raonslab-travel_lab/docs/support-api.md`
- `modules/_bundled/raonslab-travel_lab/docs/support.md`
- `modules/_bundled/raonslab-travel_lab/src/Listeners/ExcludeTravelSupportQuestionsFromSearch.php`
- `modules/_bundled/raonslab-travel_lab/src/Repositories/Contracts/TravelSupportPostRepositoryInterface.php`
- `modules/_bundled/raonslab-travel_lab/src/Repositories/TravelSupportPostRepository.php`
- `modules/_bundled/raonslab-travel_lab/src/Services/TravelSupportService.php`
- `modules/_bundled/raonslab-travel_lab/tests/Feature/W03SupportHardeningTest.php`
- `modules/_bundled/raonslab-travel_lab/tests/scenarios/travel-support.yaml`

New recovery scripts/report/evidence live only in `docs/symphony/w03-support/` and this report. A complete SHA-256 file inventory is committed as `w03-support/evidence/delivery-files.json`; the final delivery commit adds docs/evidence only atop the fixed source checkpoint.
