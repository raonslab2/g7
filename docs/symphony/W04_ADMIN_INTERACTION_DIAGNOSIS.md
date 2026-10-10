# W04 admin interaction diagnosis and scoped core repair

**Confirmed product defect:** native departure state becomes false before Save, but the outgoing PUT still sends stale true. Root cause is two incorrect fresh-global lookups in the core expression evaluator. They are repaired in source and meaningful focused regression passes. **Original PC menu failure remains UNKNOWN:** ordinary native pointer and coordinate input succeed on both a current own inquiry and the original own inquiry150; no menu source change is justified by this diagnosis.

This is author diagnosis/repair evidence, not independent Validation or a deployed repaired-runtime PASS. Original independent browser failures at fa552 are unchanged. Lead owns build, fixed Git checkpoint, publication, runtime activation and independent browser recheck.

## Scope and baseline

Original evidence target: `fa5523175ac494cfbd13bbf89bf06b3ec91835a6`, tree `fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c`. Read original safe scripts and `inactive-ui-toggle-witness.json`, `inactive-exact-date-failed.json`, `journey-1440-menu-harness.json`, `menu-diagnostic.json` from final evidence-only child `1d2a4f0bf3ac933504c79bd07dceb68c254de7c4`.

Actual baseline native SPA was loopback18871; protected parent-owned scoped credentials were read in process only after source/schema/loopback/expiry and file0600/directory0700 checks. No private file or value was printed/committed. No SQL, configuration, environment, service, installed-source or old fixture writes were performed. The only new data fixture was native-API-created synthetic product86 / option174 / departure140, hidden/unpublished. Preparation is not UI CREATE evidence. Existing category26/policy6 were read/referenced unchanged and never activated.

Two late baseline phases explicitly measured unchanged served assets:

| Runtime asset | Baseline SHA-256 |
|---|---|
| `/build/core/template-engine.min.js` | `be2d01f3be637afd127f9c62c2fed35cc3de2cabd5da14a7734635ae1798a414` |
| `/api/templates/assets/sirsoft-admin_basic/js/components.iife.js` | `d25eb47e1308a83a456b17fe86953fec8fefc46a38769c0c225be72b0028e6c9` |

Both actual HTTP200 byte hashes match in `settled-render-evidence.json` and `original-pc-inquiry-evidence.json`. Earlier phases rely on the lead's frozen runtime context; do not relabel these later asset measurements initial-before. The final safe helper is retained; no exact-start script archive is claimed for its early revisions.

## Native status-save diagnosis

The helper captures real trusted pointerdown/mousedown/mouseup/click events, timestamps, safe element identity, canonical `G7Core.state.get().travelDepartureEdit.is_active`, actual native request body and server/API readback. It does not patch event handlers, state, responses or core behavior. Only its own fixture is reset between cases via native API.

| Old-runtime case | Native state before Save | Actual PUT/readback |
|---|---|---|
|1440 immediate pointer |false; visible selector still “접수 가능” |true / true |
|1440 poll canonical state=false |false at Save pointerdown/click |true / true |
|390 immediate tap |false at Save pointerdown/click |true / true |
|390 poll canonical state=false |false at Save pointerdown/click |true / true |
|1440 wait for actual selector label “접수 중지” |false, rendered label updated |false / false |
|390 wait for actual selector label “접수 중지” |false, rendered label updated |false / false |

See `tests/W04_ADMIN_DIAGNOSTIC/correct-native-route-evidence.json` and `settled-render-evidence.json`. In immediate cases, Save click is about126ms after1440 option click and136ms after390 option click, with canonical false already present. Polling false alone adds no missing state commit. Waiting for the actual rendered label is a **counterfactual diagnostic control**, not a fix, required UX delay or completed rapid-save validation.

Actual native Select emits a change event. `handleSetState(target=global)` writes the native global store immediately; `TemplateApp.setGlobalState` schedules rendering through the engine import. Save's existing rendered handler can still capture the older dataContext. The evaluator is intended to refresh that snapshot:

- `ActionDispatcher.evaluateExpression` reads `G7Core.state.get()?._global` in both complex interpolation and single-expression branches.
- Native `initStateAPI` exposes `get: () => templateApp.getGlobalState()`, and `getGlobal` aliases `get`: the return value is **global content directly**, with no `_global` wrapper.
- Thus the erroneous read is undefined and the evaluator retains captured old dataContext. Backend receives a valid true boolean and faithfully saves it. This is a frontend source defect, not documented asynchronous-form behavior requiring users to wait.

## Authorized source repair

Changed only the two confirmed evaluator reads to `G7Core?.state?.get()`, with brief comments documenting the existing native shape. Existing fallback remains when the state API is unavailable; computed/local/isolated state, pipe handling, event handling, auth, headers, preview guards and public state API signatures are unchanged. Analogous native readers were inspected; unrelated paths were not edited.

Owned product file: `resources/js/core/template-engine/ActionDispatcher.ts` (4 added /2 removed lines: two reads and two comments). No travel custom handler/workaround, admin layout edit, Page/campaign source change, version/manifest/CHANGELOG/package/build change was made. Lead's compatibility/release-batch decision remains separate.

New `ActionDispatcher.liveGlobalExpressions.test.ts` uses **actual TemplateApp storage and initialized native G7Core state API**, with deliberately stale rendered contexts. It exercises outgoing native dispatcher payloads, not a copied evaluator implementation. Network calls are unit mocks; native global getter/storage are not fake wrappers. Cases cover false boolean, complex interpolation, number/object types, deleted keys, same-sequence updates, two API-unavailable fallbacks and local/row preservation.

One existing fixture in `ActionDispatcher.test.ts` was corrected from `get() => {_global:{shopBase:'/store'}}` to the actual native `get() => {shopBase:'/store'}`. Its original navigation assertion remains unchanged. This fake wrapper had validated the broken evaluator path. In the first wider run, that assertion failed and its trailing cleanup did not execute, causing one subsequent updater failure; both initial failures remain in the report. No assertions were weakened or unrelated fixture behavior changed.

## Fail-first and regression

All test commands use `npx vitest run … --maxWorkers=1 --no-file-parallelism --reporter=json --outputFile=…`; full evidence is under `tests/W04_ADMIN_DIAGNOSTIC/`.

| Phase | Source/test condition | Result |
|---|---|---|
| `core-fail-first.json` |Original two reads + new native-state regressions |8 tests:2 PASS /6 FAIL; exit1 |
| `core-focused-pass.json` |Two reads repaired |8/8 PASS; exit0 |
| `core-regression.json` |First9-file run; old fake-wrapper fixture |507:505 PASS /2 FAIL; exit1. Fixture-shape failure plus cleanup cascade; retained. |
| `core-regression-final.json` |Corrected only scoped native-shape fixture; preview/debounce checks added |11 files, **546/546 PASS**,0 skipped; exit0 |

Final command file arguments, relative to repository root:

```text
resources/js/core/template-engine/__tests__/ActionDispatcher.liveGlobalExpressions.test.ts
resources/js/core/template-engine/__tests__/ActionDispatcher.globalHeaders.test.ts
resources/js/core/template-engine/__tests__/ActionDispatcher.loginTwoFactor.test.ts
resources/js/core/template-engine/__tests__/ActionDispatcher.handleNavigate.fallback.test.ts
resources/js/core/template-engine/__tests__/ActionDispatcher.paramPipes.test.ts
resources/js/core/template-engine/__tests__/ActionDispatcher.canonicalLocalWrite.test.ts
resources/js/core/template-engine/__tests__/ActionDispatcher.test.ts
resources/js/core/template-engine/__tests__/DataBindingEngine.test.ts
resources/js/core/__tests__/G7CoreGlobals.test.ts
resources/js/core/template-engine/__tests__/ActionDispatcher.previewMode.test.ts
resources/js/core/template-engine/__tests__/ActionDispatcher.debounceFlush.test.ts
```

The final run includes the8 focused cases, so do not add them again as separate unique tests. Existing jsdom `Window.scrollTo not implemented` warnings occurred but did not fail assertions. No build, TypeScript compiler, hosted CI, runtime repaired-code check or canonical Validation was run by this author; these are **NOT_RUN** here and lead-owned where required.

## PC menu diagnosis and boundary

`evidence.json` tests a native own returned inquiry with locator mouse, actual coordinate mouse down/up and keyboard Enter; all navigate to the exact detail ID. `original-pc-inquiry-evidence.json` repeats all three on original own inquiry150, queried read-only through its owner first; all succeed. No inquiry transition/note/body was changed. Pointer events are trusted native browser input, not DOM dispatchEvent or forced clicks.

Native `ActionMenu` portals the menu, positions it in an effect, and closes on scroll/resize or outside mousedown. Captured traces show no pre-item closure in these successful cases; navigation follows actual detail-item clicks. Original failed record contains timeout awaiting the menu item plus consequent journey failures, but no pointer/scroll causal event trace establishing which close path occurred. Therefore original cause remains **UNKNOWN / not reproduced in these bounded cases**. No claim is made about every viewport edge, simultaneous scrolling, all rows, or a completed source repair. Neither keyboard fallback nor current pointer success rewrites the original failure.

## Harness diagnostics and cleanup

Two preparation/selection errors are preserved, not attributed to product:

- `evidence.json`: own newly created fixture was not identified in the initial first48-only strict-ID lookup. Cleanup ran.
- `followup-evidence.json`: helper used nonexistent `/admin/travel-lab/catalog` and `#catalog_card` instead of actual native `/admin/travel-lab` and `.admin-card`; selection failed. Cleanup ran. The next phase used actual native route/card and bounded pagination/numeric identity.

Only the same newly created product86/departure140 was reused; no second product was created. Every modifying phase ends native API cleanup to departure inactive/reserved0 and product hidden/unpublished. It remains an explicitly retained synthetic row, not a pristine database claim. No cart/inquiry/contact fixture was created by this diagnosis. Category26/policy6 and other seed/product/actor records are unchanged.

Across all phases,8 newly issued native login tokens were revoked individually and native auth401 rechecked; original parent handoff tokens were not revoked. Browser contexts/processes closed before source repair; there is no remaining live baseline work or active diagnostic fixture/token. No platform credentials, SQL or environment file was read. Exact scoped private values never enter evidence; an in-process known-private/token/UUID scan of the10 helper/evidence files before this report found0 hits. No new screenshots were generated.

## Frozen source pins and next verification

| Frozen owned file | SHA-256 |
|---|---|
| `resources/js/core/template-engine/ActionDispatcher.ts` | `bb57838bde3849631f31c6b0e0947bbef84bb885548749f5fa7ee1008b524f68` |
| `resources/js/core/template-engine/__tests__/ActionDispatcher.liveGlobalExpressions.test.ts` | `e975330094fa2878239ef752e5c9f432532068016584ce71fdb8476ac0411353` |
| `resources/js/core/template-engine/__tests__/ActionDispatcher.test.ts` | `9a04112ade42c939518183c756ec90979210510cc0249c7573bb531882f47730` |

`tests/W04_ADMIN_DIAGNOSTIC/manifest.json` binds the frozen files, public evidence and scoped tracked diff. Only this report, the safe diagnostic helper/evidence and the three explicitly owned core/test files were authored. No stage/commit/push/merge/deploy or new agent/official Request was created.

Lead can build the native core at a fixed checkpoint, verify served-byte/source binding, and require a **nonauthor actual rapid-select→Save recheck at390/1440 without waiting for rendered label**, including server request/readback and changed ID/number/date values. Recheck normal pointer/menu navigation separately; retain original PC cause UNKNOWN unless new causal evidence establishes it. Unit PASS is not a substitute for that repaired-runtime or post-integration verification.
