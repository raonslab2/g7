<?php

declare(strict_types=1);

require_once __DIR__.'/guard.php';

const RECOVERY_SOURCE = '1052e3fb4bc4cccabb51b8c538116c78655f345b';
const RECOVERY_TREE = 'f18fa2056a031353c889785d768f87824e3083f5';
const RECOVERY_BASELINE_DIGEST = 'ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e';
const RECOVERY_FAILED = '/home/ubuntu/.agentopt-v2/workspaces/req_3e70e7ceddd34beea51a17da727584bc';
const RECOVERY_PARENT = '/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba';

function recoveryPrivate(): string
{
    return dirname(__DIR__, 3).'/storage/framework/testing/w04-test-recovery-2';
}

function recoveryMode(string $path, int $mode): void
{
    clearstatcache(true, $path);
    if (is_link($path) || ! file_exists($path) || (fileperms($path) & 0777) !== $mode) {
        throw new RuntimeException('Private file/directory boundary failed.');
    }
}

function recoveryOrphans(): array
{
    $process = proc_open(['sudo', '-n', 'python3', '-I', __DIR__.'/process-check.py'],
        [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('Readonly process scan unavailable.');
    }
    $output = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    $record = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
    if (is_dir(recoveryPrivate())) {
        w04Save(recoveryPrivate().'/last-process-check.json', $record);
    }
    if ($exit !== 0 || $record['count'] !== 0 || $record['lock_holders'] !== [] || $record['unreadable_pids'] !== []) {
        throw new RuntimeException('Failed Request live handle or unreadable process inventory.');
    }

    return $record;
}

function recoveryConnection(PDO $pdo): array
{
    $identity = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account, CONNECTION_ID() AS id')->fetch(PDO::FETCH_ASSOC);
    if ($identity['db'] !== 'req81_travel_lab_test' || $identity['account'] !== 'req81_travel@127.0.0.1') {
        throw new RuntimeException('Effective SQL identity failed.');
    }
    w04Exclusive($pdo);
    // PROCESS 권한 없는 TEST 계정의 가시성을 보완한다. 로컬 소켓은 이 읽기 전용
    // 단일 TEST 필터에만 사용하며 자격증명/권한/다른 스키마를 읽거나 변경하지 않는다.
    $probeStarted = microtime(true);
    $probe = proc_open(['sudo', '-n', 'mysql', '--no-defaults', '--protocol=socket', '-uroot', '--batch', '--skip-column-names'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($probe)) {
        throw new RuntimeException('Readonly TEST connection scan unavailable.');
    }
    fwrite($pipes[0], "SELECT ID FROM information_schema.PROCESSLIST WHERE DB = 'req81_travel_lab_test';");
    fclose($pipes[0]);
    $global = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($probe) !== 0) {
        throw new RuntimeException('Readonly TEST connection scan failed.');
    }
    $ids = array_values(array_filter(explode("\n", trim($global)), static fn ($v) => $v !== ''));
    foreach ($ids as $id) {
        if (! ctype_digit($id) || (int) $id !== (int) $identity['id']) {
            throw new RuntimeException('Other globally visible TEST connection observed.');
        }
    }
    if (count($ids) !== 1) {
        throw new RuntimeException('Global TEST process inventory incomplete.');
    }

    return ['database' => $identity['db'], 'account' => $identity['account'],
        'own_connection_id' => (int) $identity['id'], 'other_test_handles' => [], 'other_test_connections' => 0,
        'visibility' => 'scoped SHOW PROCESSLIST plus readonly local-socket global PROCESSLIST filtered to exact TEST',
        'readonly_socket_probe_seconds' => round(microtime(true) - $probeStarted, 3),
        'globally_observed_test_connection_ids' => array_map('intval', $ids)];
}

function recoveryDigest(array $inventory): string
{
    return hash('sha256', json_encode($inventory, JSON_THROW_ON_ERROR));
}

function recoveryValidateOriginal(): array
{
    $private = recoveryPrivate();
    recoveryMode($private, 0700);
    foreach (['original.sql', 'original-manifest.json', 'original-before.json'] as $name) {
        recoveryMode($private.'/'.$name, 0600);
    }
    $manifest = json_decode(file_get_contents($private.'/original-manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    $before = json_decode(file_get_contents($private.'/original-before.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($manifest['source_sha'] !== RECOVERY_SOURCE || $manifest['source_tree'] !== RECOVERY_TREE
        || $manifest['before_digest'] !== RECOVERY_BASELINE_DIGEST || $manifest['stable'] !== true || $manifest['dump_exit'] !== 0
        || hash_file('sha256', $private.'/original.sql') !== $manifest['dump_sha256']
        || $before['schema'] !== 'req81_travel_lab_test' || $before['table_count'] !== 55
        || $before['row_count'] !== 104 || $before['other_schema_objects'] !== 0
        || recoveryDigest($before) !== RECOVERY_BASELINE_DIGEST) {
        throw new RuntimeException('Original snapshot integrity failed.');
    }

    return [$manifest, $before];
}

function recoveryPhase(string $phase): void
{
    w04Save(recoveryPrivate().'/phase.json', ['phase' => $phase, 'utc' => gmdate('c'),
        'original_retained' => true, 'failure_child_untouched' => true]);
}
