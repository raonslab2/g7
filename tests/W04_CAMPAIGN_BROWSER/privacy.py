"""공개 증거 위생 검사. 허용된 인계와 자체 private 장부만 읽고 값·OCR 원문을 출력하지 않는다."""
from pathlib import Path
import json,re,os,stat,subprocess,hashlib,datetime,sys
root=Path(__file__).resolve().parents[2]
priv=root/'storage/framework/testing/w04-campaign-fc51-private'
p=Path('/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba/storage/framework/testing/travel-campaign-browser-31a18318/access.json')
assert not p.is_symlink() and stat.S_IMODE(p.stat().st_mode)==0o600 and stat.S_IMODE(p.parent.stat().st_mode)==0o700
access=json.loads(p.read_text());assert access['base_url']=='http://127.0.0.1:18871'
assert datetime.datetime.fromisoformat(access['expires_at'])>datetime.datetime.now(datetime.timezone.utc)
known=[]
for role in ['member','other_member','admin']:
 known.extend(access[role][k] for k in ['email','password','bearer_token'])
for f in priv.glob('*.json'):
 j=json.loads(f.read_text())
 if f.name=='issued.json':known.extend(x['token'] for x in j)
 if f.name=='contact.json':known.append(j.get('name',''))
 if f.name=='actor-names.json':known.extend(j)
 if f.name.startswith('baseline-'):
  for k in ['creator','updater']:
   for sub in ['name','uuid']:
    if isinstance(j.get(k),dict):known.append(j[k].get(sub,''))
known=list({k for k in known if isinstance(k,str) and len(k)>=6})
regex=[re.compile(r'[\w.+-]+@[\w-]+\.[\w.-]+'),re.compile(r'\b[a-f\d]{8}(?:-[a-f\d]{4}){3}-[a-f\d]{12}\b',re.I),re.compile(r'\b\d+\|[A-Za-z\d]{20,}'),re.compile(r'signature=[A-Za-z\d]{12,}')]
rows=[];quarantine=priv/'quarantine';quarantine.mkdir(mode=0o700,exist_ok=True)
dest=root/'tests/W04_CAMPAIGN_BROWSER/evidence/privacy.json'
prev=json.loads(dest.read_text()) if dest.exists() else []
text_only='--text-only' in sys.argv
reused_png_count=0
files=list((root/'tests/W04_CAMPAIGN_BROWSER').rglob('*'))
report=root/'docs/symphony/W04_CAMPAIGN_BROWSER_FINAL.md'
if report.exists():files.append(report)
for f in sorted(files):
 if not f.is_file() or f.name in ['privacy.json','MANIFEST.sha256','manifest.json']:continue
 is_png=f.suffix=='.png'
 if is_png and text_only:
  digest=hashlib.sha256(f.read_bytes()).hexdigest()
  assert any(r['path']==str(f.relative_to(root)) and r['sha256']==digest and r['status']=='PASS' for audit in prev for r in audit['rows']), 'PNG needs fresh full OCR scan'
  reused_png_count+=1
  continue
 if is_png:
  text=subprocess.check_output(['tesseract',str(f),'stdout'],stderr=subprocess.DEVNULL,env={**os.environ,'OMP_THREAD_LIMIT':'1'},text=True,timeout=40)
 else:
  text=f.read_text(errors='replace')
 known_hits=sum(1 for k in known if k in text);patterns=sum(len(r.findall(text)) for r in regex)
 row={'path':str(f.relative_to(root)),'sha256':hashlib.sha256(f.read_bytes()).hexdigest(),'known_private_hits':known_hits,'pattern_hits':patterns,'kind':'PNG_OCR' if is_png else 'text','status':'PASS' if not known_hits and not patterns else 'FAIL'}
 if row['status']=='FAIL' and is_png:
  target=quarantine/f.name;f.rename(target);target.chmod(0o600);row['public_artifact']='OMITTED_PRIVATE_QUARANTINE';row['raw_original_retained_private']=True
 rows.append(row)
# 후속 검사 기록을 기존 결과 뒤에 추가한다.
prev.append({'utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'scope':'own scoped report/scripts/JSON/PNG only; exact private values + patterns; no raw OCR persisted','mode':'text_only_with_existing_exact_png_digest_audits' if text_only else 'full_ocr','reused_png_count':reused_png_count,'rows':rows,'public_fail_count':sum(x['status']=='FAIL' and x.get('public_artifact')!='OMITTED_PRIVATE_QUARANTINE' for x in rows),'quarantined_png_count':sum(x.get('public_artifact')=='OMITTED_PRIVATE_QUARANTINE' for x in rows)})
dest.write_text(json.dumps(prev,indent=2)+'\n')
print(json.dumps({'files':len(rows),'public_fail_count':prev[-1]['public_fail_count'],'quarantined_png_count':prev[-1]['quarantined_png_count']}))
raise SystemExit(1 if prev[-1]['public_fail_count'] else 0)
