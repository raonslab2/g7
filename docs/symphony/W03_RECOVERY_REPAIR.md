# W03 bounded recovery/package repair evidence

Implementation check, 2026-10-09T11:05:43.142365+00:00. Parent Request `req_81ac33cac94046b9a2249cd14c0d00ba`, native owner `/root/w03_recovery_repairs`. Source is the working-tree repair over starting HEAD `fe3b2f23`; this report is not independent review or canonical Validation. Original `W03_RUNTIME_REVIEW.md` and its evidence were not changed.

W03-R01 now purges the DB connection and compares the complete original eight-selected-table digest after fallback import. Only equality clears `damaged` and allows snapshot deletion. Import failure, mismatch or digest-query exception keeps the private dump/options (0600) and directory (0700). Diagnostics classify exceptions without raw messages. A successfully recovered fallback still exits 1 for the original failed check. This does not broaden the digest to all 128 TEST tables.

W03-R02/R03 update reproduction prerequisites to the lockfile Node engine `^20.19.0 || >=22.12.0`, required PHP extensions/SQLite test driver, local SQL clients and preexisting socket administrator access. Initial template build uses `G7_BUILD_SOURCEMAP=0`; subsequent official `template:build --production` and bundled update are documented. No fresh dependency/build success is claimed.

## Exact repaired scope

| Path | SHA256 |
| --- | --- |
| `scripts/travel-lab/live-recovery.php` | `9e65fd5f1440fc827aef3eb4c4e2fa2dc51608d15d9d7b34d0258fae493fe62e` |
| `scripts/travel-lab/recovery-failure-test.php` | `e8d64aeaa02b5e5489145ddad4e258771e5587be3c39db69ef72190294839b23` |
| `deploy/travel-lab/README.md` | `39fe1d100473e038f5e643b2e57c7d8356a4f6d767d213ebe581d1da6994e3ec` |
| `docs/symphony/RUNTIME.md` | `94f5ea3ce26350ff3f92b8d07daa15231493bfe3a183880c50d7ad09a4fc7df1` |

Scope digest: `b196276cccaaecbbc1126f98d5090b3a061a9e1cd0b88770a02fa6ae3403d05d`; SHA256 of compact JSON manifest in the table order, object keys sorted. This report itself is excluded to avoid a self-hash. No Git staging/commit/push, env/source setup, DB access, account rotation or preview/service operation was performed.

## Fail-first and corrected control flow

Command: `php scripts/travel-lab/recovery-failure-test.php`. The helper executes the exact product catch/finally text using importer, DB purge and digest stubs plus private synthetic files. It never loads the application, environment or SQL client. Original source was supplied as a temporary file containing the exact Git blob from `28ada286c1c34606741bcfe4f9d12e06ac50af30`; it was removed afterward.

- Original source SHA256 `24cb60481c6d5814eb19aa48a6f60202f25a1e531f0f5a76b9ee27bc8c93793d`: expected **FAIL**, exit 1, 0.394s. Purge/digest calls were absent and mismatch/query-failure paths deleted snapshots.
- Corrected source: **PASS_CONTROL_FLOW_ONLY**, exit 0, 0.242s. No injected private exception marker appeared in stderr. Every product failure branch returned exit 1 as intended.

| Scenario | Probe | Purges | Digest checks | Snapshot retained |
| --- | --- | ---: | ---: | --- |
| verified | PASS | 1 | 1 | false |
| mismatch | PASS | 1 | 1 | true |
| import_failure | PASS | 0 | 0 | true |
| digest_exception | PASS | 1 | 1 | true |

The probe verifies retained file/directory modes before removing only its synthetic fixtures. It does not deliberately corrupt MySQL or claim real recovery failure reproduction.

Other checks: `php -l` both PHP files **PASS**; scoped `vendor/bin/pint --test` **PASS**; scoped `git diff --check` **PASS**. Locked engine declarations and official module/template/core `--production` commands were checked in repository source.

## Pending gates

Actual MySQL rollback/restore and independent repaired-source review: **NOT_RUN**, the shared TEST schema is reserved for the official support-hardening wave. Lead must schedule an exclusive window, run the actual restore command, and obtain independent fixed-SHA re-review. Fresh installation, actual env-loss, production frontend build/browser checks and final integration are owned by the lead/other reviewers and are not converted to PASS here. W03-R01 remains pending those gates; no full runtime/package PASS is issued.
