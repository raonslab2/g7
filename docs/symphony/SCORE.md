# G7 Travel Lab score — contract v2 (integration)

## Current execution overlay — 2026-10-09 18:35 UTC

Earlier tables are historical contracts and wave observations; their original
RETURNED/pending statuses do not override this overlay. Runtime product target
`fa5523175ac494cfbd13bbf89bf06b3ec91835a6`, PR2. Current product delivery is
IN_PROGRESS, with initial installer/cache and standalone MySQL fixture repairs
awaiting a separately published independent execution. Native Page-backed
campaign/publication consumption is a newly identified implementation gap;
installation alone was not counted as reuse. Its concrete contract is being
prepared while native creation/browser verification continues.

- Canonical attempts24:18 COMPLETED,4 FAILED,1 INTERRUPTED,1 RUNNING.
- Native admin creation/browser: `w04-native-admin-create-final` attempt1,
  `req_c2e43e4188284ec78b8702e7e3e0d45a`, RUNNING; no duplicate Request.
- Atomic `w04-atomic-contention-final` attempt1 is canonically FAILED.
  Evidence commit `c38ee592` was accepted only within independently compared
  bounds; terminal state/cause remain distinct. [Intake](W04_ATOMIC_FINAL_INTAKE.md).
- Fresh installer attempt3 COMPLETED, product CHANGES_REQUIRED. Original
  `94c3f75b`/`0eb9e68f` and [intake](W04_INSTALL_FINAL_INTAKE.md) preserve failures.
- [Installer source review](W04_INSTALL_BOOTSTRAP_REVIEW.md) and
  [worker diagnostic](W04_ATOMIC_WORKER_DIAGNOSTIC.md) bind repair source/tests;
  source review alone is not live MySQL PASS.
- [Delivery audit](FINAL_REPORT.md) accumulates exact SHA, requirements,
  commands, limits, package, capacity and next execution. Formal Validation and
  hosted CI remain NOT_RUN. Customer-approved design/database remains NONE.


Work: `work-20261009-g7-symphony-max-child-c7ae42d1`; parent Request: `req_81ac33cac94046b9a2249cd14c0d00ba`.
Input G7 SHA: `6853f40d58acbf53a2f29cbb9dd422cc439047a9`; repository: https://github.com/raonslab2/g7 (public).
Integration branch: `feat/g7-travel-lab-c7ae42d1`. Current implementation contracts are RAON lab assumptions, **not customer-approved designs or 110-screen delivery**.

## Schedule and boundaries

Start observation: 2026-10-09 08:33 UTC / 17:33 KST. Target: 2026-10-11 14:59 UTC / 23:59 KST, about 54 hours 26 minutes remaining at intake; review October 12 KST. W00 intake and first implementation run together; W01 October 9 catalog/UI checkpoint; W02 October 10 complete transaction/support; W03 October 11 first half independent fixed-SHA browser/security/regression and repair; W04 second half integration/retest/reproducible package. Ready results are tested immediately rather than waiting for the calendar wave.

Keep Spring `work-20261009-spring-symphony-max-child-91bf5a6c`, Request `req_8b1601dbd8a34a83b136a8204125d8bf`, independent. No changes to its source/state. Share only approved Git workflow/status/scenario artifacts. Existing RAON product, membership, consultations, Pages and production DB/services are outside scope. Travel activation/seeding occurs only in an isolated lab.

Public source observations and inaccessible internals: [REFERENCE.md](REFERENCE.md). Original light RAON identity and original repo-native scenic SVG assets; synthetic product/date/price/member/help data only. No live checkout/order/payment/refund/supplier/mail/SMS calls. Transaction surfaces explicitly label test requests; `TEST_ACCEPTED` never means a real booking confirmation.

## Ownership and executable work

| Work / screens | Owner and input | File ownership | Completion and tests | Result |
| --- | --- | --- | --- | --- |
| TR-CATALOG; HOME/LIST/CATEGORY/CAMPAIGN/DETAIL | CODEX official `w01-domain`, attempt 1, input SHA above | new travel module manifest/bootstrap/provider/models/enums/migrations/seed/repositories/catalog service/controllers/requests/resources, `src/routes/catalog.php`, domain tests | Actual commerce products/options/catalog; filters, publication, valid dates, admin departures; migration down/rerun and synthetic seed replay | RETURNED; unverified author result |
| TR-TRANSACTION; CART/REQUEST/MINE/ADMIN | CODEX official `w02-workflow`, attempt 1, input SHA above | travel cart/inquiry services, scoped repositories, controllers/requests/resources, `src/routes/workflow.php`, workflow tests | Real commerce cart + calculation; durable idempotent test inquiry, capacity locking, owner/admin gates, explicit transitions | RETURNED; unverified author result |
| TR-UI; HOME through MINE/HELP | CLAUDE official `w01-ui`, attempt 1, input SHA above | dedicated `templates/_bundled/raonslab-travel_lab/**`, UI_DESIGN.md | Live API customer screens; 390/1440, loading/empty/error/retry, original assets; build/typecheck | RETURNED; unverified author result |
| TR-SUPPORT; HELP/INQUIRY/ADMIN | CLAUDE official `w02-support-admin`, attempt 1, input SHA above | support services/provisioner/controllers/requests/routes/listeners; travel module admin layouts/routes; support tests | Native isolated G7 notices/FAQ/private inquiry; owner/admin and notification suppression; admin live transitions/catalog | RETURNED; unverified author result |
| TR-RUNTIME | parent native `commerce_contract` followup | `scripts/travel-lab/**`, `deploy/travel-lab/**`, `.env.travel-lab.example`, RUNTIME.md | Distinct lab/test DB, supported installer/build/seed, restart and package instructions | IN_PROGRESS |
| TR-EVIDENCE | parent native `inheritance_capacity` followup | INHERITANCE.md, WORK_ORDER_VALIDATION.md | Canonical order validation exact tree, lineage and known retrieval limits | IN_PROGRESS |
| TR-INTEGRATION | CODEX parent | shared `src/routes/api.php`, merged provider bindings/contracts, separately named integration migration for full calculation snapshot and inquiry actor events; SCORE/CAPACITY/WAVE_STATUS/final evidence | Integrate scoped commits, meaningful Git checkpoint, independent review gates, integration retest and deliverable | PENDING implementations |
| TR-INDEPENDENT | subsequent official reviewer/browser Requests | fixed commit SHA + criterion/evidence binding; review reports/tests, no self-approval | Security/contract + actual 390/1440 browser/regression, real DB concurrency/restart | NOT_RUN until fixed implementation SHA |

Children own only assigned Request worktrees. They return committed scoped results and do not publish/merge/deploy independently. Lead integrates Git once at each meaningful checkpoint. Native roles are inside one Request, not additional official Requests or PCs.

## Shared module and database contract

Identifier: `raonslab-travel_lab`, namespace `Modules\Raonslab\TravelLab`; first extension version 0.1.0; core >=7.0.11, ecommerce >=1.2.1, board/page >=1.1.2. Existing consumer APIs are unchanged; adding a new extension alone does not raise existing consumer version constraints. New dependencies declare the minimum versions actually consumed.

| Model / table | Fields and relationships |
| --- | --- |
| TravelProduct / travel_lab_products | id; product_id unique FK ecommerce_products; region; theme; duration_days; summary ko/en JSON; itinerary JSON; published; timestamps. Relations product, departures via product_id. |
| Departure / travel_lab_departures | id; product_id; product_option_id unique FK ecommerce_product_options; departure_date; return_date; capacity; reserved default 0; is_active; timestamps. Relations product and option. Raw simulated capacity = capacity-reserved; customer effective availability also applies current option stock as defined below. |
| Inquiry / travel_lab_inquiries | id; user_id; idempotency_key; payload_hash char64; status; total_amount decimal(20,2); currency_code; contact JSON nullable; admin_note nullable; timestamps. Unique(user_id,idempotency_key). Relations items/user. |
| InquiryItem / travel_lab_inquiry_items | id; inquiry_id; departure_id; product_id; product_option_id; quantity; unit_price and line_total decimal(20,2); product_name JSON; departure_date snapshot; timestamps. Relations departure/inquiry. |
| Parent integration addition | Inquiry.calculation_snapshot JSON records the complete server calculation result, required for new submissions. InquiryEvent / travel_lab_inquiry_events: id,inquiry_id,actor_id FK logical users (physical g7_users),from_status nullable,to_status,note nullable,created_at; append only in the same inquiry transaction. Parent owns a separate migration/model/repository integration and corresponding workflow write tests; child base migrations are not edited concurrently. |

Ecommerce Product is the product identity; each departure maps to one real ProductOption. Traveler quantity uses real Cart.quantity. CartService owns cart mutations; OrderCalculationService/CartService calculation owns amounts. Client money is never trusted. This is a separate TEST_INQUIRY workflow, not an ecommerce shipping/order status rename.

Critical evidence from actual commerce code: a null shipping policy falls back to the shop default, so travel products need an explicit isolated FREE ShippingPolicy with external/API fees disabled. ProductService option updates replace the collection: preserve existing option IDs and deny removal of options referenced by inquiry history. Calculation can skip missing options: verify every selected row and returned calculation count before creating an inquiry. Keep complete calculation snapshot and actor transition audit as integration obligations if child implementation does not already supply them.

Inquiry statuses: TEST_INQUIRY → UNDER_REVIEW / DECLINED / CANCELLED; UNDER_REVIEW → TEST_ACCEPTED / DECLINED / CANCELLED; TEST_ACCEPTED → CANCELLED. The owner may cancel any noncancelled/nondeclined request, including an accepted **test** request. DECLINED and CANCELLED are terminal; replaying cancellation must not release twice. Decline/cancel releases simulated allocation exactly once. Stable same-key same-payload retries return the same inquiry; different payload returns 409. Lock owned selected cart rows and departure rows in deterministic order, validate active/published/future/quantity/available, calculate server amounts, create snapshot/reserve/remove cart inside one transaction. Unique-key races must not oversell or reserve twice.

Inventory integration rule: Departure owns simulated reserved allocation, ecommerce ProductOption owns sellable stock/active/pricing. Seed travel products with stock management enabled and option stock at least departure.capacity; never decrement/rewrite commerce stock merely to disguise test inquiry as an order. For catalog and submit, effective availability is `max(0,min(departure.capacity,option.stock_quantity)-departure.reserved)` for these stock-managed travel products. Core option stock edits can reduce the ceiling: submit must lock/recheck both domains, not trust a stale catalog/cart. Travel admin capacity reduction cannot fall below reserved allocation; raising capacity requires sufficient current option stock. Removing a cart or cancelling/declining an inquiry changes no commerce stock; release only travel reserved once. Option deletion/history protection and current stock recheck remain required even when edits come through existing commerce administration.

## API and UI contract

Automatic base `/api/modules/raonslab-travel_lab`; named routes, standard ResponseHelper envelope, BaseApiResource/Collection, FormRequests; G7 Sanctum Bearer authentication. Services inject repository interfaces. Public catalog list is `data.data` plus pagination, not a guessed flat array. Lead reconciles final child DTO serialization before browser tests.

| API | Input / behavior | Access |
| --- | --- | --- |
| GET /catalog, /catalog/{product}, /catalog/{product}/departures, /facets | q,region,theme,date_from,date_to,min_price,max_price,sort recommended/price_asc/price_desc/departure_asc,page,per_page<=48 | public published only |
| GET/POST /cart | POST departure_id,quantity; GET travel-only items + authoritative totals/currency | authenticated owner |
| PATCH/DELETE /cart/{cart} | quantity on PATCH; server owner/option/departure checks | authenticated owner |
| POST /inquiries | cart_ids[],contact{name,phone?},idempotency_key; immutable server-priced snapshot | authenticated owner |
| GET /inquiries, /inquiries/{id}; POST /inquiries/{id}/cancel | own durable requests/status | authenticated owner; foreign IDs 404 |
| GET /admin/inquiries, /admin/inquiries/{id}; PATCH /admin/inquiries/{id} | explicit status,admin_note; permitted transitions only | travel inquiry read/update permissions |
| GET /admin/catalog; PATCH /admin/catalog/{product}; GET/POST /admin/catalog/{product}/departures; PUT /admin/catalog/{product}/departures/{departure} | travel metadata/capacity; commerce admin owns product/options/prices | travel catalog read/update permissions |
| GET /support/notices,/support/faqs | authored dedicated native G7 Board records | public published content |
| GET/POST /support/questions; GET /support/questions/{id} | persisted private question; native board contracts; no external notifications | authenticated owner/admin |

Catalog DTO: id=product_id,title,region,theme,duration_days,summary,itinerary,from_price,currency_code,image_url,departures[{id,product_id,product_option_id,departure_date,return_date,available,unit_price,currency_code}].
Visitor paths: /travel,/travel/search,/travel/products/:id,/travel/cart,/travel/requests,/travel/requests/:id,/travel/help. Preserve G7 toast/modal/login hosts and existing AuthManager. Submission retry preserves key/payload; successful/new cart creates a new key. All actions use documented G7 dispatch handlers. Admin path /admin/travel-lab plus dedicated catalog/inquiry layouts and native ecommerce/board administration links.

## Verification and publication gates

Run canonical work-order validator in its owning ai_gcs_v2 exact tree, not a homemade G7 substitute. New-function scenario matrix, focused PHP/API/DB tests, actual commerce/board regressions, Installation smoke, frontend build/typecheck, 390/1440 real browser. Required tamper/idempotency/concurrency/ownership/state/restart/seed rerun/rollback checks are independently recorded PASS/FAIL/NOT_RUN/BLOCKED against a fixed SHA. Internal review is not official Validation. No CI workflow exists at intake; unavailable required checks are not waived.

G7 repo-owned workflows/hooks are absent at intake, main unprotected; unmanaged external automatic deployment remains UNKNOWN. Preserve an inspectable integration branch if main merge could mutate production. Never restart production to prove this lab. Deliver Git-addressable install/run package and sanitized UI evidence, not only a local path.

## W01 integration contract v2 — 2026-10-09

Four source commits are locally reachable and scoped paths reviewed (same baseline): domain `a1960f108d5384d6d7f494441099f65b81735b33`, workflow `6aaf80af9ad73fa44f642535b2de24b0d9b86a6c`, support/admin `2c0e38765acccac000923711e9e271dad483cc49`, visitor UI `48445bb122015d1b2c501fa4f953809a2622cd19`. Lead cherry-picks are `5b33a5db`, `bd4eff52`, `839da366`, `2114703d`; unpushed until a reviewed integration checkpoint. Canonical child receipts still report GIT_EVIDENCE_REVIEW_REQUIRED, not official Validation PASS.

Independent preflight reviewer `w01-contract-preflight` / `req_e35bb0ef069a4e15bcb44ac8831d092e`, source report `751b3a083ff6d904c927543a38cedc9baf102f97`, reviewed workflow/source6aaf +support/source2c before integration and required changes. `W01_CONTRACT_REVIEW.md` preserves that report; it does not review the later merged code. Correcting oversell, paid/missing shipping, ordinary order/payment escape, missing calculation/actor history, enum/DTO/layout mismatches. Same-status changed note must persist/audit; exact replay is no-op. Inquiry submit retry returns200 for existing and201 for new.

All inquiry status values are uppercase enum backing values. `InquiryStatus.allowedNext()` and `canCancel()` define transitions for service and resources. Inquiries support validated `status` filter; result pagination is `data.pagination`, admin collection `abilities` is computed from real permissions. Admin catalog links use actual product_code; departure ID is distinct from commerce option ID. Cart/inquiry contacts are normalized before idempotency hash. Shipping country is pinned KR; active explicit nondefault FREE policy with KR FREE row is mandatory, zero shipping/discount/points expected. No caller price is accepted. Synchronous travel checkout guard must reject native temp-order/create/update/order/payment hooks even outside the Travel API.

Current parent-native ownership: commerce_contract→workflow/services/resources/controllers/tests/guard; reference_runtime→visitor template/admin layouts/frontend tests; inheritance_capacity→runtime/recovery/live harness. Lead→module/provider/contracts/models/enums/migrations/catalog/free-policy seeding/publication/evidence. Four active native slots include lead, not four extra Requests or PCs. Tests against generated fixtures remain author-level only; real merged models and MySQL are required next.


## W03 verification score binding

Implementation input/test target 28ada286c1c34606741bcfe4f9d12e06ac50af30, PR2, contractv2. Three independent
Requests and disjoint ownership are listed in WAVE_STATUS.md. Browser/security operate
different synthetic users/fixtures in the marked APPschema; runtime owns TESTschema.
Only lead may update shared module/template/runtime/service/Git Delivery. Nonauthor
reviewer returns committed findings/tests/evidence for lead comparison, never its own
implementation finalapproval. Publication of source is established; validation/integration
completion remains pending. Documentation-only handoff updates do not change this tested
source target or grant main/production approval.


## W03 contract v3 — catalogue and recovery repairs

Input fixed source: 28ada286c1c34606741bcfe4f9d12e06ac50af30 (tree a821bd89); local repair base fe3b2f23 after original independent evidence intake. Customer-approved design/database: NONE. RAON lab assumption: domestic business-day Asia/Seoul, separate module config; no global timezone change.

| Work/screen ID | Own decision and shared contract | Owner/files | Dependency and completion gate | Result |
| --- | --- | --- | --- | --- |
| TL.CAT.03 / admin catalogue registration/editor | Select real unmapped native product, retain native option IDs/prices; create translated travel metadata, unpublished default; immutable product ID; itinerary JSON editor | lead CatalogRepository/Service/Requests/Resources/controllers/catalog.php; UI native owns admin JSON and labels | native product exists; 201 registration, 409 duplicate, 422 tamper, native departure write + real390/1440 admin journey | working-tree repair; independent catalogue review pending |
| TL.SEARCH.02 / search and detail choices | Native KeywordSearch on decoded ko/en JSON title/description; future sold-out dates shown available0 in detail, discovery requires available date; from_price excludes sold-out | lead catalogue source, UI existing disabled departure control | actual MariaDB Korean query and PC/mobile zero/nonzero/filter/sold-out/pagination checks | old P1 reproduced before repair; new fixed-SHA recheck pending |
| TL.INTAKE.03 / cart recovery | Exact prepared body/key captured through native sequence $prev to explicit local setState; preserve consumed-cart uncertain request across reload, owner UUID guard; acknowledged edits form new intent | UI native template handler/cart/modal/tests | real201 response loss ->retry200 same ID/body; reload-empty recovery; edited-contact before upstream producesnew201 | 126 intermediate implementer tests; final128/build pending |
| TL.COMMERCE.04 / native delete/options | Synchronous before_delete and prewrite final option filter guards; module response adapter renders truthful409; linked removal422; ordinarycommerce unchanged | commerce native own Listener/Middleware/repository/workflow tests; lead module/provider registration | actual installed hook discovery, filesystem remains intact, legitimate edit/add and ordinaryproduct regression | 17/258 implementer tests; installed fixed-source recheck pending |
| TL.DATE.02 / departure/cart/submit | Asia/Seoul same-day exclusion, strictfuture validation, user-specific workflowwrite buckets preserve normal idempotentretry | commerce native TravelDate/DepartureRequest/cart/routes; lead catalogue config/search | timezone boundary00:00–08:59KST and isolated per-user throttle checks | implementer SQLite PASS; live independent pending |
| TL.RECOVERY.03 / recovery package | Fallback import must purge/reconnect and match original selected-table digests before private snapshot deletion; failure retains0700/0600 snapshot | recovery native live-recovery.php/fault-control tests/README | realTEST rollback/replay/restore plus injected failure and retained snapshot check; noAPPchanges | purecontrol4PASS; MySQL independent pending |
| TL.SUPPORT.03 / private questions | Admin-only unrestricted private moderation, native PostService edit/audit, safety permissions verified, prove actual Scout exclusion | officialCLAUDE w03-support-hardening; lead permission declarations | dedicated MySQL tests and nativeboard regression, new fixed-source security review | RUNNING; TEST schema exclusive |

Original W03 negative reports remain failure evidence. New fixed-source review attempts, then integration/postintegration verification, establish current status. Internal nonauthor review and official independent Request execution remain distinct from a canonical Validation receipt, which is currently absent.


Contractv3 repair evidence update: actual native edit route is numeric `/admin/ecommerce/products/{id}/edit`; the earlier v2 product_code link statement was superseded. Candidate page bound48 matches CatalogCandidatesRequest max48. Optional itinerary defaults[]; all prices/options retain native identity. Nonauthor catalogue review25/366 and UI handler/diff review passed after fail-first fixes, bounded working-tree only. Inquiry recovery now treats in-memory state (including cleared tombstone) as authoritative after storage write/removal failure; latest live browser8scenarios/10cleanup checks passed on working tree, not independent fixed SHA. Support scope requires owner membership for every required native and travel grant, with exact native audit/write paths and mysql-fulltext-only privacy contract. New editor-spec covers4actual data_sources with6groups/24synthetic editor variants; nativecollector/type/resolver checks declared PASS, actual admineditorUI remains independent NOT_RUN. Customer-approved design/DB remainsNONE.


## W04 repair contract v4 — October9 13:40UTC

Input independent target7de0c444/tree3c285467; security dd43331a and fresh install cdd2def9 original commits compared and locally cherry-picked as f6eb5176/ae9d823c. Original negative decisions remain unchanged; browser attempt1 INTERRUPTED with no accepted Git result. The next target must include reviewed fixes, not reuse the original failed product SHA.

| Work/screen | Owner paths / resulting contract | Acceptance / next independent target |
|---|---|---|
| TR-HELP create/edit | Lead `src/routes/support.php`: distinct public600, questions120 and create10 prefixes; native UI owner-only PATCH with native service authorization/audit | Read requests cannot consume create budget; 11th creation returns429; foreign owner returns404; editor error retains input |
| TR-CART uncertain retry/account switch | UI `inquiryKey.ts`, `_user_base.json`: live native SDK UUID wins over stale render context, successful desktop/mobile logout clears pending intent | Same-owner committed-response loss retains key; another account sees no old request/contact |
| TR-ADMIN catalog pagination | UI admin catalog blur explicitly depends only on fetched catalog | Actual pointer clicks on Next and numeric product edit work at390/1440 |
| PKG-INSTALL | Native core ModuleManager owner; package scripts owner; lead version/manifests | Existing native resolver honors bundled mode before module entry/DDL, actual dependency autoload required; missing/corrupt bundle aborts; no forced-update/copy fresh-PASS |

Core candidate7.0.12 is recorded in config/app.php, .env.example and rootCHANGELOG. Scan24 bundled manifests: affected ecommerce/travel module/travel template require>=7.0.12. The ecommerce external-dependency install is the affected path; other modules have no external Composer packages. Ecommerce Service/Model/Route signatures and native vendor composer.json/lock/archive are unchanged, so seven ecommerce consumers (travel module/template, four payment plugins, sirsoft-basic) do not need a new ecommerce dependency version. PluginManager install path is unchanged. New Travel APIs remain unreleased0.1.1 with explicit changelog entries. No RAON business module or production DB changes.

## W04 closure contracts — October9 15 UTC

| Work/screen | Contract and owner | Evidence / completion condition |
| --- | --- | --- |
| TR-REQUESTS / MOBILE-01 | Template request card is a vertical native Button; grid/card min-width0, ID/status/title/amount wrap without clipping. UI native owner, lead integrates. | Independent598 browser424px FAIL preserved; same-owner author425→390 and1440→1440, keyboard/pointer/date DOM PASS. New fixed-SHA independent browser must close the defect. |
| TR-HELP / W04-S01 | Module-only TravelOptionalSanctum inherits core optional auth and implements native AuthenticatesRequests priority marker; valid actor auth precedes600/public signature. Guest/expired/invalid/private120/create10 unchanged. Lead production owner, separate native test owner/reviewer. | Fail-first actual Router suite, repaired10/2032 fixture assertions; not native HTTP/Sanctum/cache atomicity PASS. New fixed-SHA per-actor same-IP/native token/installed-cache verification required. |
| TL.INSTALL / W04-I02 | Native Module model persists vendor_mode string through real ModuleRepository create/updateOrCreate; default auto and protected fields unchanged. Recovery native owner; commerce native nonauthor review. | Fail-first SQLite real persistence6 failures→10/92 PASS. Existing7.0.12 consumer constraints apply; no public signature change. Actual empty TEST native install must persist bundled and exercise HTML handling. Plugin analogue is source-only, untouched. |
| TL.RECOVERY / interrupted req337 | Separate official recovery owner preserves failed originals and damaged22/21 safety snapshot; native TEST-only wipe/import followed by whole55/104 inventory equality. | req4be original8c29b9a6, lead760042e9: full row/DDL/object digest ded72a53…d056e PASS_BASELINE_RECOVERY_ONLY. Failed install remains FAILED; previous128/639 is NOT_PROVEN. TEST released for next install. |

Browser evidence original695341be→lead117a2b77 and security original3251043d→leadcf7a2be4 are evidence-only intake, not source repair or canonical Validation. Original reports and negative outcomes remain immutable. New source is a recorded owned diff until the lead publishes a reviewed checkpoint; author local checks and native source review are separate from official fixed-SHA verification.


W04F-01/W04F-02 follow-up contract0.1.2: owner files InquiryService.php (native commerce worker), TravelThrottleRequests.php/routes/env/cache fixture (lead), counter/lease/fault tests (native commerce worker), design+nonauthorreview (native UI worker), evidence/failed-install intake (native recovery worker). Input1052/f18fa205; repaired working file pins evidence/W04_ATOMIC_REPAIR/source-pins.json. P3 remainsOPEN until fixed published SHA nativeMySQL/HTTP independent confirmation. No API schema/status-state redefinition, additional503retry behavior documented; dependency consumer scan finds only own travel template>=0.1.2. Native ecommerce shipping is not renamed booking; native TEST_INQUIRY persists. BrowserCREATE requires own category/policy UI baseline then product/options/native registration; previous BLOCKED preserved.


## October9 continuation — source intake and independent recipe completion

Canonical bounded status:27official attempts =21COMPLETED/4FAILED/1INTERRUPTED/
1RUNNING/0QUEUED. RunningCLAUDE Page campaign implementation req_7fe59f839ad748fcb2db4c8ad4b488b0
owns travel shared campaign files at input992; no account/slot changes or duplicate
completed key. CODEX recipe req_bed1c2288b1946de95782fe1a4acff75 completed at992:
actual published migration helper, installed kernel37HTTP, unchangedMySQL1/34,
HTML2, exactTEST55/104rowDDL digest restored; TEST release18:42:38UTC. Whole setup/
account provisioning/wizard/officialValidation/hostedCI NOT_RUN. Original harness
exit2 and historical productFAIL remain preserved. Original0e634566 intaken locally.

Native admin CODEX result completed atfa: own category/policy/products/options/
travel metadata/departures/UI journeys at390/1440; 100sanitizedPNG and heterogeneous
sorting/retry cleanup evidence intaken. Rapid-save stale is_active and initialPC
pointermenu failure preserved as findings. Diagnostic independently shows native
G7Core.state.get returns content while ActionDispatcher incorrectly looked for
._global; two reads repaired locally. Actual8test fail-first6FAIL/2PASS ->8PASS;
focused final11files546PASS after correcting one existing wrong-shape test mock.
PC pointer failure not reproduced by standard locator/raw mouse/keyboard; cause
UNKNOWN, no unrelated menu fix. Product86/departure140 retained hidden/inactive/
reserved0;8own diagnosis tokens revoked by native logout401, handofftokens unchanged.

CLAUDE private-attachment review at992/runtimefa inheritance: real positive bytes,
foreign/guest unsigned denial, delegated valid signed-preview capability and native
owner-download restriction explicit. Nonimage500P3 repaired in one nativeBoard
line;6unit/47 assertions passed (including lead independent replay), native live
400/image gates still pending. Boardmetadata1.1.3 synced; other existing consumers
reviewed, unrelated RAON business files unchanged. Core fix is internal evaluator
restoration in existing unpublished7.0.12 batch with unchanged public state APIs.

Remote head992 remains latest before this evidence/repair batch. Same PR2 reused;
originalreview heads will be ancestry-preserved on meaningful reviewed publication.
Official core production build follows completed old-runtime diagnostic and source
freeze; new runtime must be independently attributed and replayed. Campaign source
and final core/board/browser integration remain IN_PROGRESS, not releasePASS.


## Published core/board checkpoint and next official runtime gate — October9 — source commit19:15:45UTC

Published fc54b6ae091cd6cef2d0fabc48d2ec4fe4fdba8c, tree
b377aeabad7f240061ae12cde8fae7ddc6f7e7d1, same OPEN/DRAFT PR2. Core source6584018b
plus ancestry-only ours merge preserves original browser1d2a4f0/private-fileabf354/
recipe0e634 as remote ancestors with unchanged repairedtree. Exactfc54Actions0,
check-runs0 ->hostedCI NOT_RUN; canonicalValidation receipt NOT_RUN, no gatewaiver.
Canonical work-order validation89/errors0 PASS before publication. Installed
Board1.1.3 controllerd368cb... matches bundled; official core production build
exit0 changed only enginebundleb8cf27..., sourcebb57838...; normalfa travel
catalog/workflow/template source unchanged. NOT finalcampaignintegrationPASS.

New official w04-common-binding-attachment-runtime-final attempt1 CLAUDE,
req_00485fdfe1eb455dbb11a6575fdd52d7, creation stateQUEUED; fixedtargetfc54.
Actual rapid-select->Save without rendered-label waits at390/1440 and native
nonimage400/authorizedPNGbytes/unsignedpermission/deletion/signature distinction.
Own3role handoff minimumread only; native newtokens mustlogout401, suppliedtokens
unchanged. NoSQL/TEST/env/service/build/source writes. Parent freezes runtime
until reviewer returns, PageCLAUDE owns only ownsource/SQLite and cannotdeploy.

Page work still w04-page-campaign-implementation attempt1 req_7fe59f... input992.
Do not duplicate either key. Own cumulative28 attempts by creationledger:
21COMPLETED/4FAILED/1INTERRUPTED +2nonterminal (PageRUNNING, newgateQUEUED atcreation),
not guaranteed model-stream state. Supported native authors/reviewers have now
finished; no internalagent counted asPC orofficialRequest. DeadlineOct11 23:59KST
about43h40m at19:19UTC; global/Spring/accountusage UNKNOWN, existingadmission unchanged.

On automaticresume: compare returned Page source/contracts/metadata/tests/build;
wait newruntime verification before any installedPage/core/cache change. Integrate
Page0.1.3 and Boardminimum>=1.1.3; add explicitly guarded campaign flag/provisioning
with actor, run affected tests/build then fixed final Page/customer/admin/recovery
validation. Preserve oldnegative findings and privatefixturecleanup. Finalreport
IN_PROGRESS; main/business/operatingDB/services/Spring untouched. No local-only
path used as final delivery; source/package/screens are Git-addressable onPR2.


## Page source recovery and reviewed integration checkpoint — 2026-10-09 19:40:25 UTC

Original Page implementation req_7fe59f839ad748fcb2db4c8ad4b488b0 canonically FAILED
while awaiting native review, causeUNKNOWN. Savedc85eea30 parent992 has78sourcepaths;
root95d16543 and source review recover it without changing Request state. Native
nonauthor Campaign20/384/template typecheck160/admin9 passed. ExpandedSQLite175
firstfailed old installedModule-vs-bundled hash; clean-subprocess fixture repair
preserves real guard/membership/ordinary/noTrade/stock, focused3/61 and final
175/2936PASS90.541s. This is source-origin, not installednewPage proof. Runtime
frozenfc54 while CLAUDEreq00485 actualcore/boardverify runs.

Four native write-navigation predicates use collectioncan_create/rowcan_update;
failfirst9/3 ->wrongslug10/2 ->12/0, original negatives preserved. Final nonauthor
source reviewf345dc5c compares real ability/nativehook contracts, author tests not
rerun. CAMPAIGN_EVIDENCE.md now exists and linksrealreceipts; earlier absence is
historical. Publicrunnerlogs/JSON normalize only Request paths, original and
publishedhashes/failures retained. Scope20 and final175 overlap, no inflation.

Travelmodule/template0.1.3, template>=module0.1.3, Page>=1.1.2 unchangednativeAPI,
Board>=1.1.3 nativefix; metadata/doc contradictions/TLDR fixed. Campaignflagdefault0,
setup appends only missing0, no auto command/actor. Explicit guarded provision
requireslab-confirm+permittedPageactor, skips existingedits/drafts. Rootrecipe
source/syntax/PintPASS; actualprovisioning NOT_RUN. No APPenv/setup/runtime/cache/
service/install duringfreeze. Isolation27/workorder89/errors0 PASS prepublication.
Supplied compiledtemplate IIFE no sourcemap/currenthash0cd1fbeb; independent
rebuild/installedbinding PENDING. Fixed TEST-native campaign installation/recovery
can run separately; actualPage390/1440 after core/board gate returns and leadupdate.
FinalreportIN_PROGRESS; hostedCI/canonicalValidationNOT_RUN; main/business/
production/Spring unchanged. No account/capacity increase or artificial2lane cap.


## Campaign native activation and independent gates — 2026-10-09 19:49:08 UTC

Reviewed Page checkpointd059/tree6dc is published on existing draftPR2. Actions/
checkruns exactd059 are0/0 NOT_RUN, no canonical Validation receipt. Catalog
unchanged7f77/2ca confirms10active50total/CODEX8CLAUDE3 configured admission,
not available-account slots. New CODEX req_812d0334c2064f5d88339114e7954235
w04-campaign-native-install-final attempt1 QUEUED on assignment; sole TEST owner,
minimal scopedTESTenv only, independently measure/snapshot/restore/release.
At19:44 status28attempts22COMPLETED5FAILED1INTERRUPTED0RUNNING, followed by this
29th creation. Global/Spring/accountremainingusage unknown; CENTRALverified1PC,
no off-host execution or capacity changes. Host19:47:55 load3.05/2.01/1.78,
RAMavailable10631MiB/15783MiB, swapused4776MiB; hostwide not projectload.

CLAUDEreq00485 completed fc54 bounded actual runtime PASS. Originale902 report/
14safe paths reviewed/nativeintake3732ce14, 12 rapidSave/trustedstale-label cases
bothwidths, attachment14PASS1signedcapabilityOBSERVED, own20tokenslogout401,
suppliedtokensunchanged. No direct cache/counter manipulation; HTTPnaturally
updates native counters. OriginalPCpointercauseUNKNOWN. Scope0.1.2 retained.

After review returned, lead official templateproductionbuild/moduleupdate/
templateupdate exits0 at19:46:21/30/48 activated0.1.3. Native Page provision actor56
withprocess-onlyflag1 createdPages7/8 at19:47:05 then skipped2 at19:47:21.
Measured additional replay preservedwhole-rowhashes/IDs/version1/publication/
snapshotcount1, defaultflagfalsebefore/after; flagoffexit1nowrites. LocalAPPonly,
noTEST/setup/account/env/servicebusinesschanges. Source/runtimeparity156/156,
explicitselection/exclusions in W04_CAMPAIGN_ACTIVATION. CompiledJS8941442d
diffonly embeddedBoarddependencyfloor1.1.2→1.1.3; coreb8cf/Boardd368 unchanged.
Native readonlyreview11assetpairs/source-map0, no duplicatedtests. Independent
newPage390/1440fixedruntime and TESTnativegates pending; no wholeproductPASS.
