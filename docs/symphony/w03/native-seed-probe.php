<?php

declare(strict_types=1);

$w03Root = dirname(__DIR__, 3);
require __DIR__.'/test-bootstrap.php';
require $w03Root.'/scripts/travel-lab/live-bootstrap.php';
App\Extension\Testing\ExtensionTestAllowlist::set(['modules/sirsoft-ecommerce', 'modules/raonslab-travel_lab']);
$app = travelLabApp(true);
$app->register(Modules\Sirsoft\Ecommerce\Providers\EcommerceServiceProvider::class);
$app->register(Modules\Raonslab\TravelLab\Providers\TravelLabServiceProvider::class);
function w03SeedDigests(): array
{
    $result = [];
    foreach (['users', 'ecommerce_products', 'ecommerce_product_options', 'travel_lab_products', 'travel_lab_departures', 'travel_lab_inquiries', 'travel_lab_inquiry_items', 'travel_lab_inquiry_events', 'ecommerce_orders', 'ecommerce_order_payments'] as $table) {
        $rows = Illuminate\Support\Facades\DB::table($table)->orderBy('id')->get()->toArray();
        $result[$table] = ['count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }

    return $result;
}
$before = w03SeedDigests();
$seeder = $app->make(Modules\Raonslab\TravelLab\Database\Seeders\DatabaseSeeder::class)->setContainer($app);
$seeder->run();
travelLabCheck(w03SeedDigests() === $before, 'Default native travel seed changed existing synthetic records.');
$seeder->setIncludeSample(true)->run();
travelLabCheck(w03SeedDigests() === $before, 'Explicit native sample rerun changed existing synthetic records.');
echo json_encode(['status' => 'PASS_W03_NATIVE_DEFAULT_AND_EXPLICIT_SEED_RERUN',
    'db' => 'req81_travel_lab_test', 'tables_unchanged' => count($before), 'preserved_digests' => $before,
    'limitation' => 'Native seeder classes on TEST; setup/account rotation and installed APP module:seed path NOT_RUN'], JSON_PRETTY_PRINT).PHP_EOL;
