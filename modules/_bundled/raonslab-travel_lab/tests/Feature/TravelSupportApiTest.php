<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

require_once __DIR__.'/../SupportTestCase.php';

use Modules\Raonslab\TravelLab\Tests\SupportTestCase;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Post;
use PHPUnit\Framework\Attributes\Test;

/**
 * @scenario case=public_lists|public_detail|auth_required|author_isolation|admin_read_all|admin_update_permission|validation|native_board_closed
 */
class TravelSupportApiTest extends SupportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->provisionSupport();
    }

    /**
     * @scenario case=public_lists
     *
     * @effects response_helper_envelope, notice_first, body_excluded_from_list, pagination_meta, channel_scoped_detail, per_page_capped
     */
    #[Test]
    public function public_notices_and_faqs_use_response_helper_envelope_without_auth(): void
    {
        $notices = $this->getJson(self::API.'/support/notices')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('raonslab-travel_lab::support.messages.notices_loaded'))
            ->assertJsonPath('data.meta.total', 3)
            ->assertJsonPath('data.meta.current_page', 1)
            ->assertJsonPath('data.data.0.is_notice', true)
            ->assertJsonPath('data.data.0.channel', 'notices');

        $row = $notices->json('data.data.0');
        $this->assertArrayNotHasKey('content', $row, 'list must not serialize body');
        $this->assertArrayNotHasKey('user_id', $row);
        $this->assertArrayNotHasKey('ip_address', $row);

        $this->getJson(self::API.'/support/notices/'.$row['id'])
            ->assertOk()
            ->assertJsonPath('data.id', $row['id'])
            ->assertJsonStructure(['success', 'message', 'data' => ['id', 'channel', 'title', 'content', 'created_at']]);

        $faqs = $this->getJson(self::API.'/support/faqs?per_page=2')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 4)
            ->assertJsonPath('data.meta.last_page', 2)
            ->assertJsonCount(2, 'data.data');

        // 다른 채널의 글은 이 채널 상세로 조회되지 않는다 (채널 스코프)
        $this->getJson(self::API.'/support/notices/'.$faqs->json('data.data.0.id'))->assertNotFound();
        $this->getJson(self::API.'/support/faqs?per_page=500')->assertUnprocessable();
    }

    /**
     * @scenario case=native_board_closed
     *
     * @effects native_public_board_404, board_list_excludes_travel
     */
    #[Test]
    public function native_board_public_routes_do_not_expose_travel_boards(): void
    {
        foreach (['travel-lab-notices', 'travel-lab-faqs', 'travel-lab-questions'] as $slug) {
            $this->getJson("/api/modules/sirsoft-board/boards/{$slug}")->assertNotFound();
        }
        $this->getJson('/api/modules/sirsoft-board/boards')->assertOk()
            ->assertJsonMissing(['slug' => 'travel-lab-questions']);
    }

    /**
     * @scenario case=auth_required
     *
     * @effects unauthorized_401, nothing_stored
     */
    #[Test]
    public function questions_require_sanctum_authentication(): void
    {
        $this->getJson(self::API.'/support/questions')->assertUnauthorized();
        $this->postJson(self::API.'/support/questions', ['title' => '합성', 'content' => '합성 본문'])->assertUnauthorized();
        $this->getJson(self::API.'/support/questions/1')->assertUnauthorized();
        $this->patchJson(self::API.'/support/questions/1', ['title' => '변경'])->assertUnauthorized();
        $this->assertSame(0, $this->questionCount());
    }

    /**
     * @scenario case=author_isolation
     *
     * @effects secret_forced, author_not_spoofable, attachments_absent, own_list_only, other_detail_404, other_update_404_unchanged, author_update_ok
     */
    #[Test]
    public function members_only_see_and_update_their_own_private_questions(): void
    {
        $alice = $this->createMember();
        $bob = $this->createMember();

        $created = $this->actingAs($alice)->postJson(self::API.'/support/questions', [
            'title' => '[합성] 앨리스 출발일 문의',
            'content' => '합성 테스트 문의입니다. 실제 예약이 아닙니다.',
            'is_secret' => false,
            'user_id' => $bob->id,
            'attachment_ids' => [1, 2],
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_secret', true)
            ->assertJsonPath('data.is_mine', true);
        $aliceQuestionId = $created->json('data.id');

        $stored = Post::findOrFail($aliceQuestionId);
        $this->assertTrue($stored->is_secret, 'questions are private by default regardless of input');
        $this->assertSame($alice->id, $stored->user_id, 'author cannot be spoofed');
        $this->assertSame(0, (int) $stored->attachments_count);

        $this->actingAs($bob)->postJson(self::API.'/support/questions', [
            'title' => '[합성] 밥 인원 문의',
            'content' => '합성 테스트 문의입니다.',
        ])->assertCreated();

        // 목록: 본인 글만
        $this->actingAs($alice)->getJson(self::API.'/support/questions')->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.id', $aliceQuestionId);
        $bobList = $this->actingAs($bob)->getJson(self::API.'/support/questions')->assertOk()
            ->assertJsonPath('data.meta.total', 1);
        $this->assertNotContains($aliceQuestionId, array_column($bobList->json('data.data'), 'id'));

        // 상세/수정: 타인 글은 존재를 숨긴 404, 원문 불변
        $this->actingAs($bob)->getJson(self::API."/support/questions/{$aliceQuestionId}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('raonslab-travel_lab::support.errors.question_not_found'));
        $this->actingAs($bob)->patchJson(self::API."/support/questions/{$aliceQuestionId}", ['title' => '탈취 시도'])
            ->assertNotFound();
        $this->assertSame('[합성] 앨리스 출발일 문의', $stored->fresh()->title);

        // 작성자는 읽고 수정할 수 있다
        $this->actingAs($alice)->getJson(self::API."/support/questions/{$aliceQuestionId}")->assertOk()
            ->assertJsonPath('data.content', '합성 테스트 문의입니다. 실제 예약이 아닙니다.')
            ->assertJsonPath('data.answers', []);
        $this->actingAs($alice)->patchJson(self::API."/support/questions/{$aliceQuestionId}", ['title' => '[합성] 수정된 문의'])
            ->assertOk()->assertJsonPath('data.title', '[합성] 수정된 문의');
        $this->assertTrue($stored->fresh()->is_secret);

        // 존재하지 않는 글과 공지 글 ID 도 문의로 조회되지 않는다
        $noticeId = Post::where('board_id', Board::where('slug', 'travel-lab-notices')->value('id'))->value('id');
        $this->actingAs($alice)->getJson(self::API."/support/questions/{$noticeId}")->assertNotFound();
        $this->actingAs($alice)->getJson(self::API.'/support/questions/999999')->assertNotFound();
    }

    /**
     * @scenario case=admin_read_all,admin_update_permission
     *
     * @effects admin_lists_all, admin_reads_any, read_only_admin_cannot_update, admin_updates_other
     */
    #[Test]
    public function support_admin_reads_all_but_needs_update_permission_to_edit_others(): void
    {
        $alice = $this->createMember();
        $id = $this->actingAs($alice)->postJson(self::API.'/support/questions', [
            'title' => '[합성] 관리자 확인용', 'content' => '합성 테스트 문의입니다.',
        ])->assertCreated()->json('data.id');

        $reader = $this->createSupportAdmin(['raonslab-travel_lab.support.read']);
        $this->actingAs($reader)->getJson(self::API.'/support/questions')->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.is_mine', false);
        $this->actingAs($reader)->getJson(self::API."/support/questions/{$id}")->assertOk();
        $this->actingAs($reader)->patchJson(self::API."/support/questions/{$id}", ['title' => '읽기 권한만'])->assertNotFound();

        $updater = $this->createSupportAdmin(['raonslab-travel_lab.support.read', 'raonslab-travel_lab.support.update']);
        $this->actingAs($updater)->patchJson(self::API."/support/questions/{$id}", ['content' => '관리자가 정리한 합성 본문'])
            ->assertOk()->assertJsonPath('data.content', '관리자가 정리한 합성 본문');
    }

    /**
     * @scenario case=native_admin_answer
     *
     * @effects answer_visible_to_author, answer_hidden_from_other, answer_identity_not_exposed
     */
    #[Test]
    public function admin_answer_through_native_board_admin_is_visible_to_author_only(): void
    {
        $alice = $this->createMember();
        $bob = $this->createMember();
        $id = $this->actingAs($alice)->postJson(self::API.'/support/questions', [
            'title' => '[합성] 답변 대기', 'content' => '합성 테스트 문의입니다.',
        ])->assertCreated()->json('data.id');

        $admin = $this->createSupportAdmin([]);
        $this->actingAs($admin)
            ->postJson("/api/modules/sirsoft-board/admin/board/travel-lab-questions/posts/{$id}/comments", [
                'content' => '합성 관리자 답변입니다.',
                'is_secret' => true,
            ])->assertCreated();

        $this->actingAs($alice)->getJson(self::API."/support/questions/{$id}")->assertOk()
            ->assertJsonCount(1, 'data.answers')
            ->assertJsonPath('data.answers_count', 1)
            ->assertJsonPath('data.answers.0.content', '합성 관리자 답변입니다.')
            ->assertJsonPath('data.answers.0.is_author', false)
            ->assertJsonMissingPath('data.answers.0.user_id');
        $this->actingAs($bob)->getJson(self::API."/support/questions/{$id}")->assertNotFound();
    }

    /**
     * @scenario case=validation
     *
     * @effects title_required_422, title_max_422, content_max_422, nothing_stored
     */
    #[Test]
    public function question_validation_returns_422_and_stores_nothing(): void
    {
        $alice = $this->createMember();

        $this->actingAs($alice)->postJson(self::API.'/support/questions', ['content' => '제목 없음'])
            ->assertUnprocessable()->assertJsonValidationErrors(['title']);
        $this->actingAs($alice)->postJson(self::API.'/support/questions', [
            'title' => str_repeat('가', 201), 'content' => '본문',
        ])->assertUnprocessable()->assertJsonValidationErrors(['title']);
        $this->actingAs($alice)->postJson(self::API.'/support/questions', [
            'title' => '본문 초과', 'content' => str_repeat('a', 5001),
        ])->assertUnprocessable()->assertJsonValidationErrors(['content']);

        $this->assertSame(0, $this->questionCount());
    }

    private function questionCount(): int
    {
        return Post::where('board_id', Board::where('slug', 'travel-lab-questions')->value('id'))->count();
    }
}
