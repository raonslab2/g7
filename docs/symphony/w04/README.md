# W04 TEST-only reproducible review harness

This directory contains authored verification helpers, not a product fix or official Validation receipt. They execute only in this Request's own checkout against the explicitly assigned `req81_travel_lab_test`. Keep `.env` and `.env.testing` identical, nonsymlinked, mode0600, with the authorized marked TEST credentials only. Root `tests/bootstrap.php` remains unchanged.

The authorized TEST handoff initially had file cache/session. This verifier privately changed both own files to array cache/session, retained mysql-fulltext/array mail/sync queue/local storage, added a new synthetic installer administrator, set HTTP_PROXY/HTTPS_PROXY/ALL_PROXY (both uppercase/lowercase) to http://127.0.0.1:9 and NO_PROXY to 127.0.0.1,localhost, and blocked foreign HTTP with a request-local loopback discard proxy (port9). Root dependencies were installed into own vendor with `composer install --no-interaction --prefer-dist --no-scripts`; installed extensions/settings/caches were created here. Never use another Request's vendor/storage, APP environment, setup.php, extensions.php, or default APP lifecycle wrapper.

`guard.php` also requires a validated matching private snapshot/BLOCKED before fresh-install and destructive recovery probes; invoking them standalone without that boundary refuses work. It requires TEST-only files, calls the existing `travelLabEnvironment(true)` boundary, checks live `DATABASE()/CURRENT_USER()`, and inventories every actual table's complete row digest and `SHOW CREATE TABLE` hash. Non-table objects/triggers/routines/events cause pre-destructive refusal. `runtime-bootstrap.php` uses ordinary `APP_ENV=local` **on TEST database names only** for native lifecycle/runtime; `testing` suppresses extension autoload generation and cannot prove a normal fresh installation. It validates effective read/write host/port/schema/account and local adapters before commands. `guarded-artisan.php` then invokes unchanged native `artisan` with the same validated TEST environment. Cleanup revalidates TEST=true and removes only own nonsymlinked `bootstrap/cache/config.php`.

Set own installation-completed false and start without own installed copies/settings/caches. Do not reset the vendor or files of another Request. Supply the synthetic installer values privately; do not print them.

```bash
# Save stable whole TEST backup and persistent BLOCKED marker before destructive work.
php docs/symphony/w04/snapshot.php fresh-run save
# Native wipe -> zero tables -> core migrations/settings/seeder -> ordinary bundled
# module/template installs/activations -> explicit sample/support -> installed HTTP.
php docs/symphony/w04/fresh-install.php fresh-run
# Always restore, including after a failed child. The label must match the saved snapshot.
php docs/symphony/w04/snapshot.php fresh-run restore
# Separate native tests, each with its own starting-schema snapshot/recovery.
php docs/symphony/w04/native-regressions.php
```

The recorded staged run used label `final-install`. Recovery directory is private0700; dump and MySQL defaults file are0600, credentials never argv. Stable pre/post-dump rows+DDL and completion trailer/hash must pass before marker creation. Restoration uses guarded native TEST wipe, verifies zero tables, imports valid dump with explicit TEST schema, and compares every starting table/count/row+DDL digest. Any uncertainty retains backup/BLOCKED; only exact equality permits removal. A local flock protects each snapshot operation; assignment and observations, not a new global allocator, establish exclusivity. No PDO is parked while root native tests kill stale connections.

To run a bounded batch directly:

```bash
php docs/symphony/w04/snapshot.php bounded-name run php vendor/bin/phpunit \
  --bootstrap docs/symphony/w03-support/test-bootstrap.php \
  tests/Unit/Support/InstallerContextTest.php
```

The existing TEST-only bootstrap hides **own** TEST `.env` only for root same-name comparison and restores bytes/mode before Laravel boots. PHPUnit stays `APP_ENV=testing`. Native regression classes retain their own normal fakes where defined (mail/notification/settings); these are separate from the installed HTTP probe's real logins and tokens.

`installed-http.php` boots actual installed providers/routes (no manual mounting or fake HTTP authentication), treats each kernel request as an independent cookie-free client by flushing its own array session, and logs only method/path/status. It creates synthetic actors through native UserService/role relationships; the authenticated synthetic super administrator supplies the provisioning actor ceiling. The ordinary admin tested over HTTP is not super. Valid transitions are TEST_INQUIRY -> UNDER_REVIEW -> TEST_ACCEPTED, then owner cancellation.

`recovery-runtime.php` runs a private adapted copy of the product recovery script. Only source-relative paths and one normal-path check after native rollback/replay/import change; catch/finally bytes remain verbatim. Expected exit1 must include the verified fallback indication and full all-table/DDL equality. `scripts/travel-lab/recovery-failure-test.php` separately runs four pure source-control cases; it is not a MySQL failure test. Do not intentionally corrupt a real backup to exercise retention.

`vendor-mode-probe.php` is a diagnostic for the recorded product defect: valid bundled dependency archive, missing installed vendor/autoload, actual installed ProductService purifier constructor error. It writes no products, users, orders or payments. `cleanup-probe.php` is narrow recovery for this run's synthetic failed HTTP fixture; it requires `final-install/BLOCKED`, matching w04 idempotency/actor markers and uses native cancellation. It is not a general cleanup command.

Native `module:install` has `--vendor-mode=bundled`, but no `--source` option; native `template:install` has neither option. Fresh absence of active/_pending sources plus byte hashes establishes `_bundled` origin. Native `module:update --source=bundled --vendor-mode=bundled` is a possible separate repair path; it was not used to hide the fresh-install finding.

Do not publish raw logs, SQL/options, environment values, credentials, tokens, or question/contact data. The report and JSON hashes/counts/status summaries are safe evidence. Historical BLOCKED JSON remains as an event; its matching `recovery-resolution.json` and final-current describe resolved current state. APP loss/account/password/restart follow-up requires a coordinated later window owned by the lead, outside this Request.
