// Public runtime verification; PLAYWRIGHT_MODULE may point to an operator install.
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');
(async () => {
  const browser = await chromium.launch({headless:true, ...(process.env.CHROMIUM_PATH ? {executablePath:process.env.CHROMIUM_PATH} : {})});
  const base = process.env.G7_BROWSER_BASE_URL || 'http://g7.3.34.73.254.sslip.io';
  const out = process.env.G7_EVIDENCE_DIR || '/tmp/raon-header-evidence';
  fs.mkdirSync(out,{recursive:true});
  const results=[];
  try {
    for (const [width,height] of [[360,800],[390,844],[412,915],[1440,900]]) {
      const context=await browser.newContext({viewport:{width,height},userAgent:'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'});
      const page=await context.newPage(); const errors=[];
      page.on('pageerror',e=>errors.push(e.message));
      await page.goto(base,{waitUntil:'networkidle'});
      const header=page.locator(width<768?'#mobile_header':'[data-raon-header]');
      await header.waitFor({state:'visible'});
      const brand=width<768?header.getByRole('link',{name:'RAON Agent Factory'}):header.getByRole('button',{name:/RAON\s+AGENT FACTORY/});
      await brand.waitFor({state:'visible'});
      assert(! (await header.innerText()).includes('그누보드7'));
      const metrics=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,hero:document.querySelector('h1')?.textContent}));
      assert(!metrics.overflow);
      const box=await header.boundingBox();
      if(width<768) {
        assert.equal(box.height,56);
        const right=await page.locator('#mobile_header_right').boundingBox();
        const logo=await brand.boundingBox(); assert(logo.x+logo.width<=right.x);
        const menu=page.locator('#mobile_menu_toggle');
        await menu.click(); await page.locator('#mobile_nav_drawer').waitFor({state:'visible'});
        await page.locator('#mobile_nav_drawer button').first().click();
        await page.waitForFunction(()=>document.querySelector('#mobile_nav_drawer')?.classList.contains('translate-x-full'));

      }
      const theme=header.getByRole('button',{name:'Toggle theme'});
      await theme.click();
      await header.getByRole('button',{name:/라이트|Light/,exact:false}).click();
      assert(!(await page.evaluate(()=>document.documentElement.classList.contains('dark'))));
      await theme.click();
      await header.getByRole('button',{name:/다크|Dark/,exact:false}).click();
      assert(await page.evaluate(()=>document.documentElement.classList.contains('dark')));
      await page.waitForTimeout(400);
      await page.screenshot({path:`${out}/${width}.png`,fullPage:false});
      results.push({width,height,headerHeight:box.height,brand:await brand.innerText(),overflow:false,jsErrors:errors,hero:metrics.hero,homeLink:true,themeLightDark:true,mobileMenuOpenClose:width<768});
      assert.equal(errors.length,0);
      await brand.click(); await page.waitForLoadState('networkidle'); assert.equal(new URL(page.url()).pathname,'/');
      if(width<768) {
        await page.locator('#mobile_user_btn').click(); await page.waitForURL(/\/login(?:\?|$)/);
        await page.locator('input[type=password]').waitFor({state:'visible'});
        await page.locator('#mobile_header_left').click(); await page.waitForURL(base+'/');
      }
      if(width>=768) {
        await header.getByRole('button',{name:'로그인',exact:true}).click();
        await page.waitForURL(/\/login(?:\?|$)/);
        await page.locator('input[type=password]').waitFor({state:'visible'});
      }
      results.at(-1).loginNavigation=true;
      await context.close();
    }
    const context=await browser.newContext({viewport:{width:390,height:844},userAgent:'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'});
    const page=await context.newPage(); const regression=[];
    for (const path of ['/register','/boards','/board/community','/page/service','/page/cases']) {
      const response=await page.goto(base+path,{waitUntil:'networkidle'});
      const text=await page.locator('body').innerText();
      assert.equal(response.status(),200);
      const brand=page.locator('#mobile_header_left');
      assert.equal(await brand.getAttribute('aria-label'),'RAON Agent Factory');
      regression.push({path,http:response.status(),bodyChars:text.length,existingPageNotFound:path.startsWith('/page/')});
    }
    await context.close();
    fs.writeFileSync(`${out}/browser.json`,JSON.stringify({base,results,regression},null,2));
    console.log(JSON.stringify(results,null,2));
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});
