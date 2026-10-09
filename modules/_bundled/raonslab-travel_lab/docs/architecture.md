# Travel Lab — 아키텍처

## 설계 의도

<!-- @intent START -->
코어·기존 RAON 사업 확장을 수정하지 않고 여행 전용 메타데이터/출발/시험 문의를 추가합니다. 상품·옵션·현재 가격·카트·가격 계산은 native ecommerce, 고객지원 콘텐츠는 native board가 소유합니다. 실제 거래를 모의 확정으로 이름만 바꾸지 않습니다. `TEST_ACCEPTED`는 별도 문의 상태입니다.
<!-- @intent END -->

## 계층 지도

<!-- @intent START -->
CatalogController → FormRequest/Resource → CatalogService → CatalogRepositoryInterface입니다. CartController/InquiryController → TravelCartService/InquiryService → WorkflowCartRepositoryInterface/WorkflowInquiryRepositoryInterface → native 커머스와 travel 모델입니다. SupportController → TravelSupportService → native PostService와 전용 Repository입니다. Provider가 인터페이스 바인딩/config/API 응답 어댑터를, Module이 메뉴/권한/리스너/기본 시더를 선언합니다. 여행 UI는 별도 템플릿이고 관리자 레이아웃은 모듈이 소유합니다. 잠금 안의 가격/정원 재검증과 idempotency/event 스냅샷 상세는 [도메인](domain.md), [워크플로 증거](../tests/WORKFLOW_EVIDENCE.md)를 확인합니다.
<!-- @intent END -->

## 디렉토리

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

<!-- @intent START -->
`src/routes/api.php`는 catalog/workflow/support 파일을 require하는 공통 진입점입니다. 요청별 소유 파일 경계를 유지하며 공유 계약은 리드가 조정합니다. `_bundled` 수정 후 공식 업데이트로 설치 사본에 반영하고 실제 클래스/라우트 출처를 확인합니다.
<!-- @intent END -->
