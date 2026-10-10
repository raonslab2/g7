# W04 campaign admin native Retry repair

Status: **AUTHOR_SOURCE_TEST_PASS**, runtime repair **NOT_RUN**. This report documents an internal TemplateApp bug fix; it is neither independent product approval nor official Validation. The lead owns the fixed commit, production build, activation, Git delivery and subsequent nonauthor browser recheck.

## Frozen inputs and ownership

The independent browser Request `req_fc51a817f76d40eca856b390222911fc` tested product `31a18318f91dde34a9c75eaaac65ae1d434b7bc3` (tree `c0984e682a77153753edcde318a6d44b6973b1c0`). Its immutable `W04_CAMPAIGN_BROWSER_FINAL.md`, `adapter-retry-2.json` and `bounded-followup-1.json` record the same failure at390px and1440px: abort one native Page-list GET, remove the abort, click Retry, receive HTTP200 containing2 Pages, but retain the error banner and0 titles. Same-context reload renders2 titles. That negative evidence is preserved; a source test cannot retroactively turn that product version into PASS.

Source investigation began at parent `9bfe0136a79982d8e1d2310dd94b262bfddfd4ab`. Parent documentation intake commits subsequently advanced HEAD to `dd9f4ef4a52e8c82579a5391f43eabf16e8983cd`; the TemplateApp input SHA256 is identical at all three revisions above. Repair results refer to **WORKINGTREE**, not to any of these unchanged committed products.

Authorized edits are restricted to these five paths:

- `resources/js/core/TemplateApp.ts`
- `resources/js/core/template-engine/__tests__/TemplateApp.refetchRecovery.test.tsx`
- `resources/js/core/template-engine/CHANGELOG.md`
- root `CHANGELOG.md`, one Fixed bullet in the existing unpublished7.0.12 batch
- this report

No campaign JSON, Page API, permissions, template bundle, manifests, versions, installed files, database, environment, cache or service were changed. No tokens or live fixtures were created. No build, installation, commit, push or deployment was performed by this author.

## Cause and minimal repair

The initial progressive load places the native transport error in `_dataSourceErrors.campaign_pages`. The existing campaign layout correctly gates its list on the absence of that error. The native Retry action calls `refetchDataSource` with `params.dataSourceId`; its dispatcher path and Page-list endpoint are correct.

`TemplateApp.refetchDataSource` updated the successful source payload but never removed its previous error. Failed retries only logged the failure, leaving the older error message/status. The resulting HTTP200 with a stale error explains the actual independent browser failure. The `createLayoutTest` helper does not execute the real TemplateApp refetch, so earlier layout-only tests did not exercise this state transition. Changing the layout predicate or clearing its error before receiving a response would hide a genuine failure and was rejected.

The repair keeps the error visible while the request is pending. On native success with defined data, it invalidates source/error bindings, reads the latest error bag, removes only that source's entry and writes `undefined` when the last error disappears. A failed retry records the latest native message/status for that source while preserving unrelated errors and the previous source payload/cache. Both updates preserve the existing optional synchronous rendering mode; `finally` still clears the native pending indicator. Reading the latest bag after the await preserves another source's error added during the request.

The public method/signature, native AuthManager and DataSourceManager paths, return value, endpoint/query/header construction, initGlobal/initLocal and fallback handling remain unchanged. Native DataSourceManager marks an explicitly declared fallback as success; the same defined-data success path remains effective. The actual campaign source declares **no fallback**. Native usable empty data (empty200 collection, null, Axios204 empty string) also clears the old error. The pre-existing `result.data !== undefined` condition remains; no new result-state or return convention is introduced. Network failures without a response retain the existing raw-status convention (`undefined`), as in progressive loading.

This restores an existing internal recovery contract. Travel's existing `requires.g7_version >=7.0.12` remains compatible. There is no new extension API or blanket minimum-version increase for unrelated bundles.

## Reproducible tests and phase evidence

The new regression uses actual TemplateApp, native DataSourceManager, native AuthManager.checkAuth, ActionDispatcher, binding engine, shared state/update path and DynamicRenderer. Only ApiClient/fetch transport is mocked; basic test component wrappers stand in for the separately shipped admin component bundle. It loads the actual campaign source/layout JSON. The rendered Retry test executes the actual action dispatcher rather than calling a copied test implementation. Mock transport is unit evidence, not HTTP/auth/browser evidence.

| Phase | Result | Interpretation |
|---|---|---|
| First draft, original source,8 tests |0 PASS /8 FAIL | Seven intended defects; one initGlobal fixture expected the response wrapper instead of the existing mapped rows. Retained as harness diagnostic. |
| Corrected regression, original source,8 tests |0 PASS /8 FAIL | Meaningful fail-first baseline, before product source edits. |
| First repair, same8 tests |8 PASS /0 FAIL | Actual native state/render recovery. |
| Final regression plus native compatibility controls |12 PASS /0 FAIL | Includes the same8 and4 additional empty/fallback controls; additional controls have no claimed separate old-source fail-first run. |
| Related nine-file suite |158 PASS /0 FAIL /1 existing SKIP | Final12 are included, not additional successes to add to this count. Existing TemplateApp changeLocale rerender skip remains unexecuted. |

The fail-first command (and first fixed run) was:

```sh
./node_modules/.bin/vitest run resources/js/core/template-engine/__tests__/TemplateApp.refetchRecovery.test.tsx --maxWorkers=1 --no-file-parallelism --reporter=json --outputFile=/tmp/g7-campaign-native-refetch-baseline.json
```

The final related suite was:

```sh
./node_modules/.bin/vitest run \
  resources/js/core/template-engine/__tests__/TemplateApp.refetchRecovery.test.tsx \
  resources/js/core/__tests__/TemplateApp.test.ts \
  resources/js/core/__tests__/TemplateApp.networkResilience.test.ts \
  resources/js/core/__tests__/TemplateApp.unauthorized.test.ts \
  resources/js/core/__tests__/TemplateApp.updateQueryParams.condition.test.ts \
  resources/js/core/__tests__/template-engine.layoutEditorRerender.test.tsx \
  resources/js/core/template-engine/__tests__/DataSourceManager.test.ts \
  resources/js/core/template-engine/__tests__/DataSourceManager.conditions.test.ts \
  resources/js/core/template-engine/__tests__/DataSourceManager.globalHeaders.test.ts \
  --maxWorkers=1 --no-file-parallelism --reporter=json \
  --outputFile=/tmp/g7-campaign-refetch-regression.json
```

This ran locally with the existing locked root dependencies and jsdom; no dependency installation, production build or database tests were needed. Vitest emitted an existing nested-mock hoisting warning in TemplateApp.test.ts; it did not fail the suite. The historical546-test suite was not repeated or credited to this repair.

The portable new test covers: actual Retry200/banner removal/two Page titles with abilities preserved; own-error-only recovery; latest503 preserving cached data and another error; repeated429/network failure then200; real pending state and retained error until response; another error introduced while the response is in flight; native initGlobal/initLocal with binding invalidation; native no-token401 skipping transport; empty200 collection; null data;204 empty-string data; and explicit native fallback semantics.

| Related file | PASS | SKIP |
|---|---:|---:|
| TemplateApp.refetchRecovery.test.tsx |12|0|
| TemplateApp.test.ts |53|1|
| TemplateApp.networkResilience.test.ts |9|0|
| TemplateApp.unauthorized.test.ts |8|0|
| TemplateApp.updateQueryParams.condition.test.ts |3|0|
| template-engine.layoutEditorRerender.test.tsx |2|0|
| DataSourceManager.test.ts |45|0|
| DataSourceManager.conditions.test.ts |13|0|
| DataSourceManager.globalHeaders.test.ts |13|0|

Raw local result files are temporary diagnostics, not permanent delivery paths. These hashes bind their observed executions; the counters, commands and committed regression source above are the portable evidence:

| Local result | SHA256 |
|---|---|
| Initial draft fail-first JSON |`d2126b282b9575dd61be5212f44adea47c85b40ff2914d2a51bec85bd34c9707`|
| Corrected old-source baseline JSON |`6ea419c5a4b182fff666fa77ecad4753c3c875c8d9f5cc57320e3d282cb4ff2a`|
| First fixed8-test JSON |`b153148246cf726d48198f38db684bf95acb9423221b4da84685316306ee8879`|
| Final nine-file JSON |`4292d51f987ddc7426e4b0d601dbc7e73c1a0509655166462803a034a35cdded`|

## Frozen source pins

| Path | SHA256 |
|---|---|
| Original TemplateApp.ts,31a /9bfe /dd9f |`bc578f3fd5437b3634d630057a105a1696d9648130496f1737a5ee5b46599ebb`|
| Repaired TemplateApp.ts |`62e89b9dedb93c932a92e1b01944cffb11e88cf0c5cd5a79dc41ca8835f5b76e`|
| New native regression file |`bb92c44535629e4c0cc47c2f0087b9d2f10d3d718eed323cd2a0fe101922fcc3`|
| Engine changelog |`82fb0d7482137df51a4525745c5704aa7182bb140a1e131eb5689eaab9fec4b2`|
| Root changelog |`0d5761c6503e52e645203fb0047d6c1700733bc912dca3fe1f2d7c6bd7d430fe`|
| Unchanged campaign admin JSON |`32ac9d46844dcdada237a680c43f10f78c822b4e07c9abe8c2a2baac93d0861b`|

The report's own final hash is provided to the lead separately to avoid a recursive self-hash. Other parent/native owners' files are outside this author's diff.

## Remaining gates

Nonauthor fixed-source review, lead production core build/activation and independent actual390px/1440px browser recheck are **NOT_RUN by this author**. The required recheck must use native login and the actual campaign adapter: abort the Page-list once, remove the abort, click Retry, observe genuine200,2 titles and no error; repeat a real failed retry to ensure current failure remains honest and then recover again. Existing ability gates, empty/native draft/admin flows and authentication must remain effective. General Page role/browser-editor/SEO limits from the original independent report remain outside this repair's proof. Hosted CI, formal Validation and integration follow the existing lead gates; none is waived or replaced by the unit results.
