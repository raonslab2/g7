# W01 shared integration review

Decision: **CHANGES_REQUIRED**, bounded to the 16 pinned shared files listed below.
Reviewer: native `/root/commerce_contract`, a nonimplementer of this shared scope.
Base: `2114703d`; scope digest: `4d49cb81d611221e5df3f820914db50497901237fd7f0d3e905ee68fde5b70f2`.
Each SHA256 matched before review and immediately before this report. No shared
implementation was changed. The workflow services, checkout listener, workflow
resources and workflow tests implemented by this reviewer are explicitly outside
independent approval. Official Validation and whole-product approval: **NOT_RUN**.

## Findings

No P0/P1 findings in this bounded inspection.

- **P2 SHARED-01 — lock order can cycle during admin departure edits and inquiry submission.**
  `src/Repositories/CatalogRepository.php:143` locks TravelProduct, then Departure,
  then ProductOption; its `DB::transaction` uses the default single attempt.
  The consumer submission sequence locks Departure/Product/Option and then
  TravelProduct during published-product validation. An admin can hold
  TravelProduct while waiting for Departure, while submission holds Departure
  and waits for TravelProduct. The native DB exception has no domain handling in
  CatalogService or Admin CatalogController, so a deadlock victim may receive
  an unhandled failure rather than an allowed conflict/retry. This is static
  opposite-order evidence; MySQL concurrency reproduction is **NOT_RUN** in this
  review. Harmonize the locking order or add bounded native deadlock retry with
  an actual isolated MySQL contention test before claiming the gate PASS.
- **P2 SHARED-02 — same-day visibility contract differs at the workflow boundary.**
  CatalogRepository line37 requires `departure_date > today`; domain regression
  `test_reduced_stock_limits_available_seats_without_hiding_remaining_seats`
  asserts same-day exclusion. The current workflow accepts today, so a known
  departure ID or stale cart can submit a departure hidden by catalog.
  The lead accepted this finding and assigned a separate workflow correction.
  That correction is outside this shared pinned review; it must have its own
  changed-file evidence and cannot be self-approved here.

## Checks and bounded conclusions

- Module lifecycle: nonempty declared DatabaseSeeder prevents core-seeder fallback;
  exact `inquiries` permission/menu namespace now matches workflow consumers.
  Module/category/leaf permission descriptions are present as required by
  ModuleManager direct array access. Inquiry ownership and route metadata use
  `user_id` / `inquiry`. Menu shape matches ExtensionMenuSyncHelper and its role
  access path; the additional `permission` key is not consumed by that helper,
  so it is not asserted to replace native menu role access.
- Provider uses the supported module identifier alias and four interface mappings;
  parent register/boot retain native migrations/translations. Configuration merge
  and console registration perform no direct DB access and do not override core
  repositories. Referenced hook classes exist. Native filter registration runs
  synchronously; checkout listener registration presence was checked, while its
  implementation is outside independent scope.
- The uppercase enum has one transition source; terminal states have no next state,
  TEST_ACCEPTED remains cancellable. Inquiry casts full calculation_snapshot and
  exposes ordered events. InquiryEvent has explicit actor FK and created_at cast.
- Both migrations have down paths and documented columns. Compile-only native
  MySQL grammar with prefix `g7_` produced **18 index/constraint names, maximum
  53 characters** with native `prefix_indexes=true` (50 when false), including actual `g7_users` targets for user/actor and native
  commerce FK targets. This used PDO SQLite memory only as a nonnull connection
  placeholder in `pretend`; **no SQL was executed and no MySQL connection was
  opened**. A first null-PDO compile attempt failed with no reconnector; the
  corrected compile completed. This is not a MySQL migration-run PASS.
- Catalog price SQL wraps table/column names through the active grammar, preserving
  prefix-aware references and bound filter inputs. Public list/detail/departures/
  facets share visibility. Effective availability is min(capacity, stock)-reserved.
  Admin update locks actual option/departure, disallows option reuse/replacement,
  capacity below reservations and historical date rewrites; SHARED-01 limits the
  concurrent-update conclusion.
- Synthetic seeder assigns explicit active nondefault KR FREE/pickup/no-extra-fee
  policy through native ShippingPolicyService and ProductService; no trade tables
  are directly written. Rerun preserves existing products, options, metadata and
  reservations. An operator-modified existing sample policy is preserved and must
  be rejected by workflow eligibility rather than silently overwritten.
- Support provisioning requires both explicit isolated/provisioning booleans,
  default false. Notification suppression recognizes only the three exact travel
  board slugs from target or Post/Comment/Report board relationship and returns
  context.skip before native recipient/template dispatch. Ordinary-board data is
  returned unchanged. Native support runtime tests are **NOT_RUN** here.
- Both frontend language root JSON files parse and refer to existing same-locale
  partial files, matching TemplateService fragment resolution. No preexisting
  extension public API was edited in this 16-file scope; new bindings/helpers are
  module-owned. Dependency manifest inspection was supporting read-only evidence,
  outside the pinned approval scope.

## Executed evidence

- Per-file SHA256 verification: **PASS 16/16**.
- `php -l` of all 14 PHP scope files: **PASS**.
- `php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter='CatalogDomainTest|SchemaAndSeederTest'`:
  **PASS 22 tests / 402 assertions**, 21.355 seconds, 96.50 MB,
  PHP8.3.6 / PHPUnit11.5.56 / SQLite memory, actual domain models/migrations.
- Native MySQL grammar `pretend` prefix compilation: **PASS**, execution **NOT_RUN**.
- Shared MySQL DB, preview server and production services: not accessed or mutated.
- Hosted CI, independent official Validation, full browser/product approval:
  **NOT_RUN**. No commit/stage/push/deploy by this reviewer.

## Fixed file manifest

| Path | SHA256 |
|---|---|
| `modules/_bundled/raonslab-travel_lab/module.php` | `0b436be33ef1df2e63de3b4dccc5dd3bbbb7fec27af0df90d2c0833e1bdc2339` |
| `modules/_bundled/raonslab-travel_lab/src/Providers/TravelLabServiceProvider.php` | `ddfcabfc5c5b86193a0931634e552c40f08a291399d51307c55041657935a04b` |
| `modules/_bundled/raonslab-travel_lab/src/Enums/InquiryStatus.php` | `a168d2e952ff64873f53b412b679e5848dd6de4c1c571086c1835e4961ed3db1` |
| `modules/_bundled/raonslab-travel_lab/src/Models/Inquiry.php` | `2aafdc59abd4cc32db1bb61962a692ae7c52a0f73793c79b2d7d9ec257c413ae` |
| `modules/_bundled/raonslab-travel_lab/src/Models/InquiryEvent.php` | `2e300e84776e78dcba2aebb568b62365b358506b5c210d86f87a56c95570529f` |
| `modules/_bundled/raonslab-travel_lab/src/Models/Departure.php` | `ec7f5e98c2cc0b79f9c2b9ff0632d1790696068020e703003a6907dd12a092e9` |
| `modules/_bundled/raonslab-travel_lab/database/migrations/2026_10_09_000001_create_travel_lab_tables.php` | `b36466a9064fae5bf3f9beab15b84dbf7f21a5ecbaf3b9f6b68cd55ba46d6561` |
| `modules/_bundled/raonslab-travel_lab/database/migrations/2026_10_09_900001_add_inquiry_evidence.php` | `c40f9b460619c5efb6006ffacf00647395573f72373d16aa23200d15d0ebd776` |
| `modules/_bundled/raonslab-travel_lab/database/seeders/SyntheticCatalogHelper.php` | `f5743601292fbca9c2b41a208a03e06e1df226e1e2322b472a3fccda6b22234f` |
| `modules/_bundled/raonslab-travel_lab/src/Repositories/CatalogRepository.php` | `fa00a0ee2b0c509cd006530c91322038ac0b4dd9a96f6548e256b92131dbee7d` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/AdminCatalogCollection.php` | `bcf1104bf6f335da81611e75716ef1a01a3deef976e2c3b85bbf3cc58b78d0c9` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Resources/AdminCatalogResource.php` | `a438488b72925232c8908a528bf506e66f91382ce8d43b97b425410889441f7e` |
| `modules/_bundled/raonslab-travel_lab/src/Listeners/SuppressTravelSupportNotifications.php` | `7079d91df238488595a0512e2867aee2873267672150fbff78348315822fe5ce` |
| `modules/_bundled/raonslab-travel_lab/config/support.php` | `545368558ae09dc62452cfed53b5bd5873410fe2f16aeec80c1075037c52befa` |
| `modules/_bundled/raonslab-travel_lab/resources/lang/ko.json` | `9997f147604d69ed8a419cfa29f3017a525e06b04cf762ac25f3d1b6296189b1` |
| `modules/_bundled/raonslab-travel_lab/resources/lang/en.json` | `582d8ce817e2b7289d8bf4fdfb934f3dbc11c25356ed869cd146a16cd71ee9ac` |
