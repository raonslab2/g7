# W04 atomic final child — read-only evidence intake

**Intake decision: ACCEPT_WITH_LIMITS for the bounded product evidence; canonical execution/publication remain separate.** The child is reported by the lead as terminal **FAILED**. Its actual local Git result and internally consistent evidence support measured closure of W04F-01/02 at the fixed installed candidate within the stated test window. Neither the terminal state alone nor the report's PASS labels establish a product/release verdict. No canonical Validation receipt or final publication proof was supplied to this intake.

## Identity, scope and Git truth

| Item | Observed fact |
|---|---|
| Official child | `req_ea58da0bbb7043999b64641fcfc32490`, task `w04-atomic-contention-final`, attempt1; terminal FAILED is lead-supplied canonical status. The cause is **NOT_ESTABLISHED** from the available source/evidence. |
| Child actual HEAD | `c38ee592b0bd7538fafd6fbd5d926700026b8a87`; clean worktree. Parent commit is exactly `fa5523175ac494cfbd13bbf89bf06b3ec91835a6`. |
| Fixed product target | `fa5523175ac494cfbd13bbf89bf06b3ec91835a6`, tree `fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c`. |
| Delivery diff |56 added paths; every path is the child report or `docs/symphony/w04-atomic-contention/**`. No product change in this commit. |
| Reachability at intake | Commit object exists locally on `refs/heads/verify/w04-atomic-contention-nonauthor`. It is **not** an ancestor of the lead's inspected HEAD `251f253b9d20a0e60b662c2886b49a34b6653ec7`. No containing remote-tracking ref was observed. Child report is not yet present in the lead checkout. Local-only child result is consumable, but not remotely published/integrated by this intake. |
| Intake owner | Native `/root/w03_ui_repairs`; only this report written. Child files read-only. No private handoff/env reads, tests/probe execution, HTTP/DB/config/service mutation, Git staging/commit/push or new agents. |

The original task key is terminal; this review does not regenerate it. Lead must preserve/import the existing Git evidence through authorized delivery and reconcile official status separately. A canonical failure must not be quietly relabelled completed, and an execution/collector failure must not erase bounded product measurements.

## Integrity and source/runtime binding

- Manifest54 package-file hashes all match; report hash also matches. Package plus report plus manifest account for the56 Git paths. SHA-256 of child report: `8b93c63dfbddbf677fca691131527af33bbe9de10c1511d9c84115e77058553f`; manifest: `553488662956afa6982cf394f7cdfcf686f7e8c0ee06a48dc91dafb8761f5ee3`.
- Captured provenance pins child and lead HEAD to the exact fa552317 target, with0 tracked lead modifications during that capture. Bundled module168/168 equals parent bundle. Installed module165/168 differs only in `AGENTS.md`, `README.md`, `docs/settings.md`; installed template153/154 differs only in README. The stale installed documentation is disclosed, not silently counted as complete parity.
- Captured21 core/framework comparison files all match; only15 selected vendor files were compared with the child dependency installation. This is not a whole-vendor parity claim.
- At this intake, the four critical child-source files below still equal both lead bundled and installed bytes. These are current **file reads**, not a new runtime/config/HTTP verification.
- Captured route cache has31 travel routes,0 old throttle aliases,26 new throttle occurrences and8 preserved numeric prefixes. Native reconstruction checks auth-before-throttle on19 routes; it is a route-cache/Kernel reconstruction, not19 freshly executed HTTP authorization tests. Admin permission ordering after throttle remains a disclosed prior behavior.
- Runtime DatabaseStore evidence is own native integer counter/timer rows in `g7_cache`, temporary owner admission rows in `g7_cache_locks`, native headers and exact counter conservation. Runtime private env values were not read; egress configuration is NOT_RUN. This does not reduce the actual own-counter SQL observation to a merely configured cache choice.

| Critical product file | SHA-256; child = lead bundle = installed at intake |
|---|---|
| `TravelThrottleRequests.php` | `aab02c6b01254dd62b7973ee5b0fbc68bb0afd052d49171ac19f3bd21e97e92d` |
| `InquiryService.php` | `17c969a752820487c062b97e67c2cf88d0c03160e42346075dc5060ee2e38ad8` |
| `src/routes/workflow.php` | `fb5e2dd56b9fcd595a4a9237db483dc6dc1856f5210355d27f03fcfbaf327cbf` |
| `src/routes/support.php` | `86529ee8eb4c566adc1fa6dea9bd2a14b36d15a5ed8b571360efdc2a0856877d` |

## Actual quota and admission records

The source uses real loopback HTTP with no forwarded IP, natural own counter expiry, no seed/reset/clock/quota/cache clear. The sequential attempt and two bursts below finish before one60s window expires; recorded DB remaining TTL is positive. The burst probe distinguishes a request sent **after a completed429** from responses reordered while controllers finish. Duplicated remaining headers under concurrency are not treated as counter loss.

| Recorded scenario | Directly checked sanitized evidence |
|---|---|
| Sequential member |600 requests200, remaining599→0 strictly by1;601st429, limit600/remaining0/Retry-After6;53.813s through601; DB counter600, TTL6s,0 own admission locks. |
|8 Lane /900 calls |600×200 +300×429;48.436s; issued900,0 busy/other errors/malformed429,0 admissions sent after completed429; DB600, TTL11s,0 locks. |
|4 Lane /800 calls |600×200 +200×429;44.713s; issued800,0 busy/other errors/malformed429,0 late admissions; DB600, TTL15s,0 locks. |
| Same-IP actors | Own member40 reads and other/admin first reads yield40/1/1 counters; guest delta1. This is own actor separation, not anonymous IP600 exhaustion. |
| Lock wait positive control | Own row barrier held6.103s; holder pending under one admission lock, then200. Two waiters503/Retry-After1 in3.095/3.062s, before barrier release. Counter1→2,0 locks afterwards. |
| Lease headroom | Barrier27.499s; holder503/Retry-After1 after27.517s, counter2→3,0 locks. One hit is consumed before the headroom rejection. Controller nonexecution is **source-inferred**, not directly instrumented by the probe. |
| Controller release scope | R1–R4 record0 own admission locks during the sustained controller barrier. Same-actor cart200 during the hold was measured inR1 only. |

These are strong bounded measured counter/admission results on a four-worker MariaDB10.11.14 lab. They do not prove infinite-stall safety, crash recovery by live kill, anonymous shared-IP exhaustion, larger worker counts or production capacity.8 client Lanes do not mean8 PHP workers,8 accounts or8 PCs. The30s lease/25s headroom is the implementation bound.

## Same-gap contention and transaction evidence

- Nine gap rounds:6 SQL-confirmed shared-gap cases,3 interleaved different-gap controls. Each records two pending HTTP responses until release,3.202–3.349s sustained sampled overlap,201/409, capacity1/reserved1 and deadlock delta0.
- R1–R4 additionally exercise last seat, same-member/same-key201+200 with the same inquiry ID, cancellation vs decline, and submit vs capacity reduction. Replay recovers200; changed body409; reservation/event checks are recorded. The source repair replaces an absent-key locking lookup with a non-locking lookup inside the submit transaction; UNIQUE constraint/retries remain.
- Engine lock graphs were unavailable (privilege1142/1227). PROCESSLIST statements containing FOR UPDATE are a **proxy**, not proof of each connection blocking on the same row. Particularly R2 may wait on the user row. Pending responses, first/last matching connection samples and barrier release establish practical overlap but not a complete wait-for graph.
- Server-wide deadlocks stayed6 across the rounds and idle controls; no attribution to a particular transaction is possible. This supports absence of the earlier observed cycle under these layouts, not mathematical deadlock impossibility. The possibility of an outer caller supplying a prior REPEATABLE-READ snapshot remains a source limitation; current HTTP store path does not wrap submit that way.

The bounded W04F-01 closure is supportable with those limits. No blanket “all concurrency safe” conclusion follows.

## Failures, changed harness criteria and P3 follow-ups

Raw inventory independently recounted: **59 PASS,6 FAIL,2 NOT_RUN,1 OBSERVED**. All original records remain. Six failures arise from own exhausted submit budget, an un-fresh create sequencing condition, unsound concurrent response criteria and an audit floor that included other own activity. They are diagnosed by later evidence, not deleted. Both throughput overruns are NOT_RUN because natural window rollover prevented a600-boundary exercise.

Delivered probes are final versions. Mid-run criteria changed; earlier records cannot be attributed byte-for-byte to those final scripts. The child explicitly discloses this, including corrected completed429 ordering, removed contiguous concurrent-header criterion, race setup and cleanup changes. Three inline read-only inspections have preserved source copies. This improves auditability but is weaker than version-pinning each probe invocation.

- **W04C-O1 P3 remains a bounded performance observation:** native lock polling sleeps inside four PHP workers; actor contention can occupy workers. Cross-actor latency is measured during load, while the supposed idle baseline and some host-load samples are unsaved. A causal regression/production throughput claim is not established.
- **W04C-O2 P3 remains unresolved:** unit fixture run1 failed21/2201 at worker-exit assertion; stderr was not retained. Four later runs passed21/2209, and domain154/2539 passed. The saved tail confirms failure but cannot explain its cause. Do not call this proven “host-load-only” or stable5/5 PASS. Capture exact worker stderr if it recurs; no unchanged broad rerun requested by this intake.
- External Scout indexing remains **CONTAINED**, not CLOSED; foreign attachments, broad legacy G7/browser/restart/installer gates and hosted CI/canonical Validation remain outside this review.

## Hygiene, cleanup and side-effect limits

Read-only pattern scan of44 evidence files found0 email, bearer-token literal, UUID or JWT patterns. This is a bounded public-evidence hygiene check, not a comparison with unknown private credentials or exhaustive secret certification. The probe reads0600 private handoffs inside0700 directories, checks target SHA/loopback/expiry and scrubs registered secrets before recording. No private file was opened by this intake.

SQL helper accepts fixed named parameterized queries, checks local APP schema/account, and scopes barriers to supplied own SKU/user/counter keys. Its token mutation modes check own user, token ID floor and creation time. Actual caller ownership comes from the scoped handoff plus probe parameters; it is not a general automatic ownership oracle. Own expiry/delete token operations and FOR UPDATE barriers are explicitly disclosed test effects. TEST schema was not a probe target.

Final cleanup evidence records:

-16 own inquiries terminal,17 departures inactive/reserved0, carts empty on run departures; product85 unpublished/hidden and native deletion retained409.
-20 own secret questions soft-deleted and owner lists clear; one admin answer was not separately deleted. Native relational records remain, rather than a DB wipe.
-Only three original handoff tokens remain; new login/expiry-probe tokens are gone.0 own admission lock rows;19 own lazy-expiring counter/timer rows remain intentionally. This is not “zero DB changes.”
-Order/payment/temp-order rows remain0, jobs/failed jobs/mail logs remain0, notifications2 and notification logs4 remain unchanged across measured snapshots. No live payment/booking was attempted. Counters plus adapter source support bounded nontransactional testing; no external network-capture proof or effective egress env read exists.
-A disclosed cleanup residue: native policy8 deletion returned200 while hidden product85 still references that ID; product GET200/policy GET404. This is an ecommerce referential observation on an inactive own fixture, not automatically a travel transaction defect or a fully restored pristine DB. Lead should retain the cleanup limitation in handoff; this intake makes no repair.

## Portable evidence pins and next responsibility

All paths below are relative to the existing child Git result and should be retained when the lead imports that result. This report does not copy or overwrite child evidence.

| Child evidence input | SHA-256 |
|---|---|
| `w04-atomic-contention/evidence/source-provenance.json` | `b8b15431487b1c44fcd3c693828aa9092a3801c15bcc984fb7a2f44044e2a890` |
| `w04-atomic-contention/evidence/raw/t1-identity_sequential-174924.json` | `a4ac1ad46028be55ecc97f96da007de3c6439ef4071833d29d51521fe86306bc` |
| `w04-atomic-contention/evidence/raw/t1-burst-other_member-8-900-174723.json` | `3167feb8bd3a90746724fefc7ba14cf00c5061053a7efb90ecbcde215e7fe4bc` |
| `w04-atomic-contention/evidence/raw/t1-burst-admin-4-800-174822.json` | `7a5fb844c22277e2bf4507b46b2bb8118efdaeba50e1b3bb9acbbaafda9fe066` |
| `w04-atomic-contention/evidence/raw/t1-admission-admin-174623.json` | `b366850e92b7231454c8be67226ba6516bf0d01f2da93c7d3c45af1f4c45bd23` |
| `w04-atomic-contention/evidence/raw/t3-gaps-173111.json` | `73eede858265e44159479c37aaa4d07eff0d66a8ea626bbaa595232b04568512` |
| `w04-atomic-contention/evidence/raw/t9-final-state.json` | `37f4a70ad467cb919a46882500fd5c5c45e8b4f83914d96e0e2cdfba918245d0` |
| `w04-atomic-contention/evidence/unit-atomic.txt` | `a8fec2c953e3121742ca87524cd827fd6dcab2e6eb149cc502d79387b9b3e6d0` |
| `w04-atomic-contention/c_common.py` | `00b0042bccae5b8b758bd0ada1fce0dbfdf46554580f7fabd22a2a2694336eac` |
| `w04-atomic-contention/db_guard.php` | `ec6fffb144cb38784b142d09e8cb19ce7787ae28321a81864f36705c6eb2ca9f` |
| `w04-atomic-contention/t1_throttle.py` | `13c4c14556ae1a161d2ff067f48d551e903e4b1f4765a9bf9871dcd165466b20` |
| `w04-atomic-contention/t3_workflow.py` | `994a2f1ff2e74bc475142478ec8a4abd72191e88925e6f5c8d36b69c46a2cabf` |

Lead owns evidence Git delivery/reachability, status reconciliation, the final integrated version and remaining gates. Do not recreate this terminal task just to recover already completed measurements. A changed runtime/source needs a separately scoped fixed-target check, while unchanged valid measurements can be retained with their original target and limitations.
