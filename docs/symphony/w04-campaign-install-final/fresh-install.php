<?php
// Actual native fresh installation on the exclusive TEST schema (req_812d0334).
// Adapted from failed historical reviewer w04r/fresh-install.php; corrections: stricter exclusivity before wipe,
// per-module persisted vendor_mode requery after every native module:install, own private paths.
require __DIR__.'/guard.php';
$root = w04fRoot();
$probe=fopen($root.'/storage/framework/testing/w04c-exclusive.lock','c');
if(flock($probe,LOCK_EX|LOCK_NB)) { flock($probe,LOCK_UN); throw new RuntimeException('Fresh lifecycle requires live operation wrapper lease.'); }
fclose($probe);
$label = $argv[1] ?? 'original';
w04fRequireSnapshot($label);
if (w04Env()['INSTALLER_COMPLETED'] !== 'false') { throw new RuntimeException('Fresh install requires own completed=false.'); }
$private = $root.'/storage/framework/testing/w04c-install-logs';
if (!is_dir($private) && !mkdir($private, 0700)) { throw new RuntimeException('Log dir unavailable.'); }
$evidence = w04fEvidence('install');
$commands = [];
function w04fCommand(array $args): void
{
    global $private, $evidence, $commands;
    static $n = 0;
    $name = sprintf('%02d', ++$n).'-'.str_replace(':', '-', $args[0]).(isset($args[1]) && !str_starts_with($args[1], '-') ? '-'.$args[1] : '');
    $quiet = w04fQuiesce();
    $status = w04fChild([PHP_BINARY, __DIR__.'/guarded-artisan.php', ...$args], $private.'/'.$name.'.txt');
    $result = json_decode(file_get_contents($private.'/'.$name.'.txt.result.json'), true);
    $commands[] = ['step' => $name, 'args' => $args, 'quiesce_wait' => $quiet['waited_seconds'], 'exit' => $status, 'seconds' => $result['seconds'], 'pid' => $result['pid']];
    w04Save($evidence.'/commands.json', ['commands' => $commands]);
    echo $name.' exit='.$status."\n";
    if ($status !== 0) { throw new RuntimeException('Native lifecycle failed: '.$name); }
}
foreach (['modules', 'templates', 'plugins'] as $type) {
    foreach (glob($root.'/'.$type.'/*', GLOB_ONLYDIR) as $dir) {
        if (!in_array(basename($dir), ['_bundled', '_pending'], true)) { throw new RuntimeException('Fresh state has installed extensions.'); }
    }
    if (glob($root.'/'.$type.'/_pending/*', GLOB_ONLYDIR)) { throw new RuntimeException('Pending sources forbidden.'); }
}
require __DIR__.'/fixtures.php';
$pdo = w04Pdo(); $exclusive = w04fExclusive($pdo); $pdo = null;
w04Save($evidence.'/pre-wipe-exclusive.json', $exclusive);
w04fCommand(['db:wipe', '--database=mysql', '--drop-views', '--force', '--no-interaction']);
$empty = w04Measure(w04Pdo());
w04Save($evidence.'/empty.json', $empty);
if ($empty['table_count'] !== 0) { throw new RuntimeException('Schema not empty.'); }
w04fQuiesce();
if(w04fChild([PHP_BINARY,__DIR__.'/bootstrap-check.php','empty'],$private.'/empty-bootstrap-check.txt')!==0) { throw new RuntimeException('Published empty guard fixtures failed.'); }
$bootstrap=w04fChild([PHP_BINARY,__DIR__.'/empty-migrate.php'],$private.'/02-empty-native-migrate.txt');
if($bootstrap!==0) { throw new RuntimeException('Native empty core bootstrap failed.'); }
w04fCommand(['settings:install', '--no-interaction']);
$pdo = w04Pdo();
if ((int) $pdo->query('SELECT COUNT(*) FROM g7_users')->fetchColumn() !== 0) { throw new RuntimeException('Core seeder user-delete guard.'); }
$pdo = null;
w04fCommand(['db:seed', '--class=DatabaseSeeder', '--force', '--no-interaction']);
$registration = [];
foreach (['sirsoft-board', 'sirsoft-page', 'sirsoft-ecommerce', 'raonslab-travel_lab'] as $id) {
    w04fCommand(['module:install', $id, '--vendor-mode=bundled', '--no-interaction']);
    $row = w04rModuleRow($id);
    $registration[$id] = ['after' => 'module:install --vendor-mode=bundled', 'vendor_mode' => $row['vendor_mode'] ?? null, 'status' => $row['status'] ?? null, 'version' => $row['version'] ?? null];
    w04Save($evidence.'/vendor-mode-registration.json', ['modules' => $registration]);
    if (($row['vendor_mode'] ?? null) !== 'bundled') { throw new RuntimeException('Persisted vendor_mode is not bundled: '.$id); }
    if ($id === 'sirsoft-ecommerce') { w04rDependencyGate(); }
    w04fCommand(['module:activate', $id, '--no-interaction']);
    $registration[$id]['vendor_mode_after_activate'] = w04rModuleRow($id)['vendor_mode'] ?? null;
    $registration[$id]['status_after_activate'] = w04rModuleRow($id)['status'] ?? null;
    w04Save($evidence.'/vendor-mode-registration.json', ['modules' => $registration]);
}
$pdo = w04Pdo();
$defaults = ['users' => (int) $pdo->query('SELECT COUNT(*) FROM g7_users')->fetchColumn(), 'products' => (int) $pdo->query('SELECT COUNT(*) FROM g7_ecommerce_products')->fetchColumn(), 'pages' => (int) $pdo->query('SELECT COUNT(*) FROM g7_pages')->fetchColumn(), 'travel_products' => (int) $pdo->query('SELECT COUNT(*) FROM g7_travel_lab_products')->fetchColumn()];
w04Save($evidence.'/default-install.json', $defaults);
w04Save($evidence.'/zero-page-contract.json',['status'=>$defaults['pages']===0?'PASS':'FAIL','required_pages'=>0,'actual_pages'=>$defaults['pages'],'diagnostic_continuation'=>true]);
if ($defaults['users']!==1 || $defaults['products']!==0 || $defaults['travel_products']!==0) { throw new RuntimeException('Default installation created sample rows.'); }
$pdo = null;
foreach (['sirsoft-admin_basic', 'raonslab-travel_lab'] as $id) {
    w04fCommand(['template:install', $id, '--no-interaction']);
    w04fCommand(['template:activate', $id, '--no-interaction']);
}
$ref=w04Pdo();
if((int)$ref->query('SELECT COUNT(*) FROM g7_ecommerce_shipping_types')->fetchColumn()!==0) { throw new RuntimeException('Fresh shipping reference not empty.'); }
$ref=null;
w04fCommand(['db:seed','--class=Modules\\Sirsoft\\Ecommerce\\Database\\Seeders\\ShippingTypeSeeder','--force','--no-interaction']);
w04Save($evidence.'/shipping-empty-branch.json',['before_rows'=>0,'native_seeder'=>'ShippingTypeSeeder','whole_ecommerce_sample'=>false]);
w04fCommand(['module:seed', 'raonslab-travel_lab', '--sample', '--no-interaction']);
w04fCommand(['raonslab-travel_lab:support-provision', '--lab-confirm', '--no-interaction']);
$p=w04Pdo(); $pages=(int)$p->query('SELECT COUNT(*) FROM g7_pages')->fetchColumn();$p=null;
w04Save($evidence.'/sample-support-zero-pages.json',['pages'=>$pages]);
if($pages!==$defaults['pages']){throw new RuntimeException('Sample/support changed native Pages.');}
w04fCompleted(true);
if (w04Env()['INSTALLER_COMPLETED'] !== 'true') { throw new RuntimeException('Own completed flag update failed.'); }
$installed = w04Measure(w04Pdo()); w04Save($evidence.'/installed.json', ['table_count' => $installed['table_count'], 'row_count' => $installed['row_count'], 'digest' => w04fDigest($installed)]);
echo 'Fresh native installation complete: '.$installed['table_count'].' tables/'.$installed['row_count']." rows\n";
echo "Installation lifecycle finished; probes run separately under retained original snapshot.\n";
