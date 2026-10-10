# W04 TEST 기준선 복구 2 — RECOVERY ONLY

**PASS_BASELINE_RECOVERY_ONLY.** 독립 복구 Request `req_dd6208f86a0b487796de37b9c4365cd4`가 2026-10-09 16:35:48 UTC에 TEST `req81_travel_lab_test`를 원본 **55테이블 / 104행**으로 복구하고 TEST 소유권을 해제했다. 전체 테이블 집합과 각 테이블의 모든 행·중복·NULL·DDL 해시 및 객체 inventory가 원본과 정확히 같다. 설치 검증 결과나 공식 Validation PASS가 아니다.

고정 제품 소스는 `1052e3fb4bc4cccabb51b8c538116c78655f345b`, tree는 `f18fa2056a031353c889785d768f87824e3083f5`이다. 기본 HEAD `6853f40`은 대상에 사용하지 않았다. 지정 SHA를 fetch한 뒤 assigned Request에서만 detached checkout했다. 제품 파일 수정은 없고 공개 API·버전 제약 동기화 대상도 없다. 이번 자료는 로컬 scoped commit으로만 전달하며 push/merge/deploy/공식 자식 Request는 실행하지 않는다. 후속 fresh 설치와 독립 P3 수리는 root lead가 소유한다.

## 실제 원본·현재·최종 상태

| 단계 | 테이블 | 행 | 전체 inventory SHA-256 |
| --- | ---: | ---: | --- |
| 실패 req3e의 보존된 원본 | 55 | 104 | `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e` |
| 이번 변경 전 실제 TEST / safety backup | 55 | 419 | `f9ba89d77dac19888cfe5982292c782d08b640453493ceaacea923dc1456dfe1` |
| 네이티브 wipe 직후 | 0 | 0 | [empty.json](w04-test-recovery-2/evidence/empty.json) |
| 복구 및 release 직전 재확인 | 55 | 104 | `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e` |

원본 dump SHA-256은 `f8e6aed98484864daf1ae08436c8e229d4eeda5774f1afec5a6a80e1ddb447ff`이며, 매니페스트 주장에만 의존하지 않고 복사 전·후 및 restore 직전에 실제 dump로 검증했다. 현재 손상 상태의 별도 safety dump SHA-256은 `f15e10a0ff973f84e9a37c903b458e65278af00ab4577f322462e0fecacd15f2`이다. view/trigger/routine/event는 모든 단계에서 0이었다. [각 테이블 원본](w04-test-recovery-2/evidence/original-before.json), [손상 상태](w04-test-recovery-2/evidence/current-before.json), [최종](w04-test-recovery-2/evidence/final.json)은 행 수·행 해시·DDL 해시만 포함한다.

원본 출처는 실패 Request의 `storage/framework/testing/w04f-original/`이다. `snapshot.sql`, `before.json`, `manifest.json`, `phase.json`, `BLOCKED`만 승인된 읽기 전용 출처로 읽어 자체 private 디렉터리에 보존했다. 실패 Request의 `mysql.cnf`, env, private stdout/stderr는 읽지 않았다. 앞선 경로 검사에 `w04-original` 오타가 있었으나 올바른 `w04f-original`을 확인한 뒤 진행했다. 실패 Request의 snapshot/before/manifest/phase/BLOCKED 해시는 최종 복구와 이후 파일 검증에서도 모두 불변이다. 실패 Request의 파일·잠금·프로세스는 수정·삭제·종료하지 않았다.

자체 보관 경로는 `storage/framework/testing/w04-test-recovery-2/`이다. 원본 복사본, 출처 phase/BLOCKED 복사본, 손상 상태 safety dump와 inventory, 실패 launcher 로그, TEST-only options를 유지한다. 디렉터리는 0700, private 파일은 0600이며 Git에 포함하지 않는다. 성공 private phase는 `verified_exact_baseline_test_released_backups_retained`이다. 자체 BLOCKED 파일도 검증 완료/백업 보존 문구로 남겼으며 실패 원본의 BLOCKED는 변경하지 않았다. 실패하면 모든 백업과 BLOCKED를 유지하고 blind retry를 거부한다.

## 프로세스·잠금·연결 경계

canonical FAILED는 사용자 제공 상태이며 quiescence 증거로 사용하지 않았다. 최초 일반 사용자 `/proc` 검사에서는 일부 읽기 권한이 없어 충분하지 않았다. 16:30:14 UTC의 권한 포함 읽기 전용 검사와 이후 prepare/wipe/import/final 검사에서는 정확한 실패 req3e cwd/하위 cwd, 개별 NUL argv의 경로 경계, 파일 FD 경로가 모두 **0개**, unreadable PID도 **0개**였다. 실패 checkout의 `w04f-exclusive.lock`은 존재하지만 `/proc/locks`의 inode 기반 소유자는 **0명**이었다. 셸 본문 substring을 프로세스 소유권으로 취급하지 않았다. [process-check.py](w04-test-recovery-2/process-check.py)가 이 경계를 재검증한다.

각 DB 단계에서 TCP `127.0.0.1:3306`의 `SELECT DATABASE(), CURRENT_USER(), CONNECTION_ID()`가 TEST / `req81_travel@127.0.0.1`임을 확인했다. scoped SHOW PROCESSLIST와 기존 로컬 소켓 권한의 읽기 전용 **정확한 TEST DB 필터**로 다른 계정의 TEST 연결도 확인했다. 준비 connection 94357, 변경 전 94382, native 유효 앱 guard 94386, empty 94391, final 94394, release 94396에서 자체 ID만 있었고 다른 TEST 연결은 **0개**다. 마지막 실패 Request 프로세스·FD·잠금 관찰은 16:35:47.827695 UTC이며 모두 0이다. 초기 읽기 전용 account PROCESSLIST 관찰에는 동일 계정의 APP Sleep 연결이 보였지만 해당 연결이나 APP schema를 조작하지 않았다. 이후 복구 관찰은 TEST DB 필터만 사용했다.

자격증명은 승인된 부모 `.env.testing`만 보호된 프로세스 안에서 파싱하고 필요한 TEST DB 필드만 취했다. 부모/root APP `.env`, 플랫폼 설정, 프로세스 env를 읽거나 출력하지 않았다. 자체 checkout에는 새 TEST-only `.env`/`.env.testing`을 생성하고 보호했으며 기존 파일은 덮어쓰지 않았다. URL/socket/SSL 및 inherited DB/cache/installer overrides를 거부하고 native 앱의 read/write DB·host·port·account·adapter 설정을 확인했다. 필요 Composer 의존성은 자체 vendor에 lock 기준 `COMPOSER_DISABLE_NETWORK=1 composer install --no-interaction --prefer-dist --no-scripts --no-plugins`로 준비했다. 서비스/계정/권한/전역 설정/generic setup이나 G7 fresh 설치는 수행하지 않았다.

## 실제 명령과 사전 검증 실패

진입점은 [test-baseline-recovery.php](w04-test-recovery-2/test-baseline-recovery.php)의 `prepare`, `restore`, 확인된 사전 검증 오류에 한정한 `resume-verified-preflight`이다. SQL scanner가 원본 및 safety dump의 정확한 CREATE/DROP 테이블 집합과 local 식별자·허용된 세션 변수만 검증한다. 다른 schema, view/trigger/routine/event, GLOBAL/PERSIST, 알 수 없는 executable directive는 거부한다. restore는 safety 해시·원본 해시·현재 전체 inventory 불변성과 다른 TEST 연결 부재를 재검사한다. native 앱 bootstrap 이후, 실제 wipe 진입 전에도 current inventory를 다시 비교했다.

첫 restore launcher는 제가 Composer autoload를 먼저 불러오지 않아 `guard.php`의 Dotenv class 조회에서 Error로 중단됐다. 실제 artisan/wipe/import는 실행되지 않았고 유효 config 기록도 없었다. 읽기 전용 진단으로 이 오류를 DB 접근 전에 재현했으며 당시 TEST는 safety inventory **55/419와 완전히 동일**했다. autoload를 추가한 사전 검증은 통과했다. 실패 로그·result·failure/BLOCKED를 보존하고, 해당 오류·변경 없음·백업 무결성·live handle 없음이 확인된 경우에만 1회 이어갈 수 있는 별도 guard를 사용했다. 이는 fresh retry가 아니며 완료한 task key/원본 snapshot/자격증명을 재생성하지 않았다. 재개 marker가 있어 같은 재개를 반복하지 못한다.

| 실제 작업 | 횟수 | exit | 시간 |
| --- | ---: | ---: | ---: |
| 현재 손상 상태 `mysqldump` safety backup | 1 | 0 | 0.196초 |
| DB 접근 전 실패한 native launcher | 1 | 1 | 0.169초 |
| guarded native `php artisan db:wipe --database=mysql --drop-views --force --no-interaction` | 1 | 0 | launcher 포함 7.539초 |
| TEST-only native `mysql` 전체 import | 1 | 0 | 2.438초 |
| 복구 이어가기 및 최종 검증 전체 | 1 | 0 | 14.302초 |

성공 wipe launcher PID는 131498, native import PID는 131863이다. [result.json](w04-test-recovery-2/evidence/result.json)에 실제 argv·PID·연결 guard·시간·최종 release가 있다. mysql은 private `--defaults-file`, 명시적 TCP/host/port/account/TEST database, `--local-infile=0 --skip-reconnect`로 실행했다. native Artisan 변경 명령은 1개, native import는 1개, fresh-install/회귀 PHPUnit 실행은 0개다. 단순 helper 파일·로그 존재를 실행 성공으로 취급하지 않았다.

## 검사·검토·후속 소유자

[checks.json](w04-test-recovery-2/evidence/checks.json)에 6개 PHP lint, Pint, 실제 원본/safety SQL 경계 검사, DB 접근 없는 위험 SQL 거부 11건과 inherited override 거부 10건, 백업 권한·원본 불변성 검사를 기록했다. [source-manifest.json](w04-test-recovery-2/evidence/source-manifest.json)은 최종 helper 파일 해시와 고정 제품 소스/tree를 결합한다. helper source, Laravel WipeCommand, TEST config 및 guard를 작성자가 검토했다. 별도 비작성자 helper review와 공식 Validation/CI는 **NOT_RUN**이다. recovery 실행자는 제품 변경 작성자가 아니지만 자체 helper 검토를 독립 공식 검증으로 표시하지 않는다.

실패 req3e의 안전한 JSON metadata와 부모 `W04_INSTALL_ATTEMPT2_INTAKE.md`를 읽었고 이전 실패/설치 상태를 바꾸지 않았다. 최초 baseline과 6개 완료된 native 명령 기록은 [failed-safe-metadata.json](w04-test-recovery-2/evidence/failed-safe-metadata.json)에 출처 해시와 함께 보존했다. page/ecommerce/travel/template 전체 설치, HTML/kernel/회귀 및 P3 수리는 이 Request에서 실행하지 않았다. 과거 **128테이블/639행 보존 NOT_PROVEN**도 유지한다.

**다음 TEST 소유자 준비 완료:** root lead가 새로운 소유권과 최신 고정 소스를 정한 뒤 후속 작업을 진행할 수 있다. 이 Request는 release 이후 TEST DB에 추가 접근하지 않는다. APP/Spring/production, 계정·grants·서비스·providers는 변경하지 않았다. 실패 Request와 모든 원본/safety 사본은 유지하며 cleanup 권한을 추정하지 않는다.
