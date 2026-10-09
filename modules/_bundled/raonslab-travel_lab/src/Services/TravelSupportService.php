<?php

namespace Modules\Raonslab\TravelLab\Services;

use App\Enums\PermissionType;
use App\Helpers\PermissionHelper;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;
use Modules\Raonslab\TravelLab\Exceptions\TravelSupportException;
use Modules\Raonslab\TravelLab\Repositories\Contracts\TravelSupportPostRepositoryInterface;
use Modules\Sirsoft\Board\Models\Board;
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
 * - 작성자 본인은 언제나 자기 문의를 보고 고칠 수 있다.
 * - 남의 문의는 "여행 고객지원 권한 + 문의 게시판의 네이티브 관리자 권한" 을 모두 가진
 *   경우에만 열린다. 게시판 권한은 프로비저너가 admin 전용으로 고정·검증하므로, 전역
 *   역할(manager 등)에 여행 support.* 권한만 남아 있어도 남의 비밀 문의는 열리지 않는다.
 * - 각 권한의 유효 스코프도 적용한다: 상세는 PermissionHelper::checkScopeAccess() 로 글
 *   소유자를 판정하고, 목록 전체 열람은 모든 권한의 스코프가 전체(null)일 때만 허용한다
 *   (self/role 스코프는 목록에서 본인 글로 좁힌다).
 * - 문의 상세/수정 거부는 존재 여부를 숨긴 404.
 * - 문의 저장: 비밀글 강제, 첨부 없음, 알림 발송 SKIP. 수정은 게시판 PostService::updatePost()
 *   로 수행해 네이티브 훅·활동 로그·캐시 무효화를 그대로 거친다.
 */
class TravelSupportService
{
    /** 문의 열람 관리자 권한 */
    public const PERMISSION_SUPPORT_READ = 'raonslab-travel_lab.support.read';

    /** 문의 수정 관리자 권한 */
    public const PERMISSION_SUPPORT_UPDATE = 'raonslab-travel_lab.support.update';

    /** 남의 문의 열람에 함께 요구하는 문의 게시판 네이티브 권한 키 */
    public const BOARD_READ_PERMISSION_KEYS = ['admin.posts.read', 'admin.posts.read-secret'];

    /** 남의 문의 수정에 추가로 요구하는 문의 게시판 네이티브 권한 키 */
    public const BOARD_WRITE_PERMISSION_KEYS = ['admin.posts.write'];

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
     * 문의 목록을 조회합니다. 스코프 제한 없는 관리자 권한이 없으면 본인 글만 반환합니다.
     */
    public function listQuestions(User $viewer, int $perPage, int $page): LengthAwarePaginator
    {
        $board = $this->provisioner->requireReady(TravelSupportChannel::Questions);
        $authorId = $this->hasUnscopedForeignAccess($viewer, $board, write: false) ? null : (int) $viewer->id;

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
        $post = $this->authorizedQuestion($viewer, $board, $postId, write: false);

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
     * 문의 제목·본문을 수정합니다 (작성자 본인 또는 문의 게시판 관리자).
     *
     * @param  array{title?: string, content?: string}  $data  검증된 입력
     *
     * @throws TravelSupportException
     */
    public function updateQuestion(User $viewer, int $postId, array $data): Post
    {
        $board = $this->provisioner->requireReady(TravelSupportChannel::Questions);
        $post = $this->authorizedQuestion($viewer, $board, $postId, write: true);

        $changes = array_intersect_key($data, array_flip(['title', 'content']));
        if ($changes === []) {
            return $post;
        }

        // 네이티브 수정 경로: before/after_update 훅(활동 로그·SEO 캐시), 캐시 무효화.
        // 게시판 알림은 게시판 정의(notify_* 끔)와 SuppressTravelSupportNotifications 가 막는다.
        return $this->postService->updatePost($board->slug, (int) $post->id, $changes);
    }

    /**
     * 전체 문의 목록을 볼 수 있는지 반환합니다 (게시판 미준비 시 false).
     */
    public function canReadAll(User $viewer): bool
    {
        return $this->hasUnscopedForeignAccess($viewer, $this->readyQuestionsBoard(), write: false);
    }

    /**
     * 모든 문의를 수정할 수 있는지 반환합니다 (게시판 미준비 시 false).
     */
    public function canUpdateAll(User $viewer): bool
    {
        return $this->hasUnscopedForeignAccess($viewer, $this->readyQuestionsBoard(), write: true);
    }

    private function readyQuestionsBoard(): ?Board
    {
        try {
            return $this->provisioner->requireReady(TravelSupportChannel::Questions);
        } catch (TravelSupportException) {
            return null;
        }
    }

    /**
     * 남의 문의 접근에 필요한 권한 식별자 목록.
     *
     * @return array<int, string>
     */
    private function foreignAccessPermissions(Board $board, bool $write): array
    {
        $keys = $write
            ? [...self::BOARD_READ_PERMISSION_KEYS, ...self::BOARD_WRITE_PERMISSION_KEYS]
            : self::BOARD_READ_PERMISSION_KEYS;

        $permissions = [self::PERMISSION_SUPPORT_READ];
        if ($write) {
            $permissions[] = self::PERMISSION_SUPPORT_UPDATE;
        }

        foreach ($keys as $key) {
            $permissions[] = "sirsoft-board.{$board->slug}.{$key}";
        }

        return $permissions;
    }

    /**
     * 필요한 관리자 권한을 전부 보유하고 그 유효 스코프가 모두 전체(null)인지 반환합니다.
     */
    private function hasUnscopedForeignAccess(User $viewer, ?Board $board, bool $write): bool
    {
        if (! $board instanceof Board) {
            return false;
        }

        foreach ($this->foreignAccessPermissions($board, $write) as $permission) {
            if (! $viewer->hasPermission($permission, PermissionType::Admin)
                || $viewer->getEffectiveScopeForPermission($permission) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * 특정 남의 문의에 대해 관리자 권한과 각 권한의 스코프 접근이 모두 허용되는지 반환합니다.
     */
    private function canAccessForeignQuestion(User $viewer, Board $board, Post $post, bool $write): bool
    {
        foreach ($this->foreignAccessPermissions($board, $write) as $permission) {
            if (! $viewer->hasPermission($permission, PermissionType::Admin)
                || ! PermissionHelper::checkScopeAccess($post, $permission, $viewer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 문의를 찾고 작성자 또는 문의 게시판 관리자만 통과시킵니다. 거부는 존재 여부를 숨긴 404.
     *
     * @throws TravelSupportException
     */
    private function authorizedQuestion(User $viewer, Board $board, int $postId, bool $write): Post
    {
        $post = $this->supportPosts->findInBoard($board->id, $postId);

        if (! $post instanceof Post) {
            throw TravelSupportException::questionNotFound();
        }

        $isAuthor = $post->user_id !== null && (int) $post->user_id === (int) $viewer->id;
        if (! $isAuthor && ! $this->canAccessForeignQuestion($viewer, $board, $post, $write)) {
            throw TravelSupportException::questionNotFound();
        }

        return $post;
    }
}
