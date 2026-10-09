# W04 common binding + native attachment — independent NONAUTHOR runtime recheck at fixed fc54b6ae

## Decision

**PASS (bounded)** for the two repaired defects on the actually served/installed fc54 runtime:

1. **Rapid status select → Save (core evaluator repair).** I used a real Chromium at 390 (touch/tap) and 1440 (pointer) in the native G7 admin departure editor. Save was pressed right after choosing the option, with no wait for the rendered label. Every outgoing PUT/POST carried the **latest** native global value, and the server readback matched. At the trusted Save `pointerdown`, the native global was already `false` but the rendered selector still showed “접수 가능”. This is the exact stale-render condition that reproduced the fa552 failure (PUT `true`). It now sends `false`.
2. **Nonimage preview (board 1.1.3 one-line repair).** A real text attachment preview returns **400** with a translated message for admin, owner, other_member and guest. No file bytes, filename or storage path is disclosed. Before the repair it returned 500.

There are no new P0–P2 findings. The original PC menu failure cause stays **UNKNOWN**: every bounded menu run succeeded, and nothing reproduced the original failure.

Scope limits: this is not canonical Validation, not hosted CI (CI checkruns 0 → **NOT_RUN**), and not a release PASS. SQL was **NOT_RUN** by rule. Product code edits: **0**.

| Item | Value |
| --- | --- |
| Request | `req_00485fdfe1eb455dbb11a6575fdd52d7` (depth-one child). Parent/runtime owner: `req_81ac33cac94046b9a2249cd14c0d00ba`. |
| Review target | fetched `origin/feat/g7-travel-lab-c7ae42d1`, detached `fc54b6ae091cd6cef2d0fabc48d2ec4fe4fdba8c`, tree `b377aeabad7f240061ae12cde8fae7ddc6f7e7d1` |
| Baseline failed source | `fa5523175ac494cfbd13bbf89bf06b3ec91835a6` (ActionDispatcher `bfa67b16…`, engine bundle `be2d01f3…`, board controller `50001905…`) |
| Runtime | `http://127.0.0.1:18871` (parent preview), Chromium 156 headless, Playwright 1.60 (parent `node_modules`, read only) |
| Window | 2026-10-09 19:18:59–19:31:36 UTC. The handoff expires 21:07:54 UTC; the scripts refuse to run within 10 min (browser) or 5 min (HTTP) of expiry. |
| Native helpers | **0**. No subagents and no official descendants. |

## Source and runtime binding

Full table: [source-provenance.txt](../../tests/W04_COMMON_BINDING_ATTACHMENT/evidence/source-provenance.txt).

| Pin | fc54 Git | Parent installed / served | Baseline fa552 |
| --- | --- | --- | --- |
| `ActionDispatcher.ts` | `bb57838b…f68` | `bb57838b…f68` | `bfa67b16…e8b` |
| served `/build/core/template-engine.min.js` (HTTP 200 bytes, before and after) | `b8cf27fa…76d` | `b8cf27fa…76d` | `be2d01f3…414` |
| served admin `components.iife.js` | `d25eb47e…6c9` | `d25eb47e…6c9` | unchanged |
| board `User/AttachmentController.php` (installed `modules/sirsoft-board`) | `d368cb01…816` | `d368cb01…816` | `50001905…429` |
| board `module.json` | 1.1.3 | 1.1.3 installed | 1.1.2 |
| travel module / template installed | 0.1.2 | 0.1.2, travel components bytes = fc54 | — |

- Comparing the installed module/template trees with fc54 `_bundled` (excluding `node_modules`, `vendor`, tests and `*.map`) shows **docs-only** differences: README/AGENTS/docs `*.md` regenerated at install. There is no runtime-file mismatch.
- `fa552..fc54` changes 13 runtime-path files: the two repairs, their tests, the board version/CHANGELOG/package files, the engine CHANGELOG and the rebuilt bundle.
- The route cache holds the board preview route.
- **Concurrent parent source change, not activated.** The parent Git HEAD was `fc54` at intake. During this review it moved to `95d16543` (`feat(travel-lab)… 기획전 구현 (0.1.3)`), with 11 tracked edits under `_bundled`, scripts and docs. No installed or served file under the travel/board/admin-template modules or `public/build` is newer than my checkout. The installed version is still 0.1.2, and the served engine, admin, travel bundle and installed board hashes re-measured after the runs equal the fc54 pins. The runtime that was exercised is therefore fc54. Not BLOCKED, but the lead must re-bind after activating 0.1.3.

## A. Rapid admin save (browser)

Script: [browser_recheck.mjs](../../tests/W04_COMMON_BINDING_ATTACHMENT/browser_recheck.mjs). It derives from the author's `tests/W04_ADMIN_DIAGNOSTIC/diagnose.mjs`, which is preserved unchanged.

- Real trusted pointer/tap/keyboard input only. Handlers, state and services are not patched. Capture-phase listeners only *read* `G7Core.state.get().travelDepartureEdit` and the rendered label.
- Each case records: the option click → the native global and rendered label immediately after → the trusted Save `pointerdown/mousedown/mouseup/click` (or touch) with state and label at that instant → the actual outgoing JSON body → HTTP status → native GET readback.

Fixture: own hidden/unpublished products **87** (options 175/176, departures 141/142) and **88** (options 177/178, departures 143/144). They were created by the native admin API, which counts as preparation and **not** as UI-CREATE. Category 26 and policy 6 were referenced read-only and never activated. All departure/metadata edits and both new departures went through the native UI.

| Case (both 390 tap and 1440 pointer unless noted) | At Save pointerdown | Outgoing body → HTTP → readback | Result |
| --- | --- | --- | --- |
| true→false, Save immediately (run1, run2, plus a repeat in each) | global `false`, rendered “접수 가능” (stale) | `is_active:false` → 200 → `false` | PASS ×8 (4 per width) |
| false→true immediately (run1, run2) | global `true`, rendered “접수 중지” (stale) | `true` → 200 → `true` | PASS ×4 |
| Edit dates + capacity by keyboard, status last, Save immediately (run3) | global `false`, rendered stale | option unchanged, dates, capacity 6/7, `false` → 200 → all 5 fields match | PASS ×2 |
| New departure: option **Select** reference + dates + capacity + status `false` (1440 run2 on product 87; 390 run4 on product 88) | global `false` | POST 200, all 5 fields match readback | PASS ×2 |
| Metadata: duration (keyboard) + region select + explicitly unpublished, PATCH immediately (run2 1440, run3 both) | latest global equals target | PATCH 200, readback duration/region match, `published:false` | PASS ×3 |
| Server validation via UI: return before departure | — | 422, readback unchanged | PASS ×4 |
| Server validation via UI: capacity 0 | — | 422, readback unchanged | PASS ×4 |
| Ownership: member / other_member / guest PUT on own departure | — | 403 / 403 / 401, readback unchanged | PASS |

### Native contracts observed (not defects)

- An existing departure's option input is rendered **disabled**, matching the server's `option_immutable` rule.
- One departure per option is enforced (`option_in_use`). The first 390 new-departure attempt in run2 reused option 176, which run2 at 1440 had already taken, and got **409** with nothing created. That was a harness design error. It is preserved in `browser-run2.json` and was fixed by run4 on a fresh own fixture.
- **Price** is not editable in the departure/metadata editor (“금액은 여기서 바꾸지 않습니다”). A price edit was **NOT_RUN** here; original fa552 evidence covers the ecommerce price path.

### Menu navigation

The menu checks used the newest own inquiry (read-only) and original report inquiry **150** (owner read 200, read-only):

- **1440:** locator pointer, raw coordinate mouse down/up, and keyboard (Enter on the trigger, then click the item) → exact detail URL. PASS ×6.
- **390:** tap, and Enter + tap → PASS ×4.

The original PC failure is **not reproduced**, so its cause stays UNKNOWN. No fix is claimed.

### Harness errors preserved, not product failures

- **run1:**
  - The combined case looked for the option `Select`, but edit mode renders a disabled number input.
  - 1440 keyboard mode pressed Enter on a non-focusable menu item.
  - The 390 row locator assumed `tr`.
- **run2:**
  - The edit case tried to type into the disabled option input.
  - The 390 metadata case used an unscoped `role=option` that matched a page chip.
  - The 390 new-departure case hit the 409 above.

Each was fixed in the harness and rerun as a separate phase. `browser-run3` and `browser-run4` are `--departure-only`, so they end with `ScopedStop` and run no menu/smoke checks, by design.

### Smoke after the shared-core change

- Member 390 UI login, then `/travel`, `/travel/cart` and `/travel/requests` all render, with 0 page errors.
- API: member cart 200 (0 items), guest cart 401, member admin-catalog 403, member `auth/user` 200.
- No cart item, inquiry, order, booking or payment was created. Cart and inquiry cleanup are therefore **not applicable**.

## B. Native attachment (HTTP)

Script: [attachment_probe.py](../../tests/W04_COMMON_BINDING_ATTACHMENT/attachment_probe.py). It derives from the 992 reviewer probe `docs/symphony/w04-private-attachment/probe.py`, now pinned to `REVIEW_SHA=fc54`. The old probe, its results and the historical **500** observation are preserved unchanged.

Results: [probe-results.json](../../tests/W04_COMMON_BINDING_ATTACHMENT/evidence/probe-results.json), **14 PASS / 1 OBSERVED / 0 FAIL**. These are confidentiality checks, not a global PASS.

### Fixture

- The member created private question **117** through the support API (201, `is_secret`, no `attachments` key).
- The admin uploaded two files through the native admin board API:
  - attachment 3: inert text, 208 B, sha `bc2a93d6…`;
  - attachment 4: 8×8 PNG, 166 B, sha `6bd3c16b…`.
- Each has exactly one on-disk file, and its sha equals the upload (read-only stat/sha of these own files only).
- Member uploads were **not** enabled and no permissions were granted.

### Nonimage preview

- **400** for admin, owner, other_member and guest.
- Message: `이미지 파일만 미리보기가 가능합니다.` (ko), and `Only image files can be previewed.` (en) for the guest with `Accept-Language: en`.
- Authenticated actors received ko even when they sent `en`. This is native `SetLocale` priority (`users.language` before the header), not a defect. Two locales were therefore observed, en only as guest.
- No bytes, filename, path, raw key or `preview_failed` appeared in any response.

### Real-byte positive controls

| Actor / route | Result |
| --- | --- |
| Admin download, txt and PNG | 200, bytes = disk |
| Admin and owner PNG preview | 200, bytes = disk |
| Owner download | 403 (contract **C1**: download is admin-only) |
| Owner support show | no attachments (support API omits them) |

### Foreign and guest denials on the same real files

| Route | other_member | guest |
| --- | --- | --- |
| User download | 403 | 401 |
| Unsigned PNG preview | 403 | 403 |
| Admin download | 403 | 401 |
| Attachment deletes | 403 | 401 |
| Post show/list | 403 | 401 |
| Support show | 404 | 401 |
| Support list | 200, excludes 117 | 401 |

### Distinct 404 controls

The wrong board slug, a nonexistent hash and a nonexistent board each return 404 for all four actors. The same PNG on the correct board still returns its bytes to the admin, so the 404s are not caused by a dead hash.

### Signed preview (C2, OBSERVED)

A guest holding the admin-issued signed URL got 200 with matching bytes. A tampered signature or no signature got 403. The TTL is about 1796 s. This is native delegated bearer capability, so guest denial is not universal. No signed URL is stored in evidence.

### Deletion

- After the attachment DELETE (200), the formerly readable text file gives 404 to the admin on download and preview, 403 on download to owner/other, and 401 to the guest.
- After the question DELETE (200), the PNG previously readable by admin and owner gives 404 to the admin on all routes and on the owner's preview. Support show returns 404 and both lists exclude 117.
- Physical files are **retained** with unchanged sha (C3, native soft delete). No storage was pruned and no physical removal is claimed.

## Cleanup and tokens

[final-state.json](../../tests/W04_COMMON_BINDING_ATTACHMENT/evidence/final-state.json), read at 19:31:36Z:

- Products 87 and 88: `hidden`, unpublished, every departure (141, 142, 143, 144) inactive with reserved 0. Kept as synthetic rows; no pristine-DB claim is made.
- Question 117: soft-deleted (owner show 404). Attachments 3 and 4: soft-deleted.
- Member cart: 0 items.
- **20 own-issued native tokens** (3 HTTP + 17 browser: 7/7/2/1) were each revoked with native logout (200), and `/api/auth/user` then returned **401** for each.
- The supplied handoff tokens were never logged out. A read after every phase returned 200.
- The private 0700 ledger (fixture IDs and token hashes) lives outside the repo under `/tmp`. Nothing private is committed.

## Privacy

- The scripts read `access.json` in-process after mode, expiry and loopback checks. Nothing from `.env`, `.env.testing`, platform config, credentials or the DB was read, and there were no SQL, cache, counter, service or settings writes.
- A scan of every committed evidence and script file for known private values, Sanctum token patterns, `signature=` strings and email patterns found **0 hits**.
- Screenshots are three modal-only crops of the own synthetic fixture: 1440 and 390 from run1, and 390 from run4. Duplicate byte-identical crops were dropped. All were visually inspected: no actor, contact or token is visible.

## Separation of evidence kinds

| Kind | Status |
| --- | --- |
| Author unit results (core fail-first 6 FAIL / 2 PASS → 8 PASS; 11 files / 546 PASS; board 6/41 FAIL → 6/47 PASS) | inherited, **not re-run** here |
| This actual runtime (browser + HTTP at fc54 served/installed bytes) | executed, above |
| SQL / DB inventory | **NOT_RUN** (forbidden) |
| Hosted CI | **NOT_RUN** (checkruns 0) |
| Formal canonical Validation | none |

## Follow-ups

- **Lead:** re-bind served/installed hashes after activating parent `95d16543` (travel 0.1.3), because the evidence here covers 0.1.2 + board 1.1.3 + engine `b8cf27fa`.
- **Lead:** the original fa552 PC menu failure cause stays UNKNOWN.
- **Product decisions (no change made):** C1 owner download, C2 signed-URL TTL, C3 prune.

## Delivery

Local commit only. Owned paths: this report and `tests/W04_COMMON_BINDING_ATTACHMENT/**`. No push, merge, deploy or official Request; the lead owns Git delivery.
