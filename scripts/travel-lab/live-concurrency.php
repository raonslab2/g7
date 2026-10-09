<?php

declare(strict_types=1);
use App\Models\User;
use Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface;
use Modules\Raonslab\TravelLab\Services\InquiryService;
use Modules\Raonslab\TravelLab\Services\TravelCartService;

require __DIR__.'/live-bootstrap.php';
require __DIR__.'/live-fixtures.php';

/** Independent PHP processes + independent real MySQL connections, synchronized before work. */
function travelLabRace(array $jobs, bool $stockFirst = false): array
{
    $root = dirname(__DIR__, 2);
    $directory = $root.'/storage/framework/testing/travel-live-'.bin2hex(random_bytes(8));
    mkdir($directory, 0700, true);
    $processes = [];
    try {
        foreach ($jobs as $index => $job) {
            $job += ['directory' => $directory, 'index' => $index];
            $processes[$index] = proc_open([PHP_BINARY, __DIR__.'/live-worker.php'],
                [0 => ['pipe', 'r'], 1 => ['file', $directory.'/result-'.$index, 'w'], 2 => ['file', $directory.'/error-'.$index, 'w']], $pipes,
                $root, travelLabEnvironment());
            travelLabCheck(is_resource($processes[$index]), 'Could not create concurrency worker.');
            fwrite($pipes[0], json_encode($job, JSON_THROW_ON_ERROR));
            fclose($pipes[0]);
        }
        $deadline = microtime(true) + 20;
        foreach (array_keys($jobs) as $index) {
            while (! is_file($directory.'/ready-'.$index)) {
                travelLabCheck(microtime(true) < $deadline, 'Worker readiness timed out.');
                usleep(10000);
            }
        }
        file_put_contents($directory.'/go-0', 'go');
        if ($stockFirst) {
            while (! is_file($directory.'/stock-locked')) {
                travelLabCheck(microtime(true) < $deadline, 'Stock-edit lock was not acquired.');
                usleep(10000);
            }
        }
        file_put_contents($directory.'/go-1', 'go');
        $results = [];
        foreach ($processes as $index => $process) {
            $exit = proc_close($process);
            $processes[$index] = null;
            travelLabCheck($exit === 0, 'Worker failed; inspect only sanitized result metadata.');
            $results[] = json_decode(file_get_contents($directory.'/result-'.$index), true, flags: JSON_THROW_ON_ERROR);
        }
        travelLabCheck(count(array_unique(array_column($results, 'connection_id'))) === 2, 'Race did not use two independent MySQL connections.');

        return $results;
    } finally {
        foreach ($processes as $process) {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
        }
        foreach (glob($directory.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
}

$fixtureProductIds = [];
$exitCode = 0;
try {
    travelLabApp();
    $run = bin2hex(random_bytes(6));
    $users = [travelLabUser($run, 'a'), travelLabUser($run, 'b')];
    $admin = User::query()->where('email', 'admin@travel-lab.example.invalid')->firstOrFail();
    $cartService = app(TravelCartService::class);
    $inquiries = app(InquiryService::class);
    $report = ['source_sha' => trim(shell_exec('git rev-parse HEAD')), 'source_state' => 'WORKING_TREE_IMPLEMENTER_CHECK', 'db' => 'req81_travel_lab', 'run' => $run, 'checks' => []];
    $departure = travelLabDeparture($run.'-last', 1);
    $fixtureProductIds[] = $departure->product_id;
    $jobs = [];
    foreach ($users as $index => $user) {
        $cart = $cartService->add($user->id, $departure->id, 1);
        $jobs[] = ['action' => 'submit', 'user_id' => $user->id, 'cart_ids' => [$cart['items'][0]['id']], 'key' => $run.'-last-'.$index];
    }
    $results = travelLabRace($jobs);
    $statuses = array_column($results, 'status');
    sort($statuses);
    travelLabCheck($statuses === [200, 409] && $departure->fresh()->reserved === 1, 'Last-seat competition oversold or did not reject exactly one loser.');
    $report['checks']['last_seat'] = $results;

    $departure = travelLabDeparture($run.'-same', 2);
    $fixtureProductIds[] = $departure->product_id;
    $cart = $cartService->add($users[0]->id, $departure->id, 1);
    $cartId = collect($cart['items'])->firstWhere('departure_id', $departure->id)['id'];
    $job = ['action' => 'submit', 'user_id' => $users[0]->id, 'cart_ids' => [$cartId], 'key' => $run.'-same'];
    $results = travelLabRace([$job, $job]);
    travelLabCheck(array_column($results, 'status') === [200, 200]
        && count(array_unique(array_column($results, 'inquiry_id'))) === 1 && $departure->fresh()->reserved === 1,
        'Same-key race created duplicate inquiry/reservation.');
    $inquiryId = $results[0]['inquiry_id'];
    $report['checks']['same_key'] = $results;

    $results = travelLabRace([
        ['action' => 'cancel', 'user_id' => $users[0]->id, 'inquiry_id' => $inquiryId],
        ['action' => 'decline', 'user_id' => $admin->id, 'inquiry_id' => $inquiryId],
    ]);
    $statuses = array_column($results, 'status');
    sort($statuses);
    travelLabCheck($statuses === [200, 409] && $departure->fresh()->reserved === 0, 'Cancel/decline race released zero or more than one allocation.');
    $report['checks']['cancel_decline'] = $results;

    $departure = travelLabDeparture($run.'-stock', 2);
    $fixtureProductIds[] = $departure->product_id;
    $cart = $cartService->add($users[1]->id, $departure->id, 1);
    $cartId = collect($cart['items'])->firstWhere('departure_id', $departure->id)['id'];
    $results = travelLabRace([
        ['action' => 'stock', 'product_id' => $departure->product_id, 'option_id' => $departure->product_option_id],
        ['action' => 'submit', 'user_id' => $users[1]->id, 'cart_ids' => [$cartId], 'key' => $run.'-stock'],
    ], true);
    travelLabCheck($results[0]['status'] === 200 && $results[1]['status'] === 409 && $departure->fresh()->reserved === 0,
        'Submission did not recheck the locked current commerce stock.');
    $report['checks']['stock_edit'] = $results;
    $report['status'] = 'PASS_IMPLEMENTER_MYSQL_CONCURRENCY';
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: '.$error->getMessage().PHP_EOL);
    $exitCode = 1;
} finally {
    // Keep test history for inspection while the customer catalog retains the authored seed set.
    foreach ($fixtureProductIds as $productId) {
        app(CatalogRepositoryInterface::class)->updateMetadata($productId, ['published' => false]);
    }
}
exit($exitCode);
