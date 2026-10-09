<?php
// 모든 destructive handle 완료 후 원본 equality와 연결/프로세스0을 확인하고 TEST lease를 해제한다.
require __DIR__.'/guard.php';
$lock=fopen(w04fRoot().'/storage/framework/testing/w04f-exclusive.lock','c');
if(!flock($lock,LOCK_EX|LOCK_NB)){throw new RuntimeException('Own runner active.');}
$original=w04fRequireSnapshot('original');
foreach(glob(w04fRoot().'/storage/framework/testing/w04f-*/BLOCKED') as $blocked){throw new RuntimeException('Pending own recovery remains.');}
$before=json_decode(file_get_contents($original.'/before.json'),true,flags:JSON_THROW_ON_ERROR);
$p=w04Pdo();$exclusive=w04fExclusive($p);$after=w04Measure($p);$p=null;
if($before!==$after || w04fDigest($after)!==W04F_BASELINE_DIGEST){throw new RuntimeException('Final original equality failed.');}
$quiet=w04fQuiesce();
$process=proc_open(['sudo','-n','python3','-I',__DIR__.'/own-process-check.py',(string)getmypid()],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,w04fRoot());
$out=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
$own=json_decode($out,true,flags:JSON_THROW_ON_ERROR);
if($exit!==0 || $own['count']!==0 || w04fOwnServerAlive()!==[] || w04fTestIds()!==[]){throw new RuntimeException('Release process/connection proof failed.');}
$port=@fsockopen('127.0.0.1',18874,$errno,$error,0.5);
if($port){fclose($port);throw new RuntimeException('Own HTTP listener remains.');}
$backups=[];
foreach(glob(w04fRoot().'/storage/framework/testing/w04f-*/manifest.json') as $f){
 $m=json_decode(file_get_contents($f),true,flags:JSON_THROW_ON_ERROR);$dir=dirname($f);
 if(!isset($m['dump_sha256'])){continue;}
 w04fRequireSnapshot(substr(basename($dir),5));
 $backups[]=['label'=>$m['label'],'original_sha256'=>$m['dump_sha256'],'original_retained'=>true];
 foreach(glob($dir.'/safety-*-manifest.json') as $s){
  $sm=json_decode(file_get_contents($s),true,flags:JSON_THROW_ON_ERROR);$sql=substr($s,0,-strlen('-manifest.json')).'.sql';
  if($sm['exit']!==0 || !$sm['dump_trailer'] || (fileperms($sql)&0777)!==0600 || hash_file('sha256',$sql)!==$sm['sha256']){throw new RuntimeException('Retained safety integrity failed.');}
  $backups[]=['label'=>basename($dir).'/'.basename($sql),'safety_sha256'=>$sm['sha256'],'safety_retained'=>true];
 }
}
$rows=[];$ddl=[];foreach($after['tables'] as $t=>$v){$rows[$t]=['rows'=>$v['rows'],'sha256'=>$v['row_sha256']];$ddl[$t]=$v['ddl_sha256'];}
$r=['status'=>'PASS','lease'=>'TEST RELEASE','request'=>'req_caef46f3473043048b5b4bc2ec41eaea','release_utc'=>gmdate('c'),'pid'=>getmypid(),'cwd'=>getcwd(),'argv'=>$_SERVER['argv'],
 'tested_product_sha'=>'fa5523175ac494cfbd13bbf89bf06b3ec91835a6','tested_product_tree'=>'fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c',
 'tables'=>$after['table_count'],'rows'=>$after['row_count'],'full_digest'=>w04fDigest($after),'row_inventory_sha256'=>w04fDigest($rows),'ddl_inventory_sha256'=>w04fDigest($ddl),'object_inventory_sha256'=>w04fDigest(['views'=>0,'triggers'=>0,'routines'=>0,'events'=>0]),
 'other_schema_objects'=>$after['other_schema_objects'],'exact_original_inventory'=>true,'test_connections_after_pdo_close'=>0,'own_native_processes_excluding_observer'=>$own,'own_http_processes'=>0,'own_http_port_closed'=>true,'exclusive'=>$exclusive,'quiesce'=>$quiet,'retained_backups'=>$backups,'original_128_639'=>'NOT_PROVEN'];
w04Save(w04fEvidence('release').'/inventory.json',$after);w04Save(w04fEvidence('release').'/result.json',$r);
w04Save(w04fPrivate('private').'/lease.json',$r);
echo json_encode(['TEST_RELEASE'=>$r['release_utc'],'tables'=>$r['tables'],'rows'=>$r['rows'],'digest'=>$r['full_digest'],'connections'=>0,'HTTP_processes'=>0])."\n";
