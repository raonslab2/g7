# W04 mobile request cards and optional-auth repair — nonauthor source review

Decision: **PASS_BOUNDED_SOURCE**; no new P1/P2 finding in the reviewed scope. This reviewer did not implement either mobile/auth production repair or run a browser/API/APP/TEST DB probe, and grants no official Validation. The initial decision was read-only source review; the distinct clean-process test integration execution is recorded separately below. Only this report was written. The separate Module vendor-mode repair was authored by this reviewer and is expressly excluded from this nonauthor decision.

## Reviewed behavior

Read the root guide, both travel extension AGENTS guides, frontend iteration/rendering guidance and native backend authentication contract. Compared actual request-layout/support-route diffs, shipped Button implementation, production CSS, native OptionalSanctum/Router sorting implementation, author regression sources and available append-only geometry evidence.

`layouts/travel/requests.json:374–462` addresses the observed cause: the shipped Button defaults to `inline-flex items-center justify-center`, so multiple card sections previously became horizontal children. `flex-col items-stretch`, `min-w-0` on grid/item/card, wrapping header/identity/footer and `break-words` on full product title now permit a narrow card. Production CSS independently contains `flex-direction:column`, `align-items:stretch`, `min-width:0`, `flex-wrap:wrap` and `overflow-wrap:break-word`. The title's previous `line-clamp-1` is removed. No blanket `overflow-hidden`, truncation, text removal or shared component change is introduced.

Request ID, status enum, timestamp, full product-title fallback, optional additional-item count, quantity and server amount/currency bindings remain present. The actual Button remains `type=button`; click navigation still targets `/travel/requests/{{inq.id}}` with `mergeQuery:true`. Pagination, datasource, iteration variable and ownership/API declarations are unchanged. The new focused test checks full long title, ID/status and Enter navigation using registered components. It is **one new case in a 27-case focused file**, not 27 new regressions. Geometry helper source uses shipped preview components and actual production CSS, but manually supplies synthetic bindings and navigation: that stress probe is a component/layout check rather than full DynamicRenderer/API E2E.

`TravelOptionalSanctum.php:13` subclasses native `OptionalSanctumMiddleware` and adds only the native `AuthenticatesRequests` marker. It inherits `handle()` without overriding any token lookup/decision. The four public notices/FAQ routes select its FQCN; question routes retain `auth:sanctum`, 120 aggregate and ten-create limits. No global middleware alias, priority list, core authentication or unrelated route changes are present. Public limit remains600 with its dedicated prefix.

Native Kernel priority lists `AuthenticatesRequests` before throttle and bindings. Native `SortedMiddleware::middlewareNames` examines implemented interfaces, so the wrapper is ordered before the public counter. It can establish the actor before native throttling chooses the user signature. Class namespace/path matches the module's existing Composer PSR-4 declaration and native extension autoload-path registration; this review did not manually mount or execute an installed route.

Inherited decisions remain: no bearer→guest; expired token→guest; unknown/invalid token→native401; valid token→native `Authenticate` using Sanctum; an already-authenticated request remains accepted. Private questions continue to require native authentication. Moving the inherited optional decision earlier closes the ordering defect without introducing fake identity or changing private ownership policy.

## Evidence interpretation and limits

| Evidence | Source-reviewed observation | Independent runtime status |
| --- | --- | --- |
| Original independent browser source598, evidence commit695341be | 390px document412/final424 failure remains negative evidence | Historical, not repaired PASS |
| Author original-owner before JSON | Configured390, document/innerWidth425; old installed request layout/CSS;1440 fits | Not rerun by reviewer |
| Author original-owner after and after-pointer JSON | Document390/1440; bundled/installed layout/CSS/JS hashes match; Enter plus mobile tap/PC click target inquiry100; exact issued-token cleanup PASS | Author checks only; official fixed-SHA browser recheck pending |
| Author source stress geometry | Old390 document1265 FAIL; candidate390/1440 fits, status count3, navigation/focus retained | Manually supplied synthetic bindings, not independent E2E |
| Focused27 template cases/type/build | Author report asserts PASS and unchanged JS; inspected actual test/CSS sources | No reviewer execution; whole frontend/CI not certified |
| Native auth-ordering/counter suites10/2032 | Actual Router/Pipeline/native optional auth and sequential ArrayStore; source replaces SQL token lookup with fixture records and guard callback | Source review only; not actual Sanctum DB validity, HTTP/business persistence or multi-worker cache atomicity PASS |

Viewed the author's sanitized original-owner candidate390 PNG: retained readable ID/status/time/title/quantity/amount, without horizontal clipping. Artifact viewing does not establish a new browser execution. Geometry JSON after phases bind `actualHead=cf7a2be44ad2fb9e2b55c7a39657df219fde1cd9` plus explicit uncommitted source hashes; HEAD alone is not the repaired source identity. Original-owner source598 access lineage and candidate pins are distinguished. Final author freeze report is `a4958806bb6059ee57a66c47d61f5ac67fa38843102cd714adb601d8c4fbc970`; the portable mobile-request-repair-manifest is `3903a80f66346f095a8ed3961e24387a3e9eff1b8b6fdb83434636d1d62b0c1f`. This reviewer independently verified all29 referenced files against their hashes (8 source/build/test/changelog,7 phase JSON,14 PNG). Hash verification is not execution or visual inspection of every screenshot.

The earlier date-absence observation is **resolved as an author diagnostic interpretation of a resized image**, not a reproduced product defect. This reviewer had already seen timestamps in exact `mobile-request-native-owner-after-pointer-390.png` (hash `21a840f5c2c084b07822206c6fcf6f830a82f24cd96ded89451058723c78d601`). The author subsequently added a settled native DOM phase for the same original owner/inquiry100: at390/1440, server `2026-10-09 23:19:41` compares to visible minute-prefix `2026-10-09 23:19`, both PASS; document widths remain390/1440 and exact own new-token logout/requery reports401. Product sources remain unchanged. This is reviewed author DOM evidence, not a new browser execution by this reviewer.

Follow-up final author report hash is `5fc3f5ca355199948e70cff7fa64512ec590bd4f40ef22c968b0bddbf51bb46d`; updated32-file manifest is `99189bd6984a01fce9c91c8559429e471f6e7f09cfd43f7cd4235cbd76a6ab3f`; new date JSON is `09d7f87777b230a6246f9484e3d50da2c696ffde13366319bd19574440df99a5`. All32 bindings independently match on read-only recheck. The new helper phase waits for the actual native inquiries GET response and settled card date text, then compares its server prefix. Its hash changed from `45a940c91170e6330b8f0b82683943751b16b849a38165babb510f2afc142567` to `927c9da81a8bbf0c504ac8ff387ebf08ee0c90b7299cad839e9947d56f9295d2`; the other eleven original source inputs match their earlier pins. Thus the original twelve-file scope below remains the initial review record; follow-up scope with only that helper hash replacement is `e169f08456bca69a3429dd4a8cb79f697dd7eb4831da9d33c7ba47f8fa454d2c`. New date evidence records HEAD `760042e94b8b3f38cf5ae0f5830f7dbea3089841` and the unchanged three bundled/installed UI product hashes; it does not certify all backend/runtime source at that HEAD.

Required remaining checks: publish/fix the integration SHA, independently rerun native390/1440 request-list geometry and keyboard/pointer navigation, and independently verify actual public member/guest/expired/invalid bearer behavior and600/601 per-actor same-IP separation. Installed wrapper availability/cache provenance, persistent Sanctum and file-cache contention are outside this review. No broad regression, security release, database recovery, deployment or canonical receipt is inferred from these source checks.

## Fixed input hashes

All twelve files below were hashed at intake and rehashed at review completion without change. Snapshot HEAD was `cf7a2be44ad2fb9e2b55c7a39657df219fde1cd9`; candidate remains a shared owned diff, not an independent commit or remotely integrated result.

| Path | SHA-256 |
| --- | --- |
| Template `layouts/travel/requests.json` | `ad03a73eec4e82d0663dadeb9468579ae5a324a450716e24cededeb0312d663c` |
| Template `dist/css/components.css` | `e340b3bfcf22be2ef56f7d11a8f9bf2fc3563439f908628ab044931b463dce66` |
| Template `dist/js/components.iife.js` | `9cc99d8d8db0161cfc5f537531eb16f00d1a7ca272fd0b31824823185f46b390` |
| Template `__tests__/layouts/requests-help.test.tsx` | `24e9234df9b941a483c3bd0605c56d45f950cbd940b8ce94c9735ca9f1928dab` |
| Template `__tests__/geometry/requests-mobile.mjs` | `6c161061cba037b54118d72f6ab3fa727da5f55c726897c02bfd867c7580f69a` |
| Template `__tests__/geometry/requests-native.mjs` | `45a940c91170e6330b8f0b82683943751b16b849a38165babb510f2afc142567` |
| Travel module `src/Http/Middleware/TravelOptionalSanctum.php` | `268e1a45c37eb266676a8bf580b26f3c09bf8f15eb92a76517a872d6814198d2` |
| Travel module `src/routes/support.php` | `e04270e3628c1a59e6d8585b855f8eeaee3126e8ad1b40be7bca822e16d77195` |
| `app/Http/Middleware/OptionalSanctumMiddleware.php` | `5f43087b44425fc4ed4d08ce2a4a6c9bfa9093368c56d2adf1c5371cd8cd1e96` |
| `bootstrap/app.php` | `5089e0d9f00ad17c0d4ee6e199b4ab2622349cd140b255efe73a16a7e429f62d` |
| `tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php` | `a0b870889de8b28a7f0cff8db14f58c2e183b92d06a4c06ce3031a2832e1dfa2` |
| `tests/Unit/Extension/TravelSupportThrottleIsolationTest.php` | `985357e8bbea8bee707ea7b9fd6cbab3c431cfeed4790b517819d53b63d8082c` |

Template paths are under `templates/_bundled/raonslab-travel_lab`; travel module paths are under `modules/_bundled/raonslab-travel_lab`. Twelve-file scope digest `f5a5c66da784061d3bb512f8edae2e27c2bdaa56b2ea64972c9dab7f7a36722d` uses full paths and Python `sha256(json.dumps({path: sha256(bytes)}, sort_keys=True).encode())`.

Before-repair request layout `a57563b0da7f85aafa336c83fa076376f8f357e4c557c89bf58515e88a6ef266`, CSS `2750062e8f93d635d9f126f6249852fe370725e3d7abd77d6c09cf58c6507529`, public support route `d120806e34d46327a94e34de41df02692e0627845a199507eacf8883d83d093f`. Wrapper is new; JS and native OptionalSanctum/bootstrap are unchanged. No source/build/DB/environment/service/Git operation was performed by this reviewer.

## Distinct follow-up: clean-process counter test integration

The lead preserved an earlier combined35-test execution with2,187 assertions and five controller-DI setup errors. Another native author repaired `TravelSupportThrottleIsolationTest.php` from the earlier frozen `985357e8bbea8bee707ea7b9fd6cbab3c431cfeed4790b517819d53b63d8082c` to `e905e18258e5afd302c7364beef97082a0c019dc64ba3faef07d731e4b4ceae5`. This reviewer independently read the revised source and verified the new hash unchanged before/after execution. This test input change is separate from the initial twelve-file source review and helper-only date follow-up; the original hashes above are retained as historical attribution.

The repair creates clean PHP test processes with `PreserveGlobalState(false)`, registers only the bundled module source ClassLoader, and binds the actual native SupportController with a mocked **unused** TravelSupportService for middleware metadata. Cleanup closes Mockery/unregisters the loader and restores facade/container context. It does not boot an application/provider or call the support business service. Five existing counter scenarios and required route/method/rate assertions remain. Counter requests still inject GenericUser and select only native numeric throttle middleware; they do not now certify optional authentication, authorization, database tokens or multi-worker atomicity. The file's introductory “no controller involved” wording means no controller action executes; its real constructor now supplies metadata.

Actual independent integration execution by this reviewer:

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/ModuleVendorInstallGateTest.php tests/Unit/Extension/ModuleVendorModePersistenceTest.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php
```

**PASS:35 tests /2,233 assertions,3.046s,28MiB**, PHP8.3.6/PHPUnit11.5.56, exit0. No forced global `--process-isolation` override or dropped assertion/filter was used. The earlier five setup errors remain negative harness evidence rather than product findings retroactively marked PASS.

Other unchanged test-file pins in this execution: ModuleVendorInstallGateTest `e0128b8e31ad573109b0a27c369a0318334842ee685e88fdc80440ded2ba9ccd`; ModuleVendorModePersistenceTest `e018ffb131c1d71b45b10ad1cd8c9878124e15ae0c5455ad220d4a9c63933d72`; TravelSupportAuthThrottleOrderingTest `a0b870889de8b28a7f0cff8db14f58c2e183b92d06a4c06ce3031a2832e1dfa2`. These are fixture/vendor-loader, native in-memory SQLite persistence, and in-memory auth/counter checks. This run does not connect to APP/TEST MySQL, read dotenv, invoke HTTP/preview/business controllers, restart services, or modify product/Git files. The persistence implementation was authored by this reviewer; its included test execution is integration evidence, **not nonauthor acceptance of that implementation**. Existing independent runtime/browser/auth/cache/official Validation gates remain separate.

Final comment-only follow-up: counter test hash `939081b79ca0c609ac8ed955b5b961ab200292bd84c4d6405bf62aa019a6d013` corrects its opening docblock to state that actual controller metadata is resolved through an unused service fixture while no controller action executes. This reviewer independently reconstructed the prior docblock in memory; the resulting entire file hash exactly equals the tested `e905e18258e5afd302c7364beef97082a0c019dc64ba3faef07d731e4b4ceae5`. Thus only that comment changed; executable logic and assertions are identical. The35/2,233 behavioral execution above remains bound to the earlier hash, with unchanged behavior supported by the exact comment-only delta. No repeated test execution or new runtime PASS is claimed for the final comment hash. Review is frozen.
