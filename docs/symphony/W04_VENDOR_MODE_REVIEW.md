# W04 vendor-mode persistence — native nonauthor review

**Accepted for integration within this model/persistence scope; no blocking finding identified.** Independently rerun **10 tests / 92 assertions PASS**. This is a native nonauthor review of the other author's Module model change and persistence tests, not a canonical Validation receipt, actual fresh MySQL installation, or whole-product PASS.

Reviewer: native agent `w03_commerce_guards` within Request `req_81ac33cac94046b9a2249cd14c0d00ba`. I authored the earlier ModuleManager vendor preparation/autoload gate and its mock-boundary tests; this review does **not** independently approve that earlier work. The newly reviewed `app/Models/Module.php` fillable repair and actual repository persistence test are owned by another native author. Only this report was edited by this reviewer.

## Frozen input and independently executed checks

| Source | SHA-256 before and after review |
| --- | --- |
| `app/Models/Module.php` | `3c853703e6098be6aa11f01ca14b533ffddca1ab5fa882ac5ae291adb6c63f9b` |
| `tests/Unit/Extension/ModuleVendorModePersistenceTest.php` | `e018ffb131c1d71b45b10ad1cd8c9878124e15ae0c5455ad220d4a9c63933d72` |
| Native `app/Repositories/ModuleRepository.php` | `b0a684863e4700d2a7b6091e87b1a282d27aa53253a35d7444c385014f3ed0de` |
| Native vendor-column migration `2026_04_14_000001_add_vendor_mode_to_modules_and_plugins_tables.php` | `8b2c02a5ad6dd50744663df1f17301151e0cda5e343e70709f9bddcb3f8dcfad` |

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/ModuleVendorModePersistenceTest.php
vendor/bin/pint --test app/Models/Module.php tests/Unit/Extension/ModuleVendorModePersistenceTest.php
git diff --check -- app/Models/Module.php tests/Unit/Extension/ModuleVendorModePersistenceTest.php
```

PHP 8.3.6 / PHPUnit 11.5.56: exit0, **10 tests / 92 assertions**, 0.166s, 22MiB. Pint read-only check and whitespace check: **PASS**. Both requested fixed hashes were checked before and after; no reviewed source changed.

The tests explicitly create only a process-local SQLite `:memory:` connection via Capsule. They call the real ModuleRepository, real guarded Eloquent Module, and native vendor-column migration. The base modules/plugins tables are minimal fixtures, not the full native installation schema. Production-default silent-discard policy is set explicitly while guarding remains enabled; unfillable primary-key and `is_active` fields remain protected. The connection resolver, event dispatcher, discard policy and Container/facade context are restored. No application boot, dotenv, APP/TEST/MySQL connection, native service process or external account is used.

## Review conclusions

The functional model diff adds exactly `vendor_mode` to `$fillable`; the other diff is a trailing-comma formatting correction in `isInstalled()`. Native repository `create`, `update` and `updateOrCreate` use Eloquent mass assignment. Without this allowlist entry, valid installer attributes were silently discarded and the migration's `auto` default remained. The lead/author reports a prior six-failure baseline and fresh598 installation recording auto despite requested bundled; those historical executions were **not rerun by this reviewer**. The current actual repository roundtrip independently verifies the proposed root-cause correction. Earlier 15/109 installer tests captured attributes at a mocked repository boundary and could not verify persisted values; their PASS is not substituted for this evidence.

The field remains a **string**: migration defines a16-character non-null string with default `auto`; the existing native VendorMode is string-backed (`auto`, `composer`, `bundled`). Existing consumers use `$resolvedVendorMode->value`, string conversion/`tryFrom`, or `fromStringOrAuto`. Introducing a model enum cast would alter that contract and is unnecessary. The executed tests preserve exact string modes, nested metadata/config/name JSON, row identity and same-row updates; omitted values retain the native auto default or prior bundled selection as appropriate. All three enum values are covered through create/update; install-style updateOrCreate covers bundled→composer and omitted-field preservation.

Allowing this one field is compatible with the inspected HTTP boundary. Native admin module install/update routes require `auth:sanctum`, user status, admin gate and `permission:admin,core.modules.install`. InstallModuleRequest/PerformModuleUpdateRequest validate `vendor_mode` as nullable string with `in:auto,composer,bundled`; controllers consume only `validated()` data and convert it to typed VendorMode before ModuleService/ModuleManager. Public module routes serve assets/editor specifications, not raw Module attributes. No public raw request-to-Module create/update path was found in the inspected native source. This is **source review**, not an executed HTTP authentication/permission test. The model/repository do not validate arbitrary trusted in-process strings, and SQLite does not establish MySQL length/constraint enforcement; validation remains the native request/typed service boundary.

## Residuals and handoff

- `app/Models/Plugin.php` also omits `vendor_mode` from fillable (inspected SHA-256 `fa4260e5cb4e60e2a7ab0f4bc9cbfc28e1944fcb11c8b1ecf1b49cf76e93a344`). This is a source-only analogous residual for native plugin mass assignment, not an executed plugin defect/PASS and not repaired in this module-only scope.
- Real ModuleManager→repository installation, native events/providers/permissions, actual MySQL fresh installation, dependency availability and whole TEST snapshot restoration require the next fixed-candidate independent runtime execution. This focused review neither runs nor certifies them.
- Lead owns version/CHANGELOG synchronization, Git checkpoint/CI and integration. Original failed fixed-SHA evidence remains unchanged. Hosted CI and canonical Validation are **NOT_RUN here**; no waiver or product release approval is granted.

No product/test source edits, staging, commit, push, service or operational database change were performed by this reviewer. The report is ready for lead's source-bound checkpoint.
