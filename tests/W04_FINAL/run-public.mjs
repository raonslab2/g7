import fs from 'node:fs';import {spawnSync} from 'node:child_process';
const out='docs/symphony/evidence/W04_FINAL_BROWSER',f=JSON.parse(fs.readFileSync(out+'/pagination-query.json')),owned=JSON.parse(fs.readFileSync(out+'/admin.json')).owned.products;
const r=spawnSync(process.execPath,['tests/W04_FINAL/public-recheck.mjs'],{env:{...process.env,W04_PAGINATION_IDS:f.productIds.join(','),W04_PAGINATION_Q:f.marker,W04_DETAIL_IDS:owned.map(x=>x.id).join(',')},stdio:'inherit'});process.exitCode=r.status??1;
