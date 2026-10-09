<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/environment.php';

/** Boot the actual root application against exactly one marked request-local schema. */
function travelLabApp(bool $testing = false): Application
{
    travelLabApplyEnvironment(travelLabEnvironment($testing));
    $root = dirname(__DIR__, 2);
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $expected = $testing ? 'req81_travel_lab_test' : 'req81_travel_lab';
    foreach (['read', 'write'] as $direction) {
        if (config('database.connections.mysql.'.$direction.'.database') !== $expected
            || config('database.connections.mysql.'.$direction.'.username') !== 'req81_travel') {
            throw new RuntimeException('Effective application DB is outside the marked lab scope.');
        }
    }
    if (config('mail.default') !== 'array' || config('queue.default') !== 'sync'
        || config('filesystems.default') !== 'local') {
        throw new RuntimeException('Effective application egress settings violate lab isolation.');
    }
    $identity = DB::selectOne('SELECT DATABASE() AS db, CURRENT_USER() AS account');
    if ($identity->db !== $expected || $identity->account !== 'req81_travel@127.0.0.1') {
        throw new RuntimeException('Live connection escaped the marked schema/account.');
    }

    return $app;
}

function travelLabCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
