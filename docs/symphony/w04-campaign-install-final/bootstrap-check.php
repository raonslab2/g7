<?php
/** Actual published helper, safe input fixtures; all live SQL only existing TEST PDO. */
require __DIR__.'/guard.php';
require w04fRoot().'/scripts/travel-lab/migration-bootstrap.php';
$env=w04Env(); $pdo=w04Pdo(); $exclusive=w04fExclusive($pdo); $before=w04Measure($pdo);
$mode=$argv[1]??'nonempty'; $results=[];
$check=static function(string $label,array $fixture,string $root,bool $reject) use($pdo,&$results): void {
    $denied=false; $value=null;
    try { $value=travelLabInitialMigrationEnvironment($pdo,$fixture,$root); } catch(RuntimeException $e) { $denied=true; }
    if($denied!==$reject || (!$reject && $value!==$fixture)) { throw new RuntimeException('Bootstrap fixture failed: '.$label); }
    $results[$label]=['status'=>'PASS','rejected'=>$denied,'unchanged_environment'=>$reject?null:$value===$fixture];
};
$check('wrong-schema',array_replace($env,['DB_WRITE_DATABASE'=>'forbidden_fixture']),w04fRoot(),true);
$check('wrong-account',array_replace($env,['DB_WRITE_USERNAME'=>'forbidden_fixture']),w04fRoot(),true);
foreach(['DB_URL'=>'mysql://fixture','CACHE_STORE'=>'array','G7_ENV_PRIORITY'=>'false','DB_CACHE_LOCK_CONNECTION'=>'forbidden_fixture'] as $key=>$value) {
    $check('wrong-'.$key,array_replace($env,[$key=>$value]),w04fRoot(),true);
}
if($mode==='empty') {
    if($before['table_count']!==0 || $env['INSTALLER_COMPLETED']!=='false') { throw new RuntimeException('Genuine empty fixture required.'); }
    $check('completed-empty',array_replace($env,['INSTALLER_COMPLETED'=>'true']),w04fRoot(),true);
    $temp=w04fPrivate('bootstrap-fixtures'); if(file_exists($temp)) { throw new RuntimeException('Existing fixture refused.'); } mkdir($temp,0700);
    try {
        foreach(['modules','templates','plugins'] as $type) {
            mkdir($temp.'/'.$type,0700); mkdir($temp.'/'.$type.'/owned-fixture',0700);
            try { $check('installed-'.$type,$env,$temp,true); } finally { rmdir($temp.'/'.$type.'/owned-fixture'); rmdir($temp.'/'.$type); }
        }
    } finally { rmdir($temp); }
    $initial=travelLabInitialMigrationEnvironment($pdo,$env,w04fRoot());
    if($initial!==array_replace($env,['CACHE_STORE'=>'array'])) { throw new RuntimeException('Initial process-only cache diff failed.'); }
    $results['genuine-empty']=['status'=>'PASS','only_changed_key'=>'CACHE_STORE','process_value'=>'array','persisted_value'=>w04Env()['CACHE_STORE']];
    // Native TEST-only partial-schema fixture: no cache tables, input must remain database.
    $pdo->exec('CREATE TABLE w04c_partial_fixture (id INT NULL)');
    try { $check('partial-no-cache',$env,w04fRoot(),false); } finally { $pdo->exec('DROP TABLE w04c_partial_fixture'); }
} else {
    if($before['table_count']===0) { throw new RuntimeException('Nonempty fixture required.'); }
    $check('nonempty',$env,w04fRoot(),false);
}
$after=w04Measure($pdo);
if($before!==$after) { throw new RuntimeException('Bootstrap guard fixtures changed measured schema.'); }
w04Save(w04fEvidence('intake').'/bootstrap-'.$mode.'.json',['results'=>$results,'count'=>count($results),'whole_inventory_unchanged'=>true,'before_digest'=>w04fDigest($before),'after_digest'=>w04fDigest($after),'exclusive'=>$exclusive,'published_helper_sha256'=>hash_file('sha256',w04fRoot().'/scripts/travel-lab/migration-bootstrap.php')]);
echo 'PASS published bootstrap '.$mode.' checks='.count($results).PHP_EOL;
