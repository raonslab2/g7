# W04 UI repair nonauthor source review

Reviewer: native `/root/w03_recovery_repairs`, nonauthor of the current UI repair. Read-only source/JSON/build inspection; no tests/build, DB, environment, service, browser execution, source edit or Git publication. Only this report is authored here. **PASS_BOUNDED_SOURCE**: no new P1/P2 defect found in the assigned frozen UI scope. This is not independent E2E, fixed-SHA runtime verification, release PASS or canonical Validation.

## Frozen review input

All **14** source/build/test paths in `templates/_bundled/raonslab-travel_lab/__tests__/evidence/w03-ui-boundary-repair.json` match their recorded SHA-256 **before and after** inspection. Declared source-set digest independently recomputed using its stated `SHA256(json.dumps(source_files, sort_keys=True).encode())` definition:

`04bcf33558199245bd158893c31ff8c125282fd27df8fd142f4de221a792c12a`

Evidence manifest itself: `c9b0faf1b8e48271b5194f60e91089e15e138cc65033902f73ec108b807b9407`.

Key source/build pins:

| Input | SHA-256 |
| --- | --- |
| Inquiry handler | `56da7f5628fb739913eccfbc3c9d1ffb8227602fe1ee20e2ac156c02cf193282` |
| User base | `0aa7219abf51d716ffc8f08b5117d2eb472782ec6e3d4f645c31168a7afbe254` |
| Help layout | `ac03e403769d46ba03135143796712aa8e316f2f0de7f175fc3f20c5337d1300` |
| Admin catalogue layout | `ea031efad85eb59b75abc9d16aa1aed3c7c33089b303567c99d09889b4c539fb` |
| Production JS | `9cc99d8d8db0161cfc5f537531eb16f00d1a7ca272fd0b31824823185f46b390` |
| Production CSS | `2750062e8f93d635d9f126f6249852fe370725e3d7abd77d6c09cf58c6507529` |
| Native G7CoreGlobals | `b6b67e14b9de7baf46fe2abf1ad8aba1c90e2598fb2ea4b6c23f6ba4b6548616` |
| Native DynamicRenderer | `01b107483b3fe731f7b572791a9ee3ec95c9f8ae2f159c4d1f5ddf923aaabba2` |

These are working-tree file pins, not a repaired integrated Git SHA. Native core/public APIs were only read, never edited by this reviewer.

## Identity and uncertain-request preservation

`G7CoreGlobals.initStateAPI()` defines `state.get()` as the **current** `window.__templateApp.getGlobalState()` contents, with no `_global` wrapper. The repaired handler therefore correctly reads live `currentUser.uuid`, then live id, before the possibly stale action closure's ownerId. It clears a stored key/body when the live identity differs before checking an empty-cart recovery body. Both new stale-closure tests cover a changed live UUID with missing or old callback ownerId.

Desktop and mobile native logout success chains now invoke `travelLabClearInquiryKey` before subsequent navigation/state cleanup. That handler clears session-storage pending data and global key/contact/body, including the existing in-memory null tombstone when storage removal fails. Native logout remains responsible for actual authentication; the handler does not synthesize a user/token. Storage identity is a client UX boundary, not server authorization.

Unchanged-account uncertain retries retain the exact stored body/key. Contact or acknowledged cart edits create new intent; the existing source still captures prepare's returned body through sequence `$prev` into explicit local state before authenticated apiCall. Inquiry payload sends cart IDs/contact/idempotency key; it does not send a client-selected amount or authorization owner identity. Server native price/capacity/ownership checks remain separate requirements. The new identity priority preserves the earlier response-loss/reload/storage-quota mechanism rather than replacing it with a render snapshot.

The minified production JS contains the same fresh SDK currentUser lookup and UUID/id/closure fallback order, mismatch clearing, existing memory override/tombstone handling and registered clear handler. Its byte hash matches the manifest; no sourceMappingURL is present. This source/generated feature alignment is not an independently rebuilt artifact proof.

## Private-question editing and actual dispatch contracts

The new edit control and form require server `question_detail.data.is_mine === true`. Prefill reads returned title/content; fields stay in local state. Save uses native sequence/setState, top-level required auth_mode and target, PATCH body **only title/content**, and the current question detail ID. Success closes saving/editing and refetches both detail and list using the native `dataSourceId` parameter. Error reads the native top-level `error.errors`/message and resets saving without clearing typed inputs; cancellation hides editing and sends no PATCH.

The native-dispatch tests inspect the actual outgoing PATCH body, prefill, control disappearance and 422 input retention. Their APIs/auth are fixture boundaries; they do not prove server ownership enforcement or persistence. Visibility controls do not grant another user's question access. Nonauthor fixed-SHA server/browser tests must still verify foreign read/update/attachments and administrator scopes. No new fake authentication or direct price/post-table mutation appears in these UI changes.

## Catalogue loading and native product navigation

Native DynamicRenderer's object `blur_until_loaded.data_sources` checks only explicitly named sources for undefined and ignores unrelated page transition/unfetched sources. The repaired catalogue now names only required `catalog`, so optional unfetched `travel_candidates` no longer indefinitely overlays pointer controls. This is a native contract-supported change, not an invented loading API. The actual numeric product link remains `/admin/ecommerce/products/{id}/edit`; navigation success alone does not prove shipping reference data, product update or departure-write correctness.

## Author results and limitations

Portable author evidence records **62 + 13 focused tests = 75**, type check, production build35 modules/4.87s and eight unique browser scenarios across390/1440. The first after-browser run is preserved as **6 PASS / 2 FAIL** (network navigation interruption and support POST429); only the two affected1440 checks were followed up. Different-member POST201 does not prove the original account's throttle bucket was repaired. The report accurately treats native support limiter-prefix remediation as root-owned and outside this frozen UI manifest.

Phase record counts12/4/15/3 include observations/cleanup and are not functional case totals. Before installed mappings retain baseline7de assets; after affected five mappings match the candidate, a partial provenance check. The phase sourceSHA7de is baseline lineage, **not** a newly integrated repaired source identity. Eight unique final author cases are neither ten cases including retries nor official independent E2E. New screenshots are NOT_CAPTURED. Earlier69 screenshots and interrupted partial screenshots retain their original independent target boundaries.

Root additionally reports whole-template **139 tests / 56.61s PASS**. That is lead execution evidence, not rerun by this reviewer, and overlaps the author focused set rather than adding139 to75. Tests/build/browser claims remain unverified by this source-only reviewer. Backend support throttle, package shipping reference data, actual fixed-SHA product/departure administration, concurrent capacity, persistence/restart, remote CI and canonical Validation remain separate gates. The UI author owned no backend/shipping correction and does not claim those gaps closed.

No current UI evidence or repaired source was rebound to the earlier7de/28ada targets. The next independent browser attempt requires the lead's new fixed integrated SHA and its own complete provenance, cleanup and screenshot evidence.
