// Public screenshots and sanitized statuses only. Administrator token stays in memory.
const fs = require('fs');
const path = require('path');
const { chromium } = require(process.env.G7_PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.G7_BROWSER_BASE || 'http://g7.3.34.73.254.sslip.io';
const output = process.argv[2];
if (!output) throw new Error('Output directory required');
fs.mkdirSync(output, { recursive: true });
const slugs = ['about','service','cases','technology','faq','contact','privacy','terms','refund','ai-workspace-policy','open-source'];
// G7 deliberately serves SEO/SSR to HeadlessChrome. Use a normal browser UA for
// interactive SPA verification; bot SSR is verified separately.
const userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36';
const cleanUrl = url => { try { return new URL(url).pathname; } catch { return 'invalid-url'; } };
(async () => {
  const browser = await chromium.launch({ headless: true });
  const results = { base, viewports: [], administrator: {} };
  try {
    for (const viewport of [{width:390,height:844},{width:1440,height:900}]) {
      // Independent fresh-browser boots generate many API calls from the same IP.
      // Respect the existing consultation config throttle between viewport runs.
      if(results.viewports.length) await new Promise(resolve=>setTimeout(resolve,45000));
      const context = await browser.newContext({ viewport, locale: 'ko-KR', userAgent });
      await context.addInitScript(() => localStorage.setItem('g7_locale', 'ko'));
      const page = await context.newPage();
      let errors = [], failed = [];
      page.on('pageerror', error => errors.push({type:'pageerror', message:error.message}));
      page.on('console', message => { if (message.type() === 'error') errors.push({type:'console', message:message.text()}); });
      page.on('response', response => { if(response.status() >= 400) failed.push({path:cleanUrl(response.url()),status:response.status()}); });
      const rows = [];
      for (const route of ['/',...slugs.map(slug=>'/page/'+slug),'/boards','/board/community','/board/questions','/board/notice']) {
        errors=[]; failed=[];
        const response = route==='/'
          ? await page.goto(base+route, {waitUntil:'networkidle',timeout:45000})
          : await page.request.get(base+route);
        if(route!=='/') {
          await page.evaluate(route=>window.G7Core.dispatch({handler:'navigate',params:{path:route}}),route);
          await page.waitForURL(base+route);await page.waitForTimeout(500);await page.waitForLoadState('networkidle');
        }
        const text = await page.locator('body').innerText();
        const geometry = await page.evaluate(() => ({overflow:document.documentElement.scrollWidth>innerWidth+1,
          brokenImages:[...document.images].filter(image=>image.getClientRects().length && image.currentSrc && image.complete && image.naturalWidth===0).map(image=>image.getAttribute('src') || '(empty-src)'),
          brand:[...document.querySelectorAll('a')].some(a=>/RAON/.test(a.textContent) && /AGENT FACTORY/.test(a.textContent)),
          theme:document.documentElement.className}));
        const row = {route,status:response.status(),...geometry,errors:[...errors],failed:[...failed],notFound:/페이지를 찾을 수 없|Page not found|404 Not Found/.test(text)};
        if (route==='/page/service') {
          row.originalServiceCopy = text.includes('업무 하나를 실제로 동작시키는 데서 시작해');
          await page.screenshot({path:path.join(output,`service-${viewport.width}.png`),fullPage:true});
        }
        if (route==='/') await page.screenshot({path:path.join(output,`home-${viewport.width}.png`),fullPage:true});
        rows.push(row);
      }
      await page.evaluate(()=>window.G7Core.dispatch({handler:'navigate',params:{path:'/'}}));
      await page.waitForURL(base+'/');await page.waitForTimeout(500);await page.waitForLoadState('networkidle');
      const navigation = [];
      // Follow actual UI links; open the native mobile drawer / desktop disclosure as needed.
      for (const route of ['/page/service','/page/technology','/page/cases','/page/contact']) {
        if(viewport.width<768) await page.locator('#mobile_menu_toggle').click();
        const selector=viewport.width<768?`[data-rh-mobile-drawer-docs] [data-rh-nav-path="${route}"]`:`#rh_gnav_root [data-rh-nav-path="${route}"]:visible`;
        let link=page.locator(selector).first();
        if (!await link.count()) {
          const triggers=page.locator('[data-rh-menu-trigger]:visible');
          for(let i=0;i<await triggers.count();i++) {
            await triggers.nth(i).click();
            if(await page.locator(selector).count()) break;
          }
          link=page.locator(selector).first();
        }
        if (!await link.count()) {
          // Current brand header owns the mobile menu button; expose drawer by accessible name.
          const buttons=page.getByRole('button',{name:/메뉴|menu/i});
          for(let i=0;i<await buttons.count();i++) {
            if(await buttons.nth(i).isVisible()) { await buttons.nth(i).click(); break; }
          }
          link=page.locator(selector).first();
        }
        if(await link.count()) {
          await link.click(); await page.waitForTimeout(500);
          await page.waitForLoadState('networkidle');
          navigation.push({route,reached:cleanUrl(page.url())===route});
        } else navigation.push({route,reached:false});
      }
      await page.evaluate(()=>window.G7Core.dispatch({handler:'navigate',params:{path:'/'}}));
      await page.waitForURL(base+'/');await page.waitForTimeout(500);await page.waitForLoadState('networkidle');
      const community=page.locator('#raon_home_more_community');
      if(await community.count()) {
        await community.click();await page.waitForLoadState('networkidle');
        navigation.push({route:'/board/community',reached:cleanUrl(page.url())==='/board/community'});
      } else navigation.push({route:'/board/community',reached:false});
      results.viewports.push({viewport,routes:rows,navigation});
      await context.close();
    }
    const admin = await browser.newContext({viewport:{width:1440,height:900}, userAgent});
    const token=fs.readFileSync('/etc/g7-product/verification-token','utf8').trim();
    await admin.addInitScript(token=>{localStorage.setItem('auth_token',token);localStorage.setItem('g7_locale','ko');},token);
    const page=await admin.newPage();
    const serviceId=JSON.parse(fs.readFileSync(path.join(output,'applied.json'),'utf8')).mapping.pages['7'];
    for(const route of ['/admin/pages',`/admin/pages/${serviceId}`,'/admin/board/raon-consultations']) {
      const errors=[];page.on('pageerror',error=>errors.push(error.message));
      const response=await page.goto(base+route,{waitUntil:'networkidle',timeout:45000});
      results.administrator[route]={status:response.status(),onAdminRoute:cleanUrl(page.url()).startsWith('/admin/'),pageErrors:errors};
    }
    await admin.close();
    results.pass=results.viewports.every(v=>v.routes.every(r=>r.status===200&&!r.overflow&&!r.notFound&&!r.errors.length&&!r.failed.length&&!r.brokenImages.length&&r.brand&&(r.route!=='/page/service'||r.originalServiceCopy))&&v.navigation.every(n=>n.reached)) && Object.values(results.administrator).every(r=>r.status===200&&r.onAdminRoute&&!r.pageErrors.length);
    fs.writeFileSync(path.join(output,'browser.json'),JSON.stringify(results,null,2)+'\n');
    process.exitCode=results.pass?0:1;
  } finally {await browser.close();}
})().catch(error=>{ console.error(error.message);process.exitCode=1; });
