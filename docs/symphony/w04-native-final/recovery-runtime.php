<?php
/** 实际 native migration rollback/replay + 原始 catch/finally fallback，TEST-only 参数适配。 */
require __DIR__.'/guard.php';
w04fRequireSnapshot($argv[1]??'original');
$root=w04fRoot();$source=$root.'/scripts/travel-lab/live-recovery.php';$bytes=file_get_contents($source);
$mode=$argv[2]??'fault'; if(!in_array($mode,['normal','fault'],true)) { throw new RuntimeException('Recovery mode rejected.'); }
$private=w04fPrivate('recovery-'.$mode);
if(file_exists($private)) { throw new RuntimeException('Existing recovery probe retained.'); }mkdir($private,0700);
$adapted=str_replace("require __DIR__.'/live-bootstrap.php';","require ".var_export(__DIR__.'/runtime-bootstrap.php',true).';',$bytes);
$adapted=str_replace('dirname(__DIR__, 2)',var_export($root,true),$adapted);
$adapted=str_replace('travelLabApp(true);','w04App();',$adapted);
$adapted=str_replace('travelLabEnvironment(true);','w04Env();',$adapted);
$adapted=str_replace("    return \$result;", "    \$result['__whole_schema'] = w04Measure(w04Pdo());\n    return \$result;",$adapted);
$adapted=str_replace("'--defaults-extra-file='", "'--defaults-file='",$adapted);
$adapted=str_replace("'--single-transaction'", "'--protocol=tcp', '--host=127.0.0.1', '--port=3306', '--user=req81_travel', '--single-transaction'",$adapted);
$adapted=str_replace("'--database=req81_travel_lab_test'", "'--protocol=tcp', '--host=127.0.0.1', '--port=3306', '--user=req81_travel', '--local-infile=0', '--skip-reconnect', '--database=req81_travel_lab_test'",$adapted);
// 옵션 파일 quoting은 SQL이나 비밀번호를 출력하지 않는다.
$line='file_put_contents($options, "[client]\\nhost=127.0.0.1\\nport=3306\\nuser=req81_travel\\npassword=".$environment[\'DB_WRITE_PASSWORD\']."\\n");';
$replacement='file_put_contents($options, "[client]\\nprotocol=tcp\\nhost=127.0.0.1\\nport=3306\\nuser=req81_travel\\npassword=\\\"".str_replace(["\\\\", "\\\""],["\\\\\\\\", "\\\\\\\""],$environment[\'DB_WRITE_PASSWORD\'])."\\\"\\n");';
if(substr_count($adapted,$line)!==1) { throw new RuntimeException('Options adaptation mismatch.'); }
$adapted=str_replace($line,$replacement,$adapted);
$adapted=str_replace("2 => ['pipe', 'w']], \$pipes);", "2 => ['pipe', 'w']], \$pipes, w04fRoot(), w04Env());",$adapted);
$adapted=str_replace("    \$process = proc_open(\$command,", "    w04Env();\n    \$verified=w04Pdo(); \$verified=null;\n    \$process = proc_open(\$command,",$adapted);
$needle="travelLabCheck(travelLabTestDigest() === \$before, 'Restored synthetic testing records differ from the pre-rollback digest.');";
if($mode==='fault') {
 if(substr_count($adapted,$needle)!==1) { throw new RuntimeException('Fault target mismatch.'); }
 $adapted=str_replace($needle,"throw new RuntimeException('Injected post-native-import verification fault.');",$adapted);
}
$originalTail=substr($bytes,strpos($bytes,'} catch (Throwable $error) {'));
$adaptedTail=substr($adapted,strpos($adapted,'} catch (Throwable $error) {'));
if($originalTail!==$adaptedTail) { throw new RuntimeException('Native fallback changed.'); }
file_put_contents($private.'/probe.php',$adapted);chmod($private.'/probe.php',0600);
$before=w04Measure(w04Pdo());
$exit=w04fChild([PHP_BINARY,$private.'/probe.php'],$private.'/probe.log');
$after=w04Measure(w04Pdo());$stderr=file_get_contents($private.'/probe.log.stderr');
$pass=$before===$after && ($mode==='normal' ? $exit===0 : ($exit===1 && str_contains($stderr,'RECOVERED: testing schema restored and all selected-table digests verified')));
w04Save(w04fEvidence('recovery').'/'.$mode.'.json',['status'=>$pass?'PASS':'FAIL','exit'=>$exit,'expected_exit'=>$mode==='normal'?0:1,'all_tables_rows_ddl_equal'=>$before===$after,'before_digest'=>w04fDigest($before),'after_digest'=>w04fDigest($after),'native_catch_finally_verbatim'=>true,'source_sha256'=>hash('sha256',$bytes),'adapter_sha256'=>hash('sha256',$adapted),'full_inventory_added_to_native_digest'=>true,'table_count'=>$before['table_count'],'row_count'=>$before['row_count'],'private_probe_retained'=>true]);
echo 'Recovery '.$mode.' '.($pass?'PASS':'FAIL').' native_exit='.$exit."\n";
exit($pass?0:1);
