# Workflow integration verification

The W01 independent review at workflow `6aaf80af` found B1 stock-ceiling,
B2 shipping-policy and B3 native-checkout blockers. That revision's 48-test PASS
used test-only domain classes and is historical evidence only. It does not
certify the integrated domain. The current rework removes DomainContract.php
and its fallback schema/enum and uses canonical Travel Lab ModuleTestCase,
actual shared models, enum, provider, migrations and real ecommerce services.

Commands:

```sh
php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter=TravelWorkflowTest
php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter='TravelWorkflowRegressionTest|TravelCheckoutGuardTest'
```

Initial integrated rework run: original **48 tests / 373 assertions PASS**,
18.107 seconds, PHP 8.3.6 / PHPUnit 11.5.56 / SQLite memory.
Initial added regression run before the final audit-rollback case:
**18 tests / 125 assertions PASS**, 14.139 seconds.
Combined actual-domain developer check including the audit-rollback case:
**67 tests / 509 assertions PASS**, 62.714 seconds, 157 MB. Final broader rework
result is recorded by the integrating lead against the published SHA; these
developer checks are not official independent Validation.
After formatting and tightening new-request HTTP201 and independent product/
option membership assertions, the two affected tests passed again (30 assertions,
1.080 and 1.121 seconds). Scoped `vendor/bin/pint --test` passed for the owned PHP
files. The extra membership assertion increases the combined assertion count by
one; no unchanged broad suite was repeated solely for that reporting count.

Regression cases cover the original capacity10/stock5/reserved3/quantity4
oversell, both ceilings at boundaries, stock falling below reservations, stock
changed after cart add, paid default with no explicit travel FREE policy,
country header/context behavior, nonzero shipping calculation rejection,
full snapshot and actor event persistence, normalized contact retries/HTTP200,
same-state note writes/retry/explicit-null clearing, uppercase status query and
resource/state contract, owner cancellation after test acceptance, audit-write
rollback and safe retry, native cart and direct-item checkout rejection before
trade writes, native order guard and ordinary-commerce membership preservation.

Actual-domain fixture reconciliation exposed two prior fixture assumptions:
`Departure.reserved` is not mass assignable, so tests now explicitly force their
synthetic preexisting reservations; native option_values is nonnullable, so
ordinary-product setup supplies its required value. No production model was
changed to satisfy tests.

SQLite has no SELECT FOR UPDATE row locking. Real MySQL concurrent last-seat,
same-key, cancel/decline and stock-edit contention remain a separate required
gate. Native board tests run separately against the marked isolated MySQL test
DB; no board SQLite compatibility hacks are introduced. Domain migrations all
run unchanged. Tests send no external email/SMS/provider calls and create no real
travel order or payment. The scenario source is `scenarios/workflow.yaml`.


## Same-day correction and authoritative source pin

P2 SHARED-02 is corrected in workflow eligibility: departure_date must be
strictly after today, matching public catalog. Two new actual HTTP regression
cases first **FAIL** (known-ID cart add returned201; stale cart remained available),
then **PASS 2tests/21assertions, 1.478s** after fixing eligibility and source loading.
Same-day stale submit rejects409 without cart, capacity, inquiry or audit loss.

When the installed isolated preview was present, native bootstrap's installed
extension src_classmap overrode Composer's canonical bundled PSR-4 paths. The
initial bundled-only fix still failed because the service loaded from installed
source. The test launcher now prepends a resolver for real bundled travel/ecommerce
src classes; no fake repositories or runtime copies are changed.
ModuleTestCase asserts seven canonical source origins on every case. The initial
whole-suite attempt after that pin reached47cases then failed with duplicate
Module declaration. Root Module autoload mapping/preload attempts also failed:
native CoreServiceProvider unconditionally requires installed classmap entry files,
bypassing Composer. The final harness retains that native Module declaration and
asserts its file SHA256 equals canonical module.php before counting metadata
checks; this works both with and without an installed preview. It pins src class
origins while explicitly detecting stale root metadata instead of hiding it.
A new whole-suite result follows this correction; previous results are preserved
as earlier bounded runs, not fabricated current-head PASS.


Final canonical travel SQLite run after the class-resolution fixes:
`php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml`
**PASS 118tests/1895assertions, 67.933s, 229MB**, PHP8.3.6/PHPUnit11.5.56.
This includes 69 workflow/checkout cases (previous67 plus two same-day cases),
domain/API/schema tests and static support layout checks. Assertion growth
includes seven source-origin checks per ModuleTestCase case; it is not reported
as that many new business scenarios. Native MySQL board tests remain excluded.
Final affected5tests/78assertions PASS5.940s and scoped Pint PASS.

Same-day/harness source digest: `0c1c488491a0aa5783fd734eccce87911891d8f9961d03fb505108842edf6258`. Formula: SHA256 of the ordered lines
`path + space + file_sha256 + newline` below; evidence file excluded to avoid
self-reference. Git fixed revision and official independent Validation are owned
by the lead. This developer run does not approve the reviewer's implementation.

| Source path | SHA256 |
|---|---|
| `modules/_bundled/raonslab-travel_lab/src/Services/TravelCartService.php` | `5c326179dbfa17e2c482853f203ea53ed70015e25fe9b501d460247e5ecbdb8f` |
| `modules/_bundled/raonslab-travel_lab/tests/bootstrap.php` | `dd821e1397bbd1244516c73fc705e3510b9c3a8dedeb598cbeeee68480f57da2` |
| `modules/_bundled/raonslab-travel_lab/tests/ModuleTestCase.php` | `3ad3c1ce5e11ac52e53734d0397862688c2312081b57589b784fe4fa048a1f58` |
| `modules/_bundled/raonslab-travel_lab/tests/WorkflowTestCase.php` | `2212ae45632eba276b17785841930bd10852b3be8072d370d52de5215ecd0d89` |
| `modules/_bundled/raonslab-travel_lab/tests/Feature/TravelWorkflowRegressionTest.php` | `2ba745b1f725aedb42c25d17b935c4a60200da14e56bd76ed117716b535d859d` |
| `modules/_bundled/raonslab-travel_lab/tests/Feature/TravelCheckoutGuardTest.php` | `b36e75d6405c4cafe319f318f4fb64505abe750e0e7fbcfe0f789a6f1c7c201f` |
| `modules/_bundled/raonslab-travel_lab/tests/scenarios/workflow.yaml` | `92cba695e89167c4ac174281501c800ba8993aa27e1b1b9d16d6a12f75e8aa5b` |
| `modules/_bundled/raonslab-travel_lab/tests/README.md` | `d3955735494cbbc59fc6795d286ff82c2fd844d2ff0cde0ac04c30bbeaa00248` |
| `modules/_bundled/raonslab-travel_lab/docs/api/workflow.md` | `c68ae6e4bd0cb710e7f66960aa107e6123e0b2c51454b8143e3bca8d9a042e64` |
