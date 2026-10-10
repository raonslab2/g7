# W04 initial migration cache bootstrap — independent source review

**Decision: SOURCE REVIEW PASS for the final candidate pins below. Actual fresh-install/MySQL execution remains NOT_RUN by this reviewer.** The narrowly guarded array cache is confined to the first native migration subprocess on an empty marked lab schema. Existing/partial installations receive unchanged database-cache environment. This is not an installer/product release PASS or canonical Validation.

## Scope and input identity

- Work `work-20261009-g7-symphony-max-child-c7ae42d1`; parent `req_81ac33cac94046b9a2249cd14c0d00ba`; native nonauthor reviewer `/root/w03_ui_repairs`.
- Read-only candidate files: `storage/framework/testing/w04-bootstrap-candidate/{migration-bootstrap.php,setup.php}`. These ignored candidate files are **not** published source or a fixed Git checkpoint. Lead must move the reviewed bytes into the intended `scripts/travel-lab/` package paths and publish them through the normal gates.
- Also reviewed the actual test-only `scripts/travel-lab/LiveMysqlTest.php` repair, current environment guard, shared native throttle test trait and relevant core/cache source. No implementation was authored by this reviewer.
- Lead HEAD at final pin collection: `76cdfb19749df969759649ffb4e1dcffbdd84142`; public setup remains the previous implementation at its hash below. APP/browser runtime was not changed for this review.
- Only this report written. No candidate execution, syntax/test/build commands, private env/access reads, APP/TEST SQL/HTTP/config/service changes, staging/commit/push or agents.

## Concrete problem and source trace

The public lab example selects native database cache before a fresh schema has its cache tables. During ordinary console application boot, `CoreServiceProvider::boot()` calls module compatibility validation before the migration command executes. `validateAndDeactivateIncompatibleExtensions()` calls injected CacheInterface `has()`. CoreCacheDriver uses `config('cache.default')`; its native underlying database store accesses the absent cache table. Thus a first native migration can fail before creating the table needed by that boot path. This is source reasoning tied to the reported bootstrap failure, **not a new live reproduction**.

The candidate retains native Artisan migration, providers and CacheInterface. It changes only the subprocess environment for a tightly constrained initial migration. It does not globally alter G7 cache drivers, core provider logic, settings, throttle aliases or runtime middleware.

## Final boundary review

| Branch/contract | Source review |
|---|---|
| PDO and target | Requires PDO mysql; schema exactly APP `req81_travel_lab` or TEST `req81_travel_lab_test`; marker1 and original CACHE_STOREdatabase. Live SELECT DATABASE/CURRENT_USER must match schema and `req81_travel@127.0.0.1`. |
| Read/write isolation | DB_WRITE/DB_READ/DB_DATABASE agree; both hosts127.0.0.1, ports3306 and usernamesreq81_travel. Requires native mysql connection/g7_ prefix. |
| Cache and egress | Requires G7_ENV_PRIORITYtrue, mailarray/queuesync/local storage, cache+lock connectionmysql and native cache/cache_locks table names. Rejects nonempty DB_URL, DB_SOCKET, MYSQL_ATTR_SSL_CA and CACHE_LIMITER. |
| Nonempty/partial schema | Any information_schema.TABLES row, including a view, returns the original environment unchanged. There is **no** automatic array fallback to repair missing cache tables in a partial installation. A broken partial schema remains a separate recovery/blocked case. |
| Empty schema | Requires INSTALLER_COMPLETEDfalse; ROUTINES, EVENTS and TRIGGERS counts must also be0. Query failure throws rather than assuming emptiness. |
| Installed code | Rejects installed directory entries under modules, templates **and plugins**; only native `_bundled`/`_pending` packaging roots are allowed. This is a filesystem installed-code check, not proof of no unrelated process or concurrent installer. |
| Cache override | Returns a new array with CACHE_STOREarray only after the above checks. Input environment files are not rewritten and no persistent setting/global driver is changed by the helper. |
| Actual caller | Candidate setup first obtains the existing `travelLabEnvironment()` guard, including marker, DB/egress/cache and absent installer runtime/config cache checks. It supplies its fixed local scoped PDO and actual checkout root. This caller guard remains required; arbitrary direct helper callers with a retained config cache are not certified. |
| Invocation scope | Setup calls helper only for the `migrate --no-interaction` command. `settings:install`, core seeding and administrator recovery continue using the original database-cache environment. The first array subprocess must successfully create native cache tables before those next commands. |
| Lifecycle cleanup | Existing guarded lifecycle wrapper clears only the marked checkout's generated native config cache. Failure does not select a permanent alternate store. No live wrapper was executed here. |
| Existing data | Original setup still skips the destructive core user seed when users exist. Credentials/environment recovery and ordinary native reference data behavior are unchanged by this proposed cache bootstrap. Setup itself performs its pre-existing scoped provisioning work; this review does not claim the entire setup command is read-only. |

G7 EnvPriority maps the explicit cache store and protects it from persisted driver settings with the priority switch on. Because the helper retains that switch while overriding only the initial child process, the array selection is a process bootstrap choice, not a new module cache service. Normal preview remains native database cache/locks and its numeric travel admission adapter still rejects array stores.

The scope is **single-owner initial installation**. No schema lock serializes the emptiness observation against a second concurrent installer or unrelated actor. Run setup in its own isolated checkout/schema, without overlapping runtime/browser targets. Installed extension/SQL object checks do not create a universal concurrent-install safety guarantee.

## Initial observations and corrections

The first helper input, SHA256 `9a44142db7bb83096ef09093b862eadd80ff803ccf05244bf3ca5640f62903f2`, checked TABLES emptiness and modules/templates only. It did not independently enforce the complete connection/cache override boundary. This reviewer reported that routine/event-only partial state and installed plugins were outside that proposed empty-only guard. The actual setup caller already provided stronger env validation; these were candidate boundary gaps, not observed APP damage.

Root strengthened the final helper with explicit isolation/cache/egress keys and override rejection, SQL object checks and installed plugin detection. Those final bytes were re-read. No P0/P1/P2 source finding remains within the actual guarded caller contract. No negative record or previous runtime result was rewritten.

## LiveMysql fixture correction

The actual repaired `LiveMysqlTest.php` explicitly requires the canonical shared trait file because the root PHPUnit context does not necessarily load the module's Tests namespace. It boots the ordinary TEST environment with array cache, confirms mysql and the `_test` schema, executes native migrations, then invokes `UsesDatabaseThrottleCache` before actual travel requests. The trait resets cache driver/RateLimiter instances and binds native cache+lock tables to the test's already selected database connection.

The fixture then asserts native DatabaseStore and equality of its counter/lock connection database names to the main TEST DB. Both native getters exist in the installed framework. It does not bypass throttling or change quotas/authentication. Root environment guard plus mysql/schema assertions remain before the actual request checks. The TEST route group still deliberately mounts actual bundled routes instead of claiming installed runtime discovery.

Root-reported syntax and isolated SQLite checks are supporting author checks only. **Actual MySQL execution of this corrected fixture is NOT_RUN by this reviewer** and needs a guarded post-freeze run. Earlier successful SQLite/old-fixture evidence cannot establish this new MySQL behavior.

## Required execution gates and delivery

1. Publish the reviewed helper and setup integration in the real package source, with their content hashes retained; candidate setup is staged for its intended package location, not execution from the ignored candidate directory.
2. Fresh isolated empty-schema native install: demonstrate initial migrate succeeds with transient array, native cache/cache_locks exist, then settings/seed and normal preview use effective database cache/locks. Record source/env/runtime bindings without secrets.
3. Existing installed and partial-schema paths: no array fallback, no existing user/record/setting replacement; explicit failure/recovery as appropriate. Exercise env loss recovery and final cache selection separately.
4. Run the corrected LiveMysql fixture against guarded TEST, then final installation/browser/regression gates at the integrated SHA. Preserve previous failures and this source-review boundary. No live validation should overlap the current fixed browser run.

No caller permission, production restart, new cache service or Provider/global resource change is required by this source proposal. Lead owns execution, Git checkpoint/validator/CI and the remaining independent runtime evidence.

## Final input pins (SHA-256)

| Reviewed input | SHA-256 |
|---|---|
| Candidate `migration-bootstrap.php` | `9b0ab095834ae01f8255b083e2070571600dae44cde52c082f099b6db85c160d` |
| Candidate `setup.php` | `3003ca97c34818b1d4f603e00db4e5c670d7fc090cbff0d22bb93376a1726b65` |
| Actual `scripts/travel-lab/LiveMysqlTest.php` | `6a9f3b6a273489b292fb3eebbe4986300e78a2e5065c2acc2c3e2a648bee6f7d` |
| Unchanged public `scripts/travel-lab/setup.php` | `0353c4ffce837ae29eb400fedf508d0441ca6dc45e5a1a76e497e5bc0b70cdce` |
| `scripts/travel-lab/environment.php` | `e2f9b633dd26b9a2d69b06eaa4c25f87f3ddc767747f6517e6948c0e3224ba24` |
| `modules/_bundled/raonslab-travel_lab/tests/UsesDatabaseThrottleCache.php` | `ed82ef4de82d0fe648fe8cb67e793b8e884c397b1400b8935dbccd19bca086e6` |
| `app/Providers/CoreServiceProvider.php` | `70b87d8dd5d1171514b5c166df0820458702d934292f026f29bb36e613cb9094` |
| `app/Extension/Cache/AbstractCacheDriver.php` | `a17b9eec511dbe2c954d9b77a12b89a37e258dfee486bc39afb8d4f0085df09f` |
| `app/Support/EnvPriority.php` | `e7086a8cd3d4778efa1d0b731eb406d01f9c2265234cfb9287d6dcf0a23ddd45` |

These are content pins, not a claim that the ignored candidate is durable Git delivery. This report is the only intake-owned file; no runtime assertion is inferred from a syntax/source review.
