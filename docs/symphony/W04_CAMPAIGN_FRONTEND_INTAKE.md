# W04 campaign frontend nonauthor intake

**Decision: PASS_BOUNDED_SOURCE_AND_UNIT.** No confirmed P1/P2 frontend blocker was found in the two-slot native Page campaign implementation. Actual Page administration/customer browser, deployed asset parity, permissions, version restore and post-integration regression remain **NOT_RUN by this reviewer**. This report does not grant canonical Validation or product-release PASS.

## Target, ownership and source boundary

Requested fixed input is root commit `95d16543605d391dbf7a2d2869c08dbb9f163754`, integrating saved campaign implementation `c85eea30`. The implementation provider's terminal FAILED state and saved code are separate facts; its self-reported test results were not accepted as proof. This reviewer authored the prior design contract and older unrelated travel/core repairs, but did not implement the new PageBody, campaign layouts/API or Page admin adapter reviewed here.

Read root/travel module/template guides, native frontend action/data-source/editor specification guides and `docs/symphony/W04_CAMPAIGN_PAGE_CONTRACT.md` (current contract SHA-256 `0f6dd26920e09c781579f9dc8c1d4d77fdb5ca49533dfaf17714270b61fe46cb`; original bounded v1 `ef79adef…` plus lead clarifications). Native Page controllers/resources/requests/form/detail and actual core data-source/evaluator contracts were checked as supporting input.

The campaign commit has78 changed paths. Initial source parity check selected34 template/frontend/module-resource/editor-spec paths;32 matched fixed commit byte-for-byte. Only root-owned template manifest/CHANGELOG had subsequently changed for the Board preview compatibility floor. The whole-template run completed with the original Board>=1.1.2 expectation, before the changed manifest was loaded by that process; it must not be described as a final unchanged fixed-SHA suite. Campaign component/layout/admin source remained identical to95d16543 throughout this intake.

Lead then explicitly authorized this reviewer to change **only** the contract-test Board dependency expectation from>=1.1.2 to>=1.1.3. A current-metadata fail-first and targeted successful rerun are separated below. No implementation source, runtime, build, env, SQL, fixture, service or installed extension was changed by this reviewer. No Git staging/commit/push or agents were created. The live fa552 preview and independent browser Request were left untouched.

## Confirmed frontend contracts

- **Body security:** PageBody uses an isolated actual DOMPurify(window) instance, independent of shared default hooks/config. It allows only formatting tags; `ALLOWED_ATTR=[]`, `ALLOW_DATA_ATTR=false`, `ALLOW_ARIA_ATTR=false`, unknown protocols disabled. Links become text; image/media/iframe/form/SVG/style/script nodes and all content attributes are removed. `purifyConfig` or arbitrary API/layout props cannot widen the policy. Unsupported purification is fail-closed. Text mode uses escaped React children, preserves line breaks and wraps words. No extra active template dependency or remote asset is required.
- **Hooks:** useMemo runs before every conditional return. Empty→text→HTML→text→empty and unknown-mode fallback are exercised with real DOMPurify/jsdom, not a fake sanitizer. Attack tests include events, javascript and external URLs, remote CSS/images/media, data/ARIA attributes and shared-purifier hooks. Unit DOM absence is not an actual browser network-observation PASS.
- **Registration/assets:** PageBody is exported from src/index, registered in components.json and template composite registry, and present in the supplied compiled bundle/declaration. Existing ScenicArt and core wrapper Div are reused. This intake did not reproduce the production build, source-map check or installed-bundle hash verification; lead owns those checks after runtime freeze.
- **Public screens:** home reads real `/campaigns`, replacing static seasonal success copy; no published slots hides the section. Campaign list distinguishes initial loading, empty and error/retry. Detail distinguishes404 from retryable errors and has native Page body/version plus catalog loading/empty/error/results. `/travel/campaigns/:slug` and `/page/:slug` use the same consumer.
- **Privacy boundary:** these routes are public with optional native bearer on campaign calls. Server source resolves only the exact two registry slots through PageService::getPublishedPageBySlug(slug,false), independently requires published=true, and returns a narrow CampaignResource. The frontend does not forward preview flags or serialize native admin PageResource. Guests, members and admins share the same draft exclusion in this source contract; actual role/HTTP denial is not proven by these mocked layout tests.
- **Catalog/transactions:** campaign success stores `response.data.data.catalog_query` then refetches the non-auto-fetch real `/catalog` source. Native DataSourceManager successContext wraps the API envelope in response.data, so this path is correct; native refetch carries fresh global state. Only theme/sort/per_page are sent. CTA uses the server-provided registry filters, product cards use existing numeric product/detail flow and server PriceTag. Page text introduces no price/discount/cart/inquiry field or booking state change. Existing cart/auth/key/help/request template tests run in the same suite.
- **Native admin reuse:** route/module menu/layout require `sirsoft-page.pages.read`; data source uses native Sanctum-required GET Page admin list. Native PageListRequest allows per_page100 and the encoded nested slug filter. `starts_with` is accepted by Request but the repository uses substring matching, so exact two-slug `.find` checks are essential and present. Unknown decoy rows are ignored. Actual PageCollection `data.data`, `data.meta` and row abilities match bindings. Remaining native result pages have a next-results link and absence copy states this account's scope, not global nonexistence.
- **Native edit/publish/version:** create navigates to `/admin/pages/create` with the exact slug query; native form really initializes query.slug and defaults HTML. Hint correctly tells operators to turn off HTML mode for first synthetic text content. Edit/detail use native numeric IDs; detail's native versions/restore API and controls exist. Four publish/unpublish calls use native PATCH with a single boolean published, required auth, top-level onSuccess/onError and numeric resource ID; controls require row abilities.can_update. Actual editing/version/restore/publish persistence remains browser/backend verification work.
- **Samples/locales:** module editor-spec owns synthetic campaigns/campaign/campaign_trips/campaign_pages samples with the exact envelope/registry/filter/body/ability fields. They are labeled editor-only and are not runtime response fallbacks. Components/translation contracts cover source exports and ko/en keys; admin-specific translations are independently tested.

## Bounded nonblocking observations

**CF-UI-01 / P3 documentation — corrected within authorized scope:** travel `docs/editor-spec.md` says that absent template editor-spec prevents placing components, then reverses template/module domain-sample ownership in its later advice. Actual native ComponentPalette.tsx resolveCategories has a components.json basic/composite/layout fallback; specTypes documents the same fallback. Thus absence of a custom palette is **not a demonstrated PageBody registration or native Page editing defect**. Like existing ScenicArt, PageBody has manifest registration; dedicated template capability/style controls remain undeclared. Lead authorized a bounded documentation correction in templates/_bundled/raonslab-travel_lab/docs/editor-spec.md: only authored intent blocks now distinguish flat manifest fallback, module-domain samples and untested actual canvas behavior; generated blocks were preserved. No new general template-editor project was introduced.

**CF-UI-02 / P3 permission UX observation:** publish controls respect can_update, but edit links remain visible for can_update=false and absent-slot create links do not gate collection can_create. The existing admin render test explicitly expects two edit links for a read-only account. Native Page form/API permission and scope checks still enforce authorization; no bypass or unauthorized write was found by source inspection. A future small UX refinement can hide/disable unavailable write navigation while keeping detail/version viewing, with an actual read-only-role browser check. Do not interpret the present links as a security PASS or a new P1/P2 defect.

The source tests use injected initial campaign_trips responses and inspect the success-chain contract; they do not execute real sequential campaign HTTP→catalog HTTP, cache/refetch transitions or scope-denial journeys. Existing response retained during a failed requery, filter freshness across both slug transitions, and390px/1440px geometry are important independent browser targets, not established failures from this intake.

## Executed commands and results

Template dependencies were absent locally. Authorized `npm ci --legacy-peer-deps --ignore-scripts` first failed EACCES in the shared npm cache. One bounded retry used `--cache /tmp/g7-page-frontend-intake-npm-cache` and passed, with no lockfile edit/global setting change. npm reported6 dependency audit advisories; this is not a completed dependency-security audit or permission to update versions.

Commands executed:

```text
# cwd templates/_bundled/raonslab-travel_lab
npm run type-check
npm run test:run -- --maxWorkers=1 --no-file-parallelism --reporter=json --outputFile=/tmp/g7-campaign-frontend-intake-template.json
npm run test:run -- __tests__/layouts/contract.test.ts --maxWorkers=1 --no-file-parallelism --reporter=json --outputFile=/tmp/g7-campaign-frontend-intake-current-contract.json
# after authorized one-line Board expectation correction:
npm run test:run -- __tests__/layouts/contract.test.ts --maxWorkers=1 --no-file-parallelism --reporter=json --outputFile=/tmp/g7-campaign-frontend-intake-final-contract.json

# cwd modules/_bundled/raonslab-travel_lab
../../../node_modules/.bin/vitest run --config vitest.config.ts resources/js/__tests__/layouts/admin-travel-lab-campaigns.test.tsx --maxWorkers=1 --no-file-parallelism --reporter=json --outputFile=/tmp/g7-campaign-frontend-intake-admin.json
```

Type-check (`tsc --noEmit`, src-only configured scope) PASS exit0. A first admin invocation combining relative --config and --root incorrectly doubled its config path, exited1 before any tests; correcting cwd/config resolved it without source changes.

| Run | Total | PASS | FAIL | JSON SHA-256 |
|---|---:|---:|---:|---|
| `template` | 160 | 160 | 0 | `de2db7b8475602a88716f36c1b55c1adb94d3fe0bb42e5e70338f957cfce0650` |
| `admin` | 9 | 9 | 0 | `cbe2aa54507fccf70a3bf128f7073f661f5a65c9d915b2a95e30244bb57151dd` |
| `current-contract` | 30 | 29 | 1 | `6b1bb0626c62c2d35cd7b7c3484b9ea16c93a38bc4e577fb2c127bf8a36addff` |
| `final-contract` | 30 | 30 | 0 | `8231fd25aeb07b0fddca68273016d5d8291491ce1dc39d7f30f65478f50e46ee` |

- Whole template:12 files,160/160 tests; PageBody8 and campaigns12 included. Other files: inquiryKey21, travelComponents15, admin-api-contract8, cart13, contract30, help-edit3, home-search7, installed-response10, product6, requests-help27.
- Module campaign admin:1 file,9/9 tests. These use native layout helpers and mock network/basic components, not a real Page admin browser/permission proof.
- Current contract fail-first:29 PASS/1 FAIL, expected Board>=1.1.2 but actual>=1.1.3. Authorized expectation-only correction then30/30 PASS, exit0. These are reruns of the same30 tests; do not add them to the169 unique template+admin cases.
- Existing jsdom Window.scrollTo-not-implemented warnings occurred; assertions passed. No warnings were hidden by a code change.

JSON reports were temporary test-runner output; this report preserves commands, counts, phase separation and hashes as durable review evidence. Parent may retain safe raw outputs if needed. No full160 rerun after metadata-only correction was required or performed. No author test counts from the implementation child were included.

## Frozen source pins

The following are review-time WORKINGTREE pins. Most campaign files match95d16543; template manifest and the one-line compatibility fixture represent the separately stated lead-authorized transition. These are not a new fixed Git SHA.

| Path | SHA-256 |
|---|---|
| `templates/_bundled/raonslab-travel_lab/docs/editor-spec.md` | `f04315cab2fa3e133b4faf2a8b6f65ffe3bf973609da9745d8a019d0d039108b` |
| `templates/_bundled/raonslab-travel_lab/src/components/composite/PageBody.tsx` | `79c839f2b28b346f0f6639c08c153bc81938d26cd937ea42c04e93f26a77fc43` |
| `templates/_bundled/raonslab-travel_lab/layouts/travel/campaign_detail.json` | `43cdc90cc64d1b73c515cb1359a86b618b9a5c2521a17e5a4f50ec55c99739f2` |
| `templates/_bundled/raonslab-travel_lab/layouts/travel/campaigns.json` | `58cbcbdff9e3ef838797a912c9434beebd713c8cdafc298d9f6905320490fdf2` |
| `templates/_bundled/raonslab-travel_lab/layouts/travel/home.json` | `529e63690e62cd7b56804d81ae24120e4fb193e3d473b9b583b6d3d9e4911f43` |
| `templates/_bundled/raonslab-travel_lab/routes.json` | `6fced1f37c941ecbad8b5cfe1d0c20ee7f0c10b3dd7a494c2b5ba83c06014f28` |
| `templates/_bundled/raonslab-travel_lab/components.json` | `d948fdb9eb16d68feefc7fe81caa3f43a70360a5686cfa218e26763b023f46ed` |
| `modules/_bundled/raonslab-travel_lab/resources/layouts/admin/admin_travel_lab_campaigns.json` | `f52767d26664b1a5d5be3623b46f7171580377143f79348640899707e5163a5f` |
| `modules/_bundled/raonslab-travel_lab/editor-spec.json` | `8e07d6d05d2b53ba8afc16ce644a5482dfa37eb72b00aa5d7262733a8cb5b6a0` |
| `templates/_bundled/raonslab-travel_lab/__tests__/layouts/contract.test.ts` | `9504de7ead079ba57cc1f2bcfd5401d1d66bede755605e4fc5ef02f7991a41db` |
| `templates/_bundled/raonslab-travel_lab/template.json` | `28268a1d971ddfc3080a059955bc21d101e0eca7bb750004ecbd89bdadac4991` |
| `templates/_bundled/raonslab-travel_lab/package-lock.json` | `f78c69c971e2884304d3907816cb1ff441ce005ccf2b5239f1cb58445725188f` |
| `resources/js/core/template-engine/ActionDispatcher.ts` | `bb57838bde3849631f31c6b0e0947bbef84bb885548749f5fa7ee1008b524f68` |

## Required next verification

Lead should freeze the integrated candidate, run the native no-sourcemap production build/install after the active browser completes, and bind installed/source/bundle hashes. Require independent390/1440 native Page create/edit→version, unpublish/removal+404 even for admin, restore/re-publish, registry alias/nonregistry404, malicious HTML/no remote request, both campaign catalog filters and product/cart/inquiry continuation. Include scoped read-only/create/update authorization and actual API error/retry/empty states. Native Page attachments, CLI provisioning guards/rerun preservation, SEO invalidation and backend tests are separate review scopes; this frontend intake does not mark them PASS.

No browser/auth/fixture/DB/environment/service action was performed by this reviewer. Live fa552 evidence and the active independent Request are unchanged. Reviewer mutations outside this report were only the explicitly authorized one-line contract-test compatibility expectation and authored editor-spec documentation correction; lead owns publication and final integration.


## Subsequent author fix: scoped campaign write navigation abilities

**Status: AUTHOR_SOURCE_AND_UNIT_PASS; independent source/browser verification pending.** Lead subsequently authorized this reviewer to implement the small CF-UI-02 UX repair. Consequently the original nonauthor95d16543 intake above remains a review of that earlier source, and does **not** independently approve the subsequent authored repair. Its exact pre-append report SHA-256 is `d7d8df5924acd4fb069ac801d5aea34efa92c352921f945c444798b61ac159cf`; that complete body is preserved as this report's prefix.

Only two implementation/test files below changed. Four Button conditions were added: each existing slot's edit button requires that exact row's `abilities.can_update === true`; each missing slot's create button requires native collection `campaign_pages.data.abilities.can_create === true`. Unknown/absent ability fails closed. No role shortcut, inherited row creation permission or client permission grant was introduced. Detail/versions navigation remains available to permitted readers, missing-scope wording and native create hints remain unchanged, and publish already keeps its existing row-update check. Native API/service scope and write checks remain authoritative.

The rendering fixture now reflects actual PageCollection's collection abilities beside data/meta and independent PageResource row abilities. The existing positive admin case asserts edit/create availability. The read-only case asserts no edit/publish button while retaining two detail/version links and existing no-update messages. New tests assert collection can_create=false hides both create buttons without replacing the scope-absence message, true enables creation even when collection can_update=false, and absent collection creation permission cannot be supplied by another row's can_create=true. These are actual native layout-helper rendering tests with mocked responses/basic components, not real role/HTTP/browser permission verification.

| Focused12-test phase | PASS | FAIL | JSON SHA-256 |
|---|---:|---:|---|
| `fail-first` | 9 | 3 | `b0c3fe1140579179f37ca412c14c35082fcd0d6a3edf000456fa0a17e624214d` |
| `fixed` | 10 | 2 | `535ef3be6bb3f6d50572ed36489c8ad75e0447bdaf7c3c73ea0a1904e982e041` |
| `final` | 12 | 0 | `e37d2bb548df86672df32624a375b2df49b2a32fd7e8e1122cd772e0d4ac3cb3` |

Fail-first used unchanged campaign layout with the new expectations:9 PASS/3 FAIL reproducing ungated read-only/edit and no-create navigation. First repair attempt had an incorrectly generated `travel-lab-campaign-slot-…` lookup instead of the exact registry slug;10 PASS/2 FAIL caught hidden permitted edit links. Only those two lookup strings were corrected, then12/12 PASS,0 skipped,exit0. Both negative phases are preserved rather than relabeled PASS. The new file has12 unique tests, replacing the prior9-test version; do not sum all three reruns or count the previous9 again.

Executed command (module cwd), once for each phase with the corresponding output name:

```text
../../../node_modules/.bin/vitest run --config vitest.config.ts resources/js/__tests__/layouts/admin-travel-lab-campaigns.test.tsx --maxWorkers=1 --no-file-parallelism --reporter=json --outputFile=/tmp/g7-campaign-admin-ability-{fail-first,fixed,final}.json
```

No template component/code changed, so no template160 rerun or production build was performed for this JSON-only fix. Native extension lifecycle remains lead-owned. `git diff --check` passed for the two files and appended report; source JSON parses successfully. No API/signature/version/locale/DB/env/service/runtime or other product source changed in this repair phase.

| Frozen author repair file | SHA-256 |
|---|---|
| `modules/_bundled/raonslab-travel_lab/resources/layouts/admin/admin_travel_lab_campaigns.json` | `32ac9d46844dcdada237a680c43f10f78c822b4e07c9abe8c2a2baac93d0861b` |
| `modules/_bundled/raonslab-travel_lab/resources/js/__tests__/layouts/admin-travel-lab-campaigns.test.tsx` | `456a71beabb778e875bc2163356fca3e48c3bc6d9b45a6ae51622b1d910224a9` |

Scoped tracked two-file diff SHA-256: `82c65bf9bf41d1cbff2378c09069343b754c63f990b0acb928c6df48ba329718`. These pins are a workingtree repair, not a fixed Git revision. Lead is a nonauthor for this small repair and must review the exact native ability paths/source, then preserve a fixed candidate. The eventual independent Page browser should verify actual read-only/no-create and allowed creator/editor roles at390/1440 alongside native publish/version/restore/customer journeys. CF-UI-02 is resolved by this author at source/unit scope only; no canonical Validation or live permission PASS is claimed.
