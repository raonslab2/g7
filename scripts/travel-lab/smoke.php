<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/environment.php';

try {
    $testing = in_array('--testing', $argv, true);
    $environment = travelLabEnvironment($testing);
    // Unlike subprocess commands, this smoke bootstraps Laravel in this process.
    // Remove rejected inherited keys from every adapter before loading Dotenv.
    $inheritedKeys = array_unique(array_merge(array_keys(getenv()), array_keys($_ENV), array_keys($_SERVER)));
    foreach ($inheritedKeys as $key) {
        if (! array_key_exists($key, $environment) && (
            str_starts_with($key, 'DB_') || $key === 'MYSQL_ATTR_SSL_CA'
            || str_starts_with($key, 'INSTALLER_ADMIN_')
            || (str_starts_with($key, 'APP_') && str_ends_with($key, '_CACHE'))
        )) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }
    foreach ($environment as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
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
