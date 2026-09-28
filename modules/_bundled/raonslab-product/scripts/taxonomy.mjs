#!/usr/bin/env node
/**
 * 정보·정책 문서 분류(resources/taxonomy/info-policy.json)를 Layout Extension JSON 에 반영한다.
 *
 * - native-page.json 은 분류에서 전체를 생성한다(breadcrumb·모바일 문서 메뉴·데스크톱 우측 메뉴).
 * - product-nav.json 은 상위 드롭다운 두 목록과 product footer 의 정보·정책 linkGroups 만 교체한다.
 *
 * 사용: `node scripts/taxonomy.mjs` (쓰기) / `node scripts/taxonomy.mjs --check` (불일치 시 exit 1).
 * vitest 드리프트 테스트가 같은 함수를 호출해 커밋된 JSON 과 비교한다.
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const moduleRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');
export const TAXONOMY_PATH = resolve(moduleRoot, 'resources/taxonomy/info-policy.json');
export const NATIVE_PAGE_PATH = resolve(moduleRoot, 'resources/extensions/native-page.json');
export const PRODUCT_NAV_PATH = resolve(moduleRoot, 'resources/extensions/product-nav.json');

const t = (key) => `$t:raonslab-product.${key}`;
const pagePath = (item) => `/page/${item.slug}`;
const navigate = (path) => [{ type: 'click', handler: 'navigate', params: { path } }];

/** 분류에 속한 slug 를 표시 순서대로 돌려준다. */
export function taxonomySlugs(taxonomy) {
  return taxonomy.groups.flatMap((group) => group.items.map((item) => item.slug));
}

/** 레이아웃 표현식용 slug 배열 리터럴 (`['about','service']`). */
function slugList(slugs) {
  return `[${slugs.map((slug) => `'${slug}'`).join(',')}]`;
}

function slugCondition(slugs) {
  return `{{${slugList(slugs)}.includes(page?.data?.slug)}}`;
}

function docGroups(taxonomy, nodePrefix, domPrefix) {
  return taxonomy.groups.flatMap((group) => [
    {
      id: `${nodePrefix}_${group.key}_label`,
      type: 'basic',
      name: 'P',
      props: { className: 'rh-side-label', id: `${domPrefix}-${group.key}-label` },
      text: t(group.label),
    },
    {
      id: `${nodePrefix}_${group.key}_list`,
      type: 'basic',
      name: 'Ul',
      props: { className: 'rh-side-list', 'aria-labelledby': `${domPrefix}-${group.key}-label` },
      children: group.items.map((item) => ({
        id: `${nodePrefix}_${group.key}_${item.key}_item`,
        type: 'basic',
        name: 'Li',
        children: [
          {
            id: `${nodePrefix}_${group.key}_${item.key}`,
            type: 'basic',
            name: 'A',
            props: { className: 'rh-side-link', href: pagePath(item), 'data-rh-nav-path': pagePath(item) },
            text: t(item.label),
            actions: navigate(pagePath(item)),
          },
        ],
      })),
    },
  ]);
}

/** page/show overlay 전체를 분류에서 만든다. */
export function buildNativePage(taxonomy) {
  const slugs = taxonomySlugs(taxonomy);
  const condition = slugCondition(slugs);

  const groupCrumbs = taxonomy.groups.map((group) => ({
    id: `rh_native_page_breadcrumb_${group.key}`,
    type: 'basic',
    name: 'Span',
    if: slugCondition(group.items.map((item) => item.slug)),
    props: { className: 'rh-crumb-group' },
    text: t(group.label),
  }));

  return {
    _comment: 'scripts/taxonomy.mjs 가 resources/taxonomy/info-policy.json 에서 생성한다. 직접 수정하지 말 것.',
    target_layout: 'page/show',
    priority: 25,
    injections: [
      {
        target_id: 'page_content_card',
        position: 'inject_props',
        props: {
          className: `{{${slugList(slugs)}.includes(page?.data?.slug) ? 'rh-native-page-card' : 'bg-white dark:bg-gray-800 rounded-lg shadow p-10'}}`,
        },
      },
      {
        target_id: 'page_content_card',
        position: 'prepend_child',
        components: [
          {
            id: 'rh_native_page_breadcrumb',
            type: 'basic',
            name: 'Nav',
            if: condition,
            props: { className: 'rh-native-breadcrumb', 'aria-label': t('native_page.breadcrumb_label') },
            children: [
              {
                type: 'basic',
                name: 'A',
                props: { href: '/', className: 'rh-crumb-link' },
                text: t('footer.home'),
                actions: navigate('/'),
              },
              { type: 'basic', name: 'Span', props: { 'aria-hidden': 'true' }, text: '/' },
              ...groupCrumbs,
              { type: 'basic', name: 'Span', props: { 'aria-hidden': 'true' }, text: '/' },
              { type: 'basic', name: 'Span', props: { 'aria-current': 'page' }, text: "{{page?.data?.title ?? ''}}" },
            ],
          },
        ],
      },
      {
        target_id: 'page_html_content',
        position: 'prepend',
        components: [
          {
            id: 'rh_native_page_docnav',
            comment: '모바일·태블릿 문서 메뉴: 기본 닫힘 disclosure. 데스크톱에서는 CSS 로 숨기고 우측 메뉴를 쓴다.',
            type: 'basic',
            name: 'Div',
            if: condition,
            props: { className: 'rh-docnav-mobile' },
            children: [
              {
                id: 'rh_native_page_docnav_toggle',
                type: 'basic',
                name: 'Button',
                props: {
                  className: 'rh-docnav-toggle',
                  type: 'button',
                  id: 'rh-docnav-toggle',
                  'aria-expanded': 'false',
                  'aria-controls': 'rh-docnav-panel',
                  'data-rh-docnav-toggle': 'true',
                },
                children: [
                  {
                    type: 'basic',
                    name: 'Span',
                    props: { className: 'rh-docnav-toggle-text' },
                    children: [
                      { type: 'basic', name: 'Span', props: { className: 'rh-docnav-toggle-label' }, text: t('native_page.menu_toggle') },
                      { type: 'basic', name: 'Span', props: { className: 'rh-docnav-toggle-current' }, text: "{{page?.data?.title ?? ''}}" },
                    ],
                  },
                  { type: 'basic', name: 'Span', props: { className: 'rh-gnav-caret', 'aria-hidden': 'true' } },
                ],
              },
              {
                id: 'rh_native_page_docnav_panel',
                type: 'basic',
                name: 'Nav',
                props: {
                  className: 'rh-docnav-panel',
                  id: 'rh-docnav-panel',
                  hidden: true,
                  'aria-label': t('native_page.navigation_label'),
                },
                children: docGroups(taxonomy, 'rh_native_page_docnav', 'rh-docnav-mobile'),
              },
            ],
          },
        ],
      },
      {
        target_id: 'page_content_card',
        position: 'append_child',
        components: [
          {
            id: 'rh_native_page_side_navigation',
            comment: '데스크톱 우측 문서 메뉴. DOM·탭 순서상 본문 뒤에 둔다.',
            type: 'basic',
            name: 'Nav',
            if: condition,
            props: { className: 'rh-native-side', 'aria-label': t('native_page.navigation_label') },
            children: docGroups(taxonomy, 'rh_native_page_side', 'rh-docnav-side'),
          },
        ],
      },
    ],
  };
}

function gnavItem(group, item) {
  const base = `rh_gnav_${group.key}_${item.key}`;
  const path = pagePath(item);
  return {
    id: `${base}_item`,
    type: 'basic',
    name: 'Li',
    props: { className: 'rh-gnav-item' },
    children: [
      {
        id: base,
        type: 'basic',
        name: 'A',
        props: { className: 'rh-gnav-link', href: path, 'data-rh-nav-path': path },
        children: [
          { id: `${base}_title`, type: 'basic', name: 'Span', props: { className: 'rh-gnav-link-title' }, text: t(item.label) },
          { id: `${base}_desc`, type: 'basic', name: 'Span', props: { className: 'rh-gnav-link-desc' }, text: t(item.description) },
        ],
        actions: navigate(path),
      },
    ],
  };
}

function findNode(value, id) {
  if (Array.isArray(value)) {
    for (const child of value) {
      const found = findNode(child, id);
      if (found) return found;
    }
    return null;
  }
  if (!value || typeof value !== 'object') return null;
  if (value.id === id) return value;
  for (const child of Object.values(value)) {
    const found = findNode(child, id);
    if (found) return found;
  }
  return null;
}

/** product-nav.json 의 분류 종속 부분만 교체한 사본을 돌려준다. */
export function applyProductNav(productNav, taxonomy) {
  const next = structuredClone(productNav);

  for (const group of taxonomy.groups) {
    const list = findNode(next, `rh_gnav_${group.key}_list`);
    if (!list) throw new Error(`product-nav.json 에 rh_gnav_${group.key}_list 가 없습니다.`);
    list.children = group.items.map((item) => gnavItem(group, item));
  }

  const footer = next.injections.find((injection) => injection.target_id === 'footer' && injection.position === 'inject_props');
  if (!footer) throw new Error('product-nav.json 에 footer linkGroups 주입이 없습니다.');
  footer.props.linkGroups = footer.props.linkGroups.map((linkGroup) => {
    const group = taxonomy.groups.find((candidate) => linkGroup.title === t(candidate.label));
    if (!group) return linkGroup;
    return {
      title: linkGroup.title,
      links: group.items.map((item) => ({ label: t(item.label), href: pagePath(item) })),
    };
  });

  for (const group of taxonomy.groups) {
    if (!footer.props.linkGroups.some((linkGroup) => linkGroup.title === t(group.label))) {
      throw new Error(`footer linkGroups 에 ${group.label} 그룹이 없습니다.`);
    }
  }

  return next;
}

export const serialize = (value) => `${JSON.stringify(value, null, 2)}\n`;

const readJson = (path) => JSON.parse(readFileSync(path, 'utf8'));

export function generated() {
  const taxonomy = readJson(TAXONOMY_PATH);
  return {
    [NATIVE_PAGE_PATH]: serialize(buildNativePage(taxonomy)),
    [PRODUCT_NAV_PATH]: serialize(applyProductNav(readJson(PRODUCT_NAV_PATH), taxonomy)),
  };
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const check = process.argv.includes('--check');
  let drift = false;
  for (const [path, content] of Object.entries(generated())) {
    const current = readFileSync(path, 'utf8');
    if (current === content) continue;
    if (check) {
      drift = true;
      console.error(`drift: ${path}`);
    } else {
      writeFileSync(path, content);
      console.log(`updated: ${path}`);
    }
  }
  if (drift) process.exit(1);
}
