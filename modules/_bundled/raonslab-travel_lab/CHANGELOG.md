# Changelog

## [Unreleased]

## [0.1.3] - 2026-10-09

### Added

- 페이지 모듈에 저장된 두 개의 고정 기획전을 발행된 것만 공개하는 기획전 목록·상세 API를 추가했습니다. 초안·미등록 주소는 관리자를 포함한 모든 방문자에게 찾을 수 없음으로 응답하고, 작성자·버전 이력·첨부는 내보내지 않습니다.
- 관리자 화면에 기획전 관리 메뉴를 추가했습니다. 페이지 읽기 권한으로 들어가 기존 페이지 편집·버전·발행 화면으로 이동합니다.
- 격리 LAB 에서만 명시적으로 실행하는 기획전 준비 명령을 추가했습니다. 이미 있는 페이지는 초안·편집본을 포함해 그대로 두고 없는 기획전만 합성 문구로 만듭니다. 기본 설치·업데이트·샘플 시드는 페이지를 만들지 않습니다.
- 기획전 페이지가 바뀌면 여행 홈·기획전 화면의 검색엔진용 화면 캐시를 함께 비우도록 했습니다.

### Compatibility

- 새 공개 API 를 쓰는 여행 템플릿이 유일한 소비 확장이므로 템플릿의 최소 모듈 의존성을 >=0.1.3 으로 올립니다. 페이지 모듈 공개 API 는 바뀌지 않아 기존 >=1.1.2 제약을 유지하며 코어·이커머스·게시판 제약도 그대로입니다.

## [0.1.2] - 2026-10-09

### Fixed

- 사용자 PK 잠금 뒤 멱등 키를 일관 읽기로 확인하여 없는 키의 InnoDB gap lock과 출발 정원 잠금 사이의 교착 가능성을 줄였습니다. 동일 사용자 직렬화·고유 키·native 가격 계산·3회 transaction 재시도는 유지합니다. 실제 MySQL 재검증 전에는 새 P3를 CLOSED로 표시하지 않습니다.
- 여행 API의 native numeric 한도 검사를 scoped DatabaseStore/DatabaseLock의 짧은 입장 잠금으로 직렬화합니다. 컨트롤러는 잠금 밖에서 실행하며 한도 수치·사용자/익명 카운터 분리·native 429를 유지합니다. 잠금 timeout/지원되지 않는 store/유실된 lease는 Retry-After: 1의 503입니다. backend 오류는 native 오류로 남고 cleanup 오류가 원래 오류를 덮지 않습니다.

### Compatibility

- 격리 일반 런타임은 native database cache와 cache_locks가 필요하며 일반 guarded TEST는 array cache를 사용합니다. 한도 기능 테스트만 실제 DB cache를 명시적으로 사용합니다. 여행 템플릿이 유일한 소비 확장이므로 최소 모듈 의존성을 >=0.1.2로 올립니다. 코어/이커머스/게시판의 공개 API는 바뀌지 않습니다.

### Changed

- bundled 의존성의 정상 신규 설치와 실패 중단을 보장하는 코어 최소 요구 버전을 7.0.12로 상향했습니다. 기존 모듈 Service·Route·가격 계약은 바뀌지 않아 확장 간 최소 API 버전은 유지합니다.

### Fixed

- 공지/FAQ 선택적 Sanctum 인증을 공개 제한 계산 전에 실행하여 같은 IP의 로그인 사용자가 익명 제한 카운터를 공유하던 문제를 수정했습니다. 모듈 전용 우선순위 표지만 추가하며 코어 인증 동작·분당 한도·비공개 문의 권한은 유지합니다. 기존 공개 API 시그니처를 바꾸지 않아 추가 소비 확장 버전 상향은 없습니다.
- 공개 조회·내 문의·문의 작성의 제한 카운터를 분리하여 정상 조회 후 문의 작성이 조기 차단되는 문제를 수정했습니다. 기존 인증·분당 제한 수치는 유지합니다.
- 관리자 여행 목록은 실제 조회하는 카탈로그만 로딩 판정에 사용하여 페이지 이동과 상품 편집 클릭을 허용합니다.

## [0.1.1] - 2026-10-09

### Added

- Native product-to-travel registration, translated metadata editing, option-selection candidates and generated API documentation.
- Synchronous native deletion/option guards with truthful conflicts, user-specific workflow throttles and a domestic Asia/Seoul business-day contract.

### Fixed

- Short Korean keyword discovery searches decoded translations; detail shows unavailable future dates and prices only available departures.
- Optional itinerary registration stores an empty array instead of a server error.
- Travel support defaults unrestricted private moderation to administrators; shared board hardening is verified separately.

### Compatibility

- Only bundled raonslab-travel_lab template consumes the new travel API; its minimum module dependency becomes >=0.1.1. Existing G7 and other extensions' public APIs/versions remain unchanged.

## [0.1.0] - 2026-10-09

### Added

- 이커머스 상품·출발 옵션을 기반으로 지역, 테마, 날짜, 가격 검색과 여행 상세·출발 일정 조회를 제공합니다.
- 권한 있는 관리자가 여행 공개 정보와 테스트 출발 정원을 관리할 수 있습니다.
- 실제 거래를 만들지 않는 재현 가능한 합성 여행 카탈로그와 테스트 문의 데이터 계약을 제공합니다.

- 공식 장바구니·금액 계산을 재사용하는 영속 시험 접수와 상태 처리, 계산 기록·처리자 이력을 제공합니다.
- 옵션 재고와 여행 정원의 최소값에 대한 잠금·멱등성 검증 및 여행 항목의 일반 주문·결제 차단 훅을 추가합니다.
- 격리 고객지원 게시판의 공지·FAQ·비공개 문의와 여행 전용 관리자/사용자 UI를 추가합니다.

### Changed

- 코어 7.0.11, 이커머스 1.2.1, 게시판·페이지 1.1.2 이상을 요구합니다. 기존 확장의 공개 표면을 변경하지 않아 다른 번들 확장의 최소 버전 변경은 없습니다.
