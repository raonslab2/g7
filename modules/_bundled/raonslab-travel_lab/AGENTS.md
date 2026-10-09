# G7 Travel Lab — 에이전트 가이드

## TL;DR (5초 요약)

모듈 식별자는 `raonslab-travel_lab`, 네임스페이스는 `Modules\Raonslab\TravelLab`입니다. 실제 이커머스 카탈로그·옵션·카트·계산기와 G7 게시판을 사용한 시험 여행 접수 모듈입니다. 소스 수정은 `_bundled`에서만 합니다. 사용자 화면은 별도 `templates/_bundled/raonslab-travel_lab`가 소유합니다. 거래·권한 계약은 [도메인](docs/domain.md), [워크플로](tests/WORKFLOW_EVIDENCE.md), [지원](docs/support.md)을 먼저 읽습니다.

## 1. 이 확장은 무엇인가

<!-- @intent START -->
여행 메타데이터와 출발일, 서버 계산 스냅샷을 가진 시험 문의를 영속 저장합니다. 상품명·상품 ID·옵션 ID·현재 가격·카트는 native ecommerce가 소유합니다. 공지·FAQ·비공개 문의는 native board 데이터입니다. 실예약·주문·결제·환불·외부 메일/SMS·공급자 연계는 만들지 않습니다. `TEST_ACCEPTED`도 시험 수락입니다. RAON 실증 가정과 고객 승인 자료를 혼동하지 않습니다.
<!-- @intent END -->

## 2. 디렉토리 지도

<!-- @generated:directory-map START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 경로 | 역할 | 수정 시 필요한 절차 |
|---|---|---|
| `module.json` | manifest (버전 SSoT) | version 변경 시 package.json·package-lock.json·composer.json 동기화 |
| `module.php` | 진입 클래스 (선언형 표면 SSoT) | 표면 변경 시 `ext:docgen` 재실행 + 코어 최소 버전 검토 |
| `src/Http/Controllers/` | 컨트롤러 | API 표면 변경 시 `api:docgen` 재실행 |
| `src/Http/Requests/` | FormRequest (검증 SSoT) | 검증 규칙은 Service 가 아니라 여기에 둔다 |
| `src/Http/Resources/` | API 리소스 | 목록 응답은 화면이 실제로 그리는 것만 싣는다 |
| `src/Services/` | 비즈니스 로직 | Repository 인터페이스 주입 (구체 클래스 금지) |
| `src/Repositories/` | 데이터 접근 | 목록 쿼리는 컬럼 프루닝·정렬 화이트리스트 확인 |
| `src/Models/` | Eloquent 모델 | 스키마 변경 시 마이그레이션 + 업그레이드 스텝 동반 |
| `src/Listeners/` | 훅 리스너 | Repository 경유 (Model·DB 파사드 직접 접근 금지) |
| `src/Enums/` | 상태·타입·분류 | 문자열 리터럴 대신 Enum 을 SSoT 로 둔다 |
| `src/routes/` | 라우트 | 모든 라우트에 `name()` 필수 |
| `src/lang/` | 백엔드 다국어 | ko·en 동시 반영 + 번들 ja 팩 동기화 |
| `database/migrations/` | 마이그레이션 | 한국어 comment + `down()` 필수, 기설치본은 업그레이드 스텝으로 백필 |
| `database/seeders/` | 시더 | composer autoload 등록 + `extension:update-autoload` |
| `resources/layouts/` | 레이아웃 JSON | `php artisan module:update raonslab-travel_lab --force` (빌드 불필요) |
| `resources/routes.json` | 라우트 → 레이아웃 매핑 | `php artisan module:update raonslab-travel_lab --force` |
| `resources/js/` | 프론트 엔트리·핸들러 | `php artisan module:build` → `php artisan module:update raonslab-travel_lab --force` |
| `editor-spec.json` | 레이아웃 편집기 스펙 | `php artisan module:update raonslab-travel_lab --force` |
| `config/` | 확장 config | 설정 기본값은 settings 스키마와 어긋나지 않게 |
| `tests/` | 테스트 | 변경 범위만 필터 실행 |
| `CHANGELOG.md` | 변경 이력 | 버전 상향 시 항목 추가 (미기재 시 버전 상향 불가) |
| `docs/` | 개발자 문서 | 표면 변경 시 `php artisan ext:docgen` 재실행 |
<!-- @generated:directory-map END -->

## 3. 핵심 흐름

<!-- @intent START -->
검색/상세 → `CatalogService`/`CatalogRepositoryInterface` → native Product/Option과 TravelProduct/Departure입니다. 인원 선택 → `TravelCartService` → native CartService/공식 계산 → `InquiryService` → 사용자·카트·출발·상품·옵션 잠금 → 문의/항목/이벤트/모의 확보 인원 저장 → 관리자 허용 상태 처리 → 소유자 재조회/취소입니다. 날짜 판정은 `TravelDate::today()`의 `Asia/Seoul` 사업일을 공유하고 당일 출발을 제외합니다. HTTP 입력은 FormRequest, API 응답은 ResponseHelper와 BaseApiResource/BaseApiCollection 계약을 따릅니다.
<!-- @intent END -->

## 4. 확장점

<!-- @generated:extension-points-summary START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 확장점 | 수 | 상세 |
|---|---|---|
| 발행 훅 | 0개 | [발행 훅](docs/extension-points.md#발행-훅) |
| 구독 훅 | 11개 | [구독 훅](docs/extension-points.md#구독-훅) |
| 훅 리스너 | 4개 | [훅 리스너](docs/extension-points.md#훅-리스너) |
| 레이아웃 확장 | 0개 | [레이아웃 확장](docs/extension-points.md#레이아웃-확장) |
| 미들웨어 | 0개 | [미들웨어](docs/extension-points.md#미들웨어) |
| 브로드캐스트 채널 | 0개 | [브로드캐스트 채널](docs/extension-points.md#브로드캐스트-채널) |
| 스케줄 | 0개 | [스케줄](docs/extension-points.md#스케줄) |
| 알림 정의 | 0개 | [알림 정의](docs/extension-points.md#알림-정의) |
<!-- @generated:extension-points-summary END -->

<!-- @intent START -->
`module.php`가 리스너 선언을 소유합니다. 실제 checkout/order/payment 차단과 여행 상품 삭제·옵션 제거 보호는 반드시 동기 훅입니다. `TravelCatalogConflictResponse`는 native 삭제 가드가 표시한 409만 여행 사유로 바꾸는 비인증 API 응답 어댑터입니다. 게시판 알림 추출과 지원 검색 훅은 여행 지원 범위에만 적용합니다. 상세는 [확장점](docs/extension-points.md)을 확인합니다.
<!-- @intent END -->

## 5. 수정 시 동반 의무

- 공개 API 변경: FormRequest/Resource/시나리오/API 문서를 함께 갱신하고 실제 설치 경로에서 검증합니다.
- 모델·마이그레이션 변경: 한국어 comment, `down()`, 격리 migration/복원 증거를 유지합니다.
- manifest 버전 변경: package/composer 및 CHANGELOG, 영향 받는 소비 확장의 버전 제약을 함께 검토합니다.
- 권한/메뉴/리스너 변경: 설치본 동기화 후 실제 로드 경로·권한 관계·동기 등록을 확인합니다.
- ko/en 메시지와 관리자 JSON 레이아웃, [편집기 스펙 계약](docs/editor-spec.md)을 함께 확인합니다.
- `ext:docgen`과 `api:docgen`은 native 생성기를 사용하고 생성 표를 손으로 채우지 않습니다.
- 루트 AGENTS.md와 `docs/backend/{controllers,validation,response-helper,api-resources,service-repository}.md`, `docs/extension/{module-routing,module-i18n}.md`를 따릅니다.

## 6. 금지 패턴

<!-- @intent START -->
API 상품 식별자에 TravelProduct 메타 ID를 쓰지 않습니다. Departure는 같은 상품의 ProductOption과 일대일이고 Cart.quantity가 인원입니다. 현재 단가는 `ProductOption::getSellingPrice()`, 문의 금액은 native 계산 결과가 출처입니다. 여행 가격 컬럼·가격 우회·주문/결제 테이블 직접 조작은 금지합니다. 공개 목록/상세/출발/facets에 동일 가시성 규칙을 적용합니다. reserved는 관리자 입력이 아니며 출발 저장과 재검증은 Repository 인터페이스 및 잠금 경로를 거칩니다. 옵션 교체와 문의 이력이 있는 날짜 변경은 불가합니다. `Module::getSeeders()`를 빈 배열로 바꾸면 루트 시더로 폴백하므로 금지합니다. 기본 설치는 합성 상품을 만들지 않고 명시 `--sample`만 만듭니다. 활성 디렉토리·운영 DB·다른 Request의 worktree를 직접 수정하지 않습니다. 서비스에 구체 Repository를 주입하거나 공개 API 응답에 비밀정보를 넣지 않습니다.
<!-- @intent END -->

## 7. 테스트 실행

<!-- @generated:test-commands START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 종류 | 개수 | 위치 |
|---|---|---|
| PHPUnit | 15개 | `modules/_bundled/raonslab-travel_lab/tests` |
| Vitest | 1개 | `vitest.config.ts` |
| Playwright | 0개 | — |
| 시나리오 매니페스트 | 3개 | `tests/scenarios` |

기저 TestCase: `tests/ModuleTestCase.php` — 확장 테스트는 이 클래스를 상속합니다 (`Tests\TestCase` 직접 상속 금지).

```bash
# PHPUnit (변경 범위만) (Bash)
php vendor/bin/phpunit modules/_bundled/raonslab-travel_lab/tests --filter='<대상클래스>'

# Vitest (확장 디렉토리에서) (PowerShell)
cd modules/_bundled/raonslab-travel_lab && powershell -Command "npm run test:run -- <대상>"

```

무필터 전체 실행은 금지되어 있습니다 — 변경 범위에 걸리는 대상만 지정해 실행합니다.
<!-- @generated:test-commands END -->

<!-- @intent START -->
Canonical SQLite의 실제 모델/native 서비스/Sanctum 검증은 `vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml --filter=<대상클래스>`로 실행합니다. 기저는 `tests/ModuleTestCase.php`입니다. native 게시판/MySQL 검증은 격리 TEST 소유권을 확보하고 루트 guarded runner를 사용합니다. `vendor/bin/pint --test modules/_bundled/raonslab-travel_lab`와 관리자 레이아웃 Vitest도 변경 범위에 맞춰 실행합니다. [테스트 안내](tests/README.md)와 [워크플로 증거](tests/WORKFLOW_EVIDENCE.md), [API 생성](tests/generate-domain-docs.php)을 참고합니다. SQLite, 저자 검사, 비구현자 고정 SHA 검증, 공식 Validation을 구분합니다. 무필터 전체 재실행이나 설치 사본 검사로 canonical source PASS를 대신하지 않습니다.
<!-- @intent END -->

## 8. 문서 목차

<!-- @generated:docs-index START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 문서 | 내용 | 상태 |
|---|---|---|
| [docs/README.md](docs/README.md) | 문서 통합 목차와 실측 집계 | ✅ |
| [docs/architecture.md](docs/architecture.md) | 설계 의도·계층 지도·디렉토리 맵 | ✅ |
| [docs/extension-points.md](docs/extension-points.md) | 발행/구독 훅·미들웨어·채널·스케줄 | ✅ |
| [docs/data-model.md](docs/data-model.md) | 모델·소유 테이블·마이그레이션·Enum | ✅ |
| [docs/settings.md](docs/settings.md) | 설정 스키마·권한·메뉴·라우트·의존 관계 | ✅ |
| [docs/frontend.md](docs/frontend.md) | 레이아웃·액션 핸들러·전역 진입점·에셋 | ✅ |
| [docs/editor-spec.md](docs/editor-spec.md) | 레이아웃 편집기에 선언한 팔레트·컨트롤·샘플 데이터 | ✅ |
| [docs/api/](docs/api/README.md) | API 레퍼런스 (엔드포인트별 파라미터·응답 필드) | ✅ |
| [CHANGELOG.md](CHANGELOG.md) | 변경 이력 | ✅ |
<!-- @generated:docs-index END -->
