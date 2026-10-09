# W04 Page-backed campaigns — backend intake and isolated regression

## Decision and attribution

Campaign backend source review and the supplied focused SQLite suite are **bounded PASS: 20 tests / 384 assertions**. The first expanded integration run found a checkout-test source-origin harness failure, preserved below; the separately authorized test-only repair passes its focused three cases. The final expanded SQLite regression is **PASS: 175 tests / 2936 assertions**. No product/backend defect requiring a source change was established in this review.

The original official Page implementation child remains **canonical FAILED, cause UNKNOWN**. Its saved product commit `c85eea30ca674289648c772f7f36ea4cdaa1611f` has 78 changed paths and parent `992f9a65ac3f8957e5ec618f21072dc499053810`; lead intake was `95d16543`. Recovering that Git source and passing native local tests does not change the canonical child state, supply an official Validation receipt, or prove deployed/installed behavior.

This reviewer did not implement the Campaign feature. The narrowly assigned checkout harness repair below is reviewer-authored, so that repair's verification is author evidence awaiting another review. The Campaign backend evaluation and the checkout-harness authorship are kept distinct. Lead owns product/package/version changes, final Git delivery and runtime/independent verification. This task edits only this report and the separately authorized `TravelCheckoutGuardTest.php`; no production source, environment files, APP/TEST databases, installed extensions or services were changed.

## Inputs and source binding

Read root/travel/Page development guides, backend ResponseHelper and repository rules, `W04_CAMPAIGN_PAGE_CONTRACT.md`, the saved child diff, new Campaign PHP source/tests and corresponding native Page service/model/repository/controllers/listeners. All **29 changed travel-module PHP inputs** from the saved child commit matched current worktree bytes before and after the Campaign run. This comparison includes provider, route entry, module declaration and the supplied bootstrap; it is not a claim that all documentation/package files remain identical after lead metadata work.

The supplied bootstrap adds bundled ecommerce/Page/travel PSR-4 sources and a prepend loader to prevent installed src classmaps winning. `ModuleTestCase` uses an intentionally absent test environment path; after configuration load it replaces the **entire database connection map with SQLite `:memory:` only**. Each case asserts the actual connection driver/name. It fakes settings storage and keeps mail/session array and queue sync. Campaign fixture registers actual native Page provider/migrations/listeners, Page and campaign routes, and the actual native DatabaseStore quota cache on that same SQLite connection. It does not mock PageService or its repositories.

Native Page hook registration and route mounts in this fixture are explicit test setup for bundled source. They do not certify installed ModuleManager discovery or installed application route metadata. The SEO cache-interface recorder in the two invalidation tests is a test double; those assertions prove actual native Page lifecycle hook delivery and requested invalidation operations, not physical cache deletion.

## Reviewed backend contracts

| Contract | Observed source and focused evidence |
| --- | --- |
| Exactly two curated slots | `CampaignRegistry` reads the bundled registry directly, validates count2, syntax, uniqueness, enum filters and art variant, and returns immutable slot objects. Runtime config overrides cannot add slots. Registry test checks exact two slugs/queries and admin/template parity. |
| Publication and arbitrary Page isolation | `CampaignPageRepository` calls native `PageService::getPublishedPageBySlug($slug, false)` with literal false and rechecks `published === true`. `CampaignService` rejects nonregistry slugs before native lookup. Missing/draft/unregistered return 404; list drops missing/draft slots, independently of guest/member/Page administrator. |
| Native admin preview remains distinct | Actual native Page public API permits an administrator's draft preview; Campaign API denies the same draft. The focused test exercises that contrast rather than globally changing native preview. No campaign preview bypass is added. |
| Minimal public projection | List is the exact ten-field projection; detail adds only content/mode/update time. No Page/admin IDs, actors, versions, attachments, signed previews, SEO metadata or abilities are serialized. Published persisted title/body and locale fallback are exercised. List has a bounded plain excerpt; raw HTML detail goes to the separately reviewed formatting-only client renderer, not a server HTML-sanitization claim. |
| Request/response behavior | FormRequests prohibit scope-widening selectors, constrain route slug and prevent preview behavior. Named GET routes use the existing optional-auth marker and dedicated numeric600 atomic quota prefix. Native ResponseHelper produces success/404/422 envelopes. Route metadata/header checks pass; authenticated601/anonymous contention are not newly executed here. |
| Admin native authorization | Actual native Page admin routes/real role and permission rows return guest401, catalog-only administrator403, reader publish403, and self-scoped foreign list exclusion/detail403/publish403. Page permissions/row abilities remain native; no travel writing API is added. Native slug filter remains substring matching even when the valid `starts_with` operator is supplied, so UI exact mapping is required and tested. |
| Native lifecycle/version semantics | Native create/update/unpublish/restore/republish execute; content revisions increase versions, publishing alone does not create a content version, restore creates a new version and does not implicitly republish. Native audit rows are checked. |
| Explicit provisioning and actor state | Default flagfalse, confirmation/actor/real Admin read+create permissions and local adapters are checked before writes. Only missing slots use native PageService create. Existing drafts/edited content/IDs/versions/publication remain identical on rerun. Guard actor is cleared/restored in finally and no token is created. Default module seed creates no Pages. |
| Native SEO/search jobs | Travel listener declares sync callbacks for actual native create/update/publish/restore/delete hooks and includes old registered slug from update snapshot. It invalidates travel home/list/detail and `/page/{slug}` aliases, ignores unrelated Pages and preserves native listener behavior. Native Page SEO listener still updates/deindexes sitemap resources and dispatches `GenerateSitemapJob`; no claim of zero jobs or XML generation PASS is made. Native keyword query source groups published scope with keyword predicates; core search/MySQL FULLTEXT/external indexing are not dynamically covered by these Campaign tests. |

No Page table/version model/new writing repository was introduced. Existing native Page writes preserve their own scope checks, snapshots and hooks. Provisioning's per-page transactions and explicit uniqueness conflict do not promise atomic creation of both slots; it never upserts an existing row. The public package runner's marked-schema/actor execution and version synchronization remain lead-owned integration obligations.

Cache failure handling logs the native-style invalidation failure and allows Page writes to finish. Therefore this review does not certify draft disappearance from stale physical bot cache after a backend failure; the agreed first-cut no-response-cache/`G7_STATIC_CACHE=false` policy and an installed cache transition probe remain required. This is a scope/coverage limitation, not a newly reproduced cache disclosure.

## Executed commands and initial results

```sh
php vendor/bin/phpunit --no-configuration --bootstrap modules/_bundled/raonslab-travel_lab/tests/bootstrap.php modules/_bundled/raonslab-travel_lab/tests/Feature/Campaign
php vendor/bin/pint --test <reviewed Campaign PHP source and Feature/Campaign paths>
php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml
php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter TravelCheckoutGuardTest
php vendor/bin/pint --test modules/_bundled/raonslab-travel_lab/tests/Feature/TravelCheckoutGuardTest.php
git diff --check -- modules/_bundled/raonslab-travel_lab/tests/Feature/TravelCheckoutGuardTest.php
```

| Execution | Actual result |
| --- | --- |
| Focused Campaign suite | **PASS 20 / 384**, 21.208 s / 103 MiB, PHP8.3.6 / PHPUnit11.5.56 |
| Focused reviewed Campaign source/tests Pint | **PASS** |
| Initial requested expanded run with mistaken config path at module root | **CLI_ERROR exit2 / no tests or bootstrap**: no such `modules/_bundled/raonslab-travel_lab/phpunit.xml`; original log retained, corrected to `tests/phpunit.xml` |
| Correct canonical expanded run before harness repair | **FAIL 175 / 2917 / 1 failure**, 77.747 s / 311 MiB |
| Focused checkout harness after repair | **PASS 3 / 61**, 2.751 s / 70.5 MiB |
| Checkout harness Pint/check | **PASS** |
| Final canonical expanded run after changed harness | **PASS 175 / 2936**, 90.541 s / 311 MiB |

The canonical suite excludes the four native-MySQL support classes named in its config; no extra test exclusion was introduced. Its SQLite coverage includes the new Campaign cases and existing travel domain/workflow/security regression. It is not a native MySQL support/module installation run.

## Preserved failure and narrowly assigned test repair

The failing case was `TravelCheckoutGuardTest::test_guard_uses_product_and_option_membership_and_preserves_ordinary_commerce`, line79 before repair. It expected bundled module SHA-256 `83ce3cb92ecca87ad5e9d302bc90b96fdfd835066a3b5ab8e40f71fa7e08b393`, but the long-lived native test boot had already loaded installed `Module` bytes `f350cb830b51041b1a22a43152a9c7c2d60ad06cf50b8573e9fc8e2c3b1dcf09`. The assertion failed before that method reached its membership checks. The original full failure is not relabeled as a checkout-product PASS or discarded.

Lead then explicitly assigned the one test file. The fixture now inspects the bundled module and checkout listener in a clean PHP subprocess, asserts exact source paths/current source hashes/listener declaration/hook arrays, and proves the parent's already loaded declaration remains unchanged. It does not accept an arbitrary old hash or redefine the Module class in the existing application.

Original direct ordinary-commerce and mismatched product/option guard assertions remain. The first two native HTTP checkout/direct OrderProcessingService tests are unchanged. Added assertions exercise actual native HookListenerRegistrar/HookManager callbacks for **all four declared sync hooks**, allowing ordinary product/option input and rejecting travel-option membership. This is source-backed test registration, not a live installed hook-registration PASS. No price/order/payment/stock assertion was removed or bypassed.

## Final expanded regression

Final run after the changed fixture: **PASS 175 tests / 2936 assertions**, 90.541 s / 311 MiB, exit0. Command is the same canonical `tests/phpunit.xml` suite, executed once after the authorized repair. The prior failure, not-found config attempt and focused test logs remain separate. No repeated unchanged-head loop was performed. All 29 Campaign PHP inputs still match the saved child commit after this final run; only the separately assigned checkout test changed.

## Source and evidence pins (SHA-256)

| Input/output | SHA-256 |
| --- | --- |
| Contract | `0f6dd26920e09c781579f9dc8c1d4d77fdb5ca49533dfaf17714270b61fe46cb` |
| Campaign registry source | `d1572fde50c55b85567b01a088684f1e101b698e3729fff9c8d60552c022ff3b` |
| Registry config | `c9c0e0d475a00860cbd96481124135606cefd70fe0c9657f0c069286460c2838` |
| Campaign repository adapter | `2437d0fdc0e55ff0a6daf847773efae04c5da742656b2b9fbc167f5f4fac0a65` |
| Campaign service | `aace717697973c072e6a1854a5b65cd3300c54393fd14e46e6c0637a33c16a92` |
| Provisioner | `ca1a20d6cb4c8158c9995ec01cef4b1f0b312c7002cde490857892c8aa90cdda` |
| Campaign resource | `f61994c349d58c119678de945c071fcfd25fe5f62edb56e694f78c0b7c81485d` |
| Travel SEO listener | `7ab30d22b6010fa5752d3e070aae493dc680c0550dae422bd6c39b412162c68c` |
| Campaign routes | `792ed9b6494a01779d58f99fafc80943748dcd52f74b835566d31900e122e897` |
| Campaign controller | `86e87ccbe98cf543056133a15d333ec0c485b36f053dce3848f4d86a597d63e7` |
| Native PageService | `6f9c57ccd7fc08aae4a8972c2c0ba545806ae4aa58dc6f0be3b8f1991803a287` |
| Native PageRepository | `b8b8fe1e241b1ee7c0ac9e255bbac3da2ce7d6fd9141158d9bf032161bfe363a` |
| Native Page SEO listener | `374a3c0b8f0e73ad80a97399f95fb179d26185e10a41e6dafdf82dd27fb72ef9` |
| Bundled module declaration | `83ce3cb92ecca87ad5e9d302bc90b96fdfd835066a3b5ab8e40f71fa7e08b393` |
| Bundled checkout listener | `f81b898f22f5d4c00d9c26614660df369bce86507cf01ae53dd74072fd11d36e` |
| Repaired checkout test | `a5ad8f047a3287cb74c01e17d288fc8cffd485cd2271419497e1a90c72e91fec` |
| SQLite bootstrap | `8829cd4d732d03d5ac9f4bbdf2c57cfdebcbe3d96aae89c1607b3adc83966274` |
| CampaignTestCase | `90598b012cdc734435fbf03b055ea2c3c4401f712adb01b61322019c08eccf4a` |
| ModuleTestCase | `3ad3c1ce5e11ac52e53734d0397862688c2312081b57589b784fe4fa048a1f58` |
| Canonical test config | `03749f72165cb28eee3bed83c3bc1a1eb1701540250070bb248f660fc7eef7a6` |
| Focused Campaign full log | `e440ea294e19d78c516db82ab1761b2096a0bc072b0645f93c5d1bddfdd88f1f` |
| Wrong config CLI-error log | `850cbce18b9ad3f80e118b5a2f7c68596c428e5dca4045d4e382c050bbd01f92` |
| Initial canonical expanded FAIL log | `f942cb94c3e4ad50a22e77cee8dd30518c79d9eab1a031b054f30c6d799841dc` |
| Repaired checkout focused log | `db1efa58435ba0d3e1b290ffeb4fb59f057364af559250880ea1396b96824f61` |
| Final canonical expanded PASS log | `201e43e3dbf93d5b1c9f62780710b6272a51c4d488e7da7a2d3d705de67851e4` |

Ignored full logs remain under `storage/framework/testing/w04-campaign-*`, with mode0600; public evidence consists of these safe diagnostics/counts/pins. No credential/contact dump or raw SQL data is published in this intake.

## Outstanding gates

Native installed discovery/commands/MySQL/source sync, actual admin editor/version/publish controls, 390px/1440px browser behavior, actual cache/search/sitemap transitions, whole package/recovery, frontend renderer/build and changed final CI/Validation remain separate. Other agents/lead may already have their own results; none is counted as this review's run. Root must obtain fixed-version integration and nonauthor review of the checkout harness before using this evidence for a release decision. Original Page child FAILED/cause UNKNOWN remains in the canonical record.
