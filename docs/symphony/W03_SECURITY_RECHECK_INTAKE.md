# W03 security recheck evidence intake

- Intake timestamp: 2026-10-09T13:04:14.010046+00:00.
- Evidence commit: `dd43331a91ef13a3e6c6e68f6689ab998c16895c`.
- Evidence parent / tested product target: `7de0c4441b68b1c012dbf4a75114200e322051c9`, product tree `3c285467c066149d26b9df2b392299d819862a69`.
- Original recheck Request: `req_82f0e86c05094c4eb83b85cc6c98f9e0`; owning lead Request: `req_81ac33cac94046b9a2249cd14c0d00ba`.
- Intake author: native subagent `/root/w03_commerce_guards`. This author implemented the commerce guards, KST checks and workflow throttles. **This intake is not an independent audit of those repairs.** The original CLAUDE reviewer authored the new tests/HTTP probes; this intake checks their committed artifacts and current source identity without reexecuting them.
- No product changes, DB queries/writes, test runs, service operations, deployment or publication were performed for this intake. Source-file hashing and parsing of committed evidence are read-only checks.

## Intake decision and required delivery correction

**Accept the bounded evidence after removing the compiled Python cache from Git delivery.** The final committed JSON supports four measured HTTP overlaps and the specified invariants. It does not supply whole-product PASS, hosted CI PASS, canonical Validation, complete privacy proof for external Scout engines, or global fixture-cleanup proof.

The actual commit has **six** changed paths, while its report lists five:

1. `docs/symphony/W03_SECURITY_RECHECK.md`.
2. `modules/_bundled/raonslab-travel_lab/tests/Feature/W03SecurityRecheckTest.php`.
3. `modules/_bundled/raonslab-travel_lab/tests/evidence/w03-recheck/live_recheck_probe.py`.
4. `modules/_bundled/raonslab-travel_lab/tests/evidence/w03-recheck/row_lock_barrier.php`.
5. `modules/_bundled/raonslab-travel_lab/tests/evidence/w03-recheck/live-recheck-probe.json`.
6. **Omit/remove before public publication:** `modules/_bundled/raonslab-travel_lab/tests/evidence/w03-recheck/__pycache__/live_recheck_probe.cpython-312.pyc`.

The cache is a generated, opaque binary artifact, is not required for reproduction and was omitted from the reviewer's delivery list. Lead owns its removal; this intake does not edit the incoming commit or any other file. The five readable artifacts contain no product implementation change.

## Four final-run overlaps checked against code and JSON

The final run is `W03R-123332`. The barrier uses the marked isolated **APP** preview schema `req81_travel_lab` and the existing scoped `req81_travel` account. This is the lab APP database, not a customer/production database, and not the exclusive TEST schema. No credentials are printed by the barrier. The source checks database/account identity and ownership before locking a departure, user or inquiry row.

`row_lock_barrier.php` holds `SELECT ... FOR UPDATE` on the owned row, captures first detection and a second observation at least three seconds later, and commits only after the Python caller has received the observation. Python starts two client threads, releases the lock after observation, then records each response's send/completion timing relative to the release.

| Race | Distinct persistent connections | Total hold / elapsed since first detection | HTTP responses in caller order | Both sent before release / completed after release |
|---|---|---|---|---|
| Last seat, two members; departure 52 | 64549, 64548 | 3.213 s / 3.058 s | 201, 409 | 3.216/3.214 s before; 0.092/0.097 s after |
| Same key, same member; user 30 | 64700, 64699 | 3.182 s / 3.027 s | 201, 200; both inquiry 70 | 3.182/3.182 s before; 0.070/0.077 s after |
| Member cancel vs admin decline; inquiry 70 | 64893, 64891 | 3.372 s / 3.066 s | 200 CANCELLED, 409 | 3.372/3.370 s before; 0.021/0.025 s after |
| Submit quantity 2 vs capacity 2→1; departure 54 | 64943, 64942 | 3.217 s / 3.063 s | 201, 409 | 3.217/3.217 s before; 0.087/0.142 s after |

For each race, the first and second observations retain the same two connection IDs, `still_blocked_after_hold=true`, and both connections show `Execute` / `Statistics`, an elapsed process time of 3 seconds, and `statement_targets_locked_table_for_update=true`. The recorded HTTP completion ordering satisfies the source's overlap predicate. All four final races pass on their first attempt.

**Measurement limit:** `INNODB_TRX` is not visible to this scoped account. `innodb_lock_wait=false` in the JSON therefore does not assert absence of a lock wait; the declared fallback uses PROCESSLIST statement-target facts plus sustained timing and response ordering. The public record does not expose raw SQL or directly map each HTTP client to a specific DB connection. These are four bounded contention scenarios, not a load/throughput benchmark, proof that deadlocks are impossible, or an InnoDB lock-instrumentation PASS.

The final result details preserve:

- Last seat: one 201, one capacity 409, `reserved=capacity=1`, subsequent add returns 409.
- Same key: exactly one reported inquiry ID, `reserved=1`, exact replay 200, changed payload 409, native recomputed amount `12500.00` after the option adjustment. This is a TEST_INQUIRY, not an order or booking confirmation.
- Cancel/decline: one cancellation and one invalid-transition 409; reserved becomes 0, repeated cancellation/replay keeps it 0.
- Submit/capacity edit: submit succeeds with reserved 2; capacity reduction returns 409 and capacity remains 2.

## Test totals and source/runtime identity

- The report states baseline **144 tests / 2327 assertions**, recheck **8 / 137**, combined **152 / 2464**. Both sums agree. **153 is not supported by this artifact.**
- The recheck file declares five KST provider rows plus three other test methods, supporting eight executed cases. It preserves UTC app timezone while testing KST midnight/08:59/09:00 boundaries.
- Raw PHPUnit/Pint command-output logs are **not committed in these six paths**. The suite counts and command outcomes are reviewer assertions, structurally consistent but not newly rerun or independently established by this intake. The final HTTP JSON does not substitute for raw PHPUnit logs.
- Read-only intake independently enumerated the frozen target module using `git ls-tree -r --name-only 7de0c4441b68b1c012dbf4a75114200e322051c9 modules/_bundled/raonslab-travel_lab`: **159 tracked files**.
- For all 159 files, SHA-256 of the frozen Git blob was compared with the corresponding active file under this same Request's `modules/raonslab-travel_lab`. At intake time: **0 missing, 0 different, 0 extra**.
- Target manifest digest: `9b3b4770639138eabd19e00b60a5f3bc32feedc1b6db0c0ef9d5c228a12c8ef2`. Algorithm: sorted Git path order; list of `{path: module-relative path, sha256: Git-blob SHA-256}`; JSON encoded with `sort_keys=True,separators=(',',':')`, then SHA-256.
- The original recheck's historical 159/159 assertion has no per-file manifest in its committed artifacts. The new intake-time comparison confirms current identical bytes; it cannot retrospectively attest which PHP classes/routes/cache were executing during every historical request.

The evidence commit's parent is exactly the stated tested product SHA. No review artifacts are counted as new product implementation. Integration must retain tested product identity separately from the later evidence/integration commit SHA.

## Cleanup evidence and remaining claims

The final-run JSON records successful cancellation of inquiries 69 and 71, the earlier scenario's inquiry 70 already CANCELLED, one foreign-member cart delete, departure writes 52–57 all returning 200, unpublish/hide/category-deactivate 200, and the ordinary product hidden with a 200 read. Final departure snapshots show all six inactive and `reserved=0`; public catalog omits this product and public detail returns 404.

The following wider assertions appear in the report but do not have a final aggregate snapshot in this commit:

- both members' carts empty after all deletions;
- all 15 inquiries from all attempts CANCELLED;
- all earlier travel products/departures/categories cleaned to their stated residual state;
- public catalog exactly unchanged at all eight seeded IDs;
- zero new order/temp-order/payment rows across the complete review.

The script filters cleanup actions by its run marker/departure IDs, and retains guarded travel rows as hidden/unpublished/inactive rather than trying to delete them. Private support questions 24–28 remain because their API has no delete. This documented residue is not “zero fixture residue.” Lead's separate aggregate cleanup/transaction-state evidence is still required for stronger claims.

The cleanup function's PASS predicate checks reserved counts, inactive departures, unpublish and public omission. It does **not** require every individual cancellation/delete HTTP status to succeed, requery both carts afterwards, or attest every earlier fixture. Its final PASS must retain those boundaries.

## Earlier negative attempts remain visible

The JSON retains five earlier runs; this intake does not relabel them:

| Run | Recorded counts / negative evidence |
|---|---|
| W03R-121518 | 1 PASS / 1 FAIL / 1 OBSERVED; departure response expectation failure. Separate aborted-run cleanup retains a FAIL, including `public_catalog_contains_fixture=true`. |
| W03R-121728 | 11 PASS / 4 FAIL / 1 OBSERVED / 1 NOT_RUN; guard-message expectation and three overlap detections failed; cancel/decline dependent step not run. |
| W03R-122142 | 15 PASS / 1 FAIL / 1 OBSERVED; expected price 12500 vs actual 12000 diagnostic failure. |
| W03R-122519 | 15 PASS / 1 FAIL / 1 OBSERVED; one overlap not observed. |
| W03R-122933 | 13 PASS / 2 FAIL / 1 OBSERVED / 1 NOT_RUN; same-key overlap not observed; owner/foreign assignment diagnostic failure; cancel/decline dependent step not run. |

The report attributes these to probe expectations, native prepared-statement detection, option price-adjustment semantics, scheduling of shared PHP workers and winner identity. The final script includes corrected expectations and bounded fresh-fixture retries. Earlier raw worker/PHPUnit logs are not committed, so the scheduling explanation remains a reviewer explanation, not a new measured fact from this intake. Final JSON counts recomputed from its 17 result records are **16 PASS / 0 FAIL / 1 OBSERVED / 0 BLOCKED / 0 NOT_RUN**. One OBSERVED combines blocked own-shipping-policy creation (422) with read-only reuse of the existing free-KR policy; it is not a successful fresh policy-creation test.

## Retained findings and unsupported scope

The report's headline “Original findings closed” needs the explicit exception already present in its matrix: **W03-05(a) is CONTAINED, not CLOSED**. No new P0/P1/P2 is the original reviewer's judgment, not a whole-product conclusion by this guard author.

- W03R-01, P3: native `can-delete` preflight returns `canDelete=true` while actual travel DELETE correctly returns 409. The final JSON confirms this mismatch.
- W03R-02, P3/static: native option stock can be lowered below departure capacity/reserved; reservation caps prevent the specific oversell path, but administration can enter an inconsistent state. The four races do not test every native option/bulk-edit path.
- W03R-03, P3/static: a role-scoped native board grant sharing the common `user` role can permit staff access/edit by ID. It was not exercised with such a staff fixture; owner/foreign/admin HTTP checks do not close this residual.
- W03R-04, observation/static: file-store throttle increments are not atomic across PHP workers. Exact sequential 429 checks do not establish concurrent rate-limit enforcement.
- Residual source-only race: linking a new departure between deletion preflight and the FK delete can still end at the generic FK/500 path. This was not one of the four measured races.
- Private support permission/provisioning/audit closures mix SRC and limited HTTP evidence. MySQL support hardening regression suites were explicitly NOT_RUN by this reviewer; their separate official runtime evidence must be linked by lead.
- External Scout engine/bulk import/manual searchable/already queued jobs/existing indexed rows are not comprehensively excluded. Unsupported search drivers fail closed for the support API/provisioner; that containment is not proof that already queued/imported private content cannot be indexed. No external engine/config/network test was run. Keep the supported mysql-fulltext lab constraint and the unclosed finding visible.
- Native product Q&A creation returned 422 (“board not configured”). The image preservation HTTP test passes, but live Q&A preservation remains NOT_RUN; source ordering and author SQLite mocking are different evidence.

## Public Git hygiene and release boundaries

Read-only scans of the five readable changed artifacts found no recognizable GitHub tokens, JWTs, Sanctum token values, private keys or SQL dump markers. Recursive JSON-key inspection found no token/password/secret/contact/email/phone/raw-SQL fields; its fixture rows are authored synthetic product identifiers and sanitized probe facts. Source credential field names and in-memory SQL for the bounded barrier are not credential values or a dumped database. Do not publish the private access file, ignored environment files, raw contacts/tokens, or the compiled cache.

This is a bounded hygiene check, not a guarantee against every possible secret representation. No access-file/platform credentials were read to perform it.

Hosted CI is NOT_RUN (0 runs). Canonical Validation remains NOT_RUN; neither the original review nor this intake is a receipt. Actual full mobile/PC product journeys, package recovery, post-integration regressions and user-visible preview delivery remain separately owned verification gates. Do not sum author tests, this intake, repeated attempts and canonical verification as independent product PASS counts.
