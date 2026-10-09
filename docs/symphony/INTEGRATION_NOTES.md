# Ready-result handoff before W01 integration

Remote W00 checkpoint: [draft PR2](https://github.com/raonslab2/g7/pull/2), branch `feat/g7-travel-lab-c7ae42d1`, SHA `332b5ddfd7db0fabda159d8a0f30af4b4ccab15c`. This note is a local Git handoff until the next meaningful implementation publication; it does not change the published W00 head.

## Canonical results received

| Task / Request | State at 2026-10-09 09:03:51 UTC | Source result / self-test scope |
| --- | --- | --- |
| w02-workflow / req_418b2989a0d84e428c68a9afa77b1196 | COMPLETED | `6aaf80af9ad73fa44f642535b2de24b0d9b86a6c`,29 scoped files; author reports48 tests/373 assertions PASS using SQLite contract models and actual commerce services. Native subagents2 reported. Actual merged domain/MySQL concurrency NOT_RUN. |
| w02-support-admin / req_33a086f25eb74d288a2f541cf2f101ef | COMPLETED | `2c0e38765acccac000923711e9e271dad483cc49`,37 module files; author PHP18/layout13 PASS, manual registration/self-tests. No independent product PASS. Child-native count not returned in bounded summary. |
| w01-domain / req_42c3e3430d144efc847e7988bc8dee01 | RUNNING | No result SHA yet. |
| w01-ui / req_46df947e5b814808b5023ac2d3f235d0 | RUNNING | No result SHA yet. |

Both returned commits are present in the parent shared Git object database and inspected with `git show`; their changed paths are confined to their assigned new module scope. Canonical delivery currently reports `GIT_EVIDENCE_REVIEW_REQUIRED: unique_commit_or_reachability_unproven`. Existence/local author tests do not remove that gate or establish remote integration/official Validation. Lead must verify ancestry/scope, integrate and publish reviewed results before reporting reachable final source.

The native Git common directory is shared, so source can be consumed by SHA without editing another Request's worktree. On continuation first fetch the remote W00 branch/PR, then recover this committed handoff through native history if the local branch was recreated. Do not assume ignored `.env` or old workspace files remain. Never publish actual lab credentials.

## New independent work, actual allocation

Official `w01-contract-preflight`, attempt1, CLAUDE Request `req_e35bb0ef069a4e15bcb44ac8831d092e`, created QUEUED after two results became ready. Review target is workflow SHA `6aaf80af9ad73fa44f642535b2de24b0d9b86a6c`; support compatibility checked at separate SHA `2c0e38765acccac000923711e9e271dad483cc49`. This is preintegration review, not a single integrated fixed-head release or product Validation. Reviewer owns only its report; lead owns corrections.

Cumulative official attempts now5; last observed original children2 COMPLETED/2 RUNNING; new reviewer initial QUEUED (running placement not yet observed). No additional account/capacity/PC introduced. Parent natives all completed after runtime independent recheck; no queued message is counted as execution. Last host snapshot09:03:51 UTC load3.63/5.35/5.12, memory available11Gi. Exact shared Spring execution/usage remains UNKNOWN; its registered Request remains unchanged.

## Lead findings to resolve against actual domain/UI

These are source/contract differences, not an independent release verdict:

1. Workflow `TravelCartService::unavailableReason` compares quantity separately to option stock and capacity-reserved. Shared contract now requires effective stock ceiling minus existing reserved allocation. `WorkflowCartRepository` must lock actual option/product state as well as departure before final calculation/allocation; review actual code and concurrent admin stock edits. Preserve CartService/price authority and no real stock decrement for test inquiries.
2. Workflow service does not yet write complete calculation_snapshot or actor transition event. Parent-assigned integration migration/model/repository and transactional writes remain required; own the additional distinctly named files after domain schema lands.
3. Workflow fixtures/API documentation use lowercase enum backing values (`test_inquiry`,`under_review`,...). Initial SCORE/UI contract names are uppercase enum cases. Resolve one final actual backing-value contract with domain enum, adapt JSON/UI/docs/tests consistently; do not simply rename shipping/order states.
4. Support admin layouts assume `allowed_transitions`, reference/party/product flattened fields. Compare actual workflow Resource output before testing UI. A missing field can hide every state button while static mocked tests pass. Include server-derived transitions/abilities and coherent summary or align bindings to real nested items/contact.
5. Support catalog layout assumes OPEN/CLOSED/HIDDEN and reserved_count and mistakenly documents departure route id as ProductOption id. Canonical contract uses Departure.id, is_active and reserved. Align admin update FormRequest/JSON/DTO; do not silently PATCH the wrong resource. Accepted test inquiry remains cancellable, despite an outdated support-doc suggested terminal table.
6. Add provider repository bindings, exact inquiry owner/scope permission metadata, support read/update, command+hook listeners, backend workflow translations and frontend partial translations. api.php already requires catalog/workflow/support once. Read actual module provider before integration, avoid duplicate route prefixes.
7. Support boards are intentionally inactive; adapter serves notices/FAQs/owner questions while native public board/search paths must reject the private board. Explicit lab provisioning defaults off; configure only isolated environment and call `raonslab-travel_lab:support-provision --lab-confirm`. APIs may return503 until provisioning; root extension script must include this explicit safe phase after full artifacts land.
8. Inspect strict shipping policy (separate FREE policy, no fallback default), option replacement/deletion history guards and seeder rerun through real ecommerce services. Re-run own test results on real models/provider/MySQL; fixtures are not substitutes for actual migration/row-lock/restart proof.

Next: wait durably for domain and visitor UI results, integrate the four file-owned result commits, resolve these findings, run actual lab installation/API/DB and browser journey, publish one W01 implementation checkpoint on the existing PR, then bind independent final verification and postintegration tests to the exact source SHA. Keep prior coordinator UNKNOWN/quarantined output boundary until official historical handoff is resolved; no production changes.
