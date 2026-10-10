#!/usr/bin/env python3
"""W04 atomic contention: sha256 manifest of every delivered probe/evidence file plus the report (no private state)."""
import hashlib, json, os, sys
root = os.path.dirname(os.path.abspath(__file__))
files = {}
for d, _, fs in os.walk(root):
    for f in sorted(fs):
        p = os.path.join(d, f)
        rel = os.path.relpath(p, root)
        if rel == 'evidence/manifest.json' or '__pycache__' in rel:
            continue
        files[rel] = hashlib.sha256(open(p, 'rb').read()).hexdigest()
rep = os.path.join(root, '..', 'W04_ATOMIC_CONTENTION_FINAL.md')
out = {'review_sha': 'fa5523175ac494cfbd13bbf89bf06b3ec91835a6', 'review_tree': 'fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c', 'files': files,
       'report_sha256_at_build': hashlib.sha256(open(rep, 'rb').read()).hexdigest()}
json.dump(out, open(os.path.join(root, 'evidence', 'manifest.json'), 'w'), indent=1, sort_keys=True)
print(len(files), 'files')
