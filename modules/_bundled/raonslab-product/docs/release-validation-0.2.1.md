# 0.2.1 릴리스 검증 기록

## 입력과 경계

- baseline: `origin/main` `cb515dd200a23d805708ed7eea87331491f4392f`
- visual implementation: Request `req_e23e7b3d59804deca38c523f17e90823`, commit `0c2744c876b7ddf2e60f1594c0edbbef2f8131cf` (`496d2410` 포함)
- independent QA: Request `req_b7230833280548bf87f8962eab4205aa`
- integration owner: Request `req_7130b85d2da44007867b9b9322c97140`

변경 경계는 `modules/_bundled/raonslab-product`의 홈 visual JSON/CSS/lang/frontend tests/dist와
0.2.1 metadata/changelog/release evidence뿐이다. 상담 backend/admin, SEO listener, G7 core, AI workspace는 변경하지 않는다.

## 재사용 evidence

| 항목 | 상태 | 근거 |
|---|---|---|
| Visual candidate Vitest | PASS | 독립 QA exact `0c2744c8`, 48/48 |
| Visual candidate Chromium | PASS | 독립 QA single critical suite 176 PASS, 360/390/412/1280 |
| 누적 config 429 | OBSERVED | 환경 rate limit 관측; fail-closed와 PII input 0 PASS, limit 완화 금지 |

## 통합·배포 evidence

| 항목 | 상태 | 근거 |
|---|---|---|
| Integration focused Vitest | PASS | `homeLayout.test.ts` + `homePage.test.ts`, 24/24 |
| Production asset build | PASS | 0.2.1 integrated source에서 공식 `module:build --production` 1회 |
| Concise candidate Chromium | PASS | candidate dist/layout/lang 주입, 390/1280 2/2 |
| Candidate visual contract | PASS | 4단계 가로 workflow, 상태 3레인·되돌림 loop, case evidence rail, 반응형 process timeline |
| Candidate safety/navigation | PASS | overflow 0, CTA focus, fail-closed UI, PII input 0, 예상 밖 4xx/5xx 0 |
| 상담 backend/admin 회귀 | REUSED | 0.2.0의 20 tests / 119 assertions; backend/admin source 무변경 |
| 기존 G7/AI 회귀 | REUSED | 0.2.0 release evidence; core/AI workspace source 무변경 |

## Runtime delivery

- integration/runtime source SHA: `bf58fa4e220d243865f188ed83644fecbde01ebe`
- runtime: active/bundled `raonslab-product` 모두 `0.2.1`
- manifest SHA-256: `ae840112d4e81cbee29086fd05b05b60e690cf51952b7a91df7475ab57dfb998`
- home layout SHA-256: `c56fb272955c1407b3eb3bfefe3cd3fdac65e884a2120d4379a59e354548250c`
- served/active CSS SHA-256: `c71ce24e6a11d8842d2de2dced358cbb35dd2864b38802c57cec99dde7dcf476`
- served/active JS SHA-256: `f0b3b72bab7f7091a7cb782b415e5cb129d05099fe32c365b830e8709af51798`
- migration: 기존 consultation migration production batch 7/Ran 유지, 새 migration 없음
- URL: `http://127.0.0.1:18770/`, `http://203.245.29.156:58770/`
- runtime browser: visual 8 PASS(local/external × 360/390/412/1280), 기존 public route/auth boundary 14 PASS, 총 22 PASS / 0 FAIL
- bot render: local/external title, description, product CSS PASS
- consultation: local/external config 200 + `intake_enabled=false`, synthetic empty POST 503, PII input 0
- auth boundary: `/ai` → `/login`, `/admin/consultations` → `/admin/login`, admin API guest 401
- services: product FPM/web/queue active; queue PID 유지. FPM/web만 graceful reload
- legacy 8600: closed 유지
- rollback: `/var/backups/g7-product/g7-product-20260928T070146Z.tar.gz`, SHA-256 `91411aa3f2da3efd6f8cfc2da8ba5e94a35bba7eb9681ebded77007bc6f71a5e`, source `cb515dd200a23d805708ed7eea87331491f4392f`

## SKIPPED / UNVERIFIED

- broad/full browser와 backend regression은 source 경계가 변하지 않아 중복 실행하지 않고 독립 QA 및 0.2.0 evidence를 재사용했다(`SKIPPED` by optimization).
- 실제 public 상담 접수와 실제 PII 저장은 HTTP review 환경에서 수행하지 않았다(`SKIPPED`).
- 실제 상담 데이터의 process restart persistence는 이번 visual-only release에서 재검증하지 않았다(`UNVERIFIED`, 0.2.0 evidence 유지).
- 독립 QA의 누적 요청 중 raw config 429 관측은 그대로 기록하며 rate limit은 변경하지 않았다.
