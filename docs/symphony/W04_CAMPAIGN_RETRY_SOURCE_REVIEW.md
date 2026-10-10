# W04 campaign Retry fixed-source review

## Decision and limits

**PASS for the bounded fixed-source repair review; no blocking finding.** This permits the lead to continue its already-authorized production core build and subsequent independent verification. It is not a deployment, browser, whole-product, or official Validation PASS. The original `31a18318…` admin Retry **FAIL at 390px and 1440px remains intact**.

Reviewer: native `/root/w03_commerce_guards`, Request `req_81ac33cac94046b9a2249cd14c0d00ba`. The reviewer did not implement this TemplateApp repair or its new regression. Prior authorship of the unrelated checkout test harness/Board/core installation repairs does not receive independent approval in this review. Only this report was written; no product, environment, DB, runtime, cache, build, service, or Git mutation was performed.

## Frozen target

- Commit: `0b446a608aa9d76c1e046115d8ea2805a1dd92f2`.
- Tree: `10ba36318652b96dc7dc769ec8405381cbded1f8`.
- Parent: `dd9f4ef4a52e8c82579a5391f43eabf16e8983cd`.
- Exactly five changed paths, 338 insertions/one deletion. Production change is confined to `TemplateApp.ts` (22 insertions/one deletion); remaining paths are the new test, two changelog bullets, and author report.

| Changed path | SHA256 |
|---|---|
| `resources/js/core/TemplateApp.ts` | `62e89b9dedb93c932a92e1b01944cffb11e88cf0c5cd5a79dc41ca8835f5b76e` |
| `resources/js/core/template-engine/__tests__/TemplateApp.refetchRecovery.test.tsx` | `bb92c44535629e4c0cc47c2f0087b9d2f10d3d718eed323cd2a0fe101922fcc3` |
| `resources/js/core/template-engine/CHANGELOG.md` | `82fb0d7482137df51a4525745c5704aa7182bb140a1e131eb5689eaab9fec4b2` |
| `CHANGELOG.md` | `0d5761c6503e52e645203fb0047d6c1700733bc912dca3fe1f2d7c6bd7d430fe` |
| `docs/symphony/W04_CAMPAIGN_RETRY_REPAIR.md` | `b321fd81dd1d1e845e050088b3a144c1698525298e4bd410b23d95325ebba42b` |

All five working files matched the fixed Git blobs before and after the test run. Actual campaign admin JSON is unchanged from the parent, digest `32ac9d46844dcdada237a680c43f10f78c822b4e07c9abe8c2a2baac93d0861b`. No stale installed or rebuilt file was treated as this source target.

## Source assessment

Read the root development guide and relevant data-source/auth/error/init contracts, native `DataSourceManager`, `updateTemplateData`, progressive error handling, refetch body, and changelog/version policy.

The existing refetch success predicate (`state === 'success'` and defined data) is preserved. On that path, the implementation reads the current engine context after the await, clones the current error bag, removes only the requested source entry, and replaces the last empty bag with `undefined`. The native update function spreads the explicit new value into the context, so old error state is actually removed. Both source and error bindings are invalidated. This fixes the reported successful-response/sticky-error condition without clearing the error before a real result.

On returned error and caught exception, the implementation reads the latest bag again and replaces only the requested source message/status. Native API message takes precedence over the transport message; network status remains undefined, consistent with the existing progressive convention. Old payload and `currentFetchedData` remain untouched, as do unrelated errors. `finally` still clears pending state. The change introduces no request, auth bypass, error-handler duplication, fallback endpoint, or arbitrary success response.

Native endpoint/query/header construction, forced `auto_fetch: false` refetch, page/modal lookup, optional sync behavior, return value, AuthManager decision, and initGlobal/initLocal remain in their existing paths. Existing undefined-result behavior is unchanged. Usable empty collection, null, and empty-string data enter the same defined-data success path. The 204 test exercises Axios's empty-body value via transport fixture; it is not a real HTTP 204 probe. Native DataSourceManager treats explicitly declared fallback as success; that compatibility is preserved. The real campaign data source has no fallback.

Latest-bag reads preserve errors introduced by another source while this request is in flight. The test proves that case. It does not introduce or certify request-order arbitration for overlapping requests to the same source, full route-change cancellation, or every progressive/refetch interleaving; those are not changes in this patch.

No public signature/extension API is added. Both Travel Lab manifests already require `>=7.0.12`. Adding a Fixed bullet to the existing unpublished core 7.0.12 batch and engine Unreleased section is consistent with the documented internal-repair decision; no unrelated blanket consumer minimum-version bump is justified by this diff. Existing release smoke/CI/Validation gates remain mandatory at lead scope.

## Independent execution

Executed once at the pinned source with existing locked dependencies:

```sh
./node_modules/.bin/vitest run resources/js/core/template-engine/__tests__/TemplateApp.refetchRecovery.test.tsx --maxWorkers=1 --no-file-parallelism --reporter=verbose --no-cache
```

**PASS: one file, 12 tests, zero failed/skipped; exit 0.** Start 21:00:06 UTC on 2026-10-09; reported duration 4.68s, test time 352ms. One React `SlotProvider` missing-child-key warning appeared in the rendered test; it did not fail the assertions and is not represented as warning-free execution. No production cache/build or external service was exercised.

The suite runs actual TemplateApp, DataSourceManager, AuthManager.checkAuth, action dispatcher, binding engine, shared engine context update, and rendering. Native ApiClient/fetch transport is mocked, and basic HTML test components replace the separately shipped admin component bundle. The actual campaign JSON is loaded. This distinction matters: native auth decisions are exercised, but credentials/network token issuance, actual shipped component behavior and real browser events are not.

The 12 cases cover actual rendered Retry dispatch/title recovery/abilities; unrelated error preservation; failed requery retaining payload; repeated 429/network failure then success; pending/error visibility; another error added in flight; initGlobal/initLocal plus binding invalidation; native no-token 401 and zero transport calls; empty collection/null/204-style empty value; declared native fallback.

The author's nine-file result (158 PASS, one existing SKIP, including these 12) was inspected as author evidence and not redundantly rerun or added to the independent count. Original first-draft old-source 0/8 includes one initGlobal fixture mistake. The author preserves a corrected old-source 0/8 baseline and subsequent 8/8 repair before adding four compatibility controls. Those historical results were not re-executed here; no temporary author JSON is claimed as a new durable independent test run. The independent browser's original failures remain the actual product baseline.

## Next verification

Lead must build and bind the updated core asset to the new fixed candidate, then independently exercise actual login/native Page list and Retry at both widths: abort once, retain error while pending, recover with genuine native 200/two titles/no error; verify another real failure remains visible and later success recovers. Existing permission/ability, scoped actor, draft/empty/native editor and customer transaction/support checks remain separate. Source review and jsdom test results do not close scoped actors, served SEO/cache, locked dependency binding, PHP/MySQL persistence/concurrency, full historic scenario matrix, CI, formal Validation, Git integration, or runtime release gates.
