<?php

namespace Modules\Raonslab\Product\Tests\Unit;

require_once __DIR__.'/../ModuleTestCase.php';

use App\Models\Permission;
use Modules\Raonslab\Product\Http\Middleware\EnsureConsultationIntakeEnabled;
use Modules\Raonslab\Product\Http\Middleware\RequireSameOrigin;
use Modules\Raonslab\Product\Module;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use Modules\Sirsoft\Board\Models\Board;
use PHPUnit\Framework\Attributes\Test;

class ConsultationContractTest extends ModuleTestCase
{
    #[Test]
    public function module_uses_board_lifecycle_and_private_board_permissions(): void
    {
        $module = new Module;
        $this->assertTrue($module->install());
        $menus = $module->getAdminMenus();
        $this->assertCount(1, $menus);
        $this->assertSame('raonslab-product-consultations', $menus[0]['slug']);
        $this->assertSame('/admin/board/raon-consultations', $menus[0]['url']);
        $this->assertSame([], $module->getPermissions());

        $middleware = collect($module->getMiddleware())->keyBy('class');
        $this->assertArrayHasKey(RequireSameOrigin::class, $middleware);
        $this->assertArrayHasKey(EnsureConsultationIntakeEnabled::class, $middleware);

        $board = Board::where('slug', 'raon-consultations')->sole();
        $this->assertFalse($board->is_active);
        $this->assertSame('always', $board->secret_mode->value);
        $this->assertFalse($board->notify_author);
        $this->assertFalse($board->notify_admin_on_post);
        $this->assertTrue(Permission::where('identifier', 'sirsoft-board.raon-consultations.admin.posts.read-secret')->exists());
        $this->assertFalse(Permission::where('identifier', 'sirsoft-board.raon-consultations.posts.read')
            ->whereHas('roles', fn ($query) => $query->whereIn('identifier', ['guest', 'user']))->exists());
    }
}
