<?php

namespace Modules\Raonslab\Product\Services;

use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Services\BoardService;
use RuntimeException;

class ConsultationBoardProvisioner
{
    public function __construct(
        private BoardService $boardService,
        private LegacyConsultationAudit $legacyAudit,
    ) {}

    public function ensureReady(): Board
    {
        $slug = $this->validatedSlug();

        $board = $this->boardService->getBoardBySlug($slug, checkScope: false)
            ?? $this->boardService->createBoard($this->definition($slug));

        return $this->validateBoard($board);
    }

    /**
     * 요청 경로에서는 게시판을 만들거나 고치지 않고 준비 상태만 확인합니다.
     */
    public function requireReady(): Board
    {
        $board = $this->boardService->getBoardBySlug($this->validatedSlug(), checkScope: false);
        if (! $board instanceof Board) {
            throw new RuntimeException(__('common.failed'));
        }

        return $this->validateBoard($board);
    }

    private function validatedSlug(): string
    {
        if ($this->legacyAudit->hasData()) {
            throw new RuntimeException(__('common.failed'));
        }

        $slug = trim((string) config('raonslab-product-consultations.board_slug'));
        if ($slug === '' || preg_match('/^[a-z0-9-]{3,50}$/', $slug) !== 1) {
            throw new RuntimeException(__('common.failed'));
        }

        return $slug;
    }

    private function validateBoard(Board $board): Board
    {
        if ($board->is_active
            || $board->secret_mode->value !== 'always'
            || ! $board->use_comment
            || $board->notify_author
            || $board->notify_admin_on_post) {
            throw new RuntimeException(__('common.failed'));
        }

        return $board;
    }

    /** @return array<string, mixed> */
    private function definition(string $slug): array
    {
        $adminOnly = ['roles' => ['admin']];

        return [
            'name' => ['ko' => '사업 상담', 'en' => 'Business Consultations'],
            'slug' => $slug,
            'description' => ['ko' => '관리자 전용 사업 상담 게시판', 'en' => 'Private business consultation board'],
            'type' => 'basic',
            'is_active' => false,
            'secret_mode' => 'always',
            'use_comment' => true,
            'use_reply' => true,
            'max_reply_depth' => 1,
            'max_comment_depth' => 1,
            'use_report' => false,
            'use_file_upload' => false,
            'show_view_count' => false,
            'categories' => ['NEW', 'CONTACTED', 'QUALIFIED', 'CLOSED'],
            'notify_author' => false,
            'notify_admin_on_post' => false,
            'add_to_menu' => true,
            'permissions' => [
                'admin_posts_read' => $adminOnly,
                'admin_posts_write' => $adminOnly,
                'admin_posts_read-secret' => $adminOnly,
                'admin_comments_read' => $adminOnly,
                'admin_comments_write' => $adminOnly,
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
