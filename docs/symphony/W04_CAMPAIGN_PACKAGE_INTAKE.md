# W04 campaign package source intake

**CHANGES_REQUIRED (integration/evidence); PASS_BOUNDED_SOURCE for the inspected projection/provisioning paths.** 비작성자가 실패한 canonical CLAUDE Request의 저장된 구현을 실제 Git 객체로 인수했다. 실패 요약을 테스트 PASS나 내부 리뷰 승인으로 받아들이지 않는다. 제품·DB/env/service/runtime/build/Git 변경 또는 테스트 실행 없이 이 보고서만 작성했다. 다른 native 검증자의 backend/frontend 실행과 후속 공식 고정 SHA 검증은 별도다.

## 고정 계보와 소유 범위

Original saved commit `c85eea30ca674289648c772f7f36ea4cdaa1611f` → lead cherry-pick `95d16543605d391dbf7a2d2869c08dbb9f163754`, tree `c93db745cfce2353833abe7651ec196baabb10c9`. **78 changed paths: module50 /template28 /outside0**. 각 변경 경로의 두 commit Git blob bytes를 직접 비교해 78/78 동일함을 확인했다. 두 전체 tree는 다른 부모 기반을 가지므로 전체 tree 동일이라고 하지 않는다. sorted native diff-tree path list SHA-256 `28d7b35ea31ec1ad557775af7d020cbef9f2378e9d1ed33810caa0c4ff77b34d`.

Read input contract `docs/symphony/W04_CAMPAIGN_PAGE_CONTRACT.md` SHA-256 `0f6dd26920e09c781579f9dc8c1d4d77fdb5ca49533dfaf17714270b61fe46cb` (original body ef79… preserved); module/template AGENTS, native Page service/repository and package/helper contracts. Commit78 paths stay inside explicitly assigned travel module/template ownership: core/native Page/Board/ecommerce/package runtime scripts are unchanged in that child commit. Parent's subsequent seven-file integration diff is separately bound below.

## Requirements: implemented source versus execution

| Requirement | Actual source | Review scope / remaining proof |
| --- | --- | --- |
| Native Page persistence/reuse, own two campaigns | `config/campaigns.php`, `CampaignRegistry`, `CampaignPageRepository`, `CampaignService` | Registry directly reads own definition, two slots with existing nature/wellness enums; literal PageService($slug,false) and boolean published recheck. Source implemented, real installed Page propagation NOT_RUN here. |
| Public published-only privacy, no arbitrary Page enumeration | `src/routes/campaigns.php`, Requests/Campaign, `CampaignController`, `CampaignResource` | Named two GET routes, distinct travel public numeric throttle/optional native auth; exact membership before lookup, draft/missing/unknown404 including admins; narrow title/excerpt/version/body fields, no creator/attachment/preview/internal metadata. Native private preview is not reused. Actual token roles/draft/cache transitions pending. |
| Campaign main/list/detail/catalog transaction entry | Template home/campaigns/campaign_detail, routes/base navigation | Real campaign API data sources; old static campaign success fallback removed; server registry filters call existing catalog, prices come from server products. Empty/error/retry states and Page alias present. Actual390/1440→catalog→product/cart and existing transaction regression NOT_RUN here. Four catalog themes remain existing behavior; four extra guide Pages were explicitly deferred in contract, not silently implemented. |
| Admin published content management | `module.php`, `resources/routes.json`, admin_travel_lab_campaigns.json | Native Page read permission, native numeric ID edit/detail links, abilities-gated native publish API, fixed-slug prefill. Native substring search acknowledged and exact two-row client mapping/pagination preserved. Actual create/edit/version restore/publish/unpublish controls NOT_RUN here. |
| Safe own editorial renderer | Template PageBody.tsx, components/export/editor spec/locks | Private DOMPurify instance, formatting-only tags, ALLOWED_ATTR=[], DATA/ARIA false; text React-escaped, no mutable policy props. Links/images/media removed, unknown mode uses text. Source implemented; actual remote-request/browser geometry proof pending. |
| Default seed preservation/explicit synthetic preparation | Command + TravelCampaignProvisioner, existing DatabaseSeeder/module getSeeders | No default install/update/sample/request invocation. Real native create/version/Auth hooks, skip all existing rows, explicit flags/actor/local adapters. Actual guarded twice/after-edit/race and empty default-install proof pending. |
| Page mutation cache/native local side effects | InvalidateTravelCampaignSeoCache + module hooks | Sync native Page after_create/update/publish/restore/delete listener targets two slugs (including old update snapshot), travel layouts/URLs+alias; native version/activity/SEO/sitemap not bypassed. Cache exceptions logged, not a universal stale-HTML privacy guarantee. Keep static cache disabled until real transition proof. |

No P1/P2 source defect was identified in the inspected public projection/provisioning flow. This is not full review of every78 file/UI/core/search/attachment path and is not a whole-product PASS.

## Explicit provisioning: flags, actor, writes

`ProvisionTravelCampaignsCommand.php:16–25` declares **boolean `--lab-confirm`** and explicit `--actor=`. Positive decimal actor ID is parsed before provision; no automatic admin lookup/account creation/role grant/token creation. `config/campaigns.php` requires isolated marker AND dedicated flag, both defaultfalse. `TravelCampaignProvisioner.php:56–64` requires confirmation, effective true flag, array mail/sync queue/mysql-fulltext/local disk and native user before any write. `resolveActor` requires real UserRepository user with native Page read/create Admin permissions; a catalog role does not substitute.

Provisioner captures default Auth guard actor, temporarily sets the permitted actor, and restores/forgets it in finally. Native PageService uses Auth::id for created_by/updated_by and version snapshot. `PageRepositoryInterface::findBySlug` is a direct native slug-existence read, not a permission-scoped admin list: **any existing document is skipped**, including foreign owner/draft/edited content/version/publication. It does not upsert, republish or update. Missing slots alone call native PageService::createPage with own ko/en public copy, text mode, publishedtrue, no temp attachment key; a unique race is explicit failure rather than overwrite. First slot may remain created if second fails; no all-or-nothing batch claim exists and rerun safely skips already-existing content.

Native Page create legitimately runs versions/activity/SEO/sitemap jobs. “No external sends” depends on effective guarded package adapters; it is not “no hooks/jobs”. The command itself checks marker/local config but **does not bind a schema/account by marker alone**. Its module docs' standalone artisan example must run only in a known isolated environment. Parent's package command below supplies the existing schema/account guard. Reviewer did not invoke either command or change any actor/flag/environment.

## Metadata and missing evidence

Original commit module/composer/template/package/package-lock root+packages[empty].version all **0.1.3**. Module has no JS package/lock in this architecture; absence is not fabricated metadata failure. Template requires Travel>=0.1.3, native Page>=1.1.2 unchanged public API, ecommerce>=1.2.1, core>=7.0.12. DOMPurify package range ^3.3.1 locks **3.4.14**; use committed lock/native production build, not unlocked install.

Original Travel module/template still declare Board>=1.1.2 although final package needs existing Board1.1.3 attachment translation fix. Parent's subsequent metadata correction closes that bounded integration issue below. Native Page/core/other RAON business manifests require no blanket change for this travel-only API. Original template AGENTS TLDR still says v0.1.0; this is a documentation consistency issue, not implementation evidence.

`modules/_bundled/raonslab-travel_lab/docs/campaigns.md:3` links `../tests/CAMPAIGN_EVIDENCE.md`, but **that file is absent**; template equivalent absent too. No canonical child validation receipt/internal review report or execution log exists in the78-path saved scope. There are20 PHP test methods across five Campaign Feature classes, native Page test base and focused UI/security tests/scenarios, but source presence is not a PASS count. CampaignTestCase loads real Page provider/service/migrations/hooks with scoped SQLite and manually registers routes/listeners for test integration; it does not prove native installed lifecycle/module registration or MySQL/actual admin browser. Do not accept failed provider summary as PASS. Missing evidence link and execution/installation/independent gates must be completed by lead/reviewers.

Source scope contains no environment/SQL dump/options paths. New public content is own synthetic copy; test users/password literals are synthetic source fixtures, not verified live credentials. This bounded path/content inspection does not replace lead's final public Git hygiene checks or infer that private child evidence was published.

## Parent seven-file integration follow-up (working diff, not child SHA)

Read only; no setup/artisan/build command was run. `.env.travel-lab.example` adds dedicated flag **0**. `setup.php:28` recovers only a missing key to0 and leaves an existing value untouched; no campaign invocation is added. Package README requires installed0.1.3+Board1.1.3, dedicated opt-in, **actual existing native permitted Page actor** replacing example123, and `php scripts/travel-lab/run.php artisan raonslab-travel_lab:campaigns-provision --lab-confirm --actor=123`. No auto actor/grant/default invocation; disables optional flag after explicit work and separates pending runtime gates.

Existing run.php calls travelLabEnvironment before artisan; inspected environment helper validates marked local allowlisted APP schema/account/host/ports, connection overrides, effective mail/queue/storage/cache, and rejects retained installer/config overrides. Values including feature flag are passed to the native process. Lifecycle wrapper retains scoped finally config cleanup. Existing extensions.php installs Page but does not seed campaign Pages automatically. Thus the proposed package recipe supplies bounded schema guard absent from standalone module marker; actual recipe remains NOT_RUN here.

| Parent file reviewed working SHA-256 | Hash |
| --- | --- |
| `.env.travel-lab.example` | `d7b3588103f0f1e1aa1866303bc8c9654010ba6bd162017ed119f43f3022d762` |
| `deploy/travel-lab/README.md` | `17e63e4a60fc6e8414173a47e5e2395eaa8ffffa925f76c7906f5a7f0e3e49bc` |
| `scripts/travel-lab/setup.php` | `6ec1e8378e54ff450e2b135041dea07cd6b7dcabc7e5899821fa666852ce80c9` |
| Travel `module.json` | `4cdd3b1baf6c8794bc029327c686249157a2f7182ef47723d463869f2f85c866` |
| Travel `template.json` | `28268a1d971ddfc3080a059955bc21d101e0eca7bb750004ecbd89bdadac4991` |
| Module `CHANGELOG.md` | `397254371167e81b9991e2e853f4194d2085f2e9c7e19309fc05b38fc2c6912f` |
| Template `CHANGELOG.md` | `0c628995e1c964012da79799b82537487f628bb42631558d2f33ee0e8bb832f5` |

These two manifests now require Board>=1.1.3 and keep version0.1.3; no unrelated manifests touched. Current0.1.3 changelog headings normalized to canonical Changed. **P3 wording correction requested:** preserved old module compatibility sentence says Board remains unchanged and template says other dependencies remain unchanged immediately before the new Board bump bullet. Exclude Board from those sentences to avoid contradiction. Subsequent parent changes need their own pins; hashes above do not claim a future working state.

## Key original implementation pins (Git95d16543 bytes)

| Original source | SHA-256 |
| --- | --- |
| module.json | `608567d3bd20309b224e05c1a3facb42e3590d4144366684fcdc929af4f9d932` |
| composer.json | `4aeede429ea529bea951a1c80cd5d40be5ab949ceb0a2ecc441bf7240aac35c0` |
| config/campaigns.php | `c9c0e0d475a00860cbd96481124135606cefd70fe0c9657f0c069286460c2838` |
| ProvisionTravelCampaignsCommand.php | `b40a0948c6fe2a2c5ee7e3cac7abac4167ef3cd461743eb55b6c63b5535b6aca` |
| TravelCampaignProvisioner.php | `ca1a20d6cb4c8158c9995ec01cef4b1f0b312c7002cde490857892c8aa90cdda` |
| CampaignPageRepository.php | `2437d0fdc0e55ff0a6daf847773efae04c5da742656b2b9fbc167f5f4fac0a65` |
| CampaignResource.php | `f61994c349d58c119678de945c071fcfd25fe5f62edb56e694f78c0b7c81485d` |
| native admin campaigns layout | `f52767d26664b1a5d5be3623b46f7171580377143f79348640899707e5163a5f` |
| Template manifest | `e8d5461b3e0835d3ebfe32c8802825eed907331b67bea34b2b71df69a1b742fd` |
| Template package-lock | `f78c69c971e2884304d3907816cb1ff441ce005ccf2b5239f1cb58445725188f` |
| PageBody.tsx | `79c839f2b28b346f0f6639c08c153bc81938d26cd937ea42c04e93f26a77fc43` |
| Template dist/js/components.iife.js | `0cd1fbebface8e0a89c5a5c14597435e29bbbcd6a363bf109cca281543aa201c` |

Next: fix stale/missing document references; attach actual bounded execution evidence rather than summary claims; publish a single integrated checkpoint with parent package changes; independent native install/provision+role/privacy/cache/browser390/1440 and postintegration transaction regressions at that same fixed SHA. Existing fa552 and prior failure evidence keep their original bindings. This source report does not close those gates.
