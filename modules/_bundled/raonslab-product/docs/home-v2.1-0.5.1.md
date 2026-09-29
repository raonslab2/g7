# 0.5.1 릴리스 노트 — HOME V2.1 증거·안내 보정 (모듈 부분)

기준: GNUBOARD7 `origin/main` `51e1d550`(raonslab-product 0.5.0 제품 기준선 `664c36ba` + 감사 문서 커밋 3개).
입력: `docs/g7/audit/HOME_V2_VISUAL_BUSINESS_AUDIT.md` 의 F1–F4. 이 릴리스는 **모듈이 소유한 F1·F2·F3(홈 링크 문구)·F4(봇·정적 표면 닫힘)** 를 담는다. F1–F3 은 커밋 `6b79d16e`, F4 는 그 위 별도 커밋이다.

## 이 릴리스(모듈)에 들어간 것

| ID | 변경 | 위치 |
|---|---|---|
| F1 | 사례마다 "무엇을 입증하는가" 한 줄(`rh-proof-case-role`). RAON Hub: "지금 보고 계신 이 사이트입니다. 기존 서비스에 AI 요청·결과·후속 지시 흐름을 확장했습니다." MOBILE_STOCK: "업무 흐름 구현과 실주문 차단 검증을 보여 주는 자체 개발 사례입니다." | 홈 레이아웃 확장, ko/en |
| F1 순증 억제 | RAON Hub 종류 줄(`proof_hub_kind`)을 역할 줄로 교체하고, 역할 줄과 겹치는 "확장 방식" 칸(`proof_extend_*`)을 뺐다. 사실 줄은 실제 실행·검증·범위 세 칸. 두 사례 검증 범위 이동 링크는 MOBILE_STOCK 본문 끝(화면 이미지 옆 빈 세로 공간)으로 옮겼다 | 홈 레이아웃 확장, ko/en |
| F2 | 같은 이미지 1장(바이트·캡션·PROVENANCE 불변)을 휴대폰 이미지 열 9.5rem(152px)·데스크톱 10rem(160px)으로 키웠다. 비율 390/880(=780/1760) 유지, 자르지 않음. 이미지를 링크(`a[data-rh-asset-link]`)로 감싸 탭·키보드로 같은 자산 원본을 연다. href 는 `homePage.ts` 가 안쪽 이미지와 같은 `G7Core.asset.module` 주소로 채우며, 주소를 못 만들면 href 를 두지 않는다. 뷰어 의존성 없음. 접근 가능한 이름 "MOBILE_STOCK 화면 원본 크기로 보기", 기존 alt·캡션 유지 | 레이아웃, CSS, JS, ko/en |
| F2 | 화면 속 앱 이름을 홈에서 한 번 설명: MOBILE_STOCK 종류 줄 "모바일 모의투자 앱 · 화면 속 앱 이름 Symphony" | ko/en |
| F3(홈) | 사례 영역 `/page/cases` 링크 문구 "기술 근거 보기" → "사례 상세 보기"(en "See case details"). 도착지 불변 | ko/en |
| F4 | 봇·정적 렌더에서만 닫힘 표면을 싣는다. `ApplyHomeSeoMeta` 가 `core.seo.filter_context` 필터(`markHomeStaticContext`)로 **홈일 때만** `_local.raonStaticClosedFallback = true` 를 넣는다(다른 레이아웃 컨텍스트는 그대로, 기존 `core.seo.filter_meta` 동작 유지). 레이아웃은 열림 행동 묶음에 `condition: {{!_local.raonStaticClosedFallback}}` 를 달고, 상담 호스트 안에 `condition: {{_local.raonStaticClosedFallback}}` 인 정적 안내(`rh-state rh-consult-static-closed` · 기존 키 `consult.unavailable_title/copy`)를 둔다. 입력·양식·버튼·연락처·대체 채널 없음. SPA 상태 전환용 `data-rh-intake-show` 두 속성은 유지 — 사람 화면에서는 표시가 없어 종전과 같다. 새 CSS·JS·번역 키 없음. `seo-config.json` allowlist 는 바꾸지 않았다 | `src/Listeners/ApplyHomeSeoMeta.php`, 홈 레이아웃 확장 |

주장 경계는 그대로다: 자체 구축·고객 납품 아님, 모의투자·예시 데이터, 검증됨/아직 미검증 병기. AI 분석·실거래·투자 성과·외부 고객 운영·전체 자율 개발을 주장하지 않는다(증거 결합·레이아웃 테스트가 금지 표현을 검사).

## 모듈 밖에서 함께 반영된 것 — Page 콘텐츠 (CODEX, 독립)

native Page 는 모듈 0.5.1 이 바꾸지 않는다. CODEX Request `req_63add39eb40b4acdbb2e15adc294897a` 가 PageService 공식 경로로 변경했다(raw SQL 없음). 아래는 그 Request 의 보고를 옮긴 기록이며, 이 모듈 Request 에서 다시 측정하지 않았다.

| Page | id | 버전 | 본문 sha256 (전→후) |
|---|---|---|---|
| cases | 8 | v2 → v3 | `d87826d056f5cdcd6bfd062fd8380126628f4c2c3b4418fe528e3a1b712a1d2a` → `4018a2971ed50359a4ea1f56ffbf632e527d1f32a6cbb15633b9a7e520891920` |
| service | 7 | v2 → v3 | `af76d119f866d2037be3410c7423d92723da21d0e170e3852467562b0180f325` → `2d19f76b64f14e42f43eea578b456ae362a6e6c8db00b94ad3bc01e09ddb1fe8` |
| contact | 6 | v2 → v3 | `936137cab8d98d0138656ea6a1a0e0224a708f0b81c192f313dc4c8fc1589b1e` → `42712b0f43231bc4776cdb18defd04d9f7853b67a9e4f632bc2284dc35dbddb3` |

- PageVersion 370/371/372, 활동 로그 532/533/534.
- 백업 `/var/backups/g7-product/g7-product-20260929T050602Z.tar.gz` (sha256 `6b9f84e9eac52995503420823d8e02db82d59970980ae2a326a0fabf364866ba`).
- 실제 Page 390/1280 이동 6건 PASS, 상담 닫힘 유지.

상담 접수는 닫힘 그대로다(`intake_enabled=false`). 연락처·대체 채널을 만들지 않았다.

## F4 원인과 수정 방식

- 원인(CODEX 재현): 새 봇 URL 의 최초 `X-SEO-Cache: MISS` 와 다음 `HIT` 가 바이트 동일한 결함 — 캐시 문제가 아니다. 템플릿 SEO allowlist 가 `data-*` 속성을 떼어 Hero 행동 묶음 두 벌(열림 상담 행동 포함)이 모두 남고, 상담 패널은 JS 가 채우므로 비어 있었다.
- 금지된 방식(쓰지 않음): 코어·템플릿·SEO 렌더러 변경, allowlist 에 `data-rh-intake-show` 추가(원시 HTML 에 두 벌이 그대로 남는다), CSS 만으로 숨김.
- 채택: 렌더 컨텍스트 표시 + 레이아웃 조건. 봇 렌더는 조건을 서버에서 평가하므로 원시 HTML 자체에 한 벌만 남는다.

## 실측 (후보 주입 실제 Chromium, 런타임 `127.0.0.1:18770` = `664c36ba`/0.5.0)

| 항목 | 0.5.0 | 0.5.1 |
|---|---|---|
| 390 전체 높이 | 5,925px | 5,996px (상한 6,000 유지, 완화 없음) |
| 360 / 412 / 1280 전체 높이 | 6,093 / 5,877 / 4,937px | 6,142 / 5,948 / 4,930px |
| 390 MOBILE_STOCK 화면 표시 크기 | 100×226px | 152×343px (1280: 160×361px) |
| 390 "이 사이트가 RAON Hub" 줄 위치 | 없음 | 증거 섹션 695px 안(y<1,000 검사 통과) |
| 390 MOBILE_STOCK 미리보기 시작 | 1,083px | 1,093px (2화면 안) |
| 390 홈 글자(공백 제외) | 1,441 | 1,487 (상한 1,580) |
| 홈 이미지 수 | 1 | 1 |

원본 열기: 이미지 `src` 와 같은 주소, 런타임 응답 200, sha256 `4fa8b7a705bede727aa21f4ba7a089466b2455b15051f2c660adcbeafa3e30de`. 390 에서 키보드 포커스 + Enter, 탭 모두 같은 원본으로 이동.

## 검증

| 항목 | 결과 |
|---|---|
| 모듈 Vitest(`npm run test:run`) | F1–F3: PASS 104/104 · F4 포함: PASS 108/108 |
| 분류 드리프트(`node scripts/taxonomy.mjs --check`) | PASS |
| 결정적 빌드(재빌드 = 커밋 dist, 기준선 소스 재빌드 = 기준선 dist) | PASS |
| 홈 스모크 390 전체 경로(폼·탐색 포함) | PASS 98 · FAIL 0 |
| 홈 스모크 360/412/1280(레이아웃·영향 요소) | PASS 130 · FAIL 0 |
| F4 후 홈 스모크 390 전체 경로(합성 열림 스텁 양식 시나리오 포함) | PASS 98 · FAIL 0 |
| F4 후 홈 스모크 360/390/412/1280 레이아웃(사람 화면: 정적 안내 0 · Hero 상태 묶음 2) | PASS 180 · FAIL 0 |
| PHPUnit `ProductLayerContractTest` | 공식 명령 미실행 — `ModuleTestCase` 가 `RefreshDatabase`(테스트 DB 마이그레이션)를 요구하고 이번 지시가 마이그레이션을 금지한다. 대신 런타임 vendor 오토로더(읽기 전용)로 SEO 관련 두 테스트 메서드(`home_seo_meta_uses_the_module_owned_localized_config`, `home_seo_context_is_marked_static_closed_only_for_the_home_layout`)를 앱 부팅·DB 없이 직접 실행: PASS 2/2(단언 20) |
| 실제 SEO 렌더(F4) | 아래 "F4 실제 렌더 검증" PASS |
| 배포 런타임 | 미배포 후보 |

`raonslab-ai-workspace` 의 `raonslab-product` 의존 제약은 공개 API·Service·라우트 변경이 없어 그대로 둔다(홈 표현만 변경).

## F4 실제 렌더 검증

활성 런타임을 바꾸지 않고 실제 `SeoMiddleware → SeoRenderer` 를 돌렸다.

- 임시 오버레이(`/tmp`): 런타임 코드·vendor·템플릿은 심링크, `modules/raonslab-product` 만 후보 사본, `bootstrap`·`storage/framework` 는 오버레이 전용. 설정 캐시를 쓰지 않아 base path 가 오버레이다.
- CLI 에서 HTTP 커널로 봇 요청(`Googlebot` UA, `Accept-Language` ko/en)을 두 번 처리. 캐시·세션 저장소는 array 로 강제(새 캐시 → 첫 요청은 반드시 MISS), DB 는 트랜잭션 안에서 실행 후 롤백.
- 후보 레이아웃 확장은 코어의 미리보기 오버라이드(`LayoutExtensionService::setPreviewContentOverride`, 확장 id 39)로 주입. 훅은 후보 모듈의 `getSubscribedHooks` 로 등록되었다.

| 로케일 | 요청 | X-SEO-Cache | sha256 | Hero 행동 묶음 | 열림 상담 행동 | 상담 패널 | 패널 입력 |
|---|---|---|---|---|---|---|---|
| ko | 1 | MISS | `cb086a53bd00a55b…` | 1 (`#rh-case`, `#rh-process`) | 0 | "온라인 상담 접수 준비 중입니다" + 안내 | 0 |
| ko | 2 | HIT | 같음(바이트 동일) | 1 | 0 | 같음 | 0 |
| en | 1 | MISS | `80aa6727395874236…` | 1 | 0 | "Online consultation intake is being prepared" + 안내 | 0 |
| en | 2 | HIT | 같음(바이트 동일) | 1 | 0 | 같음 | 0 |

- 대조: 같은 오버레이에서 0.5.0 레이아웃(오버라이드 없음)으로 돌리면 Hero 묶음 2 · 열림 상담 행동 1 · 정적 안내 없음 — 결함이 재현되어 검사가 결함을 잡는다는 것을 확인.
- 문서 전체 입력 필드 1개는 템플릿 헤더 검색창이며 상담 패널 밖이다. `mailto:`·`tel:` 0.
- 판정 스크립트: `tests/browser/seo-static-closed.cjs`(배포 후 실제 런타임 대상, 배포 전에는 `RH_SEO_FILES` 로 저장 HTML 검사). 후보 ko/en PASS, 0.5.0 대조 FAIL.
- 부수 효과: 렌더러의 데이터소스가 런타임(`127.0.0.1:18770`)의 공개 GET API(게시판 최근글 등)를 호출했다. 런타임이 평소 방문과 같이 접근 로그와 파일 캐시 항목을 남겼다. 런타임 코드·모듈·설정·DB 는 바뀌지 않았다.

## 배포·롤백

- 반영: `module:build raonslab-product --production` 결과가 커밋 dist 와 같은지 확인 → `module:update raonslab-product --force`(훅 캐시가 다시 만들어져 새 `core.seo.filter_context` 구독이 등록된다).
- 반영 후: 홈 SEO 캐시가 옛 HTML 을 들고 있으므로 홈 레이아웃 SEO 캐시를 비운 뒤(`seo:clear --layout=home`) `node tests/browser/seo-static-closed.cjs ko` / `en` 으로 MISS→HIT 를 확인한다.
- 롤백: 이 커밋을 되돌린 dist(0.5.0)로 `module:update raonslab-product --force`. DB·마이그레이션 변경 없음.
