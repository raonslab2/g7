<?php

namespace Tests\Unit\Extension;

use Composer\Autoload\ClassLoader;
use Illuminate\Auth\GenericUser;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\RateLimiter;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelOptionalSanctum;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelThrottleRequests;
use Modules\Raonslab\TravelLab\Services\TravelSupportService;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Actual support routes + travel admission/native RateLimiter/DatabaseStore on SQLite :memory:.
 * No application boot, auth provider, environment or installed schema is involved; no controller
 * action or service process executes. Actual controller metadata uses an unused service. Actors are
 * injected authenticated identifiers so only rate-budget isolation is exercised, not authorization
 * or native auth ordering. TravelSupportAuthThrottleOrderingTest exercises the latter separately.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class TravelSupportThrottleIsolationTest extends TestCase
{
    private Container $app;

    private Container $previousContainer;

    private $previousFacadeApplication;

    private Router $router;

    private ClassLoader $moduleLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $this->app = new Container;
        Container::setInstance($this->app);
        $this->router = new Router(new Dispatcher($this->app), $this->app);
        $this->app->instance('router', $this->router);
        $this->moduleLoader = new ClassLoader;
        $this->moduleLoader->addPsr4('Modules\\Raonslab\\TravelLab\\', dirname(__DIR__, 3).'/modules/_bundled/raonslab-travel_lab/src');
        $this->moduleLoader->register(true);
        $this->app->instance('config', new ConfigRepository(['cache' => [
            'default' => 'fixture', 'limiter' => 'fixture', 'stores' => ['fixture' => [
                'driver' => 'database', 'table' => 'cache', 'lock_table' => 'cache_locks', 'lock_lottery' => [0, 100], 'prefix' => '',
            ]],
        ]]));
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
        // Native route metadata may resolve this controller after another test registered its class.
        // Preserve its real constructor/metadata; isolate only the unused no-SQL service boundary.
        $this->app->instance(SupportController::class, new SupportController(Mockery::mock(TravelSupportService::class)));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        $this->router->group([
            'prefix' => 'api/modules/raonslab-travel_lab',
            'as' => 'api.modules.raonslab-travel_lab.',
            'middleware' => 'api',
        ], dirname(__DIR__, 3).'/modules/_bundled/raonslab-travel_lab/src/routes/support.php');
        $this->router->getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            $this->moduleLoader->unregister();
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

    /** Parameters come only from actual registered middleware, never a duplicated throttle policy. */
    private function middleware(Route $route): array
    {
        return array_values(array_filter($route->gatherMiddleware(), static fn ($name) => str_starts_with($name, TravelThrottleRequests::class.':')));
    }

    private function request(Route $route, int $actor): array
    {
        $request = Request::create('/'.str_replace('{id}', '1', $route->uri()), $route->methods()[0]);
        $request->setUserResolver(static fn () => new GenericUser(['id' => $actor]));
        $request->setRouteResolver(static fn () => $route);
        try {
            $response = (new Pipeline($this->app))->send($request)->through($this->middleware($route))
                ->then(static fn () => new Response('Native throttle fixture reached terminal', 200));

            return ['status' => $response->getStatusCode(), 'limit' => $response->headers->get('X-RateLimit-Limit')];
        } catch (ThrottleRequestsException $exception) {
            return ['status' => $exception->getStatusCode(), 'limit' => (string) ($exception->getHeaders()['X-RateLimit-Limit'] ?? '')];
        }
    }

    private function traffic(Route $route, int $actor, int $count): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->request($route, $actor)['status'];
        }

        return $codes;
    }

    public function test_public_and_own_question_reads_do_not_consume_the_ten_create_budget(): void
    {
        $this->assertSame([200], array_values(array_unique($this->traffic($this->route('notices.index'), 101, 40))));
        $this->assertSame([200], array_values(array_unique($this->traffic($this->route('questions.index'), 101, 70))));
        $create = $this->route('questions.store');
        $this->assertSame(array_fill(0, 10, 200), $this->traffic($create, 101, 10), 'Read traffic must not consume the first ten permitted creations.');
        $eleventh = $this->request($create, 101);
        $this->assertSame(429, $eleventh['status']);
        $this->assertSame('10', $eleventh['limit']);
    }

    public function test_eleventh_create_is_limited_and_another_actor_retains_ten_creations(): void
    {
        $create = $this->route('questions.store');
        $this->assertSame(array_fill(0, 10, 200), $this->traffic($create, 201, 10));
        $this->assertSame(429, $this->request($create, 201)['status']);
        $this->assertSame(array_fill(0, 10, 200), $this->traffic($create, 202, 10));
        $this->assertSame(429, $this->request($create, 202)['status']);
        $this->assertSame(200, $this->request($this->route('questions.index'), 201)['status'], 'Create limit must not throttle an ordinary question read.');
    }

    public function test_121st_question_read_is_limited_without_affecting_other_actors_or_public_reads(): void
    {
        $questions = $this->route('questions.index');
        $this->assertSame(array_fill(0, 120, 200), $this->traffic($questions, 301, 120));
        $limited = $this->request($questions, 301);
        $this->assertSame(429, $limited['status']);
        $this->assertSame('120', $limited['limit']);
        $this->assertSame(200, $this->request($questions, 302)['status']);
        $this->assertSame(200, $this->request($this->route('faqs.index'), 301)['status']);
    }

    public function test_601st_public_read_is_limited_without_exhausting_own_question_budget(): void
    {
        $public = $this->route('faqs.index');
        $this->assertSame(array_fill(0, 600, 200), $this->traffic($public, 401, 600));
        $limited = $this->request($public, 401);
        $this->assertSame(429, $limited['status']);
        $this->assertSame('600', $limited['limit']);
        $this->assertSame(200, $this->request($this->route('questions.index'), 401)['status']);
        $this->assertSame(200, $this->request($public, 402)['status']);
    }

    public function test_registered_support_routes_keep_auth_method_and_limit_contracts(): void
    {
        $public = $this->route('notices.index');
        $questions = $this->route('questions.index');
        $create = $this->route('questions.store');
        $this->assertContains('api', $public->gatherMiddleware());
        $this->assertContains(TravelOptionalSanctum::class, $public->gatherMiddleware());
        $this->assertNotContains('auth:sanctum', $public->gatherMiddleware());
        foreach (['questions.index', 'questions.show', 'questions.store', 'questions.update'] as $suffix) {
            $route = $this->route($suffix);
            $this->assertContains('api', $route->gatherMiddleware());
            $this->assertContains('auth:sanctum', $route->gatherMiddleware());
        }
        $limits = static fn ($route) => array_map(static fn ($middleware) => (int) explode(':', $middleware, 2)[1], array_filter($route->gatherMiddleware(), static fn ($middleware) => str_starts_with($middleware, TravelThrottleRequests::class.':')));
        $this->assertSame([600], array_values($limits($public)));
        $this->assertSame([120], array_values($limits($questions)));
        $this->assertSame([120, 10], array_values($limits($create)));
        $this->assertSame(['POST'], $create->methods());
        $this->assertSame(['PATCH'], $this->route('questions.update')->methods());
    }
}
