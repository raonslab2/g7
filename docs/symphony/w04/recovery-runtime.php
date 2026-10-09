<?php
require __DIR__.'/guard.php';
$root=dirname(__DIR__,3);
w04RequireSnapshot($argv[1]??'final-install');
$source=$root.'/scripts/travel-lab/live-recovery.php';
$bytes=file_get_contents($source);
$private=$root.'/storage/framework/testing/w04-recovery-probe';
if (file_exists($private)) { throw new RuntimeException('Unresolved recovery probe exists.'); }
mkdir($private,0700);
// Product functions and catch/finally remain verbatim; only source-relative paths and
// one normal-path check are adapted to exercise a recoverable failure on real TEST.
$adapted=str_replace("require __DIR__.'/live-bootstrap.php';","require ".var_export($root.'/scripts/travel-lab/live-bootstrap.php',true).';',$bytes);
$adapted=str_replace('dirname(__DIR__, 2)',var_export($root,true),$adapted);
$needle="travelLabCheck(travelLabTestDigest() === \$before, 'Restored synthetic testing records differ from the pre-rollback digest.');";
if (substr_count($adapted,$needle) !== 1) { throw new RuntimeException('Failure injection target differs.'); }
$adapted=str_replace($needle,"throw new RuntimeException('W04 injected recoverable check failure after native rollback/replay/import.');",$adapted);
$start=strpos($bytes,'} catch (Throwable $error) {');
$adaptedStart=strpos($adapted,'} catch (Throwable $error) {');
if (substr($bytes,$start) !== substr($adapted,$adaptedStart)) { throw new RuntimeException('Product fallback changed.'); }
file_put_contents($private.'/probe.php',$adapted);
$before=w04Measure(w04Pdo());
$status=w04Child([PHP_BINARY,$private.'/probe.php'],$private.'/probe.log');
$after=w04Measure(w04Pdo());
$stderr=file_get_contents($private.'/probe.log.stderr');
$pass=$status === 1 && str_contains($stderr,'RECOVERED: testing schema restored and all selected-table digests verified') && $before === $after;
w04Save(__DIR__.'/evidence/install/recovery-runtime.json',['status'=>$pass?'PASS':'FAIL','expected_child_exit'=>1,'child_exit'=>$status,'source_sha256'=>hash('sha256',$bytes),'adapter_sha256'=>hash('sha256',$adapted),'fallback_bytes_verbatim'=>true,'injection'=>'Normal-path check fails after successful real rollback/replay/import; unchanged catch/finally runs real native mysql fallback and selected digest verification','all_tables_rows_ddl_equal'=>$before === $after,'table_count'=>$before['table_count'],'row_count'=>$before['row_count']]);
if ($pass) { foreach(glob($private.'/*') as $f) {unlink($f);} rmdir($private); }
exit($pass?0:1);
