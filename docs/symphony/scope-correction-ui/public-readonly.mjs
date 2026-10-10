// Read-only current loopback public journey. No login, cookies read, fixture or service mutations.
import fs from 'node:fs';
import crypto from 'node:crypto';
import {execFileSync} from 'node:child_process';
import {chromium} from '/tmp/w04-4d7d-pw/node_modules/playwright/index.mjs';
const base='http://127.0.0.1:18871', out='docs/symphony/scope-correction-ui';
const result={started:new Date().toISOString(),source:execFileSync('git',['rev-parse','HEAD'],{encoding:'utf8'}).trim(),checks:[],screens:[],metrics:[]};
fs.mkdirSync(out,{recursive:true});
const hash=b=>crypto.createHash('sha256').update(b).digest('hex');
const browser=await chromium.launch({executablePath:'/home/ubuntu/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome',args:['--disable-background-networking']});
result.chromium=browser.version();
const flush=()=>fs.writeFileSync(out+'/public-readonly.json',JSON.stringify(result,null,2)+'\n');
async function check(width,name,fn){try{result.checks.push({width,name,status:'PASS',data:await fn()});}catch(e){result.checks.push({width,name,status:'FAIL',error:String(e.message).replace(/[\w.+-]+@[\w.-]+\.[\w-]+/g,'[EMAIL]').slice(0,600)});}flush();console.log(width,name,result.checks.at(-1).status);}
for(const width of [390,1440]){
 const context=await browser.newContext({viewport:{width,height:1000},locale:'ko-KR',hasTouch:width===390,isMobile:width===390});
 const m={width,external_attempts:[],pageerrors:[],http:[],assets:[],trusted:[]};result.metrics.push(m);
 await context.route('**/*',async route=>{const req=route.request(),u=new URL(req.url());if(['http:','https:'].includes(u.protocol)&&(u.origin!==base||!['GET','HEAD'].includes(req.method()))){m.external_attempts.push({path:u.pathname,origin:u.origin,method:req.method()});return route.abort('blockedbyclient');}return route.continue();});
 await context.addInitScript(()=>{window.__uiInputs=[];document.addEventListener('click',e=>{const n=e.target instanceof Element?e.target.closest('[data-testid]'):null;window.__uiInputs.push({trusted:e.isTrusted,testid:n?.getAttribute('data-testid')});},true);});
 const p=await context.newPage();p.setDefaultTimeout(18000);p.setDefaultNavigationTimeout(30000);
 p.on('pageerror',e=>m.pageerrors.push(e.message));p.on('response',r=>{const u=new URL(r.url());if(u.pathname.startsWith('/api/'))m.http.push({path:u.pathname,status:r.status(),method:r.request().method()});if(/\.(?:js|css)$/.test(u.pathname))r.body().then(b=>m.assets.push({path:u.pathname,sha256:hash(b),status:r.status()})).catch(()=>{});});
 const act=locator=>width===390?locator.tap():locator.click();
 const visible=id=>p.getByTestId(id).first().waitFor({state:'visible'});
 async function screenshot(name){await p.evaluate(()=>{const w=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);let n;while(n=w.nextNode()){if(n.parentElement?.closest('script,style'))continue;n.textContent=n.textContent.replace(/[\w.+-]+@[\w.-]+\.[\w-]+/g,'[EMAIL]');}});const file=out+'/'+name+'-'+width+'.png';await p.screenshot({path:file,fullPage:true});result.screens.push({width,file,sha256:hash(fs.readFileSync(file))});return p.evaluate(()=>({viewport:innerWidth,document:document.documentElement.scrollWidth}));}
 await check(width,'public home real GET and screenshot',async()=>{await p.goto(base+'/travel');await visible('home-hero');await visible('trip-card');await p.waitForLoadState('networkidle');return {cards:await p.getByTestId('trip-card').count(),geometry:await screenshot('home')};});
 await check(width,'trusted campaign navigation and product detail',async()=>{if(width===390){await act(p.getByTestId('menu-toggle'));await act(p.getByTestId('mobile-nav-campaigns'));}else await act(p.getByTestId('nav-campaigns'));await visible('campaign-card');await act(p.getByTestId('campaign-card').first());await visible('campaign-detail');await act(p.getByTestId('campaign-search-cta'));await visible('trip-card');const searchURL=p.url();await act(p.getByTestId('trip-card').first());await visible('product-title');await visible('booking-panel');await screenshot('product');await p.goBack();await visible('trip-card');if(p.url()!==searchURL)throw Error('History URL differs from campaign-filtered search');return {search_path:new URL(searchURL).pathname,query:new URL(searchURL).search,productThenHistory:true};});
 await check(width,'real empty search and filter reset',async()=>{await p.goto(base+'/travel/search?q=RAON_NO_RESULTS_SCOPE_CORRECTION_20261010');await visible('catalog-empty');if(await p.getByTestId('trip-card').count())throw Error('Unexpected empty-search cards');await screenshot('empty-search');if(width===390)await act(p.getByTestId('filters-toggle'));await act(p.getByTestId('filter-reset'));await visible('trip-card');return {empty:true,resetRecovered:true};});
 await check(width,'catalog network failure real Retry recovery without reload',async()=>{const pattern='**/api/modules/raonslab-travel_lab/catalog*';await p.route(pattern,route=>route.abort('failed'));await p.goto(base+'/travel/search');await visible('catalog-error');await screenshot('catalog-error');const origin=await p.evaluate(()=>{window.__scopeRetry=1;return performance.timeOrigin;});await p.unroute(pattern);const response=p.waitForResponse(r=>new URL(r.url()).pathname==='/api/modules/raonslab-travel_lab/catalog'&&r.request().method()==='GET');await act(p.getByTestId('catalog-retry'));const r=await response;if(r.status()!==200)throw Error('Retry native response '+r.status());await visible('trip-card');await p.getByTestId('catalog-error').waitFor({state:'hidden'});if(!(await p.evaluate(t=>window.__scopeRetry===1&&performance.timeOrigin===t,origin)))throw Error('Unexpected reload');await screenshot('catalog-recovered');return {native:200,errorCleared:true,reload:false};});
 await check(width,'help and public back navigation',async()=>{await p.goto(base+'/travel');await act(p.getByTestId(width===390?'tab-help':'nav-help'));await p.waitForURL('**/travel/help');await p.waitForLoadState('networkidle');await screenshot('help');await p.goBack();await visible('home-hero');return {help:true,historyHome:true};});
 m.trusted=await p.evaluate(()=>window.__uiInputs??[]).catch(()=>[]);
 await context.close();flush();
}
await browser.close();result.finished=new Date().toISOString();flush();process.exitCode=result.checks.some(c=>c.status==='FAIL')?1:0;
