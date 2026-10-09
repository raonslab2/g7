<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;

require __DIR__.'/live-bootstrap.php';

try {
    travelLabApp();
    $rows = [];
    foreach (['travel_lab_inquiries', 'travel_lab_inquiry_items', 'travel_lab_departures', 'travel_lab_inquiry_events'] as $table) {
        $records = DB::table($table)->orderBy('id')->get()->toArray();
        $rows[$table] = ['count' => count($records), 'sha256' => hash('sha256', json_encode($records, JSON_THROW_ON_ERROR))];
    }
    echo json_encode(['db' => 'req81_travel_lab', 'digests' => $rows], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: '.$error->getMessage().PHP_EOL);
    exit(1);
}
