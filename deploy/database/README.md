# G7 DB 구조 스냅샷

**데이터까지 필요한 경우 [G7 전체 백업](full-backup-20261004/README.md)을 사용한다.** 전체 DB와 `.env`·`storage/app`을 포함한 암호화 archive이며, 아래 SQL은 구조 확인용이다.

[`g7-schema.sql`](g7-schema.sql)은 2026-10-04에 1PC의 `g7_product`에서 추출한 **데이터 없는 구조 전용 SQL**이다. AWS에서 확보한 소스와 함께 DB 구조를 확인하거나 빈 검토용 DB를 만들 때 사용한다.

| 항목 | 값 |
| --- | --- |
| 원본 DB | MySQL 8.0.46 |
| 원본 런타임 소스 | `c9e93660` |
| 테이블 | 111개, 접두사 `g7_` |
| 기본 문자셋 / 정렬 | `utf8mb4` / `utf8mb4_unicode_ci` |
| 포함 | 테이블·컬럼·기본값·주석·인덱스·외래키 |
| 원본의 뷰 / 프로시저·함수 / 트리거 / 이벤트 | 모두 0개 |

회원, 게시글, Page 본문, 상담 내역, 관리자 계정, 확장 등록 상태, 마이그레이션 이력을 포함한 **모든 행 데이터**는 제외했다. `.env`, 비밀번호, 인증 토큰, 업로드 파일, 파일 기반 설정도 포함하지 않는다. 테이블의 현재 `AUTO_INCREMENT` 카운터는 제거했으며 컬럼의 자동 증가 속성은 유지한다.

## 빈 DB에 구조 불러오기

저장소 루트에서 실행한다. 접속 호스트·사용자는 대상 환경에 맞추고, 비밀번호는 `-p` 프롬프트로 입력한다.

```bash
mysql -h 127.0.0.1 -u DB_USER -p \
  -e 'CREATE DATABASE g7_schema_reference CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'

mysql --default-character-set=utf8mb4 -h 127.0.0.1 -u DB_USER -p \
  g7_schema_reference < deploy/database/g7-schema.sql

mysql -h 127.0.0.1 -u DB_USER -p -e \
  "SELECT COUNT(*) AS table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA='g7_schema_reference';"
```

예상 테이블 수는 `111`이다. `CREATE DATABASE` 권한이 없다면 관리자가 빈 DB를 먼저 준비하고 그 이름으로 import한다. SQL에는 `CREATE DATABASE`, `USE`, `DROP TABLE`, 데이터 삽입문이 없으며 대상 DB는 명령줄에서 지정한다.

**기존 AWS 운영 DB에 그대로 실행하지 않는다.** 이 파일은 차이만 적용하는 마이그레이션이 아니므로 기존 테이블과 충돌한다. `--force`로 오류를 무시하지 않는다. [AWS 배포 기록](../aws/README.md)의 운영 DB는 이미 설치된 상태이며 MariaDB를 사용한다. 원본 import는 로컬 MySQL 8.0.46에서 성공했다. AWS MariaDB 10.11.14에서는 별도 reference DB의 원본 import가 ngram 오류로 중단되는 것을 확인했고, 명시적 별도 변환본으로만 111개 구조를 검증했다([정합성 기록](mariadb-reconciliation.md)).

스냅샷을 전수 스캔한 결과 FULLTEXT 인덱스 14개 (게시글·게시판·신고 로그·이커머스·Page)는 MySQL `WITH PARSER ngram`을 사용한다. 대상 DB에서 해당 파서를 지원하는지 확인해야 하며, MariaDB 호환 SQL로 간주하지 않는다. 원본 검색 구조를 보존하기 위해 이 정의를 임의로 제거하지 않았다.

구조만 불러온 DB는 G7 설치·복원이 완료된 DB가 아니다. `g7_migrations`도 빈 테이블이므로 그 상태에서 `php artisan migrate`를 실행하면 기존 테이블 생성과 충돌할 수 있다. 새 운영 설치는 프로젝트의 기본 설치·시더·확장 설치 절차를 사용하고, 기존 사이트 복원은 별도의 승인된 데이터 및 영속 파일 이관으로 진행한다.

## 검증 결과 (2026-10-04)

- 로컬 MySQL 8.0.46의 새 임시 DB에 import 성공: 111개 테이블, 전체 행 수 0.
- 원본과 복원본의 모든 컬럼 메타데이터(타입·기본값·문자셋·정렬·주석 포함) 일치.
- 테이블 정의는 현재 자동 증가 카운터와 정렬에 이미 내포된 문자셋의 중복 표기 차이만 제외하면 원본과 일치.
- 데이터 삽입·변경·삭제문, `DROP`, `CREATE DATABASE`, `USE`, `DEFINER`, `GTID_PURGED` 없음.
- 검증용 DB는 검증 후 삭제했으며 원본 DB는 변경하지 않음.

## 스냅샷 갱신

원본 서버에서 구조만 추출한다. 다음 명령은 원본 DB를 변경하지 않는다.

```bash
set -o pipefail
sudo mysqldump --no-data --skip-add-drop-table --skip-lock-tables \
  --no-tablespaces --set-gtid-purged=OFF --skip-comments \
  --default-character-set=utf8mb4 g7_product \
  | sed -E '/^\) ENGINE=/s/ AUTO_INCREMENT=[0-9]+//; s/[[:blank:]]+$//; ${/^$/d;}' \
  > deploy/database/g7-schema.sql
```

갱신 시 추출일·원본 소스 커밋·테이블 수를 이 문서에 반영한다. 뷰·프로시저·함수·트리거·이벤트가 추가되었다면 별도 검토하여 누락과 `DEFINER`·환경별 참조 여부를 확인한다. 커밋 전 데이터 삽입문과 비밀값이 없는지 확인하고, 새 임시 DB에 import하여 테이블 정의와 빈 데이터 상태를 검증한다.

AWS MariaDB 10.11에서 수행한 별도 reference import·운영 schema 비교·복원 검증은 [MariaDB 정합성 runbook](mariadb-reconciliation.md)을 참고한다.
