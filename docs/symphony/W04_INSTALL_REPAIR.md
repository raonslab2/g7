# W04 bundled dependency package repair

Owner: native `/root/w03_recovery_repairs`, package/scripts only. Recorded parent HEAD `ae9d823c19a9c5aeeb869a6a6b1487ef17497a96`; original independent negative report from `cdd2def9` remains unchanged. **Package guard/real native vendor fixture PASS; complete repaired fresh installation NOT_RUN here.** No APP/TEST DB, environment file, current preview, service, account/password, external network or another Request checkout was modified. No staging/commit/push.

## Diagnosis and canonical mechanism

The original fresh installer accepted `--vendor-mode=bundled` but the ordinary `_bundled` branch called Composer after publishing module data, tolerated failure and never extracted the valid dependency archive. Text descriptions could therefore conceal the missing HTMLPurifier. The existing pending/update paths already expose native `installVendorViaResolver()`, which dispatches to `VendorResolver` / `VendorBundleInstaller` with native manifest/composer/ZIP integrity validation.

The minimal native fix proposed to the lead is to use that resolver for ordinary module installation, respect the requested mode, and propagate mandatory dependency failure before migration/DB publication. Pending-path failure must likewise not be silently swallowed. Core/native lifecycle, regression tests and version constraints belong to the lead; this assignment changes none of them. A forced update/manual library copy does not make the original fresh-install result pass.

Package `extensions.php` now explicitly supplies `--vendor-mode=bundled` to native module installation. Before any extension lifecycle, it executes the native integrity checker against the checked-out ecommerce source bundle. After ecommerce installation and before its activation or any travel sample/support provisioning, it executes a fresh PHP process that checks the actual installed dependency. The same gate applies to already-active installations and repeats before `run.php preview` starts a server. Failure exits without an activation/seed/runtime PASS.

`vendor-check.php` requires dependency-only root Composer autoload; it never boots the Laravel application, reads environment files or connects to a DB. It requires a real in-module `vendor/autoload.php`, rejects a vendor-directory escape or symlinked autoload, checks reflection origins of actual HTMLPurifier/Config under the installed ecommerce vendor, matches HTMLPurifier's exact runtime version to ecommerce `composer.lock`, and performs real HTML sanitization with serializer cache disabled. Its controlled failure output includes only a failure class, no exception/SQL/credential text. The installed check does not install or repair anything.

## Actual bounded verification

Executed after scoped Pint formatting:

```sh
php scripts/travel-lab/vendor-check-test.php
php scripts/travel-lab/vendor-check.php --bundled
php -l scripts/travel-lab/vendor-check.php
php -l scripts/travel-lab/vendor-check-test.php
php -l scripts/travel-lab/extensions.php
php -l scripts/travel-lab/run.php
git diff --check -- scripts/travel-lab/extensions.php scripts/travel-lab/run.php deploy/travel-lab/README.md
```

All commands exited 0. The **8-case real filesystem/native dependency fixture**:

| Case | Result |
| --- | --- |
| Missing installed autoload rejected before installation | PASS |
| Actual supplied native archive integrity | PASS |
| Actual native resolver/bundle installer offline extraction | PASS |
| Actual installed library origin, locked version and HTML sanitization | PASS |
| Globally loaded library cannot hide another module's missing vendor | PASS |
| Corrupted source archive rejected by package preflight | PASS |
| Corrupted source archive rejected by native resolver | PASS |
| Removed vendor rejected despite in-process class cache | PASS |

The fixture copies the source bundle, manifest, Composer files and native helper into a private random path under this checkout's `storage/framework/testing`, binds only native filesystem/null-log/translation services, and invokes the **real** native `VendorResolver`/`VendorBundleInstaller`. No Composer executor is supplied; explicit bundled mode never requires Composer/environment detection or foreign calls. The actual packaged HTMLPurifier v4.19.0 removes script/event/JavaScript URL content while preserving allowed markup. Fixture data is removed after execution, including test failure. It neither uses nor edits installed modules.

Native source archive SHA-256: `713f98578a866fada6782d11f8c80ff13037f1faf0edaaa55f004ddc4e80d1b9`; real source integrity preflight reports one package. These are vendor filesystem/library checks, not fresh core/module migration or native ProductService transaction checks. Four PHP syntax checks and scoped whitespace checks pass.

## Source pin

Five authored package/source files, excluding this report, scope SHA-256 **`f89bf86315cc7a8667ad9a7ff8003ca7a60a08dc87d6f9df9ef539acfc20b315`** (sorted `path + NUL + file_sha256 + newline`):

| File | SHA-256 |
| --- | --- |
| `deploy/travel-lab/README.md` | `a3275c87d86b25ae29be9801aed17d12ac6b8f7b08a3fd191b9cb2dbc1fbb62e` |
| `scripts/travel-lab/extensions.php` | `f438dc99c1da543c23d901e15376b73c1dbc1be6d5bd219f007ee48d6f9d1ad5` |
| `scripts/travel-lab/run.php` | `6b9d1af8a2b48ca8af88cc3910622ee2f601b7a57d5411233f697cd58566816f` |
| `scripts/travel-lab/vendor-check-test.php` | `f4265413f9bf8c379605d1307f3299ba8bd14969dd1078f56f7a33d018f75db0` |
| `scripts/travel-lab/vendor-check.php` | `38322c8b64774d6712d84db3195e242f50ae43d14b26a8d27232c97eaa2158e6` |

If lead version synchronization changes ecommerce `composer.json`, its native bundle manifest must be rebuilt through the official bundle mechanism; the new integrity preflight intentionally rejects stale composer hashes. No shared manifest or archive was edited here.

## Required remaining gate

The lead must integrate the native installer correction and this package at a fixed SHA, then have a nonauthor verifier execute a clean empty-TEST native install with foreign calls blocked, prove the actual installed dependency, exercise native HTML product create/update and travel/support flow, and restore the entire starting schema with row/DDL equality. Installed hook discovery, preview/UI, CI and canonical Validation remain separate gates. The original W04 **CHANGES_REQUIRED** report is not overwritten or converted to PASS by these fixture results.
