# AWS MariaDB 구조 정합성 검증

운영 `g7_product`에 `g7-schema.sql`을 import하지 않는다. 스냅샷은 데이터·설치 이력이 없는 원본 MySQL 구조이며, native 설치 상태의 정답 테이블 수를 강제하지 않는다.

## 2026-10-04 판정

원본 snapshot/main `ca273d16a21abbfa1221c9e65c4c6ff23376bd8f`, 변경 전 배포 `89a85f519acc408473e6995cd9ff4ee861b77b9c`를 확인했다. MariaDB 10.11.14 운영 DB는 109개 테이블, utf8mb4/utf8mb4_unicode_ci, migration 215건(최대 batch 5)이다. 설치된 core/board/ecommerce/page/product migration은 pending 없이 일치한다.

- 원본 SQL의 ngram 정의는 **14개**다. 원본 reference import는 최초 게시글 FULLTEXT 정의에서 `1128: Function 'ngram' is not defined`로 중단했다. 오류를 무시하지 않았다.
- `mariadb-reference.py`는 원본을 수정하지 않고 별도 출력 파일에서 정확히 `/*!50100 WITH PARSER ngram */` 14개만 제거한다. 이는 **빈 reference DB 검토 전용**이다. MariaDB 기본 FULLTEXT 파서로 변환한 reference는 111개 테이블, 0행이다.
- 운영에 없는 `g7_ckeditor5_image_uploads`, `g7_raonslab_ai_requests`는 각각 미설치 CKEditor5와 AI Workspace 소유다. 해당 테이블의 22개 컬럼, 8개 인덱스 구성 행(복합 인덱스 컬럼 별 집계), 1개 FK만 누락으로 나타난다. 설치·활성화 요청 없이 이를 수동 생성하지 않는다.
- 나머지 109개 테이블의 엔진/정렬/주석, 컬럼 타입/NULL/기본값/주석/자동증가 속성, 인덱스(14개 FULLTEXT 포함), FK 및 규칙, CHECK는 일치한다. 유일한 raw 컬럼 차이는 mileage 파생 컬럼의 문자열 리터럴 `_utf8mb4` introducer이며 양쪽 컬럼 의미/타입/unique 인덱스는 동일하다. AUTO_INCREMENT 현재 카운터는 데이터 상태이므로 비교·변경하지 않는다.
- native `DatabaseFulltextEngine::isMariaDb()`/`supportsNgramParser()`가 서버를 감지하고 기본 파서 인덱스를 만든다. 별도 운영 compatibility migration이 필요하지 않다. 기본 파서는 ngram의 한국어 부분 문자열 검색과 동등하지 않다. 격리된 합성 문자열 검증에서도 영어 token/한국어 전체 token은 각각 1건, 한국어 내부 부분 문자열은 0건이었다. CJK 부분검색 동등성은 보장하지 않으며 MySQL 엔진 전환도 수행하지 않았다.
- 현재 실제 색인 검사는 내용이 있는 4개 인덱스가 healthy, 빈 10개 인덱스는 skipped다. `search:index --repair`나 DDL은 실행하지 않았다.
- 운영 DDL 변경·삭제·초기화는 0건이다. core migrate 실행과 설치 모듈별 dry-run은 `Nothing to migrate`다. Laravel `--force`는 production 확인 프롬프트만 해제하는 옵션이며 SQL import의 오류 무시 옵션과 다르다.

## 재검증 절차

먼저 runtime source SHA, Git remote main, 서비스, 비밀값을 출력하지 않는 설정 파일 권한, read/write 연결을 확인한다. `/srv/g7/current`의 `.env`와 storage는 기존 운영 경계를 유지한다.

1. `sudo bash /srv/g7/current/deploy/aws/backup.sh`로 DB/storage/app/설정을 백업한다. root 0700 디렉터리와 0600 파일, `gzip -t`, `tar -tzf`, checksum 파일의 `sha256sum -c`를 확인한다. 설정 archive의 내용을 출력하지 않는다. framework/cache/log는 canonical 백업 범위에서 제외된다.
2. backup dump를 **별도 빈 복원 DB**에 import해 테이블/행 수 및 핵심 테이블 checksum을 운영과 비교한다. 복원 검증이 끝난 격리 DB에는 credential/운영 데이터가 있으므로 검증 후 제거한다. 운영 DB는 수정하지 않는다.
3. 임의의 새 빈 reference DB를 생성한 뒤 원본 SQL을 import한다. ngram 오류에서 중단되는 것이 예상 결과다. SQL client `--force`를 사용하지 않는다.
4. 별도 변환본을 만들고 다른 새 빈 reference DB에 import한다:

```bash
python3 deploy/database/mariadb-reference.py deploy/database/g7-schema.sql /tmp/g7-mariadb-reference.sql
# 아래 대상은 사전에 생성한 빈 reference DB임을 반드시 확인한다.
sudo mariadb g7_schema_reference < /tmp/g7-mariadb-reference.sql
sudo python3 deploy/database/schema-diff.py g7_product g7_schema_reference > schema-diff.json
```

`schema-diff.py`는 information_schema만 SELECT한다. 테이블/컬럼/인덱스/FK/규칙/CHECK의 raw metadata를 기록하며 행 데이터나 접속 비밀값은 출력하지 않는다. 이 diff를 자동 production DDL로 변환하지 않는다. 미설치 확장 소유 테이블, DB 엔진 표현 차이, 실제 pending migration을 각각 판단한다.

5. production `migrate:status`와 설치 확장의 migration 경로별 상태/dry-run을 확인한다. 필요한 native migration이 있을 때만 백업 후 적용한다. 이력 행을 직접 조작하지 않는다.
6. `php deploy/aws/verify-runtime.php /srv/g7/current`로 DB read/write 대상 검증과 관리자 API/공개 Page API 상태를 확인한다. 출력은 status/boolean/행 수뿐이다. 관리자 token은 `/etc/g7-product/verification-token`에서 메모리로만 읽는다.
7. 기존 private lead 보존과 공식 Service의 비식별 생성→관리자 조회→동일 키 반복 무증가를 검증한다. 공개 HTTPS/개인정보 게이트를 열지 않는다. 최종 backup과 상태 증거를 보존한다.

## 콘텐츠 blocker

운영의 회원 1건, Page 6건(비공개 기본 콘텐츠), 기존 게시글 3건은 보존했다. 요청 단위 합성 private lead 한 건만 추가했다. 공개 business Page API는 about/service/cases/technology/faq/contact/privacy/terms 모두 404이며 SPA HTML 200을 콘텐츠 성공으로 해석하지 않는다.

원본 1PC 회원/게시글/Page 본문/관리자/상담/migration rows는 Git에 없다. 원본 데이터가 복원됐다고 주장할 수 없다. 기존 설치를 유지한 상태에서 승인된 원본 export와 충돌·소유권·파일 참조를 검토하는 별도 데이터 이관이 필요하다. 공개 Contact 검증 완료에는 HTTPS와 승인된 개인정보 설정도 필요하다.

전체 구조 diff와 비밀값 없는 API/브라우저 검증은 [evidence](../../docs/evidence/aws-schema-20261004/summary.json)에 보존한다.
