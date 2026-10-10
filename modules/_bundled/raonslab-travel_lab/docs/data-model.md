# Travel Lab — 데이터 모델

## 모델

<!-- @generated:models START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 모델 | 테이블 | fillable | 관계 | 특성 |
|---|---|---|---|---|
| `Departure` | `travel_lab_departures` | 6 | product→Product, option→ProductOption | - |
| `Inquiry` | `travel_lab_inquiries` | 9 | items→InquiryItem, events→InquiryEvent, user→User | - |
| `InquiryEvent` | `travel_lab_inquiry_events` | 6 | actor→User | - |
| `InquiryItem` | `travel_lab_inquiry_items` | 9 | inquiry→Inquiry, departure→Departure, product→Product, option→ProductOption | - |
| `TravelProduct` | `travel_lab_products` | 7 | product→Product, departures→Departure | - |
<!-- @generated:models END -->

<!-- @intent START -->
TravelProduct의 API 식별자는 product_id(native Product.id)입니다. Departure는 native ProductOption과 같은 product_id에 속하며 일대일입니다. Inquiry/InquiryItem/InquiryEvent는 서버 계산과 처리 이력을 저장합니다. 모델 필드의 상세 계약은 [domain.md](domain.md)에 있습니다.
<!-- @intent END -->

## 소유 테이블

<!-- @generated:tables START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 테이블 | 모델 |
|---|---|
| `travel_lab_departures` | `Departure` |
| `travel_lab_inquiries` | `Inquiry` |
| `travel_lab_inquiry_events` | `InquiryEvent` |
| `travel_lab_inquiry_items` | `InquiryItem` |
| `travel_lab_products` | `TravelProduct` |
<!-- @generated:tables END -->

<!-- @intent START -->
travel_lab_products/departures/inquiries/inquiry_items/inquiry_events만 여행 데이터입니다. native ecommerce cart/product/option 및 board post/comment를 재사용하며 주문·결제 테이블에 시험 접수를 직접 쓰지 않습니다. 문의 항목/계산 스냅샷은 과거 금액을 보존하고 현재 옵션 가격으로 덮어쓰지 않습니다.
<!-- @intent END -->

## 마이그레이션

<!-- @generated:migrations START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
마이그레이션 2개.

| 파일 | 생성 테이블 | 변경 테이블 | down() |
|---|---|---|---|
| `2026_10_09_000001_create_travel_lab_tables.php` | `travel_lab_products`, `travel_lab_departures`, `travel_lab_inquiries`, `travel_lab_inquiry_items` | - | ✅ |
| `2026_10_09_900001_add_inquiry_evidence.php` | `travel_lab_inquiry_events` | `travel_lab_inquiries` | ✅ |
<!-- @generated:migrations END -->

<!-- @intent START -->
한국어 comment와 down을 유지합니다. Inquiry unique(user_id,idempotency_key), 관계 FK, 단축 MySQL 인덱스 이름을 보존합니다. 재현은 격리 DB에서 migration/rollback/전체 스냅샷 복구를 구분하며 [fresh 설치 recipe](../../../../docs/symphony/W04_FRESH_INSTALL_RECIPE.md)는 실행 증거가 아닌 NOT_RUN 계획입니다.
<!-- @intent END -->

## Enum

<!-- @generated:enums START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| Enum | backing | case 수 | case |
|---|---|---|---|
| `CatalogSort` | `string` | 4 | `recommended`, `price_asc`, `price_desc`, `departure_asc` |
| `InquiryStatus` | `string` | 5 | `TEST_INQUIRY`, `UNDER_REVIEW`, `TEST_ACCEPTED`, `DECLINED`, `CANCELLED` |
| `Region` | `string` | 4 | `jeju`, `gangwon`, `busan`, `seoul` |
| `Theme` | `string` | 4 | `nature`, `culture`, `city`, `wellness` |
| `TravelSupportChannel` | `string` | 3 | `notices`, `faqs`, `questions` |
<!-- @generated:enums END -->

<!-- @intent START -->
InquiryStatus의 허용 전이가 단일 출처입니다. TEST_INQUIRY→UNDER_REVIEW/DECLINED/CANCELLED, UNDER_REVIEW→TEST_ACCEPTED/DECLINED/CANCELLED, TEST_ACCEPTED→CANCELLED이며 terminal 상태는 추가 전이가 없습니다. Region/Theme/CatalogSort는 유한 분류/정렬, TravelSupportChannel은 native 지원 게시판 slug를 소유합니다.
<!-- @intent END -->

## Repository

<!-- @generated:repositories START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 클래스 | 종류 | 설명 |
|---|---|---|
| `CampaignPageRepository` | 구현 | native PageService 를 감싸는 캠페인 Page 조회 어댑터. |
| `CampaignPageRepositoryInterface` | 인터페이스 | 캠페인 고객 화면이 native sirsoft-page 를 읽는 단일 어댑터. |
| `CatalogRepository` | 구현 | - |
| `CatalogRepositoryInterface` | 인터페이스 | - |
| `TravelSupportPostRepository` | 구현 | 여행 고객지원 게시판 조회 구현. |
| `TravelSupportPostRepositoryInterface` | 인터페이스 | 여행 고객지원 게시판(board_posts) 조회 계약. |
| `WorkflowCartRepository` | 구현 | 여행 워크플로의 읽기·잠금과 모의 정원 변경을 소유한다. 커머스 쓰기는 하지 않는다. |
| `WorkflowCartRepositoryInterface` | 인터페이스 | - |
| `WorkflowInquiryRepository` | 구현 | - |
| `WorkflowInquiryRepositoryInterface` | 인터페이스 | - |
<!-- @generated:repositories END -->

<!-- @intent START -->
CatalogRepositoryInterface, WorkflowCartRepositoryInterface, WorkflowInquiryRepositoryInterface, TravelSupportPostRepositoryInterface를 주입합니다. 확보/반환 및 상태 변경은 DB 트랜잭션, 서버 현재 행 잠금, 순서가 정해진 상품/옵션 질의를 사용합니다. 사용자/키 직렬화와 UNIQUE를 함께 사용해 중복 문의를 방지합니다. available=max(0,min(capacity,option.stock_quantity)-reserved), KST 당일 제외 규칙은 탐색과 접수에서 동일합니다. SQL CHECK나 SQLite PASS를 MySQL 동시성 PASS로 바꾸지 않습니다.
<!-- @intent END -->
