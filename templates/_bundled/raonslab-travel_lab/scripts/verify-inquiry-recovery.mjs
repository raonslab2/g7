/** Implementer repair check. Real native login/API; only named response-loss scenarios inject faults.
 * W03_ACCESS_FILE must contain fresh private scoped lab credentials; never prints their values.
 * Run after lead freezes/resyncs the candidate preview. Does not grant independent Validation. */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { chromium, expect } from '@playwright/test';
const root=fileURLToPath(new URL('../../../../',import.meta.url));
const file=process.env.W03_ACCESS_FILE;
if(!file || !file.includes('/storage/framework/testing/travel-live-review-')) throw new Error('Private scoped lab access file required');
const realFile=fs.realpathSync(file);
if(!realFile.startsWith(path.join(root,'storage/framework/testing/travel-live-review-')) || fs.lstatSync(file).isSymbolicLink() || (fs.statSync(realFile).mode & 0o077) || (fs.statSync(path.dirname(realFile)).mode & 0o077))throw new Error('Access must be a private regular file in this Request lab directory');
const access=JSON.parse(fs.readFileSync(realFile,'utf8'));
if(access.db!=='req81_travel_lab' || !Number.isFinite(Date.parse(access.expires_at)) || Date.parse(access.expires_at)<=Date.now())throw new Error('Scoped lab marker or unexpired access is required');
const sourceSHA=execFileSync('git',['rev-parse','HEAD'],{cwd:root,encoding:'utf8'}).trim();
if(access.source_sha!==sourceSHA || (process.env.TRAVEL_REPAIR_SOURCE_SHA && process.env.TRAVEL_REPAIR_SOURCE_SHA!==sourceSHA))throw new Error('Access source revision does not match the candidate');
const sourceMapping=[];
for(const [kind,relative] of [['templates','dist/js/components.iife.js'],['templates','layouts/travel/cart.json'],['templates','layouts/partials/travel/_modal_cart_remove.json'],['modules','resources/layouts/admin/admin_travel_lab_catalog.json']]){
 const bundled=path.join(root,kind,'_bundled/raonslab-travel_lab',relative),installed=path.join(root,kind,'raonslab-travel_lab',relative);
 const digest=f=>crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
 const sha256=digest(bundled);if(digest(installed)!==sha256)throw new Error('Preview installation does not match the candidate source');sourceMapping.push({kind,path:relative,sha256});
}
const base=access.base_url;
if(new URL(base).hostname!=='127.0.0.1')throw new Error('Loopback lab preview required');
const out=path.resolve(process.env.TRAVEL_REPAIR_EVIDENCE_DIR || path.join(root,'storage/framework/testing/travel-ui-repair'));
if(!out.startsWith(path.join(root,'storage/framework/testing/')) || (fs.existsSync(out)&&fs.lstatSync(out).isSymbolicLink()))throw new Error('Private Request evidence directory required');
fs.mkdirSync(out,{recursive:true,mode:0o700});fs.chmodSync(out,0o700);
const apiBase='/api/modules/raonslab-travel_lab';
const modes=[...new Set((process.env.TRAVEL_REPAIR_MODES || 'immediate,reload,edited-contact').split(','))];
if(!modes.length || modes.some(mode=>!['immediate','reload','edited-contact','storage-quota'].includes(mode)))throw new Error('Unknown bounded repair mode');
const results=[];const owned=new Map();const cartIds=new Map();let currentRole='member';const browser=await chromium.launch();
const hash=o=>crypto.createHash('sha256').update(JSON.stringify(o)).digest('hex');
async function call(p,uri,method='GET',data,role=currentRole){const r=await p.request.fetch(base+apiBase+uri,{method,headers:{Accept:'application/json',Authorization:'Bearer '+access[role].bearer_token},...(data?{data}:{})});return {status:r.status(),body:await r.json()};}
try {
 for(const width of [390,1440]) {
  currentRole=width===390?'member':'other_member';
  const c=await browser.newContext({locale:'ko-KR',viewport:{width,height:1000}});const p=await c.newPage();p.setDefaultTimeout(15000);
  const errors=[];p.on('pageerror',()=>errors.push('pageerror'));
  await p.goto(base+'/login');await p.locator('#travel-login-email').fill(access[currentRole].email);await p.locator('#travel-login-password').fill(access[currentRole].password);
  const login=p.waitForResponse(r=>new URL(r.url()).pathname==='/api/auth/login' && r.request().method()==='POST');await p.locator('form button[type=submit]').click();expect((await login).status()).toBe(200);await expect(p).not.toHaveURL(/\/login/);
  const existing=await call(p,'/cart');expect(existing.status).toBe(200);if(existing.body.data.items.length)throw new Error('Fresh reviewer member must have an empty cart; foreign cart cleanup is forbidden');
  const catalog=await call(p,'/catalog');expect(catalog.status).toBe(200);const product=catalog.body.data.data[0];if(!product)throw new Error('No available synthetic travel fixture');
  for(const mode of modes) {
   await p.goto(base+'/travel/products/'+product.id);await p.getByTestId('departure-option').first().click();const add=p.waitForResponse(r=>r.url().endsWith(apiBase+'/cart')&&r.request().method()==='POST');await p.getByTestId('add-to-cart').click();const added=await add;expect(added.status()).toBe(201);for(const item of (await added.json()).data.items)cartIds.set(item.id,currentRole);await p.getByTestId('go-cart').click();
   await p.getByTestId('contact-name').fill('Synthetic recovery '+width+' '+mode+' '+Date.now());await p.getByTestId('contact-phone').fill('000-0000-0000');await p.getByTestId('ack-test').check();
   if(mode==='storage-quota'){
    expect(await p.evaluate(()=>!!sessionStorage.getItem('raon_travel_inquiry_key'))).toBe(true);
    await p.evaluate(()=>{window.__travelQuotaOriginalSetItem=Storage.prototype.setItem;Storage.prototype.setItem=function(key,value){if(key==='raon_travel_inquiry_key')throw new DOMException('Synthetic quota fault','QuotaExceededError');return window.__travelQuotaOriginalSetItem.call(this,key,value);};});
   }
   let firstBody,retryBody,created,intercepted=false;
   await p.route('**'+apiBase+'/inquiries',async route=>{
    if(route.request().method()!=='POST'){await route.continue();return;}
    if(!intercepted){intercepted=true;firstBody=route.request().postDataJSON();if(mode!=='edited-contact'){const r=await route.fetch();expect(r.status()).toBe(201);created=(await r.json()).data;owned.set(created.id,currentRole);}await route.abort('failed');}
    else {retryBody=route.request().postDataJSON();await route.continue();}
   });
   await p.getByTestId('submit-inquiry').click();await expect.poll(()=>intercepted).toBe(true);await expect(p.getByTestId('submit-inquiry')).toBeEnabled();
   if(mode==='reload'){await p.reload();await expect(p.getByTestId('inquiry-recovery-notice')).toBeVisible();await expect(p.getByTestId('contact-name')).toBeDisabled();await p.getByTestId('ack-test').check();}
   if(mode==='edited-contact')await p.getByTestId('contact-phone').fill('000-0000-0001');
   const retry=p.waitForResponse(r=>r.url().endsWith(apiBase+'/inquiries')&&r.request().method()==='POST');await p.getByTestId('submit-inquiry').click();const r=await retry;expect(r.status()).toBe(mode==='edited-contact'?201:200);const inquiry=(await r.json()).data;
   if(mode==='edited-contact'){expect(retryBody.idempotency_key).not.toBe(firstBody.idempotency_key);expect(retryBody.contact.phone).toBe('000-0000-0001');owned.set(inquiry.id,currentRole);}
   else {expect(retryBody).toEqual(firstBody);expect(inquiry.id).toBe(created.id);}
   await p.unroute('**'+apiBase+'/inquiries');await expect(p).toHaveURL(new RegExp('/travel/requests/'+inquiry.id));await expect(p.getByTestId('detail-status')).toContainText('테스트 접수');
   await p.screenshot({path:path.join(out,`recovery-${mode}-${width}.png`),fullPage:true,mask:[p.locator('input,textarea'),p.getByText(/@/),p.getByText(/Synthetic recovery/),p.getByText(/000-0000-000[01]/)]});
   fs.chmodSync(path.join(out,`recovery-${mode}-${width}.png`),0o600);
   results.push({width,mode,status:'PASS',firstBodySHA256:hash(firstBody),retryBodySHA256:hash(retryBody),sameBody:mode!=='edited-contact',inquiryId:inquiry.id,httpStatus:r.status()});
   if(mode==='storage-quota')await p.evaluate(()=>{Storage.prototype.setItem=window.__travelQuotaOriginalSetItem;delete window.__travelQuotaOriginalSetItem;});
   const cancel=await call(p,'/inquiries/'+inquiry.id+'/cancel','POST',{});expect(cancel.status).toBe(200);expect(cancel.body.data.status).toBe('CANCELLED');
  }
  expect(errors).toHaveLength(0);await c.close();
 }
} finally {
 const cleanup=await browser.newPage();
 try {
  for(const [id,role] of owned){
   const found=await call(cleanup,'/inquiries/'+id,'GET',undefined,role);
   if(found.status!==200){results.push({cleanupOwnInquiry:id,status:'FAIL',httpStatus:found.status});continue;}
   if(found.body.data.status!=='CANCELLED')await call(cleanup,'/inquiries/'+id+'/cancel','POST',{},role);
   const final=await call(cleanup,'/inquiries/'+id,'GET',undefined,role);
   results.push({cleanupOwnInquiry:id,status:final.status===200 && final.body.data.status==='CANCELLED'?'PASS':'FAIL'});
  }
  for(const role of ['member','other_member']){
   const remaining=await call(cleanup,'/cart','GET',undefined,role);
   if(remaining.status!==200){results.push({cleanupCartRole:role,status:'FAIL',httpStatus:remaining.status});continue;}
   for(const item of remaining.body.data.items)if(cartIds.get(item.id)===role)await call(cleanup,'/cart/'+item.id,'DELETE',undefined,role);
   const final=await call(cleanup,'/cart','GET',undefined,role);
   results.push({cleanupCartRole:role,status:final.status===200 && !final.body.data.items.some(item=>cartIds.get(item.id)===role)?'PASS':'FAIL'});
  }
 } catch {results.push({cleanup:'FAIL',reason:'Own fixture cleanup request failed; private access may have expired'});}
 await browser.close();fs.writeFileSync(path.join(out,'recovery-results.json'),JSON.stringify({source_sha:sourceSHA,source_state:'WORKING_TREE_IMPLEMENTER_CHECK',sourceMapping,results,independentValidation:false},null,2),{mode:0o600});fs.chmodSync(path.join(out,'recovery-results.json'),0o600);
}
if(results.some(r=>r.status==='FAIL'))throw new Error('Own fixture cleanup failed; inspect private repair evidence');
console.log(JSON.stringify({checks:results.length,result:'PASS',independentValidation:false}));
