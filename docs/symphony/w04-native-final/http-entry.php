<?php
/** 실제 설치본 HTTP kernel만 사용. fake route/provider/auth 등록 없음. */
require __DIR__.'/runtime-bootstrap.php';
try {
    $app=w04App();
    $cache=$app['cache']->store();
    if(!$cache->getStore() instanceof Illuminate\Cache\DatabaseStore) { throw new RuntimeException('HTTP native cache implementation rejected.'); }
    $id=Illuminate\Support\Facades\DB::selectOne('SELECT DATABASE() AS db,CURRENT_USER() AS account');
    w04Save(w04fPrivate('server').'/last-runtime-proof.json',['pid'=>getmypid(),'cwd'=>getcwd(),'app_env'=>$app->environment(),'database'=>$id->db,'account'=>$id->account,'cache'=>config('cache.default'),'native_cache_class'=>get_class($cache->getStore()),'cache_connection'=>config('cache.stores.database.connection'),'cache_table'=>config('cache.stores.database.table'),'lock_connection'=>config('cache.stores.database.lock_connection'),'lock_table'=>config('cache.stores.database.lock_table'),'mail'=>config('mail.default'),'queue'=>config('queue.default'),'filesystem'=>config('filesystems.default'),'provider_http'=>'blocked before send','utc'=>gmdate('c')]);
    $app->handleRequest(Illuminate\Http\Request::capture());
} catch(Throwable $e) {
    http_response_code(503);header('Content-Type: application/json');echo json_encode(['status'=>'BLOCKED','class'=>get_class($e)]);
}
