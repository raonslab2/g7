"""Read only /proc metadata. Never open a foreign Request's files or credentials."""
import datetime
import json
import os
from pathlib import Path

root = str(Path(__file__).resolve().parents[3])
prior = ['req_bed1c2288b1946de95782fe1a4acff75', 'req_3e70e7ceddd34beea51a17da727584bc', 'req_337b3638df9b4b958b0627e0b262a54a',
         'req_dd6208f86a0b487796de37b9c4365cd4', 'req_caef46f3473043048b5b4bc2ec41eaea',
         'req_ea58da0bbb7043999b64641fcfc32490']
prior += ['req_bed1c2288b1946de95782fe1a4acff75', 'req_812d0334c2064f5d88339114e7954235']
roots = ['/home/ubuntu/.agentopt-v2/workspaces/' + x for x in prior]
ancestors = set()
pid = os.getpid()
while pid > 1:
    ancestors.add(pid)
    raw = Path(f'/proc/{pid}/stat').read_text()
    pid = int(raw[raw.rfind(')') + 2:].split()[1])
handles, unreadable, locks = [], [], []
for proc in Path('/proc').glob('[0-9]*'):
    try:
        pid = int(proc.name)
        args = [os.fsdecode(x) for x in (proc / 'cmdline').read_bytes().split(b'\0') if x]
        if not args:
            continue
        cwd = os.readlink(proc / 'cwd')
        native = Path(args[0]).name.startswith(('php', 'mysql', 'mariadb'))
        bounded = []
        for a in args:
            for prefix in ('--working-dir=', '--directory=', '--chdir='):
                if a.startswith(prefix):
                    a = a[len(prefix):]
                    break
            bounded.append(a)
        def match(p, roots):
            return any(p == r or p.startswith(r + '/') for r in roots)
        fds = []
        for fd in (proc / 'fd').iterdir():
            try:
                path = os.readlink(fd)
            except FileNotFoundError:
                continue
            if match(path, roots) or (native and pid not in ancestors and match(path, [root])):
                fds.append(int(fd.name))
        foreign = match(cwd, roots) or any(match(a, roots) for a in bounded) or bool(fds)
        own_competing = native and pid not in ancestors and (match(cwd, [root]) or any(match(a, [root]) for a in bounded))
        explicit_test = native and pid not in ancestors and any(a in ('req81_travel_lab_test', '--database=req81_travel_lab_test', '--dbname=req81_travel_lab_test') for a in args)
        if foreign or own_competing or explicit_test:
            handles.append({'pid': pid, 'foreign_test_owner_metadata': foreign,
                            'own_competing_native': own_competing, 'explicit_test_argv': explicit_test,
                            'fd_numbers': fds})
    except FileNotFoundError:
        continue
    except PermissionError:
        unreadable.append(int(proc.name))
pids = {h['pid'] for h in handles}
for line in Path('/proc/locks').read_text().splitlines():
    f = line.split()
    if '->' in f:
        f.remove('->')
    if int(f[4]) in pids:
        locks.append({'pid': int(f[4]), 'type': f[1], 'access': f[3]})
r = {'utc': datetime.datetime.now(datetime.timezone.utc).isoformat(), 'count': len(handles),
     'handles': handles, 'unreadable_pids': unreadable, 'lock_holders': locks,
     'excluded_ancestor_pids': sorted(ancestors), 'method': 'proc-only bounded cwd/argv/FD; known TEST owners and own nonancestor native processes'}
print(json.dumps(r))
raise SystemExit(0 if not handles and not unreadable and not locks else 1)
