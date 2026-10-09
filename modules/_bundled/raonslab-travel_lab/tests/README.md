Run from the repository root:

```sh
vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml
```

Requires locally installed Composer dependencies and PHP `pdo_sqlite`. The module-specific launcher pins `APP_ENV=testing`, a fresh SQLite `:memory:` connection per test, array settings/cache/session and the null Scout driver. It does not read `.env` or `.env.testing`, has no MySQL connection configured, and never points to an existing application database. `ModuleTestCase` uses real Eloquent repositories, native migrations and the real ecommerce services; it does not mock authentication or catalog results.

This suite verifies travel schema up/down and constraints, shared inquiry relationships/enum/idempotency, public product visibility and facets, combined filters/price/date sorting, FormRequest date/bounds validation, departure stock/capacity invariants and repeatable synthetic seeding. The scenario manifest is `scenarios/catalog_domain.yaml`.

SQLite evidence is scoped to this module. Three unrelated ecommerce migrations are excluded from dependency setup: coupon-category creation uses the MySQL table-local name `idx_type` already used by another table (SQLite index names are global); obsolete mail-template removal requires the core notification migration; purchase-earn mileage uniqueness uses MySQL generated-column SQL. Travel migrations are all executed unchanged. This is not a full ecommerce migration or MySQL compatibility certification. HTTP tests execute the native API middleware with real Sanctum bearer tokens and persisted role/permission rows, including authorized reads/writes, denied read-only writes, 401/403/404/409/422 and the resource envelope. Installed MySQL/deployment verification remains the integrating lead’s responsibility.

Recorded local result: **44 tests, 462 assertions passed** (PHP 8.3.6, PHPUnit 11.5.56, real SQLite). Raw output and tested PHP file hashes are preserved under `evidence/`; MySQL, hosted CI and deployment checks were not run by this suite.
