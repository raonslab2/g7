# W04 private question attachment — real-file verification at fixed 992f9a65 (independent NONAUTHOR)

## Decision

**PASS for the original requirement "a foreign actor cannot access a real attachment".** A real file attached to a member's private question is denied to other_member and guest on every native route (download, preview, admin download, wrong-board hash, attachment delete, post show/list, support show/list). No denial response contained file bytes, a filename or the question body. The denial was tested against **existing** attachments, and positive controls returned those same files. A nonexistent-ID 404 is not counted anywhere.

- **No P0, P1 or P2 findings.**
- **One new P3 product finding (W04PA-01):** a preview of a non-image attachment returns 500 instead of 400. It is in the native `sirsoft-board` module, not in travel code, and leaks nothing.
- **Contract notes:** the owner cannot *download* their own question's attachment (C1), the signed preview URL works as a bearer capability (C2), and deleted files stay on disk (C3).
- **No product source was changed.** Member uploads stay off. No board/global setting, SQL, cache, service or external storage was touched.
- This is not an official canonical Validation receipt. Hosted CI was not run (NOT_RUN, not waived), and this is not a release PASS.

| Item | Value |
| --- | --- |
| Request | `req_34bd43fee9d043399c7662d480a9dbc0`. Parent / runtime owner: `req_81ac33cac94046b9a2249cd14c0d00ba`. |
| Review target | Fetched `origin/feat/g7-travel-lab-c7ae42d1`, checked out exact `992f9a65ac3f8957e5ec618f21072dc499053810` (detached), tree `db85f35c5f42165802ec7ba6b02bc8a147a12c39`. |
| Runtime | `http://127.0.0.1:18871`, the parent's running preview. Not restarted, not reconfigured. The parent HEAD read `992f9a65` with one untracked doc. |
| Source attribution | `git diff fa552317 992f9a65 -- modules templates app config routes resources bootstrap` is **empty**. The 21 relevant files are byte-identical across this SHA, the parent `_bundled` and the parent installed `modules/{sirsoft-board,raonslab-travel_lab}` (or core) copies: [source-provenance.txt](w04-private-attachment/evidence/source-provenance.txt). The handoff `source_sha` is `fa552317`, which is why the probe's "handoff sha == 992" flag reads false; product source is identical. |
| Window | 2026-10-09 18:38:41–18:38:51 UTC. One run, 12 steps, about 10 s. Supplied tokens expire 21:07:52 UTC. |
| Credentials | Read in-process from the 0600 `access.json` (0700 dir) only. Never printed, logged or committed, and scrubbed from evidence. `database-access.json`, the parent `.env`, platform config and TEST were **not** used. |
| Internal help | None. No subagent, no official child or grandchild Request. |

## Commands

```bash
git fetch origin feat/g7-travel-lab-c7ae42d1 && git checkout --detach 992f9a65ac3f8957e5ec618f21072dc499053810
ST=$(mktemp -d /tmp/w04pa-state-XXXX)      # private 0700 state (token flags, stored UUID names); not committed
python3 -I docs/symphony/w04-private-attachment/probe.py \
  <parent>/storage/framework/testing/travel-live-review-38789ea501e5 "$ST" \
  docs/symphony/w04-private-attachment/evidence <parent>
```

Raw scrubbed results: [probe-results.json](w04-private-attachment/evidence/probe-results.json). For every response it stores the status, content type, length, sha256 and leak flags. Bodies are never stored.

## Contracts read (source at 992 = installed)

- **Support API** (`raonslab-travel_lab`): questions are always secret, the API never exposes attachments (`SupportPostResource`), and a foreign question returns 404. The provisioner creates `travel-lab-questions` with `use_file_upload=false` and **every** board permission admin-only.
- **Admin upload** `POST /api/modules/sirsoft-board/admin/board/{slug}/attachments`:
  - middleware `auth:sanctum, admin, permission:admin,…admin.attachments.upload`;
  - `UploadAttachmentRequest` checks extension, size and count only, not `use_file_upload` (confirming the W04 security SRC note);
  - `post_id` attaches directly to the post.
- **User download** `GET …/boards/{slug}/attachment/{hash}`: `permission:user,…attachments.download` → `AttachmentService::download`, which applies the scope check, the deleted-post gate and the secret gate (`SecretContentGate`: author, view token, or `posts.read-secret`/`manager`).
- **Preview** `GET …/boards/{slug}/attachment/{hash}/preview`: **no permission middleware**. Images only. Applies the deleted and secret gates, unless the request carries a valid `temporarySignedRoute` signature issued by `PostResource` to a viewer who passed the gate.
- **Hash lookup** is scoped by `board_id` of `{slug}` and excludes soft-deleted rows.
- **Attachment DELETE and post DELETE** are soft deletes. A post delete cascade-soft-deletes its attachments. Physical files are kept until the operator-enabled `sirsoft-board:prune-attachments` runs.
- **Storage**: disk `modules`, root `storage/app/modules/sirsoft-board/attachments/{slug}/Y/m/d/{uuid}.{ext}`. The parent has no `public/storage` link, and `getUrl()` returns the gated route, not a disk URL.

## Fixture (own, synthetic, native APIs only)

| Step | Result |
| --- | --- |
| Native login of member / other_member / admin (own tokens) | 200 ×3 |
| Member `POST /support/questions` | **201**, question **116**, `is_secret` true, `is_mine` true, no `attachments` key |
| Admin native upload, inert text file (208 B), `post_id=116` | **201**, attachment **1**, hash `jvcrvxdlT5Rg`, `text/plain`. Response `url` is the gated download route. |
| Admin native upload, 8×8 PNG (166 B), `post_id=116` | **201**, attachment **2**, hash `PI7QTg7Srosl`, `image/png` |
| Files exist (parent runtime, read-only stat + sha256) | Exactly one file each under `storage/app/modules/sirsoft-board/attachments/travel-lab-questions/2026/10/09/`, mode 0644. sha256 equals the uploaded bytes: `7d5b00e2…` (txt) and `6bd3c16b…` (png). |

The fixture was prepared through the admin route. This is **not** a member upload and not the member upload UI, both of which stay disabled.

## Positive controls (real bytes, not SPA 200)

| Actor / route | txt | png |
| --- | --- | --- |
| admin, admin download | **200**, `text/plain`, 208 B, bytes = on-disk sha | **200**, `image/png`, 166 B, bytes = on-disk sha |
| admin token, user download | **200**, bytes = disk | **200**, bytes = disk |
| admin, preview | n/a (non-image) | **200**, bytes = disk |
| **owner (member), preview** | n/a | **200**, bytes = disk (secret-gate author branch) |
| owner, user download | 403 `해당 권한이 없습니다` (C1) | 403 (C1) |
| owner, admin download | 403 admin required | 403 |
| admin `GET admin/board/{slug}/posts/116` | 200, attachments `[1, 2]`, PNG `preview_url` signed | — |
| owner `GET /support/questions/116` | 200, no `attachments` key, no file bytes or filenames | — |

## Foreign / guest denials on the existing real files (all PASS)

Every response below was JSON, carried no file bytes, no synthetic filename, no question body and no `Content-Disposition` filename.

| Route | other_member | guest |
| --- | --- | --- |
| user download (txt, png) | 403 | 401 |
| preview png (secret gate) | **403** `auth.scope_denied` | **403** |
| preview txt | 500 (W04PA-01) | 500 (W04PA-01) |
| admin download | 403 | 401 |
| same hash under another board slug (`travel-lab-notices`): preview / download | 404 / 403 | 404 / 401 |
| admin attachment DELETE / user attachment DELETE | 403 / 403 | 401 / 401 |
| support question show / list | 404 / 200 without question 116 | 401 / 401 |
| native board post show / list | 403 / 403 | 401 / 401 |
| native admin post show / list | 403 / 403 | 401 / 401 |

## Signed preview delegation (C2, OBSERVED — contract)

The admin post show issued a signed PNG `preview_url` with a time-to-live of about 1797 s.

| Request | Result |
| --- | --- |
| Guest, admin-issued signed URL | **200**, bytes = disk |
| Guest, same URL with the signature altered | 403 |
| Guest, same URL with no query string | 403 |

This is the documented design. A signature is issued only in a serialization that has already passed the gate, so that `<img>` can render without an Authorization header. Anyone holding the URL can read the image until it expires.

## Native storage paths

Six direct-path candidates derived from the real stored path (for example `/storage/modules/sirsoft-board/attachments/…`, `/storage/attachments/…`, `/storage/app/…`) were requested as guest and as admin. **None returned bytes equal to the file**, and none contained a leak marker. The parent has no `public/storage` link. No external request was made.

## Deleted-attachment and deleted-question boundaries (cleanup, PASS)

1. **Admin native `DELETE …/attachments/1` → 200.** After that:
   - admin download and admin-token user download → **404** (the same route returned 200 seconds earlier, so this is a real transition, not a nonexistent-ID 404);
   - owner and other_member → 403, guest → 401;
   - the admin post show lists `[2]` only.
2. **Admin native `DELETE …/posts/116` → 200.** This cascade-soft-deletes attachment 2. After that:
   - admin download, user download and preview of the PNG → **404**, including for admin and for the owner's preview (both were 200 before);
   - other_member → 403/404, guest → 401/404;
   - owner and other_member `/support/questions/116` → 404, and neither list contains 116;
   - the admin show of the trashed post returns 200 with `deleted_at` set, listing the cascade attachment `[2]` for the privileged viewer.
3. **Physical files (C3):** both files still exist with unchanged sha256. This is the native soft-delete design. **No physical deletion is claimed.** Rows are retained as soft-deleted; their exact state was not measured, because no SQL was used.
4. **Tokens:** the three login-issued tokens were revoked via native logout (200). Each then returned 401 on `/api/auth/user`. The supplied handoff tokens were left untouched; a read after the run returned 200 for all three.

## Findings

### W04PA-01 (P3, new, native `sirsoft-board`) — non-image preview returns 500 instead of 400

- **Cause:** `User\AttachmentController::preview` calls `$this->badRequest(...)` for non-image files. No such method exists on `BaseApiController`, so `Illuminate\Routing\Controller::__call` throws `BadMethodCallException`. The generic catch turns that into 500 `attachment.preview_failed` (with a non-debug body).
- **Live evidence:** other_member and guest previews of the real text attachment returned **500** with a 58–75 B JSON body and no bytes. The code path runs before any gate, so owner and admin get the same 500.
- **Impact:** wrong status, plus a logged server error per request. No confidentiality impact: no bytes are served, and the response differs from a 404 only for a known 12-character random hash. It is not travel-specific, and it violates the AGENTS.md rule against using generic-catch 5xx for an expected 4xx.
- **Owner:** the product lead / board module owner. Not fixed here; the Request scope forbids product fixes.

### Contract notes (not defects at this target)

- **C1:** on the travel question board, `attachments.download` is admin-only, as the provisioner designs. The question author can preview images through the author branch of the secret gate, but gets 403 on *download* of any file attached to their own question. Combined with the support API exposing no attachments, admin-attached files are effectively admin-visible only. This matches the "no attachments" support contract. If owner download is desired, that is a product decision.
- **C2:** the signed preview URL is a bearer capability with a TTL of about 30 minutes (see above).
- **C3:** files remain on disk after native deletes until the operator-enabled prune runs.

## Status summary

| Check | Status |
| --- | --- |
| Own synthetic question + real file via native admin attachment API | PASS |
| File exists, bytes = upload (parent read-only) | PASS |
| Positive: admin download/preview, owner preview, real sha compare | PASS |
| Foreign/guest denial on existing real files, all routes, no leakage | PASS |
| Listing/resource exclusion (support, native user, native admin) | PASS |
| Secret post boundary (secret gate on preview) | PASS |
| Deleted attachment / deleted question boundary | PASS |
| Native storage URL bypass | PASS (no bypass) |
| Signed preview delegation | OBSERVED (contract C2) |
| Owner download of own question attachment | Denied by contract (C1) |
| Probe identity assertion via `/api/auth/user` | Probe defect: the user resource exposes `uuid`, not an integer `id`, so `me_user_id_matches=false`. Identity is corroborated by `is_mine=true` for the member and by the other_member 404 / admin 200 split. |
| Member upload, settings changes, external storage | NOT_RUN by scope |
| Physical file removal | NOT_CLAIMED (native soft-delete) |
| Browser UI, TEST install, hosted CI, official Validation | NOT_RUN |

## Permission bounds kept

- **Writes:** in this worktree only (this report, `docs/symphony/w04-private-attachment/**`). Nothing was written in the parent workspace; reads there were stat/sha256 of this probe's own two files and the source hashes above.
- **Runtime changes:** none to settings, environment, schema, cache, services or boards. No SQL, TEST access or `database-access.json`.
- **Data touched:** only the own question 116 and own attachments 1 and 2. No existing post, answer or other actor's data was modified.
- **Git and Requests:** no push, merge, deploy or official Request.
