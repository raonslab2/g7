# Wave status

Work `work-20261009-g7-symphony-max-child-c7ae42d1`; parent `req_81ac33cac94046b9a2249cd14c0d00ba`; baseline `6853f40d58acbf53a2f29cbb9dd422cc439047a9`.

## W00 / initial parallel implementation — October 9

- Public reference, local extension contracts, canonical receipt/catalog and scoped Git inheritance checked. Previous pilot order is retained; deleted receipt is not cancellation. Reissue and old live Request status are UNKNOWN under limited tool coverage; no previous Request cancelled/deleted/terminated.
- Real commerce service research completed. Contract v1 in SCORE.md; crucial free-shipping/missing-option/option-replacement hazards recorded for integration review.
- Official implementation: domain CODEX, transaction CODEX, visitor UI CLAUDE, support/admin CLAUDE, attempt1 each. All four canonically observed RUNNING (not completion/PASS); parent native runtime and canonical evidence work continue.
- Local locked Composer/npm dependencies installed; Composer install used --no-scripts, 144 packages; npm ci added255 packages. No app DB mutations or production service restarts by lead.
- Baseline verification: `npm run build` PASS (7.80s); `npx vitest run resources/js/core/auth/__tests__/AuthManager.test.ts resources/js/core/template-engine/__tests__/ActionDispatcher.handleNavigate.fallback.test.ts resources/js/core/template-engine/__tests__/ActionDispatcher.globalHeaders.test.ts` PASS, 3 files/56 tests (6.82s, 08:43 UTC). jsdom scrollTo notices were nonfatal. These checks validate unchanged baseline frontend contracts, **not Travel or independent validation**. Generated root build outputs are excluded from the travel checkpoint.
- New branch `feat/g7-travel-lab-c7ae42d1`; no existing travel branch or PR discovered. Remote checkpoint/PR pending canonical validation + meaningful implementation/evidence. No per-agent push.
- Open limitations: account quota/global live count unavailable; old live coordinator unavailable; unmanaged main autodeploy unknown. All isolated design/implementation outcomes remain reviewable and not customer-approved.

### W00 verification checkpoint

- Canonical input-order validation at ai_gcs_v2 `10bc5eff5f777aa49760387778303063b0222129`: PASS, 89 documents, explicit new work ID present, no errors. An initial lazy-object read failure was recovered without source/validator changes; full receipt in WORK_ORDER_VALIDATION.md.
- Runtime harness completed: isolated MariaDB schemas `req81_travel_lab` / `req81_travel_lab_test`, dedicated scoped account; core migrations/settings/synthetic admin initialized, setup rerun preserves users. Actual local/test DB and array-mail/sync-queue smoke PASS; InstallerContext 8 tests/8 assertions PASS; 15 negative isolation checks PASS; loopback /up HTTP200. Preview stopped after smoke. Secrets only in ignored 0600 environment files. This is runtime preflight, not Travel journey or independent product PASS.
- Native W00 review at `6efbf0a57dfb75c0fcea6944407f136f5366e273`: CHANGES_REQUIRED, product NOT_RUN. Contract inventory/cancellation/list-route defects corrected in next checkpoint; full calculation snapshot/actor audit explicitly assigned to parent integration. Fixed-target source/canonical evidence now included; next revision requires re-review. REFERENCE's author did not approve their own file.
- Child implementation files still pending. Runtime extension installer returns BLOCKED before mutations if any required travel manifest/class/composer/route/template is absent. Shared API entry point lint PASS, its scoped includes are pending.
- Historical coordinator lookup remains UNKNOWN. One asynchronous request for prior Request ID/official handoff link has been issued; work proceeds as quarantined travel-only scoped output, no main/production integration and no duplicate-free assertion until bounded coordination is resolved.
- Separate nonauthor runtime/reference review at `8822971c994955abd196a0d3936c32b4f54c1e51`: bounded clean-environment preflight PASS, Travel/official Validation NOT_RUN. Two hardening findings corrected before publication: FILESYSTEM_DISK must be local before every runner command; same-process smoke clears rejected inherited DB/cache/installer keys from process/ENV/SERVER adapters. The new external-storage guard first failed exit255, then the corrected guard suite passed16 checks. Injected unusable local DB_URL smoke passed before and after (current effective G7 config already chose lab DB); source-level inherited-key retention, rather than an observed foreign DB connection, motivated adapter cleanup. Corrected dev/test smoke and Pint PASS; fixed-revision independent recheck pending. Report W00_RUNTIME_REVIEW.md keeps the original target/results separate.
- `tests/scenarios/travel-lab.yaml` records14 required journey/security/concurrency/restart/UI scenarios and cross-product axes. Symfony YAML parse succeeds; this is a requirement matrix, not14 executed product checks.
- Independent runtime hardening recheck at `09f7fe71b3439cac00a497513cd63e27dcaffb14`:16 guard cases PASS, dev/test smoke with unusable loopback DB_URL/socket PASS, rejected keys absent from process/ENV/SERVER verified. Findings F1/F2 RESOLVED for W00 harness; product/official/browser/concurrency/integration remain NOT_RUN. This is the reviewed runtime code revision; later checkpoint changes record evidence only.
- Publication will batch the W00 contracts, runtime and its fixes/reviews into one remote branch checkpoint and one draft PR. Canonical review targets and outputs above remain fixed. No hosted G7 Actions workflow exists, so remote CI is NOT_RUN; no required gate is waived. The first runnable Travel version is still pending official implementation returns.
- Continuation must fetch the published `feat/g7-travel-lab-c7ae42d1` branch and its draft PR; never assume workspace-only paths or ignored env survive. Rehydrate scoped runtime without touching production; if env secrets are lost while lab schemas/account persist, resolve scoped account/admin recovery before treating setup rerun as PASS. This recovery case is not established by the existing same-env setup rerun.

## Historical W00 handoff (completed by W01 integration)

1. Publish the reviewed W00 contract/runtime/canonical evidence checkpoint; then use official wait to release the parent slot until selected implementation results are ready.
2. Integrate scoped child commits as they become ready; align API DTOs/permissions/routes/provider/support hooks before running same-version UI.
3. Record W01 remote checkpoint in one reusable PR after relevant tests; delegate fixed-SHA independent contract/security and browser/regression checks via official tool.
4. Repair failures, rerun affected checks, complete core cart→test inquiry→admin→owner status journey first; then broaden authored help/campaign/screens.
5. Preserve a Git-addressable reproducible package/preview and sanitised evidence; integrate under actual repo gates and retest. Report incomplete gates truthfully.

Spring Request remains registered and independent; no state/source changes made to it. Recoverable implementation needs no approval. Previous Request ID/official handoff link has been requested once because the provided canonical historical lookup is bounded; preserve scoped output while awaiting it. If a conflicting implementation appears, stop only that conflicting scope and perform the permitted handoff.


## W01/W02 integration candidate — October 9, approximately10:10 UTC

The four implementation results were compared and cherry-picked without overlapping
ownership conflicts. Original child commits are listed in SCORE.md. The independent
CLAUDE contract preflight is retained verbatim in W01_CONTRACT_REVIEW.md; its
CHANGES_REQUIRED findings motivated integration repairs. No child assertion is
reported as canonical Validation. The existing draft PR is
https://github.com/raonslab2/g7/pull/2; this checkpoint will reuse its branch.

### Same-version implementation and repaired contracts

- Real ecommerce Product/ProductOption/Cart/OrderCalculationService supply identity,
  quantity and price. An explicit active nondefault KR/FREE zero-fee policy is required;
  money fields, coupons/points and real checkout/order/payment routes are rejected for
  travel. Ordinary commerce behavior remains subject to its own regression checks.
- Four original travel tables plus inquiry events/calculation snapshot persist the
  TEST_INQUIRY/UNDER_REVIEW/TEST_ACCEPTED/DECLINED/CANCELLED state machine. Actor audit,
  normalized contact, idempotent retry and capacity release use DB transactions/locks.
- Catalog and workflow agree that same-day departures are unavailable; capacity is
  capped by native option stock. Admin departure edits now use the same lock ordering
  as submission. MySQL's 64-character index limit and native permission description
  requirements were discovered during actual installation and corrected.
- Native board/page/ecommerce/admin and own light visitor template are installed through
  official extension lifecycle commands. Notices/FAQ/private questions persist in native
  board tables. Private questions are excluded from public search; notifications, including
  report notifications, are suppressed for the lab boards.
- UI binds actual resource envelopes/routes, uppercase states, cart IDs/contacts, admin
  product/departure IDs and retry keys. Captured installed HTTP responses cover eleven
  actual endpoints. Actual guest browser inspection caught empty region/theme facets:
  the API returns singular region/theme arrays. Those bindings and ko/en friendly labels
  were corrected and tested; guest debug is disabled in this marked preview.

### Verification ledger before the fixed checkpoint

| Scope | Result | Evidence/boundary |
| --- | --- | --- |
| Canonical bundled domain/workflow + admin-layout suite | PASS118 tests/1895 assertions | SQLite/native Sanctum and actual bundled source-path assertions; not MySQL concurrency |
| Visitor template | PASS120 tests/9 files; type-check/build PASS | Author checks, including actual captured response fixtures; not independent browser Validation |
| Native MySQL support API/provisioner/layout | PASS16 tests/142 assertions | Root guarded isolated testing schema |
| Native MySQL support notification/report suppression | PASS2 tests/15 assertions | Same isolated schema; no external delivery |
| Existing ecommerce cart quantity/purchase limits | PASS15 tests/39 assertions,34.236s | Native existing feature regression, source unchanged |
| Core auth/navigation/headers/binding registry | PASS64 tests/4 files | Only registry snapshot updated for new template registrations, no engine change |
| Actual MySQL root HTTP contract | PASS1 test/31 assertions | Native services/migrations/Sanctum, own testing schema |
| Installed app API capture | PASS11 responses | Actual installed routes; author harness, no auth headers/secrets exported |
| Four multiprocess MySQL races | PASS | Distinct connections, domain service boundary; HTTP concurrency remains NOT_RUN |
| Seed/provision rerun; migration rollback/replay/restore; env-loss recovery | PASS author checks | Scoped lab DB only, digest comparisons in portable runtime-self-checks.json |
| Preview stop/start persistence | PASS author check | Three inquiries/items and five events preserved; loopback /up200 |
| Runtime safeguards | PASS20 guards | New runtime independent delta review is separate and may require repairs |
| Official fixed-SHA security/browser Validation | NOT_RUN | Must delegate against the published integrated source, then repair/reverify |
| Native Installation suite | PASS2 tests/29 assertions,43.721s | Guarded isolated testing schema; root G7 installation smoke |
| Remote CI | NOT_RUN | No hosted G7 workflow observed; no gate waived |
| Main/production deployment | NOT_RUN | Unknown unmanaged main auto-deploy; preserve integration branch |

Assertions include source-identity checks and cannot be summed into distinct business
scenarios. Overlapping focused reruns are not added to the suite totals. Earlier child
fixture results do not replace the canonical suite. Tests initially loaded installed
extension copies: the canonical bootstrap now pins bundled src and verifies origins;
root Module uses native ExtensionManager loading with an exact byte-hash identity gate.

An independent runtime delta review found that failed API capture could leave an active
seed inquiry, and a direct LiveMysqlTest invocation validated but did not apply the clean
environment before bootstrap. Capture cleanup and direct-entry sanitization were repaired; normal capture
PASS11, injected post-inquiry failure retained CANCELLED/reserved0/unpublished,
and direct-entry hostile loopback overrides PASS1/31. Independent delta review
is recorded separately before publication. Original fixed-scope review findings stay in
W01_SHARED_REVIEW.md/W01_RUNTIME_REVIEW.md; fixes do not erase failed evidence.

### Current next actions

1. Completed bounded runtime delta re-review and actual guest1440/390 smoke; freeze reviewed
   code and record the exact integration SHA in the reused PR/checkpoint.
2. Publish one meaningful integration checkpoint, retaining original child SHAs in its
   reachable provenance. No production source/database/service change.
3. Delegate new fixed-SHA official security and browser tasks (not completed task keys).
   Share only the ignored request-local synthetic review credential path. Browser and
   service/API race scopes use disjoint new fixtures and serialize destructive recovery.
4. Integrate findings, rerun affected tests plus postintegration journey/regression, then
   issue W03/W04 FINAL_REPORT and Git-accessible execution package/screens.

W01 is runnable locally; W03 independent verification and W04 final integration are
pending. Remaining deadline to 2026-10-11 23:59 KST is about52h49m at10:10UTC October9.
October10 remains available for feature defects/admin/support completeness, October11
for independent recheck/final recovery/package and review-ready delivery. The early
implementation does not advance these unexecuted gates to PASS.


Lead actual authenticated1440 smoke: native UI login → selected product/departure and
people → real cart → acknowledged trial notice → UI submission → owner TEST_INQUIRY
screen; native admin API PATCH UNDER_REVIEW200 → owner page reload shows 검토 중,
no page errors; owner cancellation requested afterward. An initial probe sent an
unsupported `note` field and correctly got422; the valid field is `admin_note`.
This is a lead integration smoke, **not an admin UI or independent E2E PASS**.
The UI author guest smoke at1440/390 verified4regions/4themes, actual 제주 filter2
products, localized badges, no overflow/page errors; six portable screenshots and
measurement JSON are in deploy/travel-lab/evidence/screens. All remain explicitly
working-tree author checks until independent fixed-SHA verification.


W01_RUNTIME_REVIEW.md: bounded nonauthor delta/source review PASS after two P2
repairs, original23 hashes plus two explicit changed-file hashes. Reviewer ran
20 guards and21 PHP lints; live DB/race/recovery facts remain attributed to their
implementer/lead. Earlier harness author's baseline and their own workflow are
excluded from that approval. Official whole-product Validation remains pending.
Canonical input-order validator rerun before this publication again PASS89/noerrors
at the same pinned source/order; no work-order edit or source-validator bypass.

The publication preserves original four implementation commits and the preflight
review commit as reachable parents using an explicit provenance merge after reviewed
cherry-picks/repairs. Its strategy retains the already integrated tree; it does not
reapply stale child content or treat original hashes as the final review target.


## W03 fixed-source verification handoff — 2026-10-09 10:22 UTC

Published implementation/review target **28ada286c1c34606741bcfe4f9d12e06ac50af30**, reusable draft
PR https://github.com/raonslab2/g7/pull/2. GitHub remote branch and PR head matched;
Git ancestor checks prove all four original implementation SHAs and preflight751b
reachable in the published history. Canonical old-child status still retains its
Git-evidence blocker and no Validation receipt; local/remote reachability proof does
not alter those platform facts. Last provenance merge tree equals a07dc393 exactly.
Installed module/template runtime source/assets were byte-compared:160paths,0mismatch.

Three NEW official child Requests, attempt1 each, canonically **RUNNING** at10:21:45UTC:

| Task | Provider | Request | Own scope |
| --- | --- | --- | --- |
| w03-security | CLAUDE | req_e66ad2372e844e5683a7619a727b2682 | Nonauthor price/quantity/date/permissions/privacy/checkout/idempotency/capacity contracts; app API new fixtures |
| w03-browser | CODEX | req_76c856ee21a445ffa3dbe55838cd9e77 | Real390/1440 customer + nativeadmin/support journeys, new distinct synthetic users |
| w03-runtime | CODEX | req_b3b4d5373a384cfd8e0b7b8f55fb011f | Own-worktree package/guards/native testDB/rollback-restore/G7 regressions |

No prior task key regenerated or existing Request terminated. One rejected delegation
call created no child: expected_source_revision requires explicit placement, and deployed
source_binding checks the project's configured current upstream. That upstream is main,
while this quarantined integration branch is deliberately separate. Supported canonical
review_target pins 28ada286c1c34606741bcfe4f9d12e06ac50af30; each child must fetch/check out that exact
remote commit in its own worktree before review. Assigned default source and actual tested
review SHA are recorded separately; no global project/upstream/capacity change or source
acceptance bypass. Default source-binding equality is **not claimed**.

Security/browser use distinct ignored synthetic test auth files; only paths are supplied,
never secret values. Runtime child owns ONLY req81_travel_lab_test and may privately copy
the generated test env into its own ignored worktree files; never platform credentials.
It must not run account-rotating setup/env-loss or stop APPpreview during parallel journeys.
Independent fresh-empty install/env-loss/APPrestart need later safe coordination and are
NOT_RUN until executed. The three requests are ready scopes, no dependency-wait filler.

The loopback preview was transferred from the lead's foreground process to transient
request-owned **req81-travel-lab-preview.service**, User=ubuntu, same assigned worktree,
no public listener or production unit. /up200 and ActiveState=active confirmed. Its
private0600 log is ignored. This preserves testing across parent slot release; it is
neither an operational deployment nor a guaranteed permanent hosted preview. On resume,
check this exact unit and source first; never restart another service. Published package
and screenshots are the durable user-accessible result if the disposable checkout ends.

Parent source is frozen for reviews. Release parent slot using official wait, then resume
with selected child results, compare exact source/commits/findings, perform scoped repairs
and new attempts as needed, integrate and rerun postintegration gates. Rehydrate from the
published task branch, never assume ignored credentials/service/path survive. Native
conversation/canonical Request records retain the private auth paths. No whole-goal PASS,
main merge or production deployment at this handoff.
