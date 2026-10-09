# W04 native 설치 독립 최종 검증 — attempt3

**최종 gate: CHANGES_REQUIRED / 회귀 FAIL.** 실제 빈 TEST 설치, 설치본 HTML 서비스·인증·HTTP·재시작/환경 복구·native migration/fallback 복구는 아래 한정 범위에서 PASS다. 고정 소스의 `LiveMysqlTest`는 **1 test / 8 assertions, FAIL**이다. 이를 다른 HTTP 성공으로 대체하지 않는다. 모든 파괴적 실행 이후 원본을 복원했고 **TEST RELEASE: 2026-10-09T17:53:26+00:00 (KST 2026-10-10 02:53:26)**를 기록했다.

Request `req_caef46f3473043048b5b4bc2ec41eaea`, 비작성자 CODEX attempt3. 최초 작업은 origin `feat/g7-travel-lab-c7ae42d1` fetch 및 정확한 제품 SHA `fa5523175ac494cfbd13bbf89bf06b3ec91835a6`, tree `fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c` checkout이었다. 기본6853은 사용하지 않았다. 제품 파일은 변경하지 않았으며 자체 보고서/helper/evidence만 로컬 Git에 기록한다. 이후 snapshot의 `source_sha/tree`는 문서/helper checkpoint를 포함한 harness HEAD일 수 있다. **실제 제품 검증 대상은 위 fa552317/tree fa685339**이며 [2266파일 intake hash](w04-native-final/evidence/intake/source.json)와 [재시작 전후 source/bundle 비교](w04-native-final/evidence/server/source-bundle-persistence.json)가 해당 파일의 원본 blob 일치를 증명한다.

루트/확장 AGENTS, `deploy/travel-lab/README.md`, W04_FRESH_INSTALL_REVIEW, W04_TEST_BASELINE_RECOVERY_2, W04_INSTALL_ATTEMPT2_INTAKE, W04_FINAL_INTAKE_REVIEW, W04_ATOMIC_THROTTLE_REVIEW를 읽었다. 허용된 실패 Request req3e70의 final-install, reqdd620의 test-recovery-2 도구를 읽기 전용으로 인수했다. 이전 Request/worktree는 수정하지 않았다. 앞선 CLAUDE Provider 실패 원인은 **UNKNOWN**이며 인증/쿼터 추측을 하지 않는다. 부모 SQLite154/2539·atomic21/2209 PASS는 인수된 이전 증거이며 이번 실행 수에 합산하거나 재실행으로 슬롯을 채우지 않았다.

## 실행 경계와 실제 설치

전용 TEST는 `req81_travel_lab_test`, TCP `127.0.0.1:3306`, `req81_travel@127.0.0.1`이다. 승인된 부모 `.env.testing`에서 최소 TEST DB 필드만 guarded process 안에서 읽어 자체 ignored0600 환경을 만들었다. 부모 APP `.env`/서비스, production/Spring, 계정/grants/capacity/platform config 변경, `setup.php`, 부모 APP-bound `extensions.php`, 공식 자식, push/merge/deploy는 실행하지 않았다. native readonly reviewer 한 명이 소스·증거를 검토했다([고정 diff 최종 검토](w04-native-final/evidence/intake/final-review.json)); 별도 DB 실행이나 공식 Validation은 아니다.

각 SQL/native child 전에 자체 환경의 read/write schema/user/host/port, URL/socket/SSL/cache/egress override를 거부하고 effective connection을 확인했다. 전체 schema snapshot 전에 정확한 cwd/개별 argv/FD/잠금 metadata와 TEST 한정 processlist를 확인했다. own flock/PID/argv/cwd/phase/BLOCKED, `proc_close` 완료 및 PID 소멸을 기록했다. TEST 외 processlist 내용을 조회하지 않았고 권한을 부여하지 않았다. Laravel provider HTTP는 RequestSending 단계에서 차단하고 loopback discard proxy와 Composer offline을 고정했다. mail=array, queue=sync, filesystem=local이다. payment/booking/mail/SMS/provider endpoint는 호출하지 않았다.

[설치 명령 ledger](w04-native-final/evidence/install/commands.json), [empty core](w04-native-final/evidence/install/empty-core-bootstrap.json), [resume1](w04-native-final/evidence/install/resume-1.json)~[4](w04-native-final/evidence/install/resume-4.json): native wipe 후 실제0테이블에서 migrate → settings:install → DatabaseSeeder → board/page/ecommerce/travel install+activate → admin/travel template install+activate → empty ShippingTypeSeeder → explicit travel sample → support-provision을 실행했다. 최종 경로의 성공 native 명령은19개이며, bootstrap 전 metadata 거부1회를 별도 보존했다. 앱을 사전 부팅한 뒤 DB connection을 purge하는 방식을 제거하고 정상 native `handleCommand`를 한 번 부팅한다. 설치 직후 **112테이블/1587행**이다. native routes31개, 실제 설치 디렉터리 autoload/Service/Listener4개·역할권한/관리자메뉴·두 template origin과 해시를 [installed-source](w04-native-final/evidence/install/installed-source.json)에 기록했다. fake route/manual provider 등록은 없다.

빈 schema에서 코어 provider가 cache 테이블 생성 전 cache를 조회하므로, 최초 일반 database-cache migrate는 실패했다. 최종 최초 migrate만 **0테이블·설치 미완료·설치된 module/template 없음**을 확인한 installer-only array bootstrap을 적용했다. 정상 `.env`는 database를 유지하고 이후 install/activate/kernel/HTTP는 실제 DatabaseStore이다. 따라서 **최초 migrate database-cache PASS, 공개 recipe 원형 전체 PASS, installer wizard 전체 PASS는 주장하지 않는다.** 정상 guard/제품 소스는 수정하지 않았다. PHPUnit의 명시 array 및 `.env` 이름비교 workaround와도 구분한다.

네 module의 `--vendor-mode=bundled` 값은 실제 `g7_modules`에 모두 bundled로 남았다([DB 재조회](w04-native-final/evidence/install/installed-vendor-modes.json)). ecommerce bundle SHA256 `713f98578a866fada6782d11f8c80ff13037f1faf0edaaa55f004ddc4e80d1b9`, missing/corrupt native 설치는 각각 exit1로 거부되고 등록/active/pending 실패 상태가 남지 않았다. source bundle을 원본 해시로 복원했으며 silent Composer/network fallback은 관찰하지 않았다. 이 실패 fixture의 native cache delta는 별도 비교했고 다른 테이블/DDL은 동일했다.

native ecommerce install이 ShippingTypeSeeder를 생략하는 조건을 실제 확인했다. 공개 package의 **참조 테이블 empty이면 그 native class만 seed**하는 동일 분기를 실행하고 전체 ecommerce sample/orders seed는 실행하지 않았다. nonempty 분기는 native ShippingTypeService로 만든 custom/operator 행을 포함한12행에서 seeder를 호출하지 않았고 전체 inventory가 정확히 동일했다([참조 보존](w04-native-final/evidence/install/reference-preservation.json)). explicit travel sample/support rerun은 운영자 수정과 held capacity2를 보존했다([전체 비교](w04-native-final/evidence/install/seed-rerun.json)). notices3/FAQ4 및 private board settings를 실제 provision했다.

## 설치본 결과와 경계

| 실제 실행 | 상태·고유 카운트 | 시간/증거 |
|---|---|---|
| public bundle 의존성 preflight | PASS, package1 | 1.171s; [preflight](w04-native-final/evidence/intake/preflight.json) |
| public guard | PASS, isolation27 | 35.085s; 같은 preflight |
| public fallback control-flow | PASS_CONTROL_FLOW_ONLY, scenario4 | 0.311s; 실제 MySQL 증거와 구분 |
| 자체 inherited override guard | PASS, subprocess18 (16거부/2허용) | [own guard](w04-native-final/evidence/intake/own-guard-check.json) |
| 실제 설치 ProductService/HTMLPurifier | PASS, synthetic product2 create/update, ko/en | 2.103s; [HTML](w04-native-final/evidence/install/html-product.json) |
| 실제 설치 native HTTP kernel | PASS, 요청37 / native login5 | 13.185s; [result](w04-native-final/evidence/install/http-result.json), [statuses](w04-native-final/evidence/install/http-statuses.json) |
| 자체 실제 TEST HTTP 서버 | PASS, 요청20 / process start3 | 18.424s; [server](w04-native-final/evidence/server/result.json), [PID/종료](w04-native-final/evidence/server/processes.json) |
| native migration rollback/replay | PASS, inner exit0 | 26.308s; [normal](w04-native-final/evidence/recovery/normal.json) |
| 실제 native fallback fault | PASS 복구 검증, inner 예상 exit1 유지 | 37.663s; [fault](w04-native-final/evidence/recovery/fault.json) |

실제 설치된 HTMLPurifier4.19.0의 `modules/sirsoft-ecommerce/vendor/...` origin과 ProductService origin/hash를 확인했다. 새 synthetic HTML의 safe markup을 보존하고 script/event handler/javascript URL을 제거하며 native create/update의 저장값을 검증했다. 단순 dependency 검사만으로 HTML PASS를 주장하지 않는다.

정상 설치본 `APP_ENV=local`의 실제 `Illuminate\Cache\DatabaseStore`, connection/lock connection=mysql, table=cache/lock table=cache_locks를 커널 및 HTTP entry에서 확인했다([cache proof](w04-native-final/evidence/install/kernel-cache-proof.json)). testing이 native extension autoload를 건너뛰므로 실제 설치 검증은 testing context가 아니다.

인증은 실제 설치 kernel 로그인과 Sanctum이다. native cart→201/replay200→권한 있는 비-super 관리자 UNDER_REVIEW/TEST_ACCEPTED→owner read/cancel 및 foreign404를 검증했다. private question 작성/edit/native audit/admin answer와 native private board 공개404를 검증했다. sample/ecommerce product identity 및 native 계산으로 가격24000과 capacity 증감/취소0을 확인했다. 직접 order/payment write 없이 orders0/payments0/notification logs0이었다.

자체 `127.0.0.1:18874` HTTP process를 실제 종료/재시작했다. A→B **B startup 직후, 후속 HTTP 전에** 전체 table/row/DDL 해시가 동일했다([restart 비교](w04-native-final/evidence/server/restart-comparison.json)). 이후 B에서 수행한 정상 인증/HTTP의 token/audit/cache 변화는 full before/after inventory로 남겼다. own `.env`와 `.env.testing`을 실제 제거했고 guard/native CLI/server startup 거부를 확인한 다음 bytes와0600을 복원했다. **env 제거 직전→own env 복원 직후, C startup/HTTP 전에** full digest equality를 확인했다([env loss 비교](w04-native-final/evidence/server/env-loss-comparison.json)); 이후 C의 owner cancel 등 의도된 runtime delta는 [after-C](w04-native-final/evidence/server/after-C.json)에 있다. source/bundle2266파일도 동일했다. 부모 preview18871은 건드리지 않았다.

native recovery source를 TEST bootstrap 및 명시 defaults-file/TCP/user/schema/local-infile 인자로 한정 적응하고 native catch/finally **control flow는 보존**했다. byte-verbatim은 false이다. 전체112테이블/1763행 inventory를 기존 native digest에 추가했고 정상 rollback/replay 및 실제 fault fallback 모두 전후 `f2aaeb72925291d8bf7f9e0220b5dce0081fa8cc4eb6584e87a34f73ab48fc04` 일치였다. fault가 발생한 native 작업 자체 exit1은 성공으로 바꾸지 않았고 fallback 복구 확인만 PASS로 분리했다.

## PHPUnit 명령별 결과

모든 명령은 own TEST snapshot 아래 기존 검토된 w03-support bootstrap을 사용했다. 동일한 own TEST `.env`를 루트 이름비교 동안만 숨기고 Laravel bootstrap 전에 복원했다. ordinary PHPUnit은 array; 특정 native quota fixture만 실제 DB cache를 bind한다. 실제 설치본 kernel/autoload 증거와 bundled fixture 테스트를 혼동하지 않는다. counts는 각 명령의 최종 PHPUnit summary이며 이전/partial 실행과 합산하지 않았다. [실행·시간·출력해시 ledger](w04-native-final/evidence/regressions/progress.json).

| 명령 대상 | PHPUnit | tests/assertions | PHPUnit 시간 | snapshot 포함 wall | 원본 복구 |
|---|---|---:|---:|---:|---|
| scripts/travel-lab/LiveMysqlTest.php | **FAIL exit1** | 1/8 | 26.219s | 50.125s | PASS + 별도 재측정 |
| TravelSupportApiTest | PASS exit0 | 7/90 | 34.586s | 64.549s | PASS + 별도 재측정 |
| TravelSupportProvisionerTest | PASS exit0 | 5/48 | 28.104s | 68.107s | 최초 BLOCKED → 별도 독립 재검증 PASS |
| TravelSupportNotificationTest | PASS exit0 | 2/15 | 15.171s | 24.090s | PASS + 별도 재측정 |
| SecretPostCommentAccessTest | PASS exit0 | 8/17 | 28.553s | 35.642s | PASS + 별도 재측정 |
| CartQuantityAndPurchaseLimitTest | PASS exit0 | 15/39 | 35.238s | 46.987s | PASS + 별도 재측정 |
| UserAuthControllerTest | PASS exit0 | 63/186 | 46.880s | 55.991s | PASS + 별도 재측정 |
| --testsuite=Installation | PASS exit0 | 2/29 | 29.033s | 39.169s | PASS + 별도 재측정 |
| InstallerContextTest | PASS exit0 | 8/8 | 1.483s | 11.070s | PASS + 별도 재측정 |

Installation의 실제 발견 대상은 `IdentityVerificationMigrationSmokeTest`의 IDV schema2개뿐이다. FreshInstallSmoke/UpgradeSmoke 예시와 TODO는 실행된 테스트가 아니므로 일반 설치/업그레이드 전체 커버리지 PASS로 확대하지 않는다.

**고정 소스 회귀 원인:** LiveMysqlTest는 루트 TestCase에서 `travelLabEnvironment(true)`로 ordinary array cache를 적용하지만 `UsesDatabaseThrottleCache::useDatabaseThrottleCache`를 호출하지 않는다. 0.1.2 `TravelThrottleRequests`는 ArrayStore를503으로 거부하므로 native cart quantity0의 예상422 대신503이다. readonly 비작성자 검토도 이 결합을 확인했다. actual installed database runtime의 정상 요청은 통과했지만 배포된 smoke fixture/recipe 회귀는 그대로 남는다. 이 Request는 고정 소스를 바꾸거나 fixture 외부에서 DB cache를 억지로 bind하여 PASS를 만들지 않았다. 제품 작성자는 해당 fixture의 native cache 계약을 수정하고 새 고정 revision에서 검증해야 한다.

## 실패·중단 증거와 복원

- 최초 database-cache empty migrate bootstrap 실패 및 already-empty restore wipe preflight 실패: 원본 보존 후 empty이면 반복 wipe를 생략하고 import/전체55/104 equality를 확인했다([initial failure](w04-native-final/evidence/install-initial-failure/commands.json)).
- missing bundle native exit1은 올바른 거부였으나 harness가 native cache delta까지 일치 요구해 중단했다. 해당 실패와 이후 cache 명시 분리 비교를 보존했다([fixture-cache failure](w04-native-final/evidence/install-fixture-cache-failure/commands.json)).
- 사전 bootstrap+connection purge가 cache의 stale Connection과 template transaction을 분리했다. native 명령이 success 메시지를 내더라도 repository null로 실패한 실행을 PASS로 바꾸지 않았다. readonly trace 후 단일 native 부팅으로 수정하고 원본 복구 뒤 다시 설치했다([template failure](w04-native-final/evidence/install-template-command-failure/commands.json)).
- 최종 설치 travel template activate 전에 metadata preflight가 거부해 native 명령은 실행되지 않았다. 최초 세부 scanner 결과는 저장되지 않아 정확한 거부 원인은 UNKNOWN이다. 다시0 확인 후 bounded resume에서 own PDOStatement를 보유한 첫 harness 재개도 quiesce에서 중단했고, statement 해제 후 남은 native 명령4개를 완료했다. 이를 원형 한 번의 uninterrupted 설치 PASS로 기재하지 않는다.
- kernel helper의 evidence closure capture 누락: native login1 이후 harness 오류. 수정 후 별도37요청 실행을 기록했다([partial1](w04-native-final/evidence/probe-kernel-attempt1/http-failure.json)).
- 첫 HTTP server 실행은20요청을 완료했지만 hidden env-copy cleanup glob의 rmdir 오류로 helper exit1이었다. [attempt1](w04-native-final/evidence/server-attempt1/result.json)을 보존했고 명시 unlink 수정 후 별도20요청/3 starts/모든 handle 종료를 검증했다. 두 실행 수를 합산하지 않는다.
- recovery adapter 범위 검사 중단 및 생성 인자 quoting parse failure255는 native destructive SQL 이전이었다. [parse failure](w04-native-final/evidence/recovery/normal-parse-failure.json)의 full digest도 동일했다. 수정 후 lint하고 실제 정상/실패 fallback을 실행했다. 초기 동일 명칭 retry 로그 일부가 보존 wrapper 도입 전에 덮어써졌으므로 모든 초기 raw 로그가 완전하다고 주장하지 않는다; 공개 partial/ledger/digest 증거는 보존했다.
- support provisioning PHPUnit은5/48 PASS였지만 import 직후 scanner가 이전 recovery Request 파일 FD11을 가진 PID3650359를 관찰해 복원 wrapper가 BLOCKED exit1이었다. [당시 metadata](w04-native-final/evidence/intake/foreign-scan-failure-8b67d8e7.json) 및 [BLOCKED](w04-native-final/evidence/reg-support-provision/BLOCKED.json)를 보존했다. 이후 새 guarded process가 full55/104/ded equality와 foreign handles0을 확인했다. 추가 wipe/import 없이 [independent recovery](w04-native-final/evidence/reg-support-provision/independent-recovery.json)로 private marker를 해제한 뒤 다음 suite를 시작했다. 최초 runner_exit1은 그대로 남았다. FD의 의도/Provider 원인은 추측하지 않는다.

모든 초기 파괴적 설치 실패 뒤, 설치본 suite 전체 뒤, 각 PHPUnit suite 뒤 전체 원본 equality를 확인한 후에만 다음 파괴적 suite를 시작했다. SQL dump는 private0700/0600, native TCP/mysql explicit TEST schema/user, SQL scanner 검증, dump trailer/hash 및 restore 전 safety backup을 사용했다. 모든 original/safety snapshots를 유지했다. public historical BLOCKED.json은 해결 전 증거이며 현재 live pending marker를 뜻하지 않는다.

## 최종 원본과 남은 gate

[최종 독립 재측정](w04-native-final/evidence/measure/final-independent-before-release.json) 및 [TEST RELEASE](w04-native-final/evidence/release/result.json): 원본 **55테이블/104행**, table/NULL/중복 row/DDL 및 view/trigger/routine/event0 전체 equality. full digest `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`.

- 전체 row inventory hash: `f491092e081db3bd9e999cc81a1eb0c25c1362f002a7cd7ad30ebd8c3b78fa5b`
- 전체 DDL inventory hash: `23f247d9ee8d400287155a15eb23d9eb0b0ece53d77600b098ad5d0bacf33534`
- object0 inventory hash: `4fd7145664b849158a8cd0da85d622d78e0fdfb9651624b8a5a7713f643b7653`

PDO 종료 후 TEST 연결0, own native PHP/mysql 프로세스0(측정 observer 제외 후 그 실제 handle도 종료), HTTP process0/port18874 closed, 다른 Request exact/descendant cwd·개별 argv·FD·잠금0, unreadable0을 확인했다. TEST RELEASE 이후 SQL/native runtime 테스트를 실행하지 않았다. 원본·safety SQL, own 최소 credentials, synthetic contact/token 및 raw PHPUnit diagnostics는 ignored private에만 남기고 Git에는 안전한 hashes/counts/status/helper만 기록한다. **기존128/639는 NOT_PROVEN 그대로이다.**

| Gate | 최종 사실 |
|---|---|
| actual native empty installed context / HTML / auth / HTTP / persistence / recovery | 범위 한정 PASS; 최초 migrate accommodation 및 실패 이력 명시 |
| fixed-source MySQL smoke fixture | **FAIL / CHANGES_REQUIRED**, 1/8 |
| exact55/104 baseline restoration 및 TEST RELEASE | PASS |
| 원형 database-cache empty migrate 및 전체 installer wizard | NOT_PROVEN / NOT_RUN 범위 유지 |
| frontend build/typecheck | NOT_RUN, root와 travel-template node_modules 없음; 조건부 prerequisite 미충족, 실행0 ([environment](w04-native-final/evidence/intake/environment.json)) |
| hosted CI / formal Validation | NOT_RUN; native readonly review 및 로컬 실행은 대체 승인 아님 |
| original128/639 | NOT_PROVEN |
| publication / deployment | 실행하지 않음; 로컬 review commit만 |
