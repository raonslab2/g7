# W04F-01 idempotency gap repair — nonauthor source review

Decision: **PASS_BOUNDED_SOURCE for the current top-level HTTP path**. No new P1/P2 source finding. **W04F-01 remains OPEN** until actual fixed-SHA MySQL contention/replay recheck. This reviewer did not implement the repair, execute SQLite/MySQL tests, call HTTP, touch APP/TEST/services or change product/Git files. Only this report was written. The optional two-case SQLite rerun was not needed for this bounded source decision and was omitted under the reported host load; author assertions below are not independently executed results.

## Source correction and retained contracts

The entire production diff changes the submit lookup from `byKey($userId,$key,true)` to the existing default-nonlocking `byKey($userId,$key)` and updates its explanatory comment. The first service transaction query remains `requireUser($userId,true)`: actual WorkflowCartRepository uses `User::whereKey($userId)->lockForUpdate()->first()`, selecting an existing user primary-key row. Current User source has no default eager-loaded relations/retrieved listener doing an earlier consistent read; its explicit boot callback is creation-only UUID setup.

Current InquiryController::store directly invokes submit; the service starts its own `DB::transaction(...,3)`. Source search found this sole production caller and no enclosing application/travel HTTP middleware transaction. For that top-level MySQL REPEATABLE READ path, the initial user locking read is a current read; the first consistent inquiry read follows the acquired user lock. A same-owner waiter therefore creates its new read snapshot after the preceding owner transaction commits. Same-user submit/cancel serialization remains on that existing user row. The missing-key lookup no longer requests an absent unique-index gap lock before shared departure acquisition, removing the identified edge in the cross-member cycle.

Unique `(user_id,idempotency_key)` schema protection, payload/contact/cart normalization and conflict409 remain. Lookup is owner-filtered and eager loads immutable items/events; replay returns the original inquiry/status/prices without re-reading removed carts, repricing or reserving again. Cart/departure/product/option/shipping policy/country-setting locking, native price calculation, conditional reserve/release, status permissions, transactional rollback and three retries are unchanged. Cancel still locks owner user→inquiry; admin transitions still lock the inquiry; terminal repeat transitions release no capacity a second time. Concurrent admin state changes can be observed according to the consistent read's snapshot; this patch does not promise linearizable replay status beyond normal subsequent requery.

**Compatibility boundary:** a caller entering submit inside an already-open REPEATABLE READ transaction with an earlier consistent-read snapshot can retain that older snapshot after acquiring the user lock. A nested Laravel savepoint does not make it fresh. The current shipped HTTP caller does not do this; no nested production caller was found. The unchanged public method signature must not be advertised as preserving replay behavior for arbitrary preexisting outer-snapshot callers. Such an integration needs its own contract/guard/design and tests. This is an explicit residual compatibility limitation, not a newly approved caller restriction or universal database-isolation change.

## Meaningful regression scope

The two author regressions use actual models/repositories/native pricing and real in-memory SQLite effects. Temporary global scopes attach `beforeQuery` callbacks to the builders actually executed; they do not mock the lookup or merely inspect service source. Native `getAllGlobalScopes/setAllGlobalScopes` use the whole shared model scope registry, which the test restores in `finally`.

Nine models are observed: User, Inquiry, Cart, Departure, Product, ProductOption, ShippingPolicy, ShippingPolicyCountrySetting and TravelProduct. The callback records lock property, transaction depth and MySqlGrammar compilation. MySQL compilation is source/query-shape evidence only: SQLite remains the executing connection and cannot reproduce InnoDB next-key locks or concurrent deadlocks. Replay's asserted two **observed** reads are User/Inquiry; eager-loaded InquiryItem/InquiryEvent queries are outside that observer, so this is not a total-SQL-count claim.

The missing-key case verifies current native price changes,35,400 total for two travellers, authoritative locks in one transaction, capacity/cart/inquiry/event effects and no native orders/payments. The replay case verifies same textual key for distinct owners, cancellation/normalized replay retaining the original12,000 snapshot, changed-payload409 and once-only release, with no observed pricing/cart/departure reads. These assertions meaningfully cover compatibility with native workflow effects while leaving actual concurrency unproven.

Author report records fail-first2tests/33assertions/2failures; repaired2/70; broader68/1,030; Pint/whitespace PASS. They were read as author evidence, **not rerun here**, and the overlapping runs are not additive. Actual MySQL same-gap/different-gap last-seat, same-user/same-key overlap and post-integration retry/owner/state checks remain **NOT_RUN on the repaired source**.

## Independent security evidence intake

Original independent evidence commit `d97012094bd296edad5a6fb22a5b8fb2cb425e9d`, tree `ccc93bf63fd52163dc3a80638b316b1485564802`, is the W04 security review at product target `1052e3fb4bc4cccabb51b8c538116c78655f345b`. The shorthand `d970` also matches an unrelated older commit; this review uses the full resolved review commit. Original review report remains unchanged at `dfb75a10bc2b39fcb7d52587e254c9140ada08f4a2820df7184d3143e29c03fd`.

`docs/symphony/w04-final-security/evidence/execution-manifest.json` hash `4e7a039dec5406fd3daee99568b428ce02c36df82c09161a5a1c6a4e18d059b7` binds43 files; all43 independently match, plus the manifest itself makes the44-file delivery. This is hash/intake verification, not a rerun of the independent commands or canonical Validation.

Preserve the review's raw R1 FAIL, correlated server deadlock counter4→5,45-second idle control flat5, different-gap repeat flat, and predicted same-gap R1c5→6. Recorded results remained201/409 with reserved1=capacity1. These bounded attribution records do not contain an engine deadlock graph and must not become “MySQL no-deadlock PASS” after a SQLite repair. Original W04F-02 native FileStore increment observation remains open/outside this fix; fixture10/2,032 and live600/601 results keep their original scopes. Probe parser/display/version-drift limitations and raw NOT_RUN/OBSERVED records are not waived or rewritten.

All44 delivered text files passed the bounded email/Bearer/private-key/raw-INSERT-values/password-literal/UUID pattern scan, and delivered JSON had no nonempty email/password/contact/access-token/authorization fields. Only file/category findings were permitted in outputs; no sensitive values were printed. No matches were found. Private payload-state files excluded by the original delivery were not read or restored. This is a minimal public evidence hygiene check, not an exhaustive credential audit or proof of runtime privacy.

## Fixed source attribution

The service/test hashes below were checked at intake and again at completion, unchanged. This source review targets the owned frozen diff, not a newly published commit.

| Input | SHA-256 |
| --- | --- |
| Original InquiryService at1052 | `d8330a0cae7bd4d0a5552118e0c88189cf1ffc5a832bc909f81919b4c5b8f9a2` |
| Repaired `src/Services/InquiryService.php` | `17c969a752820487c062b97e67c2cf88d0c03160e42346075dc5060ee2e38ad8` |
| `tests/Feature/InquiryIdempotencyGapRegressionTest.php` | `e4b3373ddc42e329b311ddfb3b66a2aa7898417ed3f5206b5bece114396795c2` |
| Unchanged WorkflowCartRepository | `d1aee869f15f1b81e1f0145086b1772be71e3b8a228d4dd2be38231b386aef8e` |
| Unchanged WorkflowInquiryRepository | `072442036d221b26b044983edf8f3821fee4c051444acdf7ddea82ce52911000` |
| Unchanged WorkflowInquiryRepositoryInterface | `0cb01dabd570b511b517e15be7edb327f4a80312913b65c1a55e5a676a2d0397` |
| Actual InquiryController | `7c7138ac1443102e6f4bbb9831440b3ce953e36a275951c88a7b4866313682f2` |
| `docs/symphony/W04_IDEMPOTENCY_GAP_REPAIR.md` | `430734e40a9be1fac781d806fcc454564ce687c9eff752861f88a3fdcec65af5` |

Source/test paths are under `modules/_bundled/raonslab-travel_lab`. Two-file full-path digest `d810102800f27c6d63457654fd1c2b9ef86e8134b994c1c89ab3555b6b27f439` uses Python `sha256(json.dumps({path:sha256(bytes)},sort_keys=True).encode())`.

Lead must publish/pin the repaired SHA, bind installed source, run the actual independent MySQL overlap controls including replay/cancel/release, and separately address remaining FileStore/installation/runtime gates. Existing target1052 evidence does not validate this new implementation. This review changes no source, DB, environment, service, original evidence or Git state.
