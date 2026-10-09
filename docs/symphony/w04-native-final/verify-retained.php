<?php
// 중단된 복원 이후 재파괴 없이 원본 전체 일치와 독점 상태를 재검증한다.
require __DIR__.'/guard.php';
$label=$argv[1]??'';
$lock=fopen(w04fRoot().'/storage/framework/testing/w04f-exclusive.lock','c');
if(!flock($lock,LOCK_EX|LOCK_NB)){throw new RuntimeException('Own runner active.');}
$private=w04fRequireSnapshot($label);
if(!is_file($private.'/BLOCKED')){throw new RuntimeException('Pending recovery required.');}
$before=json_decode(file_get_contents($private.'/before.json'),true,flags:JSON_THROW_ON_ERROR);
$p=w04Pdo();$exclusive=w04fExclusive($p);$after=w04Measure($p);$p=null;
if($before!==$after || w04fDigest($after)!==W04F_BASELINE_DIGEST){throw new RuntimeException('Retained original equality not verified.');}
w04fQuiesce();
$r=['label'=>$label,'status'=>'PASS','method'=>'new guarded process full inventory comparison; no repeated wipe/import','exact_tables_rows_ddl'=>true,'equals_original_baseline'=>true,'digest'=>w04fDigest($after),'table_count'=>$after['table_count'],'row_count'=>$after['row_count'],'exclusive'=>$exclusive,'prior_restoration_failure_preserved'=>true,'utc'=>gmdate('c')];
w04Save(w04fEvidence($label).'/independent-recovery.json',$r);
w04fPhase($private,'verified_exact_restore_backup_retained',$r);
unlink($private.'/BLOCKED');
$progress=w04fEvidence('regressions').'/progress.json';$all=json_decode(file_get_contents($progress),true,flags:JSON_THROW_ON_ERROR);
$key=substr($label,4);$native=json_decode(file_get_contents($private.'/native.log.result.json'),true,flags:JSON_THROW_ON_ERROR);
$all[$key]['phpunit_exit']=$native['exit'];$all[$key]['initial_restore_blocked']=true;$all[$key]['restored_exact']=true;$all[$key]['restored_baseline']=true;$all[$key]['restored_tables']=$after['table_count'];$all[$key]['restored_rows']=$after['row_count'];$all[$key]['independent_remeasure_digest']=$r['digest'];
w04Save($progress,$all);
echo json_encode($r)."\n";
