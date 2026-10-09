#!/usr/bin/env python3
"""W04 final security (nonauthor): installed module/template/core provenance vs the fixed review checkout.

Usage: python3 -I source_provenance.py <review-root> <parent-root> <out.json>

Read-only. Hashes files (SHA-256) and compares; reads hooks.php / routes-v7.php as text only, extracting
only travel-related listener/route lines. Never opens .env, config caches or credential files.
"""
import hashlib, json, os, re, subprocess, sys

REVIEW, PARENT, OUT = sys.argv[1:4]
SKIP_DIRS = {'node_modules', 'vendor', '.git'}


def tree(root):
    out = {}
    for d, dirs, files in os.walk(root):
        dirs[:] = [x for x in dirs if x not in SKIP_DIRS]
        for f in files:
            p = os.path.join(d, f)
            if os.path.islink(p):
                out[os.path.relpath(p, root)] = 'symlink:' + os.readlink(p)
                continue
            out[os.path.relpath(p, root)] = hashlib.sha256(open(p, 'rb').read()).hexdigest()
    return out


def compare(a_root, b_root):
    a, b = tree(a_root), tree(b_root)
    common = sorted(set(a) & set(b))
    differ = [p for p in common if a[p] != b[p]]
    manifest = hashlib.sha256('\n'.join(f'{p} {a[p]}' for p in sorted(a)).encode()).hexdigest()
    return {'a_files': len(a), 'b_files': len(b), 'common': len(common), 'differ': differ,
            'only_in_review': sorted(set(a) - set(b)), 'only_in_installed': sorted(set(b) - set(a)),
            'review_manifest_sha256': manifest, 'identical': not differ and set(a) == set(b)}


def git_tracked(root, sub):
    r = subprocess.run(['git', '-C', root, 'ls-files', sub], capture_output=True, text=True, check=True)
    return [x for x in r.stdout.splitlines() if x]


res = {'review_head': subprocess.run(['git', '-C', REVIEW, 'rev-parse', 'HEAD'], capture_output=True, text=True).stdout.strip(),
       'parent_head': subprocess.run(['git', '-C', PARENT, 'rev-parse', 'HEAD'], capture_output=True, text=True).stdout.strip(),
       'parent_status_porcelain_lines': len(subprocess.run(['git', '-C', PARENT, 'status', '--porcelain', '--untracked-files=no'], capture_output=True, text=True).stdout.splitlines())}

M = 'modules/_bundled/raonslab-travel_lab'
T = 'templates/_bundled/raonslab-travel_lab'
res['module_review_bundled_vs_parent_installed'] = compare(os.path.join(REVIEW, M), os.path.join(PARENT, 'modules/raonslab-travel_lab'))
res['module_review_bundled_vs_parent_bundled'] = compare(os.path.join(REVIEW, M), os.path.join(PARENT, M))
if os.path.isdir(os.path.join(PARENT, 'templates/raonslab-travel_lab')):
    res['template_review_bundled_vs_parent_installed'] = compare(os.path.join(REVIEW, T), os.path.join(PARENT, 'templates/raonslab-travel_lab'))
res['module_tracked_files_at_review_sha'] = len(git_tracked(REVIEW, M))

core = ['app/Http/Middleware/OptionalSanctumMiddleware.php', 'bootstrap/app.php', 'app/Http/Middleware/RefreshTokenExpiration.php',
        'app/Http/Middleware/ExtensionMiddlewareGate.php', 'app/Providers/ModuleRouteServiceProvider.php',
        'vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php', 'vendor/laravel/framework/src/Illuminate/Cache/RateLimiter.php',
        'vendor/laravel/framework/src/Illuminate/Cache/FileStore.php', 'vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php',
        'vendor/laravel/framework/src/Illuminate/Routing/SortedMiddleware.php', 'vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php',
        'vendor/laravel/sanctum/src/Guard.php', 'vendor/laravel/sanctum/src/PersonalAccessToken.php']
res['core_files'] = {}
for p in core:
    a, b = os.path.join(REVIEW, p), os.path.join(PARENT, p)
    h = lambda x: hashlib.sha256(open(x, 'rb').read()).hexdigest() if os.path.isfile(x) else None
    res['core_files'][p] = {'review': h(a), 'parent_installed': h(b), 'equal': h(a) is not None and h(a) == h(b)}

hooks = open(os.path.join(PARENT, 'bootstrap/cache/hooks.php'), encoding='utf-8', errors='replace').read()
res['hooks_cache'] = {'sha256': hashlib.sha256(hooks.encode()).hexdigest(),
                      'travel_listener_lines': sorted({l.strip() for l in hooks.splitlines() if 'TravelLab' in l})}
# Hook names in which travel listeners appear (text context: nearest preceding quoted hook key).
names = []
for m in re.finditer(r"TravelLab\\\\Listeners\\\\(\w+)", hooks):
    pre = hooks[:m.start()]
    key = re.findall(r"\n\s{2,6}'([a-z0-9_.\-]+)' =>\s*\n?\s*array", pre)
    names.append((key[-1] if key else None, m.group(1)))
res['hooks_cache']['travel_listener_by_hook'] = sorted({f'{k} -> {v}' for k, v in names if k})

routes = open(os.path.join(PARENT, 'bootstrap/cache/routes-v7.php'), encoding='utf-8', errors='replace').read()
tl = sorted(set(re.findall(r"'as' => '(api\.modules\.raonslab-travel_lab\.[a-z0-9_.\-]+)'", routes)))
res['route_cache'] = {'sha256': hashlib.sha256(routes.encode()).hexdigest(), 'travel_named_routes': len(tl), 'names': tl,
                      'throttle_strings': sorted(set(re.findall(r"'(throttle:[0-9]+,[0-9]+,travel-lab-[a-z-]+:)'", routes))),
                      'TravelOptionalSanctum_occurrences': routes.count('TravelOptionalSanctum')}
src_routes = 0
for f in ('catalog.php', 'workflow.php', 'support.php'):
    src_routes += len(re.findall(r"->name\('(?:[a-z.]+)'\)", open(os.path.join(REVIEW, M, 'src/routes', f)).read()))
res['route_cache']['source_leaf_name_calls'] = src_routes
json.dump(res, open(OUT, 'w'), indent=1, sort_keys=True)
print(json.dumps({k: (v.get('identical'), v.get('a_files'), v.get('b_files'), len(v.get('differ', []))) for k, v in res.items() if isinstance(v, dict) and 'identical' in v}))
print('core_equal', all(v['equal'] for v in res['core_files'].values()), 'routes', len(tl), res['route_cache']['throttle_strings'])
