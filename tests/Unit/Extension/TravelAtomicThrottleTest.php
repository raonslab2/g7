<?php

namespace Tests\Unit\Extension;

use Carbon\Carbon;
use Composer\Autoload\ClassLoader;
use Illuminate\Auth\GenericUser;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Mockery;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelThrottleRequests;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Native cache/lock/limiter fixtures only; SQLite concurrency is not an InnoDB/HTTP result. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class TravelAtomicThrottleTest extends TestCase
{
    private Application $app;

    private Container $previousContainer;

    private $previousFacadeApplication;

    private $previousClock;

    private ClassLoader $moduleLoader;

    private CacheManager $cache;

    private Connection $connection;

    private RateLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $this->previousClock = Carbon::getTestNow();
        Carbon::setTestNow('2026-10-09 12:00:00 UTC');
        $this->app = new Application(sys_get_temp_dir().'/g7-atomic-throttle-unbooted');
        $this->app->instance('config', new ConfigRepository([
            'app' => ['locale' => 'en'],
            'cache' => ['default' => 'fixture', 'limiter' => 'fixture', 'stores' => [
                'fixture' => ['driver' => 'database', 'table' => 'cache', 'lock_table' => 'cache_locks', 'lock_lottery' => [0, 100], 'prefix' => ''],
                'unsupported' => ['driver' => 'array'],
            ]],
        ]));
        $capsule = new Capsule($this->app);
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $this->app->instance('db', $capsule->getDatabaseManager());
        $this->connection = $capsule->getConnection();
        $this->createTables($this->connection);
        $this->cache = new CacheManager($this->app);
        $this->limiter = new RateLimiter($this->cache->store('fixture'));
        $this->moduleLoader = new ClassLoader;
        $source = dirname(__DIR__, 3).'/modules/_bundled/raonslab-travel_lab/src';
        $this->moduleLoader->addPsr4('Modules\\Raonslab\\TravelLab\\', $source);
        $this->moduleLoader->register(true);
        $loader = new FileLoader(new Filesystem, []);
        $loader->addNamespace('raonslab-travel_lab', $source.'/lang');
        $this->app->instance('translator', new Translator($loader, 'en'));
        $router = new Router(new Dispatcher($this->app), $this->app);
        $this->app->instance(ResponseFactoryContract::class, new ResponseFactory(
            Mockery::mock(ViewFactory::class), new Redirector(new UrlGenerator($router->getRoutes(), Request::create('/'))),
        ));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        $this->assertSame('sqlite', $this->connection->getDriverName());
        $this->assertSame(':memory:', $this->connection->getDatabaseName());
        $this->assertInstanceOf(DatabaseStore::class, $this->cache->store('fixture')->getStore());
        $this->assertSame(realpath($source.'/Http/Middleware/TravelThrottleRequests.php'), (new \ReflectionClass(TravelThrottleRequests::class))->getFileName());
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            $this->connection->disconnect();
            $this->moduleLoader->unregister();
            Carbon::setTestNow($this->previousClock);
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->previousFacadeApplication);
            Container::setInstance($this->previousContainer);
            parent::tearDown();
        }
    }

    private function createTables(Connection $connection): void
    {
        $schema = $connection->getSchemaBuilder();
        $schema->create('cache', static function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value');
            $table->integer('expiration');
        });
        $schema->create('cache_locks', static function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    private function request(int $actor = 701): Request
    {
        $request = Request::create('/fixture', server: ['REMOTE_ADDR' => '192.0.2.1']);
        $request->setUserResolver(static fn () => new GenericUser(['id' => $actor]));
        $request->setRouteResolver(static fn () => new Route('GET', '/fixture', fn () => null));

        return $request;
    }

    private function middleware(): TravelThrottleRequests
    {
        return new TravelThrottleRequests($this->limiter, $this->cache);
    }

    private function key(Request $request, string $prefix): string
    {
        // Resolve with the actual native signature method, not a duplicate user/IP key algorithm.
        $method = new \ReflectionMethod(ThrottleRequests::class, 'resolveRequestSignature');

        return $prefix.$method->invoke($this->middleware(), $request);
    }

    public function test_exact_sequential_limit_actor_prefix_retry_headers_and_window_reset(): void
    {
        $middleware = $this->middleware();
        $first = $this->request();
        $next = static fn () => new Response('admitted');
        for ($i = 0; $i < 10; $i++) {
            $response = $middleware->handle($first, $next, 10, 1, 'fixture-create:');
            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame((string) (9 - $i), $response->headers->get('X-RateLimit-Remaining'));
        }
        try {
            $middleware->handle($first, $next, 10, 1, 'fixture-create:');
            $this->fail('The eleventh admission must be refused.');
        } catch (ThrottleRequestsException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertSame(10, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertSame(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertGreaterThan(0, $e->getHeaders()['Retry-After']);
        }
        $this->assertSame(10, $this->limiter->attempts($this->key($first, 'fixture-create:')));
        $this->assertSame(0, $this->connection->table('cache_locks')->count());
        $this->assertSame(200, $middleware->handle($this->request(702), $next, 10, 1, 'fixture-create:')->getStatusCode());
        $this->assertSame(200, $middleware->handle($first, $next, 10, 1, 'fixture-read:')->getStatusCode());
        Carbon::setTestNow(Carbon::now()->addSeconds(61));
        $reset = $middleware->handle($first, $next, 10, 1, 'fixture-create:');
        $this->assertSame('9', $reset->headers->get('X-RateLimit-Remaining'));
        $this->assertSame(1, $this->limiter->attempts($this->key($first, 'fixture-create:')));
    }

    public function test_admission_cache_operations_have_a_real_short_lease_released_before_controller(): void
    {
        $admission = true;
        $operations = 0;
        $this->connection->beforeExecuting(function ($sql) use (&$admission, &$operations): void {
            if ($admission && str_contains($sql, '"cache"')) {
                $this->assertSame(1, $this->connection->table('cache_locks')->count(), 'The counter admission read/write must hold a native DB lease.');
                $lock = $this->connection->table('cache_locks')->sole();
                $this->assertSame(Carbon::now()->timestamp + 30, $lock->expiration);
                $this->assertNotEmpty($lock->owner);
                $operations++;
            }
        });
        $response = $this->middleware()->handle($this->request(), function () use (&$admission): Response {
            $admission = false;
            $this->assertSame(0, $this->connection->table('cache_locks')->count(), 'Admission must release before business logic.');

            // A same-key nested operation must proceed; holding the admission lock would time out.
            return $this->middleware()->handle($this->request(), static fn () => new Response('nested admitted'), 10, 1, 'lease:');
        }, 10, 1, 'lease:');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertGreaterThan(0, $operations);
        $this->assertSame(2, $this->limiter->attempts($this->key($this->request(), 'lease:')));
        $this->assertSame(0, $this->connection->table('cache_locks')->count());
    }

    public function test_native_counter_store_error_releases_owned_admission_lock(): void
    {
        $injected = false;
        $this->connection->beforeExecuting(static function ($sql) use (&$injected): void {
            if (! $injected && str_contains($sql, '"cache"')) {
                $injected = true;
                throw new RuntimeException('synthetic cache failure');
            }
        });
        try {
            $this->middleware()->handle($this->request(), static fn () => new Response('must not run'), 10, 1, 'cache-error:');
            $this->fail('The native cache error must not admit a controller.');
        } catch (RuntimeException $e) {
            $this->assertSame('synthetic cache failure', $e->getMessage());
        }
        $this->assertSame(0, $this->connection->table('cache_locks')->count());
        $this->assertSame(0, $this->limiter->attempts($this->key($this->request(), 'cache-error:')));
    }

    public function test_controller_exception_propagates_with_admission_already_released(): void
    {
        try {
            $this->middleware()->handle($this->request(), function (): void {
                $this->assertSame(0, $this->connection->table('cache_locks')->count());
                throw new RuntimeException('synthetic controller failure');
            }, 10, 1, 'controller-error:');
            $this->fail('The downstream exception must propagate.');
        } catch (RuntimeException $e) {
            $this->assertSame('synthetic controller failure', $e->getMessage());
        }
        $this->assertSame(1, $this->limiter->attempts($this->key($this->request(), 'controller-error:')));
        $this->assertSame(0, $this->connection->table('cache_locks')->count());
    }

    public static function cleanupFaultOrigins(): array
    {
        return ['native 429' => [false, 1], 'original counter error' => [true, 0]];
    }

    #[DataProvider('cleanupFaultOrigins')]
    public function test_release_query_failure_preserves_original_failure_and_successor_holder(bool $counterError, int $hits): void
    {
        $request = $this->request();
        $prefix = 'cleanup-fault:';
        if (! $counterError) {
            $this->middleware()->handle($request, static fn () => new Response('first admission'), 1, 1, $prefix);
        }
        $original = new RuntimeException('synthetic original counter exception');
        $cleanup = new RuntimeException('synthetic native release SQL exception');
        $counterFault = false;
        $releaseFault = false;
        $this->connection->beforeExecuting(function ($sql) use ($counterError, $original, $cleanup, &$counterFault, &$releaseFault): void {
            if ($counterError && ! $counterFault && str_contains($sql, '"cache"')) {
                $counterFault = true;
                throw $original;
            }
            if (! $releaseFault && str_starts_with($sql, 'delete from "cache_locks"')) {
                $releaseFault = true;
                $this->assertSame(1, $this->connection->table('cache_locks')->count());
                // Actual row ownership changes before the conditional DELETE encounters a backend fault.
                $this->connection->table('cache_locks')->update(['owner' => 'synthetic-cleanup-successor', 'expiration' => Carbon::now()->timestamp + 30]);
                throw $cleanup;
            }
        });
        $ran = false;
        $caught = null;
        try {
            $this->middleware()->handle($request, function () use (&$ran): Response {
                $ran = true;

                return new Response('must not run');
            }, 1, 1, $prefix);
        } catch (Throwable $exception) {
            $caught = $exception;
        }
        $this->assertTrue($releaseFault, 'The actual native owner-conditional release SQL must be attempted.');
        $this->assertFalse($ran);
        if ($counterError) {
            $this->assertSame($original, $caught, 'Cleanup must preserve the exact original Throwable, not merely its message.');
        } else {
            $this->assertInstanceOf(ThrottleRequestsException::class, $caught);
            $this->assertSame(429, $caught->getStatusCode());
            $this->assertSame(1, $caught->getHeaders()['X-RateLimit-Limit']);
            $this->assertGreaterThan(0, $caught->getHeaders()['Retry-After']);
        }
        $this->assertSame('synthetic-cleanup-successor', $this->connection->table('cache_locks')->sole()->owner);
        $this->assertSame($hits, $this->limiter->attempts($this->key($request, $prefix)));
    }

    public function test_unsupported_store_returns_translated_503_without_admission(): void
    {
        $this->app['config']->set('cache.limiter', 'unsupported');
        $ran = false;
        try {
            $this->middleware()->handle($this->request(), function () use (&$ran): Response {
                $ran = true;

                return new Response;
            }, 10, 1, 'unsupported:');
            $this->fail('An unsupported shared counter store must fail closed.');
        } catch (HttpResponseException $e) {
            $this->assertBusy($e);
        }
        $this->assertFalse($ran);
        $this->assertSame(0, $this->connection->table('cache')->count());
        $this->assertSame(0, $this->connection->table('cache_locks')->count());
    }

    private function assertBusy(HttpResponseException $exception): void
    {
        $response = $exception->getResponse();
        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('1', $response->headers->get('Retry-After'));
        $this->assertSame(__('raonslab-travel_lab::messages.throttle_busy'), $response->getData(true)['message']);
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_real_native_lock_timeout_preserves_another_holder_and_does_not_hit_counter(): void
    {
        Carbon::setTestNow(); // Lock::block uses now(): frozen time would make a real timeout unbounded.
        $prefix = 'timeout:';
        $request = $this->request();
        $holder = $this->cache->store('fixture')->getStore()->lock('travel-admission:'.hash('sha256', $this->key($request, $prefix)), 30, 'synthetic-holder');
        $this->assertTrue($holder->get());
        $ran = false;
        $started = microtime(true);
        try {
            $this->middleware()->handle($request, function () use (&$ran): Response {
                $ran = true;

                return new Response;
            }, 10, 1, $prefix);
            $this->fail('Contended admission must time out.');
        } catch (HttpResponseException $e) {
            $this->assertBusy($e);
        } finally {
            $this->assertTrue($holder->isOwnedByCurrentProcess());
            $holder->release();
        }
        $this->assertFalse($ran);
        $this->assertGreaterThanOrEqual(2.5, microtime(true) - $started);
        $this->assertLessThan(5, microtime(true) - $started);
        $this->assertSame(0, $this->limiter->attempts($this->key($request, $prefix)));
        $this->assertSame(0, $this->connection->table('cache_locks')->count());
    }

    public static function lostLeaseStages(): array
    {
        return ['before admission' => ['ownership', 0], 'during admission' => ['counter', 1]];
    }

    #[DataProvider('lostLeaseStages')]
    public function test_real_successor_lock_is_preserved_on_lost_lease(string $stage, int $expectedHits): void
    {
        $replaced = false;
        $this->connection->beforeExecuting(function ($sql) use ($stage, &$replaced): void {
            $trigger = $stage === 'ownership' ? str_starts_with($sql, 'select') && str_contains($sql, '"cache_locks"') : str_contains($sql, '"cache"');
            if (! $replaced && $trigger) {
                $replaced = true;
                $this->connection->table('cache_locks')->update(['owner' => 'synthetic-successor', 'expiration' => Carbon::now()->timestamp + 30]);
            }
        });
        $ran = false;
        try {
            $this->middleware()->handle($this->request(), function () use (&$ran): Response {
                $ran = true;

                return new Response;
            }, 10, 1, 'lost-lease:');
            $this->fail('Lost ownership must not admit a controller.');
        } catch (HttpResponseException $e) {
            $this->assertBusy($e);
        }
        $this->assertTrue($replaced);
        $this->assertFalse($ran);
        $this->assertSame('synthetic-successor', $this->connection->table('cache_locks')->sole()->owner);
        $this->assertSame($expectedHits, $this->limiter->attempts($this->key($this->request(), 'lost-lease:')));
    }

    public function test_four_actual_php_processes_share_one_temporary_sqlite_budget_without_counter_loss(): void
    {
        $directory = sys_get_temp_dir().'/g7-travel-throttle-'.bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($directory, 0700));
        $database = $directory.'/cache.sqlite';
        $this->assertNotFalse(file_put_contents($database, ''));
        chmod($database, 0600);
        $capsule = new Capsule;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => $database, 'prefix' => '', 'busy_timeout' => 5000, 'journal_mode' => 'WAL']);
        $connection = $capsule->getConnection();
        $this->createTables($connection);
        $workers = [];
        try {
            for ($i = 0; $i < 4; $i++) {
                $process = proc_open([PHP_BINARY, dirname(__DIR__, 2).'/Fixtures/TravelAtomicThrottleWorker.php', $database, (string) $i], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = ['process' => $process, 'pipes' => $pipes];
            }
            $deadline = microtime(true) + 10;
            do {
                $ready = count(glob($directory.'/ready-*'));
                if ($ready === 4) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertSame(4, $ready, 'All independently bootstrapped workers must reach the start barrier.');
            file_put_contents($directory.'/start', 'own-fixture-barrier');
            $deadline = microtime(true) + 15;
            do {
                $running = false;
                foreach ($workers as &$worker) {
                    if (! isset($worker['exit'])) {
                        $status = proc_get_status($worker['process']);
                        if (! $status['running']) {
                            $worker['exit'] = $status['exitcode'];
                        } else {
                            $running = true;
                        }
                    }
                }
                unset($worker);
                if ($running) {
                    usleep(10000);
                }
            } while ($running && microtime(true) < $deadline);
            $this->assertFalse($running, 'Own worker processes must finish within their bounded fixture window.');
            $records = [];
            foreach ($workers as &$worker) {
                $stdout = stream_get_contents($worker['pipes'][1]);
                $stderr = stream_get_contents($worker['pipes'][2]);
                fclose($worker['pipes'][1]);
                fclose($worker['pipes'][2]);
                proc_close($worker['process']);
                $this->assertSame(0, $worker['exit'], $stderr);
                $worker['process'] = null;
                $records[] = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
            }
            unset($worker);
            $codes = array_merge(...array_column($records, 'codes'));
            $this->assertCount(60, $codes);
            $this->assertSame(20, count(array_filter($codes, static fn ($code): bool => $code === 200)));
            $this->assertSame(40, count(array_filter($codes, static fn ($code): bool => $code === 429)));
            $this->assertSame([200, 429], array_values(array_unique(array_merge([200, 429], $codes))));
            $this->assertCount(4, array_unique(array_column($records, 'pid')));
            $this->assertLessThan(min(array_column($records, 'finished')), max(array_column($records, 'started')), 'Worker execution intervals must actually overlap.');
            $store = new DatabaseStore($connection, 'cache', '', 'cache_locks', [0, 100]);
            $counter = new RateLimiter(new Repository($store));
            $this->assertSame(20, $counter->attempts($this->key($this->request(), 'multi-process:')));
            $this->assertSame(0, $connection->table('cache_locks')->count());
        } finally {
            foreach ($workers as $worker) {
                if (is_resource($worker['process'])) {
                    proc_terminate($worker['process']);
                    foreach ($worker['pipes'] as $pipe) {
                        if (is_resource($pipe)) {
                            fclose($pipe);
                        }
                    }
                    proc_close($worker['process']);
                }
            }
            $connection->disconnect();
            foreach (glob($directory.'/*') as $file) {
                unlink($file); // Only this test's private random directory; no external schema/files.
            }
            rmdir($directory);
        }
    }
}
