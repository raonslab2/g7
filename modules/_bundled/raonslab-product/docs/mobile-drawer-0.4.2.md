# 0.4.2 모바일 드로어 문서 메뉴 계약

0.4.1 에서 "템플릿 제한"으로 남겼던 모바일 드로어의 기본 쇼핑·정보/정책 섹션을, 템플릿·코어·이커머스를
고치지 않고 공식 `_user_base` Layout Extension 과 모듈 CSS 로 대체한다.

## 구성

- `scripts/taxonomy.mjs` 가 `resources/extensions/product-nav.json`(이 모듈의 유일한 `_user_base` overlay,
  priority 400)에 `mobile_nav_drawer` `append_child` injection 을 생성한다. 소유 노드는 `rh_mobile_drawer_docs`
  (`Nav`, `data-rh-mobile-drawer-docs`) 하나이며, 분류(`resources/taxonomy/info-policy.json`)의 11개 문서를
  정보 6 · 정책 5 순서로 담는다. 그룹 제목은 `nav.info`/`nav.policy`, 링크 라벨은 상위 메뉴와 같은 ko/en 키다.
- 링크는 `href` + `data-rh-nav-path` 를 갖고, 선택하면 템플릿 드로어 링크와 같이 `mobileMenuOpen=false` 후 이동한다.
  현재 문서 링크에는 `productNav.ts` 가 `aria-current="page"` 를 단다.
- `resources/css/main.css` 가 `#mobile_nav_drawer` 직계 자식 중 소유 노드 **바로 앞 두 칸**(템플릿의 쇼핑 →
  정보/정책 섹션)만 `:has()` 로 숨긴다. 언어·회원·로그인·홈·게시판 섹션은 그대로다. `:has()` 는 선언된 브라우저
  하한(Chrome 111 / Safari 16.4 / Firefox 128) 안이다.

## 구조 계약 (깨지면 테스트가 실패해야 한다)

`resources/js/infoPolicy.test.ts` "모바일 드로어 문서 섹션":

- 번들 `sirsoft-basic` `_user_base.json` 드로어의 마지막 두 자식이 무조건 렌더되는(`if`·`iteration` 없음) 쇼핑 →
  정보/정책 섹션인지 확인한다. 템플릿이 순서를 바꾸거나 섹션을 추가하면 숨김 규칙을 쓰지 말고 이 계약을 먼저 고친다.
- 다른 번들 모듈·플러그인이 `mobile_nav_drawer` 에 주입하지 않는지 확인한다(주입하면 앵커 앞 칸이 달라진다).
- CSS 숨김 selector 가 정확히 두 개이고 `#mobile_nav_drawer >` 로 시작하는지, 드로어 스타일이 `.rh-drawer-docs`
  아래로만 한정되는지 확인한다.
- `_user_base` 대상 overlay 파일은 여전히 `product-nav.json` 하나다(같은 target 두 파일은 설치 시 덮어쓰기).

## 오프라인 시뮬레이션

```bash
node scripts/taxonomy.mjs --check
npx vitest run
G7_BASE_URL=http://127.0.0.1:18770 node tests/browser/mobile-drawer-simulation.cjs
```

런타임이 실제로 내려준 `page/show` 병합 응답에 이 소스의 overlay 를 설치 계약대로 다시 적용하고, 드로어를 jsdom 으로
그려 빌드된 `dist/css/module.css` 의 드로어 규칙을 적용한다. 서버·DB·게시본은 바꾸지 않는다. 비회원·회원 두 경우에
소유 섹션 1개·링크 11개·숨김 대상(쇼핑, 정보/정책)·보존 섹션·상위 메뉴·푸터·통화 억제를 판정한다.

## 문제 해결

- 드로어에 기본 쇼핑·정보/정책 섹션이 다시 보인다: 모듈 CSS 가 게시되지 않았거나 템플릿 드로어 구조가 바뀐 것이다.
  `npx vitest run resources/js/infoPolicy.test.ts` 의 템플릿 구조 계약부터 확인한다.
- 드로어에 RAON 문서 섹션이 없다: 설치된 `_user_base` overlay 행이 0.4.2 인지(`rh_mobile_drawer_docs` 포함) 확인하고
  `module:update raonslab-product --force` 로 다시 반영한다.
