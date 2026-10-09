<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\Inquiry;
use Modules\Raonslab\TravelLab\Providers\TravelLabServiceProvider;
use Modules\Raonslab\TravelLab\Tests\UsesDatabaseThrottleCache;
use Modules\Sirsoft\Board\Providers\BoardServiceProvider;
use Modules\Sirsoft\Ecommerce\Database\Seeders\DatabaseSeeder;
use Modules\Sirsoft\Ecommerce\Providers\EcommerceServiceProvider;
use Modules\Sirsoft\Page\Providers\PageServiceProvider;
use Tests\TestCase;

require_once __DIR__.'/environment.php';
require_once __DIR__.'/live-bootstrap.php';
require_once __DIR__.'/live-fixtures.php';
// This root PHPUnit fixture has no module Tests namespace autoloader; pin the shared test trait.
require_once dirname(__DIR__, 2).'/modules/_bundled/raonslab-travel_lab/tests/UsesDatabaseThrottleCache.php';

/** Actual root Tests\TestCase + Sanctum + native services; MySQL only, no SQLite/mocks. */
final class LiveMysqlTest extends TestCase
{
    use UsesDatabaseThrottleCache;

    protected array $requiredExtensions = ['modules/sirsoft-board', 'modules/sirsoft-page', 'modules/sirsoft-ecommerce', 'modules/raonslab-travel_lab'];

    protected function setUp(): void
    {
        // Apply the sanitized adapters before parent bootstrap can open a DB connection.
        travelLabApplyEnvironment(travelLabEnvironment(true));
        parent::setUp();
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('req81_travel_lab_test', DB::connection()->getDatabaseName());
        foreach ([BoardServiceProvider::class, PageServiceProvider::class, EcommerceServiceProvider::class, TravelLabServiceProvider::class] as $provider) {
            $this->app->register($provider);
        }
        $paths = [base_path('database/migrations')];
        foreach (['sirsoft-board', 'sirsoft-page', 'sirsoft-ecommerce', 'raonslab-travel_lab'] as $identifier) {
            $paths[] = base_path('modules/_bundled/'.$identifier.'/database/migrations');
        }
        $this->assertSame(0, Artisan::call('migrate', ['--path' => $paths, '--realpath' => true, '--force' => true]));
        // Ordinary test boot stays array; actual travel admission requires native DB counters/locks.
        $this->useDatabaseThrottleCache();
        $cache = app('cache')->store(config('cache.limiter'))->getStore();
        $this->assertInstanceOf(DatabaseStore::class, $cache);
        $this->assertSame(DB::connection()->getDatabaseName(), $cache->getConnection()->getDatabaseName());
        $this->assertSame(DB::connection()->getDatabaseName(), $cache->getLockConnection()->getDatabaseName());
        if (User::count() === 0) {
            $this->assertSame(0, Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]));
        }
        $this->assertSame(0, Artisan::call('db:seed', [
            '--class' => DatabaseSeeder::class, '--force' => true,
        ])); // Native installation reference data only; ecommerce --sample/order seeds are excluded.
        $seed = app(Modules\Raonslab\TravelLab\Database\Seeders\DatabaseSeeder::class);
        $seed->setIncludeSample(true);
        $seed->run();
        // The test DB deliberately does not install runtime module metadata. Load the actual
        // bundled route entry once into the root HTTP kernel for this contract test.
        Route::prefix('api/modules/raonslab-travel_lab')->name('api.modules.raonslab-travel_lab.')
            ->middleware('api')->group(base_path('modules/_bundled/raonslab-travel_lab/src/routes/api.php'));
    }

    private function bearer(User $user): array
    {
        Auth::forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('isolated-mysql-test')->plainTextToken];
    }

    public function test_real_mysql_http_cart_inquiry_owner_and_server_price_contract(): void
    {
        $api = '/api/modules/raonslab-travel_lab';
        $run = bin2hex(random_bytes(6));
        $owner = travelLabUser($run, 'owner');
        $other = travelLabUser($run, 'other');
        $departure = travelLabDeparture($run, 3);
        $this->getJson($api.'/cart')->assertUnauthorized();
        $this->getJson($api.'/catalog')->assertOk();
        $headers = $this->bearer($owner);
        $this->withHeaders($headers)->postJson($api.'/cart', ['departure_id' => $departure->id, 'quantity' => 0])->assertUnprocessable();
        $this->withHeaders($headers)->postJson($api.'/cart', [
            'departure_id' => $departure->id, 'quantity' => 2, 'unit_price' => 1,
        ])->assertUnprocessable();
        $added = $this->withHeaders($headers)->postJson($api.'/cart', [
            'departure_id' => $departure->id, 'quantity' => 2,
        ])->assertCreated();
        $cartId = $added->json('data.items.0.id');
        $this->assertNotNull($cartId);
        $this->assertEquals(24000, $added->json('data.totals.final_amount'));
        $payload = ['cart_ids' => [$cartId], 'contact' => ['name' => 'Synthetic MySQL owner'], 'idempotency_key' => $run.'-mysql'];
        $this->withHeaders($this->bearer($owner))->postJson($api.'/inquiries', $payload + ['total_amount' => 1])->assertUnprocessable();
        $created = $this->withHeaders($this->bearer($owner))->postJson($api.'/inquiries', $payload)->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals(24000, $created->json('data.total_amount'));
        $this->assertSame('TEST_INQUIRY', $created->json('data.status'));
        $this->assertSame(2, Departure::findOrFail($departure->id)->reserved);
        $this->assertNotEmpty(Inquiry::findOrFail($id)->calculation_snapshot);
        $replay = $this->withHeaders($this->bearer($owner))->postJson($api.'/inquiries', $payload)->assertOk();
        $this->assertSame($id, $replay->json('data.id'));
        $this->assertSame(1, Inquiry::where('user_id', $owner->id)->where('idempotency_key', $payload['idempotency_key'])->count());
        $payload['contact']['name'] = 'Changed payload';
        $this->withHeaders($this->bearer($owner))->postJson($api.'/inquiries', $payload)->assertConflict();
        $this->withHeaders($this->bearer($other))->getJson($api.'/inquiries/'.$id)->assertNotFound();
        $this->withHeaders($this->bearer($other))->getJson($api.'/admin/inquiries')->assertForbidden();
        $this->withHeaders($this->bearer($owner))->postJson($api.'/inquiries/'.$id.'/cancel')->assertOk()->assertJsonPath('data.status', 'CANCELLED');
        $this->withHeaders($this->bearer($owner))->postJson($api.'/inquiries/'.$id.'/cancel')->assertOk();
        $this->assertSame(0, Departure::findOrFail($departure->id)->reserved);
        $this->assertSame(3, $departure->option->fresh()->stock_quantity);
        $this->assertSame(0, DB::table('ecommerce_orders')->where('user_id', $owner->id)->count());
        $this->assertSame(0, DB::table('ecommerce_order_payments')->count());
    }
}
