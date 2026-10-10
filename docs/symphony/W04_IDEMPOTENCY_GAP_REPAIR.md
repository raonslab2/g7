# W04F-01 idempotency gap repair — author evidence

The submit path now reads the idempotency key without `FOR UPDATE`, after obtaining the existing user row lock. This removes the absent-key locking query from the observed cross-member deadlock cycle. **Author SQLite regression PASS; fixed-SHA MySQL contention verification NOT_RUN. W04F-01 remains OPEN pending independent recheck.**

## Intake and ownership

- Work: `work-20261009-g7-symphony-max-child-c7ae42d1`; parent Request `req_81ac33cac94046b9a2249cd14c0d00ba`; native author `/root/w03_commerce_guards`.
- Root HEAD at intake inspection: `fd649b1cf6436fd9c4887b3b5e5c459f74b0de02`. The original service bytes equal security target `1052e3fb4bc4cccabb51b8c538116c78655f345b`.
- Read-only intake: independent Request `req_0683bc352fac4373b748cee64dedc53a`, `docs/symphony/W04_SECURITY_FINAL.md`, SHA256 `dfb75a10bc2b39fcb7d52587e254c9140ada08f4a2820df7184d3143e29c03fd`.
- Owned edits: `src/Services/InquiryService.php`, new `tests/Feature/InquiryIdempotencyGapRegressionTest.php` under the bundled travel module, and this report. No repository/interface/schema/common route edits were necessary. Lead owns Git delivery, version decisions and runtime integration.

## Original negative evidence remains valid

The independent reviewer observed two different members blocked on one departure while missing keys occupied the same unique-index gap. `byKey(..., true)` can acquire that gap lock before the departure lock. The departure winner's inquiry INSERT then waits for the other member's gap lock, while that member waits for the departure.

The report records the server-wide deadlock counter **4→5** in R1; a 45-second idle control stayed at 5. A deliberately different-gap repeat stayed flat, while the predicted same-gap R1c incremented **5→6** between the blocked-barrier sample and completed responses. Outcomes remained correct: 201/409 and reserved capacity 1. These are correlated, predicted reproductions with distinct DB connections and a sustained barrier, not an engine-level deadlock graph: the scoped account could not read InnoDB lock diagnostics. The existing three transaction attempts masked those observed deadlocks; repeated deadlocks beyond that budget could expose a 5xx. This author did not repeat any live race or alter the original negative evidence.

## Minimal correction and transaction boundary

`InquiryService::submit` retains `requireUser($userId, true)` as its first database read. `WorkflowCartRepository::user` reads only that user's existing primary-key row with `FOR UPDATE`; the current User model has no default eager-loaded relations or retrieved listener making an earlier consistent read. The next read is now `byKey($userId, $key)` with its existing default `lock=false`.

The current native HTTP controller directly invokes this service, which starts its own top-level transaction. Under MySQL REPEATABLE READ, its first consistent read occurs **after** the user lock is obtained, so a same-user submit waiting for the preceding winner sees that committed inquiry. Same-user submit/cancel serialization remains on the user row. Existing unique `(user_id, idempotency_key)` protection remains unchanged. Different users no longer acquire an absent-key gap lock through this lookup.

All other locking reads, reserve/release conditional updates, native price calculation, contact/cart normalization and payload hash, immutable snapshots, owner filtering, transitions, three-attempt transaction retry and failure rollback remain unchanged. Replay returns the stored inquiry without reading deleted carts, recalculating current prices or reserving again. Admin transitions still lock the inquiry independently; replay remains a read operation.

**Boundary:** this reasoning covers the present top-level HTTP transaction. A new caller that invokes submit inside an already-open REPEATABLE READ transaction with an earlier consistent-read snapshot would require a separate contract/design review. The patch does not promise snapshot freshness for such an unsupported enclosing transaction or globally change database isolation. The independent fixed-SHA same-user replay race must still verify the deployed path.

## Executed verification

Commands ran in the parent worktree with the canonical module bootstrap, PHP 8.3.6 / PHPUnit 11.5.56. The bootstrap pins SQLite `:memory:`, null search, array mail/cache and canonical bundled source paths; no APP/TEST MySQL schema or service was accessed.

| Command / phase | Result | Evidence scope |
| --- | --- | --- |
| `php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter=InquiryIdempotencyGapRegressionTest` before repair | **FAIL: 2 tests / 33 assertions / 2 failures**, 2.446 s, 62.50 MB | Both real inquiry lookup builders had `lock=true`; the missing-key case failed at the explicit gap-lock assertion and the replay case failed at its nonlocking lookup assertion. No MySQL deadlock was simulated or claimed. |
| Same command after repair | **PASS: 2 / 70**, 2.849 s, 62.50 MB | Actual model/repository/native price services and transaction effects; executed builder inspection. |
| `php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter='InquiryIdempotencyGapRegressionTest\|TravelWorkflowTest\|TravelWorkflowRegressionTest'` | **PASS: 68 / 1030**, 55.084 s, 157.00 MB | Includes current-price snapshots, owner denial, capacity ceilings, rollback/retry, HTTP tamper/date checks, normalization, state/release and new lookup regressions. |
| `php vendor/bin/pint --test modules/_bundled/raonslab-travel_lab/src/Services/InquiryService.php modules/_bundled/raonslab-travel_lab/tests/Feature/InquiryIdempotencyGapRegressionTest.php` | **PASS** | Initial unused test import was removed; no behavior change. |
| Scoped `git diff --check` | **PASS** | Owned service/test whitespace only. |

The new observer adds temporary Eloquent global scopes, records `beforeQuery` builders actually executed by the real repositories, and restores the previous scope registry in `finally`. It observes the user lock before the inquiry lookup, one transaction, and remaining cart/departure/product/option/shipping-policy/country-setting locks. Native `MySqlGrammar` compiles those observed builders solely to check `FOR UPDATE` presence; the connection and execution stay SQLite. This checks the locking query emitted by the service, **not InnoDB's runtime lock behavior**.

The first case verifies a current product-price change plus option adjustment totals 35,400 for two travellers, capacity reserved twice, selected cart removed, one inquiry/event and zero native orders/payments. The second uses the same key for two distinct owners, verifies separate inquiries, then cancellation and normalized replay return the original cancelled inquiry/12,000 snapshot with exactly one release; changed payload still returns 409 and no additional pricing, cart or departure reads occur on replay.

## Frozen source pins

| Path (relative to bundled travel module unless stated) | SHA256 |
| --- | --- |
| `src/Services/InquiryService.php` original / security target | `d8330a0cae7bd4d0a5552118e0c88189cf1ffc5a832bc909f81919b4c5b8f9a2` |
| `src/Services/InquiryService.php` repaired | `17c969a752820487c062b97e67c2cf88d0c03160e42346075dc5060ee2e38ad8` |
| `tests/Feature/InquiryIdempotencyGapRegressionTest.php` | `e4b3373ddc42e329b311ddfb3b66a2aa7898417ed3f5206b5bece114396795c2` |
| `src/Repositories/WorkflowInquiryRepository.php` unchanged | `072442036d221b26b044983edf8f3821fee4c051444acdf7ddea82ce52911000` |
| `src/Repositories/Contracts/WorkflowInquiryRepositoryInterface.php` unchanged | `0cb01dabd570b511b517e15be7edb327f4a80312913b65c1a55e5a676a2d0397` |

## Pending independent checks / residual findings

- **NOT_RUN here:** actual MySQL same-gap/different-gap last-seat contention, sampled deadlock-counter control, same-user/same-key overlapping replay, post-integration browser/runtime checks and formal Validation/CI. Lead must publish a new fixed SHA, bind installed source to it and assign the nonauthor recheck; prior 1052 results cannot close this repair.
- **Unchanged residual:** W04F-02 native FileStore non-atomic rate-limit increments, and privacy/search external-engine coverage remain outside this repair. Neither is marked fixed.
- No Git staging/commit/push, environment changes, schema writes outside in-memory SQLite, credentials/account rotation, fixture cleanup on shared databases, source installation, service restart or preview mutation was performed by this native author.
