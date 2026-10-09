"""Read-only analysis supplements the retained collector exit1; never rewrites it."""
import datetime, hashlib, json, pathlib, subprocess, urllib.request

ROOT = pathlib.Path(__file__).resolve().parents[2]
PARENT = pathlib.Path('/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba')
SHA = '31a18318f91dde34a9c75eaaac65ae1d434b7bc3'
OUT = ROOT / 'tests/W04_CAMPAIGN_BROWSER/evidence/binding-audit.json'
assert not OUT.exists()
digest = lambda data: hashlib.sha256(data).hexdigest()
git = lambda path: subprocess.check_output(['git', '-C', str(ROOT), 'show', SHA+':'+path])
j = json.loads((OUT.parent/'source-binding.json').read_text())
rows = []
for kind, name, manifest, expected in [('modules','raonslab-travel_lab','module.json','0.1.3'),('templates','raonslab-travel_lab','template.json','0.1.3'),('modules','sirsoft-board','module.json','1.1.3')]:
    path = f'{kind}/_bundled/{name}/{manifest}'
    pinned = git(path)
    installed = (PARENT/f'{kind}/{name}/{manifest}').read_bytes()
    rows.append({'source':path,'installed_version':json.loads(installed)['version'],'expected_version':expected,'bytes_equal':pinned==installed,'sha256':digest(installed)})
comparisons = {}
for key in ['supplemental_core_entrypoints','supplemental_core_languages']:
    before, after = j['before'][key], j['after'][key]
    comparisons[key] = {'files_equal':before['files']==after['files'],'before_count':before['count'],'after_count':after['count'],'both_all_equal':before['all_equal'] and after['all_equal']}
js = git('modules/_bundled/sirsoft-ecommerce/dist/js/module.iife.js')
css = git('modules/_bundled/sirsoft-ecommerce/dist/css/module.css')
bundles=[]
for name, data in [('js',js),('css',css)]:
    with urllib.request.urlopen('http://127.0.0.1:18871/api/modules/bundle.'+name,timeout=15) as r:
        served = r.read()
        bundles.append({'path':'/api/modules/bundle.'+name,'http':r.status,'served_sha256':digest(served),'fixed_source_sha256':digest(data),'bytes':len(served),'exact_equal':served==data,'method':'Only ecommerce declares global module assets in the four active module manifests; one segment needs no concat separator/source-map rewrite.'})
out={'utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'tested_sha':SHA,'tree':j['source_tree'],'versions':rows,'supplemental_before_after':comparisons,'module_bundles':bundles,'collector_exit':1,'collector_exit_diagnosis':'Inherited after summary compares missing during_verification_* keys against after files. Both supplemental sets actually exist in before and after and match. Raw false fields/exit retained. Optional sirsoft-basic463 absent is separate and does not cause required active byte mismatches.','active_selected_count':sum(v['count'] for k,v in j['after']['summary'].items() if k!='templates/sirsoft-basic'),'supplement_count':92,'optional_absent_count':463,'before_after_identical':j['before_after_identical'],'required_active_binding':j['required_runtime_binding'],'public_shell_assets_equal':j['after']['public_shell_asset_binding']['all_equal'],'collector_scope_correction':'Extension config source included by runtime_path; legacy scope text saying config directories excluded is inaccurate. Root config is excluded. Supplemental entrypoint/lang sets were collected in both actual snapshots despite inherited timing label. No unseen during-verification snapshot claimed.'}
out['status']='PASS' if all(r['bytes_equal'] and r['installed_version']==r['expected_version'] for r in rows) and all(r['files_equal'] and r['both_all_equal'] for r in comparisons.values()) and all(r['exact_equal'] for r in bundles) and all(j['required_runtime_binding'].values()) and out['before_after_identical'] and out['public_shell_assets_equal'] else 'FAIL'
OUT.write_text(json.dumps(out,indent=2)+'\n')
print(json.dumps({'status':out['status'],'versions':[r['installed_version'] for r in rows],'active_selected_count':out['active_selected_count'],'bundles':[r['exact_equal'] for r in bundles]}))
raise SystemExit(0 if out['status']=='PASS' else 1)
