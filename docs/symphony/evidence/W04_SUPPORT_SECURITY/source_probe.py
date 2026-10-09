#!/usr/bin/env python3
"""Git-only independent inventory and historical implementation comparison."""
import hashlib
import json
import pathlib
import re
import subprocess

SHA = '598a89fff702d51c1405f1a5952d95ab1d2651f4'
OLD = '7de0c4441b68b1c012dbf4a75114200e322051c9'
DD = 'dd43331a91ef13a3e6c6e68f6689ab998c16895c'
PREFIX = 'modules/_bundled/raonslab-travel_lab/'
assert subprocess.check_output(['git', 'rev-parse', SHA + '^{tree}'], text=True).strip() == '9e00273bdf18d6a713343755aac54f44a9b032b4'


def blob(sha, path):
    return subprocess.check_output(['git', 'show', sha + ':' + path])


paths = subprocess.check_output(['git', 'ls-tree', '-r', '--name-only', SHA, PREFIX], text=True).splitlines()
files = [{'path': p[len(PREFIX):], 'sha256': hashlib.sha256(blob(SHA, p)).hexdigest()} for p in paths]
php = [p for p in paths if p.endswith('.php') and not p.startswith(PREFIX + 'tests/')]
native = [
    'modules/_bundled/sirsoft-ecommerce/src/Services/ProductService.php',
    'modules/_bundled/sirsoft-ecommerce/src/Services/CartService.php',
    'modules/_bundled/sirsoft-ecommerce/src/Services/OrderCalculationService.php',
    'modules/_bundled/sirsoft-board/src/Services/PostService.php',
    'modules/_bundled/sirsoft-board/src/Listeners/BoardActivityLogListener.php',
    'app/Helpers/PermissionHelper.php',
    'app/Extension/HookManager.php',
    'app/Providers/ModuleRouteServiceProvider.php',
    'app/Http/Middleware/OptionalSanctumMiddleware.php',
    'bootstrap/app.php',
]
comparisons = []
for path in php + native:
    hashes = {sha: hashlib.sha256(blob(sha, path)).hexdigest() for sha in [SHA, OLD, DD]}
    comparisons.append({'path': path, 'sha256': hashes, 'unchanged_since_original': len(set(hashes.values())) == 1})
routes = {}
for name in ['catalog', 'workflow', 'support']:
    verbs = re.findall(r'Route::(get|post|patch|put|delete)\(', blob(SHA, PREFIX + 'src/routes/' + name + '.php').decode())
    routes[name] = {v: verbs.count(v) for v in sorted(set(verbs))}
output = {'source_sha': SHA, 'tree': '9e00273bdf18d6a713343755aac54f44a9b032b4',
          'module_tracked_files': len(files), 'module_non_test_php_count': len(php),
          'module_manifest_sha256': hashlib.sha256(json.dumps(files, sort_keys=True, separators=(',', ':')).encode()).hexdigest(),
          'source_route_counts': routes, 'source_route_total': sum(sum(x.values()) for x in routes.values()),
          'comparisons': comparisons, 'module_files': files,
          'native_scope': 'Ten explicitly listed business/security paths; whole core not claimed'}
pathlib.Path(__file__).with_name('source-hashes.json').write_text(json.dumps(output, indent=2) + '\n')
print('Source inventory:', len(files), 'files;', len(php), 'module non-test PHP;', output['source_route_total'], 'routes')
print('Changed compared module/native paths:', [x['path'] for x in comparisons if not x['unchanged_since_original']])
