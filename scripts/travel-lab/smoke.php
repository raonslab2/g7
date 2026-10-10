<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/environment.php';

try {
    $testing = in_array('--testing', $argv, true);
    $environment = travelLabEnvironment($testing);
    travelLabApplyEnvironment($environment);
    $root = dirname(__DIR__, 2);
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $expected = $testing ? 'req81_travel_lab_test' : 'req81_travel_lab';
    foreach (['database.connections.mysql.read.database', 'database.connections.mysql.write.database'] as $key) {
        if (config($key) !== $expected) {
            throw new RuntimeException('Effective database mismatch: '.$key);
        }
    }
    if (config('mail.default') !== 'array' || config('queue.default') !== 'sync' || config('filesystems.default') !== 'local') {
        throw new RuntimeException('Effective external mail/queue/storage settings violate lab isolation.');
    }
    $row = DB::selectOne('SELECT DATABASE() AS db');
    if ($row->db !== $expected) {
        throw new RuntimeException('Live connection is outside the expected lab DB.');
    }
    echo 'PASS: effective Laravel '.$app->environment().' / '.$expected.'; mail=array queue=sync storage=local.'.PHP_EOL;
    if (! $testing) {
        $count = DB::table('users')->count();
        echo 'Lab users: '.$count.PHP_EOL;
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: '.$error->getMessage().PHP_EOL);
    exit(1);
}
