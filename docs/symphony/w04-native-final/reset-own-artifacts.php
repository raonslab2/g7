<?php
/** 정확한 DB 복구 이후, 이 Request가 설치한 disposable copies/cache만 private safety로 이동한다. */
require __DIR__.'/guard.php';
$p=w04Pdo();$exclusive=w04fExclusive($p);$m=w04Measure($p);$p=null;
if(w04fDigest($m)!==W04F_BASELINE_DIGEST) { throw new RuntimeException('Artifact cleanup requires exact original baseline.'); }
$dir=w04fPrivate('artifacts-'.bin2hex(random_bytes(4)));mkdir($dir,0700);
$records=[];
foreach(['modules'=>['sirsoft-board','sirsoft-page','sirsoft-ecommerce','raonslab-travel_lab'],'templates'=>['sirsoft-admin_basic','raonslab-travel_lab']] as $type=>$ids) {
 foreach($ids as $id) {
  $path=w04fRoot().'/'.$type.'/'.$id;if(!is_dir($path)) { continue; }
  if(is_link($path)) { throw new RuntimeException('Own installed symlink rejected.'); }
  $manifest=$type==='modules'?'module.json':'template.json';
  if(hash_file('sha256',$path.'/'.$manifest)!==hash_file('sha256',w04fRoot().'/'.$type.'/_bundled/'.$id.'/'.$manifest)) { throw new RuntimeException('Foreign installed artifact rejected.'); }
  $to=$dir.'/'.$type.'-'.$id;if(!rename($path,$to)) { throw new RuntimeException('Own artifact preservation failed.'); }
  $records[]=['path'=>$type.'/'.$id,'action'=>'moved own generated installation to private safety'];
 }
}
foreach(['autoload-extensions.php','hooks.php','routes-v7.php','config.php'] as $name) {
 $path=w04fRoot().'/bootstrap/cache/'.$name;
 if(is_link($path)) { throw new RuntimeException('Generated cache symlink rejected.'); }
 if(is_file($path)) { rename($path,$dir.'/'.$name);$records[]=['path'=>'bootstrap/cache/'.$name,'action'=>'preserved own generated lifecycle cache']; }
}
w04Save(w04fEvidence('intake').'/artifacts-'.basename($dir).'.json',['exclusive'=>$exclusive,'baseline_digest'=>w04fDigest($m),'actions'=>$records]);
echo 'Own partial native artifacts preserved after exact baseline recovery.'.PHP_EOL;
