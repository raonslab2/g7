<?php

declare(strict_types=1);

require __DIR__.'/recovery-common.php';
require __DIR__.'/runtime-bootstrap.php';

try {
    if (array_slice($argv, 1) !== ['db:wipe', '--database=mysql', '--drop-views', '--force', '--no-interaction']) {
        throw new RuntimeException('Only the reviewed native TEST wipe is permitted.');
    }
    recoveryValidateOriginal();
    recoveryOrphans();
    w04App();
    Illuminate\Support\Facades\DB::purge();
    $pdo = w04Pdo();
    $connection = recoveryConnection($pdo);
    $pdo = null;
    w04Save(recoveryPrivate().'/wipe-effective-config.json', $connection + [
        'effective_read_write_database' => 'req81_travel_lab_test',
        'effective_local_adapters' => true, 'native_entrypoint' => 'artisan',
    ]);
    recoveryOrphans();
    $environment = w04Env();
    $environment['APP_ENV'] = 'local';
    try {
        $status = travelLabProcess([PHP_BINARY, 'artisan', ...array_slice($argv, 1)], $environment);
    } finally {
        w04Env(true);
        $cache = dirname(__DIR__, 3).'/bootstrap/cache/config.php';
        if (is_link($cache) || (is_file($cache) && !unlink($cache))) {
            throw new RuntimeException('Own config cache cleanup failed.');
        }
    }
    exit($status);
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: native TEST wipe preflight '.get_class($error)."\n");
    exit(1);
}
