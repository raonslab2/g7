// Supplemental readonly precision check after cleanup. No new login or fixture writes.
import fs from 'node:fs';
import {api, browser, travel, out, expect, step, flush, results} from './common.mjs';
const p=await browser.newPage();
const own=await api(p,'/api/auth/user');
const admin=await api(p,'/api/auth/user','GET',undefined,'admin');
expect(own.status).toBe(200);expect(admin.status).toBe(200);
expect(typeof own.body.data?.uuid==='string').toBe(true);expect(typeof admin.body.data?.uuid==='string').toBe(true);
const ordinary=JSON.parse(fs.readFileSync(out+'/journey.json')).owned.inquiries;
for(const [i,f] of ordinary.entries())await step(i===0?390:1440,'readonly native persisted inquiry audit actor identity and transitions',async()=>{
 const q=await api(p,travel+'/admin/inquiries/'+f.id,'GET',undefined,'admin');expect(q.status).toBe(200);const d=q.body.data;
 const ownId=d.user_id;expect(Number.isInteger(ownId)&&ownId>0).toBe(true);expect(d.events.map(e=>e.to_status)).toEqual(['TEST_INQUIRY','UNDER_REVIEW','TEST_ACCEPTED','CANCELLED']);
 const actors=d.events.map(e=>e.actor_id);expect(actors[0]===ownId&&actors[3]===ownId&&actors[1]===actors[2]&&actors[1]!==ownId&&actors.every(v=>Number.isInteger(v)&&v>0)).toBe(true);
 expect(d.events.map(e=>e.from_status)).toEqual([null,'TEST_INQUIRY','UNDER_REVIEW','TEST_ACCEPTED']);
 return {id:f.id,eventStates:d.events.map(e=>e.to_status),actorRoles:['owner','admin','admin','owner'],ownerAndAdminActorSequencesConsistent:true,exactAdminNativeUserIDMapping:'NOT_RUN; native auth resource exposes UUID only, no user-management or SQL joins attempted',fromStatusSequenceMatches:true};
},'actor-audit');
flush('actor-audit');await browser.close();process.exitCode=results.some(r=>r.status==='FAIL')?1:0;
