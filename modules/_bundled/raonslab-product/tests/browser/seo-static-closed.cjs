#!/usr/bin/env node
/**
 * 홈 봇(SEO) 응답의 닫힘 표면 검사 — HOME V2.1 F4.
 *
 * 같은 URL 을 봇 UA 로 두 번 받아(최초 MISS 또는 기존 HIT, 이어서 HIT) 원시 HTML 에서 확인한다.
 *   - Hero 행동 묶음(.rh-actions) 정확히 1개, 목적지는 #rh-case · #rh-process
 *   - 열림 상담 행동(#rh-consult 행동 링크·"AI 에이전트 구축 상담") 0개
 *   - 상담 패널에 정적 닫힘 안내(제목·안내)가 있고 입력 필드·연락 수단 0개
 *   - 두 응답 바이트 동일
 * 배포 후 실제 런타임에 대해 실행한다. 쓰기 요청은 보내지 않는다(GET 만).
 *
 * 사용: G7_BASE_URL=http://127.0.0.1:18770 node tests/browser/seo-static-closed.cjs [ko|en]
 * 배포 전 후보 렌더 결과(저장된 두 HTML)를 검사할 때: RH_SEO_FILES=first.html,second.html (캐시 헤더 검사는 생략)
 */
const fs = require('node:fs');
const crypto = require('node:crypto');

const BASE = (process.env.G7_BASE_URL || 'http://127.0.0.1:18770').replace(/\/$/, '');
const LOCALE = process.argv[2] === 'en' ? 'en' : 'ko';
const UA = 'Googlebot/2.1 (+http://www.google.com/bot.html)';
const COPY = {
  ko: { title: '온라인 상담 접수 준비 중입니다', cta: 'AI 에이전트 구축 상담' },
  en: { title: 'Online consultation intake is being prepared', cta: 'Talk to us about an AI agent' },
};

function inspect(html) {
  const panel = (html.match(/<div class="rh-consult-panel"[^>]*>([\s\S]*?)<\/div>\s*<\/div>/) || [])[1] ?? '';
  return {
    actionGroups: (html.match(/<div class="rh-actions"/g) || []).length,
    heroHrefs: [...html.matchAll(/<a [^>]*class="rh-action[^"]*"[^>]*href="([^"]+)"|<a [^>]*href="([^"]+)"[^>]*class="rh-action[^"]*"/g)].map((m) => m[1] ?? m[2]),
    openCta: html.split(COPY[LOCALE].cta).length - 1,
    staticClosed: /class="rh-state rh-consult-static-closed"/.test(panel) && panel.includes(COPY[LOCALE].title),
    panelFields: (panel.match(/<(input|textarea|select|form|button)\b/g) || []).length,
    contact: (panel.match(/mailto:|tel:/g) || []).length,
  };
}

(async () => {
  const runs = [];
  const files = process.env.RH_SEO_FILES ? process.env.RH_SEO_FILES.split(',') : null;
  for (let i = 0; files && i < 2; i += 1) {
    const html = fs.readFileSync(files[i], 'utf8');
    runs.push({ status: 200, cache: i === 1 ? 'HIT' : 'FILE', file: files[i], sha256: crypto.createHash('sha256').update(html).digest('hex'), ...inspect(html) });
  }
  for (let i = 0; !files && i < 2; i += 1) {
    const res = await fetch(`${BASE}/`, { headers: { 'User-Agent': UA, 'Accept-Language': LOCALE === 'en' ? 'en-US,en;q=0.9' : 'ko-KR,ko;q=0.9' } });
    const html = await res.text();
    runs.push({ status: res.status, cache: res.headers.get('x-seo-cache'), sha256: crypto.createHash('sha256').update(html).digest('hex'), ...inspect(html) });
  }
  const failures = [];
  runs.forEach((r, i) => {
    if (r.status !== 200) failures.push(`#${i} status ${r.status}`);
    if (r.actionGroups !== 1) failures.push(`#${i} Hero 행동 묶음 ${r.actionGroups}`);
    if (JSON.stringify(r.heroHrefs) !== JSON.stringify(['#rh-case', '#rh-process'])) failures.push(`#${i} Hero 목적지 ${JSON.stringify(r.heroHrefs)}`);
    if (r.openCta !== 0) failures.push(`#${i} 열림 상담 행동 ${r.openCta}`);
    if (!r.staticClosed) failures.push(`#${i} 정적 닫힘 안내 없음`);
    if (r.panelFields !== 0 || r.contact !== 0) failures.push(`#${i} 패널 입력 ${r.panelFields} · 연락 수단 ${r.contact}`);
  });
  if (runs[1].cache !== 'HIT') failures.push(`두 번째 응답 X-SEO-Cache=${runs[1].cache}`);
  if (runs[0].sha256 !== runs[1].sha256) failures.push('두 응답 바이트 불일치');
  console.log(JSON.stringify({ base: BASE, locale: LOCALE, runs, failures }, null, 2));
  process.exitCode = failures.length ? 1 : 0;
})();
