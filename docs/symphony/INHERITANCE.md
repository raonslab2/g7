# G7 Travel Lab inheritance and source evidence

Work: `work-20261009-g7-symphony-max-child-c7ae42d1`
Request: `req_81ac33cac94046b9a2249cd14c0d00ba`
Observed: 2026-10-09, approximately 08:30–08:40 UTC
G7 input source: `6853f40d58acbf53a2f29cbb9dd422cc439047a9`

## Inheritance and duplicate coordinator boundary

The canonical work-order source is private `raonslab2/ai_gcs_v2`, revision
`10bc5eff5f777aa49760387778303063b0222129`. The [prior pilot order](https://github.com/raonslab2/ai_gcs_v2/blob/10bc5eff5f777aa49760387778303063b0222129/work/orders/work-20261008-g7-travel-commerce-pilot-001.md)
remains present. Its creation commit is [7ccd51ad](https://github.com/raonslab2/ai_gcs_v2/commit/7ccd51adab4c3b7ce5101d698777d9e812e0f07a).
The [new order](https://github.com/raonslab2/ai_gcs_v2/blob/10bc5eff5f777aa49760387778303063b0222129/work/orders/work-20261009-g7-symphony-max-child-c7ae42d1.md)
defines this execution and preserves the previous lineage.

Read-only GitHub observations:

| Observation | Result | Scope |
| --- | --- | --- |
| Current G7 branches (`gh api repos/raonslab2/g7/branches?per_page=100`) | No travel branch found | Current visible branches only |
| G7 PRs (`gh pr list --state all --limit 40`) | Only unrelated open [PR #1](https://github.com/raonslab2/g7/pull/1) | All returned repository PRs |
| Commit/code search for `travel` in G7 | Empty results | GitHub search visibility/index, corroborated with local source/ref reads |
| Reissue path `work-20261008-g7-travel-reissue-8dbd6c2a.md` contents | HTTP 404 | Current canonical main |
| Commits filtered to that reissue path | Empty list | Canonical repository history exposed by GitHub |
| Prior live coordinator | **UNKNOWN** | No callable global Request/receipt lookup provided |

The lead's official catalog observation is `LATEST_50_OWN_RECEIPTS_SCOPED`.
Within that subset, the prior pilot has a DELETE receipt with `request_id: null`
and there is no reissue receipt. This is scoped parent-observed evidence, not
proof that a previous execution is absent or cancelled. The canonical
[work-order operations contract](https://github.com/raonslab2/ai_gcs_v2/blob/10bc5eff5f777aa49760387778303063b0222129/docs/work-orders/FORMAT_AND_OPERATIONS.md)
says cancel/delete receipts never cancel execution. Git source disappearance
also does not establish cancellation. No previous Request, receipt, source
order or worktree was cancelled, deleted or force-stopped by this investigation.

The separate Spring work remains preserved:
`work-20261009-spring-symphony-max-child-91bf5a6c`, parent-observed Request
`req_8b1601dbd8a34a83b136a8204125d8bf`. G7 owns only its assigned source;
Spring's completion is not a G7 dependency.

## Git publication and operations boundary

`gh repo view raonslab2/g7` confirms **PUBLIC**, default branch `main`,
observed head `6853f40d58acbf53a2f29cbb9dd422cc439047a9`.
GitHub main protection returns `Branch not protected` (HTTP 404); repository
rulesets, Actions workflows and webhook metadata return empty lists. Versioned
`deploy/systemd/*.service` and `deploy/aws/*.service` contain no Git pull/fetch
or source publication watcher. These facts establish no visible repository-owned
automatic deployment. Unmanaged external automation is **UNKNOWN**.

The existing [branch policy](https://github.com/raonslab2/g7/blob/6853f40d58acbf53a2f29cbb9dd422cc439047a9/docs/g7/UpstreamStrategy.md)
defines feature branches and verified integration to main. Use one travel branch
and one reusable PR with reviewed, tested checkpoints. Publish a fixed SHA when
official implementation/review children need the contract. Keep operational
deployment approval separate; preserve an integration branch if an unapproved
production deployment could follow main integration. Repository protection
absence does not waive the task's independent review and verification gates.

All new public travel assets must be synthetic or rights-cleared. Existing
`raonslab-product`, member, consultation, Page and production DB data remain
outside scope. The existing repository includes historical backup artifacts;
this investigation did not open/decrypt/copy them into travel evidence.

## Current source evidence pack

The following original files were read from the exact G7 input commit, not
inferred from search/RAG results. The excerpts establish implementation contracts;
they are not evidence of a running travel implementation.

| Original source path | Git blob at input SHA | Verified original-source fact and consequence |
| --- | --- | --- |
| `modules/_bundled/sirsoft-ecommerce/module.json` | `9881d403d3842979dbb845a559b1eb10d6a28ea4` | `"version": "1.2.1"`, `"g7_version": ">=7.0.10"`; ecommerce is a real existing dependency. |
| `modules/_bundled/sirsoft-ecommerce/AGENTS.md` | `fed048884ae612b46a0c488b2b0f34a29bad871a` | “금액은 계산기 하나만 지난다.” The documented `OrderCalculationService` is used for details, cart and checkout. Travel must consume that calculation contract. Visitor storefront UI belongs to a template; PG integration belongs to plugins. |
| `docs/backend/service-repository.md` | `d7974366344574ad5e683b8c38d3c70993c931c5` | `Controller → Request → Service → RepositoryInterface → Repository → Model`; inject repository interfaces and keep transaction/business work in Services. |
| `docs/backend/validation.md` | `9385ed2db8733ec0107e3a603a8c0095bea1d288` | “필수: FormRequest에서 검증”; use Custom Rules where necessary, not duplicated Service input validation. |
| `docs/backend/response-helper.md` | `76a9241449469710c46431b68bd367d4497b51a2` | “모든 API 응답은 ResponseHelper 사용”; `success($messageKey, $data)` puts the message first. |
| `docs/g7/UpstreamStrategy.md` | `2a1957c3783efa6510cf917a608d4b34c580c62a` | Feature branch, focused/broad regression and isolated DB rehearsal precede verified release/integration. |

The ecommerce agent guide documents the real order flow as temporary order →
PG round trip → order processing, with shipping/payment states. This supports
a separate persisted `TEST_INQUIRY` contract when a safe official no-payment
flow is not suitable. It does not prove feasibility of the travel adapter;
the assigned commerce owner must verify that against actual Services/API tests.

## Agent-Tools advisory sources and freshness limit

Read-only project-scoped source:
`raonslab2/agent-tools` local checked-out SHA
`c06ade67fcc12972275f21159d880aebfbc24cfb`.

| Source | Blob | Read finding |
| --- | --- | --- |
| `content/projects/GNUBOARD7/README.md` | `6f2ddb69adb3ce18a378c57e4ab78f6b22fde29a` | `console_ready_scaffold`, RAG not built; original source and command/browser evidence are authoritative. Explicit target-root binding is mandatory. |
| `content/projects/GNUBOARD7/validation/gnuboard7-validation-recipes.yaml` | `903dbba32cc495a6f7119e3749e22239da0717a7` | Source identity is pinned to `c9e93660`; several recipes touch existing production service names. They are stale/inapplicable as exact travel validation. |
| `content/common/workflows/evidence-pack/README.md` | `5812568b7c9ab5eca46ac41f9fc266030e67fc74` | “원본 파일 발췌 없는 Evidence Pack 금지.” Evidence must include read original-source excerpts. |
| `content/common/workflows/review-gate/README.md` | `d2a6e9c7dd16e83754f824c59b00d71074520625` | Project commands establish test/build gates; advisory checklist completion does not create operational authority. |

The project pack, rules and source map are useful locators. The actual current
G7 files above were re-read at `6853f40d`. No stale source-identity recipe was
run or claimed PASS, no RAG index/freshness result was fabricated, and no
production-service recipe was executed. Travel validation must bind the fixed
travel source SHA and isolated DB/runtime explicitly.
