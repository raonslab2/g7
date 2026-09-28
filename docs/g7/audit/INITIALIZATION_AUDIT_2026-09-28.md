# GNUBOARD7 초기화 감사 — 공개 정보·정책 표면 (2026-09-28)

| 항목 | 값 |
|---|---|
| 기준 source main / runtime checkout | `390cdc7a379e1f2b9c8e3991b241edcc59dbdd71` (`/home/mrdev/git/g7`, clean) |
| upstream stable | GNUBOARD7 7.0.11 (`f00b8d04…`) |
| 활성 확장(runtime) | modules: raonslab-product 0.4.0, raonslab-ai-workspace 0.1.4, sirsoft-board 1.1.2, sirsoft-ecommerce 1.2.1, sirsoft-page 1.1.2 · templates: sirsoft-basic 1.1.4, sirsoft-admin_basic 1.0.9 · plugins 활성: sirsoft-ckeditor5 1.0.3, sirsoft-daum_postcode 1.0.3 (결제·본인인증·GDPR·마케팅 플러그인 9종 미설치) |
| 이 문서와 함께 바뀐 source | `raonslab-product 0.4.1` (request `req_04b635bdc600403cbf7a3795159ce168`) — **미배포** |
| 근거 요청 | UX 감사(이 요청), 기술 감사 `req_3f95be490cb44061be39e563598fe52d`, 콘텐츠 감사 `req_9160cea72adc49aabbcbcc3f5edab73a` |

분류 키: `PASS` 확인됨 · `EXPECTED_FAIL_PREDEPLOY` 배포 전 구 런타임에서 실패가 정상인 검사 · `P0` 공개 신뢰·법적 위험 즉시 조치 · `P1` 이번 릴리스 안에서 조치 · `P2` 후속 · `EXPECTED` 의도된 상태 · `NA` 해당 없음 · `UNVERIFIED` 증거 부족.
층위 키: **SOURCE MAIN**(main 커밋의 파일) · **RUNTIME**(실행 중 코드·설정·빌드 산출물) · **DB**(운영 DB 행) · **ADMIN CONFIG**(`storage/app/settings/*.json`·관리자 화면 값) · **PUBLIC OUTPUT**(브라우저·봇이 받는 응답).

> DB 층위 수치는 기술 감사의 읽기 전용 조회 결과를 인용했다. 이 요청은 운영 DB에 쓰지 않았고, Page 행은 공개 API로만 재확인했다.

## 1. 증거 수집 방법

| 표면 | 방법 | 주의 |
|---|---|---|
| human SPA | Playwright + 명시한 일반 Chrome UA, 360/390/412/1280 | Playwright 기본 UA(`HeadlessChrome`)는 `SeoMiddleware`가 봇으로 보고 서버 렌더 HTML을 준다 — 이 차이를 모르면 footer·메뉴를 오진한다(Troubleshooting CASE 8) |
| bot SEO | `Googlebot/2.1` / `HeadlessChrome` UA로 같은 URL 요청 | 템플릿 `seo-config.json` 렌더 모드가 header/footer를 고정 생성 |
| Page 데이터 | `/api/modules/sirsoft-page/pages/{slug}` 공개 API, 기술 감사의 DB 조회 | 쓰기 없음 |
| 설정 | `module/template/plugin:list`, `config()` 읽기, settings JSON의 키 존재 여부만(비밀값 미출력) | — |
| 0.4.1 UI 사전 검증 | live 런타임 응답에 0.4.1 layout JSON·module 번들 조각·번역을 **클라이언트 쪽에서만** 겹쳐 렌더 | 서버·DB·빌드 게시본 무변경. 배포 후 동일 smoke 재실행 필요 |

## 2. 공개 문서 인벤토리 (11 slug)

| slug | 그룹(0.4.1) | PUBLIC OUTPUT 제목(ko) | DB version / 출처 | published_at | 판정 |
|---|---|---|---|---|---|
| about | 정보 1 | RAON Agent Factory 소개 | **v2**(2026-09-28 재배포, actor 1), v1 이력 유지 | 00:59:16 | **PASS** — 샘플 교체 완료(이전 P0) |
| service | 정보 2 | 서비스 소개 | v1, RAON native | 18:44:35 | PASS(본문) / P2 H2·H3 중복 제목 |
| cases | 정보 3 | 구현 사례 | v1, RAON native | 18:44:35 | PASS |
| technology | 정보 4 | 기술·검증 원칙 | v1, RAON native | 18:44:35 | PASS |
| faq | 정보 5 | 자주 묻는 질문 | **v2**(actor 1), v1 이력 유지 | 00:59:16 | **PASS** — 미검증 운영 약속 제거(이전 P0) |
| contact | 정보 6 | 문의·상담 안내 | **v2**(actor 1), v1 이력 유지 | 00:59:16 | **PASS** — 자리표시자 제거(이전 P0) |
| privacy | 정책 1 | 개인정보 처리 안내 | v4(= v4 스냅샷), 샘플 → RAON | 18:47:32 | PASS |
| terms | 정책 2 | 서비스·커뮤니티 이용 원칙 | v2, 샘플 → RAON | 18:44:35 | PASS |
| ai-workspace-policy | 정책 3 | AI 작업공간 접근·데이터·실행 권한 정책 | v1, RAON native | 18:44:35 | PASS |
| open-source | 정책 4 | 오픈소스·라이선스 고지 | v1, RAON native | 18:44:35 | PASS |
| refund | 정책 5 | 결제·취소·환불 안내 | **v2**(actor 1), v1 이력 유지 | 00:59:16 | **PASS** — 쇼핑 정책·자리표시자 제거(이전 P0) |

- 11개 모두 공개 API 200, 발행 상태. DB에 다른 Page 행 없음(기술 감사).
- 샘플 4종의 v1 의미 지문(기술 감사) = `PageSeeder` 원문 재계산값: about `a422a20f…8338`, faq `7d1af3c3…979c`, contact `789463ce…eadd`, refund `473df9e9…e5fc` — 정확히 일치(PHPUnit으로 고정).

## 3. 층위별 사실 행렬

| # | 사실 | SOURCE MAIN | RUNTIME | DB | ADMIN CONFIG | PUBLIC OUTPUT | 판정 |
|---|---|---|---|---|---|---|---|
| F1 | 샘플 Page 4종 원문 | `PageSeeder.php` | — | v1·published | — | 4 URL 200, 자리표시자·G7 문구 | P0 |
| F2 | 상위 메뉴 정보/정책 | 7 slug(0.4.0) | 0.4.0 활성 | — | — | 드롭다운 3+4 항목 | P1 → 0.4.1에서 6+5 |
| F3 | product footer `linkGroups` | `product-nav.json` 주입 | 병합 layout에 포함 | — | — | human SPA: RAON 3그룹 렌더 **PASS** / bot: 템플릿 고정 footer | 아래 §4 |
| F4 | 모바일 드로어 정보·정책 | 템플릿 `_user_base.json` 고정 6링크(id 없음) | 동일 | — | — | 회사소개·이용약관 등 G7 라벨, 이용약관 → `/page/terms`(제목 "서비스·커뮤니티 이용 원칙") | P1 · 템플릿 확장 지점 필요 |
| F5 | 문서 메뉴 레이아웃 | float + sticky, 부모 `overflow:hidden` | 동일 | — | — | 1280: 스크롤 후 top 음수(sticky 무효), 첫 섹션 뒤 본문 전체 폭 | P1 → 0.4.1 수정 |
| F6 | 모바일 문서 메뉴 | 7개 칩 목록 정적 노출 | 동일 | — | — | 360/390: 본문 전 353px 링크 벽 | P1 → 0.4.1 수정 |
| F7 | 목록 이중 표식 | `.ck-content ul{list-style:disc;padding-left:2em}` + `.rh-doc-list-item::before` | 동일 | — | — | "• ■" 이중 표식 | P1 → 0.4.1 수정 |
| F8 | 발행일 줄 | 템플릿 `page/show.json` | — | — | — | 모든 문서 "발행일: 2026-09-28" | P2 → 0.4.1 숨김 |
| F9 | 통화 선택기 | 이커머스 `header-currency-selector-user.json`(priority 320) | ecommerce 1.2.1 활성 | 상품 0건 | 결제 플러그인 미설치 | 헤더·드로어 "KRW" | P1 → 0.4.1 숨김(확장 교체) |
| F10 | 쇼핑 진입점 | 템플릿 Header `nav-shop`, 드로어 쇼핑 섹션(id 없음) | product JS가 데스크톱 탭·장바구니 숨김 | 상품 0건 | — | 데스크톱 숨김, 모바일 드로어 "쇼핑" 노출, `/shop/products` 200 | P1(드로어) · 템플릿 확장 지점 필요 |
| F11 | "새 게시판" | — | board-menu API | 활성 게시판 `new-board` | 게시판 설정 | 헤더·드로어 노출 | P1 · ADMIN CONFIG |
| F12 | Powered by 그누보드7 | product `index.ts`에 `#footer p` 숨김 코드 존재(런타임에서는 표기가 그대로 노출돼 무동작 확인) | — | — | — | 표기 노출 | EXPECTED(유지 결정) → 0.4.1 숨김 코드 제거 |
| F13 | 상담 접수 | fail-closed 기본값 | `intake_enabled=false` | legacy 0행(이전 감사) | 승인 설정 없음 | 폼 비노출 | EXPECTED |
| F14 | 메일 발송 | 코어 mail provider | `mail.default=smtp` | — | `mail.json` host·username 비어 있음(값 미출력) | FAQ가 인증·재설정 메일 발송 약속 | UNVERIFIED(발송) / P0(문구) |
| F15 | 가입 인증 | `core.auth.signup_*` 정책 | identity provider `g7:core.mail` | 정책 활성 여부 미조회 | `identity.json` | — | UNVERIFIED |
| F16 | sitemap | Page·board·shop contributor | index `http://localhost/sitemap-1.xml` | — | APP_URL/host | `127.0.0.1:18770`과 `localhost` 혼재, about/faq/contact/refund 중복, `/shop/products` 포함 | P1(RUNTIME/ADMIN CONFIG) |
| F17 | vite `emptyOutDir` | `raonslab-product` true(규정 위반) | — | — | — | — | P2 → 0.4.1 false |
| F18 | 활성 확장 소스맵 | — | `modules/raonslab-ai-workspace/dist/js/module.iife.js.map` 존재 | — | — | 서빙 경로 노출 가능 | P2(범위 밖 모듈, `NoSourcemapArtifactsTest` 실패) |
| F19 | 확장 개발자 문서 구조 | `raonslab-product`에 `ext:docgen` 필수 문서 6종·섹션·블록 없음 | — | — | — | — | P2(기존 공백) |
| F20 | 검색봇 header/footer | 템플릿 `seo-config.json` `header_nav`(쇼핑·게시판)·`footer_nav`(G7 기본 6링크) | `SeoConfigMerger` 템플릿 최우선 | — | — | 봇 HTML에 G7 기본 분류, 봇 HTML에서 드롭다운 패널 `hidden` 미직렬화로 둘 다 펼친 상태 | P1 · 템플릿 확장 지점 필요 |

## 4. footer `linkGroups` "실패" 진단

| 단계 | 결과 |
|---|---|
| 보고된 증상 | 콘텐츠 감사가 `/`, `/page/service`, `/board/questions`(390·1280)에서 G7 기본 footer를 보고 fiber에서 `linkGroups`를 찾지 못함 |
| 재현 조건 | Playwright 기본 UA = `…HeadlessChrome/…` |
| 원인 | `SeoMiddleware` 봇 판정 → 서버 렌더 HTML. footer는 `templates/sirsoft-basic/seo-config.json`의 `footer_nav` 고정 필드, React 없음 |
| human 확인 | 일반 Chrome UA에서 footer = 커뮤니티/정보/정책 RAON 그룹(0.4.0 3+4 링크) — 주입 정상 **PASS** |
| 모듈로 봇 footer 변경 가능? | 불가. `SeoConfigMerger` 병합 순서 모듈 → 플러그인 → 템플릿(최종 우선)이라 모듈 `seo-config.json`이 `footer_nav`를 덮지 못한다 |
| 조치 | human footer는 0.4.1 분류(6+5)로 생성. smoke가 문구+목적지(렌더된 `linkGroups` prop)를 단언. 봇 footer는 §7 제안 |

## 5. 0.4.1 source 변경 요약 (이 요청 — 1차 배포 실패·롤백, 수정본 `888e11c2` 배포 완료)

| 영역 | 변경 | 파일 |
|---|---|---|
| 분류 단일 출처 | 정보 6·정책 5, 라벨·설명 키 | `modules/_bundled/raonslab-product/resources/taxonomy/info-policy.json` |
| 생성·드리프트 | extension JSON 분류 종속부 생성, `--check` | `scripts/taxonomy.mjs`, `package.json`(`taxonomy:sync/check`) |
| 상위 메뉴·footer | 드롭다운 6+5, footer 정보/정책 그룹 | `resources/extensions/product-nav.json`(생성) |
| 문서 화면 | 11 slug 공통 breadcrumb(홈/분류/문서), 모바일 disclosure(본문 위), 데스크톱 우측 메뉴(append_child, DOM 본문 뒤) | `resources/extensions/native-page.json`(생성) |
| 동작 | 분류 JSON에서 현재 그룹 판정, disclosure 토글·Escape 포커스 복귀·경로 변경 시 닫힘 | `resources/js/productNav.ts` |
| 표현 | grid 2열 + sticky, overflow 제거, 목록 표식 정리, 발행일 숨김, 상태 행 격자 | `resources/css/main.css` |
| 커머스 억제 | 통화 선택기 앵커 교체 injection을 유일한 `_user_base` overlay에 포함(overlay priority 400 > 이커머스 320) | `resources/extensions/product-nav.json`(생성기 `scripts/taxonomy.mjs`) |
| attribution | Powered by 숨김 코드 제거 | `resources/js/index.ts` |
| 샘플 Page 교체 | 운영자 전용 명령·서비스·충돌 예외·actor trait | `src/Services/InfoPageRemediator.php`, `src/Console/Commands/RemediateInfoPagesCommand.php`, `src/Console/Concerns/ActsAsPageActor.php`, `src/Exceptions/InfoPageRemediationConflict.php` |
| 빌드 규정 | `emptyOutDir: false` | `vite.config.ts` |

## 6. 검증 결과

| 검증 | 범위 | 결과 |
|---|---|---|
| vitest | 모듈 전체 5파일(분류 드리프트·IA 순서·footer/드롭다운/문서 메뉴 일치·ko/en 키·overlay 계약·CSS 계약·통화 억제·disclosure 키보드) | 74/74 PASS. 분류 순서를 바꾸고 재생성하지 않으면 2건 FAIL 확인(드리프트 검출) |
| phpunit — worktree 고정 실행(현 HEAD 후보 코드) | `InfoPageRemediationCommandTest`의 `aa09b6d3` 신규 2건(`apply_requires_the_approved_whole_file_sha256`, `pack_envelope_is_validated_before_any_page_is_read`)만. 명령·환경은 §6.1 | **2 tests / 15 assertions PASS**(9 + 6), 474 s, exit 0 |
| phpunit — 이전 실행(worktree 고정 아님) | ProductLayerContract, NativePageBootstrap command·unit, InfoPageRemediationCommand 나머지 8건 | `aa09b6d3` 이후 24 tests / 253 assertions PASS. 이 실행은 `base_path()`·`App\`·`Tests\`가 운영 checkout(동일 commit `390cdc7a` 코어)에서, 모듈 클래스는 worktree에서 로드됐다 → **worktree 고정 재실행 안 함**(변경 없는 테스트, 분류: PASS-UNPINNED) |
| phpunit 저장소 계약 | ChangelogParser, SeoNodeKeyParity | PASS |
| phpunit 저장소 계약 | ViteOutDirContract, NoSourcemapArtifacts | 이 실행에서 `base_path()`가 운영 checkout으로 해석되어 운영 파일을 검사함 → main의 `emptyOutDir: true`(0.4.1이 수정)와 ai-workspace 활성 `.map`(F18) 검출. worktree의 추적 vite config는 모두 `false` 확인 |
| browser smoke(새 버전) × live 0.4.0 | 11 slug × 4 viewport + en 4 + bot SEO + legacy 301 | 476 PASS / 356 FAIL — `EXPECTED_FAIL_PREDEPLOY`(결함 검출력 확인, release gate 아님, 재실행 안 함) |
| browser smoke × 0.4.1 클라이언트 오버레이 | 동일, content gate off | 1208 PASS / 0 FAIL |
| 〃 content gate on | 동일 | 1240 PASS / 16 FAIL = 샘플 4 slug × 4 viewport — `EXPECTED_FAIL_PREDEPLOY`(DB 교체 전) |
| content pack 오프라인 검증 | `/tmp/rh-pack/pack.canonical.json` → `NativePageContentPack`(SHA-256·envelope·base_commit) + pages 검증 | PASS, SHA 한 글자 변경은 거부 |
| 1차 배포 gate 5 (`c64085b2`, content gate off) | runtime 0.4.1 | **1116 PASS / 140 FAIL** — 같은 `_user_base` overlay 파일 2개가 설치 시 덮어써짐(Troubleshooting CASE 9). 즉시 롤백 |
| 롤백 검증 (`4d9856dc` = `390cdc7a` 트리, 0.4.0) | runtime | 14 routes, 301, 0.4.0 smoke **451 / 0**. Page DB 교체 미실행(about/faq/contact/refund v1 유지) |
| 수정본 정적 계약 | manifest target 중복 검사·실패 후보 재현·시뮬레이션 계약 | vitest 77/77 PASS, `taxonomy:check` clean, 실패 후보 복원 시 3건 FAIL·check가 중복 보고 |
| 재배포 gate 5 (`888e11c2`, persisted overlay) | `LayoutExtension` 행·served `page/show.json` | PASS — sirsoft-basic(template 2)에 모듈 target별 1행, `_user_base` 행 id 41 priority 400 active, injection `main_content_area/prepend_child`·`footer/inject_props`·`header_currency_inject_anchor/replace`, `rh_gnav_root`·`linkGroups`(커뮤니티 5·정보 6·정책 5)·`data-rh-commerce-suppressed` 존재, 이커머스 선택기 0 |
| 재배포 gate 6 smoke (content gate off) | runtime 0.4.1 | **1256 PASS / 0 FAIL** |
| 재배포 Page 교체 | pack `88e7e7b0…85f7`, `--actor=1` | dry-run 4 `would_update` → apply 4 `updated` → replay 4 `already_applied`; 4개 모두 `current_version=2`, 발행 유지, versions 1·2, v2 작성자 1 |
| release gate — 최종 smoke (content gate on) | 11 slug × 360/390/412/1280 + en + bot SEO 11 slug + legacy 301 | **1304 PASS / 0 FAIL** (content gate 48/48, bot SEO 44/44) |
| 외부 검토 URL `203.245.29.156:58770` | about/faq/contact/refund × 1280/390, 일반 Chrome UA | 200, RAON 제목·본문, 샘플·자리표시자 없음, 1280 우측 sticky 메뉴 11개 taxonomy 순서·현재 위치, 390 닫힌 disclosure·같은 11개 링크, overflow 없음 |
| ext:docgen `--check` | raonslab-product | 기존 문서 구조 미도입(F19) |
| G7 AI 도구 | `docs/ai-tools/**` MCP·skills | source-review만, 연결·호출하지 않음 |

### 6.1 worktree 고정 PHPUnit 실행 기록

- 명령(요청 worktree에서 1회, 분리 프로세스): `/tmp/uxr/phpt-pinned.sh --stop-on-error --stop-on-failure --log-junit /tmp/uxr/new2-junit.xml --filter "/::(apply_requires_the_approved_whole_file_sha256|pack_envelope_is_validated_before_any_page_is_read)$/" modules/_bundled/raonslab-product/tests/Feature/InfoPageRemediationCommandTest.php`
- 환경: PHP 8.3.35, PHPUnit 11.5.56, `APP_ENV=testing`, DB `g7_product_test`(운영 DB 미접촉, `tests/bootstrap.php` 동일 DB 이름 가드 통과). 제3자 패키지만 runtime `vendor/`에서 로드하고, 임시 autoload shim이 `App\`·`Database\`·`Tests\`·composer `files` helper를 worktree로 고정했으며 `base_path()` = worktree. probe로 `App\Extension\ModuleManager`·`Tests\TestCase`·helper·`InfoPageRemediator`·`NativePageContentPack`·`PageService`가 모두 worktree 파일임을 확인. shim·`.env`(운영 DB *이름*만)·`.env.testing` 사본은 실행 후 삭제.
- 대상 코드: HEAD `dc8ac2e8` + 테스트 파일의 명령 명시 등록(이 실행 직후 같은 내용으로 commit). 제품 코드는 `dc8ac2e8`와 동일.
- 고정 실행에서 드러난 harness 사실(제품 결함 아님): ① vendor 없는 worktree에서는 `app/Support/SampleData/bootstrap.php`가 Faker 부재로 판단해 `FakerShim`을 alias → shim이 실제 Faker를 먼저 로드해 해소 ② 활성 설치본이 없는 base path에서는 Artisan이 provider 등록보다 먼저 생성돼 `commands()`가 반영되지 않음 → 신규 테스트가 명령을 console kernel에 명시 등록 ③ 테스트 1건마다 전체 G7 migration(약 4분).

## 7. 기본 템플릿 제한과 최소 upstream 제안 (템플릿 직접 패치 금지)

| 표면 | 최소 변경(upstream-safe) | 효과 |
|---|---|---|
| 모바일 드로어 정보·정책 섹션 | `_user_base.json` 해당 `Div`에 `id: mobile_drawer_info_policy` 부여(+ 쇼핑 섹션 `id: mobile_drawer_shop`) | 모듈이 `replace`/`inject_props` Layout Extension으로 교체·숨김 가능. 기본 동작 무변경 |
| Header 쇼핑 탭 | `Header` composite에 `showShopNav?: boolean`(기본 true) | 결제 없는 사이트가 JS DOM 숨김 없이 끔 |
| 봇 footer | `seo-config.json` `footer_nav`가 `linkGroups` prop이 있으면 `iterate`로 렌더하고 없으면 기존 고정 필드 | human·bot footer 동일 분류 |
| 봇 드롭다운 | SEO 렌더러가 boolean `hidden` prop을 속성으로 직렬화 | 봇 HTML의 펼친 패널 제거 |

## 8. 수정 백로그

| ID | 우선 | 층위 | 조치 | 담당 lane | 선행 |
|---|---|---|---|---|---|
| B1 | ~~P0~~ DONE | DB | about/faq/contact/refund content pack v1(`88e7e7b0…85f7`) 적용 완료 — 4개 v2, replay already_applied | 콘텐츠·DB lane | 완료 2026-09-28 |
| B2 | ~~P1~~ DONE | RUNTIME | 0.4.1 수정본 `888e11c2` 재배포 완료(1차 `c64085b2`는 gate 5 실패 후 롤백, CASE 9). 백업 `g7-product-20260928T140533Z.tar.gz` | 배포 lane | 완료 2026-09-28 |
| B3 | P1 | ADMIN CONFIG | 샘플 게시판 `new-board` 비활성화(게시물 0건 확인 후, 삭제 아님) | 관리 lane | — |
| B4 | P1 | RUNTIME/ADMIN CONFIG | `APP_URL`·sitemap host 단일화 후 sitemap 재생성(중복·`localhost` 제거), 결제 미운영 동안 `/shop/products` sitemap 제외 여부 결정 | 배포 lane | — |
| B5 | P1 | SOURCE(템플릿 upstream) | §7 제안 4건을 upstream 또는 템플릿 fork 정책으로 결정 | 제품·기술 결정 | — |
| B6 | P1 | ADMIN CONFIG | SMTP 실제 발송 리허설 전까지 공개 문구에서 메일 발송 약속 금지(B1 payload 검토 조건) | 콘텐츠 lane | — |
| B7 | P2 | DB | `service` 본문 H2 "업무 한 개 실증" ↔ 카드 H3 "01 업무 한 개 실증" 중복 제거(PageService 편집, 별도 승인) | 콘텐츠 lane | B1 이후 |
| B8 | P2 | RUNTIME | `raonslab-ai-workspace` 활성 dist `.map` 제거(`--production` 재빌드 + update) | 해당 모듈 lane | — |
| B9 | P2 | SOURCE | `raonslab-product` `ext:docgen --init` 문서 구조 도입 | 모듈 lane | — |
| B10 | P2 | SOURCE | 테스트 harness: vendor 없는 worktree에서 기본 실행은 `base_path()`·`App\`를 운영 checkout으로 해석한다(§6.1 shim으로 우회). 고정 실행 시 `NativePageBootstrapCommandTest`도 같은 명령 등록 의존(활성 설치본 필요)을 가질 것으로 보이나 **미검증** — 같은 명시 등록 적용 여부 결정, 테스트당 전체 migration 비용 축소 | 기술 lane | — |

## 9. 진실성 경계 (B1 payload 검토 기준)

- 회사 법인명·주소·이메일·전화·운영 시간·가격·기간·SLA·고객·성과 수치·새 연락 채널을 만들지 않는다.
- contact: 비공개 상담 접수는 현재 닫혀 있음(`intake_enabled=false`), 민감하지 않은 질문은 `/board/questions`, 개인정보·자격증명·계약 정보 게시 금지 안내. 응답 기한 약속 없음.
- refund: 이 사이트는 현재 온라인 결제·구독·유료 주문을 받지 않아 사이트 차원의 환불 절차가 없다는 사실만. 별도 계약에 대한 일반 주장 없음. 상거래를 켜기 전 개정 필수.
- faq: 제품 범위·상담 상태·데이터·계정의 고정 사실만, 대화형 Q&A는 `/board/questions`로 구분.
- about: RAON Agent Factory 범위(실증 → 구축 → 운영)와 약속하지 않는 것, G7 기반 표기. 미승인 법인 주장 없음.
