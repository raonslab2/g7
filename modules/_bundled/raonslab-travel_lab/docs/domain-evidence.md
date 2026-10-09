# 구현 근거와 통합 인계

작업: `work-20261009-g7-symphony-max-child-c7ae42d1`, Request `req_42c3e3430d144efc847e7988bc8dee01`, 부모 `req_81ac33cac94046b9a2249cd14c0d00ba`. 소스 기준 SHA는 `6853f40d58acbf53a2f29cbb9dd422cc439047a9`입니다.

실제 원본 확인 근거:

| 원본 | 확인 내용 |
| --- | --- |
| ecommerce src/Models/ProductOption.php getSellingPrice/getFinalPrice | 상품 판매가 + price_adjustment, 소수 가격 보존 |
| ecommerce src/Services/OrderCalculationService.php prepareItems | unit_price를 productOption->getSellingPrice()에서 읽음 |
| ecommerce src/Services/ProductService.php create/createOptions/generateUniqueCode | 서비스 경유 상품·옵션 생성, 옵션 재고 합계를 상품에 동기화, 공식 상품 채번 |
| ecommerce src/Services/ShippingPolicyResolver.php resolveForProduct | 상품 shipping_policy_id가 null이면 기본 배송정책으로 폴백 |
| ecommerce src/Services/OrderCalculationService.php resolveCountrySetting | 명시 정책의 국가 설정이 없으면 배송비 0 |
| app/Extension/ModuleManager.php runSeeders | getSeeders가 빈 배열이면 root *Seeder.php를 전부 실행하는 폴백 |
| app/Console/Commands/Module/SeedModuleCommand.php | DatabaseSeeder에 --sample을 setIncludeSample로 전달 |
| app/Support/Query/BoundedPaginator.php / PaginationLimits.php | 코어의 총수 상한과 페이지 상한을 적용 |
| app/Search/KeywordSearch.php | 활성 검색 엔진에 위임하고 부분일치 폴백 제공 |

구현 계획은 메타/FK 계약 → 가시성·동일 출발 필터 Repository → 서비스 인터페이스 → FormRequest/Resource/컨트롤러·이름 있는 라우트 → 명시적 샘플 시더 → 격리 DB·실제 인증 테스트 → 문서·독립 리뷰 → 로컬 커밋 순으로 수행했습니다. 새로운 상품 도메인·여행 가격 저장·스케줄러는 없습니다.

현재 Request 안에서 native subagent 두 개를 관찰했습니다: domain_tests(격리 테스트와 시나리오), catalog_review(읽기 전용 독립 리뷰). 별도 공식 child Request, PC 노드 또는 추가 용량을 생성하지 않았습니다. 리드만 통합·Git 커밋을 담당합니다.

독립 리뷰 고정 대상은 staged diff SHA256 `3f8aca54d2f08a1a42e6be6ded71aa27c0f340ac3f349282e8b986f059a278df`입니다. 리뷰는 P0–P2 없음, P3로 q=0가 PHP empty 처리에서 빠지는 문제 하나를 보고했습니다. 명시 문자열 검사로 수정하고 회귀 테스트를 추가했습니다. 이후 DB 기본값 refresh와 관리 리소스 필드 추가도 격리 HTTP 생성과 최종 테스트로 검증했습니다. 최종 delta 리뷰(`0cb57d06d903896e5f59a42566a2c4527e565c0f504d311ce771919d67e34599`)에서 코드 문제는 없었고 생성 GET 예시의 잘못된 sort 값·설명 P3를 추가로 확인해 실제 성공 URI와 enum 목록으로 교정했습니다. 독립 리뷰는 공식 Validation 또는 MySQL 동시성 PASS를 의미하지 않습니다.

검증 출력과 실행한 PHP 소스 hash는 [evidence](../tests/evidence/domain-test-evidence.json), [raw PHPUnit output](../tests/evidence/domain-phpunit.txt)에 있습니다. 실제 SQLite 데이터베이스, 이커머스 서비스와 native API 미들웨어·Sanctum token·권한 행을 사용합니다. 운영 DB, mock 인증 또는 mock 카탈로그를 사용하지 않습니다. SQLite 의존 스키마에서 제외한 이커머스 마이그레이션 3개는 테스트 README와 evidence에 공개합니다. 여행 마이그레이션은 전량 수정 없이 up/down을 실행합니다.

MySQL에서의 native 전체 마이그레이션·FOR UPDATE 동시성, 원격 CI, 서비스 설치/활성화/배포, 브라우저 UI는 이 범위에서 **NOT_RUN**입니다. SQLite 결과를 그 검증의 PASS로 주장하지 않습니다. 운영 중 raonslab-product·DB는 변경하지 않았습니다.

통합 리드의 연결 요구:

1. 같은 namespace/model 이름으로 workflow child 코드를 결합합니다. Inquiry 로직과 reserved의 원자적 증감은 그 범위가 소유합니다.
2. `src/routes/api.php`에서 catalog.php를 require하고 workflow.php/support.php와 연결합니다. 이 child는 api.php를 만들지 않습니다.
3. UI가 catalog의 data.data 배열, localized title/summary, bilingual itinerary, nullable image_url과 native pagination을 사용하도록 합니다. 합성 fixture SKU를 별도 여행 상품 ID로 쓰지 않습니다.
4. 모듈 버전·의존 제약은 0.1.0/코어7.0.11/이커머스1.2.1/게시판·페이지1.1.2 이상입니다. 소비 확장의 제약은 통합 코드의 최종 공개 표면으로 검토합니다.
5. 별도 격리 설치에서 공식 모듈 설치/활성화·MySQL·워크플로·UI 검증을 수행하고 필요하면 명시적으로 --sample을 실행합니다. 실제 결제·주문·예약·환불·메일·SMS·제공자 연결은 열지 않습니다.

이 Request는 사용자 범위대로 로컬 커밋 SHA와 경로를 반환합니다. push main, merge, deploy는 수행하지 않습니다.
