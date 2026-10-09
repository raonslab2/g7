<?php
require __DIR__.'/runtime-bootstrap.php';
try {
    $app = w04App();
    Illuminate\Support\Facades\DB::purge();
    w04Exclusive(w04Pdo());
    $env = w04Env(); $env['APP_ENV']='local';
    // Execute the unchanged native entry point, including installed vendor autoloads.
    exit(travelLabProcess([PHP_BINARY,'artisan',...array_slice($argv,1)],$env));
} catch (Throwable $e) {
    fwrite(STDERR,'BLOCKED native TEST command preflight: '.get_class($e)."\n");
    exit(1);
}
