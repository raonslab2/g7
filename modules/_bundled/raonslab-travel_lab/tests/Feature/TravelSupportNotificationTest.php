<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

require_once __DIR__.'/../SupportTestCase.php';

use App\Extension\HookManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\Raonslab\TravelLab\Tests\SupportTestCase;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Comment;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Models\Report;
use PHPUnit\Framework\Attributes\Test;

/**
 * @scenario case=question_submit_skip|native_admin_answer|filter_scope
 */
class TravelSupportNotificationTest extends SupportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->provisionSupport();
    }

    /**
     * @scenario case=question_submit_skip
     *
     * @effects skip_notification_option_passed, no_notifications_sent, no_mail_sent
     */
    #[Test]
    public function question_submission_and_native_admin_answer_send_no_mail_or_notifications(): void
    {
        Notification::fake();
        Mail::fake();

        $capturedOptions = [];
        HookManager::addAction('sirsoft-board.post.after_create', function ($post, $slug, $options = []) use (&$capturedOptions) {
            if ($slug === 'travel-lab-questions') {
                $capturedOptions[] = $options;
            }
        }, 1);

        $alice = $this->createMember();
        $id = $this->actingAs($alice)->postJson(self::API.'/support/questions', [
            'title' => '[합성] 알림 없음 확인', 'content' => '합성 테스트 문의입니다.',
        ])->assertCreated()->json('data.id');

        $admin = $this->createSupportAdmin([]);
        $this->actingAs($admin)
            ->postJson("/api/modules/sirsoft-board/admin/board/travel-lab-questions/posts/{$id}/comments", [
                'content' => '합성 관리자 답변입니다.',
            ])->assertCreated();

        $this->assertSame([['skip_notification' => true]], $capturedOptions);
        Notification::assertNothingSent();
        Mail::assertNothingSent();
    }

    /**
     * @scenario case=filter_scope
     *
     * @effects extract_data_skipped_for_travel_posts, extract_data_skipped_for_travel_comments, other_boards_passthrough
     */
    #[Test]
    public function extract_data_filter_skips_only_travel_support_boards(): void
    {
        $alice = $this->createMember();
        $id = $this->actingAs($alice)->postJson(self::API.'/support/questions', [
            'title' => '[합성] 필터 범위', 'content' => '합성 테스트 문의입니다.',
        ])->assertCreated()->json('data.id');
        $post = Post::findOrFail($id);
        $default = ['notifiable' => null, 'notifiables' => null, 'data' => ['keep' => true], 'context' => []];

        foreach (['post_reply', 'new_post_admin', 'post_action'] as $type) {
            $result = HookManager::applyFilters('sirsoft-board.notification.extract_data', $default, $type, [$post, 'travel-lab-questions', []]);
            $this->assertTrue($result['context']['skip'] ?? false, "{$type} must be skipped for travel support");
            $this->assertSame('raonslab-travel_lab.support', $result['context']['suppressed_by']);
        }

        // 슬러그 인수가 없어도 대상 모델의 게시판으로 판정한다 (댓글)
        $comment = Comment::create([
            'board_id' => $post->board_id, 'post_id' => $post->id, 'user_id' => null,
            'author_name' => 'LAB', 'content' => '합성 댓글', 'is_secret' => true,
            'status' => 'published', 'ip_address' => '0.0.0.0', 'depth' => 0,
        ]);
        $result = HookManager::applyFilters('sirsoft-board.notification.extract_data', $default, 'new_comment', [$comment]);
        $this->assertTrue($result['context']['skip'] ?? false);

        $report = new Report(['board_id' => $post->board_id]);
        $result = HookManager::applyFilters('sirsoft-board.notification.extract_data', $default, 'report_received_admin', [$report]);
        $this->assertTrue($result['context']['skip'] ?? false);

        // 다른 게시판 알림은 그대로 둔다 (게시판 기본 추출 결과 유지 여부만 확인)
        $other = $this->createOtherBoardPost();
        $passthrough = HookManager::applyFilters('sirsoft-board.notification.extract_data', $default, 'unknown_type', [$other, 'other-board', []]);
        $this->assertSame($default, $passthrough);
    }

    private function createOtherBoardPost(): Post
    {
        $board = Board::create([
            'name' => ['ko' => '다른 게시판', 'en' => 'Other'],
            'slug' => 'other-board',
            'type' => 'basic',
        ]);

        return Post::create([
            'board_id' => $board->id, 'title' => '다른 글', 'content' => '본문',
            'ip_address' => '0.0.0.0', 'author_name' => 'x',
        ]);
    }
}
