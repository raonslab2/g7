// Author native request-list geometry. API fixture setup is not an independent UI journey PASS.
import fs from 'node:fs';import path from 'node:path';import crypto from 'node:crypto';import {fileURLToPath} from 'node:url';import {execFileSync} from 'node:child_process';import {chromium,expect} from '@playwright/test';
const template=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..'),root=path.resolve(template,'../../..');
const file=process.env.TRAVEL_ACCESS_FILE,phase=process.env.TRAVEL_NATIVE_PHASE||'before';
if(!['before','before-multiple','after','owner-before','owner-after','owner-after-pointer','owner-after-date'].includes(phase))throw Error('Unknown phase');
if(!file||!fs.realpathSync(file).startsWith(path.join(root,'storage/framework/testing/travel-live-review-'))||!fs.statSync(file).isFile()||fs.lstatSync(file).isSymbolicLink()||(fs.statSync(file).mode&0o077)||(fs.statSync(path.dirname(file)).mode&0o077))throw Error('Private approved access required');
const a=JSON.parse(fs.readFileSync(file));if(a.db!=='req81_travel_lab'||(!Number.isFinite(Date.parse(a.expires_at))||Date.parse(a.expires_at)<=Date.now())||new URL(a.base_url).hostname!=='127.0.0.1'||a.source_sha!==process.env.TRAVEL_SOURCE_SHA)throw Error('Lab/source/expiry mismatch');
const out=path.join(template,'__tests__/evidence/mobile-request-native-'+phase+'.json');if(fs.existsSync(out))throw Error('Preserve earlier phase');
const api='/api/modules/raonslab-travel_lab',results=[],browser=await chromium.launch();let ownId,ownCart;const ownCarts=new Set();
const hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const mapping=['layouts/travel/requests.json','dist/css/components.css','dist/js/components.iife.js'].map(p=>({path:p,bundled:hash(path.join(template,p)),installed:hash(path.join(root,'templates/raonslab-travel_lab',p))}));
if(phase.includes('after')&&mapping.some(m=>m.bundled!==m.installed))throw Error('Installed UI does not match candidate');
async function call(uri,method='GET',data){const r=await fetch(a.base_url+uri,{method,headers:{Accept:'application/json',Authorization:'Bearer '+a.member.bearer_token,...(data?{'Content-Type':'application/json'}:{})},...(data?{body:JSON.stringify(data)}:{})});return {status:r.status,body:await r.json()};}
const readOnlyOwner=phase.startsWith('owner-');
execFileSync('git',['merge-base','--is-ancestor',a.source_sha,'HEAD'],{cwd:root});
try{
 if(!readOnlyOwner){
 const cart=await call(api+'/cart');expect(cart.status).toBe(200);if(cart.body.data.items.length)throw Error('Empty own cart precondition; do not clear unrelated data');
 const cat=await call(api+'/catalog');expect(cat.status).toBe(200);const id=cat.body.data.data[0]?.id;if(!id)throw Error('No synthetic catalog');
 const dep=await call(api+'/catalog/'+id+'/departures');expect(dep.status).toBe(200);const departures=Array.isArray(dep.body.data)?dep.body.data:dep.body.data.data;const departure=departures.find(d=>d.available>0);if(!departure)throw Error('No available departure');
 const added=await call(api+'/cart','POST',{departure_id:departure.id,quantity:1});expect(added.status).toBe(201);ownCart=added.body.data.items[0].id;ownCarts.add(ownCart);
 const ids=[ownCart];
 if(phase!=='before'){
  const second=cat.body.data.data.find(p=>p.id!==id);if(!second)throw Error('Need second own travel item');
  const dr=await call(api+'/catalog/'+second.id+'/departures');const list=Array.isArray(dr.body.data)?dr.body.data:dr.body.data.data;const d=list.find(x=>x.available>0);if(!d)throw Error('Second departure unavailable');
  const r=await call(api+'/cart','POST',{departure_id:d.id,quantity:1});expect(r.status).toBe(201);for(const x of r.body.data.items)ownCarts.add(x.id);ids.splice(0,ids.length,...r.body.data.items.map(x=>x.id));
 }
 const created=await call(api+'/inquiries','POST',{cart_ids:ids,contact:{name:'Synthetic mobile geometry',phone:null},idempotency_key:'geometry-'+crypto.randomUUID()});expect(created.status).toBe(201);ownId=created.body.data.id;results.push({name:'own API fixture setup (not UI journey)',status:'PASS',http:201,id:ownId});
 }
 for(const width of [390,1440]){
  const c=await browser.newContext({locale:'ko-KR',viewport:{width,height:1000},...(width===390?{isMobile:true,hasTouch:true}:{})});const p=await c.newPage();let token;const errors=[];p.on('pageerror',()=>errors.push('pageerror'));
  try{await p.goto(a.base_url+'/login');await p.locator('#travel-login-email').fill(a.member.email);await p.locator('#travel-login-password').fill(a.member.password);
   const response=p.waitForResponse(r=>r.url().endsWith('/api/auth/login')&&r.request().method()==='POST');await p.locator('form button[type=submit]').click();const login=await response;expect(login.status()).toBe(200);const body=await login.json();token=body.data?.token||body.data?.access_token;await expect(p).not.toHaveURL(/\/login/);
   const listResponse=phase==='owner-after-date'?p.waitForResponse(r=>new URL(r.url()).pathname===api+'/inquiries'&&r.request().method()==='GET'):null;
   await p.goto(a.base_url+'/travel/requests');await expect(p.getByTestId('request-card').first()).toBeVisible();await p.waitForLoadState('networkidle');
   if(listResponse){const response=await listResponse;expect(response.status()).toBe(200);const data=(await response.json()).data.data[0];const expectedPrefix=String(data.created_at).slice(0,16);if(!/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(expectedPrefix))throw Error('Unexpected synthetic created_at format');const date=p.getByTestId('request-card').first().locator(':scope > div').first().locator(':scope > span').first();await expect(date).toContainText(expectedPrefix);results.push({width,name:'settled native date versus server prefix',status:'PASS',id:data.id,serverCreatedAt:data.created_at,expectedPrefix,renderedDate:await date.textContent()});}

   const geometry=await p.evaluate(()=>({viewport:innerWidth,document:document.documentElement.scrollWidth,cards:[...document.querySelectorAll('[data-testid=request-card]')].map(e=>{const r=e.getBoundingClientRect();return {left:r.left,right:r.right,width:r.width,direction:getComputedStyle(e).flexDirection};})}));
   const inside=geometry.cards.every(c=>c.left>=0&&c.right<=width);results.push({width,name:'native list geometry',status:geometry.document<=width&&inside?'PASS':'FAIL',geometry,pageErrors:errors.length});
   await p.screenshot({path:path.join(template,'__tests__/evidence/mobile-request-native-'+phase+'-'+width+'.png'),fullPage:true,mask:[p.locator('input,textarea'),p.locator('[data-testid=contact-name],[data-testid=contact-phone]')]});
   const card=p.getByTestId('request-card').first();const captured=await card.textContent();const targetId=readOnlyOwner?Number(captured.match(/#(\d+)/)?.[1]):ownId;expect(targetId).toBeTruthy();await card.focus();await p.keyboard.press('Enter');await expect(p).toHaveURL(new RegExp('/travel/requests/'+targetId+'(?:\\?|$)'));results.push({width,name:'native keyboard detail route',status:'PASS',id:targetId});
   if(phase==='owner-after-pointer'){await p.goto(a.base_url+'/travel/requests');await expect(p.getByTestId('request-card').first()).toBeVisible();if(width===390)await p.getByTestId('request-card').first().tap();else await p.getByTestId('request-card').first().click();await expect(p).toHaveURL(new RegExp('/travel/requests/'+targetId+'(?:\\?|$)'));results.push({width,name:'native pointer detail route',status:'PASS',method:width===390?'tap':'click',id:targetId});}
  }finally{
   if(token){const r=await p.request.post(a.base_url+'/api/auth/logout',{headers:{Accept:'application/json',Authorization:'Bearer '+token}});const check=await p.request.get(a.base_url+'/api/auth/user',{headers:{Accept:'application/json',Authorization:'Bearer '+token}});results.push({width,name:'exact own issued login token logout/requery',status:r.status()===200&&check.status()===401?'PASS':'FAIL'});}else results.push({width,name:'issued login token cleanup',status:'NOT_PROVEN'});
   await c.close();
  }
 }
}finally{
 if(ownId){await call(api+'/inquiries/'+ownId+'/cancel','POST',{});const r=await call(api+'/inquiries/'+ownId);results.push({name:'exact own inquiry cancelled',id:ownId,status:r.status===200&&r.body.data.status==='CANCELLED'?'PASS':'FAIL'});}
 if(ownCart){const r=await call(api+'/cart');for(const id of ownCarts)if(r.body.data.items.some(i=>i.id===id))await call(api+'/cart/'+id,'DELETE');const check=await call(api+'/cart');results.push({name:'own cart requery empty',status:check.body.data.items.length===0?'PASS':'FAIL'});}
 await browser.close();fs.writeFileSync(out,JSON.stringify({kind:'author native authenticated WORKINGTREE geometry; not independent Validation',phase,accessSourceSHA:a.source_sha,actualHead:execFileSync('git',['rev-parse','HEAD'],{cwd:root,encoding:'utf8'}).trim(),sourceMapping:mapping,results},null,2)+'\n');
}
console.log(JSON.stringify(results));if(results.some(r=>r.status==='FAIL'||r.status==='NOT_PROVEN'))process.exitCode=1;
