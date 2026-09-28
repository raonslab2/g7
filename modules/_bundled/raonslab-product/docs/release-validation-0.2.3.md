# 0.2.3 후보 결합 검증 기록

이 문서는 `raonslab-product` 0.2.3 **후보 결합 준비** 기록이다. 전체 릴리스 승인이나 main 전달을
의미하지 않는다. AI Workspace 0.1.5는 독립 QA가 FAIL 상태이며, 이 후보에는 0.2.2 리허설에 포함된
AI Workspace 트리가 변경 없이 그대로 실려 있다.

## 입력과 부모 관계

- 결합 base(통합 리허설 후보): `ba5b87222e005ed560a42db62ba4048a0c352fa7`
  (상담 0.2.2 + AI Workspace 0.1.5 prep + 기존 visual 0.2.1)
- visual pass 2 구현: `a229dea0df0415023279d07cbde78525690e9d5f` (부모 `75096bc2`)
- cherry-pick 결과: `15477775` — 충돌 없음. 변경 3개 경로(`resources/css/main.css`,
  `resources/extensions/home-product.json`, `resources/js/homeLayout.test.ts`)와
  `75096bc2..ba5b8722` 변경 경로의 교집합 없음
- 최종 후보 SHA는 이 문서를 포함한 준비 커밋 후 보고한다

## 변경 경계

- `raonslab-product` 홈 사례·서비스·절차 layout, 관련 CSS, 홈 레이아웃 테스트
- `raonslab-product` 0.2.3 metadata(`module.json`·`package.json`·`package-lock.json`·`composer.json`·`composer.lock`),
  생성 산출물(`dist/`, `components.json`), CHANGELOG, 이 문서
- 상담 source·tests·admin layout·번역·config·migration, G7 core, `raonslab-ai-workspace` 변경 없음

## 검증

| 항목 | 상태 | 근거 |
|---|---|---|
| Home layout focused Vitest | PASS (신규 실행) | `homeLayout.test.ts`: 28/28 |
| Product contract PHP | PASS (신규 실행) | `ProductLayerContractTest.php`: 3 tests / 10 assertions |
| Production asset build | PASS (신규 실행) | 0.2.3 결합 소스에서 공식 `module:build raonslab-product --production` 1회 |
| Consultation focused PHP | REUSED | 0.2.2의 46 tests / 323 assertions — 아래 트리 동일성 |
| Consultation focused frontend | REUSED | 0.2.2의 `consultation.test.ts` 35/35 — 아래 트리 동일성 |
| Visual pass 2 단일 browser fixture | REUSED | `a229dea0` 기준 정적 fixture(360/390/412/1280, ko/en, reduced-motion): overflow 0, 44px 미만 0, 최소 대비 6.33:1, 접수 닫힘 PII 입력 0. `main.css`·`home-product.json`·번역·홈 JS blob이 `a229dea0`와 동일(fixture는 같은 소스의 `/tmp` vite 빌드 사용, G7 셸 없는 정적 재현) |
| Broad/full/runtime browser | SKIPPED | 후보 결합 범위 및 테스트 최적화 지시 |
| AI Workspace 0.1.5 독립 QA | FAIL (미해결) | 재시도 멱등성 키 재생성, 이벤트마다 상세 전체 재렌더, upstream 필드 무필터 전달 |

### 재사용 근거: 트리 동일성

`ba5b8722`와 이 후보에서 아래 경로의 git tree/blob id가 동일하다.

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
main push, `module:update`, runtime 배포는 독립 QA와 전달 승인 전 수행하지 않는다.
