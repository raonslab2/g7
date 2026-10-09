"""실패 Request의 프로세스·파일 핸들·잠금만 읽고 안전한 메타데이터를 반환한다."""
import datetime
import json
import os
from pathlib import Path

FAILED = '/home/ubuntu/.agentopt-v2/workspaces/req_3e70e7ceddd34beea51a17da727584bc'
handles, unreadable = [], []
for proc in Path('/proc').glob('[0-9]*'):
    try:
        args = (proc / 'cmdline').read_bytes().split(b'\0')
        try:
            cwd = os.readlink(proc / 'cwd')
        except FileNotFoundError:
            cwd = ''
        arg_match = False
        for raw in args:
            arg = os.fsdecode(raw)
            for prefix in ('--working-dir=', '--directory=', '--chdir='):
                if arg.startswith(prefix):
                    arg = arg[len(prefix):]
                    break
            arg_match |= arg == FAILED or arg.startswith(FAILED + '/')
        fd_matches = []
        for fd in (proc / 'fd').iterdir():
            try:
                path = os.readlink(fd)
            except FileNotFoundError:
                continue
            if path == FAILED or path.startswith(FAILED + '/'):
                fd_matches.append(int(fd.name))
        if cwd == FAILED or cwd.startswith(FAILED + '/') or arg_match or fd_matches:
            handles.append({'pid': int(proc.name), 'exact_cwd': cwd == FAILED,
                            'descendant_cwd': cwd.startswith(FAILED + '/'),
                            'individual_path_argv': arg_match, 'failed_tree_fd_numbers': fd_matches})
    except FileNotFoundError:
        continue
    except PermissionError:
        unreadable.append(int(proc.name))
lock = Path(FAILED) / 'storage/framework/testing/w04f-exclusive.lock'
lock_holders = []
try:
    stat = lock.stat()
    for line in Path('/proc/locks').read_text().splitlines():
        fields = line.split()
        if '->' in fields:
            fields.remove('->')
        major, minor, inode = fields[5].split(':')
        if (int(major, 16), int(minor, 16), int(inode)) == (os.major(stat.st_dev), os.minor(stat.st_dev), stat.st_ino):
            lock_holders.append({'pid': int(fields[4]), 'type': fields[1], 'access': fields[3]})
    lock_present = True
except FileNotFoundError:
    lock_present = False
result = {'utc': datetime.datetime.now(datetime.timezone.utc).isoformat(),
          'method': 'privileged readonly proc exact cwd/individual bounded argv/fd; proc locks inode',
          'count': len(handles), 'handles': handles, 'unreadable_pids': unreadable,
          'failed_lock_present': lock_present, 'lock_holders': lock_holders}
print(json.dumps(result))
raise SystemExit(0 if not handles and not unreadable and not lock_holders else 1)
