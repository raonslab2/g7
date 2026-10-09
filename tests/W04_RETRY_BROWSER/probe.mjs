// 읽기 전용 native API 사전 관찰 (쓰기 없음, 자기 발급 토큰은 종료 시 native logout). 구조/ID/상태만 기록.
import {travel,pages,shop,api,step,result,finish,hash,suppliedStatus} from './common.mjs';
try{
await step(null,'supplied handoff bearer status (read-only GET)',async()=>({supplied:await suppliedStatus(),status:'OBSERVED'}));
for(const role of ['admin','readonly','self_reader'])await step(null,role+' page list abilities observe',async()=>{const r=await api(pages+'?per_page=100','GET',undefined,role);return {status_http:r.status,abilities:r.body?.data?.abilities,rows:(r.body?.data?.data??[]).map(x=>({id:x.id,slug:x.slug,published:x.published,abilities:x.abilities})),meta:r.body?.data?.meta,status:'OBSERVED'};});
await step(null,'public campaigns and catalog observe',async()=>{const c=await api(travel+'/campaigns','GET',undefined,null);const out={campaigns:c.status,campaign_body_keys:Object.keys(c.body?.data??{}),campaign_rows:JSON.stringify(c.body?.data).slice(0,600)};for(const theme of ['nature','wellness']){const rows=[];let page=1,last=1;do{const r=await api(travel+'/catalog?theme='+theme+'&per_page=48&page='+page,'GET',undefined,null);if(r.status!==200)throw Error('catalog '+r.status);rows.push(...r.body.data.data.map(x=>x.id));last=r.body.data.meta?.last_page??1;page++;}while(page<=last);out[theme]=rows;}out.status='OBSERVED';return out;});
}finally{await finish();}
