# W04 repaired-install failed Request intake

Canonical Request `req_337b3638df9b4b958b0627e0b262a54a`, `w04-repaired-native-install` attempt1: **FAILED**, result ends waiting for remaining regressions. Tested checkout HEAD **`598a89fff702d51c1405f1a5952d95ab1d2651f4`**, tree **`9e00273bdf18d6a713343755aac54f44a9b032b4`**. No final commit/report was delivered; `docs/symphony/w04r/` is untracked. Source/evidence are untrusted child artifacts, not canonical Validation or final product PASS.

Reviewer `/root/w03_recovery_repairs` performed read-only intake. Only this parent-worktree report was written. No child files, DB state, process/service, environment, password, Git or delegation were changed. Private environment/options/dump/log bodies and actual contact values were not read or printed. Native scoped local administrative SQL was used solely for TEST connection/table names/counts, not rows.

## Immediate safety state

**TEST is unrestored and must not receive a new mutating suite/install yet.** Observation at **2026-10-09 15:09:46 UTC**:

| Boundary | Observed state |
| --- | --- |
| Live `req81_travel_lab_test` | **22 tables / 21 rows** |
| Starting validated snapshot | **55 tables / 104 rows**, digest `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e` |
| Difference by table names | 34 starting tables missing; one new table |
| TEST connections | 0 at observation |
| Original child native processes | None observed by `/proc` cwd/argv-path scans |
| Pending private snapshot | `w04-support-provision/BLOCKED` retained, no matching result/after evidence |

A concurrent short `git -C` process in the second scan belonged to the intake's read-only Git inspection, not the failed test. Request terminal state alone was not used as proof of process termination. Observations are time-bound and must be checked again by the recovery owner immediately before mutation; no kill/stop was performed. Other APP/browser/non-TEST repair scopes can continue. This is a TEST ownership block, not a global shutdown or machine audit.

Private retained directory, **path only**:

`/home/ubuntu/.agentopt-v2/workspaces/req_337b3638df9b4b958b0627e0b262a54a/storage/framework/testing/w04-support-provision/`

Contains BLOCKED, before.json, manifest.json, snapshot.sql, mysql.cnf and private native/dump logs. Directory0700; metadata, snapshot and options0600. Snapshot size125,166 bytes. Manifest records source598a89/tree9e002, successful dump, stable measurement, zero competing TEST connections, recorded dump hash `5ef6d31adabf2ec8f4508c5e1dc6acda9dcb48f5ccd0d6ccb8b9e5493bf672ef`. BLOCKED/manifest timestamps14:05:16 UTC; private native.log has277 bytes and last changed14:05:17, with no child exit metadata. Dump/options/log bodies were not read; the dump hash was **not recomputed** in this intake. Recovery owner must validate it before import.

## Completed evidence, distinct from unfinished recovery

There are **72 JSON artifacts** under untracked `docs/symphony/w04r/evidence/`. No email/phone/bearer/private-key/raw INSERT/REPLACE value patterns were identified in these JSON blobs. That scan does not certify private files for publication. A source/helper/evidence inventory over the current untracked w04r tree has SHA-256 **`1f0798f50f4084b73d629eb017f51a87aec0fcdd1a3ffbaec53bd2911fead3d8`** (sorted path/NUL/file hash/newline); it is a disposable artifact pin, not Git delivery.

The recorded completed fresh stage has **18 native commands, each exit0**, summed88.357s. Empty/default-install/native-registration records exist. Actual installed origin evidence has31 travel APIs and nine module/provider/service/repository/listener/template origin hashes matching target598a89 product Git blobs. ModuleManager, Module model and repository bytes likewise equal that fixed Git target; no tracked child source diff was observed.

Native missing/corrupt ecommerce bundle probes each record exit1 before module registration/active publication, exact starting58-table455-row digest preservation, original bundle restoration and clean bundled source. These are bounded negative probes, not current whole TEST restoration.

Installed dependency evidence records native module installation before activation/sample/support, real HTMLPurifier4.19.0 and native vendor autoload, exact bundle/lock identity and dependency-only checks exit0. Actual native ProductService HTML create/update **two synthetic products** in ko/en records allowed-markup preservation, script/event/JavaScript URL removal, persisted HTML mode/price and zero direct SQL writes; its child exit0/1.310s. Runtime shipping reference rows are reported separately, not inferred from product-route navigation.

Installed HTTP child exit0/20.071s; **37 kernel requests**, all recorded statuses equal expectations, **five real native password logins**, ordinary non-super admin, server price24,000, same inquiry replay, capacity returned after cancellation, private question/native answer and zero order/payment/notification counts. This is kernel integration, not browser/mobile UI or server restart. The recorded real fallback probe expects child1, unchanged catch/finally and full112-table1,385-row equality; its product source hash remains `9e65fd5f1440fc827aef3eb4c4e2fa2dc51608d15d9d7b34d0258fae493fe62e`. Full112 inventories are not separately committed, so this intake reconciles the probe record/harness boundary rather than independently recomputing that particular in-memory equality.

Four completed public before/after pairs—attempt1-r-fresh, r-fresh2, mysql-workflow and support-api—were independently compared as entire objects: all equal55/104 and the digest above. Their successful historical restorations **do not** prove restoration after the later support-provision interruption.

Completed native regression-progress contains only:

| Batch | Tests / assertions | Native exit | Restoration evidence |
| --- | --- | ---: | --- |
| mysql-workflow | 1 / 31 | 0 | Exact55/104 |
| support-api | 7 / 90 | 0 | Exact55/104 |
| Completed subtotal | **8 / 121** | — | — |
| support-provision | No final count/exit | NOT_PROVEN | Before+snapshot only; BLOCKED and partial live schema |
| Remaining regressions | NOT_RUN / unproven | — | — |

Do not reuse original prior111/463 or add them to this partial attempt. No final native test completion, full regression PASS or final recovery is evidenced. Earlier128-table639-row preservation remains NOT_PROVEN; this attempt's actual intake/current recovery target is the observed55/104 baseline.

## New metadata contract finding — W04-I02 P2

`w04r/evidence/install/dependency-gate.json` records **registered_vendor_mode=auto** despite explicit bundled installation and **persisted_vendor_mode_matches_request=false**, while its broad status says PASS. Native registration evidence subsequently records auto for all four modules. Keep the successful dependency-loading facts, but classify the mode-persistence contract as **FAIL / CHANGES_REQUIRED**, not full repair PASS.

Readonly cause: fixed598a89 `ModuleManager` passes resolved vendor_mode into repository registration, but `app/Models/Module.php` fillable list omits vendor_mode. `ModuleRepository::updateOrCreate()` delegates to Eloquent mass assignment; the migration's column default is auto. Previous pure gate tests captured attributes before mocked persistence and therefore could not detect this native persistence loss. Model hash `1b286fe363fa2f237ed2f10a2f5aae32dd56ea1481061ad3eebeb87e3af7ad75`, repository hash `b0a684863e4700d2a7b6091e87b1a282d27aa53253a35d7444c385014f3ed0de`, ModuleManager hash `bcd279e3be09f5c43b117aa8fec90e64cc1b6ba06ce7a47a7683ca19ef4455ed` match the tested target.

Lead-owned minimal follow-up: repair native model persistence and test actual model/repository registration, then pin a new source. This intake changes no implementation and does not broadly certify plugin mode persistence.

## Safe recovery and next attempt

1. Keep this Request FAILED and retain the original private snapshot/BLOCKED and all partial/negative artifacts. Do not cancel/delete it, overwrite its backup, use setup/password rotation or infer rollback from lack of processes.
2. Assign one authorized recovery owner through the normal same-project handoff/new official attempt. Recheck TEST process/connection exclusivity and source/schema ownership. Preserve private source snapshot+manifest+full before inventory through an approved private handoff into the new owner's own workspace; never publish dumps/options. Do not write another Request's worktree.
3. Validate private file types/modes and recorded dump hash **before** destructive action. Restore only the allowlisted TEST schema via guarded native wipe/import, then compare the entire55-table104-row row/DDL inventory/digest with the recorded before object. Import exit/totals alone are insufficient. On uncertainty retain private backup/BLOCKED and report failure. Preserve failed owner originals; clean only the recovery owner's verified disposable copy.
4. After exact TEST recovery, integrate the mode-persistence correction, publish a new fixed SHA and perform a new official repaired-install attempt with actual HTML product create/update, mode equality, runtime/kernel/native regressions and whole-schema recovery. Preserve attempt1's FAILED/partial8-121 and successful bounded facts; no retry count is a new independent functional case.

No recovery command, new attempt, process termination, DB write or publication was executed by this intake reviewer. The lead owns those next actions, normal admission and continued work on other ready scopes.

Lead handoff after these observations: baseline-only recovery Request **`req_4be0e82d6e2e4996bf501f0aef20aa28` QUEUED** at598a89, authorized private failed-snapshot read-only handoff, TEST-only restore and exact55/104 proof. This is an assignment state, not execution/restoration PASS. Normal fresh installation remains deferred until native mode persistence is repaired and the recovery owner releases TEST.
