<?php
require __DIR__.'/guard.php';
$root = dirname(__DIR__,3);
$label = $argv[1] ?? '';
if (!preg_match('/^[a-z0-9-]+$/', $label)) { throw new RuntimeException('Evidence label required.'); }
$private = $root.'/storage/framework/testing/w04-'.$label;
$evidence = __DIR__.'/evidence/'.$label;
$mode = $argv[2] ?? 'run';
if (!is_dir($evidence)) { mkdir($evidence,0700,true); }
$lock = fopen($root.'/storage/framework/testing/w04-exclusive.lock','c');
if (!flock($lock, LOCK_EX|LOCK_NB)) { throw new RuntimeException('Own runner active.'); }
$pdo = w04Pdo(); w04Exclusive($pdo);
$before = w04Measure($pdo);
if (in_array($mode,['run','save'],true)) {
    if (file_exists($private)) { throw new RuntimeException('Existing recovery snapshot must not be overwritten.'); }
    mkdir($private,0700);
    $env = w04Env();
    $escape = static fn($v) => '"'.str_replace(['\\','"',"\n","\r"],['\\\\','\\"','\\n','\\r'],$v).'"';
    file_put_contents($private.'/mysql.cnf', "[client]\nhost=127.0.0.1\nport=3306\nuser=".$escape($env['DB_WRITE_USERNAME'])."\npassword=".$escape($env['DB_WRITE_PASSWORD'])."\n");
    w04Save($private.'/before.json',$before); w04Save($evidence.'/before.json',$before);
    $proc = proc_open(['mysqldump','--defaults-extra-file='.$private.'/mysql.cnf','--single-transaction','--hex-blob','--no-tablespaces','--skip-lock-tables','req81_travel_lab_test'],[0=>['file','/dev/null','r'],1=>['file',$private.'/snapshot.sql','w'],2=>['file',$private.'/dump.stderr','w']],$pipes,$root,$env);
    $dumpExit = proc_close($proc);
    if ($dumpExit !== 0 || $before !== w04Measure($pdo) || !str_contains(file_get_contents($private.'/snapshot.sql'),'-- Dump completed on')) { throw new RuntimeException('Snapshot failed; no destructive work.'); }
    $manifest = ['source_sha'=>trim(shell_exec('git rev-parse HEAD')), 'source_tree'=>trim(shell_exec('git rev-parse HEAD^{tree}')), 'dump_sha256'=>hash_file('sha256',$private.'/snapshot.sql'),'dump_exit'=>$dumpExit,'stable'=>true,'directory_mode'=>decoct(fileperms($private)&0777),'dump_mode'=>decoct(fileperms($private.'/snapshot.sql')&0777),'other_test_connections'=>0];
    w04Save($private.'/manifest.json',$manifest); w04Save($evidence.'/snapshot.json',$manifest);
    file_put_contents($private.'/BLOCKED','Validated snapshot; destructive work/restoration pending.');
    $pdo = null;
    if ($mode === 'save') { echo "Validated private snapshot saved; BLOCKED retained until exact restore.\n"; exit(0); }
    // Root TestCase kills parked TEST connections.
    $status = 1;
    try { $status = w04Child(array_slice($argv,3),$private.'/native.log'); }
    catch (Throwable $e) { w04Save($evidence.'/child-failure.json',['class'=>get_class($e)]); }
} elseif ($mode === 'restore') {
    if (!is_file($private.'/BLOCKED')) { throw new RuntimeException('No pending recovery marker.'); }
    $status = null;
} else { throw new RuntimeException('Unsupported mode.'); }
try {
    $manifest = json_decode(file_get_contents($private.'/manifest.json'),true,flags:JSON_THROW_ON_ERROR);
    $before = json_decode(file_get_contents($private.'/before.json'),true,flags:JSON_THROW_ON_ERROR);
    if (hash_file('sha256',$private.'/snapshot.sql') !== $manifest['dump_sha256']) { throw new RuntimeException('Backup changed; preserve BLOCKED.'); }
    $pdo = w04Pdo(); w04Exclusive($pdo); $pdo = null;
    $wipe = w04Child([PHP_BINARY,__DIR__.'/guarded-artisan.php','db:wipe','--database=mysql','--drop-views','--force','--no-interaction'],$private.'/restore-wipe.log');
    if ($wipe !== 0 || w04Measure(w04Pdo())['table_count'] !== 0) { throw new RuntimeException('Guarded restore wipe failed.'); }
    $proc = proc_open(['mysql','--defaults-extra-file='.$private.'/mysql.cnf','--database=req81_travel_lab_test'],[0=>['file',$private.'/snapshot.sql','r'],1=>['file',$private.'/restore.stdout','w'],2=>['file',$private.'/restore.stderr','w']],$pipes,$root,w04Env());
    $restore = proc_close($proc);
    $pdo = w04Pdo(); w04Exclusive($pdo); $after = w04Measure($pdo); $pdo = null;
    w04Save($evidence.'/after.json',$after);
    w04Save($evidence.'/result.json',['child_exit'=>$status,'restore_exit'=>$restore,'wipe_exit'=>$wipe,'exact_tables_rows_ddl'=>$before === $after,'digest_sha256'=>hash('sha256',json_encode($after,JSON_THROW_ON_ERROR)),'table_count'=>$after['table_count'],'row_count'=>$after['row_count'],'backup_deleted'=>$restore === 0 && $before === $after]);
    if ($restore !== 0 || $before !== $after) { throw new RuntimeException('Exact restore failed; retain private backup and BLOCKED.'); }
    // Logs remain private until explicitly sanitized; safe command/result metadata may be published.
    foreach (glob($private.'/*.result.json') as $file) { copy($file,$evidence.'/'.basename($file)); }
    if (is_file($private.'/native.log')) { copy($private.'/native.log',$root.'/storage/framework/testing/w04-'.$label.'-native.log'); }
    foreach (glob($private.'/*') as $file) { unlink($file); } rmdir($private);
    echo json_encode(['child_exit'=>$status,'restored_exactly'=>true,'table_count'=>$after['table_count'],'row_count'=>$after['row_count']])."\n";
} catch (Throwable $e) {
    w04Save($evidence.'/BLOCKED.json',['class'=>get_class($e),'snapshot_retained'=>true]);
    fwrite(STDERR,'BLOCKED: exact recovery unverified; private dump retained. '.get_class($e)."\n"); exit(1);
}
exit($status ?? 0);
