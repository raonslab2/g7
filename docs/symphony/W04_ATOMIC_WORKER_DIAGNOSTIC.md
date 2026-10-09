# W04C-O2 worker diagnostic — original failure remains UNKNOWN

The requested **single** atomic/auth/counter re-execution passed **21 tests / 2,209 assertions** with full ignored stdout/stderr captured. It did not reproduce the original intermittent worker exit. A separate deterministic, caller-owned temporary SQLite lock-contention probe proved a narrower worker-harness defect: the worker could not construct a real native busy503 because translator/response dependencies were absent. That defect is repaired and two affected cases passed **2 / 45**. **The original W04C-O2 cause remains UNKNOWN, not universally closed or attributed to host load.**

## Source and prior negative evidence

- Work `work-20261009-g7-symphony-max-child-c7ae42d1`; parent Request `req_81ac33cac94046b9a2249cd14c0d00ba`; native harness author `/root/w03_commerce_guards`.
- Read-only intake: `docs/symphony/W04_ATOMIC_CONTENTION_FINAL.md`, SHA256 `8b93c63dfbddbf677fca691131527af33bbe9de10c1511d9c84115e77058553f`, and `docs/symphony/w04-atomic-contention/evidence/unit-atomic.txt`, SHA256 `a8fec2c953e3121742ca87524cd827fd6dcab2e6eb149cc502d79387b9b3e6d0`.
- The independent report preserves its run1 **21 / 2201 / one failure** at worker exit, followed by four recorded **21 / 2209 PASS** runs. Run1 stderr, exact timing and load were not retained; an additional single-file pass was seen but not saved. These old counts and uncertainty are unchanged. No 5/5 PASS or load-only explanation is claimed.
- Source inspection confirms the original parent test already read each worker's **entire** stdout/stderr and supplied stderr to `assertSame(0, exit, stderr)`. The independent collector retained only output tails. The parent assertion did not intentionally discard stderr. Capturing the whole PHPUnit output is necessary to retain that message.

## One requested unchanged-suite run

Command, executed once before harness edits:

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelAtomicThrottleTest.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php
```

**PASS 21 / 2209**, exit0, 11.255 s, 12.00 MB. Full stdout/stderr were redirected under `umask 077` to a previously nonexistent ignored log and then inspected. There was no repeat loop. Entrance observation at 2026-10-09 18:24 UTC: `nproc`4, load averages3.02/3.19/5.69, memory available10,867 MiB; swap used5,184 MiB. These are one host observation, not a controlled performance baseline or a causal explanation for the earlier unknown failure.

## Deterministic separate harness failure

The old worker booted an unbooted Application with cache-only config and no native translator, ResponseFactory or application locale. Successful200/native quota429 paths did not require these bindings. Real lock timeout/lost-lease503 invokes module translation and ResponseHelper, so that failure path could exit before returning diagnostic JSON.

A one-off diagnostic used an independently-owned random0700 temporary directory,0600 SQLite file, native cache/cache_locks schema, real DatabaseStore/DatabaseLock and the native request-signature method. The diagnostic held precisely the worker's own actor/prefix lease and launched the actual old worker with its existing arguments/start barrier. After **3.063 s**, worker exit was **255**, stdout0 bytes, stderr5,070 bytes; the concrete exception was `BindingResolutionException: Target class [translator] does not exist`. The holder remained owned. Full stderr is in the ignored private log; no operational environment or real DB was used. The native timeout—not a fabricated middleware response—reached this missing dependency.

This reproduces a **specific source harness defect**, not the original independent run1. Its absent stderr leaves alternatives such as contention/timeouts, SQLite backend errors or process/resource failures unresolved. The observed assertion-count difference and source risk are not used to infer the missing original stderr.

## Bounded harness repair

Only `tests/Fixtures/TravelAtomicThrottleWorker.php` and `tests/Unit/Extension/TravelAtomicThrottleTest.php` changed:

- Worker now binds actual FileLoader/Translator with module language files, an application locale and native ResponseFactory/ViewFactory/Redirector dependencies. It still does not boot Laravel, load dotenv or contact installed services.
- Native HttpResponseException busy responses are recorded as status/message/Retry-After in JSON and stop that failed fixture lane immediately. There is no timeout retry loop, force-release or accepted-response substitution. Other Throwable failures still exit with their complete stderr.
- The parent explicitly requires every normal worker's `busy` field to be null. Thus503 still **fails** the ordinary concurrency test. Existing60-request/20-accepted/40-limited/counter20/zero-lock/four-distinct-PID/real-overlap assertions remain intact. No assertion was removed, quota raised or middleware bypassed.
- A new deterministic subprocess case holds the native lease on its private SQLite file, uses the same real clock as the worker, and verifies exit0, stderr empty, one recorded503, actual translated message/Retry-After1, zero limiter hits and the holder's ownership preserved. It is a diagnostic-response regression, not a successful admission case.

## Changed-scope verification only

After repair, only the two affected cases were executed; the broad21-case scope was not repeated:

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php --filter='test_four_actual_php_processes_share_one_temporary_sqlite_budget_without_counter_loss|test_worker_records_real_lock_timeout_without_losing_native_busy_response_dependencies' tests/Unit/Extension/TravelAtomicThrottleTest.php
```

**PASS 2 tests / 45 assertions**, exit0, 4.368 s, 12.00 MB. This includes normal four-process admission and the new deterministic timeout diagnostic. Scoped `pint --test` and `git diff --check` passed. All own temporary processes/files were cleaned; no MySQL APP/TEST schema or shared service was touched.

## Evidence and source pins

All three full logs are ignored and mode0600, not Git publication payloads. Public evidence here records counts, status and hashes, not raw environment/SQL dumps or sensitive data. The lead owns retained private-artifact handoff and Git publication of this report/source.

| Artifact | Bytes / SHA256 |
| --- | --- |
| `storage/framework/testing/w04-atomic-worker-diagnostic-full-20261009T182400Z.log` | 231 / `fa15b8bd32cf4fee600428d5ef9be179905daff90510c00542dbaf2bc89819f2` |
| `storage/framework/testing/w04-atomic-worker-timeout-control-20261009T182400Z.log` | 5361 / `46c294a0df1d4617ca6618e871f457a3e5ab9273c855e2433eda5e810629da78` |
| Old worker stderr contained in that control log | 5070 / `e11313a75b6a3b79d549b384aad7ebf33b2046dd8c82c5958d229d7091f65d34` |
| `storage/framework/testing/w04-atomic-worker-repaired-targeted-20261009T182400Z.log` | 228 / `a6324f875b73259a272b01785125376af00e5062617ea68a72ff0f8e5d379056` |

| Source | SHA256 |
| --- | --- |
| `tests/Fixtures/TravelAtomicThrottleWorker.php` original / one unchanged-suite run | `9c91889f8dc50d5014b7a82c6abe49f4b1ca03bfc3a428d30df41a5c537cd694` |
| Worker repaired | `0fd24527c814ed9f9f6e00194b9e22787ed5c0cf15ec0b5221878c47c7baca77` |
| `tests/Unit/Extension/TravelAtomicThrottleTest.php` original / one unchanged-suite run | `24b16ce672b2341dfe382f7492b7fef0b73bdb97bd1806598513cc5a7c8b51a6` |
| Atomic test repaired | `9783e67d35c69041d542cfb92f53c37480093b121d0f778eb95b45beeeebf4b6` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Middleware/TravelThrottleRequests.php` unchanged | `aab02c6b01254dd62b7973ee5b0fbc68bb0afd052d49171ac19f3bd21e97e92d` |

**NOT_RUN:** full expanded suite at the repaired harness, stress/repeat-loop stability, reproduction of the original unknown failure, new MySQL/HTTP admission or production capacity, formal Validation/CI. Previous independent live counter results remain their own fixed-source evidence. Native per-actor polling/performance observation W04C-O1 is not changed by this diagnostic. No production/env/Git/service edits, Provider/account changes or real database actions occurred.
