# W04 Page-backed travel campaigns — implementation contract v1

**Proposed minimum:** two campaign documents persisted as native `sirsoft-page` Pages, a travel-owned fixed-slot projection API, real customer screens and an admin adapter to the existing native Page editor/version/publish UI. Existing four-theme catalog/facet/search behavior stays the theme UX; additional Page-backed theme guides are deferred. Explicit lab provisioning creates missing synthetic Pages through PageService; it never overwrites existing edits. This contract is ready for one implementation child. It is a design decision for the RAON lab, not customer approval, implementation evidence or release PASS.

Product/runtime target `fa5523175ac494cfbd13bbf89bf06b3ec91835a6` remains unchanged during the running browser review. Source-only design input HEAD observed `992f9a65ac3f8957e5ec618f21072dc499053810`. Only this contract was written; no source/Git/DB/env/service edits or execution.

## Native facts and exact reuse

`sirsoft-page/AGENTS.md` defines one slug/address per document, version history, native admin CRUD and public slug lookup. The native module owns no public Page-list endpoint under its current domain contract. Therefore the travel API enumerates **two known travel navigation slots**, not arbitrary Pages or a Page search/list; it adds no generic list endpoint to the native Page module. No Page module/core changes are required.

| Native source contract | Reuse |
|---|---|
| `PageService::getPublishedPageBySlug(string $slug, bool $allowUnpublished = false): ?Page` | Call with literal **false** for every customer projection, even for authenticated admins. It delegates `PageRepositoryInterface::findBySlug()` and returns null for drafts. |
| `PageService::createPage(array $data): Page` | Explicit synthetic provisioning only; native create transaction, initial version1 snapshot, Auth actor and hooks preserved. |
| `PageService::updatePage(Page $page, array $data): Page` | Existing native admin PUT path. Native scope check, transactional current_version increment and snapshot preserved. |
| `PageService::changePublishStatus(Page $page, bool $published): Page` | Native publish/unpublish path and scope check. **Publish toggle does not create a content-version snapshot** in inspected implementation; do not claim every toggle increments version. |
| `PageService::restoreVersion(Page $page, int $versionId): Page` | Existing native version restore; only matching Page version accepted, new version created. |
| `PageService::getPage(int $id): Page`, `getPages(array $filters=[], int $perPage=20)` | Native admin API/details/list; permissions scoped to `created_by` via native Page repository/service/Resource abilities. |
| `PageRepositoryInterface::findBySlug(string $slug): ?Page` | Provisioner existence check only, through injected interface; skip any existing document. No raw Page table writes. |

Native public `GET /api/modules/sirsoft-page/pages/{slug}` allows draft preview to actors with `sirsoft-page.pages.read`. Travel customer routes must **not** inherit that preview behavior. Native admin preview remains available separately through existing Page admin UI; it is not a published travel campaign.

Native hooks actually emitted:

- Create: `sirsoft-page.page.before_create`, `filter_create_data`, `after_create(Page, data)`.
- Update: `before_update(Page, data)`, `filter_update_data(data, Page)`, `after_update(Page, data, snapshot)`.
- Publish: `before_publish(Page, bool)`, `after_publish(Page, bool)`.
- Restore: `after_restore(Page, PageVersion)`.
- Existing activity listener records create/update/publish/restore; SEO listener invalidates native Page URLs/cache, updates sitemap resource index and dispatches `GenerateSitemapJob`.

Do not call Page repository create/update directly or implement a parallel version table. No native public API or service signatures are changed by this proposal.

Exact injectable interface: `Modules\Sirsoft\Page\Repositories\Contracts\PageRepositoryInterface`, source `modules/_bundled/sirsoft-page/src/Repositories/Contracts/PageRepositoryInterface.php`. Native service source is `modules/_bundled/sirsoft-page/src/Services/PageService.php` (`Modules\Sirsoft\Page\Services\PageService`). Actor repository is `App\Contracts\Repositories\UserRepositoryInterface`, source `app/Contracts/Repositories/UserRepositoryInterface.php`.

## Two curated slots and scope limits

Registry lives in travel module `config/campaigns.php`, with immutable keys/slug/enum filters/order/art variant. Titles, body, publication and version come from native Page rows. Registry values are RAON lab assumptions; they contain no price, inventory, member IDs or customer data.

| Kind / slot | Exact native Page slug | Customer path | Catalog query |
|---|---|---|---|
| Campaign / autumn escape | `travel-lab-campaign-autumn-escape` | `/travel/campaigns/travel-lab-campaign-autumn-escape` | `theme=nature&sort=recommended` |
| Campaign / weekend reset | `travel-lab-campaign-weekend-reset` | `/travel/campaigns/travel-lab-campaign-weekend-reset` | `theme=wellness&sort=recommended` |

Nature and wellness are actual `Theme` enum values; culture/city remain the other existing catalog themes. Registry size is bounded2; no client-supplied slug prefix, search, wildcard or array of arbitrary IDs is accepted. New arbitrary campaign slots beyond these two require a later registry change; admin content/publication edits for these slots work immediately through native Page UI. This is an explicit first-cut limitation, not an implication that all Page administration was replaced. Four separate theme-guide Pages are deferred, not new completion conditions.

`/travel/campaigns` shows the published slots. Home uses real published campaign cards; existing theme chips/cards continue their real catalog filter navigation. Draft/missing slots produce no campaign card. Do not keep the old static campaign copy as a successful API fallback.

Add `/page/:slug` as a travel template alias to the same **registry-restricted** consumer. Native sitemap contributors generate `/page/{slug}`; this alias prevents the two new sitemap URLs pointing at an absent template screen. It must404 for unregistered slugs and drafts. It does not provide a generic view of unrelated customer/company Pages.

## Public API and UI contract

Base `/api/modules/raonslab-travel_lab`:

| API | Response/behavior |
|---|---|
| `GET /campaigns` | ResponseHelper envelope with `data.items` in registry order; published slots only. Each item: `slug`, `kind=campaign`, `theme=nature|wellness`, localized `title`, safe plain-text `excerpt`, `current_version`, `published_at`, `path`, fixed `catalog_query`, own `art_variant`. No pagination is necessary for two slots. |
| `GET /campaigns/{slug}` | Known published slot only; same fields plus localized full `content`, `content_mode` and `updated_at`.404 for missing, unpublished or unregistered slug, identically for guest/member/admin. No preview, creator/updater, versions, attachments or signed admin URLs in this projection. |

All API responses use BaseApiResource/ResponseHelper and server locale/fallback rules matching the native public resource. Slots must resolve via PageService, not a mock JSON payload; independently require published before serializing. Do not use native admin PageResource or serialize creator UUIDs/attachments/signed previews. Request path validation restricts slug syntax; registry membership remains mandatory even if a slug matches the prefix. Use named routes and the existing travel-only native atomic middleware with a distinct numeric600 public prefix such as `travel-lab-campaign-public:`. Preserve current optional native bearer decisions with TravelOptionalSanctum. This is a separate scope; do not modify existing inquiry/cart/support prefixes.

Customer detail fetches the existing real catalog API using only server-returned allowlisted registry filters, then renders product cards and normal product/detail/cart flow. No price/discount is derived from Page text. CTA goes to internal `/travel/search` with those filters. A campaign may have zero matching products: show Page body plus a real empty-result state and a clear “all trips” link. Loading, API error/retry,404 and empty states are distinct.390/1440 layout and keyboard actions are required.

Home's original static seasonal banner is replaced by real slot content and a detail link; Page unpublish must remove it after normal requery/reload. API failure must show an honest retry/unavailable state, not hardcoded editorial success.

### Safe body rendering, fixed first-cut policy

Create a travel-owned `PageBody` composite following the existing native `sirsoft-basic` HtmlContent pattern and G7 wrappers; do not depend on another template being active.

- `content_mode=text`: render the string as escaped React children with line breaks preserved. Provisioned synthetic content uses this mode.
- `content_mode=html`: use DOMPurify as the native renderer does, with a **strict formatting-only allowlist**: paragraphs, line breaks, headings, strong/emphasis, lists/list items, blockquote and code/pre. Allow no attributes. Links retain text but no clickable URL; images/media/iframes/form/SVG/style/script are removed. No unsafe HTML interpolation or `purifyConfig` from API/layout/editor can widen this policy.
- There is no remote media/body-link fetching from campaign content. Own ScenicArt outside the body supplies visuals. Existing native Page attachments are not part of this travel consumer; signed/draft attachment URLs are never projected.
- Register the component/export/editor sample in the travel template, with local dependency+lock update for DOMPurify using the repository's native dependency baseline. Keep hook order stable across empty→text→HTML and mode changes. Focused tests must cover script/events/javascript URLs, remote image/style URL attempts, long text, line breaks and changing content mode without runtime errors.

Formatting-only HTML is an explicit lab constraint; external links, images and downloadable attachments are not silently promised. Native Page editor still persists all normal Page data, while this consumer renders only the above supported presentation.

## Native admin management adapter

Travel-owned route `/admin/travel-lab/campaigns`, `_admin_base`, native `sirsoft-page.pages.read` permission. Use actual `GET /api/modules/sirsoft-page/admin/pages?filters[0][field]=slug&filters[0][operator]=like&filters[0][value]=travel-lab-campaign-&per_page=20` (URL-encode nested keys normally). `PageListRequest` permits these fields; actual `PageRepository::normalizeSearchFilters` consumes first field/value and applies slug `LIKE %value%`, ignoring operator. Although the Request also accepts `starts_with`, it does not enforce prefix matching; this adapter must not claim otherwise. Map response rows back to the exact two registry slugs in the UI; do not display nonregistry rows. Native list has `data.data` plus native PageCollection pagination; inspect actual response instead of assuming the travel catalog envelope. Respect each resource's `abilities` and native scoped API result. Follow native pagination if the substring search returns more rows; absence from one page must not be reported as a confirmed missing slot.

Display actual title/slug/published/current_version and create/edit/version controls:

- Create `/admin/pages/create?slug=<exact-slot-slug>` is supported: native form initializes `form.slug` from `query.slug`. Native form defaults to HTML; its real editor HTML-mode checkbox switches `form.content_mode` to text. Show a concise instruction beside create links to use text for the synthetic first content. Do not claim the query preselects content_mode; it does not.
- Edit `/admin/pages/{numeric id}/edit` and details `/admin/pages/{numeric id}` use native UI. Detail provides actual version/restore/publish flows. Numeric ID comes from native admin API, never a slot index or inferred ID.
- Optionally embed native publish API action `PATCH /api/modules/sirsoft-page/admin/pages/{id}/publish` `{published:boolean}` in the adapter, only when abilities.can_update; native403/422/error handling remains visible. Never change publication using direct Page model/repository writes.
- Guest401, authenticated actor lacking Page permissions403, scoped actor cannot read/edit a foreign Page through the adapter/native endpoint. Reuse native page permissions (`read/create/update/delete`, PermissionTypeAdmin, owner created_by), not travel catalog.update as a substitute. Roles alone are not authorization.

No new admin Page-writing API is required. This adapter plus actual native editor is a concrete management flow, not a documentation link standing in for tested administration. Independent browser must exercise create, edit, unpublish/re-publish and version restore through real controls.

## Explicit-only synthetic provisioning

**Yes: explicit-only provision command is appropriate; default install/update seed must create zero Pages.** Proposed command `raonslab-travel_lab:campaigns-provision --lab-confirm --actor=<id>`; no scheduler or request-time invocation.

- Require marker `TRAVEL_LAB_ISOLATED=1`, campaign-specific provisioning flag (defaultfalse) and explicit `--lab-confirm`; reuse the marked package runner to bind local APP/TEST schema/account and effective mailarray/queuesync/local storage/mysql-fulltext. Lead adds the public example flag and runs this command only after installed dependencies are ready and browser freeze ends.
- Require an explicit existing permitted Page admin actor via injected `App\Contracts\Repositories\UserRepositoryInterface::findById(int)`. No automatic super-admin selection. Require native Page read/create permissions; authorize before any write. PageService create uses Auth::id and its version snapshot expects a real actor.
- Set the native Auth actor only for the provisioning scope and restore previous guard/actor state in finally. No fake HTTP login or persistent token created by this CLI.
- Check two known slugs through injected PageRepositoryInterface. **Skip every existing row unchanged**, including drafts, operator edits, version numbers, SEO and content. No upsert, forced republish or reset. A conflict/race must not replace the existing Page; native uniqueness error should be explicit.
- Create missing synthetic ko/en title/content Pages through `PageService::createPage`, content_modetext, publishedtrue for this explicitly confirmed demo, no temp_key/attachments/external assets. Return only created/skipped counts and IDs/slugs, not contacts/credentials/body.
- Re-run must preserve all existing IDs/content/version/publication. Native API-created human changes win. No default module seed modification or new migration/table is required.

## Side effects, draft privacy and cache contract

Native Page writes do have legitimate local effects: version snapshots, activity audit, SEO cache/index changes and sitemap generation job. Do not assert “no jobs/no settings changes” just because no mail/commerce action occurs. Guarded lab retains sync queue, local files/database cache, array mail and mysql-fulltext; sitemap/broadcast behavior must stay local/disabled under those adapters. No HTTP fetch, mail/SMS, payment, real reservation or external search-engine call is added.

Travel public projection does not expose drafts to any actor, but native admin preview continues its own intended Page read policy. Customer Page content must contain only synthetic public editorial copy, never inquiry/member/contact data. Test draft exclusion through travel API/home/detail and core search; external Scout/import is NOT_RUN unless separately verified, and the lab remains mysql-fulltext.

Native SEO invalidation targets `page/show`, `home` and `/page/{slug}`; it does not automatically cover new travel layout names/paths. Add a travel-owned Page hook listener for after_create/update/publish/restore/delete, guarded to the two registry slugs, using native SeoCacheManagerInterface to invalidate travel home/list/detail and alias paths/layouts. No new business cache or global binding. Initially use no travel response cache and keep G7_STATIC_CACHEfalse; do not claim draft privacy for stale cached HTML until invalidation is verified.

## Ownership for one official implementation child

Single child owns this complete feature so shared registrations are not concurrently edited. Parent owns `scripts/travel-lab/extensions.php`, the lab environment example/actor/runtime setup and all lifecycle/install commands, source integration, Git delivery, previews and independent verification. Implementation child must not perform APP/TEST full installation or modify environment files. No child modifies another Request's worktree. Lead normally owns shared `src/routes/api.php`; this single campaign child receives that specific file as an explicit temporary ownership exception for campaign registration.

| Child-owned files | Responsibility |
|---|---|
| Travel module `config/campaigns.php`; new Services/Resources/Controller/routes/campaigns.php; provisioner/command/cache listener; focused Page integration tests/docs | Registry projection, native Page service/provisioning, strict publication, API contracts/cache/privacy. |
| Travel module existing `src/Providers/TravelLabServiceProvider.php`, `src/routes/api.php`, `module.php`, resources/routes/admin layout/locales/editor spec | Config/command/listener/route registration and native Page admin adapter. These are explicitly assigned shared files, not uncontrolled cross-owner edits. |
| Travel template home, new campaign/list/detail layouts, routes/base navigation/locales, PageBody/component manifest/exports/editor spec, focused tests | Real Page-backed UX and safe formatting. Keep existing theme catalog UX and cart/key/auth/request flow unchanged. |
| Travel module/template version/package/composer/locks/CHANGELOG and template travel dependency | New travel public API requires version sync, proposed0.1.3 after lead confirms existing head. Existing Page>=1.1.2 dependency is already present and its API is unchanged, so no Page version bump. No core compatibility change. |

No sirsoft-page/core/commerce/board source ownership, no DB direct writes, no backend booking/payment/state redesign. If a native defect blocks actual reuse, return the concrete trigger to lead before touching those unassigned sources.

## Completion and independent verification

- Fixed source/build: focused PHP tests with native Page provider/repositories/migrations and real version/audit hooks; no mock PageService; canonical doc/work-order checks; type-check, focused UI tests and production no-sourcemap build. Separate author vs independent results.
- Real native admin390/1440: create exact slot slug (prefill verified), save synthetic body, publish, public home/list/detail shows same persisted version/title/body; edit→new version; unpublish→all travel entries absent/404; restore→new version and changed public body; re-publish. Permission/scope and missing/error/retry states verified.
- Campaign detail→existing filtered catalog→product→cart/inquiry remains functional, including an empty campaign. No campaign text price is submitted. Existing four-theme catalog/search behavior is regression coverage, not new theme-guide implementation.
- Explicit provisioning twice and after operator edit: identical IDs/versions/publication/content for skipped rows; flag/confirm/actor failure writes zero Pages; no Pages in default installation seed.
- Content attack tests and actual browser remote-request observation; draft/privacy/cache transition checks. Existing Page API/attachment regression stays separate; no nonexistent attachment404 counted as private-file protection PASS.
- Preserve fa552 browser/security/installer evidence at its original target. New feature needs its own fixed candidate and post-integration browser/permission/core regression; no old evidence relabeling.

## Inspected native source pins (SHA-256)

| Input | SHA-256 |
|---|---|
| `modules/_bundled/sirsoft-page/AGENTS.md` | `0fca2282350952df82c23e2f02d4803dd92c72ac6f47a55a4aae2b1aa08193c7` |
| Native `src/Services/PageService.php` | `6f9c57ccd7fc08aae4a8972c2c0ba545806ae4aa58dc6f0be3b8f1991803a287` |
| Native `src/Repositories/Contracts/PageRepositoryInterface.php` | `ac6dd77487c29785cf5e86e6c1542003836ea59fafc69aec50764fe7346439a9` |
| Native `src/Http/Requests/PageListRequest.php` | `0df8fe0c9ca346901ee1dda66be7b1efdd4ed25e778a6d6cfb700966ad0c6c5b` |
| Native `src/Repositories/PageRepository.php` | `b8b8fe1e241b1ee7c0ac9e255bbac3da2ce7d6fd9141158d9bf032161bfe363a` |
| Native `src/Http/Controllers/User/PublicPageController.php` | `522b426316ea08c75459061d9fbd35f20d69a9cbfb1f90ec3b825a5a26869e27` |
| Native `src/routes/api.php` | `28ae4d255837ba43c2d7b8a24873786ff6746421e5d58637b112be9519507519` |
| Native `src/Listeners/SeoPageCacheListener.php` | `374a3c0b8f0e73ad80a97399f95fb179d26185e10a41e6dafdf82dd27fb72ef9` |
| Travel `module.json` | `d29edfe589684e670d39da6603aa24948ad892a4a1779c5c5bf4e733b060ecdf` |
| Travel template `layouts/travel/home.json` | `0da103d883af66f3139883e1ebb19632292613cfb6efba7e9401791d72751d54` |

All proposed feature execution is NOT_RUN by this design author. Lead may now assign implementation using this v1 contract and explicit ownership; this document is evidence, not a separate scheduler or completion claim.


## Lead clarifications after bounded v1 review

The original reviewed body is SHA-256
`ef79adefea1e5a950b4809d079eb2a2cdf0cd89f43bb8d8d2d309156b0661b0d`;
W04_CAMPAIGN_CONTRACT_REVIEW.md binds that exact input. These clarifications
retain the two fixed slots and assigned implementation boundaries.

- Native Page administrators may rename/delete a Page slug. A changed slug
  detaches that document from its fixed campaign slot: published navigation
  disappears and the old travel detail returns404. No request-time/default seed
  recreates or overwrites it; a later explicit confirmed provision may create
  the missing registry slug. No extra global Page slug guard is introduced.
- Even after paging the permission-scoped native admin list, absence means
  unavailable in this account's scope, not proof that no Page exists globally.
  Create controls must preserve native403/422 uniqueness/scope errors rather
  than imply a hidden foreign Page can be overwritten.
- Strict formatting-only DOMPurify must set `ALLOW_DATA_ATTR=false` and
  `ALLOW_ARIA_ATTR=false` in addition to `ALLOWED_ATTR=[]`; API/layout props
  cannot widen the renderer. Tests include data/ARIA attributes as well as
  event/URL/media attributes.
