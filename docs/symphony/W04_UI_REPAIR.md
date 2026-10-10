# W04 UI repair evidence — frozen author handoff

The frozen UI repair passed **75 focused tests**, type checking and the production build. Actual native-browser checks reached **8 unique functional cases PASS** across 390px and 1440px, with two initial post-repair failures preserved and two bounded follow-up checks. These are **implementer WORKINGTREE checks**, not independent fixed-SHA Validation, full regression or release approval. Support API throttling remains **OPEN, root-owned** in this handoff.

Author: native `/root/w03_ui_repairs`, within parent Request `req_81ac33cac94046b9a2249cd14c0d00ba`. The author has frozen implementation; this report adds no product, runtime or DB changes. Git publication, integration, version changes, native lifecycle updates and official child attempts belong to the lead.

## Source and portable evidence binding

The preview baseline was `7de0c4441b68b1c012dbf4a75114200e322051c9`. The actual Git HEAD recorded during these checks was `ae9d823c19a9c5aeeb869a6a6b1487ef17497a96`, with candidate repairs in the WORKINGTREE. Neither value alone identifies the tested repaired tree; the file hashes below bind the candidate UI and tests.

Portable evidence: [w03-ui-boundary-repair.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/w03-ui-boundary-repair.json), generated `2026-10-09T13:17:18+00:00`. It preserves before, before-help, after and after-retry source mappings, sanitized observations, failures and exact fixture cleanup records.

| Binding | SHA-256 / definition |
| --- | --- |
| Portable evidence JSON | `c9b0faf1b8e48271b5194f60e91089e15e138cc65033902f73ec108b807b9407` |
| 14-file inventory digest | `04bcf33558199245bd158893c31ff8c125282fd27df8fd142f4de221a792c12a` |
| Inventory digest definition | `SHA256(json.dumps(source_files,sort_keys=True).encode())` |
| Owned tracked binary diff at implementation handoff | `45ac618f83cb30039412ad42a03e9376aeb376c0d942b7b8324b6baf96e0864c` |
| Production JS, bundled and installed after lead resync | `9cc99d8d8db0161cfc5f537531eb16f00d1a7ca272fd0b31824823185f46b390` |

The following 14 paths are a verification inventory, not a claim that all 14 changed. In particular `dist/index.d.ts` is included as a checked build artifact. The portable evidence JSON is a separate artifact. All inventory hashes were rechecked when writing this report. The owned tracked-diff digest describes the earlier implementation handoff, not this report or the later lead-owned tree.

| Source / build / test path | SHA-256 |
| --- | --- |
| [templates/_bundled/raonslab-travel_lab/src/handlers/inquiryKey.ts](../../templates/_bundled/raonslab-travel_lab/src/handlers/inquiryKey.ts) | `56da7f5628fb739913eccfbc3c9d1ffb8227602fe1ee20e2ac156c02cf193282` |
| [templates/_bundled/raonslab-travel_lab/layouts/_user_base.json](../../templates/_bundled/raonslab-travel_lab/layouts/_user_base.json) | `0aa7219abf51d716ffc8f08b5117d2eb472782ec6e3d4f645c31168a7afbe254` |
| [templates/_bundled/raonslab-travel_lab/layouts/travel/help.json](../../templates/_bundled/raonslab-travel_lab/layouts/travel/help.json) | `ac03e403769d46ba03135143796712aa8e316f2f0de7f175fc3f20c5337d1300` |
| [templates/_bundled/raonslab-travel_lab/lang/ko.json](../../templates/_bundled/raonslab-travel_lab/lang/ko.json) | `bb2d948e8564c8fab26c507ae29ef9ef38c19fab68d5672ad0279cdc400659c0` |
| [templates/_bundled/raonslab-travel_lab/lang/en.json](../../templates/_bundled/raonslab-travel_lab/lang/en.json) | `1c2e2b201457ed7ae6ecac0a586931a3da295dd50a00e6ed9ce89ce24f615c55` |
| [templates/_bundled/raonslab-travel_lab/__tests__/components/inquiryKey.test.ts](../../templates/_bundled/raonslab-travel_lab/__tests__/components/inquiryKey.test.ts) | `64b33f591176186929f9c84d9ab54ab68107ae988949350ff45259f4b39c95a9` |
| [templates/_bundled/raonslab-travel_lab/__tests__/layouts/admin-api-contract.test.tsx](../../templates/_bundled/raonslab-travel_lab/__tests__/layouts/admin-api-contract.test.tsx) | `9c0b65bf2e8260b87f5ad01b4aac6c21a3566e972bbb5dd9520b409c22e7f9ff` |
| [templates/_bundled/raonslab-travel_lab/__tests__/layouts/help-edit.test.tsx](../../templates/_bundled/raonslab-travel_lab/__tests__/layouts/help-edit.test.tsx) | `be8a59096660b3ff340dd0a51e87ead2b77f1900aeb1a6cbaa259027f0c6cd3a` |
| [templates/_bundled/raonslab-travel_lab/__tests__/layouts/contract.test.ts](../../templates/_bundled/raonslab-travel_lab/__tests__/layouts/contract.test.ts) | `93ecbdc92e70f03e256fc479b53c273bc423c8fe17704fef04965d66e4706d79` |
| [templates/_bundled/raonslab-travel_lab/scripts/triage-ui-boundaries.mjs](../../templates/_bundled/raonslab-travel_lab/scripts/triage-ui-boundaries.mjs) | `69b27cc96ed35479937b0eadb64135b9779715f5f82da215bb71aea7e0ca970b` |
| [templates/_bundled/raonslab-travel_lab/dist/js/components.iife.js](../../templates/_bundled/raonslab-travel_lab/dist/js/components.iife.js) | `9cc99d8d8db0161cfc5f537531eb16f00d1a7ca272fd0b31824823185f46b390` |
| [templates/_bundled/raonslab-travel_lab/dist/css/components.css](../../templates/_bundled/raonslab-travel_lab/dist/css/components.css) | `2750062e8f93d635d9f126f6249852fe370725e3d7abd77d6c09cf58c6507529` |
| [templates/_bundled/raonslab-travel_lab/dist/index.d.ts](../../templates/_bundled/raonslab-travel_lab/dist/index.d.ts) | `241e110368cd5c0726cb16be34582f82e86f02ea13c088f8e042bd5a9fed06fe` |
| [modules/_bundled/raonslab-travel_lab/resources/layouts/admin/admin_travel_lab_catalog.json](../../modules/_bundled/raonslab-travel_lab/resources/layouts/admin/admin_travel_lab_catalog.json) | `ea031efad85eb59b75abc9d16aa1aed3c7c33089b303567c99d09889b4c539fb` |

## Repairs and native contracts

- Inquiry preparation resolves the current owner from the live native `G7Core.state.get().currentUser` UUID/id before falling back to supplied owner data. This closes restoration caused by a stale data-source callback owner. Native logout success clears persisted and in-memory inquiry key/body/contact state on both desktop and mobile before another account logs in. The earlier stable prepared-payload, reload and quota/tombstone handling remains covered by the focused handler/cart tests.
- Native admin catalog loading now watches only the required `catalog` data source. An optional, unfetched `travel_candidates` source no longer leaves the entire catalog permanently unable to receive pointer events. The actual Next button can navigate to page 2.
- Owner-only private question editing uses the native support PATCH endpoint with only title/content, native bearer authentication, prefilled fields and list/detail refresh after success. A 422 preserves inputs for retry; cancel sends no PATCH. The UI hides editing for `is_mine !== true`; server ownership enforcement remains a backend contract and requires independent verification.
- The existing travel link uses the supported numeric native ecommerce product edit route `/admin/ecommerce/products/{product.id}/edit`. This pass proves navigation, not the entire ecommerce editing lifecycle. No ecommerce core, price calculation or shipping reference table was changed by this UI author.
- The owned template contract assertion follows the lead's `requires.g7_version >=7.0.12` change. Backend, module and version contracts remain lead-owned.

## Focused checks and production build

Commands ran from `templates/_bundled/raonslab-travel_lab`. Results are recorded in the portable evidence; the report-writing task did not rerun tests or rebuild.

| Command | Result | Scope |
| --- | --- | --- |
| `npm run test:run -- __tests__/components/inquiryKey.test.ts __tests__/layouts/admin-api-contract.test.tsx __tests__/layouts/help-edit.test.tsx __tests__/layouts/contract.test.ts` | PASS: 62 tests, 9.06s | Owner isolation, optional-source loading, help editing, manifest/API contracts |
| `npm run test:run -- __tests__/layouts/cart.test.tsx` | PASS: 13 tests, 8.23s | Cart regression; jsdom emits unimplemented `window.scrollTo` warnings |
| `npm run type-check` | PASS | Template TypeScript |
| `G7_BUILD_SOURCEMAP=0 npm run build` | PASS: 35 modules, 4.87s | JS 38.22kB, CSS 47.65kB; no `sourceMappingURL` |
| Whole frontend suite | NOT_RUN in this bounded repair | 75 focused PASS must not be expanded to the whole suite |
| Full PHP/core regression, remote CI, canonical Validation | NOT_CLAIMED by this author | Lead/independent gates remain separate |

Fail-first checks reproduced two stale-identity cases and one optional-source blur case in the same 5.41s run. Before the help edit control was implemented, its new file reproduced two failures and passed one negative-owner visibility case in 3.28s. These failures precede the focused green results above.

The lead performed native module/template resync. The author did not run lifecycle commands, restart services or copy product source into the installed extension. The browser helper checked five bundled/installed files: JS, user base, cart, help and admin catalog layout. The first before run matched the baseline; the second before-help run preserved those same installed baseline hashes while bundled candidate files had changed. Both after phases matched all five candidate hashes. This mapping is partial provenance for the affected UI, not a hash proof of the entire application or DB.

## Actual browser results, including failed first checks

The [author Playwright helper](../../templates/_bundled/raonslab-travel_lab/scripts/triage-ui-boundaries.mjs) used real native login, native UI dispatch and API persistence on the parent's isolated APP preview. A real upstream inquiry POST returned 201 before the helper dropped only its response. No fake authentication or mocked server acceptance was substituted.

| Unique functional scenario | 390px final | 1440px final | Exact scope |
| --- | --- | --- | --- |
| Response loss, native logout, different UUID login, pending owner isolation | PASS | PASS | New account cart empty, no old pending payload/contact recovery |
| Native admin Next pointer | PASS | PASS | Real pointer hit and page 2 navigation |
| Numeric native ecommerce product edit link | PASS | PASS after follow-up | Real navigation to the supported native edit route |
| Owner private question creation/edit/reload | PASS | PASS after follow-up | POST 201, UI PATCH 200, persisted text after reload |

The original before and before-help phases reproduced the four defects at both widths while the installed baseline UI remained unchanged. After the repair was installed, the **first after run was 6 PASS / 2 FAIL**. Both failures remain in the portable JSON:

1. At 1440px the native edit-link check encountered `page.goto net::ERR_NETWORK_CHANGED`. A bounded fresh-navigation follow-up passed. This is recorded as an initial failure followed by successful navigation, not erased or labeled an uninterrupted pass.
2. At 1440px private question creation returned **429**, rather than the expected 201. The follow-up used `other_member`, a distinct authenticated throttle bucket, and passed POST 201 → UI edit PATCH 200 → reload persistence. This does not prove the original account's throttle condition was corrected.

Only those two affected 1440px checks were repeated. Final coverage is **8 unique cases**, not 10 cases obtained by counting retries. Phase record counts include observations and cleanup: before 12, before-help 4, after 15, after-retry 3. They are not functional test totals.

Before phases recorded cleanup of exactly the author's two inquiries and two questions. After phases recorded exactly two own inquiry cancellations and two own question cleanups, including the follow-up fixture. Question cleanup used the authorized native admin soft-delete path with subsequent owner 404 checks; inquiry cleanup used each fixture's matching owner token. There was no broad deletion, SQL cleanup, real order/payment/mail or author change to shipping reference data. Cleanup of this author's fixtures does not establish cleanup of the separate interrupted official child's fixtures.

## Support 429: OPEN, root-owned

`SUPPORT_THROTTLE_SHARED_KEY` remains **OPEN** here. The direct runtime observation is the initial 1440px support-question POST 429 and the distinct-member follow-up POST 201. Source inspection at that time found unprefixed `throttle:600,1`, `throttle:120,1` and `throttle:10,1` middleware in the module's support routes. Laravel's limiter combines its prefix with `resolveRequestSignature`, so empty prefixes can share a same-user counter across these read/write middleware. This is the source-based explanation reported to the backend lead, not an independently proven remediation.

The UI author did not change these PHP routes. The lead owns distinct limiter prefixes and revalidation. A later repaired fixed SHA must include and independently test any backend throttle correction. Native shipping policy creation's missing `shipping_types` reference data was a separate isolated-package issue owned by the lead; successful product-route navigation does not close that package/admin lifecycle coverage.

## Portable evidence, screenshots and privacy

The helper's ignored runtime JSON files are under `storage/framework/testing/travel-ui-boundary-triage/`: `before.json`, `before-help.json`, `after.json` and `after-retry.json`. Their sanitized result structures and source mappings are embedded in the Git-deliverable portable evidence linked above; consumers need not depend on these local-only files.

The helper guards the parent Request lab root, access expiry, expected source binding, loopback preview and private regular-file permissions (directory 0700, JSON/access 0600). It requires an ancestor baseline SHA and checks the affected installed mappings after resync. Published observations exclude credentials and redact role credential values, email patterns, Bearer values and synthetic contact literals. No private access JSON, environment values, tokens, passwords or actual contact values are included in this report.

**New screenshots: NOT_CAPTURED.** This bounded helper produced no PNGs. The existing [W03 evidence directory](evidence/W03/) contains 69 sanitized PNGs from the earlier independent review at `28ada286c1c34606741bcfe4f9d12e06ac50af30`; they remain immutable historical evidence and do not prove this repaired candidate UI. The interrupted official child had 100 partial PNGs in its own `docs/symphony/evidence/W03_RECHECK_BROWSER/` tree. That directory is not imported into this parent's evidence, and partial screenshots must undergo their own redaction/source review before acceptance. See [interruption intake](W03_BROWSER_INTERRUPTION_INTAKE.md); its cleanup and product PASS are not established by partial artifacts.

## Remaining independent gates and handoff

The lead must pin a **new repaired SHA**, including these UI repairs and the lead's support-throttle/core fixes, before official `w03-browser-recheck` attempt 2. Preserve original attempt 1 INTERRUPTED and its old failures; do not reuse the original 7de baseline as the repaired verification target. The lead assigns a nonauthor review of the frozen handler/auth/help/catalog changes before Git delivery.

The independent attempt still needs actual 390px/1440px screenshots and full user/admin traversal, including unchanged-account response-loss retry/reload/contact handling, foreign-member data and question/attachment access, catalog registration plus departure writes, commerce/options/price validation, last-seat concurrency, idempotency, status transitions and restart persistence. Fresh install/recovery, full G7 regression, remote fixed-head CI and formal Validation remain their respective separate gates. This author evidence does not replace them, claim overall W04 completion or claim customer-approved design/production deployment.

Implementation and this report are frozen for lead review. No staging, commit, push or merge was performed by the native UI author.
