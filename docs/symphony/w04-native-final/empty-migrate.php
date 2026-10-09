<?php
/** 빈 DB 설치 단계에만 native core migration을 bootstrap한다. 정상 설치본 cache는 database이다. */
require __DIR__.'/runtime-bootstrap.php';
$env=w04Env();
if($env['INSTALLER_COMPLETED']!=='false') { throw new RuntimeException('Empty installation only.'); }
$pdo=w04Pdo();$before=w04Measure($pdo);$exclusive=w04fExclusive($pdo);$pdo=null;w04fQuiesce();
if($before['table_count']!==0) { throw new RuntimeException('Native core bootstrap requires genuine empty TEST.'); }
foreach(['modules','templates'] as $type) {
 foreach(glob(w04fRoot().'/'.$type.'/*',GLOB_ONLYDIR) as $p) { if(!in_array(basename($p),['_bundled','_pending'],true)) { throw new RuntimeException('Installed runtime cannot use empty bootstrap.'); } }
}
// CoreServiceProvider compatibility cache is accessed before migrate creates its native DB table.
// Explicit installer-only array bootstrap, no normal runtime guard weakened or .env edited.
$env['CACHE_STORE']='array';travelLabApplyEnvironment($env);
$app=require w04fRoot().'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach(['database.default'=>'mysql','mail.default'=>'array','queue.default'=>'sync','filesystems.default'=>'local','cache.default'=>'array'] as $k=>$v) { if(config($k)!==$v) { throw new RuntimeException('Empty native installer adapter escaped.'); } }
foreach(['read','write'] as $d) { $c=config('database.connections.mysql.'.$d); if($c['host']!==['127.0.0.1']||(string)$c['port']!=='3306'||$c['database']!=='req81_travel_lab_test'||$c['username']!=='req81_travel'||$c['password']!==$env['DB_WRITE_PASSWORD']) { throw new RuntimeException('Empty native installer DB escaped.'); } }
if(config('database.connections.mysql.url')||config('database.connections.mysql.unix_socket')||config('database.connections.mysql.options')) { throw new RuntimeException('Empty DB override.'); }
$id=Illuminate\Support\Facades\DB::selectOne('SELECT DATABASE() AS db,CURRENT_USER() AS account');
if($id->db!=='req81_travel_lab_test'||$id->account!=='req81_travel@127.0.0.1') { throw new RuntimeException('Empty identity failed.'); }
$app['events']->listen(Illuminate\Http\Client\Events\RequestSending::class,static fn()=>throw new RuntimeException('Provider HTTP blocked.'));
$exit=$app->handleCommand(new Symfony\Component\Console\Input\ArgvInput(['artisan','migrate','--database=mysql','--force','--no-interaction']));
w04Save(w04fEvidence('install').'/empty-core-bootstrap.json',['exit'=>$exit,'empty_tables_before'=>0,'installer_completed'=>false,'bootstrap_cache'=>'array','normal_env_cache'=>'database','native_command'=>['migrate','--database=mysql','--force','--no-interaction'],'exclusive'=>$exclusive,'source_guard_unchanged'=>true,'normal_cache_bypass'=>false]);
exit($exit);
