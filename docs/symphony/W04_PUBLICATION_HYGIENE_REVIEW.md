# W04 public Git evidence hygiene review

**No blocking disclosure found within the scanned text/metadata and sampled image content.** This is a bounded publication hygiene review, not a claim that every screenshot was visually reviewed or that the product's negative validation decisions are closed. Native reviewer `w03_commerce_guards`, Request `req_81ac33cac94046b9a2249cd14c0d00ba`; no credentials, environment contents, database data or service endpoint were read for this review.

## Actual review boundary

Inspected `docs/symphony/W04*`, `docs/symphony/w04/`, and `docs/symphony/evidence/W04*` (including the separate browser/support/checkpoint directories), mobile-request evidence, plus tracked changes and nonignored new publication paths. The final text pass read **376 text files**. Image inventory contained **137 PNGs**; all137 were checked for embedded text/EXIF chunks, **13 representative PNGs** received English/Korean OCR, and **three of those13** were visually inspected. No claim of full image-content coverage is made. Concurrent new files or later edits require a refreshed check at publication.

Text checks covered recognizable bearer/JWT/GitHub credentials and private keys, literal password candidates, email/telephone values, raw dump signatures, sensitive JSON scalar keys, and environment/compiled-cache/dump file paths. Sensitive candidate values were never printed. No recognized secret, email/contact scalar, raw SQL dump, committed environment file or pyc appeared in this publication scope. Two password-pattern matches in `snapshot.php` and `test-baseline-recovery.php` were PHP variable/command expressions, not literal passwords. Eight telephone-pattern matches in browser manifest/source-binding files were substrings of64-hex digests, not contact values. The repository's existing `deploy/database/g7-schema.sql` lies outside these publication changes and is a native schema artifact, not a newly delivered runtime dump; it is not counted as a scoped disclosure.

All137 PNGs contained no `tEXt`, `iTXt`, `zTXt` or `eXIf` chunks. The13 sampled OCR files produced zero recognized email/phone/bearer/JWT matches. Initial default-thread OCR did not finish promptly; only that reviewer's own OCR processes were stopped. The completed pass used at most two workers, one OCR thread each and15-second per-image bounds, without changing Node/service configuration. OCR cannot reliably establish absence of every personal name or obscured value.

Representative OCR paths, relative to `docs/symphony/evidence/W04_BROWSER_RECHECK/`:

- `ordinary-inquiry-1440.png`, `cancel-edited-contact-390.png`, `native-product-form-1440.png`.
- `own-question-390.png`, `edited-question-1440.png`, `native-answer-390.png`.
- `admin-accepted-1440.png`, `relogin-accepted-390.png`, `public-home-1440.png`.

And relative to `templates/_bundled/raonslab-travel_lab/__tests__/evidence/`:

- `mobile-request-native-owner-after-pointer-390.png`.
- `mobile-request-native-owner-after-date-1440.png`.
- `mobile-request-native-before-390.png`, `mobile-request-candidate-1440.png`.

Visual inspection was limited to cancel-edited-contact390, admin-accepted1440 and mobile-native-owner-after-pointer390. Contact/operator input regions in the first two have opaque masks; the admin screen also uses blurred surrounding detail. The mobile request list contains synthetic fixture product labels, status/price/date/request identifiers, with no visible contact fields. These sampled images do not establish the privacy of the remaining124 image contents. No screenshot was modified by this reviewer.

## Manifest integrity and negative evidence preservation

| Manifest/evidence | Independent readonly comparison |
| --- | --- |
| Browser `sha256-manifest.json` | 199 entries, zero missing/different |
| Browser `public-sha-manifest.json` | 30 entries, zero missing/different |
| Support `execution-manifest.json` evidence entries | 20 entries, zero missing/different |
| Mobile `mobile-request-repair-manifest.json` | 32 entries, zero missing/different; rechecked final SHA-256 `99189bd6984a01fce9c91c8559429e471f6e7f09cfd43f7cd4235cbd76a6ab3f` |
| Recovery commit `8c29b9a6` delivery | All16 changed paths present and byte-identical to their Git blobs |

The281 manifest entries overlap and are not281 distinct independent validations. Browser source binding/summary remains tied to `598a89fff702d51c1405f1a5952d95ab1d2651f4`, tree `9e00273bdf18d6a713343755aac54f44a9b032b4`, with `overall=FAIL` and `officialPASS=false`. The support report retains **CHANGES_REQUIRED**, its public actor-isolation finding and the same original source binding. Recovery records retain the failed official-child status and unchanged original files/BLOCKED marker; recovery result is **PASS_BASELINE_RECOVERY_ONLY**, with installer/canonical Validation **NOT_RUN**. Later implementation fixes do not relabel these original observations.

New checkpoint `original-browser-token-cleanup.json` was separately scanned, parsed and hashed: SHA-256 `87d46de771c8444406819d481e53941695b2e91364760c4a19bda70a6fd5a85e`. It contains safe identifier/time/count metadata, **six exact expired token removals, zero remaining target IDs**, and unchanged untargeted metadata; no plaintext credential was found. Original-token HTTP401 is explicitly **NOT_RUN because original plaintext is unavailable**. This document's hygiene review does not independently perform those removals or elevate DB absence to an executed HTTP logout result.

## Authorized test-harness follow-up

The lead's preserved `storage/framework/testing/w04-closure-core.log` records **35 tests / 2,187 assertions / five errors**. The counter fixture was previously class-order dependent: when an earlier suite registered module classes, native route middleware metadata lookup constructed SupportController and tried to resolve its unused board-service dependency in the fixture's plain Container. This is a test-harness isolation failure, not a demonstrated product defect.

At lead's explicit reprioritization, only owned `tests/Unit/Extension/TravelSupportThrottleIsolationTest.php` was repaired: each case runs in a clean PHP process without imported globals, a source-scoped ClassLoader resolves bundled module classes, and the real SupportController supplies its real middleware metadata with an unused mocked TravelSupportService boundary. Numeric native limiter pipeline and all existing auth/method/budget/counter assertions remain. No controller action or SQL/service process executes. Loader/Container/facade context is restored.

```sh
php vendor/bin/phpunit tests/Unit/Extension/ModuleVendorModePersistenceTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/ModuleVendorInstallGateTest.php
```

Exact default project configuration rerun: **PASS35 tests / 2,233 assertions**, 3.358s,30MiB. The lead independently repeated that command with the same counts in3.732s. The extra46 assertions are the previously aborted counter cases completing, not new product scenarios. Pint, syntax and whitespace checks passed. A later comment-only clarification changes the final counter source hash to `939081b79ca0c609ac8ed955b5b961ab200292bd84c4d6405bf62aa019a6d013`; test behavior is unchanged from both executed runs. The original failure log was not edited or overwritten. A nonauthor review of the harness change remains lead-owned.

Only this report and the explicitly reassigned counter harness were edited. No other report/product source, Git stage/commit/push, APP/TEST or persistent DB, environment file or application service was changed. Publication hygiene, fixture regression, independent fixed-source runtime validation, CI and Git integration remain separate states; this review grants no canonical Validation or release approval.
