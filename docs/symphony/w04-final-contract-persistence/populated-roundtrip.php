<?php
require __DIR__.'/guard.php';$label='populated-recovery';w04fRequireSnapshot($label);
$p=w04Pdo();w04fExclusive($p);$before=w04Measure($p);$events=(int)$p->query('SELECT COUNT(*) FROM g7_travel_lab_inquiry_events')->fetchColumn();$inq=(int)$p->query('SELECT COUNT(*) FROM g7_travel_lab_inquiries')->fetchColumn();$p=null;
if($events<3||$inq<1){throw new RuntimeException('Populated installed inquiry baseline required.');}
$log=w04fPrivate('private').'/populated';
$down=w04fChild([PHP_BINARY,__DIR__.'/guarded-artisan.php','migrate:rollback','--database=mysql','--step=1','--path=modules/_bundled/raonslab-travel_lab/database/migrations','--force','--no-interaction'],$log.'-down.log');
$p=w04Pdo();$mid=w04Measure($p);$p=null;
$up=w04fChild([PHP_BINARY,__DIR__.'/guarded-artisan.php','migrate','--database=mysql','--path=modules/_bundled/raonslab-travel_lab/database/migrations','--force','--no-interaction'],$log.'-up.log');
$p=w04Pdo();$after=w04Measure($p);$eventsAfter=(int)$p->query('SELECT COUNT(*) FROM g7_travel_lab_inquiry_events')->fetchColumn();$p=null;
$check=$down===0&&$up===0&&!isset($mid['tables']['g7_travel_lab_inquiry_events'])&&isset($after['tables']['g7_travel_lab_inquiry_events'])&&$eventsAfter===0;
w04Save(w04fEvidence('populated-recovery').'/roundtrip.json',['status'=>$check?'PASS_BOUNDED':'FAIL','down_exit'=>$down,'up_exit'=>$up,'initial_inquiries'=>$inq,'initial_events'=>$events,'after_replay_events'=>$eventsAfter,'before_digest'=>w04fDigest($before),'mid_digest'=>w04fDigest($mid),'after_digest'=>w04fDigest($after),'before_tables'=>$before['table_count'],'mid_tables'=>$mid['table_count'],'after_tables'=>$after['table_count'],'destructive_down_drops_events_and_snapshot'=>true,'migration_alone_preserves_old_event_data'=>false,'whole_validated_backup_recovery'=>'REQUIRED_NEXT']);
echo ($check?'PASS':'FAIL').' populated native down/up; full original data recovery required next'.PHP_EOL;exit($check?0:1);
