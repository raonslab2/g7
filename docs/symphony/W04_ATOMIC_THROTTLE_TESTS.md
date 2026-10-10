# W04F-02 atomic travel throttle — test-author evidence

**Latest PASS: 21 tests / 2,209 assertions** for native DB admission, cleanup-error preservation and preserved support counter/auth-ordering cases; the canonical W03 contract suite separately passed **8 / 142**. Initial 19 / 2,186 evidence and hashes remain below. This is a source-bound test-author result on isolated SQLite, not independent MySQL/HTTP contention verification, a product release PASS or formal Validation. **W04F-02 remains OPEN pending a new fixed-SHA live recheck.**

## Ownership and original negative evidence

- Work `work-20261009-g7-symphony-max-child-c7ae42d1`, parent `req_81ac33cac94046b9a2249cd14c0d00ba`, native test author `/root/w03_commerce_guards`.
- Lead authored `TravelThrottleRequests` and owns routes, translations, LAB runtime cache policy, feature test bases, Git delivery and source installation. This agent owns the four initial test/worker paths, the subsequently assigned W03 contract-test metadata adaptation and this report. This is not an independent review of the lead's implementation.
- Independent Request `req_0683bc352fac4373b748cee64dedc53a`, target `1052e3fb4bc4cccabb51b8c538116c78655f345b`, report `docs/symphony/W04_SECURITY_FINAL.md` SHA256 `dfb75a10bc2b39fcb7d52587e254c9140ada08f4a2820df7184d3143e29c03fd`, observed native FileStore counter loss in actual HTTP: four lanes admitted 611 before first 429 at request 612; two lanes admitted 661 while the file counter was 561. Those observations remain negative evidence. They were neither removed nor repeated by this author.

## Actual fixtures and preserved contracts

`TravelAtomicThrottleTest` starts a plain, unbooted Application with an explicit local config, actual Capsule SQLite `:memory:` connection, `cache` / `cache_locks` tables, real native CacheManager implementing the cache Factory contract, DatabaseStore, DatabaseLock and RateLimiter. Limiter and lock use the same store. No model/controller action, application boot, dotenv, APP/TEST account, installed cache or shared service is involved. ResponseFactory and the actual module translation files serve the 503 envelope; there is no fabricated busy response.

Existing `TravelSupportThrottleIsolationTest` retains its **5 / 53** cases/assertions and actual support route metadata, actor/prefix budgets and create/read nesting. Its fixture now supplies the required actual DatabaseStore instead of ArrayStore and filters the registered FQCN middleware rather than the old throttle alias. Authenticated actor injection remains explicitly a budget-only fixture.

Existing `TravelSupportAuthThrottleOrderingTest` retains its **5 / 1,979** cases/assertions. It still uses actual native Router/Kernel priority sorting, native optional-Sanctum decisions and required-auth middleware, with only token lookup/provider data isolated. Each request starts guest; no identity is preassigned. It now uses DatabaseStore for admission and recognizes the travel subclass through native ThrottleRequests inheritance. Guest/invalid/expired/member decisions, separate same-IP member budgets, 600/601 boundaries and create-10/question-120 nesting are preserved. Actual controller constructor metadata resolves with an unused mocked service; no controller action runs.

All three test classes run in separate PHP processes with global-state preservation disabled. Own module class loaders are unregistered and container/facade/clock state restored. Scoped Pint and whitespace checks pass.

## Meaningful admission control before/after

The new critical-stage test was first run with the **real native parent ThrottleRequests** and real DatabaseStore/RateLimiter instead of the new adapter. A Connection `beforeExecuting` observer saw the first actual counter SQL operation while `cache_locks` held **0 rows**. The assertion requiring an admission lease failed: **1 test / 5 assertions / 1 FAIL**, 0.278 s, 12.00 MB. The temporary test-only parent selection was then removed; no production source was changed for this control.

This checks absence of the required critical-section lease in the native parent, **not reproduction of the historical FileStore lost-increment counts**. No absent-class error is counted as a product fail-first result. The final critical-stage test runs the new travel middleware and verifies a real lease row throughout native counter reads/writes, expiration exactly 30 seconds from its frozen clock, nonempty native owner, release before the terminal callback, and successful same-key nested admission inside that callback.

## Executed checks

PHP 8.3.6 / PHPUnit 11.5.56, no-configuration bootstrap `vendor/autoload.php`:

| Command / phase | Result |
| --- | --- |
| `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php` | **PASS 10 / 2032**, 5.529 s, 12.00 MB |
| New fixture initial diagnostic run | **8 / 91, 3 ERROR / 1 FAIL**: unbooted fixture omitted `app.locale` for native ResponseHelper; the first owner-replacement selector expected a projected `owner` column while native DatabaseLock selects the row. These were fixture defects, not product findings. |
| New fixture after those harness corrections, before adding process case | **PASS 8 / 123**, 4.082 s, 12.00 MB |
| `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelAtomicThrottleTest.php` after process case | **PASS 9 / 154**, 9.596 s, 12.00 MB |
| `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelAtomicThrottleTest.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php` after final formatting | **PASS 19 / 2186**, 14.924 s, 12.00 MB |
| `php vendor/bin/pint --test tests/Unit/Extension/TravelAtomicThrottleTest.php tests/Fixtures/TravelAtomicThrottleWorker.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php` | **PASS** |
| Scoped `git diff --check` | **PASS** |

The nine new cases (two are data-provider variants) verify:

1. Exact sequential 10/11, native 429 limit/remaining/retry headers, per-actor and per-prefix isolation, 61-second expiration/reset and no leftover admission lock.
2. Actual native DB lease during admission, 30-second expiration, release before terminal business logic and same-key nested request succeeding without an admission timeout.
3. Injected counter SQL failure propagates and releases the owned lease, with zero hits.
4. Downstream controller exception propagates after release; its admitted request still consumes one attempt as in the native policy.
5. Unsupported ArrayStore fails closed with actual translated 503/Retry-After 1, no controller, no DB counter or lease.
6. A real separately-owned native DatabaseLock blocks admission until the configured 3-second wait expires, returns the actual 503 envelope, preserves that holder and records zero hits. This case explicitly unfreezes Carbon: native Lock::block uses `now()`, so freezing it would make a real timeout unbounded. The holder is released in `finally`.
7. Actual fixture `cache_locks.owner` replacement just before the first native ownership read prevents all counter admission (0 hits), returns 503 and preserves the successor row.
8. Actual fixture owner replacement during counter admission makes the pre-release guard return 503, does not invoke the controller, preserves the successor row and leaves the one already-counted admission. Neither variant mocks a release boolean or deletes another owner's row.
9. Four independently bootstrapped PHP workers share one caller-owned temporary SQLite file and the same actor/prefix. All four reach a start barrier; recorded PID values are distinct and execution intervals overlap. Across **60 requests** to a synthetic **20-attempt** budget, **20 returned 200 / 40 returned 429**, actual final limiter counter is **20** and admission locks are **0**. Workers have 10-second barrier / parent 15-second execution bounds; the random temporary directory is mode 0700, DB file 0600, and only these workers/files are cleaned in `finally`.

## First frozen source and test pins (before cleanup correction)

| Path | SHA256 |
| --- | --- |
| `modules/_bundled/raonslab-travel_lab/src/Http/Middleware/TravelThrottleRequests.php` (lead-owned) | `77e8dd9d43837ae155f5335d46b194098a30ca2eda0a78be8f3084e504f6b664` |
| `modules/_bundled/raonslab-travel_lab/src/routes/support.php` (lead-owned) | `86529ee8eb4c566adc1fa6dea9bd2a14b36d15a5ed8b571360efdc2a0856877d` |
| `modules/_bundled/raonslab-travel_lab/src/routes/workflow.php` (lead-owned) | `fb5e2dd56b9fcd595a4a9237db483dc6dc1856f5210355d27f03fcfbaf327cbf` |
| `tests/Unit/Extension/TravelAtomicThrottleTest.php` | `789632a334d90c28d9707589a14ea9c1510e9d3464b7ccbd306c098265a3d328` |
| `tests/Fixtures/TravelAtomicThrottleWorker.php` | `9c91889f8dc50d5014b7a82c6abe49f4b1ca03bfc3a428d30df41a5c537cd694` |
| `tests/Unit/Extension/TravelSupportThrottleIsolationTest.php` | `1b89a1327e799d2f5e2cd5fa4828266d84e9203dbfeaa7590dfa969eb6a0b5b6` |
| `tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php` | `f4624dd1f812b05bcd1f660e1e3ec1d02dfa52e7e8c57e0480c50e0f90b52b4e` |

## Independent cleanup finding and subsequent test freeze

An independent source review found that the middleware's `finally` release could throw and mask the original native 429 or counter exception. The lead corrected the middleware to preserve the original Throwable while attempting native owner-conditional cleanup. This author's two new data-provider cases inject an actual `delete from cache_locks` query error after changing the fixture row to a successor owner. They assert that the release was attempted, the controller did not run, the successor row remains, and either the original native 429/headers or the **same original RuntimeException object** survives. The native-429 counter remains 1; the original counter-error case remains 0. A cleanup failure is lease-bounded; it is not represented as a successful release.

The lead also reported its full-domain run **154 / 2,519 with one failure** from W03SecurityRecheckTest's old literal `throttle:` alias expectation. This author changed only that expected middleware representation to actual `TravelThrottleRequests::with(...)` strings, preserving all numeric/prefix, route, Sanctum and permission assertions. No throttle bypass or reduced budget was introduced. The earlier full-domain failure is not replaced by the narrower passes below; the lead owns another full-domain run.

| Latest command | Result |
| --- | --- |
| `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelAtomicThrottleTest.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php` | **PASS 21 / 2209**, 18.823 s, 12.00 MB; new atomic cases 11 / 177, prior support cases 10 / 2032 |
| `php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter=W03SecurityRecheckTest` | **PASS 8 / 142**, 6.838 s, 74.50 MB; includes canonical actual hook/provider/declaration and workflow route metadata |
| `php vendor/bin/pint --test tests/Unit/Extension/TravelAtomicThrottleTest.php modules/_bundled/raonslab-travel_lab/tests/Feature/W03SecurityRecheckTest.php` | **PASS** |
| Scoped `git diff --check` | **PASS** |

The cleanup fault tests were executed after the lead's source-ready notification. **NOT_RUN:** these two tests against the prior uncorrected middleware. The original independent source finding and first-phase source hash remain preserved; neither initial fixture setup errors nor the parent admission-control failure is mislabelled as a cleanup-fault reproduction.

| Latest changed path | SHA256 |
| --- | --- |
| `modules/_bundled/raonslab-travel_lab/src/Http/Middleware/TravelThrottleRequests.php` (lead-owned cleanup fix) | `aab02c6b01254dd62b7973ee5b0fbc68bb0afd052d49171ac19f3bd21e97e92d` |
| `tests/Unit/Extension/TravelAtomicThrottleTest.php` | `24b16ce672b2341dfe382f7492b7fef0b73bdb97bd1806598513cc5a7c8b51a6` |
| `modules/_bundled/raonslab-travel_lab/tests/Feature/W03SecurityRecheckTest.php` | `10aa59de23fb412198e35fa280808e252711038af23ece32417ce971ecc4118a` |

The worker, support counter and auth-ordering test hashes remain those in the first freeze. The canonical W03 suite uses the lead's now-corrected actual database-cache feature fixture; its assertions are not counted as independent MySQL evidence.

## Limits and next verification

- SQLite locking differs from InnoDB; its whole-file writer serialization and `lockForUpdate` behavior cannot certify the real MySQL row-lock/counter path. The process result is bounded to one temporary file, one synthetic budget and this native admission adapter. It is neither production load testing nor provider capacity evidence.
- Native cache error recovery is checked for an injected counter-table operation failure, and original-error preservation is checked for an injected owner-conditional lock DELETE failure with a successor present. A total database outage, arbitrary prolonged DB calls, worker death/OS suspension, and the 25-second monotonic guard under a real long stall are **NOT_RUN** here.
- Feature-base migration to a real native DB cache fixture belongs to the lead; this agent did not bypass middleware or change those bases. This report does not claim module feature/browser/runtime regression after cache policy changes.
- **NOT_RUN:** new fixed-SHA MySQL/HTTP multiple-worker 600-budget contention and precise accepted-vs-counter comparison, deployed source/cache-driver parity, independent nonauthor source review, formal Validation/CI. These are required before claiming W04F-02 closed.
- No production PHP/routes/language/environment files, APP/TEST schema/account, installed runtime/cache, service, Git staging/commit/push or external actor were changed by this test author. Only in-memory fixtures and the independently-owned temporary SQLite process fixture were written.
