# W04 atomic travel throttle — bounded nonauthor source review

**Final decision: SOURCE REVIEW PASS at the final input pins below; runtime verification pending.** The initial review was CHANGES_REQUIRED. Root corrected both findings; their original observations and initial hashes remain below. This approves the bounded implementation contract for a fixed checkpoint, not runtime activation proof or W04F-02 closure. Independent fixed-SHA MySQL/HTTP concurrency and runtime activation checks remain **NOT_RUN** by this reviewer.

## Scope and independence

- Work: `work-20261009-g7-symphony-max-child-c7ae42d1`; parent Request: `req_81ac33cac94046b9a2249cd14c0d00ba`; native reviewer: `/root/w03_ui_repairs`.
- Reviewed root-owned implementation, routes, translations, marked-lab environment/setup runners and actual native framework source. The reviewer authored the preceding design note, but did **not** implement this middleware, route/cache guards or enforcement test fixtures. This is a nonauthor implementation source review, not an independent design-author review or canonical Validation.
- Input HEAD: `0906083ec41f43e968397bfefee32c8243bf9908`; reviewed middleware and fixture are untracked additions and several guards/routes are working-tree modifications. **HEAD alone does not identify the reviewed candidate.** SHA-256 pins below identify actual inputs.
- Only this report was written. No product/config/environment/DB/service changes, private env/access-file reads, test executions, lifecycle commands, Git staging/commit/push or official child/subagent creation occurred.

## Findings

### ATR-01 — initial P2, CLOSED by source correction: backend failure and cleanup response contract

`TravelThrottleRequests` catches only native `LockTimeoutException` from `block()`. Counter-store or ownership/release SQL failures propagate. The test `test_native_counter_store_error_releases_owned_admission_lock` explicitly expects a synthetic counter `RuntimeException` to propagate while the lock table remains healthy. The package README, however, promises that an unavailable lock/backend returns retryable **503**, with exhausted quota retaining **429**. Actual backend failure follows the application exception path rather than that translated503/Retry-After contract.

There is also a distinct cleanup path: native `parent::handleRequest` raises `ThrottleRequestsException` when quota is exhausted; the adapter's `finally` immediately calls `DatabaseLock::release()` without protecting the original exception. A lock ownership SELECT or owner-conditional DELETE exception during cleanup replaces the original native429 (or an earlier admission error). Native `DatabaseLock::release()` declares throwable behavior and rethrows non-concurrency errors. Thus the source admits a concrete response-contract violation even though no controller is admitted and the lease eventually expires.

**Required correction:** define and implement the intended protection-error response consistently. Translate an unavailable admission backend to the module's retryable503 without swallowing controller exceptions. Cleanup must not replace an already selected native quota429 or the intended primary error; owner-scoped, bounded lease cleanup must still be attempted. Preserve fail-closed admission and never force-release a successor's lock. Add focused fixtures that fail lock ownership/release, including the already-exhausted-budget branch and admission-counter failure with failing cleanup. Keep controller exceptions unchanged after the admission lock is released. A source-only trace is the evidence here; no outage was injected into APP/TEST.

### ATR-02 — initial P2, CLOSED by source correction: ordinary TEST cache isolation

The public APP example now selects `CACHE_STORE=database`. `setup.php` creates a missing `.env.testing` by replacing only `APP_ENV=local` and the APP schema name in `.env`; this leaves `CACHE_STORE=database` and native DB cache fields in the TEST file. `travelLabEnvironment(true)` intentionally skips the APP cache validation and returns those marked values. `run.php test` passes them as the PHPUnit subprocess environment.

Root `phpunit.xml` declares `CACHE_STORE=array` without `force=true`. Installed PHPUnit's `PhpHandler::handleEnvVariables` writes an env value only when forced or `getenv(name) === false`; an inherited database value therefore survives. Consequently the documented ordinary TEST array baseline is not assured by the public fresh-package path. This is an isolation/configuration contract defect within the allowed TEST schema, **not evidence of access to production or another project's DB**.

**Required correction:** normalize generated/recovered ordinary TEST environments to array cache in the marked lab path, or explicitly supply that baseline in the guarded test subprocess. Validate its effective selection. Dedicated travel admission route tests should continue opting into real native database cache/lock tables on their already validated fixture connection through `UsesDatabaseThrottleCache`. Add a generation/runner regression covering an APP database-cache example, an existing TEST database-cache value, and PHPUnit inherited env precedence. Do not globally force array on the actual enforcement fixture.

## Correction recheck and final source decision

Root changes were re-read without execution:

1. Middleware catches the primary Throwable, records it and rethrows it. The finally release attempt catches a cleanup Throwable and raises it only when there is no primary failure. Thus native429 and the exact earlier backend/error exception survive failing cleanup. The release-before-controller success path remains intact; controller exceptions are not converted to busy responses.
2. Package README now accurately limits503 to lock timeout, unsupported stores and lost admission leases. Native database/backend failures retain native error handling. This is a deliberate narrower contract, not evidence that total backend outage returned503 in a live test.
3. A new TEST file explicitly substitutes `CACHE_STORE=array`. In addition, `travelLabEnvironment(true)` merges the array baseline **after** inherited and marked file values, so an existing marked TEST database-cache setting cannot override the guarded ordinary subprocess baseline. Files are not mutated by the environment reader. Dedicated route fixtures still explicitly configure their own native DB cache and connection; their enforcement is not bypassed.
4. New focused cleanup fixture source covers an exhausted native quota and an injected primary counter error while actual native owner-conditional release SQL fails. It verifies the original exception, no downstream call, unchanged counter and preserved successor-owner row. The subsequently updated test-author report records atomic11/177 plus support10/2032, combined21/2209. Execution is **NOT_RUN by this reviewer**; the new author result is separate from this nonauthor source decision and independent runtime gates.
5. The revised standalone guard creates its own temporary surviving TEST database-cache env and verifies that `travelLabEnvironment(true)` returns array. It checks the new precedence boundary without connecting to APP/TEST or altering their private files. Source inventory is now27 checks; execution is still not claimed by this reviewer.

No P0/P1/P2 implementation finding remains at these final source pins. Existing APP file-cache env migration remains an explicit manual, root-owned stop/change/restart procedure. Normal short admission, limited lease semantics, strict APP scope and fresh fixed-target runtime tests are mandatory practical bounds. A passing source decision does not supply a blanket atomicity guarantee across arbitrarily long stalls.

## Confirmed implementation properties (initial snapshot and final corrections above)

| Property | Source-review result |
|---|---|
| Native check/add/increment/repair retained | `parent::handleRequest` and native RateLimiter remain the admission engine. No parallel custom counter algorithm. |
| Same configured store | Native CacheServiceProvider resolves RateLimiter from `cache.limiter`; adapter resolves the same factory selection. APP guards require native database default and null limiter override. No dynamic store reconfiguration is certified. |
| Shared lease | Native DatabaseStore creates DatabaseLock keyed by SHA-256 of the native budget/actor key; lease30s and wait3s. Supported routes each use one numeric limit, no after-response callback. |
| Owner protection | Native lock acquisition uses insert/expired-owner update; release checks ownership and deletes by key **and owner**, without forceRelease. Successor owner rows are preserved. |
| Short admission scope | Monotonic25s bounds include acquisition; ownership checked before admission and before release; elapsed time checked again after release. Inner middleware/controller is invoked only after successful release. Native parent response-header reads after controller are outside admission. |
| Native quota semantics | Normal exhausted quota is still native429; success uses native rate headers, prefixes and actor signatures. ATR-01 covers failure during cleanup. |
| Busy response | Unsupported store, detected lost/late protection and lock timeout produce translated ResponseHelper503 with Retry-After1. ko/en messages exist. Backend errors deliberately remain native; final README now states that boundary. |
| Route scope | Only travel workflow/support routes substitute the FQCN numeric middleware. Public support600, questions120 plus create10, workflow120, cart60, submit10, cancel20 and admin60 retain distinct existing prefixes. Auth middleware/permission contracts remain separate. No core global alias/priority changes introduced by this adapter. |
| Native feature fixture | UsesDatabaseThrottleCache runs native cache/cache_locks migrations only when absent and sets cache/lock connections to `database.default`; cache drivers and RateLimiter singleton are reset before tests. It does not remove throttling or substitute an array counter. Workflow and support test bases invoke it. |
| APP guard | Marker, local loopback MySQL, schema/account/prefix, native cache and lock connection/table names, mailarray/queuesync/local storage, G7 env priority and absent installer/config-cache overrides are checked. Live bootstrap independently checks effective APP DB/cache/egress selection and SQL DB/account identity. |
| Existing-env transition | README explicitly instructs stop/update guarded marked env/cache fields/restart. setup's legacy recovery currently only fills missing G7 priority/provisioning. Automatic old file-cache migration is **not** implemented; documented manual transition must precede activation. No runtime switch was performed here. |

The native DatabaseLock boolean release result is not a row-count proof: it returns true after a delete and on recognized concurrency exceptions. Ownership and elapsed bounds reduce normal admission risk; they do not prove correctness across unbounded process suspension, database stalls beyond the lease or clock discontinuity. A stale process can resume after lease takeover. These are documented execution limits, not a claim that a source bound is a production load-test PASS.

The APP guard pins connection/table selection. Cache prefix itself is supplied by the public example but is not independently allowlisted, and APP-specific TEST cache validation is skipped. Final guarded TEST output nevertheless explicitly selects ordinary array cache. This source review does not invent cross-project DB leakage from these facts: main schema/account isolation and the dedicated fixture's connection reset remain present.

## G7 native primitive and CacheInterface boundary

`App\Contracts\Extension\CacheInterface` defines extension cache-domain CRUD/remember/tag/store operations; it exposes neither native limiter increment/add semantics nor owner locks. This adapter injects the native `Illuminate\Contracts\Cache\Factory` specifically to protect Laravel's own ThrottleRequests primitive and does not create module business-data cache records through a new domain service. It has no Cache facade call, global CacheInterface rebinding, new cache driver/engine or core throttle alias override. Existing module domain caching rules remain applicable to domain services. Within this explicit framework-adapter boundary, native Factory use is consistent with the selected mechanism; it is not a blanket exemption for future business caches.

## Evidence attribution and remaining gates

| Evidence | Attribution/status |
|---|---|
| Legacy W04F-02 file-store races | Prior independent security result: 611 and661 accepted against a600 budget under different runs. Preserved negative evidence; not rerun or rewritten here. |
| Atomic fixture | Initial test-author9/154; final test-author11/177 includes cleanup failure preservation. Report/source hashes match inspected files. Native SQLite fixture, including4 actual PHP worker processes, total20 admitted/40 limited and final counter20. **Not independently executed HTTP/MySQL verification.** |
| Counter/auth-order fixtures10 tests/2032 assertions | Test-author report only, inspected source pins. No rerun by this reviewer. |
| Root environment guard | Initial26-case source lacked surviving TEST-cache coverage. Final27-case source adds it. **No execution by this reviewer.** |
| This review | Initial CHANGES_REQUIRED; final **SOURCE REVIEW PASS** with ATR-01/02 closed by the pinned source corrections. No runtime PASS or canonical Validation receipt. |
| Actual new-cache runtime source/store parity, MySQL/HTTP600-budget contention, exact accepted-vs-counter conservation | **NOT_RUN** here; required on newly fixed integration SHA and active guarded database cache. |
| Fresh install/recovery and user/admin/browser regressions after cache activation | **NOT_RUN** here; prior fixed1052 runtime evidence covers the previous cache target only. |

Root owns corrections, checkpoint/Git delivery and runtime activation. Before closure, independently test the final fixed middleware SHA on the installed marked APP cache selection and rerun the relevant browser/permission/regression paths. Do not run runtime mutations concurrently with a fixed-target browser verification.

## Initial reviewed input pins (SHA-256; negative observations preserved)

| Input | SHA-256 |
|---|---|
| `modules/_bundled/raonslab-travel_lab/src/Http/Middleware/TravelThrottleRequests.php` | `77e8dd9d43837ae155f5335d46b194098a30ca2eda0a78be8f3084e504f6b664` |
| `modules/_bundled/raonslab-travel_lab/src/routes/support.php` | `86529ee8eb4c566adc1fa6dea9bd2a14b36d15a5ed8b571360efdc2a0856877d` |
| `modules/_bundled/raonslab-travel_lab/src/routes/workflow.php` | `fb5e2dd56b9fcd595a4a9237db483dc6dc1856f5210355d27f03fcfbaf327cbf` |
| `modules/_bundled/raonslab-travel_lab/src/lang/en/messages.php` | `a7d7a4e24eb20d1e943bf73825de516a82cb7d385c92ab67902a872d6e408a05` |
| `modules/_bundled/raonslab-travel_lab/src/lang/ko/messages.php` | `ffed7054a5147481140eaeee9607f2f3e31877e0c4ce803af4d6a1ab3b098207` |
| `.env.travel-lab.example` | `907f93cc6647c2be9046b2142da9ddaac51d6d53afca4be905f53853d8e1017d` |
| `scripts/travel-lab/environment.php` | `b4655d4b441b9135e69a05b729c916ab7ea0e048149e5074b4818993e3220fb8` |
| `scripts/travel-lab/live-bootstrap.php` | `98a1324c1fe93a27e4a5ddbf79d2086dfc8e4c87968595735687078bb6d4d135` |
| `scripts/travel-lab/setup.php` | `67e2966335c4cc8c896bed22baaf33bedcf60712ef2a1e26e9debb3be4e9a3e2` |
| `scripts/travel-lab/run.php` | `6b9d1af8a2b48ca8af88cc3910622ee2f601b7a57d5411233f697cd58566816f` |
| `scripts/travel-lab/guard-test.php` | `60c9f695d4d0dae5f5285015cf97f2b9963f9aee5250366f298da946626389a9` |
| `modules/_bundled/raonslab-travel_lab/tests/UsesDatabaseThrottleCache.php` | `ed82ef4de82d0fe648fe8cb67e793b8e884c397b1400b8935dbccd19bca086e6` |
| `modules/_bundled/raonslab-travel_lab/tests/WorkflowTestCase.php` | `7150a79f20c277386a3c0ac296fb7e10f94b83874c834d508c5752751c09d187` |
| `modules/_bundled/raonslab-travel_lab/tests/SupportTestCase.php` | `ce2c67c8b211193fc58ccb2b3646514371fce36ecd7432b27f1c1418473c4ce3` |
| `tests/Unit/Extension/TravelAtomicThrottleTest.php` | `789632a334d90c28d9707589a14ea9c1510e9d3464b7ccbd306c098265a3d328` |
| `tests/Fixtures/TravelAtomicThrottleWorker.php` | `9c91889f8dc50d5014b7a82c6abe49f4b1ca03bfc3a428d30df41a5c537cd694` |
| `tests/Unit/Extension/TravelSupportThrottleIsolationTest.php` | `1b89a1327e799d2f5e2cd5fa4828266d84e9203dbfeaa7590dfa969eb6a0b5b6` |
| `tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php` | `f4624dd1f812b05bcd1f660e1e3ec1d02dfa52e7e8c57e0480c50e0f90b52b4e` |
| `docs/symphony/W04_ATOMIC_THROTTLE_TESTS.md` | `7189ead1e41c74ce0a04961f4c60240c1de5d0b4c53561b1a31a69440dc8e9b8` |
| `deploy/travel-lab/README.md` | `185b0545aaf15fed133d30f7497c54a601118a164a2e260f67b9b4e9408f2678` |
| `phpunit.xml` | `3243a89815b472295657e48e90ae1b07acaeb3e2f998351a9ee7f0aac3972d98` |
| `vendor/phpunit/phpunit/src/TextUI/Configuration/PhpHandler.php` | `65acfc365e7968ff47af2f739d4a2765a86008c557eca88684edb9b8cba1c66d` |
| `vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php` | `1df45d12a4d675d15b10353eff2f0c3457c6eab79e4ffe50fb85b6dffe2e6b34` |
| `vendor/laravel/framework/src/Illuminate/Cache/DatabaseLock.php` | `05257b67976a203d0b45d8bf871671c8d4166f5a3e599326ce1f4ac0c9991ff8` |
| `vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php` | `18ed0bb8e3c89c6df6da74e34b9e8d1a5d8a197ee4cf39cdecae1d592fd3390a` |
| `vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php` | `73124b8bdfee5ed080630d466d970a90a867abb368e8cbf0d948875398b5048b` |
| `vendor/laravel/framework/src/Illuminate/Cache/CacheServiceProvider.php` | `8dca04071c1e46f7010d80823d973e10f2d01959ea8387c66b1e4cecf551ffe2` |
| `app/Contracts/Extension/CacheInterface.php` | `71ffd9683d31cadbae93f46849c64dfad339c4a0449737daf366b3b04374dd69` |
| `config/cache.php` | `b5d633029fa736bcec98516b984624a78ea4a03a83931a62f044f45972bba1da` |

Pins are content hashes of public source only. Installed vendor files are inspection inputs, not additional committed delivery files. No private credential/env content is included.

## Final corrected input pins (SHA-256)

HEAD remains `0906083ec41f43e968397bfefee32c8243bf9908`; these are working-tree content pins, not a fixed integrated commit. All unchanged inputs retain their initial pins. A fixed checkpoint and independent runtime target must include the final changed files below.

| Corrected/rechecked input | SHA-256 |
|---|---|
| `modules/_bundled/raonslab-travel_lab/src/Http/Middleware/TravelThrottleRequests.php` | `aab02c6b01254dd62b7973ee5b0fbc68bb0afd052d49171ac19f3bd21e97e92d` |
| `scripts/travel-lab/setup.php` | `0353c4ffce837ae29eb400fedf508d0441ca6dc45e5a1a76e497e5bc0b70cdce` |
| `scripts/travel-lab/environment.php` | `e2f9b633dd26b9a2d69b06eaa4c25f87f3ddc767747f6517e6948c0e3224ba24` |
| `scripts/travel-lab/guard-test.php` | `e842ed44453f2d3823151fa0bf46194941d40a1d4d5f214c26ffed9ba4c07819` |
| `deploy/travel-lab/README.md` | `517d106c752a8483545063554de2608390be419cde8ed8c324ded6a6df212455` |
| `tests/Unit/Extension/TravelAtomicThrottleTest.php` | `24b16ce672b2341dfe382f7492b7fef0b73bdb97bd1806598513cc5a7c8b51a6` |
| `docs/symphony/W04_ATOMIC_THROTTLE_TESTS.md` | `74e10f69b61c66658724b0518981add2458cc036c6350996b76994db844311ed` |

Final commands were scoped source reads/hash collection and report-only edits. Tests/build/live commands were **NOT_RUN** by this reviewer. Lead owns final checkpoint, applicable validator/test gates, activation and independent MySQL/HTTP recheck.
