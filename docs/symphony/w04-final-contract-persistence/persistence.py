"""Actual own HTTP writes/reads before and after a separately stopped/restarted server."""
import sys,json,os,hashlib,subprocess
from pathlib import Path
sys.dont_write_bytecode=True
sys.path.insert(0,str(Path(__file__).resolve().parent))
from c_common import Ctx,TL,BD,data,rows,check
H=Path(__file__).resolve().parent;R=H.parents[2];P=R/'storage/framework/testing/w04p-private';mode=sys.argv[1]
C=Ctx(str(P),str(H/'evidence/persistence'),mode)
STATE=P/'persistence.json';S=json.loads(STATE.read_text()) if STATE.exists() else {};W=json.loads((P/'t3-state.json').read_text())
def req(m,path,role=None,body=None,expected=200):
 st,j,meta=C.req(m,path,role=role,body=body);check(st==expected,f'HTTP {m} {path}: {st}, expected {expected}');return data(j)
def safe_digest(x):return hashlib.sha256(json.dumps(x,sort_keys=True,separators=(',',':')).encode()).hexdigest()
def facts():
 i=req('GET',f'{TL}/inquiries/{S["iid"]}','member')
 q=req('GET',f'{TL}/support/questions/{S["qid"]}','member')
 camp=req('GET',f'{TL}/campaigns/{S["slug"]}')
 sql=C.dbq('inquiries',users=[C.uid['member']],min_id=S['iid'])['inquiries'];sql=next(x for x in sql if x['id']==S['iid'])
 items=C.dbq('inquiry_items',ids=[S['iid']],users=[C.uid['member']])['items']
 dep=C.dbq('departures',ids=[S['dep']])['departures'][0]
 check(i['status']=='TEST_ACCEPTED' and sql['status']=='TEST_ACCEPTED' and int(sql['events'])==3 and int(sql['items'])==1 and int(dep['reserved'])==1,'Persisted inquiry/state/event/capacity')
 check(len(q['answers'])==1,'Native answer persisted')
 check(q['content']==S['question_body'] and q['answers'][0]['content']==S['answer_body'],'Native private question/answer body persisted')
 check(camp['content']==S['campaign_body'] and camp['current_version']==S['campaign_version'],'Published campaign body/version persisted')
 req('GET',f'{TL}/inquiries/{S["iid"]}','other_member',expected=404)
 req('GET',f'{TL}/support/questions/{S["qid"]}','other_member',expected=404)
 req('GET',f'{TL}/support/questions/{S["qid"]}',expected=401)
 foreign=req('GET',TL+'/support/questions','other_member');check(not any(x['id']==S['qid'] for x in rows({'data':foreign})),'Foreign list excludes private question')
 # SQL whole rows/DDL of retained inquiry children, native post/comment and Page/version are privately compared separately.
 inventory=C.dbq('persistence_inventory',users=[C.uid['member']],iid=S['iid'],qid=S['qid'],page=S['page_id'])['inventory']
 r={'persistent_native_row_inventory':inventory,'inquiry_id':S['iid'],'question_id':S['qid'],'page_id':S['page_id'],'inquiry_state':i['status'],'inquiry_item_digest':safe_digest(items),'inquiry_sql':sql,'reserved':dep['reserved'],'capacity':dep['capacity'],'question_body_digest':safe_digest(q['content']),'answer_body_digest':safe_digest(q['answers'][0]['content']),'campaign_body_digest':safe_digest(camp['content']),'campaign_version':camp['current_version'],'foreign_inquiry404':True,'foreign_question404':True,'guest_question401':True,'foreign_list_excludes':True}
 return r
if mode=='create':
 dep=W['dep']['spare'];cart=req('POST',TL+'/cart','member',{'departure_id':dep,'quantity':1},201);cid=next(x['id'] for x in cart['items'] if int(x['departure_id'])==dep)
 body={'cart_ids':[cid],'contact':{'name':'Synthetic persistence'},'idempotency_key':W['run']+'-restart'}
 i=req('POST',TL+'/inquiries','member',body,201);iid=i['id']
 req('PATCH',f'{TL}/admin/inquiries/{iid}','admin',{'status':'UNDER_REVIEW'})
 req('PATCH',f'{TL}/admin/inquiries/{iid}','admin',{'status':'TEST_ACCEPTED'})
 question_body='Private synthetic persistence '+os.urandom(12).hex();answer_body='Native synthetic answer '+os.urandom(12).hex()
 q=req('POST',TL+'/support/questions','member',{'title':'Synthetic restart question','content':question_body},201);qid=q['id']
 req('POST',f'{BD}/admin/board/travel-lab-questions/posts/{qid}/comments','admin',{'content':answer_body,'is_secret':True},201)
 slug='travel-lab-campaign-autumn-escape';pages=req('GET','/api/modules/sirsoft-page/admin/pages','admin');pg=next(x for x in pages['data'] if x['slug']==slug)
 content='Synthetic published persistence '+os.urandom(12).hex()
 req('PUT',f'/api/modules/sirsoft-page/admin/pages/{pg["id"]}','admin',{'title':{'ko':'합성 지속성 기획전','en':content},'content':{'ko':content,'en':content},'content_mode':'text'})
 req('PATCH',f'/api/modules/sirsoft-page/admin/pages/{pg["id"]}/publish','admin',{'published':True})
 camp=req('GET',f'{TL}/campaigns/{slug}')
 S={'iid':iid,'qid':qid,'dep':dep,'page_id':pg['id'],'slug':slug,'question_body':question_body,'answer_body':answer_body,'campaign_body':camp['content'],'campaign_version':camp['current_version']}
 S['before']=facts();STATE.write_text(json.dumps(S));os.chmod(STATE,0o600)
 C.record('inquiry/private-native-answer/campaign saved before actual process stop','PASS',S['before'])
elif mode=='verify':
 after=facts();check(after==S['before'],'Restart changed semantic persistent state')
 C.record('new process native relogin retains same inquiry/items/events/reserved/private native answer/campaign version','PASS',{'before':S['before'],'after':after,'exact_semantic_equal':True})
else:raise SystemExit('Unsupported mode')
C.flush()
