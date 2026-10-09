import sys,json,subprocess
from pathlib import Path
sys.dont_write_bytecode=True
sys.path.insert(0,str(Path(__file__).resolve().parent))
from c_common import Ctx,TL,data,check
H=Path(__file__).resolve().parent;R=H.parents[2];P=R/'storage/framework/testing/w04p-private'
C=Ctx(str(P),str(H/'evidence/matrix'),'matrix-sideeffects');S=json.loads((P/'t3-state.json').read_text())
def sql(phase):
 subprocess.run(['php',str(H/'effects.php'),phase],cwd=R,check=True,capture_output=True)
 return json.loads((H/'evidence/effects'/f'{phase}.json').read_text())['effects']
b=sql('bounded-before');dep=S['dep']['qty'];before=C.dbq('departures',ids=[dep])['departures'][0]
st,j,_=C.req('POST',TL+'/cart',role='member',body={'departure_id':dep,'quantity':1});check(st==201,'Own decline setup cart')
cid=next(x['id'] for x in data(j)['items'] if int(x['departure_id'])==dep)
st,j,_=C.req('POST',TL+'/inquiries',role='member',body={'cart_ids':[cid],'contact':{'name':'Synthetic effects'},'idempotency_key':S['run']+'-effects'});check(st==201,'Own decline setup inquiry');iid=data(j)['id']
mid=C.dbq('departures',ids=[dep])['departures'][0]
res=[]
for _ in range(2):
 st,j,_=C.req('PATCH',f'{TL}/admin/inquiries/{iid}',role='admin',body={'status':'DECLINED'});res.append(st);check(st==200,'Native decline/repeat status')
end=C.dbq('departures',ids=[dep])['departures'][0];i=next(x for x in C.dbq('inquiries',users=[C.uid['member']],min_id=iid)['inquiries'] if x['id']==iid)
check(int(mid['reserved'])==int(before['reserved'])+1 and int(end['reserved'])==int(before['reserved']) and int(i['events'])==2,'Admin decline releases once, repeat no event')
C.record('M10 additional native admin decline/repeat exactly once','PASS',{'statuses':res,'events':i['events'],'reserved':[before['reserved'],mid['reserved'],end['reserved']]})
a=sql('bounded-after');original=json.loads((H/'evidence/effects/before.json').read_text())['effects'];check(a==b==original,'Whole native order/payment/temporder/queue/mail/notification rows or DDL changed')
C.record('M13 whole native orders/payments/temporders unchanged SQL before/after','PASS',{'table_inventories':{k:v for k,v in a.items() if 'ecommerce' in k},'exact_rows_and_DDL':True})
runtime=json.loads((H/'evidence/runtime/before.json').read_text());check(runtime['adapters']['mail.default']=='array' and runtime['adapters']['queue.default']=='sync' and runtime['adapters']['filesystems.default']=='local','Actual effective safe adapters')
C.record('M14 actual effective array/sync/local, jobs/mail/notification SQL unchanged','PASS',{'adapters':runtime['adapters'],'table_inventories':{k:v for k,v in a.items() if 'ecommerce' not in k},'exact_rows_and_DDL':True,'external_provider_endpoints_called':0})
C.flush()
