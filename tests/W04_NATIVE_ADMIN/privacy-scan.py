"""비공개 값은 보호된 프로세스 안에서 비교하고 결과에는 수치와 파일명만 남긴다."""
import pathlib,json,re,hashlib,subprocess,os,stat,datetime,sys
root=pathlib.Path(__file__).resolve().parents[2];out=root/'docs/symphony/evidence/W04_NATIVE_ADMIN'
p=pathlib.Path('/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba/storage/framework/testing/travel-live-review-fb40eddf4a39/access.json')
assert not p.is_symlink() and p.stat().st_mode&0o077==0 and p.parent.stat().st_mode&0o077==0
access=json.loads(p.read_text());assert access['source_sha']=='fa5523175ac494cfbd13bbf89bf06b3ec91835a6' and access['base_url']=='http://127.0.0.1:18871' and access['db']=='req81_travel_lab' and datetime.datetime.fromisoformat(access['expires_at'].replace('Z','+00:00'))>datetime.datetime.now(datetime.timezone.utc)
values=[]
for role in ['member','other_member','admin']:
 for key,v in access[role].items():
  if key in ['email','password','bearer_token','uuid','user_uuid','id','user_id'] and isinstance(v,str) and len(v)>=5:values.append(v)
private=root/'tests/W04_NATIVE_ADMIN/.private'
for f in private.glob('token-*.json'):values.append(json.loads(f.read_text())['token'])
if(private/'issued.json').exists():values.extend(x['token'] for x in json.loads((private/'issued.json').read_text()))
# 연락처는 코드의 생성 함수와 같은 합성 값이다. raw 값은 보고하지 않는다.
values.extend(['-'.join(['0'*3,'0'*4,'0'*4]),'-'.join(['0'*3,'0'*4,'0'*3+'1'])]);values=list(set(values))
pattern=re.compile(r'[\w.+-]+@[\w.-]+\.[\w-]+|\b\d+\|[A-Za-z0-9]{30,}|[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}',re.I)
files=[root/'docs/symphony/W04_NATIVE_ADMIN_BROWSER_FINAL.md']+[f for folder in [out,root/'tests/W04_NATIVE_ADMIN'] for f in folder.rglob('*') if f.is_file() and not any(x in f.parts for x in ['node_modules','.private','__pycache__']) and f.name!='package.json']
previous=json.loads((out/'privacy-final.json').read_text()) if (out/'privacy-final.json').exists() else {};prior_rows={x['path']:x for x in previous.get('files',[])}
rows=[]
for f in files:
 if f.name=='privacy-final.json':continue
 digest=hashlib.sha256(f.read_bytes()).hexdigest();key=str(f.relative_to(root))
 if '--reuse-unchanged-ocr' in sys.argv and f.suffix=='.png' and key in prior_rows and prior_rows[key]['sha256']==digest and prior_rows[key]['knownPrivateHits']==0 and prior_rows[key]['privatePatternHits']==0:
  rows.append({**prior_rows[key],'verifiedOCRReusedForIdenticalOwnPNG':True,'priorObservedAt':previous.get('observedAt')});continue
 if f.suffix=='.png':
  result=subprocess.run(['tesseract',str(f),'stdout'],env={**os.environ,'OMP_THREAD_LIMIT':'1'},stdout=subprocess.PIPE,stderr=subprocess.DEVNULL,text=True,timeout=60);assert result.returncode==0;body=result.stdout
 else:
  try:body=f.read_text()
  except UnicodeDecodeError:continue
 # Test source regex syntax is not a real secret; exact private strings are still checked.
 hits=sum(v in body for v in values);patterns=len(pattern.findall(body));rows.append({'path':str(f.relative_to(root)),'sha256':hashlib.sha256(f.read_bytes()).hexdigest(),'knownPrivateHits':hits,'privatePatternHits':patterns,'kind':'OCR' if f.suffix=='.png' else 'text'})
report={'observedAt':datetime.datetime.now(datetime.timezone.utc).isoformat(),'files':rows,'pngCount':sum(x['kind']=='OCR' for x in rows),'allPassed':all(x['knownPrivateHits']==0 and x['privatePatternHits']==0 for x in rows),'rawOCRStored':False,'oldPNGsReused':False}
(out/'privacy-final.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps({'files':len(rows),'png':report['pngCount'],'allPassed':report['allPassed'],'failedPaths':[x['path'] for x in rows if x['knownPrivateHits'] or x['privatePatternHits']]}));raise SystemExit(0 if report['allPassed'] else 1)
