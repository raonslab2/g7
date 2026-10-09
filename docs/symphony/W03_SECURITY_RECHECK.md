# W03 security recheck — fixed SHA 7de0c444 (nonauthor)

## Decision

**Original findings closed. No new P0/P1/P2.**

- **Original findings.** W03-01, W03-02, W03-03, W03-04, W03-05(b), W03-05(c) and W03-05(d) are closed. W03-05(a) is contained but not closed.
- **New findings.** Four new P3 observations (W03R-01..04).
- **Real HTTP overlap.** It has now been measured: 4/4 races in the final run, with two distinct blocked DB connections each.
- **What this is not.** This is a nonauthor internal review. It is **not** an official canonical Validation receipt or a product PASS. Hosted CI is **NOT_RUN** (0 runs). This reviewer authored no product fix; only tests, probe scripts and this document were added.

| Item | Value |
| --- | --- |
| Work / Request | `work-20261009-g7-symphony-max-child-c7ae42d1` / `req_82f0e86c05094c4eb83b85cc6c98f9e0`. Parent: `req_81ac33cac94046b9a2249cd14c0d00ba`. |
| Review target | `7de0c4441b68b1c012dbf4a75114200e322051c9`, tree `3c285467c066149d26b9df2b392299d819862a69`. Checked out on own branch `review/w03-security-recheck-req82`; `git rev-parse HEAD` was verified. It was not the default `6853f40d`. |
| Original negative retained | `docs/symphony/W03_SECURITY_REVIEW.md` (target `28ada286`, CHANGES_REQUIRED), unchanged. |
| Module versions at target | `raonslab-travel_lab` 0.1.1 (deps: ecommerce `>=1.2.1`, board `>=1.1.2`, page `>=1.1.2`), `g7_version >=7.0.11` |
| Toolchain | PHP 8.3.6, PHPUnit 11.5.56. `composer install --no-scripts` in the own worktree; `vendor/` is ignored. |
| Live preview | `http://127.0.0.1:18871`, unit `req81-travel-lab-preview.service`, `php scripts/travel-lab/run.php preview` → `artisan serve` → `php -S`. `PHP_CLI_SERVER_WORKERS=4`; main plus 4 forks were observed. APP DB `req81_travel_lab` on MariaDB 10.11.14, account `req81_travel`. The preview was not restarted or reconfigured. |
| Installed-module identity | The active `modules/raonslab-travel_lab` is byte-identical to the candidate `_bundled` tree: 159/159 files compared, 0 differ, none missing on either side. |
| Installed-module caches | The installed `bootstrap/cache/hooks.php` lists `ProtectTravelCommerceCatalog` with `before_delete` (sync, priority 1), `before_update` (sync, priority 1) and `filter_update_data` (filter, priority `PHP_INT_MAX`). The installed `routes-v7.php` contains all 5 throttle prefixes and `admin.catalog.store`. |
| Fixtures | Only the private 3-role file was used: synthetic member, other_member and admin, `0600` in a `0700` directory, expiry 16:00Z, `source_sha 7de0`. It was copied to the own ignored `storage/framework/testing/w03-recheck/`. Every row was created by this Request through authorized APIs. Shared seed fixtures, migrations, the TEST schema and `RefreshDatabase` were not touched. |

## Original finding closure matrix

The kinds of evidence are kept separate: **SRC** = source/static reading, **SQL** = module SQLite launcher, **HTTP** = real native HTTP on the isolated preview with MySQL (MariaDB), **OV** = official Validation (not run).

| Original ID | Original severity | Status at 7de0c444 | Evidence |
| --- | --- | --- | --- |
| W03-01: native delete of a travel product fails late, after image/Q&A cleanup | P2 | **CLOSED** | See [W03-01 detail](#w03-01-detail). |
| W03-02: native option sync removes a departure option, rejected only by the FK (500) | P3 | **CLOSED** | See [W03-02 detail](#w03-02-detail). |
| W03-03: same-day cut-off computed in UTC | P3 | **CLOSED** | See [W03-03 detail](#w03-03-detail). |
| W03-04: no throttle on workflow routes | P3 | **CLOSED** | See [W03-04 detail](#w03-04-detail). |
| W03-05(a): search exclusion covers only `index_should_update` | P3 | **CONTAINED, not closed** | See [W03-05(a) detail](#w03-05a-detail). |
| W03-05(b): provisioner does not verify board permissions | P3 | **CLOSED** | SRC: `assertSafe` checks the board flags and all 16 board permissions (non-empty, includes `admin`, nothing beyond admin/manager/step roles). Every support request path calls it uncached: 503 at runtime, 409 on provisioning. SQL: NOT_RUN, because the board tests need MySQL and are owned by the runtime TEST Request. HTTP: the support channel was ready (question store returned 201). |
| W03-05(c): question edits bypass `PostService` and the audit trail | P3 | **CLOSED** | SRC: edits go through `PostService::updatePost` (before/after hooks plus the filter). The raw `updateContent` method was removed from the interface and the repository. SQL: NOT_RUN (MySQL-only test). |
| W03-05(d): manager reads all private questions | P3 | **CLOSED** (one P3 residual, W03R-03) | See [W03-05(d) detail](#w03-05d-detail). |
| SHARED-01: lock order | — | **Measured** | See [real HTTP overlap](#real-http-overlap). |

### W03-01 detail

- **SRC:**
  - `ProtectTravelCommerceCatalog::beforeDelete` is a sync action at priority 1 on `sirsoft-ecommerce.product.before_delete`. That hook fires at `ProductService.php:446`, before `deleteInquiriesForProduct` (`:451`) and before image deletion (`:455`).
  - It throws `ProductHasOrderHistoryException`. The native controller maps that exception to 409.
  - `TravelCatalogConflictResponse` (pushed to the `api` group) rewrites only that marked 409 to the travel reason.
- **SQL:** the author tests pass in the independent rerun.
- **HTTP (final run):**
  - Real `DELETE /api/modules/sirsoft-ecommerce/admin/products/36` → **409** with the travel reason (ko locale).
  - The product still exists (200), and its image count is unchanged.
  - The image asset GET is 200 with the same SHA-256 before and after.
  - All 6 departures were retained. No 500.
  - An ordinary own product DELETE → 200, then 404. The native path is unaffected.
- **HTTP NOT_RUN:** the Q&A half. The native product-Q&A store returns 422 "inquiry board not configured" on this preview, so no thread could be created. The guard runs before `deleteInquiriesForProduct`; that is covered by the author SQLite test (Mockery `shouldNotReceive`) and by SRC.

### W03-02 detail

- **SRC:**
  - `beforeUpdate` and the final `filterUpdate` (priority `PHP_INT_MAX`) both throw `ValidationException`, before `DB::transaction` (`:350`).
  - The native controller maps it to 422 with field errors.
- **SQL:** my test proves that a lower-priority native filter which strips options is still rejected, and that nothing is written.
- **HTTP:**
  - A real native `PUT` omitting the linked option, together with a name change → **422** with `errors.options` = the travel reason. Name and option IDs were unchanged.
  - These legitimate edits all returned 200: linked option `price_adjustment` 0→500, a new option added, and the unlinked option removed. Ordinary-product option removal and price edit → 200.

### W03-03 detail

- **SRC:** `TravelDate::today()` uses `Asia/Seoul` and is used in the catalog (`CatalogRepository:38,122`), the cart/submit availability check (`TravelCartService:212`) and the departure form (`DepartureRequest:21`). `app.timezone` remains UTC.
- **SQL:** 5 boundary cases on the real `TravelDate`. Only the clock moves (`travelTo`); the timezone is asserted to stay UTC.
  - UTC 14:59:59 (KST 23:59:59) — the KST-tomorrow departure is available.
  - UTC 15:00 (KST 00:00) — the KST-today departure is closed.
  - UTC 23:59:59 (KST 08:59:59) — the KST-today departure is still closed, while the next KST day is available.
  - UTC 00:00 (KST 09:00) — the KST-today departure stays closed.
  - Closed cases return 409 with no reservation and no inquiry, and the cart is kept.
- **HTTP:** a departure dated KST today is rejected with 422, and tomorrow is accepted. The live clock was KST evening, not the 00:00–08:59 window.

### W03-04 detail

- **SRC/SQL:** the route contract test confirms these buckets: `throttle:120,1,travel-lab-workflow:` plus cart 60, submit 10, cancel 20 and admin PATCH 60, all with `auth:sanctum`.
- **HTTP (validation-failing probes only, no writes):**

  | Bucket | Requests before limit | Next request |
  | --- | --- | --- |
  | Submit | 10× 422 | 11th = **429** |
  | Cart | 60× 422 | 61st = **429** |
  | Cancel | 20× 404 | 21st = **429** |
  | Admin PATCH | 60× 404 | 61st = **429** |
  | Workflow total | — | 429 with `X-RateLimit-Limit: 120` |

- **Isolation:**
  - Buckets are per actor: the member's submit returned 422 (not 429) while other_member was throttled.
  - Buckets are per route: other_member's cart GET was 200 after its submit limit, and the admin list was 200 after the PATCH limit.

### W03-05(a) detail

- **SRC, what is covered:**
  - The question channel and provisioning fail closed (503 / 409) for any `scout.driver` other than `mysql-fulltext`; this includes `database` and `collection`.
  - Normal saves are excluded from indexing.
  - Restore and delete call `unsearchableSync()`.
- **SRC, what is NOT covered:**
  - `shouldBeSearchable()` and `toSearchableArray()` are not filtered.
  - `scout:import` / `makeAllSearchable` bulk import still exports questions (asserted by the author's test).
  - Manual `searchable()` calls are not covered.
  - Already-queued `MakeSearchable` jobs are not covered.
  - Rows that already exist when an operator switches drivers are not covered.
- **Not tested:** no external engine and no network. The shared search config was not changed. The preview `SCOUT_DRIVER` is `mysql-fulltext`.

### W03-05(d) detail

- **SRC:**
  - The travel `support.*` permissions are granted to `admin` only.
  - Reading others' questions also requires the native board permissions `admin.posts.read` and `admin.posts.read-secret`.
  - Each grant runs through `hasPermission`, `checkScopeAccess` and the module's own owner check. With a `self` grant, the user may access only their own questions; with a `role` grant, the user must share a role with the question's owner. Anything else returns 404.
  - Listing narrows to the viewer's own questions unless every grant is unscoped.
- **HTTP:**
  - The member's private question: other_member show 404, patch 404, and it is not in other_member's list.
  - Owner 200 and listed; admin 200; anonymous 401.
- **SQL:** NOT_RUN (MySQL-only tests).

## Real HTTP overlap

- **How it was measured:** `tests/evidence/w03-recheck/row_lock_barrier.php`.
  - The parent's marked `.env` is loaded in memory through the parent's `scripts/travel-lab/environment.php` allowlist.
  - It verifies `DATABASE()` = `req81_travel_lab` and `CURRENT_USER()` = `req81_travel`, and no TEST connection is used.
  - It locks `FOR UPDATE` only one owned row: a departure of a `W03R-` product, a synthetic user (id 30 or 31), or that user's inquiry.
- **What is reported:** redacted PROCESSLIST facts only (connection ID, command, time, state, booleans). SQL text, credentials and contact data are never printed.
- **What counts as PASS:** two distinct app connections are seen in `Execute`/`Statistics` on a `FOR UPDATE` against the held table. They are still waiting after a hold of 3 s or more. Both HTTP requests were sent at least 3 s before the release and completed after it.
- **INNODB_TRX:** not visible to the scoped account, so the PROCESSLIST state plus the statement target is the criterion.

Final run `W03R-123332`. Every race overlapped on the first attempt:

| Race (lock held on) | Blocked distinct connection IDs | Result | Invariant |
| --- | --- | --- | --- |
| Last seat, 2 members (departure, capacity 1) | 64549, 64548 (Execute/Statistics, 3 s) | 201 + 409 `capacity_unavailable` | reserved 1 = capacity 1. A follow-up add by the winner → 409 (full capacity). |
| Same idempotency key, same member (user row) | 64700, 64699 | 201 + 200, same inquiry 70 | Exactly one inquiry, reserved 1. Exact replay → 200 with the same ID. A changed contact with the same key → 409 `idempotency_conflict`. The amount is 12500.00: native option price (12000 + adjustment 500) recomputed by the server, free KR shipping. |
| Member cancel vs admin DECLINE (inquiry row) | 64893, 64891 | cancel 200 + decline 409 `invalid_transition` | reserved 1 → 0, released exactly once. Repeat cancel → 200 (same-state no-op). Key replay → the same CANCELLED inquiry. reserved stays 0, not negative. |
| Submit qty 2 vs admin capacity 2→1 (departure) | 64943, 64942 | submit 201 + PUT 409 `capacity_conflict` | reserved 2 ≤ capacity 2, so the hold below reserved was rejected. |

- **No deadlocks:** none were observed in any run, and no config was changed. `DB::transaction(..., 3)` retries were not needed.
- **Lock order observed:** submit takes User → Cart → Departure; cancel takes User → Inquiry → Departure; admin transition takes Inquiry → Departure; `saveDeparture` takes Departure → Product → Option → TravelProduct. No cycle was found.
- **Earlier runs (honest record; raw kept in the evidence JSON):**
  - **Run 2:** detection bug in my barrier. It matched only `COMMAND=Query`, but Laravel's native prepares show `Execute`. Both requests did wait 20 s and completed 60–70 ms after the release.
  - **Run 3:** all races were detected. One check failed because of my wrong price expectation: the option's `selling_price` input is ignored, and the price is product price + `price_adjustment`.
  - **Runs 4 and 5:** one race each showed only one waiting connection. The other request had no DB connection during the hold, because the shared preview's 4 `php -S` workers were busy with another Request's concurrent browser traffic (preview log 12:25). Those attempts are counted as **no overlap**, not PASS. They led to a retry-on-fresh-fixture design that still checks the invariants on every attempt.
  - **Invariants in every run:** reserved was never negative, never above capacity, and there was never more than one accepted inquiry per seat.

## New observations (nonauthor, P3 or lower)

- **W03R-01 (P3, truthfulness):** the native preflight `GET /admin/products/{id}/can-delete` returns `canDelete: true` for a travel product (live). The actual DELETE then returns 409 with the travel reason. No data is lost, but the native UI precheck is misleading.
- **W03R-02 (P3, operational):** native option stock edits (single-product update or `option.bulk_*`) are not travel-guarded. An operator can lower a linked option's stock below a departure's capacity or reserved count.
  - `reserve()` caps at min(capacity, stock), so this cannot oversell.
  - The departure then shows 0 remaining, and admin departure saves return `capacity_conflict` until stock is restored. Releases still work.
- **W03R-03 (P3, support):** a `role`-scoped native board grant means "shares any role". A staff user whose role-scoped board grants coexist with the common `user` role could open and edit (but not list) any member's question by ID. This matches native `PermissionHelper` semantics and is not covered by tests. SRC only.
- **W03R-04 (observation):** the throttles use `CACHE_STORE=file`. File-store increments are not atomic across the 4 PHP workers, so a parallel burst may slightly under-count. Sequential limits were exact (above).
- **Residual race (static):** a new departure linked between `before_delete` and the native FK delete would still fall to the FK and the generic 500 path. It requires a concurrent admin write within the same window.

## Executed evidence

| Check | Kind | Command / environment | Result |
| --- | --- | --- | --- |
| Module domain suite at target, independent rerun | SQL | `php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml` | **PASS 144 tests / 2327 assertions** (3:32); matches the lead's 144/2327 |
| Nonauthor recheck tests | SQL | `--filter W03SecurityRecheckTest` | **PASS 8 / 137** |
| Suite including the recheck tests | SQL | same config | **PASS 152 tests / 2464 assertions** (1:24) |
| Pint | — | `vendor/bin/pint` on the new test | PASS |
| Live recheck probe | HTTP | `python3 -I modules/_bundled/raonslab-travel_lab/tests/evidence/w03-recheck/live_recheck_probe.py <private access.json> <parent root> <private out>` | Final run: **16 PASS / 0 FAIL / 1 OBSERVED / 0 BLOCKED**. The OBSERVED step is the shipping-policy fallback below. Sanitized output: `tests/evidence/w03-recheck/live-recheck-probe.json`, which also contains every earlier run. |
| Own free-KR shipping policy via native API | HTTP | `POST /admin/shipping-policies` | **BLOCKED**: 422, because the preview's `shipping_types` reference table is empty, so no `shipping_method` validates. Seeding shared reference data is out of scope. The existing free-KR travel policy (id 1) was reused read-only; the probe verifies it is active, non-default, free, has 0 fee and has no API endpoint. |
| Native product Q&A thread | HTTP | `POST /products/{id}/inquiries` | **NOT_RUN**: 422, inquiry board not configured on the preview |
| Native board-backed support tests (`TravelSupportApiTest` / `NotificationTest` / `ProvisionerTest` / `W03SupportHardeningTest`) | — | root MySQL runner | **NOT_RUN**: owned by the runtime TEST Request; support conclusions above are SRC plus live HTTP |
| External Scout engine, bulk import, queued job | — | — | **NOT_RUN** by design (no network, shared config not changed); containment is documented above |
| External order/payment/booking/mail/SMS/supplier/search calls | — | — | None were made. No order, temp order or payment row was created. `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`. |
| Hosted CI | — | — | **NOT_RUN** (0 runs) |
| Official canonical Validation | OV | — | **NOT_RUN**; this document is not a receipt |

## Own residue on the APP DB (all synthetic, created by this Request)

- **Guarded travel products** 19, 22, 25, 28, 33, 36:
  - all hidden and unpublished, absent from the public catalog (the public catalog is unchanged at the 8 seeded IDs);
  - all departures (33–57) inactive with reserved 0;
  - not deleted, because deletion is guarded.
- **Ordinary products:**
  - 23, 26, 29, 34 and 37 are hidden;
  - 20, 21, 24, 27, 30, 35 and 38 were deleted through the native API.
- **Other rows:**
  - Categories 5, 6, 12, 18, 19 and 20 are inactive.
  - Images: one PNG per travel product.
  - Inquiries: 15 own inquiries (13 member, 2 other_member), all CANCELLED.
  - Carts: both members' carts are empty.
- **Support questions** 24–28: private questions by the synthetic member. The API has no delete, so they remain.

## Changed paths (this review)

- `docs/symphony/W03_SECURITY_RECHECK.md`
- `modules/_bundled/raonslab-travel_lab/tests/Feature/W03SecurityRecheckTest.php`
- `modules/_bundled/raonslab-travel_lab/tests/evidence/w03-recheck/live_recheck_probe.py`
- `modules/_bundled/raonslab-travel_lab/tests/evidence/w03-recheck/row_lock_barrier.php`
- `modules/_bundled/raonslab-travel_lab/tests/evidence/w03-recheck/live-recheck-probe.json`

- **Product source:** no implementation source was changed, so there is no authored fix to separate.
- **Native helper:** one native read-only subagent did the support/privacy static review (items W03-05(a)–(d)); it did no edits or runs.
- **Publication:** a local commit only. Nothing was pushed, merged, deployed or published to main.
