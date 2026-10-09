#!/usr/bin/env python3
"""Write evidence/execution-manifest.json: commands, phase timings and sha256 of every delivered file."""
import glob, hashlib, json, os, subprocess, sys
root = sys.argv[1]
base = os.path.join(root, 'docs/symphony/w04-final-security')
files = sorted([p for p in glob.glob(base + '/**/*', recursive=True) if os.path.isfile(p) and not p.endswith('execution-manifest.json')] + [os.path.join(root, 'docs/symphony/W04_SECURITY_FINAL.md')])
raw = {}
for f in sorted(glob.glob(base + '/evidence/raw/p*.json')):
    d = json.load(open(f))
    if 'results' in d:
        raw[os.path.basename(f)] = {'counts': d['counts'], 'steps': [[r['status'], r['name'], r.get('at')] for r in d['results']]}
m = {
    'request': 'req_0683bc352fac4373b748cee64dedc53a', 'review_sha': '1052e3fb4bc4cccabb51b8c538116c78655f345b', 'review_tree': 'f18fa2056a031353c889785d768f87824e3083f5',
    'head_at_manifest': subprocess.run(['git', '-C', root, 'rev-parse', 'HEAD'], capture_output=True, text=True).stdout.strip(),
    'product_source_diff_vs_target_empty': subprocess.run(['git', '-C', root, 'diff', '--quiet', '1052e3fb4bc4cccabb51b8c538116c78655f345b', '--', '.', ':!docs/symphony/W04_SECURITY_FINAL.md', ':!docs/symphony/w04-final-security'], capture_output=True).returncode == 0,
    'commands': [
        'git fetch origin feat/g7-travel-lab-c7ae42d1 && git checkout -B review/w04-final-security-1052 1052e3fb4bc4cccabb51b8c538116c78655f345b',
        'composer install --no-scripts --no-interaction --prefer-dist -q   # 17.4s, own vendor (ignored)',
        'php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Extension/TravelSupportThrottleIsolationTest.php tests/Unit/Extension/TravelSupportAuthThrottleOrderingTest.php  # PASS 10/2032 3.32s',
        'php docs/symphony/w04-final-security/middleware_order_installed.php <parent>/bootstrap/cache/routes-v7.php evidence/middleware-order-installed.json',
        'python3 -I docs/symphony/w04-final-security/source_provenance.py <review-root> <parent-root> evidence/source-provenance.json',
        'php docs/symphony/w04-final-security/hooks_parity.php <parent>/bootstrap/cache/hooks.php evidence/hooks-parity.json',
        'php docs/symphony/w04-final-security/db_guard.php <private-dir> query {floors|counts|tokens}   # p0 baseline, p9 final',
        'python3 -I p1_public_identity.py <private-dir> <private-out> <parent-root> identity | boundary <role> <lanes>',
        'python3 -I p2_questions.py <private-dir> <private-out> budget | semantics | create_race | auth | cleanup',
        'python3 -I p3_workflow.py <private-dir> <private-out> fixtures | matrix | races | deadlock_attribution [W04F_VARIANT=c] | effects | cleanup',
    ],
    'phase_results_raw': raw,
    'notes': [
        'Probe defect retained: p1-identity cache-parse FAIL (PHP serialized i:N; not decoded); corrected in later runs, no product implication.',
        'R1 raw FAIL is the strict deadlock-counter assertion; overlap and invariants passed; finding W04F-01.',
        'p1-boundary-160208 used the pre-correction OBSERVED note text (duplicate values); accepted-before-429 = 611 is computed from its raw post_601 list.',
        'M05 raw detail key kst_today shows the KST date string, overwriting the asserted status 422 (display defect in probe).',
        'Private state files (p2-state.json, p3-state.json: contacts/payloads) are not delivered.',
    ],
    'files_sha256': {os.path.relpath(f, root): hashlib.sha256(open(f, 'rb').read()).hexdigest() for f in files},
}
json.dump(m, open(os.path.join(base, 'evidence/execution-manifest.json'), 'w'), indent=1)
print(len(m['files_sha256']), m['product_source_diff_vs_target_empty'])
