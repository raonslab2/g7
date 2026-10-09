<?php

namespace Modules\Raonslab\TravelLab\Tests;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Schema;

/** Exercise native admission against the test's existing isolated DB, without bypassing middleware. */
trait UsesDatabaseThrottleCache
{
    protected function useDatabaseThrottleCache(): void
    {
        foreach (['cache' => '2026_04_01_000003_create_cache_table.php',
            'cache_locks' => '2026_04_01_000004_create_cache_locks_table.php'] as $table => $migration) {
            if (! Schema::hasTable($table)) {
                (require base_path('database/migrations/'.$migration))->up();
            }
        }
        config([
            'cache.default' => 'database', 'cache.limiter' => 'database',
            'cache.stores.database.connection' => config('database.default'),
            'cache.stores.database.table' => 'cache',
            'cache.stores.database.lock_connection' => config('database.default'),
            'cache.stores.database.lock_table' => 'cache_locks',
        ]);
        app('cache')->forgetDriver('database');
        app()->forgetInstance('cache.store');
        app()->forgetInstance(RateLimiter::class);
    }
}
