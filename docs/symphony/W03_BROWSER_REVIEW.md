# W03 independent browser review — FAIL; release gates remain open

Official Request `req_76c856ee21a445ffa3dbe55838cd9e77`, work `work-20261009-g7-symphony-max-child-c7ae42d1`. Non-author Provider execution on 2026-10-09 UTC. Review target is **28ada286c1c34606741bcfe4f9d12e06ac50af30**, PR [#2](https://github.com/raonslab2/g7/pull/2). No implementation changes, push, merge, deployment, official grandchildren or other Request workspace writes.

## Fixed source and runtime binding

Fetched GitHub origin `feat/g7-travel-lab-c7ae42d1`; created isolated `review/w03-browser-76c856ee` at the exact target. Before testing, actual HEAD was the target and tree was **a821bd89f6cf8bdf8cad3b8b35d8758ce8446d9c**, with a clean product checkout. Assigned baseline had been 6853f40d. Runtime: `http://127.0.0.1:18871`, existing owner `req81-travel-lab-preview.service`, initially active/HTTP200. It was never stopped, updated or restarted.

Read-only comparison of bundled target files with the owner's installed travel module/template: **258 files compared, 252 equal**; **all 196 executable/manifest/layout/asset files after excluding documentation/tests equal**. Six differences are tests/docs, listed individually in [runtime-source-binding.json](evidence/W03/runtime-source-binding.json). This is an explicit travel runtime binding, not a claim that all core/runtime files or the default canonical source binding match. The supplied 160-file owner check is not silently substituted for our independently enumerated comparison.

The canonical order receipt in WORK_ORDER_VALIDATION.md validates AI_GCS source 10bc5eff… and its work-order tree. It does **not** validate this G7 commit or turn these browser results into formal Validation. Security/contract, DB/package/recovery, fixed-head CI and canonical formal Validation remain separate gates.

Read root AGENTS, travel module/template AGENTS and docs, admin/ecommerce/board AGENTS, SCORE/UI_DESIGN, actual customer/admin route JSON and catalog/workflow/support/native commerce/API references. Standalone `tests/W03` orchestrates travel cross-extension review; it adds no core/extension implementation or core Playwright spec.

## Per-viewport outcome

PASS means actual Chromium UI and real API, including native Bearer authentication. BLOCKED/NOT_RUN are not waived. Both widths used separate native member/admin contexts; foreign checks used the distinct supplied other-member account. No AuthManager override, fabricated auth state, fake API success, HAR or trace.

| Scenario | 390px | 1440px | Actual evidence / limit |
|---|---|---|---|
| Home, live region/category/theme chips, friendly Korean labels | PASS | PASS | Real facets and rendered cards |
| Keyword search `제주` | FAIL | FAIL | HTTP200 but zero matches despite two visible matching titles |
| Date + price + combined filter/reset, four sorts | PASS | PASS | Actual response and settled rendered order |
| Pagination next/back | NOT_RUN | NOT_RUN | 8 public products, fixed page size12; no next page; no authorized native travel-registration API to add own fixtures |
| Detail, itinerary, departure and people controls | PASS | PASS | Actual product/options/departure data |
| Sold-out disabled option | NOT_RUN | NOT_RUN | Public repository excludes exhausted departures; no available0 option supplied |
| Native login, empty cart, real add/change/remove + modal | PASS | PASS | POST201/PATCH200/DELETE200, quantity2→3→2, remove/re-add |
| Trial notice, synthetic contacts, ordinary inquiry submit | PASS | PASS | Native UI POST201/navigation and explicit TEST_INQUIRY |
| Response-loss retry preserves key / same inquiry | FAIL | FAIL | Real upstream201 intentionally dropped; unchanged retry carries different key and gets409 |
| My requests list/detail | PASS | PASS | Own real requests and Korean statuses |
| Native admin lookup + note + permitted state processing | PASS | PASS | Native UI list/detail; PATCH200 UNDER_REVIEW then TEST_ACCEPTED, synthetic note |
| Owner reload, accepted state still clearly test only | PASS | PASS | Exact status badge and simulation notice |
| Real logout/relogin persistence | PASS | PASS | Native login/logout200; retained owned request/status; corrected ambiguous text locator independently rerun |
| Notice/FAQ read; NEW native admin create → customer detail | PASS | PASS | Native board POST201, real public detail200 and content; own posts soft-deleted afterward |
| Private question create → own requery → native admin answer → owner answer | PASS | PASS | Own question201, native board comment201, customer answers200/rendered |
| Foreign member denial / private question isolation | PASS | PASS | Actual other-member UI, detail404, own list omits target/title |
| Private attachment serving | NOT_RUN | NOT_RUN | Help question UI has no attachment input; server forces no attachments. Detailed attachment API abuse belongs to security reviewer |
| Native commerce product/options/prices read and own write | PASS | PASS | Existing fixture read only; NEW own product18/option34 changed to13000/14000 via native UI PUT200 and persisted |
| Native travel catalog/departure form read | PASS | PASS | Loaded catalog; modal populated future dates and enabled save control, no authored fixture writes |
| NEW product → travel metadata registration / full metadata UI | FAIL | FAIL | Own commerce product exists; PATCH travel admin catalog/{id}404; no create/bind route or metadata editor in native UI |
| Own departure create/update/unpublish write | BLOCKED | BLOCKED | Depends on registering own travel metadata; authored and other reviewers' fixtures must not be modified |
| Native nav/drawer, toast/modal, loading/error/empty/retry | PASS | PASS | Native UI controls; bounded labeled fault injection then actual HTTP200 recovery |
| Mobile/document overflow | PASS | PASS | 68 primary measurements, none wider than viewport |
| Uncaught browser page errors | PASS | PASS | Zero across primary runs |

Commerce PASS is specifically ordinary native product/option price persistence. It does not establish full travel metadata/departure write UX. Admin inquiry/support PASS includes real UI interactions; no admin API-only result is counted as admin UI PASS.

## Prioritized findings and proposals

1. **P1 — response-loss inquiry recovery breaks.** Select departure, add real cart, enter unchanged synthetic name/phone and acknowledge trial; let the real POST create201, drop only its browser response, click submit again. Final inquiries17/19 were committed once; retries409, original/retry key SHA256s differ while cart_ids/contact match. The browser stays on cart with a generic changed-cart message, so the successful intake is not recovered. Own query later found11 distinct contact groups/11 inquiries across diagnostic/final runs and zero duplicate groups; no duplicate was created in this tested consumed-cart retry. **Key preservation and same-inquiry recovery still FAIL.** Native source diagnosis: custom contact-aware key preparation changes global key, while ActionDispatcher refresh reads `state.get()._global` although `state.get()` returns global contents directly; initial sequence sends stale key, next render sends newer key. Correct the global refresh or pass the returned key into sequence-local state; freeze key/payload across uncertain response and reload. See [idempotency-diagnostics.json](evidence/W03/idempotency-diagnostics.json), [journey-results.json](evidence/W03/journey-results.json), independent [public-code-review.json](evidence/W03/public-code-review.json).
2. **P1 — Korean keyword discovery misses visible products.** Home → enter `제주` → search. URL `/travel/search?q=제주`; actual catalog200 `data.data=[]`, UI 여행0개. Same source displays 제주 바다와 오름/제주 숲 속 쉼. Confirm installed native keyword indexing/query behavior for these newly seeded commerce products; add a real fixed-source Korean search regression. Screens `public-search-defect-390.png` and `public-failure-hero-search-real-results-1440.png`.
3. **P2 — native travel onboarding/metadata write gap.** Creating a real own commerce product is supported; registering travel metadata is not exposed. Existing `CatalogRepository::updateMetadata()` calls `find(...,false)` before update, so the advertised PATCH only edits already seeded mappings. Native travel UI offers publication/departure controls but no region/theme/summary/itinerary editor or new-product binding. Add a supported, permission-checked registration/editor path before claiming full admin UX; then rerun own departure write/pagination/sold-out scenarios. The tested own product18 remains hidden; no author fixture changed.
4. **P2 — native commerce custom-code route mismatch (bounded diagnostic).** API accepts own product_code `W03-P-1791542298261` (product17,201), but edit retrieval uses `/{identifier}` constrained to alphanumeric and produces404/405 with disabled empty fields. Alphanumeric own product18 loads and saves successfully. Normalize creation constraints or use existing `/by-code/{code}` lookup consistently. This is a native commerce regression, outside the new travel implementation surface. See [commerce-hyphen-code-diagnostic.json](evidence/W03/commerce-hyphen-code-diagnostic.json).

Exhausted departures are intentionally filtered by eligibleDepartures (`capacity > reserved`, stock ceiling). The template has a disabled 마감 branch, but no real sold-out option was observable. Pagination/sold-out coverage remains open rather than using fabricated responses or modifying protected seed fixtures.

## Execution, counters and evidence

Node22.23.3, Playwright1.60.0, same-node cached Chromium, headless, ko-KR, widths390/1440, heights844 or1000. `npm ci --ignore-scripts --no-audit --no-fund` used this workspace's lock; no Composer/DB harness, direct SQL or testing DB access.

```bash
# Run on the delivered evidence branch (product source still matches the target).
git switch review/w03-browser-76c856ee
node tests/W03/review-source.mjs
git rev-parse HEAD '28ada286c1c34606741bcfe4f9d12e06ac50af30^{tree}'
npm ci --ignore-scripts --no-audit --no-fund
export W03_ACCESS_FILE=/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba/storage/framework/testing/travel-live-review-bfcc5509b705/access.json
node tests/W03/public-review.mjs
node tests/W03/journey-review.mjs
node tests/W03/admin-support-review.mjs
node tests/W03/commerce-review.mjs
node tests/W03/regression-review.mjs
node tests/W03/own-cleanup.mjs
```

Scripts read the scoped ignored0600 lab access only privately. Caller must supply fresh scoped credentials if expired. No credential values appear in commands or reports. Only the named network scenarios intercept: public catalog abort to check loading/error/retry, and inquiry real upstream201 response loss. All successful domain/admin API responses are real. Scripts exit1 for observed product FAIL; that exit does not mean infrastructure failure.

Evidence-only descendant commits can rerun using `review-source.mjs`, which rejects committed, indexed and tracked working changes outside W03 report/tests/evidence paths. Target SHA/tree remain authoritative; this guard does not validate a different product revision or canonical receipt. Source protection/check and all script syntax checks passed.

Primary raw run counts are deliberately retained: public **28PASS/2FAIL/4NOT_RUN**; journey **23PASS/3FAIL/2OBSERVED**; admin-support **18PASS**; commerce **4PASS/4FAIL/2OBSERVED**; regression **4PASS/2OBSERVED**. Three raw harness negatives were superseded by actual reruns: one strict text locator matching both breadcrumb/heading, two pre-blur price assertions. Regression verifies both login/logout persistence and valid, settled native price saves. Do not count those as open product defects. Four regression rows repeat prior scenario coverage. Consolidating replacements and excluding OBSERVED/duplicate rerun rows gives **76PASS/6FAIL/4NOT_RUN**, including four width0 setup/cleanup checks; departure writes and attachments additionally remain explicitly BLOCKED/NOT_RUN in the matrix. These are bounded scenario checks, not author120 tests or screen-delivery totals.

Across the five primary result files: **0 page errors; 12 console errors**. All12 are accounted for: public4 real nonexistent-product404 +2 injected aborts; journey2 injected response-loss errors +2 actual retry409; support2 expected foreign-private404. No other primary console errors. The separately retained custom-code diagnostic has404/405 errors and is not hidden in the primary count. **68 measured overflow checks,0 overflow**. **69 sanitized PNGs** include actual customer/admin/support/regression states; generated inputs and visible authentication email labels are masked; native support list/answer captures were replaced after review found a parenthesized synthetic email missed by exact matching. No HAR/trace. Raw diagnostic failures remain labeled, not rewritten to PASS.

Native counts: one supported internal subagent performed disjoint public browser execution and independent source/harness review; no official child Requests were created. Primary final transaction has4 real inquiry201 creations (2 normal +2 dropped-response),2 failed409 retries,4 native admin transitions200 and2 native answers201 from the support run. Normal final inquiries18/20 and response-loss17/19 were cancelled using owner UI; all11 own diagnostic/final inquiries10–20 are now CANCELLED, own cart0. Four NEW public posts10–13 were native soft-deleted with history retained. Own products17/18 remain hidden, own categories1–4 inactive; private questions/answers remain synthetic audit history. [cleanup-results.json](evidence/W03/cleanup-results.json) records actual API requery rather than assumed cleanup. No authored seed data or foreign fixtures were edited.

Independent review found missing answer/owner assertions, inadequate cleanup and weak route-only checks in the early harness. The support supplemental run used owned questions8/9 from the prior customer run at this same source; final new questions14/15 are retained separately. The supplemental runs exercised the real native answer chain, own price writes and all own cancellations; conclusions above use those results. Final evidence review and local Git checkpoint are owned by this Request's lead. No remote CI, formal Validation receipt, package/recovery certification, merge or deploy is claimed. Git publication is explicitly prohibited for this reviewer; deliver the local commit to the lead collector.

Final internal evidence review: the non-implementing native reviewer checked staged binary diff SHA256 **dfc446aa6d866ff2e5d18a0b56b40e9efd3d0145704094758babe9e6ab2df423** and confirmed previous evidence findings resolved, no remaining publication blocker, no implementation paths, and no suspected token exposure. Inventory at that review:91 files (69 PNG +22 text, including8 scripts). This acknowledgement was appended afterward; it does not grant formal Validation or change the FAIL verdict.
