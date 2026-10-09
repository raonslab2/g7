<?php
require __DIR__.'/guard.php';
$root = dirname(__DIR__,3);
$label=$argv[1]??'';
w04RequireSnapshot($label);
if (w04Env()['INSTALLER_COMPLETED'] !== 'false') { throw new RuntimeException('Fresh install requires own completed=false.'); }
$private = $root.'/storage/framework/testing/w04-install-logs';
if (!mkdir($private,0700) && !is_dir($private)) { throw new RuntimeException('Log dir unavailable.'); }
$evidence = __DIR__.'/evidence/install';
if (!is_dir($evidence)) { mkdir($evidence,0700,true); }
function w04Command(array $args): void
{
    global $private,$evidence;
    static $n = 0;
    $name = sprintf('%02d',++$n).'-'.str_replace(':','-',$args[0]);
    $status = w04Child([PHP_BINARY,__DIR__.'/guarded-artisan.php',...$args],$private.'/'.$name.'.txt');
    copy($private.'/'.$name.'.txt.result.json',$evidence.'/'.$name.'.json');
    echo $name.' exit='.$status."\n";
    if ($status !== 0) { throw new RuntimeException('Native lifecycle failed: '.$name); }
}
foreach (['modules','templates'] as $type) {
    foreach (glob($root.'/'.$type.'/*',GLOB_ONLYDIR) as $dir) {
        if (!in_array(basename($dir),['_bundled','_pending'],true)) { throw new RuntimeException('Fresh state has installed extensions.'); }
    }
    if (glob($root.'/'.$type.'/_pending/*',GLOB_ONLYDIR)) { throw new RuntimeException('Pending sources forbidden.'); }
}
w04Command(['db:wipe','--database=mysql','--drop-views','--force','--no-interaction']);
$empty = w04Measure(w04Pdo());
w04Save($evidence.'/empty.json',$empty);
if ($empty['table_count'] !== 0) { throw new RuntimeException('Schema not empty.'); }
w04Command(['migrate','--database=mysql','--force','--no-interaction']);
w04Command(['settings:install','--no-interaction']);
$pdo = w04Pdo();
if ((int)$pdo->query('SELECT COUNT(*) FROM g7_users')->fetchColumn() !== 0) { throw new RuntimeException('Core seeder user-delete guard.'); }
$pdo = null;
w04Command(['db:seed','--class=DatabaseSeeder','--force','--no-interaction']);
foreach (['sirsoft-board','sirsoft-page','sirsoft-ecommerce','raonslab-travel_lab'] as $id) {
    w04Command(['module:install',$id,'--vendor-mode=bundled','--no-interaction']);
    w04Command(['module:activate',$id,'--no-interaction']);
}
$pdo = w04Pdo();
$defaults = ['users'=>(int)$pdo->query('SELECT COUNT(*) FROM g7_users')->fetchColumn(), 'products'=>(int)$pdo->query('SELECT COUNT(*) FROM g7_ecommerce_products')->fetchColumn(),'travel_products'=>(int)$pdo->query('SELECT COUNT(*) FROM g7_travel_lab_products')->fetchColumn()];
w04Save($evidence.'/default-install.json',$defaults);
if ($defaults !== ['users'=>1,'products'=>0,'travel_products'=>0]) { throw new RuntimeException('Default installation created sample rows.'); }
$pdo = null;
foreach (['sirsoft-admin_basic','raonslab-travel_lab'] as $id) {
    w04Command(['template:install',$id,'--no-interaction']);
    w04Command(['template:activate',$id,'--no-interaction']);
}
w04Command(['module:seed','raonslab-travel_lab','--sample','--no-interaction']);
w04Command(['raonslab-travel_lab:support-provision','--lab-confirm','--no-interaction']);
// Own TEST environment only. Both files remain identical and mode 0600.
foreach (['.env','.env.testing'] as $name) {
    $path = $root.'/'.$name;
    $bytes = file_get_contents($path);
    $bytes = preg_replace('/^INSTALLER_COMPLETED=false$/m','INSTALLER_COMPLETED=true',$bytes);
    file_put_contents($path,$bytes); chmod($path,0600);
}
if (w04Env()['INSTALLER_COMPLETED'] !== 'true') { throw new RuntimeException('Own completed flag update failed.'); }
$installed = w04Measure(w04Pdo()); w04Save($evidence.'/installed.json',$installed);
echo 'Fresh native installation complete: '.$installed['table_count'].' tables/'.$installed['row_count']." rows\n";
$http = w04Child([PHP_BINARY,__DIR__.'/installed-http.php',$label],$private.'/installed-http.txt');
copy($private.'/installed-http.txt.result.json',$evidence.'/http-exit.json');
exit($http);
