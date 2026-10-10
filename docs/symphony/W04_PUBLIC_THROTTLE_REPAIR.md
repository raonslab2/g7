# W04-S01 public authentication/throttle ordering repair

Independent CODEX support review Request `req_1c49be4c4f164489b069557bb1db4d06`, evidence commit `3251043d6eaa9a467b13758573ad853f55f621f7`, found **CHANGES_REQUIRED** at product SHA `598a89fff702d51c1405f1a5952d95ab1d2651f4`: public authenticated actors on one IP consumed the same throttle counter. Its report and `middleware-order.json`/`middleware_order.php` were inspected read-only; no original evidence was changed. This repair and local regression do not retroactively turn that review into PASS.

## Diagnosis and bounded production contract

The native Kernel prioritizes `AuthenticatesRequests` before `ThrottleRequests`, and throttling before `SubstituteBindings`. Core `OptionalSanctumMiddleware` does not implement that authentication marker. The original public route resolved to **ThrottleRequests → SubstituteBindings → OptionalSanctumMiddleware**, so throttling saw the guest domain/IP signature before optional authentication established the actor.

The earlier 5/53 `TravelSupportThrottleIsolationTest` extracted only numeric throttle middleware and injected already-authenticated GenericUser identifiers. It verified prefix separation but could not verify this native auth-ordering behavior. Its earlier result remains counter-only evidence.

The lead added travel-module `src/Http/Middleware/TravelOptionalSanctum.php`, a subclass of core OptionalSanctumMiddleware implementing the native `Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests` marker. It inherits `handle()` without overriding authentication. Only public support routes now select this class. Native sorting consequently resolves **TravelOptionalSanctum → ThrottleRequests → SubstituteBindings**. No core authentication implementation, global priority list or unrelated route is changed. Public guest/expired-token/invalid-token behavior stays inherited. The 600 public, 120 aggregate questions and ten-create per-minute limits/prefixes are retained.

The lead owns both production files. This native agent owns the new ordering test, the existing counter test's metadata update (expected public class rather than old alias), and this report. The metadata test still requires guest-compatible public routes and `auth:sanctum` on all question routes; no behavioral assertion was dropped.

## Fail-first and repaired executions

Environment: PHP 8.3.6, PHPUnit 11.5.56. Frozen native clock: `2026-10-09 12:00:00 UTC`. Each ordering case executes in a clean PHP process, with no PHPUnit global-state import.

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php
vendor/bin/pint tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php
```

| Execution | Actual result |
| --- | --- |
| New ordering suite, before lead production change | **EXPECTED FAIL**, exit1, **5 tests / 1,374 assertions / 3 failures**, 1.957s, 12MiB |
| New ordering suite, after production change | **PASS**, exit0, **5 tests / 1,979 assertions**, 2.321s, 12MiB |
| Final post-Pint ordering + existing counter suites | **PASS**, exit0, **10 tests / 2,032 assertions**, 2.965s, 20MiB |
| Pint and PHP syntax checks for both test files | **PASS** |

The three valid baseline failures were actual Router auth-after-throttle sorting; a second valid actor's first same-IP request returning429 after the first actor's600 reads; and the first valid actor receiving429 after an anonymous client's600 reads. Optional token-decision and required-question auth/limit cases already passed. Early harness construction attempts stopped safely on unused-controller DI and unbound route parameters; those setup errors were corrected before the reported valid fail-first run and are not product findings.

The five ordering cases cover:

1. Actual registered middleware, expanded and priority-sorted by the native Router, authenticates before signing the public limit key.
2. Two distinct valid fixture actors on the same IP each receive600 accepted public reads and429 at request601.
3. Anonymous clients on the same IP share600/601; a different IP remains available, and a valid actor on the exhausted anonymous IP remains available.
4. Native optional authentication permits guest/expired-token access, rejects invalid token with native `ResponseHelper`401/error envelope, and establishes a valid actor. Expired tokens do not invoke the authenticating fixture guard.
5. Actual native required authentication rejects anonymous question reads401; ten creations pass, eleventh429/limit10; another actor remains available. The outer question budget counts the rejected eleventh creation plus109 reads, then rejects request121/limit120. Public reads remain available after question exhaustion.

The larger assertion total includes a guest-initial-state assertion on each sequential request and repeated sequential600-request budget checks. It is **five ordering scenarios**, not1,979 independent release checks or HTTP product-controller executions.

## Native boundaries and source binding

The fixture uses an unbooted Application, native Kernel default priority, native Middleware configuration aliases/API binding group, and the inspected G7 optional alias. The actual bundled support route source and native SupportController middleware metadata are used. Actual `Router::gatherRouteMiddleware`, native `SubstituteBindings`, OptionalSanctumMiddleware, Authenticate, AuthManager/RequestGuard, Pipeline, RateLimiter, ArrayStore and ResponseHelper execute. Every request begins guest; no user is preassigned. A valid bearer fixture must pass the native optional-token decision and native Authenticate guard selection before its dynamic request user resolver supplies an actor.

SQL `PersonalAccessToken::findToken` is replaced by an explicit in-process token-record lookup; user-provider data is an injected RequestGuard callback. Synthetic tokens are generated in memory and never logged or persisted. Thus actual database Sanctum token validity, native login and support controller/business persistence are **not certified**. SupportController is constructed with an unused mocked service solely for native middleware metadata; the terminal uses ResponseHelper with a fixture actor field, not a product support response. Unrelated G7 API-group policies, global middleware and application exception rendering are outside this isolated pipeline. Native token decision/order/counter behavior is the measured scope.

Clock, Container/facade context and the temporary module ClassLoader are restored. No dotenv read, application boot, SQL schema/connection, credential read, production/file cache, cache reset, service restart or network is involved. Sequential ArrayStore limits do not establish multi-worker file-cache atomicity. Installed source/cache provenance, sustained HTTP races and independent actual public600/601/member isolation recheck remain **NOT_RUN here**. The official nonauthor must recheck the published candidate; hosted CI, canonical Validation and whole-product PASS remain separate.

Final SHA-256 review input:

| File | SHA-256 |
| --- | --- |
| `tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php` | `a0b870889de8b28a7f0cff8db14f58c2e183b92d06a4c06ce3031a2832e1dfa2` |
| `tests/Unit/Extension/TravelSupportThrottleIsolationTest.php` | `985357e8bbea8bee707ea7b9fd6cbab3c431cfeed4790b517819d53b63d8082c` |
| `modules/_bundled/raonslab-travel_lab/src/routes/support.php` | `e04270e3628c1a59e6d8585b855f8eeaee3126e8ad1b40be7bca822e16d77195` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Middleware/TravelOptionalSanctum.php` | `268e1a45c37eb266676a8bf580b26f3c09bf8f15eb92a76517a872d6814198d2` |
| Core `app/Http/Middleware/OptionalSanctumMiddleware.php` | `5f43087b44425fc4ed4d08ce2a4a6c9bfa9093368c56d2adf1c5371cd8cd1e96` |
| `bootstrap/app.php` | `5089e0d9f00ad17c0d4ee6e199b4ab2622349cd140b255efe73a16a7e429f62d` |
| Native Kernel | `9b5e787137463aed13372a8bbaaf0bf44307bc7e7190031325b72995df8eb383` |
| Native SortedMiddleware | `bc0beaae21b1293f37c295c8805897cad3e873d423ec64ac8246832f2775c793` |

These are source-bound local checks, pending lead checkpoint binding and nonauthor review. This native agent performed no staging, commit, push or deployment and did not edit other Request files.
