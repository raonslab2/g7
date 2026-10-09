# RAON 트래블랩 — 핸들러

> 템플릿 전용 핸들러와 부트스트랩 · 진입점: [AGENTS.md](../AGENTS.md)

## 템플릿 전용 핸들러

<!-- @generated:handlers START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
핸들러 2개 (정의: `src/handlers/index.ts`).

| 핸들러 | 레이아웃에서 부르는 이름 |
|---|---|
| `travelLabEnsureInquiryKey` | (템플릿 전용 — 네임스페이스 없음) |
| `travelLabClearInquiryKey` | (템플릿 전용 — 네임스페이스 없음) |
<!-- @generated:handlers END -->

<!-- @intent START -->
두 핸들러는 **상담 요청(테스트)의 멱등 키 수명주기**만 담당합니다. 이름의 `travelLab` 접두사는
다른 템플릿·모듈 핸들러와의 충돌을 피하기 위한 것입니다.

| 핸들러 | 부르는 곳 | 하는 일 |
|---|---|---|
| `travelLabEnsureInquiryKey` | `travel/cart` 의 `cart` 데이터소스 `onSuccess` (`params.cartIds` · `params.quantities`) | 장바구니 id:인원 묶음의 순서 무관 지문을 만들어, 세션 저장소 `raon_travel_inquiry_key` 의 지문과 같으면 기존 키 재사용, 다르면 새 키 생성·저장. 결과를 `_global.travelInquiryKey` 에 둔다. 빈 장바구니면 키를 `null` 로 |
| `travelLabClearInquiryKey` | `POST /inquiries` 의 `onSuccess` 에서만 | 저장소와 `_global.travelInquiryKey` 를 비운다 — 다음 요청은 새 키 |

**왜 레이아웃 표현식이 아니라 핸들러인가**: 키를 새로고침 뒤에도 유지해야 하기 때문입니다.
네트워크 오류는 서버가 요청을 받았는지 알 수 없는 상태이므로, 재시도(새로고침 포함)는 반드시
같은 키를 실어야 서버가 중복을 막을 수 있습니다. 그래서 **`onError` 에서는 어떤 핸들러도
부르지 않습니다** — 오류 시 키를 새로 만드는 순간 같은 요청이 두 번 접수될 수 있습니다.

인원을 바꾸면 서버가 받는 요청 내용이 달라지므로 지문이 바뀌어 새 키가 됩니다. 요청 버튼은
`_global.travelInquiryKey` 가 확보되기 전에는 비활성입니다.

동작은 `__tests__/components/inquiryKey.test.ts`(재시도 시 같은 키 · 새로고침 후 같은 키 ·
묶음/인원 변경 시 새 키 · 성공 후 새 키 · 빈 장바구니)와 계약 테스트의 "상담 요청(테스트)
멱등성" 묶음이 고정합니다.
<!-- @intent END -->

## 부트스트랩

<!-- @generated:frontend-entry START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 항목 | 값 |
|---|---|
| 엔트리 파일 | `src/index.ts` |
| 전역 객체 | **미노출** |
| 재등록 진입점 | `initTemplate()` |

재등록 진입점이 전역에 고정 이름으로 노출되지 않으면 로케일 전환 후 이 확장의 액션이 전부 무반응이 됩니다 (오류·토스트 없음).
<!-- @generated:frontend-entry END -->

<!-- @intent START -->
`src/index.ts` 가 모듈 로드 시점에 `initTemplate()` 을 호출합니다. `initTemplate()` 은
`window.load` 이후(이미 로드됐으면 즉시) `G7Core.getActionDispatcher()` 를 **100ms 간격 최대
50회 재시도**로 기다렸다가 `handlerMap` 의 두 핸들러를 등록합니다. 끝내 디스패처가 없으면
오류 로그를 남기며, 그 경우 장바구니의 키 확보가 일어나지 않아 요청 버튼이 열리지 않습니다.

컴포넌트 등록은 템플릿이 직접 하지 않습니다 — 코어 `ComponentRegistry` 가 `components.json` 을
읽어 번들 전역 `RaonslabTravelLab` 의 export 를 찾습니다.

위 표의 "전역 객체 미노출" 은 모듈·플러그인식 고정 이름 진입점(`window.__[Name].init*`)이 없다는
뜻입니다. 템플릿은 코어가 부트스트랩 경로를 직접 알고 있어 그 진입점이 필요 없습니다. 다만
`initTemplate()` 은 로케일 전환 후 재등록을 위해 `handlerMap` 을 `window.G7TemplateHandlers` 에도
노출합니다.

핸들러를 추가할 때는 `src/handlers/index.ts` 의 `handlerMap` 에 넣으면 같은 재시도 루프로 등록되며,
`template:build --production` 후 `dist/` 를 함께 커밋합니다.
<!-- @intent END -->
