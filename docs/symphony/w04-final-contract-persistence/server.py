"""Own process group only; no parent APP process/service interaction."""
import os,json,subprocess,signal,socket,time,sys
from pathlib import Path
HERE=Path(__file__).resolve().parent;ROOT=HERE.parents[2];PRIV=ROOT/'storage/framework/testing/w04p-private';E=HERE/'evidence/service';E.mkdir(exist_ok=True,parents=True)
def closed():
 s=socket.socket();s.settimeout(.2)
 try:s.connect(('127.0.0.1',18880));return False
 except OSError:return True
 finally:s.close()
def start(tag):
 assert closed(),'Port occupied; no foreign kills'
 # Native guard writes minimum own environment privately; stdout has no values.
 p=subprocess.run(['php',str(HERE/'emit-env.php')],cwd=ROOT,capture_output=True,check=True)
 env=json.loads((PRIV/'server-environment.json').read_text());assert ((PRIV/'server-environment.json').stat().st_mode & 0o777)==0o600;env['PHP_CLI_SERVER_WORKERS']='4'
 log=open(PRIV/f'server-{tag}.log','wb')
 proc=subprocess.Popen(['php','-S','127.0.0.1:18880',str(HERE/'http-router.php')],cwd=ROOT,env=env,stdin=subprocess.DEVNULL,stdout=log,stderr=log,start_new_session=True)
 (PRIV/'server.json').write_text(json.dumps({'pgid':proc.pid,'pid':proc.pid,'tag':tag}));os.chmod(PRIV/'server.json',0o600)
 for _ in range(60):
  if not closed():break
  assert proc.poll() is None,'Own server exited';time.sleep(.1)
 assert not closed(),'Own port not opened'
 r={'tag':tag,'pid':proc.pid,'pgid':proc.pid,'start_utc':time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime()),'port':18880,'workers':4}
 (E/f'start-{tag}.json').write_text(json.dumps(r,indent=2)+'\n');print(json.dumps(r))
def stop():
 v=json.loads((PRIV/'server.json').read_text());pgid=v['pgid'];pids=[]
 for f in Path('/proc').glob('[0-9]*/stat'):
  try:
   raw=f.read_text();fs=raw[raw.rfind(')')+2:].split()
   if int(fs[2])==pgid:pids.append(int(f.parent.name))
  except FileNotFoundError:pass
 # Pgid from our saved Popen, verify own cwd before signal; no arbitrary pid accepted.
 for pid in pids: assert os.readlink(f'/proc/{pid}/cwd')==str(ROOT)
 if pids:os.killpg(pgid,signal.SIGTERM)
 for _ in range(100):
  alive=[]
  for pid in pids:
   try:
    raw=Path(f'/proc/{pid}/stat').read_text();state=raw[raw.rfind(')')+2:].split()[0]
    if state!='Z':alive.append(pid)
   except FileNotFoundError:pass
  if not alive and closed():break
  time.sleep(.1)
 assert not alive and closed(),'Own processes/port remain'
 r={'tag':v['tag'],'stopped_utc':time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime()),'owned_pids':pids,'own_live_processes':0,'port_closed':True}
 (E/f'stop-{v["tag"]}.json').write_text(json.dumps(r,indent=2)+'\n');print(json.dumps(r))
if __name__=='__main__':
 if sys.argv[1]=='start':start(sys.argv[2])
 elif sys.argv[1]=='stop':stop()
 else:raise SystemExit('Unsupported mode')
