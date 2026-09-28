# GNUBOARD7 프로젝트 트러블슈팅

이 문서는 RAON Hub 운영에서 재발 가능성이 확인된 사례만 기록한다. 현재 기준은 main/runtime
`390cdc7a379e1f2b9c8e3991b241edcc59dbdd71`, `raonslab-product 0.4.0`이다.

## CASE 1 — Native Page 배포 뒤 legacy route 404

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-001` / 2026-09-28 KST |
| symptom | `module:update` 성공 및 active route 파일 존재 뒤에도 `/info/*`, `/policy/*`, locale 호환 경로 14개가 404였다. 두 번째 delivery smoke는 전체 `380 PASS / 73 FAIL`이었다. |
| wrong initial assumption | route/controller 코드 또는 설정 파일 자체가 잘못됐거나 `module:update` 내부 route rebuild만으로 새 provider route가 항상 반영된다고 보았다. |
| actual cause | update 프로세스가 파일 교체 전에 이전 `ProductServiceProvider` class를 이미 로드했다. `ModuleManager::reloadModule()`은 `module.php`만 다시 읽고, 같은 PHP 프로세스의 route rebuild는 이미 로드된 provider class를 새 파일로 재선언하지 못해 compatibility route 없는 cache를 만들었다. |
| evidence | `app/Extension/ModuleManager.php`, `app/Support/RouteCacheHelper.php`, `modules/_bundled/raonslab-product/src/Providers/ProductServiceProvider.php`, `modules/_bundled/raonslab-product/src/routes/compatibility.php`; 실패 직후 route 부재와 최종 fresh-process `route:list` 14개 및 실제 301을 대조했다. |
| resolution | update 종료 뒤 각각 새 PHP 프로세스로 `/usr/bin/php8.3 artisan route:clear`, `/usr/bin/php8.3 artisan route:cache`를 실행하고 `g7-product-fpm.service`만 graceful reload한다. full smoke 전 `route:list` 14개와 실제 legacy GET 1개의 same-origin exact 301을 fail-fast로 확인한다. |
| prevention rule | root web route를 추가·변경한 module delivery는 update 프로세스의 cache 결과를 신뢰하지 않는다. fresh route lifecycle, 격리 FPM reload, CLI+HTTP preflight가 모두 통과하기 전 browser suite를 시작하지 않는다. 다른 G7/AI/nginx/queue service는 재시작하지 않는다. |
| related regression test | `modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`; `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`; `modules/_bundled/raonslab-product/tests/Feature/ProductLayerContractTest.php`; `tests/Feature/Extension/ExtensionRouteCacheInvalidationTest.php`; `tests/Feature/Api/Admin/ModuleUpdateTest.php` |
| related commit/request_id | feature `b59b45f49c1e13f31ecab4229678c79bac2295cf`; final main `390cdc7a379e1f2b9c8e3991b241edcc59dbdd71`; `req_83dcac7eb8cd454db7146f270f0cb1bd` |

## CASE 2 — canonical 검증의 false failure

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-002` / 2026-09-28 KST |
| symptom | normal human SPA 31개 검사에서 `link[rel=canonical]`이 없어 모두 실패했다. |
| wrong initial assumption | native Page 또는 product layout overlay가 모든 human DOM에 canonical element를 삽입해야 하며, 부재는 SEO 회귀라고 판단했다. |
| actual cause | G7의 authoritative canonical surface는 `SeoMiddleware`가 bot 요청에 반환하는 server-rendered HTML이다. 일반 사용자 요청은 SPA를 받고 human DOM canonical은 공식 계약이 아니다. |
| evidence | `docs/backend/seo-system.md`, `app/Seo/SeoMiddleware.php`, `app/Seo/SeoRenderer.php`, `resources/views/seo.blade.php`, `templates/_bundled/sirsoft-basic/layouts/page/show.json`; 동일 `/page/privacy`에서 human SPA canonical 없음과 Googlebot HTML의 exact same-origin canonical을 확인했다. |
| resolution | responsive human DOM 검증과 SEO 검증을 분리했다. human context는 Page UI·presentation·overflow를 검사하고, 저호출 bot check는 7개 Page의 HTML 200, `X-SEO-Cache`, same-origin 및 exact canonical을 검사한다. product/core에 duplicate canonical을 추가하지 않는다. |
| prevention rule | SEO acceptance는 구현이 소유한 authoritative response surface에서 검증한다. bot/server 계약을 human SPA DOM에 투영하거나 false failure를 제품 변경으로 보정하지 않는다. |
| related regression test | `modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`; `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`; `modules/_bundled/sirsoft-page/tests/Feature/User/PublicPageControllerTest.php`; `tests/Feature/Seo/SeoMiddlewareTest.php`; `tests/Feature/Seo/SeoPageRenderingTest.php` |
| related commit/request_id | feature `b59b45f49c1e13f31ecab4229678c79bac2295cf`; final main `390cdc7a379e1f2b9c8e3991b241edcc59dbdd71`; `req_83dcac7eb8cd454db7146f270f0cb1bd` |

## CASE 3 — Native Page rollback과 직렬 retry

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-NATIVE-PAGE-003` / 2026-09-28 KST |
| symptom | 첫 delivery는 429, 다음 gate는 absolute `Location` 비교 실패, 두 번째 delivery는 legacy 404와 human canonical 실패를 냈다. 최종 delivery만 committed smoke `451 PASS / 0 FAIL`이었다. |
| wrong initial assumption | 각 smoke 실패를 곧바로 동일한 제품 결함으로 묶거나, 실패 뒤 재실행해 PASS를 만들 수 있다고 보았다. |
| actual cause | 첫 429는 viewport/page마다 31개 SPA context를 만든 smoke burst였다. Laravel의 absolute redirect는 정상이고 relative 문자열 비교가 테스트 결함이었다. 두 번째 legacy 404는 CASE 1의 실제 delivery lifecycle 결함이었지만 human canonical 부재는 CASE 2의 false assumption이었다. |
| evidence | context를 viewport당 하나로 줄인 `09719936`, absolute URL을 origin+pathname+query로 검증한 `571b934e`, route/SEO preflight를 추가한 `b59b45f4`; source rollback `6201e320`, `52677b3f`; 최종 main `390cdc7a`에서 route 14개와 smoke `451/0`. |
| resolution | acceptance 실패 즉시 source와 공식 module lifecycle을 0.3.1로 되돌리고 route cache/FPM 상태까지 baseline으로 복구했다. 결함 유형별로 smoke만 최소 수정한 뒤 독립 gate와 단일 직렬 delivery를 다시 수행했다. |
| prevention rule | HTTP status, content type, redirect origin/target, route activation, throttle을 분리 진단한다. smoke는 1회만 실행하고 실패를 숨기지 않는다. 제품/runtime 결함과 assertion·request-shape 결함을 구분한 뒤에도 acceptance가 실패하면 rollback한다. |
| related regression test | `modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`; `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`; `modules/_bundled/raonslab-product/tests/Unit/NativePageBootstrapperTest.php`; `modules/_bundled/raonslab-product/tests/Feature/NativePageBootstrapCommandTest.php`; `modules/_bundled/sirsoft-page/tests/Unit/Services/PageServiceTest.php`; `modules/_bundled/sirsoft-page/tests/Feature/Admin/PageControllerTest.php` |
| related commit/request_id | rollbacks `6201e320`, `52677b3f`; feature lineage `09719936` → `571b934e` → `b59b45f4`; final main `390cdc7a`; implementation `req_83dcac7eb8cd454db7146f270f0cb1bd`, independent gate `req_9460a4c28982425bb8ca2c5937179528` |

## CASE 4 — Provider 실행 slot 대기

| 필드 | 내용 |
|---|---|
| CASE ID / date | `G7-PROVIDER-001` / 2026-09-28 KST |
| symptom | Provider 요청이 즉시 실행되지 않고 `QUEUED`, `waiting_reason=execution_slot` 상태에 머물렀다. |
| wrong initial assumption | 대기를 G7 제품 코드 실패나 유실된 요청으로 보고 새 request를 중복 제출해야 한다고 판단했다. |
| actual cause | `PROVIDER_CAPACITY` 범주의 queue/slot 가용성 문제다. 제품 소스·runtime 결함이 아니며 request truth는 AgentOpt V2 Request/event 상태에 있다. |
| evidence | Production Request 기록에서 `req_9460a4c28982425bb8ca2c5937179528`은 sequence 2·301·425·463·586·610에서 `execution_slot` 대기 후 같은 request의 sequence 682에서 COMPLETED됐다. `req_65edcf3409dd460a94f739b76360def8`도 sequence 2 대기 후 sequence 22에서 COMPLETED됐다. 두 ID 모두 request row 1개와 기존 idempotency key 1개를 유지했다. |
| resolution | request ID와 event cursor를 유지해 대기·resume/follow-up 결과를 관찰한다. 새 request를 중복 제출하지 않으며 재전송이 필요해도 동일 작업에는 원래 idempotency key를 사용한다. |
| prevention rule | 코드 진단 전에 Request state, `waiting_reason`, provider availability를 확인한다. `execution_slot`은 `PROVIDER_CAPACITY`로 별도 분류하고 terminal evidence가 나오기 전 제품 실패로 기록하지 않는다. |
| related regression test | G7 제품 회귀 대상 아님. Adapter 경계는 `modules/_bundled/raonslab-ai-workspace/tests/Feature/AiAdapterContractTest.php`, 사용자 상태 표시는 `modules/_bundled/raonslab-ai-workspace/resources/js/presentation.test.ts`로 고정한다. |
| related commit/request_id | product commit 없음; Production evidence `req_9460a4c28982425bb8ca2c5937179528`, `req_65edcf3409dd460a94f739b76360def8` |

## 권위 경로

| 영역 | repository-relative authoritative paths |
|---|---|
| Module route lifecycle | `docs/extension/extension-update-system.md`; `docs/extension/module-routing.md`; `app/Extension/ModuleManager.php`; `app/Support/RouteCacheHelper.php`; `modules/_bundled/raonslab-product/src/Providers/ProductServiceProvider.php`; `modules/_bundled/raonslab-product/src/routes/compatibility.php` |
| Page/SEO | `modules/_bundled/sirsoft-page/AGENTS.md`; `modules/_bundled/sirsoft-page/docs/api/pages.md`; `modules/_bundled/sirsoft-page/src/Services/PageService.php`; `modules/_bundled/sirsoft-page/src/routes/api.php`; `docs/backend/seo-system.md`; `app/Seo/SeoMiddleware.php`; `app/Seo/SeoRenderer.php`; `resources/views/seo.blade.php`; `templates/_bundled/sirsoft-basic/layouts/page/show.json` |
| Frontend/layout | `docs/extension/layout-extensions.md`; `docs/frontend/responsive-layout.md`; `docs/frontend/layout-testing.md`; `modules/_bundled/raonslab-product/resources/extensions/native-page.json`; `modules/_bundled/raonslab-product/resources/css/main.css` |
| Validation | `docs/testing-guide.md`; `docs/testing/e2e-testing.md`; `modules/_bundled/raonslab-product/resources/js/infoPolicy.test.ts`; `modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`; `modules/_bundled/raonslab-product/tests/Feature/ProductLayerContractTest.php`; `modules/_bundled/sirsoft-page/tests/Unit/Services/PageServiceTest.php`; `modules/_bundled/sirsoft-page/tests/Feature/Admin/PageControllerTest.php`; `tests/Feature/Extension/ExtensionRouteCacheInvalidationTest.php`; `tests/Feature/Seo/SeoMiddlewareTest.php` |

## G7 AI 개발 도구

| 자산 | 실제 repository path | 상태 |
|---|---|---|
| Agent guides | `AGENTS.md`; `modules/_bundled/raonslab-product/AGENTS.md`; `modules/_bundled/sirsoft-page/AGENTS.md` | source available. 이 learning gate에서 읽어 적용했으며 별도 연결 개념은 없다. |
| G7 Skills 문서 | `docs/ai-tools/skills/`; 진입점 `docs/ai-tools/README.md` | source available. 현재 Provider의 설치 skill 목록에는 등록되지 않았고 이 요청에서 live skill로 호출하지 않았다. |
| G7 validation/test MCP | `docs/ai-tools/agents/src/mcp/g7-tools-server.ts`; validators `docs/ai-tools/agents/src/mcp/tools/` | source available. 현재 요청의 callable tool 목록에 연결되지 않았고 MCP로 호출하지 않았다. 검증은 직접 repository 명령으로 수행했다. |
| G7 DevTools MCP | `docs/ai-tools/agents/src/mcp/g7-devtools-server.ts`; 안내 `docs/ai-tools/devtools/README.md` | source available. repository에 연결 설정을 추가하지 않았고 이 요청에서 Connected 상태나 호출을 확인하지 않았다. |
| Browser DevTools runtime | `resources/js/core/devtools/`; `routes/devtools.php`; `public/build/core/devtools.min.js` | source/build asset available. production debug dump를 활성화하거나 수집하지 않았다. |
| G7 agent workflow | `docs/ai-tools/agents/src/coordinator/Coordinator.ts`; `docs/ai-tools/agents/src/mcp/index.ts` | source available. 이 요청의 orchestrator로 실행하거나 MCP endpoint를 게시하지 않았다. |

AgentOpt/Agent.Tools는 request truth, 실행 조율과 프로젝트 context를 담당한다. 위 G7 도구는 저장소 규칙에
맞춘 구현·검증 보조 수단이며 대체 orchestrator가 아니다. 파일 존재는 연결 증거가 아니므로 MCP는 실제
등록·health·호출 evidence가 있을 때만 connected로 기록한다.
