<?php

namespace Tests\Unit\Extension;

use App\Helpers\ResponseHelper;
use App\Http\Middleware\OptionalSanctumMiddleware;
use Carbon\Carbon;
use Composer\Autoload\ClassLoader;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\GenericUser;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\RequestGuard;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\RateLimiter;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Mockery;
use Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelThrottleRequests;
use Modules\Raonslab\TravelLab\Services\TravelSupportService;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Native ordering/optional-auth decision, with SQL token lookup and user-provider data isolated.
 * No preassigned identity, dotenv/application boot, controller action or installed DB/cache.
 * The admission cache is actual native DatabaseStore on isolated SQLite :memory:.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class TravelSupportAuthThrottleOrderingTest extends TestCase
{
    private Application $app;

    private Container $previousContainer;

    private $previousFacadeApplication;

    private $previousClock;

    private Router $router;

    private AuthManager $auth;

    private ClassLoader $moduleLoader;

    private array $tokens = [];

    private int $guardCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $this->previousClock = Carbon::getTestNow();
        Carbon::setTestNow('2026-10-09 12:00:00 UTC');
        $this->app = new Application(sys_get_temp_dir().'/g7-auth-throttle-unbooted');
        $this->app->instance('config', new ConfigRepository([
            'app' => ['locale' => 'en', 'debug' => false],
            'auth' => ['defaults' => ['guard' => 'web'], 'guards' => [
                'web' => ['driver' => 'fixture-guest'],
                'sanctum' => ['driver' => 'fixture-token'],
            ]],
            'cache' => ['default' => 'fixture', 'limiter' => 'fixture', 'stores' => ['fixture' => [
                'driver' => 'database', 'table' => 'cache', 'lock_table' => 'cache_locks', 'lock_lottery' => [0, 100], 'prefix' => '',
            ]]],
        ]));
        $this->app->instance('translator', new Translator(new ArrayLoader, 'en'));
        $this->router = new Router(new Dispatcher($this->app), $this->app);
        $this->app->instance('router', $this->router);
        (new Kernel($this->app, $this->router)); // copies native middlewarePriority to Router
        $definition = new Middleware;
        foreach ($definition->getMiddlewareAliases() as $name => $class) {
            $this->router->aliasMiddleware($name, $class);
        }
        $this->router->aliasMiddleware('optional.sanctum', OptionalSanctumMiddleware::class);
        $this->router->middlewareGroup('api', $definition->getMiddlewareGroups()['api']);
        $this->app->instance(ResponseFactoryContract::class, new ResponseFactory(
            Mockery::mock(ViewFactory::class), new Redirector(new UrlGenerator($this->router->getRoutes(), Request::create('/'))),
        ));
        $this->auth = new AuthManager($this->app);
        $this->auth->extend('fixture-guest', fn () => new RequestGuard(static fn () => null, $this->app['request']));
        $this->auth->extend('fixture-token', fn () => new RequestGuard(function ($request) {
            $this->guardCalls++;
            $record = $this->tokens[$request->bearerToken()] ?? null;

            return $record ? new GenericUser(['id' => $record->actor]) : null;
        }, $this->app['request']));
        $this->app->instance(Authenticate::class, new Authenticate($this->auth));
        // Token lookup is the only native OptionalSanctum dependency stub; cache SQL stays in memory.
        Mockery::mock('alias:Laravel\\Sanctum\\PersonalAccessToken')->shouldReceive('findToken')
            ->andReturnUsing(fn ($token) => $this->tokens[$token] ?? null);
        $this->moduleLoader = new ClassLoader;
        $this->moduleLoader->addPsr4('Modules\\Raonslab\\TravelLab\\', dirname(__DIR__, 3).'/modules/_bundled/raonslab-travel_lab/src');
        $this->moduleLoader->register();
        $capsule = new Capsule($this->app);
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $this->app->instance('db', $capsule->getDatabaseManager());
        $schema = $capsule->getConnection()->getSchemaBuilder();
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
        $cache = new CacheManager($this->app);
        $this->app->instance(TravelThrottleRequests::class, new TravelThrottleRequests(new RateLimiter($cache->store('fixture')), $cache));
        // Keep native controller metadata while its unused service stays a no-SQL boundary.
        $this->app->instance(SupportController::class, new SupportController(Mockery::mock(TravelSupportService::class)));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        $this->router->group([
            'prefix' => 'api/modules/raonslab-travel_lab', 'as' => 'api.modules.raonslab-travel_lab.', 'middleware' => 'api',
        ], dirname(__DIR__, 3).'/modules/_bundled/raonslab-travel_lab/src/routes/support.php');
        $this->router->getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            $this->moduleLoader->unregister();
            Carbon::setTestNow($this->previousClock);
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->previousFacadeApplication);
            Container::setInstance($this->previousContainer);
            parent::tearDown();
        }
    }

    private function route(string $suffix): Route
    {
        $route = $this->router->getRoutes()->getByName('api.modules.raonslab-travel_lab.support.'.$suffix);
        $this->assertInstanceOf(Route::class, $route);

        return $route;
    }

    private function token(int $actor, bool $expired = false): string
    {
        $token = bin2hex(random_bytes(24));
        $this->tokens[$token] = (object) ['actor' => $actor, 'expires_at' => $expired ? Carbon::now()->subMinute() : null];

        return $token;
    }

    private function request(Route $route, ?string $token = null, string $ip = '192.0.2.10'): array
    {
        $request = Request::create('/'.str_replace('{id}', '1', $route->uri()), $route->methods()[0], server: ['REMOTE_ADDR' => $ip, 'HTTP_ACCEPT' => 'application/json']);
        if ($token !== null) {
            $request->headers->set('Authorization', 'Bearer '.$token);
        }
        $this->app->instance('request', $request);
        $this->auth->forgetGuards()->setDefaultDriver('web');
        $request->setUserResolver(fn ($guard = null) => $this->auth->guard($guard)->user());
        $route->bind($request);
        $request->setRouteResolver(static fn () => $route);
        $this->assertNull($request->user(), 'Every request starts guest; the native auth middleware must establish its actor.');
        try {
            $response = (new Pipeline($this->app))->send($request)
                ->through($this->router->gatherRouteMiddleware($route))
                ->then(static fn ($request) => ResponseHelper::success(data: ['actor' => $request->user()?->getAuthIdentifier()]));
        } catch (ThrottleRequestsException $exception) {
            return ['status' => 429, 'limit' => (string) $exception->getHeaders()['X-RateLimit-Limit'], 'body' => null];
        } catch (AuthenticationException) {
            $response = ResponseHelper::unauthorized(); // isolated API render boundary, no core handler boot
        }

        return ['status' => $response->getStatusCode(), 'limit' => $response->headers->get('X-RateLimit-Limit'), 'body' => $response->getData(true)];
    }

    private function traffic(Route $route, ?string $token, int $count, string $ip = '192.0.2.10'): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->request($route, $token, $ip)['status'];
        }

        return $codes;
    }

    public function test_native_router_orders_optional_authentication_before_public_throttle(): void
    {
        $resolved = $this->router->gatherRouteMiddleware($this->route('notices.index'));
        $auth = array_keys(array_filter($resolved, static fn ($name) => is_a(explode(':', $name, 2)[0], OptionalSanctumMiddleware::class, true)));
        $throttle = array_keys(array_filter($resolved, static fn ($name) => is_a(explode(':', $name, 2)[0], ThrottleRequests::class, true)));
        $this->assertCount(1, $auth);
        $this->assertCount(1, $throttle);
        $this->assertLessThan($throttle[0], $auth[0], 'Native priority sorting must resolve the actor before signing its public throttle key.');
    }

    public function test_distinct_authenticated_actors_on_one_ip_have_separate_exact_public_600_budgets(): void
    {
        $route = $this->route('notices.index');
        $first = $this->token(501);
        $second = $this->token(502);
        $this->assertSame(array_fill(0, 600, 200), $this->traffic($route, $first, 600));
        $this->assertSame(429, $this->request($route, $first)['status']);
        $fresh = $this->request($route, $second);
        $this->assertSame(200, $fresh['status'], 'A different valid actor must not inherit the exhausted same-IP bucket.');
        $this->assertSame(502, $fresh['body']['data']['actor']);
        $this->assertSame(array_fill(0, 599, 200), $this->traffic($route, $second, 599));
        $limited = $this->request($route, $second);
        $this->assertSame(429, $limited['status']);
        $this->assertSame('600', $limited['limit']);
    }

    public function test_anonymous_ip_bucket_remains_shared_but_does_not_block_a_valid_actor(): void
    {
        $route = $this->route('faqs.index');
        $this->assertSame(array_fill(0, 600, 200), $this->traffic($route, null, 600));
        $this->assertSame(429, $this->request($route)['status']);
        $otherIp = $this->request($route, ip: '192.0.2.11');
        $this->assertSame(200, $otherIp['status']);
        $this->assertNull($otherIp['body']['data']['actor']);
        $authenticated = $this->request($route, $this->token(503));
        $this->assertSame(200, $authenticated['status']);
        $this->assertSame(503, $authenticated['body']['data']['actor']);
    }

    public function test_native_optional_auth_preserves_guest_expired_invalid_and_valid_token_decisions(): void
    {
        $route = $this->route('faqs.index');
        $guest = $this->request($route);
        $this->assertSame(200, $guest['status']);
        $this->assertTrue($guest['body']['success']);
        $this->assertNull($guest['body']['data']['actor']);
        $expired = $this->request($route, $this->token(504, expired: true));
        $this->assertSame(200, $expired['status']);
        $this->assertNull($expired['body']['data']['actor']);
        $this->assertSame(0, $this->guardCalls, 'Expired native token decision must not promote a fixture actor.');
        $invalid = $this->request($route, bin2hex(random_bytes(24)));
        $this->assertSame(401, $invalid['status']);
        $this->assertFalse($invalid['body']['success']);
        $this->assertSame('auth.invalid_token', $invalid['body']['message']);
        $valid = $this->request($route, $this->token(505));
        $this->assertSame(200, $valid['status']);
        $this->assertSame(505, $valid['body']['data']['actor']);
        $this->assertSame(1, $this->guardCalls);
    }

    public function test_actual_required_auth_pipeline_keeps_create10_and_aggregate_question120_isolation(): void
    {
        $create = $this->route('questions.store');
        $questions = $this->route('questions.index');
        $this->assertSame(401, $this->request($questions)['status']);
        $first = $this->token(601);
        $second = $this->token(602);
        $this->assertSame(array_fill(0, 10, 200), $this->traffic($create, $first, 10));
        $limited = $this->request($create, $first);
        $this->assertSame(429, $limited['status']);
        $this->assertSame('10', $limited['limit']);
        $this->assertSame(200, $this->request($create, $second)['status']);
        $this->assertSame(array_fill(0, 109, 200), $this->traffic($questions, $first, 109));
        $aggregate = $this->request($questions, $first);
        $this->assertSame(429, $aggregate['status']);
        $this->assertSame('120', $aggregate['limit']);
        $this->assertSame(200, $this->request($questions, $second)['status']);
        $this->assertSame(200, $this->request($this->route('faqs.index'), $first)['status']);
    }
}
