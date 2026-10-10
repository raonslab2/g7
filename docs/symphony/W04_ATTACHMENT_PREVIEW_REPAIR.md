# W04PA-01 — native board nonimage preview response repair

## Outcome and attribution

The bundled user `AttachmentController::preview()` now returns the existing translated `attachment.not_image` message through its inherited `error(message, 400)` helper. It previously called an unavailable `badRequest()` method; the controller caught that exception and returned `preview_failed` with HTTP 500. The production diff is one line. No storage, authorization, signature, deleted-post, download or response-envelope implementation changed.

This report records the implementation author's bounded no-bootstrap unit verification. It is **not independent product approval, native installed HTTP verification, MySQL verification, hosted CI or canonical Validation**. Lead owns board version/CHANGELOG/consumer synchronization, Git delivery and installed-source publication.

## Original independent evidence remains valid

Read-only intake: attachment result commit `abf354abcab07f171a4e83989561a16364adb113`, `docs/symphony/W04_PRIVATE_ATTACHMENT_FINAL.md`, Request `req_34bd43fee9d043399c7662d480a9dbc0`. Its target was `992f9a65ac3f8957e5ec618f21072dc499053810`, tree `db85f35c5f42165802ec7ba6b02bc8a147a12c39`; its real HTTP observations occurred on 2026-10-09, 18:38:41–18:38:51 UTC.

That earlier result found **P3 W04PA-01 OPEN**, with foreign/guest nonimage previews returning 500. Its positive authorized attachment-byte controls and image access-denial controls supported the original private-attachment requirement, without file-byte/path/name/question-body leakage on rejected calls. Its separate contracts remain unchanged: owner download denial under native board permissions, valid signed image URLs as temporary bearer capabilities, and physical file retention following soft deletion. The earlier negative response result is preserved rather than relabeled as PASS by this source repair.

## Reproduction and regression scope

New root unit fixture: `tests/Unit/Extension/BoardAttachmentPreviewResponseTest.php`. Each case runs in a clean PHP subprocess with global-state preservation disabled. It registers the bundled board PSR-4 path and asserts that the actual controller and attachment service resolve from that path. It uses:

- Actual `AttachmentController`, `BoardService`, `AttachmentService`, `Attachment` MIME-derived image classification, and `ResponseHelper` through the native controller base.
- Actual framework `ResponseFactory`, translator loading the bundled Korean/English messages, URL signature validator and unsigned requests.
- Actual `SecretContentGate` for the secret-image owner and actual framework Gate with no granted abilities for the deleted-image denial.
- Mocked repository/storage interfaces only at their persistence/storage boundaries; unsaved native model fixtures, and a temporary synthetic PNG file for the authorized `BinaryFileResponse`.

No application/bootstrap files, environment files, SQL connections, installed module tables, preview services or runtime processes are involved. The six cases cover Korean and English nonimage 400/error-envelope/no filename or path disclosure, missing hash 404, unresolved-parent unsigned image 403 before storage access, deleted-image unauthorized actor 403 before storage access, and secret-image owner 200 with matching PNG file bytes/MIME/ETag. The owner file assertion inspects the actual response's file; it is not a network byte-capture claim. Repository-fixture missing/denied results do not certify real database lookup/scope behavior.

Board `ModuleTestCase` remains the required native module integration gate. This separate root unit scope was explicitly assigned because that base boots the application and migrates/registers native module data, which would violate the active exclusive TEST lease and this task's no-APP/TEST restriction. It does not replace or waive that gate.

## Executed checks

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/BoardAttachmentPreviewResponseTest.php
php vendor/bin/pint tests/Unit/Extension/BoardAttachmentPreviewResponseTest.php modules/_bundled/sirsoft-board/src/Http/Controllers/User/AttachmentController.php
php vendor/bin/pint --test tests/Unit/Extension/BoardAttachmentPreviewResponseTest.php modules/_bundled/sirsoft-board/src/Http/Controllers/User/AttachmentController.php
git diff --check -- tests/Unit/Extension/BoardAttachmentPreviewResponseTest.php modules/_bundled/sirsoft-board/src/Http/Controllers/User/AttachmentController.php
```

| Check | Result |
| --- | --- |
| Original controller, same six cases | **FAIL**: 6 tests / 41 assertions / 2 failures; both locales expected 400, received 500; 1.036 s / 12 MiB |
| One-line repaired controller, same six cases | **PASS**: 6 tests / 47 assertions; 0.995 s / 12 MiB |
| Pint and Pint check | **PASS** |
| Owned-file whitespace check | **PASS** |
| Installed native module/SQL/HTTP at a published repair SHA | **NOT_RUN** in this scope |
| Independent fixed-source review, CI and canonical Validation | **NOT_RUN** by this implementation author |

Environment: PHP 8.3.6, PHPUnit 11.5.56, Composer autoload only. No test exclusions or middleware/policy bypasses were added. Full ignored logs are mode 0600 under `storage/framework/testing/`; the sanitized counts/failure above are the Git-deliverable evidence.

## Source and evidence pins (SHA-256)

| Input/output | SHA-256 |
| --- | --- |
| Original `AttachmentController.php` (reconstructed by reversing only the recorded one-line diff) | `500019051d4722f9c19947c9c320a229002676948058596d82f091f83c906429` |
| Repaired `AttachmentController.php` | `d368cb0108765094f6739c3f3a110d7e597fff6606ad367971943dc091a6c816` |
| New `BoardAttachmentPreviewResponseTest.php` | `2f4218ea591cb3e7645c938f8083f24bcec57cfc8f766e033b1ac3d801f8d132` |
| `app/Helpers/ResponseHelper.php`, unchanged | `c7fc02b35d76de044d0928244a35d322e0cc7456d046d55375aabe77c909ed93` |
| `app/Http/Controllers/Api/Base/BaseApiController.php`, unchanged | `c0c7461434f57614007e0323e956dea6f1abf6341f9c886faa8a497c6cc90ddd` |
| Bundled `AttachmentService.php`, unchanged | `21782348c3cfd0f576cc9907873c9556592736d75f6753e36a9b3603a13da519` |
| Bundled `BoardService.php`, unchanged | `248272904c39be7f21522a7554068882d90bb4b7c5fdef1cd58beb8e2d406673` |
| Bundled `SecretContentGate.php`, unchanged | `740afce15357be84bd315eea9544c728d90c5cdcc6ae39bc188b146ccff0f21d` |
| `w04-attachment-preview-before.log` | `e49ecd9e25793da1c3422e5e53fb419eb510387d7588c9c268c89e38d791e397` |
| `w04-attachment-preview-after.log` | `687b03a8b0df190c5750b8bbc472bdb5e53597cb9801526ddefad4c5fecaa4ce` |

## Required next verification

Lead must freeze/publish the reviewed candidate and verify the installed board source matches it. A nonauthor verifier must then execute native installed HTTP nonimage preview **400** with the translated safe envelope, unauthorized image **403**, authorized image actual-byte controls and the existing private/deleted attachment gates at that same version. The original report's unsigned/signed/download distinctions must remain explicit. Actual module-test/HTTP/CI/Validation gates and Git integration remain separate pending statuses; no APP/TEST/runtime or operational-data changes were made by this task.
