# G7 Travel Lab — public reference and native UI/runtime intake

Request: `req_81ac33cac94046b9a2249cd14c0d00ba`
Source intake SHA: `6853f40d58acbf53a2f29cbb9dd422cc439047a9`
Observation: 2026-10-09 08:34 UTC / 17:34 KST. Read-only public web extraction; no login, form submission, booking, payment, image download, or performance test.

## Public observation evidence

The following are information-architecture observations, not a reproduction of the reference site's design or claims about its private behavior. Public content extraction is not a visual browser verification. Product photography, logos, marketing text, and original source were not copied into the repository.

| Public source | Observed fact | Limit / implication for RAON |
| --- | --- | --- |
| [Welcome](https://www.lottetour.com/welcome) | Region and travel-type navigation, search entry, campaign links, recommended product cards with starting prices, account/request entry, customer-help entry. | Adopt these discovery-to-detail entry points with RAON identity and synthetic products. No reference assets are needed. |
| [Regional landing](https://www.lottetour.com/area/826/854) | Regional subnavigation, recommended-products section and campaign section. | Some product sections were absent from extracted HTML; dynamic population was not verified. |
| [Campaign index](https://www.lottetour.com/promotion) | Campaign tiles/links, regional/type tabs, keyword entry. | Campaign membership should be RAON-authored catalog metadata. Publication management is our own assumption. |
| [Search](https://www.lottetour.com/search) | Keyword, departure-date and price entry; result categories; refine controls for duration, weekday, departure city, carrier, grade and availability status. | No query was submitted; the unqueried page showed zero counts. This is evidence of exposed controls, not proof of search correctness or inventory semantics. RAON minimum filters are region/date/price with explicit sort and empty state. |
| [Public product entry](https://www.lottetour.com/evtList/848/1169/1184/3786?godId=64416) | Product code, media slots, summary price range, duration, carrier and departure weekdays; date/price view controls and related-campaign section. | Day-by-day departure inventory and actual reservation handling were not visible in extraction. RAON departure inventory/selection must be implemented and tested against its own DB. |
| [Customer center](https://www.lottetour.com/cs) | Notice, Q&A and FAQ entries, FAQ keyword/category navigation and prominent questions, notice teasers and other help links. | Reuse G7 Board/Page concepts for authored help and owner-scoped inquiries. Actual private Q&A access rules were not observed. |
| [Q&A entry](https://www.lottetour.com/cs/qna) | Public help layout, category/region/search controls and guidance against posting personal identifiers. | Tool response reported an older crawl; only the public structure is considered evidence. No posts or inquiry submission were accessed. |
| [FAQ entry](https://www.lottetour.com/cs/faq) | Link exists on customer center. | Fetch denied by robots.txt; FAQ internal detail behavior is NOT_OBSERVED. No bypass attempted. |

Reference-site administrator screens, authenticated account flows, actual payment, confirmation, refund, supplier integration, notifications and database contracts are **NOT_OBSERVED**. Customer-approved design/DB and 110-screen delivery are outside this evidence.

## Proposed RAON original UI contract

These are RAON lab assumptions for lead approval through implementation, not customer approval. Use a light canvas, deep-teal navigation, warm accent, generous whitespace and restrained destination illustrations authored in the repository. Do not force the existing RAON business-site dark theme onto travel. Avoid third-party image dependencies in the first runnable version; authored SVG/CSS artwork can make catalog cards recognizable and remain available offline.

Desktop: central content width about 1200 px, concise primary navigation, two-column discovery/detail compositions, readable search summary, 3-column product cards, visible request-status access. Mobile (390 px): search controls stack, filters open as an accessible panel, cards become one column, and the date/party/action summary stays reachable without horizontal overflow. Include descriptive labels, focus visibility, actual disabled/loading states, error text and retry actions.

| Screen ID | Proposed route / surface | Required behavior and API dependency |
| --- | --- | --- |
| TR-HOME-001 | `/travel` | Original hero + region/date search, destination entries, campaign and recommended product cards; live catalog API. |
| TR-LIST-001 | `/travel/products` | URL-preserved region/date/price/query filters, explicit sort, result count, reset, loading/empty/error/retry; live catalog API. |
| TR-CATEGORY-001 | List route with region query | Same list contract reached from a destination entry; do not create a disconnected catalog. |
| TR-CAMPAIGN-001 | List route with theme/campaign query | Authored campaign header and filtered catalog membership; explicit empty result. |
| TR-DETAIL-001 | `/travel/products/:id` | Product summary, authored itinerary, available departures, party count and server-calculated selection quote; unavailable departure blocks cart action. |
| TR-CART-001 | `/travel/cart` | Actual ecommerce cart adapter with selected departure and party, server prices, change/remove/revalidate; preserve this step. |
| TR-REQUEST-001 | Cart submission / receipt | Explicit test-only acknowledgment, idempotency key, persisted test inquiry receipt; never display real booking confirmation. |
| TR-MINE-001 | `/travel/requests` | Authenticated own-request list/detail, durable status and processing result, retry and re-login restoration. |
| TR-ADMIN-001 | G7 admin travel layout | Permission-protected request list/detail and allowed state transitions; actual server checks. |
| TR-ADMIN-002 | G7 catalog + travel departure layouts | Reuse ecommerce product/price management; travel-owned departure/date/capacity metadata. |
| TR-HELP-001 | `/travel/help` | Notice/FAQ discovery and detail through dedicated G7 Board/Page records. |
| TR-INQUIRY-001 | `/travel/help/inquiries` | Authenticated private inquiry creation/own view, staff processing; owner/permission and attachment checks. |

At cart submission and on receipt/status screens, make the test nature explicit: no real supplier reservation, payment or outbound email/SMS. Use separate test-request status vocabulary (`TEST_INQUIRY` purpose and lab processing states), never rename ecommerce shipping status to travel confirmation. The business/API owners must finalize precise field/status contracts in SCORE/API evidence before UI consumption.

## G7 integration evidence and constraints

Read local guides: `docs/frontend/template-development.md`, `docs/frontend/components.md`, `docs/frontend/layout-json.md`, `docs/frontend/layout-json-inheritance.md`, `docs/extension/template-workflow.md`, `templates/_bundled/sirsoft-basic/AGENTS.md`, `docs/testing/e2e-testing.md`, `tests/Playwright/README.md`, `docs/cheatsheet.md`, `docs/requirements.md`.

- `sirsoft-basic` (1.1.4 at intake) owns visitor ecommerce/board/page UI. The ecommerce and board modules own backend/public API and admin layouts. Template manifests declare module dependencies.
- Work on a dedicated bundled travel module/template; do not modify activated directories or `raonslab-product`. Travel activation occurs only against isolated lab DB/config. G7 admin can remain `sirsoft-admin_basic`, consuming module-owned travel layouts.
- User layouts need their own `_user_base` inheritance (or correctly installed shared toast/modal hosts). Missing hosts make successful action dispatch appear visually inert. A new template must supply its own base/error layouts and cannot assume cross-template components are automatically available.
- Use G7 basic components instead of raw HTML in composite/JSON UI. Register actual components in manifest; use IIFE template build, React globals, esbuild and production outputs without sourcemaps. Common UI owner owns base, routes, component registration and shared state contract.
- Partial files only replace component trees; their top-level data sources, computed values, scripts, state and modal definitions are not merged. Define these in the owning main layout.
- Existing cart uses cart-key initialization and ecommerce server APIs; guest-to-user cart merge is server-owned. A travel adapter must preserve that contract and canonical server amounts. Auth/status inquiry screens must use bearer token authentication.
- Use `G7Core.dispatch`/`apiCall`, top-level target/success/error hooks, `navigate` with `params.path`, `refetchDataSource` with `dataSourceId`. Preserve query filters. Treat API response envelope nesting as a contract, not a guessed path.
- Layout-only changes need extension update and template cache clear in the lab runtime. Component/handler changes need build then update. Do not run these against an existing product runtime.

## Runtime observations and proposed isolated verification

At intake, local tools were PHP 8.3.6, Node 22.23.3 and Composer 2.7.1; mysql/mariadb/docker commands exist. Root `vendor`, `node_modules` and `public/build/manifest.json` exist. `.env` and `database/database.sqlite` did not exist. These observations are not a runtime PASS and do not prove DB service availability, browser installation or extension activation.

Repository requirements are PHP 8.2+, MySQL 8.0+/MariaDB 10.3+ and required PHP extensions. Root PHPUnit explicitly selects MySQL. `tests/bootstrap.php` requires `.env.testing` and rejects matching application/test `DB_WRITE_DATABASE` names. Use separate lab application and testing DB names, explicit read/write DB configuration, test-only app key and non-network mail/queue drivers. Do not silently substitute SQLite for production-shaped concurrency/price checks.

Suggested reproducible lab workflow, to be implemented/verified by runtime owner:

1. Provision distinct `g7_travel_lab` and `g7_travel_lab_testing` databases without touching existing DBs. Configure isolated root/server, storage paths, local-only logs/array mail and lab-only synthetic users; no payment/supplier/notification credentials.
2. Migrate core, install/activate necessary ecommerce/board/page dependencies, travel module and travel template only in this lab. Seed through supported services/helpers. Exercise seed rerun and isolated rollback/restore.
3. Build changed module/template with the project's Artisan production build. Run targeted PHPUnit, relevant ecommerce/board regressions, template component tests and template `type-check`. Record every command/result against the fixed source SHA.
4. Start the lab via a dedicated local port/root, e.g. `php artisan serve --host=127.0.0.1 --port=8097`. Supply `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8097` explicitly; a loopback preview alone is not the user's final remote-access artifact.
5. Travel E2E belongs to the owning extension's `tests/Playwright/specs`, not core tests. Use a real Chrome-like UA (G7 SEO middleware can otherwise send bot HTML), `ko-KR`, 390/1440 viewports and visible UI assertions. Assert `window.G7Core`/actual SPA execution, not just HTTP 200. Store extension-specific reports/traces outside active extension directories.
6. Use G7 token fixtures for synthetic identities. Permission-boundary tests require `--no-admin-role`; default admin roles can conceal missing explicit permissions. Verify owner/other-user/staff boundaries, quantity/date/amount tampering, duplicated and parallel submissions, retained DB state after process restart, empty/error/retry and keyboard access.
7. Deliver a Git-bound install/run package or approved isolated preview plus sanitized screenshots/report links. No existing public product services need restart. Hosted/native CI and independent validation remain separate gates and must be labeled if not executed.

No tests, runtime mutations, deployments or publication were performed by the reference investigator. This document is intake evidence only; the lead owns Git delivery and final validation status.
