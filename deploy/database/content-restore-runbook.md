# 2026-10-04 full-backup content reconciliation

This is an offline, one-shot migration of verified original content, not a site seed.
The main request owns backup, rehearsal, integration, deployment and validation.
Never import the full source dump into production. Never use SQL client `--force`.

## Evidence and boundaries

Source artifact: `full-backup-20261004/g7-product-full.tar.gz.gpg`, captured
2026-10-04 04:07:06 UTC on 1PC, MySQL 8.0.46, source checkout
`c9e93660870901db12c4d69d525073f6e72c9fa9`. Backup delivery commit is
`49c64a0682a8e4e0450c3db8d2e7d9f678425e61`; it is not the source checkout SHA.
Source manifest requires 111 tables / 2,296 rows. Source migration history has
218 entries; AWS has 215. The three source-only migrations belong to uninstalled
CKEditor5 (two) and AI workspace (one); do not insert their history or install
those extensions as a side effect of this content migration.

Before any operation, fetch current main, compare `/srv/g7/current` HEAD, and
retain existing environment/storage symlinks and installed extension copies.
Only the assigned request worktree contains edits; release checkout receives
committed Git changes through fast-forward delivery.

## Backup and reference preparation

1. Run `sudo bash /srv/g7/current/deploy/aws/backup.sh`. The root-owned backup
   directory is 0700; archives/checksums are 0600. This includes all target rows
   (including private consultation leads/history), storage/app and /etc/g7-product.
2. Validate the generated checksums, `gzip -t` on the DB, and `tar -tzf` on both
   archives without printing file contents. Restore the SQL to a **new empty**
   `g7_restore_target_check` database. Compare every table's rows and an internal
   sorted-row digest against production, not just table counts. The reconciliation
   tool performs this comparison before opening its mutation transaction.
3. Use a root-only 0700 restore directory and canonical recovery-key file boundary.
   Do not print the key. Verify the encrypted SHA256SUMS, decrypt to that directory,
   compare plaintext archive SHA against the manifest, unpack the outer archive,
   verify its SHA256SUMS and both inner gzip/tar streams. Never unpack `.env` over
   production. Keep the original SQL untouched.
4. Run `mariadb-reference.py ORIGINAL_SQL NEW_REFERENCE_SQL`. Its only substitution
   removes the 14 explicit MySQL ngram parser clauses. Import that output to a
   **new empty** `g7_restore_source_reference`, without ignoring any SQL errors.
   Source SHA, migration status, all table counts and attachment relationships
   must match the archive manifest. This default-parser reference conversion is
   not a production DDL patch and does not promise equivalent CJK substring search.

## Merge decisions

The tool emits classification/counts for every source table and all ID mappings.

- A: all 11 real Pages, their 24 source versions, 18 original posts (16 published,
  two deleted), one original comment, one original board attachment. No source
  Page attachments exist. Preserve deleted status; never publish deleted test data.
- C: match boards by slug, roles/permissions by identifier and menus by slug.
  Restore missing public board metadata and only its missing dynamic roles,
  permissions, admin menus and additive grants. Existing rows, especially private
  board `raon-consultations`, remain identical. Source board 1 (`community`) must
  receive a different target ID from AWS board 1 (`raon-consultations`). Map all
  post/parent/comment/attachment references to allocated target IDs, keeping FKs on.
- Existing AWS Pages with matching slugs keep IDs and existing history rows.
  Source versions append with `target_version = source_version + AWS max(version)`;
  current_version is mapped by the same offset. Title/body/publication/SEO come
  from the original source Page. Exact source-to-target version mapping is emitted.
- Source authorship foreign keys become NULL, without inventing attribution to
  the AWS administrator. Public author display names remain part of the original
  post/comment content; source IP addresses become `0.0.0.0`. Source password hashes
  are not transferred. No users/user_roles/sessions/tokens/consents are imported.
- Preserve AWS extension installation state, templates/layouts/layout extensions,
  settings, notifications, jobs/cache/runtime state and authentication. Existing
  product navigation already declares the business Page URLs; source DB menus
  are administrative menus, not the product public header. Reuse the latest
  RAON Agent Factory shell and its public navigation. Do not import older shell
  layout rows or extension bundle files. Source-only AI requests/ecommerce profiles
  and notification/activity history are outside public content and remain excluded.

`restore-referenced-files.py` audits every archive regular file, rejects traversal,
symlinks/special files and collisions, then restores only relationship-verified
attachments. The exact source archive contains one 27-byte text/plain `.txt` file
on a deleted post; existing download/publication gates continue to apply. New dirs
are ubuntu:www-data 0750, file 0640; existing modes are not broadened. .env, settings,
old bundles, evidence scripts/screenshots and caches are audited but not copied.
Any new disk/MIME/extension needs explicit policy review, not an automatic import.

## Rehearsal and apply

Run the meaningful relationship/preservation regression against the isolated target
clone, canonical Installation smoke, public/admin Page and menu tests in a separate
testing DB. Never run Laravel tests using the runtime `.env` or production database.

```bash
sudo php deploy/database/reconcile-content.php \
  g7_restore_source_reference g7_product g7_restore_target_check > rehearsal.json
sudo python3 deploy/database/restore-referenced-files.py \
  ROOT_ONLY/persistent-files.tar.gz /var/lib/g7/storage/app \
  g7_restore_source_reference > storage-audit.json
```

Default reconciliation runs the complete transaction and rolls it back. Numeric
auto-increment gaps from rehearsal are harmless; final apply mappings are authoritative.
Review the emitted counts/operations/mappings; read its `plan_hash` programmatically.
Apply requires `--apply PLAN_HASH` after the three DB names and checks exact target
backup equality again. Unexpected SQL, FK, content, preserve-target or row-loss
conditions roll back the transaction. The source content already being present is
an explicit duplicate-import rejection. No target rows are deleted/truncated.
Do not reuse this one-shot tool for subsequent ongoing synchronization.

Copy validated attachments with the Python tool's `--apply` before committing DB
relationships; if SQL fails, an unreferenced attachment may remain, without data loss.
Do not call authenticated verification APIs between the latest target snapshot and
apply: token last-used timestamps legitimately change and invalidate snapshot equality.
If equality fails, create/verify a fresh backup and repeat the rehearsal.

## Delivery, runtime checks and cleanup

Commit scripts/tests/runbook and sanitized evidence, push without rewriting history,
integrate main, then fast-forward the existing runtime checkout to that SHA. No
frontend build or restart is necessary for deploy-layer-only changes. Preserve
runtime .env/storage and latest product extension versions/assets.

Check runtime migrations/extension status, admin auth/Page lists/detail/private leads,
public Page API body equality to source for all 11 slugs, SSR bot routes separately
from SPA HTML, settings, queue/scheduler and logs. In Chromium at 390x844 and 1440x900,
verify home, each business Page, header navigation and community, original service
copy rendered, no 5xx/pageerror/console error/broken images/overflow. HTTP contact
submission remains fail-closed; it is not a restore success criterion. Finally check
AI_GCS and MOBILE_STOCK health without restarting their services.

Archive sanitized before/after counts and final mapping in Git; retain canonical
root-only operational backups. Drop only the disposable reference/check/testing DBs
created for this request, remove the root-only plaintext restore directory, test
configuration and transient files. Do not remove production or canonical backups.

Rollback is scoped to the mapping and verified pre-migration archives. Freeze new
content writes before any rollback review; never restore the entire old DB over
new private leads. Revert only migrated Page fields, appended source histories and
inserted content rows after checking that no new relationships depend on them.
