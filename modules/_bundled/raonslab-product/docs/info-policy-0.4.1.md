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

`raonslab-product:remediate-info-pages {pack} --actor=ID|email --sha256=<승인 SHA-256> [--dry-run]`

- 인자 `pack`은 콘텐츠 lane의 content pack v1 파일 절대 경로다(저장소에 두지 않음). `NativePageContentPack`이 파일 전체
  SHA-256을 계산하고 `--sha256`과 다르면 거부한다. 실제 적용(`--dry-run` 없음)은 `--sha256`이 없으면 실행하지 않는다.
- envelope은 정확히 `schema`·`pack_id`·`base_commit`·`pages` 네 키다.
  - `schema` = `raonslab-product.native-page-content-pack.v1`
  - `pack_id` = 비어 있지 않은 문자열(100자 이하)
  - `base_commit` = 소문자 40자 SHA이며 `InfoPageRemediator::AUDITED_BASE_COMMIT`
    (`390cdc7a379e1f2b9c8e3991b241edcc59dbdd71`, 원문 지문을 측정한 감사 기준)과 같아야 한다
  - `pages` = slug 키 객체. 명령은 이 `pages`만 `InfoPageRemediator`로 넘긴다
- `pages`: 정확히 about·faq·contact·refund, 각 page 키는 `title`·`content`·`content_mode`·`published`·`seo_meta`만.
  ko·en 필수, `content_mode=html`, `published`는 `true`만 허용(현재 발행 상태 선언일 뿐 `updatePage`로 넘기지 않아 발행 상태 불변),
  `seo_meta`는 title·description 필수·keywords 선택, `입력하세요`·`DEMO/MOCK/SANDBOX/TEST` 문구 거부.
- 명령 출력: `pack_sha256: … (matches approved|not checked)`, `schema`, `pack_id`, `base_commit`, slug별 결과.

- 하나의 외부 transaction에서 4행을 `lockForUpdate`로 잠그고 먼저 모두 분류한다.
  - 현재 의미 지문 = 목표 지문 → `already_applied`
  - 발행 상태 + `current_version=1` + 감사 지문(`InfoPageRemediator::SOURCE_FINGERPRINTS`) 일치 → 교체 대상
  - 그 밖(누락·비발행·다른 version·관리자 편집) → `conflict`, 4개 모두 쓰지 않고 exit 1
- 쓰기 직후 같은 transaction에서 v2·`updated_by`·v2 스냅샷 `created_by`·필드 지문을 재확인하고 어긋나면 전체 rollback.
- 지문: `sha256("g7-page-sample-v1|" + join("|", len:value for slug, title.ko, title.en, content.ko, content.en, content_mode, seo.title, seo.description, seo.keywords))`. `PageSeeder` 원문으로 재계산한 값이 감사 값과 일치함을 테스트가 고정한다.

## 배포·적용 순서(이 source 턴에서는 실행하지 않음)

1. 콘텐츠 pack을 승인하고 파일 전체 SHA-256을 기록한다. 이번 pack: `raon-native-pages-wave2-2026-09-28`, 51,412 bytes,
   SHA-256 `88e7e7b0e18dca947f0251ce6dd217e0f0a0c0aa73e6161dbf4c1882b0ac85f7`, 현재 위치 `/tmp/rh-pack/pack.canonical.json`
   (운영자 전용 보안 경로로 옮긴 경우 아래 경로만 바꾸고 SHA-256은 그대로 대조한다).
2. 공식 백업과 새 archive 검증(배포 전, runtime HEAD `390cdc7a379e1f2b9c8e3991b241edcc59dbdd71` 상태에서):

   ```bash
   sudo systemctl start g7-product-backup.service          # oneshot, /home/mrdev/git/g7/scripts/g7-backup.sh
   A=$(ls -t /var/backups/g7-product/g7-product-*.tar.gz | head -1)   # 방금 만든 archive인지 시각 확인
   sha256sum -c "$A.sha256"                                # 외부 SHA-256
   T=$(mktemp -d); tar -xzf "$A" -C "$T"
   (cd "$T" && sha256sum -c SHA256SUMS)                    # 내부 SHA-256(database.sql.gz·migration-status.txt·persistent-files.tar.gz·source-sha.txt)
   cat "$T/source-sha.txt"                                 # 390cdc7a379e1f2b9c8e3991b241edcc59dbdd71 이어야 한다
   rm -rf "$T"
   ```

3. source 반영은 push → main 통합 → runtime `git pull --ff-only`만 쓴다(runtime checkout에서 직접 수정·commit 금지):

   ```bash
   git -C /home/mrdev/git/g7 pull --ff-only                # HEAD = 통합된 main commit
   cd /home/mrdev/git/g7
   /usr/bin/php8.3 artisan module:build raonslab-product --production
   git status --porcelain                                  # 비어 있어야 한다(커밋된 dist와 동일). 무엇이든 나오면 중단
   /usr/bin/php8.3 artisan module:update raonslab-product --force
   ```
4. 새 PHP 프로세스에서 `route:clear` → `route:cache`, `g7-product-fpm.service`만 graceful reload(Troubleshooting CASE 1).
5. `route:list --name=raonslab-product.compatibility` 14개와 legacy 301 1건 확인.
6. `G7_SMOKE_CONTENT_GATE=0 node modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs` — UI 계약 확인.
7. 샘플 Page 교체(같은 파일·같은 SHA-256, actor는 활성 최고 관리자 ID 1 — 기존 Page 기록자와 같은 관리자, 명령이 활성·최고 관리자 여부를 다시 확인):

   ```bash
   cd /home/mrdev/git/g7
   PACK=/tmp/rh-pack/pack.canonical.json
   PACK_SHA256=88e7e7b0e18dca947f0251ce6dd217e0f0a0c0aa73e6161dbf4c1882b0ac85f7
   sha256sum "$PACK"   # 위 값과 같아야 한다
   /usr/bin/php8.3 artisan raonslab-product:remediate-info-pages "$PACK" --actor=1 --sha256="$PACK_SHA256" --dry-run
   #   기대: pack_sha256: 88e7…85f7 (matches approved) / pack_id: raon-native-pages-wave2-2026-09-28
   #         base_commit: 390cdc7a…dd71 / about·faq·contact·refund: would_update
   /usr/bin/php8.3 artisan raonslab-product:remediate-info-pages "$PACK" --actor=1 --sha256="$PACK_SHA256"
   #   기대: 4개 updated
   /usr/bin/php8.3 artisan raonslab-product:remediate-info-pages "$PACK" --actor=1 --sha256="$PACK_SHA256"
   #   기대: 4개 already_applied (쓰기 없음)
   ```

   `conflict`가 하나라도 나오면 아무것도 쓰지 않은 상태이므로 원인(관리자 편집·version·비발행)을 확인하기 전 재시도하지 않는다.
8. `node modules/_bundled/raonslab-product/tests/browser/info-policy-smoke.cjs`(content gate 포함) 1회.

## 롤백

- source: 롤백 기준 commit은 `390cdc7a379e1f2b9c8e3991b241edcc59dbdd71`(raonslab-product 0.4.0). 0.4.1 commit들을 되돌리는 revert를 main에 push한 뒤
  runtime에서 `git pull --ff-only` → `module:build raonslab-product --production` 후 `git status --porcelain` 비어 있음 확인 →
  `module:update raonslab-product --force` → 4~5단계(route clear/cache, FPM reload, 14 routes·301 확인). `public-commerce-chrome.json`이 사라지면서 통화 선택기가 복구된다.
- data: 각 Page가 배포한 목표 지문·`current_version=2` 그대로일 때만 관리자 버전 복원으로 v1 내용을 복원한다. 복원은 이력을 지우지 않고
  **새 version v3**(내용 = v1 샘플)를 만든다. 그 뒤 같은 pack으로 이 명령을 다시 실행하면 `current_version=3`이라 `unexpected_version:3` conflict로
  **의도적으로 거부**한다(샘플 v1 전제만 자동 교체). 다시 적용하려면 새 검토·새 사전 조건이 필요하다. 이후 관리자 편집이 있으면 수동 검토.

## 검증 기록(source 턴)

- 콘텐츠 pack `raon-native-pages-wave2-2026-09-28`(SHA-256 `88e7e7b0e18dca947f0251ce6dd217e0f0a0c0aa73e6161dbf4c1882b0ac85f7`, 51,412 bytes)은 DB 없이 `NativePageContentPack::fromFile`(승인 SHA-256·감사 base_commit 대조)과 `InfoPageRemediator` pages 검증을 통과했고(한 글자 다른 SHA-256은 거부), 4개 목표 지문이 모두 원문 지문과 다르며, smoke content gate 정규식에 걸리는 문구가 없다. 본문은 저장소에 복사하지 않았다.

- vitest 74/74 PASS.
- phpunit, worktree 고정(현 HEAD 후보 코드, 명령·환경은 `docs/g7/audit/INITIALIZATION_AUDIT_2026-09-28.md` §6.1): `InfoPageRemediationCommandTest`의
  신규 2건 `apply_requires_the_approved_whole_file_sha256`·`pack_envelope_is_validated_before_any_page_is_read` → **2 tests / 15 assertions PASS**.
- phpunit, 이전 실행(worktree 고정 아님 — base_path·`App\`는 운영 checkout의 동일 코어 commit, 모듈 클래스는 worktree):
  ProductLayerContract·NativePageBootstrap command/unit·InfoPageRemediation 나머지 8건 포함 24 tests / 253 assertions PASS. 변경 없는 테스트라 고정 재실행하지 않았다.
- 증거 분류
  | 실행 | 결과 | 분류 |
  | --- | --- | --- |
  | 새 smoke × 배포 전 live 0.4.0 | 476 PASS / 356 FAIL(새 표현 부재·샘플 본문) | `EXPECTED_FAIL_PREDEPLOY` — 결함 검출력 확인용, release gate 아님, 재실행하지 않음 |
  | 새 smoke × live 위 0.4.1 클라이언트 오버레이(content gate off) | 1208 PASS / 0 FAIL | 배포 전 긍정 증거 |
  | 〃 content gate on | 16 FAIL = 샘플 4 slug × 4 viewport | `EXPECTED_FAIL_PREDEPLOY`(DB 교체 전) |
  | 새 smoke × 배포 + DB 적용 후 runtime | 미실행 | release gate — 배포 순서 8단계에서 1회 |
- smoke는 networkidle 뒤 `.rh-native-breadcrumb`를 짧은 고정 시간(`G7_SMOKE_PRESENTATION_WAIT_MS`, 기본 3000ms)만 기다리고, 없으면
  `RAON document presentation is applied` FAIL을 기록한 뒤 다음 페이지로 진행한다.
