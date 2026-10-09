"""Own fixed-source suites. Each native fixture runs under a fresh full TEST snapshot/restore."""
import json,subprocess,time,hashlib,os,sys
from pathlib import Path
root=Path(__file__).resolve().parents[3]; here=Path(__file__).resolve().parent
os.umask(0o077)
suites={
 'page-regression':['modules/_bundled/sirsoft-page/tests','--filter','^(.*\\\\)?(PageServiceTest|PageControllerTest|PageVersionScopeTest|PublicPageControllerTest|PageSitemapIndexTest)::'],
 'board-regression':['modules/_bundled/sirsoft-board/tests/Feature/User/SecretPostCommentAccessTest.php'],
 'core-regression':['tests/Unit/Helpers/PermissionHelperScopeTest.php'],
}
for label,args in suites.items():
 if len(sys.argv)>1 and label not in sys.argv[1:]:continue
 cmd=['/usr/bin/php8.3',str(here/'snapshot.php'),label,'run','/usr/bin/php8.3','vendor/bin/phpunit','--bootstrap',str(here/'test-bootstrap.php'),*args]
 started=time.time();p=subprocess.run(cmd,cwd=root,capture_output=True);private=root/'storage/framework/testing/w04c-private'
 (private/(label+'-driver.log')).write_bytes(p.stdout+p.stderr)
 native=root/('storage/framework/testing/w04c-'+label+'-native.log');raw=native.read_bytes() if native.exists() else b''
 safe=[l for l in raw.decode(errors='replace').splitlines() if l.startswith(('PHPUnit ','Runtime:','Time:','OK (','Tests:','FAILURES!','ERRORS!','There was','There were','No tests executed'))]
 restored=json.loads((here/'evidence'/label/'result.json').read_text()) if (here/'evidence'/label/'result.json').exists() else {}
 result={'command':[str(x).replace(str(root)+'/','') for x in cmd],'exit':p.returncode,'seconds':round(time.time()-started,3),'native_log_sha256':hashlib.sha256(raw).hexdigest(),'summary':safe,'restored_exactly':restored.get('exact_tables_rows_ddl',False)}
 (here/'evidence'/label/'summary.json').write_text(json.dumps(result,indent=2)+'\n')
 print(label,result['exit'],safe,result['restored_exactly'],flush=True)
 if not result['restored_exactly']:raise SystemExit('BLOCKED restoration; stop all subsequent scopes')
