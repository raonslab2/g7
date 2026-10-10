# W04 domain harness repair — isolate canonical Module declaration

- Timestamp: 2026-10-09T13:26:58.743735+00:00.
- Lead Request: `req_81ac33cac94046b9a2249cd14c0d00ba`.
- Base HEAD during this focused check: `ae9d823c19a9c5aeeb869a6a6b1487ef17497a96`; this is a working-tree test-only repair, not a remotely integrated revision.
- Owned files: `modules/_bundled/raonslab-travel_lab/tests/Feature/W03SecurityRecheckTest.php` and this report only.
- Production `ModuleManager` and the installer repair under independent review were not edited. APP/TEST databases, environment files, services and runtime configuration were not modified.

## Failure, scope and diagnosis

The lead's retained `storage/logs/w04-candidate-domain-regression.log` shows the 152-case suite aborting after its first 133 completed cases, at the recheck's direct bundled `module.php` require. It reports `Cannot declare class Modules\Raonslab\TravelLab\Module, because the name is already in use` at bundled `module.php:12`.

Focused baseline command reproduced the same failure in the current installed-source context:

```sh
php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter W03SecurityRecheckTest
```

**Before: exit 255 after six completed cases; no complete test/assertion total.** The failure occurs when the declaration test executes `require_once` of the bundled module entry in a long-lived test application that has already encountered the same module class through native installed-source loading. `require_once` deduplicates file paths, not PHP class names. Installed and bundled entries are different paths even when their bytes agree.

This is a test-origin/class-identity collision, not a failing price/permission/lock assertion or evidence that the native installer vendor gate itself regressed. The failure was not masked by excluding the test, changing production entry loading, weakening assertions or suppressing PHP errors.

## Repair and maintained behavior

Only the module declaration inspection moved into a clean PHP subprocess, following the existing `W03CatalogRepairTest` pattern. The child requires this checkout's Composer autoloader and the exact realpath of bundled `module.php`; it does not boot Laravel or connect to a database.

The child reflects the instantiated actual `Modules\Raonslab\TravelLab\Module`, returning its source realpath, SHA-256 and `getHookListeners()` values as JSON. The parent asserts child exit 0, the exact canonical source path and the hash taken before the probe, then checks that its own preexisting Module-class origin is unchanged. An installed-class result with the same content hash would still fail the canonical-path check.

All original behavioral assertions remain:

- `ProtectTravelCommerceCatalog` is actually declared by that bundled Module.
- Native before-delete and before-update subscriptions are synchronous; the final update-data hook is a filter at `PHP_INT_MAX`.
- The real parent-process `HookListenerRegistrar` registers the guard.
- A lower-priority native filter stripping options is rejected by the real `ProductService` path.
- Product name remains unchanged and the departure-linked option remains present.
- Five UTC/KST boundary cases, orphaned-cart checks and route/throttle checks are unchanged.

The parent application's real domain/provider/route behavior continues using the canonical SQLite test launcher. Class declaration JSON is not a mock Module implementation, a replacement API or an installed-runtime lifecycle PASS.

## Executed evidence

| Check | Command | Result |
|---|---|---|
| Focused baseline | Command above, before the test-only edit | **REPRODUCED FAILURE**, exit 255 after six cases; duplicate Module class at bundled entry line 12 |
| Focused repair | Same command above, after formatting | **PASS 8 tests / 142 assertions**, 3.684 s, 74.50 MiB |
| Formatting | `vendor/bin/pint modules/_bundled/raonslab-travel_lab/tests/Feature/W03SecurityRecheckTest.php` | PASS |
| PHP syntax | `php -l modules/_bundled/raonslab-travel_lab/tests/Feature/W03SecurityRecheckTest.php` | PASS |
| Scoped whitespace | `git diff --check -- modules/_bundled/raonslab-travel_lab/tests/Feature/W03SecurityRecheckTest.php docs/symphony/W04_DOMAIN_HARNESS_REPAIR.md` plus explicit new-report newline/trailing-whitespace check | PASS |

The prior reported focused total was 8/137. The repaired test retains those assertions and adds five subprocess/source-origin/parent-class-preservation assertions; the truthful new total is **8/142**, not 8/137. No extra test cases were created and no suite exclusion was changed. The command uses module `tests/bootstrap.php`, whose pinned environment/database configuration is SQLite `:memory:`; the base case additionally asserts the SQLite driver, in-memory database and canonical class source paths.

Input hashes for lead's next checkpoint:

- Previous committed test: `fdd1432c7b3fb28eb4716e4495faccee9eed0202b8ec5788d04aea9b17251b5d`.
- Repaired test: `3b872cbce88be955d290325a3aec4f08f9b31964e1a2fb8456831775b29278c0`.
- Bundled `module.php` declaration probed: `f350cb830b51041b1a22a43152a9c7c2d60ad06cf50b8573e9fc8e2c3b1dcf09`.

## Remaining lead gates and attribution

Full 152-case regression after this repair is **NOT_RUN by this author**; lead should execute it once on the repaired candidate. Canonical Validation, hosted CI, integrated SHA, package fresh-install and post-integration runtime checks remain separate states.

The original independent security report and its tested SHA/8/137 record remain historical evidence; this harness repair does not retroactively rewrite them. This author implemented the original commerce guard and now fixed its test harness, so these focused results are author execution evidence, not an independent guard approval. Lead owns subsequent independent review/Git delivery. No stage, commit, push or deployment was performed by this native subagent.
