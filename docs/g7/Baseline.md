# GNUBOARD7 기준선 감사

감사 기준 시각은 2026-09-28 KST이며, 문서의 버전 정보는 공식 저장소와 실제 설치 소스에서 확인했다.

## 소스 기준선

| 항목 | 확인값 |
|---|---|
| 공식 저장소 | `https://github.com/gnuboard/g7.git` |
| 공식 기본 브랜치 | `main` |
| 최신 stable tag | `7.0.11` |
| release commit | `f00b8d04a16879801eac2424f2ddf19ad4e50919` |
| release commit date | 2026-09-09 14:37:34 +09:00 |
| 감사 시점 `upstream/main` | stable commit과 동일, ahead/behind `0/0` |
| 제품 fork | `https://github.com/raonslab2/g7.git` |
| 라이선스 | MIT, 저작권·허가 고지 보존 필요 |

설치 기준선은 stable `7.0.11`이다. 개발 브랜치의 기능을 stable 설치물에 섞지 않는다.

## 공식 요구사항과 실제 런타임

| 영역 | 공식 요구/구조 | 이 설치의 확인값 |
|---|---|---|
| PHP | `^8.2`, FPM 권장 | G7 전용 PHP-FPM 8.3.35; 시스템 기본 CLI는 기존 8.2.30 유지 |
| Laravel | 12.x | 12.69.1 |
| DB | MySQL 8.0+ 또는 MariaDB 10.3+ | MySQL 8.0.46, 전용 DB/계정 `g7_product` |
| Node | 소스 빌드 시 20+ | 20.19.6 |
| package manager | npm lockfile | npm 11.19, `npm ci` 사용 |
| frontend | React SPA | React 19.2, Zustand 5, Axios |
| build | Vite | Vite 7.0.4, `npm run build` 성공 |
| backend test | PHPUnit | PHPUnit 11 계열 잠금 |
| frontend test | Vitest | Vitest 4.1.8 |
| browser E2E | Playwright | Playwright 설정과 테스트 스위트 존재, Chromium v1223 설치 |
| web server | Nginx 1.18+ 권장 | 전용 standalone nginx 1.18, loopback `18770` |

운영 의존성은 `composer install --no-dev --prefer-dist --optimize-autoloader`로 설치했다. 잠금된 개발 의존성 `laravel/pint 1.31.0`은 PHP 8.3 이상을 요구하여 PHP 8.2에서 dev install이 실패했다. 공식 런타임 요구 PHP 8.2와 개발 lock 사이의 불일치이므로, 제품 런타임은 명시적 PHP 8.3 바이너리를 사용하고 시스템 기본 PHP는 변경하지 않는다.

`npm ci`는 성공했으나 감사 시점 npm audit 기준 15건(critical 2, high 7, moderate 5, low 1)이 보고됐다. 자동 `audit fix`로 잠금파일을 변경하지 않고 별도 회귀 검증 대상으로 둔다.

## 설치·영속성 증거

| 항목 | 결과 |
|---|---|
| 브라우저 installer | welcome → license → requirements → configuration → extension selection → ready → install 실행 완료 |
| installer state | `completed`, 완료 task 27개, 실패 없음 |
| DB | 테이블 108개, migration 216개 |
| 최초 관리자 | 1명 생성, credential은 ignored `.env`/로컬 검증 자산 밖에 노출하지 않음 |
| 앱 접근 | `http://127.0.0.1:18770/` HTTP 200 |
| 설치기 재진입 | 설치 후 `/install` 비공개(HTTP 404) |
| 서비스 | `g7-product-fpm`, `g7-product-web`, `g7-product-queue`, scheduler/backup timer |
| 재시작 영속성 | 제품화 후 전용 service 재시작 전후 user 3, board 3, AI request 1, HTTP 200 |
| 백업 | `/var/backups/g7-product`, 최신 archive SHA-256·DB gzip·persistent payload 검증 성공 |
| 외부 검수 | 승인된 reverse SSH ingress `http://203.245.29.156:58770`, 외부 HTTP 200 |

인스톨러 완료 상태는 서버에서 확정됐으나 Chromium 폴링 화면은 완료 card로 전환되지 않고 빠른 폴링을 계속했다. 이는 설치 실패가 아니라 installer UI 상태 반영 결함 후보이며 E2E 문서에 원본 증거와 함께 추적한다.

## 공식 구조 조사

### 설치와 마이그레이션

- 진입점은 `public/index.php`, 설치 UI 정적 자산은 `public/install`, 서버 로직은 installer 관련 컨트롤러/서비스에 있다.
- Laravel migration은 `database/migrations`에 있으며 확장은 자체 migration을 가질 수 있다.
- 세션·캐시·큐는 database driver로 독립 구성했다.

### 확장 모델

- Module: 독립 비즈니스 기능. `modules/` 동적 스캔, `AbstractModule`/service provider/lifecycle command를 사용한다.
- Plugin: 기능 확장. `plugins/` 동적 스캔, `AbstractPlugin`과 lifecycle command를 사용한다.
- Template: admin/user layout과 component 자산. `templates/`에서 발견되고 JSON layout을 제공한다.
- Hook/Event: `HookManager`, listener registrar, action/filter와 queued listener를 제공한다.
- 개발 소스는 각 타입의 `_bundled`, 설치 후보는 `_pending`, 활성 복사본은 일반 identifier 경로로 분리된다.
- 설치·활성화·업데이트·비활성화·제거는 직접 복사가 아니라 공식 Artisan/API lifecycle을 거친다.

### 애플리케이션 기능

- Auth/session: Laravel Sanctum, DB session, refresh/session middleware, user status 검사.
- RBAC: `roles`, `permissions`, `role_permissions`, `user_roles`, menu permission 모델과 `permission:*` middleware.
- Notification: definition/template/log와 Laravel notification channel abstraction.
- Search: Laravel Scout 계약과 MySQL fulltext engine, keyword/search index 유지보수 계층.
- JSON/Layout: DB versioning을 갖는 template layout, component registry, extension point, permission/filter/action DSL.
- Admin: 별도 admin template와 `routes/api.php`의 권한별 admin API.
- File/upload: `Attachment` 모델, storage driver, upload policy와 extension별 저장소 격리.
- Configuration: `.env`의 배포 설정과 `storage/app/settings/*.json`의 운영 설정을 구분한다.
- Queue/scheduler: Laravel queue와 `routes/console.php`; 운영 시 worker와 매분 scheduler 필요.
- Cache: Laravel cache store와 extension별 cache driver/cache invalidation 계층.

### 업데이트와 복구

- 코어 업데이트는 감지 → 다운로드/검증 → maintenance → backup → vendor 처리 → 증분 적용 → migration/upgrade step → 마무리의 공식 흐름을 갖는다.
- 코어 적용 실패 시 생성한 backup에서 파일·데이터를 복원한다. 확장도 `_pending`과 `ExtensionBackupHelper`를 이용한 원자적 교체/rollback을 제공한다.
- 제품 fork는 공식 self-update로 커스텀 계층을 덮어쓰지 않으며, Git upstream 동기화와 별도 운영 백업을 1차 복구 수단으로 사용한다.

### CI와 테스트

소스에는 PHPUnit, Vitest, Playwright 설정과 다수의 unit/feature/browser test가 있다. stable tag에는 `.github/workflows`가 없으므로 저장소 내부에서 확인 가능한 공식 CI workflow는 없다. 제품 fork에서 focused test → extension test → backend/frontend integration → browser E2E → restart/adapter E2E 순으로 검증한다.

## 설치된 공식 확장

- Templates: `sirsoft-admin_basic 1.0.9`, `sirsoft-basic 1.1.4`
- Modules: `sirsoft-board 1.1.2`, `sirsoft-ecommerce 1.2.1`, `sirsoft-page 1.1.2`
- Plugins: `sirsoft-ckeditor5 1.0.3`, `sirsoft-daum_postcode 1.0.3`

제품 범위에 불필요한 확장은 의존성과 실제 화면을 확인한 뒤 공식 lifecycle로만 비활성화한다.

## 재현 명령

```bash
/usr/bin/php8.3 /usr/bin/composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
/usr/bin/php8.3 artisan migrate:status
systemctl status g7-product-fpm g7-product-web g7-product-queue
systemctl list-timers 'g7-product-*'
```
