<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use App\Enums\ExtensionOwnerType;
use App\Extension\Helpers\ExtensionMenuSyncHelper;
use App\Extension\HookListenerRegistrar;
use App\Models\Menu;
use Illuminate\Support\Facades\Artisan;
use Modules\Raonslab\Product\Listeners\ExcludeConsultationPostsFromSearch;
use Modules\Raonslab\Product\Module;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Post;
use PHPUnit\Framework\Attributes\Test;

/**
 * 공개 접수 활성화 전 운영 도구(준비 상태 점검·합성 리허설)와 관리자 진입 경로를 고정합니다.
 */
class ConsultationOperationsTest extends ModuleTestCase
{
    #[Test]
    public function readiness_reports_pending_approvals_without_printing_values(): void
    {
        $exit = Artisan::call('raonslab-product:consultation-readiness', ['--json' => true]);
        $result = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $exit);
        $this->assertFalse($result['ready']);
        $this->assertTrue($result['checks']['private_board_ready']);
        foreach (['enabled_flag', 'consent_version_set', 'privacy_copy_set', 'privacy_policy_url_set',
            'privacy_contact_set', 'retention_notice_set', 'privacy_policy_url_https'] as $pending) {
            $this->assertContains($pending, $result['pending']);
        }

        $this->enableIntake();
        $exit = Artisan::call('raonslab-product:consultation-readiness', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertSame([], json_decode(trim($output), true)['pending']);
        $this->assertStringNotContainsString('privacy@example.test', $output);
        $this->assertStringNotContainsString('Synthetic test privacy notice', $output);
    }

    #[Test]
    public function readiness_keeps_http_app_url_pending_even_when_all_values_are_set(): void
    {
        $this->enableIntake();
        config(['app.url' => 'http://203.0.113.10:58770']);

        $this->assertSame(1, Artisan::call('raonslab-product:consultation-readiness', ['--json' => true]));
        $this->assertSame(['app_url_https'], json_decode(trim(Artisan::output()), true)['pending']);
    }

    #[Test]
    public function rehearsal_passes_every_check_and_leaves_no_rows_while_intake_is_closed(): void
    {
        HookListenerRegistrar::register(ExcludeConsultationPostsFromSearch::class, 'raonslab-product-ops-test');
        $board = Board::where('slug', 'raon-consultations')->sole();

        $exit = Artisan::call('raonslab-product:consultation-rehearsal');
        $output = Artisan::output();

        $this->assertSame(0, $exit, $output);
        $this->assertStringNotContainsString('FAIL', $output);
        foreach (['stored_private', 'search_excluded', 'replay_idempotent', 'conflict_rejected', 'admin_status_update', 'no_residue'] as $check) {
            $this->assertMatchesRegularExpression("/{$check}\\s*\\|\\s*PASS/", $output);
        }
        $this->assertSame(0, Post::withTrashed()->where('board_id', $board->id)->count());
    }

    #[Test]
    public function stale_admin_menu_is_repointed_to_the_private_board(): void
    {
        $stale = Menu::updateOrCreate(['slug' => 'raonslab-product-consultations'], [
            'name' => ['ko' => '사업 상담', 'en' => 'Consultations'],
            'url' => '/admin/consultations',
            'icon' => 'fas fa-comments',
            'order' => 80,
            'is_active' => true,
            'extension_type' => ExtensionOwnerType::Module,
            'extension_identifier' => 'raonslab-product',
        ]);

        foreach ((new Module)->getAdminMenus() as $menu) {
            app(ExtensionMenuSyncHelper::class)->syncMenuRecursive($menu, ExtensionOwnerType::Module, 'raonslab-product');
        }

        $this->assertSame('/admin/board/raon-consultations', $stale->fresh()->url);
        $this->assertSame(1, Menu::where('slug', 'raonslab-product-consultations')->count());
    }

    #[Test]
    public function legacy_admin_screens_no_longer_call_the_retired_api(): void
    {
        foreach (['admin_consultation_list', 'admin_consultation_detail'] as $name) {
            $raw = file_get_contents(dirname(__DIR__, 2)."/resources/layouts/admin/{$name}.json");
            $layout = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

            $this->assertSame([], $layout['data_sources'], $name);
            $this->assertStringNotContainsString('/api/modules/raonslab-product/admin/consultations', $raw);
            $this->assertStringContainsString('"path": "/admin/board/raon-consultations"', $raw);
        }
    }
}
