# W04 vendor mode persistence repair

Status: **AUTHOR_FIX_TESTED / NONAUTHOR_REVIEW_PENDING**. This is a bounded native persistence repair, not official Validation, fresh MySQL installation, or whole-product PASS.

## Defect and scope

Failed runtime Request `req_337b3638df9b4b958b0627e0b262a54a`, source `598a89fff702d51c1405f1a5952d95ab1d2651f4`, registered native modules with stored `vendor_mode=auto` despite requested/resolved bundled mode. Preserve that negative evidence and the interrupted TEST recovery finding in `W04_INSTALL_INTERRUPTION_INTAKE.md`.

`ModuleManager.php:494` and `:4572` pass the resolved native mode string to the repository. Actual `ModuleRepository::create`, `update`, and `updateOrCreate` use Eloquent mass assignment. `Module::$fillable` omitted `vendor_mode`, so Eloquent silently discarded it and the existing migration's `auto` database default remained. Mocked repository attribute-capture tests could not detect this persisted-state defect.

Changed owned files only:

- `app/Models/Module.php`: allow `vendor_mode` mass assignment. No enum cast or public signature change; native manager readers still receive a string. Pint also adds one missing trailing comma in the existing `isInstalled` array.
- `tests/Unit/Extension/ModuleVendorModePersistenceTest.php`: actual native repository/model and native vendor-mode migration with a process-local SQLite `:memory:` database.
- This report.

The test creates minimal modules/plugins fixture schemas, then invokes `2026_04_14_000001_add_vendor_mode_to_modules_and_plugins_tables.php` unchanged. No application boot, dotenv, SQL server, production/test schema, network, external Composer process, service operation, mocked repository, or fake attribute-capture persistence is used. Eloquent connection resolver, event dispatcher, discard protection, container, and facade application are restored afterward. Guarding remains enabled.

## Fail first and verification

Command for both runs:

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/ModuleVendorModePersistenceTest.php
```

| Execution | Result | Actual scope |
| --- | --- | --- |
| Before production fix, initial nine-case test set | **FAIL**, 9 tests / 59 assertions / 6 failures; 0.334s, 22 MiB | Bundled/composer create and update, install-style updateOrCreate, and guarded-field case persisted `auto` instead of requested mode |
| Immediately after fix, same nine-case set | **PASS**, 9 / 83; 0.133s, 22 MiB | Actual reloaded row values, metadata/config JSON, default and protected attributes |
| Final strengthened set | **PASS**, 10 / 92; 0.131s, 22 MiB | Also reset bundled to explicit auto through update, and preserve existing bundled mode when updateOrCreate omits mode |
| Pint on both owned PHP files | **PASS** | Final `--test` run |
| PHP lint on both PHP files | **PASS** | Actual syntax checks |
| Scoped tracked diff whitespace check | **PASS** | `git diff --check -- app/Models/Module.php` |
| Actual native MySQL fresh install and requested bundled requery | **NOT_RUN** | Independent official fixed-SHA runtime attempt required after baseline-only TEST recovery releases exclusivity |

Assertions reload through the real repository and additionally read the actual stored SQL column. Modes remain strings compatible with native `VendorMode::tryFrom`. Existing JSON name/config/metadata survives create/update. Omitted mode obtains the native migration default; native string fallback for null/empty/unknown remains auto. Guarded primary key and existing unfillable `is_active` cannot be assigned through ordinary create/update. The final suite is an expanded set, not an additive total with the earlier runs.

## Frozen source attribution

Recorded parent HEAD at final author execution: `cf7a2be44ad2fb9e2b55c7a39657df219fde1cd9`. Repair is an uncommitted owned diff; lead owns Git delivery and shared version/changelog synchronization.

| Path | SHA-256 |
| --- | --- |
| Module before repair (also matches failed runtime source) | `1b286fe363fa2f237ed2f10a2f5aae32dd56ea1481061ad3eebeb87e3af7ad75` |
| `app/Models/Module.php` after fix/Pint | `3c853703e6098be6aa11f01ca14b533ffddca1ab5fa882ac5ae291adb6c63f9b` |
| Final `tests/Unit/Extension/ModuleVendorModePersistenceTest.php` | `e018ffb131c1d71b45b10ad1cd8c9878124e15ae0c5455ad220d4a9c63933d72` |
| Unchanged `app/Repositories/ModuleRepository.php` | `b0a684863e4700d2a7b6091e87b1a282d27aa53253a35d7444c385014f3ed0de` |
| Unchanged native migration | `8b2c02a5ad6dd50744663df1f17301151e0cda5e343e70709f9bddcb3f8dcfad` |

Two-file frozen source scope digest: `3fb9339d156c9b2061b49e733b6303a5bccb88e9b0d364da76fb29f099145979`, computed as SHA-256 of Python `json.dumps({path: sha256(file_bytes)}, sort_keys=True).encode()`.

## Adjacent finding and remaining work

`app/Models/Plugin.php:36` also omits `vendor_mode` from `$fillable` although the same migration creates that column for plugins. This is a source-only adjacent finding, not an independently reproduced plugin defect. Plugin source remains unchanged at `fa4260e5cb4e60e2a7ab0f4bc9cbfc28e1944fcb11c8b1ecf1b49cf76e93a344`; lead decides any separate scope.

Nonauthor fixed-source review remains pending. Official TEST baseline-only recovery is separately queued as `req_4be0e82d6e2e4996bf501f0aef20aa28`; queued status is not recovery proof. After recovery, the next official native fresh-install attempt must verify native lifecycle/HTMLPurifier and persisted requested bundled mode on MySQL, then restore and prove its entire starting baseline. This author made no APP/TEST DB or service changes, did not edit the failed Request, and did not stage, commit, push, merge, or deploy.
