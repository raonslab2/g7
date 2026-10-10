# G7 Travel Lab delivery audit — IN_PROGRESS

2026-10-10 범위 정정과 현재 실행 검토본은 [BENCHMARK_REVIEW.md](BENCHMARK_REVIEW.md)에 있습니다.
아래는 원 시점별 검증 기록이며 최신 인수 결과는 문서 끝의 scope correction을 참조합니다.

Current audit: **2026-10-09 21:15 UTC / October 10 06:15 KST**. Deadline:
October 11 23:59 KST /14:59 UTC, approximately **41h44m** remaining; review
October 12 KST. This records an executable RAON demonstration and its remaining
gates, not a completion declaration or Lotte Tour 110-screen delivery.
Customer-approved design/database: **NONE**. Earlier negative reports remain
unchanged; each result below belongs to its stated source and environment.

## Identity, Git and runnable delivery

- Repository/project: [raonslab2/g7](https://github.com/raonslab2/g7), AgentOpt `g7`.
  Work `work-20261009-g7-symphony-max-child-c7ae42d1`; parent
  `req_81ac33cac94046b9a2249cd14c0d00ba`. Pilot/reissue lineage is in
  [INHERITANCE.md](INHERITANCE.md) and [SCORE.md](SCORE.md).
- Existing branch `feat/g7-travel-lab-c7ae42d1` and
  [draft PR 2](https://github.com/raonslab2/g7/pull/2) are reused. Latest published
  campaign activation target is `31a18318f91dde34a9c75eaaac65ae1d434b7bc3`, tree
  `c0984e682a77153753edcde318a6d44b6973b1c0`. Installer independently tested
  `d059d735cd17ed3c88dd27cca5a9f83f00042011`, tree
  `6dc9381b1d50196c81867ef3f9e31ca86611f410`.
- Current **local** repair commit is
  `0b446a608aa9d76c1e046115d8ea2805a1dd92f2`, tree
  `10ba36318652b96dc7dc769ec8405381cbded1f8`. Its evidence, production asset and
  current audit await the next reviewed publication/fixed integration target.
  This local revision is not reported as remotely integrated or final validated.
- Main `6853f40d58acbf53a2f29cbb9dd422cc439047a9` remains unchanged. No production
  merge/deployment occurred. Automatic production deployment behavior remains
  UNKNOWN; the integration branch preserves the reviewable result.

The Git-addressable [isolated execution package](../../deploy/travel-lab/README.md),
[source contracts](SCORE.md), [wave history](WAVE_STATUS.md), and sanitized screen
artifacts in linked browser reports are reviewable through the existing PR.
The nonproduction preview is loopback `127.0.0.1:18871`, **not a public hosted
preview**. The local unit is ACTIVE, MainPID185050, Userubuntu, assigned worktree,
KillModecontrol-group observation recorded by lead; no business service restart.
Use a dedicated isolated MySQL instance for reproduction; fixed lab account/schema
names must not collide with another installation. Private env/token/dump files
are not Git deliverables. Full public setup/account provisioning/wizard remains
NOT_RUN; bounded installed-runtime checks do not constitute whole-recipe PASS.

## Required behavior and current evidence

| Requirement | Implemented / independently observed | Current limit |
| --- | --- | --- |
| Main/search/category/theme/list/detail/date/person | Own light travel UI, original repo-native assets, native commerce product/option identity and prices, real region/date/price/Korean search and availability APIs;390/1440 browsing and native category/policy/product/option/travel registration executed in prior final browser reviews. | Original browser failures remain in their reports; full historic scenario cross-product and unrelated culture/city/filter bounds are NOT_RUN in the newest campaign review. |
| Cart → test inquiry → admin → owner | Native cart/calculation; persistent inquiries/items/events, simulated capacity and explicit allowed states. Latest campaign browser observes quantity2→3→2, server13700×2=27400, inquiry201, admin review/test acceptance or decline, owner requery/relogin and cancellation; response-loss replay returns same key/ID200. | API snapshots are not SQL concurrency proof; final repaired-core integration/persistence recheck is pending. TEST_ACCEPTED is never a real booking confirmation. |
| Native admin/products/departures | Native ecommerce admin plus travel metadata/departure adapters, preserved identity/price contracts. Actual creation flows and rapid-save repair replay executed at their fixed sources. | Original desktop pointer cause remains UNKNOWN; later bounded scrolling/keyboard/pointer successes do not explain every historic failure. |
| Notices/FAQ/private questions/attachments | Native Board persistence, edit audit, admin answers and ownership checks. Actual text/image bytes, foreign/guest unsigned denials and repaired nonimage400 observed atfc54. | Signed previews are bearer capabilities; owner download is native admin-only. Soft deletion retains inaccessible physical files. External Scout indexing remains contained, not closed; no external engine connected. |
| Persistent campaigns/Page content | Two fixed native Page slots, native editor/create/edit/version restore/publish, exact registry, strict published-only public projection including admin, safe customer PageBody and real catalog filters. Actual390/1440 editor/version/draft/alias/customer flows at31a. | **Original overall browser FAIL:** successful admin Retry200 keeps old error/zero titles. Repaired runtime browser result NOT_RUN. Scoped read-only/self UI, zero-matching catalog campaign and served SEO body/cache semantics remain separate unclosed checks. |
| Validation/concurrency/recovery | Native price/count/auth/idempotency/KST/capacity controls; earlier independent real contention/strict quotas. Latestd059 native installation, HTML dependency, KernelHTTP, migration and exact baseline recovery observed. | Whole-product fixed integration, public recipe, canonical Validation and hosted CI are NOT_RUN; no release PASS. |

No real payment/order/reservation/refund, supplier flight/hotel, external mail/SMS
is connected. Customer assets/internal Lotte administration were not copied or
claimed observed. Public-reference observations and inaccessible internals are
separated in [REFERENCE.md](REFERENCE.md).

## Latest campaign verification and repair

Both official campaign verifiers are **COMPLETED**, with bounded public evidence
accepted by nonauthor native intakes. A Request completion is not product PASS.

- Installer `req_812d0334c2064f5d88339114e7954235`, original evidence
  `2df00960492992afab2446a805fb45ea2b2962d0`, tested **d059**. The
  [installation intake](W04_CAMPAIGN_INSTALL_INTAKE.md) compares213 public paths,
  43 source pins and12 installed origin hashes. Native installed HTMLPurifier4.19.0
  product create/update2; installed journey37 KernelHTTP; campaign31 KernelHTTP
  and93 named checks; real physical SEO cache invalidation and own18879 stop/start
  observed. Those physical cache checks are not served-browser SEO proof.
- Installer regressions atd059: unchanged LiveMysqlTest **1/34 PASS**, Page
  **123/375 PASS**, Board **8/17 PASS**, core PermissionHelperScope **11/14 PASS**.
  Native migration actual112→111→112; populated rollback/forced fallback NOT_RUN.
  Env-loss was **GUARD_ONLY**, not wizard recovery. Frontend20/20 jsdom used
  DOMPurify3.4.14 matching the template lock, but root Vitest4.1.8 differs from
  template lock4.1.11; this is not a whole locked-template dependency replay.
- Browser `req_fc51a817f76d40eca856b390222911fc`, original final evidence
  `ca21cb7fc276bde3b373ba01a5c18c7448973d3a`, tested **31a**. The
  [browser intake](W04_CAMPAIGN_BROWSER_INTAKE.md) accepts177 public paths,
  175 manifest hashes and4478 selected source matches. Actual62 browser contexts,
  390/1440, pageerrors0, observed external connections0;81 invalid native admin
  media attempts intercepted. Customer PageBody removes unsafe descendants/attrs;
  native Page storage remains raw, so backend Page HTMLPurifier is NOT_PROVEN.
- Original [browser FAIL](W04_CAMPAIGN_BROWSER_FINAL.md) is preserved: native
  admin Page-list200/two rows after Retry leaves error/zero titles at both widths.
  Successful native Page and transaction/support segments do not erase it.
  Original47 own tokens return401; supplied3 remain200. Original Page7/8 content,
  mode, slug, publication and SEO properties restored; retained version histories
  11/9 mean no pristine whole-row/DB claim. Eight unsafe initial PNGs excluded;
  105 safe PNGs retained, quarantine private.
- **Repair0b446:** TemplateApp refetch clears only its recovered source error after
  actual success and retains current errors/payload on failure. Source SHA256
  `62e89b9dedb93c932a92e1b01944cffb11e88cf0c5cd5a79dc41ca8835f5b76e`.
  [Nonauthor review](W04_CAMPAIGN_RETRY_SOURCE_REVIEW.md): **12/12 PASS**, no skipped,
  actual focused jsdom rerun. Author related nine-file result: **158 PASS /1 existing
  SKIP**, including those12; counts are not added. Transport is mocked, not E2E.
- Lead official production core build **21:01:32 UTC exit0**, served engine SHA256
  `738ee97c6eebc33bc75d29f24397daabb54f124bede254689ca68a18fdb64a0b`.
  ActionDispatcher/Board/TravelIIFE unchanged. Actual repaired390/1440 native
  browser PASS **NOT_RUN**; new fixed published review target is forthcoming.

Default-Page correction is a **RAON internal design clarification**, not a user
waiver/customer approval: the original user never required zeroTOTALPages. Our
old campaign contract was overbroad. Keep the unchanged native PageSeeder's six
basic/legal sample documents; default travel campaign Pages remain **zero**.
Only explicit guarded actor-backed provisioning creates missing campaign slots,
preserving existing edits. Original zero-total-Pages FAIL/exit255 remains intact.
Do not strip native defaults to make that internal assumption pass.

## Restoration, historical negatives and source boundaries

Latest independently measured TEST restoration/release: **20:02:30 UTC**, **55 tables /
104 rows**, all row/DDL/object inventories equal starting baseline, whole digest
`ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`.
Recorded release has own connections/processes/handles0 and18879closed; this is
historical measured release, not a new liveness query. A new TEST owner must
independently remeasure/snapshot before writes. Earlier128/639 is NOT_PROVEN here.

The following remain historical, pinned evidence rather than current failures
silently converted to PASS or tests summed across revisions:

- W03 reviews at28ada: Korean search, retry/idempotency, native delete-before-FK,
  date/throttle/privacy and fallback-recovery findings; repairs/rechecks retain
  fail-first evidence and their own fixed targets.
- Fresh attempt failures/interruption, missing bundled HTMLPurifier, first-install
  database-cache bootstrap failure and array-cache LiveMysql failure atfa552;
  later unchanged repaired installation at992 andd059 is separate evidence.
- Canonical atomic Request `req_ea58da0bbb7043999b64641fcfc32490` FAILED although
  originalc38ee592 supplied bounded evidence. Atfa552:600/601 sequential quota;
  8HTTP lanes/900→600+300 and4/800→600+200, database counters600, real row-barrier
  and same-gap controls. These load lanes are not Request/Provider/PC lanes.
  Engine wait graph NOT_RUN; global deadlock observations have narrower scope.
- Earlier worker failure under load had unretained stderr/causeUNKNOWN; repaired
  fixture regressions do not retrospectively explain it. Admission waits can
  occupy preview workers up to about3s; no paid capacity/config increase.
- Parentfa552 SQLite154/2539, atomic/auth21/2209 and isolation27; later campaign
  backend175/2936, core binding546, prior browser/attachment results stay attached
  to their own source/command reports. They are not a unique final test total.
- Native Page first gateFAIL, reused-Kernel login500, wrong initial rollback,
  transient Board/migration restore guards, collector exit1, dependency/harness
  failures and rejected PNGs remain in original campaign reports. Final exact
  restore resolves recovery, not every release gate.

## Requests, capacity and remaining gates

At21:15 UTC, canonical **30 attempts =24 COMPLETED /5 FAILED /1 INTERRUPTED /
0 RUNNING**. Both campaign scopes are terminal; evidence acceptance does not
rewrite their product decisions. Original review objects are locally retained
for ancestry-preserving publication of the next checkpoint. No completed task
key regenerated; no previous Request cancelled/deleted/forcibly terminated.

[CAPACITY.md](CAPACITY.md) records official parent/child versus native agents,
actual execution/queues and PCs. Multiple CODEX/CLAUDE children actually ran
concurrently; no artificial two-lane cap. Catalog21:14 unchanged7f77/2ca:
parent10 active/50 cumulative, configured CODEX8/CLAUDE3 admission, not available
account slots or guaranteed actual streams. CENTRAL placement observed1PC;
offline nodes not execution. Parent native capacity4 including lead. Shared
account quota/token usage and Spring activity are UNKNOWN, not double counted.

Lead prepared only new synthetic Page readers57/58 with roles13/14, native
NULL unrestricted-read versus self-read. Existing actors/roles unchanged. Wrong
scope input created empty own scratch role12, subsequently removed. Wrong logout
alias returned404; native AuthService removed only own tokens569/570/571,
remaining0. Exact old-token HTTP401 is **NOT_RUN** because plaintext was discarded;
this is not a successful HTTP logout check. Supplied review tokens preserved.

Next two official scopes — independent repaired browser/scoped-Page UI and
isolated TEST transaction/persistence — are **ready but not assigned/running** at
this cutoff. Lead must first publish/bind the reviewed fixed source/asset, then
execute those scopes, inspect results, repair defects, and perform integration
regression at the same version. No placeholder Requests consume slots.

Latest repository observation: hosted Actions/check runs **0**, no workflows,
rulesets`[]`, main unprotected. Hosted CI and canonical Validation are **NOT_RUN**,
not waived; no canonical Validation receipt. Work-order validator previously
passed89 documents/0 errors atAI_GCS10bc5eff; lead must record the applicable
publication rerun in [WORK_ORDER_VALIDATION.md](WORK_ORDER_VALIDATION.md).
Final fixed integration SHA, verified public reproduction and remaining required
checks must be recorded before marking complete; deadline residuals must be
handed off explicitly if still open.

Spring `work-20261009-spring-symphony-max-child-91bf5a6c` /
`req_8b1601dbd8a34a83b136a8204125d8bf` is maintained independently. Existing RAON
business/product/member/contact/Page/production DB and services remain outside
this isolated lab; neither project was arbitrarily cancelled.


## Published Retry candidate and final parallel dispatch — 2026-10-09 21:22:21 UTC

Fixed product/evidence integration target **5783e6ba124061bdfae639cdaf9b1c14a83cdf03**, tree **361f9a142572f8a6c0c28326c461a233a92aec4e**, pushed to reused branch/PR2. Original2df009/ca21cb7 are actual reachable ancestors, not just copied reports. Activation SHA256 manifest matches all three published files; canonical work-order89/errors0 and diffcheck PASS. Exact-target Actions/checkruns0/0 NOT_RUN. Main remains6853. This dispatch-record checkpoint changes documentation only and does not change either independent review target or any installed/served runtime source/assets.

New canonical32nd cumulative attempts: CLAUDE **req_4d7d64bbb8754974867adc0d9edbe971**, task w04-campaign-retry-browser-closure attempt1, returnedQUEUED on creation; CODEX **req_95d0024e7b144a5bb903cc7daad1fb5f**, task w04-final-contract-persistence attempt1, returnedQUEUED on creation. They are independently ready APP browser/exclusiveTEST scopes at exact5783, not regenerated completed keys or waiting placeholders. Actual subsequent RUNNING/queue/modelstream counts are not inferred. Three parent native followups completed bounded source-derived contract/docs/activation intake; internal native usage is part of same provider accounts, not PCs. Global/Spring usage and quotas UNKNOWN; no capacity changes.

APP is frozen for browser source/runtime/env/cache/service parity; own private minimum handoff source5783/tree361f/expiresOctober10 01:00UTC, five synthetic actors,0600/0700. TEST verifier independently measures/backups before writes and owns only TEST/own18880 service. Parent preliminary catalog query per_page100 returned422 (caller bound exceeded48); valid48 read returned nature IDs1/3 andwellness2/4. Only parent synthetic nature published flags1/3 may be temporarily changed under explicit snapshot/restoration window for real empty-campaign testing, no transaction test during it; no foreign rows or price/stock/capacity edits. These are preflight observations, not independent browserPASS.

Next: officialwait yields parent slot, automatic resume with results, compare safe Git evidence/original failures, repair only observed issues, and produce final package/source integration/revalidation. Deadline stillOctober11 14:59UTC; approximately41h37m remain. No user action required.
## Current review entry — October 10 scope correction

사용자 검토본의 현재 진입점은 [BENCHMARK_REVIEW.md](BENCHMARK_REVIEW.md)입니다.
이번 범위는 롯데관광 공개 홈페이지 벤치마킹과 RAON 자체 여행앱입니다.
고객 Figma·DB·소스 부재는 BLOCKED 사유가 아닙니다. 이전 진행 보고와
실패 기록은 해당 시점의 원본으로 유지합니다.
검토 source5783e6ba와 기존PR head888f6b2d의 제품 소스는 동일합니다.
마지막 독립 원본3f16c7d5/e4c736ff를 변경 없이 ancestry로 인수했습니다.
Retry/Page권한/빈기획전, native14행 계약/MySQL4barrier/재시작영속/전체복원
PASS를 인수했으며, 현재 loopback설치4507파일/served5자산의 동일성을 확인했습니다.
추가390/1440 public browser10/10 및 동일context관리자 history18/18 PASS;
이전 간헐 메뉴FAIL은 유지하고 현재 재현0으로 한정합니다.
새 checkout domain175tests/2936assertions PASS, isolation27 PASS.
정식 과거Request 조회401·활성총괄UNKNOWN에 따라 중복 구현·DB/service변경 없이
비충돌 인계만 연결합니다. 공식Validation/hostedCI NOT_RUN,
main/운영HOLD, Spring merged성과 보존. 현재 화면·실행/SSH접속·남는 연결조치는
검토본 문서에서 확인할 수 있습니다. 제품 전체 release완료는 주장하지 않습니다.
