"""Hash scoped public evidence. Manifest itself is excluded to avoid self-reference."""
from pathlib import Path
import datetime,hashlib,json,subprocess
root=Path(__file__).resolve().parents[2]
e=root/'tests/W04_CAMPAIGN_BROWSER/evidence'
files=[p for p in (root/'tests/W04_CAMPAIGN_BROWSER').rglob('*') if p.is_file() and p.name not in ['manifest.json','MANIFEST.sha256'] and '__pycache__' not in p.parts]
files.append(root/'docs/symphony/W04_CAMPAIGN_BROWSER_FINAL.md')
rows=[{'path':str(p.relative_to(root)),'bytes':p.stat().st_size,'sha256':hashlib.sha256(p.read_bytes()).hexdigest()} for p in sorted(files)]
obj={'generated_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'tested_source_sha':'31a18318f91dde34a9c75eaaac65ae1d434b7bc3','tested_tree':'c0984e682a77153753edcde318a6d44b6973b1c0','evidence_base_head':subprocess.check_output(['git','-C',str(root),'rev-parse','HEAD'],text=True).strip(),'source_provenance':'Exact fixed Git, selected installed-source and served-byte bindings; actual native Chromium executions. Script/source/local checks are distinct from actual browser PASS.','files':rows,'file_count':len(rows),'safe_png_count':sum(r['path'].endswith('.png') for r in rows),'exclusions':['manifest.json and MANIFEST.sha256 self-reference','ignored private auth/contact/original baseline ledgers and raw quarantined PNGs','private review-only contact sheets','node_modules and every other project path'],'raw_failed_attempts_retained':True,'product_source_changes':0,'remote_publication':False,'ci_runs':0,'canonical_validation':'NOT_RUN absent','official_children':0,'internal_helpers':1 if (e/'internal-review.json').exists() else 0,'internal_helpers_planned':1,'review_type':'Native same-Request nonauthor evidence review; not a Validation receipt'}
(e/'manifest.json').write_text(json.dumps(obj,indent=2)+'\n')
(e/'MANIFEST.sha256').write_text(''.join(r['sha256']+'  '+r['path']+'\n' for r in rows))
print(json.dumps({'files':len(rows),'safe_png_count':obj['safe_png_count']}))
