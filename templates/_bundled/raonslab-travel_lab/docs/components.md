# RAON 트래블랩 — 컴포넌트

> 템플릿이 제공하는 컴포넌트 · 진입점: [AGENTS.md](../AGENTS.md)

## 제공 컴포넌트

<!-- @generated:components START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
컴포넌트 30개 (루트: `src/components`).

| 분류 | 개수 |
|---|---|
| `basic` | 23개 |
| `composite` | 7개 |
<!-- @generated:components END -->

<!-- @intent START -->
**고유 컴포넌트는 composite 4개**입니다. 나머지(basic 23 · Toast · Modal · Pagination)는
`sirsoft-basic` 템플릿(MIT)에서 가져온 것으로, 레이아웃 작성 규약(`Div` · `Button` 등 기본
컴포넌트만 사용)을 그대로 지키기 위한 어휘입니다.

| 컴포넌트 | 왜 있는가 | 잡을 때 |
|---|---|---|
| `ScenicArt` | 상품 이미지가 없을 때의 대체 표지 · 홈 히어로/테마 카드 배경. raonslab 이 직접 그린 인라인 SVG(MIT) — 외부 이미지·사진·로고를 쓰지 않는다 | `variant`(coast · mountain · city · island · forest · desert · snow · lake)를 주거나, 비우고 `seed`(상품 id)를 주면 결정적으로 장면이 정해진다 — 같은 상품은 항상 같은 그림. 모르는 variant 는 seed 로 대체. `title` 을 비우면 장식(`aria-hidden`) |
| `PriceTag` | 금액 표기를 통화 인지로 단일화 | `amount` 와 서버 `currency_code` 를 `currency` 로. `Intl.NumberFormat` 이 기호·자릿수를 정하고, 통화 코드가 없으면 단위를 지어내지 않고 숫자만. `원` 을 레이아웃에 붙이지 않는다 |
| `StatusBadge` | 상담 요청 상태 표시 | 서버 Enum 문자열(`TEST_INQUIRY` 등)을 그대로 `status` 로. 문구는 `travel.status.{소문자}`, 모르는 값은 `travel.status.unknown` |
| `PageBody` | 기획전(native 페이지) 본문을 다른 템플릿 없이 안전하게 그린다 | `content`·`contentMode` 를 서버 응답에 바인딩. `text`(그 밖의 값 포함)는 React 자식으로 이스케이프·줄바꿈 보존, `html` 은 전용 DOMPurify 인스턴스가 p·br·h1–h6·strong/b·em/i·ul/ol/li·blockquote·code/pre 만 남기고 속성은 0개(링크는 글자만, 이미지·미디어·SVG·폼·style·script 제거). 정책을 넓히는 prop 이 없다. 이미지는 본문 밖 `ScenicArt` 가 맡는다 |

**이 목록이 다른 확장과의 계약**입니다. 코어 `ComponentRegistry` 는 `components.json` 을 읽어
전역 `RaonslabTravelLab` 에서 같은 이름의 export 를 찾으므로, `src/index.ts` 의 export 이름이 곧
레이아웃 계약입니다. 컴포넌트를 추가할 때는 소스 · `src/index.ts` export · `template.json`
레지스트리 · `components.json` 을 함께 갱신합니다 — 하나라도 빠지면 그 컴포넌트만 조용히
렌더되지 않으며, 정적 계약 테스트가 이 정합을 검사합니다.

TSX 를 고쳤으면 `template:build raonslab-travel_lab --production` 후 `dist/` 를 함께 커밋합니다.
<!-- @intent END -->
