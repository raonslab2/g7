# Travel Lab 개발 가이드

루트 AGENTS.md와 `docs/backend/{controllers,validation,response-helper,api-resources,service-repository}.md`, `docs/extension/{module-routing,module-i18n}.md`를 따릅니다. 변경 전에 [도메인 계약](docs/domain.md)과 [API 계약](docs/domain-api.md)을 읽으십시오.

- 상품의 식별자는 이커머스 `Product.id`입니다. 여행 메타 테이블의 `id`를 API 상품 식별자로 쓰지 않습니다.
- 출발 일정은 이커머스 `ProductOption`에 일대일 연결됩니다. 사람 수는 `Cart.quantity`입니다. 여행 가격 컬럼을 추가하지 않습니다.
- 현재 단가는 옵션 `getSellingPrice()`를 사용합니다. 문의 스냅샷 가격은 워크플로에서 이커머스 계산 결과로 기록합니다.
- 공개 목록·상세·출발·분류에 같은 가시성 규칙을 적용합니다. 옵션과 상품의 소속도 확인합니다.
- 출발 변경은 `CatalogRepositoryInterface`를 거쳐 잠금 안에서 처리합니다. 옵션 교체는 불가하며, 문의 이력이 생긴 날짜는 변경하지 않습니다. `reserved`는 관리 API 입력으로 수정하지 않습니다.
- 테스트 데이터는 명시적 `--sample`에서만 생성합니다. `Module::getSeeders()`를 빈 배열로 바꾸면 설치 경로가 루트 시더 전량 실행으로 폴백하므로 금지합니다.
- 실제 예약·주문·결제·환불·메일·SMS·외부 제공자·스케줄러 추가 금지. 운영 DB를 테스트에 사용하지 않습니다.
- 서비스에는 Repository 인터페이스를 주입합니다. API 응답은 ResponseHelper, 요청 검증은 FormRequest, 라우트는 name 필수입니다.
- `InquiryStatus::TEST_ACCEPTED`도 테스트 수락일 뿐 실제 예약 확정이 아닙니다.

테스트는 `tests/ModuleTestCase.php`를 상속합니다. 다음 명령은 `.env`를 읽지 않고 실제 SQLite `:memory:`와 native 서비스·Sanctum을 사용합니다.

```sh
vendor/bin/phpunit -c modules/_bundled/raonslab-travel_lab/tests/phpunit.xml
php modules/_bundled/raonslab-travel_lab/tests/generate-domain-docs.php
vendor/bin/pint --test modules/_bundled/raonslab-travel_lab
```

API 표면 변경 시 시나리오 매니페스트와 생성 API 문서를 함께 갱신합니다. 통합 이후에는 리드가 활성 모듈 라우트에 대해 `php artisan api:docgen --scope=module:raonslab-travel_lab`와 네이티브 MySQL 검증을 수행합니다. 이 작업 범위에서는 `src/routes/api.php`, 워크플로·지원 로직, 템플릿을 수정하지 않습니다.
