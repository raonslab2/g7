<?php

namespace Modules\Raonslab\TravelLab\Tests;

use App\Extension\Testing\ExtensionTestAllowlist;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Modules\Raonslab\TravelLab\Enums\CatalogSort;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Providers\TravelLabServiceProvider;
use Modules\Raonslab\TravelLab\Repositories\CatalogRepository;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Raonslab\TravelLab\Services\TravelCartService;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;
use Modules\Sirsoft\Ecommerce\Providers\EcommerceServiceProvider;

/** Real isolated SQLite database; no mock repository or operational DB fallback. */
abstract class ModuleTestCase extends TestCase
{
    public function createApplication(): Application
    {
        ExtensionTestAllowlist::set(['modules/sirsoft-ecommerce', 'modules/raonslab-travel_lab']);
        $app = require dirname(__DIR__, 4).'/bootstrap/app.php';
        $app->useEnvironmentPath(__DIR__);
        $app->loadEnvironmentFrom('.env.travel-lab-tests'); // Intentionally absent: bootstrap pins the environment.
        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections', ['sqlite' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);
            $app['config']->set('app.supported_locales', ['ko', 'en']);
            $app['config']->set('scout.driver', 'null');
        });
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $canonicalSource = realpath(dirname(__DIR__).'/src');
        foreach ([
            CatalogRepository::class,
            TravelCartService::class,
            InquiryService::class,
            Departure::class,
            InquiryStatus::class,
            CatalogSort::class,
            TravelLabServiceProvider::class,
        ] as $class) {
            $this->assertStringStartsWith($canonicalSource.'/', (new \ReflectionClass($class))->getFileName());
        }
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Storage::fake('settings');
        Carbon::setTestNow('2026-10-09 12:00:00');
        $this->app->register(EcommerceServiceProvider::class);
        $this->app->register(TravelLabServiceProvider::class);
        $this->runSchema();
        $catalogRoutes = dirname(__DIR__).'/src/routes/catalog.php';
        if (is_file($catalogRoutes)) {
            Route::prefix('api/modules/raonslab-travel_lab')->name('api.modules.raonslab-travel_lab.')->middleware('api')->group($catalogRoutes);
            Route::getRoutes()->refreshNameLookups();
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        ExtensionTestAllowlist::reset();
        parent::tearDown();
    }

    protected function runSchema(): void
    {
        // Dependencies are migrated from native sources. Only travel migration down/up is under test.
        foreach (glob(base_path('database/migrations/*users_table.php')) as $file) {
            (require $file)->up();
        }
        foreach (['create_personal_access_tokens_table', 'create_permissions_table', 'create_roles_table', 'create_role_permissions_table', 'create_user_roles_table', 'add_scope_type_to_role_permissions_table', 'create_identity_policies_table'] as $name) {
            foreach (glob(base_path('database/migrations/*'.$name.'.php')) as $file) {
                (require $file)->up();
            }
        }
        $paths = glob(base_path('modules/_bundled/sirsoft-ecommerce/database/migrations/*.php'));
        sort($paths);
        foreach ($paths as $file) {
            // Unrelated coupon-category migration reuses MySQL-local idx_type; SQLite names indexes globally.
            if (str_contains($file, 'create_ecommerce_promotion_coupon_categories') || str_contains($file, 'drop_ecommerce_mail_templates') || str_contains($file, 'add_unique_purchase_earn_lot')) {
                continue;
            }
            (require $file)->up();
        }
        foreach (glob(dirname(__DIR__).'/database/migrations/*.php') as $file) {
            (require $file)->up();
        }
    }

    /** @return array{0: TravelProduct, 1: Departure} */
    protected function createTravel(array $travel = [], array $product = [], array $departure = [], array $option = []): array
    {
        $commerce = Product::withoutSyncingToSearch(fn () => Product::create(array_replace([
            'name' => ['ko' => '제주 바다 여행', 'en' => 'Jeju sea journey'],
            'product_code' => 'TRAVEL-TEST-'.bin2hex(random_bytes(5)),
            'selling_price' => 100000, 'list_price' => 100000, 'has_options' => true,
            'sales_status' => 'on_sale', 'display_status' => 'visible', 'stock_quantity' => 20,
        ], $product)));
        $metadata = TravelProduct::create(array_replace([
            'product_id' => $commerce->id, 'region' => 'jeju', 'theme' => 'nature',
            'duration_days' => 3, 'summary' => ['ko' => '바다 산책', 'en' => 'Sea walk'],
            'itinerary' => [['day' => 1, 'title' => ['ko' => '바다', 'en' => 'Sea']]], 'published' => true,
        ], $travel));
        $commerceOption = ProductOption::create(array_replace([
            'product_id' => $commerce->id, 'option_code' => 'DEPART-TEST',
            'option_values' => ['date' => '2026-11-01'], 'option_name' => ['ko' => '출발', 'en' => 'Departure'],
            'price_adjustment' => 0, 'stock_quantity' => 20, 'is_active' => true,
        ], $option));
        $date = Departure::create(array_replace([
            'product_id' => $commerce->id, 'product_option_id' => $commerceOption->id,
            'departure_date' => '2026-11-01', 'return_date' => '2026-11-03',
            'capacity' => 20, 'reserved' => 0, 'is_active' => true,
        ], $departure));

        if (isset($departure['reserved'])) {
            $date->forceFill(['reserved' => $departure['reserved']])->save();
        }

        return [$metadata, $date];
    }
}
