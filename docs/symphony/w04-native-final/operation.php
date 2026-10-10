<?php
/** 독립 destructive installed suite의 live handle/lease를 유지한다. detach하지 않는다. */
require __DIR__.'/guard.php';
$label=$argv[1]??'original';
$dir=w04fRequireSnapshot($label);
$lock=fopen(w04fRoot().'/storage/framework/testing/w04f-exclusive.lock','c');
if(!flock($lock,LOCK_EX|LOCK_NB)) { throw new RuntimeException('Own TEST runner already active.'); }
file_put_contents($dir.'/BLOCKED',"Native operation pending; exact restoration required.\n");chmod($dir.'/BLOCKED',0600);
w04fPhase($dir,'BLOCKED_native_operation_running',['pid'=>getmypid(),'cwd'=>getcwd(),'argv'=>$argv]);
$cmd=array_slice($argv,2);
if($cmd===[] || $cmd[0]!==PHP_BINARY) { throw new RuntimeException('Scoped PHP operation only.'); }
$status=w04fChild($cmd,w04fPrivate('private').'/operation-'.bin2hex(random_bytes(4)).'.log');
w04fPhase($dir,'BLOCKED_native_operation_complete_restore_pending',['exit'=>$status]);
echo 'Native operation exit='.$status."\n";
exit($status);
