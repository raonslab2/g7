<?php
require __DIR__.'/runtime-bootstrap.php';
$app=w04App();$blocked=[];
foreach(['payment','booking','mail','sms','provider'] as $purpose) {
    try { Illuminate\Support\Facades\Http::timeout(1)->get('https://'.$purpose.'.example.invalid/preflight');$blocked[$purpose]=false; }
    catch(RuntimeException $e) { $blocked[$purpose]=$e->getMessage()==='Offline native TEST blocks provider HTTP.'; }
}
if(in_array(false,$blocked,true)||Illuminate\Support\Facades\DB::table('plugins')->count()!==0) { throw new RuntimeException('Offline boundary failed.'); }
w04Save(w04fEvidence('install').'/offline-installed.json',['status'=>'PASS','blocked_before_send'=>$blocked,'installed_plugins'=>0,'mail'=>config('mail.default'),'queue'=>config('queue.default'),'storage'=>config('filesystems.default'),'foreign_proxy'=>'loopback discard port9','composer_network_disabled'=>getenv('COMPOSER_DISABLE_NETWORK')==='1']);
echo 'PASS offline native adapters/provider preflight.'.PHP_EOL;
