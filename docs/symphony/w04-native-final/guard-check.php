<?php
/** SQL 접속 없이 실제 subprocess 환경 override 거부를 확인한다. */
require __DIR__.'/guard.php';
$normal=w04Env();$cases=[];
foreach(['DB_URL'=>'mysql://foreign.invalid/x','DB_SOCKET'=>'/tmp/foreign.sock','DB_WRITE_DATABASE'=>'foreign_schema','DB_READ_DATABASE'=>'foreign_schema','DB_WRITE_HOST'=>'192.0.2.1','DB_WRITE_USERNAME'=>'root','MYSQL_PWD'=>'forbidden','CACHE_LIMITER'=>'redis','CACHE_STORE'=>'array','DB_CACHE_CONNECTION'=>'sqlite','DB_CACHE_LOCK_TABLE'=>'foreign_locks','QUEUE_CONNECTION'=>'redis','MAIL_MAILER'=>'smtp','FILESYSTEM_DISK'=>'s3','HTTP_PROXY'=>'http://192.0.2.1:9','APP_CONFIG_CACHE'=>'/tmp/foreign.php'] as $key=>$value) {
    $env=$normal;$env[$key]=$value;
    $log=w04fPrivate('private').'/guard-check-'.$key.'.log';
    $p=proc_open([PHP_BINARY,'-r','require "docs/symphony/w04-native-final/guard.php"; try { w04Env(); exit(0); } catch (RuntimeException $e) { exit(23); }'],[0=>['file','/dev/null','r'],1=>['file',$log,'w'],2=>['file',$log.'.stderr','w']],$pipes,w04fRoot(),$env);
    $status=proc_close($p);$cases[$key]=['expected_exit'=>23,'actual_exit'=>$status,'pass'=>$status===23];
}
foreach(['normal'=>false,'ordinary'=>true] as $name=>$testing) {
    $env=$normal;if($testing) { $env['CACHE_STORE']='array'; }
    $code='require "docs/symphony/w04-native-final/guard.php"; w04Env(false,'.($testing?'true':'false').');';
    $p=proc_open([PHP_BINARY,'-r',$code],[0=>['file','/dev/null','r'],1=>['file','/dev/null','w'],2=>['file','/dev/null','w']],$pipes,w04fRoot(),$env);
    $status=proc_close($p);$cases[$name]=['expected_exit'=>0,'actual_exit'=>$status,'pass'=>$status===0];
}
$pass=!in_array(false,array_column($cases,'pass'),true);
w04Save(w04fEvidence('intake').'/own-guard-check.json',['status'=>$pass?'PASS':'FAIL','cases'=>$cases,'case_count'=>count($cases),'database_access'=>false]);
echo 'Own guard '.($pass?'PASS':'FAIL').' cases='.count($cases).PHP_EOL;exit($pass?0:1);
