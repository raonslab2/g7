# 카탈로그 API 계약

기본 경로는 `/api/modules/raonslab-travel_lab`입니다. 라우트 이름은 `api.modules.raonslab-travel_lab.` 접두사가 자동 적용됩니다. 공개 API는 무인증, 관리 API는 실제 `Authorization: Bearer {Sanctum token}`과 `admin.access` 및 해당 모듈 권한이 필요합니다. 로그인·가짜 인증 엔드포인트는 추가하지 않습니다.

| 메서드·경로 | 이름의 접미사 | 권한·결과 |
| --- | --- | --- |
| GET /catalog | catalog.index | 공개, data.data 상품 배열 + data.pagination |
| GET /catalog/{product} | catalog.show | 공개, data에 상품 객체 |
| GET /catalog/{product}/departures | catalog.departures | 공개, data에 출발 배열 |
| GET /facets | catalog.facets | 공개, data.region/data.theme 문자열 배열 |
| GET /admin/catalog | admin.catalog.index | catalog.read, 비공개 포함 목록 |
| PATCH /admin/catalog/{product} | admin.catalog.update | catalog.update, 여행 메타 수정 |
| GET /admin/catalog/{product}/departures | admin.catalog.departures.index | catalog.read, 비활성·지난 일정 포함 |
| POST /admin/catalog/{product}/departures | admin.catalog.departures.store | catalog.update, 기존 이커머스 옵션에 출발 생성 |
| PUT /admin/catalog/{product}/departures/{departure} | admin.catalog.departures.update | catalog.update, 기존 출발 수정 |

권한 접두사는 `raonslab-travel_lab.`입니다. catalog/inquiries/support 각각 read/update를 등록합니다. product 경로값은 반드시 이커머스 상품 ID이며 travel_lab_products.id가 아닙니다. departure 경로값은 travel_lab_departures.id입니다.

목록 쿼리:

| 키 | 형태·의미 |
| --- | --- |
| q | 최대 200자, 활성 G7 키워드 엔진으로 상품 name/description 검색. 문자열 `0`도 검색어입니다. |
| region, theme | 최대 50자, 정확 일치 |
| date_from, date_to | 실제 YYYY-MM-DD, 출발일 범위 양끝 포함, 종료 >= 시작 |
| min_price, max_price | 0 이상 현재 단가, 종료 >= 시작, 동일 출발에 날짜 조건과 함께 적용 |
| sort | recommended(기본), price_asc, price_desc, departure_asc |
| per_page | 1..48, 기본 12 |
| page | 1 이상, 코어 PaginationLimits.maxPage 적용 |

관리 목록은 q/region/theme와 페이지·정렬을 공유하지만 public 가시성·날짜·가격 필터로 숨기지 않습니다. 관리자는 모든 일정의 원본 capacity/reserved/is_active를 봅니다.

공개 목록 예시: `GET /catalog?region=jeju&date_from=2026-11-01&max_price=250000&sort=price_asc&per_page=12&page=1`.

```json
{
  "success": true,
  "message": "여행 카탈로그를 조회했습니다.",
  "data": {
    "data": [{
      "id": 101,
      "title": "제주 바다와 오름 (테스트)",
      "region": "jeju",
      "theme": "nature",
      "duration_days": 3,
      "summary": "실제 예약이 아닌 합성 여행 탐색 데이터입니다.",
      "itinerary": [{"day": 1, "title": {"ko": "바다", "en": "Sea"}}],
      "from_price": 189000,
      "currency_code": "KRW",
      "image_url": null,
      "departures": [{
        "id": 1, "product_id": 101, "product_option_id": 201,
        "departure_date": "2026-11-01", "return_date": "2026-11-03",
        "available": 24, "unit_price": 189000, "currency_code": "KRW"
      }]
    }],
    "pagination": {
      "current_page": 1, "per_page": 12, "from": 1, "to": 1,
      "has_more_pages": false, "last_page": 1, "total": 1,
      "total_relation": "exact", "total_is_exact": true, "result_cap": 10000
    }
  }
}
```

위 ID와 건수는 설명용입니다. title/summary는 현재 로케일의 문자열, itinerary는 다국어 JSON, 금액은 JSON 숫자, 날짜는 YYYY-MM-DD입니다. image_url은 이미지 미등록 시 null입니다. 코어 설정으로 총수 상한이 달라지며 상한 초과 시 total_relation은 at_least, last_page는 null일 수 있습니다.

관리 응답의 추가 필드는 published(bool), summary_translations(ko/en 객체), title_translations(이커머스 다국어 상품명)입니다. 관리 departure 응답은 공개 departure 필드에 capacity(int), reserved(int), is_active(bool)를 더합니다. title은 이커머스 관리 API가 수정하며 이 모듈 PATCH로 변경하지 않습니다.

PATCH 예시 body는 `{"published":true,"region":"jeju","theme":"nature","duration_days":3,"summary":{"ko":"요약","en":"Summary"}}`입니다. itinerary는 최대 365개 day/title 항목이며 선택 description ko/en 객체를 포함할 수 있습니다. product_id는 변경할 수 없습니다.

POST/PUT 예시 body:

```json
{"product_option_id":201,"departure_date":"2026-11-01","return_date":"2026-11-03","capacity":24,"is_active":true}
```

product_option_id, departure_date, return_date, capacity는 필수입니다. is_active는 선택 bool입니다. product_id/reserved/unit_price 입력은 금지합니다. 옵션은 URL 상품에 속해야 하며 생성·수정은 이미 존재하는 이커머스 옵션을 연결합니다. 새 이커머스 옵션 작성은 공식 이커머스 관리 경로의 책임입니다. 생성 성공도 native moduleSuccess에 따라 200과 data 객체를 반환합니다.

오류는 ResponseHelper 봉투의 `success:false,message`입니다. 401은 토큰 없음, 403은 관리자/권한 부족, 404는 공개되지 않거나 없는 상품·출발, 422는 잘못된 날짜·범위·금지 필드·옵션 소속, 409는 잠금 안에서 확인된 재고·정원·옵션 중복/교체·문의 사용 날짜 충돌입니다. 422에는 errors 필드별 배열이 있습니다. 일반 서버 오류는 코어 예외 처리 경로가 처리합니다.

[네이티브 생성 API 레퍼런스](api/README.md)는 격리 실제 HTTP 응답을 기반으로 만들었습니다. [생성 스크립트](../tests/generate-domain-docs.php)는 운영 DB나 외부 URL을 호출하지 않습니다. 통합 시 리드가 api.php에 catalog.php를 연결해야 일반 모듈 등록 경로에서 이 API가 열립니다.
