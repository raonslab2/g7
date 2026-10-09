# G7 Travel Lab isolated execution package

This package is a nonoperational demonstration. Use a fresh isolated checkout of
the published integration SHA. Do not copy a business site's `.env`, database,
storage, settings, or customer assets into it. It does not provision public hosting.

Prerequisites: PHP 8.2+, Composer with the committed lockfile, and Node satisfying
the locked Vite engine **`^20.19.0 || >=22.12.0`** (Node 20.19+ within the 20.x
series, or Node 22.12+), with npm. PHP must provide the 16 required extensions in
[the repository requirements guide](../../docs/requirements.md#14-php-확장-모듈),
including `pdo_mysql` and `zip`; the canonical SQLite suite additionally requires
`pdo_sqlite`. CLI `proc_open`, `exec` and `shell_exec` must be available.
Use a dedicated local MySQL 8+ or MariaDB 10.3+ instance on port 3306 and install
the `mysql` and `mysqldump` client tools. Setup requires existing local
administrative socket access through `sudo -n mysql --protocol=socket -uroot`;
it does not obtain that privilege or configure a shared/production server.
The checkout's `storage/` and `bootstrap/cache/` must be writable by the executing
user. The initial execution used PHP 8.3.6 and MariaDB 10.11.14.

```bash
composer install --no-interaction --prefer-dist
npm ci
cd templates/_bundled/raonslab-travel_lab
npm ci --legacy-peer-deps
G7_BUILD_SOURCEMAP=0 npm run build
npm run type-check
npm run test:run
cd ../../..
php scripts/travel-lab/setup.php
php scripts/travel-lab/smoke.php
php scripts/travel-lab/smoke.php --testing
php scripts/travel-lab/guard-test.php
php scripts/travel-lab/extensions.php
php scripts/travel-lab/run.php preview
```

The template build above uses the production no-sourcemap setting before initial
installation. For subsequent source changes in the marked lab, use the official
command from the checkout root, then update the installed template:

```bash
php scripts/travel-lab/run.php artisan template:build raonslab-travel_lab --production
php scripts/travel-lab/run.php artisan template:update raonslab-travel_lab --force --source=bundled --no-interaction
```

Committed core/admin/ecommerce assets are part of the fixed checkout. If those
sources change, use their official `core:build --production`, `template:build
sirsoft-admin_basic --production`, or `module:build sirsoft-ecommerce --production`
commands and publish the matching production assets at the same reviewed SHA.
Do not use plain `npm run build` for published template assets: its default
sourcemap references do not satisfy the G7 distribution rule. These instructions
are a reproduction procedure, not evidence that every fresh dependency/build
combination has already passed.

The preview listens only at <http://127.0.0.1:18871>. The script refuses unrelated
environment files. Generated `.env` and `.env.testing` have mode 0600 and remain
Git ignored. The synthetic administrator address is
`admin@travel-lab.example.invalid`; its random local password is stored only in
the generated `.env` as `INSTALLER_ADMIN_PASSWORD`. Do not publish that file.

A brand-new installation has no native cache tables yet. `setup.php` validates a
marked empty schema, an incomplete installer, no SQL objects and no installed
extensions, then gives only the first native migration process an array cache.
The environment file keeps `CACHE_STORE=database`; subsequent settings, seed and
HTTP processes use native database cache and admission locks. An existing or
partial schema keeps its normal database configuration. Run one installer at a
time. The MySQL smoke fixture explicitly uses the same native cache tables and
checks that both counter and lock connections stay on the TEST schema.

The databases are `req81_travel_lab` and `req81_travel_lab_test`. User
`req81_travel` has privileges only on those databases.

Normal preview uses native database cache and `cache_locks` on the same scoped
MySQL connection. Travel numeric throttles hold a native per-budget/actor lock
only while checking and incrementing the limit, then release it before controllers.
Lock timeout, unsupported stores and lost admission leases return a retryable 503;
quota exhaustion remains 429. Database/backend failures retain their native error
response. A failed lock cleanup cannot replace an already raised error or 429;
the native lock lease bounds abandoned cleanup.
File/array stores are refused for this preview, rather than silently losing counts
under concurrent calls. Existing marked lab environments must change `CACHE_STORE`
to `database` and use the example's four `DB_CACHE_*` fields without changing
credentials, database names or other applications. Stop the request-owned preview
before changing its private environment, verify core cache migrations exist, then
restart through the guarded runner. PHPUnit's general array cache stays isolated;
travel HTTP tests bind real native cache tables within their own test database.

Focused native core regression (no application DB):

```bash
php vendor/bin/phpunit tests/Unit/Extension/ModuleVendorModePersistenceTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/ModuleVendorInstallGateTest.php tests/Unit/Extension/TravelAtomicThrottleTest.php
```

```bash
php scripts/travel-lab/run.php test tests/Unit/Support/InstallerContextTest.php
php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml
php scripts/travel-lab/run.php test scripts/travel-lab/LiveMysqlTest.php
php scripts/travel-lab/run.php test modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportApiTest.php
php scripts/travel-lab/run.php test modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportNotificationTest.php
php scripts/travel-lab/run.php test modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportProvisionerTest.php
php scripts/travel-lab/run.php test --testsuite=Installation
```

The canonical `-c` domain suite uses its own in-memory SQLite bootstrap; it is
distinct from native board/support tests and `LiveMysqlTest`, which use the root
G7 test context and the guarded MySQL testing schema. Do not run the whole module
directory through root PHPUnit: its domain tests require the canonical bootstrap.
`LiveMysqlTest` installs native reference data without ecommerce sample orders;
it mounts the actual bundled routes for the test context. Runtime route discovery
is checked separately by `live-api-responses.php` against installed module metadata.
The explicitly scoped
Installation suite is permitted for the mandatory installation smoke gate;
the runner rejects an unscoped full suite. Run broad gates sequentially on this
DB. Use a separate request-local
database/checkout for another team's tests rather than sharing this test DB.

`extensions.php` checks all module/template artifacts before mutation. It
installs and activates board/page/ecommerce/travel plus the native G7 admin
template and Travel Lab user template using official Artisan commands. Module
installation explicitly uses `--vendor-mode=bundled`; the checked-out native
installer must honor that mode and fail on mandatory dependency failure before
publishing the installation. The original W04 candidate did not satisfy this
gate; its negative review remains historical evidence.

Before any extension lifecycle, the package checks the native ecommerce bundle's
manifest/ZIP/composer integrity. After native ecommerce installation and before
activation or sample/support provisioning, it checks the **installed** vendor
autoload, HTMLPurifier/Config file origins, the exact locked version and real HTML
sanitization. This dependency-only check never boots Laravel, reads an environment
file or connects to a DB. An already-active but incomplete installation also
fails; the package does not silently update it or copy library files to make the
old fresh-install result pass. Repair the native lifecycle at the reviewed source
and execute a separately recorded fresh installation. `run.php preview` repeats
the installed dependency gate before starting a server.

Read-only dependency checks and private disposable filesystem/native-vendor tests:

```bash
php scripts/travel-lab/vendor-check.php --bundled
php scripts/travel-lab/vendor-check.php
php scripts/travel-lab/vendor-check-test.php
```

The fixture test actually uses the native `VendorResolver` and
`VendorBundleInstaller`, the fixed ecommerce archive and real HTMLPurifier. It
uses no Composer/network, Laravel application, DB or installed-module mutation.
It proves dependency behavior in a filesystem fixture, not the complete native
fresh-install/product transaction. The independent fixed-SHA empty-TEST install,
native HTML product create/update and whole-schema restoration remain separate
release gates.

After the installed dependency gate, the package
runs the declared travel sample seed with `module:seed --sample`, then the explicit
`raonslab-travel_lab:support-provision --lab-confirm` command. Both provisioning
markers are required. Already installed/active extensions
are preserved on rerun. To apply new bundled code to an existing lab, explicitly
run the official commands through `run.php`, after final source/assets are ready:

```bash
php scripts/travel-lab/run.php artisan module:update raonslab-travel_lab --force --source=bundled --vendor-mode=bundled --no-interaction
php scripts/travel-lab/run.php artisan template:update raonslab-travel_lab --force --source=bundled --no-interaction
```

Native lifecycle commands generate a configuration cache. The harness removes
only this marked checkout's regular `bootstrap/cache/config.php` after each
command, including failures. For a interrupted older lifecycle command, use
`php scripts/travel-lab/clear-cache.php`; all DB/egress checks still apply and
symlinked cache/installer runtime overrides are refused.
The explicit bundled source uses the checked-out reviewed code rather than
the default GitHub-first update discovery path. Template update has no vendor-mode
flag; module update does.

Root dependencies are installed by Composer; extension Composer dependencies
are installed from verified native bundles by the official module installer in
this package. No manual vendor copy or forced post-install update substitutes for
successful fresh installation. Build frontend assets through the
repository's official core/module/template build commands for the final change
and commit production build output according to G7's guides.

Implementation checks, run sequentially while no other tests/reviewer mutate
these two schemas:

```bash
php scripts/travel-lab/live-api-responses.php
php scripts/travel-lab/live-concurrency.php
php scripts/travel-lab/live-seed-rerun.php
php scripts/travel-lab/live-recovery.php
php scripts/travel-lab/live-env-recovery.php
php scripts/travel-lab/live-persistence.php
```

The concurrency check uses two separate PHP processes/MySQL connections calling
actual domain services: last seat, identical idempotency key, cancel/decline,
and locked commerce-stock edit. It is service concurrency evidence; independent
fixed-SHA validation must additionally exercise the HTTP/browser boundaries.
The recovery check snapshots only the testing schema privately, rolls back and
replays travel migrations, restores the snapshot, and compares all eight selected
table digests (not a full-schema digest). Both the normal and fallback restore
paths purge the DB connection and require exact pre-rollback digest equality
before deleting the snapshot. An import failure, mismatch or digest-query failure
retains the mode-0600 dump/options within the mode-0700 private directory and
returns exit 1 without printing raw SQL or credential diagnostics. A verified
fallback still returns exit 1 for the original failed check; `RECOVERED` alone
is not a successful migration verification. The no-DB control-flow checks are
`php scripts/travel-lab/recovery-failure-test.php`; actual MySQL restore is a
separate gate.
It changes the testing schema; never overlap another suite. The env-loss check
privately moves generated env files, regenerates scoped authentication, preserves
user IDs/travel records, and leaves new local credentials. A new APP_KEY invalidates
old sessions; no preservation of unrelated encrypted customer data is claimed.

`recover-partial.php` is only for a failed initial travel migration. It refuses
applied migrations or any populated travel table, then removes empty travel tables
in reverse FK order without disabling foreign keys. Retry the native
`module:install raonslab-travel_lab --force` afterward. It is never a general reset.

Recovery: stop only this foreground preview with Ctrl-C, correct the isolated
checkout, rerun `setup.php`, and rerun the relevant extension update and seed.
Core migration is incremental. Setup skips core `DatabaseSeeder` when any user
exists because `AdminUserSeeder` deletes all users. It safely rotates only the
dedicated localhost DB account after checking it has no privileges outside the
two schemas, and recovers only the exact synthetic administrator via native
UserService. No existing user is deleted. It refuses unmarked environments.

For same-node reviewers, `php scripts/travel-lab/live-review-access.php` creates
unique synthetic member/other-member/admin accounts through native UserService
and role ceiling checks. Credentials and four-hour native Sanctum tokens go only
to an ignored mode-0600 JSON file within a mode-0700 request-local directory.
Share the path, never its contents in prompts/logs/Git. A separate node should
reproduce its own marked lab from the fixed SHA and generate its own credentials.

Array mail, sync queue, local storage, environment-priority locking, and absent
installer runtime/config cache are enforced before commands. No payment plugin
is installed by this package. The implemented travel module must also reject
real commerce checkout/order/payment entry; runtime isolation alone does not
prove that application contract.


## Page-backed campaigns (explicit opt-in)

Travel0.1.3 consumes two fixed native sirsoft-page publication slots. Default
installation, samples, extension updates and ordinary requests create zero Pages.
After installing/updating the published0.1.3 module/template and board1.1.3,
a permitted administrator may manage the fixed slots in the native Page editor
through the travel admin adapter. Public home/list/detail show published Pages
only, including for administrators; unknown/draft slots return404. Changing or
deleting a fixed slug detaches the slot until it is explicitly restored/recreated.

For an explicitly confirmed synthetic lab, set
`TRAVEL_LAB_CAMPAIGN_PROVISIONING=1` in its private marked environment, select the
existing native Page administrator ID, then run the guarded command below. Replace
123 with that actual ID; no account is automatically chosen and no permissions
are granted. The isolated marker, dedicated flag, --lab-confirm and native
read/create permissions are all required. Do not run setup again to enable this
feature or share environment credentials.

```bash
php scripts/travel-lab/run.php artisan raonslab-travel_lab:campaigns-provision --lab-confirm --actor=123
```

The command creates only missing autumn-escape/weekend-reset synthetic Pages
through native PageService. Every existing Page, including a draft or operator
edit, is skipped unchanged; rerunning is not a forced republish. Set the optional
flag back to0 when explicit provisioning is finished. Editing, publishing and
version restoration remain normal native Page admin operations, with native
local activity/SEO/sitemap side effects. Actual campaign browser/role/draft/cache
and postintegration regression are separate fixed-source gates; this procedure
is not a claim those gates have already run.
