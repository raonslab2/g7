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
전용 팔레트 · 중첩 · 컴포넌트 역량 · 컨트롤 블록을 선언하지 않았습니다. 코어
`ComponentPalette.tsx` 는 그룹 스펙이 없으면 `components.json` 의 basic/composite/layout 분류를
사용합니다. 따라서 스펙 부재만으로 컴포넌트를 놓을 수 없다고 판단하지 않습니다. 전용 스타일·중첩
편집 역량은 별도 선언이 없으며, 실제 편집기 조작과 캔버스 프리뷰 검증은 NOT_RUN 입니다.
<!-- @intent END -->

## 컴포넌트 팔레트

<!-- @generated:editor-spec-palette START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 편집기 팔레트에 항목을 추가하지 않습니다._
<!-- @generated:editor-spec-palette END -->

<!-- @intent START -->
현재 `components.json` 에 등록한 30개 컴포넌트는 코어의 평면 분류 폴백 대상입니다.
`ScenicArt` 와 `PageBody` 도 이 등록 경로를 사용합니다. 고유 composite 4종은 값 형태에 주의합니다
— `ScenicArt` 의 `variant` 는 8개 장면 중 하나(또는 비우고 `seed`), `PriceTag` 의 `currency` 는
서버 `currency_code` 바인딩이어야 하므로 리터럴 통화 코드를 넣는 컨트롤을 두지 않습니다.
`PageBody` 의 `content`·`contentMode` 는 서버 응답 바인딩 전용이고 정제 정책을 바꾸는 컨트롤(예: purifyConfig)은
두지 않습니다 — 정책은 컴포넌트 상수가 단일 출처입니다.
<!-- @intent END -->

## 샘플 데이터와 페이지 상태

<!-- @generated:editor-spec-samples START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 편집기 스펙을 두지 않아 선언된 샘플 데이터·페이지 상태가 없습니다._

**프리뷰 샘플이 없는 `data_source` 15개** — 편집기 캔버스에서 이 자리만 빈 화면이 됩니다. 실제 화면은 정상이라 오류도 경고도 남지 않습니다.

`campaign` · `campaign_trips` · `campaigns` · `catalog` · `departing_trips` · `departures` · `facets` · `faqs` · `featured_trips` · `inquiries` · `inquiry` · `notices` · `question_detail` · `questions` · `travel_cart_badge`
<!-- @generated:editor-spec-samples END -->

<!-- @intent START -->
위 집계는 템플릿 단독 선언 기준이며, 활성 모듈 스펙을 병합한 캔버스 전체의 샘플 부재 판정이 아닙니다.
여행 도메인 응답의 샘플은 데이터 소유자인 여행 모듈 `editor-spec.json` 이 선언합니다. 현재 모듈의
`byDataSourceId` 는 `catalog` · `inquiries` · `inquiry` · `travel_candidates` 와 기획전
`campaigns` · `campaign` · `campaign_trips` · `campaign_pages` 를 포함합니다. 샘플은 편집기 전용
합성 응답이며 실제 API 오류의 대체 응답이 아닙니다. 샘플이 선언되지 않은 나머지 ID 와 활성 확장의
병합 결과는 실제 캔버스에서 별도로 확인해야 합니다. 이 문서는 캔버스 렌더 PASS 를 주장하지 않습니다.

`product` · `cart` · `current_user` 가 목록에 없는 것은 다른 확장·템플릿 스펙이 같은 ID 의 샘플을
이미 선언하고 있기 때문이며, 그 샘플은 여행 응답 형태가 아니므로 캔버스 표시가 실제와 다를 수
있습니다.

여행 도메인 `sampleData` · `sampleGlobal` · `states` 는 여행 모듈이 소유합니다. 템플릿은
`componentPalette` · `nesting` · `componentCapabilities` · `controls` 와 여러 확장이 함께 쓰는
공용 ID 의 샘플을 소유합니다. 같은 ID 를 여러 확장에 중복 선언해 병합 순서에 기대지 않습니다.
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
전용 템플릿 편집 역량이 필요해지면 `editor-spec.json` 을 신설합니다. 순서는 `componentPalette` →
`nesting` → `componentCapabilities` → `controls` 입니다. 도메인 데이터소스 샘플과 업무 상태는
여행 모듈 스펙에서 보완합니다. 완성된 선례는 `sirsoft-basic/editor-spec/` 입니다.

`ext:docgen` 의 템플릿 단독 집계와 실제 활성 스펙 병합 범위를 구분합니다. native Page 본문·발행·버전
편집은 별도 페이지 모듈 관리자 화면이 맡으며, 여행 템플릿의 전용 편집기 스펙 유무에 의존하지 않습니다.
<!-- @intent END -->
