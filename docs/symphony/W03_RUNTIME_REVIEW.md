# W03 독립 runtime/package 검증

판정: **CHANGES_REQUIRED — P2 복구 실패 경로 결함 1건.** 아래에서 실행한 TEST 스키마·SQLite 검증은 통과했다. 새 빈 설치, 실제 env-loss/인증 복구, APP 설치 라우트 탐색과 재시작은 **NOT_RUN**이다. 이 보고서는 전체 제품 또는 릴리스 PASS, canonical formal Validation receipt를 대신하지 않는다.

검증자: 비저자 `/root`, 공식 Request `req_b3b4d5373a384cfd8e0b7b8f55fb011f`. 대상 work order `work-20261009-g7-symphony-max-child-c7ae42d1`, [PR2](https://github.com/raonslab2/g7/pull/2), 2026-10-09 UTC. 원 구현에는 참여하지 않았다. 같은 Request의 비저자 native `package_review`가 DB/env 접근 없이 원본 및 검증 helper를 검토했다. 공식 자식 Request는 만들지 않았다.

## 고정 소스와 환경

- 시작 작업트리는 `6853f40d58acbf53a2f29cbb9dd422cc439047a9`였다. `git fetch origin feat/g7-travel-lab-c7ae42d1` 후 **제 작업트리의** `review/w03-runtime-req-b3b4d537`를 정확히 `28ada286c1c34606741bcfe4f9d12e06ac50af30`에 만들었다. 실행 전 HEAD와 트리 `a821bd89f6cf8bdf8cad3b8b35d8758ce8446d9c`, clean tracked source를 확인했다. 기본 canonical workspace binding이 review target과 같다는 주장은 하지 않는다.
- 모든 아래 명령의 tested product SHA는 위 `28ada286…`이다. package/module/template와 부트스트랩·잠금 의존성 등 **300개 파일**을 해당 Git blob과 byte 비교하고 종료 시 재확인했다. [source-manifest.json](w03/evidence/source-manifest.json)은 SHA256/Git blob을 제공한다. 이것은 사용자 보고 APP frozen160 파일의 독립 실측이 아니다.
- root AGENTS, 확장 AGENTS/docs, testing guide, deploy README, scripts, RUNTIME/W00/W01 원본과 source hash를 확인했다. W01의 저자 실행 결과를 이번 PASS로 전환하지 않았다.
- PHP 8.3.6, PHPUnit 11.5.56. Composer 2.7.1의 locked dependencies를 제 vendor에 `--no-scripts`로 설치했다. Node v22.23.3/npm10.9.9를 관찰했으나 npm 설치/빌드는 실행하지 않았다.
- 사용자 지정 원본 `.env.testing`만 privately 읽어 marker, TEST DB 세 이름, account/loopback3306, array mail/sync queue/local storage/env priority, URL/socket override 부재를 **부팅 전에** 검증했다. 원본 바이트는 제 `.env.testing`/`.env`에만 복사했다. 둘 다 ignored/0600이며 내용은 출력하거나 커밋하지 않았다.
- DB 작업은 독점 허용된 `req81_travel_lab_test`뿐이다. `live-bootstrap` TEST 경로는 effective read/write 설정·egress·실제 `DATABASE()/CURRENT_USER()`를 검사했다. 다른 DB/APP fixture, 공동 source/service, 플랫폼 설정/인증, account rotation, public deployment에 접근하지 않았다. 서비스 `req81-travel-lab-preview.service`와 loopback18871은 관리하지 않았다.

## 독립 실행 결과

모든 시간은 [evidence](w03/evidence/)의 각 JSON `duration_seconds`인 명령 전체 wall time이다. 정확한 argv, UTC 시작, source SHA/tree, exit code, 환경 범위와 sanitized stdout이 함께 있다. DB 네이티브 명령은 한 번에 하나씩 실행했다. SQLite memory suite는 별도 DB이며 네이티브 실행 일부와 겹쳤다. Composer의 기록된 lock 재확인은 최초 설치 이후 실행이며 실제 신규144 package 설치 로그를 독립 runtime PASS로 삼지 않는다.

네이티브 CLI 표에서 `BOOT`는 `--bootstrap docs/symphony/w03/test-bootstrap.php`, 파일 경로는 아래 evidence의 argv가 원본이다.

| 명령 / evidence 파일 | 환경 | 시간(s) | 판정 / 실제 결과 |
| --- | --- | ---: | --- |
| `composer install --no-scripts --no-interaction --prefer-dist` / dependencies-locked.json | own dependencies; 부팅 없음 | 35.569 | PASS, lock platform 검증·autoload 생성; 기존 PSR-4 fixture 경고4개 |
| `php scripts/travel-lab/guard-test.php` / guard20.json | worktree 임시 fixture; DB 없음 | 7.690 | PASS20, DB/host/user/port/URL/socket/mail/queue/storage/cache/installer 거부 및 안전한 cache cleanup |
| `php vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml` / sqlite-domain.json | SQLite `:memory:` | 136.817 | PASS **118 tests / 1895 assertions**, canonical source-origin 단언 포함 |
| 원래 `run.php test scripts/travel-lab/LiveMysqlTest.php` / native-entry-original.json | prebootstrap | 0.757 | **BLOCKED**, 두 TEST env의 동일 DB 이름을 root guard가 거부; DB 실행 없음 |
| `run.php test BOOT scripts/travel-lab/LiveMysqlTest.php` / native-mysql.json | TEST MySQL | 9.188 | PASS **1 / 31**; 실제 native reference migration/seed·root HTTP kernel·Sanctum, tamper/owner/idempotency/cancel/가격 및 주문·결제0 단언 |
| direct PHPUnit `BOOT LiveMysqlTest.php` + unusable loopback URL/socket/cache 주입 / native-direct-overrides.json | TEST MySQL | 3.114 | PASS **1 / 30**; subprocess runner 없이 직접 진입, 원본 LiveMysqlTest가 parent bootstrap 전에 sanitized adapters 적용 |
| 원본 `php scripts/travel-lab/live-recovery.php` / mysql-rollback-restore.json | TEST MySQL만 | 14.773 | PASS, 여행2 migration rollback/replay + private snapshot restore, **8개 선택 테이블** count/digest 일치 |
| `php docs/symphony/w03/capture-test.php` / api-capture-test-fault.json | TEST로 명시 적응한 캡처 | 2.738 | PASS 예상 exit1: inquiry 직후 fault 도달, cleanup CANCELLED/reserved0/unpublished |
| `php docs/symphony/w03/verify-capture.php` / api-capture-persisted.json | 새 TEST 프로세스 | 3.530 | PASS: inquiry3 CANCELLED, departure27 reserved0, product11 unpublished, 생성 capture token0 |
| `schema-snapshot.php save` / native-regression-snapshot.json | TEST 전체 | 3.894 | PASS private snapshot: **128 tables / 639 rows**, 비밀 SQL/options는0700/0600, Git 제외 |
| `run.php test BOOT …TravelSupportApiTest.php` / native-support-api.json | TEST MySQL | 26.503 | PASS **7 / 90** |
| `run.php test BOOT …TravelSupportNotificationTest.php` / native-support-notification.json | TEST MySQL | 28.030 | PASS **2 / 15**, native board 연동과 notification/mail fake; 실제 발송 없음 |
| `run.php test BOOT …TravelSupportProvisionerTest.php` / native-support-provisioner.json | TEST MySQL | 28.111 | PASS **5 / 48**, lab gate/idempotent rerun/unsafe board refusal |
| `run.php test BOOT --testsuite=Installation` / native-installation.json | TEST MySQL | 67.405 | PASS **2 / 29**. 현재 스위트는 IDV migration schema 두 검사뿐; Travel Lab fresh install 전체가 아님 |
| `run.php test BOOT tests/Unit/Support/InstallerContextTest.php` / native-installer-context.json | guarded root 테스트 context | 4.990 | PASS **8 / 8** |
| `schema-snapshot.php restore` / native-regression-restore.json | TEST 전체 | 7.166 | PASS **128 tables / 639 rows 전체 digest 동일**, 일치 확인 후 private snapshot 제거 |
| `native-seed-probe.php` / native-seed-rerun.json | TEST native seeder class | 1.843 | PASS default seed 및 명시 sample rerun, **10개 테이블** count/digest 보존: users6/products11/options27/departures27/inquiries3/items3/events6/orders0/payments0 |
| `recovery-failure-probe.php` / recovery-failure-controlflow.json | 고정 catch/finally + importer stub; DB 없음 | 0.059 | **FAIL 제품 실패 경로 재현**, probe 자체 exit0; 아래 W03-R01. 실제 restore failure는 NOT_RUN |
| `helper-env-restoration.php` / helper-env-restoration.json | 검증 helper만; Laravel 없음 | 0.285 | PASS, 제 TEST env overwrite 후 shutdown으로 원본 바이트/0600 복원; setup/env-loss 복구와 별개 |
| PHP syntax27개 / syntax.json | 원본 scripts20 + own PHP helpers7; 부팅 없음 | 2.379 | PASS; own recorder Python compile와 evidence JSON parsing도 별도 확인 |

네이티브 PHPUnit는 **25개 고유 테스트**, LiveMysqlTest 재실행까지 **26 test executions / 251 assertions**이다. SQLite118/1895는 별도이다. Native Installation2/29는 이번 독립 실행이며 기존 저자2/29를 인용한 것이 아니다.

전체 복원 digest는 전후 `3d3706693359b161fe6ab29442f0d09a7cbb2297dd02e52fe0432c8830ae5033`이다. restore 실패는 실제로 발생하지 않았고 private dump는 남아 있지 않다. 이후 native seeder probe도 기존 synthetic users/기록을 변경하지 않았다.

## 발견 사항과 재현

| ID / 우선순위 | 고정 소스 위치 | 문제 / 정확한 재현 및 조치 |
| --- | --- | --- |
| **W03-R01 / P2 / OPEN** | `scripts/travel-lab/live-recovery.php:69` catch, 72–74, 83–87 | 정상 경로는 import 후 purge/digest를 확인한다. 그러나 digest mismatch 등의 예외 후 fallback import가 exit0이면 재검증 없이 `$damaged=false`로 바꾸고 `RECOVERED`를 출력하며 private snapshot을 삭제한다. 따라서 일관성 실패가 유지돼도 복구 자료가 사라질 수 있다. `python3 docs/symphony/w03/record.py recovery-failure-controlflow php docs/symphony/w03/recovery-failure-probe.php`: 원본 catch/finally를 그대로 추출하여 mismatch 예외와 성공 importer stub으로 삭제 분기를 재현한다. 원본 SHA256 `24cb60481c6d5814eb19aa48a6f60202f25a1e531f0f5a76b9ee27bc8c93793d`. **fallback에서도 DB purge + `$before` digest equality가 성공한 뒤에만 damaged 해제/삭제**해야 한다. 실제 TEST 복원을 고의로 훼손하지 않았다. 수정 후 안전한 실패 분기와 실제 복원 재검증이 필요하다. |
| W03-R02 / P3 / OPEN | `deploy/travel-lab/README.md:7`, requirements170, 양쪽 package-lock | prose의 Node20 이상보다 locked Vite/React plugin은 `^20.19.0 || >=22.12.0`를 요구한다. JSON의 `packages.node_modules/vite.engines`와 plugin engines로 재현 가능하다. 지원 Node 최소 minor를 명시해야 한다. 제 Node22.23.3은 만족하지만 구 Node build를 실행한 FAIL은 아니다. |
| W03-R03 / P3 / OPEN | `deploy/travel-lab/README.md:17`, template `vite.config.ts:38` | plain `npm run build`는 sourcemap 생성 기본값이고 root AGENTS의 production 산출물 규칙과 다르다. 최종 산출물 절차에는 공식 `template:build … --production` 또는 대응 `G7_BUILD_SOURCEMAP=0` 설정을 명시해야 한다. 기존 tracked core/admin/ecommerce assets는 존재한다. 신규 npm/build 실행 및 브라우저 map404 확인은 NOT_RUN이며 기존 pinned dist 결함을 주장하지 않는다. |

**검증 환경 제약:** `tests/bootstrap.php:62`의 정상 안전망은 두 env DB가 같으면 중단한다. 사용자 지시의 정확한 TEST env 복사와 충돌하므로 그대로 실행하면 BLOCKED다. APP env 또는 foreign DB로 바꾸거나 core guard를 수정하지 않았다. 검증 전용 BOOT는 TEST 환경을 먼저 엄격 검증하고, 이름 비교가 실행되는 동안만 제 `.env` 존재를 숨긴 뒤 Laravel 부팅 전에 원본/0600을 복원한다. shutdown은 변경/유실 바이트도 복원하며 symlink를 거부한다. 이 adaptation 없이 README native 명령이 TEST-only 복사 worktree에서 통과한다고 주장하지 않는다.

## W01 수정과 package 평가

직접 진입 수정 `LiveMysqlTest.php` SHA256 **a2d9c3c75e7652082d336bec56f6c6c786fc821c08fba47d5b60518e49aca05b**, API cleanup 수정 SHA256 **808b987e43608cef99c5e8f68832a7528cb9e272c07ca9cefa59a946ba9c1124**는 W01 follow-up hash와 독립 일치했다. 첫 수정은 직접 PHPUnit 오염 변수 주입으로 native 실행했다. 둘째는 원본 cleanup 본문을 보존하고 **APP bootstrap 한 호출을 guarded TEST bootstrap + manually mounted bundled routes로 치환**하여 fault를 실행했다. 원본의 `__DIR__` 경로도 제 scripts 디렉토리로 보존했다. 이 한정 결과는 **APP installed metadata discovery 또는 HTTP 서버/browser PASS가 아니다**. 원래 APP capture 명령은 NOT_RUN이다.

Canonical SQLite suite는 데이터베이스 전체 구성을 memory로 고정하고 주요 서비스/모델/enum/provider의 파일 출처를 `_bundled`로 단언한다. native BOOT도 InquiryService/TravelCartService/CatalogRepository/native CartService의 제 작업트리 파일 출처를 검증했다. 등록한 실제 provider/route와 source manifest를 함께 근거로 삼았으며 설치된 저자 사본 테스트로 대체하지 않았다.

Setup은 기존 user가 있으면 destructive core DatabaseSeeder를 건너뛰고, 정확한 synthetic administrator만 native UserService로 복구한다. source 방향은 합리적이지만 **setup rerun은 실행하지 않았다**. 계정 rotation 때문에 이번 wave에 실행 금지된 절차이다. Default travel seeder는 sample flag가 없으면 실행할 일이 없고 explicit seed는 native TEST에서10개 테이블을 보존했다. 빈 memory DB에서 default0/explicit8 products·24departures라는 canonical 테스트도 독립 통과했다. TEST 클래스 seed 확인과 APP `module:seed`/extension installation 검증을 구분한다.

Env-loss 코드는 scoped DB/admin 비밀번호를 복구하고 사용자 ID·여행 digest를 보존하려는 절차이며 원본 APP_KEY 교체를 명시한다. **일반 password update가 기존 Sanctum token을 모두 revoke한다는 근거는 없다** (`UserService:261` 토큰 삭제는 비활성 상태 변경 분기). APP_KEY/session 설명을 bearer-token 폐기 증명으로 해석하지 않는다. 정상 계정 rotation, 새/옛 credentials, 쿠키/session, 기존 bearer token의 실제 전후 상태는 NOT_RUN이다.

Fresh checkout prerequisites에 local admin socket, MySQL dump/client 도구, PHP extensions, locked Composer, extension Composer 설치, Node minor 및 production build 모드를 함께 확인해야 한다. 이번에는 own root `--no-scripts` 설치/부팅과 committed assets 존재까지 확인했다. 공식 extension 설치·activation/update, 최초 empty-schema bootstrap, fresh npm 빌드 전체는 재현하지 않았다. native lifecycle config cleanup은 guard20의 임시 fixture로 검사했고, 실제 APP lifecycle 재실행으로 확대하지 않는다.

민감 자료는 ignored env/SQL/options를 제외했다. committed evidence는 source hash, synthetic IDs, counts/digests, 단언 결과뿐이다. 기록기가 알려진 local passwords/APP_KEY와 bearer 출력을 제거하며 마지막에 실제 secret bytes가 deliverables에 없음을 privately 확인했다. [final-hygiene.json](w03/evidence/final-hygiene.json)은 env0600/ignored, source300 unchanged, private recovery dump/config cache 없음의 최종 확인이다. 임의의 외부 비밀까지 전부 탐지했다는 주장은 하지 않는다.

## 남은 gate와 재현 범위

| 명령 / gate | 상태 | 사유 / 다음 검증 |
| --- | --- | --- |
| `setup.php`, `extensions.php`, 빈 DB fresh installation | **NOT_RUN**, duration 해당 없음 | 별도 승인된 독립 DB/server가 없고 setup은 shared scoped account를 rotation한다. 기존 TEST migration/Installation2/29로 대체하지 않는다. |
| `live-env-recovery.php`, 실제 env-loss credentials/session/bearer 상태 | **NOT_RUN**, duration 해당 없음 | APP/account rotation 금지. 별도 DB 또는 조정된 후속 wave 필요. helper env overwrite 복원은 이 gate가 아니다. |
| 원본 `live-api-responses.php` normal/fault, APP installed discovery | **NOT_RUN**, duration 해당 없음 | 이번 env는 TEST-only, 원본 APP 모드가 올바르게 거부한다. security/browser Requests의 product HTTP/UI 증거와 별도 조합 필요. |
| APP preview restart / `live-persistence.php` | **NOT_RUN**, duration 해당 없음 | frozen18871 service는 lead가 관리한다. browser/security 종료 후 lead-coordinated restart와 전후 digest 필요. DB server restart도 NOT_RUN. |
| native service concurrency / live-worker APP recipes | **NOT_RUN**, duration 해당 없음 | APP fixture/다른 runner에 접근하지 않음. 저자 service races와 이번 결과를 HTTP concurrency PASS로 결합하지 않는다. |
| fresh npm/build/frontend browser gates, remote CI | **NOT_RUN**, duration 해당 없음 | W03 runtime/package 범위; source prerequisite 점검만 수행. 다른 공식 리뷰 결과를 대신하지 않는다. |
| canonical formal Validation receipt, push/merge/public deployment | **NOT_RUN**, duration 해당 없음 | 이번 native evidence는 receipt 대체물이 아니다. 사용자 지시대로 local evidence commit만 전달한다. |

재현 시 exclusive TEST 소유권을 먼저 확보하고 동일 target HEAD에 helper 디렉토리를 evidence commit에서 **worktree 파일로만** 복원한다. 별도 APP env/metadata를 가져오지 않는다. `record.py`는 HEAD가 정확한 target일 때만 실행한다. 예: `git restore --source=<W03 evidence commit> --worktree -- docs/symphony/w03`, 이후 표의 argv를 순서대로 실행한다. native broad regression 전 `schema-snapshot.php save`, 종료 후 `restore`를 실행한다. 이 helper는 **128개 전체 table digest 검증이 성공해야만 snapshot을 삭제**하며 불일치 시 private 자료를 유지하고 즉시 보고한다.

helper 검토 중 own env shutdown 보호를 changed-byte 복원으로 강화했다. 제품/source harness에는 수정하지 않았고 초기 command evidence에는 helper hash가 없음을 보존했다. 뒤의 evidence에는 실행 시작 helper hashes가 있다. 현재 helper 복원은 source reviewer 재검토 및 실제 no-Laravel overwrite probe로 확인했다. 원본 runtime P2는 이 보고서에서 수정하지 않았다.

Native `package_review`의 최종 문서/evidence 검토는 중대한 추가 finding 없이 PASS였다. counts·duration·digest·명시적 adaptation과 미실행 gate를 확인했으며 reviewer는 env/DB에 접근하거나 runtime을 실행하지 않았다. 이 내부 리뷰는 canonical formal Validation 상태를 부여하지 않는다.

결과물은 이 보고서와 `docs/symphony/w03/`의 sanitized recipes/evidence뿐이다. 제품 source/부모 harness는 변경하지 않았다. **W03-R01 수정과 미실행 gate를 해결하기 전 전체 runtime/package 완료 PASS를 부여하지 않는다.**
