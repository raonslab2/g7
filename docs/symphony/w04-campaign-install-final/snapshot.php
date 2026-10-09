<?php
// Whole-TEST snapshot / guarded destructive child / exact restore (req_812d0334).
// Adapted from audited docs/symphony/w04/snapshot.php. Corrections in this copy:
//  - stricter exclusivity (global PROCESSLIST, foreign /proc handles, own server) before dump, wipe, import;
//  - pending/BLOCKED phase.json at every step; digest/baseline recorded in manifest;
//  - restore/restore-keep은 보존된 원본을 복원하고 삭제하지 않는다;
//  - 모든 원본/safety 백업은 검증 이후에도 보존한다.
// Usage: snapshot.php <label> save | run <cmd...> | restore | restore-keep
require __DIR__.'/guard.php';
$root = w04fRoot();
$label = $argv[1] ?? '';
$mode = $argv[2] ?? '';
$private = w04fPrivate($label);
$evidence = w04fEvidence($label);
w04Env();
$lock = fopen($root.'/storage/framework/testing/w04c-exclusive.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) { throw new RuntimeException('Own runner active.'); }
w04Save(w04fPrivate('private').'/lease.json',['request'=>'req_812d0334c2064f5d88339114e7954235','pid'=>getmypid(),'cwd'=>$root,'argv'=>$argv,'label'=>$label,'mode'=>$mode,'utc'=>gmdate('c')]);
$status = null;
if (in_array($mode, ['run', 'save'], true)) {
    if (file_exists($private)) { throw new RuntimeException('Existing recovery snapshot must not be overwritten.'); }
    $pdo = w04Pdo(); $exclusive = w04fExclusive($pdo);
    $before = w04Measure($pdo);
    mkdir($private, 0700);
    w04fPhase($private, 'snapshot_pending');
    $env = w04Env();
    $escape = static fn ($v) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $v).'"';
    file_put_contents($private.'/mysql.cnf', "[client]\nprotocol=tcp\nhost=127.0.0.1\nport=3306\nuser=".$escape($env['DB_WRITE_USERNAME'])."\npassword=".$escape($env['DB_WRITE_PASSWORD'])."\n");
    chmod($private.'/mysql.cnf', 0600);
    w04Save($private.'/before.json', $before);
    $start = microtime(true);
    $proc = proc_open(['mysqldump', '--defaults-file='.$private.'/mysql.cnf', '--protocol=tcp', '--host=127.0.0.1', '--port=3306', '--user=req81_travel', '--single-transaction', '--hex-blob', '--no-tablespaces', '--skip-lock-tables', 'req81_travel_lab_test'],
        [0 => ['file', '/dev/null', 'r'], 1 => ['file', $private.'/snapshot.sql', 'w'], 2 => ['file', $private.'/dump.stderr', 'w']], $pipes, $root, $env);
    $dumpPid=proc_get_status($proc)['pid'];
    $dumpExit = proc_close($proc);
    if(file_exists('/proc/'.$dumpPid)) { throw new RuntimeException('Dump PID remains; preserve snapshot.'); }
    $dumpHandles=w04fForeignHandles();
    $dumpSeconds = round(microtime(true) - $start, 3);
    $stable = $before === w04Measure($pdo);
    $trailer = str_contains((string) shell_exec('tail -c 200 '.escapeshellarg($private.'/snapshot.sql')), '-- Dump completed on');
    if ($dumpExit !== 0 || !$stable || !$trailer) { w04fPhase($private, 'snapshot_failed_no_destructive_work'); throw new RuntimeException('Snapshot failed; no destructive work.'); }
    foreach (['before.json', 'snapshot.sql', 'dump.stderr'] as $f) { chmod($private.'/'.$f, 0600); }
    $manifest = ['label' => $label, 'source_sha' => trim(shell_exec('git rev-parse HEAD')), 'source_tree' => trim(shell_exec('git rev-parse HEAD^{tree}')),
        'dump_sha256' => hash_file('sha256', $private.'/snapshot.sql'), 'dump_bytes' => filesize($private.'/snapshot.sql'), 'dump_exit' => $dumpExit, 'dump_pid'=>$dumpPid,'dump_process_gone'=>true,'dump_handles'=>$dumpHandles, 'dump_seconds' => $dumpSeconds,
        'stable' => true, 'before_digest' => w04fDigest($before), 'table_count' => $before['table_count'], 'row_count' => $before['row_count'],
        'equals_original_baseline' => w04fDigest($before) === W04F_BASELINE_DIGEST, 'directory_mode' => '700', 'file_mode' => '600', 'exclusive' => $exclusive, 'utc' => gmdate('c')];
    w04Save($private.'/manifest.json', $manifest); chmod($private.'/manifest.json', 0600);
    w04Save($evidence.'/snapshot.json', $manifest); w04Save($evidence.'/before.json', $before);
    file_put_contents($private.'/BLOCKED', "Validated snapshot; destructive work/restoration pending.\n"); chmod($private.'/BLOCKED', 0600);
    w04fPhase($private, 'BLOCKED_destructive_pending');
    $pdo = null;
    $scan=w04fChild(['python3','docs/symphony/w04-campaign-install-final/recovery-dump-check.py',$private.'/snapshot.sql',$private.'/before.json'],$private.'/dump-check.log');
    copy($private.'/dump-check.log',$evidence.'/dump-check.json');
    if($scan!==0) { throw new RuntimeException('Dump SQL scope validation failed.'); }
    if ($mode === 'save') { echo json_encode(['saved' => $label, 'tables' => $before['table_count'], 'rows' => $before['row_count'], 'digest' => $manifest['before_digest']])."\n"; exit(0); }
    $status = 1;
    try {
        $cmd=array_slice($argv,3);
        $ordinary=in_array('vendor/bin/phpunit',$cmd,true);
        $status = w04fChild($cmd,$private.'/native.log',w04Env(false,$ordinary));
    }
    catch (Throwable $e) { w04Save($evidence.'/child-failure.json', ['class' => get_class($e)]); }
    w04fPhase($private, 'BLOCKED_child_finished_restore_pending', ['child_exit' => $status]);
} elseif (in_array($mode, ['restore', 'restore-keep'], true)) {
    if (!is_file($private.'/BLOCKED')) { throw new RuntimeException('No pending recovery marker.'); }
} else { throw new RuntimeException('Unsupported mode.'); }
$keep = true; // 검증 이후에도 private original/safety snapshots를 보존한다.
try {
    w04fRequireSnapshot($label);
    $manifest = json_decode(file_get_contents($private.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    $before = json_decode(file_get_contents($private.'/before.json'), true, flags: JSON_THROW_ON_ERROR);
    if(w04fChild(['python3','docs/symphony/w04-campaign-install-final/recovery-dump-check.py',$private.'/snapshot.sql',$private.'/before.json'],$private.'/restore-sql-check.log')!==0) { throw new RuntimeException('Import SQL boundary rejected.'); }
    $pdo = w04Pdo(); $preWipe = w04fExclusive($pdo); $current = w04Measure($pdo); $pdo = null;
    // 복구 전 현재 상태도 별도로 보존한다. 원본은 절대 덮어쓰지 않는다.
    $suffix=bin2hex(random_bytes(4));
    w04Save($private.'/safety-'.$suffix.'.json',$current);
    $safe=w04fChild(['mysqldump','--defaults-file='.$private.'/mysql.cnf','--protocol=tcp','--host=127.0.0.1','--port=3306','--user=req81_travel','--single-transaction','--hex-blob','--no-tablespaces','--skip-lock-tables','req81_travel_lab_test'],$private.'/safety-'.$suffix.'.sql');
    w04Save($private.'/safety-'.$suffix.'-manifest.json',['sha256'=>hash_file('sha256',$private.'/safety-'.$suffix.'.sql'),'exit'=>$safe,'inventory_digest'=>w04fDigest($current),'dump_trailer'=>str_contains(substr(file_get_contents($private.'/safety-'.$suffix.'.sql'),-200),'-- Dump completed on')]);
    if($safe!==0 || !str_contains(substr(file_get_contents($private.'/safety-'.$suffix.'.sql'),-200),'-- Dump completed on') || $current!==w04Measure(w04Pdo())) { throw new RuntimeException('Safety snapshot failed.'); }
    w04Save($evidence.'/pre-restore.json', ['table_count' => $current['table_count'], 'row_count' => $current['row_count'], 'digest' => w04fDigest($current), 'exclusive' => $preWipe]);
    w04fPhase($private, 'BLOCKED_restore_wipe');
    w04fQuiesce();
    $wipe = $current['table_count']===0 ? 0 : w04fChild([PHP_BINARY, __DIR__.'/guarded-artisan.php', 'db:wipe', '--database=mysql', '--drop-views', '--force', '--no-interaction'], $private.'/restore-wipe.log');
    $pdo = w04Pdo(); $empty = w04Measure($pdo); $preImport = w04fExclusive($pdo); $pdo = null;
    if ($wipe !== 0 || $empty['table_count'] !== 0) { throw new RuntimeException('Guarded restore wipe failed.'); }
    w04fPhase($private, 'BLOCKED_restore_import');
    $start = microtime(true);
    $proc = proc_open(['mysql', '--defaults-file='.$private.'/mysql.cnf', '--protocol=tcp', '--host=127.0.0.1', '--port=3306', '--user=req81_travel', '--local-infile=0', '--skip-reconnect', '--database=req81_travel_lab_test'], [0 => ['file', $private.'/snapshot.sql', 'r'], 1 => ['file', $private.'/restore.stdout', 'w'], 2 => ['file', $private.'/restore.stderr', 'w']], $pipes, $root, w04Env());
    $importPid=proc_get_status($proc)['pid'];
    $restore = proc_close($proc);
    if(file_exists('/proc/'.$importPid)) { throw new RuntimeException('Import PID remains; no measurement.'); }
    $importHandles=w04fForeignHandles();
    $importSeconds = round(microtime(true) - $start, 3);
    $pdo = w04Pdo(); $postExclusive = w04fExclusive($pdo); $after = w04Measure($pdo); $pdo = null;
    $exact = $before === $after;
    w04Save($evidence.'/after.json', $after);
    $result = ['label' => $label, 'child_exit' => $status, 'wipe_exit' => $wipe, 'empty_tables_after_wipe' => $empty['table_count'], 'import_exit' => $restore, 'import_seconds' => $importSeconds,'import_pid'=>$importPid,'import_process_gone'=>true,'import_handles'=>$importHandles,
        'exact_tables_rows_ddl' => $exact, 'restored_digest' => w04fDigest($after), 'expected_digest' => $manifest['before_digest'], 'equals_original_baseline' => w04fDigest($after) === W04F_BASELINE_DIGEST,
        'table_count' => $after['table_count'], 'row_count' => $after['row_count'], 'exclusive_pre_import' => $preImport, 'exclusive_post' => $postExclusive,
        'backup_retained' => true, 'utc' => gmdate('c')];
    w04Save($evidence.'/result.json', $result);
    w04Save($evidence.'/restoration-'.$suffix.'.json',$result);
    w04Save($evidence.'/restored-inventory-'.$suffix.'.json',$after);
    if ($restore !== 0 || !$exact) { throw new RuntimeException('Exact restore failed; retain private backup and BLOCKED.'); }
    foreach (glob($private.'/*.result.json') as $file) { copy($file, $evidence.'/'.basename($file)); }
    if (is_file($private.'/native.log')) { copy($private.'/native.log', $root.'/storage/framework/testing/w04c-'.$label.'-native.log'); }
    if ($keep) {
        unlink($private.'/BLOCKED');
        w04fPhase($private, 'verified_exact_restore_backup_retained');
    } else {
        foreach (glob($private.'/*') as $file) { unlink($file); } rmdir($private);
    }
    echo json_encode(['label' => $label, 'child_exit' => $status, 'restored_exactly' => true, 'tables' => $after['table_count'], 'rows' => $after['row_count'], 'baseline' => $result['equals_original_baseline']])."\n";
} catch (Throwable $e) {
    if (is_dir($private)) { w04fPhase($private, 'BLOCKED_recovery_unverified_backup_retained', ['class' => get_class($e)]); }
    w04Save($evidence.'/BLOCKED.json', ['class' => get_class($e), 'snapshot_retained' => true, 'utc' => gmdate('c')]);
    fwrite(STDERR, 'BLOCKED: exact recovery unverified; private dump retained. '.get_class($e)."\n"); exit(1);
}
exit($status ?? 0);
