// 현재 native 계정으로 목록을 열어 가로 넘침을 측정하고 발급 토큰만 폐기한다.
import fs from 'node:fs';
import {base,out,SHA,context,login,browser,expect,shot,results,flush,finishTokens} from './common.mjs';
const ledger='storage/framework/testing/w04-private/issued.json';
// 직전 자체 재현의 알려진 실패로 남은 private 장부도 정확한 토큰만 정리한다.
for(const width of [390,1440]){
 const {p,c,m}=await context(width,'member');await login(p,'member');await p.goto(base+'/travel/requests');
 await expect(p.getByRole('heading',{name:'내 상담 요청',exact:true})).toBeVisible();
 await expect(p.getByRole('button').filter({hasText:'요청 #'}).first()).toBeVisible();
 const dimensions=await p.evaluate(()=>({viewport:innerWidth,document:document.documentElement.scrollWidth}));
 results.push({width,name:'native request list overflow reproduction after terminal cleanup',status:dimensions.document>width?'FAIL':'PASS',detail:dimensions});
 await shot(p,m,'final-requests-overflow');flush('overflow-reproducer');await c.close();
}
const p=await browser.newPage();await finishTokens(p);const tokens=JSON.parse(fs.readFileSync(ledger));let verified=0;
for(const {token} of tokens){await p.request.post(base+'/api/auth/logout',{headers:{Accept:'application/json',Authorization:'Bearer '+token},data:{}});const r=await p.request.get(base+'/api/auth/user',{headers:{Accept:'application/json',Authorization:'Bearer '+token}});expect(r.status()).toBe(401);verified++;}
fs.unlinkSync(ledger);results.push({name:'only own newly issued tokens native revoked and requery401',status:'PASS',count:verified});flush('overflow-reproducer');
fs.writeFileSync(out+'/overflow-token-count.json',JSON.stringify({sourceSHA:SHA,verifiedRevoked:verified,privateLedgerRemoved:true},null,2)+'\n');await browser.close();
