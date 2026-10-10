<?php

declare(strict_types=1);

$w03Root = dirname(__DIR__, 3);
require __DIR__.'/test-bootstrap.php';
require $w03Root.'/scripts/travel-lab/live-bootstrap.php';
travelLabApp(true);
$evidence = json_decode(file_get_contents(__DIR__.'/evidence/api-capture-test-fault.json'), true, flags: JSON_THROW_ON_ERROR);
travelLabCheck($evidence['exit_code'] === 1, 'Capture fault must exit 1.');
preg_match('/CLEANUP: (\{[^\n]+\})/', $evidence['output'], $match);
travelLabCheck(isset($match[1]), 'Capture cleanup result is missing.');
$cleanup = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
travelLabCheck(($cleanup['failure_injected'] ?? false) === true, 'Expected fault was not reached.');
$inquiry = Illuminate\Support\Facades\DB::table('travel_lab_inquiries')->find($cleanup['inquiry_id']);
$departure = Illuminate\Support\Facades\DB::table('travel_lab_departures')->find($cleanup['departure_id']);
$metadata = Illuminate\Support\Facades\DB::table('travel_lab_products')->where('product_id', $cleanup['product_id'])->sole();
travelLabCheck($inquiry->status === 'CANCELLED', 'Fresh-process inquiry must be cancelled.');
travelLabCheck((int) $departure->reserved === 0, 'Fresh-process reservation must be released.');
travelLabCheck((int) $metadata->published === 0, 'Fresh-process fixture must be unpublished.');
travelLabCheck(Illuminate\Support\Facades\DB::table('personal_access_tokens')
    ->where('tokenable_id', $inquiry->user_id)->where('name', 'isolated-api-response')->count() === 0,
    'Capture-created token must be deleted.');
echo json_encode(['status' => 'PASS_W03_TEST_ADAPTED_CAPTURE_CLEANUP',
    'db' => 'req81_travel_lab_test', 'fresh_process_verified' => true,
    'inquiry_id' => $inquiry->id, 'inquiry_status' => $inquiry->status,
    'departure_id' => $departure->id, 'reserved' => (int) $departure->reserved,
    'product_id' => $metadata->product_id, 'published' => (bool) $metadata->published,
    'created_token_count' => 0,
    'limitation' => 'TEST adaptation with manually mounted bundled routes; APP installed discovery and product browser boundaries NOT_RUN'], JSON_PRETTY_PRINT).PHP_EOL;
