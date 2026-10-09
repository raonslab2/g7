# W03 independent security / contract review — Travel Lab (nonauthor)

Decision: **CHANGES_REQUIRED (P2 + P3 only)**. No P0/P1 found in the checked scope.
This is an independent review by a nonauthor. It is **not** official Validation, a whole-product PASS, or a browser review.

| Item | Value |
| --- | --- |
| Work / Request | `work-20261009-g7-symphony-max-child-c7ae42d1` / `req_e66ad2372e844e5683a7619a727b2682` (W03 security/contract) |
| Review target | `28ada286c1c34606741bcfe4f9d12e06ac50af30`, tree `a821bd89f6cf8bdf8cad3b8b35d8758ce8446d9c`. Fetched from origin `feat/g7-travel-lab-c7ae42d1` (PR #2) and checked out on the own branch `review/w03-security-28ada286`. `git rev-parse HEAD` was verified. The five original child commits are reachable. |
| Baseline | `6853f40d` is the base only; it was not tested as this review |
| Reviewer | Native lead of this Request plus one native read-only subagent (support/privacy static review). Neither implemented the target. |
| Environment | PHP 8.3.6, PHPUnit 11.5.56. `composer install --no-scripts` in the own worktree (vendor is ignored). Module launcher: SQLite `:memory:`. Live preview: `http://127.0.0.1:18871`, `req81-travel-lab-preview.service`, APP DB `req81_travel_lab`, source_sha `28ada286`. The preview was not stopped, updated or reconfigured. |

## Findings (most severe first)

### W03-01 — P2: native product delete of a travel product fails late, after irreversible side effects

- **Where:** `sirsoft-ecommerce/src/Services/ProductService.php:434-497`, with travel FKs at `raonslab-travel_lab/database/migrations/2026_10_09_000001_create_travel_lab_tables.php:13,25,26` (`restrictOnDelete`).
- **Why the guard does nothing for travel products:** the native guard only counts *order* history. Travel products can never have orders, because the checkout guard blocks them, so `checkCanDelete` passes.
- **What happens on delete:**
  1. `deleteInquiriesForProduct()` runs before the transaction. It deletes the native product Q&A threads (static reading).
  2. `deleteProductImageFiles()` deletes the storage directory `images/products/{code}`.
  3. `options()->delete()` hits the FK on `travel_lab_departures` and throws `QueryException`.
  4. The admin controller's generic catch turns this into HTTP 500 (`Admin/ProductController.php:293-330`).
- **Result:** DB rows are rolled back, but the files are gone, so the product that is still on sale has broken images.
- **Repro:** `W03SecurityReviewTest::test_finding_native_product_delete_of_travel_product_fails_on_fk_after_irreversible_image_cleanup`. It asserts a foreign-key `QueryException`, that the product and departure still exist, and that the probe file was deleted.
- **Suggested fix:** subscribe a `sync` listener to `sirsoft-ecommerce.product.before_delete`, which fires at line 446 before every destructive step. It should throw a domain exception that the native controller maps to 409, and the repro should then be inverted. Retain travel/inquiry history.

### W03-02 — P3: native option sync rejects removing a departure option only through the FK

- **Where:** `ProductService::syncOptions` (`:818-880`).
- **Behaviour:** it removes omitted options after `validateOptionsDeletion`, which checks order history only. The departure FK stops the delete, so DB integrity holds (the option survives), but the result is a 500 with no domain reason.
- **Contract gap:** SCORE says option removal for options referenced by travel history must be denied explicitly.
- **Repro:** `test_finding_native_option_sync_removing_departure_option_is_rejected_only_by_fk`.

### W03-03 — P3: same-day cut-off is computed in UTC, not KST

- **Where:** `config/app.php` sets `timezone=UTC`. Catalog uses `departure_date > now()->toDateString()` (`CatalogRepository.php:37`). Workflow uses `now()->startOfDay()` (`TravelCartService.php:211`).
- **Behaviour:** catalog and workflow agree with each other, so SHARED-02 is closed. But between 00:00 and 08:59 KST a departure dated "today" in Korea is still treated as in the future, so it can be added to the cart and submitted.
- **Repro:** `test_finding_same_day_cutoff_uses_utc_not_kst`. At 2026-11-30 15:30 UTC (2026-12-01 00:30 KST), a 2026-12-01 departure is submitted as `TEST_INQUIRY`.
- **Next step:** decide which business timezone is canonical. Then apply that one timezone in both catalog and workflow.

### W03-04 — P3: no throttle on workflow routes

- **Where:** `src/routes/workflow.php`.
- **Behaviour:** cart, inquiry and admin routes carry only `auth:sanctum`, with no per-route `throttle`. There is no global API throttle either; the only one found is the core login throttle in `AppServiceProvider`.
- **Impact:** an authenticated user can submit test inquiries without limit. Each one is idempotent per key, but new keys can repeatedly reserve and then release simulated capacity.
- Support routes do have throttles.

### W03-05 — P3 group (support/privacy, static only, not exercised by tests here)

- **(a)** The search exclusion filters only `sirsoft-board.search.post.index_should_update` (the hook is real: `sirsoft-board/src/Models/Post.php:278-283`). `shouldBeSearchable()` is not filtered.
  - This has no effect under the default `mysql-fulltext` driver, and native search also skips inactive boards.
  - It could matter for an external Scout engine with `scout:import`.
- **(b)** `TravelSupportProvisioner::assertSafe` (`:145-155`) does not verify board permissions or `use_comment`/`use_reply`. A later operator change that loosens permissions would not be flagged.
- **(c)** Question edits go through `TravelSupportPostRepository::updateContent` (`:74-80`) and bypass native `PostService` hooks and the activity log. An admin with `support.update` can rewrite a member question without an audit trail. The fields are limited to title/content, so there is no mass-assignment issue.
- **(d)** `module.php:51` grants every travel permission to `manager` as well as `admin`.
  - The support service checks `hasPermission` without scope, so `manager` reads all private questions through the travel API. The native board itself is admin-only.
  - Inquiry admin paths do apply `checkScopeAccess`.
  - This may be intended, but it should be confirmed.

## SHARED re-review (nonauthor)

- **SHARED-02 (same-day visibility):** **closed for consistency.**
  - Catalog (`> today`) and workflow (`lte(today)` → unavailable) now agree.
  - The author's regression tests and my W03-03 probe confirm known-ID/stale-cart rejection on the UTC day.
  - The timezone basis is a separate issue (W03-03).
- **SHARED-01 (lock order):** **no cycle found statically.**
  - `CatalogRepository::saveDeparture` (`:145-148`) now locks Departure → Product → Option → TravelProduct.
  - The submit/cart paths lock User → Cart → Departure → Product → Option → ShippingPolicy → Country → TravelProduct, consistent with that.
  - `updateMetadata` takes only the TravelProduct row lock.
  - Real MySQL contention: **NOT_RUN** (see below).

## Verified controls

These were verified by tests in this Request, plus live preview checks where noted.

- **Price and amount authority.** No client money/owner/status/currency field is accepted.
  - Unknown fields → 422 via the `WorkflowRequest` allow-list (SQLite and live).
  - Amounts come from the real `CartService::getCartWithCalculation` with the shipping country pinned to KR.
  - Nonzero shipping/discount/points, a mismatched row count or a validation error → 409.
  - Live: subtotal = final = 189000, shipping/discount/points 0.
- **Quantity.**
  - Valid range is 1–99. Live checks: 0, -1, 100, `1.5` and `abc` → 422; over-capacity PATCH → 409.
  - A native cart `updateQuantity` to 50 is rechecked at locked submit → 409. Nothing is reserved, no inquiry is written, and the cart is kept.
- **Idempotency.**
  - Key format, and header/body key mismatch, → 422 (live).
  - Replaying the same key after cancellation returns the same cancelled inquiry and does not release capacity twice. The event count stays at 2.
- **Capacity.**
  - Owner cancel followed by a repeat cancel and an admin DECLINE (409) releases capacity exactly once.
  - Admin capacity below reserved → 409. Above option stock → 409. `reserved` in the body → 422. Setting capacity exactly equal to reserved → 200.
- **Ownership and permissions.**
  - Foreign cart PATCH/DELETE → 404 (SQLite and live). Foreign cart submit → 409 with no write.
  - Foreign inquiry read/cancel → 404 (SQLite; live 3/3 foreign inquiry reads → 404).
  - Member admin read/transition/departure edit → 403. Anonymous → 401.
  - Admin PATCH with `total_amount` → 422, and status and amount are unchanged.
- **Native checkout guard.** Live: `POST /sirsoft-ecommerce/checkout` with `item_ids` → 400, and with `direct_items` → 400. `GET /checkout` → 404, so no temp order exists.
  - Statically, all four subscribed hooks are really dispatched synchronously: `TempOrderService.php:77,341`, `OrderProcessingService.php:112,306,1657`.
  - Every order is created through `createFromTempOrder`. `HookManager::doAction` propagates exceptions.
- **Support privacy (static, native subagent).**
  - Owner/admin isolation in the travel API returns 404 for others.
  - The board is provisioned inactive and secret-always, with uploads, reports and notifications off, and admin-only native permissions.
  - The notification `extract_data` filter (priority 95, after native 20) sets `context.skip`, which is honoured at `app/Listeners/NotificationHookListener.php:117-132`, and only for the three travel slugs.
  - Members cannot post answers. Content is text-mode and rendered as text bindings.

## Executed evidence

| Check | Command / environment | Result |
| --- | --- | --- |
| Canonical module suite, independent rerun at target | `php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml` (SQLite memory) before adding tests | **PASS 118 tests / 1895 assertions** (1:28) |
| Module suite + W03 tests | same command after adding `tests/Feature/W03SecurityReviewTest.php` | **PASS 127 / 2026** (3:01) |
| W03 tests alone | `--filter W03SecurityReviewTest` | **PASS 9 / 131**; 3 are finding repros (W03-01..03) |
| Pint | `vendor/bin/pint <W03 test>` | PASS |
| Live HTTP probe | `python3 -I tests/evidence/w03/live_security_probe.py <access.json> <out>` against frozen preview `28ada286` with the security-review synthetic member/other_member/admin | **PASS 39 / 39**; the only write was the member's own cart row, created and then deleted (cleanup verified); catalog availability before == after; no inquiry/temp order/order/payment/mail/SMS. Output: `tests/evidence/w03/live-security-probe.json` (no tokens, passwords or emails). |
| Live last-seat / same-key / cancel / decline / admin capacity on NEW departures | — | **BLOCKED**: the travel API has no endpoint to create TravelProduct metadata (admin catalog API is PATCH-only; metadata comes only from the seeder or service), and every seeded option is already mapped. NEW departures therefore require direct service/DB writes in the shared APP DB, which needs lead coordination. These behaviours are covered on SQLite only (above). |
| Real concurrency (HTTP) | — | **NOT_RUN**: the preview is a single `php -S` built-in server, so overlap of requests is unproven (serialized). The author's four service-race runs in `scripts/travel-lab/live-concurrency.php` remain **author-only** and were not rerun. |
| Real MySQL row-lock / deadlock (SHARED-01) | — | **NOT_RUN**: the only MySQL test schema `req81_travel_lab_test` belongs to the runtime verifier, and it was not used. |
| Native board-backed `TravelSupportApiTest` / `NotificationTest` / `ProvisionerTest`; native ecommerce/board suites | root `phpunit.xml` requires MySQL | **NOT_RUN** (same reason). The support conclusions above are static. |
| Hosted CI, official formal Validation, browser 390/1440 | — | **NOT_RUN** here. They are owned by separate Requests/receipts. |

The implementation author's claims (Parent 118/1895, native MySQL 1/31 and the support runs, the four service races) were not used as evidence. Only the 118/1895 SQLite result was independently re-executed here.

## Changed paths (this review)

- `docs/symphony/W03_SECURITY_REVIEW.md`
- `modules/_bundled/raonslab-travel_lab/tests/Feature/W03SecurityReviewTest.php`
- `modules/_bundled/raonslab-travel_lab/tests/evidence/w03/live_security_probe.py`
- `modules/_bundled/raonslab-travel_lab/tests/evidence/w03/live-security-probe.json`

No implementation source was changed. Nothing was pushed, merged or deployed by this reviewer.
