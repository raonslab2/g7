# W04 campaign install — 자체 독립 TEST verifier helpers

대상 source `d059d735cd17ed3c88dd27cca5a9f83f00042011` / tree `6dc9381b1d50196c81867ef3f9e31ca86611f410`. 결과는 [최종 보고서](../W04_CAMPAIGN_NATIVE_INSTALL_FINAL.md)다. 이 helpers는 자기 Request에서 공개 `w04-recipe-final`을 검사·복사·수정한 검증 코드다. 다른 Request private config/dump를 복사하지 않는다. 제품 코드와 제품 테스트는 read-only다.

**현재 TEST RELEASE 완료. 아래 명령을 재실행하지 않는다.** 새 TEST 소유권 배정과 새 snapshot label이 필요하다. 원본/안전 backup은 ignored0700/0600에 유지한다. 기존 완료 lease나 원본 dump를 덮어쓰지 않는다. Foreign connection/process/FD/lock/unreadable이면 BLOCK하고 kill/retry loop를 하지 않는다. State change 후 recovery-only 작업도 fresh guard를 통과해야 한다.

실제 실행 순서(정확한 명령/exit/PID/시간은 [ledger](evidence/commands/ledger.json)):

```sh
# Exact checkout; root dependencies are offline from canonical lock, no scripts.
git fetch origin feat/g7-travel-lab-c7ae42d1
git checkout --detach d059d735cd17ed3c88dd27cca5a9f83f00042011
composer install --no-scripts --no-interaction --prefer-dist
php docs/symphony/w04-campaign-install-final/make-env.php
php docs/symphony/w04-campaign-install-final/snapshot.php original save
php docs/symphony/w04-campaign-install-final/operation.php original /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/fresh-install.php original
# Original install fails pages6 gate; retain attempt1 then exact restore.
php docs/symphony/w04-campaign-install-final/snapshot.php original restore-keep
php docs/symphony/w04-campaign-install-final/reset-own-artifacts.php
# Own completion flags false. Separate install2 snapshot; explicit diagnostic continuation
# records zero-default-Pages FAIL rather than changing the product/requirement.
php docs/symphony/w04-campaign-install-final/snapshot.php install2 save
php docs/symphony/w04-campaign-install-final/operation.php install2 /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/fresh-install.php install2
php docs/symphony/w04-campaign-install-final/operation.php install2 /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/html-product.php install2
php docs/symphony/w04-campaign-install-final/operation.php install2 /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/installed-http.php install2
php docs/symphony/w04-campaign-install-final/operation.php install2 /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/campaign-http.php install2
# Campaign late login failure from own reused Kernel guard. Restore, retain original failure,
# reset only own request-client guard, then fresh install3 snapshot and lifecycle.
php docs/symphony/w04-campaign-install-final/snapshot.php install2 restore-keep
php docs/symphony/w04-campaign-install-final/reset-own-artifacts.php
php docs/symphony/w04-campaign-install-final/snapshot.php install3 save
php docs/symphony/w04-campaign-install-final/operation.php install3 /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/fresh-install.php install3
php docs/symphony/w04-campaign-install-final/operation.php install3 /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/installed-http.php install3
php docs/symphony/w04-campaign-install-final/operation.php install3 /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/campaign-http.php install3
php docs/symphony/w04-campaign-install-final/operation.php install3 /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/service-restart.php install3
php docs/symphony/w04-campaign-install-final/operation.php install3 /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/rollback-probe.php install3
# Rollback-probe exit0 made NO rollback (migration path absent); assessment retains failure.
php docs/symphony/w04-campaign-install-final/snapshot.php install3 restore-keep
php docs/symphony/w04-campaign-install-final/reset-own-artifacts.php
php docs/symphony/w04-campaign-install-final/snapshot.php mysql-live run /usr/bin/php8.3 vendor/bin/phpunit --bootstrap docs/symphony/w04-campaign-install-final/test-bootstrap.php scripts/travel-lab/LiveMysqlTest.php
python3 docs/symphony/w04-campaign-install-final/regressions.py
# Page completes/restores; Board tests pass but transient foreign FD pauses import.
# Fresh recovery only after observed FD closure; no kills. Retain original BLOCKED result.
php docs/symphony/w04-campaign-install-final/snapshot.php board-regression restore-keep
python3 docs/symphony/w04-campaign-install-final/regressions.py core-regression
php docs/symphony/w04-campaign-install-final/snapshot.php migration-roundtrip run /usr/bin/php8.3 docs/symphony/w04-campaign-install-final/migration-roundtrip.php migration-roundtrip
# Native explicit-path down/up actually executes. Own overlapping dependency-only PHP
# auxiliary check pauses restore measurement. It completes, then fresh guarded recovery.
php docs/symphony/w04-campaign-install-final/snapshot.php migration-roundtrip restore-keep
php docs/symphony/w04-campaign-install-final/env-loss-guard.php
php docs/symphony/w04-campaign-install-final/final-measure.php
php docs/symphony/w04-campaign-install-final/release.php
```

Shell launch는 discard proxies `127.0.0.1:9`/COMPOSER_DISABLE_NETWORK를 적용했다. 자체 completion flag resets는 `w04fCompleted(false)` and touch only own `.env/.env.testing`. Initial migration calls published `travelLabInitialMigrationEnvironment` unchanged/directly; no copied body, normal database-cache bypass or global array admission. Ordinary PHPUnit temporarily hides only its own verified TEST `.env` during the unchanged root DB-name guard, restores bytes/0600 before Laravel, and leaves test cache aliases/array intact. Native MySQL fixture selects its own database throttle trait without product edits.

Owned `fresh-install.php` calls native install/activate/seed commands; it **retains explicit zero-default-Pages FAIL** for native Page6 and continues only diagnostic scopes. `installed-http.php` updates the inherited route/menu expectations to actual campaign33/4, preserving all original37 business requests. `campaign-http.php` uses actual installed Kernel/native command/PageService paths and real SeoCacheManager; no manual route/provider mount/cache recorder.

Frontend commands used own offline root lock install; template npm ci failed(EUSAGE), `--legacy-peer-deps` then failed(ENOTCACHED muggle-string). Product manifests/locks stayed unchanged. Exact DOMPurify3.4.14 was obtained offline into `storage/framework/testing/w04c-node` only; own ignored root node_modules symlink permits source import. Root Vitest4.1.8 executes these unchanged source tests (template cwd):

```sh
../../../node_modules/.bin/vitest run --config vitest.config.ts \
  __tests__/components/pageBody.test.tsx __tests__/layouts/campaigns.test.tsx \
  --maxWorkers=1 --no-file-parallelism --reporter=json \
  --outputFile=../../../storage/framework/testing/w04c-private/frontend.json
```

Raw logs/env/dumps/token diagnostics stay ignored0600. Public JSON contains command metadata/status/hash/counts, SQL object/table/row digests and exact restore/release evidence. Original mislabeled rollback receipt and transient BLOCKED receipts are preserved with separate corrections/resolution; do not treat their historical labels as outstanding current blocks or new PASS gates. Hosted CI/canonical Validation/whole wizard/new build/browser are NOT_RUN.
