<?php

namespace Modules\Raonslab\TravelLab\Services;

use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;
use Modules\Raonslab\TravelLab\Exceptions\TravelSupportException;
use Modules\Raonslab\TravelLab\Repositories\Contracts\TravelSupportPostRepositoryInterface;
use Modules\Sirsoft\Board\Enums\SecretMode;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Repositories\Contracts\BoardRepositoryInterface;
use Modules\Sirsoft\Board\Services\BoardService;
use Modules\Sirsoft\Board\Services\PostService;

/**
 * 여행 고객지원 게시판 3종(공지/FAQ/문의)을 그누보드7 게시판 모듈로 준비합니다.
 *
 * 안전 계약:
 * - 설정 `raonslab-travel_lab.support.lab_provisioning` 이 true 이고 호출자가 LAB 확인을
 *   명시했을 때만 쓰기를 수행한다. 설치·업데이트·요청 경로에서 자동 실행되지 않는다.
 * - 게시판이 없을 때만 만든다. 같은 슬러그의 게시판이 이미 있으면 절대 수정하지 않고,
 *   보안 기준(비활성·알림 끔·첨부 끔·문의 비밀글 강제)과 다르면 실패한다.
 * - 합성 공지/FAQ 는 provenance 키로 식별하여 재실행 시 중복 생성하지 않는다.
 * - 요청 경로는 requireReady() 로 상태만 확인하고 아무것도 만들지 않는다.
 */
class TravelSupportProvisioner
{
    /** 프로비저너가 심은 콘텐츠의 provenance 접두사 */
    public const PROVENANCE_PREFIX = 'raonslab.travel_lab.support.v1';

    /** 합성 콘텐츠 표식 */
    public const PROVENANCE_KIND = 'synthetic_lab_content';

    public function __construct(
        private BoardService $boardService,
        private BoardRepositoryInterface $boards,
        private PostService $postService,
        private TravelSupportPostRepositoryInterface $supportPosts,
    ) {}

    /**
     * LAB 프로비저닝이 설정상 허용되었는지 반환합니다.
     */
    public function isLabProvisioningEnabled(): bool
    {
        return config('raonslab-travel_lab.support.lab_provisioning') === true;
    }

    /**
     * 게시판과 합성 콘텐츠를 준비합니다 (멱등).
     *
     * @param  bool  $labConfirmed  호출자가 LAB 환경임을 명시적으로 확인했는지
     * @return array{boards: array<string, array{slug: string, id: int, created: bool}>, seeded: array<string, int>, skipped: array<string, int>}
     *
     * @throws TravelSupportException 허용되지 않았거나 기존 게시판이 기준과 다를 때
     */
    public function provision(bool $labConfirmed): array
    {
        if (! $labConfirmed || ! $this->isLabProvisioningEnabled()) {
            throw TravelSupportException::provisioningNotAllowed();
        }

        // 쓰기 전에 기존 게시판 전부를 먼저 검증한다 — 하나라도 어긋나면 아무것도 만들지 않는다.
        foreach (TravelSupportChannel::cases() as $channel) {
            $existing = $this->boards->findBySlug($channel->boardSlug());
            if ($existing instanceof Board) {
                $this->assertSafe($channel, $existing);
            }
        }

        $report = ['boards' => [], 'seeded' => [], 'skipped' => []];

        foreach (TravelSupportChannel::cases() as $channel) {
            $board = $this->boards->findBySlug($channel->boardSlug());
            $created = false;

            if (! $board instanceof Board) {
                $board = $this->boardService->createBoard($this->definition($channel));
                $created = true;
            }

            $board = $this->assertSafe($channel, $board);
            $report['boards'][$channel->value] = ['slug' => $board->slug, 'id' => $board->id, 'created' => $created];
            $report['seeded'][$channel->value] = 0;
            $report['skipped'][$channel->value] = 0;

            foreach (TravelSupportSeedContent::for($channel) as $key => $item) {
                $provenanceKey = self::PROVENANCE_PREFIX.':'.$channel->value.':'.$key;

                if ($this->supportPosts->findByProvenanceKey($board->id, $provenanceKey) !== null) {
                    $report['skipped'][$channel->value]++;

                    continue;
                }

                $this->postService->createPost($board->slug, [
                    'title' => $item['title'],
                    'content' => $item['content'],
                    'content_mode' => 'text',
                    'category' => null,
                    'author_name' => 'Travel Lab',
                    'ip_address' => '0.0.0.0',
                    'is_notice' => (bool) ($item['is_notice'] ?? false),
                    'is_secret' => false,
                    'trigger_type' => 'system',
                    'user_id' => null,
                    'action_logs' => [[
                        'action' => 'provisioned',
                        'provenance_key' => $provenanceKey,
                        'provenance_kind' => self::PROVENANCE_KIND,
                        'at' => now()->toIso8601String(),
                    ]],
                ], options: ['skip_notification' => true]);

                $report['seeded'][$channel->value]++;
            }
        }

        return $report;
    }

    /**
     * 요청 경로용: 채널 게시판이 준비되어 있는지만 확인합니다 (쓰기 없음).
     *
     * @throws TravelSupportException 미준비 또는 기준 불일치 시 (fail-closed)
     */
    public function requireReady(TravelSupportChannel $channel): Board
    {
        $board = $this->boards->findBySlug($channel->boardSlug());
        if (! $board instanceof Board) {
            throw TravelSupportException::notReady();
        }

        try {
            return $this->assertSafe($channel, $board);
        } catch (TravelSupportException) {
            throw TravelSupportException::notReady();
        }
    }

    /**
     * 게시판이 고객지원 보안 기준을 만족하는지 검증합니다.
     *
     * @throws TravelSupportException
     */
    private function assertSafe(TravelSupportChannel $channel, Board $board): Board
    {
        $secretMode = $board->secret_mode instanceof SecretMode ? $board->secret_mode->value : (string) $board->secret_mode;
        $expectedSecret = $channel === TravelSupportChannel::Questions ? SecretMode::Always->value : SecretMode::Disabled->value;

        $safe = ! $board->is_active
            && $secretMode === $expectedSecret
            && ! $board->use_file_upload
            && ! $board->notify_author
            && ! $board->notify_admin_on_post
            && ! $board->use_report;

        if (! $safe) {
            throw TravelSupportException::boardMisconfigured($board->slug);
        }

        return $board;
    }

    /**
     * 채널별 게시판 정의.
     *
     * 게시판은 비활성(is_active=false)으로 만들어 게시판 모듈의 공개 라우트·메뉴·검색에
     * 노출되지 않게 하고, 방문자 경로는 이 모듈의 고객지원 API 만 사용한다.
     * 관리자는 게시판 모듈의 기존 관리자 화면(/admin/board/{slug})에서 그대로 관리한다.
     *
     * @return array<string, mixed>
     */
    private function definition(TravelSupportChannel $channel): array
    {
        $adminOnly = ['roles' => ['admin']];
        $isQuestions = $channel === TravelSupportChannel::Questions;

        $names = [
            TravelSupportChannel::Notices->value => ['ko' => '여행 연구소 공지사항', 'en' => 'Travel Lab Notices'],
            TravelSupportChannel::Faqs->value => ['ko' => '여행 연구소 자주 묻는 질문', 'en' => 'Travel Lab FAQs'],
            TravelSupportChannel::Questions->value => ['ko' => '여행 연구소 1:1 문의', 'en' => 'Travel Lab Questions'],
        ];

        return [
            'name' => $names[$channel->value],
            'slug' => $channel->boardSlug(),
            'description' => [
                'ko' => 'raonslab-travel_lab 모듈이 LAB 용도로 준비한 고객지원 게시판입니다.',
                'en' => 'Customer support board prepared by raonslab-travel_lab for LAB use.',
            ],
            'type' => 'basic',
            'is_active' => false,
            'secret_mode' => $isQuestions ? SecretMode::Always->value : SecretMode::Disabled->value,
            'use_comment' => $isQuestions,
            'use_reply' => false,
            'max_reply_depth' => 1,
            'max_comment_depth' => 1,
            'use_report' => false,
            'use_file_upload' => false,
            'show_view_count' => false,
            'notify_author' => false,
            'notify_admin_on_post' => false,
            'add_to_menu' => false,
            'permissions' => [
                'admin_posts_read' => $adminOnly,
                'admin_posts_write' => $adminOnly,
                'admin_posts_read-secret' => $adminOnly,
                'admin_comments_read' => $adminOnly,
                'admin_comments_write' => $adminOnly,
                'admin_attachments_upload' => $adminOnly,
                'admin_attachments_download' => $adminOnly,
                'admin_manage' => $adminOnly,
                'posts_read' => $adminOnly,
                'posts_write' => $adminOnly,
                'posts_read-secret' => $adminOnly,
                'comments_read' => $adminOnly,
                'comments_write' => $adminOnly,
                'attachments_upload' => $adminOnly,
                'attachments_download' => $adminOnly,
                'manager' => $adminOnly,
            ],
        ];
    }
}
