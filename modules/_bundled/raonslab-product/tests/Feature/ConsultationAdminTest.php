<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Modules\Raonslab\Product\Tests\ModuleTestCase;
use Modules\Sirsoft\Board\Models\Comment;
use Modules\Sirsoft\Board\Models\Post;
use PHPUnit\Framework\Attributes\Test;

class ConsultationAdminTest extends ModuleTestCase
{
    #[Test]
    public function public_board_list_detail_feed_and_search_do_not_expose_consultations(): void
    {
        $this->enableIntake();
        $receipt = $this->postConsultation($this->syntheticPayload(), 'synthetic-public-denial')
            ->assertCreated()->json('data.reference');
        $post = Post::where('title', $receipt)->sole();

        $this->getJson('/api/modules/sirsoft-board/boards')->assertOk()
            ->assertJsonMissing(['slug' => 'raon-consultations']);
        $this->getJson('/api/modules/sirsoft-board/boards/board-menu')->assertOk()
            ->assertJsonMissing(['slug' => 'raon-consultations']);
        $this->getJson('/api/modules/sirsoft-board/boards/raon-consultations')->assertNotFound();
        $this->getJson('/api/modules/sirsoft-board/boards/raon-consultations/posts')->assertUnauthorized();
        $this->getJson("/api/modules/sirsoft-board/boards/raon-consultations/posts/{$post->id}")->assertUnauthorized();
        $this->getJson('/api/modules/sirsoft-board/boards/posts/recent')->assertOk()
            ->assertJsonMissing(['title' => $receipt]);
        $this->getJson('/api/search?q=synthetic%40example.test&type=posts')->assertOk()
            ->assertJsonMissing(['title' => $receipt]);
    }

    #[Test]
    public function authorized_admin_uses_official_board_read_reply_and_category_status_contracts(): void
    {
        $this->enableIntake();
        $receipt = $this->postConsultation($this->syntheticPayload(), 'synthetic-admin-flow')
            ->assertCreated()->json('data.reference');
        $post = Post::where('title', $receipt)->sole();
        $admin = $this->createAdminUser([]);

        $this->actingAs($admin)
            ->getJson('/api/modules/sirsoft-board/admin/board/raon-consultations/posts')
            ->assertOk()->assertJsonPath('data.data.0.title', $receipt)
            ->assertJsonPath('data.data.0.content_preview', '');

        $this->actingAs($admin)
            ->getJson("/api/modules/sirsoft-board/admin/board/raon-consultations/posts/{$post->id}")
            ->assertOk()->assertJsonPath('data.title', $receipt)
            ->assertJsonPath('data.category', 'NEW');

        $this->actingAs($admin)
            ->postJson("/api/modules/sirsoft-board/admin/board/raon-consultations/posts/{$post->id}/comments", [
                'content' => 'Synthetic administrator response.',
                'is_secret' => true,
            ])->assertCreated();

        $this->actingAs($admin)
            ->putJson("/api/modules/sirsoft-board/admin/board/raon-consultations/posts/{$post->id}", [
                'category' => 'CONTACTED',
            ])->assertOk()->assertJsonPath('data.category', 'CONTACTED');

        $this->assertSame('CONTACTED', $post->fresh()->category);
        $this->assertSame('Synthetic administrator response.', Comment::where('post_id', $post->id)->sole()->content);

        $this->actingAs($admin)
            ->getJson('/api/modules/raonslab-product/admin/consultations')
            ->assertStatus(410)
            ->assertJsonPath('errors.deprecated', true)
            ->assertJsonPath('errors.admin_path', '/admin/board/raon-consultations');
    }
}
