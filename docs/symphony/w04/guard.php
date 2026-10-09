<?php

declare(strict_types=1);
umask(0077);
require_once dirname(__DIR__, 3).'/scripts/travel-lab/environment.php';
function w04Env(bool $cleanup = false): array
{
    $root = dirname(__DIR__, 3);
    foreach (['.env', '.env.testing'] as $file) {
        $path = $root.'/'.$file;
        if (is_link($path) || !is_file($path) || (fileperms($path) & 0777) !== 0600) {
            throw new RuntimeException('Private nonsymlink TEST environments required.');
        }
    }
    if (file_get_contents($root.'/.env') !== file_get_contents($root.'/.env.testing')) {
        throw new RuntimeException('TEST environment bytes differ.');
    }
    $env = travelLabEnvironment(true, $cleanup);
    foreach (['DB_URL', 'DB_SOCKET', 'MYSQL_ATTR_SSL_CA', 'REDIS_URL', 'SCOUT_MEILISEARCH_HOST', 'MEILISEARCH_HOST'] as $key) {
        if (!empty($env[$key])) { throw new RuntimeException('Forbidden connection override.'); }
    }
    foreach (['CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'SCOUT_DRIVER' => 'mysql-fulltext'] as $key => $value) {
        if (($env[$key] ?? '') !== $value) { throw new RuntimeException('Local adapters required: '.$key); }
    }
    // Native extension dependency resolution may consume bundled/cache files only.
    $env['COMPOSER_DISABLE_NETWORK']='1';
    return $env;
}
function w04Pdo(): PDO
{
    $env = w04Env();
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=req81_travel_lab_test;charset=utf8mb4', $env['DB_WRITE_USERNAME'], $env['DB_WRITE_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $id = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account')->fetch(PDO::FETCH_ASSOC);
    if ($id !== ['db' => 'req81_travel_lab_test', 'account' => 'req81_travel@127.0.0.1']) { throw new RuntimeException('Live TEST identity failed.'); }
    return $pdo;
}
function w04Measure(PDO $pdo): array
{
    $tables = [];
    foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
        if ($type !== 'BASE TABLE') { throw new RuntimeException('Non-table object needs expanded recovery.'); }
        $q = '`'.str_replace('`', '``', $name).'`';
        $rows = [];
        foreach ($pdo->query('SELECT * FROM '.$q)->fetchAll(PDO::FETCH_NUM) as $row) {
            $rows[] = json_encode(array_map(static fn ($v) => $v === null ? null : base64_encode((string)$v), $row), JSON_THROW_ON_ERROR);
        }
        sort($rows, SORT_STRING);
        $ddl = $pdo->query('SHOW CREATE TABLE '.$q)->fetch(PDO::FETCH_NUM)[1];
        $tables[$name] = ['rows' => count($rows), 'row_sha256' => hash('sha256', implode("\n", $rows)), 'ddl_sha256' => hash('sha256', $ddl)];
    }
    foreach (['TRIGGERS', 'ROUTINES', 'EVENTS'] as $object) {
        $column = $object === 'TRIGGERS' ? 'TRIGGER_SCHEMA' : ($object === 'ROUTINES' ? 'ROUTINE_SCHEMA' : 'EVENT_SCHEMA');
        if ((int)$pdo->query("SELECT COUNT(*) FROM information_schema.$object WHERE $column = DATABASE()")->fetchColumn() !== 0) { throw new RuntimeException('Additional schema objects require expanded recovery.'); }
    }
    ksort($tables);
    return ['schema' => 'req81_travel_lab_test', 'table_count' => count($tables), 'row_count' => array_sum(array_column($tables, 'rows')), 'tables' => $tables, 'other_schema_objects' => 0];
}
function w04Exclusive(PDO $pdo): void
{
    $id = (int)$pdo->query('SELECT CONNECTION_ID()')->fetchColumn();
    foreach ($pdo->query('SHOW PROCESSLIST')->fetchAll(PDO::FETCH_ASSOC) as $p) {
        if ($p['db'] === 'req81_travel_lab_test' && (int)$p['Id'] !== $id) { throw new RuntimeException('Other TEST connection observed.'); }
    }
}
function w04Save(string $file, array $data): void
{
    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n") === false) { throw new RuntimeException('Evidence write failed.'); }
}
function w04Child(array $command, string $log): int
{
    $env = w04Env();
    $start = microtime(true);
    $child = proc_open($command, [0=>['file','/dev/null','r'],1=>['file',$log,'w'],2=>['file',$log.'.stderr','w']], $pipes, dirname(__DIR__,3), $env);
    if (!is_resource($child)) { throw new RuntimeException('Child unavailable.'); }
    try { $status = proc_close($child); } finally {
        w04Env(true);
        $cache = dirname(__DIR__,3).'/bootstrap/cache/config.php';
        if (is_link($cache)) { throw new RuntimeException('Config cache symlink forbidden.'); }
        if (is_file($cache) && !unlink($cache)) { throw new RuntimeException('Own config cleanup failed.'); }
    }
    w04Save($log.'.result.json', ['command'=>$command, 'exit'=>$status, 'seconds'=>round(microtime(true)-$start,3)]);
    return $status;
}
/** Require a validated persistent recovery boundary before standalone destructive probes. */
function w04RequireSnapshot(string $label): void
{
    w04Env();
    if (!preg_match('/^[a-z0-9-]+$/',$label)) { throw new RuntimeException('Snapshot label required.'); }
    $private=dirname(__DIR__,3).'/storage/framework/testing/w04-'.$label;
    if (is_link($private) || !is_dir($private) || (fileperms($private)&0777)!==0700) { throw new RuntimeException('Private recovery snapshot required.'); }
    foreach (['BLOCKED','before.json','manifest.json','snapshot.sql'] as $name) {
        if (is_link($private.'/'.$name) || !is_file($private.'/'.$name) || (fileperms($private.'/'.$name)&0777)!==0600) { throw new RuntimeException('Validated recovery files required.'); }
    }
    $manifest=json_decode(file_get_contents($private.'/manifest.json'),true,flags:JSON_THROW_ON_ERROR);
    $before=json_decode(file_get_contents($private.'/before.json'),true,flags:JSON_THROW_ON_ERROR);
    if (($before['schema']??'') !== 'req81_travel_lab_test' || ($manifest['stable']??false)!==true || ($manifest['dump_exit']??1)!==0 || hash_file('sha256',$private.'/snapshot.sql')!==($manifest['dump_sha256']??'')) { throw new RuntimeException('Recovery snapshot validation failed.'); }
}
