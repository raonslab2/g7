# 0.4.0 native Page 전환 근거·운영·롤백

## 결론과 분류

`0c5107d1`이 추가한 고객용 문서 7종은 문서 자체가 아니라 product layout과 ko/en 번역에 본문을 고정해
관리자 CRUD·발행·version을 우회했다. `sirsoft-page 1.1.2`가 이미 활성 상태이며 같은 요구를 공식 계약으로
제공하므로 본문은 `NATIVE_PAGE`로 전환한다.

| 범위 | 분류 | 근거와 소유권 |
| --- | --- | --- |
| 7개 제목·본문·발행·SEO·수정시각·version·attachment | `NATIVE_PAGE` | `PageService`, `/admin/pages`, `/api/modules/sirsoft-page/pages/{slug}`, `/page/{slug}` |
| RAON header/footer 및 정보·정책 taxonomy | `NAVIGATION` | `/admin/menus`와 `g7_menus`는 admin sidebar 전용이고 `sirsoft-basic` public template이 소비하지 않음 |
| breadcrumb·side navigation·dark neutral CSS·responsive behavior | `PRODUCT_UI` | `page/show` layout overlay와 module asset으로 유지, G7/page core 수정 없음 |
| GDPR privacy 연결 | `SPECIAL_INTEGRATION` | plugin은 미설치. 설치 시 `privacy_policy_slug=privacy`가 `/page/privacy`를 소비하며 지금은 설정/DB 변경 없음 |

## canonical Page 계약

| 문서 | slug | 이전 URL |
| --- | --- | --- |
| 서비스 소개 | `service` | `/info/services` |
| 구현 사례 | `cases` | `/info/cases` |
| 기술·검증 원칙 | `technology` | `/info/principles` |
| 개인정보 처리 안내/방침 | `privacy` | `/policy/privacy` |
| 서비스·커뮤니티 이용 원칙 | `terms` | `/policy/community` |
| AI 작업공간 권한 정책 | `ai-workspace-policy` | `/policy/ai-workspace` |
| 오픈소스·라이선스 고지 | `open-source` | `/policy/open-source` |

이전 URL과 locale prefix URL은 query string을 보존하는 HTTP 301만 반환한다. canonical, sitemap, public API,
product navigation과 footer의 대상은 모두 `/page/{slug}` 하나다. thin shell과 duplicate canonical은 두지 않는다.

## one-time bootstrap 안전성

운영 전환은 다음 fail-closed 절차로 수행했다.

1. 기존 7개 URL의 ko/en 렌더에서 승인 대기 상태를 포함한 본문과 SEO를 추출하고 내부 링크만 canonical로 변경했다.
2. 공식 백업을 DB 첫 쓰기 직전에 1회 생성했다.
3. `PageService::slugExists()`가 false인 5개 slug만 `PageService::createPage()`로 생성했다.
4. 기존 `privacy`와 `terms`는 공식 `PageSeeder`의 title/content/content_mode/published/빈 SEO와 정확히 같고
   `current_version=1`인 경우에만 `PageService::updatePage()`로 전환했다.
5. 재실행 판정은 7개 모두 `PRESERVED_EXISTING`이었다. module update에는 Page 수정 로직이 없으므로 이후 관리자
   편집본과 version을 덮어쓰지 않는다.

새 설치/복구 환경에서는 일회성 대화형 명령에 의존하지 않는다. 운영자가 `/admin/pages`에서 승인한 7개 Page를
보안 경로의 JSON으로 export한 뒤 아래 공식 bootstrap 명령을 사용한다.

```bash
/usr/bin/php8.3 artisan raonslab-product:bootstrap-pages /secure/approved-raon-pages.json --dry-run
/usr/bin/php8.3 artisan raonslab-product:bootstrap-pages /secure/approved-raon-pages.json
```

payload root는 아래 7개 slug를 정확히 한 번씩 가져야 하며 각 값은 `title.ko/en`, `content.ko/en`,
`content_mode`, boolean `published`, flat `seo_meta.{title,description,keywords}` 계약을 따른다. 본문 payload는
repository에 포함하지 않는다. 명령은 payload 전체를 먼저 검증하고 각 slug에 `PageService::slugExists()`를
호출한다. 존재하는 slug는 `preserved_existing`으로 끝내며 `updatePage()`를 호출하는 경로가 없다. 누락분만
super admin attribution으로 `PageService::createPage()`를 호출해 v1 snapshot을 만든다.

SEO metadata는 sirsoft-page admin 계약이 flat string만 허용하므로 ko/en 설명을 함께 담은 중립적인 단일 title과
description으로 저장했다. 본문과 title은 native 다국어 필드다. 회사·담당자·보관기간 등 미확정 값은 만들지 않았고
기존 승인 필요 표기를 그대로 보존했다.

변경 전 백업은 `/var/backups/g7-product/g7-product-20260928T094352Z.tar.gz`이며 SHA-256은
`b67c07b135de82950d66c26d04dc133fca71a4a08152660790cc07135bfe4dc8`이다. 기존 격리 restore evidence를
재사용하고 이번 작업에서 중복 restore rehearsal은 수행하지 않았다.

## 제거한 중복과 변경 경계

- 제거: `resources/layouts/user/rh_info_*.json`, `rh_policy_*.json` 7개
- 제거: `resources/routes/user.json`
- 제거: ko/en의 `doc`, `services`, `cases`, `principles`, `privacy`, `community`, `ai_policy`, `oss` 본문/SEO 묶음
- 유지: product header/footer, taxonomy label, native Page breadcrumb/side navigation label, CSS/JS
- 수정하지 않음: G7 core, `sirsoft-page`, `sirsoft-gdpr`, `raonslab-ai-workspace`, AI_GCS, AgentOpt

## 운영과 rollback

- 편집·발행·attachment·version 조회/restore는 `/admin/pages`만 사용한다. 코드 배포 없이 public render에 반영된다.
- 소스 rollback은 0.3.1 commit으로 module lifecycle update 후 기존 URL route를 되돌릴 수 있다.
- 데이터 rollback은 변경 전 공식 backup archive를 격리 복원 절차에 투입하거나 각 Page의 이전 version을
  `/admin/pages`에서 restore한다. raw SQL write는 사용하지 않는다.
- bootstrap 자체의 rollback은 생성된 다섯 Page를 admin에서 제거하고 `privacy`·`terms`의 v1을 restore하는 방식이다.
  이미 관리자가 수정한 Page라면 자동 rollback/update하지 않고 수동 검토한다.

## 검증 기록

- native bootstrap: 5개 v1 생성, untouched sample `privacy`·`terms` v2 전환, 재실행 7개 무변경
- public API와 bot canonical render: 7개 모두 HTTP 200 및 canonical slug 확인
- privacy admin E2E: baseline v2 → 합성 marker v3 → public 반영. 첫 restore 시 version-list envelope를 잘못
  읽어 빈 version id로 호출해 404가 1회 발생했다. 실제 v2 id를 조회한 재시도는 restore v4에 성공했고 baseline과
  일치했다. API에 version 삭제 계약이 없어 exact marker·slug·version·current baseline sentinel을 확인한 뒤 해당
  임시 v3 snapshot만 Eloquent transaction으로 제거했다(raw SQL write 없음). 전체 DB text/json column 검사,
  public API와 public page 검사에서 marker 0건, 임시 Sanctum token 0건을 확인했다.
- source unit: Vitest 5 files / 61 tests PASS; PHP focused 8 tests / 67 assertions PASS; production asset build PASS
- 최종 PHP focused tests, 360/390/412/1280 runtime browser smoke와 main/runtime SHA는 delivery 단계에서 확인한다.
