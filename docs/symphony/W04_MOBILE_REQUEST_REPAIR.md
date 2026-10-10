# W04 mobile request list repair — author evidence

Status: author implementation checks and same-owner installed-candidate browser geometry/navigation PASS; nonauthor fixed-revision review remains required. This is a bounded implementer handoff, not independent Validation or overall release PASS. The original independent browser FAIL remains immutable.

## Defect and repair

Independent child `req_7e41235d7eeb44a0b4efe254bc678748` tested product `598a89fff702d51c1405f1a5952d95ab1d2651f4`, tree `9e00273bdf18d6a713343755aac54f44a9b032b4`, and delivered evidence-only local commit `695341be26a18ecb91acb4d663fca50e57659725`. Its request-list document width was initially 412px and finally 424px at configured viewport 390px, while 1440px passed. Its native customer/admin, retry, owner-isolation and private-edit passes do not close this UI defect. The child worktree was read-only; its report and negative evidence were not altered.

The travel Button component supplies `inline-flex items-center justify-center`. The request-card layout did not specify a flex direction, so its intended header, product title, extra-item label and quantity/price footer became horizontal flex children. Their combined min-content widths enlarged the grid track. This also compressed the request ID, badge and quantity into narrow vertical text in the independent screenshot.

The repair is local to `layouts/travel/requests.json`: cards use `flex-col items-stretch`, grid/card items allow `min-w-0`, identity and totals can wrap, and product titles use wrapping instead of the prior `line-clamp-1`. No blanket overflow hiding or new truncation is used. Button type, test IDs, iteration/data source, inquiry IDs, enum/status binding, currency/amount binding, detail route, page query and action remain unchanged. No shared Button/StatusBadge/PriceTag implementation or backend/API was modified. The internal layout change requires no new public API dependency version; the template Unreleased changelog records that decision.

## Fail-first and focused verification

| Check | Result | Scope |
| --- | --- | --- |
| New long-title test before repair | FAIL, 1 selected case / 26 skipped, 4.50s | Existing `line-clamp-1` suppressed full visible title |
| Source-layout/native shipped-component geometry before repair | 390 FAIL: document 1265px; 1440 PASS | Synthetic long unbroken title, statuses, extra-item label, formatted prices; no authentication mock |
| `npm run test:run -- __tests__/layouts/requests-help.test.tsx` after repair | PASS: 27 tests, 6.98s | Existing list/detail/help/login/error flows plus long-title, ID/status and Enter detail-navigation regression |
| `npm run type-check` | PASS | Template TypeScript |
| `G7_BUILD_SOURCEMAP=0 npm run build` | PASS: 35 modules, 8.40s | CSS 47.75kB, JS 38.22kB; JS unchanged, sourcemaps disabled |
| Source-layout/native shipped-component geometry after repair | 390 PASS: document390; 1440 PASS: document1440 | Full Korean/English product titles wrap, all child bounds fit, card focus/click route retained |
| Full frontend/PHP/CI/official Validation | NOT_RUN by this repair author | Relevant fixed-revision gates belong to the lead/independent verifier |

The source geometry helper loads the actual preview-exported components, reads the source request-list tree and renders an isolated synthetic list. Candidate geometry uses the production CSS file, not injected hand-authored repair rules. It is a component/layout browser check, not a full authenticated inquiry journey. The stronger native same-owner check below is separate.

An earlier full focused-file run of 26 unchanged tests passed before adding the new case. An initial new-test attempt expected a translated label that the jsdom SDK did not supply; that harness assertion was corrected to the existing `data-status` contract before recording the real fail-first truncation failure. These diagnostic executions are not extra product test coverage.

## Native baseline and same-owner comparison

Fresh author actor fixtures101 and102, with one and two native-API cart items respectively, passed 390/390 and1440/1440 on the old installed flex-row layout. These seed snapshot/status combinations did not reproduce the original data-dependent overflow; their PASS is preserved and is not substituted for the independent FAIL. API fixture setup is not counted as an independent UI journey. Both exact own inquiries were cancelled, own cart was rechecked empty, and four exact newly issued native login tokens were logged out and rechecked401. No broad data cleanup occurred.

The lead then explicitly authorized read-only access to the original browser-owner account's existing requests. At configured 390px with mobile/touch enabled, the author reproduced **document425px**, with ten first-page cards each407.640625px wide/right423.640625px and `flexDirection=row`. The measured mobile `innerWidth` expanded to425px; the configured viewport remained390px. The one-pixel difference from the earlier independent424px measurement is retained. At1440px, document1440px passed. Native Enter navigation to original inquiry100 and exact two newly issued login token logout200→user401 passed at both widths. This original-owner run performed no cart/inquiry/old-fixture mutation or handoff-token revocation.

Same-owner installed-candidate after result: **PASS**. At390px, document390px, ten cards358px wide/right374px and `flexDirection=column`; at1440px, document1440px, cards546px. Native Enter navigation passed at both widths. A separately preserved bounded pointer phase also passed real mobile tap and desktop mouse click to original inquiry100. These are six unique functional checks (geometry/Enter/pointer × two widths); repeated geometry/Enter and cleanup rows are not additional coverage. Page errors were0. Four exact new login tokens from these two after phases were logged out200 and rechecked401. The entire original-owner comparison remained read-only apart from login/own-token logout.

The lead ran native template/module updates, not the author. After source mapping matches bundled/installed requests.json, CSS and JS at all three paths. Actual recorded HEAD was `cf7a2be44ad2fb9e2b55c7a39657df219fde1cd9` with WORKINGTREE candidate hashes; access baseline598 is an ancestor, not a claim that repaired code was tested at598. These three UI bindings do not establish all application/backend files or runtime state.

**Resolved author diagnostic, not a product defect:** the author initially reported a date-absent interpretation of `mobile-request-native-owner-after-pointer-390.png`, viewed through the default resized image tool. The nonauthor reviewer could see dates in that exact original PNG, and the root's original-owner HTTP response contained `created_at`. A distinct, preserved `owner-after-date` phase then waited for the real inquiry-list response and settled DOM. At390px and1440px, first inquiry100 returned synthetic server `created_at="2026-10-09 23:19:41"`; the date span rendered the expected server prefix `2026-10-09 23:19` at both widths. Geometry and Enter navigation still passed, page errors remained0, and the two exact newly issued login tokens were logged out200/rechecked401. The account's existing fixtures and handoff tokens were untouched. The earlier date-absent inference is corrected as an author resized-image interpretation diagnostic; it was not reproduced in settled DOM and required no product/backend/date-code change. This adds two distinct date checks to the previous six geometry/navigation checks; repeated geometry/Enter/cleanup rows do not increase coverage. It remains author evidence, not independent Validation.

## Evidence and source bindings

All portable artifacts are under [template test evidence](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/). Evidence files are append-only per phase; helpers refuse to overwrite an earlier phase.

| Artifact | Purpose |
| --- | --- |
| [requests-mobile.mjs](../../templates/_bundled/raonslab-travel_lab/__tests__/geometry/requests-mobile.mjs) | Loopback-only source-layout/native component geometry helper |
| [requests-native.mjs](../../templates/_bundled/raonslab-travel_lab/__tests__/geometry/requests-native.mjs) | Native login/list/Enter route, exact issued-token cleanup; owner phases read-only |
| [mobile-request-before.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/mobile-request-before.json) and `mobile-request-before-{390,1440}.png` | Source stress FAIL/PASS before |
| [mobile-request-candidate.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/mobile-request-candidate.json) and `mobile-request-candidate-{390,1440}.png` | Source stress PASS/PASS candidate |
| [mobile-request-native-before.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/mobile-request-native-before.json), [mobile-request-native-before-multiple.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/mobile-request-native-before-multiple.json) and corresponding `{390,1440}.png` | Fresh fixture old-layout PASS diagnostics and cleanup |
| [mobile-request-native-owner-before.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/mobile-request-native-owner-before.json) and `mobile-request-native-owner-before-{390,1440}.png` | Original-owner real425px negative reproduction |
| [mobile-request-native-owner-after.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/mobile-request-native-owner-after.json) and `mobile-request-native-owner-after-{390,1440}.png` | Same original-owner geometry/Enter after PASS |
| [mobile-request-native-owner-after-pointer.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/mobile-request-native-owner-after-pointer.json) and corresponding `{390,1440}.png` | Preserved tap/click follow-up PASS |
| [mobile-request-native-owner-after-date.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/mobile-request-native-owner-after-date.json) and corresponding `{390,1440}.png` | Resolved date diagnostic: real server prefix/settled DOM PASS at both widths |
| [mobile-request-repair-manifest.json](../../templates/_bundled/raonslab-travel_lab/__tests__/evidence/mobile-request-repair-manifest.json) | 32 file SHA-256 inventory:8 source/build/test/changelog,8 phaseJSON,16 PNG |

The original-owner before run binds access baseline598 to current WORKINGTREE with an ancestor check and records actual HEAD. It records candidate bundled request layout `ad03a73eec4e82d0663dadeb9468579ae5a324a450716e24cededeb0312d663c` versus installed old layout `a57563b0da7f85aafa336c83fa076376f8f357e4c557c89bf58515e88a6ef266`. Candidate CSS is `e340b3bfcf22be2ef56f7d11a8f9bf2fc3563439f908628ab044931b463dce66`, installed old CSS `2750062e8f93d635d9f126f6249852fe370725e3d7abd77d6c09cf58c6507529`. Bundled/installed JS remained `9cc99d8d8db0161cfc5f537531eb16f00d1a7ca272fd0b31824823185f46b390`. A current Git HEAD by itself does not identify these uncommitted candidate UI files.

Native access is read only inside the helper from the specifically authorized parent lab file, regular private0600 under0700, with exact source SHA, loopback, isolatedDB marker and finite future expiry guards. Private access, credentials, UUID, contact values and Bearer tokens are not copied to evidence. Published JSON contains geometry, source hashes, numeric inquiry IDs and cleanup statuses only. Native screenshots mask all inputs/textarea/contact controls; list pages expose synthetic product snapshots and statuses, not contacts. Candidate390, native fresh-before390, original-owner-before390 and final original-owner-after-pointer390 PNGs were visually inspected. All eight phase JSON files parse and pass checks for email/UUID/Bearer/private contact fields. Sixteen new PNGs are hashed and retained; they show controlled synthetic list/component screens, with no credential/detail/contact screen capture. Further nonauthor visual/redaction review is still required before Git publication.

## Independent review and integration limits

The lead must pin and publish the repaired tree, request a nonauthor review and run a bounded independent mobile request-list recheck at390/1440 on that new SHA. Preserve independent598's412/424 negative evidence and this author's425 reproduction. The author does not merge, stage, commit, push, deploy, alter DB schemas or count these checks as a canonical Validation receipt. Full transactions already verified at598 are historical evidence; integration regression remains a separate lead/independent gate.

Frozen manifest SHA-256: `99189bd6984a01fce9c91c8559429e471f6e7f09cfd43f7cd4235cbd76a6ab3f`. All32 inventory entries were rechecked at final author freeze. Scoped whitespace/link checks passed. No author staging, commit, push or service/DB-schema change occurred.

Date diagnostic continuation supersedes author report pin `a4958806bb6059ee57a66c47d61f5ac67fa38843102cd714adb601d8c4fbc970` and inventory pin `3903a80f66346f095a8ed3961e24387a3e9eff1b8b6fdb83434636d1d62b0c1f`. Eight phase/width PNG pairs now total16 images; earlier source/native phase JSON and PNG files remain unchanged. Only the native verification helper, this report, inventory and new date-phase artifacts changed. Product layout/CSS/JS remain frozen at the candidate hashes above. No further tests/builds were run for this evidence-only continuation.
