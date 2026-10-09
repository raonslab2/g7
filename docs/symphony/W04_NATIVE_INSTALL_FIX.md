# W04-R01 — native module vendor installation gate

## Problem and resulting behavior

A native ordinary `_bundled` installation at product SHA `7de0c4441b68b1c012dbf4a75114200e322051c9` called the module install hook, migrations, DB registration, seed and settings before a direct Composer invocation. That invocation ignored the requested `VendorMode::Bundled`, and failure only logged a warning. A missing dependency could therefore be discovered after irreversible DDL/data work, or the command could finish despite unresolved dependencies. The pending path used the resolver earlier but swallowed its failure before copying to the active directory.

`ModuleManager::installModule()` now uses the existing `installVendorViaResolver()` before extension autoload registration, entry/lifecycle loading and all migration/data phases. The native signature and `VendorMode` contract remain unchanged:

- `_pending` dependencies are prepared before active copy. Native `VendorInstallException` propagates immediately instead of being swallowed/retried after copy. This also protects an existing active directory during `force=true` pending replacement.
- Ordinary bundled/active installs prepare dependencies in the active copy, inside the existing installation rollback guard. A failed first install removes its newly created active directory and leaves the `_bundled` source untouched.
- Existing `VendorResolver` handles explicit Composer/Bundled and Auto selection, bundle integrity and extraction. The resolved mode reaches the existing `vendor_mode` registration field; Auto is not stored when a dependency strategy has actually resolved it.
- A successful pending resolution is not performed a second time in the active copy.
- After either successful preparation, the active `vendor/autoload.php` is loaded with the existing native `require_once` convention before module entry/loading/install. Extension PSR-4 registration alone does not load third-party package classes or `autoload.files` helpers. This load remains inside the existing Throwable rollback guard; it does not invoke late DB-based autoload publication early.
- No-external-dependency and `testing` policy bypasses retain their existing intent. No new installer/resolver API or core dependency was introduced.
- The late direct Composer/warning block is removed. Dependency failure does not enter install hooks, migrations, DB registration, seed or settings.

The actual native exception is `VendorInstallException`; the repository contains no separate `PendingVendorInstallException` class. Version requirements and CHANGELOG changes are owned by lead, outside this author's file scope.

## Owned paths and fixed review input

- `app/Extension/ModuleManager.php`: only the native `installModule` dependency ordering/failure handling changed (37 insertions, 33 deletions).
- `tests/Unit/Extension/ModuleVendorInstallGateTest.php`: new isolated behavior tests.
- `docs/symphony/W04_NATIVE_INSTALL_FIX.md`: this report.

Current author source SHA-256 after Pint and the independent review's autoload repair:

- ModuleManager: `bcd279e3be09f5c43b117aa8fec90e64cc1b6ba06ce7a47a7683ca19ef4455ed`.
- Gate tests: `e0128b8e31ad573109b0a27c369a0318334842ee685e88fdc80440ded2ba9ccd`.

The first review input was ModuleManager `ce615b2cfd86eed107c259aaf90ee5bdc87dbb4ab6aab553443d79ce85c8bb1b` and test `cb95f4a910c22d7de006ade9131775e46d3d6234802776d6b44e78c81e864cf2`, with **12 / 75 author PASS** independently rerun by the nonauthor. That review identified the missing runtime package loader: file preparation did not ensure dependency resolution before entry/install. This finding is preserved; the new source and three actual-entry regressions address it and require a review reset.

These hashes bind the working-tree review input before lead's checkpoint. They are not an integrated Git SHA or deployment attestation. No files outside this ownership were edited, and no stage/commit/push was performed by this native subagent.

## Executed checks

Environment: PHP 8.3.6, PHPUnit 11.5.56, ZipArchive available. Timestamp: 2026-10-09T13:14:30.607088+00:00.

| Check | Command | Result |
|---|---|---|
| Current native gate cases | `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/ModuleVendorInstallGateTest.php` | **PASS 15 tests / 109 assertions**, final post-Pint execution 0.955 s, 24 MiB |
| Existing autoload-order regression | `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/ExtensionInstallAutoloadOrderTest.php` | **PASS 2 / 10**, with **1 existing PHPUnit metadata deprecation**; not a clean no-issues suite claim |
| Formatting | `vendor/bin/pint app/Extension/ModuleManager.php tests/Unit/Extension/ModuleVendorInstallGateTest.php` | PASS |
| PHP syntax | `php -l app/Extension/ModuleManager.php` and `php -l tests/Unit/Extension/ModuleVendorInstallGateTest.php` | PASS |
| Baseline defect reproduction | Same new test file, filter `/(test_dependency_failure_stops_before_lifecycle|test_composer_failure_propagates)/`, temporary bootstrap loading the exact frozen `7de0c444…:app/Extension/ModuleManager.php` class | **EXPECTED FAIL: exit 2, 5 cases / 5 errors**. The old native method crosses into the forbidden lifecycle lookup instead of propagating the expected vendor failure. Original repository source was not rewritten. |
| Independent-review loader defect reproduction | New actual-entry cases, `--filter test_prepared_vendor_autoloader`, against the first-review source before adding early vendor loading | **EXPECTED FAIL: exit 2, 3 cases / 10 assertions / 3 errors**, actual package class not found at native `reloadModule()` entry in ordinary bundle, pending bundle and Composer callback modes. After early loading, final full suite above passes all three. |
| Scoped whitespace check | `git diff --check -- app/Extension/ModuleManager.php tests/Unit/Extension/ModuleVendorInstallGateTest.php docs/symphony/W04_NATIVE_INSTALL_FIX.md` | PASS |

The baseline reproduction uses an ephemeral bootstrap: require this checkout's `vendor/autoload.php`, then evaluate the exact Git blob with its opening PHP tag removed before ModuleManager can autoload. PHPUnit invokes the actual original method in a separate process. Temporary fixtures/bootstrap are removed; it does not boot the installed app or access SQL.

## Coverage and isolation boundaries

The 15 cases exercise actual native `ModuleManager::installModule`, filesystem copy/rollback, `VendorResolver`, `VendorIntegrityChecker` and `VendorBundleInstaller`:

1. Ordinary bundled archive missing: exception before lifecycle/DDL/data, new active copy removed, source retained.
2. Ordinary bundled checksum corrupt: same fail-closed behavior.
3. Pending archive missing: exception before any copy/publication.
4. Pending checksum corrupt under forced replacement: existing active marker unchanged, no copy phase.
5. Explicit Bundled despite available Composer: real checksum/extraction, no Composer callback.
6. Auto with Composer unavailable: real bundle fallback and resolved Bundled metadata.
7. Explicit Composer: existing executor callback invoked once, resolved Composer metadata.
8. Auto with Composer available: resolver selects that same callback strategy.
9. Pending bundle success: prepared vendor copied, no second resolution/execution.
10. Composer callback false: exception before lifecycle, first-install active copy removed.
11. No external dependencies: no resolver required even without a bundle.
12. Existing testing policy: dependency preparation remains skipped.
13. Ordinary bundled real entry/install: native lookup/reload loads an actual AbstractModule fixture that calls a prepared dependency class and helper function at both entry and install.
14. Pending bundled real entry/install: the copied active vendor loader, rather than the pending source loader, resolves the same actual dependency calls.
15. Composer callback real entry/install: generated fixture files are prepared by the executor callback; the native manager, not the test, loads their actual package autoloader before entry/install.

Tests extend `PHPUnit\Framework\TestCase`. They create an **unbooted in-memory Application** rooted in a unique temporary directory, bind only filesystem/config/translator/no-op logging and mocks, and restore the previous Container/Facade context. No dotenv, project service provider, MySQL/SQLite connector, installed source tree, APP/TEST schema, external package process or network is used. A DB facade mock fails on unplanned calls; repository collaborators are mocks.

The original twelve cases use injected lookup/lifecycle boundaries. The five successful strategy cases let native control flow reach the real registration attribute construction, capture `vendor_mode`, then deliberately throw before repository persistence. Migration/DB boundaries are recorded mocks, not real DDL/DML. Seed/settings are forbidden in this controlled fixture. This verifies sequencing and metadata without disguising a mocked transaction as a fresh-install PASS.

The three additional actual-entry cases each run in a separate clean PHP process with PHPUnit global-state preservation disabled. They keep `getModule()`/`reloadModule()` native and write a real AbstractModule entry file. The archive or Composer callback prepares a real Composer ClassLoader, dependency source class and function helper loaded via that fixture's `vendor/autoload.php`. Neither package class nor helper is present before installation. Entry and install each invoke both; phase records, exactly one loader invocation, and reflected module/class/function paths prove that the active fixture files were loaded. Install intentionally throws before migrations/DB, and native rollback removes the created active directory. Hook registration and middleware-index cache calls alone are bounded mock collaborators during reload to prevent installed-module/DB/cache enumeration. No actual project provider, SQL connector, runtime service or Composer executable participates.

## Remaining verification and integration duties

- Independent review of this fixed input is pending and must come from a nonauthor.
- Actual native fresh Travel Lab installation in the existing isolated TEST schema, complete lifecycle, seed/settings/permissions, runtime routes and dependency availability remains **NOT_RUN by this author**. The official package/runtime owner must rerun after lead checkpoint and restore its full database snapshot with the existing recovery gates.
- The original fixed-SHA `CHANGES_REQUIRED` reports remain valid records; this patch does not retroactively change them.
- Hosted CI, canonical Validation, integration and deployment are separate lead-owned states. No operational service or customer data was changed.
- Native plugin/template installation paths were not edited or broadly certified by this module-only fix.
- Existing ordinary bundled `force=true` replacement can overwrite a pre-existing active directory before its dependency resolver fails, and the existing rollback helper does not restore a directory that existed at entry. This legacy replacement/backup limitation is **not repaired or certified** here. Pending dependency failures are stopped before forced copy; that narrower protection is tested. Already-loaded PHP classes/functions cannot be unloaded by filesystem rollback; these fixtures use clean processes and do not claim same-process package-version rollback.
