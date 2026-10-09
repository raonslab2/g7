<?php

declare(strict_types=1);

require_once __DIR__.'/guard.php';

const RECOVERY_SOURCE = '598a89fff702d51c1405f1a5952d95ab1d2651f4';
const RECOVERY_TREE = '9e00273bdf18d6a713343755aac54f44a9b032b4';
const RECOVERY_BASELINE_DIGEST = 'ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e';
const RECOVERY_FAILED = '/home/ubuntu/.agentopt-v2/workspaces/req_337b3638df9b4b958b0627e0b262a54a';
const RECOVERY_PARENT = '/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba';

function recoveryPrivate(): string
{
    return dirname(__DIR__, 3).'/storage/framework/testing/w04-baseline-recovery';
}

function recoveryMode(string $path, int $mode): void
{
    clearstatcache(true, $path);
    if (is_link($path) || !file_exists($path) || (fileperms($path) & 0777) !== $mode) {
        throw new RuntimeException('Private file/directory boundary failed.');
    }
}

function recoveryOrphans(): array
{
    $handles = [];
    foreach (glob('/proc/[0-9]*') as $path) {
        $cwd = @readlink($path.'/cwd');
        $raw = @file_get_contents($path.'/cmdline');
        if ($cwd === false || $raw === false) {
            if (is_dir($path) && is_file($path.'/cmdline') && $raw === false) {
                throw new RuntimeException('Process inventory unreadable.');
            }
            continue;
        }
        $args = array_filter(explode("\0", $raw), static fn ($v) => $v !== '');
        $argMatch = false;
        foreach ($args as $arg) {
            // 개별 argv 경로/알려진 경로 옵션만 검사하며 셸 본문은 검색하지 않는다.
            foreach (['--working-dir=', '--directory=', '--chdir='] as $prefix) {
                if (str_starts_with($arg, $prefix)) {
                    $arg = substr($arg, strlen($prefix));
                    break;
                }
            }
            $argMatch = $argMatch || $arg === RECOVERY_FAILED || str_starts_with($arg, RECOVERY_FAILED.'/');
        }
        if ($cwd === RECOVERY_FAILED || str_starts_with($cwd, RECOVERY_FAILED.'/') || $argMatch) {
            $handles[] = ['pid' => (int) basename($path), 'exact_cwd' => $cwd === RECOVERY_FAILED,
                'descendant_cwd' => str_starts_with($cwd, RECOVERY_FAILED.'/'), 'individual_path_argv' => $argMatch];
        }
    }
    if ($handles !== []) {
        throw new RuntimeException('Failed Request process still live.');
    }
    return ['method' => 'proc_exact_cwd_or_individual_path_argv_prefix', 'handles' => [], 'count' => 0];
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
    $global = travelLabAdminSql("SELECT ID FROM information_schema.PROCESSLIST WHERE DB = 'req81_travel_lab_test'");
    $ids = array_values(array_filter(explode("\n", trim($global)), static fn ($v) => $v !== ''));
    foreach ($ids as $id) {
        if (!ctype_digit($id) || (int) $id !== (int) $identity['id']) {
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
        || $manifest['stable'] !== true || $manifest['dump_exit'] !== 0
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
