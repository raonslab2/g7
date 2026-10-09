# W03 support recovery integration review

Reviewer: native `/root/w03_recovery_repairs`, nonauthor of the support repair. Read-only Git/source/evidence review; no DB connection, test execution, environment inspection, service operation, source edit, staging or publication. Only this report is authored here.

Fixed review targets: source `df063262693f5b9c95f11bc9a404171f0dfb6b97`, evidence `5e3c04bcb2e09ab976986cb25fb5cd8b576e8e86`. The latter's combined 75 changed paths, relative to the source commit's parent, have inventory SHA-256 `9fbde253df4c71320c264f6a239db64bb4a47bb4d7231f9d6985c0280c67198b` (sorted `path + NUL + file_sha256 + newline`). At integrated HEAD `ef44bf2186d93c16de6cc5a07752fac451182e1e`, all 12 source-binding file hashes matched the reviewed originals. All 66 entries of the committed delivery inventory also matched their Git blobs.

Decision: **bounded support source/evidence review accepted; no live credential/contact-data leak identified**. No new blocking product defect was found in this scope. This is neither a fresh native test PASS nor canonical Validation, full-product verification, preview verification or CI approval.

## Support scope correctness

The original native helper returns unrestricted success when owner metadata is absent. The repair retains `hasPermission(..., Admin)` and native `checkScopeAccess()` checks, and adds explicit question-owner enforcement for every required travel/native board grant. `self` requires the question owner; `role` requires a shared actual `user_roles` relation; a null effective scope preserves native union semantics. Foreign question detail and PATCH require travel read, native board read/read-secret, and additionally travel update/native write for PATCH. Restricted lists remain owner-only. Authors retain their own question access. The repository receives a concrete owner/viewer identity rather than trusting client input.

The repair's role lookup matches the native helper's shared-role definition. Missing permission cannot exploit the native effective-scope null default because permission presence is checked first. The checked repository query stays in the native role pivot table and does not alter permissions. The added tests cover the missing-owner-metadata bypass, role peers versus outsiders, self PATCH, and all 16 board permission identifiers with foreign, empty and missing grants. Native `PostService::updatePost()` remains the mutation path; there is no direct post-table edit in the service.

Provisioning and request safety remain fail-closed: all 16 board permissions must have the native admin grant and only the permitted board-specific roles; incompatible operator changes are rejected without repair. Questions require `mysql-fulltext`. This is a supported-configuration restriction, **not** proof that existing rows cannot leak after an operator changes to an external Scout driver and imports or replays queued jobs. External engines remain NOT_RUN. The listener comment now accurately states that synchronous removal cannot defeat a later queued indexing job.

These two commits do not change parent catalogue/UI/guard implementations, shared settings, manifests, production data or Spring source. Final manifest/version integration remains the lead's responsibility.

## Durable native results reconciled

The reviewer parsed both final JUnit blobs, compared `(class, testcase name)` identities and assertion totals, and checked matching command/exit records and text summaries:

| Saved final batch | Distinct testcases | Assertions | Failure/error/skip | Exit |
| --- | ---: | ---: | --- | ---: |
| `support-final` | 93 | 607 | 0 / 0 / 0 | 0 |
| `auth-isolated` | 14 | 40 | 0 / 0 / 0 | 0 |
| Disjoint total | **107** | **647** | 0 / 0 / 0 | — |

This confirms consistency of the **author's bounded native execution evidence**, not reviewer execution. Final batches have no overlapping testcase identities. The earlier green subset, intentional red run and original batch are not added to this total. Original mixed batch remains FAIL: 104 tests / 328 assertions / 14 errors. Scope red remains expected FAIL: 1 / 2 / 1 failure; green subset is 3 / 277 PASS. The isolated auth rerun resolves the recorded role-fixture collision without modifying core tests. SQLite evidence is configuration/list-only; the SQLite suite is NOT_RUN in this official support scope.

## TEST restoration boundary

The first saved current-state inventory contains **55 tables / 104 rows**. Independently compared JSON objects show exact equality between every `snapshot-before.json` and `restored.json` for original, scope-red, scope-green, auth-isolated and support-final; all equal that first current-state inventory. The final read-only measurement also equals it. Each saved restore result has successful import exit and exact table/row/DDL equality. This evidence includes table names, per-table row counts, row hashes and DDL hashes, without raw cell values.

The earlier independently reported **128 tables / 639 rows** is different. These commits contain no evidence establishing why or when that state changed. **Preservation/restoration of the earlier 128/639 baseline remains NOT_PROVEN**. The 55/104 evidence must never be relabeled as restoration of that earlier baseline.

Source review of the TEST wrapper confirms a marked local TEST-only environment guard, private umask, whole-table snapshot, validation before tests, complete current table removal before import, exact row/DDL comparison before clearing BLOCKED, and private snapshot retention on restore failure. The corrected wrapper releases its PDO before the native test child and reconnects before restore. The first wrapper's parked-connection failure diagnosis remains a source/process-supported explanation, not a captured terminal exception. Exclusivity observations are time-bound; they are not a new scheduler or concurrency guarantee. No recovery was executed by this reviewer.

## PUBLIC evidence hygiene findings

All 75 changed Git blobs were scanned for emails, phone patterns, credential assignments, bearer tokens, private keys and SQL data statements, then relevant hits were classified. No live password/token, actual contact details, environment file, private snapshot/raw SQL dump or private key was identified. Hashes/counts and TEST schema labels are evidence, not row snapshots.

The following explicit synthetic fixtures and exception SQL were classified separately from live credentials or private user content. The lead confirmed the generic installer entries are executable test fixtures and may remain. Reducing unnecessary exception SQL is an optional evidence cleanup, not an identified credential incident. Any cleanup must preserve source SHA attribution, failure classification and JUnit counts. This report intentionally does not reproduce their values.

| Exact original path | Lines | Classification / requested handling |
| --- | --- | --- |
| `docs/symphony/w03-support/evidence/auth.xml` | 16, 17 | Generic synthetic installer email and password fixture, not an identified live credential; executable test configuration. |
| `docs/symphony/w03-support/evidence/native.xml` | 16, 17 | Same generic fixture classification. |
| `docs/symphony/w03-support/evidence/support-fixed.xml` | 16, 17 | Same generic fixture classification. |
| `docs/symphony/w03-support/evidence/original/native.txt` | 15, 56, 97, 138, 179, 220, 261, 302, 343, 384, 425, 466, 507, 548 | Fourteen INSERT exception statements containing synthetic native role values and local TEST connection metadata, not private user content or credentials. Optional cleanup may retain the duplicate-role error diagnosis/count and stack provenance while reducing SQL/connection details. |

The companion previous-driver exception lines are 35, 76, 117, 158, 199, 240, 281, 322, 363, 404, 445, 486, 527 and 568; retain only safe error classification as appropriate. Tests use synthetic identities. A pattern hit in `test-db.php` is construction of a **private** SQL client config from validated environment values, not a committed credential. Documentation hits are permission identifiers/ordinary text, not secret assignments.

No original evidence was edited by this reviewer. Any sanitized copies require a fresh content hash and inventory, and must retain explicit attribution to these original review commits. No blocking secret-removal finding remains in this examined scope.

## Remaining verification

Lead owns Git delivery, fixed integrated SHA, fresh independent security/runtime/browser checks, installed hook discovery and actual preview support journeys. Later root-reported combined 144/2327, Pint/API/docgen and installed-source results are outside this pinned review and were not independently rerun here. This review supplies no additional native test count, full-product PASS, remote CI PASS, deployment claim or canonical Validation receipt.
