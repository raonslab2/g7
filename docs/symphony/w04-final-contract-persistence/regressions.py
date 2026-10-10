"""Actual unchanged selected source fixtures, each with full populated TEST snapshot/restore."""
import subprocess,json,hashlib,time,sys
from pathlib import Path
H=Path(__file__).resolve().parent;R=H.parents[2];P=R/'storage/framework/testing/w04p-private'
suites={
 'mysql-live-guard-corrected':['scripts/travel-lab/LiveMysqlTest.php'],
 'mysql-live-fresh-baseline34':['scripts/travel-lab/LiveMysqlTest.php'],
 'support-private':['modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportApiTest.php','modules/_bundled/raonslab-travel_lab/tests/Feature/W03SupportHardeningTest.php','--filter','questions_require_sanctum_authentication|members_only_see_and_update_their_own_private_questions|support_admin_reads_all_but_needs_update_permission_to_edit_others|admin_answer_through_native_board_admin_is_visible_to_author_only|legacy_support_role_without_native_board_admin_cannot_reach_foreign_questions|foreign_access_requires_both_support_permission_and_native_board_admin|self_scoped_native_board_manager_sees_only_own_questions|question_edit_goes_through_native_post_service_audit_without_notifications|restricted_grants_without_owner_metadata_cannot_bypass_foreign_scope'],
}
for label,args in suites.items():
 if len(sys.argv)>1 and label not in sys.argv[1:]:continue
 cmd=['/usr/bin/php8.3',str(H/'snapshot.php'),label,'run','/usr/bin/php8.3','vendor/bin/phpunit','--bootstrap',str(H/'test-bootstrap.php'),*args]
 t=time.time();proc=subprocess.run(cmd,cwd=R,capture_output=True);(P/(label+'-driver.log')).write_bytes(proc.stdout+proc.stderr)
 raw=(R/f'storage/framework/testing/w04p-{label}-native.log').read_bytes() if (R/f'storage/framework/testing/w04p-{label}-native.log').exists() else b''
 summary=[l for l in raw.decode(errors='replace').splitlines() if l.startswith(('PHPUnit ','Runtime:','Time:','OK (','Tests:','FAILURES!','ERRORS!','There was','There were','No tests executed'))]
 restored=json.loads((H/'evidence'/label/'result.json').read_text()) if (H/'evidence'/label/'result.json').exists() else {}
 result={'command':[str(x).replace(str(R)+'/','') for x in cmd],'exit':proc.returncode,'seconds':round(time.time()-t,3),'native_log_sha256':hashlib.sha256(raw).hexdigest(),'summary':summary,'restored_exactly':restored.get('exact_tables_rows_ddl',False),'source_file_hashes':{a:hashlib.sha256((R/a).read_bytes()).hexdigest() for a in args if a.endswith('.php')}}
 (H/'evidence'/label/'summary.json').write_text(json.dumps(result,indent=2)+'\n');print(label,result['exit'],summary,result['restored_exactly'],flush=True)
 if not result['restored_exactly'] or proc.returncode:raise SystemExit('STOP: fixture/restore failure retained')
