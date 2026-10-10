<?php
// Adapted (req_caef46f3) from audited docs/symphony/w04/installed-http.php (sha256 2d3665bc...): own guard/paths only,
// plus explicit installed vendor_mode requery for all four modules. Assertions unchanged.
require __DIR__.'/guard.php';
require __DIR__.'/runtime-bootstrap.php';
require_once dirname(__DIR__,3).'/scripts/travel-lab/live-bootstrap.php';
require dirname(__DIR__,3).'/scripts/travel-lab/live-fixtures.php';
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use Modules\Raonslab\TravelLab\Models\Departure;
use Modules\Raonslab\TravelLab\Models\Inquiry;
$snapshotLabel=$argv[1]??'original';
w04fRequireSnapshot($snapshotLabel);
$records = [];
$evidence = w04fEvidence('install');
try {
    $app = w04App(); // No PHPUnit bootstrap, providers, migrations, manual routes or fake auth.
    $cache=$app['cache']->store()->getStore();
    travelLabCheck($cache instanceof Illuminate\Cache\DatabaseStore,'Installed kernel cache is not native database.');
    w04Save($evidence.'/kernel-cache-proof.json',['native_cache_class'=>get_class($cache),'app_env'=>$app->environment(),'cache_store'=>config('cache.default'),'connection'=>config('cache.stores.database.connection'),'lock_connection'=>config('cache.stores.database.lock_connection'),'table'=>config('cache.stores.database.table'),'lock_table'=>config('cache.stores.database.lock_table'),'limiter_override'=>config('cache.limiter')]);
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $moduleClass = Modules\Raonslab\TravelLab\Module::class;
    $modes = App\Models\Module::query()->orderBy('identifier')->pluck('vendor_mode','identifier')->map(fn($m)=>$m instanceof BackedEnum ? $m->value : $m)->all();
    travelLabCheck($modes === ['raonslab-travel_lab'=>'bundled','sirsoft-board'=>'bundled','sirsoft-ecommerce'=>'bundled','sirsoft-page'=>'bundled'],'Installed module vendor_mode is not exactly bundled.');
    w04Save($evidence.'/installed-vendor-modes.json',['source'=>'native App\\Models\\Module (installed app)','modes'=>$modes,'all_bundled'=>true]);
    $origin = (new ReflectionClass($moduleClass))->getFileName();
    travelLabCheck($origin === base_path('modules/raonslab-travel_lab/module.php'), 'Module not from actual installed checkout.');
    travelLabCheck(hash_file('sha256',$origin) === hash_file('sha256',base_path('modules/_bundled/raonslab-travel_lab/module.php')), 'Installed source differs.');
    $routes = [];
    foreach ($app['router']->getRoutes() as $route) {
        if (str_starts_with($route->uri(),'api/modules/raonslab-travel_lab/')) { $routes[] = ['uri'=>$route->uri(),'methods'=>$route->methods(),'name'=>$route->getName(),'middleware'=>$route->gatherMiddleware()]; }
    }
    travelLabCheck(count($routes) === 31,'Installed API count differs.');
    $groups = $app['router']->getMiddlewareGroups();
    travelLabCheck(count(array_filter($groups['api'],fn($x)=>$x === Modules\Raonslab\TravelLab\Http\Middleware\TravelCatalogConflictResponse::class)) === 1,'Conflict adapter not exactly once.');
    $registrar = (new ReflectionClass(App\Extension\HookListenerRegistrar::class))->getProperty('registered')->getValue();
    $hooks = [];
    foreach ((new $moduleClass)->getHookListeners() as $listener) {
        travelLabCheck(isset($registrar['raonslab-travel_lab::'.$listener]),'Native hook registrar entry absent.');
        $hooks[$listener] = $listener::getSubscribedHooks();
    }
    $permissions = [];
    foreach (['admin','manager','user'] as $roleName) {
        $role = Role::where('identifier',$roleName)->firstOrFail();
        $permissions[$roleName] = $role->permissions()->where('identifier','like','raonslab-travel_lab.%')->pluck('identifier')->sort()->values()->all();
    }
    travelLabCheck(in_array('raonslab-travel_lab.support.read',$permissions['admin'],true) && !in_array('raonslab-travel_lab.support.read',$permissions['manager'],true),'Default support permission boundary differs.');
    travelLabCheck(!in_array('raonslab-travel_lab.support.update',$permissions['manager'],true) && $permissions['user'] === [],'Default member/manager permissions differ.');
    $menus = App\Models\Menu::where('slug','like','travel-lab-%')->get(['slug','url'])->toArray();
    travelLabCheck(count($menus) === 3,'Three admin navigation entries missing.');
    $origins=[];
    foreach ([Modules\Raonslab\TravelLab\Providers\TravelLabServiceProvider::class, Modules\Raonslab\TravelLab\Services\InquiryService::class, Modules\Raonslab\TravelLab\Repositories\CatalogRepository::class, Modules\Raonslab\TravelLab\Listeners\ProtectTravelCommerceCatalog::class, Modules\Sirsoft\Board\Services\PostService::class, Modules\Sirsoft\Ecommerce\Services\ProductService::class] as $class) {
        $file=(new ReflectionClass($class))->getFileName();
        travelLabCheck(str_starts_with($file,base_path('modules/')) && !str_contains($file,'/_bundled/'),'Runtime class not installed source.');
        $relative=str_replace(base_path().'/modules/','',$file);
        travelLabCheck(hash_file('sha256',$file) === hash_file('sha256',base_path('modules/_bundled/'.$relative)),'Installed runtime class bytes differ.');
        $origins[$class]=['path'=>'modules/'.$relative,'sha256'=>hash_file('sha256',$file)];
    }
    foreach (['sirsoft-admin_basic','raonslab-travel_lab'] as $template) {
        $file=base_path('templates/'.$template.'/template.json');
        travelLabCheck(hash_file('sha256',$file) === hash_file('sha256',base_path('templates/_bundled/'.$template.'/template.json')),'Installed template bytes differ.');
        $origins[$template]=['path'=>'templates/'.$template.'/template.json','sha256'=>hash_file('sha256',$file)];
    }
    w04Save($evidence.'/installed-source.json',['module_origin'=>str_replace(base_path().'/','',$origin),'module_sha256'=>hash_file('sha256',$origin),'api_count'=>count($routes),'routes'=>$routes,'hooks'=>$hooks,'role_permissions'=>$permissions,'admin_menus'=>$menus,'origins'=>$origins]);
    $request = function ($method,$path,$body=[],$token=null,$expected=200) use ($kernel,&$records,$app,$evidence) {
        // Each simulated request is a separate cookie-free HTTP client. Only Bearer authenticates it.
        $app['session']->driver()->flush();
        Auth::forgetGuards();
        $server = ['HTTP_ACCEPT'=>'application/json','CONTENT_TYPE'=>'application/json','REMOTE_ADDR'=>'127.0.0.1'];
        if ($token !== null) { $server['HTTP_AUTHORIZATION']='Bearer '.$token; }
        $input = Illuminate\Http\Request::create($path,$method,[],[],[],$server,$body === [] ? null : json_encode($body,JSON_THROW_ON_ERROR));
        $response = $kernel->handle($input); $kernel->terminate($input,$response);
        $records[] = ['method'=>$method,'path'=>$path,'status'=>$response->getStatusCode(),'expected'=>$expected,'native_rate_limit'=>$response->headers->get('X-RateLimit-Limit'),'native_rate_remaining'=>$response->headers->get('X-RateLimit-Remaining')];
        w04Save($evidence.'/http-statuses.json',['requests'=>$records]);
        travelLabCheck($response->getStatusCode() === $expected,'HTTP status mismatch '.$method.' '.$path.': '.$response->getStatusCode());
        return json_decode($response->getContent(),true,flags:JSON_THROW_ON_ERROR);
    };
    $api = '/api/modules/raonslab-travel_lab';
    $env = w04Env();
    $adminLogin = $request('POST','/api/auth/admin/login',['email'=>$env['INSTALLER_ADMIN_EMAIL'],'password'=>$env['INSTALLER_ADMIN_PASSWORD']]);
    $superToken = $adminLogin['data']['token'];
    // Provision explicit non-super administrator/manager through native UserService under native actor.
    $super = User::where('email',$env['INSTALLER_ADMIN_EMAIL'])->firstOrFail();
    $run = bin2hex(random_bytes(6));
    $actors = []; $passwords = [];
    foreach (['owner'=>'user','foreign'=>'user','admin'=>'admin','manager'=>'manager'] as $label=>$roleName) {
        Auth::forgetGuards(); Auth::setUser($super);
        $passwords[$label] = bin2hex(random_bytes(18));
        $actors[$label] = app(App\Services\UserService::class)->createUser(['name'=>'W04 synthetic '.$label,'email'=>'w04f-'.$run.'-'.$label.'@example.invalid','password'=>$passwords[$label],'email_verified_at'=>now(),'language'=>'ko','role_ids'=>[Role::where('identifier',$roleName)->value('id')]]);
    }
    travelLabCheck(!$actors['admin']->is_super,'Ordinary admin unexpectedly super.');
    $tokens = [];
    foreach ($actors as $label=>$actor) {
        $response = $request('POST',in_array($label,['admin','manager'],true)?'/api/auth/admin/login':'/api/auth/login',['email'=>$actor->email,'password'=>$passwords[$label]]);
        $tokens[$label] = $response['data']['token'];
    }
    $request('GET',$api.'/cart',[],null,401);
    $request('GET',$api.'/catalog');
    $request('GET',$api.'/facets');
    $departure = DB::transaction(fn()=>travelLabDeparture($run,3));
    // Exercise actual native product/option deletion guards before accepting any inquiry.
    $products=app(Modules\Sirsoft\Ecommerce\Services\ProductService::class);
    $guardedDelete=false;
    try { $products->delete($departure->product); } catch (Modules\Sirsoft\Ecommerce\Exceptions\ProductHasOrderHistoryException $e) { $guardedDelete=true; }
    travelLabCheck($guardedDelete && $departure->product->fresh() !== null,'Native linked product delete guard failed.');
    $guardedOptions=false;
    try { $products->update($departure->product,['options'=>[]]); } catch (Illuminate\Validation\ValidationException $e) { $guardedOptions=true; }
    travelLabCheck($guardedOptions && $departure->option->fresh() !== null,'Native linked option guard failed.');
    $search = $request('GET',$api.'/catalog?q='.rawurlencode('격리 동시성'));
    travelLabCheck(in_array($departure->product_id,array_column($search['data']['data'],'id')),'Korean keyword search missing newly created native product.');
    $request('GET',$api.'/catalog/'.$departure->product_id);
    $request('GET',$api.'/catalog/'.$departure->product_id.'/departures');
    $cart = $request('POST',$api.'/cart',['departure_id'=>$departure->id,'quantity'=>2],$tokens['owner'],201);
    travelLabCheck((int)$cart['data']['totals']['final_amount'] === 24000,'Native calculated price differs.');
    $body = ['cart_ids'=>[$cart['data']['items'][0]['id']],'contact'=>['name'=>'W04 synthetic traveler'],'idempotency_key'=>'w04-'.$run];
    $created = $request('POST',$api.'/inquiries',$body,$tokens['owner'],201); $id=$created['data']['id'];
    travelLabCheck($created['data']['status'] === 'TEST_INQUIRY' && (int)$created['data']['total_amount'] === 24000 && $departure->fresh()->reserved === 2,'Inquiry snapshot/capacity failed.');
    $replay = $request('POST',$api.'/inquiries',$body,$tokens['owner']);
    travelLabCheck($replay['data']['id'] === $id && Inquiry::where('idempotency_key',$body['idempotency_key'])->count() === 1,'Idempotency failed.');
    $request('GET',$api.'/inquiries/'.$id,[],$tokens['foreign'],404);
    $request('GET',$api.'/admin/inquiries',[],$tokens['owner'],403);
    $request('PATCH',$api.'/admin/inquiries/'.$id,['status'=>'UNDER_REVIEW'],$tokens['admin']);
    $accepted = $request('PATCH',$api.'/admin/inquiries/'.$id,['status'=>'TEST_ACCEPTED'],$tokens['admin']);
    travelLabCheck($accepted['data']['status'] === 'TEST_ACCEPTED','Admin acceptance failed.');
    $request('GET',$api.'/inquiries/'.$id,[],$tokens['owner']);
    // Native installed default+sample rerun with held capacity and operator edits preserved.
    $metadata = app(Modules\Raonslab\TravelLab\Repositories\Contracts\CatalogRepositoryInterface::class)->updateMetadata($departure->product_id,['summary'=>['ko'=>'W04 운영자 수정','en'=>'W04 operator edit']]);
    DB::purge(); $pdo=w04Pdo(); $seedBefore=w04Measure($pdo); $pdo=null;
    foreach ([['module:seed','raonslab-travel_lab','--no-interaction'],['module:seed','raonslab-travel_lab','--sample','--no-interaction'],['raonslab-travel_lab:support-provision','--lab-confirm','--no-interaction']] as $i=>$cmd) {
        w04fQuiesce();
        travelLabCheck(w04fChild([PHP_BINARY,__DIR__.'/guarded-artisan.php',...$cmd],base_path('storage/framework/testing/w04f-install-logs/rerun-'.$i.'.txt')) === 0,'Native seed rerun failed.');
    }
    $seedAfter=w04Measure(w04Pdo());
    $seedComparison=w04fCompare($seedBefore,$seedAfter,['g7_cache']);
    travelLabCheck($seedComparison['all_nonallowed_tables_equal'] && $seedComparison['ddl_equal'],'Native seed/provision changed noncache rows, DDL, operator edit or held capacity.');
    w04Save($evidence.'/seed-rerun.json',['exact_all_tables_rows_ddl'=>$seedBefore===$seedAfter,'full_comparison'=>$seedComparison,'table_count'=>$seedBefore['table_count'],'held_capacity'=>2,'operator_edit'=>true]);
    $cancel = $request('POST',$api.'/inquiries/'.$id.'/cancel',[],$tokens['owner']);
    $request('POST',$api.'/inquiries/'.$id.'/cancel',[],$tokens['owner']);
    travelLabCheck($cancel['data']['status'] === 'CANCELLED' && $departure->fresh()->reserved === 0,'Capacity not returned.');
    $notices = $request('GET',$api.'/support/notices');
    $faqs = $request('GET',$api.'/support/faqs');
    travelLabCheck(count($notices['data']['data']) === 3 && count($faqs['data']['data']) === 4,'Persistent notices/FAQ missing.');
    $request('GET',$api.'/support/notices/'.$notices['data']['data'][0]['id']);
    $request('GET',$api.'/support/faqs/'.$faqs['data']['data'][0]['id']);
    $request('GET',$api.'/support/questions',[],null,401);
    $question = $request('POST',$api.'/support/questions',['title'=>'W04 synthetic private question','content'=>'Synthetic TEST question only.'],$tokens['owner'],201); $qid=$question['data']['id'];
    $request('GET',$api.'/support/questions/'.$qid,[],$tokens['foreign'],404);
    $request('PATCH',$api.'/support/questions/'.$qid,['title'=>'Foreign edit'],$tokens['foreign'],404);
    $request('GET',$api.'/support/questions/'.$qid,[],$tokens['manager'],404);
    $request('PATCH',$api.'/support/questions/'.$qid,['title'=>'W04 synthetic amended question'],$tokens['owner']);
    $request('GET',$api.'/support/questions/'.$qid,[],$tokens['admin']);
    $request('POST','/api/modules/sirsoft-board/admin/board/travel-lab-questions/posts/'.$qid.'/comments',['content'=>'Synthetic native administrator answer.','is_secret'=>true],$tokens['admin'],201);
    $answered=$request('GET',$api.'/support/questions/'.$qid,[],$tokens['owner']);
    travelLabCheck(count($answered['data']['answers']) === 1,'Native answer absent.');
    foreach (['notices','faqs','questions'] as $channel) { $request('GET','/api/modules/sirsoft-board/boards/travel-lab-'.$channel,[],null,404); }
    $post=Modules\Sirsoft\Board\Models\Post::findOrFail($qid);
    travelLabCheck($post->is_secret && $post->user_id === $actors['owner']->id && !empty($post->action_logs),'Private question/audit failed.');
    foreach (['ecommerce_orders','ecommerce_order_payments','notification_logs'] as $table) { travelLabCheck(DB::table($table)->count() === 0,'Unexpected order/payment/outbound notification record.'); }
    w04Save($evidence.'/http-result.json',['status'=>'PASS','actual_kernel_requests'=>count($records),'real_native_logins'=>5,'non_super_admin'=>true,'server_price'=>24000,'replay_same_inquiry'=>true,'capacity_after_cancel'=>$departure->fresh()->reserved,'native_answer_count'=>count($answered['data']['answers']),'question_private'=>true,'audit_present'=>true,'notices'=>3,'faqs'=>4,'orders'=>0,'payments'=>0,'notification_logs'=>0]);
    echo 'PASS actual installed HTTP kernel requests='.count($records)."\n";
    DB::purge();
} catch (Throwable $e) {
    file_put_contents(dirname(__DIR__,3).'/storage/framework/testing/w04f-private/http-private-error.txt',$e->getMessage()."\n".$e->getTraceAsString());
    w04Save($evidence.'/http-failure.json',['class'=>get_class($e),'safe_check'=>'Probe failed; private diagnostics retained.','requests_completed'=>count($records)]);
    fwrite(STDERR,'FAIL installed HTTP probe ('.get_class($e).")\n"); exit(1);
}
