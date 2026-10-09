# API 레퍼런스 문서 목차

> **소유**: 모듈 `raonslab-travel_lab` · **생성**: `php artisan api:docgen` (실측 기반).
> 아래 표는 자동 생성됩니다. 각 문서를 열면 엔드포인트별 파라미터·응답·예시를 볼 수 있습니다.

## 검증 범위와 공통 계약

모든31라우트는 네이티브 `api:docgen`의 소스 인벤토리에서 생성했습니다. 전체 생성 당시 격리 환경에서11건을 실측했고20건은 자동 프로브가 생략했습니다. 실측에는 트랜잭션 후 rollback한 POST도 포함됩니다. 카탈로그12건은 별도 격리 SQLite 실제 커널 생성기로 성공 응답을 보완했습니다. 나머지 예시는 소스·테스트 기반 합성 계약이며 모든31건 실측 PASS라는 뜻이 아닙니다.

기본 URI는 `/api/modules/raonslab-travel_lab`. 공개 카탈로그/공지/FAQ 외에는 Sanctum Bearer 인증이 필요합니다. 성공은 ResponseHelper의 success/message/data 봉투이며, 코어401/422에는 success가 없을 수 있습니다. 타인 자원은404로 존재를 숨기고, 상태·정원·멱등 본문 충돌은409, 잘못된 입력은422, 사용자별 제한 초과는429입니다. support 채널 미준비/안전 설정 불일치는503입니다.

가격은 서버의 네이티브 커머스 계산 결과이며 시험 수락은 실제 예약 확정이 아닙니다. 기록·이미지·예시가 모두 합성임을 전제로 하며 실제 결제/예약/환불/메일/SMS를 연결하지 않습니다. [업무 상세 계약](workflow.md), [지원 계약](../support-api.md), [카탈로그 계약](../domain-api.md)을 함께 읽으십시오.

<!-- @generated:start:api-readme-index -->
- **문서 수**: 4 · **엔드포인트 수**: 31

| 문서 | 도메인 | 엔드포인트 |
| --- | --- | --- |
| [cart.md](cart.md) | `cart` | 4 |
| [catalog.md](catalog.md) | `catalog` | 12 |
| [inquiries.md](inquiries.md) | `inquiries` | 7 |
| [support.md](support.md) | `support` | 8 |

<!-- @generated:end -->
