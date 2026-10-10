<?php

declare(strict_types=1);

// RefreshDatabase 기반 네이티브 회귀 전후 TEST 스키마만 보존한다.
umask(0077);
$w03Root = dirname(__DIR__, 3);
require __DIR__.'/test-bootstrap.php';
require $w03Root.'/scripts/travel-lab/live-bootstrap.php';
travelLabApp(true);
$w03Private = $w03Root.'/storage/framework/testing/w03-native-regression-snapshot';
$w03Mode = $argv[1] ?? '';
function w03AllTableDigests(): array
{
    $db = Illuminate\Support\Facades\DB::connection();
    $tables = $db->select('SHOW TABLES');
    $digests = [];
    foreach ($tables as $table) {
        $name = array_values((array) $table)[0];
        travelLabCheck((bool) preg_match('/^[a-zA-Z0-9_]+$/', $name), 'Unexpected TEST table identifier.');
        $rows = $db->select('SELECT * FROM `'.$name.'`');
        $rows = array_map(static fn ($row) => json_encode($row, JSON_THROW_ON_ERROR), $rows);
        sort($rows, SORT_STRING);
        $digests[$name] = ['count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }
    ksort($digests);

    return $digests;
}
function w03ScopedMysql(array $command, string $input, string $output): void
{
    $process = proc_open($command, [0 => ['file', $input, 'r'], 1 => ['file', $output, 'w'], 2 => ['pipe', 'w']], $pipes);
    travelLabCheck(is_resource($process), 'Scoped TEST tool failed to start.');
    stream_get_contents($pipes[2]); // 생 SQL/인증 진단 출력 금지.
    fclose($pipes[2]);
    travelLabCheck(proc_close($process) === 0, 'Scoped TEST tool failed; retain private snapshot.');
}
try {
    if ($w03Mode === 'save') {
        travelLabCheck(! file_exists($w03Private), 'Existing private snapshot must not be overwritten.');
        travelLabCheck(mkdir($w03Private, 0700), 'Private snapshot directory creation failed.');
        $env = travelLabEnvironment(true);
        travelLabCheck((bool) preg_match('/^[a-f0-9]{48}$/', $env['DB_WRITE_PASSWORD']), 'Scoped secret format differs.');
        file_put_contents($w03Private.'/mysql.cnf', "[client]\nhost=127.0.0.1\nport=3306\nuser=req81_travel\npassword=".$env['DB_WRITE_PASSWORD']."\n");
        $digests = w03AllTableDigests();
        file_put_contents($w03Private.'/digests.json', json_encode($digests, JSON_THROW_ON_ERROR));
        w03ScopedMysql(['mysqldump', '--defaults-extra-file='.$w03Private.'/mysql.cnf', '--single-transaction', '--no-tablespaces', '--skip-comments', 'req81_travel_lab_test'], '/dev/null', $w03Private.'/test.sql');
        travelLabCheck(is_file($w03Private.'/test.sql') && filesize($w03Private.'/test.sql') > 0, 'Snapshot is empty.');
        echo json_encode(['status' => 'PASS_PRIVATE_TEST_SNAPSHOT', 'tables' => count($digests),
            'rows' => array_sum(array_column($digests, 'count')), 'digest_sha256' => hash('sha256', json_encode($digests)),
            'private_dump_sha256' => hash_file('sha256', $w03Private.'/test.sql')], JSON_PRETTY_PRINT).PHP_EOL;
    } elseif ($w03Mode === 'restore') {
        travelLabCheck(is_dir($w03Private) && ! is_link($w03Private), 'Private snapshot missing.');
        $before = json_decode(file_get_contents($w03Private.'/digests.json'), true, flags: JSON_THROW_ON_ERROR);
        w03ScopedMysql(['mysql', '--defaults-extra-file='.$w03Private.'/mysql.cnf', '--database=req81_travel_lab_test'], $w03Private.'/test.sql', $w03Private.'/restore-output');
        Illuminate\Support\Facades\DB::purge();
        travelLabCheck(w03AllTableDigests() === $before, 'TEST restore digest differs; preserve private snapshot.');
        echo json_encode(['status' => 'PASS_W03_ALL_TABLE_RESTORE', 'tables' => count($before),
            'rows' => array_sum(array_column($before, 'count')), 'digest_sha256' => hash('sha256', json_encode($before)),
            'snapshot_deleted_after_digest_verification' => true], JSON_PRETTY_PRINT).PHP_EOL;
        foreach (glob($w03Private.'/*') as $file) {
            unlink($file);
        }
        rmdir($w03Private);
    } else {
        throw new RuntimeException('Use save or restore.');
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'BLOCKED: '.$error->getMessage().' Private snapshot retained at '.$w03Private.PHP_EOL);
    exit(1);
}
