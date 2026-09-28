#!/usr/bin/env node
/**
 * 배포 전 UI 시뮬레이션: 실행 중인 런타임(구 버전) 응답 위에 이 소스의 layout overlay·모듈 번들·번역을
 * 브라우저 쪽에서만 겹쳐 본다. 서버·DB·빌드 게시본은 바꾸지 않는다.
 *
 * G7 설치 계약을 그대로 따른다: overlay 는 (모듈, target layout) 마다 하나만 저장되므로
 * `persistedOverlays()` 가 고른 manifest 만 적용하고, 같은 target 에 파일이 둘 이상이면 시작 전에 실패한다.
 * (0.4.1 첫 배포에서 파일마다 따로 적용한 시뮬레이션은 1208/0 이었지만 운영은 product-nav 가 덮어써져 실패했다.)
 *
 * 사용: install(context, { baseline }) 을 Playwright BrowserContext 에 건다.
 *   baseline.css / baseline.js = 런타임에 배포된 이 모듈의 dist 내용(예: `git show <ref>:…/dist/css/module.css`)
 */
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');

const MODULE_ROOT = resolve(__dirname, '../..');
const MODULE_ID = 'raonslab-product';
/** 런타임 응답에서 걷어 낼 이 모듈의 이전 overlay 노드(구 버전이 주입한 것). */
const PREVIOUS_NODE_IDS = ['rh_native_page_breadcrumb', 'rh_native_page_side_navigation', 'rh_native_page_docnav', 'rh_mobile_drawer_docs'];

function walk(list, visit) {
  if (!Array.isArray(list)) return;
  for (let index = 0; index < list.length; index += 1) {
    visit(list, index);
    const node = list[index];
    if (node && typeof node === 'object') for (const key of ['children', 'components', 'default']) walk(node[key], visit);
  }
}

function find(root, id) {
  let hit = null;
  walk(root, (list, index) => { if (!hit && list[index]?.id === id) hit = { list, index }; });
  return hit;
}

function applyOverlay(root, overlay) {
  for (const injection of overlay.injections) {
    const hit = find(root, injection.target_id);
    if (!hit) throw new Error(`simulation: target ${injection.target_id} not found`);
    const node = hit.list[hit.index];
    const components = structuredClone(injection.components ?? []);
    if (injection.position === 'inject_props') node.props = { ...(node.props ?? {}), ...injection.props };
    else if (injection.position === 'prepend_child') node.children = [...components, ...(node.children ?? [])];
    else if (injection.position === 'append_child') node.children = [...(node.children ?? []), ...components];
    else if (injection.position === 'prepend') hit.list.splice(hit.index, 0, ...components);
    else if (injection.position === 'append') hit.list.splice(hit.index + 1, 0, ...components);
    else if (injection.position === 'replace') hit.list.splice(hit.index, 1, ...components);
    else throw new Error(`simulation: unsupported position ${injection.position}`);
  }
}

/** 설치 계약대로 저장될 overlay 만 돌려준다. 중복 target 이면 실패한다. */
async function persistedModuleOverlays() {
  const { extensionManifests, persistedOverlays } = await import('../../scripts/taxonomy.mjs');
  const { byTarget, overwritten } = persistedOverlays(extensionManifests());
  if (overwritten.length > 0) {
    throw new Error(`simulation refused: duplicate target layout ${JSON.stringify(overwritten)}`);
  }
  return byTarget;
}

/**
 * 병합된 layout 응답(page/show 는 _user_base 를 상속해 한 응답으로 온다)에 이 모듈의 overlay 를 다시 적용한다.
 * 런타임이 이미 적용한 이 모듈의 이전 노드는 먼저 걷어 낸다.
 */
function patchMergedLayout(body, overlays) {
  const document = JSON.parse(body);
  const root = document.data.components;
  for (const id of PREVIOUS_NODE_IDS) {
    for (let hit = find(root, id); hit; hit = find(root, id)) hit.list.splice(hit.index, 1);
  }
  const gnav = find(root, 'rh_gnav_root');
  if (gnav) gnav.list.splice(gnav.index, 1);

  for (const target of ['_user_base', 'page/show']) {
    const overlay = overlays.get(target)?.content;
    if (overlay) applyOverlay(root, overlay);
  }
  return JSON.stringify(document);
}

const safe = (handler) => async (route, request) => {
  try {
    await handler(route, request);
  } catch (error) {
    if (!/closed|disposed/.test(String(error))) throw error;
  }
};

async function install(context, { baseline }) {
  const overlays = await persistedModuleOverlays();
  const lang = {
    ko: JSON.parse(readFileSync(resolve(MODULE_ROOT, 'resources/lang/ko.json'), 'utf8')),
    en: JSON.parse(readFileSync(resolve(MODULE_ROOT, 'resources/lang/en.json'), 'utf8')),
  };
  const nextCss = readFileSync(resolve(MODULE_ROOT, 'dist/css/module.css'), 'utf8').trim();
  const nextJs = readFileSync(resolve(MODULE_ROOT, 'dist/js/module.iife.js'), 'utf8').trim();

  await context.route(/\/api\/layouts\/[^/]+\/page\/show\.json/, safe(async (route) => {
    const response = await route.fetch();
    await route.fulfill({ response, body: patchMergedLayout(await response.text(), overlays) });
  }));
  await context.route(/bundles\/modules\.css/, safe(async (route) => {
    const response = await route.fetch();
    const text = await response.text();
    if (!text.includes(baseline.css.trim())) throw new Error('simulation: baseline css segment not found');
    await route.fulfill({ response, body: text.replace(baseline.css.trim(), () => nextCss) });
  }));
  await context.route(/bundles\/modules\.js/, safe(async (route) => {
    const response = await route.fetch();
    const text = await response.text();
    if (!text.includes(baseline.js.trim())) throw new Error('simulation: baseline js segment not found');
    await route.fulfill({ response, body: text.replace(baseline.js.trim(), () => nextJs) });
  }));
  await context.route(/templates\/[^/]+\/lang\/(ko|en)\.json/, safe(async (route, request) => {
    const locale = request.url().match(/lang\/(ko|en)\.json/)[1];
    const response = await route.fetch();
    const data = JSON.parse(await response.text());
    data[MODULE_ID] = { ...data[MODULE_ID], nav: lang[locale].nav, native_page: lang[locale].native_page };
    await route.fulfill({ response, body: JSON.stringify(data) });
  }));
}

module.exports = { install, patchMergedLayout, persistedModuleOverlays, applyOverlay };
