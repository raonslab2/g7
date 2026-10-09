# G7 Travel Lab delivery audit — IN_PROGRESS

This is the accumulated delivery audit, not a completion declaration. Updated
2026-10-09 18:30 UTC (October 10 KST). Deadline: October 11 23:59 KST /
14:59 UTC; approximately 44 hours remain. Customer-approved design/database:
NONE. The implementation is a RAON demonstration, not delivery of Lotte Tour's
110 customer screens.

## Identity and inspectable delivery

- Repository/project: [raonslab2/g7](https://github.com/raonslab2/g7), AgentOpt `g7`.
- Work: `work-20261009-g7-symphony-max-child-c7ae42d1`; parent
  `req_81ac33cac94046b9a2249cd14c0d00ba`.
- Original pilot/reissue remain identified in [SCORE.md](SCORE.md); no prior
  Request was cancelled, deleted or forcibly terminated.
- Reused integration branch `feat/g7-travel-lab-c7ae42d1`,
  [draft PR 2](https://github.com/raonslab2/g7/pull/2).
- Published runtime/product target:
  `fa5523175ac494cfbd13bbf89bf06b3ec91835a6`, tree
  `fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c`.
- Published documentation handoff before this audit:
  `251f253b9d20a0e60b662c2886b49a34b6653ec7`.
- New installer/bootstrap and MySQL fixture repairs are local, awaiting a reviewed
  published checkpoint and independent execution. They do not change the running
  catalog/workflow/UI source. Final integration SHA: PENDING.
- Main `6853f40d58acbf53a2f29cbb9dd422cc439047a9` was not merged or deployed.
  External automatic deployment behavior remains UNKNOWN. No production service
  restart or business-site DB modification is authorized by this result.

The Git-addressable [execution package](../../deploy/travel-lab/README.md),
[source contracts](SCORE.md), [wave history](WAVE_STATUS.md), and sanitized
browser evidence linked by their review reports are available through PR 2.
The active nonproduction preview is loopback `127.0.0.1:18871`; it is not a
publicly reachable hosted preview. Use the package on a dedicated isolated
MySQL instance: its fixed lab account/schema names must not share another team's
installation. No environment files, passwords, tokens or SQL dumps are published.

## Current implementation and independent evidence

| Required scope | Implemented behavior / evidence | Current delivery status |
| --- | --- | --- |
| Main, search, region/date/price filters, detail, departures/person selection | Own light template and real catalog API/native products/options; Korean keyword and mobile defects repaired. [Browser final](W04_BROWSER_FINAL.md) preserves original failures and later 390/1440 checks. | Working at published runtime target; latest native creation/browser review RUNNING |
| Native cart → persistent test inquiry → admin review/test acceptance → owner state/cancel | Real ecommerce calculation, native cart identity, inquiry/items/events snapshots and simulated reservation; no real orders/payments. Independent installed-kernel 37 requests and live browser journeys recorded. | PASS within those fixed-source checks; final integrated recipe smoke pending |
| Native product/options, travel metadata/departures, content management | Native admin plus travel extension adapters. Earlier creation blocker was not waived; new independent browser Request explicitly exercises creating its own category/policy/product/options and travel registration at both widths. | Current creation gate RUNNING |
| Notices/FAQ/private questions | Native board persistence/edit audit/admin answers, permission and search-driver fail-closed checks; no external notification. | Tested support ownership/throttles; attachment and external indexing boundaries below |
| Price/person/input/idempotency/capacity/auth | Four real SQL barriers, same-key and last-seat contention, tampering, KST date, user separation. [Atomic report](W04_ATOMIC_CONTENTION_FINAL.md). | Bounded nonauthor PASS, official Request terminal FAILED; not canonical Validation |
| Empty installation/vendor HTML/restart/env loss/recovery | [Native install report](W04_NATIVE_INSTALL_FINAL.md): actual empty TEST lifecycle, native HTML create/update, installed HTTP, stop/start and env-loss refusal, whole-schema restore. | CHANGES_REQUIRED: public initial cache bootstrap and standalone MySQL smoke failed; fixes pending independent rerun |
| Existing G7 regression | Native support, notifications, cart quantities, auth, installation/installer tests, plus locked SQLite module suite. | Actual commands/counts below; not a whole-platform or Windows PASS |
| Independent final integration and remote gates | Source/results independently compared and integrated on PR branch. | Final fixed revision and postintegration rerun PENDING; hosted CI/canonical Validation NOT_RUN |

## Verified commands and boundaries

Results retain their original source SHA; they must not be added into an invented
unique-test total. Full commands/environment/raw logs are in linked reports.

- At `fa552317`, parent isolated SQLite module suite: **154 tests / 2,539
  assertions PASS**; atomic/auth fixture scope **21 / 2,209 PASS**; isolation
  guard **27 PASS**; Pint and API metadata checks PASS. API metadata generation
  is not a live request to every endpoint.
- Canonical source-work-order validator at AI_GCS
  `10bc5eff5f777aa49760387778303063b0222129`: **89 documents, 0 errors PASS**.
  Publication reruns are recorded in [WORK_ORDER_VALIDATION.md](WORK_ORDER_VALIDATION.md).
- Independent native fresh install attempt 3, target `fa552317`:
  **19 successful lifecycle commands**, installed-kernel **37 HTTP requests**,
  real HTML create/update **2 checks**, separate restart/env-loss sequence
  **20 HTTP requests / 3 starts**. Its `LiveMysqlTest` still **FAIL 1 / 8
  assertions** because the fixture selected array cache; other kernel passes do
  not substitute for that failure. The earlier installer cache-table failure is
  also retained. Frontend build/type checks in that child were **NOT_RUN**.
- Native independent regressions at that target: support API **7/90**,
  provisioner **5/48**, notification **2/15**, secret-comment **8/17**, commerce
  cart quantity **15/39**, auth **63/186**, Installation **2/29** (IDV only),
  InstallerContext **8/8**. A transient restoration guard failure is retained;
  successful follow-up restoration is separate evidence.
- Independent atomic/quota target `fa552317`: sequential **600 accepted / 601st
  429**, 53.813 s; **8 concurrent HTTP lanes / 900 calls → 600 accepted + 300
  rejected**, 48.436 s; **4 lanes / 800 → 600 + 200**, 44.713 s. These HTTP
  load lanes are not official Requests, Provider lanes or separate PCs. Native
  database counters were exactly 600. Question reads **200 → 120**; creates
  **20 → 10 persisted**. Four row-barrier races plus six same-gap and three
  control gap races were checked; observed global deadlock delta was zero.
  Engine wait-graph inspection was **NOT_RUN** because required privileges were
  unavailable; connection/PROCESSLIST observations have narrower meaning.
- Actual TEST baseline for the latest installation review was **55 tables /
  104 rows**, restored with whole row and DDL digest
  `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`.
  Earlier 128-table / 639-row preservation is **NOT_PROVEN** by this run.
- Parent/native frontend checks passed in earlier waves with their pinned source;
  these are not a newly run final frontend/hosted CI gate.

## Requests, integration and capacity

Canonical observation at October 9 18:29 UTC: **24 cumulative official attempts**,
**18 COMPLETED / 4 FAILED / 1 INTERRUPTED / 1 RUNNING**, no queued child. This
does not mean 24 simultaneous executions or 18 verified products. Current open
Request: `w04-native-admin-create-final` attempt 1,
`req_c2e43e4188284ec78b8702e7e3e0d45a`, CODEX. Do not duplicate it.

The latest independent atomic Request
`req_ea58da0bbb7043999b64641fcfc32490` / `w04-atomic-contention-final` attempt 1,
CLAUDE is canonically **FAILED**, although it supplied a bounded report commit
`c38ee592b0bd7538fafd6fbd5d926700026b8a87`. Failure cause is not established.
The lead separately compared its Git tree, manifests and evidence; the report's
PASS claims do not overwrite Request state. Fresh install attempt 3
`req_caef46f3473043048b5b4bc2ec41eaea` is COMPLETED with original commits
`94c3f75b41ef44ca9456c742216382fc089a2f45` and
`0eb9e68f11e4e25f46fdfa1ed600b3c851951d14`, product CHANGES_REQUIRED.
Local lead intake commits: `be1c0d92`, `1a2d61f4`, `76cdfb19`; remote ancestry
and the final publication checkpoint are pending this audit update.

The full per-wave Request/attempt/source/review/PR ledger is in SCORE and
WAVE_STATUS. [CAPACITY.md](CAPACITY.md) distinguishes official parent/child,
Request-native agents, actual execution, queues and PC placement. Multiple
CODEX/CLAUDE children were actually observed concurrently; no two-lane artificial
cap was used. Configured provider admission limits and parent 10-active/50-total
limits are not account concurrency guarantees. Placements were CENTRAL/logical
1PC; offline PC nodes were not counted as running. Native root concurrency has
four slots including lead. At this checkpoint three reusable native agents
handle fixture diagnosis, independent intake review and completion-scope audit.
Exact shared account quota/token usage and other project's active usage remain
UNKNOWN; parent/child/native consumption is not double counted as account usage.

## Residual issues and honest release limits

- New public installer migration/bootstrap and real database-cache MySQL fixture
  repairs need a new fixed-source independent execution. Product/API/UI on the
  running preview remains `fa552317` during the current browser review.
- The atomic worker test failed once under load in the independent review,
  then passed four times. Original stderr was not retained there; its cause is
  UNKNOWN. A separate held-lock diagnostic reproduced missing translator/response
  dependencies in the worker harness, which were repaired. This does not
  retrospectively explain the old failure. Lead expanded fixture regression:
  **22 tests / 2,230 assertions PASS**, 14.870 s; Pint and 27 isolation guards PASS.
- P3 worker occupation: bounded admission waits can occupy the four preview PHP
  workers for up to about three seconds. No production capacity configuration
  or paid resource expansion was performed to hide that limit.
- External Scout bulk/manual/already-queued indexing of private questions is
  **contained, not closed**. Current supported database search fails closed on
  unsupported drivers. No external engine was connected or falsely verified.
- Attachment and editor-preview checks not actually executed remain NOT_RUN;
  full source/requirement audit is pending. Disabled private uploads are not
  positive proof of a foreign attachment access denial.
- Hosted Actions and check runs were **0**, required remote CI **NOT_RUN**.
  No canonical Validation receipt exists. Internal/native and official Request
  nonauthor verification does not waive those gates.
- Preview and review fixtures are synthetic. Actual payment, reservation,
  refund, flight/hotel providers and external mail/SMS are not connected.
  Original negative evidence, interrupted attempts and cleanup limitations are
  retained, not relabeled PASS.

Spring `work-20261009-spring-symphony-max-child-91bf5a6c` /
`req_8b1601dbd8a34a83b136a8204125d8bf` remains independent and unchanged.
RAON business site/product/member/contact/Page/production databases and services
were not modified. Only this marked lab and its explicit TEST fixtures were used.

## Next execution

1. Publish the independently reviewed installer/smoke and evidence checkpoint on
   PR 2, retaining original child commits and final source bindings.
2. Run a new independent TEST-only installer/recipe/MySQL smoke gate at that
   exact revision; restore its measured baseline before releasing TEST ownership.
3. Accept the running native customer/admin creation browser result, repair any
   concrete findings and perform applicable final integration regression.
4. Close supported requirement gaps, record the final fixed SHA/package/screens
   and all remaining required CI/Validation outcomes. Update this report to a
   factual completed delivery or explicit deadline handoff, never a plan-only PASS.
