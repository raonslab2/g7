<?php
/** Last travel evidence migration only; generated fixtures, guarded full TEST original restore follows. */
require __DIR__.'/guard.php';w04fRequireSnapshot($argv[1]??'install3');
$p=w04Pdo();$exclusive=w04fExclusive($p);$before=w04Measure($p);
$last=$p->query('SELECT migration,batch FROM g7_migrations ORDER BY batch DESC,id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);$p=null;
if(($last['migration']??null)!=='2026_10_09_900001_add_inquiry_evidence'){throw new RuntimeException('Last migration target changed; no rollback.');}
$log=w04fPrivate('private').'/rollback';
$down=w04fChild([PHP_BINARY,__DIR__.'/guarded-artisan.php','migrate:rollback','--database=mysql','--step=1','--force','--no-interaction'],$log.'-down.log');
$p=w04Pdo();$mid=w04Measure($p);$p=null;
$up=$down===0?w04fChild([PHP_BINARY,__DIR__.'/guarded-artisan.php','migrate','--database=mysql','--path=modules/_bundled/raonslab-travel_lab/database/migrations','--force','--no-interaction'],$log.'-up.log'):null;
$p=w04Pdo();$after=w04Measure($p);$p=null;
$r=['status'=>$down===0&&$up===0?'PASS_BOUNDED':'FAIL','target'=>$last,'down_exit'=>$down,'up_exit'=>$up,'before_digest'=>w04fDigest($before),'rollback_digest'=>w04fDigest($mid),'reapplied_digest'=>w04fDigest($after),'before_tables'=>$before['table_count'],'rollback_tables'=>$mid['table_count'],'reapplied_tables'=>$after['table_count'],'populated_generated_fixture_data_roundtrip'=>false,'original_full_baseline_restoration'=>'REQUIRED_NEXT','exclusive'=>$exclusive];w04Save(w04fEvidence('rollback').'/result.json',$r);echo $r['status'].' native last-travel-migration down/up'.PHP_EOL;exit($down===0&&$up===0?0:1);
