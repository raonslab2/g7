# Verified 1PC content restore to AWS G7 — 2026-10-04

Request: `req_8390c9e0ce444e47af7cba487f1c4b45`. One operator request; no second
coordinator, external CLI handoff or child delegation was created.

## Baseline and backups

Fetched main and `/srv/g7/current` both were
`e7c6f10729a262e1143805e2f557653769ed1a60` at intake. The same main was checked
again before delivery. Existing schema/ngram conclusions were reused.
AWS canonical backups were generated at 07:36:51 and 07:43:52 UTC; the latter
is the authoritative pre-apply backup under `/var/backups/g7-product`:
`database-20261004T074352Z.sql.gz`, `persistent-20261004T074352Z.tar.gz`,
`config-20261004T074352Z.tar.gz`, `checksums-20261004T074352Z.txt`.
Checksum/gzip/tar validation passed; the database was imported into a separate
empty check DB, and every target table's row contents matched the restored clone
using internal sorted-row hashes. Directory 0700, archives 0600, root-owned.
The archives retain all four existing private leads and operational settings.

Source artifact was delivered in commit `49c64a0682a8e4e0450c3db8d2e7d9f678425e61`.
Its original checkout is **`c9e93660870901db12c4d69d525073f6e72c9fa9`**, not that
delivery commit. Encrypted archive checksum, plaintext archive manifest checksum,
inner checksums, SQL/persistent gzip and tar integrity all passed. The reference
conversion removed exactly 14 MySQL ngram clauses, without changing the source SQL
or ignoring errors. Actual reference: **111 tables, 2,296 rows**, every CHECK TABLE
OK (`source-validation.json`). Source migration history: 218; target: 215, no
missing installed migration. Three source-only migrations belong to uninstalled
CKEditor5 and AI workspace and were excluded.

## Mapping and restored content

`applied.json` is the authoritative per-table A/B/C classification, before/after
counts, operations and final source-ID→target-ID/version mapping. `rehearsal.json`
was rolled back; its numeric allocated IDs are not the delivered IDs. No production
row was deleted/truncated, no schema was imported, no constraints were disabled.

| Domain | Before | After | Delivered source scope |
|---|---:|---:|---|
| Pages | 6 | 11 | 6 matching slugs updated, 5 inserted; all original bodies |
| Page versions | 12 | 36 | all 24 source snapshots appended, 12 AWS snapshots retained |
| Boards | 1 | 5 | 4 source public-board definitions; AWS private board unchanged |
| Posts | 4 | 22 | all 18 original posts, 16 published / 2 deleted |
| Comments | 0 | 1 | original comment and remapped relationships |
| Board attachments | 0 | 1 | original attachment on deleted post; same gated visibility |
| Menus | 31 | 35 | 4 missing board administration menus |
| Roles | 7 | 15 | 8 missing board roles, no source user assignments |
| Permissions | 200 | 268 | 68 missing public-board permissions |
| Role permissions | 300 | 520 | 220 additive grants for restored board surfaces |
| Role menus | 32 | 46 | 14 additive restored-board menu grants |
| Users | 1 | 1 | AWS administrator unchanged; no source credential import |

Source `community` board ID 1 was allocated a separate target ID; private
`raon-consultations` stays target ID 1. All references use final mapping, not raw
source IDs. Existing target roles/permissions/grants/menu rows remained identical.
Source author IDs/passwords are not transferred; attribution is not invented to
the AWS administrator. Public author display names remain original content.

`storage-audit.json` compares every one of the **78 archived regular files**.
`storage-applied.json` copied only the one referenced 27-byte text attachment,
ubuntu:www-data 0640 in new 0750 directories. Existing files were not overwritten.
Source .env, old bundles, settings, caches and evidence scripts/screenshots were
not copied. `configuration-preservation.json` proves all three operational files
and all 30 existing settings files are byte-identical to the pre-apply archive.

## Actual public and administrative verification

All **11** original slugs have API 200 and exact original localized body equality
(`content-verification.json`). Each source Page's current mapped snapshot has the
same decoded title/content (`current-version-equality.json`). The required about,
service, cases, technology, faq, contact, privacy and terms are all public.
Refund, AI workspace policy and open-source were also restored because original
Pages link to them. There were no invented replacement Pages or copy.

`/page/service` displays the original service introduction, including
“업무 하나를 실제로 동작시키는 데서 시작해”. Separate Googlebot SSR checks confirm
all 11 routes return 200 and service renders original content (`ssr.json`).
The normal browser UA was used for interactive smoke because G7 deliberately
serves SSR HTML to HeadlessChrome. This distinction did not require a route fix.

`browser.json` passes: Chromium **390x844 and 1440x900**, 16 routes each (home,
11 original Pages, board list, community, questions and notice), actual service→
technology→cases→contact menu clicks and home community button. Latest two-line
RAON Agent Factory header and dark theme remain; no 5xx, console/page errors,
broken visible images, 404 responses or horizontal overflow in the final smoke.
Four public screenshots record home/service at both widths.

Repeated fresh-browser debugging reached the existing config rate-limit once
(429); no rate limit/cache/HTTPS/privacy gate was disabled or cleared. Final
smoke uses actual SPA transitions and waits between independent viewport boots.
The final browser run has no 429. This is verification pacing, not an intake fix.

Admin Page list and every Page detail API return 200. The actual administrator
Page list/detail and private-board browser routes also load. Private lead IDs
**2, 3, 4, 5** remain byte-identical to backup and each admin detail API is 200.
Their existing categories/action logs are unchanged. Relational checks report
zero orphan board/post/parent/comment/attachment/Page-version/menu/grant references.
Settings and admin APIs pass; jobs and failed_jobs both remain 0. Runtime migration
status, module/plugin status and G7 services are healthy; no new SQL/5xx log errors.
AI_GCS and MOBILE_STOCK health both return 200/ok (`adjacent-health.json`).

## Tests, delivery and retained boundaries

PHPUnit: **91 tests / 400 assertions passed** (Installation 2/29, public/admin Page
and menu 87/320, reconciliation 2/51). File boundary regression: 3 Python tests
passed (`tests.json`). The existing Page feature fixture needed a route-name
lookup refresh after registering routes at runtime; its failing attachment-preview
test was observed before the test-only fix and passes afterward. No core or module
public API changed, so extension version constraints and product assets are unchanged.
No frontend build, service restart or host reboot was required.

Implementation started with `f1c6a8ab`; scripts/tests/runbook and this sanitized
evidence are delivered to main through ordinary non-force Git integration. The
final request report records the observed final main and runtime SHA. Environment
and persistent storage symlinks are retained during the release fast-forward.

Intentional exclusions: source users/credentials/sessions, two source AI request
rows, uninstalled CKEditor5/AI workspace state, source ecommerce user profiles,
notification/activity/SEO runtime histories and older template/layout/settings
state. These are not required for the restored public content and would cross
current authentication/privacy or latest product-shell boundaries. The one source
CKEditor5 admin menu and its permissions/grants are not installed on AWS.

Public consultation submission **remains closed**: HTTP site plus pending approved
privacy/consent configuration (`consultation-readiness.txt`). Restoring contact
content does not authorize bypassing these existing gates. There are no remaining
missing original public Business Pages, posts/comments or referenced files.

After verification, disposable reference/check/testing databases, the root-only
plaintext restore directory, synthetic test environment and diagnostic temp files
are removed. Canonical AWS backups and the original encrypted source artifact
remain. No plaintext dump, key, token, cookie or secret value was added to evidence.
