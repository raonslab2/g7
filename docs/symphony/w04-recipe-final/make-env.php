<?php
// W04 final install review (req_bed1c228): create this checkout's own private TEST-only env files.
// Reads the explicitly authorized parent .env.testing inside this process and copies ONLY the exact
// TEST DB fields. Parent .env (APP) is never opened. Values are never printed.
declare(strict_types=1);
umask(0077);
$root = dirname(__DIR__, 3);
const W04F_PARENT_TESTING = '/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba/.env.testing';
require $root.'/vendor/autoload.php';
foreach (['.env', '.env.testing'] as $name) {
    if (file_exists($root.'/'.$name) || is_link($root.'/'.$name)) { fwrite(STDERR, "Own env exists; refuse overwrite.\n"); exit(1); }
}
if (is_link(W04F_PARENT_TESTING) || (fileperms(W04F_PARENT_TESTING) & 0777) !== 0600) { fwrite(STDERR, "Parent TEST env boundary failed.\n"); exit(1); }
$parent = Dotenv\Dotenv::parse(file_get_contents(W04F_PARENT_TESTING));
$db = [];
foreach (['DB_CONNECTION', 'DB_WRITE_HOST', 'DB_WRITE_PORT', 'DB_WRITE_DATABASE', 'DB_WRITE_USERNAME', 'DB_WRITE_PASSWORD',
    'DB_READ_HOST', 'DB_READ_PORT', 'DB_READ_DATABASE', 'DB_READ_USERNAME', 'DB_READ_PASSWORD', 'DB_DATABASE', 'DB_PREFIX'] as $key) {
    $db[$key] = $parent[$key] ?? '';
}
$expect = ['DB_CONNECTION' => 'mysql', 'DB_WRITE_HOST' => '127.0.0.1', 'DB_READ_HOST' => '127.0.0.1', 'DB_WRITE_PORT' => '3306', 'DB_READ_PORT' => '3306',
    'DB_WRITE_DATABASE' => 'req81_travel_lab_test', 'DB_READ_DATABASE' => 'req81_travel_lab_test', 'DB_DATABASE' => 'req81_travel_lab_test',
    'DB_WRITE_USERNAME' => 'req81_travel', 'DB_READ_USERNAME' => 'req81_travel', 'DB_PREFIX' => 'g7_'];
foreach ($expect as $key => $value) { if ($db[$key] !== $value) { fwrite(STDERR, "Parent TEST field outside allowlist: $key\n"); exit(1); } }
if ($db['DB_WRITE_PASSWORD'] === '' || $db['DB_WRITE_PASSWORD'] !== $db['DB_READ_PASSWORD']) { fwrite(STDERR, "TEST credential shape unexpected.\n"); exit(1); }
unset($parent);
$q = static fn (string $v): string => '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $v).'"';
$run = bin2hex(random_bytes(4));
$lines = [
    'TRAVEL_LAB_ISOLATED=1', 'TRAVEL_LAB_SUPPORT_PROVISIONING=1', 'APP_NAME="G7 W04 Final Install TEST"', 'APP_ENV=local',
    'APP_KEY=base64:'.base64_encode(random_bytes(32)), 'APP_DEBUG=false', 'APP_URL=http://127.0.0.1:18876',
    'APP_LOCALE=ko', 'APP_FALLBACK_LOCALE=en', 'G7_ENV_PRIORITY=true', 'INSTALLER_COMPLETED=false',
];
foreach ($db as $key => $value) { $lines[] = $key.'='.(str_contains($key, 'PASSWORD') ? $q($value) : $value); }
$lines = array_merge($lines, [
    'SESSION_DRIVER=array', 'SESSION_COOKIE=w04q-test-session', 'CACHE_STORE=database', 'DB_CACHE_CONNECTION=mysql', 'DB_CACHE_LOCK_CONNECTION=mysql', 'DB_CACHE_TABLE=cache', 'DB_CACHE_LOCK_TABLE=cache_locks', 'CACHE_PREFIX=w04q-test-',
    'QUEUE_CONNECTION=sync', 'MAIL_MAILER=array', 'MAIL_FROM_ADDRESS="w04q-'.$run.'@example.invalid"', 'BROADCAST_CONNECTION=log',
    'FILESYSTEM_DISK=local', 'SCOUT_DRIVER=mysql-fulltext', 'LOG_CHANNEL=single', 'LOG_LEVEL=warning', 'G7_STATIC_CACHE=false',
    'INSTALLER_ADMIN_NAME="W04F synthetic installer"', 'INSTALLER_ADMIN_EMAIL="w04q-'.$run.'-installer@example.invalid"',
    'INSTALLER_ADMIN_PASSWORD='.$q(bin2hex(random_bytes(18))), 'INSTALLER_ADMIN_LANGUAGE=ko',
    'HTTP_PROXY=http://127.0.0.1:9', 'HTTPS_PROXY=http://127.0.0.1:9', 'ALL_PROXY=http://127.0.0.1:9',
    'http_proxy=http://127.0.0.1:9', 'https_proxy=http://127.0.0.1:9', 'all_proxy=http://127.0.0.1:9',
    'NO_PROXY=127.0.0.1,localhost', 'no_proxy=127.0.0.1,localhost', 'COMPOSER_DISABLE_NETWORK=1',
]);
$bytes = implode("\n", $lines)."\n";
$roundTrip = Dotenv\Dotenv::parse($bytes);
foreach ($db as $key => $value) { if (($roundTrip[$key] ?? null) !== $value) { fwrite(STDERR, "TEST field round-trip failed: $key\n"); exit(1); } }
unset($db, $roundTrip);
foreach (['.env', '.env.testing'] as $name) {
    $output = $name === '.env.testing' ? str_replace(['CACHE_STORE=database','APP_ENV=local'], ['CACHE_STORE=array','APP_ENV=testing'], $bytes) : $bytes;
    $fh = fopen($root.'/'.$name, 'x');
    if ($fh === false || fwrite($fh, $output) !== strlen($output)) { fwrite(STDERR, "Own env write failed.\n"); exit(1); }
    fclose($fh); chmod($root.'/'.$name, 0600);
}
echo "Own private TEST-only .env/.env.testing created (0600, local database / ordinary testing array, ".count($lines)." keys).\n";
