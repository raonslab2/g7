# W04 repaired campaign Retry — independent actual browser closure

**Overall: PASS for the repaired Retry contract at 390 and 1440, with retained FAIL/BLOCKED items listed below.** On the served engine `738ee97c…`, the native campaign admin Retry now clears the error and renders both Page slots after a genuine native 200, without reload. A failed Retry keeps the error visible. Scoped Page readers, the empty campaign catalog, and the affected customer/admin/support journeys passed. All own fixtures and tokens were closed. This is nonauthor browser evidence only. It is not hosted CI, not an official Validation receipt, and not a release approval.

Task key `w04-campaign-retry-browser-closure`, Request `req_4d7d64bbb8754974867adc0d9edbe971`. Product files were read-only. This child made a local evidence commit only: no push, PR, merge, deployment, or official child Request.

## Fixed target and boundaries

- `origin feat/g7-travel-lab-c7ae42d1` was explicitly fetched; FETCH_HEAD was `5783e6ba…`. The worktree was detached at **`5783e6ba124061bdfae639cdaf9b1c14a83cdf03`**, tree **`361f9a142572f8a6c0c28326c461a233a92aec4e`**, not the default `6853f40d`. Every harness run rechecks: the SHA is an ancestor of HEAD, the tree matches, and `git diff <SHA>` outside this report and `tests/W04_RETRY_BROWSER/` is empty. The parent APP checkout HEAD was the same SHA and clean.
- The parent APP is `http://127.0.0.1:18871` (`php -S`, docroot = parent `public/`), DB `req81_travel_lab`. The handoff was read in-process only. Checks passed: not a symlink, file 0600, directory 0700, `source_sha`/`source_tree`/`base_url`/`db` exact, expiry 2026-10-10T01:00Z. Credential values were never printed or committed. No `.env`, TEST schema, SQL, private backups, platform config, service/cache/build/settings/role/user change, or logout-all.
- Read: root AGENTS.md, `W04_RETRY_FINAL_REVIEW_CONTRACT`, `W04_CAMPAIGN_BROWSER_FINAL`, `W04_CAMPAIGN_RETRY_REPAIR`, `W04_RETRY_ACTIVATION`, and the old harness. Old helpers hardcode 31a, the old handoff and expected FAIL. They were not reused. New verifier files carry their own guards.
- Browser: Chromium 156.0.8078.4 (Playwright 1.60.0, private `/tmp` install, symlink excluded from Git). 390×1000 uses `isMobile`/`hasTouch` and `tap()`; 1440×1000 uses mouse `click()`. Totals: 12 append-only phases, 22 actual browser contexts, 2026-10-09 21:22–21:39 UTC. pageerrors 0. External HTTP attempts and connections 0 (non-loopback requests would be aborted; none occurred).

## 1. Source → installed → served binding

`binding.py before` (21:22Z) and `after` (21:39Z) both exited 0 ([before](../../tests/W04_RETRY_BROWSER/evidence/binding-before.json), [after](../../tests/W04_RETRY_BROWSER/evidence/binding-after.json)). The pinned Git archive was compared byte-for-byte with the parent's active installed paths. Extensions map `_bundled/{id}` → `{id}`.

| Active runtime group (from pinned Git) | Files equal / selected |
|---|---:|
| Core `app` / `routes` / `resources` / `bootstrap/app.php` / `public/index.php` | 998/998; 5/5; 330/330; 1/1; 1/1 |
| Core `config` / `lang` | 30/30; 90/90 |
| `public/build` | 7/7 |
| Modules: travel_lab / board / ecommerce / page | 125/125; 338/338; 1178/1178; 84/84 |
| Templates: travel_lab / sirsoft-admin_basic | 108/108; 1212/1212 |

Totals: 4507 selected files, 0 mismatches, local fixed source equal. Excluded from runtime selection: docs/tests/tooling 1797 and *.md/test files 27. These exclusions are docs and test files only. Also out of scope: root `vendor/`, `.env`, storage, DB, and non-route/hook caches; `sirsoft-basic` is not installed in the parent. Route/hook cache bytes were identical before and after.

| Required pin | Git = installed | Served HTTP 200 |
|---|---|---|
| Core engine `public/build/core/template-engine.min.js` `738ee97c6eebc33bc75d29f24397daabb54f124bede254689ca68a18fdb64a0b` | yes | `/build/core/template-engine.min.js` exact |
| `TemplateApp.ts` `62e89b9d…5b76e` | yes | (compiled into engine) |
| Travel IIFE `8941442d1a921c2f366063359e39f79ed3a719bce77a174b6c0aa5657b691d63` | yes | `/api/templates/assets/raonslab-travel_lab/js/components.iife.js` exact |
| Board `src/Http/Controllers/User/AttachmentController.php` `d368cb01…c816` | yes | server-side |
| `ActionDispatcher.ts` `bb57838b…4f68` | yes | (compiled into engine) |

Travel CSS and admin_basic IIFE/CSS served bytes also equal Git. Browser response hashes are in each phase's `metrics.assets`.

Between 31a and 5783e6ba, the only runtime changes are `TemplateApp.ts` and the built engine. The rest of the diff is changelogs, docs and the new unit test.

## 2. Repaired Retry, actual 390 tap / 1440 pointer

Method: native UI login, then actual `/admin/travel-lab/campaigns`. Only the native GET whose pathname is `/api/modules/sirsoft-page/admin/pages` was aborted. Once `campaign-pages-error` was visible, the abort route was removed and `waitForResponse` (GET list) was installed before tapping/clicking `campaign-pages-retry`. No reload, no success mock. A no-reload marker was set on `window` and `performance.timeOrigin` was compared; both were unchanged.

| Check | 390 | 1440 | Evidence |
|---|---|---|---|
| A strict: error → Retry → native 200 → error gone, titles 2, edit 2/unpublish 2/create 0, abilities equal admin API baseline, no reload | PASS | **FAIL** (retry-1, screenshot guard) → PASS (retry-2) | [retry-1](../../tests/W04_RETRY_BROWSER/evidence/retry-1.json), [retry-2](../../tests/W04_RETRY_BROWSER/evidence/retry-2.json) |
| At request start (before response): error still visible, titles 0 | OBSERVED | OBSERVED | `pendingAtRequestStart` |
| B: Retry itself fails (2nd abort) → error stays, titles 0, honest | PASS | PASS | retry-1 |
| B: real request held (route waits, then `continue()`, body untouched) → pending error retained → native 200 → recovered, abilities unchanged | PASS | PASS | retry-1 |
| Own token actual UI logout (390 `menu-toggle` → exactly one visible `로그아웃`; 1440 `header-logout`) → request carried own token → exact old token `/api/auth/user` 401 | PASS | PASS | retry-1/2 |

The 1440 strict FAIL in retry-1 is a harness/privacy-guard rejection: Korean+English OCR of the error screenshot matched the email pattern, so the image was quarantined privately and not committed. The step aborted before Retry was exercised. The bounded rerun retry-2 (1440, A only) passed. The FAIL record is retained, and the OCR language was switched to English: all three actor names are ASCII, so English OCR covers every secret. Screens: [A error 390](../../tests/W04_RETRY_BROWSER/evidence/retry-A-error-390.png), [A recovered 1440](../../tests/W04_RETRY_BROWSER/evidence/retry-A-recovered-1440.png), [B failed again 390](../../tests/W04_RETRY_BROWSER/evidence/retry-B-failed-again-390.png), [B recovered 1440](../../tests/W04_RETRY_BROWSER/evidence/retry-B-recovered-1440.png).

Observed, not a contract failure: the prior failure's "Network Error" toast is still on screen after the list recovers.

The 31a product FAIL (`W04_CAMPAIGN_BROWSER_FINAL`, `adapter-retry-2.json`) is not modified or relabelled. It remains the historical result for 31a.

## 3. Scoped Page actors (no grant/role/user changes)

[scope-1](../../tests/W04_RETRY_BROWSER/evidence/scope-1.json): 14/14 PASS.

| Check | 390 | 1440 |
|---|---|---|
| readonly57: native 200, 2 slot titles (Page 7/8), list and row `can_create/update/delete` all false; edit/publish/unpublish/create 0; no-update notice 2; detail 2 | PASS | PASS |
| readonly57: actual detail-button navigation to `/admin/pages/7` and `/8`, native 200, title visible, 0 enabled write buttons (native detail renders them disabled) | PASS | PASS |
| self58: native 200 scoped empty list (0 rows), `campaign-slot-missing` 2, create hint 2, create 0, error 0 | PASS | PASS |
| self58: actual browser direct `/admin/pages/7`, native 403 page, title not leaked | PASS | PASS |
| Own issued tokens: UI logout 200, then exact old token 401 | PASS | PASS |

Native API matrix: self58 gets 403 on Page 7/8 show, versions, and a version item. readonly57 gets 200 on all of them. Guest list 401. Member list/show 403. Own unpublished draft Page 18 via the public Page API: member and guest both 404. Self58 on draft 18: 403. Unknown travel campaign: 404. So 401, 403 and 404 stay distinct.

Valid scoped writes by both readers all returned 403: create, PUT, publish, bulk-publish, delete, version restore (and check-slug). After them, Page 7/8 digests were unchanged and the ID set equalled the original plus own draft 18. Draft 18 was then native-deleted: delete 200, admin read 404, public 404.

Observed: Page-only readers get three `해당 권한이 없습니다` toasts and an empty sidebar. Cause: the native admin shell sources `/api/admin/menus/active`, `/api/admin/notifications`, and `…/unread-count` return 403 for these roles. This is a role-scope UX observation; no grants were changed to hide it.

## 4. Real empty campaign catalog (authorized parent window, IDs 1/3 only)

[empty-1](../../tests/W04_RETRY_BROWSER/evidence/empty-1.json), 21:34:25–21:34:41Z, before any cart or transaction phase.

- **Preflight:** `per_page=48` pagination was exhausted (`per_page=100` returns 422). Nature IDs were exactly `[1,3]`; all public IDs were `[1..8]`. Native admin travel, departure and product snapshots were saved privately (0600).
- **Mutation:** `PATCH /admin/catalog/{1,3}` with `{published:false}` only. Nature set became `[]`. Page 7 stayed published (public campaign 200).
- **390/1440 guest browser:** actual `/travel/campaigns/travel-lab-campaign-autumn-escape` returned campaign 200 and catalog 200 with `data.data=[]`. The campaign body was visible, `campaign_trips-empty` visible, 0 trip cards, no error. Tapping/clicking the all-trips CTA navigated to `/travel/search`. Result: PASS/PASS ([390](../../tests/W04_RETRY_BROWSER/evidence/empty-campaign-catalog-390.png), [1440](../../tests/W04_RETRY_BROWSER/evidence/empty-campaign-catalog-1440.png)).
- **Restore:** `published:true` for both. Semantic state equals the snapshot (`updated_at` excluded; the travel admin resource did not expose `updated_at`), including departure, price, options, stock and reserved hashes. Nature `[1,3]` and the public set are unchanged. No stock, capacity, price or option writes.

## 5. Affected customer/admin/support regression

[transaction-1](../../tests/W04_RETRY_BROWSER/evidence/transaction-1.json): 16 PASS, 1 FAIL. Own fixtures were prepared via native API: products 94 (nature, 390) and 95 (wellness, 1440), departures 150/151, inactive nondefault free policies 12/13, reference product 70 and category 26 read-only. API fixture preparation is not a UI-create PASS.

| Check | 390 | 1440 |
|---|---|---|
| Customer menu, trusted input (390 drawer `mobile-nav-campaigns` tap + `tab-requests`; 1440 `nav-campaigns` click + `nav-requests` keyboard Enter); no direct-path success counted | PASS | PASS |
| Campaign → filtered catalog CTA → product; Korean keyword `합성 여행` native 200 | PASS | PASS |
| Detail date/quantity → cart 2→3→2 PATCH 200 → TEST inquiry 201, server price 13700×2=27400, no client price | PASS | PASS |
| Admin inquiry row ActionMenu, trusted pointer (scroll + 750 ms) → detail; 1440 also keyboard | PASS | **FAIL** (`상세 보기` not visible) |
| Rapid status select → immediate save via native binding/ActionDispatcher: sent status = selected; 390 UNDER_REVIEW→TEST_ACCEPTED, then owner UI logout/relogin/requery, cancel 200; 1440 →DECLINED, no cancel control, API 409; other member 404 | PASS | PASS |
| Response lost after real upstream 201 → reload → same key/payload/ID native 200, items/events/amount/calculation/allocation unchanged | PASS | PASS |
| Request list width equals viewport | PASS | PASS |

**1440 ActionMenu diagnosis:** the transaction-1 FAIL is retained, and the step does not record whether the first click or the post-`goBack` keyboard attempt missed. A bounded fresh-context follow-up, [menu-diag-1](../../tests/W04_RETRY_BROWSER/evidence/menu-diag-1.json), ran 5/5 PASS: 1440 locator ×2, coordinate mouse, keyboard Enter, and 390 tap. In each, `elementFromPoint` hit the button, the event was trusted, the menu appeared, and detail navigation worked. Conclusion: intermittent within a long-lived context, not reproduced in fresh contexts, consistent with the 31a narrowing. It is not claimed as a universal 1440 menu PASS, and no permission defect was found.

[support-1](../../tests/W04_RETRY_BROWSER/evidence/support-1.json): 8/8 PASS at both widths. Covered: native notice/FAQ UI create 201 / edit 200 / public read 200; private question owner UI create 201 / edit 200 / reload; admin answer 201; owner requery shows answer; other member 404. No attachments.

Inheritance of the 31a full Page create/version/sanitizer evidence: the server-side Page, travel and admin-template paths are byte-identical between 31a and 5783e6ba, so the 31a server behaviour (native save keeps the raw probe, customer DOMPurify) is inherited by digest. Those UI flows run on the changed engine and **were not re-run here: NOT_RUN at this SHA.** Also NOT_RUN / untested: served SEO body and cache invalidation, general layout-editor preview, backend HTMLPurifier on the Page path, culture/city regression, and the full old 14-row matrix.

## 6. Cleanup, tokens, privacy

[final-audit-1](../../tests/W04_RETRY_BROWSER/evidence/final-audit-1.json): all PASS.

- **Pages 7/8:** ID, slug, title, content, mode, SEO and publication equal the private original. The whole resource is also hash-equal (versions 11/9 unchanged) — no Page edits were made.
- **Products 1/3:** semantic-equal; nature set `[1,3]`.
- **Own fixtures:** inquiries 181/182/184 CANCELLED, 183 DECLINED (reserved 0). Products 94/95 unpublished and public 404; departures inactive, reserved 0. Policies inactive and nondefault. Support posts 124–129 soft-deleted, public 404 (answers remain inside their deleted parents). Draft Page 18 is 404. Member and other-member carts are 0.
- **Tokens:** 37 unique own tokens were issued. Every one has a per-phase cleanup record: logout 200, then exact `/api/auth/user` 401 at revocation time. 9 were revoked through actual UI logout, 28 through native `POST /api/auth/logout`.
  - The final audit independently re-queried the 30 still in the private ledger: all 401 (27 from earlier phases + its own 3).
  - **Harness defect, retained:** retry-2 ran concurrently with scope-1, and support-1 with transaction-1. Their processes overwrote the shared private ledger, which lost 7 plaintexts (retry-2: 1 UI + 1 API; support-1: 5 API). For those 7, only the at-revocation 401 is evidence; the final re-query is NOT_RUN.
- **Supplied handoff tokens — BLOCKED:** "supplied tokens valid" cannot be shown. All three supplied bearers (admin/member/other_member) already returned 401 at the very first preflight, probe-1 at 21:24:42Z, which made GET requests only. The verifier never used them for logout or revocation. probe-1's first check is mislabelled PASS while observing that 401; it is retained unchanged. All API work used own natively issued tokens instead.

Screenshots: editable fields are hidden, and known actor/contact/token values plus email/UUID patterns are masked in the DOM before capture. After capture, an OCR exact-value/pattern scan runs. One PNG was rejected and kept only in ignored private quarantine. 34 committed PNGs, several visually inspected (recovered/failed Retry, empty catalog, reader adapter, 403). `privacy-manifest.json` records per-file SHA-256 and 0 exact/pattern hits across reports, JSON, scripts and OCR of PNGs. Private ledgers live under ignored `storage/framework/testing/w04-retry-4d7d-private/` (0700/0600).

## Commands and limits

All commands run from the worktree root. Note: the private token ledger is not safe for concurrent phases (see section 6). `execution-index.json` lists each phase's command, timestamps, contexts, counts and exit. Phases: `binding.py before|after`; `node tests/W04_RETRY_BROWSER/{probe,retry,scope,empty,transaction,support,menu-diag,final-audit}.mjs <phase> [width] [A]`; `python3 -I …/execution-index.py`; `node …/privacy-manifest.mjs`. Exit 1 occurred for probe-1 (supplied-token observation), retry-1 (1440 screenshot guard) and transaction-1 (1440 menu); all other phases exited 0.

Checks: 62 PASS / 7 OBSERVED / 2 FAIL (both retained and diagnosed), 34 screenshots, 22 browser contexts. No independent native helper was spawned (count 0). Hosted CI: **0 runs, NOT_RUN** (no workflows). Canonical Validation receipt: **NOT_RUN** (no callable path), not waived. Delivery is one local evidence commit descending from 5783e6ba, containing only this report and `tests/W04_RETRY_BROWSER/**`; the lead owns Git delivery.
