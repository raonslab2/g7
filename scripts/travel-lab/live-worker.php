<?php

declare(strict_types=1);
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;
use Modules\Sirsoft\Ecommerce\Services\ProductOptionService;

require __DIR__.'/live-bootstrap.php';

try {
    $payload = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    $directory = realpath($payload['directory'] ?? '');
    $allowed = realpath(dirname(__DIR__, 2).'/storage/framework/testing');
    travelLabCheck($directory !== false && $allowed !== false && str_starts_with($directory, $allowed.'/travel-live-'),
        'Worker barrier must remain in this request checkout.');
    travelLabApp();
    $connectionId = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
    $index = (int) $payload['index'];
    file_put_contents($directory.'/ready-'.$index, 'ready');
    $deadline = microtime(true) + 20;
    while (! is_file($directory.'/go-'.$index)) {
        travelLabCheck(microtime(true) < $deadline, 'Worker start barrier timed out.');
        usleep(10000);
    }
    $started = microtime(true);
    $service = app(InquiryService::class);
    $action = $payload['action'];
    if ($action === 'submit') {
        $inquiry = $service->submit((int) $payload['user_id'], $payload['cart_ids'], ['name' => 'Synthetic concurrent tester'], $payload['key']);
        $result = ['status' => 200, 'inquiry_id' => $inquiry->id, 'inquiry_status' => $inquiry->status->value];
    } elseif ($action === 'cancel') {
        $inquiry = $service->cancel((int) $payload['user_id'], (int) $payload['inquiry_id']);
        $result = ['status' => 200, 'inquiry_id' => $inquiry->id, 'inquiry_status' => $inquiry->status->value];
    } elseif ($action === 'decline') {
        $inquiry = $service->transition((int) $payload['user_id'], (int) $payload['inquiry_id'], InquiryStatus::DECLINED, 'Synthetic concurrency check');
        $result = ['status' => 200, 'inquiry_id' => $inquiry->id, 'inquiry_status' => $inquiry->status->value];
    } elseif ($action === 'stock') {
        DB::transaction(function () use ($payload, $directory) {
            ProductOption::query()->whereKey($payload['option_id'])->lockForUpdate()->firstOrFail();
            app(ProductOptionService::class)->bulkUpdateStockByMixedIds([], [$payload['product_id'].'-'.$payload['option_id']], 'set', 0);
            file_put_contents($directory.'/stock-locked', 'locked');
            usleep(250000);
        });
        $result = ['status' => 200, 'stock' => 0];
    } else {
        throw new RuntimeException('Unsupported live worker action.');
    }
    $result += ['connection_id' => $connectionId, 'started' => $started, 'finished' => microtime(true)];
} catch (HttpResponseException $error) {
    $result = ['status' => $error->getResponse()->getStatusCode(), 'connection_id' => $connectionId ?? null,
        'started' => $started ?? null, 'finished' => microtime(true), 'rejected' => true];
} catch (Throwable $error) {
    // Never expose connection strings, generated credentials or raw SQL failures.
    $result = ['status' => 500, 'error_class' => get_class($error)];
}
echo json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL;
exit(($result['status'] ?? 500) === 500 ? 1 : 0);
