<?php

declare(strict_types=1);

// TEST-only recovery evidence. Never loads Laravel or APP credentials.
umask(0077);
$root = dirname(__DIR__, 3);
require_once $root.'/scripts/travel-lab/environment.php';
$env = travelLabEnvironment(true);
$private = $root.'/storage/framework/w03-support-private';
if (! is_dir($private)) {
    mkdir($private, 0700, true);
}
$connect = static fn () => new PDO('mysql:host=127.0.0.1;port=3306;dbname=req81_travel_lab_test;charset=utf8mb4', $env['DB_WRITE_USERNAME'], $env['DB_WRITE_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo = $connect();
$quote = static fn (string $name): string => '`'.str_replace('`', '``', $name).'`';
$measure = static function () use (&$pdo, $quote): array {
    $tables = [];
    foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
        if ($type !== 'BASE TABLE') {
            throw new RuntimeException('Non-table object requires additional snapshot support.');
        }
        $rows = [];
        foreach ($pdo->query('SELECT * FROM '.$quote($name))->fetchAll(PDO::FETCH_NUM) as $row) {
            $rows[] = json_encode(array_map(static fn ($v) => $v === null ? null : base64_encode((string) $v), $row), JSON_THROW_ON_ERROR);
        }
        sort($rows, SORT_STRING);
        $ddl = $pdo->query('SHOW CREATE TABLE '.$quote($name))->fetch(PDO::FETCH_NUM)[1];
        $tables[$name] = ['rows' => count($rows), 'row_sha256' => hash('sha256', implode("\n", $rows)), 'ddl_sha256' => hash('sha256', $ddl)];
    }
    ksort($tables);

    return ['schema' => 'req81_travel_lab_test', 'table_count' => count($tables), 'row_count' => array_sum(array_column($tables, 'rows')), 'tables' => $tables];
};
$mode = $argv[1] ?? 'measure';
$label = getenv('W03_RECOVERY_LABEL') ?: 'original';
if (! preg_match('/^[a-z0-9-]+$/', $label)) { throw new RuntimeException('Invalid evidence label.'); }
$evidence = __DIR__.'/evidence/'.$label;
if (! is_dir($evidence)) { mkdir($evidence, 0700, true); }
$save = static function (string $path, array $data): void {
    if (file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n") === false) {
        throw new RuntimeException('Evidence write failed.');
    }
};
$facts = [];
foreach ($pdo->query('SHOW PROCESSLIST')->fetchAll(PDO::FETCH_ASSOC) as $process) {
    if ($process['db'] === 'req81_travel_lab_test' && (int) $process['Id'] !== (int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn()) {
        $facts[] = ['id' => $process['Id'], 'command' => $process['Command'], 'seconds' => $process['Time']];
    }
}
$save($evidence.'/connections-'.$mode.'.json', ['other_test_connections' => $facts]);
if ($facts !== []) {
    throw new RuntimeException('Other TEST connections: exclusivity not established.');
}
if ($mode === 'measure') {
    $current = $measure();
    $save($evidence.'/current-before.json', $current);
    echo json_encode(['table_count' => $current['table_count'], 'row_count' => $current['row_count'], 'other_test_connections' => count($facts)])."\n";
    exit;
}
$lock = fopen($private.'/exclusive.lock', 'c');
if (! flock($lock, LOCK_EX | LOCK_NB)) {
    throw new RuntimeException('Own TEST runner already active.');
}
$defaults = $private.'/mysql.cnf';
$escape = static fn (string $v): string => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $v).'"';
file_put_contents($defaults, "[client]\nhost=127.0.0.1\nport=3306\nuser=".$escape($env['DB_WRITE_USERNAME'])."\npassword=".$escape($env['DB_WRITE_PASSWORD'])."\n");
$sql = $private.'/snapshot.sql';
$process = static function (array $command, array $descriptors, ?array $environment = null) use ($root): int {
    $child = proc_open($command, $descriptors, $pipes, $root, $environment);
    if (! is_resource($child)) {
        throw new RuntimeException('Child process creation failed.');
    }

    return proc_close($child);
};
$restoreSnapshot = static function (array $before) use (&$pdo, $connect, $process, $sql, $defaults, $private, $measure, $save, $evidence, $quote): array {
        $pdo = $connect();
        foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
            if ($type !== 'BASE TABLE') {
                throw new RuntimeException('Unexpected object: preserve BLOCKED snapshot.');
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $name) {
            $pdo->exec('DROP TABLE '.$quote($name));
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $restore = $process(['mysql', '--defaults-extra-file='.$defaults, 'req81_travel_lab_test'], [0 => ['file', $sql, 'r'], 1 => ['file', $private.'/restore.stdout', 'w'], 2 => ['file', $private.'/restore.stderr', 'w']]);
        $restored = $measure();
        $save($evidence.'/restored.json', $restored);
        $exact = $before === $restored;
        $save($evidence.'/restore-result.json', ['exit' => $restore, 'exact_tables_rows_ddl' => $exact]);
        if ($restore !== 0 || ! $exact) {
            throw new RuntimeException('Restore failed: private snapshot and BLOCKED retained.');
        }
        unlink($private.'/BLOCKED');
        unlink($defaults);
        return $restored;
};
if ($mode === 'restore') {
    if (! is_file($private.'/BLOCKED') || ! is_file($sql)) { throw new RuntimeException('No pending private snapshot.'); }
    $valid = json_decode(file_get_contents($evidence.'/snapshot-valid.json'), true, flags: JSON_THROW_ON_ERROR);
    if (hash_file('sha256', $sql) !== $valid['sha256']) { throw new RuntimeException('Snapshot digest mismatch.'); }
    $restoreSnapshot(json_decode(file_get_contents($evidence.'/snapshot-before.json'), true, flags: JSON_THROW_ON_ERROR));
    echo "Exact pending snapshot restored.\n";
    exit;
}
if ($mode === 'run') {
    if (is_file($private.'/BLOCKED')) {
        throw new RuntimeException('Previous restore blocked; preserve snapshot.');
    }
    $before = $measure();
    $save($evidence.'/snapshot-before.json', $before);
    $status = $process(['mysqldump', '--defaults-extra-file='.$defaults, '--single-transaction', '--hex-blob', '--skip-lock-tables', '--no-tablespaces', 'req81_travel_lab_test'], [0 => ['file', '/dev/null', 'r'], 1 => ['file', $sql, 'w'], 2 => ['file', $private.'/dump.stderr', 'w']]);
    $afterDump = $measure();
    if ($status !== 0 || $before !== $afterDump || ! str_contains(file_get_contents($sql), '-- Dump completed on')) {
        throw new RuntimeException('Snapshot validation failed; no tests permitted.');
    }
    $save($evidence.'/snapshot-valid.json', ['dump_exit' => $status, 'sha256' => hash_file('sha256', $sql), 'stable_digests' => true, 'mode' => decoct(fileperms($sql) & 0777)]);
    file_put_contents($private.'/BLOCKED', 'Snapshot valid; tests/restoration pending.');
    // Each bounded batch stays in one native process; always restore even after test failure.
    try {
        $command = array_slice($argv, 2);
        if ($command === []) {
            throw new RuntimeException('Missing bounded native test command.');
        }
        $pdo = null; // Native TestCase cleans stale TEST connections; keep no recovery session parked.
        $status = $process($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $evidence.'/native.txt', 'w'], 2 => ['file', $evidence.'/native.stderr', 'w']], $env);
        $save($evidence.'/native-exit.json', ['command' => $command, 'exit' => $status]);
    } finally {
        $restored = $restoreSnapshot($before);
    }
    echo json_encode(['test_exit' => $status, 'restored_exactly' => true, 'table_count' => $restored['table_count'], 'row_count' => $restored['row_count']])."\n";
    exit($status);
}
throw new RuntimeException('Unsupported recovery mode.');
