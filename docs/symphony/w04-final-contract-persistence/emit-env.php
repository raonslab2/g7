<?php
/** Private IPC for own server only. Never print environment/credentials. */
require __DIR__.'/guard.php';w04Env();
$env=Dotenv\Dotenv::parse(file_get_contents(w04fRoot().'/.env'));
$env['PATH']=getenv('PATH')?:'/usr/local/bin:/usr/bin:/bin';$env['LANG']='C.UTF-8';$env['TZ']='UTC';
w04Save(w04fPrivate('private').'/server-environment.json',$env);
chmod(w04fPrivate('private').'/server-environment.json',0600);
echo 'Own private server environment prepared'.PHP_EOL;
