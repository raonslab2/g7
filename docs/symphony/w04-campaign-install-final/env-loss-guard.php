<?php
/** Own config loss only: fail closed before any native app can open an unscoped database. */
require __DIR__.'/guard.php';$env=w04Env();$p=w04Pdo();$exclusive=w04fExclusive($p);$before=w04Measure($p);$p=null;
$path=w04fRoot().'/.env';$bytes=file_get_contents($path);$backup=w04fPrivate('private').'/env-loss-original';file_put_contents($backup,$bytes);chmod($backup,0600);$denied=false;
try {if(!unlink($path)){throw new RuntimeException('Own env removal failed.');}try{w04Env();}catch(RuntimeException $e){$denied=true;}}
finally {file_put_contents($path,$bytes);chmod($path,0600);}
if(file_get_contents($path)!==$bytes || !$denied){throw new RuntimeException('Own missing-env guard/restoration failed.');}
$after=w04Measure(w04Pdo());if($before!==$after){throw new RuntimeException('Env-loss guard changed TEST.');}
w04Save(w04fEvidence('service').'/env-loss-guard.json',['status'=>'PASS_GUARD_ONLY','native_product_recovery'=>'NOT_RUN','own_env_missing_guard_rejected'=>true,'native_unscoped_app_booted'=>false,'own_env_restored_exact_bytes'=>true,'mode'=>'0600','own_env_sha256'=>hash('sha256',$bytes),'whole_test_unchanged'=>true,'whole_test_digest'=>w04fDigest($after),'exclusive'=>$exclusive]);echo 'PASS own missing-env fail-closed guard; native product recovery NOT_RUN'.PHP_EOL;
