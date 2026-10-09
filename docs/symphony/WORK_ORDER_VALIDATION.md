# Canonical work-order validation receipt

Work: `work-20261009-g7-symphony-max-child-c7ae42d1`
Request: `req_81ac33cac94046b9a2249cd14c0d00ba`
Execution date: 2026-10-09 UTC
Status: **PASS — canonical source document/tree validation only**

## Exact source and canonical validator identity

| Subject | Identity |
| --- | --- |
| Canonical repository | Private `raonslab2/ai_gcs_v2` |
| Git source revision | `10bc5eff5f777aa49760387778303063b0222129` |
| New order | [work/orders/work-20261009-g7-symphony-max-child-c7ae42d1.md](https://github.com/raonslab2/ai_gcs_v2/blob/10bc5eff5f777aa49760387778303063b0222129/work/orders/work-20261009-g7-symphony-max-child-c7ae42d1.md) |
| New order Git blob | `65c8b0a461d9c638ba7a281635203db485b278aa` |
| New order SHA-256 / bytes | `7b071415f55bc0bf8d1d3f5c0e8d557c18cd7368a3795c8035c1150d00899a33` / 16103 |
| Canonical validator wrapper | [scripts/validate-work-orders](https://github.com/raonslab2/ai_gcs_v2/blob/10bc5eff5f777aa49760387778303063b0222129/scripts/validate-work-orders) |
| Artifact owner/source commit | `raonslab2/agentOpt_v2`, `90c1807e2fd71561dc754b9ed450b25c17c6cb1f` |
| Immutable `canonical.zip` SHA-256 | `14be9a877ca02f7679509bfbcb0a6d9040cd4a777b7154a38df3f4f62fba7d41` |

The original metadata was read and hashes verified:

```yaml
format_version: 1
work_id: work-20261009-g7-symphony-max-child-c7ae42d1
project_id: g7
provider: CODEX
profile: default
```

The wrapper invokes `scripts/work_order_tool.py validate` with explicit root and
`work/order-contract.json`. That loader reads the pinned manifest/archive from
the selected Git revision and checks archive/member SHA-256 values before
importing the official `agentopt_v2.work_order_cli`. No substitute parser was used.

| Read original file at exact revision | SHA-256 |
| --- | --- |
| `scripts/validate-work-orders` | `9fd6aff38c3b7204562090ffc65dc6e4d6cee8606beba9931b77247dabe30942` |
| `scripts/work_order_tool.py` | `636b181b8e5f27b07708def3e5d3778fc2be13be29d1f068a4b8016038a4409f` |
| `work/order-contract.json` | `a976a04162e484aca875fafa19aad3d0b3bc9f7a7a8bdcd4f925f4d0bcdccb36` |
| `vendor/agentopt-work-orders/manifest.json` | `7211a5319d2bcc68c6911ce690978a024722aa79900df10dd470a8ba5823daca` |

## Commands, result and recovery

An isolated temporary read-only source clone was prepared. The existing shared
AI_GCS checkout was not changed. Authentication used existing Git tooling; no
platform configuration, credential file, database or bypass API was read.
Required paths were materialized from the exact commit so the validator could
read Git objects without waiting on lazy individual network fetches.

Equivalent reproduction commands, with a caller-selected temporary directory:

```bash
git clone --filter=blob:none --no-checkout --depth 1 \
  https://github.com/raonslab2/ai_gcs_v2.git "$EVIDENCE_DIR/source"
git -C "$EVIDENCE_DIR/source" fetch --depth 1 origin \
  10bc5eff5f777aa49760387778303063b0222129
git -C "$EVIDENCE_DIR/source" checkout \
  10bc5eff5f777aa49760387778303063b0222129 -- \
  scripts/validate-work-orders scripts/work_order_tool.py \
  work/order-contract.json vendor/agentopt-work-orders work/orders
"$EVIDENCE_DIR/source/scripts/validate-work-orders" \
  --revision 10bc5eff5f777aa49760387778303063b0222129 --json \
  work/orders/work-20261009-g7-symphony-max-child-c7ae42d1.md
```

The clone's fetched HEAD already equaled the required revision in this run, so
the explicit extra fetch above is included for reproducibility when main moves.
`--revision` validates the exact Git tree, not partial checkout contents.
The path argument additionally requires this particular new order to exist.

| Attempt | Command scope | Exit | Result |
| --- | --- | --- | --- |
| Initial filtered clone | Exact revision before all order blobs were materialized | 2 | `BASE_READ_FAILED: The base revision could not be read.` |
| Recovery | Materialize `work/orders` from the same revision, validate entire exact tree with `--json` | 0 | PASS, 89 documents, no errors |
| Required new-order identity | Same revision with explicit new-order path argument | 0 | PASS, 89 documents, no errors |

The original failure was an object-read/environment failure. The canonical
validator's generic diagnostic does not prove an invalid base or document.
No source content or validator pin was changed to obtain PASS.

Exact successful stdout, including its trailing newline:

```json
{"valid": true, "documents": 89, "errors": []}
```

Stdout SHA-256: `cab3312108129e799520c145858b1e15e638181c85ddd1baf4be5fa556d8c7fc`.

## Scope limits and next publication gate

- **PASS:** canonical full-tree format, declared project/provider/profile
  contract, unique work IDs and explicit new-order presence at the recorded SHA.
- **NOT_RUN:** base-to-head history continuity checks (`--base-ref`). No work
  order source was edited by this G7 implementation. A later order edit must
  run the repository-prescribed base-ref check and required remote CI.
- **NOT_APPLICABLE to the G7 tree:** G7 has no canonical work-order folder or
  validator artifact. This receipt validates its input order in its owning
  repository; it does not add or replace that canonical system in G7.
- **NOT_ESTABLISHED:** prior coordinator absence, Request registration status,
  independent product Validation, price/concurrency/authorization checks,
  browser E2E, Git integration or deployment. See `INHERITANCE.md` for the
  scoped live-coordinator UNKNOWN and preservation of the Spring Request.

Run G7's applicable tests/build and independent fixed-SHA verification before
the reviewed travel checkpoint is integrated. Store this receipt with that
checkpoint; do not report 89 source documents as executed agents, lanes or
product test cases.


October9 18:35 UTC publication precheck: unchanged canonical source
`10bc5eff5f777aa49760387778303063b0222129`, same official validator command
`--revision ... --json work/orders/work-20261009-g7-symphony-max-child-c7ae42d1.md`: exit0, valid=true, documents89, errors[]. No work-order/source validator changes.


October9 19:20UTC publication rerun: identical canonical10bc5eff validator/source
command, exit0,89documents/errors0 PASS before repairedfc54 checkpoint publication.
This is source order validation only, not product/fixed-head CI/Validation receipt.
