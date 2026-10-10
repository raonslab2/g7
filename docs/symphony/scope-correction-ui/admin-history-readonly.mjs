// Residual ActionMenu diagnosis only. Native own login/logout; no fixture/domain writes.
import fs from 'node:fs';
import {execFileSync} from 'node:child_process';
import {chromium} from '/tmp/w04-4d7d-pw/node_modules/playwright/index.mjs';
const base='http://127.0.0.1:18871',out='docs/symphony/scope-correction-ui';
const env=fs.readFileSync('/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba/.env','utf8');
function key(name){const v=env.split('\n').find(x=>x.startsWith(name+'='))?.slice(name.length+1).trim();return v?.replace(/^(['"])(.*)\1$/,'$2');}
if(key('TRAVEL_LAB_ISOLATED')!=='1'||key('APP_URL')!==base)throw Error('Expected isolated loopback marker mismatch');
const email=key('INSTALLER_ADMIN_EMAIL'),password=key('INSTALLER_ADMIN_PASSWORD');if(!email||!password)throw Error('Missing installer auth');
const result={started:new Date().toISOString(),source:execFileSync('git',['rev-parse','HEAD'],{encoding:'utf8'}).trim(),checks:[],contexts:[]};
const flush=()=>fs.writeFileSync(out+'/admin-history-readonly.json',JSON.stringify(result,null,2)+'\n');
const browser=await chromium.launch({executablePath:'/home/ubuntu/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome',args:['--disable-background-networking']});
for(const width of [1440,390]){
 const context=await browser.newContext({viewport:{width,height:1000},locale:'ko-KR',hasTouch:width===390,isMobile:width===390});
 const m={width,pageerrors:[],blocked:[],http:[],cleanup:null};result.contexts.push(m);let token;
 await context.route('**/*',route=>{const r=route.request(),u=new URL(r.url());if(u.origin!==base||(!['GET','HEAD'].includes(r.method())&&!['/api/auth/login','/api/auth/logout'].includes(u.pathname))){m.blocked.push({origin:u.origin,path:u.pathname,method:r.method()});return route.abort('blockedbyclient');}return route.continue();});
 await context.addInitScript(()=>{window.__menuTrace=[];for(const type of ['click','mousedown','keydown','scroll','resize','popstate'])document.addEventListener(type,e=>{const el=e.target instanceof Element?e.target:null;window.__menuTrace.push({type,time:Math.round(performance.now()),trusted:e.isTrusted,tag:el?.tagName,key:e.type==='keydown'?e.key:undefined,x:scrollX,y:scrollY,menuCount:document.querySelectorAll('div.fixed.z-\\[9999\\]').length});},true);});
 const p=await context.newPage();p.setDefaultTimeout(16000);p.setDefaultNavigationTimeout(30000);
 p.on('pageerror',e=>m.pageerrors.push(e.message));p.on('response',r=>{const u=new URL(r.url());if(u.pathname.startsWith('/api/'))m.http.push({path:u.pathname,status:r.status(),method:r.request().method()});});
 const act=l=>width===390?l.tap():l.click();
 try{
  await p.goto(base+'/login');await p.locator('#travel-login-email').fill(email);await p.locator('#travel-login-password').fill(password);
  const rw=p.waitForResponse(r=>new URL(r.url()).pathname==='/api/auth/login'&&r.request().method()==='POST');await act(p.locator('form button[type=submit]'));const rr=await rw;const body=await rr.json();token=body.data?.token??body.data?.access_token;if(rr.status()!==200||!token)throw Error('Native own login failed');await p.waitForURL(u=>u.pathname!=='/login');
  await p.goto(base+'/admin/travel-lab/inquiries');await p.getByText(/^TL-\d{8}$/).first().waitFor({state:'visible'});await p.waitForLoadState('networkidle');
  const text=await p.getByText(/^TL-\d{8}$/).first().textContent();const label=p.getByText(text,{exact:true});
  const button=()=>width===1440?p.locator('tr').filter({has:label}).getByRole('button').last():label.locator('xpath=ancestor::*[.//button][1]').getByRole('button').last();
  for(let cycle=1;cycle<=(width===1440?4:2);cycle++){
   for(const stage of ['initial-pointer','post-back-keyboard','post-second-back-pointer']){
    const record={width,cycle,stage,status:'FAIL'};
    try{
     await label.waitFor({state:'visible'});const btn=button();await btn.scrollIntoViewIfNeeded();
     // Match old verifier: initial-pointer waits after scrolling; keyboard waits before focus scroll.
     if(stage!=='post-back-keyboard')await p.waitForTimeout(750);
     record.hit=await btn.evaluate(b=>{const r=b.getBoundingClientRect(),e=document.elementFromPoint(r.x+r.width/2,r.y+r.height/2);return {x:r.x,y:r.y,width:r.width,height:r.height,hit:!!e&&(e===b||b.contains(e))};});await p.evaluate(()=>window.__menuTrace=[]);
     if(stage==='post-back-keyboard'){await btn.focus();await p.keyboard.press('Enter');}else await act(btn);
     const item=p.getByText('상세 보기',{exact:true});await item.waitFor({state:'visible',timeout:4500});record.menuVisible=true;record.events=await p.evaluate(()=>window.__menuTrace);
     await act(item);await p.waitForURL(/\/admin\/travel-lab\/inquiries\/\d+$/);record.detail=true;
     await p.goBack();await label.waitFor({state:'visible'});await p.waitForTimeout(750);record.history=true;record.status='PASS';
    }catch(e){record.error=String(e.message).replace(/[\w.+-]+@[\w.-]+\.[\w-]+/g,'[EMAIL]').slice(0,500);record.events=await p.evaluate(()=>window.__menuTrace).catch(()=>[]);await p.goto(base+'/admin/travel-lab/inquiries');await label.waitFor({state:'visible'});await p.waitForLoadState('networkidle');}
    result.checks.push(record);flush();console.log(width,cycle,stage,record.status);
   }
  }
 }catch(e){result.checks.push({width,name:'context setup',status:'FAIL',error:String(e.message).replaceAll(email,'[EMAIL]').replaceAll(password,'[PASSWORD]').slice(0,500)});}
 finally{
  if(token){const headers={Authorization:'Bearer '+token,Accept:'application/json'};const lo=await context.request.post(base+'/api/auth/logout',{headers,data:{}});const re=await context.request.get(base+'/api/auth/user',{headers});m.cleanup={ownLogout:lo.status(),exactOwnRequery:re.status(),status:lo.status()===200&&re.status()===401?'PASS':'FAIL'};token=null;}
  await context.close();flush();
 }
}
await browser.close();result.finished=new Date().toISOString();flush();process.exitCode=result.checks.some(x=>x.status==='FAIL')?1:0;
