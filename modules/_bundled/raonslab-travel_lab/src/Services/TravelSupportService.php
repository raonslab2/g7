<?php

namespace Modules\Raonslab\TravelLab\Services;

use App\Enums\PermissionType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;
use Modules\Raonslab\TravelLab\Exceptions\TravelSupportException;
use Modules\Raonslab\TravelLab\Repositories\Contracts\TravelSupportPostRepositoryInterface;
use Modules\Sirsoft\Board\Models\Comment;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Services\PostService;

/**
 * 여행 고객지원(공지/FAQ/1:1 문의) 서비스.
 *
 * 저장은 그누보드7 게시판 모듈(PostService)을 그대로 재사용하고, 이 서비스는
 * 채널 스코프와 작성자 격리 규칙만 책임진다.
 *
 * 격리 규칙 (fail-closed):
 * - 문의 목록: 관리자 권한(support.read)이 없으면 언제나 본인 글만.
 * - 문의 상세/수정: 본인 글이 아니고 관리자 권한도 없으면 존재 여부를 숨긴 404.
 * - 문의 저장: 비밀글 강제, 첨부 없음, 알림 발송 SKIP.
 */
class TravelSupportService
{
    /** 문의 열람 관리자 권한 */
    public const PERMISSION_SUPPORT_READ = 'raonslab-travel_lab.support.read';

    /** 문의 수정 관리자 권한 */
    public const PERMISSION_SUPPORT_UPDATE = 'raonslab-travel_lab.support.update';

    /** 상세에 싣는 답변 최대 건수 */
    public const MAX_ANSWERS = 50;

    /** 합성 문의 provenance 표식 */
    public const QUESTION_PROVENANCE_KIND = 'synthetic_lab_question';

    public function __construct(
        private TravelSupportProvisioner $provisioner,
        private TravelSupportPostRepositoryInterface $supportPosts,
        private PostService $postService,
    ) {}

    /**
     * 공개 채널(공지/FAQ) 목록을 조회합니다.
     *
     * @throws TravelSupportException 채널이 공개 채널이 아니거나 게시판 미준비 시
     */
    public function listPublic(TravelSupportChannel $channel, int $perPage, int $page): LengthAwarePaginator
    {
        if (! $channel->isPublic()) {
            throw TravelSupportException::questionNotFound();
        }

        $board = $this->provisioner->requireReady($channel);

        return $this->supportPosts->paginatePublic($board->id, $perPage, $page);
    }

    /**
     * 공개 채널 글 한 건을 조회합니다.
     *
     * @throws TravelSupportException
     */
    public function showPublic(TravelSupportChannel $channel, int $postId): Post
    {
        if (! $channel->isPublic()) {
            throw TravelSupportException::questionNotFound();
        }

        $board = $this->provisioner->requireReady($channel);
        $post = $this->supportPosts->findInBoard($board->id, $postId);

        if (! $post instanceof Post || $post->is_secret) {
            throw new TravelSupportException('raonslab-travel_lab::support.errors.post_not_found', 404);
        }

        return $post;
    }

    /**
     * 문의 목록을 조회합니다. 관리자 권한이 없으면 본인 글만 반환합니다.
     */
    public function listQuestions(User $viewer, int $perPage, int $page): LengthAwarePaginator
    {
        $board = $this->provisioner->requireReady(TravelSupportChannel::Questions);
        $authorId = $this->canReadAll($viewer) ? null : (int) $viewer->id;

        return $this->supportPosts->paginateQuestions($board->id, $authorId, $perPage, $page);
    }

    /**
     * 문의 상세와 답변을 조회합니다.
     *
     * @return array{post: Post, answers: Collection<int, Comment>}
     *
     * @throws TravelSupportException 작성자·관리자가 아니면 404
     */
    public function showQuestion(User $viewer, int $postId): array
    {
        $board = $this->provisioner->requireReady(TravelSupportChannel::Questions);
        $post = $this->authorizedQuestion($viewer, $board->id, $postId, $this->canReadAll($viewer));

        return [
            'post' => $post,
            'answers' => $this->supportPosts->answersFor($board->id, $post->id, self::MAX_ANSWERS),
        ];
    }

    /**
     * 문의를 등록합니다 (비밀글 강제, 알림 SKIP).
     *
     * @param  array{title: string, content: string}  $data  검증된 입력
     */
    public function createQuestion(User $author, array $data, ?string $ipAddress): Post
    {
        $board = $this->provisioner->requireReady(TravelSupportChannel::Questions);

        return $this->postService->createPost($board->slug, [
            'title' => $data['title'],
            'content' => $data['content'],
            'content_mode' => 'text',
            'category' => null,
            'user_id' => $author->id,
            'author_name' => (string) ($author->name ?? ''),
            'ip_address' => $ipAddress !== null && $ipAddress !== '' ? $ipAddress : '0.0.0.0',
            'is_notice' => false,
            'is_secret' => true,
            'trigger_type' => 'user',
            'action_logs' => [[
                'action' => 'submitted',
                'provenance_kind' => self::QUESTION_PROVENANCE_KIND,
                'at' => now()->toIso8601String(),
            ]],
        ], options: ['skip_notification' => true]);
    }

    /**
     * 문의 제목·본문을 수정합니다 (작성자 본인 또는 support.update 관리자).
     *
     * @param  array{title?: string, content?: string}  $data  검증된 입력
     *
     * @throws TravelSupportException
     */
    public function updateQuestion(User $viewer, int $postId, array $data): Post
    {
        $board = $this->provisioner->requireReady(TravelSupportChannel::Questions);
        $post = $this->authorizedQuestion($viewer, $board->id, $postId, $this->canUpdateAll($viewer));

        return $this->supportPosts->updateContent($post, $data);
    }

    /**
     * 관리자 권한으로 전체 문의를 볼 수 있는지 반환합니다.
     */
    public function canReadAll(User $viewer): bool
    {
        return $viewer->hasPermission(self::PERMISSION_SUPPORT_READ, PermissionType::Admin);
    }

    /**
     * 관리자 권한으로 다른 사람의 문의를 수정할 수 있는지 반환합니다.
     */
    public function canUpdateAll(User $viewer): bool
    {
        return $viewer->hasPermission(self::PERMISSION_SUPPORT_UPDATE, PermissionType::Admin);
    }

    /**
     * 문의를 찾고 작성자 또는 관리자만 통과시킵니다. 거부는 존재 여부를 숨긴 404.
     *
     * @throws TravelSupportException
     */
    private function authorizedQuestion(User $viewer, int $boardId, int $postId, bool $adminAllowed): Post
    {
        $post = $this->supportPosts->findInBoard($boardId, $postId);

        if (! $post instanceof Post) {
            throw TravelSupportException::questionNotFound();
        }

        $isAuthor = $post->user_id !== null && (int) $post->user_id === (int) $viewer->id;
        if (! $isAuthor && ! $adminAllowed) {
            throw TravelSupportException::questionNotFound();
        }

        return $post;
    }
}
