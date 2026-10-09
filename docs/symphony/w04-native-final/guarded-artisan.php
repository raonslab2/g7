<?php
/** 单次 unchanged native handleCommand/bootstrap；console 内部重建 app前不额外 boot/purge。 */
require_once __DIR__.'/runtime-bootstrap.php';
try {
    $env=w04Env();$p=w04Pdo();w04fExclusive($p);$p=null;w04fQuiesce();
    travelLabApplyEnvironment($env);$root=w04fRoot();
    $file=$root.'/bootstrap/cache/autoload-extensions.php';
    if(is_link($file)) { throw new RuntimeException('Own autoload symlink rejected.'); }
    if(is_file($file)) { foreach((require $file)['vendor_autoloads']??[] as $r) {
        $path=realpath($root.'/'.$r);
        if(!$path||!str_starts_with($path,$root.'/modules/')||str_contains($path,'/_bundled/')) { throw new RuntimeException('Installed vendor boundary failed.'); }
        require_once $path;
    } }
    $app=require $root.'/bootstrap/app.php';
    $app->booting(static function() use($app) { $app['events']->listen(Illuminate\Http\Client\Events\RequestSending::class,static fn()=>throw new RuntimeException('Native TEST provider HTTP blocked.')); });
    $app->booted(static function() use($app,$env) { w04Effective($app,$env); });
    exit($app->handleCommand(new Symfony\Component\Console\Input\ArgvInput($argv)));
} catch(Throwable $e) {
    w04Save(w04fPrivate('private').'/last-native-preflight-failure.json',['class'=>get_class($e),'message_sha256'=>hash('sha256',$e->getMessage()),'table_missing'=>str_contains($e->getMessage(),"doesn't exist"),'cache_table'=>str_contains($e->getMessage(),'g7_cache'),'trace'=>array_map(static fn($f)=>['file'=>str_replace(w04fRoot().'/', '',$f['file']??''),'line'=>$f['line']??null,'class'=>$f['class']??null,'function'=>$f['function']??null],$e->getTrace())]);
    fwrite(STDERR,'BLOCKED native TEST preflight '.get_class($e)."\n");exit(1);
}
