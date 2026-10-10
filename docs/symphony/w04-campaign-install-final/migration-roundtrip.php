<?php
/** Baseline TEST migration-only native down/up scope; no runtime mounting or product source edits. */
require __DIR__.'/guard.php';$label=$argv[1]??'migration-roundtrip';w04fRequireSnapshot($label);
$private=w04fPrivate('private');$paths=['database/migrations','modules/_bundled/sirsoft-board/database/migrations','modules/_bundled/sirsoft-page/database/migrations','modules/_bundled/sirsoft-ecommerce/database/migrations','modules/_bundled/raonslab-travel_lab/database/migrations'];
$args=array_map(fn($p)=>'--path='.$p,$paths);
$up=w04fChild([PHP_BINARY,__DIR__.'/guarded-artisan.php','migrate','--database=mysql',...$args,'--force','--no-interaction'],$private.'/roundtrip-initial.log');
if($up!==0){throw new RuntimeException('Initial native migration suite failed.');}
$p=w04Pdo();$before=w04Measure($p);$last=$p->query('SELECT migration,batch FROM g7_migrations ORDER BY batch DESC,id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);$p=null;
if($last['migration']!=='2026_10_09_900001_add_inquiry_evidence'){throw new RuntimeException('Last migration changed.');}
$down=w04fChild([PHP_BINARY,__DIR__.'/guarded-artisan.php','migrate:rollback','--database=mysql','--step=1','--path=modules/_bundled/raonslab-travel_lab/database/migrations','--force','--no-interaction'],$private.'/roundtrip-down.log');
$p=w04Pdo();$mid=w04Measure($p);$p=null;
$replay=$down===0?w04fChild([PHP_BINARY,__DIR__.'/guarded-artisan.php','migrate','--database=mysql','--path=modules/_bundled/raonslab-travel_lab/database/migrations','--force','--no-interaction'],$private.'/roundtrip-replay.log'):null;
$p=w04Pdo();$after=w04Measure($p);$p=null;
$changed=!isset($mid['tables']['g7_travel_lab_inquiry_events']);$recovered=isset($after['tables']['g7_travel_lab_inquiry_events']);
$r=['status'=>$down===0&&$replay===0&&$changed&&$recovered?'PASS_BOUNDED':'FAIL','initial_exit'=>$up,'down_exit'=>$down,'replay_exit'=>$replay,'target'=>$last['migration'],'before_tables'=>$before['table_count'],'rollback_tables'=>$mid['table_count'],'replayed_tables'=>$after['table_count'],'events_table_removed'=>$changed,'events_table_recreated'=>$recovered,'before_digest'=>w04fDigest($before),'mid_digest'=>w04fDigest($mid),'after_digest'=>w04fDigest($after),'populated_module_roundtrip'=>'NOT_RUN','forced_fallback'=>'NOT_RUN'];
w04Save(w04fEvidence($label).'/native-result.json',$r);echo $r['status'].' native explicit-path rollback/replay'.PHP_EOL;exit($r['status']==='PASS_BOUNDED'?0:1);
