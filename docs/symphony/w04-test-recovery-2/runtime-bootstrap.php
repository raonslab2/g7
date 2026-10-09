<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/guard.php';
require_once dirname(__DIR__, 3).'/scripts/travel-lab/live-bootstrap.php';
/** Native installation/runtime context on TEST schema; PHPUnit keeps APP_ENV=testing. */
function w04App(): Application
{
    $env = w04Env();
    $env['APP_ENV'] = 'local';
    travelLabApplyEnvironment($env);
    $root = dirname(__DIR__, 3);
    $cache = $root.'/bootstrap/cache/autoload-extensions.php';
    if (is_file($cache)) {
        if (is_link($cache)) {
            throw new RuntimeException('Own autoload cache symlink forbidden.');
        }
        $autoloads = require $cache;
        foreach ($autoloads['vendor_autoloads'] ?? [] as $relative) {
            $path = realpath($root.'/'.$relative);
            if ($path === false || ! str_starts_with($path, $root.'/modules/')) {
                throw new RuntimeException('Vendor autoload outside own installed modules.');
            }
            require_once $path;
        }
    }
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $c = config('database.connections.mysql');
    foreach (['read', 'write'] as $direction) {
        if ($c[$direction]['host'] !== ['127.0.0.1'] || (string) $c[$direction]['port'] !== '3306' || $c[$direction]['database'] !== 'req81_travel_lab_test' || $c[$direction]['username'] !== 'req81_travel') {
            throw new RuntimeException('Effective TEST host/account boundary failed.');
        }
    }
    if (empty($c['url']) !== true || empty($c['unix_socket']) !== true || empty($c['options']) !== true || config('mail.default') !== 'array' || config('queue.default') !== 'sync' || config('filesystems.default') !== 'local' || config('cache.default') !== 'array' || config('session.driver') !== 'array' || config('scout.driver') !== 'mysql-fulltext') {
        throw new RuntimeException('Effective TEST adapters failed.');
    }
    $id = DB::selectOne('SELECT DATABASE() AS db, CURRENT_USER() AS account');
    if ($id->db !== 'req81_travel_lab_test' || $id->account !== 'req81_travel@127.0.0.1') {
        throw new RuntimeException('Live TEST identity failed.');
    }

    return $app;
}
