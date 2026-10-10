# Travel Lab — 프론트엔드

## 레이아웃

<!-- @generated:layouts START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
레이아웃 5개 (루트: `resources/layouts`).

| 그룹 | 개수 |
|---|---|
| `admin` | 5개 |

| 레이아웃 | 그룹 | 종류 | extends |
|---|---|---|---|
| `admin_travel_lab_campaigns` | `admin` | 화면 | `_admin_base` |
| `admin_travel_lab_catalog` | `admin` | 화면 | `_admin_base` |
| `admin_travel_lab_inquiry_detail` | `admin` | 화면 | `_admin_base` |
| `admin_travel_lab_inquiry_list` | `admin` | 화면 | `_admin_base` |
| `admin_travel_lab_support` | `admin` | 화면 | `_admin_base` |
<!-- @generated:layouts END -->

<!-- @intent START -->
모듈은 여행 카탈로그, 문의 목록/상세, 고객지원 관리자 JSON 레이아웃을 소유합니다. resources/routes.json이 경로와 레이아웃을 연결합니다. 실제 방문자 메인/검색/상세/장바구니/상태/도움말은 templates/_bundled/raonslab-travel_lab의 별도 화면입니다. 직접 HTML 태그 대신 G7 기본 컴포넌트와 다국어 props를 사용합니다.
<!-- @intent END -->

## 액션 핸들러

<!-- @generated:handlers START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 액션 핸들러가 없습니다._
<!-- @generated:handlers END -->

<!-- @intent START -->
모듈 관리자 JSON은 G7Core.dispatch의 apiCall/navigate/setState/refetchDataSource/modal 계약을 사용합니다. handler api/nav 또는 G7Core.api.call을 쓰지 않습니다. 데이터소스 봉투는 실제 Resource 응답 경로를 확인합니다. visitor의 접수 키/가격 표시 전용 핸들러는 별도 여행 템플릿이 소유합니다.
<!-- @intent END -->

## 전역 진입점

<!-- @generated:frontend-entry START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_프론트 엔트리포인트가 없습니다._
<!-- @generated:frontend-entry END -->

<!-- @intent START -->
모듈은 별도 전역 JS 부트스트랩을 제공하지 않습니다. 인증은 코어 AuthManager/Sanctum, 상태와 액션은 G7Core가 소유합니다. 가격·정원·권한을 UI 상태만으로 결정하지 않습니다.
<!-- @intent END -->

## 에셋

<!-- @generated:assets START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 경로 | 구분 |
|---|---|
| `editor-spec.json` | 레이아웃 편집기 스펙 (manifest) |

로딩 설정: `{"strategy":"global","priority":100,"dependencies":[]}`
<!-- @generated:assets END -->

<!-- @intent START -->
모듈 관리자 UI는 코어 컴포넌트와 기존 관리자 자산을 재사용합니다. resources/js의 관리자 레이아웃 테스트는 런타임 JS 번들이 아닙니다. 사용자 템플릿의 자체 SVG/스타일/커밋된 dist는 해당 템플릿 문서/빌드 증거가 소유합니다. 고객 로고·사진·비공개 자산을 복사하지 않습니다.
<!-- @intent END -->
