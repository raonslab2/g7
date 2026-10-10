# W04 TEST 원본 기준선 복구

**PASS_BASELINE_RECOVERY_ONLY.** 2026-10-09, 비작성자 Request `req_4be0e82d6e2e4996bf501f0aef20aa28`가 손상된 `req81_travel_lab_test`의 **22테이블/21행**을 보존한 뒤 원본 **55테이블/104행**으로 복구했다. 전체 테이블 집합, 모든 행의 중복·NULL 보존 해시, `SHOW CREATE TABLE` 해시와 기타 객체 경계가 원본 매니페스트와 정확히 같다. TEST 소유권을 해제했으며 추가 DB 접근이나 설치 테스트를 실행하지 않는다.

대상 제품 소스는 `598a89fff702d51c1405f1a5952d95ab1d2651f4`, 트리는 `9e00273bdf18d6a713343755aac54f44a9b032b4`, 요청된 소스 브랜치는 `feat/g7-travel-lab-c7ae42d1`이다. 그 브랜치는 부모 worktree에서 사용 중이므로 이 checkout은 정확한 SHA를 fetch/checkout한 뒤 **로컬 전용** `recovery/w04-test-baseline-req4be0e82`에 복구 자료를 기록한다. 부모 브랜치나 checkout은 변경하지 않았다.

## 전체 기준선과 증거

| 상태 | 테이블 | 행 | 전체 행·DDL·객체 inventory SHA-256 |
| --- | ---: | ---: | --- |
| 보존된 원본 | 55 | 104 | `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e` |
| 이번 복구 직전 손상 상태 | 22 | 21 | `da29421ef27b5e3ce8af451e8be1de42f18e2c6d8a19730f5c73d66889a07739` |
| 네이티브 wipe 직후 | 0 | 0 | [empty.json](w04/evidence/test-baseline-recovery/empty.json) |
| 최종 복구 상태 | 55 | 104 | `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e` |

원본 SQL dump SHA-256: `5ef6d31adabf2ec8f4508c5e1dc6acda9dcb48f5ccd0d6ccb8b9e5493bf672ef`.
손상 상태의 별도 safety dump SHA-256: `dc0974bd4b96d809dd39ccb34b80255cb7fe2ba6a5fdb7740fc21b166397e555`.

[original-before.json](w04/evidence/test-baseline-recovery/original-before.json), [current-before.json](w04/evidence/test-baseline-recovery/current-before.json), [final.json](w04/evidence/test-baseline-recovery/final.json)에 모든 테이블의 행 수·행 해시·DDL 해시가 있다. 행 값이나 DDL 원문은 포함하지 않는다. 원본은 기존 공개 support-provision inventory와도 전체 객체가 일치한다. 기타 view/trigger/routine/event는 원본·현재·최종 모두 0이며, 존재했으면 이 복구는 변경 전에 중단하도록 설계했다.

## 실행 경계와 실제 핸들

실패한 `req_337b3638df9b4b958b0627e0b262a54a` checkout의 HEAD를 다시 확인했고, `/proc`에서 정확한 cwd/하위 cwd 및 **개별 argv 경로 prefix**를 검사했다. 셸 본문의 광범위 부분문자열 검색을 사용하지 않았다. 준비·wipe·import·최종 확인 모두 관련 프로세스 핸들 0이며 다른 프로세스를 종료하지 않았다.

실제 `SELECT DATABASE(), CURRENT_USER()`는 `req81_travel_lab_test` / `req81_travel@127.0.0.1`이었다. 준비 측정의 자체 connection ID는 83526이다. 변경 직전 83670, native wipe의 유효 앱 설정 확인 83690, 빈 스키마 확인 83707, 최종 전체 검증 83737이었다. 변경 전부터는 scoped `SHOW PROCESSLIST` 외에 **정확한 TEST 이름으로 필터한 읽기 전용 로컬 소켓 PROCESSLIST 쿼리**를 추가해 다른 계정 연결도 검사했다. 각 검사에는 해당 자체 ID 하나만 있었고 다른 TEST 연결은 0이다. 이 읽기 전용 관찰은 계정/권한/다른 스키마를 조회하거나 변경하지 않는다. 유효 앱 read/write DB, host, port, account, URL/socket/SSL 부재 및 array mail/cache/session, sync queue, local storage/search도 wipe 전에 검증했다.

원본 dump/manifest/before만 실패 checkout에서 이 Request의 전용 recovery 디렉터리로 복사했다. 원본 디렉터리·복구 디렉터리는 0700, snapshot/manifest/options/TEST env는 0600이고 symlink를 거부했다. 자격증명은 승인된 부모 `.env.testing`을 보호된 프로세스 안에서 파싱하여 TEST DB 필드만 취했다. APP 환경, 플랫폼 자격증명, root 계정 회전, setup.php/extensions.php는 사용하지 않았다. own Composer dependencies를 `composer install --no-interaction --prefer-dist --no-scripts`로 준비했으며 다른 Request의 vendor를 연결하지 않았다.

실패 checkout의 원본 snapshot/manifest/before/**BLOCKED** 파일을 최종 SHA-256으로 재확인했고 모두 변경되지 않았다. 본 Request의 원본 복사본과 손상 상태 safety backup도 보관한다. dump/options/env/private 로그는 Git에 포함하지 않는다. 정리 권한을 추정하지 않으며 원본 snapshot은 항상 보존한다. 전용 private `phase.json`은 `verified_exact_baseline_test_released_backups_retained`를 기록한다. 예외 시에는 단계와 예외 클래스·소스 위치를 기록하고 모든 backup을 남기며 자동 재시도하지 않는다.

## 명령, 시간 및 검토

실행 진입점은 [test-baseline-recovery.php](w04/test-baseline-recovery.php)의 `prepare`, 그 결과를 검토한 뒤 `restore`였다. 민감한 stdout/stderr는 private로 유지했다. 첫 준비 시 quoted isolation marker를 기존 guard가 거부하여 **DB 접근 전 중단**했다. 이 Request가 만든 두 TEST env만 범위를 확인해 제거하고 marker 직렬화를 교정했다. 원본 복사본은 덮어쓰지 않고 해시 일치 여부로 재사용했다. 수정 후 준비 및 복구는 각각 exit 0이었다.

| 실제 네이티브 작업 | 횟수 | exit | 시간 |
| --- | ---: | ---: | ---: |
| 손상 상태 전체 `mysqldump` safety backup | 1 | 0 | 0.082초 |
| 보호된 `artisan db:wipe --database=mysql --drop-views --force --no-interaction` | 1 | 0 | 2.479초 |
| TEST-only options, 명시적 TEST database를 사용한 native `mysql` 전체 import | 1 | 0 | 1.954초 |
| 준비 전체 | — | 0 | 0.475초 |
| 복구 및 전체 최종 검증 | — | 0 | 5.215초 |

wipe launcher PID는 4102125, import PID는 4102301이다. [result.json](w04/evidence/test-baseline-recovery/result.json)에 실제 명령 배열·PID·connection ID·각 읽기 전용 관찰 시간·결과가 있다. 네이티브 Artisan 변경 명령은 **1개**, native import는 **1개**, fresh-install 명령/회귀 PHPUnit 실행은 **0개**다. 읽기 전용 연결 관찰과 SQL 형식 검사 subprocess는 설치 테스트 수에 더하지 않는다.

원본 guard/snapshot/runtime-bootstrap/InstallerContext/config 및 Laravel native WipeCommand의 실제 소스를 검토했다. 기존 snapshot helper의 성공 후 삭제 절차를 직접 호출하지 않고 별도 보관형 복구 launcher를 작성했다. SQL scanner는 문자열 값을 출력하지 않고 local CREATE/DROP/INSERT/LOCK/ALTER KEYS 및 dump 세션 변수만 허용하며 정확한 테이블 집합을 검증한다. 외부 DB 선택·한정 테이블·GLOBAL/PERSIST·추가 객체·불명 executable directive는 거부한다. native wipe 전에 original SHA와 SQL 경계, current safety SHA 및 현재 전체 inventory의 불변성을 다시 검사했다.

helper PHP lint, 실제 dump scanner 및 DB 접근 없는 SQL 거부 사례 검사는 PASS다. `checks.json`/`source-manifest.json`은 최종 helper 해시와 원본 제품 소스 결합을 기록한다. 이 Request는 제품 작성자가 아니지만 복구 helper 자체는 실행자가 작성·검토했다. 별도 독립 helper review나 공식 Validation을 수행한 것으로 표시하지 않는다.

## 남은 설치 판정

이번 결과는 **기준선 복구만 PASS**다. 기존 공식 `w04-repaired-native-install` Request는 FAILED 상태를 유지한다. 실패 checkout의 안전한 메타데이터를 읽어 18개 native 설치 명령(exit 0, 합계 88.357초), HTML 제품 2개와 kernel 요청 37개 기록을 확인했다. 완료된 회귀 기록은 mysql-workflow 1/31와 support-api 7/90, 합계 **8 tests / 121 assertions**뿐이다. support-provision 중단 뒤 22테이블로 남았던 상태를 이번에 복구했을 뿐 전체 신규 설치 검증 완료로 바꾸지 않는다.

부모 intake의 `vendor_mode auto != bundled` persistence finding은 **FAIL/미해결**로 보존하며 여기서 재측정하거나 고쳤다고 주장하지 않는다. 나머지 회귀·새 고정 소스 fresh-install·UI/auth/vendor persistence 재검증은 NOT_RUN/UNKNOWN 상태를 유지한다. 과거 **128테이블/639행**의 보존은 **NOT_PROVEN**이다. APP/preview/accounts/grants/env/services/Spring/prod를 변경하지 않았고 product edits, push, merge, deployment, official grandchildren 및 canonical Validation을 수행하지 않았다. TEST는 후속 새 고정 소스 검증을 위해 해제되어 있다.
