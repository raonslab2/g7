# 0.5.1 릴리스 노트 — HOME V2.1 증거·안내 보정 (모듈 부분)

기준: GNUBOARD7 `origin/main` `51e1d550`(raonslab-product 0.5.0 제품 기준선 `664c36ba` + 감사 문서 커밋 3개).
입력: `docs/g7/audit/HOME_V2_VISUAL_BUSINESS_AUDIT.md` 의 F1–F4. 이 릴리스는 **모듈이 소유한 F1·F2·F3(홈 링크 문구)·F4(봇·정적 표면 닫힘)** 를 담는다. 커밋은 셋이다: F1–F3 `6b79d16e`, F4 `07e3671e`, F4 검증 스크립트·배포 절차 보정(그 위 세 번째 커밋).

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

native Page 는 모듈 0.5.1 이 바꾸지 않는다. CODEX Request `req_63add39eb40b4acdbb2e15adc294897a` 가 PageService 공식 경로로 변경했다(raw SQL 없음). 모듈 쪽 Request 는 `req_f327b70159d943aca01d2f3e3e04bb56` 이다.

### 현재 최종 상태 (배포 후, 2026-09-29)

| Page | id | 현재 버전 | PageVersion | 활동 로그 | 전체 콘텐츠 hash (sha256) |
|---|---|---|---|---|---|
| cases | 8 | v3 | 370 | 532 | `4018a2971ed50359a4ea1f56ffbf632e527d1f32a6cbb15633b9a7e520891920` |
| service | 7 | v3 | 371 | 533 | `2d19f76b64f14e42f43eea578b456ae362a6e6c8db00b94ad3bc01e09ddb1fe8` |
| contact | 6 | **v4** | **376** | **535** | `35c23542c42f78e03dff8ebbbc83b970e411aed861a4139d5fdfd403d52bb402` |

**전체 콘텐츠 hash 계산 방식** (모듈 Request 에서 세 값 모두 그대로 재현함):

1. `Modules\Sirsoft\Page\Models\Page` 를 Eloquent 로 읽어 **cast 가 적용된 값**으로 `title`·`content`·`content_mode`·`published`·`seo_meta` 다섯 필드를 모은다(`title`·`content` 는 `AsUnicodeJson`, `seo_meta` 는 `array` cast).
2. 배열을 재귀적으로 정렬한다 — **연관 배열은 키 순서로 정렬, 리스트(`array_is_list`)는 원래 순서 유지**.
3. `json_encode(…, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)` 결과 문자열의 SHA-256.

DB 컬럼 원문이나 Page API 응답을 그대로 해시하면 다른 값이 나온다. 비교할 때는 반드시 위 방식을 쓴다.

**contact v3 → v4** (PageVersion 372 → 376, 2026-09-29 05:55:21 UTC, 활동 로그 535 `page.update`, `changes_summary.changed_fields = ["content"]`, `title`·`seo_meta` 불변). 바뀐 문장은 ko/en 각 한 곳이다. 링크 주소(`/#rh-consult`)는 같고, 링크 문구가 "신청" 이 아니라 "안내·현재 상태 확인" 을 말하도록 바뀌었다.

| 로케일 | v3 (전) | v4 (후) |
|---|---|---|
| ko | `AI 에이전트 구축 상담은 홈의 <a href="/#rh-consult">구축 상담</a> 영역에서 신청합니다.` | `AI 에이전트 구축 상담 안내와 현재 접수 상태는 홈의 <a href="/#rh-consult">상담 안내·현재 상태 보기</a> 영역에서 확인합니다.` |
| en | `Request an AI agent build consultation through the <a href="/#rh-consult">consultation</a> section on the home page.` | `AI agent build consultation guidance and the current intake status are available in <a href="/#rh-consult">consultation guidance &amp; current status</a> on the home page.` |

뒤따르는 문장("온라인 접수는 … 개인정보도 수집하지 않습니다." / "Online intake opens only after … no personal information is collected.")은 두 버전이 같다.

**v4 반영 뒤 공식 백업**: `/var/backups/g7-product/g7-product-20260929T055917Z.tar.gz`, sha256 `24735f5e22b65c3daa73196ef372fed8f1bad48a1427148b9de691445f92aa3c`. 바깥 checksum·내부 `SHA256SUMS` 통과, `source-sha.txt` = `4cbd9da7a1d3cc63ce6bf6f44049eb80bb0df2b7`, DB 덤프에 PageVersion `(376,6,4…)` 포함.

### 이력 — v2 → v3 (CODEX 최초 변경)

| Page | id | 버전 | 전체 콘텐츠 hash (sha256, 전→후) |
|---|---|---|---|
| cases | 8 | v2 → v3 | `d87826d056f5cdcd6bfd062fd8380126628f4c2c3b4418fe528e3a1b712a1d2a` → `4018a2971ed50359a4ea1f56ffbf632e527d1f32a6cbb15633b9a7e520891920` |
| service | 7 | v2 → v3 | `af76d119f866d2037be3410c7423d92723da21d0e170e3852467562b0180f325` → `2d19f76b64f14e42f43eea578b456ae362a6e6c8db00b94ad3bc01e09ddb1fe8` |
| contact | 6 | v2 → v3 | `936137cab8d98d0138656ea6a1a0e0224a708f0b81c192f313dc4c8fc1589b1e` → `42712b0f43231bc4776cdb18defd04d9f7853b67a9e4f632bc2284dc35dbddb3` (현재는 v4로 대체됨) |

- PageVersion 370/371/372, 활동 로그 532/533/534.
- 당시 백업 `/var/backups/g7-product/g7-product-20260929T050602Z.tar.gz` (sha256 `6b9f84e9eac52995503420823d8e02db82d59970980ae2a326a0fabf364866ba`) — v3 이전 상태.
- 실제 Page 390/1280 이동 6건 PASS, 상담 닫힘 유지.

상담 접수는 닫힘 그대로다(`intake_enabled=false`). 연락처·대체 채널을 만들지 않았다.

### 알려진 별도 문제 (V2.1 회귀 아님) — en 390 Page 화면의 문서 가로 폭

- 관측: en 390 에서 `/page/contact`·`/page/cases`·`/page/service` 모두 `document.scrollWidth` 494 (뷰포트 390, +104px). 세 Page 본문 카드(`.rh-native-page-card`) 안의 보이는 요소는 넘침 0 이다. ko 390 은 390 으로 넘침이 없다. Page 내용이 바뀌지 않은 cases/service 에서도 같으므로 contact v4 콘텐츠 결함이 아니다.
- 처음에는 닫힌 모바일 드로어(`#mobile_nav_drawer`) 때문으로 보였다. 격리 측정(요소를 하나씩 숨겨 `scrollWidth` 재측정)에서는 드로어를 숨겨도 494 그대로였고, **제품 모듈 상위 메뉴의 상담 항목(`#rh_gnav_consult_item`, en 문구 "Intake not open yet" 포함)** 을 숨기면 390 으로 돌아왔다. 드로어는 `position: fixed` 로 넓어진 문서 오른쪽 끝(left 494)에 놓일 뿐 원인이 아니다.
- 이 항목은 0.5.0 A(`33490607`)에서 들어왔고 0.5.1 은 `product-nav.json`·관련 CSS·`nav.*` 문구를 바꾸지 않았다(`git diff 664c36ba 4cbd9da7`) — 0.5.1 회귀가 아니다.
- 분류: **별도 P1, 현재 Page 콘텐츠·V2.1 변경 범위 밖**. 무해하다고 판정하지 않았고, 이번 릴리스에서 고치지 않는다(수정은 제품 상위 메뉴의 좁은 폭 줄바꿈/축약을 별도 Request 로).
- 경위와 재발 방지: `docs/g7/Troubleshooting.md` CASE 10.

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
| 배포 런타임 | 배포 완료(2026-09-29): main·런타임 `4cbd9da7`, 활성 모듈 0.5.1(번들=활성 해시 동일), 훅 캐시에 `core.seo.filter_context`. 외부 `http://203.245.29.156:58770` 사람 390/1280 스모크 PASS 92 · FAIL 0(390 높이 5,996px, 화면 152×343px, 원본 200·sha256 `4fa8b7a7…`, Enter·탭 동작), 봇 ko `/`·en `/?locale=en` 각각 MISS→HIT 바이트 동일·실패 0, 상담 `intake_enabled=false`. 배포 전 백업 `g7-product-20260929T054554Z.tar.gz`(sha256 `ad0798246fd140b4d6915730a6615f8cc7998a071244a963733aa25fa7aa88fc`). 증거 `/home/mrdev/g7-evidence/home-v2.1-0.5.1-delivery/` |

`raonslab-ai-workspace` 의 `raonslab-product` 의존 제약은 공개 API·Service·라우트 변경이 없어 그대로 둔다(홈 표현만 변경).

## F4 실제 렌더 검증

활성 런타임을 바꾸지 않고 실제 `SeoMiddleware → SeoRenderer` 를 돌렸다.

- 임시 오버레이(`/tmp`): 런타임 코드·vendor·템플릿은 심링크, `modules/raonslab-product` 만 후보 사본, `bootstrap`·`storage/framework` 는 오버레이 전용. 설정 캐시를 쓰지 않아 base path 가 오버레이다.
- CLI 에서 HTTP 커널로 봇 요청을 두 번 처리했다. 요청은 판정 스크립트와 같다: ko `/`, en `/?locale=en`, `User-Agent: Googlebot/2.1`, `Accept-Language: *`. 캐시·세션 저장소는 array 로 강제(새 캐시 → 첫 요청은 반드시 MISS), 요청 사이에 로케일을 기본값(ko)으로 되돌려 요청마다 새 프로세스인 FPM 과 같게 했다. DB 는 트랜잭션 안에서 실행 후 롤백.
- 후보 레이아웃 확장은 코어의 미리보기 오버라이드(`LayoutExtensionService::setPreviewContentOverride`, 확장 id 39)로 주입. 훅은 후보 모듈의 `getSubscribedHooks` 로 등록되었다.

| 요청 URL | 순서 | X-SEO-Cache | sha256 | html lang | Hero 행동 묶음 | 열림 상담 행동 | 상담 패널 | 패널 입력 |
|---|---|---|---|---|---|---|---|---|
| `/` | 1 | MISS | `cb086a53bd00…` | ko | 1 (`#rh-case`, `#rh-process`) | 0 | "온라인 상담 접수 준비 중입니다" + 안내 | 0 |
| `/` | 2 | HIT | 같음(바이트 동일) | ko | 1 | 0 | 같음 | 0 |
| `/?locale=en` | 1 | MISS | `c56e6b9f7d89…` | en | 1 | 0 | "Online consultation intake is being prepared" + 안내 | 0 |
| `/?locale=en` | 2 | HIT | 같음(바이트 동일) | en | 1 | 0 | 같음 | 0 |

- 두 요청 결과를 판정 스크립트 격리 모드(`RH_SEO_FILES=… RH_SEO_CACHE=MISS,HIT`)에 넣어 ko/en 모두 실패 0.
- 대조: 같은 오버레이에서 0.5.0 레이아웃(오버라이드 없음)으로 돌리면 Hero 묶음 2 · 열림 상담 행동 1 · 정적 안내 없음 — 결함이 재현되어 검사가 결함을 잡는다는 것을 확인.
- 문서 전체 입력 필드 1개는 템플릿 헤더 검색창이며 상담 패널 밖이다. `mailto:`·`tel:` 0.
- `Accept-Language` 주의: 코어 `SetLocale` 이 `SeoMiddleware` 보다 먼저 Accept-Language 로 기본 로케일을 바꾼다. 그래서 `/?locale=en` 에 `Accept-Language: en` 을 함께 보내면 "기본 로케일을 쿼리로 명시" 로 판정되어 `/` 로 301 된다(격리 렌더에서 확인, 코어 동작이며 이번 범위에서 바꾸지 않음). 판정 스크립트는 로케일을 URL 로만 고르고 `Accept-Language: *` 를 보낸다.
- 판정 스크립트: `tests/browser/seo-static-closed.cjs`. 실제 런타임 모드는 첫 응답 정확히 `MISS`, 둘째 정확히 `HIT`, 두 응답 SHA-256 동일, 리다이렉트 불허를 강제하고 요청 URL·헤더를 출력한다. 스크립트 계약(URL·헤더·MISS/HIT·해시 동일·리다이렉트·DOM 기준·lang)은 정적 검사 9건 PASS.
- 부수 효과: 렌더러의 데이터소스가 런타임(`127.0.0.1:18770`)의 공개 GET API(게시판 최근글 등)를 호출했다. 런타임이 평소 방문과 같이 접근 로그와 파일 캐시 항목을 남겼다. 런타임 코드·모듈·설정·DB 는 바뀌지 않았다.

## 배포 절차 (runbook)

아래 순서를 지킨다. 어느 단계든 기대와 다르면 **전달을 멈추고** 검토한다(자동으로 다음 단계로 넘어가지 않는다). 모든 단계에서 AI_GCS·AgentOpt 서비스는 재시작하지 않는다.

1. **main 사전 확인**: `git fetch origin && git rev-parse origin/main` 이 `51e1d550618adc4780e949753af0b914ec641a36` 인지 확인한다(이 후보를 넘긴 시점 기준). 다르면(drift) 멈추고 검토한다.
2. **후보 worktree 확인**: 후보 worktree 의 `git status --short` 가 비어 있고(`node_modules` 등 추적·미추적 잔여물 없음), HEAD 가 이 브랜치의 세 번째 커밋이며 그 부모가 `07e3671e` → `6b79d16e` → `51e1d550` 인지 확인한다.
3. **새 공식 백업**: 현재 Page v3(cases 8 · service 7 · contact 6)를 포함하도록 지금 새로 만든다. `sudo systemctl start g7-product-backup.service` → 출력된 `/var/backups/g7-product/g7-product-<UTC>.tar.gz` 에 대해 `sha256sum -c <파일>.tar.gz.sha256`, 압축 안의 `SHA256SUMS` 검증(`sha256sum -c SHA256SUMS`), `source-sha.txt` 가 배포 전 런타임 HEAD(`664c36ba…`)인지 확인한다. 이전 백업(`g7-product-20260929T050602Z`)은 v3 이전 상태일 수 있으므로 대신 쓰지 않는다.
4. **main 전달(직렬)**: 세 커밋(`6b79d16e` → `07e3671e` → 세 번째 커밋)을 순서대로 main 에 전달한다(fast-forward, 병렬·스쿼시·재작성 없음). 전달 후 `origin/main` HEAD 가 세 번째 커밋인지 확인한다.
5. **런타임 갱신**: 런타임 checkout(`/home/mrdev/git/g7`)의 `git status --short` 가 비어 있는지 확인한 뒤 `git pull --ff-only`. fast-forward 가 아니면 멈춘다(`reset`·`checkout` 으로 맞추지 않는다).
6. **빌드 확인·모듈 반영**: 런타임에서 `/usr/bin/php8.3 artisan module:build raonslab-product --production` 후 `git status --short` 가 비어 있는지(=빌드 결과가 커밋 dist 와 같음) 확인 → `/usr/bin/php8.3 artisan module:update raonslab-product --force`(버전 0.5.1, 훅 캐시 재생성으로 `core.seo.filter_context` 구독 등록).
7. **홈 SEO 캐시만 비우기**: `/usr/bin/php8.3 artisan seo:clear --layout=home`. 전체 SEO 캐시는 비우지 않는다.
8. **외부 사람 화면**: 외부 공개 주소에서 390·1280 홈 확인 — 사례 역할 줄 두 개, MOBILE_STOCK 화면 152px(390)·원본 열기 200·sha256 `4fa8b7a7…`, "사례 상세 보기", 상담 닫힘 표시, 가로 넘침·콘솔 오류 0.
9. **외부 봇 화면**: 7 직후 외부 공개 주소로
   `G7_BASE_URL=<외부 주소> node modules/_bundled/raonslab-product/tests/browser/seo-static-closed.cjs ko` (요청 `/`),
   `… seo-static-closed.cjs en` (요청 `/?locale=en`). 둘 다 첫 응답 MISS → 둘째 HIT, 바이트 동일, 실패 0 이어야 한다. 누가 먼저 홈을 요청해 첫 응답이 HIT 이면 7 을 다시 하고 곧바로 재실행한다.
10. **Page 재확인**: cases id 8 · service id 7 · contact id 6 의 현재 버전·PageVersion·활동 로그·전체 콘텐츠 hash 가 위 "현재 최종 상태" 표와 같은지 확인한다(hash 는 위 계산 방식으로). 배포 당시 기준은 v3 세 개(370/371/372, 532/533/534)였고, 배포 뒤 contact 가 v4(376, 535)로 바뀌었다.
11. **상담 닫힘 확인**: `/usr/bin/php8.3 artisan raonslab-product:consultation-readiness` 로 접수 닫힘(`intake_enabled=false`)을, 공개 `GET /api/modules/raonslab-product/consultations/config` 응답이 열림 설정을 내주지 않는지(`enabled: false` 또는 접수 불가 응답)를 확인한다. 이번 배포에서 열지 않는다.
12. **실패 시**: 5–11 중 하나라도 실패하면 전달을 멈추고 아래 롤백 여부를 판단한다. 이번 범위에서는 **자동 롤백하지 않는다**.

## 롤백

- **소스(모듈 0.5.1 전체)**: `6b79d16e` · `07e3671e` · 세 번째 커밋을 되돌리는 **새 revert 커밋**을 만들어 main 에 같은 절차(4–7)로 전달한다 → 런타임 `git pull --ff-only` → `module:build … --production` 결과 확인 → `module:update raonslab-product --force` → `seo:clear --layout=home`. 런타임에서 `git reset`·`git checkout` 으로 되돌리지 않는다. 모듈 롤백에는 DB·마이그레이션 변경이 없다.
- **Page v3 는 별개다**: 소스 롤백은 Page 본문을 되돌리지 않는다. Page 를 되돌려야 하면 PageService 의 공식 버전 복원(restore)으로만 한다(raw SQL 금지). 이번 범위에서 Page 롤백은 자동으로 하지 않는다.
- 3 의 새 백업은 최후 수단 복구용 기준점이다.
