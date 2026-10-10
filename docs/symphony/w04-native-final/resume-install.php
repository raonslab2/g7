<?php
/** 단일 install suite의 app boot 전 process metadata preflight 실패에 한정한 1회 재개. */
require __DIR__.'/guard.php';
w04fRequireSnapshot('original');
$private=w04fPrivate('install-logs');$evidence=w04fEvidence('install');
$failed=json_decode(file_get_contents(w04fPrivate('private').'/last-native-preflight-failure.json'),true,flags:JSON_THROW_ON_ERROR);
if($failed['class']!=='RuntimeException'||($failed['trace'][0]['function']??'')!=='w04fForeignHandles') { throw new RuntimeException('Not the verified pre-application guard failure.'); }
$marker=w04fPrivate('original').'/resume-preflight-own-pdo-15';
$fh=fopen($marker,'x');if(!$fh) { throw new RuntimeException('Preflight resume already consumed.'); }fwrite($fh,'Single bounded resume'.PHP_EOL);fclose($fh);
$p=w04Pdo();$exclusive=w04fExclusive($p);$before=w04Measure($p);
foreach(['sirsoft-board','sirsoft-page','sirsoft-ecommerce','raonslab-travel_lab'] as $id) {
 $s=$p->prepare('SELECT status,vendor_mode FROM g7_modules WHERE identifier=?');$s->execute([$id]);
 if($s->fetch(PDO::FETCH_ASSOC)!==['status'=>'active','vendor_mode'=>'bundled']) { throw new RuntimeException('Installed module resume state differs.'); }
}
$states=$p->query('SELECT identifier,status FROM g7_templates ORDER BY identifier')->fetchAll(PDO::FETCH_KEY_PAIR);
if($states!==['raonslab-travel_lab'=>'inactive','sirsoft-admin_basic'=>'active']) { throw new RuntimeException('Installed native template resume state differs.'); }
$s=null;$p=null;w04fQuiesce();
w04Save($evidence.'/preflight-resume-intake.json',['classification'=>'before app/bootstrap/native command; repeated safe process scan0','exclusive'=>$exclusive,'before_inventory'=>$before,'failed_command'=>'template:activate raonslab-travel_lab','single_resume'=>true,'prior_resume_pre_native_failure'=>'retained PDOStatement kept own connection; no native command executed; corrected reference release']);
foreach([
 ['template:activate','raonslab-travel_lab','--no-interaction'],
 ['db:seed','--class=Modules\\Sirsoft\\Ecommerce\\Database\\Seeders\\ShippingTypeSeeder','--force','--no-interaction'],
 ['module:seed','raonslab-travel_lab','--sample','--no-interaction'],
 ['raonslab-travel_lab:support-provision','--lab-confirm','--no-interaction'],
] as $i=>$args) {
 if($i===1) { $p=w04Pdo();if((int)$p->query('SELECT COUNT(*) FROM g7_ecommerce_shipping_types')->fetchColumn()!==0) { throw new RuntimeException('Empty native reference branch failed.'); }$p=null; }
 w04fQuiesce();$log=$private.'/resume-'.($i+1).'.log';$status=w04fChild([PHP_BINARY,__DIR__.'/guarded-artisan.php',...$args],$log);
 copy($log.'.result.json',$evidence.'/resume-'.($i+1).'.json');
 if($status!==0) { throw new RuntimeException('Native resumed command failed.'); }
}
w04Save($evidence.'/shipping-empty-branch.json',['before_rows'=>0,'native_seeder'=>'ShippingTypeSeeder','whole_ecommerce_sample'=>false]);
w04fCompleted(true);$m=w04Measure(w04Pdo());
w04Save($evidence.'/installed.json',['table_count'=>$m['table_count'],'row_count'=>$m['row_count'],'digest'=>w04fDigest($m),'native_resume_complete'=>true]);
echo 'Native installation complete '.$m['table_count'].' tables/'.$m['row_count'].' rows'.PHP_EOL;
