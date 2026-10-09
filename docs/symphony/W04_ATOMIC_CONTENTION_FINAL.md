# W04 atomic contention — fixed fa552317 (independent NONAUTHOR live verification)

## Decision

**W04F-01 (idempotency gap deadlock) and W04F-02 (lost throttle increments) are CLOSED at the fixed installed source, within the bounds below.** No new P0/P1/P2 finding. No product change by this reviewer.

Two new P3 observations:

- **W04C-O1:** per-actor admission is serialized, and lock waiters sleep inside the 4 PHP workers.
- **W04C-O2:** the author's atomic unit fixture failed once in five local runs under host load.

Neither changes the W04F verdicts.

The status words are used as follows:

- **PASS** means measured on the actual installed HTTP and SQL stack, at the stated bounds.
- **NOT_RUN** means the condition could not be produced honestly. Every NOT_RUN and FAIL record below is kept unedited.

This is **not** canonical Validation, **not** hosted CI (0 runs, NOT_RUN, not waived) and **not** a release PASS. W03-05(a) remains **CONTAINED, not CLOSED**.

| Item | Value |
| --- | --- |
| Request / parent | `req_ea58da0bbb7043999b64641fcfc32490` / `req_81ac33cac94046b9a2249cd14c0d00ba` |
| Review target | Exact `fa5523175ac494cfbd13bbf89bf06b3ec91835a6`, tree `fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c`, fetched from `origin/feat/g7-travel-lab-c7ae42d1`. Local branch `verify/w04-atomic-contention-nonauthor`. The default `6853f40d` was not reviewed. |
| Source before → after | Product source unchanged. Only this report and `docs/symphony/w04-atomic-contention/**` were added. |
| Live runtime | `http://127.0.0.1:18871`, isolated APP schema `req81_travel_lab`, MariaDB 10.11.14, REPEATABLE-READ. The PHP built-in server has 1 master and 4 workers (seen in an in-session `ps`). The window was 17:13–17:55 UTC on 2026-10-09; KST was already 2026-10-10. Every guarded statement asserted `SELECT DATABASE()` and `CURRENT_USER()` = `req81_travel`. TEST was never accessed. Nothing in the parent APP, its services, config, settings or auth was changed or restarted. |
| Host load | From `uptime` during the session: load average was **22.5 on 4 cores** at 17:24 (another Request's fresh install was running) and about 10–12 at 17:47–17:49. The in-session values were not saved; [installed-doc-drift-and-host.txt](w04-atomic-contention/evidence/installed-doc-drift-and-host.txt) holds a later capture. This load caused both NOT_RUNs below. |
| Credentials | The private 0600 `access.json` and `database-access.json` were read only inside the guarded processes. No credential value was printed, put in argv or written to evidence; a secret scan of the package found only the DB account name, which is part of the schema name. No parent `.env`, `.env.testing`, config cache, platform config or `/proc` environment was read. |
| Toolchain | PHP 8.3.6 and PHPUnit 11.5.56. Own `composer install --no-scripts` took 69 s (timing log in installed-doc-drift-and-host.txt). `composer.lock` is identical to the parent's. All **15 compared vendor files** are byte-equal to the parent installation; the rest of `vendor/` was not compared. |
| Internal help | One read-only native subagent did the final package review (see the last section). No official child Request was created, and nothing was pushed, merged or deployed. |

## 1. Fixed source ↔ installed runtime binding

Evidence: [source-provenance.json](w04-atomic-contention/evidence/source-provenance.json), [route-order-installed.json](w04-atomic-contention/evidence/route-order-installed.json), [hooks-parity.json](w04-atomic-contention/evidence/hooks-parity.json).

| Check | Result |
| --- | --- |
| Parent HEAD | `fa552317`, with 0 tracked modifications. |
| Module (168 files) | This SHA's `_bundled` equals the parent `_bundled` (168/168). The installed `modules/raonslab-travel_lab` matches 165/168 files, including byte-equal `TravelThrottleRequests.php`, `InquiryService.php`, `routes/support.php`, `routes/workflow.php` and `module.json` 0.1.2. The 3 differing files are docs only (`AGENTS.md`, `README.md`, `docs/settings.md`): the installed copies still say 16 tests and `>=0.1.1`. The exact diff lines and both `module.json` versions are in [installed-doc-drift-and-host.txt](w04-atomic-contention/evidence/installed-doc-drift-and-host.txt). This is an observation, not a code drift. |
| Template | 153/154 files match; installed `README.md` text differs. |
| Core / framework (21 files) | All equal: `bootstrap/app.php`, `OptionalSanctumMiddleware`, `ExtensionMiddlewareGate`, `RefreshTokenExpiration`, `ThrottleRequests`, `RateLimiter`, `DatabaseStore`, `DatabaseLock`, `Lock`, `CacheManager`, `Repository`, `ManagesTransactions`, Kernel, `SortedMiddleware`, `Authenticate`, the Sanctum Guard/token, `config/cache.php` and `composer.lock`. |
| Route cache | 31 travel routes. **0** legacy `throttle:` strings. 26 `TravelThrottleRequests` occurrences carry exactly the 8 unchanged numeric prefixes: `600 public`, `120 questions`, `10 create`, `120 workflow`, `60 cart`, `10 submit`, `20 cancel` and `60 admin`. `TravelOptionalSanctum` appears on exactly the 4 public routes. |
| Native order (19 throttled routes) | The installed route-cache text was fed through this SHA's `bootstrap/app.php` → native HTTP Kernel → `Router::gatherRouteMiddleware()`. This is a reconstruction from the actual cache and native stack: no closures were registered and no app was booted. In 19/19 routes, auth sorts before every throttle: public `TravelOptionalSanctum → TravelThrottleRequests:600`, and private `Authenticate:sanctum → group throttle → route throttle`. Both sort before `SubstituteBindings`. Observation (pre-existing): on admin inquiry routes, `PermissionMiddleware` sorts after the throttles, so a non-admin's 403 consumes that non-admin's own bucket. |
| Hooks | The installed travel subset equals the source `getSubscribedHooks()`: 11/11. |
| Effective store (live) | The **native `DatabaseStore` is effective**. Own keys `<17-char cache prefix>travel-lab-*:sha1(user id)` hold exact integer counters plus `:timer` rows in `g7_cache`. Admission rows appear in `g7_cache_locks` only while held. Only own actors' exact keys were read; nothing was deleted, seeded or cleared. |

## 2. Native quota admission (W04F-02)

All runs used the same real loopback IP, no forwarded headers, and no reset, seed, clock, quota or config change. Raw records: [evidence/raw/t1-*](w04-atomic-contention/evidence/raw/).

| Run (own actor, fresh own window verified by DB row absent/expired + first header 599) | Result |
| --- | --- |
| Same-IP separation | **PASS**. Member made 40 reads (599 → 560, strictly by 1). Other_member's and admin's first reads each returned **599**. The guest IP bucket moved by exactly 1 across them. DB counters were 40/1/1 with 0 lock rows. |
| Sequential 600 × 200 then 601 → 429 (member), attempt 1 | **NOT_RUN** (17:20). p50 latency was 166 ms at load 22, so it took 123 s. The window naturally rolled over, and the 601st request returned 200 in a *new* window. Not claimed, no forcing. |
| Sequential, attempt 2 (fresh condition: load about 10) | **PASS**. 600 × 200, with remaining strictly 599 → 0, in **53.7 s**. Request 601 returned **429** with limit 600, remaining 0, Retry-After 6. DB counter = **600**, 0 lock rows. |
| Concurrent 4-lane × 700 (other_member), attempt 1 | **NOT_RUN** (17:23). It took 82 s; 700 admitted across two natural windows, and the second window's counter was 221. The 600 budget was never reached inside one window. |
| **Concurrent 8-lane × 900** (other_member, fresh) | **PASS**. **600 × 200 + 300 × 429** in 48.4 s; first 429 at 30.4 s. 0 busy, 0 malformed 429s. 0 requests admitted after being sent after a completed 429. DB counter = **600**, 0 lock rows. Deadlock counter 6 → 6. |
| **Concurrent 4-lane × 800** (admin, fresh) | **PASS**. **600 × 200 + 200 × 429** in 44.7 s. DB counter = **600**, 0 lock rows. |
| Question aggregate burst, 8 lanes × 200 (admin) | **PASS** on rerun: exactly **120 × 200 + 80 × 429**, DB counter 120. The first run measured the same 120/80/120 but recorded FAIL on two unsound probe criteria, and that record is kept. The criteria were: `admitted_after_first_429` = 2, which compared client *send* order; and `remaining_headers_unique_contiguous` = false (headers are computed after the controller, so duplicates are expected). Both were replaced in `t2_questions.py` and `t1_throttle.py` before the reruns. |
| Create 10/11 + aggregate 120/121 (member, sequential) | **PASS**. Ten 201s, then the 11th returned 429 (limit 10). The window held 108 reads; request 121 returned 429 (limit 120), and a POST after exhaustion also returned 429 (limit 120). SQL shows exactly 10 secret posts. Elapsed 41.4 s. Other_member's question read returned 119 and the member's public read 599, so prefixes and actors are separate. |
| 20 concurrent creates, 8 lanes (other_member) | **PASS** on rerun: **10 × 201 + 10 × 429**, 10 rows, create counter 10, aggregate counter 21. The first attempt is a kept FAIL: my sequencing had consumed one read, so the window was not fresh. |
| Owner lock released before controller | **PASS (direct, DB rows)**. In R1–R4 the HTTP requests were held inside the controller on a DB barrier. During that hold, `g7_cache_locks` had **0** admission rows for every in-flight actor/prefix. The same-actor `GET /cart` probe (**200 in 333 ms** during the hold) was run in **R1 only**. |
| Lock wait bound (block 3) | **PASS (live positive control)**. A FOR UPDATE was held on the *own* counter row for 6.1 s. Holder A blocked inside the native increment while holding the admission row, then returned 200. Same-actor B and C returned **503 Retry-After 1 at 3.06–3.10 s** without counting (counter +1 only). 0 rows afterwards. |
| Headroom bound (25 s of the 30 s lease) | **PASS (live)**. Native increment blocked for 27.5 s → **503 Retry-After 1** after ≥25 s, and the lock was released. The probe did not directly check whether the controller ran: the busy envelope comes from the middleware, and source shows `$next` is not called on this path. The native hit had already been recorded, so one unit was consumed; this is author-documented and bounded. |
| Guest / invalid / expired / logout | **PASS**. Invalid token → native 401. An own login token expired via the guarded own-token update counted in the guest IP bucket (599 → 598 → 597 strictly between two guest reads) and returned 401 on private routes. After deletion → 401. |

These bounds are proven live on this runtime only: 30 s lease, `block(3)` and 25 s headroom. They are **not** an infinite-stall proof. A process killed between acquire and release is covered only by lease expiry; that is source and author-test evidence, not a live run here.

## 3. W04F-01 idempotency gap repair (live HTTP + SQL)

The overlap barrier is [db_guard.php](w04-atomic-contention/db_guard.php) `barrier`: `FOR UPDATE` on one own row from a separate APP connection.

**Lock graph visibility:**

- `performance_schema.data_lock_waits` → **1142**.
- `INNODB_TRX`, `INNODB_LOCK_WAITS`, `INNODB_METRICS` and `ENGINE INNODB STATUS` → **1227**.
- Both codes are recorded in [db-visibility-schema.json](w04-atomic-contention/evidence/db-visibility-schema.json), together with the UNIQUE index and collation.
- **The engine lock graph was NOT_RUN** (no privilege grants were requested).

**Overlap criterion:**

- **Limit:** because the lock-wait views are invisible, `db_guard.php` counts a connection as "waiting" when it is executing a statement containing `for update`. That is not engine proof of blocking on the barrier row. In R2 the second connection waits on the user row, not the barrier table. The overlap claim therefore rests mainly on both requests still being pending until release.

- Two distinct app connections were in a locking read at both the first and the last PROCESSLIST sample of a hold of at least 3 s.
- Both HTTP responses were still pending at release, having been sent at least 3 s earlier, and completed after release.
- `Innodb_deadlocks` is server-wide. It was sampled before the lock, at lock, at the end of hold, at release, after the responses, and 3 s later.

| Race (own fixtures, product 85) | Sustained waiters | Result |
| --- | --- | --- |
| R1 last seat, two members (dep 122, cap 1) | 2 conns, 3.44 s, sent 4.9 s before release | 201 / 409 (native capacity message). reserved 1 = cap 1. Deadlocks **6 → 6**. Admission locks 0 during hold; same-actor read 200. |
| R2 same member, same key (rerun) | 2 conns, 3.41 s | 201 + 200, **same ID**. Exactly **1** row by (user, key) with items 1, events 1, qty 1. reserved r0+1 = 2. Replay returned 200 and changed body 409 (`idempotency_conflict`); reserved stayed 2 after both. Deadlocks 6 → 6. |
| R3 cancel vs admin decline (rerun) | 2 conns, 3.38 s | Cancel 200, decline 409. reserved 1 → 0 → 0. Repeat cancel 200, never negative. Final CANCELLED, **events 2**. Deadlocks 6 → 6. |
| R4 submit qty 2 vs admin capacity 2 → 1 (rerun) | 2 conns, 3.41 s | Submit 201, PUT **409** (native capacity rule). reserved 2 = cap 2. Deadlocks 6 → 6. |
| First R2–R4 attempt | — | **FAIL, kept.** My back-to-back matrix and race submits exhausted the member's own **10/min submit** budget, which is correct 429 behaviour. Each FAIL showed it differently. **R2**: the replay and changed-body calls returned 429; the core race still returned 201/200 with the same ID. **R3**: "setup submit 429". **R4**: the step asserted "no sustained two-connection overlap", because its submit returned 429 immediately (private race-state record) while the PUT waited and returned 200, changing capacity to 1. The capacity was restored to 2 natively before the rerun. |

**Repeated same-gap series** ([t3-gaps](w04-atomic-contention/evidence/raw/t3-gaps-173111.json)) — the original R1/R1c deadlock layout, on fresh capacity-1 departures 127–135 with two different members:

- Before each round, a non-locking SQL read placed both absent keys in the UNIQUE(`user_id`, `idempotency_key`) index. The layout is reported as neighbour owner class plus a gap hash; no foreign keys are output.
- **6 same-gap rounds**: SQL confirmed one shared gap ID for both actors (adjacent users 51/52; the supremum gap in rounds 1–2 and the interior gap in rounds 4–8).
- **3 different-gap controls** were interleaved.
- **Every round:** 2 waiting connections for 3.20–3.35 s, both pending at release, then 201/409 with reserved 1 = cap 1.
- **Server-wide `Innodb_deadlocks` = 6 at every sample**: idle control A (45 s window, samples spanning 41 s, no own traffic), all 54 in-round samples, idle control B (samples spanning 41.2 s). It was 6 at the start of this review and 6 at the end.

The original 1052 run measured 4 → 5 (R1) and 5 → 6 (R1c, same gap) under the old locking lookup; see [W04_SECURITY_FINAL.md](W04_SECURITY_FINAL.md) W04F-01. Its R1c placement ("member's sorts after its own keys, other's before its own") is the layout reproduced here.

**Inferred lock order** (source plus PROCESSLIST, not engine-confirmed): User FOR UPDATE → **non-locking** `byKey` → Cart FOR UPDATE → Departure FOR UPDATE (waits here on the barrier) → Product/Option/Policy → INSERT. With no gap lock held by the loser, the winner's insert intention is not expected to wait, so the R1c cycle is not expected to form. This is source reasoning, consistent with 0 observed deadlocks; it is not a proof.

**Limits:**

- This is measured absence on this server across 13 sustained contention races (R1–R4 and 9 gap rounds) plus the first-run R2 core race. It is not a proof that deadlocks are impossible, and it is not engine-level attribution.
- Concurrent server activity by others could have added deadlocks, but none were counted.
- The source-only limitation remains: an outer caller that already holds a RR snapshot before `submit` would read a stale snapshot. The current HTTP path has no such wrapping caller (`InquiryController::store` → `submit` with its own `DB::transaction(…, 3)`). Unique constraint plus 3 retries remain as backstops.

## 4. Server-contract / side-effect matrix (14 required axes)

Raw: [t3-matrix](w04-atomic-contention/evidence/raw/t3-matrix-172821.json), [t3-effects](w04-atomic-contention/evidence/raw/t3-effects-175025.json).

**Own fixtures, all created through native admin APIs:**

- **Own** pickup/free KR shipping policy 8 (created this time, 201; the earlier W04 review had to reuse policy 1);
- category 28;
- product 85 with 17 options;
- catalog registration;
- departures 119–135.

No old seed, other fixture or default policy was touched.

| # | Contract | Result |
| --- | --- | --- |
| 1 | Native server price | **PASS**. Cart = public catalog = 12000 = SQL product price + adjustment. |
| 2 | Option repricing (current) | **PASS**. Native PUT adjustment 700 → cart 12700, option IDs preserved. |
| 3/8 | Qty × unit; reservation | **PASS**. qty 2 → line and total 25400, SQL item 12700 × 2; reserved +2 exactly. |
| 3b | Frozen snapshot | **PASS**. After the adjustment moved to 900, a new cart priced 12900 while the submitted inquiry item stayed 12700 and the total 25400 (SQL and owner API). |
| 4 | Qty tamper on add | **PASS**. 0, −1, 1.5, "abc" and 100000 → 422; over capacity → 409; unknown departure → 4xx; nothing written. |
| 4b | Cart PATCH tamper / foreign | **PASS**. qty/money/owner fields → 422, over capacity → 409, foreign PATCH/DELETE → 404, nothing changed or reserved. |
| 5 | Departure date (live KST boundary) | **PASS**. KST-today (2026-10-10), past, and return-before-departure → 422; `reserved`/`unit_price` fields → 422; member → 403. |
| 6 | Inquiry foreign cart / money / owner | **PASS**. Foreign cart → 4xx, `total_amount` → 422, `user_id` → 422; nothing reserved. |
| 7/12 | Idempotency, ownership, TEST states | **PASS**. Replay 200 with the same ID; changed body 409; foreign 404/404; member on admin routes 403/403; TEST_INQUIRY → UNDER_REVIEW → TEST_ACCEPTED 200/200; invalid back-transition 409; admin money tamper 422. |
| 9/10 | Capacity / release once / stock | **PASS**. Full capacity → other member's add 409. Cancel 3 → 0; repeat cancel 200 and still 0. Native option `stock_quantity` was unchanged across submit and cancel. |
| 11 | Races | See §3. |
| 13 | Orders / payments / temp orders | **PASS**: global and own SQL **0 → 0** (orders, order_payments, order_options, temp_orders). No checkout/order/payment call was made. |
| 14 | Dispatch blocked | **PASS, bounded**. jobs 0 → 0, failed_jobs 0 → 0, mail_send_logs 0 → 0, notification_logs 4 → 4, notifications 2/own 0 → 2/own 0. Effective runtime env values were NOT_RUN (parent `.env` not read). A database `queue:work` process of unknown ownership was seen on the host in an in-session `ps` (not saved). No real external dispatch was made. |

## 5. Private questions, audit, auth

Raw: [t2-*](w04-atomic-contention/evidence/raw/).

| Check | Result |
| --- | --- |
| Owner native edit | **PASS**. Exact title and body persisted; `is_mine`/`is_secret` true. |
| Foreign / anonymous | **PASS**. Foreign show/patch → 404/404; foreign list had 0 own items; anonymous → 401/401. |
| Admin answer | **PASS**. Admin show 200; native board admin comment 201; the owner sees `answers_count` 1. |
| Audit | Exactly one `post.update` with title metadata and the body excluded (API and SQL) passed. The step itself recorded **FAIL** on its last assertion ("foreign rows = 0"): that assertion counted all other_member activity since an early floor, which included other_member's own 10 create-race posts. **Re-inspection PASS** ([t2-audit-inspect](w04-atomic-contention/evidence/raw/t2-audit-inspect.json)): other_member's rows are 10 × `post.create` (own posts) + 1 `auth.login`, 0 rows touch the member's question, and no marker or body appears. |
| Search | **Negative-only.** `/api/search` returned 0 marker/body hits for guest, member, other and admin. Board list: other 403, guest 401. External Scout, bulk, manual and queued indexing were NOT_RUN, so containment is unchanged (CONTAINED, not CLOSED). |
| Attachments | Member upload → 403 and no row (board inactive, `use_file_upload=0`). Foreign attachment access is NOT_RUN: no realistic owned file exists, and a 404 on a nonexistent ID is not used as proof. |
| Login / logout | **PASS**. Login 200 → logout 200 → the same token returned 401 on cart and public. Relogin persisted the edited state; second logout → 401. Own issued tokens left: **0**. The 3 handoff tokens are retained. |

## 6. Author unit evidence, re-executed locally (source-level, not installed live)

| Command | Result |
| --- | --- |
| `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelAtomicThrottleTest.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php` | Run 1 (time and load not recorded): **FAIL**, 21 tests / 2201 assertions, 1 failure at `TravelAtomicThrottleTest.php:447` (`assertSame(0, worker exit, stderr)`). One of the 4 SQLite worker processes exited non-zero. **The stderr message was not captured** (only the tail was kept), so the cause is unexplained. Runs 2–5: **PASS 21/2209** each (logged in [unit-atomic.txt](w04-atomic-contention/evidence/unit-atomic.txt)). Between run 1 and run 2 I also ran the file alone once: OK 11/177. That run was seen in-session but not saved. → **W04C-O2 (P3).** |
| `php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml` | **PASS 154 tests / 2539 assertions** in 151 s (load 17.6). SQLite domain suite; it matches the author's count but is not MySQL row-lock proof. [unit-domain.txt](w04-atomic-contention/evidence/unit-domain.txt), [unit-atomic.txt](w04-atomic-contention/evidence/unit-atomic.txt) |

## 7. New observations (P3)

**W04C-O1 — serialized per-actor admission occupies workers.**

- `Lock::block(3)` polls every 250 ms, using `usleep` inside a PHP worker. The lab server has 4 workers.
- Measured live: same-actor bursts ran at about 3.8–19 req/s depending on host load. A different actor's *unthrottled* catalog read had p50 384 ms (max 1.27 s) during the 8-lane burst. An in-session, unsaved curl measurement just before gave p50 about 98 ms idle; the probe itself took 0 baseline samples. The difference was not isolated from general burst CPU load.
- Busy rejections were 0 in all bursts.
- Impact is bounded: at most 3 s per waiter, then 503. But a single client with many parallel connections can tie up all workers in lock sleeps.
- Owner decision: production runtime with more workers, or a shorter or non-sleeping wait. Not a quota correctness defect.

**W04C-O2 — atomic unit fixture worker-exit failure.**

- The author's `TravelAtomicThrottleTest` failed once in 5 local runs under host load, with the cause not captured.
- Recommend that the author capture worker stderr in CI logs and rerun under load.

Other observations, outside W04:

- **Admin permission ordering:** `PermissionMiddleware` sorts after the travel throttles on admin inquiry routes (pre-existing; consumes only the caller's own bucket).
- **Ecommerce:** native DELETE of my own shipping policy returned 200 while my hidden product 85 still referenced `shipping_policy_id` 8, and policy GET now returns 404.

## 8. Cleanup (own IDs only, native contracts)

- **Inquiries:** 16 own run inquiries, all terminal through native cancel (14 cancels in cleanup). R3 ended CANCELLED; no DECLINED → CANCELLED forcing.
- **Carts:** 0 on the run departures.
- **Departures:** 17 inactive, reserved 0.
- **Product:** product 85 unpublished (public catalog 404) and hidden. Native DELETE was guarded with 409 and retained by design.
- **Category and policy:** category 28 inactive; own shipping policy 8 deleted natively.
- **Questions:** 20 own questions deleted via native admin DELETE (owner requery 404, lists 0); SQL shows 20 soft-deleted. The 1 admin answer comment was not separately deleted.
- **Tokens:** login tokens revoked through native logout. The expiry-test token was expired and then deleted through the guarded own-token path. The final state holds only the 3 handoff tokens.
- **Locks and counters:** 0 own admission lock rows. Own expired rate-counter rows remain until native lazy expiry; they were deliberately not deleted, because no shared cache was cleared.

No other owner's rows were touched, and no global wipe, setting/cache clear, clock change or restart occurred.

## 9. Remaining gates / NOT_RUN

- Engine-level lock graph: **NOT_RUN**, privilege 1142/1227.
- Anonymous 600/601 IP exhaustion: **NOT_RUN** by design, because 127.0.0.1 is shared with the browser reviewer.
- Effective runtime mail/queue env values: **NOT_RUN**.
- External Scout, bulk, manual and queued indexing and foreign attachments: **NOT_RUN** (contained).
- User existing-source and broad G7 regressions, real restart and installer ownership: **NOT_RUN here**; they belong to other owners.
- Hosted CI and canonical Validation: **NOT_RUN**.
- Deadlock attribution is correlated via a server-wide counter with idle controls only.

## 10. Commands, probes and delivery

**Probes** live in `docs/symphony/w04-atomic-contention/`. They are derived from the earlier nonauthor `w04-final-security` probes; the changes are SHA pins and DatabaseStore, gap-layout and admission extensions.

- [run.sh](w04-atomic-contention/run.sh) wraps the probe commands and logs their timing to [run-log.tsv](w04-atomic-contention/evidence/run-log.tsv). Three read-only inspections were run as inline `python3 -I -` snippets **without** run.sh: `t2-audit-inspect`, `t3-post-cleanup-inspect` and `t9-final-state`. Verbatim copies are in [inline_inspections.py](w04-atomic-contention/inline_inspections.py). An earlier inline latency sample (8 admin reads) produced no phase record.
- Each live command uses the pattern `run.sh <probe> <mode> [args]` with one of these probes:
  - `t1_throttle.py`: identity_sequential, burst, admission, wait_fresh.
  - `t2_questions.py`: budget, create_race, qburst, semantics, auth, cleanup.
  - `t3_workflow.py`: fixtures, matrix, races [R2 R3 R4], gaps, effects, cleanup.
- Read-only binding uses `provenance.py`, `route_order.php` and `hooks_parity.php`.

**Probe edits during the run** (disclosed): the delivered probes are the **final** versions. Earlier records were produced by earlier versions:

- **`t1_throttle.py`:**
  - The ordering criterion changed to "sent after a completed 429".
  - The remaining-header contiguity check was removed.
  - A 58 s issue deadline, the cross-actor catalog sampler and the no-429 → NOT_RUN rule were added (17:47).
  - Records from 17:20, 17:23 and 17:46 predate these changes; the 17:23 record still uses the old key `admitted_after_first_429_within_window`.
- **`t2_questions.py`:** the same criterion fix was made at 17:44, after the 17:43 question-burst FAIL.
- **`t3_workflow.py`:**
  - The `races` subset/prep (stale-cart clearing, R4 capacity restore, random R2 key, reserved baseline) was added before the 17:30 rerun.
  - Own-policy cleanup was added before the 17:50 cleanup.

No edit changed a recorded outcome.

**Raw record totals:** 59 PASS and 6 FAIL. All six FAILs are kept: 3 own-budget (R2, R3, R4), 1 sequencing (create race), 1 criterion (question burst) and 1 audit confound. There are also 2 NOT_RUN and 1 OBSERVED.

**Integrity:** sha256 for every file is in [manifest.json](w04-atomic-contention/evidence/manifest.json). Private state stays in the ignored `storage/framework/testing/w04-atomic-contention/` (0700/0600).

**Git:** one LOCAL commit on `verify/w04-atomic-contention-nonauthor` (parent `fa552317`). No push, merge, deploy or official child Request.

## Independent read-only package review

One native read-only subagent (no edits, HTTP, DB, probe runs or Git writes) cross-checked the first draft against the raw evidence and probe code.

**Verdict:** **ACCEPT_WITH_CORRECTIONS**.

**What it confirmed:**

- About 95 claims verified.
- All 6 FAIL, 2 NOT_RUN and 1 OBSERVED records disclosed.
- Clean privacy scan: no emails, tokens, UUIDs, passwords or contact data.
- No overclaim of Validation, CI or delivery.
- Manifest hashes matched.

**Corrections it requested, all applied above:**

1. Core 22 → **21** files.
2. 58 → **59** PASS.
3. The R4 first-attempt FAIL reason.
4. The 11/177 run (now labelled unsaved).
5. Unsaved figures sourced or labelled: the 98 ms idle baseline, load averages, privilege codes (now saved), module version and doc drift (now saved), composer timing (now saved), and the 1052 counter values (now cited).
6. The 3 inline inspections disclosed, with verbatim code delivered.
7. Mid-run probe edits disclosed.
8. The headroom "controller did not run" and R1-only same-actor read softened.
9. The barrier "waiting" criterion limit stated.
10. The "cannot form" wording softened.
11. The first question-burst's second criterion disclosed.
12. Idle-control spans corrected.
13. This section added and the manifest rebuilt.

No probe was rerun to change a recorded outcome after the review. The only new reads were the read-only `lock_visibility`/`schema` capture and the file diffs used as evidence.
