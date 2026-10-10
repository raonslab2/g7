# W04 기획전 계약 독립 검토 — PASS_BOUNDED_CONTRACT

고정 입력 `docs/symphony/W04_CAMPAIGN_PAGE_CONTRACT.md` v1 SHA-256 **`ef79adefea1e5a950b4809d079eb2a2cdf0cd89f43bb8d8d2d309156b0661b0d`**를 비작성자가 검토했다. 최초 PENDING_TARGET 사전 검토 후 실제 계약을 인수했으며 source/design 범위에 P1/P2 blocker는 발견하지 않았다. **구현/runtime/공식 Validation PASS가 아니다.** 제품·DB·env·service·Git 및 다른 Request는 변경하지 않았고 테스트/runtime 실행은 NOT_RUN이다. 아래 두 운영 문구 보완과 구현·독립 검증 조건을 lead에 전달했다.

## 실제 v1 계약 판정

| 검토 범위 | 판정 / 근거 |
| --- | --- |
| 완료 요구 / 자체 영속 콘텐츠 | 두 캠페인의 title/body/publication/version이 native Page 행에서 오고 actual admin→public 소비를 완료조건으로 요구한다. 이전 정적 banner만으로 완료하지 않으며 theme 분류는 기존 DB/catalog 기능을 보존한다. 네 theme-guide Page를 새 필수 요구로 발명하지 않는다 |
| 범위·slug | 정확히 `travel-lab-campaign-autumn-escape`, `travel-lab-campaign-weekend-reset` 두 slot, 실제 nature/wellness enum. generic Page list/core 변경 없이 curated projection, arbitrary prefix/slug/ID 불허. `/page/:slug`도 exact registry에 제한 |
| draft/privacy | **PageService(slug,false)+published 재확인**과 guest/member/admin 동일404 명시. native pages.read admin preview를 travel API에 전달하지 않는다. creator/updater/versions/attachments/signed URLs 제외. 공개 native resource 전체 복제 금지 |
| UI / 본문 / renderer | Page body→server registry catalog query→실제 상품/cards/cart; 캠페인 text를 가격 계산으로 쓰지 않음. PageBody는 escaped text와 formatting-only DOMPurify/attrs0, remote media/link/style/SVG 제거, API/editor policy widening 금지. template 간 활성 의존 없음. empty/error/retry/404를 static 성공으로 대체하지 않음 |
| native admin | 실제 nested slug LIKE filter, exact2 registry 재필터, PageCollection envelope/페이지네이션/abilities 사용. numeric native id로 editor/detail/version 연결, 실제 slug prefill·content_mode text 안내. native permission/scope/CRUD/버전/발행 hook 보존 |
| explicit provision | marker/flag/confirm/명시 actor read/create 권한을 쓰기 전 확인, Auth scope finally 복구. injected repositories 존재 검사 뒤 native create만. existing draft/edit/version/SEO/publication **전부 skip**, 자동 republish/토큰/업데이트 seed/새 테이블 없음 |
| cache / 부수효과 | native audit/version/SEO/sitemap jobs를 인정한다. own2 slugs에만 after_create/update/publish/restore/delete travel invalidation. no response cache/static cachefalse 전제와 실제 draft 전환 검증 필요. local sync/mysql-fulltext/mailarray 유지; 외부 Scout/import를 실행 PASS로 주장하지 않음 |
| 소유 파일 / 버전 / 증거 | 한 child에 module/template 공유 registration 파일을 명시 배정, parent가 env/install/서비스/Git/검증 소유. travel0.1.3 및 template dependency 동기화, unchanged native Page API의 version bump는 필요 없음. oldfa552 결과는 original SHA 유지, 새 기능 및 최종 통합 target 별도 검증 |

native fact table의 **10개 실제 파일 해시를 독립 재계산해 10/10 일치**했다. PageListRequest는 starts_with를 허용하지만 PageRepository normalizeSearchFilters는 first field/value만 소비하고 slug LIKE를 적용한다는 계약의 제한도 source로 확인했다. 연산자=starts_with의 실제 prefix 동작 PASS로 바꾸지 않았다. native publish toggle은 content-version snapshot을 만들지 않으므로 계약의 별도 설명이 맞다.

## lead에 전달한 작은 보완 사항

1. **slug 변경/삭제의 운영 의미:** native editor는 slug를 바꾸거나 Page를 삭제할 수 있다. registry만 immutable이고 native Page slug를 잠그는 구현은 v1에 없으므로, rename/delete하면 기존 slot은 missing/404로 보이고 명시 provision을 다시 호출하기 전에는 재생성하지 않는다고 표시하는 편이 정확하다. 등록된 Page slug를 immutable로 강제하려면 별도의 own hook/완료조건을 명시해야 하며 현재 계약에 있다고 가정하지 않는다. 기존 Page 변형을 강제로 되돌리거나 자동 생성하지 않는다.
2. **목록에서 보이지 않는 것과 전역 부재:** full pagination을 따라도 native read-own scope 때문에 존재하는 foreign Page가 안 보일 수 있다. UI는 ‘현재 권한에서 이용 불가/미조회’와 전역 ‘존재하지 않음’을 구분해야 한다. 생성 시 native uniqueness422/permission403을 보이는 계약을 유지한다. 유한 목록 필터의 operator 문제를 해결했다고 permission scope가 사라지는 것은 아니다.

구현 주의: DOMPurify의 attrs0는 `ALLOWED_ATTR:[]`뿐 아니라 **ALLOW_DATA_ATTR=false 및 ALLOW_ARIA_ATTR=false**도 고정해야 한다. 기존 native HtmlContent는 purifyConfig를 받을 수 있으므로 그대로 policy override 표면을 복사하지 않는다. 이는 v1의 ‘attributes 없음/정책 확대 금지’ 요구를 실제 옵션으로 구현하는 주의 사항이며 새로운 runtime PASS가 아니다.

## 확인된 native 경계

- `sirsoft-page/src/Http/Controllers/User/PublicPageController.php:47`은 `pages.read` Admin 권한자의 draft preview를 의도적으로 허용한다. 신규 travel 공개 adapter가 이 controller의 preview 판단을 전달하면 published-only 계약을 위반할 수 있다.
- `PageService.php:358`의 `getPublishedPageBySlug(slug, false)`는 미발행을 null로 반환한다. 공개 travel API는 allowUnpublished=false를 명시하고 필요하면 returned model의 published를 다시 확인해야 한다. 관리자 편집/preview는 native admin 경로로 분리한다.
- `PublicPageResource.php`는 attachments/current_version/SEO/thumbnail 및 preview 표현을 포함한다. travel DTO가 공개 native resource 전체를 그대로 전달하면 의도하지 않은 첨부/서명 preview 표면이 추가된다. 계약은 소비 필드를 명시하고 첨부·내부 정보·서명 preview는 기본적으로 제외해야 한다.
- native `PageService::createPage/updatePage/changePublishStatus`는 버전 snapshot·hook·권한·발행 경로를 제공한다. 직접 Page 모델/DB upsert 대신 native service/admin을 사용한다. 재실행 seed는 기존 slug의 운영자 수정·draft·version을 덮어쓰거나 republish하지 않아야 한다.
- Page는 slug별 단일 문서이며 native 공개 목록 API는 없다. 고정된 작은 travel 캠페인 allowlist를 published Page 소비로 연결하는 방식은 native 문서 도메인을 변경하지 않는다. 기존 운영 Page slug와 합성 travel slug를 섞지 않는다.
- `sirsoft-basic/layouts/page/show.json:211`은 native `HtmlContent`와 `content_mode`를 소비한다. 신규 travel renderer도 실제 content mode를 존중하고 안전한 native renderer 계약을 사용해야 한다. 테스트 notice는 실제 거래 지점에서 유지한다.

## 고정 native 입력

| 파일 | SHA-256 |
| --- | --- |
| `PublicPageController.php` | `522b426316ea08c75459061d9fbd35f20d69a9cbfb1f90ec3b825a5a26869e27` |
| `PageService.php` | `6f9c57ccd7fc08aae4a8972c2c0ba545806ae4aa58dc6f0be3b8f1991803a287` |
| `PublicPageResource.php` | `695a070a180dfd669614125e69b623d4d41883eedaacfcb56a7f0f54107ae67a` |
| native template `page/show.json` | `dc8ffd26fdc982f8978fb9e855d0cc773d4625419ef5d173814313d3c4a2ac05` |

기획전 계약의 fixed slugs/소유 파일/UI·API·권한/seed 보존/noexternal/완료조건을 고정 입력에서 검토했다. published/draft guest/member/admin, unknown slug, 운영자 편집 후 seed rerun, 실제 native admin 발행/비공개/restore와 public travel 재조회, renderer/원격 request/모바일/PC/재기동은 **아직 신규 기능 검증으로 실행되지 않았다**. 계약 검토로 W04_COMPLETION_SCOPE_AUDIT의 기획전/Page 미구현 상태가 닫히지 않는다. 구현 child 및 독립 reviewer가 새 source/build SHA에서 실행해야 한다.

## lead 보완 계약 재확인

후속 target `W04_CAMPAIGN_PAGE_CONTRACT.md` SHA-256 **`0f6dd26920e09c781579f9dc8c1d4d77fdb5ca49533dfaf17714270b61fe46cb`**를 읽었다. 추가 heading 이전 원래 body의 실제 bytes를 분리해 재해시했고 정확히 **ef79adef… 원본 해시와 일치**했다. 새 변경은 세 개 clarification bullet이다.

- native slug rename/delete는 slot 분리·기존 상세404·navigation 제거이며, 명시 provision 이전에 재생성/덮어쓰기를 하지 않고 global Page slug guard도 추가하지 않는다.
- scope-filtered pagination 뒤 부재를 전역 비존재로 표현하지 않고, native403/422 scope·uniqueness 실패를 유지한다.
- DOMPurify attrs0에 ALLOW_DATA_ATTR=false/ALLOW_ARIA_ATTR=false를 명시하고 해당 속성 공격 테스트를 요구한다. API/layout의 정책 확대 금지도 유지한다.

세 보완은 앞서 전달한 주의 사항을 해소하며 **PASS_BOUNDED_CONTRACT 유지**다. native code 전수 감사·테스트·DB/runtime는 재실행하지 않았다. 기존 네 native 사전 pins/10개 계약 pins 검토는 original body에 그대로 결합되고, 새로운 feature execution PASS로 확대되지 않는다.

별도 metadata 관찰: 현재 `sirsoft-board` module/package/composer 및 package-lock root/nested version은 모두1.1.3이다. 이는 값 일치만 읽어 확인한 것이며 별도 native User AttachmentController minimal patch의 root 독립 리뷰/6tests47assertions 결과를 이번 reviewer 실행으로 합산하지 않았다. 소비자 minimum 제약 판정과 해당 source repair 검증은 그 소유 리뷰 범위다.
