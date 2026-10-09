# 여행 도메인 계약 v1

`TravelProduct.product_id`는 `ecommerce_products.id`에 대한 유일한 FK입니다. 이름·통화·현재 가격·이미지·상품 표시·판매 상태는 이커머스가 소유합니다. 여행 테이블에는 지역, 테마, 기간, 다국어 요약, 일정, 공개 플래그만 저장합니다.

`Departure.product_option_id`는 `ecommerce_product_options.id`에 대한 유일한 FK입니다. 옵션은 같은 `product_id`에 속해야 합니다. 가격은 이커머스 `ProductOption::getSellingPrice()`(상품 판매가 + 옵션 조정액)를 사용하며, 검색·정렬 쿼리만 같은 식을 SQL로 표현합니다. 가격·통화의 별도 여행 저장 필드는 없습니다.

`capacity`는 모의 정원, `reserved`는 테스트 문의가 확보한 모의 인원입니다. `available = max(0, capacity - reserved)`이며 `ProductOption.stock_quantity >= capacity`를 요구합니다. 관리자는 capacity와 활성 여부를 수정할 수 있지만 reserved를 보낼 수 없습니다. 잠금 안에서 capacity가 reserved보다 작거나 이커머스 재고보다 크면 409입니다. 초기 departure 저장 후 DB 기본값을 다시 읽어 reserved=0과 is_active=true를 응답에 보장합니다.

관리 API는 옵션 교체를 허용하지 않습니다. reserved가 있거나 InquiryItem 이력이 있으면 출발·귀환 날짜도 변경할 수 없습니다. API 입력은 실제 달력의 `YYYY-MM-DD`이며 귀환일은 출발일 이상이어야 합니다. 삭제 엔드포인트 대신 비활성화를 사용합니다.

공개 여행은 published=true, ecommerce display_status=visible, sales_status=on_sale, soft-delete 없음 조건을 모두 만족해야 합니다. 미래(오늘 포함) 활성 출발 중 정원이 남고, 같은 상품의 활성 옵션 재고가 정원 이상인 출발이 최소 하나 있어야 합니다. 목록·상세·출발 목록·분류는 이 규칙을 공유합니다. date_from/date_to는 출발일의 양끝 포함 범위입니다. 날짜와 가격의 모든 조건은 같은 출발 행에 적용됩니다. 검색 결과의 departures와 from_price도 그 조건에 맞는 출발에서 계산합니다.

추천순은 여행 메타 등록 ID 순입니다. 가격순은 적격 출발의 최저 현재 단가, 출발순은 가장 이른 적격 출발일로 정렬하며 마지막에 ID를 붙여 같은 값의 페이지 경계를 고정합니다. 총 건수는 코어 BoundedPaginator/PaginationLimits를 사용합니다. region/theme 분류 응답은 config의 유한 분류 목록 중 공개 상품이 있는 값만 반환합니다.

공유 모델과 테이블:

| 모델 | 테이블 | 주요 계약 |
| --- | --- | --- |
| TravelProduct | travel_lab_products | product_id unique FK, summary ko/en JSON, itinerary JSON |
| Departure | travel_lab_departures | product_option_id unique FK, product/option 관계, capacity/reserved |
| Inquiry | travel_lab_inquiries | user_id FK, unique(user_id,idempotency_key), payload_hash char64, status enum cast, items/user 관계 |
| InquiryItem | travel_lab_inquiry_items | inquiry/departure/product/option FK, 수량·단가·행합계·상품명·출발일 스냅샷 |

InquiryStatus는 `TEST_INQUIRY`, `UNDER_REVIEW`, `TEST_ACCEPTED`, `DECLINED`, `CANCELLED`입니다. 상태 변경·멱등 처리·스냅샷 계산·확보 인원 증감은 별도 워크플로 범위입니다. 어느 상태도 실제 예약 확정이 아닙니다. schema의 통화 기본값은 계약대로 KRW이지만 실제 문의 작성 시에는 이커머스 계산 결과 통화를 기록해야 합니다.

FK는 restrictOnDelete로 참조 이력을 보호합니다. 삭제는 서비스의 명시적 처리 대상이며 DB cascade에 의존하지 않습니다. 초기 마이그레이션에는 한국어 컬럼 comment와 역순 dropIfExists down이 있습니다. `reserved <= capacity`, 옵션-상품 소속과 문의 스냅샷 일치는 SQL CHECK가 아닌 잠금 저장 경로의 불변조건입니다. 워크플로도 같은 불변조건을 지켜야 합니다.

샘플 생성은 native ProductService와 배송정책 서비스, 전용 SyntheticCatalogHelper 및 Repository 인터페이스를 사용합니다. 명시적으로 연결한 국가 설정 없는 배송정책은 상점 기본 배송정책으로 폴백하지 않고 배송비 0을 계산합니다. 이것은 배송 없는 테스트 상품 구성이며 실제 체크아웃을 여는 계약이 아닙니다. 상품 코드는 native sequence 서비스가 생성하고 고정 SKU로 재실행을 식별합니다. 8개 여행과 24개 옵션·출발이 생성됩니다. 기본 시더는 비어 있으며 --sample만 합성 시더를 호출합니다.
