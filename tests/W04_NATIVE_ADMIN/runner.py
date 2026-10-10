"""Own browser harness runner: record actual starting script/dependency hashes and bounded logs."""
import hashlib,json,pathlib,subprocess,sys,datetime,re
root=pathlib.Path(__file__).resolve().parents[2]
out=root/'docs/symphony/evidence/W04_NATIVE_ADMIN'
script=pathlib.Path(sys.argv[1])
files=[script,pathlib.Path('tests/W04_NATIVE_ADMIN/common.mjs'),pathlib.Path('tests/W04_NATIVE_ADMIN/render-tools.mjs'),pathlib.Path('tests/W04_NATIVE_ADMIN/audit-api.mjs')]
record={'command':['node',str(script)],'started':datetime.datetime.now(datetime.timezone.utc).isoformat(),'files':[{'path':str(p),'sha256':hashlib.sha256((root/p).read_bytes()).hexdigest()}for p in files if(root/p).is_file()]}
# Keep the exact versions of these SAFE scripts, no process env/credentials/private ledger.
stamp=datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%S%f');record['execution_id']=script.stem+'-'+stamp;archive=out/'executed-scripts'/record['execution_id'];archive.mkdir(parents=True,exist_ok=True)
for p in files:
 if(root/p).is_file():(archive/p.name).write_bytes((root/p).read_bytes())
r=subprocess.run(['node',str(script)],cwd=root,stdout=subprocess.PIPE,stderr=subprocess.STDOUT,text=True)
log=r.stdout
log=re.sub(r'[\w.+-]+@[\w.-]+\.[\w-]+','[MASKED EMAIL]',log)
log=re.sub(r'\b\d+\|[A-Za-z0-9]{30,}\b','[MASKED TOKEN]',log)
log=re.sub(r'[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}','[MASKED UUID]',log,flags=re.I)
log=re.sub(r'Bearer\s+\S+','Bearer [MASKED]',log,flags=re.I)
record.update(exit_code=r.returncode,finished=datetime.datetime.now(datetime.timezone.utc).isoformat(),sanitized_output=log)
(out/(record['execution_id']+'-command.json')).write_text(json.dumps(record,indent=2)+'\n')
print(log,end='');sys.exit(r.returncode)
