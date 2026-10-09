<?php

declare(strict_types=1);
require __DIR__.'/environment.php';

try {
    $mode = $argv[1] ?? '';
    $arguments = array_slice($argv, 2);
    if ($mode === 'artisan') {
        if ($arguments === []) {
            throw new RuntimeException('Supply an Artisan command.');
        }
        exit(travelLabLifecycleProcess([PHP_BINARY, 'artisan', ...$arguments], travelLabEnvironment()));
    }
    if ($mode === 'test') {
        $installationSuite = in_array('--testsuite=Installation', $arguments, true);
        foreach ($arguments as $index => $argument) {
            if ($argument === '--testsuite' && ($arguments[$index + 1] ?? null) === 'Installation') {
                $installationSuite = true;
            }
        }
        $focused = in_array('--filter', $arguments, true) || array_filter($arguments, fn ($value) => str_starts_with($value, '--filter=') || str_ends_with($value, 'Test.php') || rtrim($value, '/') === 'modules/_bundled/raonslab-travel_lab/tests');
        if ($arguments === [] || (! $installationSuite && ! $focused)) {
            throw new RuntimeException('Supply a focused test file/filter, Travel Lab tests, or explicit Installation suite.');
        }
        exit(travelLabProcess([PHP_BINARY, 'vendor/bin/phpunit', ...$arguments], travelLabEnvironment(true)));
    }
    if ($mode === 'preview') {
        exit(travelLabProcess([PHP_BINARY, 'artisan', 'serve', '--host=127.0.0.1', '--port=18871', '--no-reload'], travelLabEnvironment()));
    }
    throw new RuntimeException('Usage: run.php artisan <command> | test <focused arguments> | preview');
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: '.$error->getMessage().PHP_EOL);
    exit(1);
}
