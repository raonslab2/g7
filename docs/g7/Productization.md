# GNUBOARD7 제품화

## 제품 표면

공식 G7 `7.0.11` 위에 RAON Hub 제품 계층을 확장으로 올렸다. upstream core는 수정하지 않았다.

| 영역 | 구현 | 상태 |
|---|---|---|
| Landing/Home | `raonslab-product` home layout extension | 완료 |
| Login/Register | 공식 auth layout 재사용, 제품 dark theme 적용 | 완료 |
| Profile | 공식 `/mypage/profile`, `/api/me` 계약 재사용 | 완료 |
| Community | `community` board | 완료 |
| Notice | `notice` board | 완료 |
| Q&A | `questions` board | 완료 |
| Notification | 공식 notification UI/API 재사용 | 완료 |
| Search | 공식 통합 검색과 board index 재사용 | 완료 |
| Admin/RBAC | 공식 admin template와 permission middleware 재사용 | 완료 |
| File/Attachment | board attachment upload/download 재사용 | 완료 |
| AI workspace | `raonslab-ai-workspace` 사용자 module | 완료 |

제품 화면에는 DEMO, MOCK, SANDBOX, TEST 표기를 넣지 않았다. 공식 ecommerce module은 user template 의존성 때문에 lifecycle이 비활성화를 거부했으므로 강제 제거하지 않았다. 대신 제품 extension이 일반 화면의 shop/cart 진입점만 숨기며 upstream source와 데이터는 유지한다.

## 확장 구현

### `raonslab-product 0.1.2`

- 공식 home extension point로 landing content 교체
- charcoal/graphite/deep navy 기반 dark-first visual system
- 로그인 상태에 따라 community, profile, AI workspace 진입 제공
- 760px 이하 single-column 전환과 full-width action
- reduced-motion 대응
- 제품 범위 밖 ecommerce navigation만 presentation layer에서 숨김

### `raonslab-ai-workspace 0.1.2`

- 공식 module lifecycle, route, JSON layout, permission, migration 사용
- loading/empty/success/validation/permission/server/network 상태 제공
- 실제 persisted event replay와 reconnect cursor 사용
- fake progress와 ETA 없음
- raw provider payload/shell 대신 사용자 의미 중심 event label 제공
- terminal result와 same-request follow-up 제공

## 반응형·접근성

실제 Chromium에서 360px, 390px, 412px를 검사했고 모든 화면에서 `scrollWidth <= clientWidth`였다. desktop home 1440px와 admin 768px도 별도로 캡처했다. form label, keyboard focus, semantic heading, status text를 유지하며 `prefers-reduced-motion`을 존중한다.

## 상태 설계

| 상태 | 표현 |
|---|---|
| loading | skeleton/명시적 불러오는 중 문구 |
| success | 실제 상태 badge, result panel |
| empty | 다음 행동을 설명하는 empty state |
| validation | API message와 form 재활성화 |
| permission denied | 401/403을 사용자 메시지로 변환 |
| server/network error | notice panel과 재시도 가능한 UI |
| long-run reconnect | persisted cursor 이후 SSE 자동 재연결 |

## 배포와 접근

- 내부 URL: `http://127.0.0.1:18770`
- 승인된 reverse SSH 경로: `http://203.245.29.156:58770`
- 임시 HTTPS 경로는 localhost.run free tunnel을 사용할 수 있으나 재연결 시 hostname이 바뀐다.

고정 `58770`은 TLS가 없으므로 정식 공개 운영 전 TLS reverse proxy와 secure cookie/origin 검증을 적용해야 한다. 현재 외부 경로는 사용자 요청에 따른 화면 검수용이다.

## Core patch inventory

`7.0.11` commit과 제품 HEAD 사이에서 `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `tests`, root dependency manifest의 semantic diff는 0이다. 변경은 `modules/_bundled/raonslab-*`, `deploy`, `scripts`, `.env.product.example`, `docs/g7`에만 있다.
