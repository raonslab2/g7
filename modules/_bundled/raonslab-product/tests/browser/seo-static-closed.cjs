#!/usr/bin/env node
/**
 * 홈 봇(SEO) 응답의 닫힘 표면 검사 — HOME V2.1 F4.
 *
 * 로케일은 URL 로만 고른다: ko = `BASE/`, en = `BASE/?locale=en`.
 * Accept-Language 는 중립값 `*` 만 보낸다(보조). 코어 SetLocale 이 SeoMiddleware 보다 먼저 Accept-Language 로
 * 기본 로케일을 바꾸므로, `?locale=en` 에 `Accept-Language: en` 을 함께 보내면 "기본 로케일 명시" 로 판정되어 301 이 된다.
 *
 * 같은 URL 을 봇 UA 로 두 번 받아 원시 HTML 을 확인한다.
 *   - 첫 응답 X-SEO-Cache 정확히 MISS, 둘째 정확히 HIT (리다이렉트는 따라가지 않고 실패로 본다)
 *   - 두 응답 SHA-256 동일(바이트 동일)
 *   - <html lang> 이 요청 로케일
 *   - Hero 행동 묶음(.rh-actions) 정확히 1개, 목적지는 #rh-case · #rh-process
 *   - 열림 상담 행동("AI 에이전트 구축 상담" / "Talk to us about an AI agent") 0개
 *   - 상담 패널에 정적 닫힘 안내(제목)가 있고 입력 필드·연락 수단 0개
 *
 * 사용(배포 후, 실제 런타임):
 *   홈 SEO 캐시를 비운 직후(seo:clear --layout=home) 실행해야 첫 응답이 MISS 다.
 *   G7_BASE_URL=http://203.245.29.156:58770 node tests/browser/seo-static-closed.cjs ko
 *   G7_BASE_URL=http://203.245.29.156:58770 node tests/browser/seo-static-closed.cjs en
 *
 * 격리 후보 렌더 결과 검사(배포 전): 새 캐시로 두 번 렌더한 HTML 과 관측한 캐시 헤더를 넘긴다. 같은 MISS→HIT 규칙을 적용한다.
 *   RH_SEO_FILES=first.html,second.html RH_SEO_CACHE=MISS,HIT node tests/browser/seo-static-closed.cjs en
 * GET 만 보낸다.
 */
const fs = require('node:fs');
const crypto = require('node:crypto');

const UA = 'Googlebot/2.1 (+http://www.google.com/bot.html)';
const ACCEPT_LANGUAGE = '*';
const COPY = {
  ko: { title: '온라인 상담 접수 준비 중입니다', cta: 'AI 에이전트 구축 상담' },
  en: { title: 'Online consultation intake is being prepared', cta: 'Talk to us about an AI agent' },
};

/** 로케일별 요청 URL. 로케일은 URL 로만 정한다. */
function urlFor(base, locale) {
  const root = String(base).replace(/\/$/, '');
  return locale === 'en' ? `${root}/?locale=en` : `${root}/`;
}

function requestHeaders() {
  return { 'User-Agent': UA, 'Accept-Language': ACCEPT_LANGUAGE };
}

function sha256(text) {
  return crypto.createHash('sha256').update(text).digest('hex');
}

function inspect(html, locale) {
  const panel = (html.match(/<div class="rh-consult-panel"[^>]*>([\s\S]*?)<\/div>\s*<\/div>/) || [])[1] ?? '';
  return {
    lang: (html.match(/<html[^>]*\blang="([^"]+)"/) || [])[1] ?? null,
    actionGroups: (html.match(/<div class="rh-actions"/g) || []).length,
    heroHrefs: [...html.matchAll(/<a [^>]*class="rh-action[^"]*"[^>]*href="([^"]+)"|<a [^>]*href="([^"]+)"[^>]*class="rh-action[^"]*"/g)].map((m) => m[1] ?? m[2]),
    openCta: html.split(COPY[locale].cta).length - 1,
    staticClosed: /class="rh-state rh-consult-static-closed"/.test(panel) && panel.includes(COPY[locale].title),
    panelFields: (panel.match(/<(input|textarea|select|form|button)\b/g) || []).length,
    contact: (panel.match(/mailto:|tel:/g) || []).length,
  };
}

/** 두 응답을 판정한다. runs: [{ status, cache, html }] (요청 순서) */
function evaluate(runs, locale) {
  const failures = [];
  const inspected = runs.map((r) => ({ status: r.status, cache: r.cache, location: r.location ?? null, sha256: sha256(r.html), ...inspect(r.html, locale) }));
  if (inspected.length !== 2) failures.push(`응답 수 ${inspected.length}`);
  inspected.forEach((r, i) => {
    if (r.status !== 200) failures.push(`#${i} status ${r.status}${r.location ? ` → ${r.location}` : ''}`);
    if (r.lang !== locale) failures.push(`#${i} html lang ${r.lang}`);
    if (r.actionGroups !== 1) failures.push(`#${i} Hero 행동 묶음 ${r.actionGroups}`);
    if (JSON.stringify(r.heroHrefs) !== JSON.stringify(['#rh-case', '#rh-process'])) failures.push(`#${i} Hero 목적지 ${JSON.stringify(r.heroHrefs)}`);
    if (r.openCta !== 0) failures.push(`#${i} 열림 상담 행동 ${r.openCta}`);
    if (!r.staticClosed) failures.push(`#${i} 정적 닫힘 안내 없음`);
    if (r.panelFields !== 0 || r.contact !== 0) failures.push(`#${i} 패널 입력 ${r.panelFields} · 연락 수단 ${r.contact}`);
  });
  if (inspected[0]?.cache !== 'MISS') failures.push(`첫 응답 X-SEO-Cache=${inspected[0]?.cache} (MISS 여야 함 — 홈 SEO 캐시를 비운 직후 실행)`);
  if (inspected[1]?.cache !== 'HIT') failures.push(`두 번째 응답 X-SEO-Cache=${inspected[1]?.cache} (HIT 여야 함)`);
  if (inspected.length === 2 && inspected[0].sha256 !== inspected[1].sha256) failures.push('두 응답 바이트 불일치');
  return { runs: inspected, failures };
}

async function main() {
  const locale = process.argv[2] === 'en' ? 'en' : 'ko';
  const base = process.env.G7_BASE_URL || 'http://127.0.0.1:18770';
  const url = urlFor(base, locale);
  const runs = [];
  let mode = 'live';
  if (process.env.RH_SEO_FILES) {
    mode = 'files';
    const files = process.env.RH_SEO_FILES.split(',');
    const caches = (process.env.RH_SEO_CACHE || '').split(',');
    files.forEach((file, i) => runs.push({ status: 200, cache: caches[i] || null, html: fs.readFileSync(file, 'utf8') }));
  } else {
    for (let i = 0; i < 2; i += 1) {
      const res = await fetch(url, { headers: requestHeaders(), redirect: 'manual' });
      runs.push({ status: res.status, cache: res.headers.get('x-seo-cache'), location: res.headers.get('location'), html: await res.text() });
    }
  }
  const result = evaluate(runs, locale);
  console.log(JSON.stringify({ mode, locale, url, headers: requestHeaders(), ...result }, null, 2));
  process.exitCode = result.failures.length ? 1 : 0;
}

module.exports = { urlFor, requestHeaders, inspect, evaluate, COPY };

if (require.main === module) main();
