<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

require_once __DIR__.'/../SupportTestCase.php';

use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;
use Modules\Raonslab\TravelLab\Exceptions\TravelSupportException;
use Modules\Raonslab\TravelLab\Services\TravelSupportProvisioner;
use Modules\Raonslab\TravelLab\Tests\SupportTestCase;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Services\BoardService;
use PHPUnit\Framework\Attributes\Test;

/**
 * @scenario case=lab_gate|first_provision|rerun|existing_unsafe_board|request_path_fail_closed
 */
class TravelSupportProvisionerTest extends SupportTestCase
{
    /**
     * @scenario case=lab_gate
     *
     * @effects provisioning_refused, no_boards_created
     */
    #[Test]
    public function provisioning_is_refused_without_lab_config_and_explicit_confirmation(): void
    {
        $provisioner = app(TravelSupportProvisioner::class);

        foreach ([[false, false], [true, false], [false, true]] as [$configEnabled, $confirmed]) {
            config(['raonslab-travel_lab.support.lab_provisioning' => $configEnabled]);

            try {
                $provisioner->provision($confirmed);
                $this->fail('provisioning must be refused');
            } catch (TravelSupportException $e) {
                $this->assertSame('raonslab-travel_lab::support.errors.provisioning_not_allowed', $e->getMessageKey());
            }
        }

        // 문자열 "1" 같은 느슨한 값도 허용하지 않는다 (명시 true 만)
        config(['raonslab-travel_lab.support.lab_provisioning' => '1']);
        $this->expectException(TravelSupportException::class);
        try {
            $provisioner->provision(true);
        } finally {
            $this->assertSame(0, Board::whereIn('slug', TravelSupportChannel::boardSlugs())->count());
        }
    }

    /**
     * @scenario case=first_provision
     *
     * @effects three_inactive_boards, questions_secret_always, notify_flags_off, uploads_off, synthetic_notices_faqs_seeded
     */
    #[Test]
    public function first_provision_creates_three_isolated_safe_boards_with_synthetic_content(): void
    {
        $report = $this->provisionSupport();

        foreach (TravelSupportChannel::cases() as $channel) {
            $board = Board::where('slug', $channel->boardSlug())->sole();
            $this->assertTrue($report['boards'][$channel->value]['created']);
            $this->assertFalse($board->is_active, 'native public board routes must stay closed');
            $this->assertFalse($board->use_file_upload);
            $this->assertFalse($board->notify_author);
            $this->assertFalse($board->notify_admin_on_post);
            $this->assertSame(
                $channel === TravelSupportChannel::Questions ? 'always' : 'disabled',
                $board->secret_mode->value,
            );
        }

        $this->assertSame(['notices' => 3, 'faqs' => 4, 'questions' => 0], $report['seeded']);
        $notices = Board::where('slug', 'travel-lab-notices')->sole();
        $this->assertSame(3, Post::where('board_id', $notices->id)->count());
        $this->assertSame(1, Post::where('board_id', $notices->id)->where('is_notice', true)->count());
        $this->assertSame(0, Post::where('board_id', $notices->id)->whereNotNull('user_id')->count());
    }

    /**
     * @scenario case=rerun
     *
     * @effects no_duplicate_boards, no_duplicate_posts, content_persists, admin_edits_preserved
     */
    #[Test]
    public function rerun_is_idempotent_and_content_persists(): void
    {
        $this->provisionSupport();
        $before = Post::query()->orderBy('id')->pluck('title', 'id')->all();

        $report = $this->provisionSupport();

        foreach (TravelSupportChannel::cases() as $channel) {
            $this->assertFalse($report['boards'][$channel->value]['created']);
        }
        $this->assertSame(['notices' => 0, 'faqs' => 0, 'questions' => 0], $report['seeded']);
        $this->assertSame(['notices' => 3, 'faqs' => 4, 'questions' => 0], $report['skipped']);
        $this->assertSame($before, Post::query()->orderBy('id')->pluck('title', 'id')->all());
        $this->assertSame(3, Board::whereIn('slug', TravelSupportChannel::boardSlugs())->count());

        // 관리자가 제목을 고쳐도 provenance 로 식별하므로 재실행이 복제하지 않는다
        $first = Post::query()->orderBy('id')->first();
        $first->update(['title' => '관리자가 수정한 제목']);
        $again = $this->provisionSupport();
        $this->assertSame(['notices' => 0, 'faqs' => 0, 'questions' => 0], $again['seeded']);
        $this->assertSame('관리자가 수정한 제목', $first->fresh()->title);
    }

    /**
     * @scenario case=existing_unsafe_board
     *
     * @effects existing_board_unmodified, other_boards_not_created, misconfigured_error
     */
    #[Test]
    public function existing_unsafe_board_with_same_slug_is_never_modified_and_blocks_provisioning(): void
    {
        $existing = app(BoardService::class)->createBoard([
            'name' => ['ko' => '기존 게시판', 'en' => 'Existing'],
            'slug' => 'travel-lab-questions',
            'type' => 'basic',
            'is_active' => true,
            'secret_mode' => 'disabled',
            'notify_author' => true,
            'notify_admin_on_post' => true,
            'add_to_menu' => false,
        ]);
        $snapshot = $existing->fresh()->getAttributes();

        try {
            $this->provisionSupport();
            $this->fail('unsafe existing board must block provisioning');
        } catch (TravelSupportException $e) {
            $this->assertSame('raonslab-travel_lab::support.errors.board_misconfigured', $e->getMessageKey());
            $this->assertSame(['slug' => 'travel-lab-questions'], $e->getMessageParams());
        }

        $this->assertSame($snapshot, $existing->fresh()->getAttributes());
        // 사전 검증 단계에서 실패하므로 다른 채널 게시판도 만들어지지 않는다
        $this->assertSame(0, Board::whereIn('slug', ['travel-lab-notices', 'travel-lab-faqs'])->count());
    }

    /**
     * @scenario case=request_path_fail_closed
     *
     * @effects service_unavailable_503, request_never_provisions
     */
    #[Test]
    public function request_paths_never_provision_and_fail_closed_until_ready(): void
    {
        $member = $this->createMember();

        $this->getJson(self::API.'/support/notices')
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('raonslab-travel_lab::support.errors.not_ready'));
        $this->getJson(self::API.'/support/faqs')->assertStatus(503);
        $this->actingAs($member)->getJson(self::API.'/support/questions')->assertStatus(503);
        $this->actingAs($member)->postJson(self::API.'/support/questions', [
            'title' => '합성 문의', 'content' => '합성 문의 본문입니다.',
        ])->assertStatus(503);

        $this->assertSame(0, Board::whereIn('slug', TravelSupportChannel::boardSlugs())->count());
        $this->assertSame(0, Post::count());
    }
}
