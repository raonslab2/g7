# W04 support throttle isolation repair

The support route's three numeric throttles previously shared the same authenticated-user key. Public and own-question GETs could exhaust the ten-per-minute creation budget. A fresh actor could submit only five requests because the inherited question limiter and the creation limiter incremented that same key twice per accepted POST. The lead changed only the three prefixes in `modules/_bundled/raonslab-travel_lab/src/routes/support.php`; this native agent owns the focused test and this report.

## Source-bound repair

| Registered policy | Budget retained | Prefix added by lead |
| --- | --- | --- |
| Public notices/FAQ | 600/minute | `travel-lab-support-public:` |
| Authenticated question routes | 120/minute | `travel-lab-support-questions:` |
| Question creation, additional nested limiter | 10/minute | `travel-lab-support-create:` |

Route names, verbs, `api`, `optional.sanctum` for public reads, and `auth:sanctum` for questions remain unchanged. Question creation still consumes the aggregate 120 question-route budget as well as its separate ten-creation budget. A fully exhausted aggregate question budget therefore still blocks creation; the repair does not promise unlimited creation after question-route exhaustion.

Native Laravel `ThrottleRequests` combines its numeric middleware prefix with the authenticated identifier, rather than the route URI. Each middleware hits its limit before invoking the next middleware. Distinct prefixes prevent the old shared-counter and double-increment behavior without increasing the budgets.

Final tested source SHA-256:

| File | SHA-256 |
| --- | --- |
| `tests/Unit/Extension/TravelSupportThrottleIsolationTest.php` | `fad5c1e094cc8f0f659126bf960f629cfa18400bdef016b98798f6077a90c593` |
| `modules/_bundled/raonslab-travel_lab/src/routes/support.php` | `d120806e34d46327a94e34de41df02692e0627845a199507eacf8883d83d093f` |
| `vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php` | `73124b8bdfee5ed080630d466d970a90a867abb368e8cbf0d948875398b5048b` |
| `vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php` | `18ed0bb8e3c89c6df6da74e34b9e8d1a5d8a197ee4cf39cdecae1d592fd3390a` |

## Fail-first and repaired execution

Same command before and after the lead's route-prefix change:

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php
```

Environment: PHP 8.3.6, PHPUnit 11.5.56, local installed Laravel framework. Before: exit **1**, **5 tests / 45 assertions / 3 failures**, 0.266 seconds, 20 MiB. After: exit **0**, **5 tests / 53 assertions**, 0.145 seconds, 18 MiB. The different assertion counts reflect baseline assertions stopping at the first defect in three cases.

| Case | Before prefixes | After prefixes |
| --- | --- | --- |
| 40 public reads + 70 own-question reads, then ten creations; eleventh creation rejected | FAIL: all ten creations returned 429 | PASS: first ten 200, eleventh 429 with limit 10 |
| Ten creations per actor; eleventh rejected; creation exhaustion permits ordinary question read | FAIL: sixth creation already 429 | PASS, two separate authenticated actor identifiers |
| 120 own-question reads; 121st rejected; other actor and public read remain usable | PASS | PASS: 429 limit 120 |
| 600 public reads; 601st rejected; own-question read and other actor remain usable | FAIL: first own-question read returned 429 | PASS: public 429 limit 600, separate reads 200 |
| Actual registered route auth, methods and numeric budgets | PASS | PASS |

Pint on the test and route file: **PASS**. PHP syntax check on the test: **PASS**. No broad product-suite rerun is included in these focused counts.

## Meaningful isolation and limits

The test extends PHPUnit's framework test case. It registers the actual support source with a real Laravel Router/RouteCollection and uses each route's `gatherMiddleware()` output. Numeric limits and prefixes passed to the real `ThrottleRequests` pipeline come from that source; the test does not duplicate the repaired prefix strings to manufacture isolation. The actual `RateLimiter`, cache `Repository`, and fresh per-test `ArrayStore` execute the counter behavior. A simple terminal response identifies arrival after the middleware; it never invokes a controller.

A temporary plain Laravel Container provides the Router and native throttle instance. Container and facade application references are restored after each test. No application boot, dotenv read, auth provider, DB connection, production cache, network call, service restart, or APP/TEST schema mutation occurs. Authenticated actors are explicit `GenericUser` identifiers injected into each request, so this tests counter isolation and registered auth metadata, not actual authentication or permission enforcement.

Parallel-worker cache atomicity, unauthenticated IP signatures, live browser behavior, support persistence, full product regression and formal Validation are **NOT_RUN** by this test. Existing support authorization evidence remains separate. The lead must bind these source hashes to the published checkpoint and validate the deployed candidate's browser retry flow; this local test result is not whole-product or canonical Validation PASS. No staging, commit, push or deployment was performed by this native agent.
