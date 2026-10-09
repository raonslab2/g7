# W04 independent fresh installation recipe

**Status: NOT_RUN.** This document is a read-only investigation result and a recipe for a new official independent runtime attempt. It does not prove fresh installation, restoration, or product completion. No MySQL command below was executed while preparing it.

The next attempt must use its own assigned worktree at the published fixed candidate SHA, and obtain exclusive ownership of the existing disposable `req81_travel_lab_test` schema. The parent APP schema, preview on port 18871, existing account authentication and privileges remain outside this procedure. Another Request currently using TEST must finish and release it before execution.

## Boundaries and prerequisites

- Install Composer dependencies in the verifier's own checkout. Do not symlink another Request's `vendor`, storage, installed extensions, settings or cache: autoload paths and mutable state would invalidate fresh-checkout evidence. Committed production assets can be used, with their SHA recorded; rebuilding them is a separate check.
- Use private, ignored, nonsymlinked `.env` and `.env.testing`, each mode 0600, containing only the marked TEST configuration. Do not copy the parent APP environment. Obtain only the explicitly supplied lab TEST credentials through the authorized local handoff; do not read platform credentials.
- Validate `TRAVEL_LAB_ISOLATED=1`, `G7_ENV_PRIORITY=true`, both read/write databases and `DB_DATABASE=req81_travel_lab_test`, host 127.0.0.1, port 3306, user `req81_travel`, MySQL connection and `g7_` prefix. Reject DB URL/socket/SSL overrides, installer runtime overrides, symlinked configuration caches and inherited connection overrides.
- Immediately before each destructive operation, confirm the effective application configuration **and** `SELECT DATABASE(), CURRENT_USER()` identify exactly `req81_travel_lab_test` and `req81_travel@127.0.0.1`. A filename or a requested environment value alone is insufficient.
- Confirm effective array mail, synchronous queue, local storage and a local search engine without an external indexing client. No external delivery, payment, reservation or supplier integration is enabled. Keep console/HTTP output private until sanitized.
- No account rotation, `CREATE DATABASE`, account creation, grants, platform configuration changes, APP schema writes or parent service operations are needed.

## TEST command launcher: a required adaptation

Do **not** run `scripts/travel-lab/setup.php`: it provisions/rotates the shared scoped account and bootstraps APP. Do **not** run `extensions.php`: its PDO connection is hardcoded to APP.

The existing `travelLabLifecycleProcess()` is also unsuitable for a checkout whose `.env` contains only TEST: its `finally` calls `travelLabClearGeneratedConfig()`, which validates `travelLabEnvironment(false, true)` and expects APP database names.

The independent verifier must implement and review a TEST-specific launcher in its own worktree. Before each subprocess, validate and obtain `travelLabEnvironment(true)`, then pass that environment directly to `travelLabProcess([PHP_BINARY, 'artisan', ...$argv], $environment)`. Its `finally` must validate `travelLabEnvironment(true, true)`, reject a symlink at its own `bootstrap/cache/config.php`, and remove only that checkout's generated config snapshot. Validate again before the next command. Do not substitute an APP wrapper or skip validation to get past cleanup failures.

The arrays below describe subprocess arguments under that guarded launcher. They are **not** instructions to invoke bare Artisan against the parent's default environment. All return codes and source hashes must be recorded.

Fresh Artisan execution does not invoke `tests/bootstrap.php`; two TEST-only env files therefore do not require hiding `.env` for this procedure. Subsequent native PHPUnit checks encounter the repository's same-database protection and need the already documented TEST-only bootstrap adaptation, with its source and limitations recorded separately. Do not change or disable the repository's protection globally.

## Preserve the entire existing TEST schema first

1. Create a unique ignored recovery directory, mode 0700, and refuse to overwrite any existing snapshot. Put credentials only in a private 0600 MySQL options file, never argv, logs or Git.
2. Capture the **complete** current TEST table set and stable row-count/digest mapping. The earlier independent run observed 128 tables and 639 rows; remeasure rather than treating those historical counts as requirements. Sort table names and serialized rows consistently. Also inventory views/triggers or other schema objects if present so the backup/restore scope is complete.
3. Run a scoped native dump with explicit `req81_travel_lab_test`, for example `mysqldump --defaults-extra-file=<private-options> --single-transaction --no-tablespaces --skip-comments req81_travel_lab_test`. Confirm success, nonempty output, mode 0600 and a recorded dump SHA. Ensure schema objects found in the inventory are included.
4. Persist a private recovery manifest containing the fixed source SHA, exact database/account boundary, original table set/digests, dump SHA and phase. Mark destructive work as started **before** wiping. A native conversation or an in-memory `finally` is insufficient recovery evidence if the Provider process stops.
5. If any preflight or snapshot check fails, stop before destructive work. Never overwrite an unresolved previous recovery snapshot.

## Native empty-schema installation

Start with no installed module/template directories or generated settings/cache in the verifier's own checkout; keep `_bundled` sources. Set its own installation-completed flag false for initial boot. Record actual loaded source paths and compare them to the candidate; installed copies from an earlier Request cannot establish current hooks or default permissions.

Execute, in order, under the TEST launcher:

```text
['db:wipe', '--database=mysql', '--drop-views', '--force', '--no-interaction']
```

Verify the effective TEST database contains zero application tables before continuing. Native `db:wipe` is recognized by G7's `InstallerContext` as a schema-mutating command. Do not run hand-built DROP SQL outside the guarded schema boundary.

```text
['migrate', '--database=mysql', '--force', '--no-interaction']
['settings:install', '--no-interaction']
['db:seed', '--class=DatabaseSeeder', '--force', '--no-interaction']
```

Run the core seeder only after confirming the freshly migrated `g7_users` count is zero. `AdminUserSeeder` deletes existing users, so it must never be used as an ordinary rerun on preserved data. Supply a synthetic initial administrator in the verifier's private TEST environment and do not expose its password.

For each identifier, in this order: `sirsoft-board`, `sirsoft-page`, `sirsoft-ecommerce`, `raonslab-travel_lab`:

```text
['module:install', IDENTIFIER, '--vendor-mode=bundled', '--no-interaction']
['module:activate', IDENTIFIER, '--no-interaction']
```

For `sirsoft-admin_basic`, then `raonslab-travel_lab`:

```text
['template:install', IDENTIFIER, '--no-interaction']
['template:activate', IDENTIFIER, '--no-interaction']
```

Use ordinary installation, not `--force`, on the empty fresh state; failures must be diagnosed and recorded rather than concealed with repeated force-reinstalls. Pin bundled source selection for module vendors and record whether any dependency installation occurred.

```text
['module:seed', 'raonslab-travel_lab', '--sample', '--no-interaction']
['raonslab-travel_lab:support-provision', '--lab-confirm', '--no-interaction']
```

Set installation-completed true only in the verifier's own environment after successful bootstrap. Never change parent files. Capture installed module/template status, migrations, source hashes, native settings, menus and permission relationships. Explicit sample seed/provision must remain separate from default installation seeding.

## Evidence of usable installation

- Boot the actual installed application and discover its installed routes; manually mounting bundled routes does not prove lifecycle integration.
- Verify the loaded `Module` bytes match the candidate and declare `ProtectTravelCommerceCatalog`; inspect actual synchronous hook registration and the API middleware group containing `TravelCatalogConflictResponse` once.
- Verify persisted default roles/permissions, including support access restricted to admins. A super administrator's successful request cannot establish the ordinary manager or owner boundary.
- Reproduce Korean keyword search and a newly created ecommerce product's travel metadata/departure registration. Exercise native product/option management, not only seeded records.
- Complete catalog/detail/date/people/cart → TEST_INQUIRY → admin processing → owner status/cancellation using real native authentication and installed APIs. Verify server-native price snapshots, held capacity, idempotent retry and isolation from another member. Exercise notice/FAQ/private question persistence and answer/ownership boundaries.
- Confirm no real orders/payments/outbound delivery were created. Record appropriate native regression separately from the fresh install result; the existing two-test Installation suite checks IDV schema and is insufficient evidence for Travel Lab installation.
- If using a real preview, start and stop only the verifier's own loopback process on an available separate port. Do not operate parent port 18871. Rebooting the verifier process and comparing TEST state can prove that process's persistence; it does not prove a database-server or parent-preview restart.

## Always restore; retain recovery evidence until proven

Regardless of installation/test success, restore the original TEST schema while exclusive ownership remains held:

1. Revalidate effective TEST database/account and private snapshot integrity. Use the guarded native TEST wipe again to remove fresh-only tables/views; a dump import alone may leave extra tables that were not in the original snapshot.
2. Import through a native MySQL client with the private options file and an explicit `--database=req81_travel_lab_test`. Never print raw SQL, credential diagnostics or the private snapshot.
3. Purge application connection caches and independently recompute the entire restored table set, all row counts/digests and inventoried schema-object boundaries.
4. Require equality with the **original exact table set and complete digest mapping**. A successful import exit code, matching total count, eight selected travel tables, or absence of an exception does not establish restoration.
5. Only after successful equality may the private snapshot/options and failure marker be deleted. Preserve sanitized PASS evidence, dump SHA and restore-digest SHA in Git.
6. On import failure, digest mismatch, interruption or uncertainty, retain the private recovery dump and durable failure marker. Record the phase and safe next recovery command, report TEST as BLOCKED, and prohibit further TEST consumers until recovery is independently verified. Do not publish private paths containing secrets or raw database content.

A fallback restore must follow the same revalidation and digest checks. A `finally` that deletes recovery files after an attempted import is forbidden. None of these steps constitutes formal Validation automatically; the official verifier must deliver its fixed-SHA report, commands, durations, PASS/FAIL/NOT_RUN/BLOCKED results and independently observed restoration evidence.
