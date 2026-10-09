# W04 전용 TEST 재검증 도구

이 Request의 assigned tree와 전용 TEST lease에서만 실행한다. 제품 소스 fa552317/tree fa685339를 고정하고 공개 가드는 변경하지 않는다. 이전 실패 req3e의 안전한 helper와 recovery2의 inventory/SQL scanner/process metadata를 읽기 전용으로 인수하여 자체 도구를 보완했다. 부모 APP/다른 worktree와 setup.php는 접근하지 않는다.

`make-env.php`는 승인된 부모 TEST 환경에서 최소 필드를 파싱하여 자체 동일0600 TEST 환경을 생성한다. 정상 CLI/kernel/HTTP는 local/database/mysql/cache/cache_locks이며 전체 read/write, URL/socket/cache/egress 경계를 확인한다. `empty-migrate.php`만 원본 snapshot + empty0테이블 + 미완료 installer에 한정한 array bootstrap으로 native core migrate를 실행한다. 정상 설치/활성/HTTP에서는 사용하지 않는다.

파괴적 operation의 순서:

```bash
php docs/symphony/w04-native-final/snapshot.php original save
php docs/symphony/w04-native-final/operation.php original /usr/bin/php8.3 docs/symphony/w04-native-final/fresh-install.php original
# 동일 suite의 native installed probes는 operation wrapper로 순차 실행한다.
php docs/symphony/w04-native-final/snapshot.php original restore-keep
# 전체 원본 equality 이후에만 별도 파괴적 suite를 시작한다.
php docs/symphony/w04-native-final/native-regressions.php
```

snapshot/operation은 live flock/PID/argv/cwd/phase/BLOCKED를 유지하고 proc_close로 실제 handle을 기다린다. 원본과 restore 직전 safety SQL은 private0700/0600에 보존한다. 원본에는 전체 row/NULL/중복/DDL/객체 inventory가 포함된다. native SQL은 private-only defaults-file/TCP/explicit schema/user/local-infile0/skip-reconnect이고 import 전에 원본 scanner를 실행한다. 검증된 원본/safety를 삭제하지 않는다. 실패하면 BLOCKED 및 snapshot을 유지한다.

`native-regressions.php`는 매 command 별 전체 snapshot/run/exact restore를 끝낸 뒤 다음 command로 진행한다. 기존 w03-support bootstrap은 동일한 TEST `.env`를 이름비교 동안만 숨기고 부팅 전에 복원한다. source origin은 실제 bundled이며 installed runtime과 별도 증거다. 일반 cachearray와 개별 fixture의 실제 DB cache를 혼동하지 않는다.

raw env/SQL/token/contact/전체 PHPUnit 오류 출력은 private에만 남긴다. 공개 evidence에는 해시·카운트·상태·시간·PID와 안전한 진단만 기록한다. 실패한 실제 실행은 성공 실행으로 덮어쓰지 않는다. 최종 보고서는 상위 W04_NATIVE_INSTALL_FINAL.md이다.

중단된 복원이 이미 원본과 일치하는 경우 `verify-retained.php <snapshot-label>`은 새 guarded process에서 retained snapshot/full inventory/exclusivity/quiescence를 확인한 뒤 private BLOCKED만 해제한다. 최초 실패 증거는 유지하며 wipe/import를 반복하지 않는다. 실패 명령을 무조건 다시 실행하거나 남은 설치 단계를 추측해서 재개하지 않는다.

모든 suite와 별도 재측정이 완료된 후에만 `release.php`를 실행한다. 원본55/104 equality, 원본/safety hash/mode, pending marker 없음, own PHP/mysql 프로세스·TEST 연결·HTTP listener0을 확인하고 TEST RELEASE를 기록한다. 이 기록 이후 SQL/runtime 테스트는 하지 않는다. 검증을 다른 Request에서 재현하려면 새 전용 TEST lease와 새 snapshot label이 필요하며 이 완료된 lease를 재사용하지 않는다.

최종 source smoke regression은 FAIL이며 설치본 HTTP 성공으로 덮지 않는다. Installation2개는 IDV migration만 커버한다. frontend는 dependencies 미준비 NOT_RUN, hostedCI/formalValidation도 NOT_RUN이다. 정확한 상태·실패·시간·복원·release는 상위 최종 보고서에 있다.
