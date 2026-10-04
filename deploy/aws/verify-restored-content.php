<?php
/** Sanitized verification; source and target must already be independently restored. */
declare(strict_types=1);
$root = $argv[1] ?? '/srv/g7/current';
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$report = ['pages' => [], 'preserved' => [], 'relationships' => []];
$source = 'g7_restore_source_reference'; $target = 'g7_product'; $snapshot = 'g7_restore_target_check';
$public = Illuminate\Support\Facades\Http::acceptJson()->withHeaders(['Accept-Language' => 'ko'])->timeout(20);
foreach ($db->query("SELECT * FROM `$source`.g7_pages ORDER BY id") as $row) {
    $slug = $row['slug'];
    $response = $public->get('http://127.0.0.1:18770/api/modules/sirsoft-page/pages/'.$slug.'?locale=ko');
    $content = json_decode($row['content'] ?? '', true);
    $expected = is_array($content) ? ($content['ko'] ?? reset($content)) : $row['content'];
    $q = $db->prepare("SELECT * FROM `$target`.g7_pages WHERE slug=?"); $q->execute([$slug]); $restored = $q->fetch(PDO::FETCH_ASSOC);
    $report['pages'][$slug] = ['api_status' => $response->status(), 'api_original_body_equal' => $response->json('data.content') === $expected,
        'database_original_content_equal' => $restored['content'] === $row['content'], 'published' => (int)$restored['published'] === 1,
        'source_version' => (int)$row['current_version'], 'target_version' => (int)$restored['current_version']];
}
foreach (['users','user_roles','boards','board_posts','board_comments','roles','permissions','role_permissions','menus','role_menus','modules','plugins','migrations','templates','template_layouts','template_layout_extensions','raonslab_product_consultations','raonslab_product_consultation_histories'] as $table) {
    $same = true; $count = 0;
    foreach ($db->query("SELECT * FROM `$snapshot`.`g7_$table` ORDER BY id")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $q = $db->prepare("SELECT * FROM `$target`.`g7_$table` WHERE id=?"); $q->execute([$row['id']]);
        $same &= $q->fetch(PDO::FETCH_ASSOC) === $row; $count++;
    }
    $report['preserved'][$table] = ['existing_rows' => $count, 'unchanged' => (bool)$same];
}
foreach (['post_board' => ['board_posts','board_id','boards'], 'post_parent' => ['board_posts','parent_id','board_posts'],
    'comment_post' => ['board_comments','post_id','board_posts'], 'comment_board' => ['board_comments','board_id','boards'],
    'attachment_post' => ['board_attachments','post_id','board_posts'], 'attachment_board' => ['board_attachments','board_id','boards'],
    'page_version' => ['page_versions','page_id','pages'], 'role_permission' => ['role_permissions','permission_id','permissions'],
    'role_menu' => ['role_menus','menu_id','menus'], 'menu_parent' => ['menus','parent_id','menus']] as $name => [$table,$key,$parent]) {
    $count = $db->query("SELECT COUNT(*) FROM `$target`.`g7_$table` c LEFT JOIN `$target`.`g7_$parent` p ON p.id=c.`$key` WHERE c.`$key` IS NOT NULL AND p.id IS NULL")->fetchColumn();
    $report['relationships'][$name] = (int)$count;
}
$token = trim(file_get_contents('/etc/g7-product/verification-token'));
$admin = Illuminate\Support\Facades\Http::acceptJson()->withToken($token)->timeout(20);
$report['admin_pages'] = [];
foreach ($db->query("SELECT id FROM `$target`.g7_pages ORDER BY id")->fetchAll(PDO::FETCH_COLUMN) as $id) {
    $report['admin_pages'][(string)$id] = $admin->get('http://127.0.0.1:18770/api/modules/sirsoft-page/admin/pages/'.$id)->status();
}
$report['private_leads'] = [];
foreach ($db->query("SELECT p.id FROM `$snapshot`.g7_board_posts p JOIN `$snapshot`.g7_boards b ON b.id=p.board_id WHERE b.slug='raon-consultations'")->fetchAll(PDO::FETCH_COLUMN) as $id) {
    $response = $admin->get('http://127.0.0.1:18770/api/modules/sirsoft-board/admin/board/raon-consultations/posts/'.$id);
    $report['private_leads'][(string)$id] = ['status' => $response->status(), 'same_id' => (int)$response->json('data.id') === (int)$id];
}
$ok = true;
foreach ($report['pages'] as $page) $ok &= $page['api_status'] === 200 && $page['api_original_body_equal'] && $page['database_original_content_equal'] && $page['published'];
foreach ($report['preserved'] as $row) $ok &= $row['unchanged'];
foreach ($report['relationships'] as $count) $ok &= $count === 0;
foreach ($report['admin_pages'] as $status) $ok &= $status === 200;
foreach ($report['private_leads'] as $row) $ok &= $row['status'] === 200 && $row['same_id'];
$report['pass'] = (bool)$ok;
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($ok ? 0 : 1);
