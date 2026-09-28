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

final main SHA, runtime module/asset identity, HTTP fail-closed 상태와 rollback archive는 배포 후 추가한다.
