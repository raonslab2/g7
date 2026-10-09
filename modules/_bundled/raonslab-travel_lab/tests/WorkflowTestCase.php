<?php

namespace Modules\Raonslab\TravelLab\Tests;

use App\Enums\PermissionType;
use App\Extension\HookListenerRegistrar;
use App\Helpers\PermissionHelper;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Listeners\BlockTravelCommerceCheckout;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;
use Modules\Sirsoft\Ecommerce\Services\ShippingPolicyService;
use Modules\Sirsoft\Ecommerce\Support\CurrencySettingsCache;

/** Actual domain model/migration/provider, real ecommerce services, isolated SQLite only. */
abstract class WorkflowTestCase extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        PermissionHelper::clearPermissionScopeCache();
        CurrencySettingsCache::clear();
        app('translator')->addNamespace('raonslab-travel_lab', dirname(__DIR__).'/src/lang');
        foreach (['user', 'admin', 'guest'] as $identifier) {
            Role::firstOrCreate(['identifier' => $identifier], ['name' => ['ko' => $identifier, 'en' => $identifier]]);
        }
        config(['sirsoft-ecommerce.cart.max_quantity' => 99]);
        Route::prefix('api/modules/raonslab-travel_lab')
            ->name('api.modules.raonslab-travel_lab.')
            ->middleware('api')->group(dirname(__DIR__).'/src/routes/workflow.php');
        Route::getRoutes()->refreshNameLookups();
        HookListenerRegistrar::register(BlockTravelCommerceCheckout::class, 'raonslab-travel_lab');
    }

    protected function createUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('identifier', 'user')->sole());

        return $user;
    }

    protected function createAdminUser(array $permissions = []): User
    {
        $user = User::factory()->create();
        $role = Role::create(['identifier' => 'travel-admin-'.$user->id, 'name' => ['ko' => '시험 관리자', 'en' => 'Test admin']]);
        $user->roles()->attach($role);
        foreach (['admin.access', ...$permissions] as $identifier) {
            $permission = Permission::firstOrCreate(['identifier' => $identifier], [
                'name' => ['ko' => $identifier, 'en' => $identifier], 'type' => PermissionType::Admin,
            ]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return $user;
    }

    protected function departure(int $capacity = 10, int $price = 12000): Departure
    {
        $policy = app(ShippingPolicyService::class)->create([
            'name' => ['ko' => '시험 배송 없음', 'en' => 'Test nonshipping'],
            'is_active' => true, 'is_default' => false,
            'country_settings' => [[
                'country_code' => 'KR', 'shipping_method' => 'custom',
                'charge_policy' => 'free', 'base_fee' => 0, 'extra_fee_enabled' => false, 'is_active' => true,
            ]],
        ]);
        [, $departure] = $this->createTravel(
            ['summary' => ['ko' => '테스트', 'en' => 'Test']],
            ['name' => ['ko' => '테스트 여행', 'en' => 'Test Travel'], 'selling_price' => $price, 'list_price' => $price, 'stock_quantity' => 1000, 'max_purchase_qty' => 0, 'shipping_policy_id' => $policy->id],
            ['capacity' => $capacity, 'departure_date' => now()->addDays(30)->toDateString(), 'return_date' => now()->addDays(31)->toDateString()],
            ['stock_quantity' => 1000, 'selling_price' => $price, 'price_adjustment' => 0],
        );

        return $departure;
    }

    protected function ordinaryOption(): ProductOption
    {
        $product = Product::withoutSyncingToSearch(fn () => Product::create([
            'product_code' => 'ORDINARY-'.bin2hex(random_bytes(5)), 'name' => ['ko' => '일반 상품', 'en' => 'Ordinary product'],
            'selling_price' => 9000, 'list_price' => 9000, 'stock_quantity' => 1000,
            'display_status' => 'visible', 'sales_status' => 'on_sale',
        ]));

        return ProductOption::create([
            'product_id' => $product->id, 'option_code' => 'ORDINARY-'.bin2hex(random_bytes(5)),
            'option_values' => [], 'option_name' => ['ko' => '기본', 'en' => 'Default'],
            'selling_price' => 9000, 'price_adjustment' => 0, 'stock_quantity' => 1000, 'is_active' => true,
        ]);
    }
}
