#!/usr/bin/env python3
"""독립 W03 명령의 고정 소스·시간·비밀 제거 출력을 기록한다."""
import datetime
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import time

ROOT = Path(__file__).resolve().parents[3]
TARGET = '28ada286c1c34606741bcfe4f9d12e06ac50af30'
name, *command = sys.argv[1:]
assert re.fullmatch(r'[a-z0-9-]+', name)
head = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()
assert head == TARGET, '검증 시작 HEAD가 고정 대상과 다름'
started = datetime.datetime.now(datetime.timezone.utc).isoformat()
start = time.monotonic()
helpers = {
    p.name: hashlib.sha256(p.read_bytes()).hexdigest()
    for p in (ROOT / 'docs/symphony/w03').iterdir()
    if p.is_file() and p.suffix in ['.php', '.py']
}
result = subprocess.run(command, cwd=ROOT, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
output = result.stdout.decode(errors='replace')
for envfile in ['.env', '.env.testing']:
    for line in (ROOT / envfile).read_text().splitlines():
        if '=' in line:
            key, value = line.split('=', 1)
            value = value.strip().strip('\"\'')
            if any(word in key.upper() for word in ['PASSWORD', 'TOKEN', 'SECRET', 'APP_KEY']) and value:
                output = output.replace(value, '[REDACTED]')
output = re.sub(r'\x1b\[[0-9;]*m', '', output)
output = re.sub(r'(?i)(Bearer\s+)\S+', r'\1[REDACTED]', output)
output = re.sub(r'(?i)(mysql://)[^\s]+', r'\1[REDACTED]', output)
directory = ROOT / 'docs/symphony/w03/evidence'
directory.mkdir(parents=True, exist_ok=True)
entry = {
    'request_id': 'req_b3b4d5373a384cfd8e0b7b8f55fb011f',
    'source_sha': head,
    'source_tree': subprocess.check_output(['git', 'rev-parse', 'HEAD^{tree}'], cwd=ROOT, text=True).strip(),
    'started_utc': started,
    'duration_seconds': round(time.monotonic() - start, 3),
    'command': command,
    'environment_scope': 'own worktree; TEST req81_travel_lab_test only / SQLite memory / no-DB guard as specified by command',
    'exit_code': result.returncode,
    'execution_provenance': 'W03 nonauthor independent execution; harness labels are not formal Validation',
    'review_helpers_sha256_at_start': helpers,
    'output': output,
}
(directory / (name + '.json')).write_text(json.dumps(entry, indent=2, ensure_ascii=False) + '\n')
print(json.dumps({k: v for k, v in entry.items() if k != 'output'}, ensure_ascii=False))
print(output[-10000:])
sys.exit(result.returncode)
