// Source-layout geometry probe with shipped native components and synthetic data only.
// This is an author rendering check, not authenticated end-to-end or independent Validation.
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {fileURLToPath} from 'node:url';
import {chromium} from '@playwright/test';
const template=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const base=process.env.TRAVEL_GEOMETRY_BASE||'http://127.0.0.1:18871';
if(new URL(base).hostname!=='127.0.0.1')throw Error('Loopback preview required');
const phase=process.env.TRAVEL_GEOMETRY_PHASE||'before';
if(!['before','candidate'].includes(phase))throw Error('Unknown evidence phase');
const layoutPath=path.join(template,'layouts/travel/requests.json');
const layout=JSON.parse(fs.readFileSync(layoutPath));
const nodes=[];const walk=n=>{if(Array.isArray(n))n.forEach(walk);else if(n&&typeof n==='object'){nodes.push(n);Object.values(n).forEach(walk);}};walk(layout);
const list=nodes.find(n=>n.props?.['data-testid']==='requests-list');
if(!list)throw Error('Source request list missing');
const ko=JSON.parse(fs.readFileSync(path.join(template,'lang/ko.json')));
const out=path.join(template,'__tests__/evidence/mobile-request-'+phase+'.json');
if(fs.existsSync(out))throw Error('Preserve previous phase evidence');
const hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const browser=await chromium.launch();const results=[];
try{for(const width of [390,1440]){
 const context=await browser.newContext({locale:'ko-KR',viewport:{width,height:1000}});const page=await context.newPage();
 await page.goto(base+'/travel');await page.waitForFunction(()=>!!window.RaonslabTravelLab?.Button&&!!window.React&&!!window.ReactDOM);
 // Candidate CSS is the actual production build, not handwritten geometry fixes.
 if(phase==='candidate')await page.addStyleTag({path:path.join(template,'dist/css/components.css')});
 const geometry=await page.evaluate(async ({list,ko})=>{
  const {React:R,ReactDOM:RD,RaonslabTravelLab:C}=window;
  const fixtures=[
   {id:100,status:'DECLINED',title:'제주 합성 여행 상품 전체 이름과 상세 일정 확인',quantity:1,amount:13000},
   {id:99,status:'TEST_ACCEPTED',title:'SyntheticUnbrokenProductTitleForWidthRegression0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',quantity:12,amount:123456789},
   {id:98,status:'CANCELLED',title:'제주 오름 산책 3일',quantity:2,amount:26000}
  ];
  const translate=k=>k.split('.').reduce((v,k)=>v?.[k],ko)||k;
  const render=(n,item,key)=>{
   const props={...n.props,key};let text=n.text;
   if(text?.startsWith('$t:travel.requests.number'))text='요청 #'+item.id;
   else if(text?.startsWith('$t:travel.requests.more_items'))text='외 1건';
   else if(text?.startsWith('$t:travel.product.people_value'))text=item.quantity+'명';
   else if(text?.includes('inq.created_at'))text='2026-10-09 23:19';
   else if(text?.includes('inq.first_product_name'))text=item.title;
   if(n.name==='StatusBadge')props.status=item.status;
   if(n.name==='PriceTag'){props.amount=item.amount;props.currency='KRW';}
   if(n.name==='Button')props.onClick=()=>document.querySelector('#geometry-root').setAttribute('data-navigation','/travel/requests/'+item.id);
   if(n.if&&n.if.includes('inq.items'))return item.id===98?null:R.createElement(C[n.name],props,text);
   return R.createElement(C[n.name],props,...(n.children||[]).map((c,i)=>render(c,item,i)),text);
  };
  // Dispose the mounted preview before this isolated component probe; no auth/API mutation.
  const mounted=document.getElementById('root');if(mounted)mounted.style.display='none';
  const host=document.createElement('div');host.id='geometry-root';document.body.appendChild(host);
  const grid=R.createElement(C.Div,list.props,...fixtures.map((item,i)=>R.createElement(C.Div,{...list.children[0].props,key:i},render(list.children[0].children[0],item,i))));
  const wrapper=R.createElement(C.Div,{className:'mx-auto w-full max-w-6xl px-4 md:px-6 mt-6 space-y-4'},grid);
  RD.createRoot(host).render(wrapper);
  await new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve)));
  const cards=[...host.querySelectorAll('[data-testid=request-card]')];
  const rect=e=>{const r=e.getBoundingClientRect();return {left:r.left,right:r.right,width:r.width,height:r.height};};
  const boxes=cards.map(c=>({box:rect(c),direction:getComputedStyle(c).flexDirection,children:[...c.children].map(rect),title:c.querySelector('p')?.textContent,titleBox:rect(c.querySelector('p')),clamp:getComputedStyle(c.querySelector('p')).webkitLineClamp}));
  cards[0].focus();cards[0].click();
  return {viewport:window.innerWidth,document:document.documentElement.scrollWidth,cards:boxes,navigation:host.getAttribute('data-navigation'),statusCount:host.querySelectorAll('[data-status]').length,activeCard:document.activeElement===cards[0]};
 },{list,ko});
 const pass=geometry.document<=width&&geometry.cards.every(c=>c.box.right<=width&&c.children.every(b=>b.right<=c.box.right+1))&&geometry.navigation==='/travel/requests/100'&&geometry.activeCard&&geometry.statusCount===3;
 results.push({width,status:pass?'PASS':'FAIL',geometry});
 await page.screenshot({path:path.join(template,'__tests__/evidence/mobile-request-'+phase+'-'+width+'.png'),fullPage:true});await context.close();
}}finally{await browser.close();}
fs.writeFileSync(out,JSON.stringify({kind:'author shipped-component/source-layout geometry, synthetic data; not authenticated E2E',phase,sourceLayoutSha256:hash(layoutPath),productionCSSSha256:hash(path.join(template,'dist/css/components.css')),results},null,2)+'\n');
console.log(JSON.stringify(results.map(({width,status,geometry})=>({width,status,document:geometry.document,direction:geometry.cards[0].direction}))));
if(results.some(r=>r.status==='FAIL'))process.exitCode=1;
