#!/usr/bin/env python3
"""Guarded exact-secret/text scan and OCR screening; print counts/filenames only.

OCR is screening, not a claim of visually reviewing every screenshot.
Access and issued-token files remain private and are never copied to evidence.
"""
import concurrent.futures, hashlib, json, os, pathlib, re, subprocess, sys

ROOT = pathlib.Path(__file__).resolve().parents[2]
OUT = ROOT / 'docs/symphony/evidence/W04_FINAL_BROWSER'
ACCESS = pathlib.Path('/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba/storage/framework/testing/travel-live-review-651fc158dee2/access.json')
if ACCESS.is_symlink() or ACCESS.stat().st_mode & 0o077 or ACCESS.parent.stat().st_mode & 0o077:
    raise SystemExit('Private scoped access permission mismatch')
a = json.loads(ACCESS.read_text())
if a['source_sha'] != '1052e3fb4bc4cccabb51b8c538116c78655f345b' or a['base_url'] != 'http://127.0.0.1:18871':
    raise SystemExit('Source binding mismatch')
secrets = [v for role in ('member', 'other_member', 'admin') for k, v in a[role].items()
           if k in ('email', 'password', 'bearer_token') and isinstance(v, str) and v]
ledger = ROOT / 'tests/W04_FINAL/.private/issued.json'
if ledger.exists():
    if ledger.stat().st_mode & 0o077:
        raise SystemExit('Own ledger permissions mismatch')
    secrets += [x['token'] for x in json.loads(ledger.read_text())]

patterns = [re.compile(r'\b[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}\b'),
            re.compile(r'\b\d+\|[A-Za-z0-9]{40}\b'),
            re.compile(r'\b000[- ]?0000[- ]?000[01]\b')]
def findings(text):
    return sum(v in text for v in secrets), sum(bool(p.search(text)) for p in patterns)

text_results = []
paths = [ROOT / 'docs/symphony/W04_BROWSER_FINAL.md']
paths += list((ROOT / 'tests/W04_FINAL').rglob('*')) + list(OUT.rglob('*'))
for p in sorted(set(paths)):
    if not p.is_file() or '.private' in p.parts or p.suffix == '.png' or p.name.startswith('privacy-scan-'):
        continue
    t = p.read_text(errors='strict')
    exact, generic = findings(t)
    text_results.append({'path': str(p.relative_to(ROOT)), 'exactSecretHits': exact, 'patternHits': generic})

def ocr(p):
    r = subprocess.run(['tesseract', str(p), 'stdout'], capture_output=True, text=True,
                       env={**os.environ, 'OMP_THREAD_LIMIT': '1'}, timeout=60)
    exact, generic = findings(r.stdout)
    return {'path': str(p.relative_to(ROOT)), 'exit': r.returncode, 'exactSecretHits': exact,
            'patternHits': generic, 'sha256': hashlib.sha256(p.read_bytes()).hexdigest()}
phase = sys.argv[1] if len(sys.argv) > 1 else 'final'
cache_path=OUT / 'ocr-screening-cache.json'
cache=json.loads(cache_path.read_text()) if cache_path.exists() else {}
images=sorted(OUT.glob('*.png'))
pending=[p for p in images if str(p.relative_to(ROOT)) not in cache or cache[str(p.relative_to(ROOT))]['sha256'] != hashlib.sha256(p.read_bytes()).hexdigest()]
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
    for row in pool.map(ocr, pending[:9]):
        cache[row['path']]=row
        cache_path.write_text(json.dumps(cache,indent=2)+'\n')
screenshots=[cache[str(p.relative_to(ROOT))] for p in images if str(p.relative_to(ROOT)) in cache]
remaining=len(images)-len(screenshots)
bad = [r for r in text_results + screenshots if r['exactSecretHits'] or r['patternHits'] or r.get('exit', 0)]
phase = sys.argv[1] if len(sys.argv) > 1 else 'final'
result = {'sourceSHA': a['source_sha'], 'phase': phase, 'status': 'FAIL' if bad else 'NOT_RUN' if remaining else 'PASS', 'remaining':remaining,
          'privateLedgerAvailable': ledger.exists(), 'textFiles': len(text_results),
          'screenshotsScreened': len(screenshots), 'flaggedFiles': [r['path'] for r in bad],
          'text': text_results, 'screenshots': screenshots,
          'limitation': 'OCR cannot prove pixel-level privacy. Representative frames visually inspected separately; no every-frame visual claim.'}
(OUT / ('privacy-scan-' + phase + '.json')).write_text(json.dumps(result, indent=2) + '\n')
print(json.dumps({k: result[k] for k in ('status', 'textFiles', 'screenshotsScreened', 'remaining', 'flaggedFiles')}))
sys.exit(1 if bad else 2 if remaining else 0)
