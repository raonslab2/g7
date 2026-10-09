# W04 final security review — fixed 1052e3fb (independent NONAUTHOR)

## Decision

**W04-S01 is CLOSED at the fixed installed source.** There are **no new P0, P1 or P2 findings** and no product fix by this reviewer. There are **two new P3 observations**:

- **W04F-01:** concurrent submits by different members can deadlock. The deadlock is masked by retry.
- **W04F-02:** native FileStore throttle counters lose increments under concurrent bursts.

All other required checks below **PASS** within the stated bounds. Some are explicitly weaker: negative-only (search), correlated (upload denial, deadlock attribution) or source-only. The rest are marked NOT_RUN or OPEN with the reason.

This is not an official canonical Validation receipt, not hosted CI (0 runs, NOT_RUN, not waived) and not a release PASS. W03-05(a) remains **CONTAINED, not CLOSED**.

| Item | Value |
| --- | --- |
| Work / Request | `work-20261009-g7-symphony-max-child-c7ae42d1` / `req_0683bc352fac4373b748cee64dedc53a`. Parent `req_81ac33cac94046b9a2249cd14c0d00ba`. |
| Review target | Fetched `origin/feat/g7-travel-lab-c7ae42d1`, checked out exact `1052e3fb4bc4cccabb51b8c538116c78655f345b`, tree `f18fa2056a031353c889785d768f87824e3083f5`. The local branch is `review/w04-final-security-1052`. The default `6853f40d` placement was not reviewed. |
| Source before → after | Product source unchanged: `git diff 1052e3fb -- ':!docs/symphony/W04_SECURITY_FINAL.md' ':!docs/symphony/w04-final-security'` is empty. Only this report and `docs/symphony/w04-final-security/**` were added. |
| Live runtime | `http://127.0.0.1:18871`. Isolated APP schema `req81_travel_lab` on MariaDB 10.11.14, REPEATABLE-READ. Account `req81_travel` was asserted by `SELECT DATABASE()` and `CURRENT_USER()` before every statement. The TEST schema was never used. Window 15:52–16:13 UTC, 2026-10-09; KST was already 2026-10-10. |
| Toolchain | PHP 8.3.6, PHPUnit 11.5.56, Python 3.12. Own `composer install --no-scripts` (17 s). `vendor/` is ignored and not shared with another Request. |
| Credentials | Read inside guarded processes only: the 3-role `access.json` and `database-access.json` (both 0600 in a 0700 dir). The guard asserts the source SHA, loopback host, exact schema/user and `g7_` prefix. Values are never printed, logged or placed on a command line. No parent `.env`, config cache, platform config, settings JSON or `/proc` environment was read. |
| Internal help | One native read-only Explore subagent reviewed the support, workflow and board sources (findings cited below as SRC). It made no edits, runs or Git operations. No official child or grandchild Request was created. |

## W04-S01 closure

| Check | Result | Evidence |
| --- | --- | --- |
| Installed registered order | **PASS** | The parent's installed `bootstrap/cache/routes-v7.php` (sha256 in [source-provenance.json](w04-final-security/evidence/source-provenance.json)) was parsed as text. The native `bootstrap/app.php` → HTTP Kernel → `Router::gatherRouteMiddleware()` of this SHA sorts all four public routes as `ExtensionMiddlewareGate:before_core → TravelOptionalSanctum → ThrottleRequests:600,1,travel-lab-support-public: → SubstituteBindings → …`. All four question routes sort as `Authenticate:sanctum → ThrottleRequests:120 (→ :10 create) → SubstituteBindings`. `TravelOptionalSanctum` implements `AuthenticatesRequests`, extends core `OptionalSanctumMiddleware` and declares no methods. [middleware-order-installed.json](w04-final-security/evidence/middleware-order-installed.json) |
| Same-IP valid actors, after member read consumption | **PASS** | Real loopback IP, no forwarded headers. The member made 40 public reads (remaining 599→560 exact). Then other_member's first read returned **599** and admin's first read **599**. The member's 41st read returned 559. The guest IP bucket moved 599→598 across the member's 40 reads, i.e. only its own read. The probe's tolerance (`< 40`) allowed for other loopback clients; the observed value was the exact 1. |
| Installed cache backend and key signature | **PASS (direct)** | The native `FileStore` under parent `storage/framework/cache/data` was read only. Key `travel-lab-support-public:`+sha1(user id) exists per actor, with exact integer counters (for example 600 after the sequential run). In a separate read-only inspection, one public read each by member, other_member, admin and guest returned remaining 599 each. The per-user FileStore keys and the separate anonymous sha1(`\|127.0.0.1`) key each held exactly 1, and the DB cache table had 0 travel-lab rows ([p1-cache_inspect](w04-final-security/evidence/raw/)). The probe's first parser did not decode PHP-serialized `i:N;`; that probe FAIL record is retained in [p1-identity-155826.json](w04-final-security/evidence/raw/p1-identity-155826.json). |
| Exact 600/601 inside one own 60 s window | **PASS** (member, sequential) | First read remaining 599. Requests 1–600 all returned 200 in 40.81 s. Request 601 returned **429** with limit 600, remaining 0, Retry-After 19, at 40.864 s. The file counter read 600. No reset, sleep-to-threshold, cache seed or clock change. |
| Earlier boundary attempts (immutable) | NOT_RUN ×4 / OBSERVED ×2 | 2 lanes at 15:58 took 71.3 s and exceeded the window → **NOT_RUN** (not claimed). Three later attempts started in a non-fresh own window (remaining 499, 0 and 38) → NOT_RUN, with no reset. The two OBSERVED runs are W04F-02 below. |
| Guest / expired / invalid | **PASS** | Guest → IP bucket. Invalid `999999\|…` token → native 401 envelope. An own native-login token, expired via guarded own-token update, returned 200 on the public route. Its counter sat strictly between two guest reads (599 → 598 → 597): the guest IP bucket, not a per-user counter. The same token returned 401 on private questions. After guarded own-token delete, the public route returned 401. |
| Required auth and counters persist | **PASS** | See the question budgets below. Anonymous private list and show returned 401. |
| Source fixture (frozen clock, separate) | **PASS 10/2032** | `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php`, 3.32 s. [fixture-tests.txt](w04-final-security/evidence/fixture-tests.txt). This is a fixture result, not live HTTP. The lead's 35/2233 default-configuration run was not reproduced, because its command is not recorded. |

## Private questions

| Check | Result |
| --- | --- |
| Create 10/11 | **PASS**. A fresh window (remaining 119) gave ten 201s, then the 11th POST returned **429** with limit 10, remaining 0. SQL confirms exactly 10 new secret posts. |
| Aggregate 120/121, counting the rejected write | **PASS**. The window held 1 read, 11 POSTs (including the rejected 11th) and 108 reads, with remaining 0. Request 121 returned **429** with limit 120. A POST after exhaustion also returned 429 with limit 120. Total 10.3 s. |
| Actor and route prefix separation | **PASS**. After the member's question exhaustion, other_member's question read returned 200 with remaining 119, and the member's public read returned 200 with limit 600 and remaining 599. (The identity probe's step name claims "after admin public exhaustion", but admin was not exhausted in that run because the window rolled over. That step only shows the routes stayed available; it is not counted as exhaustion evidence.) |
| Concurrent create burst (bounded) | **PASS, single run**. 20 POSTs across 8 lanes in 0.78 s gave exactly 10×201 and 10×429, with 10 rows. This is not an atomicity proof (see W04F-02). |
| Owner native edit and audit | **PASS**. PATCH returned 200, and requery showed exact title/body, `is_mine` and `is_secret`. The native activity API and SQL show exactly one `post.update` for the edited post. It mentions title and has no `content` key; a unique body marker is absent from all 11 rows. Foreign attempts produced 0 rows. SRC: `Post::$activityLogFields` excludes content. |
| Foreign access, anonymous, admin answer | **PASS**. A foreign user got 404 on show and patch, the foreign list returned 200 with none of the own IDs (list size not recorded), and anonymous requests got 401. Admin show returned 200, and a native board admin comment returned 201. The owner then saw `answers_count` 1. |
| Login / logout / relogin | **PASS**. Native login returned 200, and cart/question reads with that token returned 200. Logout returned 200; the same token then got 401 on both cart and public. Relogin returned 200, and the edited title persisted. Second logout returned 200, then 401. The handoff token stayed 200. Own issued tokens remaining in the DB: 0. |
| Search / indexing | **No exposure observed (current APP DB only; negative-only, no positive control)**. The question board is inactive and excluded from indexing by design, so 0 hits do not by themselves isolate the secrecy gate. `/api/search` for the run marker and the body text returned 0 hits for guest, member, other and admin. The native board list returned 403 for other and 401 for guest. External engines, `scout:import` and queued jobs were NOT_RUN. W03-05(a) remains CONTAINED, not CLOSED. |
| Attachments | **Upload denied / foreign access NOT_RUN**. Board 3 has `use_file_upload=0` and `is_active=0` (SQL). A native member upload returned 403 with the native permission-denied message, and no attachment row was created. Attributing the denial to the setting rather than to the permission/inactive gate is not isolated. Because the native member path denies owned attachment creation, no realistic owned file exists. Foreign attachment access is therefore NOT_RUN; a 404 on a nonexistent ID is not used as proof. SRC observation: the admin upload route checks only its admin permission, not `use_file_upload`. It is admin-only on this board, and it was not executed. |

## Server-contract and side-effect matrix (14)

Own fixtures were created only through native admin APIs:

- category 25;
- product 69 (8 options, plus 2 added later for the R1b/R1c reruns);
- catalog registration;
- unique departures 96–105.

The free-KR shipping policy 1 was reused read-only, as in W03, because the preview's `shipping_types` table blocks creating an own policy. Raw results: [p3-matrix-160746.json](w04-final-security/evidence/raw/p3-matrix-160746.json), [p3-effects-161230.json](w04-final-security/evidence/raw/p3-effects-161230.json).

| # | Contract | Result |
| --- | --- | --- |
| 1 | Native server price | **PASS**. Cart and public catalog unit price was 12000, equal to SQL product price 12000 + option adjustment 0. |
| 2 | Available option price change | **PASS**. A native admin PUT set the adjustment to 700, with option IDs preserved. The cart unit price followed it to 12700. |
| 3 | Quantity × unit | **PASS**. PATCH qty 2 gave line 25400. The submit returned 201, and SQL shows qty 2, unit 12700.00, line 25400.00, total 25400.00 KRW. |
| 4 | Quantity tamper | **PASS**. 0, −1, 1.5, "abc" and 100000 all returned 422; qty 5 over capacity 4 returned 409; an unknown departure returned 404; no cart row was created. |
| 5 | Money/owner tamper | **PASS**. Cart `unit_price`, `price`, `total_amount` and `user_id` all returned 422. Inquiry `total_amount`/`user_id` returned 422. Admin PATCH `total_amount` returned 422. |
| 6 | Date (live KST boundary) | **PASS**. At UTC 2026-10-09 16:07 (KST 10-10 01:07), departures dated KST-today 2026-10-10, past dates, and return-before-departure all returned 422. Fields `reserved` and `unit_price` also returned 422, and a member returned 403. This covers the 00:00–08:59 KST window that W03 could not test live. The raw `kst_today` key was overwritten by the date string after the status assertion passed; that is a probe display defect only. |
| 7 | Foreign cart / inquiry | **PASS**. A foreign cart submit returned 409 with the native "cart changed" message (`장바구니가 변경되었습니다…`; no code field recorded), and the foreign cart was kept. A foreign inquiry returned 404 on show and on cancel. |
| 8 | Reservation by quantity | **PASS**. SQL `reserved` went 0→2 for qty 2. |
| 9 | Capacity enforced | **PASS**. Capacity 3 was fully reserved, and another member's add then returned 409. The R1/R4 races also hold `reserved ≤ capacity`. |
| 10 | Release exactly once | **PASS**. Cancel took reserved 3→0. A repeated cancel returned 200 and reserved stayed at 0, never negative. R3 confirms the same under overlap. |
| 11 | TEST states separate from orders | **PASS**. TEST_INQUIRY→UNDER_REVIEW→TEST_ACCEPTED all returned 200. An invalid back-transition returned 409. A member hitting the admin list or patch got 403. SQL shows 3 events. |
| 12 | Idempotency | **PASS**. Same key and payload returned 200 with the same ID; a changed payload returned 409 (also confirmed under overlap in R2). |
| 13 | Native orders / payments unchanged | **PASS (own and global SQL)**. orders 0/0, order_payments 0, order_options 0 and temp_orders 0/0, before and after. No checkout, order or payment call was made. |
| 14 | Dispatch / mail / queue blocked | **PASS, bounded**. Own and global SQL deltas: jobs 0, failed_jobs 0, mail_send_logs 0, notification_logs 4→4, notifications 2/own 0 → 2/own 0. The suppression listener is installed. The source lab guard `scripts/travel-lab/environment.php` refuses to run unless `MAIL_MAILER=array` and `QUEUE_CONNECTION=sync`, and `.env.travel-lab.example` sets `G7_ENV_PRIORITY=true`. The effective runtime values were not read (the parent `.env` was not opened), so this is not a zero-inbox assertion. |

## Sustained real HTTP races (new fixtures, new SHA)

Per-race records (connection IDs, samples, responses, counter values) are in [races-extract.json](w04-final-security/evidence/races-extract.json). They are copied verbatim from private state, because the R1 raw step stores only its failed assertion.

**How overlap was produced:** [db_guard.php](w04-final-security/db_guard.php) `barrier` holds `FOR UPDATE` on one own row on a separate APP connection.

**What was visible to the scoped account:**

- `performance_schema.data_lock_waits` is denied (1142).
- `INNODB_TRX`, `INNODB_METRICS` and `ENGINE INNODB STATUS` are denied (1227).
- The criterion therefore uses PROCESSLIST plus HTTP timing: two distinct app connections in `Execute`/`Statistics` on a locking read whose `TIME` advanced 0→3 s, the same pair present in the first and last samples, both HTTP responses still pending at release and sent at least 3 s before it, and completed 70–130 ms after it.
- Limits of this criterion: with lock tables invisible, "waiting" is not engine-attributed to the barrier row. The table-name match is textual. `sustained_s` is at least 3.3 by loop construction, and only the first and last samples are compared. The overlap claim rests mainly on the requests still being pending until release.
- Global `Innodb_deadlocks` is visible and was sampled. It is a server-wide counter.

| Race (lock) | Waiting connections, sustained | Result | Invariant |
| --- | --- | --- | --- |
| R1 last seat, two members (departure 99, cap 1) | 92118, 92120 for 3.35 s; both pending, sent 3.57–3.62 s before release | member 201 (inquiry 120), other 409 with the native capacity message | reserved 1 = capacity 1. **Server deadlock counter 4→5 inside this race** (W04F-01); raw step FAIL retained |
| R2 same member, same key (departure 100) | 92131 on the barrier and 92132 waiting on a native locking read on another table (consistent with the user row held by the first request; not engine-confirmed), 3.39 s | 201 + 200, same ID 121 | reserved 1. Replay 200 with the same ID; changed payload 409; 1 SQL row |
| R3 owner cancel vs admin decline (inquiry 122) | 92150, 92152 for 3.32 s | cancel 200, decline 409 `invalid_transition` | reserved 1→0→0; repeat cancel 200; final CANCELLED |
| R4 submit qty 2 vs admin capacity 2→1 (departure 102) | 92210, 92212 for 3.41 s | submit 201, PUT 409 `capacity_conflict` | reserved 2 ≤ capacity 2 |

The original `7de0c444` races remain an older target and are not inherited as current evidence.

**Lock order observed:**

- submit: User → idempotency lookup (`FOR UPDATE`, gap lock when absent) → Cart → Departure → Product → Option → ShippingPolicy → CountrySetting;
- cancel: User → Inquiry → Departure;
- admin transition: Inquiry → Departure;
- `saveDeparture`: Departure → Product → Option → TravelProduct.

### W04F-01 (P3, new) — cross-member submit deadlock, masked by retry

**Mechanism (source):** `InquiryService::submit` performs a locking `byKey()` lookup on unique `(user_id, idempotency_key)`. When no row exists, this takes a gap lock. It then waits on the shared departure, and inserts into that gap.

When two members' keys fall into the same index gap, the following cycle forms:

1. Both members hold that gap lock.
2. The departure winner's INSERT waits on the loser's gap lock.
3. The loser waits on the departure lock, which the winner holds.

InnoDB then kills one transaction, and `DB::transaction(…, 3)` silently retries it.

**Live evidence:**

| Run | Keys | Deadlock counter | Outcome |
| --- | --- | --- | --- |
| R1 | Shared the gap | 4→5 | Correct |
| 45 s idle control | — | Flat at 5 | — |
| R2–R4 | — | Flat | — |
| R1b, fresh departure 104 | Placed in different gaps; the record predates the variant/keys labels added for R1c (probe version drift, disclosed) | No increment | Correct |
| R1c, fresh departure 105 | Placed in the same gap by design (member's sorts after its own keys, other's before its own) | **Exactly 5→6**, as predicted, between the in-hold sample (5, while both were blocked on the barrier) and the post-response sample (6) | Still correct: 201/409, reserved 1 |

This is a correlated, predicted reproduction, not an engine-level deadlock report: the scoped account cannot read INNODB STATUS.

**Impact:** correctness held in every run. Under sustained same-gap contention, more than three consecutive deadlocks would surface as a 5xx. Retries also hide the lock-order cycle from logs.

**Prior statements:** the W03 recheck's statement that "no deadlocks" occurred did not sample this counter. That report is unchanged.

**Owner:** the product lead decides on a fix, for example taking the departure locks before the absent-key lookup, or a non-gap idempotency strategy, followed by a fixed-SHA recheck.

### W04F-02 (P3, new; confirms W03R-04 quantitatively) — FileStore counters lose increments under concurrency

**Runtime:** the installed runtime uses the native FileStore for throttles (direct key evidence above). FileStore increments are a read-modify-write with no atomic counter.

**Runs:**

| Lanes | Requests 1–600 | Accepted before first 429 | File counter after | Lost increments |
| --- | --- | --- | --- | --- |
| 4 | 14.5 s | **611** (first 429 at request 612) | 600 | 11 |
| 2 | 18.3 s | **661**, still accepting when the bounded 60-request extension stopped | 561 | At least 100 inferred (661 accepted vs counter 561) |

The 2-lane loss is consistent with a counter reset under torn/empty concurrent reads. That mechanism is inferred, not instrumented.

A single sequential lane was exact (600/601). A bounded concurrent burst against the create limit of 10 held at exactly 10 in one run.

**Impact:** parallel clients can exceed the per-actor public budget during a window. The precision of abuse controls degrades. Identity separation (W04-S01) is unaffected.

**Recommendation for production:** an atomic cache store (database or redis) for rate limiting. The lab `.env` keeps `CACHE_STORE=file`, and no setting was changed here.

## Installed source / hook / route provenance

[source-provenance.json](w04-final-security/evidence/source-provenance.json) and [hooks-parity.json](w04-final-security/evidence/hooks-parity.json) are read-only. The parent HEAD was `77ce7114` (docs-only commit after `1052`; 2 files) with a clean tracked tree.

- **Module** (165 files):
  - This SHA's `_bundled` equals the parent `_bundled` byte-for-byte: 165/165.
  - The installed active `modules/raonslab-travel_lab` equals it for 163/165 files.
  - The two differing files are `CHANGELOG.md` and `docs/support-api.md`. The installed copies predate the S01 documentation lines; all code is identical. This is an observation (documentation-only drift; the version was not bumped).
- **Template:** 144 installed files. One test helper differs and ten evidence-only files are absent from the installed copy. No runtime `dist` difference was found.
- **Core and vendor:** 13 files are equal between this SHA and the parent installation. They include `OptionalSanctumMiddleware`, `bootstrap/app.php`, `ExtensionMiddlewareGate`, `RefreshTokenExpiration`, `ThrottleRequests` (`73124b8b…`), `RateLimiter` (`18ed0bb8…`), `FileStore`, the Kernel (`9b5e7871…`), `SortedMiddleware` (`bc0beaae…`), `Authenticate` and the Sanctum Guard/token.
- **Hooks:** the installed `hooks.php` travel subset equals the source `getSubscribedHooks()`: 4 listeners, 11 subscriptions (checkout block 4 sync priority 1, catalog guard 3, search exclusion 3, notification suppression 1).
- **Routes:** the installed route cache has 31 travel named routes and all 8 throttle prefixes. `TravelOptionalSanctum` appears on exactly the 4 public support routes.

## Cleanup, retained rows and tokens

**Questions:** the 20 own questions (10 member, 10 other_member) were deleted via native admin DELETE (200). Each owner requery returned 404. The member list contained 0 run questions; the other_member list returned 200, but its contents were not asserted. The native soft-delete retains all 20 rows (SQL count). The one admin answer (native comment) was not deleted separately; its state was not measured.

**Workflow fixtures:**

- 8 own inquiries, all CANCELLED by final SQL ([p9-inquiries](w04-final-security/evidence/raw/)).
- Carts on the run departures: 0.
- Departures 96–105: inactive, reserved 0.
- Travel product: unpublished, so public catalog 404.
- Native product 69: hidden. Its native DELETE remains guarded (409, retained by design).
- Category 25: inactive.

**Tokens:**

- Every login-issued token was revoked through native logout (logout → 401 verified).
- The one token used for the expiry test was expired and then deleted through the guarded own-token path, because native logout cannot run on an expired token. It returned 401 afterwards.
- The final SQL shows only the three handoff tokens (336–338), which are retained.

No other owner's rows, orders, payments, global wipes, snapshot restores, setting/cache clears or service restarts were touched.

## NOT_RUN / BLOCKED (precise)

- Anonymous 600/601 IP-bucket exhaustion: **NOT_RUN** by design. 127.0.0.1 is shared with other live reviewers, and exhausting it would block them. It is covered by the source fixture only.
- Engine-level lock graph (INNODB STATUS, data_lock_waits): **BLOCKED** by account privilege (1142/1227). PROCESSLIST and the global deadlock counter were used instead.
- Effective runtime mail/queue/cache env values: **NOT_RUN**, because the parent `.env` was not read. Source guard plus SQL effects only.
- Foreign attachment access: **NOT_RUN** (see above).
- External search engine and bulk import: **NOT_RUN**.
- Browser/UI and fresh TEST install: owned by other Requests, **NOT_RUN** here.
- Hosted CI and official Validation: **NOT_RUN / unavailable**.

## Independent evidence check

Before commit, a second read-only native subagent cross-checked every claim against the delivered raw JSON. It verified the large majority of the numbers and found 9 unsupported or contradicted claims, 3 overclaims, probe-criterion limits and a stray `__pycache__` (which included an absolute path). All of these are corrected or disclosed above, and the `__pycache__` was removed. No probe was rerun to change a recorded outcome. The only new measurements are the read-only cache inspection (4 public reads) and a final SQL inquiry-status read, both added as evidence for claims that previously had none.

## Delivery

**Files added:**

- this report;
- probes: `w04f_common.py`, `p1_public_identity.py`, `p2_questions.py`, `p3_workflow.py`, `db_guard.php`, `middleware_order_installed.php`, `source_provenance.py`, `hooks_parity.php`, `build_manifest.py`;
- `evidence/` (sanitized JSON);
- [execution-manifest.json](w04-final-security/evidence/execution-manifest.json), with commands, timings and sha256 of every file.

Private state, contacts and credentials stay in the ignored `storage/framework/testing/w04-final-security/` (0700/0600) and the parent handoff directory. Raw FAIL, NOT_RUN and OBSERVED records are kept unedited.

**Git and publication:** one LOCAL commit only. No push, merge, deploy or official child Request.
