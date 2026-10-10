<?php
/** Own real HTTP entrypoint: installed native Kernel, no manual routes/fake middleware. */
require __DIR__.'/runtime-bootstrap.php';
$app=w04App();
Illuminate\Support\Facades\Auth::shouldUse('web');Illuminate\Support\Facades\Auth::forgetGuards();
$app['session']->driver()->flush();
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);$request=Illuminate\Http\Request::capture();
$response=$kernel->handle($request);$response->send();$kernel->terminate($request,$response);
