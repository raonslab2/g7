# RAON 트래블랩 — 레이아웃 편집기 스펙

> 레이아웃 편집기에 선언한 팔레트·컨트롤·샘플 데이터 · 진입점: [AGENTS.md](../AGENTS.md)

## 선언 요약

<!-- @generated:editor-spec-summary START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 편집기 스펙(`editor-spec.json`)을 두지 않습니다. 편집기는 코어 기본 팔레트와 활성 템플릿의 스펙만으로 이 확장의 화면을 다룹니다._
<!-- @generated:editor-spec-summary END -->

<!-- @intent START -->
이 템플릿은 **편집기 스펙(`editor-spec.json`)을 두지 않습니다.** v0.1.0 은 고객 화면과 여행 모듈
API 계약을 먼저 고정하는 데 집중했고, 레이아웃 편집기 지원은 범위 밖으로 두었습니다. 화면은
레이아웃 JSON 을 직접 고치고 `template:update --force` 로 반영하는 방식으로 다룹니다.
<!-- @intent END -->

## 선언 블록

<!-- @generated:editor-spec-blocks START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_선언된 편집기 스펙 블록이 없습니다._
<!-- @generated:editor-spec-blocks END -->

<!-- @intent START -->
선언한 블록이 없습니다. 팔레트 · 중첩 · 컴포넌트 역량 · 컨트롤을 선언하지 않았으므로, 이 템플릿을
활성화한 상태에서는 편집기가 이 템플릿의 컴포넌트를 놓거나 스타일 컨트롤로 다룰 수 없습니다.
<!-- @intent END -->

## 컴포넌트 팔레트

<!-- @generated:editor-spec-palette START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 편집기 팔레트에 항목을 추가하지 않습니다._
<!-- @generated:editor-spec-palette END -->

<!-- @intent START -->
템플릿 스펙이 생기면 여기에 29개 컴포넌트가 올라옵니다. 특히 고유 composite 3종은 값 형태에 주의합니다
— `ScenicArt` 의 `variant` 는 8개 장면 중 하나(또는 비우고 `seed`), `PriceTag` 의 `currency` 는
서버 `currency_code` 바인딩이어야 하므로 리터럴 통화 코드를 넣는 컨트롤을 두지 않습니다.
<!-- @intent END -->

## 샘플 데이터와 페이지 상태

<!-- @generated:editor-spec-samples START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 편집기 스펙을 두지 않아 선언된 샘플 데이터·페이지 상태가 없습니다._

**프리뷰 샘플이 없는 `data_source` 12개** — 편집기 캔버스에서 이 자리만 빈 화면이 됩니다. 실제 화면은 정상이라 오류도 경고도 남지 않습니다.

`catalog` · `departing_trips` · `departures` · `facets` · `faqs` · `featured_trips` · `inquiries` · `inquiry` · `notices` · `question_detail` · `questions` · `travel_cart_badge`
<!-- @generated:editor-spec-samples END -->

<!-- @intent START -->
위 12개 데이터소스가 모두 여행 모듈(`/api/modules/raonslab-travel_lab`) 응답입니다. 편집기 스펙이
없으니 프리뷰 샘플도 없고, **레이아웃 편집기 캔버스에서 이 자리들은 빈 화면**으로 보입니다 —
실제 사이트는 정상이라 오류도 경고도 남지 않습니다. 홈의 추천·출발 임박 목록, 검색 결과·필터 옵션,
출발편 목록, 장바구니 배지, 상담 요청 목록/상세, 고객센터 공지·FAQ·문의가 해당합니다.

`product` · `cart` · `current_user` 가 목록에 없는 것은 다른 확장·템플릿 스펙이 같은 ID 의 샘플을
이미 선언하고 있기 때문이며, 그 샘플은 여행 응답 형태가 아니므로 캔버스 표시가 실제와 다를 수
있습니다.

샘플을 둔다면 여행 모듈만 쓰는 ID 는 이 템플릿 스펙의 `sampleData` 에, 여러 템플릿이 공유할 응답은
여행 모듈 스펙에 둡니다.
<!-- @intent END -->

## 수정 시 동반 의무

<!-- @generated:editor-spec-obligations START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 아직 편집기 스펙을 두지 않습니다. 아래 변경이 생기면 `editor-spec.json` 을 신설합니다._

| 이런 변경을 했다면 | 편집기 스펙에서 함께 할 일 |
|---|---|
| 컴포넌트를 새로 만들었다 | `componentPalette` 에 항목 추가 · `componentCapabilities` 에 편집 역량 선언 · `nesting` 에 담길 자리 규정 |
| 레이아웃에 `data_sources` 를 추가했다 | `sampleData` 에 같은 ID 로 프리뷰 응답 추가 (없으면 편집기 캔버스만 빈 화면) |
| `_global.*` 을 새로 읽는다 | `sampleGlobal` 에 baseline 값 추가 |
| 빈 목록·오류 같은 화면 변종을 추가했다 | `states` 에 변종 추가 · `stateLabels` 에 친화 명칭 |
| 새 액션·조건 패턴을 도입했다 | `actionRecipes` / `conditionRecipes` 에 친화 명칭 등록 |
<!-- @generated:editor-spec-obligations END -->

<!-- @intent START -->
편집기 지원이 필요해지면 `editor-spec.json` 을 신설합니다. 순서는 `componentPalette` →
`nesting` → `componentCapabilities` → `controls` 이고, 그다음 위 12개 데이터소스의 `sampleData` 와
빈 목록·오류·미로그인 같은 `states` 를 더합니다. 완성된 선례는 `sirsoft-basic/editor-spec/` 입니다.

그 전까지는 레이아웃에 `data_source` 를 추가할 때마다 위 미커버 목록이 늘어난다는 점만 기억하면
됩니다 — `ext:docgen` 재실행 시 목록이 실측으로 갱신됩니다.
<!-- @intent END -->
