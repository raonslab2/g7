# W04 native module installer nonauthor review

Reviewer: native `/root/w03_recovery_repairs`, nonauthor of ModuleManager/gate tests. Owns this report only. No source/Git changes, environment reads, DB, service, Composer process/network or other Request writes. The permitted pure fixture suite uses disposable unbooted applications and no SQL. Internal source/unit review is not canonical Validation or fresh-install/product PASS.

Latest decision: **Attempt 2 PASS_BOUNDED_SOURCE / PURE_NATIVE_GATE**; W04-N01 is closed on the new pins below. Attempt 1's CHANGES_REQUIRED finding remains preserved, and actual fixed-SHA fresh installation remains a required separate gate. The permitted clean PHP fixture subprocesses are not Composer/network or operational service processes.

## Attempt 1 fixed input — CHANGES_REQUIRED

Exact original product `7de0c4441b68b1c012dbf4a75114200e322051c9:app/Extension/ModuleManager.php` SHA-256: `abdd20026c14117cf8f0be7d8daeaff626afe0104b6ba32771ae0ddd40bffa8f`.

Nonauthor review target, before **and after** independent fixture execution:

| Input | SHA-256 |
| --- | --- |
| `app/Extension/ModuleManager.php` | `ce615b2cfd86eed107c259aaf90ee5bdc87dbb4ab6aab553443d79ce85c8bb1b` |
| `tests/Unit/Extension/ModuleVendorInstallGateTest.php` | `cb95f4a910c22d7de006ade9131775e46d3d6234802776d6b44e78c81e864cf2` |
| Author report `W04_NATIVE_INSTALL_FIX.md` | `4662a63b9894a393b35d296b1102a63c6c47e2157b841e0b5a3eed45acab4a32` |

Independent command:

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/ModuleVendorInstallGateTest.php
```

Observed exit0, **12 tests / 75 assertions PASS**, PHP8.3.6/PHPUnit11.5.56, 0.127s, 24MiB. No root PHPUnit configuration, dotenv, application service providers, DB connector or real Composer process was loaded. Actual native copy, resolver, integrity, extraction and rollback execute; entry/lifecycle/transaction/repository collaborators are mocks. Author's baseline five-case failure reproduction and separate existing autoload-order 2/10 are author evidence, not reexecuted by this reviewer.

### W04-N01 — P2 prepared vendor is not autoloaded before entry/install

The changed resolver installs the dependency files before module lookup, but neither the resolver/bundle installer nor `ExtensionManager::registerExtensionAutoloadPaths()` requires the newly installed extension `vendor/autoload.php`. That registration method only requires the **root** Composer loader and adds module PSR-4 paths. The following `getModule()` / `reloadModule()` / module `install()` may therefore reference an external dependency whose file is present but whose Composer loader has not been registered. Native extension vendor autoloads are normally loaded by the later `updateComposerAutoload()` step, after entry/lifecycle/migration/data phases.

The new success tests mock module lookup and check vendor/autoload.php existence at lifecycle entry; they do not assert actual dependency class resolution there. Consequently 12/75 PASS does not close this sequencing hole. The lead accepted the finding and reassigned the source author for a bounded correction.

Minimal proposed correction: after module PSR-4 registration and successful pending/ordinary vendor preparation, require the actual installed module's vendor autoload before entry lookup/loading and lifecycle. Keep it inside the existing rollback try and retain existing testing/no-dependency bypasses. Do not generate the global extension autoload cache prematurely: its discovery depends on installed DB registration. Add a real fixture dependency class supplied by the native ZIP's autoload and require it to resolve at lookup/install without manual test-side loader registration. New source/test hashes and a separate nonauthor recheck are required.

### Correct parts of this fixed input

Pending resolver errors now propagate before copy/publication, including `force=true` replacement. Successful pending vendor preparation is copied once and not resolved again in active. Ordinary bundled/active preparation respects the existing explicit/Auto strategy; errors enter rollback before entry, lifecycle, migrations or DB registration. Actual native metadata construction records resolved Bundled/Composer instead of requested Auto. Mandatory preparation no longer falls through to a late Composer warning. The public method signature and VendorMode/Resolver interfaces are unchanged.

The native rollback helper deletes only an active directory created by this invocation. It does not restore a preexisting active directory after ordinary forced `_bundled` replacement; that is an existing bounded limitation, not a guarantee added by this fix. Pending forced replacement is specifically protected before copy. Existing testing bypass intentionally remains; it cannot be used as evidence of real fresh dependency installation. PHP/ext-only modules bypass the resolver as before.

## Lead version/compatibility inspection

Read-only current version inputs show `.env.example` and config fallback **7.0.12**, root 7.0.12 changelog, and g7 minimum **>=7.0.12** with associated changelog entries in ecommerce, Travel Lab module and Travel Lab template. Scanning bundled module/plugin/template Composer requirements found **only sirsoft-ecommerce** has an external PHP package dependency (`ezyang/htmlpurifier`); other inspected require sets are empty or PHP/extensions only. Native plugin/template manager paths and existing RAON product source have no working changes in this scope. This does not certify those separate lifecycle paths.

Ecommerce composer.json remains hash `4f224863e21777f5ef474a7fcfb7d5f66132551b4c58220a660bd3acb573dfdb`, matching the native manifest; the original archive hash remains `713f98578a866fada6782d11f8c80ff13037f1faf0edaaa55f004ddc4e80d1b9`. No dependency archive rebuild is needed solely for module.json g7 minimum changes. Extension Service/Route/price signatures did not change; existing inter-extension API minimum versions remain while consumers require the corrected core.

Version input hashes at this inspection:

| File | SHA-256 |
| --- | --- |
| `.env.example` | `7c2b18ad69e5e33bf6cc7181f9eab8027d7314c5c80ae835754f708b85e16495` |
| `config/app.php` | `5907f34bf4ed6126cc723b15c5d545f64a1309322c2587c36666434fd363edac` |
| Root `CHANGELOG.md` | `18b6ef5c575f3993e237e6d31df82a857e044387a020320fbb952fc454f3e66e` |
| Ecommerce `module.json` | `67d214120b6b87a2882e294136503761e62311060a418210dab89c227246a656` |
| Ecommerce `CHANGELOG.md` | `5bbc299c47a0c7de5492c5f2499211b5af042f3db0762db45dd51509b9814b2f` |
| Travel module `module.json` | `da21f0b4005323fd1db66ce52c32f9a1cd4bf6abaf6f5e318d674d87ec832696` |
| Travel module `CHANGELOG.md` | `09babfc7dff698385a4ffb5c9dc2f79ace1aaa54a3d755419551966d12b99655` |
| Travel template `template.json` | `7d29a474fa85e670c2603106f08058e50f69c046494f309e0705c9ed5f772363` |
| Travel template `CHANGELOG.md` | `415a3a79b0fb0f8b6f9bfcb535bafdb383cae2a6f67514f73ccf5c8cb70ed23e` |

The original W04-R01 CHANGES_REQUIRED, negative original target and late-harness limitations remain historical evidence. This source-only review does not independently validate the package scripts authored by this reviewer. Actual fixed-SHA empty-TEST installation, real HTML product create/update, whole-baseline restoration, runtime/browser, remote CI and canonical Validation remain separate gates.

## Attempt 2 independent recheck — PASS_BOUNDED_SOURCE / PURE_NATIVE_GATE

New source was frozen by the nonauthor's separate source author. Exact pins measured before **and after** the independent reexecution:

| Input | SHA-256 |
| --- | --- |
| `app/Extension/ModuleManager.php` | `bcd279e3be09f5c43b117aa8fec90e64cc1b6ba06ce7a47a7683ca19ef4455ed` |
| `tests/Unit/Extension/ModuleVendorInstallGateTest.php` | `e0128b8e31ad573109b0a27c369a0318334842ee685e88fdc80440ded2ba9ccd` |
| Author report `W04_NATIVE_INSTALL_FIX.md` | `865d1598018ea5bb8a030373135e54aaef761f3f0b25c1544731ad34221de876` |

Same permitted no-configuration/bootstrap command independently observed exit0, **15 tests / 109 assertions PASS**, 1.726s, 24MiB, PHP8.3.6/PHPUnit11.5.56. This is the revised suite including the earlier cases, not 15 additional unique cases to add to12. Source/test hashes remained unchanged. Core7.0.12/default/minimum-manifest input hashes above were also unchanged at the second inspection.

The implementation now initializes a nullable native vendor result and, after successful pending or ordinary preparation plus module PSR-4 registration, `require_once`s the actual **active** module vendor loader before getModule/reloadModule/install. It stays inside the existing Throwable rollback. A failed loader cannot reach lifecycle/DDL/data; successful pending loading occurs at the copied active path and once. Null result preserves testing and no-external-dependency bypass. No public signature/Resolver/Mode API, unrelated plugin/template lifecycle or new DB/cache-publication path changed.

Three added cases run in separate clean PHP processes with global-state preservation disabled. They keep actual native getModule/reloadModule and a real AbstractModule entry file. Native bundled ordinary, bundled pending and the Composer callback each prepare an actual Composer ClassLoader, dependency class and autoload.files-style function helper. Both class/function are absent before installation; native entry and install invoke both. The test never manually requires the dependency loader. It asserts both phase records, exactly one active loader invocation and reflected active module/class/helper paths, then intentionally stops in install before migrations/DB. Hook/cache collaborators are bounded mocks; native directory rollback executes and retains source. This directly covers the missing runtime-autoload finding rather than merely checking vendor file existence.

The author's fail-first3/10/errors is author reproduction, not independently replayed here; the reviewer inspected its new real-entry mechanism and independently executed its final green form. Current 15/109 does not prove a complete module registration/seed/settings transaction, actual ecommerce HTML create/update or whole TEST database restoration. Existing forced-bundled rollback and in-memory class unload limitations remain explicitly documented, with no expanded guarantee.

No new blocking source/unit finding remains in this assigned native install scope. The lead must publish a new fixed integrated SHA and obtain the official nonauthor empty-TEST installation/runtime/HTML-product/full-recovery recheck. The original7de/cdd2 negative verdict is not retroactively changed by this acceptance.
