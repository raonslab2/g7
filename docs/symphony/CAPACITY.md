# Capacity observations

Work `work-20261009-g7-symphony-max-child-c7ae42d1`; parent `req_81ac33cac94046b9a2249cd14c0d00ba`.

## Contract and resource facts

2026-10-09 08:33 UTC catalog: AgentOpt runtime `97f2f93f-10bc5eff`, backend `97f2f93f5bcf2c5c37089474814165e80d0958f9`; Agent-Tools release `agent-tools-2.1.3+g7071d2c2ed91`. Actual deployed delegation.py read-only confirms MAX_ACTIVE=10, MAX_TOTAL=50, depth=1, SAME_PROJECT_ONLY. Active means nonterminal children including queued ones; cumulative attempts count toward 50. Provider catalog capacity CODEX=8, CLAUDE=3; this is configured admission capacity, not proof of available slots, accounts, quota, successful executions or independent PCs.

User authorization: CODEX one account + CLAUDE one account, no added paid accounts/capacity/config changes. Native tools support four parent-tree concurrent agents including lead. Parent and child/native usage share provider accounts: do not add their account usage as independent quotas. Account remaining token/billing limits and global concurrent request totals are **UNKNOWN**: this request's exposed canonical tools provide no account-usage/all-project-request report. Spring receipt confirms registered separate parent `req_8b1601dbd8a34a83b136a8204125d8bf`; exact live Spring child/native counts are UNKNOWN. Existing server admission performs resource sharing; no allocator or override is introduced.

Local node at 08:33 UTC: load1/5/15=0.60/0.61/0.80; memory total15Gi, used3.4Gi, available11Gi; swap used3.9Gi of8Gi. Snapshot is host-wide and includes other projects; process presence is not a task completion measurement. Short dependency installs performed locally with lockfiles, no resource/config changes. Off-host pc2/pc3 catalog entries are OFFLINE, G7 support BLOCKED; no remote PC execution claimed.

## Observed parallel execution ledger

| Observation UTC | Parent official | Official children (CODEX / CLAUDE) | Running / queued | Parent native agents | Verified placement |
| --- | --- | --- | --- | --- | --- |
| 08:33-08:35 | 1 lead | 0 / 0 | 0 / 0 | 3 parallel read-only intake agents, then completed | parent same workspace |
| 08:36 approx creation | 1 lead | 2 / 2 (4 cumulative attempts) | initial responses 0 / 4 | intake completing | canonical created Requests |
| 08:37 approx status | 1 lead | 2 / 2 | **4 RUNNING / 0 QUEUED** | 2 followup agents running; reference agent completed | all child execution_origin CENTRAL, verified, logical_pc=1PC |
| 08:46 host observation | parent active | no new official state poll | last canonical observation above | runtime implementer + W00 reviewer active; evidence investigator completed | host load1/5/15=5.46/5.21/3.17, available memory10Gi, used4.6Gi; no attribution to a specific project |
| 08:50:32 canonical checkpoint | 1 lead | 2 / 2, attempt1 each | **4 RUNNING / 0 QUEUED**, no results/evidence yet | runtime followup completed; contract re-review active; runtime/reference review message queued to completed native (not executing) | all4 CENTRAL verified logical_pc1PC; no remote PCs |
| Subsequent native lifecycle check | 1 lead | no new official poll | last canonical snapshot above | list_agents confirmed all3 native agents completed; runtime/reference review then explicitly activated via followup_task, 1 native active | Corrected prior assumption that queued send_message had started a completed agent; queued intent is not execution |

Times after 08:35 are approximate until next recorded tool timestamp. Official RUNNING is a canonical state observation, not successful execution or worker benchmark. The four children are req_42c3e3430d144efc847e7988bc8dee01 (domain), req_418b2989a0d84e428c68a9afa77b1196 (transaction), req_46df947e5b814808b5023ac2d3f235d0 (customer UI), req_33a086f25eb74d288a2f541cf2f101ef (support/admin). Child-native agents are UNKNOWN until child reports; do not count hidden internals as zero or as additional PCs. Exact aggregate simultaneous model execution/token consumption is UNKNOWN.

Four implementation children are independently ready, with disjoint source ownership and no dependency-wait placeholders. Domain/schema/auth declarations have one owner; lead owns shared routes/provider integration. Independent fixed-SHA review/browser roles follow ready code rather than occupying all slots waiting for implementation. Increase useful assignments when a genuinely independent scope/fixed revision becomes ready, within native/catalog limits. Reduce heavy builds only on measured memory/load/DB contention or observed quota/admission errors; record reason and re-expansion condition. Do not impose an artificial two-lane cap or fill numerical limits with duplicates.

Host load rose during parallel local/canonical work; memory remains available. Defer an additional broad test/build batch until the existing focused runtime check finishes; this is a build-load sequencing choice, not a two-provider execution cap. Completed baseline frontend build7.80s and focused56-test batch6.82s showed no failures. Independent reviews launch when their fixed implementation revision is ready; implementation test results are not release PASS.

## Update requirements

At meaningful WAVE checkpoints record actual child state and attempts, execution origin, observed native activity, load/memory, build/test duration/failures, queue/verification delay, fixed SHA/PR. Capacity configuration is not an operational load-test PASS. Use official wait once work is durably saved; after yielding end the native turn to release parent capacity, without shell sleeps/status polling.

## W01 returned results and repair concurrency

Canonical observation 2026-10-09 09:15:39 UTC: five official children COMPLETED (CODEX2, CLAUDE3), including independent preflight, cumulative5 attempts; no new official implementation Requests. Reported child-native counts: domain2, workflow2, preflight1; UI/support UNKNOWN. Counts are reported usage, not proof those five internal agents were concurrent or separate PCs. All source results remain untrusted pending lead verification, and the canonical Git-evidence blocker is preserved.

At 09:26 UTC all three parent native followups active concurrently with lead: workflow repair, UI/admin integration, runtime/live environment. Parent native tools cap4 including lead, fully used for ready disjoint work. Host load1/5/15=6.70/8.96/7.90; memory available10Gi of15Gi, swapused4.2Gi/8Gi. PHP focused batch45 tests24.16s had1 stale shipping-policy assertion; assertion updated to demand explicitKR/FREE/no-extra-fee. SQLite is not a MySQL concurrency benchmark. Local build/test jobs are sequenced only on readiness/DB collision; official independent verification will use the next fixed integrated commit. Spring remains registered; its live usage is UNKNOWN and not double-counted.

Catalog refreshed09:28UTC: same configuredCODEX8/CLAUDE3, max-active10/max-total50/depth1; off-host G7 nodes still OFFLINE/BLOCKED. Tool execution_source workspace head214b remains the server snapshot, whereas actual local Git includes scoped cherry-picks2114; lead uses git rev-parse and fixed new checkpoint as review truth. No capacity/settings/account increase.


At10:04-10:08UTC parent native followups again used all4slots including lead for
ready disjoint work: UI guest-browser check, nonauthor runtime delta review,
API-capture cleanup repair, lead native commerce regression/installation/publication.
Official children remain5 completed; no additional implementation Request was created.
The two next independent Request roles wait only for a verified fixed source checkpoint,
not as admission-slot placeholders. Native20guards and21lint passed; author guestbrowser
scope completed, runtime repair completed, delta review still active at this observation.
Host10:07UTC load6.98/6.51/6.88, memoryavailable10Gi/15Gi, swap4.4Gi/8Gi; no quota or
load/admission failure observed. Test DB mutations are sequenced to avoid suite collisions;
read-only guest browser and source review run concurrently. Actual configuredcatalog at
10:04 unchanged; PC2/PC3 unavailable, central-node-only, no separate PC count inferred.
