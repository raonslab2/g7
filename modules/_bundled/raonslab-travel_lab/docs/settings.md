# Travel Lab — 설정·권한·라우트

## 설정 스키마

<!-- @generated:settings-schema START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_`getSettingsSchema()` 선언이 없습니다._
<!-- @generated:settings-schema END -->

<!-- @intent START -->
config/catalog.php는 business_timezone=Asia/Seoul, Region/Theme enum 목록, 합성 sample_departure_anchor를 선언합니다. config/support.php는 TRAVEL_LAB_ISOLATED와 TRAVEL_LAB_SUPPORT_PROVISIONING 둘 다 참일 때 provisioning을 허용합니다. 전역 app.timezone, 운영 설정, 외부 연결을 변경하지 않습니다. 기본 설치는 샘플을 생성하지 않습니다.
<!-- @intent END -->

## 권한

<!-- @generated:permissions START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 카테고리 | 이름 | 액션 | 라우트 키 |
|---|---|---|---|
| `catalog` | 여행 카탈로그 | `read`, `update` | - |
| `inquiries` | 테스트 문의 | `read`, `update` | `inquiry` |
| `support` | 여행 지원 | `read`, `update` | - |
<!-- @generated:permissions END -->

<!-- @intent START -->
catalog/inquiries의 read/update는 native admin/manager 역할, support의 read/update는 admin 역할이 기본 대상입니다. 실제 설치된 권한/역할 관계도 확인해야 하며 Module 소스 선언만으로 기존 DB 권한이 바뀌었다고 주장하지 않습니다. 인증은 Bearer Sanctum입니다. 본인 카트/문의는 서비스 소유권 검사와 native scope로 격리하고 타인 자료는 404로 거절합니다. 지원 보드의 native 관리자 범위와 여행 지원 API 권한은 [support.md](support.md)에 따릅니다.
<!-- @intent END -->

## 메뉴

<!-- @generated:menus START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 구분 | slug | 이름 | URL | 하위 |
|---|---|---|---|---|
| 관리자 | `travel-lab-catalog` | 여행 카탈로그 | `/admin/travel-lab/catalog` | - |
| 관리자 | `travel-lab-inquiries` | 시험 접수 | `/admin/travel-lab/inquiries` | - |
| 관리자 | `travel-lab-support` | 여행 고객지원 | `/admin/travel-lab/support` | - |
<!-- @generated:menus END -->

<!-- @intent START -->
여행 카탈로그 / 시험 접수 / 여행 고객지원 메뉴는 각각 catalog.read / inquiries.read / support.read를 요구합니다. resources/routes.json이 관리자 UI 매핑을 소유합니다. 새 상품·옵션/가격은 이커머스 관리자 계약을 재사용하고 여행 메타/출발 API로 연결합니다.
<!-- @intent END -->

## 라우트

<!-- @generated:routes START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 종류 | 파일 | URL prefix |
|---|---|---|
| `api` | `src/routes/api.php` | `/api/modules/raonslab-travel_lab/...` |

확장 라우트는 **활성 상태인 확장의 것만** 등록됩니다. 라우트 정의를 바꾸면 라우트 캐시 재생성이 필요합니다.
<!-- @generated:routes END -->

<!-- @intent START -->
ModuleRouteServiceProvider가 /api/modules/raonslab-travel_lab 및 api.modules.raonslab-travel_lab. 접두사와 api 그룹을 부착합니다. catalog/workflow/support는 api.php에서 require됩니다. 정적 scanner의 route 집계 0은 이 분할 구조의 제한이며 수동으로 집계 표를 고치지 않습니다. 실제 HTTP inventory의 카탈로그 12개는 [api/](api/README.md), 전체 계약은 [domain-api.md](domain-api.md)/[support-api.md](support-api.md)/[워크플로 증거](../tests/WORKFLOW_EVIDENCE.md)를 봅니다. 워크플로 한도는 사용자별 전체120/분, 카트 쓰기60, 접수10, 취소20, 관리자 변경60이며 prefix로 버킷을 분리합니다. 429의 Retry-After 이후 접수 내용이 같으면 동일 키로 재시도합니다.
<!-- @intent END -->

## 의존 관계

<!-- @generated:dependencies START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
**이 확장이 의존하는 확장**

| 확장 | 유형 | 버전 제약 | 번들 |
|---|---|---|---|
| `sirsoft-board` | 모듈 | `>=1.1.2` | ✅ |
| `sirsoft-ecommerce` | 모듈 | `>=1.2.1` | ✅ |
| `sirsoft-page` | 모듈 | `>=1.1.2` | ✅ |

**이 확장에 의존하는 확장** (이 확장을 비활성화하면 함께 영향을 받습니다)

| 확장 | 유형 | 요구 버전 |
|---|---|---|
| `raonslab-travel_lab` | 템플릿 | `>=0.1.2` |
<!-- @generated:dependencies END -->

<!-- @intent START -->
의존 버전은 module.json이 단일 출처입니다. native commerce ProductOption 가격·CartService 공식 계산을 우회하지 않습니다. 지원은 native board/page 개념과 서비스를 사용합니다. 여행 사용자 템플릿은 API 소비자이며 PHP 소스나 G7 DB를 Spring 프로젝트에 이식하지 않습니다.
<!-- @intent END -->
