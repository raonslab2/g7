#!/usr/bin/env python3
"""Hash allowed durable review files. Ignores private/generated data, no APP calls."""
import hashlib, json, pathlib, subprocess
root = pathlib.Path(__file__).resolve().parents[2]
out = root / 'docs/symphony/evidence/W04_FINAL_BROWSER'
target = out / 'sha256-manifest.json'
paths = [root / 'docs/symphony/W04_BROWSER_FINAL.md']
paths += list((root / 'tests/W04_FINAL').rglob('*')) + list(out.rglob('*'))
rows=[]
for p in sorted(set(paths)):
    if not p.is_file() or p == target or '.private' in p.parts:
        continue
    relative=str(p.relative_to(root))
    if subprocess.run(['git','check-ignore','--quiet',relative],cwd=root).returncode == 0:
        continue
    rows.append({'path':relative,'bytes':p.stat().st_size,'sha256':hashlib.sha256(p.read_bytes()).hexdigest()})
target.write_text(json.dumps({'sourceSHA':'1052e3fb4bc4cccabb51b8c538116c78655f345b',
                  'selfExcluded':True,'files':rows},indent=2)+'\n')
print('Manifest',len(rows),'review files;',sum(r['bytes'] for r in rows),'bytes; private and self excluded')
