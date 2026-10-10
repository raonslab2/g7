<?php

declare(strict_types=1);

// Standalone negative isolation checks: temporary checkout under this worktree;
// never changes the running lab's .env/.env.testing or connects to any DB.
$root = dirname(__DIR__, 2);
$fixture = $root.'/storage/framework/testing/travel-harness-'.bin2hex(random_bytes(8));
mkdir($fixture.'/scripts/travel-lab', 0770, true);
mkdir($fixture.'/bootstrap/cache', 0770, true);
mkdir($fixture.'/storage/installer', 0770, true);
copy(__DIR__.'/environment.php', $fixture.'/scripts/travel-lab/environment.php');
symlink($root.'/vendor', $fixture.'/vendor');
$valid = file_get_contents($root.'/.env.travel-lab.example');
$probe = <<<'PHP'
<?php
require __DIR__.'/environment.php';
try {
    if (($argv[1] ?? '') === 'clear') { travelLabClearGeneratedConfig(); exit(0); }
    $env = travelLabEnvironment(($argv[1] ?? '') === 'test');
    if (($argv[1] ?? '') === 'test' && ($env['CACHE_STORE'] ?? null) !== 'array') { exit(3); }
    if (isset($env['DB_URL'])) { exit(2); }
    exit(0);
} catch (Throwable $error) {
    exit(1);
}
PHP;
file_put_contents($fixture.'/scripts/travel-lab/probe.php', $probe);

try {
    $cases = [
        'valid scoped environment' => [$valid, 0],
        'unmarked environment' => [str_replace('TRAVEL_LAB_ISOLATED=1', 'TRAVEL_LAB_ISOLATED=0', $valid), 1],
        'foreign write DB' => [str_replace('DB_WRITE_DATABASE=req81_travel_lab', 'DB_WRITE_DATABASE=unrelated', $valid), 1],
        'foreign read DB' => [str_replace('DB_READ_DATABASE=req81_travel_lab', 'DB_READ_DATABASE=unrelated', $valid), 1],
        'foreign fallback DB' => [str_replace('DB_DATABASE=req81_travel_lab', 'DB_DATABASE=unrelated', $valid), 1],
        'external host' => [str_replace('DB_WRITE_HOST=127.0.0.1', 'DB_WRITE_HOST=db.example.invalid', $valid), 1],
        'privileged user' => [str_replace('DB_WRITE_USERNAME=req81_travel', 'DB_WRITE_USERNAME=root', $valid), 1],
        'external port' => [str_replace('DB_READ_PORT=3306', 'DB_READ_PORT=3307', $valid), 1],
        'database URL override' => [$valid."\nDB_URL=mysql://foreign\n", 1],
        'database socket override' => [$valid."\nDB_SOCKET=/tmp/unrelated.sock\n", 1],
        'outbound mail' => [str_replace('MAIL_MAILER=array', 'MAIL_MAILER=smtp', $valid), 1],
        'background queue' => [str_replace('QUEUE_CONNECTION=sync', 'QUEUE_CONNECTION=database', $valid), 1],
        'external storage' => [str_replace('FILESYSTEM_DISK=local', 'FILESYSTEM_DISK=s3', $valid), 1],
        'nonatomic file cache' => [str_replace('CACHE_STORE=database', 'CACHE_STORE=file', $valid), 1],
        'foreign cache connection' => [str_replace('DB_CACHE_CONNECTION=mysql', 'DB_CACHE_CONNECTION=unrelated', $valid), 1],
        'foreign lock connection' => [str_replace('DB_CACHE_LOCK_CONNECTION=mysql', 'DB_CACHE_LOCK_CONNECTION=unrelated', $valid), 1],
        'foreign cache table' => [str_replace('DB_CACHE_TABLE=cache', 'DB_CACHE_TABLE=unrelated', $valid), 1],
        'foreign lock table' => [str_replace('DB_CACHE_LOCK_TABLE=cache_locks', 'DB_CACHE_LOCK_TABLE=unrelated', $valid), 1],
        'separate limiter override' => [$valid."\nCACHE_LIMITER=file\n", 1],
        'settings override protection absent' => [str_replace('G7_ENV_PRIORITY=true', 'G7_ENV_PRIORITY=false', $valid), 1],
    ];
    foreach ($cases as $label => [$contents, $expected]) {
        file_put_contents($fixture.'/.env', $contents);
        $env = getenv();
        $env['DB_URL'] = 'mysql://inherited-unrelated';
        $process = proc_open([PHP_BINARY, $fixture.'/scripts/travel-lab/probe.php'], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $fixture, $env);
        if (proc_close($process) !== $expected) {
            throw new RuntimeException('Guard failed: '.$label);
        }
        echo 'PASS: '.$label.PHP_EOL;
    }
    foreach (['bootstrap/cache/config.php', 'storage/installer/runtime.php'] as $override) {
        file_put_contents($fixture.'/.env', $valid);
        file_put_contents($fixture.'/'.$override, '<?php return [];');
        $process = proc_open([PHP_BINARY, $fixture.'/scripts/travel-lab/probe.php'], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $fixture);
        if (proc_close($process) !== 1) {
            throw new RuntimeException('Guard failed: '.$override);
        }
        unlink($fixture.'/'.$override);
        echo 'PASS: '.$override.' refusal'.PHP_EOL;
    }
    foreach (['marked generated cache removal' => [$valid, 0],
        'unmarked cache preserved' => [str_replace('TRAVEL_LAB_ISOLATED=1', 'TRAVEL_LAB_ISOLATED=0', $valid), 1],
        'foreign DB cache preserved' => [str_replace('DB_WRITE_DATABASE=req81_travel_lab', 'DB_WRITE_DATABASE=unrelated', $valid), 1]] as $label => [$contents, $expected]) {
        file_put_contents($fixture.'/.env', $contents);
        file_put_contents($fixture.'/bootstrap/cache/config.php', '<?php return [];');
        $process = proc_open([PHP_BINARY, $fixture.'/scripts/travel-lab/probe.php', 'clear'], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $fixture);
        if (proc_close($process) !== $expected || is_file($fixture.'/bootstrap/cache/config.php') !== ($expected !== 0)) {
            throw new RuntimeException('Cache recovery guard failed: '.$label);
        }
        if (is_file($fixture.'/bootstrap/cache/config.php')) {
            unlink($fixture.'/bootstrap/cache/config.php');
        }
        echo 'PASS: '.$label.PHP_EOL;
    }
    file_put_contents($fixture.'/.env', $valid);
    file_put_contents($fixture.'/cache-target.php', '<?php return [];');
    symlink($fixture.'/cache-target.php', $fixture.'/bootstrap/cache/config.php');
    $process = proc_open([PHP_BINARY, $fixture.'/scripts/travel-lab/probe.php', 'clear'], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $fixture);
    if (proc_close($process) !== 1 || ! is_link($fixture.'/bootstrap/cache/config.php') || ! is_file($fixture.'/cache-target.php')) {
        throw new RuntimeException('Cache recovery guard failed: symlink preserved');
    }
    unlink($fixture.'/bootstrap/cache/config.php');
    echo 'PASS: symlink cache preserved'.PHP_EOL;
    file_put_contents($fixture.'/.env.testing', str_replace('req81_travel_lab', 'req81_travel_lab_test', $valid));
    $process = proc_open([PHP_BINARY, $fixture.'/scripts/travel-lab/probe.php', 'test'], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $fixture);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('Ordinary TEST must use isolated array cache even with a surviving database-cache env.');
    }
    echo 'PASS: surviving TEST cache isolated to array'.PHP_EOL;
    echo 'PASS: '.(count($cases) + 7).' isolation checks; no live environment mutation or database access.'.PHP_EOL;
} finally {
    unlink($fixture.'/vendor');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($fixture);
}
