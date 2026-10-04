<?php
// Read-only API/DB evidence. Response bodies and credentials never leave this process.
$root = $argv[1] ?? '/srv/g7/current';
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$token = trim(file_get_contents('/etc/g7-product/verification-token'));
$checks = [];
$connection = Illuminate\Support\Facades\DB::connection();
$checks['production_database_read'] = $connection->selectOne('SELECT DATABASE() AS name')->name === 'g7_product';
$checks['production_database_write'] = $connection->selectOne('SELECT DATABASE() AS name', [], false)->name === 'g7_product';
$checks['mariadb'] = App\Search\Engines\DatabaseFulltextEngine::isMariaDb();
$checks['ngram_disabled'] = ! App\Search\Engines\DatabaseFulltextEngine::supportsNgramParser();
$http = Illuminate\Support\Facades\Http::acceptJson()->timeout(15);
$admin = $http->withToken($token);
$adminStatuses = [];
foreach (['/api/admin/auth/user', '/api/admin/users', '/api/admin/settings', '/api/modules/sirsoft-page/admin/pages', '/api/modules/sirsoft-board/admin/board/raon-consultations/posts'] as $path) {
    $response = $admin->get('http://127.0.0.1:18770'.$path);
    $adminStatuses[$path] = $response->status();
    $checks[$path] = $response->status() === 200 && $response->json('success') === true;
}
$public = [];
foreach (['about', 'service', 'cases', 'technology', 'faq', 'contact', 'privacy', 'terms'] as $slug) {
    // Separate client: admin credentials must not reach public requests.
    $response = Illuminate\Support\Facades\Http::acceptJson()->timeout(15)
        ->get('http://127.0.0.1:18770/api/modules/sirsoft-page/pages/'.$slug);
    $public[$slug] = $response->status();
}
$counts = [];
foreach (['users', 'pages', 'board_posts', 'migrations', 'jobs', 'failed_jobs'] as $table) {
    $counts[$table] = Illuminate\Support\Facades\DB::table($table)->count();
}
echo json_encode(['checks' => $checks, 'admin_statuses' => $adminStatuses, 'public_page_api_statuses' => $public, 'row_counts' => $counts], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit(in_array(false, $checks, true) ? 1 : 0);
