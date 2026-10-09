<?php
/** 실제 설치본의 native autoload/routes/provider. testing registration을 대체하지 않는다. */
require_once __DIR__.'/guard.php';
require_once dirname(__DIR__,3).'/scripts/travel-lab/live-bootstrap.php';
function w04App(): Illuminate\Foundation\Application {
    $env=w04Env(); travelLabApplyEnvironment($env);
    $root=w04fRoot(); $cache=$root.'/bootstrap/cache/autoload-extensions.php';
    if(is_link($cache)) { throw new RuntimeException('Autoload symlink rejected.'); }
    if(is_file($cache)) {
        foreach((require $cache)['vendor_autoloads']??[] as $relative) {
            $path=realpath($root.'/'.$relative);
            if($path===false||!str_starts_with($path,$root.'/modules/')||str_contains($path,'/_bundled/')) { throw new RuntimeException('Installed vendor escaped own module.'); }
            require_once $path;
        }
    }
    $app=require $root.'/bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    w04Effective($app,$env);
    $app['events']->listen(Illuminate\Http\Client\Events\RequestSending::class,static function($event) { throw new RuntimeException('Offline native TEST blocks provider HTTP.'); });
    return $app;
}
function w04Effective($app,array $env): void {
    $c=config('database.connections.mysql');
    foreach(['read','write'] as $d) {
        if($c[$d]['host']!==['127.0.0.1']||(string)$c[$d]['port']!=='3306'||$c[$d]['database']!=='req81_travel_lab_test'||$c[$d]['username']!=='req81_travel'||$c[$d]['password']!==$env['DB_WRITE_PASSWORD']) { throw new RuntimeException('Effective TEST connection rejected.'); }
    }
    foreach(['database.default'=>'mysql','mail.default'=>'array','queue.default'=>'sync','filesystems.default'=>'local','cache.default'=>'database','cache.stores.database.connection'=>'mysql','cache.stores.database.lock_connection'=>'mysql','cache.stores.database.table'=>'cache','cache.stores.database.lock_table'=>'cache_locks','session.driver'=>'array','scout.driver'=>'mysql-fulltext'] as $k=>$v) {
        if(config($k)!==$v) { throw new RuntimeException('Effective adapter rejected: '.$k); }
    }
    if(!empty($c['url'])||!empty($c['unix_socket'])||!empty($c['options'])||config('cache.limiter')!==null) { throw new RuntimeException('Effective override rejected.'); }
    $id=Illuminate\Support\Facades\DB::selectOne('SELECT DATABASE() AS db,CURRENT_USER() AS account');
    if($id->db!=='req81_travel_lab_test'||$id->account!=='req81_travel@127.0.0.1') { throw new RuntimeException('Effective SQL identity rejected.'); }
}
