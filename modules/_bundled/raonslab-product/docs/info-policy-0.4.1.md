# 0.4.1 정보·정책 분류·문서 메뉴·샘플 Page 교체 운영

## 소유 경계

| 대상 | 정본 | 이 모듈의 역할 |
| --- | --- | --- |
| 11개 문서의 제목·본문·SEO·version·발행 | `sirsoft-page` Page DB (`/admin/pages`) | 없음. 교체 명령은 `PageService::updatePage()`만 호출 |
| 그룹·순서·짧은 라벨·설명 | `resources/taxonomy/info-policy.json` | 단일 출처. `scripts/taxonomy.mjs`가 extension JSON 생성 |
| 상위 메뉴·product footer·문서 메뉴·breadcrumb | 생성된 `resources/extensions/{product-nav,native-page}.json` | 직접 수정 금지(`npm run taxonomy:check`, vitest 드리프트 테스트) |
| 통화 선택기 숨김 | `resources/extensions/public-commerce-chrome.json` | `_user_base`의 `header_currency_inject_anchor`를 빈 앵커로 교체(priority 400 > 이커머스 320) |

승인된 IA: 정보 `about, service, cases, technology, faq, contact` / 정책 `privacy, terms, ai-workspace-policy, open-source, refund`.

## 화면 계약

- 데스크톱(≥1024px): 문서 카드는 2열 grid, 문서 메뉴는 오른쪽 15rem 열에서 `position: sticky`. DOM·탭 순서는 본문 다음.
- 1023px 이하: 제목 아래 "정보·정책 문서 · {현재 문서}" 버튼(48px, 기본 닫힘, `aria-expanded`/`aria-controls`). Escape는 닫고 버튼으로 포커스를 돌린다. 링크 선택·주소 변경 시 닫힌다.
- 11개 문서 공통: breadcrumb `홈 / 정보|정책 / 문서 제목`, 같은 카드 폭, 템플릿 발행일 줄 숨김, CKEditor 기본 글머리표와 제품 목록 표식 중복 제거, 설명 없는 상태 행은 작은 격자로 정리.
- "Powered by 그누보드7"은 유지한다.

## 기본 템플릿 제한(모듈로 해결 불가, 템플릿 직접 패치 금지)

| 표면 | 현재 | 원인 | 최소 upstream 제안 |
| --- | --- | --- | --- |
| 모바일 드로어 "정보·정책" 목록 | G7 기본 6개 링크(회사소개·이용약관…) | `_user_base.json` 해당 섹션에 `id`가 없어 Layout Extension 대상이 될 수 없음 | 섹션에 `id`(예: `mobile_drawer_info_policy`)를 부여하거나 Footer와 같은 `linkGroups`형 prop을 받는 composite로 추출 |
| 모바일 드로어 쇼핑 섹션, 데스크톱 쇼핑 탭 | 드로어는 노출, 데스크톱 탭은 기존 JS로 숨김 | Header에 쇼핑 표시 여부 prop이 없고 드로어 섹션에 `id`가 없음 | Header `showShopNav` prop과 드로어 섹션 `id` |
| 검색봇 서버 렌더 header/footer | 템플릿 `seo-config.json` 고정 `header_nav`/`footer_nav` | `SeoConfigMerger`가 템플릿 설정을 마지막(최우선)으로 병합 | `footer_nav`가 `linkGroups` prop이 있으면 그것을 iterate |
| "새 게시판" 메뉴 | board-menu API의 활성 게시판 | 게시판 데이터/설정 | 관리자에서 해당 샘플 게시판 비활성화(게시물 0건 확인 후) — DB/관리 설정 lane |

## 샘플 Page 교체 명령

`raonslab-product:remediate-info-pages {payload} --actor=ID|email [--dry-run]`

payload는 콘텐츠 lane의 content pack v1 envelope다(저장소에 두지 않음, 보안 경로의 JSON):

```json
{
  "schema": "raonslab-product.native-page-content-pack.v1",
  "pack_id": "…",
  "base_commit": "<40자 commit SHA>",
  "pages": {
    "about":   { "title": {"ko": "…", "en": "…"}, "content": {"ko": "<…>", "en": "<…>"}, "content_mode": "html",
                 "published": true, "seo_meta": {"title": "…", "description": "…", "keywords": "…(선택)"} },
    "faq": { … }, "contact": { … }, "refund": { … }
  }
}
```

- envelope 키는 정확히 4개, `schema` 일치, `base_commit`은 40자 hex. 명령은 `payload_sha256`·`schema`·`pack_id`·`base_commit`을 출력하므로 승인된 SHA-256과 대조한다.
- `pages`는 정확히 4개 slug, ko·en 필수, `content_mode=html`, `published`는 `true`만 허용(현재 상태 선언일 뿐 `updatePage`로 넘기지 않아 발행 상태 불변), `입력하세요`·`DEMO/MOCK/SANDBOX/TEST` 문구 거부.
- 하나의 외부 transaction에서 4행을 `lockForUpdate`로 잠그고 먼저 모두 분류한다.
  - 현재 의미 지문 = 목표 지문 → `already_applied`
  - 발행 상태 + `current_version=1` + 감사 지문(`InfoPageRemediator::SOURCE_FINGERPRINTS`) 일치 → 교체 대상
  - 그 밖(누락·비발행·다른 version·관리자 편집) → `conflict`, 4개 모두 쓰지 않고 exit 1
- 쓰기 직후 같은 transaction에서 v2·`updated_by`·v2 스냅샷 `created_by`·필드 지문을 재확인하고 어긋나면 전체 rollback.
- 지문: `sha256("g7-page-sample-v1|" + join("|", len:value for slug, title.ko, title.en, content.ko, content.en, content_mode, seo.title, seo.description, seo.keywords))`. `PageSeeder` 원문으로 재계산한 값이 감사 값과 일치함을 테스트가 고정한다.

## 배포·적용 순서(이 source 턴에서는 실행하지 않음)

1. 콘텐츠 lane이 4개 ko/en payload를 승인하고 sha256을 기록한다(명령 출력 `payload_sha256`과 대조).
2. 공식 백업.
3. source 반영 뒤 `module:build raonslab-product --production`(이미 커밋된 dist와 같아야 함), `module:update raonslab-product --force`.
4. 새 PHP 프로세스에서 `route:clear` → `route:cache`, `g7-product-fpm.service`만 graceful reload(Troubleshooting CASE 1).
5. `route:list --name=raonslab-product.compatibility` 14개와 legacy 301 1건 확인.
6. `G7_SMOKE_CONTENT_GATE=0 node modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs` — UI 계약 확인.
7. `remediate-info-pages --dry-run` → 4개 `would_update` 확인 → 적용 → 재실행 4개 `already_applied`.
8. `node modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`(content gate 포함) 1회.

## 롤백

- source: 0.4.0 태그/커밋으로 되돌리고 3~5단계 반복. `public-commerce-chrome.json` 제거로 통화 선택기가 복구된다.
- data: 각 Page가 배포한 목표 지문·`current_version=2` 그대로일 때만 관리자 버전 복원으로 v1을 복원(새 version 생성, 이력 삭제 없음). 이후 관리자 편집이 있으면 수동 검토.

## 검증 기록(source 턴)

- 콘텐츠 pack `raon-native-pages-wave2-2026-09-28`(SHA-256 `88e7e7b0e18dca947f0251ce6dd217e0f0a0c0aa73e6161dbf4c1882b0ac85f7`, 51,412 bytes)은 DB 없이 명령의 payload 검증기를 통과했고, 4개 목표 지문이 모두 원문 지문과 다르며, smoke content gate 정규식에 걸리는 문구가 없다. 본문은 저장소에 복사하지 않았다.

- vitest 74/74, phpunit(ProductLayerContract·NativePageBootstrap command/unit·InfoPageRemediation) 22 tests / 225 assertions PASS.
- 새 smoke를 live 0.4.0에 실행: 476 PASS / 356 FAIL(이번 변경이 고치는 결함을 모두 검출). 같은 smoke를 live 런타임 위에 0.4.1 layout·asset·번역을 클라이언트에서만 겹쳐 실행: 1208 PASS / 0 FAIL(content gate off), content gate on이면 샘플 4개 × 4 viewport 16건만 FAIL — DB 교체 전 예상 상태.
