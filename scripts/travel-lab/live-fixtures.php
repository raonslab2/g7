<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\UserService;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface;
use Modules\Sirsoft\Ecommerce\Services\ProductService;

/** New synthetic rows only; never mutates a seed product or an existing member. */
function travelLabUser(string $run, string $suffix): User
{
    return app(UserService::class)->createUser([
        'name' => 'Lab concurrency '.$suffix,
        'email' => 'lab-'.$run.'-'.$suffix.'@travel-lab.example.invalid',
        'password' => bin2hex(random_bytes(16)),
        'email_verified_at' => now(),
        'language' => 'ko',
    ]);
}

function travelLabDeparture(string $run, int $capacity = 1): Departure
{
    $catalog = app(CatalogRepositoryInterface::class);
    $policy = $catalog->findSampleShippingPolicy();
    travelLabCheck($policy !== null, 'Run explicit module sample seed before live fixtures.');
    $products = app(ProductService::class);
    $product = $products->create([
        'product_code' => $products->generateUniqueCode(),
        'sku' => 'TRAVEL-LAB-LIVE-'.strtoupper($run),
        'name' => ['ko' => '격리 동시성 실험 (테스트)', 'en' => 'Isolated concurrency experiment (test)'],
        'description' => ['ko' => '합성 검증 데이터', 'en' => 'Synthetic verification data'],
        'description_mode' => 'text',
        'selling_price' => 12000, 'list_price' => 12000,
        'sales_status' => 'on_sale', 'display_status' => 'visible', 'tax_status' => 'tax_free',
        'shipping_policy_id' => $policy->id, 'has_options' => true,
        'min_purchase_qty' => 1, 'max_purchase_qty' => 12,
        'options' => [[
            'option_code' => 'LIVE-'.$run,
            'option_name' => ['ko' => '합성 출발', 'en' => 'Synthetic departure'],
            'option_values' => [['key' => ['ko' => '출발일', 'en' => 'Departure'], 'value' => ['ko' => '합성', 'en' => 'synthetic']]],
            'price_adjustment' => 0, 'stock_quantity' => $capacity,
            'is_active' => true, 'is_default' => false,
        ]],
    ]);
    $catalog->seedMetadata($product->id, [
        'region' => 'jeju', 'theme' => 'nature', 'duration_days' => 2,
        'summary' => ['ko' => '실제 예약 없는 동시성 실험', 'en' => 'Concurrency experiment without real bookings'],
        'itinerary' => [], 'published' => true,
    ]);

    return $catalog->seedDeparture($product->id, $product->options->sole()->id, [
        'departure_date' => now()->addDays(45)->toDateString(),
        'return_date' => now()->addDays(46)->toDateString(),
        'capacity' => $capacity, 'is_active' => true,
    ]);
}
