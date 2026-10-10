<?php
require __DIR__.'/runtime-bootstrap.php';
$app=w04App();$env=w04Env();
$super=App\Models\User::where('email',$env['INSTALLER_ADMIN_EMAIL'])->firstOrFail();
Illuminate\Support\Facades\Auth::shouldUse('web');Illuminate\Support\Facades\Auth::forgetGuards();Illuminate\Support\Facades\Auth::setUser($super);
$access=['source_sha'=>'5783e6ba124061bdfae639cdaf9b1c14a83cdf03','db'=>'req81_travel_lab_test','base_url'=>'http://127.0.0.1:18880','expires_at'=>gmdate('c',time()+7200)];
foreach(['member'=>'user','other_member'=>'user','admin'=>'admin'] as $label=>$role){
 $pw=bin2hex(random_bytes(20));$email='w04p-'.bin2hex(random_bytes(8)).'@example.invalid';
 $user=app(App\Services\UserService::class)->createUser(['name'=>'W04P synthetic '.$label,'email'=>$email,'password'=>$pw,'email_verified_at'=>now(),'language'=>'ko','role_ids'=>[App\Models\Role::where('identifier',$role)->value('id')]]);
 $access[$label]=['user_id'=>$user->id,'email'=>$email,'password'=>$pw,'bearer_token'=>''];
 if($label==='admin' && $user->is_super){throw new RuntimeException('Ordinary admin required.');}
}
w04Save(w04fPrivate('private').'/access.json',$access);
w04Save(w04fPrivate('private').'/database-access.json',['source_sha'=>$access['source_sha'],'allowed_schema'=>$access['db'],'table_prefix'=>'g7_','host'=>'127.0.0.1','port'=>3306,'username'=>'req81_travel','password'=>$env['DB_WRITE_PASSWORD']]);
// Explicit flagged provision with installed native command under installer actor.
config(['raonslab-travel_lab.campaigns.lab_provisioning'=>true]);
$exit=Illuminate\Support\Facades\Artisan::call('raonslab-travel_lab:campaigns-provision',['--lab-confirm'=>true,'--actor'=>(string)$super->id]);
if($exit!==0){throw new RuntimeException('Native explicit campaigns provision failed.');}
w04Save(w04fEvidence('fixtures').'/actors-provision.json',['actor_ids'=>array_map(fn($v)=>$v['user_id'],array_intersect_key($access,array_flip(['member','other_member','admin']))),'non_super_admin'=>true,'native_campaign_command_exit'=>$exit,'pages'=>Modules\Sirsoft\Page\Models\Page::count()]);
Illuminate\Support\Facades\DB::purge();echo 'PASS own native actors and explicit campaign provision'.PHP_EOL;
