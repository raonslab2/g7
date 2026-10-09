// Actual own fixture setup through native APIs; never counted as UI product creation.
import fs from 'node:fs';import {out,travel,api,browser,owned,step,flush,results,expect,run} from './common.mjs';
const p=await browser.newPage(),commerce='/api/modules/sirsoft-ecommerce/admin';
const cat=await api(p,travel+'/catalog?per_page=48');expect(cat.status).toBe(200);const seedId=cat.body.data.data[0].id;
const seed=(await api(p,commerce+'/products/'+seedId,'GET',undefined,'admin')).body.data;
const policy=(await api(p,commerce+'/shipping-policies/1','GET',undefined,'admin')).body.data;
expect(policy.is_active).toBe(true);expect(policy.is_default).toBe(false);expect(policy.country_settings.some(x=>x.country_code==='KR'&&x.charge_policy==='free')).toBe(true);
for(const width of [390,1440])await step(width,'native API own product/options fixture preparation (UI creation BLOCKED)',async()=>{
 const name=`제주 ${run} ${width} 겨울 숲길과 바닷길을 천천히 걷는 가족 자연 여행 긴 상품명 전체 표시 확인`;
 const body={name:{ko:name,en:run+' independent own travel '+width},product_code:run+'P'+width,category_ids:[1],list_price:20000,selling_price:13000,stock_quantity:20,sales_status:seed.sales_status,display_status:seed.display_status,tax_status:seed.tax_status,shipping_policy_id:1,has_options:true,option_groups:seed.option_groups,use_stock_management:true,options:[0,1].map(i=>({option_code:run+width+i,option_name:{ko:'합성 '+(i?'B':'A'),en:'Synthetic '+i},option_values:seed.options[0].option_values,list_price:20000,selling_price:i?9000:13000,stock_quantity:20,is_default:i===0,is_active:true}))};
 const r=await api(p,commerce+'/products','POST',body,'admin');expect(r.status).toBe(201);owned.products.push({id:r.body.data.id,policy:1,category:1,fixturePreparation:'API ONLY, UI admin creation BLOCKED empty active category tree'});flush('admin');return {id:r.body.data.id,http:201,scope:'fixture setup only; existing category/policy READONLY, no creation/update'};
},'admin');
flush('admin');await browser.close();process.exitCode=results.some(r=>r.status==='FAIL')?1:0;
