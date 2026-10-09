# Travel Lab — 확장점

## 발행 훅

<!-- @generated:hooks-published START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 훅을 발행하지 않습니다._
<!-- @generated:hooks-published END -->

<!-- @intent START -->
시험 접수 이벤트는 InquiryEvent DB 기록입니다. 이것을 외부 예약·결제 알림 신호로 해석하거나 자동 발송을 추가하지 않습니다. 생성 표는 코드에 실제 존재하는 발행 훅만 기록합니다.
<!-- @intent END -->

## 구독 훅

<!-- @generated:hooks-subscribed START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 훅 이름 | 유형 | 리스너 | 메서드 | 우선순위 |
|---|---|---|---|---|
| `sirsoft-board.notification.extract_data` | filter | `SuppressTravelSupportNotifications` | `suppressTravelSupport` | 95 |
| `sirsoft-board.post.after_delete` | action (미선언) | `ExcludeTravelSupportQuestionsFromSearch` | `removeFromExternalIndex` | - |
| `sirsoft-board.post.after_restore` | action (미선언) | `ExcludeTravelSupportQuestionsFromSearch` | `removeFromExternalIndex` | - |
| `sirsoft-board.search.post.index_should_update` | filter | `ExcludeTravelSupportQuestionsFromSearch` | `filterIndexUpdate` | - |
| `sirsoft-ecommerce.order.before_create` | action (미선언) | `BlockTravelCommerceCheckout` | `handle` | 1 |
| `sirsoft-ecommerce.order.before_payment_complete` | action (미선언) | `BlockTravelCommerceCheckout` | `handle` | 1 |
| `sirsoft-ecommerce.product.before_delete` | action (미선언) | `ProtectTravelCommerceCatalog` | `beforeDelete` | 1 |
| `sirsoft-ecommerce.product.before_update` | action (미선언) | `ProtectTravelCommerceCatalog` | `beforeUpdate` | 1 |
| `sirsoft-ecommerce.product.filter_update_data` | filter | `ProtectTravelCommerceCatalog` | `filterUpdate` | - |
| `sirsoft-ecommerce.temp_order.before_create` | action (미선언) | `BlockTravelCommerceCheckout` | `handle` | 1 |
| `sirsoft-ecommerce.temp_order.before_update` | action (미선언) | `BlockTravelCommerceCheckout` | `handle` | 1 |
<!-- @generated:hooks-subscribed END -->

<!-- @intent START -->
BlockTravelCommerceCheckout은 native temp_order/order/payment 선행 훅을 동기로 구독해 실제 주문 전환을 거절합니다. ProtectTravelCommerceCatalog는 product.before_delete/before_update와 마지막 filter_update_data에서 여행 상품 삭제/연결 옵션 제거를 변경 전에 거절합니다. 최종 데이터 필터는 type=filter이며 항상 동기입니다. 게시판 notification.extract_data는 여행 지원 대상만 skip하고 search.post.index_should_update는 비공개 질문을 지원 경로의 색인에서 제외합니다. 외부 검색 엔진의 모든 색인 경로가 검증되었다고 주장하지 않습니다.
<!-- @intent END -->

## 훅 리스너

<!-- @generated:listeners START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 리스너 | 구독 훅 | 등록 방식 | HookListenerInterface | 파일 |
|---|---|---|---|---|
| `BlockTravelCommerceCheckout` | 4개 | 명시 등록 | ✅ | `src/Listeners/BlockTravelCommerceCheckout.php` |
| `ExcludeTravelSupportQuestionsFromSearch` | 3개 | 명시 등록 | ✅ | `src/Listeners/ExcludeTravelSupportQuestionsFromSearch.php` |
| `ProtectTravelCommerceCatalog` | 3개 | 명시 등록 | ✅ | `src/Listeners/ProtectTravelCommerceCatalog.php` |
| `SuppressTravelSupportNotifications` | 1개 | 명시 등록 | ✅ | `src/Listeners/SuppressTravelSupportNotifications.php` |
<!-- @generated:listeners END -->

<!-- @intent START -->
리스너는 Module::getHookListeners()로 선언합니다. 서비스/리스너의 데이터 질의는 Repository 계약을 따릅니다. ProductHasOrderHistoryException은 native 409 매핑을 재사용하지만 실제 주문 이력을 만들지 않습니다. 모듈 응답 어댑터가 여행 충돌 사유를 표시합니다. 연결 옵션 제거는 native ValidationException의 options 오류로 422입니다.
<!-- @intent END -->

## 레이아웃 확장

<!-- @generated:layout-extensions START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_레이아웃 확장이 없습니다._
<!-- @generated:layout-extensions END -->

<!-- @intent START -->
관리자 메뉴와 모듈 자체 레이아웃을 사용합니다. 다른 RAON 운영 템플릿에 직접 패치를 넣지 않습니다. 사용자 travel 템플릿은 모듈 API를 소비하는 별도 확장입니다.
<!-- @intent END -->

## 미들웨어

<!-- @generated:middleware START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 미들웨어가 없습니다._
<!-- @generated:middleware END -->

<!-- @intent START -->
TravelLabServiceProvider::boot()가 TravelCatalogConflictResponse를 api 그룹에 추가합니다. 이 클래스는 인증 미들웨어가 아니며 현재 요청에 동기 삭제 가드가 표시한 409만 변경합니다. 요청 표시를 시작과 finally에서 지우며 일반 커머스 응답은 그대로 둡니다. 인증/권한은 각 라우트의 Sanctum/permission/admin 미들웨어가 판정합니다. Provider 직접 등록은 정적 Module 선언 스캐너 표에 나타나지 않을 수 있으므로 실제 Router의 api 그룹을 별도로 검사합니다.
<!-- @intent END -->

## 브로드캐스트 채널

<!-- @generated:channels START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 브로드캐스트 채널이 없습니다._
<!-- @generated:channels END -->

<!-- @intent START -->
실시간 예약 확정이나 외부 공급자 브로드캐스트를 제공하지 않습니다. 상태는 API 재조회로 확인합니다.
<!-- @intent END -->

## 스케줄

<!-- @generated:schedules START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 스케줄이 없습니다._
<!-- @generated:schedules END -->

<!-- @intent START -->
모듈은 별도 Scheduler/Worker/Request 큐를 만들지 않습니다. 문서의 WAVE 기록은 실행 큐가 아닙니다.
<!-- @intent END -->

## 알림 정의

<!-- @generated:notifications START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 알림 정의가 없습니다._
<!-- @generated:notifications END -->

<!-- @intent START -->
여행 지원의 native 게시판 알림은 skip 대상으로 처리합니다. LAB 환경은 array mail/sync queue이며 외부 메일·SMS가 아닙니다. 다른 일반 게시판의 알림 설정은 변경하지 않습니다. 상세 [지원 계약](support.md)을 확인합니다.
<!-- @intent END -->
