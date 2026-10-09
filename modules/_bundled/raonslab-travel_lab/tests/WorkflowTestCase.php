<?php

namespace Modules\Raonslab\TravelLab\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\TravelProduct;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowCartRepositoryInterface;
use Modules\Raonslab\TravelLab\Repositories\Contracts\WorkflowInquiryRepositoryInterface;
use Modules\Raonslab\TravelLab\Repositories\WorkflowCartRepository;
use Modules\Raonslab\TravelLab\Repositories\WorkflowInquiryRepository;
use Modules\Raonslab\TravelLab\Tests\Fixtures\WorkflowSqliteGrammar;
use Modules\Sirsoft\Ecommerce\Database\Factories\ProductFactory;
use Modules\Sirsoft\Ecommerce\Database\Factories\ProductOptionFactory;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;
use Modules\Sirsoft\Ecommerce\Tests\ModuleTestCase as CommerceModuleTestCase;

/**
 * 실제 이커머스 계산기를 쓰되 DB는 메모리 SQLite로 강제 격리한다.
 * 도메인 담당자의 ModuleTestCase와 독립적으로 워크플로 테스트를 실행한다.
 */
abstract class WorkflowTestCase extends CommerceModuleTestCase
{
    protected array $requiredExtensions = ['modules/_bundled/sirsoft-ecommerce'];

    private array $environmentBefore = [];

    private ?\Closure $travelLoader = null;

    public function createApplication()
    {
        // 앱 부팅 중의 DB 읽기도 운영 연결에 도달하지 않도록 bootstrap 전에 고정한다.
        foreach (['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => '', 'INSTALLER_COMPLETED' => 'false'] as $key => $value) {
            $this->environmentBefore[$key] ??= [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $app = require dirname(__DIR__, 4).'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function migrateFreshUsing(): array
    {
        $paths = array_merge(
            glob(base_path('database/migrations/*.php')) ?: [],
            glob(base_path('modules/_bundled/sirsoft-ecommerce/database/migrations/*.php')) ?: [],
        );
        // 실제 주문 적립의 MySQL generated-column 제약은 여행 문의와 무관하다.
        // SQLite에서 실행할 수 없는 그 1개만 제외하고 나머지 실제 스키마를 사용한다.
        $paths = array_values(array_filter($paths, static fn (string $path): bool => ! str_ends_with(
            $path, '2026_08_21_000001_add_unique_purchase_earn_lot_to_ecommerce_mileage_transactions_table.php'
        )));

        return [
            '--path' => $paths,
            '--realpath' => true,
            '--seed' => $this->shouldSeed(),
            '--seeder' => $this->seeder(),
        ];
    }

    protected function setUpTraits()
    {
        $connection = DB::connection();
        $connection->setSchemaGrammar(new WorkflowSqliteGrammar($connection));

        return parent::setUpTraits();
    }

    protected function setUp(): void
    {
        $source = dirname(__DIR__).'/src/';
        $this->travelLoader = static function (string $class) use ($source): void {
            $prefix = 'Modules\\Raonslab\\TravelLab\\';
            if (str_starts_with($class, $prefix)) {
                $file = $source.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
                if (is_file($file)) {
                    require_once $file;
                }
            }
        };
        spl_autoload_register($this->travelLoader, true, true);
        require_once __DIR__.'/Fixtures/DomainContract.php';

        parent::setUp();

        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->loadTravelSchema();
        $this->app->bind(WorkflowCartRepositoryInterface::class, WorkflowCartRepository::class);
        $this->app->bind(WorkflowInquiryRepositoryInterface::class, WorkflowInquiryRepository::class);

        Route::prefix('api/modules/raonslab-travel_lab')
            ->name('api.modules.raonslab-travel_lab.')
            ->middleware('api')
            ->group(dirname(__DIR__).'/src/routes/workflow.php');

        config(['sirsoft-ecommerce.cart.max_quantity' => 99]);
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            if ($this->travelLoader !== null) {
                spl_autoload_unregister($this->travelLoader);
            }
            foreach ($this->environmentBefore as $key => [$process, $environment, $server]) {
                putenv($process === false ? $key : $key.'='.$process);
                if ($environment === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $environment;
                }
                if ($server === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $server;
                }
            }
        }
    }

    private function loadTravelSchema(): void
    {
        if (Schema::hasTable('travel_lab_inquiry_items')) {
            return;
        }

        $migrations = glob(dirname(__DIR__).'/database/migrations/*.php') ?: [];
        if ($migrations !== []) {
            sort($migrations);
            foreach ($migrations as $path) {
                (require $path)->up();
            }

            return;
        }

        // 계약 fixture 스키마. 합본에서는 위 실제 마이그레이션을 실행한다.
        Schema::create('travel_lab_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('ecommerce_products');
            $table->string('region');
            $table->string('theme');
            $table->unsignedInteger('duration_days');
            $table->text('summary')->nullable();
            $table->json('itinerary')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
        Schema::create('travel_lab_departures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('ecommerce_products');
            $table->foreignId('product_option_id')->unique()->constrained('ecommerce_product_options');
            $table->date('departure_date');
            $table->date('return_date');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('reserved')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('travel_lab_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('idempotency_key');
            $table->char('payload_hash', 64);
            $table->string('status');
            $table->decimal('total_amount', 20, 2);
            $table->string('currency_code', 3);
            $table->json('contact')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
        });
        Schema::create('travel_lab_inquiry_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('travel_lab_inquiries');
            $table->foreignId('departure_id')->constrained('travel_lab_departures');
            $table->foreignId('product_id')->constrained('ecommerce_products');
            $table->foreignId('product_option_id')->constrained('ecommerce_product_options');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 20, 2);
            $table->decimal('line_total', 20, 2);
            $table->json('product_name');
            $table->date('departure_date');
            $table->timestamps();
        });
    }

    protected function departure(int $capacity = 10, int $price = 12000): Departure
    {
        $product = ProductFactory::new()->create([
            'name' => ['ko' => '테스트 여행', 'en' => 'Test Travel'],
            'selling_price' => $price, 'list_price' => $price,
            'stock_quantity' => 1000, 'max_purchase_qty' => 0,
        ]);
        $option = ProductOptionFactory::new()->forProduct($product)->create([
            'selling_price' => $price, 'price_adjustment' => 0,
            'stock_quantity' => 1000, 'is_active' => true,
        ]);
        TravelProduct::create([
            'product_id' => $product->id, 'region' => 'seoul', 'theme' => 'culture',
            'duration_days' => 2, 'summary' => '테스트', 'itinerary' => [], 'published' => true,
        ]);

        return Departure::create([
            'product_id' => $product->id, 'product_option_id' => $option->id,
            'departure_date' => now()->addDays(30)->toDateString(),
            'return_date' => now()->addDays(31)->toDateString(),
            'capacity' => $capacity, 'reserved' => 0, 'is_active' => true,
        ]);
    }

    protected function ordinaryOption(): ProductOption
    {
        $product = ProductFactory::new()->create(['selling_price' => 9000, 'list_price' => 9000, 'stock_quantity' => 1000]);

        return ProductOptionFactory::new()->forProduct($product)->create(['selling_price' => 9000, 'price_adjustment' => 0, 'stock_quantity' => 1000]);
    }
}
