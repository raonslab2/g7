# W03 independent catalogue repair review

Decision: **PASS for the recorded repair diff and bounded SQLite checks** after one fail-first defect was corrected by ROOT. This native reviewer did not implement the catalogue source. This is internal nonauthor review, **not official Validation, whole-product PASS, installed lifecycle discovery, MySQL contention or final fixed-commit release validation**.

Parent Request `req_81ac33cac94046b9a2249cd14c0d00ba`, reviewer `/root/w03_recovery_repairs`. The earlier runtime repair was a separate assignment; the reviewer owns no catalogue/guard implementation files. Only the two test files below and this report were changed for this task. No stage/commit/push, shared DB/env/account changes or preview operations occurred.

## Recorded target and source identity

Base HEAD: `fe3b2f23cc54d452a17ae552e9613b5d37525ede`, plus the reviewed working-tree repair. The implementation was frozen before the run; all **25** implementation file hashes and tracked diff digest were rechecked afterward and matched. The tracked diff digest alone excludes new files; the complete file manifest below includes them.

- Final implementation scope SHA256: `2630eafe774f2e64d5a9a3e146677820b2cb161ae610fba887bf0d2d5b1f2e1d`.
- Final tracked `git diff HEAD -- <manifest paths>` SHA256: `7719597cf2664a88ffac04e5f4aa196c377a6eba3342cf785a6f134606a8de6f`.
- Initial pre-fix 22-file scope SHA256: `d8e749ee7e7a6b0802d636601dc3ce2b2453f8045a1de0bac44d4df01c265ff8`, tracked diff SHA256 `dc2cb2c4289aac65e453e43e6d26fbf9b1d80cf8837784e31262b1e8ab9d1426`.
- Scope digest definition: SHA256 of compact JSON manifest, object keys sorted, in the table's sorted path order.

| Implementation path | SHA256 |
| --- | --- |
| `modules/_bundled/raonslab-travel_lab/config/catalog.php` | `856affc8784cb173efe44525047fa545e3e943c34cec21affe046ee5d0379ca9` |
| `modules/_bundled/raonslab-travel_lab/module.php` | `f350cb830b51041b1a22a43152a9c7c2d60ad06cf50b8573e9fc8e2c3b1dcf09` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Controllers/AdminCatalogController.php` | `44954398051476894a454221fe1650288b284608a2b1f208689a928eaebdff53` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Requests/CatalogCandidatesRequest.php` | `ca26ddb817cea31bb292f521ab86d2d8e1d5282ffdb720b61c69dc93aa6a09cf` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Requests/CatalogListRequest.php` | `adeb4e0eead19700333bda4534a2c4ae80725d91f4e83429c8a0296782fdc5a3` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Requests/CatalogRegisterRequest.php` | `338b4f8996aca100d90ef4053b98453b14f164ab8c7695a13f6b2f5c86b999a4` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Requests/CatalogUpdateRequest.php` | `a79904508cfcf5cab1674d40afb014a0c0d0b8c679d92b640f39cb1774f76222` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Requests/DepartureRequest.php` | `d5ca7b6aa4d804a623f04b84001071c3f41221cf0c7a1f9bed5db28bf2d7f39c` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/AdminCatalogCollection.php` | `bcf1104bf6f335da81611e75716ef1a01a3deef976e2c3b85bbf3cc58b78d0c9` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/AdminCatalogResource.php` | `83e8a00c925deab8ead04b4167999992498ff988717122b86fde5fa74571b71d` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/AdminDepartureResource.php` | `fd3285a739ccd485cdffca03441e33aecac4b8127812f07a2b4c5bdb710d5499` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/CatalogCandidateCollection.php` | `222599ce7655899ffad179abe7290580417dda992521a372331befa62cee41e3` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/CatalogCandidateResource.php` | `a211996072224cc0788e956d3080059f1f4accde43fc2e3509fa235f6a4b30d6` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/CatalogCollection.php` | `3ba304378daf5b95329dbadc8c502c37e37d1940c8049e846622a160ab9c7891` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/CatalogResource.php` | `26ac52689566f9d1a179d2ddedf1ed6f3b12a9c0d0ee03c095fbe7fc0eefbed7` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/DepartureResource.php` | `016f82f9b3ffe3e9bd87f246d06800f587be9d0f8480570a924dbc59af1c9dcb` |
| `modules/_bundled/raonslab-travel_lab/src/Listeners/ProtectTravelCommerceCatalog.php` | `903a94be831be5364d335fc798cff0577140b40d8bcc14e68e657ba2e48c62b8` |
| `modules/_bundled/raonslab-travel_lab/src/Providers/TravelLabServiceProvider.php` | `a6102f2e6bcf0dcc32fcbb3dd41c1d6358dfae3d3ebc7ed6352dbe502cbffcce` |
| `modules/_bundled/raonslab-travel_lab/src/Repositories/CatalogRepository.php` | `d02ec7a9a024ed84a871358e7de16e1a24460d899a903fb7753eb3e79e119cf1` |
| `modules/_bundled/raonslab-travel_lab/src/Repositories/Contracts/CatalogRepositoryInterface.php` | `77d8de38fdcc96d89ca1f9d2adc287ba1d877de642dda057be2469299059f04a` |
| `modules/_bundled/raonslab-travel_lab/src/Repositories/Contracts/WorkflowCartRepositoryInterface.php` | `45ccb55260110b818bf144bf40add1fb705e5c043756ccb4113ab2c99b1b4928` |
| `modules/_bundled/raonslab-travel_lab/src/Repositories/WorkflowCartRepository.php` | `d1aee869f15f1b81e1f0145086b1772be71e3b8a228d4dd2be38231b386aef8e` |
| `modules/_bundled/raonslab-travel_lab/src/Services/CatalogService.php` | `c085545a29a17601ab909fbbf4481b90e03dd07a880a14fd6f0214c4107d385a` |
| `modules/_bundled/raonslab-travel_lab/src/Support/TravelDate.php` | `55a9e2bfa98809bd38e17b2c1c89097aa5d1d06fa8e12d078b1651a49ecbc518` |
| `modules/_bundled/raonslab-travel_lab/src/routes/catalog.php` | `3748bf033e7ba347e9fb473ad18b779925a67fa1c296ecaaabe3e5ba382d9d70` |

| Test path | Final SHA256 |
| --- | --- |
| `modules/_bundled/raonslab-travel_lab/tests/Feature/W03CatalogRepairTest.php` | `b320af86cbd094bbc3e057607215fa9b9b23694c6e8267ebbe6d9a5824f7736d` |
| `modules/_bundled/raonslab-travel_lab/tests/Feature/CatalogDomainTest.php` | `1e4fb868ada545b9ae5ae674713ac38917d39655cbd8147811c0e7f01d2fa1b3` |

## Findings and actual execution

**CAT-REPAIR-01 / P1 / corrected in the reviewed diff:** registration's FormRequest permits omitted itinerary, but the NOT NULL `travel_lab_products.itinerary` column had no insertion default. POST `admin/catalog` with every required field and no itinerary returned **500**, not 201. The first independent run used the then-frozen 22-file target: **24 tests / 334 assertions / 2 failures**, **19.409s**, exit 1. Both failures reproduced this omission through the actual controller/service/repository and SQLite schema. ROOT fixed `registerMetadata` to default omitted itinerary to `[]`; the reviewer did not change implementation or weaken the expected 201 behavior. The original required-field omission scenario remains in the new tests.

After the repair, one separate module-declaration/registrar check was added and native built-in manager role checks were made explicit. Final command:

```bash
php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter 'W03CatalogRepairTest|CatalogDomainTest'
```

Result: **25 tests / 366 assertions PASS**, exit 0, **62.435s**, PHP 8.3.6 / PHPUnit 11.5.56, memory 94.50MB. This is 16 existing catalogue cases and 9 new repair cases; assertions include the canonical source/SQLite origin checks, not additional business scenarios. Only one PHPUnit process ran at a time; all DB execution was SQLite `:memory:`. Official support hardening owned MySQL TEST during this task.

Verified behavior:

- Required metadata registration preserves native product/option IDs, original full native row attributes and price fields, creates no order/departure, defaults unpublished, and rejects duplicate registration with 409.
- Guest 401, member 403, actual built-in manager with read-only grant cannot register, manager with update permission can register; candidates require native admin/read permission.
- Valid itinerary JSON string persists and re-queries through native resources; invalid JSON, missing nested title and unknown nested price return 422 without overwriting the prior itinerary.
- Reassigning product identity or injecting selling/unit price or amount returns 422. Legitimate metadata update persists without changing the native product.
- Candidates return actual options/stock, support Korean title and product-code queries, and exclude mapped/soft-deleted products.
- Public Korean keyword query matches decoded translated titles/descriptions and excludes unrelated products; nonexistent query returns an empty real API response.
- Active future full/understock dates appear as disabled choices with available 0; inactive/past dates are absent. The displayed from-price comes from an available native option, and discovery/detail still require at least one available departure.
- With application UTC at **2026-10-09 16:00 UTC**, business date is **2026-10-10 KST**. That day's departure is undiscoverable and cannot be newly registered; 10/11 is accepted.

The old departure-list expectation was explicitly updated to this approved disabled-choice contract, including available values `[20, 0, 0]`; past/inactive filtering was retained.

## Separate module declaration/registrar evidence

A clean PHP subprocess loads the **exact bundled `module.php`** before any installed Module class can shadow it and obtains the actual `getHookListeners()` result. It does not boot Laravel, load env or connect to a DB. The test verifies the synchronous deletion/update guard declaration, registers **that actual declared list** using native `HookListenerRegistrar`, then executes real native `ProductService::delete` against a mapped SQLite fixture. The product and departure remain and the travel-specific native exception is returned.

This proves the new bundled declaration and native registrar/guard interaction. It explicitly **does not prove automatic installed ModuleManager discovery, activation/cache resync or installed preview behavior**. ROOT must perform official bundled resync and independent installed HTTP/browser verification; the test does not silently use an old installed declaration or hand-pick only the guard class.

Other checks: new test PHP syntax **PASS**; scoped `vendor/bin/pint --test` **PASS**; scoped `git diff --check` **PASS**. Source review found no additional blocking issue in this bounded diff after the optional-itinerary correction.

Pending: full-module regression, MySQL registration/row-lock contention, installed lifecycle hook discovery, admin actual browser creation/departure writes, and official fixed-SHA re-review/post-integration verification are **NOT_RUN here**. No canonical Validation receipt or remote CI PASS is granted.
