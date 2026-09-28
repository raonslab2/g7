# 0.2.3 후보 결합 검증 기록

이 문서는 `raonslab-product` 0.2.3과 fixed `raonslab-ai-workspace` 0.1.5의 **최종 RC 준비** 기록이다.
전체 릴리스 승인이나 main 전달을 의미하지 않는다. 이전 AI QA findings의 보완 patch는 포함됐지만
독립 fixed-candidate QA와 최종 전달 승인은 아직 대기 중이다.

## 입력과 부모 관계

- exact RC base: `94ecea87ecdfba8708b75aadf68b5ea50667debc`
  (상담 0.2.2 + fixed AI Workspace 0.1.5 + 기존 visual 0.2.1)
- 공통 product 조상: `ba5b87222e005ed560a42db62ba4048a0c352fa7`
- AI fixed provider patch: `f122f96234f4ea46beb277bf75df5a4f58c80a4c`(부모 `895c2c2`),
  RC base의 cherry-pick `a5912c26`
- visual pass 2 구현: `a229dea0df0415023279d07cbde78525690e9d5f` (부모 `75096bc2`)
- product candidate commits: `154777753035b8dadeb403cfdc12184d487371aa` →
  `cacc8ee2ec1c9ad4538361ff663a5de8c16717ba`
- RC cherry-pick 결과: `63324ee2` → `ba7ca6a2` — 충돌 없음. product 변경 경로와
  `ba5b8722..94ecea87`의 AI 변경 경로 교집합 없음
- 최종 후보 SHA는 이 문서를 포함한 준비 커밋 후 보고한다

## 변경 경계

- `raonslab-product` 홈 사례·서비스·절차 layout, 관련 CSS, 홈 레이아웃 테스트
- `raonslab-product` 0.2.3 metadata(`module.json`·`package.json`·`package-lock.json`·`composer.json`·`composer.lock`),
  생성 산출물(`dist/`, `components.json`), CHANGELOG, 이 문서
- 상담 source·tests·admin layout·번역·config·migration, G7 core 변경 없음
- `raonslab-ai-workspace`는 exact RC base `94ecea87`의 fixed 0.1.5 tree를 그대로 보존

## 검증

| 항목 | 상태 | 근거 |
|---|---|---|
| Home layout focused Vitest | REUSED PASS | exact `cacc8ee2` product source, `homeLayout.test.ts` 28/28 |
| Product contract PHP | REUSED PASS | exact `cacc8ee2` product source, 3 tests / 10 assertions |
| Product production asset build | REUSED PASS | product source/dist blob이 exact `cacc8ee2`; 공식 production build 1회 evidence 재사용 |
| Consultation focused PHP | REUSED PASS | 0.2.2의 46 tests / 323 assertions — 아래 트리 동일성 |
| Consultation focused frontend | REUSED PASS | 0.2.2의 `consultation.test.ts` 35/35 — 아래 트리 동일성 |
| Visual pass 2 단일 browser fixture | REUSED PASS | `a229dea0` 기준 정적 fixture(360/390/412/1280, ko/en, reduced-motion): overflow 0, 44px 미만 0, 최소 대비 6.33:1, 접수 닫힘 PII 입력 0. 해당 source blob은 `cacc8ee2`와 동일 |
| AI Workspace fixed Vitest | REUSED PASS | exact `94ecea87`/`f122f962` source tree, 17 tests |
| AI Workspace fixed PHP | REUSED PASS | exact `94ecea87`/`f122f962` source tree, 10 tests |
| AI production assets | REUSED PASS | AI source/dist blob이 exact `94ecea87`; 추가 build 조건 없음 |
| RC tree/blob identity | PASS | product는 evidence 문서 외 exact `cacc8ee2`, AI는 exact `94ecea87`, 상담 경계는 exact `ba5b8722` |
| Versions/locks/components/JSON | PASS | product 0.2.3, AI 0.1.5; 양쪽 manifest·package locks·components·JSON 및 Composer validate |
| Static diff gate | PASS | 두 commit delta inventory와 `git diff --check` |
| Broad/full/browser/runtime | SKIPPED | 최종 RC 준비 범위 및 테스트 최적화 지시 |
| Independent fixed-candidate/final RC QA | PENDING | 승인 전 main push와 runtime deploy 금지 |

### 재사용 근거: 트리 동일성

product는 evidence 문서 갱신 전 exact `cacc8ee2` 전체 tree였고, 문서 외 source/assets는 계속 동일하다.
상담 경계는 `ba5b8722`, AI 경계는 `94ecea87`과 아래 tree/blob id가 동일하다.

| 경로(`modules/_bundled/raonslab-product/`) | id |
|---|---|
| `src` | `2c70d9bb4d4d` |
| `tests/Feature` | `01f8e864e9f7` |
| `tests/Unit` | `9ad53f9a4d7d` |
| `tests/ModuleTestCase.php` | `fe79c00fab86` |
| `tests/scenarios` | `152d370aea5a` |
| `resources/js/consultation.ts` | `c77d8745b90f` |
| `resources/js/consultation.test.ts` | `795fe1a68138` |
| `resources/js/consultationForm.ts` | `08bf85697bfc` |
| `resources/layouts/admin` | `d4244b48dc75` |
| `config` | `8330e671eeac` |
| `database` | `ad60ba47d01f` |
| `module.php` | `2dc0045e57df` |
| `resources/routes` | `0e2380addb0a` |
| `resources/lang` | `99cf262ec427` |

| 경로(`modules/_bundled/raonslab-ai-workspace/`) | id |
|---|---|
| `resources` | `0453a3da622a` |
| `src` | `9a413de50d38` |
| `tests` | `3f0336543e3c` |

### AI 0.1.5 산출물 해시(SHA-256)

- module manifest: `770a37f4639d68dee1b05efabd77aa9834c8d52efa20a86bbbb9cf19f424f42f`
- components: `c0d62c2fc429009774ac01a2e86d7b4d1c0ad216ee709c2c376fd4ffbd099f27`
- committed JS: `bd0cb991d3ca2133b4f31d4de89caa3064825d26dae4c39c7967d4cbea80cc16`
- committed CSS: `470d37a57294ffe8f221a93f76ea8a96d559bf768c7f3992ca40a70b7ee73425`

### 산출물 해시(SHA-256)

- module manifest: `5f8d5cee0637d1322bfcda9c4ec3cf6b53def97911c5b6779bfaa9470485f2d5`
- generated components: `2f4f06b7f1d1f9d053ba369aefe1c5d28a8625a9e5e276bfedf4708b38d3bb6c`
- generated JS: `46e9f99c16d18f53748ee83a87e95ada73934cf69b08b7b4b3cdc6bf6721529f` (0.2.2와 동일 — TS 변경 없음)
- generated CSS: `eb4759e9b23675b34aab3b2256c878e6d73dc79647b3892c33aa79b41de9f0ef` (0.2.2: `c71ce24e…`)
- `composer.lock` content-hash: `35e4e9d391e040240a0ea19d84c7ab62`

## 공개 활성화 게이트

상담 접수는 0.2.2와 같은 판정으로 fail-closed다. 현재 HTTP 검수 환경에서는 `intake_enabled=false`가 정답이며,
운영 도메인, TLS 종단과 신뢰 프록시·실제 client IP 구성, `APP_URL` HTTPS, 개인정보 문안/버전,
개인정보처리방침 HTTPS URL, 보관·파기 정책, 개인정보 담당 연락처, 알림 메일·mail transport,
스팸 방지와 메일 재시도 경로가 승인·검증되기 전에는 공개 접수를 활성화하지 않는다.
main push, `module:update`, runtime 배포는 독립 fixed-candidate/final RC QA와 별도 전달 승인 전 수행하지 않는다.
