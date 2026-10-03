#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
project_root=/srv/g7/current
backup_root=/var/backups/g7-product
install -d -m 0700 "$backup_root"
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
mysqldump --single-transaction --routines --triggers g7_product | gzip -9 > "$backup_root/database-$stamp.sql.gz"
tar --exclude='storage/logs/*' --exclude='storage/framework/*' -C "$project_root" -czf "$backup_root/persistent-$stamp.tar.gz" storage/app
tar -C /etc -czf "$backup_root/config-$stamp.tar.gz" g7-product
sha256sum "$backup_root"/*"$stamp"* > "$backup_root/checksums-$stamp.txt"
