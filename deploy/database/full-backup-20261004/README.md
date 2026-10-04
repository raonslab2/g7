# G7 전체 백업 — 2026-10-04

1PC의 G7 운영 데이터를 선별하지 않고 기존 `scripts/g7-backup.sh`로 통째로 백업했다. 이전 `g7-schema.sql`과 달리 **테이블 구조와 모든 행 데이터**를 포함한다.

- [암호화 백업](g7-product-full.tar.gz.gpg): 3,974,362바이트
- DB: `g7_product`, MySQL 8.0.46, **111개 테이블·2,296행**
- 영속 파일: **78개** (`.env`와 `storage/app` 전체)
- 회원·관리자·게시글·비즈니스 Page 본문·버전·메뉴·권한·확장 설치 상태·마이그레이션 이력 등 DB 전체 포함
- 원본 소스 SHA, 마이그레이션 상태, 내부 파일 체크섬 포함
- 복원 검증: 전체 SQL import 및 111개 테이블 `CHECK TABLE` 통과, 내부 체크섬 일치, 복호화 결과와 원본 archive 바이트 일치

전체 테이블별 행 수와 파일 해시는 [manifest.json](manifest.json)에 기록했다. 원본 DB 및 AWS DB를 변경하지 않았으며, 복원 검증용 임시 DB는 제거했다. 소스·의존성은 이 데이터 archive에 중복 포함하지 않으며 소스 기준은 archive의 `source-sha.txt`를 따른다. 실행 로그와 framework cache는 영속 데이터 백업 범위 밖이다.

## 복호화 키

사용자의 명시적 후속 요청에 따라 [복호화 키](backup-passphrase.txt)도 같은 공개 Git 저장소에 추가했다. 저장소를 읽을 수 있는 누구나 비밀번호·세션·설정 비밀값을 포함한 전체 백업을 복호화할 수 있다. 키 파일을 나중에 삭제해도 과거 Git 이력에는 남는다. 1PC 원본 키도 유지한다(디렉터리 `0700`, 파일 `0600`).

```text
/home/mrdev/.g7/backups/git-20261004T040706Z/backup-passphrase.txt
```

원본 archive는 `/var/backups/g7-product/g7-product-20261004T040706Z.tar.gz`에 있다. 다른 서버에서도 함께 제공된 키 파일로 백업을 열 수 있다.

## 백업 열기

저장소 루트에서 실행한다. 함께 제공된 복호화 키 경로를 사용한다. 아래 명령은 운영 경로에 덮어쓰지 않고 새 임시 디렉터리에 백업을 연다.

```bash
set -euo pipefail
umask 077
backup_key_file=deploy/database/full-backup-20261004/backup-passphrase.txt
restore_dir="$(mktemp -d)"

(cd deploy/database/full-backup-20261004 && sha256sum -c SHA256SUMS)
gpg --batch --pinentry-mode loopback --no-symkey-cache \
  --passphrase-file "$backup_key_file" \
  --output "$restore_dir/g7-product-full.tar.gz" \
  --decrypt deploy/database/full-backup-20261004/g7-product-full.tar.gz.gpg

tar -xzf "$restore_dir/g7-product-full.tar.gz" -C "$restore_dir"
(cd "$restore_dir" && sha256sum -c SHA256SUMS)
gzip -t "$restore_dir/database.sql.gz"
gzip -t "$restore_dir/persistent-files.tar.gz"
```

열린 archive에는 `database.sql.gz`, `persistent-files.tar.gz`, `migration-status.txt`, `source-sha.txt`, `SHA256SUMS`가 있다. `persistent-files.tar.gz` 안에 `.env`와 `storage/app`이 들어 있다. 복호화된 파일은 비밀정보가 포함된 전체 백업이므로 Git에 추가하지 않는다.

## 복원 대상

SQL 복원 검증은 원본과 같은 MySQL 8.0.46의 별도 빈 DB에서 수행했다. AWS 운영은 MariaDB이며 원본의 `ngram` FULLTEXT 파서 정의를 그대로 지원하지 않는다는 기존 검증 결과가 있다([구조 정합성 기록](../mariadb-reconciliation.md)). 따라서 이 파일을 AWS 운영 DB에 바로 덮어쓰지 않는다.

AWS 실제 이관 시에는 기존 AWS 백업을 먼저 보존하고, 별도 복원 DB에서 MySQL/MariaDB 호환성 및 기존 AWS 데이터와의 충돌을 확인한다. 원본 `.env`의 URL·DB·경로·비밀값은 AWS 설정으로 무조건 덮어쓰지 않는다. 이번 작업은 **전체 백업 생성·검증·Git 업로드까지**이며 AWS 적용은 수행하지 않았다.
