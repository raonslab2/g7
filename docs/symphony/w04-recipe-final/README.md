# W04 recipe smoke — Request-owned TEST helpers

검증 제품: `992f9a65ac3f8957e5ec618f21072dc499053810`, tree `db85f35c5f42165802ec7ba6b02bc8a147a12c39`. Assigned `req_bed1c2288b1946de95782fe1a4acff75`에서만 작성/실행했다. 기존 `w04-native-final`은 읽기 전용 인수했으며 원본 실패/보고서를 변경하지 않았다. 결과는 [W04_RECIPE_SMOKE_FINAL.md](../W04_RECIPE_SMOKE_FINAL.md)다.

`make-env.php`는 명시 허용된 부모 `.env.testing`의 최소 TEST 필드만 guarded process에서 읽는다. 자체 `.env`는 local/database, `.env.testing`은 testing/array, 둘 다 TEST/0600이다. SQL/덤프/복원은 TEST TCP/account만 사용한다. `/proc` scanner는 외부 파일 내용 대신 cwd/개별 argv/FD/잠금 metadata를 확인한다. 경쟁 TEST 연결/FD/lease면 backup 유지·즉시 BLOCK, 다른 프로세스 종료/retry loop 없음.

각 snapshot은 전체 rows(NULL/binary/중복 포함)/SHOW CREATE TABLE/objects inventory와 private0700/0600 dump를 보존한다. 다른 SQL objects가 있으면 지원되지 않는 복원을 시도하지 않고 BLOCK한다. 실제 측정값이 복원 기준이며 historical digest는 비교 표시다. `proc_close` 완료·PID 소멸·process/FD guard 후 다음 단계로 진행한다. restore 전 safety dump도 보존하고 SQL scanner로 import 범위를 확인한다.

이번 실행 순서(완료된 lease 재사용 금지):

```sh
php docs/symphony/w04-recipe-final/make-env.php
php docs/symphony/w04-recipe-final/bootstrap-check.php nonempty
php scripts/travel-lab/vendor-check.php --bundled
php docs/symphony/w04-recipe-final/snapshot.php original save
php docs/symphony/w04-recipe-final/operation.php original /usr/bin/php8.3 docs/symphony/w04-recipe-final/fresh-install.php original
php docs/symphony/w04-recipe-final/operation.php original /usr/bin/php8.3 docs/symphony/w04-recipe-final/html-product.php original
php docs/symphony/w04-recipe-final/operation.php original /usr/bin/php8.3 docs/symphony/w04-recipe-final/installed-http.php original
php docs/symphony/w04-recipe-final/snapshot.php original restore-keep
php docs/symphony/w04-recipe-final/reset-own-artifacts.php
# Own completion flags reset false; original remeasured before next suite.
php docs/symphony/w04-recipe-final/snapshot.php mysql-smoke run /usr/bin/php8.3 vendor/bin/phpunit --bootstrap docs/symphony/w04-recipe-final/test-bootstrap.php scripts/travel-lab/LiveMysqlTest.php
# Trial1 harness variable collision failed BEFORE tests; exact restoration verified.
# Dedicated variable fix, separate snapshot, no product edits:
php docs/symphony/w04-recipe-final/snapshot.php mysql-smoke-fixed run /usr/bin/php8.3 vendor/bin/phpunit --bootstrap docs/symphony/w04-recipe-final/test-bootstrap.php scripts/travel-lab/LiveMysqlTest.php
php docs/symphony/w04-recipe-final/final-measure.php
php docs/symphony/w04-recipe-final/release.php
```

`empty-migrate.php` calls the ACTUAL published `travelLabInitialMigrationEnvironment`; function body is not copied/replaced. Only its first subprocess gets array. Normal env remains database; later settings/seed/module/template commands verify database config at native boot. Empty guard fixtures cover env/schema/account/completion/installed modules/templates/plugins. A TEST-only partial table without cache returns the original env, then whole inventory is restored before migrate.

`test-bootstrap.php` temporarily hides only its own verified TEST `.env` during original root DB-name comparison, restores bytes/0600 before Laravel boot, and retains original guards/cache-file aliases. Ordinary array stays unchanged globally. Published LiveMysqlTest itself binds the database throttle trait and executes DatabaseStore/counter/lock TEST assertions. Its bundled-route fixture differs from actual installed discovery in `installed-http.php`, which has no manual route/provider mounting.

Raw env/dumps/logs/auth diagnostics are private ignored artifacts; sanitized manifests retain hashes/counts/status/commands/timing/failures. Backups remain after restore. TEST RELEASE is final; no SQL/runtime tests follow it. Whole public setup/provisioning/account rotation, production/deploy/push/merge, broad suites and formal Validation/hosted CI are NOT_RUN.
