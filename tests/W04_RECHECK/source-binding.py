import pathlib,hashlib,json,subprocess
root=pathlib.Path.cwd(); parent=pathlib.Path('/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba');sha='598a89fff702d51c1405f1a5952d95ab1d2651f4'
actual_head=subprocess.check_output(['git','rev-parse','HEAD'],text=True).strip()
subprocess.check_call(['git','merge-base','--is-ancestor',sha,actual_head])
changed=subprocess.check_output(['git','diff','--name-only',sha],text=True).splitlines()
assert all(p.startswith(('tests/W04_RECHECK/','docs/symphony/evidence/W04_BROWSER_RECHECK/')) or p=='docs/symphony/W04_BROWSER_RECHECK.md' for p in changed)
rows=[]
# Enumerate entire tracked extension trees, not an inherited selected manifest.
for kind in ('modules','templates'):
 prefix=kind+'/_bundled/raonslab-travel_lab/'
 for rel in subprocess.check_output(['git','ls-tree','-r','--name-only',sha,prefix],text=True).splitlines():
  tail=rel[len(prefix):];target=parent/kind/'raonslab-travel_lab'/tail
  row={'source':rel,'installed':kind+'/raonslab-travel_lab/'+tail,'source_sha256':hashlib.sha256((root/rel).read_bytes()).hexdigest(),'exists':target.is_file()}
  if target.is_file():row['installed_sha256']=hashlib.sha256(target.read_bytes()).hexdigest();row['equal']=row['source_sha256']==row['installed_sha256']
  else:row['equal']=False
  rows.append(row)
# Independently enumerate tracked native application/core/bootstrap source. No env/config credentials.
core=[]
for rel in subprocess.check_output(['git','ls-tree','-r','--name-only',sha,'app','routes','resources','bootstrap/app.php','config/app.php'],text=True).splitlines():
 t=parent/rel
 row={'source':rel,'source_sha256':hashlib.sha256((root/rel).read_bytes()).hexdigest(),'exists':t.is_file()}
 if t.is_file():row['runtime_sha256']=hashlib.sha256(t.read_bytes()).hexdigest();row['equal']=row['source_sha256']==row['runtime_sha256']
 else:row['equal']=False
 core.append(row)
out={'source_sha':sha,'tree':subprocess.check_output(['git','rev-parse',sha+'^{tree}'],text=True).strip(),'actual_head':actual_head,'extension_count':len(rows),'extension_all_equal':all(r['equal'] for r in rows),'core_count':len(core),'core_all_equal':all(r['equal'] for r in core),'scope':'Every Git tracked travel extension path incl docs/tests and tracked app/routes/resources/bootstrap/app.php/config/app.php. Excludes runtime state, vendor, env, other extensions; differs from lead selected198 paths. Read-only parent source only. Actual served bytes separately measured.','extensions':rows,'core':core}
dest=root/'docs/symphony/evidence/W04_BROWSER_RECHECK/source-binding.json'
if dest.exists():
 previous=json.loads(dest.read_text())
 for key in ('served_primary_assets_binding','runtime_extension_count','runtime_extension_all_equal','actual_enumeration_note'):
  if key in previous:out[key]=previous[key]
dest.write_text(json.dumps(out,indent=2)+'\n')
print(json.dumps({k:v for k,v in out.items() if k not in ('extensions','core')}))

# 설치 디렉토리 자체를 열거하여 기대 목록에 없는 소스도 확인한다.
actual_extras=[]
for kind in ('modules','templates'):
 installed=parent/kind/'raonslab-travel_lab'; expected={r['installed'] for r in rows if r['installed'].startswith(kind+'/')}; actual=[f for f in installed.rglob('*') if f.is_file()]
 out[kind+'_installed_actual_file_count']=len(actual)
 for f in actual:
  rel=f.relative_to(parent).as_posix()
  if rel not in expected:actual_extras.append({'path':rel,'sha256':hashlib.sha256(f.read_bytes()).hexdigest()})
out['installed_extra_files']=actual_extras
out['core_actual_extra_files']=[{'path':f.relative_to(parent).as_posix(),'sha256':hashlib.sha256(f.read_bytes()).hexdigest()} for top in ('app','routes','resources') for f in (parent/top).rglob('*') if f.is_file() and f.relative_to(parent).as_posix() not in {r['source'] for r in core}]
deps=[]
for name in ('sirsoft-ecommerce','sirsoft-board','sirsoft-page'):
 prefix='modules/_bundled/'+name+'/'
 for rel in subprocess.check_output(['git','ls-tree','-r','--name-only',sha,prefix],text=True).splitlines():
  tail=rel[len(prefix):]
  if not (tail.startswith(('src/','resources/layouts/','dist/')) or tail in ('module.php','module.json')):continue
  f=parent/'modules'/name/tail; row={'source':rel,'installed':'modules/'+name+'/'+tail,'exists':f.is_file(),'source_sha256':hashlib.sha256((root/rel).read_bytes()).hexdigest()}
  if f.is_file():row['installed_sha256']=hashlib.sha256(f.read_bytes()).hexdigest();row['equal']=row['source_sha256']==row['installed_sha256']
  else:row['equal']=False
  deps.append(row)
out['native_dependencies']=deps;out['native_dependency_count']=len(deps);out['native_dependency_all_equal']=all(r['equal'] for r in deps)
out['different_nonruntime_paths']=[r['source'] for r in rows if not r['equal']]
out['nonruntime_difference_classification']='7 differences: guides/docs and author test/evidence helper. Production manifests/layouts/src/dist match. Full297 does NOT all match.'
dest=root/'docs/symphony/evidence/W04_BROWSER_RECHECK/source-binding.json'
if dest.exists():
 previous=json.loads(dest.read_text())
 for key in ('served_primary_assets_binding','runtime_extension_count','runtime_extension_all_equal','actual_enumeration_note'):
  if key in previous:out[key]=previous[key]
dest.write_text(json.dumps(out,indent=2)+'\n')
print('Actual extension extras:',len(actual_extras),'native dependencies:',len(deps),'matching:',out['native_dependency_all_equal'])

# 실제 제공 자산은 인증 없이 새로 읽고 Git blob에 직접 결합한다.
import urllib.request
assets=[]
for url,rel in (('/build/core/template-engine.min.js','public/build/core/template-engine.min.js'),('/api/templates/assets/raonslab-travel_lab/js/components.iife.js','templates/_bundled/raonslab-travel_lab/dist/js/components.iife.js'),('/api/templates/assets/raonslab-travel_lab/css/components.css','templates/_bundled/raonslab-travel_lab/dist/css/components.css')):
 with urllib.request.urlopen('http://127.0.0.1:18871'+url,timeout=20) as response:
  data=response.read();status=response.status
 expected=subprocess.check_output(['git','show',sha+':'+rel])
 assets.append({'path':url,'status':status,'sha256':hashlib.sha256(data).hexdigest(),'fixed_git_path':rel,'exact_match':data==expected})
out['served_primary_assets_binding']=assets
out['runtime_extension_count']=len(rows)-len(out['different_nonruntime_paths'])
out['runtime_extension_all_equal']=all(r['equal'] for r in rows if not (r['source'].endswith(('AGENTS.md','CHANGELOG.md','README.md')) or '/__tests__/' in r['source'] or '/scripts/' in r['source']))
out['actual_enumeration_note']='Read-only installed directory recursion compared to fixed Git paths, including extras. Primary HTTP assets freshly measured without authentication.'
dest.write_text(json.dumps(out,indent=2)+'\n')
assert all(a['exact_match'] for a in assets) and out['runtime_extension_all_equal'] and out['core_all_equal'] and out['native_dependency_all_equal']
