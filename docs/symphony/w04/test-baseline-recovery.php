<?php

declare(strict_types=1);

require __DIR__.'/recovery-common.php';

$root = dirname(__DIR__, 3);
$private = recoveryPrivate();
$evidence = __DIR__.'/evidence/test-baseline-recovery';
$mode = $argv[1] ?? '';
$phase = 'preflight';
$started = microtime(true);

/** 민감한 프로세스 출력은 전용 복구 디렉터리에만 보관한다. */
function recoveryCommand(array $command, string $label, ?string $input = null, ?string $output = null): array
{
    $private = recoveryPrivate();
    $start = microtime(true);
    $process = proc_open($command, [
        0 => ['file', $input ?? '/dev/null', 'r'],
        1 => ['file', $output ?? $private.'/'.$label.'.stdout', 'w'],
        2 => ['file', $private.'/'.$label.'.stderr', 'w'],
    ], $pipes, dirname(__DIR__, 3), w04Env());
    if (!is_resource($process)) {
        throw new RuntimeException('Native child unavailable.');
    }
    $handle = proc_get_status($process);
    $exit = proc_close($process);
    if ($exit === -1 && !$handle['running'] && $handle['exitcode'] >= 0) {
        $exit = $handle['exitcode'];
    }
    $safe = array_map(static fn ($arg) => str_starts_with($arg, '--defaults-file=')
        ? '--defaults-file=<private TEST-only options>' : $arg, $command);
    $record = ['command' => $safe, 'child_pid' => $handle['pid'], 'exit' => $exit,
        'seconds' => round(microtime(true) - $start, 3)];
    w04Save($private.'/'.$label.'.result.json', $record);
    if ($exit !== 0) {
        throw new RuntimeException('Native child failed; private output retained.');
    }
    return $record;
}

function recoveryDumpCheck(string $file, string $inventory, string $label): array
{
    recoveryCommand(['python3', '-I', __DIR__.'/recovery-dump-check.py', $file, $inventory], $label);
    return json_decode(file_get_contents(recoveryPrivate().'/'.$label.'.stdout'), true, flags: JSON_THROW_ON_ERROR);
}

try {
    if (!in_array($mode, ['prepare', 'restore'], true) || getcwd() !== $root
        || $root !== '/home/ubuntu/.agentopt-v2/workspaces/req_4be0e82d6e2e4996bf501f0aef20aa28') {
        throw new RuntimeException('Assigned worktree and explicit phase required.');
    }
    recoveryMode($private, 0700);
    $lock = fopen($private.'/exclusive.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('Own recovery already active.');
    }
    $orphans = recoveryOrphans();
    require_once $root.'/vendor/autoload.php';
    if (!is_dir($evidence) && !mkdir($evidence, 0700, true)) {
        throw new RuntimeException('Evidence directory unavailable.');
    }
    if ($mode === 'prepare') {
        if (is_file($private.'/phase.json') || file_exists($root.'/.env') || file_exists($root.'/.env.testing')) {
            throw new RuntimeException('Refuse to overwrite previous recovery/configuration.');
        }
        if (trim(shell_exec('git rev-parse HEAD')) !== RECOVERY_SOURCE
            || trim(shell_exec('git rev-parse HEAD^{tree}')) !== RECOVERY_TREE
            || trim(shell_exec('git -C '.escapeshellarg(RECOVERY_FAILED).' rev-parse HEAD')) !== RECOVERY_SOURCE) {
            throw new RuntimeException('Pinned source mismatch.');
        }
        // 공개된 매니페스트 계약으로 필요한 고정 파일명만 해석한다. 원본은 읽기 전용.
        $source = RECOVERY_FAILED.'/storage/framework/testing/w04-support-provision';
        recoveryMode($source, 0700);
        $sourceHashes = [];
        foreach (['manifest.json' => 'original-manifest.json', 'before.json' => 'original-before.json',
            'snapshot.sql' => 'original.sql', 'BLOCKED' => null] as $name => $target) {
            recoveryMode($source.'/'.$name, 0600);
            $sourceHashes[$name] = hash_file('sha256', $source.'/'.$name);
            if ($target !== null) {
                if (file_exists($private.'/'.$target)) {
                    recoveryMode($private.'/'.$target, 0600);
                    if (hash_file('sha256', $private.'/'.$target) !== $sourceHashes[$name]) {
                        throw new RuntimeException('Existing original copy changed; refuse overwrite.');
                    }
                } elseif (!copy($source.'/'.$name, $private.'/'.$target) || !chmod($private.'/'.$target, 0600)) {
                    throw new RuntimeException('Private original copy failed.');
                }
            }
        }
        [$manifest, $original] = recoveryValidateOriginal();
        $published = json_decode(file_get_contents(__DIR__.'/evidence/support-provision/before.json'), true, flags: JSON_THROW_ON_ERROR);
        if ($published !== $original) {
            throw new RuntimeException('Preserved original differs from published baseline.');
        }
        // 부모의 승인된 TEST 파일은 이 보호된 프로세스 안에서만 읽고 DB 필드만 취한다.
        $credentialPath = RECOVERY_PARENT.'/.env.testing';
        if (is_link($credentialPath) || !is_file($credentialPath)) {
            throw new RuntimeException('Authorized TEST credential handoff missing.');
        }
        $sourceEnv = Dotenv\Dotenv::parse(file_get_contents($credentialPath));
        $required = [
            'TRAVEL_LAB_ISOLATED' => '1', 'G7_ENV_PRIORITY' => 'true',
            'DB_WRITE_DATABASE' => 'req81_travel_lab_test', 'DB_READ_DATABASE' => 'req81_travel_lab_test',
            'DB_DATABASE' => 'req81_travel_lab_test', 'DB_WRITE_USERNAME' => 'req81_travel',
            'DB_READ_USERNAME' => 'req81_travel', 'DB_WRITE_HOST' => '127.0.0.1', 'DB_READ_HOST' => '127.0.0.1',
            'DB_WRITE_PORT' => '3306', 'DB_READ_PORT' => '3306', 'DB_CONNECTION' => 'mysql', 'DB_PREFIX' => 'g7_',
        ];
        foreach ($required as $key => $value) {
            if (($sourceEnv[$key] ?? null) !== $value) {
                throw new RuntimeException('Authorized TEST handoff scope mismatch.');
            }
        }
        foreach (['DB_URL', 'DB_SOCKET', 'MYSQL_ATTR_SSL_CA'] as $key) {
            if (!empty($sourceEnv[$key])) {
                throw new RuntimeException('Authorized TEST handoff override forbidden.');
            }
        }
        if (empty($sourceEnv['DB_WRITE_PASSWORD']) || $sourceEnv['DB_WRITE_PASSWORD'] !== $sourceEnv['DB_READ_PASSWORD']) {
            throw new RuntimeException('TEST password handoff invalid.');
        }
        $values = $required + ['DB_WRITE_PASSWORD' => $sourceEnv['DB_WRITE_PASSWORD'],
            'DB_READ_PASSWORD' => $sourceEnv['DB_READ_PASSWORD'], 'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
            'APP_NAME' => 'W04 TEST baseline recovery', 'APP_ENV' => 'testing', 'APP_DEBUG' => 'false',
            'APP_URL' => 'http://127.0.0.1', 'INSTALLER_COMPLETED' => 'false',
            'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'FILESYSTEM_DISK' => 'local',
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'SCOUT_DRIVER' => 'mysql-fulltext', 'LOG_CHANNEL' => 'stderr'];
        $contents = '';
        foreach ($values as $key => $value) {
            $contents .= $key.'='.(preg_match('/^[A-Za-z0-9_:\/+=.-]+$/', $value) ? $value
                : '"'.str_replace(['\\', '"', '$', "\n", "\r"], ['\\\\', '\\"', '\\$', '\\n', '\\r'], $value).'"')."\n";
        }
        if (Dotenv\Dotenv::parse($contents) !== $values) {
            throw new RuntimeException('Private TEST environment serialization mismatch.');
        }
        foreach (['.env', '.env.testing'] as $name) {
            $handle = fopen($root.'/'.$name, 'x');
            if (!$handle || fwrite($handle, $contents) !== strlen($contents)) {
                throw new RuntimeException('Own TEST environment creation failed.');
            }
            fclose($handle);
            chmod($root.'/'.$name, 0600);
        }
        unset($sourceEnv, $contents, $values);
        $env = w04Env();
        $quote = static fn ($v) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $v).'"';
        file_put_contents($private.'/mysql.cnf', "[client]\nprotocol=TCP\nhost=127.0.0.1\nport=3306\nuser=req81_travel\npassword=".$quote($env['DB_WRITE_PASSWORD'])."\n");
        recoveryMode($private.'/mysql.cnf', 0600);
        recoveryPhase($phase = 'original_copied_current_measurement');
        $sqlCheck = recoveryDumpCheck($private.'/original.sql', $private.'/original-before.json', 'original-dump-check');
        $pdo = w04Pdo();
        $connection = recoveryConnection($pdo);
        $current = w04Measure($pdo);
        if ($current['table_count'] !== 22) {
            throw new RuntimeException('Unexpected starting TEST table set; mutation forbidden.');
        }
        w04Save($private.'/current-before.json', $current);
        $backup = recoveryCommand(['mysqldump', '--defaults-file='.$private.'/mysql.cnf', '--single-transaction',
            '--hex-blob', '--no-tablespaces', '--skip-lock-tables', 'req81_travel_lab_test'], 'current-safety-dump',
            output: $private.'/current-safety.sql');
        recoveryMode($private.'/current-safety.sql', 0600);
        if ($current !== w04Measure($pdo) || filesize($private.'/current-safety.sql') === 0) {
            throw new RuntimeException('Current safety snapshot unstable.');
        }
        recoveryConnection($pdo);
        $pdo = null;
        $safetyCheck = recoveryDumpCheck($private.'/current-safety.sql', $private.'/current-before.json', 'current-dump-check');
        $metadata = ['source_sha' => RECOVERY_SOURCE, 'source_tree' => RECOVERY_TREE,
            'source_branch' => 'feat/g7-travel-lab-c7ae42d1', 'request_id' => 'req_4be0e82d6e2e4996bf501f0aef20aa28',
            'original' => ['table_count' => 55, 'row_count' => 104, 'inventory_digest_sha256' => recoveryDigest($original),
                'dump_sha256' => $manifest['dump_sha256'], 'sql_boundary_check' => $sqlCheck],
            'current' => ['table_count' => $current['table_count'], 'row_count' => $current['row_count'],
                'inventory_digest_sha256' => recoveryDigest($current), 'safety_dump_sha256' => hash_file('sha256', $private.'/current-safety.sql'),
                'sql_boundary_check' => $safetyCheck], 'source_private_file_sha256' => $sourceHashes,
            'directory_mode' => '0700', 'snapshot_manifest_options_modes' => '0600',
            'orphans' => $orphans, 'connection' => $connection, 'safety_dump_command' => $backup,
            'seconds' => round(microtime(true) - $started, 3), 'database_mutations' => 0];
        w04Save($evidence.'/original-before.json', $original);
        w04Save($evidence.'/current-before.json', $current);
        w04Save($evidence.'/prepared.json', $metadata);
        recoveryPhase($phase = 'prepared_no_database_mutation');
        echo json_encode(['phase' => $phase, 'table_count' => $current['table_count'], 'row_count' => $current['row_count'],
            'other_test_connections' => 0, 'original_dump_sha256' => $manifest['dump_sha256']])."\n";
        exit(0);
    }

    $storedPhase = json_decode(file_get_contents($private.'/phase.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($storedPhase['phase'] !== 'prepared_no_database_mutation') {
        throw new RuntimeException('Restore requires reviewed prepared phase; no blind repeat.');
    }
    [$manifest, $original] = recoveryValidateOriginal();
    recoveryMode($private.'/mysql.cnf', 0600);
    $prepared = json_decode(file_get_contents($evidence.'/prepared.json'), true, flags: JSON_THROW_ON_ERROR);
    $current = json_decode(file_get_contents($private.'/current-before.json'), true, flags: JSON_THROW_ON_ERROR);
    if (hash_file('sha256', $private.'/current-safety.sql') !== $prepared['current']['safety_dump_sha256']) {
        throw new RuntimeException('Safety snapshot integrity failed.');
    }
    recoveryDumpCheck($private.'/original.sql', $private.'/original-before.json', 'restore-original-dump-check');
    $pdo = w04Pdo();
    $connectionBefore = recoveryConnection($pdo);
    if (w04Measure($pdo) !== $current) {
        throw new RuntimeException('TEST changed after safety backup.');
    }
    $pdo = null;
    recoveryOrphans();
    recoveryPhase($phase = 'native_wipe_started_original_and_safety_retained');
    $wipe = recoveryCommand([PHP_BINARY, __DIR__.'/recovery-artisan.php', 'db:wipe', '--database=mysql',
        '--drop-views', '--force', '--no-interaction'], 'native-wipe');
    w04Env();
    $pdo = w04Pdo();
    $connectionEmpty = recoveryConnection($pdo);
    $empty = w04Measure($pdo);
    if ($empty['table_count'] !== 0) {
        throw new RuntimeException('Native TEST wipe incomplete.');
    }
    $pdo = null;
    w04Save($evidence.'/empty.json', $empty);
    recoveryValidateOriginal();
    recoveryOrphans();
    recoveryPhase($phase = 'native_import_started_original_and_safety_retained');
    $import = recoveryCommand(['mysql', '--defaults-file='.$private.'/mysql.cnf', '--protocol=TCP',
        '--host=127.0.0.1', '--port=3306', '--user=req81_travel', '--database=req81_travel_lab_test',
        '--local-infile=0', '--skip-reconnect'], 'native-import', $private.'/original.sql');
    recoveryPhase($phase = 'full_rows_ddl_verification');
    $pdo = w04Pdo();
    $connectionFinal = recoveryConnection($pdo);
    $final = w04Measure($pdo);
    $pdo = null;
    w04Save($evidence.'/final.json', $final);
    if ($final !== $original || recoveryDigest($final) !== RECOVERY_BASELINE_DIGEST) {
        throw new RuntimeException('Whole baseline row/DDL equality failed.');
    }
    $source = RECOVERY_FAILED.'/storage/framework/testing/w04-support-provision';
    foreach ($prepared['source_private_file_sha256'] as $name => $hash) {
        recoveryMode($source.'/'.$name, 0600);
        if (hash_file('sha256', $source.'/'.$name) !== $hash) {
            throw new RuntimeException('Failed child original recovery file changed.');
        }
    }
    $finalOrphans = recoveryOrphans();
    $effective = json_decode(file_get_contents($private.'/wipe-effective-config.json'), true, flags: JSON_THROW_ON_ERROR);
    $result = ['status' => 'PASS_BASELINE_RECOVERY_ONLY', 'source_sha' => RECOVERY_SOURCE, 'source_tree' => RECOVERY_TREE,
        'original_table_count' => 55, 'original_row_count' => 104,
        'current_table_count' => $current['table_count'], 'current_row_count' => $current['row_count'],
        'final_table_count' => $final['table_count'], 'final_row_count' => $final['row_count'],
        'original_dump_sha256' => $manifest['dump_sha256'], 'final_inventory_digest_sha256' => recoveryDigest($final),
        'whole_tables_rows_ddl_objects_equal' => true, 'other_schema_objects' => $final['other_schema_objects'],
        'checks' => ['before' => $connectionBefore, 'empty' => $connectionEmpty, 'final' => $connectionFinal,
            'wipe_effective_application' => $effective, 'final_orphans' => $finalOrphans],
        'commands' => [$wipe, $import], 'native_artisan_commands' => 1, 'native_mysql_imports' => 1,
        'fresh_install_commands' => 0, 'seconds' => round(microtime(true) - $started, 3),
        'original_failed_child_files_unchanged' => true, 'failed_child_BLOCKED_retained' => true,
        'own_original_and_current_safety_backup_retained' => true, 'test_ownership_released' => true,
        'installer_validation' => 'NOT_RUN', 'canonical_validation' => 'NOT_RUN',
        'earlier_128_tables_639_rows' => 'NOT_PROVEN'];
    w04Save($evidence.'/result.json', $result);
    recoveryPhase($phase = 'verified_exact_baseline_test_released_backups_retained');
    echo json_encode(['status' => $result['status'], 'table_count' => $final['table_count'], 'row_count' => $final['row_count'],
        'digest_sha256' => recoveryDigest($final), 'test_ownership_released' => true])."\n";
} catch (Throwable $error) {
    if (is_dir($private)) {
        w04Save($private.'/failure.json', ['status' => 'BLOCKED', 'phase' => $phase, 'class' => get_class($error),
            'source_file' => basename($error->getFile()), 'source_line' => $error->getLine(),
            'backups_retained' => true, 'automatic_retry' => false]);
    }
    fwrite(STDERR, 'BLOCKED: '.$phase.'; '.get_class($error).'; private recovery evidence retained.' ."\n");
    exit(1);
}
