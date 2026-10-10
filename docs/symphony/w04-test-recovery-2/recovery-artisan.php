<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;

require __DIR__.'/recovery-common.php';
require __DIR__.'/runtime-bootstrap.php';

try {
    if (array_slice($argv, 1) !== ['db:wipe', '--database=mysql', '--drop-views', '--force', '--no-interaction']) {
        throw new RuntimeException('Only the reviewed native TEST wipe is permitted.');
    }
    require_once dirname(__DIR__, 3).'/vendor/autoload.php';
    recoveryValidateOriginal();
    recoveryOrphans();
    w04App();
    DB::purge();
    $pdo = w04Pdo();
    $connection = recoveryConnection($pdo);
    $expected = json_decode(file_get_contents(recoveryPrivate().'/current-before.json'), true, flags: JSON_THROW_ON_ERROR);
    if (w04Measure($pdo) !== $expected) {
        throw new RuntimeException('TEST changed during native application bootstrap.');
    }
    recoveryConnection($pdo);
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
        if (is_link($cache) || (is_file($cache) && ! unlink($cache))) {
            throw new RuntimeException('Own config cache cleanup failed.');
        }
    }
    exit($status);
} catch (Throwable $error) {
    w04Save(recoveryPrivate().'/native-preflight-failure.json', ['class' => get_class($error),
        'source_file' => basename($error->getFile()), 'source_line' => $error->getLine(),
        'message_sha256' => hash('sha256', $error->getMessage()), 'automatic_retry' => false]);
    fwrite(STDERR, 'BLOCKED: native TEST wipe preflight '.get_class($error)."\n");
    exit(1);
}
