#!/usr/bin/env bash
set -Eeuo pipefail

umask 077

readonly project_root=/home/mrdev/git/g7
readonly backup_root=/var/backups/g7-product
readonly database_name=g7_product
readonly stamp="$(date -u +%Y%m%dT%H%M%SZ)"
readonly backup_name="g7-product-${stamp}"

install -d -m 0700 "${backup_root}"
stage_dir="$(mktemp -d --tmpdir="${backup_root}" .partial.XXXXXX)"

cleanup() {
    case "${stage_dir}" in
        "${backup_root}"/.partial.*) rm -rf -- "${stage_dir}" ;;
    esac
}
trap cleanup EXIT

mysqldump --single-transaction --routines --triggers "${database_name}" \
    | gzip -9 > "${stage_dir}/database.sql.gz"

tar \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    -C "${project_root}" \
    -czf "${stage_dir}/persistent-files.tar.gz" \
    .env storage/app

runuser -u mrdev -- git -C "${project_root}" rev-parse HEAD \
    > "${stage_dir}/source-sha.txt"
runuser -u mrdev -- /usr/bin/php8.3 "${project_root}/artisan" migrate:status --no-interaction \
    > "${stage_dir}/migration-status.txt"
(
    cd "${stage_dir}"
    sha256sum database.sql.gz migration-status.txt persistent-files.tar.gz source-sha.txt \
        > SHA256SUMS
)

tar -C "${stage_dir}" -czf "${backup_root}/${backup_name}.tar.gz" .
sha256sum "${backup_root}/${backup_name}.tar.gz" \
    > "${backup_root}/${backup_name}.tar.gz.sha256"

printf '%s\n' "${backup_root}/${backup_name}.tar.gz"
