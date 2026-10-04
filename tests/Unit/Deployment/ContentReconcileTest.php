<?php
declare(strict_types=1);

namespace Tests\Unit\Deployment;

use PDO;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../deploy/database/reconcile-content.php';

/** Runs only against a separately restored backup clone; never Laravel's production DB. */
final class ContentReconcileTest extends TestCase
{
    public function test_rehearsal_preserves_private_leads_and_auth_and_maps_real_content(): void
    {
        $db = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $target = 'g7_restore_target_check';
        $tool = new \ContentReconcile($db, 'g7_restore_source_reference', $target);
        $before = $tool->inventory($target);
        $leads = $db->query("SELECT * FROM `$target`.g7_board_posts ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $db->beginTransaction();
        try {
            $report = $tool->run();
            $after = $tool->inventory($target);
            foreach (['g7_users','g7_user_roles','g7_personal_access_tokens','g7_migrations','g7_modules','g7_plugins','g7_template_layout_extensions'] as $table) {
                self::assertSame($before[$table], $after[$table], $table);
            }
            foreach ($leads as $lead) {
                $q = $db->prepare("SELECT * FROM `$target`.g7_board_posts WHERE id=?");
                $q->execute([$lead['id']]); self::assertSame($lead, $q->fetch(PDO::FETCH_ASSOC));
            }
            self::assertNotSame(1, $report['mapping']['boards'][1], 'community must not collide with private board 1');
            self::assertSame(1, $report['mapping']['boards'][78]);
            self::assertSame(11, $after['g7_pages']['rows']);
            self::assertSame($before['g7_page_versions']['rows'] + 24, $after['g7_page_versions']['rows']);
            self::assertSame($before['g7_board_posts']['rows'] + 18, $after['g7_board_posts']['rows']);
            $bad = $db->query("SELECT COUNT(*) FROM `$target`.g7_board_posts p LEFT JOIN `$target`.g7_boards b ON b.id=p.board_id LEFT JOIN `$target`.g7_board_posts parent ON parent.id=p.parent_id WHERE b.id IS NULL OR (p.parent_id IS NOT NULL AND parent.id IS NULL)")->fetchColumn();
            self::assertSame(0, (int)$bad);
            foreach (['about','service','cases','technology','faq','contact','privacy','terms'] as $slug) {
                $q = $db->prepare("SELECT s.content=t.content AND s.title=t.title AND s.published=t.published FROM g7_restore_source_reference.g7_pages s JOIN `$target`.g7_pages t USING(slug) WHERE s.slug=?");
                $q->execute([$slug]); self::assertSame(1, (int)$q->fetchColumn(), $slug);
            }
            self::assertSame(0, (int)$db->query("SELECT COUNT(*) FROM `$target`.g7_page_versions v LEFT JOIN `$target`.g7_pages p ON p.id=v.page_id WHERE p.id IS NULL")->fetchColumn());
        } finally { $db->rollBack(); }
        self::assertSame($before, $tool->inventory($target));
    }

    public function test_same_source_and_target_is_rejected_before_access(): void
    {
        $db = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock', 'root', '');
        $this->expectException(\RuntimeException::class);
        new \ContentReconcile($db, 'g7_product', 'g7_product');
    }
}
