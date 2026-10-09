// Read-only bounded resolution: actual reload recovery and stabilized PC pointer.
import {base,pages,context,login,act,readPrivate,shot,step,finish,expect} from './common.mjs';
try {
  for(const width of [390,1440]) {
    const adm=await context(width,'admin'); await login(adm.p,'admin');
    await step(width,'adapter failed Retry followed by actual same-context reload',async()=>{
      const pat='**'+pages+'*';await adm.p.route(pat,r=>r.abort('failed'));
      await adm.p.goto(base+'/admin/travel-lab/campaigns');await expect(adm.p.getByTestId('campaign-pages-error')).toBeVisible();await adm.p.unroute(pat);
      const wait=adm.p.waitForResponse(r=>new URL(r.url()).pathname===pages);await act(adm.p.getByTestId('campaign-pages-retry'));const r=await wait;expect(r.status()).toBe(200);await adm.p.waitForTimeout(1500);
      const errorAfterRetry=await adm.p.getByTestId('campaign-pages-error').isVisible();expect(errorAfterRetry).toBe(true);
      await adm.p.reload();await expect(adm.p.getByTestId('campaign-slot-title')).toHaveCount(2);await expect(adm.p.getByTestId('campaign-pages-error')).toBeHidden();
      return {retryHttp:200,errorAfterRetry,reloadClearsError:true,reloadTitles:2,productRetryStatus:'FAIL',note:'This check proves reload workaround only; Retry remains failed.'};
    });await adm.c.close();
  }
  const adm=await context(1440,'admin');await login(adm.p,'admin');
  await step(1440,'PC native menu pointer after explicit scroll stabilization',async()=>{
    const id=readPrivate('transactions').inquiries.find(x=>x.width===1440).id;await adm.p.goto(base+'/admin/travel-lab/inquiries');
    const label=adm.p.getByText('TL-'+String(id).padStart(8,'0'),{exact:true});await expect(label).toBeVisible();await adm.p.waitForLoadState('networkidle');
    const button=adm.p.locator('tr').filter({has:label}).getByRole('button').last();await button.scrollIntoViewIfNeeded();await adm.p.waitForTimeout(750);const box=await button.boundingBox();await button.click();
    const detail=adm.p.getByText('상세 보기',{exact:true});await expect(detail).toBeVisible();await shot(adm.p,adm.m,'menu-stabilized');await detail.click();await expect(adm.p).toHaveURL(new RegExp('/inquiries/'+id));
    return {id,box,explicitScroll:true,stabilizationMs:750,menuVisible:true,navigated:true,mousePointer:true,note:'Original locator-click FAIL retained; coordinate mouse and stabilized pointer success narrow the issue to scrolling/timing, not general PC permission/navigation failure.'};
  });await adm.c.close();
}finally{await finish();}
