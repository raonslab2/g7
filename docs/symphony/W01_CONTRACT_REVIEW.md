# W01 contract review — Travel Lab cart/inquiry backend (independent, pre-integration)

- Reviewer Request: `req_e35bb0ef069a4e15bcb44ac8831d092e` (did not implement the reviewed code)
- Work: `work-20261009-g7-symphony-max-child-c7ae42d1`; parent Request `req_81ac33cac94046b9a2249cd14c0d00ba`
- Review target (workflow): `6aaf80af9ad73fa44f642535b2de24b0d9b86a6c` (`git cat-file -t` → commit; parent `6853f40d`)
- Support/admin compatibility target (read via `git show` only): `2c0e38765acccac000923711e9e271dad483cc49` (parent `6853f40d`)
- Shared contract: `docs/symphony/SCORE.md` @ W00 head `332b5ddfd7db0fabda159d8a0f30af4b4ccab15c`
- Native subagents used: 1 (read-only support/admin layout compatibility check). No official child Requests, no push/merge/deploy, no production access.

**Verdict: NOT READY for integration as-is. 3 contract blockers (B1–B3) plus interoperability blockers with the support/admin layouts (I1–I5).** This is internal review, not official Validation, and it is not an integrated-release PASS.

## Evidence classes

| Check | Result | Class |
| --- | --- | --- |
| `php vendor/bin/phpunit modules/_bundled/raonslab-travel_lab/tests/Feature/TravelWorkflowTest.php` @ 6aaf80af | `OK (48 tests, 373 assertions)`, 01:10.8 | Reproduced independently. SQLite `:memory:`, with test-only `DomainContract` fixture models/enum; real ecommerce CartService/OrderCalculationService |
| Throwaway reviewer probe (not committed; deleted after the run) | Contract-violation reproduced (B1); shipping 0 only because no policy exists in the fixture (B2) | SQLite fixture probe |
| Real MySQL/MariaDB `SELECT … FOR UPDATE` contention, concurrent same-key submit, deadlock retry | NOT_RUN | SQLite has no row locks |
| Real domain models/enum/migrations, module.json/module.php/provider/api.php/permissions/lang | NOT_RUN — absent from both commits | integration pending |
| Browser/API end-to-end, 390/1440 | NOT_RUN | domain/UI children still running |
| Official Validation | NOT_RUN | — |

Environment used for the reproduction (in this worktree only): `composer install --no-scripts` using the identical `composer.lock`, plus `.env.testing` copied from `.env.testing.example` with a random APP_KEY. Both are git-ignored and not committed.

## Blockers (contract)

### B1 — The effective-availability formula does not match the contract (overselling against option stock)
- Contract: `available = max(0, min(capacity, option.stock_quantity) - reserved)`.
- Code: `TravelCartService.php:189-192` checks `quantity <= stock_quantity` and `quantity <= capacity - reserved` *independently*. That is `min(stock, capacity - reserved)`, which is weaker. It ignores `reserved` against the stock ceiling.
- The DB guard `WorkflowCartRepository.php:78-79` (`reserved + ? <= capacity`) also ignores stock.
- **Probe**: capacity 10, stock 5, reserved 3, quantity 4. The contract allows 2. The code **submitted, and reserved became 7 > min(10,5)=5**.
- Tests never vary stock (fixtures pin `stock_quantity` to 1000 at `WorkflowTestCase.php:205,209`).
- Safe correction:
  - Compute `ceiling = min(capacity, option.stock_quantity)` from the **locked** option row and require `quantity <= ceiling - reserved`.
  - Make the conditional UPDATE carry the same bound, e.g. `whereRaw('reserved + ? <= LEAST(capacity, ?)', [$q, $lockedStock])`, or a join/subquery to `ecommerce_product_options.stock_quantity`.
  - Keep the cart-add check the same as the submit check.
- Tests:
  - Data provider over (capacity, stock, reserved, qty) at the boundaries, including stock < reserved, so availability is 0.
  - A stock-reduced-after-cart-add submit returns 409 with no reserve change.

### B2 — The free-shipping assumption is not enforced; the amount depends on shop default policy and client header
- Contract: travel products need an explicit isolated FREE ShippingPolicy, because a null policy falls back to the shop default.
- That fallback is confirmed in the actual code at `sirsoft-ecommerce/src/Services/OrderCalculationService.php:2001-2014`.
- `CartService::getCartWithCalculation` also takes the country from the request (`X-Shipping-Country` > saved > GeoIP), per `CartService.php:169` (`ResolveShippingCountry::getCountry()`).
- `InquiryService.php:66` stores `total_amount = totals.final_amount` (which includes shipping). Nothing checks `total_shipping === 0` or the product's `shipping_policy_id`.
- In the probe, `shipping_policy_id=NULL` and shipping was 0 only because the fixture DB has no default policy. A real install with a paid default policy would silently add shipping to a "test inquiry". A client header could also change the amount.
- Safe correction:
  - Fail `calculation_changed`/`unsupported_cart` unless every selected product's `shipping_policy_id` is the travel FREE policy (provisioned by the domain child), AND `summary.total_shipping === 0`, AND there are no coupon/points effects.
  - Alternatively, pin the calculation country server-side.
- Tests:
  - A default paid policy exists while the travel product policy is null → submit is rejected.
  - Sending `X-Shipping-Country` does not change the stored total.

### B3 — Native commerce paths can bypass the travel workflow
- Travel cart lines are real `ecommerce_carts` rows. The workflow re-validates at submit time, but nothing in this commit stops the native shop checkout from turning them into a **real order**: stock decrement, payment, PG.
- The available hook points are `sirsoft-ecommerce.order.before_create` (`OrderProcessingService.php:112`) and `order.filter_create_data` (:619).
- The native `PATCH` cart quantity route can also exceed travel availability (this only matters until submit).
- Contract: "No live checkout/order/payment"; "never decrement commerce stock for a test inquiry".
- Owner: integration or domain. Add a sync guard listener that rejects orders and checkout containing travel-mapped options, and hide travel products from shop listing/checkout if required.
- Tests: native checkout with a travel cart line → rejected, no order rows, stock unchanged.

## Contract gaps (not blockers for this child, but required before product PASS)

- **G1 Snapshot/audit (parent-owned obligations, not completed):**
  - There is no `calculation_snapshot` JSON. Only `total_amount`, `currency_code` and per-item `unit_price`/`line_total` are stored (`InquiryService.php:61-69`).
  - There are no `InquiryEvent` actor rows. `applyTransition` (`InquiryService.php:140-175`) writes no audit, and the admin `actor` is not passed to the repository.
  - When added, they must be written inside the same transactions (submit at :34-73; cancel/transition at :94-138).
- **G2 Admin note-only update is silently dropped.** On same status, `applyTransition` returns early (`InquiryService.php:143-146`), but the admin layout sends `{status: current, admin_note}` and shows a "saved" toast.
  - Decide: either allow a note update without a status change (with an audit event), or return 409/422. Add a test.
- **G3 Idempotency details:**
  - Same-key replay returns HTTP 201 (`Api/InquiryController.php:33`). Prefer 200 for a replay, or document 201.
  - The payload hash treats `contact.phone: null` and an absent phone as different payloads (`InquiryService.php:29-32`), so a legitimate client retry that normalizes differently gets 409. Normalize by dropping nulls and trimming before hashing.
  - The concurrent same-key race relies on the user-row lock plus a UNIQUE(user_id,idempotency_key) index that the domain migration owns. It is NOT_RUN on MySQL.
- **G4 Departure deletion makes cancel/decline permanently impossible** (`InquiryService.php:159-162` returns `capacity_inconsistent`).
  - The domain must forbid deleting departures/options referenced by inquiries; options are already in SCORE.
  - Option stock edits through native commerce admin can drop stock below `reserved`, so a recheck or guard is needed. That is domain/integration scope.
- **G5 Admin scope depends on undeclared permission metadata.** `PermissionHelper::checkScopeAccess` returns true when `resource_route_key`/`owner_key` are not declared (`app/Helpers/PermissionHelper.php` ~:169-175).
  - The test registers them itself. `module.php` must declare `raonslab-travel_lab.inquiries.{read,update}` with `owner_key=user_id` and resource key `inquiry`, or self-scope silently becomes global.
- **G6 Missing translations:** no `src/lang/{ko,en}/workflow.php` exists in the commit, so every `raonslab-travel_lab::workflow.*` error renders as a raw key.
- **G7 Repo rules:**
  - `WorkflowInquiryRepository.php:39` uses `->paginate($perPage, ['*'], …)`, which AGENTS.md "목록 조회 컬럼 프루닝" forbids. Select the listed columns.
  - `commerce_rejected` puts `$exception->getMessage()` into `errors.detail` (`TravelCartService.php:224-226`). The domain exception message is already translated; acceptable for an authenticated owner, but keep the key-based message.
- **G8 Strict unknown-field rejection** (`WorkflowRequest.php:23-31`) applies to query strings on GET/DELETE too. Any extra query param (e.g. the admin list `status` filter) returns 422. This is intentional, but the UI must match (see I3).
- **G9 Registration absent:** routes, bindings, models, enum, migrations, permissions, provider, `api.php` require and lang are all missing. Tests wire them manually (`WorkflowTestCase.php:80-104`). This is lead integration work.

Verified OK in this commit (fixture level):
- **Locks:** deterministic lock order, ID ascending: user → inquiry(key) → carts → departures → products → options → travel_products (`WorkflowCartRepository.php:24-73`).
- **Totals:** the calculation-count check guards skipped items (`TravelCartService.php:115-129`). The client never sends money; any money field is rejected (unknown field).
- **Transition matrix:**
  - Matches SCORE: TEST_ACCEPTED→CANCELLED allowed; DECLINED/CANCELLED terminal.
  - Release happens exactly once, because replaying the same state is a no-op.
  - Cart removal does not touch `reserved` or commerce stock.
- **Owner and admin access:**
  - Foreign inquiry/cart → 404.
  - Admin requires `isAdmin()` + permission (service) plus route `permission:` middleware.
- **No external calls:** no Mail/Http/Notification/order/payment calls in the workflow source.

## Interoperability with support/admin layouts @ 2c0e3876

Checked by the read-only subagent and spot-checked by the reviewer.

- **I1 Status casing.** The layouts compare uppercase `'TEST_INQUIRY'…` (`admin_travel_lab_inquiry_detail.json:528-562`, list :256-398).
  - The workflow side assumes backing values `'test_inquiry'…` (`tests/Fixtures/DomainContract.php:14-18`, `docs/api/workflow.md`) and serializes `status->value` (`InquiryResource.php:20`).
  - Neither commit contains the real `InquiryStatus`. The lead must fix one canonical form (recommend lowercase backing values in the API, with labels via `$t:`). Layout filters, PATCH bodies and tests must then use the backing value; otherwise `Rule::enum` returns 422.
- **I2 `allowed_transitions`** (detail :566,:591) does not exist in the Resource. Transition buttons never render.
  - Add `allowed_transitions` computed from the single service transition table. Expose the table as `InquiryStatus::allowedNext()` so service and resource cannot diverge.
- **I3 List filter `status` query** (list :28-30) → 422 from `ListInquiriesRequest` plus strict unknown-field rejection. Either add a validated `status` filter to the request, repository and service, or remove it from the layout.
- **I4 Flattened summary fields** are absent from the Resource: `reference`, `product_name`, `departure_label`, `party_size`, `requester_name`, `message`, `product_code`. The Resource only has `items[]` (with `product_name` as a locale array), `contact`, and `user_id`.
  - Pagination is `data.pagination.*` (`app/Http/Resources/BaseApiCollection.php:95-115`), not `data.meta.*`, so the pager shows 1/1.
  - The layout test mocks the assumed shape (`admin-travel-lab-layouts.test.tsx:185-195`).
- **I5 Admin catalog endpoints** `GET /admin/catalog` and `PATCH /admin/departures/{id}` do not exist in either commit.
  - The layout uses `reserved_count`, `status` OPEN/CLOSED/HIDDEN and `label`. The fixture/SCORE uses `reserved` and `is_active`.
  - Departure `id` (not `product_option_id`) is the correct key for the reservation semantics.
- **Support notification isolation:**
  - Scoped to the three travel board slugs via the `sirsoft-board.notification.extract_data` filter (`type=>'filter'`). Other boards pass through unchanged.
  - Gap: `report_received_admin` (args `[$report]`) is not matched, so travel-board report notifications fail open when `notify_admin_on_report` is on. No test covers this.
  - Unknown support fields are silently ignored (not rejected), unlike the workflow requests. No external calls were found.

## Required re-validation after integration (fixed integrated SHA)

1. Real MySQL/MariaDB:
   - Two concurrent submits for the last seat → one 201, one 409, `reserved == capacity`.
   - Same user + same key in parallel → one inquiry, one reserve.
   - Cancel vs decline in parallel → one release.
   - Option-stock edit during submit.
2. B1/B2/B3 regression tests listed above, run against real domain models, migrations and enum (fixture removed).
3. Admin layout ↔ API contract test using the real Resource output (not a hand-written mock), for status values, `allowed_transitions`, pagination path and filters.
4. Route/permission registration test via the real module provider (`route:list` names, `permission:` middleware, scope metadata).
