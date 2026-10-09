# W04 campaign independent actual browser verification

**Overall: FAIL — native campaign admin Retry remains in the error state after a successful Page-list response at both 390 and 1440.** Native Page creation, edit, publish, draft exclusion, version restore and the bounded customer/support regressions passed. This is independent browser evidence, not an official Validation receipt or release approval. Product source was read-only; delivery is local evidence commits only.

## Fixed target and boundaries

- Request: `req_fc51a817f76d40eca856b390222911fc`, own assigned isolated workspace. Existing origin `feat/g7-travel-lab-c7ae42d1` fetched, then explicitly detached at **`31a18318f91dde34a9c75eaaac65ae1d434b7bc3`**, tree **`c0984e682a77153753edcde318a6d44b6973b1c0`**. Default6853 is not the tested source. Evidence commits descend from31a and contain only this report and `tests/W04_CAMPAIGN_BROWSER/`.
- Actual parent APP: `http://127.0.0.1:18871`, `req81_travel_lab`; installed travel module/template **0.1.3/0.1.3**, Board **1.1.3**. The separate TEST schema/request was wholly offlimits and its results are not relabeled as this verifier's results.
- Read root/affected extension AGENTS, campaign contract/activation, common binding final/intake, historical native admin final and browser recheck. Those historical results retain their original pins. Author175/2936/Page abilities12/asset11pairs and earlier attachment14/rapid12 are context, not tests rerun here.
- Only the explicitly allowed private handoff was read in-process: source/APP/exact loopback, expiry22:30UTC, file0600/directory0700 and nonsymlink validated. Native synthetic login issued own tokens; supplied tokens used only for native auth. Private originals/contact/token ledgers are0700/0600, ignored, never committed. No other Request writes, SQL, env/platform credentials, grants/settings/service/cache/config edits, build/cache flush, product fixes, push, PR, merge, deployment or official child.
- Chromium **156.0.8078.4**, Playwright1.60, native SPA on390×1000 touch/tap and1440×1000 mouse.18 retained harness phases (including API-only audit)/62 actual browser contexts;19:56:13–20:32:38UTC on2026-10-09. Phase times, reconstructed/replay commands and exit evidence limits are in [execution-index.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/execution-index.json); original PTY transcripts and dependency-command optional flags were not persisted. Synthetic fixture preparation through native APIs is distinguished from UI creation.

## Source, installed runtime and served provenance

[source-binding.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/source-binding.json) retains the before and after raw inventory. [binding-audit.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/binding-audit.json) separately diagnoses the collector and verifies installed versions/module bundles. [closing-binding.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/closing-binding.json) confirms the same selection and served assets remained identical after the final bounded browser follow-up.

| Selected active group | Fixed/installed files matching |
|---|---:|
| Core app/routes/resources/bootstrap app | 1334/1334 |
| Travel / Board / ecommerce / Page modules | 125/125;338/338;1178/1178;84/84 |
| Public build | 7/7 |
| Travel / native admin templates | 108/108;1212/1212 |
| Supplemental public index/bootstrap providers and root language | 2/2;90/90 |

Active selection totals4386 plus92 supplemental files; local fixed source also matches. Selected installed extras0. Before/after selected source/cache inventory is unchanged. Optional `sirsoft-basic`463 files are absent: its broad inventory fails, execution NOT_RUN. Root vendor implementation/autoload, root config/env/storage/DB, non-route/hook bootstrap caches and other packages are excluded; this is not whole-machine parity. Immutable extension config source **is included**. Route/hook cache bytes/hashes were inventoried read-only; no semantic cache/config/DB claim.

Direct served11 assets match fixed Git, shell9 asset bindings match (native CSS relative URL rewriting accounted for), and actual module bundle JS/CSS equal ecommerce's fixed single global asset segment. Browser response hashes independently bind the engine, travel/admin IIFEs and native fonts/CSS. No build or generated-cache replacement was used.

| Required pin | SHA-256 |
|---|---|
| Core engine served/source | `b8cf27fab58e108b1509c379a5f4d0860a21a90bc591bb34b48a94d6e56cb76d` |
| ActionDispatcher installed/source | `bb57838bde3849631f31c6b0e0947bbef84bb885548749f5fa7ee1008b524f68` |
| Board controller installed/source | `d368cb0108765094f6739c3f3a110d7e597fff6606ad367971943dc091a6c816` |
| Travel template IIFE served/source | `8941442d1a921c2f366063359e39f79ed3a719bce77a174b6c0aa5657b691d63` |

The inherited collector `after` command returned **exit1**: it compares absent `during_verification_*` keys against after supplements and emits false summary flags. Both supplemental sets actually appear in before/after and have identical files/digests. The raw exit/false fields remain unchanged. The separate binding audit returns0 with explicit actual comparisons. Inherited scope/timing wording incorrectly says extension config excluded/supplements not initially collected; actual selection and timestamps take precedence. Optional basic absence is separately disclosed; there are no selected active byte mismatches.

## Actual Page and customer results

| Check | 390 | 1440 | Evidence / limit |
|---|---|---|---|
| Home published cards, list, detail, `/page/:slug` alias; real navigation | PASS | PASS | campaign6/public-states1, native200 and trusted input |
| Missing slot adapter → real native create with exact slug prefill | PASS | PASS | Original7/8 temporarily renamed through native edit; own16/17 created201 via actual `/admin/pages/create?slug=…` |
| Native HTML checkbox → text, title/body/editor/save | PASS | PASS | Default HTMLtrue; text checkbox and actual textarea, native201 version1. This is content HtmlEditor, not general layout editor preview |
| Adapter publish, native edit HTML/save, version1 restore, unpublish/republish | PASS | PASS | Actual PATCH200/PUT200/restorePOST200; v1→v2→restoredv3 with changed public title/body/mode/version. Publish toggle does not create content version |
| Draft excluded through travel API/home/list/detail/alias, including admin | PASS | PASS | Native guest/member/admin APIs; guest SPA transitions and authenticated member/admin SPA draft exclusion; no static fallback |
| Unknown/nonregistry slug | PASS | PASS | Actual404 APIs and detail/alias screen |
| Home/list/detail loading, actual network error and native Retry | PASS | PASS | Native response held/aborted, then actual200; no manufactured JSON/mock API |
| Empty published campaign list/home absence | PASS | PASS | Temporary native unpublish only7/8, actual empty response, restored |
| Published Page with zero matching catalog trips | NOT_RUN | NOT_RUN | Real catalog has2 nature/2 wellness products; unrelated fixtures not altered to manufacture zero |
| Adapter loading | PASS | PASS | Actual native request held |
| Adapter error → Retry recovery | **FAIL** | **FAIL** | Native200 returns2 Pages, error remains and0 titles render; same-context full reload restores2 titles |
| Page guest401/member and other-member403; admin UI denial | PASS | PASS | Native list/read/create/update denied, member admin-auth403→login redirect; no write controls |
| Four adapter create/edit controls versus scoped actor abilities | BLOCKED | BLOCKED | Permitted admin exercises create/edit for both slots and both widths. No prepared read-only/self-scope Page actors; no roles/users/grants manufactured. Negative member denial passed; a full scoped-abilities matrix is not claimed |
| Nature/wellness registry filters, chips, Korean search, product detail | PASS | PASS | Actual native catalog200/CTA query/product card; culture/city full regression NOT_RUN |
| Cart quantity2→3→2, server13700×2=27400, notice and inquiry201 | PASS | PASS | Native UI, submitted cart IDs/contact/key only; no client price |
| Native admin → owner requery/relogin → terminal flow | PASS | PASS |390 UNDER_REVIEW→TEST_ACCEPTED→owner cancel200;1440 UNDER_REVIEW→DECLINED, owner has no cancel control/native409 |
| Response lost after server201 → reload → same key/ID native200 | PASS | PASS | Upstream real201 committed, only delivery aborted; items/events/amount/calculation/API allocation unchanged. New intent key differs; SQL NOT_RUN |
| Request-list mobile overflow | PASS | PASS | Actual document width390/1440 equals viewport |
| Native notice/FAQ/private question/admin answer | PASS | PASS | Native UI create201/edit200/read200; private question owner requery/admin answer201, other-member404; attachments not created |
| Own fixtures/original properties/tokens cleanup | PASS | PASS | Final native readback,47 own tokens401;3 supplied tokens remain200 |

Primary successful evidence is [campaign-6.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/campaign-6.json), [transaction-2.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/transaction-2.json) for390, [transaction-3.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/transaction-3.json) for1440, [support-1.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/support-1.json) and [public-states-1.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/public-states-1.json). Successful segments do not erase failed segments. [required-matrix.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/required-matrix.json) preserves all14 original actions/effects/axes and marks the full cross-product NOT_RUN, with separate current incremental results.

## Concrete defect and bounded diagnosis

**Campaign admin Retry, both widths:** native login → actual `/admin/travel-lab/campaigns` → abort only Page-list network request → remove abort → tap/click `campaign-pages-retry`. The native list responds200 with2 Pages. After1500ms the error banner is still visible, loading is false, titles0. The administrator cannot recover the campaign controls using Retry. Actual same-context reload clears the banner and renders2 titles. [adapter-retry-2.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/adapter-retry-2.json) and [bounded-followup-1.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/bounded-followup-1.json) reproduce this without source changes. [390 screenshot](../../tests/W04_CAMPAIGN_BROWSER/evidence/adapter-retry-repro-adapter-retry-2-390.png), [1440 screenshot](../../tests/W04_CAMPAIGN_BROWSER/evidence/adapter-retry-repro-adapter-retry-2-1440.png). Severity assessment: P2 recovery defect; cause not authoritatively assigned.

Desktop native inquiry row menu locator-click failed repeatedly: trusted pointer events occurred, but the menu did not appear. The initial action button is horizontally outside the viewport. Fresh-context coordinate mouse after explicit scroll and keyboard Enter both succeeded, then explicit scroll+750ms stabilization followed by ordinary mouse click also succeeded and navigated to detail. Retain [menu-repro-1.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/menu-repro-1.json) FAIL and follow-up PASS. This narrows an automation/scroll timing issue; it does **not** prove universally broken desktop pointer navigation or a permission defect. Desktop lifecycle itself passed through actual native detail controls.

Raw campaign1 home-count timing failure; campaign2 syntax failure with no execution; campaign3/4 restore locator/duplicate-dialog failures; transaction1 incomplete shipping fixture/cleanup validation failures; transaction2 wrong native decline label; initial member denial waiting for an unreachable Page-list response; and duplicate screenshot-name failure remain in their own JSON/scripts. Bounded corrections touched verifier scripts/own fixtures only. campaign5 passed functional flows; campaign6 repeated to safely recapture native-create screenshots. No older report/negative output was overwritten.

## Body safety and cache truth

Inert invalid-domain link/image/style/script/form probes were entered through the real native HTML editor at both widths. Native save response retained the raw probe unchanged (`backend_htmlpurifier_changed:false`). The inspected native Page path has no demonstrated HTMLPurifier step and the campaign resource deliberately passes raw localized body. **Backend HTMLPurifier execution: NOT_RUN/unproven for this Page path**; server persistence is not reported as sanitized. This differs from Board/backend sanitization contracts.

Actual customer PageBody uses strict DOMPurify: rendered formatting and link text retained; no descendant attributes, `href/src/style/data/aria`, link/image/script/form/input/SVG/iframe elements, or executed probe. The wrapper retains its native class/test/content-mode attributes; the assertion concerns untrusted content descendants. Text mode escapes literal `<em>` and retains line breaks. Customer PageBody remote attempts0 at both widths. Native admin general HTML preview attempted invalid fixture media URLs;81 attempts across retained admin contexts were aborted before network connection. They are not customer PageBody failures, nor evidence that general native preview has the same policy. Across62 browser contexts: pageerrors0, observed external HTTP connections0. The guard prevents external HTTP requests; this is a browser observation, not a packet-level or DB notification-adapter audit. The raw check's historical “real purifier” label is retained; the false backend-change flag and this explanation govern the actual result.

Actual bot-style requests to home/list/detail/alias returned200 SPA shells64906bytes without synthetic title/body. No accessible SEO Page body was served. API and actual SPA transitions requery the persisted fresh body; **served SEO body/cache invalidation and semantic page-cache metadata: NOT_RUN**. Route/hook cache byte stability and script/listener source review are not a fake callback cache PASS. Preview restart, migrations/provisioner reruns, PHP/JS author suites/build, full old14 matrix, existing attachment14 and independent TEST permission validation: NOT_RUN here.

## Cleanup, privacy and delivery

[inspect.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/inspect.json) is the actual final-audit script output: an unused `W04_PHASE` environment variable left its argv phase label at `inspect`; no file was overwritten. Original Page7/8 ID, slug, localized title/body, mode, SEO and publication match the private first-save baseline digests, and original version1 snapshots remain. Current histories11/9 are retained; native edit/publication timestamps changed legitimately. No pristine DB or whole-row hash preservation claim.

Only own new Pages9–17 were native-deleted and native404 rechecked. Own products89–93 hidden/unpublished, departures145–149 inactive/reserved0/public404. Own shipping policies9–11 inactive/nondefault; existing reference policy6/category26/product70 read-only. Own inquiries175–180 are fiveCANCELLED/oneDECLINED; both supplied members' carts0. Own native support posts118–123 soft-deleted with public404; question answers remain only within their deleted own parent records. No attachments created. No original Page or unrelated row deleted. Native logout rechecked all47 unique own issued tokens401; supplied3 handoff tokens remain200 unchanged. Repeated ledger audits are not additional issued tokens.

Screenshots hide editable values and mask known actor/contact/auth values before saving, then undergo exact-value/pattern and OCR scans. A subsequent actor-name scan detected8 initial PNGs; their raw originals were retained0600 in ignored private quarantine and excluded from Git. Their safe JSON hash references remain; privacy manifest explains missing PNGs. Safe native-create recaptures are campaign6. No raw credentials/contact/email/UUID/signed URL or OCR text is published. Final safe-artifact hashes, scope and provenance are in `manifest.json`; historical hashes are never silently replaced.

One native internal nonauthor helper completed bounded evidence review **PASS** at local checkpoint `f235e7c313cbccc792eaaefb2a8c2188f376217c`:173 manifest hashes/4478 selected source bytes, metrics/cleanup/privacy and14 critical screenshots checked; no substantive findings. [internal-review.json](../../tests/W04_CAMPAIGN_BROWSER/evidence/internal-review.json) records the fixed target and limits. Subsequent changes add review/safety metadata, clarify command/phase/wrapper wording and refresh hashes; raw product outputs and PNGs are unchanged. Root+helper are one Request (internal helper count1, observed peak2 agents), not separate PCs or official Validation. Official children0. Hosted CI runs **0 / NOT_RUN**; canonical independent Validation receipt **absent / NOT_RUN**, neither waived. No product test/build or CI result is invented. Evidence commits are local only; no remote integration/deployment claimed.
