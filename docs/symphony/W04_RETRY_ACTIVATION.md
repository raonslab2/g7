# W04 Retry core activation evidence

**BUILD_DONE / SERVED_ASSET_MATCH.** The source repair has bounded nonauthor source/test PASS. This activation check does not establish repaired browser behavior, CI, official Validation, Git integration, or whole-product completion. The original `31a18318…` Retry FAIL remains preserved.

Reviewer `/root/w03_commerce_guards` read only the parent-owned `core-build.log`, `readers-proof.json`, `readers-revocation.json`, and `explicit-actor-lookup.log` in the allowed `storage/framework/testing/w04-retry-final/` directory. No env/access/private actor/backup/platform credential file was read. The reviewer wrote only this report and its owned public evidence directory, and performed one unauthenticated read-only loopback asset GET. No runtime/source/DB/service/Git mutation occurred.

## Source, build and served bytes

| Evidence | Result |
|---|---|
| Fixed source commit/tree | `0b446a608aa9d76c1e046115d8ea2805a1dd92f2` / `10ba36318652b96dc7dc769ec8405381cbded1f8` |
| Actual `TemplateApp.ts` | `62e89b9dedb93c932a92e1b01944cffb11e88cf0c5cd5a79dc41ca8835f5b76e`; working bytes match fixed Git |
| Parent production build | Completed 2026-10-09 21:01:32 UTC, exit 0 as observed by lead; native completion recorded in retained log |
| Local core engine | 819,481 bytes; `738ee97c6eebc33bc75d29f24397daabb54f124bede254689ca68a18fdb64a0b` |
| Reviewer HTTP GET | `http://127.0.0.1:18871/build/core/template-engine.min.js`, HTTP 200 at 21:15:33.552170 UTC; same byte count/hash and byte-for-byte equality |
| Nonauthor source tests | 12/12 PASS, exit 0; [fixed-source review](W04_CAMPAIGN_RETRY_SOURCE_REVIEW.md) |
| Author related suite | Nine files / 158 PASS / one existing SKIP, including the same 12; author evidence, not an additional independent execution |

The retained transcript contains production engine/editor/DevTools/dashboard stages: 148/205/34/1 modules and build times 8.35s/6.01s/1.19s/418ms. The native production invocation is `php artisan core:build --production`; this is reconstructed from its production label and stage sequence, not a retained exact parent shell prefix. The transcript contains completion, but no independent timestamp/exit footer; the 21:01:32/exit-0 observation is attributed to lead. This reviewer did not rerun the build or claim a new deterministic-build proof.

The normalized transcript has no absolute workspace path and no detected email/password/token/signature payload. Its bytes already needed no path substitution, so the public normalized digest equals the original log digest `0b0a25b66ef2a3d9bc9b1c1c209b219d5e43a060f3ff115f381f82e9797df09f`. The source change, build execution, and served output are connected evidence, while actual authenticated UI recovery remains a separate gate.

## Actor preparation and original cleanup failure

The lead's synthetic permission preparation records users 57/58, roles 13/14: native login/list HTTP 200, no create/update/delete abilities, visible row update flags false, valid and invalid create HTTP 403. Their lists have eight/zero rows respectively. This is preparation evidence from the lead, not independent scoped-actor UI PASS or proof of a broader permission matrix.

The original wrong logout route returned **404**, followed by successful authentication **200**. That cleanup attempt failed and is retained. Subsequent native `AuthService.logoutFromAllDevices` was restricted to these two new fixture users: two own tokens (569/570) removed for user 57 and one (571) for user 58; recorded remaining count is zero for each. Other users/roles were not modified according to the bounded lead evidence. This reviewer did not execute or query token cleanup.

**Exact old-token HTTP 401 is NOT_RUN**, because the earlier plaintext was discarded. Native token-row removal is distinct from an old-token HTTP requery; no replacement successful cleanup status is fabricated for the original 404/200 probe. Published summary contains only synthetic IDs/counts/statuses and source digests, with no email, password, bearer values, or copied raw private actor ledger.

The allowed actor lookup log contains exactly user ID 1; lead reports command exit 0. This is an explicit fixed-ID native lookup, not automated superuser selection, role search, credential discovery, or a new authority grant.

## Public package and remaining verification

Public evidence is under [W04_RETRY_ACTIVATION](evidence/W04_RETRY_ACTIVATION/): normalized build transcript, field-selected `activation-proof.json`, and SHA256 manifest. Public files were checked for credentials and prohibited file types before publication; the source private ledgers are represented only by digests and bounded selected facts. This report does not publish private files or claim an independent DB audit.

Independent actual 390px/1440px Retry/scoped-actor browser verification and fixed API/persistence validation remain separate requests. Native auth, ability gates, failure honesty, draft/empty/editor behavior and customer flow must be confirmed against the built source/served hash. Hosted CI, canonical Validation receipt, Git publication/integration and production release remain outside this activation proof. No additional source-review finding was discovered here.
