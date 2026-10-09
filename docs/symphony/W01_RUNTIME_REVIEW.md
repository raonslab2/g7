# W01 runtime delta review

Decision: **PASS — bounded independent delta/code review**, with runtime and
whole-product gates explicitly separate. The original pinned candidate required
changes for two P2 findings; both follow-up source deltas below close the code
issues. No P0/P1 or unresolved P2 finding remains in this reviewed delta.

Reviewer: native `/root/commerce_contract`. Original target:
`storage/framework/testing/travel-runtime-review-target.json`, base `2114703d`,
23 paths, scope digest
`73a54d1cbea575f4be125916cd2926197cd0051257440a5cc422bd52c3ba6880`.
Entry verification **23/23 SHA256 PASS**. During authorized fixes, two files
changed; original approval for those files stopped and their owner-provided
follow-up hashes were independently matched. The other 21 hashes matched again
before this report. No implementation/staging/commit/push/DB mutation by reviewer.

## Independence boundary

The reviewer originally authored the baseline `.env.travel-lab.example`,
`deploy/travel-lab/README.md` and `environment/extensions/guard-test/run/setup/smoke`
PHP scripts before base2114703d. This review approves only other implementers'
changes after that base in those files, and the new scripts plus lead-owned
CatalogRepository delta. The unchanged original harness is **NOT independently
approved here**. The reviewer's own workflow services/listener/tests are outside
approval, including SHARED-02's workflow correction. Official Validation,
whole-product/production approval and browser E2E certification: **NOT_RUN**.

## Findings and remediation

- **P2 RUNTIME-01 — failed API capture held authored seed capacity.** Original
  live-api-responses selected an authored departure, submitted an inquiry, and
  cancelled only on the happy path; finally deleted tokens only. Failure after
  inquiry creation could leave that published seed departure reserved. Follow-up
  `808b987e43608cef99c5e8f68832a7528cb9e272c07ca9cefa59a946ba9c1124`
  uses a new transactional synthetic fixture and retains owner/key before POST.
  Finally verifies exactly one item on its own product/departure, uses native
  cancellation, verifies zero reserved and terminal status, independently hides
  the fixture even if cancellation fails, revokes exact created tokens and
  fails visibly on cleanup exceptions. **CLOSED by independent static delta
  inspection**. Runtime owner reported fault injection `--fail-after-inquiry`
  exited1 as expected with inquiry8 CANCELLED, departure31 reserved0, product15
  unpublished. That execution is **implementer evidence**, not reviewer execution
  or official Validation. Owner subsequently reported normal capture exit0 with
  11 actual runtime replies, inquiry9 CANCELLED/departure32 reserved0/product16
  unpublished, and a fresh PHP process reloaded the fault fixture with the same
  persisted released state. Ignored logs: travel-live-api-cleanup-{normal,fault}.log.
  These are owner-executed implementation checks, not independent Validation.
- **P2 RUNTIME-02 — direct test entry discarded sanitized environment.** Original
  LiveMysqlTest called travelLabEnvironment(true) but ignored its returned clean
  environment before parent bootstrap. Documented run.php already sanitized its
  subprocess, but direct root PHPUnit could retain inherited DB_URL/socket until
  post-bootstrap assertions. Follow-up
  `a2d9c3c75e7652082d336bec56f6c6c786fc821c08fba47d5b60518e49aca05b`
  applies all validated adapters before parent::setUp. **CLOSED by independent
  static delta inspection**. Lead reported direct entry with unusable loopback
  DB_URL127.0.0.1:1/forbidden and /tmp/forbidden.sock: PASS1test/31assertions,
  5.748s at10:07UTC, on exact lab MySQL assertion before migrations/data mutation.
  This is **lead implementation evidence**, not reviewer execution. No foreign
  endpoint was used.
- **SHARED-01 follow-up — catalog lock order.** Pinned CatalogRepository now locks
  existing Departure → native Product → ProductOption → TravelProduct and retries
  the native transaction up to3 attempts. This removes the original inverse
  TravelProduct/Departure cycle with submission. Option ownership, immutable
  mapping, capacity/reservation and historical-date checks remain inside that
  transaction. **CLOSED by independent static delta inspection**. Live concurrent
  admin-departure-edit reproduction is **NOT_RUN by this reviewer**; the runtime
  service races do not certify that additional scenario. SHARED-02 workflow is
  expressly not approved by this reviewer.

## Reviewed runtime behavior

- Native lifecycle subprocess finally cleanup revalidates the marked scope and
  refuses installer overrides/symlinked config files. It removes only this
  checkout's regular config snapshot; clear-cache is not a service restart.
- Setup delta checks local dedicated DB account global/schema/table/column grants
  before password rotation, uses fixed two-schema/account SQL with generated
  hexadecimal passwords, captures admin tool output without logging SQL/secrets,
  aligns surviving testing credentials, and preserves existing users by skipping
  destructive core seed. Admin recovery is exact synthetic email, generated
  password and native UserService update; no arbitrary user replacement.
- travelLabApplyEnvironment clears inherited DB/admin/cache override adapters;
  live-bootstrap verifies effective read/write config, egress config and actual
  DATABASE()/CURRENT_USER before fixture operations. Only lab schemas/account,
  array mail, sync queue and local storage are accepted.
- Extension delta uses official lifecycle commands, explicit module --sample and
  doubly marked support provisioning. Native SeedModuleCommand confirms --sample
  propagation and does not generate config cache, so live seed-rerun's plain
  subprocess is consistent. Development/test/broad suites are documented as
  sequential, with different canonical SQLite/native MySQL paths.
- LiveMysqlTest uses real root TestCase, native migrations/reference seed/services,
  named actual bundled routes and persisted Sanctum roles/tokens. It labels those
  mounted test routes separately from installed runtime discovery. No ecommerce
  order sample seed is invoked.
- Concurrency workers use separate PHP processes and actual connection IDs,
  scoped private barriers with deadlines, real domain services and native stock
  service; assertions cover last seat, same key, cancel/decline and locked stock.
  Evidence explicitly says WORKING_TREE_IMPLEMENTER_CHECK and service concurrency,
  not independent HTTP/browser/fixed-SHA Validation. New fixture products are hidden
  in finally while synthetic history is retained; no authored product is selected.
- Test recovery uses mode0700 private directory and mode0600 scoped client options/
  dump, exact testing DB CLI destination, digest comparison, fallback restore and
  retained private dump only on restore failure. SQL/credentials are not printed.
  Env recovery privately preserves regular generated files, rotates through setup,
  checks stable IDs/travel digests, restores scoped authentication on failure and
  explicitly limits APP_KEY/session claims to synthetic lab data. Partial recovery
  rejects applied/populated travel migrations and drops only fixed empty tables in
  reverse FK order without disabling FK guards.
- Reviewer access uses unique synthetic users through native UserService, native
  administrator role under an existing synthetic admin, four-hour tokens and an
  ignored0600 JSON inside0700 private directory. Only the path is printed; no
  credential/token material enters report/Git. Persistence output exposes counts/
  hashes only; API captures omit authorization headers and use synthetic data.
- No new provider account, capacity change, scheduler, public deployment, production
  data/service change or external payment/booking/email/SMS integration is added.
  The example debugfalse and documented loopback18871 agree.

## Reviewer-executed evidence

- Original manifest SHA verification23/23: **PASS**.
- PHP syntax21/21 scope PHP files: **PASS**; both follow-up files linted again PASS.
- `php scripts/travel-lab/guard-test.php`: **PASS20 isolation checks**, including
  four new cache recovery checks. It uses a temporary fixture inside the request
  worktree, no live env/cache change and no database connection.
- Final hash check: **PASS21 unchanged original +2 explicit follow-up hashes**.
- Live setup/recovery/concurrency/fixture mutation/access minting/MySQL suites:
  **NOT_RUN by reviewer**, because lead regressions owned the schemas during review.
- Lead authenticated PC UI smoke is outside this review and is not counted as
  independent admin UI/browser verification.

## Original fixed manifest

| Path | Original SHA256 |
|---|---|
| `.env.travel-lab.example` | `d9e52ed600cf3eb6ca036ae63015ffaa4b7a37c03afa17e2718b4a97033c1df8` |
| `deploy/travel-lab/README.md` | `c223c6401effe0cd340600c176229285cca6175a22bb8440043413a4bbff07c7` |
| `modules/_bundled/raonslab-travel_lab/src/Repositories/CatalogRepository.php` | `abdf2633d3aea5e69f04f9351e6c002e99cea0a9586d94ecc110100b9c5933dc` |
| `scripts/travel-lab/LiveMysqlTest.php` | `a54651fb89aa2391f3cea77d8f149893b234f480f0bc274c1863075519b91ea7` |
| `scripts/travel-lab/clear-cache.php` | `a3414e098ce3aefe692920b4d707d296f836496dafbe0c03d0926272445d6b1d` |
| `scripts/travel-lab/environment.php` | `8819e89a2abd936ba8fc7344dfc2fa424c2558ffb2bb6586849cf65d2662c11c` |
| `scripts/travel-lab/extensions.php` | `42d71f333e3b1c10cba27b3a86321081a8c356c58fe72e4059ecf6101ba8efdd` |
| `scripts/travel-lab/guard-test.php` | `801698a24ca0f7601ddf297b00614f3c9e57b76dd229add5fd75078ec8326a95` |
| `scripts/travel-lab/live-admin.php` | `e0deb2b716b193fe8593eac22101dae2b0d5f3f59434893e626266ef15eea697` |
| `scripts/travel-lab/live-api-responses.php` | `af463ceaf5fb2616efeee4cba4134126bd4aafd4ec699fae7bbd993f89eb2597` |
| `scripts/travel-lab/live-bootstrap.php` | `67a2b04129c0bdfad9e87c7a12efbab363ce267e11cc913e8b5820c93f5cbbc6` |
| `scripts/travel-lab/live-concurrency.php` | `e9f602ed0de8f908c367866f1fde1b0855a2311e86e4e3147f2d8dc174ac4e1b` |
| `scripts/travel-lab/live-env-recovery.php` | `b02c261de42ceb96949d32d0bb4c74ac57ebc97566ba8453dc2ff5ad6ab20210` |
| `scripts/travel-lab/live-fixtures.php` | `b35b38d3e93cad2611b2d8b0939a50eb4f1c28cc9e5b5e94227bb7f5b714fffe` |
| `scripts/travel-lab/live-persistence.php` | `00b8893d05f4b9cdb732dbb5dc4a86f1236bdb6e5dfbaed9e3578de0926060db` |
| `scripts/travel-lab/live-recovery.php` | `24cb60481c6d5814eb19aa48a6f60202f25a1e531f0f5a76b9ee27bc8c93793d` |
| `scripts/travel-lab/live-review-access.php` | `c3f649f0b4de277db01a3dd27c122af7cab34823dc27f7ef665f449116aa3429` |
| `scripts/travel-lab/live-seed-rerun.php` | `6f980b907bb77e328ada842199790ad7b84704146d80013a4961e408bc9d06aa` |
| `scripts/travel-lab/live-worker.php` | `b6e7c8ef8cea4b5f3dd8929b220d585b5495ac9d2bfaf37d1f53c2a1d57a3bd5` |
| `scripts/travel-lab/recover-partial.php` | `ad31667bbf2eff773f3a77f581e17faa7644a478a234246bcd3df6f04d2ff861` |
| `scripts/travel-lab/run.php` | `2e4559f2662112124b6f6d982ac0c90b074874c73704e80ae2bc3109bb7257c7` |
| `scripts/travel-lab/setup.php` | `67e2966335c4cc8c896bed22baaf33bedcf60712ef2a1e26e9debb3be4e9a3e2` |
| `scripts/travel-lab/smoke.php` | `645476e8ad52e9367d2a6767d14e12eb913e9c50c0ef3d35e868fb7c82235711` |

## Explicit follow-up manifest

| Path | Reviewed follow-up SHA256 |
|---|---|
| `scripts/travel-lab/LiveMysqlTest.php` | `a2d9c3c75e7652082d336bec56f6c6c786fc821c08fba47d5b60518e49aca05b` |
| `scripts/travel-lab/live-api-responses.php` | `808b987e43608cef99c5e8f68832a7528cb9e272c07ca9cefa59a946ba9c1124` |
