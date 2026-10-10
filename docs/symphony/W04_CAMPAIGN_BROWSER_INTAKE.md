# W04 campaign browser evidence intake

## Decision and ownership

**ACCEPT the bounded public evidence package; retain the product FAIL.** No additional evidence-integrity or publication blocker was found in this intake. Native campaign admin Retry still fails at 390px and 1440px after a successful Page-list HTTP 200. Reload is a demonstrated workaround, not closure. The separate UI owner must fix the product and obtain verification at a new fixed source revision.

This is a read-only intake by native `/root/w03_commerce_guards` in parent Request `req_81ac33cac94046b9a2249cd14c0d00ba`. The reviewer did not author these browser scripts, recordings, campaign UI, or HTTP probes. Previous authorship of Board/core/travel repair scopes is not independently re-approved by this evidence review. No browser/API/SQL rerun, private handoff/env/baseline/quarantine access, runtime/service/process mutation, Git delivery, or official Validation occurred. Only this report was written.

## Fixed inputs

| Item | Actual binding |
|---|---|
| Browser Request | `req_fc51a817f76d40eca856b390222911fc` |
| Tested product | `31a18318f91dde34a9c75eaaac65ae1d434b7bc3` |
| Tested tree | `c0984e682a77153753edcde318a6d44b6973b1c0` |
| First evidence commit | `f235e7c313cbccc792eaaefb2a8c2188f376217c`; parent is the tested product |
| Final evidence commit | `ca21cb7fc276bde3b373ba01a5c18c7448973d3a`; parent is `f235e7c3…` |
| Report | `docs/symphony/W04_CAMPAIGN_BROWSER_FINAL.md` |
| Scripts and evidence | `tests/W04_CAMPAIGN_BROWSER/` |

The complete product-to-final diff has **177 paths**, all inside the two assigned public scopes. The final commit changes exactly seven metadata/report paths: the report and `MANIFEST.sha256`, `execution-index.json`, `internal-review.json`, `manifest.json`, `privacy.json`, `safety-provenance.json`. Raw browser result JSON and all PNGs are unchanged from the internal review target `f235e7c3…`. There are no product source changes in these evidence commits.

## Independent checks performed

Read-only commands used `git rev-parse`, `git diff --name-only`, `git ls-tree`, `git show`, and Python JSON/hash aggregation. A `git cat-file --batch` comparison recomputed SHA256 from the actual tested Git blobs, rather than trusting the recorded `equal` flags. Manifest checks recomputed SHA256 for every referenced public blob. No supplied helper was executed: several helpers legitimately consume the child's private handoff during their own execution, which is outside this intake's access scope.

| Intake check | Result |
|---|---|
| `MANIFEST.sha256` and JSON manifest | **175/175** blob digests agree in each representation; self-referential manifest files explicitly excluded |
| Selected source bytes against product Git | **4,478/4,478** agree; 4,386 active + 92 supplemental |
| Before/after/closing selected inventories | All selected group rows agree; route/hook cache inventories agree; no selected installed extras |
| Direct served assets | Before and after each record **11/11** HTTP 200 with exact source/served digest equality |
| Public shell assets | Before and after each record **9/9** HTTP 200 with expected served digests; four CSS assets use documented relative URL rewriting |
| Actual recorded contexts | Recomputed **62**, with zero page errors, zero observed external HTTP connections, and 81 intercepted external attempts |
| Issued-token cleanup | Recomputed **47 unique** own token hashes, all with HTTP 401 requery; three supplied handoff tokens retain HTTP 200 |
| Public paths/PNG audits | 105 committed PNGs match stored successful OCR digests; eight initial quarantine paths are absent from Git |

Active source counts are core 1,334; travel module 125; Board 338; ecommerce 1,178; Page 84; public build 7; travel template 108; native admin template 1,212. Supplements are two core entrypoints and 90 root language files. This is a selected inventory, not all runtime dependencies: root vendor implementations/autoload, root config/env, storage, DB, and other bootstrap caches remain excluded. Extension configuration source is included. Optional `sirsoft-basic` has 463 absent files and remains NOT_RUN.

Important pinned digests are core engine `b8cf27fab58e108b1509c379a5f4d0860a21a90bc591bb34b48a94d6e56cb76d`, travel template IIFE `8941442d1a921c2f366063359e39f79ed3a719bce77a174b6c0aa5657b691d63`, and Board AttachmentController `d368cb0108765094f6739c3f3a110d7e597fff6606ad367971943dc091a6c816`. ActionDispatcher is among the independently compared selected Git blobs; the report's source digest is `bb57838bde3849631f31c6b0e0947bbef84bb885548749f5fa7ee1008b524f68`. These hashes bind source/recorded served assets; this intake did not rerequest the preview.

The inherited source collector's **exit 1 remains preserved**. Its summary compares absent `during_verification_*` keys with after supplements and emits false fields. `binding-audit.json`, source rows, and closing inventory support the narrower before/after equality diagnosis. This intake does not relabel the original command as a successful execution or invent an unseen during-verification snapshot.

## Original failures and bounded follow-up

`adapter-retry-2.json` records both widths: trusted touch/pointer Retry, native Page-list 200 with two rows, a further 1,500ms wait, visible error, loading false, and zero displayed slot titles. `bounded-followup-1.json` again retains `productRetryStatus: FAIL`; same-context full reload clears the error and displays two titles. The mobile failure PNG was directly viewed during intake and agrees with the recorded error state. No causal product-code conclusion is inferred solely from the screenshot.

`menu-repro-1.json` retains the desktop locator-pointer FAIL and successful coordinate/keyboard controls. The original measured button starts at x=1,568.9 outside the 1,440px viewport. The bounded follow-up explicitly scrolls, stabilizes for 750ms, then records an ordinary mouse-pointer success, visible menu, and detail navigation. This supports a scrolling/timing qualification, not a universally broken desktop menu or permission bypass. Earlier raw transaction/menu failures remain present.

Earlier negative harness phases are also retained: initial home timing, syntax failure with no browser execution, restore locator/dialog issues, incomplete shipping fixture, wrong decline label, member-denial wait at an unreachable Page-list endpoint, and duplicate screenshot-name rejection. Later successful steps are reported separately. The final index labels phase commands as reconstructed replay commands, not retained PTY transcripts; optional dependency-command flags are unrecovered.

## Working behaviors evidenced at the original target

`campaign-6.json` demonstrates both widths with actual native Page create, slug prefill, edit, publish, version restore, and customer rendering. Own Pages 16/17 return native create 201; publish returns 200 at version 1; HTML edit advances to 2; restore returns 200 and creates version 3 with restored text. Drafts disappear from guest/member/admin travel projections and return detail 404; unknown registry aliases are also denied. Customer loading/error/Retry and real empty published campaign list are exercised separately from the failed admin adapter Retry.

HTML fixture body is stored unchanged (`backend_htmlpurifier_changed: false`). The historical raw check label “real purifier” is not proof of backend purification. Customer PageBody DOM evidence records formatting/text preservation with prohibited descendants/attributes absent and zero remote attempts at both widths. General native admin HTML preview generated the 81 invalid-fixture media attempts, intercepted before connection. Browser interception is not a server outbound-adapter or packet audit. Bot-style Page observations return a 64,906-byte SPA shell; they do not establish served SEO body or cache invalidation correctness.

Transaction evidence preserves native price/count/cart/request behavior: quantity 2→3→2, server 13,700×2=27,400, explicit test notice and request 201. Mobile admin review/test acceptance/customer requery/relogin/cancel and desktop review/decline/customer requery/terminal cancellation 409 are present. Lost successful response followed by reload/retry returns the same request ID/key with native 200 and unchanged API item/event/calculation/allocation snapshots. These are native API observations, not SQL row audits or concurrency tests. Request-list geometry equals each viewport. Native notice/FAQ/private-question/answer UI and foreign-member 404 are recorded at both widths; no attachment was created.

## Cleanup and publication hygiene

`inspect.json` and the final-audit helper agree on original Pages 7/8: slug, localized title/content, mode, SEO metadata, and publication properties match recorded baseline digests. Original version-1 content remains. Histories are now **11/9**, and legitimate publication/edit timestamps changed. This is property restoration with retained audit history, not pristine whole-row/DB restoration.

Own Pages 9–17 return native 404 after deletion; products 89–93 are unpublished/hidden and return public 404; departures 145–149 are inactive with reserved zero. Own shipping policies 9–11 are inactive/nondefault. Inquiries 175–180 are five CANCELLED and one DECLINED; both member carts are empty. Own support posts 118–123 are soft-deleted/public 404, with answers only on own deleted parents. Existing product/category/shipping policy references were read-only. No token plaintext was used by this intake: 44 final-audit token hashes plus three bounded-follow-up hashes make the 47 distinct HTTP-401 records.

Five privacy scans preserve the initial eight OCR failures and quarantine decision. All eight rejected PNG filenames are absent from tracked public paths; private quarantine files were not opened. All 105 published PNG digests have recorded successful OCR checks. The helper's exact-private-value checks are inherited evidence, not independently rerun with private values. This reviewer independently scanned **72 public text blobs** for email, bearer payload, Sanctum/JWT/signature values and phone patterns: no sensitive candidate remained; all 40 phone-pattern candidates were substrings of SHA256 digests. No env, SQL dump, `.pyc`, or `__pycache__` publication path was present.

Exactly **two** public PNGs were directly viewed, source-hash checked against Git, and independently OCR-scanned without persisting/outputting OCR text: `adapter-retry-repro-adapter-retry-2-390.png` and `html-campaign-6-1440.png`. No email/token/signature/phone pattern was found in those two. They show the mobile error and desktop safe formatted customer body. This reviewer did not visually inspect all 105 PNGs. Stored internal helper review at `f235e7c3…` records 173 then-current manifest checks, 4,478 source comparisons, and 14 critical PNG views; final manifest growth to 175 reflects metadata additions, not contradiction.

## Remaining gates

Product Retry repair and independent actual browser recheck are required at a new revision. Scoped read-only/self-owned Page actor UI remains BLOCKED, because matching actors/grants were unavailable; the all-powerful synthetic admin does not substitute for that matrix. Served SEO/cache semantics, backend HTML purification, unrelated culture/city and full filter bounds, no-matching-catalog campaign case, actual SQL/concurrency/external-adapter internals, preview restart, migration/provision reruns, attachment matrix, PHP/JS/build suites, root vendor dependency binding, and the original 14-scenario full action/effect/axis matrix remain NOT_RUN in this browser execution.

Recorded internal review is one supported native helper, peak two agents in one Request, zero official children; it is not two PCs or a canonical Validation receipt. Hosted CI and official Validation are NOT_RUN, and this result does not itself establish remote push, PR, merge, deployment, or whole-product completion. This intake grants no permission and leaves the original fixed-target **FAIL** intact.
