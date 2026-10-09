# Travel Lab runtime evidence

Request: `req_81ac33cac94046b9a2249cd14c0d00ba`.
Owner: lead-owned native `commerce_contract` support role; official child worktrees
are not modified. Input research SHA: `6853f40d58acbf53a2f29cbb9dd422cc439047a9`.

## Scope and installation

Only this request checkout and newly created local databases are used. Native
business-site directories, DBs, services, Page/member/contact data, platform
credentials and external deployment remain untouched. Preview is loopback
`127.0.0.1:18871`, distinct from other ongoing work.

Scripts: `scripts/travel-lab/{setup,environment,run,extensions,smoke,guard-test}.php`.
Reproduction: [`deploy/travel-lab/README.md`](../../deploy/travel-lab/README.md).
Example: `.env.travel-lab.example` has empty secrets. Actual `.env` / `.env.testing`
are generated, Git ignored, mode 0600. No passwords or tokens are evidence.

Available G7 commands were verified from installed Artisan help on 2026-10-09:
`migrate`, `db:seed`, `settings:install`, `module:install`, `module:activate`,
`module:seed`, `template:install`, `template:activate` and update commands.
There is no G7 application `install` CLI. The harness composes these official
commands; it creates no scheduler, worker, Request DB, or alternate installer.

Scoped local DBs: `req81_travel_lab` and `req81_travel_lab_test`; scoped local
user `req81_travel`. The user receives database-level grants only. MariaDB admin
is invoked with local socket authentication; production `.env` and platform
credential/config files are never used. Input credentials are generated locally
and passed to mysql through stdin, not command arguments or logs.

Core `DatabaseSeeder` invokes `AdminUserSeeder`, whose code deletes all users.
The harness runs that seed only when `g7_users` is empty. Rerun preserves users.
Travel module seed is separate and must be proven repeatable on its final SHA.

## Initial verification, 2026-10-09

| Check | Result | Evidence/limit |
|---|---|---|
| PHP/MariaDB available | PASS | PHP 8.3.6; MariaDB 10.11.14; sudo local socket works |
| Scoped DB/user setup | PASS | New lab/test schemas; connection uses scoped user |
| Core migration + settings + administrator seed | PASS | Official commands complete; 13 settings categories; one synthetic admin |
| Setup rerun | PASS | Nothing to migrate; 13 settings skipped; existing admin preserved |
| Effective local Laravel configuration | PASS | Live `DATABASE()` = `req81_travel_lab`; mail=array, queue=sync, storage=local |
| Effective test Laravel configuration | PASS | Live `DATABASE()` = `req81_travel_lab_test`; same egress settings |
| Existing focused core PHPUnit | PASS | InstallerContextTest: 8 tests / 8 assertions |
| Harness isolation negative checks | PASS | 15 cases; foreign DB/host/user/port, URL/socket, mail/queue, cache/runtime overrides blocked |
| Loopback HTTP bootstrap | PASS | `run.php preview`; `GET http://127.0.0.1:18871/up` returns HTTP 200; process stopped after smoke |
| Travel artifact installation | BLOCKED | New bundled `raonslab-travel_lab` module/template pending; no partial extension install |
| Full travel transaction E2E/security/concurrency | NOT_RUN | Requires domain/UI implementation and independent fixed-SHA verification |
| Frontend build/types/regression | NOT_RUN | Lead owns final relevant frontend gates |
| Remote publication/integration/official Validation | NOT_RUN | Lead owns Git Delivery and official independent validation |

The harness locks mail/queue/storage via `G7_ENV_PRIORITY=true`: G7's settings
provider otherwise overwrites environment values after settings installation.
The runner validates both DB targets, host/port/user/prefix, rejects URL/socket
overrides and installer/config caches, strips inherited connection overrides,
and requires focused test selection. Native bootstrap uses the repository's
test guard and extension allowlist convention; no root PHPUnit config was edited.
The explicit `--testsuite=Installation` gate and final Travel Lab test directory
are also allowed. Initial PHPUnit elapsed time was 2.223 seconds; 15 harness
checks took about 1.13 seconds. No simultaneous broad suites were launched.

This document records initial environment evidence, not completed Travel Lab,
operating load certification, customer-approved design, or 110-page delivery.
