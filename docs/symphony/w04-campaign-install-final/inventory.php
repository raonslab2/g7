<?php

declare(strict_types=1);
umask(0077);
require_once dirname(__DIR__, 3).'/scripts/travel-lab/environment.php';
function w04Pdo(): PDO
{
    $env = w04Env();
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=req81_travel_lab_test;charset=utf8mb4', $env['DB_WRITE_USERNAME'], $env['DB_WRITE_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $id = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account')->fetch(PDO::FETCH_ASSOC);
    if ($id !== ['db' => 'req81_travel_lab_test', 'account' => 'req81_travel@127.0.0.1']) {
        throw new RuntimeException('Live TEST identity failed.');
    }

    return $pdo;
}
function w04Measure(PDO $pdo): array
{
    $tables = [];
    foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
        if ($type !== 'BASE TABLE') {
            throw new RuntimeException('Non-table object needs expanded recovery.');
        }
        $q = '`'.str_replace('`', '``', $name).'`';
        $rows = [];
        foreach ($pdo->query('SELECT * FROM '.$q)->fetchAll(PDO::FETCH_NUM) as $row) {
            $rows[] = json_encode(array_map(static fn ($v) => $v === null ? null : base64_encode((string) $v), $row), JSON_THROW_ON_ERROR);
        }
        sort($rows, SORT_STRING);
        $ddl = $pdo->query('SHOW CREATE TABLE '.$q)->fetch(PDO::FETCH_NUM)[1];
        $tables[$name] = ['rows' => count($rows), 'row_sha256' => hash('sha256', implode("\n", $rows)), 'ddl_sha256' => hash('sha256', $ddl)];
    }
    foreach (['TRIGGERS', 'ROUTINES', 'EVENTS'] as $object) {
        $column = $object === 'TRIGGERS' ? 'TRIGGER_SCHEMA' : ($object === 'ROUTINES' ? 'ROUTINE_SCHEMA' : 'EVENT_SCHEMA');
        if ((int) $pdo->query("SELECT COUNT(*) FROM information_schema.$object WHERE $column = DATABASE()")->fetchColumn() !== 0) {
            throw new RuntimeException('Additional schema objects require expanded recovery.');
        }
    }
    ksort($tables);

    return ['schema' => 'req81_travel_lab_test', 'table_count' => count($tables), 'row_count' => array_sum(array_column($tables, 'rows')), 'tables' => $tables, 'other_schema_objects' => 0];
}
function w04Exclusive(PDO $pdo): void
{
    $id = (int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn();
    foreach ($pdo->query("SELECT ID FROM information_schema.PROCESSLIST WHERE DB = 'req81_travel_lab_test'")->fetchAll(PDO::FETCH_ASSOC) as $p) {
        if ((int) $p['ID'] !== $id) {
            throw new RuntimeException('Other TEST connection observed.');
        }
    }
}
function w04Save(string $file, array $data): void
{
    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n") === false) {
        throw new RuntimeException('Evidence write failed.');
    }
}
