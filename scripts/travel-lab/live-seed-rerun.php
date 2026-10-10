<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;

require __DIR__.'/live-bootstrap.php';

function travelLabSeedDigest(): array
{
    $result = [];
    foreach (['users', 'ecommerce_products', 'ecommerce_product_options', 'travel_lab_products', 'travel_lab_departures', 'travel_lab_inquiries', 'travel_lab_inquiry_items', 'boards', 'board_posts'] as $table) {
        $rows = DB::table($table)->orderBy('id')->get()->toArray();
        $result[$table] = ['count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }

    return $result;
}

try {
    travelLabApp();
    $before = travelLabSeedDigest();
    foreach ([['module:seed', 'raonslab-travel_lab', '--sample', '--no-interaction'],
        ['raonslab-travel_lab:support-provision', '--lab-confirm', '--no-interaction']] as $command) {
        travelLabCheck(travelLabProcess([PHP_BINARY, 'artisan', ...$command], travelLabEnvironment()) === 0, 'Explicit lab seed/provision command failed.');
    }
    DB::purge();
    travelLabCheck(travelLabSeedDigest() === $before, 'Repeated sample seed/provision changed existing synthetic rows or reservations.');
    echo json_encode(['status' => 'PASS_IMPLEMENTER_SEED_RERUN', 'db' => 'req81_travel_lab',
        'source_sha' => trim(shell_exec('git rev-parse HEAD')), 'preserved_digests' => $before], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: '.$error->getMessage().PHP_EOL);
    exit(1);
}
