import subprocess,hashlib,json,sys,time
from pathlib import Path
H=Path(__file__).resolve().parent;R=H.parents[2];phase=sys.argv[1]
sha='5783e6ba124061bdfae639cdaf9b1c14a83cdf03'
files=subprocess.check_output(['git','ls-tree','-r','--name-only',sha],cwd=R,text=True).splitlines()
product=[p for p in files if not p.startswith('docs/symphony/')]
prior=json.loads((H/'evidence/source-before.json').read_text())['file_sha256'] if phase=='after' else None
records={};changed=[]
for f in product:
 expected=prior[f] if prior else hashlib.sha256(subprocess.check_output(['git','show',sha+':'+f],cwd=R)).hexdigest()
 actual=hashlib.sha256((R/f).read_bytes()).hexdigest();records[f]=actual
 if actual!=expected:changed.append(f)
assert not changed,'Product bytes differ'
pins=['resources/js/core/TemplateApp.tsx','resources/js/core/template-engine/TemplateApp.tsx','resources/js/core/template-engine/ActionDispatcher.ts','scripts/travel-lab/LiveMysqlTest.php']
compiled={f:h for f,h in records.items() if f.startswith('public/build/core/') and f.endswith('.js')}
inherit={}
for f in records:
 if f.startswith('modules/_bundled/raonslab-travel_lab/src/') and ('Inquiry' in f or 'Cart' in f or 'Throttle' in f):
  old=subprocess.run(['git','show','fa5523175ac494cfbd13bbf89bf06b3ec91835a6:'+f],cwd=R,capture_output=True)
  inherit[f]={'current':records[f],'prior_atomic':hashlib.sha256(old.stdout).hexdigest() if old.returncode==0 else None,'exact_unchanged':old.returncode==0 and hashlib.sha256(old.stdout).hexdigest()==records[f]}
r={'source_sha':sha,'tree':'361f9a142572f8a6c0c28326c461a233a92aec4e','phase':phase,'utc':time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime()),'product_file_count':len(records),'product_inventory_sha256':hashlib.sha256(json.dumps(records,sort_keys=True).encode()).hexdigest(),'all_product_bytes_match_git':True,'compiled_core_js':compiled,'prior_atomic_file_inheritance':inherit,'file_sha256':records}
(H/'evidence'/f'source-{phase}.json').write_text(json.dumps(r,indent=2)+'\n');print(json.dumps({k:r[k] for k in ['phase','product_file_count','product_inventory_sha256','all_product_bytes_match_git']}))
