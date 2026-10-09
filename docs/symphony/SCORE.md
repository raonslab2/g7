# G7 Travel Lab score — contract v1

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
| TR-CATALOG; HOME/LIST/CATEGORY/CAMPAIGN/DETAIL | CODEX official `w01-domain`, attempt 1, input SHA above | new travel module manifest/bootstrap/provider/models/enums/migrations/seed/repositories/catalog service/controllers/requests/resources, `src/routes/catalog.php`, domain tests | Actual commerce products/options/catalog; filters, publication, valid dates, admin departures; migration down/rerun and synthetic seed replay | RUNNING; result SHA pending |
| TR-TRANSACTION; CART/REQUEST/MINE/ADMIN | CODEX official `w02-workflow`, attempt 1, input SHA above | travel cart/inquiry services, scoped repositories, controllers/requests/resources, `src/routes/workflow.php`, workflow tests | Real commerce cart + calculation; durable idempotent test inquiry, capacity locking, owner/admin gates, explicit transitions | RUNNING; result SHA pending |
| TR-UI; HOME through MINE/HELP | CLAUDE official `w01-ui`, attempt 1, input SHA above | dedicated `templates/_bundled/raonslab-travel_lab/**`, UI_DESIGN.md | Live API customer screens; 390/1440, loading/empty/error/retry, original assets; build/typecheck | RUNNING; result SHA pending |
| TR-SUPPORT; HELP/INQUIRY/ADMIN | CLAUDE official `w02-support-admin`, attempt 1, input SHA above | support services/provisioner/controllers/requests/routes/listeners; travel module admin layouts/routes; support tests | Native isolated G7 notices/FAQ/private inquiry; owner/admin and notification suppression; admin live transitions/catalog | RUNNING; result SHA pending |
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
| Departure / travel_lab_departures | id; product_id; product_option_id unique FK ecommerce_product_options; departure_date; return_date; capacity; reserved default 0; is_active; timestamps. Relations product and option. Availability = capacity-reserved. |
| Inquiry / travel_lab_inquiries | id; user_id; idempotency_key; payload_hash char64; status; total_amount decimal(20,2); currency_code; contact JSON nullable; admin_note nullable; timestamps. Unique(user_id,idempotency_key). Relations items/user. |
| InquiryItem / travel_lab_inquiry_items | id; inquiry_id; departure_id; product_id; product_option_id; quantity; unit_price and line_total decimal(20,2); product_name JSON; departure_date snapshot; timestamps. Relations departure/inquiry. |
| Parent integration addition | Inquiry.calculation_snapshot JSON records the complete server calculation result, required for new submissions. InquiryEvent / travel_lab_inquiry_events: id,inquiry_id,actor_id FK g7_users,from_status nullable,to_status,note nullable,created_at; append only in the same inquiry transaction. Parent owns a separate migration/model/repository integration and corresponding workflow write tests; child base migrations are not edited concurrently. |

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
| GET /admin/catalog; PATCH /admin/departures/{id} | travel metadata/capacity; commerce admin owns product/options/prices | travel catalog read/update permissions |
| GET /support/notices,/support/faqs | authored dedicated native G7 Board records | public published content |
| GET/POST /support/questions; GET /support/questions/{id} | persisted private question; native board contracts; no external notifications | authenticated owner/admin |

Catalog DTO: id=product_id,title,region,theme,duration_days,summary,itinerary,from_price,currency_code,image_url,departures[{id,product_id,product_option_id,departure_date,return_date,available,unit_price,currency_code}].
Visitor paths: /travel,/travel/search,/travel/products/:id,/travel/cart,/travel/requests,/travel/requests/:id,/travel/help. Preserve G7 toast/modal/login hosts and existing AuthManager. Submission retry preserves key/payload; successful/new cart creates a new key. All actions use documented G7 dispatch handlers. Admin path /admin/travel-lab plus dedicated catalog/inquiry layouts and native ecommerce/board administration links.

## Verification and publication gates

Run canonical work-order validator in its owning ai_gcs_v2 exact tree, not a homemade G7 substitute. New-function scenario matrix, focused PHP/API/DB tests, actual commerce/board regressions, Installation smoke, frontend build/typecheck, 390/1440 real browser. Required tamper/idempotency/concurrency/ownership/state/restart/seed rerun/rollback checks are independently recorded PASS/FAIL/NOT_RUN/BLOCKED against a fixed SHA. Internal review is not official Validation. No CI workflow exists at intake; unavailable required checks are not waived.

G7 repo-owned workflows/hooks are absent at intake, main unprotected; unmanaged external automatic deployment remains UNKNOWN. Preserve an inspectable integration branch if main merge could mutate production. Never restart production to prove this lab. Deliver Git-addressable install/run package and sanitized UI evidence, not only a local path.
