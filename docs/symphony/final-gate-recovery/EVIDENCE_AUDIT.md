# Fixed-source recovery audit

Native nonauthor CODEX auditor `/root/evidence_audit` inspected fixed candidate
`e1c765d3d06b4d0e1cdd39ea32b85549de171745`. The lead records its findings here.
This is internal independent evidence intake, not official Validation.

Original CLAUDE browser commit `3f16c7d53b5fba5e56a525f3d5be7e89cb960322`
and CODEX contract commit `e4c736fff897aae3545cf32126f7da12a2a9f824` exist and
are reachable ancestors (both `git merge-base --is-ancestor` exits 0).
All original 61 browser paths and 171 contract paths are byte-identical at the
candidate (`git diff --quiet <original> <candidate> -- <original paths>`, exit 0).
They were already remotely integrated by the scope-correction Request; this
recovery performs no duplicate cherry-pick or history rewrite.

`5783e6ba124061bdfae639cdaf9b1c14a83cdf03` to candidate changes 265 paths:
docs/tests and the deployment README only. Product/runtime delta is zero.
Original source manifests match all 11,091 original Git blobs; 11,090 match
the candidate and the sole difference is `deploy/travel-lab/README.md`.
Both original source inventory aggregate hashes match their stored records.
Contract public artifact manifest: **170/170 exact SHA256 matches**.

Browser original privacy manifest: **60 non-self entries match**. Its self-entry
records an earlier manifest hash because the original script scans the previous
file before overwriting it. Actual original file SHA256 is
`ec3d042c3e49833af21b6bf301e24ccdec12e391ace00921f69c4662cfa5bb84`;
declared self-entry starts `eab51c`. Self-hash verification is
**NOT_VERIFIABLE/stale original instrumentation**. The original commit/report
remains unchanged; no claim of 61/61 is made.

| Original report | SHA256 at original and recovered candidate |
|---|---|
| `W04_RETRY_BROWSER_CLOSURE.md` | `794cff71291c3bfd89258c28e88d5b1a4dec2e8eee10095861e8647275927bc1` |
| `W04_FINAL_CONTRACT_PERSISTENCE.md` | `d5424efc180bd0de0bff76b255ce551e7a416595e75ce0e936f796d7772c30ce` |

Inherited browser ledger: **62 PASS / 7 OBSERVED / 2 FAIL**, 22 contexts and
34 screenshots. Repaired native Page Retry passes at 390/1440; failed Retry
retains error. Original screenshot-guard/intermittent ActionMenu FAILs, expired
supplied token BLOCKED and seven lost token plaintexts without final re-query
remain. The transient previous-failure toast can outlive successful list recovery.
It is distinct from the cleared list error banner.

Inherited backend evidence: 14 native server contract rows, four sustained
MySQL HTTP barriers, 159 real HTTP checks, 10 source tests /137 assertions,
own-process restart/relogin persistence and exact TEST restoration. Engine is
MariaDB 10.11.14. Original restored baseline is **55 tables /104 rows**, digest
`ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`.
This is a historical independently measured/restored baseline, not a new DB
observation or permission to restore it today. Native default basic Pages6
and travel campaign Pages0 remain separate. Migration down/up alone does not
preserve event rows; whole validated backup restoration did.

Engine lock graph, hosted CI and official Validation remain NOT_RUN. Initial
dump per-statement instrumentation limits and every preserved failure remain.
Source/mock tests do not substitute for actual browser/MySQL execution.

Additional independent publication scan of 265 delta paths found zero private
key, GitHub/AWS/Google key, bearer literal, JWT or Korean-phone patterns. Four
email-pattern hits were documented synthetic `*.example.invalid` addresses.
English OCR of 47 PNGs: 47/47 exit0, zero email/phone/token patterns. This is a
bounded pattern scan, not comprehensive personal-name/exact-secret knowledge.
Audit commands: `git show/diff/diff-tree/ls-tree/cat-file --batch`, Python SHA256
and pattern checks, `OMP_THREAD_LIMIT=1 tesseract stdin stdout -l eng`.
Completed checks exited0. Two redundant read-only audit processes were stopped;
their exits are not product test results. No source/DB/service/config was changed.

No P1 product/integration defect was found. Publication is supportable with the
self-hash caveat; main remains HOLD. Original helpers depend on historical
private handoffs/absolute paths. New isolated reproduction uses the documented
[deployment recipe](../../../deploy/travel-lab/README.md), not those handoffs.
