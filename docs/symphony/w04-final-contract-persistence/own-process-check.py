"""자체 worktree의 native SQL/PHP 실행만 읽기 전용으로 확인한다."""
import json
import os
import sys
from pathlib import Path
root = str(Path(__file__).resolve().parents[3])
allowed_pid = int(sys.argv[1])
handles, unreadable = [], []
for proc in Path('/proc').glob('[0-9]*'):
    try:
        args = [os.fsdecode(x) for x in (proc / 'cmdline').read_bytes().split(b'\0') if x]
        if not args or not Path(args[0]).name.startswith(('php', 'mysql', 'mariadb')):
            continue
        cwd = os.readlink(proc / 'cwd')
        paths = []
        for a in args:
            for prefix in ('--working-dir=', '--directory=', '--chdir='):
                if a.startswith(prefix):
                    a = a[len(prefix):]
                    break
            paths.append(a)
        fd_matches = []
        for fd in (proc / 'fd').iterdir():
            try:
                path = os.readlink(fd)
            except FileNotFoundError:
                continue
            if path == root or path.startswith(root + '/'):
                fd_matches.append(int(fd.name))
        matches = cwd == root or cwd.startswith(root + '/') or any(a == root or a.startswith(root + '/') for a in paths) or fd_matches
        if matches and int(proc.name) != allowed_pid:
            handles.append({'pid': int(proc.name), 'own_cwd': cwd == root or cwd.startswith(root + '/'), 'native_executable': Path(args[0]).name, 'own_fd_numbers': fd_matches})
    except FileNotFoundError:
        continue
    except PermissionError:
        unreadable.append(int(proc.name))
print(json.dumps({'native_processes': handles, 'count': len(handles), 'unreadable_pids': unreadable,
                  'excluded_observer_pid': allowed_pid, 'method': 'exact or descendant own cwd/path-valued argv/FD plus PHP/mysql executable'}))
raise SystemExit(0 if not handles and not unreadable else 1)
