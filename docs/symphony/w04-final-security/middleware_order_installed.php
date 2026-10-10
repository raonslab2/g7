<?php

declare(strict_types=1);

/*
 * W04 final security (nonauthor) — native middleware order of the INSTALLED support routes.
 *
 * Usage: php docs/symphony/w04-final-security/middleware_order_installed.php <installed routes-v7.php> <out.json>
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
preg_match_all("/'as' => '(api\\.modules\\.raonslab-travel_lab\\.support\\.[a-z.]+)'/", $text, $m, PREG_OFFSET_CAPTURE);
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
    $throttles = array_values(array_keys(array_filter($names, fn ($n) => str_starts_with($n, 'Illuminate\\Routing\\Middleware\\ThrottleRequests'))));
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
    ];
}
$marker = new ReflectionClass(Modules\Raonslab\TravelLab\Http\Middleware\TravelOptionalSanctum::class);
$payload = [
    'probe' => 'w04-final-security middleware_order_installed',
    'route_cache_sha256' => hash_file('sha256', $routeCache),
    'route_cache_mtime_utc' => gmdate('c', filemtime($routeCache)),
    'support_routes_found' => count($routes),
    'wrapper_implements_AuthenticatesRequests' => $marker->implementsInterface(Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class),
    'wrapper_parent' => $marker->getParentClass()->getName(),
    'wrapper_declares_methods' => array_values(array_map(fn ($m) => $m->getName(), array_filter($marker->getMethods(), fn ($m) => $m->getDeclaringClass()->getName() === $marker->getName()))),
    'native_priority_head' => array_slice($priority, 0, 12),
    'routes' => $result,
    'all_public_auth_before_throttle' => count(array_filter($result, fn ($r, $n) => str_contains($n, '.notices.') || str_contains($n, '.faqs.') ? ! $r['auth_before_every_throttle'] : false, ARRAY_FILTER_USE_BOTH)) === 0,
    'all_question_auth_before_throttle' => count(array_filter($result, fn ($r, $n) => str_contains($n, '.questions.') ? ! $r['auth_before_every_throttle'] : false, ARRAY_FILTER_USE_BOTH)) === 0,
];
file_put_contents($out, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
echo json_encode(['routes' => count($routes), 'public_ok' => $payload['all_public_auth_before_throttle'], 'questions_ok' => $payload['all_question_auth_before_throttle']]), "\n";
