# Independent W00 runtime/reference review

Review target: `8822971c994955abd196a0d3936c32b4f54c1e51`
Request: `req_81ac33cac94046b9a2249cd14c0d00ba`
Reviewer: parent native `inheritance_capacity`, who did not implement runtime
scripts, REFERENCE.md or SCORE.md
Date: 2026-10-09 UTC
Initial decision: **PASS for the observed clean-environment, quarantined W00
preflight; two isolation hardening findings required repair.** The fixed-source
follow-up below closes both findings for this W00 harness scope.

This is an internal independent source/preflight review, not official Validation
or a Travel Lab product PASS. The reviewer authored INHERITANCE.md and
WORK_ORDER_VALIDATION.md earlier and does not independently approve those files.

## Fixed scope and observations

The working HEAD equaled the target commit. `git diff` confirmed no differences
in `scripts/travel-lab`, `.env.travel-lab.example`, `deploy/travel-lab/README.md`
or `docs/symphony/REFERENCE.md` during these checks. Other ongoing documentation
edits were outside the tested runtime implementation. No runtime source was
changed by the reviewer.

Read original fixed-source `environment.php`, `setup.php`, `run.php`,
`extensions.php`, `smoke.php`, `guard-test.php`, runtime example/deployment docs,
REFERENCE.md and SCORE.md, then inspected current original G7 database and
settings-priority configuration to assess effective overrides.

The guard requires a lab marker, exact development/test DB names, local
127.0.0.1:3306 connections, scoped `req81_travel` user, MySQL with `g7_` prefix,
array mail/sync queue and environment priority. Explicit URL/socket/SSL overrides,
installer runtime and config cache are refused. `run.php` starts commands as
subprocesses with the sanitized environment and binds preview to loopback.

Setup source refuses an existing unmarked environment, generates ignored local
secrets with mode 0600, and provisions only `req81_travel_lab` and
`req81_travel_lab_test` using local administrative socket authentication.
The SQL grants cover those schemas only; this reviewer did not query global
account privileges or run provisioning. Core DatabaseSeeder is skipped once a
lab user exists because its AdminUserSeeder is destructive. Preservation on
setup rerun is supported by source inspection and the implementer's separate
evidence, **not independently rerun** here.

`extensions.php` builds its install/activation command list only after checking
all required bundled module/template files. Pending travel artifacts therefore
block execution before those commands. The reviewer did not run extension
installation or invoke setup, migrations, seeders, service restarts or deployment.
No operational DB/service or platform credential/configuration was accessed.

## Independent commands and results

| Check | Result | Evidence and limit |
| --- | --- | --- |
| `php scripts/travel-lab/guard-test.php` | PASS, exit 0 | All 15 negative/positive checks passed in temporary fixtures; no live DB access or actual env mutation. |
| `php scripts/travel-lab/smoke.php` | PASS, exit 0 | Effective Laravel local DB `req81_travel_lab`, mail=array, queue=sync, storage=local; one lab user. Read-only scoped DB queries. |
| `php scripts/travel-lab/smoke.php --testing` | PASS, exit 0 | Effective Laravel testing DB `req81_travel_lab_test`; same effective non-network settings. |
| Ignored environment/file mode | PASS | `git check-ignore .env .env.testing`; `stat` reports 0600 for both. File contents/secrets were not printed. |
| Inherited DB override key names | PASS for this run | Inspection printed only matching key names; result was `[]`. No credential values were printed. |
| PHP syntax: environment.php/smoke.php | PASS | `php -l` succeeds. |
| Inherited override removal in same process | FINDING F1 | Safe nonconnecting probe: returned environment has no injected DB_URL, but original PHP process still retains it. No Laravel bootstrap or network/DB connection in this probe. |
| Setup rerun, actual grants, extension install | NOT_RUN independently | Source reviewed; preserve prior implementer evidence as separate. Travel installation expected BLOCKED while artifact includes are pending. |
| Preview HTTP, browser, frontend build/types, domain tests | NOT_RUN by this reviewer | Initial owner's runtime results are not reclassified as independent results. |
| Product/security/concurrency/restart/integration/official Validation | NOT_RUN | Requires complete fixed implementation and independent final verification. |

Successful smoke stdout:

```text
PASS: effective Laravel local / req81_travel_lab; mail=array queue=sync storage=local.
Lab users: 1
PASS: effective Laravel testing / req81_travel_lab_test; mail=array queue=sync storage=local.
```

## Findings and safe checkpoint boundary

**F1 — same-process smoke bootstrap retains inherited connection overrides.**
`travelLabEnvironment()` removes inherited DB_* overrides from the returned array.
That protects `run.php` subprocesses. `smoke.php` instead applies the array in
its existing PHP process; omitted original `getenv`/`$_ENV`/`$_SERVER` keys are not
removed. An inherited DB_URL or DB_SOCKET can therefore remain available to
Laravel's database config. The safe no-connect probe independently reproduced
the retention. Clear the rejected inherited keys in all three stores before
same-process bootstrap, or run smoke in a sanitized subprocess. Add a regression
fixture that observes the bootstrapping process environment without connecting
to any foreign target. This finding must be resolved before claiming arbitrary
inherited-environment isolation. The observed clean smoke had no inherited
DB override keys and connected only to the allowlisted schemas.

**F2 — general command guard does not require local storage.**
The example sets FILESYSTEM_DISK=local and smoke verifies effective local
storage, but `travelLabEnvironment()` does not enforce FILESYSTEM_DISK=local
before Artisan/extension commands. A marked environment edited to an external
disk can pass this guard. Enforce local storage and include a rejecting fixture.
Review attachment-disk and broadcast/session/cache overrides as the feature
surface grows. Runtime documentation's blanket claim that storage is enforced
before commands should be narrowed until this guard exists. No external disk
operation was attempted during this review.

These are reproducible harness-hardening obligations. They do not invalidate
the clean local/testing results above or require cancelling prepared independent
implementation work. A quarantined branch checkpoint may preserve the code,
passing observations and outstanding findings honestly. It must not advertise
unconditional isolation, production readiness or a complete transaction flow.

## Reference and score review

A bounded independent read-only extraction rechecked only
[Welcome](https://www.lottetour.com/welcome) and
[Search](https://www.lottetour.com/search). Welcome exposes travel/destination
navigation, discovery/search, campaign/product entry links, starting-price cards
and account/help links. Search extraction exposes filter entry structure. This
supports REFERENCE.md's information-architecture observations. It does not
verify visual rendering, submitted search correctness, inventory, authenticated
flows or real booking/payment behavior. The other linked pages' observations
remain the reference investigator's bounded intake evidence, not new independent
browser verification. No reference images, logos, marketing paragraphs or source
files were downloaded or copied into this review artifact.

REFERENCE.md explicitly separates original RAON assumptions from customer
approval and labels inaccessible administrator/payment/account internals
NOT_OBSERVED. Its earlier suggested `g7_travel_lab` names/8097 port are proposals;
the actual fixed runtime uses the request-scoped names/18871 above. Use the
runtime package for execution. The actual UI proposal is original, with
synthetic data and authored assets; visual quality remains unverified.

SCORE.md's fixed-target contracts preserve the cart stage, TEST_INQUIRY status
meaning, canonical ecommerce calculation, scoped owner/admin checks and
departure/option-stock reconciliation as implementation obligations. Independent
product tests remain explicitly NOT_RUN. The reference and score documents are
safe to preserve as W00 evidence with the quarantine and open findings stated;
they do not prove the implementations already satisfy those contracts.

## Focused repair verification — 09f7fe71

New fixed target: `09f7fe71b3439cac00a497513cd63e27dcaffb14`.
HEAD equaled that SHA during independent verification. The reviewer inspected
the exact runtime diff from `8822971c` and made no runtime implementation edits.

- F1 repair: smoke now clears rejected inherited keys from `getenv`, `$_ENV`
  and `$_SERVER` before applying the sanitized environment and bootstrapping
  Laravel. Source inspection plus an independent adapter-level postcondition
  confirms cleanup. The earlier finding established retained key state; it
  did **not** establish a foreign connection or show that the unmodified G7
  configuration actually selected a foreign database.
- F2 repair: the common environment guard now requires FILESYSTEM_DISK=local.
  A new negative fixture changes it to s3 and must be refused. That fixture
  independently passed, bringing the guard total to 16.

Exact focused commands:

```bash
php scripts/travel-lab/guard-test.php
DB_URL=mysql://127.0.0.1:1/outside_lab \
DB_SOCKET=/tmp/req81-review-nonexistent.sock \
php scripts/travel-lab/smoke.php
DB_URL=mysql://127.0.0.1:1/outside_lab \
php scripts/travel-lab/smoke.php --testing
```

All exited 0. Guard stdout includes `PASS: external storage` and
`PASS: 16 isolation checks; no live environment mutation or database access.`
Development and testing smoke stdout still identifies only
`req81_travel_lab` and `req81_travel_lab_test`, with mail=array, queue=sync and
storage=local. The injected override URL uses loopback port 1 and the supplied
socket does not exist; no external target or production credential was used.

An additional focused PHP invocation populated the three adapters separately,
included `smoke.php`, then checked that both rejected keys were absent:

```php
putenv('DB_URL=mysql://127.0.0.1:1/outside_lab');
$_ENV['DB_URL'] = 'mysql://127.0.0.1:1/outside_lab';
$_SERVER['DB_SOCKET'] = '/tmp/req81-review-nonexistent.sock';
require 'scripts/travel-lab/smoke.php';
foreach (['DB_URL', 'DB_SOCKET'] as $key) {
    if (getenv($key) !== false || isset($_ENV[$key]) || isset($_SERVER[$key])) {
        exit(2);
    }
}
```

Exit 0; final stdout: `PASS: rejected overrides removed from process, ENV and SERVER.`
This invocation ran the same read-only development smoke and no provisioning,
extension activation, seeding or production operation.

**Final bounded decision: F1/F2 RESOLVED at `09f7fe71`; PASS for W00 runtime
preflight and safe quarantined checkpoint publication.** Extension installation
remains expected BLOCKED while complete scoped artifacts are pending. Product
behavior, browser/mobile quality, concurrency, integration and official
Validation remain **NOT_RUN**, and this review grants no production deployment
approval or universal network-egress certification.
