# W04 cache counter atomicity — read-only native design review

**Final lead decision:** isolated APP `CACHE_STORE=database` plus a travel-only numeric `TravelThrottleRequests` subclass using **the same native DatabaseStore and DatabaseLock** to serialize native check/add/hit/repair. Release the admission lock before inner middleware/controller, retaining finally cleanup for429/errors. Lease30s/block3s, translated503 on unavailable protection, explicit normal short-admission/crash bounds. Preserve ordinary TEST array cache isolation. This reuses native RateLimiter/store/lock rather than introducing a new rate engine, cache domain service, global middleware alias or Provider setting. Actual database-runtime/concurrent HTTP verification remains NOT_RUN by this design author.

Native reviewer `/root/w03_ui_repairs`, parent Request `req_81ac33cac94046b9a2249cd14c0d00ba`. Scope: this document only. No code/runtime/APP/TEST/config/settings, service, Git or credential inspection/change was performed. Sources were inspected at parent HEAD `fd649b1cf6436fd9c4887b3b5e5c459f74b0de02`; local composer.lock records Laravel `v12.69.1`. Source-based expectations below are not a database-runtime PASS. The independent browser target1052 was still running when this review was assigned; **all runtime/cache mutations must wait for the lead's explicit end-of-verification window**.

## Existing negative evidence stays open

[W04_SECURITY_FINAL.md](W04_SECURITY_FINAL.md) is a separate nonauthor result at exact `1052e3fb4bc4cccabb51b8c538116c78655f345b`, tree `f18fa2056a031353c889785d768f87824e3083f5`. It identifies the installed FileStore directly, and records:

| Observed run | Result |
| --- | --- |
| Sequential fresh own window | Exactly600 accepted,601→429; file counter600 |
| Four lanes |611 accepted, first429 at612; counter600;11 missing increments |
| Two lanes |661 accepted when bounded extension stopped; counter561; at least100 missing increments inferred |

The two-lane torn/empty-read/reset mechanism is an inference, not an instrumented finding. Public budgets, route prefixes and actor separation are distinct from counter accuracy. The original report, probes and negative results remain immutable. This review has not rerun them. The actual independent environment was reported as MariaDB10.11.14, REPEATABLE-READ, isolated APP `req81_travel_lab`; these are inherited observations, not measurements made by this document author.

## What the native database store does

| Native operation | Source behavior and limit |
| --- | --- |
| FileStore increment | Reads `getPayload`, computes current+amount, writes with `put`. A file write may be serialized, but the complete read/compute/write is not a counter transaction. Concurrent increments can overwrite each other. |
| DatabaseStore increment/decrement | Starts `connection->transaction`; selects the prefixed key using `lockForUpdate`; reads/unserializes the current value, rejects missing/non-numeric values, updates the serialized number and returns the new number. With the same committed InnoDB row and no external resets, writers serialize on that row. The transaction call has no explicit elevated retry count here. |
| Connection used by row lock | Native Query Builder's `lock()` calls `useWritePdo()`. Reads for the locked update use the write connection. The proposed lab must keep ordinary cache reads and writes on the same local schema/server too. |
| DatabaseStore add | Checks `get(key)` first, then performs `insertOrIgnore` on MySQL/MariaDB. The primary key chooses one successful insertion; an existing numeric0 is not null and is retained. The preliminary get and insertion are not one transaction. The store's SQL Server branch differs and is not the target. |
| Expiry reads/deletion | `many()` returns only `expiration > now`; expired entries are deleted via key AND `expiration <= now`. Conditional deletion avoids blindly deleting a newly refreshed row after an earlier expired observation. `add()` calls this get path. |
| Increment expiry | The locked increment itself does not test expiration or extend TTL. It updates only value. A direct increment of an expired existing row is not equivalent to a fresh limiter hit; the native limiter also uses add/get/timer logic. |
| Storage representation | Values are PHP-serialized (for example numeric counters), not encrypted by this store. Do not repeat the vendor comment's encryption wording as a property. |
| Native cache locks | DatabaseStore `lock()` uses native DatabaseLock on the configured lock connection/table. Acquisition inserts a unique key or conditionally updates a same-owner/expired row; release checks owner and deletes only that ownership. Lock expiry/default timeout/pruning need explicit semantics if used for admission. |

These conclusions are source review only. InnoDB transactional semantics, exact connection routing, table engine, constraints and runtime resolution must be verified by the lead/independent operator in the marked lab before any runtime conclusion.

## Remaining atomicity gaps in the native limiter

DatabaseStore makes the **individual counter increment** atomic. Two separate higher-level paths still prevent an unconditional strict-limit guarantee:

1. `ThrottleRequests::handleRequest()` checks `tooManyAttempts(key,max)` and subsequently calls `hit(key,decay)`. They are separate operations. Multiple workers can observe a below-limit count before any hits finish, then all pass. A correct final database count can coexist with admission above600 near the boundary. A typical single-boundary overshoot is bounded by the in-flight workers, but no fixed bound is certified here without the real workload/window assumptions.
2. `RateLimiter::increment()` adds timer, adds counter0, increments, then contains `if (!$added && $hits == $amount) put(key,amount,decay)`. A source-feasible cold-start interleaving is: A adds0 and pauses; B add returnsfalse and atomically increments0→1, then pauses; A increments1→2; B's repair put writes1 over2. Thus database increment correctness alone does not prove the entire limiter cannot lose a count. This interleaving is **not runtime-reproduced by this author** and must not be presented as a newly measured defect.

Timer/counter creation, expiry and any repair writes need testing both at cold start and near expiry. A warm-start-only test can miss the repair-put path. Counters are charged at the middleware boundary even when a later inner limiter/controller rejects the request; correlate each budget with its own hit boundary rather than comparing every counter only with HTTP200 rows.

## Selected DatabaseStore + numeric native admission wrapper

This evaluates the lead's final design, not implemented-code or runtime PASS. Root owns middleware/routes/language keys, lab environment/setup and deployment docs. This reviewer writes only this document; no source/runtime mutation was performed.

**Scope/seam.** Extend native `ThrottleRequests`, override its protected `handleRequest`, inject native `Illuminate\Contracts\Cache\Factory` alongside native RateLimiter, resolve `store(config('cache.limiter'))` and require native **DatabaseStore specifically**, not merely LockProvider. Null limiter store uses the effective default. ArrayStore implements LockProvider but is process-local; FileStore release has the limitation below. Unsupported array/file/custom stores fail-closed503 in this selected primitive. Verify effective Factory Store and the native RateLimiter counter repository are the same database connection/table/prefix; matching config text alone does not invalidate an earlier resolved singleton.

**Key/route contract.** Derive a stable module-owned admission-lock name from the actual native `$limit->key` hash, never URL/method. Same actor/budget across routes must share the lock. Keep all eight numeric route prefixes/budgets/decays and identity intact, changing travel-route registration to inherited `TravelThrottleRequests::with(...)`. Native `with` uses `static::class`. No global alias/priority modification is needed: SortedMiddleware inspects class parents, so the subclass should inherit native ThrottleRequests priority; prove effective ordering behind optional/required authentication in the route fixture before runtime closure.

**Numeric-only scope.** Native numeric handle creates one limit object with null afterCallback/responseCallback. Reject unsupported named/multiple/afterCallback paths rather than silently claiming serialized post-controller hits. Nested aggregate/write middleware retains its native charging order: outer aggregate can charge a request that inner10-write throttle denies. Release each outer admission lock before invoking inner middleware, so no unnecessary simultaneous aggregate/write lock hold or new lock-order cycle is introduced.

**Protected boundary.** Acquire native DatabaseLock with lease30s and `block(3)`, call parent handleRequest with wrapped next, release successfully **inside wrapped next before calling original next**, and run finally cleanup only while still held. Protected: native tooManyAttempts/timer/counter add/increment/repair-put. Unprotected by design: inner middleware/controller/business transactions, response generation and parent's post-controller remaining-count header calculation. A block(callback) encompassing parent/controller would release too late; do not implement that controller-wide bottleneck. Headers may reflect later concurrent hits after release, just as native behavior does; they are not a locked-admission snapshot.

**Native DatabaseLock mechanics.** Acquisition inserts unique lock key/owner/expiration; on key collision it conditionally updates only same-owner or expired rows. Native release checks current owner, then deletes WHERE key AND owner. Unlike FileLock's owner-read/unconditional-unlink, a late release cannot delete a successor with a different owner. Increment row locks remain on g7_cache; admission leases use g7_cache_locks. Both use the same approved native MySQL connection/schema and primary-key constraints, existing core migrations only.

**Lease limit, not unbounded proof.** Native DatabaseLock release returns true after its delete without checking affected-row count, and may return true for detected concurrency exceptions. A true return alone does not establish one deletion or that the lease was still valid. Measure protected hold strictly below30s with a margin; fail before original next on detected expired/lost protection, using ownership/monotonic elapsed checks where the root implementation provides them. At lease expiration a successor can enter before a stalled process finishes its old section;30s is a normal short-admission/crash recovery bound, not an unlimited scheduler-stall guarantee. Record timing, exceptional stalls and takeover separately. Native acquisition's expired-lock pruning may delete expired rows in the same lock table, a framework lifecycle behavior; keep the selected table confined to the marked lab.

**Failures/i18n.** Native exhausted-budget429/Retry-After remain parent behavior. Lock timeout, unsupported Store, native database acquisition/ownership/release protection errors yield translated ResponseHelper module503 with no keys/owners/connection credentials or internal exceptions exposed.503 is unavailable protection/contended admission, not quota exhaustion. No failure of protection executes original next. Do not relabel controller/business errors as cache503 after the lock has been released. Finally cleanup must not mask an in-flight429/original error; preserve it and report sanitized cleanup diagnostics. Track held state to avoid double release. No fail-open fallback to array/file is allowed.

**All writers/runtime scope.** All workers/probes mutating the eight budget keys must use the new admission wrapper; native DatabaseStore alone still has the higher-level races above. Finish the fixed1052 browser/unwrapped-worker window before root runtime/cache changes. A new effective backend plus module source is a new target, even if UI asset hashes remain identical. No production/global driver settings or Provider resources change.

### FileLock alternative examined and not selected

Native FileLock acquire calls FileStore.add, which opens LockableFile c+, takes nonblocking exclusive flock, tests expiry, then truncates/writes owner+expiry before close/unlock. Under the same local path/inode and no flush/takeover, lease creation is process-exclusive by source. This reviewer ran no actual file-lock process race.

FileLock inherits CacheLock release: read/check current owner, then FileStore.forget unlinks the file. That is owner-checked but not atomic compare-and-delete. A source-feasible expiry interleaving is ownerA reads itself, ownerB acquires after expiry, then A unlinks B's lock. Open/unlink/takeover or broad flush can also undermine a simplistic same-inode proof. This limitation motivated the lead's **DatabaseLock** choice. The examined15s/FileLock-preserving proposal was superseded, not implemented or granted runtime PASS. Native FileStore counter failure evidence is preserved.

### G7 cache abstraction assessment

The module's domain/business cache services must continue using G7 CacheInterface, including its namespace/TTL/lifecycle rules. That interface has no increment, native lock or limiter methods. Constructor-injected native Cache Factory/RateLimiter/LockProvider in a framework throttle subclass is a reusable **framework admission primitive**, not a replacement business-cache engine or an excuse for Cache facade calls in module services. Keep that adapter local to the middleware, no new global binding/store alias, and document/review the deliberately narrow dependency. The new middleware route surface and change log/consumer version implications belong to the root owner. Unsupported-store503 must be tested; generic cache fail-soft policy must not make an enforcement primitive fail open.

### Additional selected-wrapper tests (not executed here)

- Real DatabaseStore/DatabaseLock multiprocess cold/warm barriers, exact counters and600/601/10/11 acceptance under2/4 HTTP workers, all eight route registrations and auth priority. Array-lock mocks cannot prove cross-process exclusion.
- Assert original next runs after the lease is released; a second process can acquire while a slow controller continues. Preserve headers/native429 and aggregate charging on inner rejection.
- Inject unsupported/array/file Store, acquire timeout, DB failure, lost/expired owner, release error and native429/controller exceptions; verify no next on protection failure, translated503, no cleanup masking or private-key leakage.
- Sequential ownership transfer: old native DatabaseLock release must not delete successor owner rows. Separately record release boolean/zero-row behavior and bounded lease/takeover stalls; do not convert normal ownership tests into unlimited-stall atomicity proof.
- Verify30s hold margin and3s wait separately; repeat independent fresh cold/warm HTTP probes at newly pinned source/runtime, without shared guest-IP exhaustion or broad counter reset. Record503 separately from429/accepted and retain all old FileStore failures.
- Default TEST array remains isolated. Strict Store requirement means a domain-only HTTP feature test using array must explicitly bypass the primitive for its limited domain scope, or opt into a wholly scoped dedicated database-counter/lock fixture for enforcement. Do not silently allowArrayStore or share APP cache to make the suite green. Real middleware/HTTP enforcement tests require native DatabaseStore.

The selected combination addresses the native Store-only and higher-level admission/repair distinctions in one thin framework middleware adapter. It still needs independent runtime verification and measured lease assumptions; source review alone does not close W04F-02.

## Selected database-store isolated-lab configuration contract

At the initially inspected source snapshot, the existing public example explicitly used `CACHE_STORE=file`, `CACHE_PREFIX=req81-travel-lab-` and `G7_ENV_PRIORITY=true`. Root owns all proposed implementation/lifecycle work. Limit changes to the marked generated environment and reproducible lab scripts/docs; do not edit production/global driver settings or Provider resources.

| Proposed setting/guard | Required interpretation |
| --- | --- |
| `CACHE_STORE=database` | Apply to marked APP environment via existing safe recovery/generation paths; preserve ordinary TEST `CACHE_STORE=array` isolation. Dedicated database enforcement fixtures must explicitly opt into their own scoped database store. Existing files already contain file, so append-if-missing alone cannot migrate them. Preserve secrets and unrelated settings; reject any unmarked files. |
| `G7_ENV_PRIORITY=true` plus explicit CACHE_STORE | `EnvPriority::MAP` maps drivers.cache_driver to CACHE_STORE. The switch and explicit marker make `filterLocked('drivers',…)` remove cache_driver before SettingsServiceProvider injection. With the switch off, a drivers setting can override the env selection on local APP. TEST also has an independent settings-override suppression, but do not rely on that to certify APP. |
| `DB_CACHE_CONNECTION=mysql`, `DB_CACHE_LOCK_CONNECTION=mysql` | Explicitly use the same already-scoped connection; alternatively absent values use defaultmysql, if that absence is guarded and effective config is proved. Forbid inherited/marked foreign cache connections. |
| `DB_CACHE_TABLE=cache`, `DB_CACHE_LOCK_TABLE=cache_locks` | These are logical table names. Native `DB_PREFIX=g7_` produces physical `g7_cache` and `g7_cache_locks`. Do not supply already-prefixed names and create `g7_g7_cache`. CacheManager falls back to logical cache_locks if unspecified. |
| `CACHE_PREFIX=req81-travel-lab-` | Keep the owned namespace; APP and TEST use different schema names. Reject inherited foreign prefixes/alternate stores at the guard boundary. Do not rewrite unrelated namespaces. |
| RateLimiter's resolved store | CacheServiceProvider resolves `cache->driver(config('cache.limiter'))`; when unset it uses the effective default. Check both `cache.default` and the actual RateLimiter repository's concrete Store, since an explicit limiter store or prior resolved singleton can differ. An env file line alone is not evidence. |
| Cached config | G7 captures explicit env markers into config/env-priority.php during config build. Changing an env file while an old config cache remains does not update that snapshot. The lab already forbids retained config/installer overrides and removes only its own generated config after lifecycle commands. Preserve that guard; do not broad-clear cache/settings. |

`travelLabEnvironment()` at the initially inspected snapshot validated lab marker, APP/TEST allowlist, local DB/user/prefix and egress, strips inherited DB_* overrides, merges marked env values, and requires G7_ENV_PRIORITY. That snapshot did **not yet validate the cache store/tables/prefix**, and arbitrary inherited CACHE_* values can survive when a key is absent from the marked file. `travelLabApplyEnvironment()` likewise removes unmatched DB_* overrides, not every unmatched cache variable. Add a focused owned cache allowlist/normalization guard rather than broad environment changes. Do not read platform config or copy unrelated APP/TEST files.

Native cache migrations already define key primary keys, cache mediumText value/integer expiration and lock owner/integer expiration. No new module schema/cache engine is required. Confirm physical tables, InnoDB and scoped grants in the allowed lab after the browser window ends. DB-store increments use `g7_cache` row locks, not `g7_cache_locks`; the latter supports native LockProvider consumers and any second-stage admission lock. Table names/key primary constraints and transactional engine are necessary; rows alone are insufficient.

For an existing initialized lab, verify tables before switching the effective store. For fresh installation, assess whether boot/config/extension-cache reads can occur before the core cache migrations. If they do, stage core native migrations before enabling the database store for regular runtime; do not introduce a permanent silent file fallback or hand-create replacement tables. Re-run the existing fresh-install/recovery path as a separate verification gate. No cache migration/seed/setup command was executed for this read-only design.

## Shared selected-wrapper implementation constraints

The selected module-scoped admission middleware uses the **existing native DatabaseLock** on the same database cache store to serialize check+timer/counter add+increment/repair for the exact actor/budget key. Release before executing the controller; holding a limiter lock through expensive business work is unnecessary and would create a new throughput bottleneck. Preserve native auth ordering, anonymous/member identity, all route prefixes,600/120/10 and existing headers/retry errors. Aggregate/question-write nested budgets require deterministic lock order and established charging semantics; a lock around only the public optional-auth helper does not protect private/workflow budgets.

A lock wrapper positioned outside the native throttle that holds the lock throughout `$next` would also encompass the controller: do not describe that as the short admission section. Implementing the short section needs an explicit module-owned admission seam using native RateLimiter/LockProvider and native response/exception behavior, without editing vendor or bypassing G7 module routing contracts. G7's extension CacheInterface exposes ordinary namespaced caching, not the atomic limiter/lock contract; retain it for business caches, avoid new direct Cache facade calls in module business services, and review any adapter dependency/version implication. This is materially more code than the native database-store mitigation.

Specify bounded lock wait/TTL, owner-checked release, exception handling and expiry recovery. Never fail open on a lock/DB error to claim an enforced budget. Lock contention/unavailability must remain distinct from a genuine exhausted-budget429. Do not introduce a new scheduler/service/cache engine, process-global account limit or Provider configuration. No lock implementation or runtime PASS exists from this document.

## Focused verification proposals (not executed here)

1. **Guard tests without APP/TEST access:** extend existing scripts/travel-lab/guard-test.php fixtures. A marked canonical database store/table/connection/prefix is allowed; file/redis/foreign connection/table/prefix, inherited override, disabled priority, retained foreign config and unmarked env are rejected. Existing egress/DB/cleanup tests must stay green. Test new and existing env generation/recovery, preserving unrelated settings/secrets.
2. **Effective provider tests:** use existing EnvPriority and SettingsServiceProvider fixtures to show explicit CACHE_STORE wins with G7_ENV_PRIORITY on and cache-driver settings cannot override it; show switch-off behavior remains native. Prove config-cached explicit markers retain the decision. These are configuration tests, not HTTP atomicity proof.
3. **DatabaseStore real parallel process test:** use a unique test-owned key in isolated TEST only, same native mysql connection/InnoDB table. Barrier two/four processes, insert-once success count1, many increment calls, exact final count and TTL retained, missing/non-numeric behavior, expiry conditional delete against a refreshed row. Exercise cold add and warm add separately; cleanup only test-owned keys. SQLite/array mocks do not prove the MariaDB row-lock behavior.
4. **Native RateLimiter cold-start/expiry barrier test:** independently exercise the add=false/hits1 repair branch described above, with a controlled process barrier if needed. Record lost/count-reset results separately from the Store-only increment test. Test timer/counter expiry naturally or on a wholly isolated fixture; do not change the shared preview clock/cache.
5. **Own-actor real HTTP recheck after runtime freeze:** first record resolved native store, table/connection/namespace and worker/window provenance. Repeat fresh cold/warm sequential600/601 and bounded2/4-lane public budgets within60seconds; retain every initial failed/non-fresh/cross-window attempt. Compare accepted/429 and final counter/timer against their native hit boundaries. Test120/121 and10/11 plus distinct actor/prefix separation. Do not exhaust shared loopback anonymous IP and block other reviewers.
6. **For the selected strict lock seam:** repeat cold-start/near600 barriers and nested10/120 behavior, verify exact admission/max counters, headers/Retry-After, lock timeout/exception owner release and no lock held in controller. Record queue wait/throughput/DB contention; correct counters are not an operational load-test PASS.
7. **Lifecycle/regression:** fresh install, env recovery, native module/template updates, correct APP/TEST separation, process restart and browser user/admin regression at the final newly pinned SHA/environment. A database cache backend change is a new runtime target even if UI assets are identical. Independent fixed1052 evidence cannot be relabeled as database-runtime verification.

## Source hash inventory and status

The files below were read, not edited. Only this report was written. Vendor hashes bind installed source reviewed here; composer.lock is the dependency version record, not a promise about every other host.

| Source | SHA-256 |
| --- | --- |
| [vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php](../../vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php) | `1df45d12a4d675d15b10353eff2f0c3457c6eab79e4ffe50fb85b6dffe2e6b34` |
| [vendor/laravel/framework/src/Illuminate/Cache/DatabaseLock.php](../../vendor/laravel/framework/src/Illuminate/Cache/DatabaseLock.php) | `05257b67976a203d0b45d8bf871671c8d4166f5a3e599326ce1f4ac0c9991ff8` |
| [vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php](../../vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php) | `18ed0bb8e3c89c6df6da74e34b9e8d1a5d8a197ee4cf39cdecae1d592fd3390a` |
| [vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php](../../vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php) | `73124b8bdfee5ed080630d466d970a90a867abb368e8cbf0d948875398b5048b` |
| [vendor/laravel/framework/src/Illuminate/Cache/FileStore.php](../../vendor/laravel/framework/src/Illuminate/Cache/FileStore.php) | `d8bece77ea6f9f38d9c71723c1895030d34301be0f142c858b7c06261c4d1b3d` |
| [config/cache.php](../../config/cache.php) | `b5d633029fa736bcec98516b984624a78ea4a03a83931a62f044f45972bba1da` |
| [app/Support/EnvPriority.php](../../app/Support/EnvPriority.php) | `e7086a8cd3d4778efa1d0b731eb406d01f9c2265234cfb9287d6dcf0a23ddd45` |
| [config/env-priority.php](../../config/env-priority.php) | `cdd8722c7be9d7713742079277d31d814d2c770aa99e4c38ca759c12e6851e55` |
| [app/Providers/SettingsServiceProvider.php](../../app/Providers/SettingsServiceProvider.php) | `b75e5299a1716204123d96d92d228075a4145e9c92fe44c0fe1a726a77cd54a4` |
| [scripts/travel-lab/setup.php](../../scripts/travel-lab/setup.php) | `67e2966335c4cc8c896bed22baaf33bedcf60712ef2a1e26e9debb3be4e9a3e2` |
| [scripts/travel-lab/environment.php](../../scripts/travel-lab/environment.php) | `8819e89a2abd936ba8fc7344dfc2fa424c2558ffb2bb6586849cf65d2662c11c` |
| [.env.travel-lab.example](../../.env.travel-lab.example) | `d9e52ed600cf3eb6ca036ae63015ffaa4b7a37c03afa17e2718b4a97033c1df8` |

| [vendor/laravel/framework/src/Illuminate/Cache/FileLock.php](../../vendor/laravel/framework/src/Illuminate/Cache/FileLock.php) | `c591e8d971787a87aa1941190483be0fec167ca47dd08349df6d5480ca58d8ce` |
| [vendor/laravel/framework/src/Illuminate/Cache/CacheLock.php](../../vendor/laravel/framework/src/Illuminate/Cache/CacheLock.php) | `f76c3d68a4c31708512defcc4fa09d7366cf5a6dd684ca130970825031045242` |
| [vendor/laravel/framework/src/Illuminate/Cache/Lock.php](../../vendor/laravel/framework/src/Illuminate/Cache/Lock.php) | `4c37ba03123b37d1f69284d88cac9e820a814be94f4d0cd43c0dd26382df172a` |
| [vendor/laravel/framework/src/Illuminate/Filesystem/LockableFile.php](../../vendor/laravel/framework/src/Illuminate/Filesystem/LockableFile.php) | `2879edfbb097570c05d306a69a1f2054344b18212159e69caba3870e5fa424d4` |
| [vendor/laravel/framework/src/Illuminate/Routing/SortedMiddleware.php](../../vendor/laravel/framework/src/Illuminate/Routing/SortedMiddleware.php) | `bc0beaae21b1293f37c295c8805897cad3e873d423ec64ac8246832f2775c793` |
| [vendor/laravel/framework/src/Illuminate/Cache/ArrayStore.php](../../vendor/laravel/framework/src/Illuminate/Cache/ArrayStore.php) | `26f30b3dbc1500d9ca904a083c2c94c45eeb036c0d2127a43aaf1e29a4c46a8e` |
| [app/Contracts/Extension/CacheInterface.php](../../app/Contracts/Extension/CacheInterface.php) | `71ffd9683d31cadbae93f46849c64dfad339c4a0449737daf366b3b04374dd69` |

Read-only source/design review: DONE. Database-counter and DatabaseLock-wrapper atomicity runtime: **NOT_RUN**. Complete native limiter atomic admission/repair: **NOT_PROVEN**. W04F-02 database-runtime closure: **OPEN pending actual independent checks**. Runtime cache/lifecycle changes: **DEFERRED until existing browser target finishes**. Git integration/CI/canonical Validation are lead-owned and not claimed by this native helper.

Design history: database-counter-only and FileStore-preserving wrapper alternatives were reviewed, then superseded by the lead's final native DatabaseStore + numeric subclass + DatabaseLock choice. The author changed only this design document; code/runtime/cache environment and1052 evidence were not changed by this review.
