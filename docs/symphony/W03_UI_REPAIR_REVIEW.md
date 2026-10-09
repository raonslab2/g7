# W03 independent UI repair review

Final decision: **PASS for the bounded corrected source/handler review**, after two independently reproduced findings were corrected by the UI owner and rechecked below. This is not formal Validation, whole-product or installed/runtime PASS. This is a read-only internal nonauthor review; no product, test, DB/env/account or service changes were made by this reviewer. Only this report is owned by `/root/w03_recovery_repairs`.

Parent Request `req_81ac33cac94046b9a2249cd14c0d00ba`. Catalogue's separate 25-test review is [W03_CATALOG_REPAIR_REVIEW.md](W03_CATALOG_REPAIR_REVIEW.md); its PASS is not reused as UI or runtime verification.

## Before-correction source pin

Base `fe3b2f23cc54d452a17ae552e9613b5d37525ede` plus recorded working-tree repair. Inventory comprises **131** tracked/nonignored new files in the visitor template and the admin catalogue JSON, sorted by path. Scope SHA256 `887b87e6013fff11b1392abbc6e62934e3f35b66e40f745d029fd885402998fd`; tracked binary `git diff --binary HEAD -- templates/_bundled/raonslab-travel_lab modules/_bundled/raonslab-travel_lab/resources/layouts/admin/admin_travel_lab_catalog.json` SHA256 `17aca54f35a1e1f3381a901b484c36061005428d269fb5c01c872be49742cb3f`. New files are included in the inventory hash, not the tracked diff hash.

Inventory digest is SHA256 of compact JSON array `[{path,sha256},...]` with object keys sorted. Enumerate with `git ls-files --cached --others --exclude-standard -- <scope paths>`, deduplicate/sort, and hash each regular file. No ignored environment/dependencies or private access files are included.

| Critical before-correction file | SHA256 |
| --- | --- |
| `templates/_bundled/raonslab-travel_lab/src/handlers/inquiryKey.ts` | `44cbacfc7a86eb73eed31a1598e1f410a3ca0c96d00ccd62c37b322d5f6a3e52` |
| `templates/_bundled/raonslab-travel_lab/src/handlers/index.ts` | `186d93a6d2eb7113d916ac4f4e16b8f46cf8cc0741b36116ef728ffd70e78167` |
| `templates/_bundled/raonslab-travel_lab/layouts/travel/cart.json` | `f2cf69012e056fb875fbdbd4140d2e04274501559f3f446c0f6076250c8d1e95` |
| `templates/_bundled/raonslab-travel_lab/layouts/partials/travel/_modal_cart_remove.json` | `39391eefaa0d4526119ce3f7a60e83a9a8c699f8d09f4476130ce9617e762a03` |
| `modules/_bundled/raonslab-travel_lab/resources/layouts/admin/admin_travel_lab_catalog.json` | `32cbd3ef3f9c32f6ababa26d910ffa4c8b8d8ddbde2bb5845dc345be16beca4a` |
| `templates/_bundled/raonslab-travel_lab/dist/js/components.iife.js` | `0b4bdd07015f1675adaad905a47786ababfbfa7cec16b2c05ecf5eed7404a94a` |

Production dependencies inspected: ActionDispatcher SHA256 `bfa67b164cb19a245cde7b904630e8a6689ec4ad3f30b6b55cd7f17492d48e8b`; G7CoreGlobals SHA256 `b6b67e14b9de7baf46fe2abf1ad8aba1c90e2598fb2ea4b6c23f6ba4b6548616`.

## Findings

- **UI-REPAIR-01 / P1:** `travel_candidates` data source sent `per_page:50`, while real `CatalogCandidatesRequest` requires maximum 48. This blocks the registration candidate fetch with 422 before product/option selection. A standalone native Illuminate validator instantiated the actual bundled FormRequest rules without booting app/env/DB: 50 invalid, 48 valid. Existing author tests initialized mocked candidate data and missed this boundary. Owner was requested to use 48 and add a boundary regression.
- **UI-REPAIR-02 / P2:** After the cart-only key is already stored successfully, a setItem-only quota failure causes a contact-aware memory write to be ignored by `readStored`, which reparses the stale session JSON. Actual `prepareInquiryHandler` returns null; submitted fields become undefined. Independent Node execution of the actual TS handler reproduced this exact sequence. Related stale-contact/tombstone cases were subsequently reproduced by the UI owner; those owner results are not independent review evidence yet. Owner was requested to make the latest failed-write memory record or explicit null tombstone authoritative.

## Bounded independent checks already performed

Production source was inspected directly: executeAction binds custom-handler parameters, wraps the handler return in `result.data`; handleSequence assigns that to `$prev`; the following explicit `setState(target:local)` result updates sequence-local state before apiCall resolves its body. Cart uses `travelLabPrepareInquiry → $prev → travelInquiryPayload → apiCall`. The SDK `G7Core.state.get()` returns global contents directly, and `state.set()` does not return a payload; prepare explicitly returns its own exact payload. This is source inspection, not actual dispatcher runtime PASS.

Tiny `node --experimental-strip-types --input-type=module` checks imported actual `src/handlers/inquiryKey.ts`, using tab-storage/global-content SDK stubs; no application/HTTP/browser/DB was invoked. **PASS** for first normalized price-free body, same-body retry, a fresh-module empty-cart reload, owner UUID change, contact edit, acknowledged cart edit, quantity change and success clear. The separate existing-key quota probe **reproduced UI-REPAIR-02**.

Production JS `node --check` **PASS**; compiled handler/pending/key/owner/idempotency literals and absence of sourcemap reference **PASS**. JSON-local payload capture was checked in the cart JSON separately; it is not expected to be a compiled JS literal.

Native admin source review: the edit link uses numeric `trip.id`; ecommerce's `:itemCode` route forwards to `findByIdOrCode`, so this ID matches its contract. New departure selection takes IDs from actual active native `trip.options`, existing option IDs are disabled, and POST/PUT targets/body preserve native product/option identity. Metadata POST and PATCH split product identity correctly; translated summary and itinerary JSON feed actual server validation; loading/empty/error/candidate pagination and save retry are represented. Actual installed modal/editor behavior is not proved by these source checks.

## Author results and unexecuted gates

The UI author's `__tests__/evidence/w03-ui-repair.json` reports 128 Vitest tests, TypeScript and production build PASS. These are **author assertions, not rerun or independently approved here**. This review deliberately ran no broad tests while host load was high.

Installed source resync, actual first submission/response-loss/reload browser flows, native registration/editor and departure writes, mobile/PC rendering, post-integration regression, remote CI and canonical formal Validation are **NOT_RUN by this reviewer**. Parent/official verification remains required; source/handler checks do not replace those gates.

## Post-correction review

Both owner corrections were checked against the stable corrected source/build; no additional P1/P2 issue was found in this bounded review. The findings above remain as fail-first history and are closed **for source/handler scope**, pending actual installed verification by the parent/official reviewers.

Corrected full template/admin inventory: **131 files**, same enumeration/hash method as above; final scope SHA256 `ea0258b70a67d6c087fcc2ea5a1bddab0ca88edd09e37ef0ce1ee94302e9f667`; tracked binary diff SHA256 `8f81a5274d292f7a367850173d40a72346a5f5e8d1dd50ec637c729502eb59e7`. The owner finalized only the new author evidence JSON after the initial corrected inventory `8c46120a078312e1e34f64d008b03ebadb2f53aaf0ef9d01811897810f6189cc`; every other file, all seven critical code/test/build hashes below and the tracked diff remained unchanged on the after-review check. Production dispatcher/SDK dependency hashes remained unchanged. No changed code or build was rebound to an earlier runtime result.

| Corrected critical file | SHA256 |
| --- | --- |
| `templates/_bundled/raonslab-travel_lab/src/handlers/inquiryKey.ts` | `398e98d85e613bf0dc8e302db7a83a443a562ab154caac1a51c8f9906cc8758e` |
| `templates/_bundled/raonslab-travel_lab/__tests__/components/inquiryKey.test.ts` | `33909d992b01f7b72ac2ee5ddea5a1499f650f6532d781c95db21c98f2187560` |
| `templates/_bundled/raonslab-travel_lab/__tests__/layouts/cart.test.tsx` | `188c1ce9c12be00df1ff20973d36380c6c23879fe8af5dfbfd04a12748fa53b3` |
| `templates/_bundled/raonslab-travel_lab/dist/js/components.iife.js` | `4488241bc757d3d3f60df16de7ea715bd75ad9e628e933dc92486173714f992a` |
| `templates/_bundled/raonslab-travel_lab/dist/css/components.css` | `75f10e7ed5e1090c64a340a7915effd4a01e1279ecd2ea3318ed2d9c319ccf1d` |
| `templates/_bundled/raonslab-travel_lab/scripts/verify-inquiry-recovery.mjs` | `0718e70e34dbcefc38a59ac50cef73f95f45e034dab61c0701b48a8c3d927ec5` |
| `modules/_bundled/raonslab-travel_lab/resources/layouts/admin/admin_travel_lab_catalog.json` | `c2b8ca989ae0c3dfbeb357e79d2199c246d20abc34c7c5259b6a197ae8ef7522` |

The owner's seven-file review record has ID `b96dc0d3087142d41389492b114345c6b2082ad425147b95bbde1b7ee90e8850`. All seven file hashes were independently compared with actual bytes and matched. This provided scope ID is distinct from the independently computed full-inventory digest above.

- UI-REPAIR-01: actual corrected admin JSON now supplies **48**. A standalone actual bundled FormRequest/native Illuminate validator check returned `PASS_ACTUAL_CANDIDATE_PARAM_RULE`; app/env/DB were not loaded. The earlier 50-versus-48 failure was preserved.
- UI-REPAIR-02: `memoryOverridesStorage` makes the latest memory write or explicit null tombstone authoritative after failed storage write/remove, and clears that override only after successful persistence/removal. Independent actual TS handler execution returned `PASS_PURE_HANDLER_POST_REPAIR` at SHA256 `398e98d85e613bf0dc8e302db7a83a443a562ab154caac1a51c8f9906cc8758e` (0.097s), with unchanged bytes after execution. It covered existing-key setItem quota first body/retry, edited contact under quota, failed-remove owner-UUID tombstone, a fresh-module empty-cart reload, normal owner change, quantity change, acknowledged cart edit and success clear. Storage and global-content SDK were in-memory stubs; this is not actual dispatcher/browser execution.
- Corrected production JS `node --check` **PASS**. The compiled artifact was repinned after the owner completed its production rebuild; earlier browser results must retain their earlier artifact hash rather than being relabeled to this bundle.

The owner reports corrected handler/cart **32 scoped tests PASS / 32.99s**, TypeScript PASS and fresh production build **35 modules / 10.49s**. Those are author results, not rerun/independently approved here. The historical 128-test and overlapping 37-test records are separate phases, not fresh corrected-full-suite evidence or additive unique scenario counts.

No broad suite, HTTP request, browser or shared MySQL operation was executed by this reviewer. The parent's installed resync and actual browser repair validation remain separate, including response-loss replay200 for the same inquiry, reload, optional storage-quota mode and native registration/candidate/departure editor behavior. Canonical formal Validation, fixed-commit independent re-review and integration regression remain required.
