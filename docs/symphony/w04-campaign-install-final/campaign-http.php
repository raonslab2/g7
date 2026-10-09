<?php
/** 자체 installed Kernel/native command/API 검증. 제품 라우트/프로바이더를 수동 등록하지 않는다. */
require __DIR__.'/runtime-bootstrap.php';
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Permission;
use Modules\Sirsoft\Page\Models\Page;
use Modules\Sirsoft\Page\Models\PageVersion;
$label=$argv[1]??'install2';w04fRequireSnapshot($label);
$evidence=w04fEvidence('campaign');$records=[];$checks=[];$stage='boot';
function checkCampaign(bool $ok,string $name): void { global $checks; $checks[$name]=$ok;if(!$ok){throw new RuntimeException($name);} }
try {
 $app=w04App();$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);$env=w04Env();
 $request=function($method,$path,$body=[],$token=null,$expected=200)use($app,$kernel,&$records,$evidence){
  $app['session']->driver()->flush();Auth::shouldUse('web');Auth::forgetGuards();
  $server=['HTTP_ACCEPT'=>'application/json','CONTENT_TYPE'=>'application/json','REMOTE_ADDR'=>'127.0.0.1'];if($token!==null){$server['HTTP_AUTHORIZATION']='Bearer '.$token;}
  $input=Illuminate\Http\Request::create($path,$method,[],[],[],$server,$body===[]?null:json_encode($body,JSON_THROW_ON_ERROR));$response=$kernel->handle($input);$kernel->terminate($input,$response);
  $records[]=['method'=>$method,'path'=>$path,'status'=>$response->getStatusCode(),'expected'=>$expected,'body_sha256'=>hash('sha256',$response->getContent())];w04Save($evidence.'/http-statuses.json',['requests'=>$records]);
  checkCampaign($response->getStatusCode()===$expected,'HTTP '.$method.' '.$path.' expected '.$expected.' got '.$response->getStatusCode());
  return json_decode($response->getContent(),true,flags:JSON_THROW_ON_ERROR);
 };
 $base='/api/modules/raonslab-travel_lab/campaigns';$native='/api/modules/sirsoft-page/admin/pages';
 $slugs=['travel-lab-campaign-autumn-escape','travel-lab-campaign-weekend-reset'];
 $super=User::where('email',$env['INSTALLER_ADMIN_EMAIL'])->firstOrFail();
 $adminToken=$request('POST','/api/auth/admin/login',['email'=>$env['INSTALLER_ADMIN_EMAIL'],'password'=>$env['INSTALLER_ADMIN_PASSWORD']])['data']['token'];
 checkCampaign(Page::whereIn('slug',$slugs)->count()===0,'no default campaign Pages');
 checkCampaign($request('GET',$base)['data']['items']===[],'empty campaign list');
 foreach($slugs as $slug){$request('GET',$base.'/'.$slug,[],null,404);}
 $stage='guards';
 // 明示 actor は既存の native installer actor。プロビジョニングのための role/account 追加は無い。
 $command=function(array $args,int $expected)use($super,$evidence){
  $before=w04Measure(w04Pdo());Auth::forgetGuards();Auth::setUser($super);$guard=Auth::guard();$prior=$guard->user();
  $exit=Artisan::call('raonslab-travel_lab:campaigns-provision',$args);$output=Artisan::output();
  checkCampaign($exit===$expected,'provision exit '.$expected);
  checkCampaign($guard->user()===$prior,'Auth exact actor restoration');
  $after=w04Measure(w04Pdo());$pageTables=['g7_pages','g7_page_versions'];$same=true;foreach($pageTables as $t){$same=$same&&$before['tables'][$t]===$after['tables'][$t];}
  if($expected!==0){checkCampaign($same,'negative command zero Page/version writes');}
  return ['exit'=>$exit,'output_sha256'=>hash('sha256',$output),'page_version_rows_identical'=>$same];
 };
 $guardResults=[];
 config(['raonslab-travel_lab.campaigns.lab_provisioning'=>false]);$guardResults['flag-off']=$command(['--lab-confirm'=>true,'--actor'=>(string)$super->id],1);
 config(['raonslab-travel_lab.campaigns.lab_provisioning'=>true]);$guardResults['confirm-off']=$command(['--actor'=>(string)$super->id],1);
 $guardResults['actor-missing']=$command(['--lab-confirm'=>true],1);
 $guardResults['actor-unknown']=$command(['--lab-confirm'=>true,'--actor'=>'2147483647'],1);
 $member=User::where('name','W04 synthetic owner')->firstOrFail();$guardResults['actor-no-permission']=$command(['--lab-confirm'=>true,'--actor'=>(string)$member->id],1);
 $tokensBefore=DB::table('personal_access_tokens')->count();
 $guardResults['first']=$command(['--lab-confirm'=>true,'--actor'=>(string)$super->id],0);
 checkCampaign(Page::whereIn('slug',$slugs)->count()===2,'two fixed native slots created');
 $original=Page::whereIn('slug',$slugs)->orderBy('slug')->get()->map(fn($p)=>$p->getAttributes())->all();
 $guardResults['second']=$command(['--lab-confirm'=>true,'--actor'=>(string)$super->id],0);
 checkCampaign($original===Page::whereIn('slug',$slugs)->orderBy('slug')->get()->map(fn($p)=>$p->getAttributes())->all(),'rerun exact IDs/body/version/publication');
 checkCampaign(DB::table('personal_access_tokens')->count()===$tokensBefore,'provision no token creation');
 Auth::forgetGuards();$noActor=Auth::guard();$noActor->forgetUser();app(Modules\Raonslab\TravelLab\Services\TravelCampaignProvisioner::class)->provision(true,$super->id);checkCampaign(!$noActor->hasUser(),'Auth empty guard restored');
 w04Save($evidence.'/provision.json',$guardResults);
 $stage='projection';
 $list=$request('GET',$base);checkCampaign(array_column($list['data']['items'],'slug')===$slugs,'registry list order');
 $listKeys=['slug','kind','theme','title','excerpt','current_version','published_at','path','catalog_query','art_variant'];
 foreach($list['data']['items'] as $item){checkCampaign(array_keys($item)===$listKeys,'minimal list projection '.$item['slug']);}
 foreach($slugs as $slug){$detail=$request('GET',$base.'/'.$slug);checkCampaign(array_keys($detail['data'])===[...$listKeys,'content','content_mode','updated_at'],'minimal detail projection '.$slug);checkCampaign($detail['data']['current_version']===1,'initial version1 '.$slug);$request('GET','/api/modules/raonslab-travel_lab/catalog?'.http_build_query($detail['data']['catalog_query']));}
 foreach(['about','x-travel-lab-campaign-decoy','travel-lab-campaign-unknown'] as $slug){$request('GET',$base.'/'.$slug,[],$adminToken,404);}
 $request('GET',$base.'?preview=1',[],$adminToken,422);
 $stage='native revisions/cache';
 $page=Page::where('slug',$slugs[0])->firstOrFail();$id=$page->id;$versionId=PageVersion::where('page_id',$id)->where('version',1)->value('id');
 $cache=app(App\Seo\Contracts\SeoCacheManagerInterface::class);checkCampaign($cache instanceof App\Seo\SeoCacheManager,'native physical cache implementation');
 $cacheRecords=[];
 $prime=function(string $event)use($cache,$slugs,&$cacheRecords){
  $urls=[['/travel','travel/home'],['/travel/campaigns','travel/campaigns'],['/travel/campaigns/'.$slugs[0],'travel/campaign_detail'],['/page/'.$slugs[0],'page/show']];
  foreach($urls as [$path,$layout]){$url=config('app.url').$path;$cache->putWithLayout($url,'ko','synthetic-'.$event,$layout);checkCampaign($cache->get($url,'ko')==='synthetic-'.$event,'physical cache primed '.$event.' '.$layout);}
  $cacheRecords[$event]=['before_native_cache_rows'=>DB::table('cache')->count(),'after'=>[]];return $urls;
 };
 $verify=function(string $event,array $urls)use($cache,&$cacheRecords){foreach($urls as [$path,$layout]){$gone=$cache->get(config('app.url').$path,'ko')===null;checkCampaign($gone,'physical cache invalidated '.$event.' '.$layout);$cacheRecords[$event]['after'][$layout]=$gone;}};
 $urls=$prime('update');
 $edit=['title'=>['ko'=>'검증자 합성 편집','en'=>'Independent synthetic edit'],'content'=>['ko'=>"합성 본문\n두번째 줄 <script>escaped text</script>",'en'=>'Synthetic edited body'],'content_mode'=>'text'];
 $request('PUT',$native.'/'.$id,$edit,$adminToken);$verify('update',$urls);
 checkCampaign(Page::findOrFail($id)->current_version===2 && PageVersion::where('page_id',$id)->count()===2,'native update version2/snapshot2');
 $urls=$prime('unpublish');$request('PATCH',$native.'/'.$id.'/publish',['published'=>false],$adminToken);$verify('unpublish',$urls);
 checkCampaign(Page::findOrFail($id)->current_version===2,'toggle preserves content version');
 foreach([null,$adminToken] as $token){$request('GET',$base.'/'.$slugs[0],[],$token,404);checkCampaign(count($request('GET',$base,[],$token)['data']['items'])===1,'draft excluded list for guest/admin');}
 $request('GET','/api/modules/sirsoft-page/pages/'.$slugs[0],[],$adminToken); // Native admin preview remains native.
 checkCampaign(DB::table('sitemap_urls')->where('resource_type','page')->where('resource_id',(string)$id)->count()===0,'draft deindexed sitemap');
 $edited=Page::whereIn('slug',$slugs)->orderBy('slug')->get()->map(fn($p)=>$p->getAttributes())->all();$command(['--lab-confirm'=>true,'--actor'=>(string)$super->id],0);
 checkCampaign($edited===Page::whereIn('slug',$slugs)->orderBy('slug')->get()->map(fn($p)=>$p->getAttributes())->all(),'operator edits and draft preserved exactly');
 $urls=$prime('restore');$request('POST',$native.'/'.$id.'/versions/'.$versionId.'/restore',[],$adminToken);$verify('restore',$urls);
 checkCampaign(Page::findOrFail($id)->current_version===3 && !Page::findOrFail($id)->published,'native restore creates version3 and keeps draft');
 $urls=$prime('publish');$request('PATCH',$native.'/'.$id.'/publish',['published'=>true],$adminToken);$verify('publish',$urls);
 checkCampaign($request('GET',$base.'/'.$slugs[0])['data']['content']===$original[0]['content'] /* raw attrs JSON differs; compare localized below */ || Page::findOrFail($id)->content===json_decode($original[0]['content'],true),'native restore exact original content');
 checkCampaign(DB::table('sitemap_urls')->where('resource_type','page')->where('resource_id',(string)$id)->count()>0,'published indexed sitemap');
 checkCampaign(DB::table('activity_logs')->where('loggable_type',Page::class)->where('loggable_id',$id)->count()>=5,'native create/update/publish/restore audit');
 w04Save($evidence.'/cache.json',['status'=>'PASS','implementation'=>get_class($cache),'records'=>$cacheRecords,'recorder_mock'=>false,'static_html_browser_privacy'=>'NOT_RUN']);
 $stage='permissions';
 $request('GET',$native,[],null,401);
 Auth::forgetGuards();Auth::setUser($super);
 $permissionRows=Permission::whereIn('identifier',['sirsoft-page.pages.read','sirsoft-page.pages.update'])->get();
 $role=app(App\Services\RoleService::class)->createRole(['identifier'=>'w04c-self-reader','name'=>['ko'=>'검증용 자체 읽기','en'=>'Synthetic verifier self reader'],'is_active'=>true,'permissions'=>$permissionRows->map(fn($p)=>['id'=>$p->id,'scope_type'=>'self'])->all()]);
 $pw=bin2hex(random_bytes(18));$scoped=app(App\Services\UserService::class)->createUser(['name'=>'W04C synthetic scope','email'=>'w04c-scope-'.bin2hex(random_bytes(4)).'@example.invalid','password'=>$pw,'email_verified_at'=>now(),'language'=>'ko','role_ids'=>[$role->id]]);
 $token=$request('POST','/api/auth/admin/login',['email'=>$scoped->email,'password'=>$pw])['data']['token'];
 checkCampaign(count($request('GET',$native,[],$token)['data']['data'])===0,'self scope excludes foreign Pages');
 $request('GET',$native.'/'.$id,[],$token,403);$request('PATCH',$native.'/'.$id.'/publish',['published'=>false],$token,403);
 $request('POST',$native,['slug'=>'w04c-unauthorized','title'=>['ko'=>'합성','en'=>'Synthetic']],$token,403);
 $request('GET',$base.'/'.$slugs[0],[],$token);
 $owner=User::where('name','W04 synthetic owner')->firstOrFail();Auth::forgetGuards();$memberToken=$owner->createToken('w04c-negative')->plainTextToken;
 $request('GET',$native,[],$memberToken,403);
 foreach(['ecommerce_orders','ecommerce_order_payments','notification_logs'] as $t){checkCampaign(DB::table($t)->count()===0,'zero '.$t);}
 $origins=[];foreach([Modules\Sirsoft\Page\Services\PageService::class,Modules\Raonslab\TravelLab\Services\CampaignService::class,Modules\Raonslab\TravelLab\Listeners\InvalidateTravelCampaignSeoCache::class,Modules\Sirsoft\Page\Listeners\SeoPageCacheListener::class] as $class){$file=(new ReflectionClass($class))->getFileName();checkCampaign(!str_contains($file,'/_bundled/'),'installed origin '.$class);$relative=str_replace(base_path().'/modules/','',$file);checkCampaign(hash_file('sha256',$file)===hash_file('sha256',base_path('modules/_bundled/'.$relative)),'source bytes '.$class);$origins[$class]=['path'=>'modules/'.$relative,'sha256'=>hash_file('sha256',$file)];}
 w04Save($evidence.'/result.json',['status'=>'PASS_BOUNDED','stage'=>$stage,'requests'=>count($records),'checks'=>$checks,'origins'=>$origins,'native_default_pages'=>6,'zero_default_pages_contract'=>'FAIL','browser'=>'NOT_RUN']);
 DB::purge();echo 'PASS bounded campaign installed native checks='.count($checks).' HTTP='.count($records).PHP_EOL;
} catch(Throwable $e){file_put_contents(w04fPrivate('private').'/campaign-error.txt',$e->getMessage()."\n".$e->getTraceAsString());w04Save($evidence.'/failure.json',['status'=>'FAIL','stage'=>$stage,'safe_message'=>$e instanceof RuntimeException?$e->getMessage():get_class($e),'checks'=>$checks,'requests'=>count($records)]);fwrite(STDERR,'FAIL campaign stage='.$stage.' '.get_class($e).PHP_EOL);exit(1);}
