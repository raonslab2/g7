"""Bounded scan of this verifier's public evidence; OCR stays in memory."""
import hashlib
import json
import os
import pathlib
import re
import subprocess
import sys
from datetime import datetime, timezone

root = pathlib.Path('docs/symphony/final-gate-recovery/browser')
patterns = {
    'email': re.compile(r'[\w.+-]+@[\w.-]+\.[\w-]+'),
    'phone': re.compile(r'\b0\d{1,2}[- ]?\d{3,4}[- ]?\d{4}\b'),
    'jwt': re.compile(r'\beyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\b'),
    'bearer_value': re.compile(r'Bearer\s+[A-Za-z0-9._-]{12,}'),
}
report = {'started': datetime.now(timezone.utc).isoformat(), 'command': 'python3 docs/symphony/final-gate-recovery/browser/privacy-scan.py', 'scope': 'Only new public browser evidence. No credentials or private ledgers read. Exact unknown secrets are not claimed scanned.', 'files': []}
previous = {}
if '--reuse-unchanged-ocr' in sys.argv:
    previous = {x['file']: x for x in json.loads((root / 'privacy-scan.json').read_text())['files']}
    report['command'] += ' --reuse-unchanged-ocr'
for p in sorted(root.iterdir()):
    if p.name in ('privacy-scan.json', 'MANIFEST.sha256') or not p.is_file():
        continue
    digest = hashlib.sha256(p.read_bytes()).hexdigest()
    prior = previous.get(str(p))
    if p.suffix == '.png' and prior and prior['sha256'] == digest:
        report['files'].append({**prior, 'reused_unchanged_ocr': True})
        continue
    if p.suffix == '.png':
        proc = subprocess.run(['tesseract', str(p), 'stdout', '-l', 'eng'], env={**os.environ, 'OMP_THREAD_LIMIT': '1'}, capture_output=True, text=True, timeout=30)
        body = proc.stdout
        code = proc.returncode
        mode = 'tesseract-eng-memory'
    else:
        body = p.read_text()
        code = 0
        mode = 'utf8-text'
    counts = {k: len(v.findall(body)) for k, v in patterns.items()}
    report['files'].append({'file': str(p), 'sha256': digest, 'mode': mode, 'exit_code': code, 'pattern_hits': counts})
report['finished'] = datetime.now(timezone.utc).isoformat()
report['status'] = 'PASS' if all(x['exit_code'] == 0 and not any(x['pattern_hits'].values()) for x in report['files']) else 'FAIL'
(root / 'privacy-scan.json').write_text(json.dumps(report, indent=2) + '\n')
print(json.dumps({'status': report['status'], 'files': len(report['files']), 'pngs': sum(x['mode'].startswith('tesseract') for x in report['files']), 'total_pattern_hits': sum(sum(x['pattern_hits'].values()) for x in report['files'])}))
raise SystemExit(0 if report['status'] == 'PASS' else 1)
