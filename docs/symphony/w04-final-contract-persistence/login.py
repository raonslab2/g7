import sys,json,os,time
from pathlib import Path
sys.dont_write_bytecode=True
sys.path.insert(0,str(Path(__file__).resolve().parent))
from c_common import Ctx,data,check
HERE=Path(__file__).resolve().parent;ROOT=HERE.parents[2];PRIV=ROOT/'storage/framework/testing/w04p-private'
C=Ctx(str(PRIV),str(HERE/'evidence/http'),'login-'+sys.argv[1]);before={r:C.A[r]['bearer_token'] for r in C.tokens}
for role in C.tokens:
 path='/api/auth/admin/login' if role=='admin' else '/api/auth/login'
 st,j,_=C.req('POST',path,body={'email':C.A[role]['email'],'password':C.A[role]['password']})
 check(st==200,'Native login failed '+str(st));t=data(j)['token'];C.add_secret(t);C.A[role]['bearer_token']=t
 C.record('native login '+role,'PASS',{'status':st,'new_token':t!=before[role]})
(PRIV/'access.json').write_text(json.dumps(C.A));os.chmod(PRIV/'access.json',0o600);C.flush()
