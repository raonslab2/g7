# W04 native admin browser — read-only result intake

**Decision: evidence chain is suitable for lead integration; product completion remains CHANGES_REQUIRED.** The independent browser owner demonstrates real native CREATE and bounded customer/admin/recovery journeys at 390px and 1440px. Two preserved UI failures require triage or repair. This intake is a nonauthor review of that owner's artifacts, not a new browser run, canonical Validation, hosted CI or independent SQL verification. This reviewer previously authored travel UI repairs; this intake does not turn that author into the independent product validator.

Canonical child `req_c2e43e4188284ec78b8702e7e3e0d45a` was reported **COMPLETED** by the lead. Canonical completion is separate from product PASS. Source under test is `fa5523175ac494cfbd13bbf89bf06b3ec91835a6`, tree `fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c`; runtime reported loopback port18871, travel module/template0.1.2, Chromium156.0.8078.4. Only public Git artifacts and two already-sanitized child PNGs were read. No private access/ledger/environment file, DB, live endpoint, service, configuration or product source was modified or executed.

## Git chain, ownership and reachability

The original chain is linear:

`fa552317 → d1e85b719f17cf40acbecd7f11b03e3432a4556b → c3a61a26bb21b68dd6ee5eb3bcd4c5412ee6434f → 0bc302d748e22ec023ef2e33ee67a7224311e682 → 1d2a4f0bf3ac933504c79bd07dceb68c254de7c4`.

All four original commits are readable in the lead's Git object database. Final child revision was **not** an ancestor of lead HEAD at intake (`git merge-base --is-ancestor … HEAD` exit1). Thus local reachability is proven, integration/publication is not yet claimed. No replacement commit or duplicate child was created by this reviewer.

Diff from fa552 to final child contains292 paths, all within `tests/W04_NATIVE_ADMIN/**`, `docs/symphony/evidence/W04_NATIVE_ADMIN/**`, or `docs/symphony/W04_NATIVE_ADMIN_BROWSER_FINAL.md`. No forbidden product/runtime/DB/environment path was found. Initial source/report negative observations and later evidence-only followups remain separate; do not rewrite them while integrating the chain.

## Source, command and privacy binding

| Intake check | Result and boundary |
|---|---|
| `safe-scripts-manifest.json` |35/35 listed file SHA-256 values independently recomputed from final Git blobs; missing0/mismatch0. These are final safe versions, not assumed to be every initial executed version. |
| `privacy-final.json` |291/291 listed Git blob SHA-256 values recomputed; missing0/mismatch0. Includes191 text files and100 PNGs. The manifest excludes itself normally. |
| Source-binding `before.groups` |All4,817 source SHA-256 values independently match fa552 Git blobs;4,354 are required active paths. The463 optional sirsoft-basic paths are explicitly absent from runtime and NOT_RUN; broad `source_all_equal=false` is preserved. |
| Required active before/after/after_sort_extension |Declared installed SHA-256 values match pinned source for all4,354 required active rows; zero contradictory row-level parity claims. This is review of the collected snapshots, not a new live file measurement. |
| Scenario source |Pinned `tests/scenarios/travel-lab.yaml` SHA-256 independently equals `f87c0f98c1ea3f241f40d876d5814061ba8ce85b48c33b5a02bd95a6d973bb47`.14 actions/effects,9 axes and5 cross-product policies are retained; full Cartesian execution remains NOT_RUN. |
| Supplemental entrypoints/languages |2 entrypoints and90 root-language paths were collected during execution then after; not falsely relabeled initial-before. Original after plus append-only sort closing snapshots retained. |
| Served assets |Public/source evidence separates exact Git-byte matches from modeled native CSS URL rewriting. Root third-party vendor/autoload, config/env/DB/storage and other stated exclusions are not claimed hash-verified. |
| Screenshots |Visually inspected `mobile-final-list-390.png` and `registration-form-1440.png`; both child file digests independently match their final Git blobs. First shows wrapped full synthetic titles, dates, IDs, states and amounts; second shows the real native metadata modal with input/textarea masking and masked account field. No obvious private value was seen in these two frames. |

Privacy scanner source validates scoped private-file modes/binding inside its own process, checks exact known secrets and generic email/UUID/token patterns, and stores only hashes/counts. Shot helper waits for settled DOM, masks text and form inputs, then OCR-checks before retaining a PNG. Final manifest reports all100 PNGs have zero hits and **reuses prior verified OCR for byte-identical PNGs**; this reviewer did not rerun OCR or read the private values. Do not describe this intake as a new exhaustive visual/OCR audit. Initial rejected frames are not delivered or falsely described as newly recaptured historical frames.

`commands.json` distinguishes early runs without exact starting archives from later `executed-scripts` snapshots and command hashes. The source/package helper count is one; this is native collaboration inside the child, not another official Request or PC. Its final narrow package review targets `0bc302d…`, with earlier broader review and contributor self-check separately identified. That internal PASS is not canonical Validation.

## Actual native CREATE and bounded behavior

Initial `create-empty-country-harness.json` records real category POST201 at both widths: categories26/27, root=true, active=true. Final `admin.json` deliberately reuses those **same own UI-created categories**, rather than pretending it creates them again. Policy6/7 and product70/71 have actual201 responses from native UI actions. Script source uses native login/forms, selects the new category, creates a nondefault free KR pickup policy, and fills the native product/option editor. Two products and four options140–143 are distinguished from13 API-only pagination products72–84.

Native travel metadata, translated itinerary, publication and departure136–139 creation have separate catalog evidence; capacity4, native price/option identity and stock validation are retained. Server rejection of capacity7 over stock6 is409; current-KST/past/return-before/invalid-calendar inputs are422. Invalid-calendar wire mutation is an explicit real-server negative test, not an invented response. It does not count as persistence of an invalid departure fixture.

Recorded real journeys cover native server-priced carts/intake201, review/accept or decline, owner reload/relogin/status/cancel, private question creation/edit/native admin answer and foreign404. Price-after-cart evidence distinguishes newly recomputed14500 from unchanged accepted26000 snapshot; native13000 is restored. API/event actor hashes are compared, but mapping auth UUID to numeric actor via independent SQL is NOT_RUN. The heterogeneous sort followup adds13 distinct native prices/dates, actual four sort orders and12+1 pagination at both widths; GET-only final audit independently queries the restored original values. Neither API-only setup nor repeated report/cleanup PASS counts inflate UI CREATE or unique feature totals.

### Response-loss evidence

Six committed-response-loss cases are demonstrated: immediate/reload/sessionStorage-quota at both widths. The harness obtains the **real upstream201**, records the created inquiry, then aborts delivery to the browser. Unchanged retry returns200 with the same full-body hash/key and inquiry ID. Native admin API snapshots compare amount13000/KRW, item IDs/cardinality/quantity/unit/line, event IDs/count/actor hash, calculation hash and departure reserved/capacity before/after; observed retry effects equal upstream effects. This is stronger than a response-only duplicate-ID check, but is explicitly **native API evidence, not independent SQL**.

The edited-contact case aborts before forwarding the first request, then verifies new key/body201. It must not be described as editing the body after an already-committed201. Owner logout/relogin isolation additionally tests removal of pending key/body/contact, foreign recovery absence and owner status retrieval.

The1440 quota report records changed-body409 with inquiry/items/events/cart/departure hash unchanged; same-key/body retry reaches10/min429, waits actual Retry-After window, then returns200 same ID/effects.390 changed-body inquiry/allocation evidence is narrower than a full cart-effects comparison. Public600/workflow120 exhaustion, SQL contention and cache/lock outage503 injection are not part of this browser result.

## Remaining actionable failures

| Issue | Preserved witness | Intake disposition |
|---|---|---|
| Rapid status selection followed immediately by Save sends stale `is_active:true` at both widths | `inactive-ui-toggle-witness.json`, `inactive-exact-date-failed.json` |**OPEN.** Settled1s followup stores false and demonstrates cart409/stale-cart-intake409/reserved0, then native restore/retry201. Waiting is diagnostic evidence, not a source repair or proof immediate-save correctness. Investigate travel departure selector state/commit/save ordering. |
| Initial PC mouse RowActionMenu fails to remain open | `journey-1440-menu-harness.json` |**OPEN observation; exact cause not established.** Keyboard Enter opens the menu and detail click completes later admin journey. Triage real pointer sequence against native menu before deciding product vs harness cause; keyboard fallback does not close mouse behavior. |

No new product defect is inferred from preserved selector/accessibility/settling/bootstrap diagnostics alone. These two explicitly reported failures are sufficient to keep product completion CHANGES_REQUIRED until lead triage/repair and fresh affected verification. Previous fixed-source failures remain immutable even if a new candidate passes.

## Cleanup and side effects

The cleanup harness uses exact own IDs plus own product identity for early cart reconciliation. It does not bulk-delete arbitrary records or change old category/default policy. Public final results record:

- 28 own inquiries147–174 are closed:25 CANCELLED and3 legitimate DECLINED; native cancel on DECLINED is409. Departure native API observations report reserved0. Independent DB inventory is NOT_RUN.
- Both test member carts return200 with count0;43 observed cart fixture IDs are historical setup units, not43 final cart rows.
- 15 own products remain intentionally retained, hidden/unpublished; owned departures inactive. Linked native product DELETE409 is expected protection, not failed cleanup to be bypassed.
- Own policies6/7 and categories26/27 are inactive; policies remain nondefault. Six own support posts are native soft-deleted and read back. These are persisted synthetic remnants, not a claim of a pristine zero-fixture database.
- 13 varied-sort products are re-read through native GET200: product/option12000, original12/25→12/26 dates, hidden/unpublished/inactive and reserved0. Stock restoration is separately recorded.
- All83 recorded newly issued tokens have exact token-hash final native logout401 and auth401; supplied handoff tokens3 remain nativeAuth200. Initial37 issuance entries lack public per-login rows, later46 have them;75 per-token files plus8 aggregate-only entries reconcile to83 distinct. No raw token ledger is committed. This is ledger/native API coverage, not independent token-table inventory; historical six unrecorded tokens remain a separate immutable negative.

An initial incomplete cleanup query remains visible and is closed by later exact-owner200 requery28. The audit respects native cancel20/min429 and records actual wait/retry instead of changing admission settings. Native UI/API setup and cleanup do persist synthetic state; absence of external sends/payment/order mutation is not independently proven by this browser package's SQL/egress tests.

## Limits, fixed pins and next action

Fresh install/migration/seed/rollback, restart/env-loss, independent SQL/race barriers, external dispatch/DB negative assertions, attachments, full editor data-source previews, full native CI/build/typecheck and canonical Validation remain **NOT_RUN** here. Other independent reports may provide these checks at their own exact revisions, but cannot silently fill this browser package's missing coverage. New campaign/Page implementation is outside fa552 and needs new candidate verification.

Public evidence pins at final child `1d2a4f0…`:

| Artifact | SHA-256 |
|---|---|
| `docs/symphony/W04_NATIVE_ADMIN_BROWSER_FINAL.md` | `0c52422e51394fa75669884d0222c85b5c5bda5c36507808b7c5fc03eb80b294` |
| `docs/symphony/evidence/W04_NATIVE_ADMIN/source-binding.json` | `b91e011a4ce1ddd43195d26275917f6d9c89c9da6a2f5a027ba6849f3f4ba8f9` |

Read-only commands: `git show`, `git log --format='%H %P'`, `git diff --name-only fa552… 1d2a…`, `git merge-base --is-ancestor`, JSON parsing and Python SHA-256 over `git show`/`git cat-file --batch` blobs. Two sanitized PNGs viewed from child with byte parity verified. No browser/test process, DB/config/service action, Git mutation or child worktree write was performed. Sole authored path is this intake report.

Lead can now integrate the original evidence chain, retain the negative observations, assign bounded actual UI triage, and pin a new candidate for affected post-fix verification. No automatic release/product PASS follows from evidence integration.
