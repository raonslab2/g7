# Travel Lab — 레이아웃 편집기 스펙

## 선언 요약

<!-- @generated:editor-spec-summary START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 항목 | 값 |
|---|---|
| manifest | `modules/_bundled/raonslab-travel_lab/editor-spec.json` |
| 형태 | 단일 파일 (인라인) |
| 스펙 버전 | `1.0.0` |
| 스타일 시스템 | - |
| 다크 모드 전략 | - |

> 단일 파일 · 프리뷰 샘플 4 · 페이지 상태 6 · 액션 레시피 3
<!-- @generated:editor-spec-summary END -->

<!-- @intent START -->
`editor-spec.json`은 관리자 레이아웃의 네 데이터소스와 실제 `_global.travel*` 상태를 위한 편집기 계약입니다. 모든 응답·인물·식별자·금액·일정은 자체 작성한 합성 미리보기이며 실행 API/시드/운영 자료가 아닙니다. `travelEditorPreview.admin_uuid`는 합성 식별 메타데이터일 뿐 `currentUser`, 토큰, 실제 관리자 권한을 설정하지 않습니다.
<!-- @intent END -->

## 선언 블록

<!-- @generated:editor-spec-blocks START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 블록 | 역할 | 항목 수 | 출처 |
|---|---|---|---|
| `sampleData.byDataSourceId` | 레이아웃 `data_sources` ID 로 붙는 프리뷰 응답 | 4 | `editor-spec.json (인라인)` |
| `sampleGlobal` | `_global.*` 프리뷰 baseline 시드 | 13 | `editor-spec.json (인라인)` |
| `states.groups` | 상태 변종을 적용할 범위(라우트·베이스 레이아웃) | 6 | `editor-spec.json (인라인)` |
| `stateLabels` | 상태값 친화 명칭 카탈로그 | 21 | `editor-spec.json (인라인)` |
| `actionRecipes` | 친화 명칭 → 액션 JSON 레시피 | 3 | `editor-spec.json (인라인)` |
<!-- @generated:editor-spec-blocks END -->

<!-- @intent START -->
기본 컴포넌트 팔레트를 사용하며 새로운 컴포넌트·props·핸들러를 선언하지 않습니다. `sampleData`, `sampleGlobal`, `states`, `stateLabels`와 native `refetchDataSource` 레시피만 제공합니다. 스펙은 활성 설치 경로에서 읽으므로 bundled JSON 추가 후 공식 module update가 필요합니다.
<!-- @intent END -->

## 컴포넌트 팔레트

<!-- @generated:editor-spec-palette START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 `componentPalette` 를 선언하지 않습니다 — 편집기 팔레트에 추가되는 항목이 없습니다._
<!-- @generated:editor-spec-palette END -->

<!-- @intent START -->
코어의 기본 컴포넌트를 사용합니다. 여행 사용자 전용 컴포넌트와 편집기 선언은 사용자 템플릿의 소유 범위입니다.
<!-- @intent END -->

## 샘플 데이터와 페이지 상태

<!-- @generated:editor-spec-samples START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 자리 | 역할 | 개수 | ID |
|---|---|---|---|
| `sampleData.byDataSourceId` | 레이아웃 `data_sources` ID 로 붙는 프리뷰 응답 | 4 | `catalog` · `travel_candidates` · `inquiries` · `inquiry` |
| `sampleData.byEndpointPattern` | 엔드포인트 패턴으로 붙는 프리뷰 응답 | 미선언 | - |
| `states.groups` | 상태 변종을 적용할 범위(라우트·베이스 레이아웃) | 6 | `*/admin/travel-lab` · `*/admin/travel-lab/catalog` · `*/admin/travel-lab/inquiries` · `*/admin/travel-lab/inquiries/:id` · `travel_metadata_modal` · `travel_departure_modal` |

_이 확장 레이아웃의 `data_source` 는 전부 프리뷰 샘플이 붙습니다 (이 확장 또는 번들 템플릿 스펙이 커버)._
<!-- @generated:editor-spec-samples END -->

<!-- @intent START -->
`catalog`, `travel_candidates`, `inquiries`, `inquiry`의 실제 Resource 응답 봉투·pagination·옵션·권한 필드에 대응하는 유한 샘플을 제공합니다. 다섯 문의 상태와 허용 전이, 읽기 전용/빈 결과, 후보 조회 오류·입력 오류, 저장 중 disabled 상태를 선언합니다. 날짜는 합성 기준일 `2027-10-20 Asia/Seoul`의 미래 출발이며 실행 시계가 아닙니다. JSON `null`은 네트워크 대기의 `undefined`를 재현하지 않습니다. 실제 pending 오버레이·HTTP 오류 핸들러·재시도·권한은 설치 API/브라우저 검증 대상이고 편집기 샘플 통과로 대신하지 않습니다.
<!-- @intent END -->

## 수정 시 동반 의무

<!-- @generated:editor-spec-obligations START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 이런 변경을 했다면 | 편집기 스펙에서 함께 할 일 |
|---|---|
| 컴포넌트를 새로 만들었다 | `componentPalette` 에 항목 추가 · `componentCapabilities` 에 편집 역량 선언 · `nesting` 에 담길 자리 규정 |
| 레이아웃에 `data_sources` 를 추가했다 | `sampleData` 에 같은 ID 로 프리뷰 응답 추가 (없으면 편집기 캔버스만 빈 화면) |
| `_global.*` 을 새로 읽는다 | `sampleGlobal` 에 baseline 값 추가 |
| 빈 목록·오류 같은 화면 변종을 추가했다 | `states` 에 변종 추가 · `stateLabels` 에 친화 명칭 |
| 새 액션·조건 패턴을 도입했다 | `actionRecipes` / `conditionRecipes` 에 친화 명칭 등록 |

편집기 스펙은 JSON 이므로 빌드가 필요 없습니다. 다만 편집기 서빙은 **활성 디렉토리만** 읽으므로(`_bundled` 폴백 없음) 편집 후 반드시 반영합니다:

```bash
php artisan module:update raonslab-travel_lab --force
```
<!-- @generated:editor-spec-obligations END -->

<!-- @intent START -->
레이아웃/data_source/공개 컴포넌트 props를 바꾸면 코어 editor-spec 규약과 생성된 coverage를 재검토합니다. 공개 지원을 추가할 때 샘플/페이지 상태를 함께 제공하고 선언되지 않은 preview 지원을 주장하지 않습니다. JSON 변경은 공식 update, 클래스/TS 변경은 해당 소유 확장 빌드와 dist 반영을 수행합니다.
<!-- @intent END -->
