# G7 Travel Lab isolated execution package

This package is a nonoperational demonstration. Use a fresh isolated checkout of
the published integration SHA. Do not copy a business site's `.env`, database,
storage, settings, or customer assets into it. It does not provision public hosting.

Prerequisites: PHP 8.2+ with the project's required extensions, Composer, Node/npm,
MySQL/MariaDB on local port 3306, and local administrative socket access through
`sudo -n mysql --protocol=socket -uroot`. Use the repository requirements guide;
the initial execution used PHP 8.3.6 and MariaDB 10.11.14.

```bash
composer install --no-interaction --prefer-dist
npm ci
php scripts/travel-lab/setup.php
php scripts/travel-lab/smoke.php
php scripts/travel-lab/smoke.php --testing
php scripts/travel-lab/guard-test.php
php scripts/travel-lab/extensions.php
php scripts/travel-lab/run.php preview
```

The preview listens only at <http://127.0.0.1:18871>. The script refuses unrelated
environment files. Generated `.env` and `.env.testing` have mode 0600 and remain
Git ignored. The synthetic administrator address is
`admin@travel-lab.example.invalid`; its random local password is stored only in
the generated `.env` as `INSTALLER_ADMIN_PASSWORD`. Do not publish that file.

The databases are `req81_travel_lab` and `req81_travel_lab_test`. User
`req81_travel` has privileges only on those databases. Test commands must use:

```bash
php scripts/travel-lab/run.php test tests/Unit/Support/InstallerContextTest.php
php scripts/travel-lab/run.php test modules/_bundled/raonslab-travel_lab/tests
php scripts/travel-lab/run.php test --testsuite=Installation
```

The travel command requires the final module's tests. The explicitly scoped
Installation suite is permitted for the mandatory installation smoke gate;
the runner rejects an unscoped full suite. Run broad gates sequentially on this
DB. Use a separate request-local
database/checkout for another team's tests rather than sharing this test DB.

`extensions.php` checks all module/template artifacts before mutation. It
installs and activates board/page/ecommerce/travel plus the native G7 admin
template and Travel Lab user template using official Artisan commands. It then
runs the travel module's declared seeder. Already installed/active extensions
are preserved on rerun. To apply new bundled code to an existing lab, explicitly
run the official `module:update` / `template:update` command through `run.php`.

Root dependencies are installed by Composer; extension Composer dependencies
are handled by the official module installer. Build frontend assets through the
repository's official core/module/template build commands for the final change
and commit production build output according to G7's guides.

Recovery: stop only this foreground preview with Ctrl-C, correct the isolated
checkout, rerun `setup.php`, and rerun the relevant extension update and seed.
Core migration is incremental. The setup skips core `DatabaseSeeder` when any
user exists because `AdminUserSeeder` deletes all users. This package supplies
no automatic DB deletion, environment overwrite, or production restart command.
Migration rollback/reseed and application restart verification must be recorded
against the final implementation; initial core bootstrap is not that evidence.

Array mail, sync queue, local storage, environment-priority locking, and absent
installer runtime/config cache are enforced before commands. No payment plugin
is installed by this package. The implemented travel module must also reject
real commerce checkout/order/payment entry; runtime isolation alone does not
prove that application contract.
