# W00 internal fixed-revision review

Target: `6efbf0a57dfb75c0fcea6944407f136f5366e273`
Scope: committed `SCORE.md`, `CAPACITY.md`, `WAVE_STATUS.md`; reference document checked for contract alignment only.
Source baseline: `6853f40d58acbf53a2f29cbb9dd422cc439047a9`
Reviewer: native `reference_runtime`, Request `req_81ac33cac94046b9a2249cd14c0d00ba`
Original status: **CHANGES_REQUIRED for W00 contract/evidence completeness; product validation NOT_RUN.**
Latest scoped document recheck: **W00 checkpoint acceptable with tracked limits** at `8822971c994955abd196a0d3936c32b4f54c1e51`; see final addendum. This does not approve all files in that commit or establish product PASS.

The reviewer did not implement SCORE/CAPACITY/WAVE_STATUS or travel code. The reviewer authored REFERENCE.md, so this review cannot independently approve that file or the entire four-document target. Another nonauthor must review REFERENCE. This is an internal review, not official AgentOpt Validation. No code, contract file, database, service, publication or deployment was changed by this review.

## Findings

| ID / priority | Fixed-target location | Finding and practical effect | Required resolution / verification |
| --- | --- | --- | --- |
| W00-R01 / P1 | SCORE, shared model contract and transaction paragraph | Travel defines `capacity-reserved` availability while real CartService independently validates `ProductOption.stock_quantity`. The contract does not say which owner maintains or reconciles option stock when departure capacity, test allocation or commerce admin options change. Divergence can produce visible seats that cart rejects, or expose a different limit between cart and inquiry. | Freeze an explicit rule: authoritative travel allocation, required ecommerce stock relationship/effective limit, update ownership, and behavior when stock/capacity falls below reservations. Test cart add/update + inquiry + decline/cancel + capacity edit + commerce option edit against both quantities. Do not bypass CartService's validation to make tests pass. This is a contract gap, not a claim that pending code already has this defect. |
| W00-R02 / P1 | WAVE_STATUS final paragraph; CAPACITY implementation ledger | The old live coordinator is correctly marked UNKNOWN, but four implementation children have already started and the document defers conflict action until a conflict appears. Scoped absence of branches/PRs and a deleted receipt cannot establish absence of a prior active coordinator. The user's no-duplicate-coordinator condition remains unverified. | Preserve the uncertainty in completion status. Lead must record bounded canonical historical lookup/handoff outcome or quarantine these scoped results as inheritance/verification work until collision status is resolved. Do not claim duplicate-free execution or silently integrate competing results. If no official handoff route exists, report the missing route/action once while continuing genuinely conflict-free work. No prior Request cancellation is required or authorized. |
| W00-R03 / P2 | SCORE, inquiry states | `TEST_ACCEPTED` appears in the state vocabulary, but its outgoing cancellation semantics and public/staff permission boundary remain an integration obligation rather than a finalized contract. Independent transaction and UI owners could implement incompatible cancel buttons/409 handling or allocation release. | Define whether accepted test requests can be cancelled, by whom, and through which existing endpoint; define allocation retention/release and repeated-terminal behavior. Add a full transition/role matrix and test exactly-once release. |
| W00-R04 / P2 | SCORE, shared schema and integration obligations | Full calculation snapshot and actor transition audit are required in prose, but no table/column or named implementation owner is assigned. InquiryItem price snapshots alone do not preserve ecommerce's summary/promotions/validation output. | Lead assigns durable calculation snapshot and transition history storage/owner. Verify immutable server result, actor/timestamp/from/to information, restart persistence and read permission. A transaction must reject returned calculation validation errors and missing prepared items. |
| W00-R05 / P2 | All four committed W00 files | The fixed target lists researched commerce hazards and runtime/capacity assertions but lacks original-source excerpts/blob bindings for those claims. Therefore a reviewer cannot reproduce the entire evidence pack from this fixed revision alone. Capacity catalog/backend/source limits are parent-observed, not independently corroborated by this reviewer. | Include reviewed INHERITANCE/WORK_ORDER_VALIDATION and sanitized canonical tool evidence at the next meaningful fixed checkpoint. Pin source blobs/excerpts for stock/pricing/options/shipping/cart ownership, and tie catalog/state snapshots to observed UTC timestamps. Keep quota/account/global-PC UNKNOWN labels. Do not publish credentials or private order contents. |
| W00-R06 / P2 | SCORE visitor paths vs REFERENCE TR-LIST-001 | SCORE final contract uses `/travel/search`; REFERENCE proposal uses `/travel/products`. Both are currently described as live-list entry paths, risking split navigation/tests. | State that SCORE is the canonical finalized contract and update the proposal or provide a tested intentional alias. UI, browser scenarios and install package must use one coherent route contract. REFERENCE independently needs another reviewer. |

## Source cross-check and excerpts

Read original G7 source rather than relying on RAG/search summaries. The following blob IDs are from the exact input source revision above.

| Source | Blob | Checked consequence |
| --- | --- | --- |
| `modules/_bundled/sirsoft-ecommerce/src/Services/CartService.php` | `255eca44312054c534badd924a9df1fd50eeff6f` | Real cart ownership is user/cart-key based; stock and purchase limits are enforced by the ecommerce service. |
| `modules/_bundled/sirsoft-ecommerce/src/Services/ProductService.php` | `7c927d5ee92bfebc7b4e9435efc7424a4e7ea1ea` | Option synchronization preserves submitted existing IDs and deletes omitted IDs; deletion guard checks ecommerce order history, not new travel inquiry history. |
| `modules/_bundled/sirsoft-ecommerce/src/Services/ShippingPolicyResolver.php` | `72fee26bc3d7666e16ab1949c936d42c036be58e` | Missing product shipping policy resolves to default policy, confirming the explicit free-policy hazard. |
| `modules/_bundled/sirsoft-ecommerce/src/Services/OrderCalculationService.php` | `7f6a638509fe79ed4ebc317db7c283c14a7bec86` | Missing product option/product is skipped while preparing calculation items; adapter must verify selected/result coverage. |
| `app/Providers/ModuleRouteServiceProvider.php` | `d134a558bd5b27b7f1ed2957e9dfac21ec2b3da6` | Automatic API prefix and route-name prefix match SCORE. |

Relevant original excerpts:

```php
// CartService::validateStock
$totalQuantity = $currentQuantity + $requestedQuantity;
$availableStock = $productOption->stock_quantity ?? 0;
if ($availableStock <= 0 || $totalQuantity > $availableStock) {
    // throws CartUnavailableException
}

// ProductService::syncOptions / validateOptionsDeletion
$deleteIds = array_diff($existingIds, $newIds);
$this->validateOptionsDeletion($deleteIds);
$product->options()->whereIn('id', $deleteIds)->delete();
$hasOrders = $this->orderOptionRepository->existsByProductOptionIds($optionIds);

// OrderCalculationService::prepareItems
if (! $productOption || ! $productOption->product) {
    continue;
}

// ShippingPolicyResolver::resolveForProduct
$policy = $product->shippingPolicy;
if ($policy !== null) {
    return $policy;
}
return $this->getDefaultPolicy();

// ModuleRouteServiceProvider
Route::prefix('api/modules/'.$moduleName)
    ->name('api.modules.'.$moduleName.'.')
    ->middleware('api')
    ->group($routes['api']);
```

Other checks: input ecommerce manifest 1.2.1, board/page 1.1.2 and core default 7.0.11 match SCORE minimum dependencies. `ExtensionManager::directoryToNamespace` maps underscore word boundaries to PascalCase, so `raonslab-travel_lab` → `Modules\Raonslab\TravelLab` is correct. Option runtime price uses the real `getSellingPrice()` contract (base product price plus option adjustment); catalog/seed tests should compare against this method rather than assume a stored option field is authoritative.

## Truth and boundary assessment

- **Consistent:** one CODEX account + one CLAUDE account is distinguished from configured Provider admission capacity, official children and native agents. Four child Requests are identified; their RUNNING states are not labeled completion, concurrency benchmark or PASS. Central logical placement is distinguished from offline remote PCs. Hidden child-native usage, exact simultaneous sampling and quotas remain UNKNOWN.
- **Partially corroborated:** this reviewer observed native concurrency metadata permitting four total native agents and worked inside the same Request. Historical catalog/backend/MAX_ACTIVE/MAX_TOTAL, child placement/state and host metrics are parent-reported. A single reviewer catalog call returned `native control scope rejected`; no shell/API/DB/credential workaround was attempted. This failure does not invalidate the parent's canonical observation, but independent corroboration is absent.
- **Consistent:** Spring remains a separate named Request, not a G7 dependency; no Spring source edits or cancellation are instructed. Native collaborators do not publish independently. No scheduler/capacity workaround is specified.
- **Consistent:** original RAON assets/synthetic data, isolated travel module/template/runtime and explicit test transaction meaning are distinguished from existing product/production data. Unknown external main autodeployment is retained rather than asserted absent; branch integration can preserve approval separation.
- **Consistent:** baseline build and 56 frontend tests are explicitly baseline checks, not travel functionality, official validation, security/concurrency or E2E PASS. Pending independent implementation review/browser tests bind a future fixed SHA.
- **Open:** canonical prior-live coordination and evidence-pack completeness block a complete W00 proof. W00 remains an executable implementation start, not the user's final completion conditions.

## Ongoing files outside this fixed review

After findings were sent, the lead amended working-tree SCORE with accepted-test cancellation, declined/cancelled terminal behavior, exactly-once simulated release, stock-managed lab products and conservative `max(0,min(capacity,option.stock_quantity)-reserved)` availability. The diff also requires both-domain lock/recheck, prevents capacity reduction below reservations, requires stock for capacity increases, and states that test inquiry/cart removal does not decrement ecommerce stock. This resolves the documented direction of W00-R01/W00-R03, but the changes are **outside the fixed review target and PENDING next-revision nonauthor review plus implementation tests**. Full calculation/actor audit remains a named integration obligation requiring storage/owner reconciliation. No finding is marked implementation PASS here.

Working-tree `INHERITANCE.md` and `WORK_ORDER_VALIDATION.md` were read only as integration leads. They are absent from target commit `6efbf0a57dfb75c0fcea6944407f136f5366e273` and receive no fixed-SHA approval here. They include original-source blob references, private canonical order identity, source validation PASS/89-document output and important scope limits. The next checkpoint should include them after nonauthor review and public-data sanitization, reconcile WAVE_STATUS's still-pending canonical-validation entry, and retain historical coordinator UNKNOWN.

No runtime package or child implementation was present in this review target. PHP/MySQL/browser/rollback/restart/transaction tests are **NOT_RUN by this reviewer**. Lead should resolve contract findings now, then obtain official fixed-SHA contract/security and browser/regression validation against the integrated executable revision.

## Final addendum — fixed revision 8822971

Recheck target: `8822971c994955abd196a0d3936c32b4f54c1e51`. Scope remains SCORE/CAPACITY/WAVE_STATUS and previous finding resolution; no runtime code/product approval or independent REFERENCE approval is given. An uncommitted parent scenario matrix is outside this target and was not evaluated.

| Finding | Fixed-revision disposition |
| --- | --- |
| W00-R01 | Contract direction RESOLVED: conservative stock/capacity bound, stock-managed seed, both-domain lock/recheck, capacity floor and no commerce-stock decrement for test allocation are explicit. Implementation tests remain NOT_RUN. Small wording correction tracked below. |
| W00-R02 | OPEN historical truth, appropriately quarantined: WAVE_STATUS records one asynchronous prior-Request-ID/handoff query, no duplicate-free assertion, and no main/production integration until coordination is resolved. Scoped checkpoint preservation is not final integration approval. |
| W00-R03 | Contract direction RESOLVED: TEST_ACCEPTED can transition to CANCELLED, owner cancellation includes accepted test requests, declined/cancelled are terminal and simulated release must occur once. Role/transition implementation matrix still requires tests. |
| W00-R04 | Contract assignment RESOLVED: parent integration explicitly owns separate migration/model/repository/workflow tests; Inquiry.calculation_snapshot stores complete server result and travel_lab_inquiry_events records actor/from/to/note/time append-only in the same transaction. Implementation/persistence/access checks remain pending. |
| W00-R05 | Evidence-existence gap RESOLVED for checkpoint: INHERITANCE blob `c9f95f318800c0a71d39dfa9695fb9b5a7b022cf` and WORK_ORDER_VALIDATION blob `cb476bd4da7f1f1d483a61b90ecc08b0a22bd3c9` are committed. Receipt explicitly limits its PASS to canonical document/tree validation; prior coordinator absence/product Validation remain unestablished. Catalog/resource observations still retain parent-observed provenance and UNKNOWN limits. This reviewer did not rerun the canonical validator. |
| W00-R06 | Route alignment observed: lead-selected `/travel/search` is now the list entry in the recorded REFERENCE diff and SCORE; product detail retains `/travel/products/:id`. This resolves cross-document route mismatch without approving the reference evidence authored by this reviewer. |

Residual wording: SCORE's Departure table still says `Availability = capacity-reserved`, while its inventory integration paragraph correctly specifies effective customer availability as `max(0,min(capacity,option.stock_quantity)-reserved)`. Label the former **raw simulated capacity** and the latter **effective customer availability** before implementation contract consumption/review. The detailed inventory rule is authoritative for this checkpoint; no adapter may use the unbounded table shorthand for customer availability.

Lead subsequently reported that this table wording was corrected in the working tree. That wording-only correction is outside revision `8822971`; carry it into the final W00 publication head and verify the recorded diff. No unchanged implementation evidence was rerun.

CAPACITY adds a later host load/memory observation and defers an additional broad build batch on measured host load rather than limiting Providers to two lanes. Official RUNNING remains the last timestamped canonical observation, not newly polled state. Native activity/account/global/PC distinctions and Spring separation remain correctly scoped. WAVE_STATUS distinguishes runtime preflight, baseline regression and future travel journey/official validation.

Decision: the corrected **three-document W00 contract/evidence checkpoint may be preserved and published to its isolated reusable travel branch/PR under the lead's existing Git authority**, subject to the tracked shorthand correction and separate nonauthor review of REFERENCE/runtime/other new files. This preserves scoped work for canonical child consumption and recovery; it does not permit main/production integration while W00-R02 is unresolved and does not waive later independent fixed-SHA product gates. Product tests, full customer/admin journey, browser/security/concurrency/restart validation and integration retest are **NOT_RUN by this reviewer**.
