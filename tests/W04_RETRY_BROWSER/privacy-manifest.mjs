// 공개 산출물 privacy 스캔(정확 값/패턴, PNG OCR eng) + SHA256 manifest. 결과에는 값이 아닌 카운트만 기록.
import fs from 'node:fs';import path from 'node:path';import crypto from 'node:crypto';import {execFileSync} from 'node:child_process';
const dir='tests/W04_RETRY_BROWSER',priv='storage/framework/testing/w04-retry-4d7d-private';
const a=JSON.parse(fs.readFileSync('/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba/storage/framework/testing/travel-campaign-retry-final/access.json'));
const rp=n=>fs.existsSync(priv+'/'+n+'.json')?JSON.parse(fs.readFileSync(priv+'/'+n+'.json')):null;
const secrets=[...['member','other_member','admin','readonly','self_reader'].flatMap(r=>[a[r].email,a[r].password,a[r].bearer_token]),...(rp('issued')??[]).map(t=>t.token),...(rp('actor-names')??[]),rp('contact')?.name].filter(x=>x&&String(x).length>3);
const pat=[/[\w.+-]+@[\w-]+\.[\w.-]+/,/[a-f\d]{8}(?:-[a-f\d]{4}){3}-[a-f\d]{12}/i,/\d+\|[A-Za-z\d]{20,}/,/signature=[A-Za-z0-9%]{8,}/];
const files=[];const walk=d=>{for(const e of fs.readdirSync(d,{withFileTypes:true})){if(e.name==='node_modules')continue;const p=path.join(d,e.name);if(e.isDirectory())walk(p);else files.push(p);}};walk(dir);files.push('docs/symphony/W04_RETRY_BROWSER_CLOSURE.md');
const rows=[];let hits=0;
for(const f of files.sort()){if(!fs.existsSync(f))continue;const b=fs.readFileSync(f);let text=f.endsWith('.png')?execFileSync('tesseract',[f,'stdout','-l','eng'],{encoding:'utf8',stdio:['ignore','pipe','ignore'],env:{...process.env,OMP_THREAD_LIMIT:'1'},timeout:120000}):b.toString('utf8');
  const exact=secrets.filter(s=>text.includes(s)).length;const patterns=pat.filter(r=>r.test(text)).map(String);hits+=exact+patterns.length;rows.push({path:f,bytes:b.length,sha256:crypto.createHash('sha256').update(b).digest('hex'),exact_private_hits:exact,pattern_hits:patterns,ocr:f.endsWith('.png')});}
const out={generated:new Date().toISOString(),files:rows.length,total_hits:hits,secrets_checked:secrets.length,rows};fs.writeFileSync(dir+'/evidence/privacy-manifest.json',JSON.stringify(out,null,1)+'\n');console.log(JSON.stringify({files:rows.length,hits,flagged:rows.filter(r=>r.exact_private_hits||r.pattern_hits.length).map(r=>[r.path,r.exact_private_hits,r.pattern_hits])}));process.exitCode=hits?1:0;
