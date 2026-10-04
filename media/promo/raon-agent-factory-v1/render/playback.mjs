import http from 'node:http';import fs from 'node:fs';import path from 'node:path';
const {chromium}=await import(process.env.PLAYWRIGHT_MODULE??'playwright');
const root=process.argv[2];if(!root)throw Error('output directory required');
const server=http.createServer((req,res)=>{const p=path.join(root,path.basename(new URL(req.url,'http://x').pathname));if(!fs.existsSync(p)){res.writeHead(404);return res.end();}const size=fs.statSync(p).size;const type=p.endsWith('.html')?'text/html':'video/mp4';const range=req.headers.range;if(range){const [a,b]=range.replace('bytes=','').split('-');const start=Number(a),end=b?Math.min(Number(b),size-1):size-1;res.writeHead(206,{'Content-Type':type,'Accept-Ranges':'bytes','Content-Range':`bytes ${start}-${end}/${size}`,'Content-Length':end-start+1});fs.createReadStream(p,{start,end}).pipe(res);}else{res.writeHead(200,{'Content-Type':type,'Content-Length':size,'Accept-Ranges':'bytes'});fs.createReadStream(p).pipe(res);}});
await new Promise(r=>server.listen(0,'127.0.0.1',r));const browser=await chromium.launch(process.env.PLAYBACK_BROWSER?{executablePath:process.env.PLAYBACK_BROWSER}:{});const results=[];
try{for(const [name,w,h] of [['16x9',1440,900],['9x16',390,844]]){
 const page=await browser.newPage({viewport:{width:w,height:h}});
 fs.writeFileSync(root+`/player-${name}.html`,`<video controls muted preload="auto" style="width:100%;height:90vh;object-fit:contain" src="/RAON_AGENT_FACTORY_PROMO_V1_${name}.mp4"></video>`);
 await page.goto(`http://127.0.0.1:${server.address().port}/player-${name}.html`,{waitUntil:'domcontentloaded'});
 await page.waitForFunction(()=>document.querySelector('video').readyState>=2||document.querySelector('video').error,{},{timeout:20000});
 console.log('loaded',name,await page.evaluate(()=>{const v=document.querySelector('video');return {ready:v.readyState,error:v.error?.code??null};}));
 await page.evaluate(()=>Promise.race([document.querySelector('video').play(),new Promise((_,j)=>setTimeout(()=>j(Error('play timeout')),10000))]));
 await page.waitForTimeout(3500);
 const r=await page.evaluate(()=>{const v=document.querySelector('video');const advanced=v.currentTime>2;v.pause();return {duration:v.duration,width:v.videoWidth,height:v.videoHeight,advanced,error:v.error?.code??null};});r.seeks=[];
 for(const t of [25,43,56,64,77,86]){await page.evaluate(t=>document.querySelector('video').currentTime=t,t);await page.waitForFunction(t=>{const v=document.querySelector('video');return !v.seeking&&Math.abs(v.currentTime-t)<.2&&v.readyState>=2;},t,{timeout:15000});r.seeks.push({t,decoded:true});}
 if(!r.advanced||r.error)throw Error('playback failed '+JSON.stringify(r));await page.screenshot({path:root+`/playback-${name}.png`});results.push({name,...r});await page.close();
}fs.writeFileSync(root+'/playback.json',JSON.stringify(results,null,2));console.log(JSON.stringify(results));}finally{await browser.close();server.close();}
