<?php

/** CLI fixture: native admission against its caller-owned temporary SQLite file only. */
use Composer\Autoload\ClassLoader;
use Illuminate\Auth\GenericUser;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\RateLimiter;
use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Foundation\Application;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Facade;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = realpath($argv[1] ?? '');
$worker = $argv[2] ?? '';
if (! $database || ! preg_match('/^g7-travel-throttle-[a-f0-9]{16}$/', basename(dirname($database))) || basename($database) !== 'cache.sqlite' || ! preg_match('/^[0-3]$/', $worker)) {
    throw new RuntimeException('Caller-owned fixture path is required.');
}
$app = new Application(dirname($database)); // No application bootstrap, dotenv or installed service.
$app->instance('config', new Repository(['cache' => ['default' => 'fixture', 'limiter' => 'fixture', 'stores' => ['fixture' => [
    'driver' => 'database', 'table' => 'cache', 'lock_table' => 'cache_locks', 'lock_lottery' => [0, 100], 'prefix' => '',
]]]]));
$capsule = new Manager($app);
$capsule->addConnection(['driver' => 'sqlite', 'database' => $database, 'prefix' => '', 'busy_timeout' => 5000, 'journal_mode' => 'WAL']);
$app->instance('db', $capsule->getDatabaseManager());
$cache = new CacheManager($app);
$limiter = new RateLimiter($cache->store('fixture'));
$loader = new ClassLoader;
$loader->addPsr4('Modules\\Raonslab\\TravelLab\\', dirname(__DIR__, 2).'/modules/_bundled/raonslab-travel_lab/src');
$loader->register(true);
Facade::setFacadeApplication($app);
$middleware = new TravelThrottleRequests($limiter, $cache);
$request = Request::create('/fixture');
$request->setUserResolver(static fn () => new GenericUser(['id' => 701]));
$request->setRouteResolver(static fn () => new Route('GET', '/fixture', fn () => null));
file_put_contents(dirname($database).'/ready-'.$worker, 'ready');
$deadline = microtime(true) + 10;
while (! is_file(dirname($database).'/start')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Fixture barrier timed out.');
    }
    usleep(10000);
}
$started = microtime(true);
$codes = [];
for ($i = 0; $i < 15; $i++) {
    try {
        $codes[] = $middleware->handle($request, static fn () => new Response('admitted'), 20, 1, 'multi-process:')->getStatusCode();
    } catch (ThrottleRequestsException $e) {
        $codes[] = $e->getStatusCode();
    }
}
echo json_encode(['pid' => getmypid(), 'started' => $started, 'finished' => microtime(true), 'codes' => $codes], JSON_THROW_ON_ERROR);
