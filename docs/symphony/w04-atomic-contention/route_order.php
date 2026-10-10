<?php

declare(strict_types=1);

/*
 * W04 atomic contention (nonauthor, fixed fa552317) — native middleware order of ALL installed travel routes.
 * Derived from the earlier nonauthor w04-final-security probe; extended to TravelThrottleRequests and workflow routes.
 *
 * Usage: php docs/symphony/w04-atomic-contention/route_order.php <installed routes-v7.php> <out.json>
 *
 * - Reads the parent's installed compiled route cache as TEXT only (never include/eval, never writes it).
 * - Requires this worktree's bootstrap/app.php at the fixed review SHA, resolves the native HTTP Kernel so the
 *   real withMiddleware() closure configures groups/aliases/priority on the native Router, then asks the native
 *   Router::gatherRouteMiddleware() to expand and priority-sort each installed support route's middleware.
 * - No dotenv load, no bootstrap(), no provider boot, no DB/cache/network. Only class names and middleware strings
 *   are emitted.
 */

[$self, $routeCache, $out] = $argv + [null, null, null];
if (! is_file((string) $routeCache) || ! $out) {
    fwrite(STDERR, "usage: <routes-v7.php> <out.json>\n");
    exit(2);
}
$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
// Module classes (TravelOptionalSanctum) are not in root composer autoload; map the bundled source of this SHA.
$loader = new Composer\Autoload\ClassLoader;
$loader->addPsr4('Modules\\Raonslab\\TravelLab\\', $root.'/modules/_bundled/raonslab-travel_lab/src/');
$loader->register(true);

$text = file_get_contents($routeCache);
$routes = [];
preg_match_all("/'as' => '(api\\.modules\\.raonslab-travel_lab\\.[a-z0-9_.\\-]+)'/", $text, $m, PREG_OFFSET_CAPTURE);
foreach ($m[1] as [$name, $offset]) {
    $start = strrpos(substr($text, 0, $offset), "'middleware' => ");
    $chunk = substr($text, $start, $offset - $start);
    preg_match("/'middleware' => \\s*array \\((.*?)\\),\\s*'uses' => '([^']+)'/s", $chunk, $mm);
    preg_match_all("/\\d+ => '((?:[^'\\\\]|\\\\.)*)'/", $mm[1], $items);
    $mw = array_map(fn ($s) => stripslashes($s), $items[1]);
    $routes[$name] = ['middleware' => $mw, 'uses' => stripslashes($mm[2])];
}

$app = require $root.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$router = $app->make('router');
$priority = $router->middlewarePriority;

$result = [];
foreach ($routes as $name => $r) {
    $route = new Illuminate\Routing\Route(['GET'], 'probe/'.$name, ['middleware' => $r['middleware'], 'uses' => fn () => null]);
    $route->setRouter($router)->setContainer($app);
    $sorted = $router->gatherRouteMiddleware($route);
    $names = array_map(fn ($x) => is_string($x) ? $x : get_debug_type($x), $sorted);
    $idx = function (callable $pred) use ($names) {
        foreach ($names as $i => $n) {
            if ($pred($n)) {
                return $i;
            }
        }

        return null;
    };
    $auth = $idx(fn ($n) => str_starts_with($n, 'Modules\\Raonslab\\TravelLab\\Http\\Middleware\\TravelOptionalSanctum') || str_starts_with($n, 'Illuminate\\Auth\\Middleware\\Authenticate'));
    $throttles = array_values(array_keys(array_filter($names, fn ($n) => str_starts_with($n, 'Illuminate\\Routing\\Middleware\\ThrottleRequests') || str_starts_with($n, 'Modules\\Raonslab\\TravelLab\\Http\\Middleware\\TravelThrottleRequests'))));
    $legacy = array_values(array_filter($names, fn ($n) => str_starts_with($n, 'Illuminate\\Routing\\Middleware\\ThrottleRequests')));
    $perm = $idx(fn ($n) => str_starts_with($n, 'App\\Http\\Middleware\\PermissionMiddleware'));
    $bind = $idx(fn ($n) => $n === 'Illuminate\\Routing\\Middleware\\SubstituteBindings');
    $result[$name] = [
        'installed_route_cache_middleware' => $r['middleware'],
        'controller' => $r['uses'],
        'native_sorted' => $names,
        'auth_index' => $auth,
        'throttle_indexes' => $throttles,
        'substitute_bindings_index' => $bind,
        'auth_before_every_throttle' => $auth !== null && $throttles !== [] && $auth < min($throttles),
        'throttle_before_bindings' => $bind === null || ($throttles !== [] && max($throttles) < $bind),
        'throttle_args' => array_values(array_map(fn ($i) => explode(':', $names[$i], 2)[1] ?? '', $throttles)),
        'legacy_native_throttle' => $legacy,
        'permission_index' => $perm,
    ];
}
$tt = new ReflectionClass(Modules\Raonslab\TravelLab\Http\Middleware\TravelThrottleRequests::class);
$marker = new ReflectionClass(Modules\Raonslab\TravelLab\Http\Middleware\TravelOptionalSanctum::class);
$payload = [
    'probe' => 'w04-atomic-contention route_order (installed route cache text -> native Kernel/Router sort)',
    'route_cache_sha256' => hash_file('sha256', $routeCache),
    'route_cache_mtime_utc' => gmdate('c', filemtime($routeCache)),
    'support_routes_found' => count($routes),
    'wrapper_implements_AuthenticatesRequests' => $marker->implementsInterface(Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class),
    'wrapper_parent' => $marker->getParentClass()->getName(),
    'wrapper_declares_methods' => array_values(array_map(fn ($m) => $m->getName(), array_filter($marker->getMethods(), fn ($m) => $m->getDeclaringClass()->getName() === $marker->getName()))),
    'native_priority_head' => array_slice($priority, 0, 12),
    'travel_throttle_parent' => $tt->getParentClass()->getName(),
    'travel_throttle_sha256' => hash_file('sha256', $tt->getFileName()),
    'throttled_routes' => count(array_filter($result, fn ($r) => $r['throttle_indexes'] !== [])),
    'throttled_routes_auth_before_every_throttle' => count(array_filter($result, fn ($r) => $r['throttle_indexes'] !== [] && $r['auth_before_every_throttle'])),
    'routes_with_legacy_native_throttle' => count(array_filter($result, fn ($r) => $r['legacy_native_throttle'] !== [])),
    'routes' => $result,
    'all_public_auth_before_throttle' => count(array_filter($result, fn ($r, $n) => str_contains($n, '.notices.') || str_contains($n, '.faqs.') ? ! $r['auth_before_every_throttle'] : false, ARRAY_FILTER_USE_BOTH)) === 0,
    'all_question_auth_before_throttle' => count(array_filter($result, fn ($r, $n) => str_contains($n, '.questions.') ? ! $r['auth_before_every_throttle'] : false, ARRAY_FILTER_USE_BOTH)) === 0,
];
file_put_contents($out, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
echo json_encode(['routes' => count($routes), 'throttled' => $payload['throttled_routes'], 'throttled_auth_first' => $payload['throttled_routes_auth_before_every_throttle'], 'legacy' => $payload['routes_with_legacy_native_throttle'], 'public_ok' => $payload['all_public_auth_before_throttle'], 'questions_ok' => $payload['all_question_auth_before_throttle']]), "\n";
