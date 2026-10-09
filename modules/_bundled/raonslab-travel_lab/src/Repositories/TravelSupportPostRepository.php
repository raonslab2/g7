<?php

namespace Modules\Raonslab\TravelLab\Repositories;

use App\Models\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Raonslab\TravelLab\Repositories\Contracts\TravelSupportPostRepositoryInterface;
use Modules\Sirsoft\Board\Enums\PostStatus;
use Modules\Sirsoft\Board\Models\Comment;
use Modules\Sirsoft\Board\Models\Post;

/**
 * 여행 고객지원 게시판 조회 구현.
 */
class TravelSupportPostRepository implements TravelSupportPostRepositoryInterface
{
    /**
     * 목록이 실제로 직렬화하는 컬럼만 조회한다 (본문 제외).
     *
     * @var array<int, string>
     */
    private const LIST_COLUMNS = [
        'id', 'board_id', 'title', 'user_id', 'author_name', 'is_notice', 'is_secret',
        'status', 'category', 'comments_count', 'created_at', 'updated_at',
    ];

    public function paginatePublic(int $boardId, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->baseQuery($boardId)
            ->where('is_secret', false)
            ->orderByDesc('is_notice')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, self::LIST_COLUMNS, 'page', $page);
    }

    public function paginateQuestions(int $boardId, ?int $authorId, int $perPage, int $page): LengthAwarePaginator
    {
        $query = $this->baseQuery($boardId);

        if ($authorId !== null) {
            $query->where('user_id', $authorId);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, self::LIST_COLUMNS, 'page', $page);
    }

    public function findInBoard(int $boardId, int $postId): ?Post
    {
        return $this->baseQuery($boardId)->whereKey($postId)->first();
    }

    public function findByProvenanceKey(int $boardId, string $provenanceKey): ?Post
    {
        $needle = '%'.addcslashes('"provenance_key":"'.$provenanceKey.'"', '%_\\').'%';

        // LIKE 는 후보 축소용이며, 최종 판정은 디코드한 action_logs 로 한다.
        return Post::query()
            ->withTrashed()
            ->where('board_id', $boardId)
            ->whereNull('user_id')
            ->where('action_logs', 'like', $needle)
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->first(fn (Post $post): bool => collect($post->action_logs ?? [])
                ->contains(fn ($log): bool => is_array($log) && ($log['provenance_key'] ?? null) === $provenanceKey));
    }

    public function boardPermissionRoles(string $boardSlug, array $permissionKeys): array
    {
        $identifiers = array_map(fn (string $key): string => "sirsoft-board.{$boardSlug}.{$key}", $permissionKeys);

        // 게시판 모델 permissions 접근자는 권한마다 2쿼리를 내므로, 요청 경로 점검은 한 번에 읽는다.
        $permissions = Permission::query()
            ->whereIn('identifier', $identifiers)
            ->with(['roles' => fn ($query) => $query->select('roles.id', 'roles.identifier')])
            ->get(['id', 'identifier'])
            ->keyBy('identifier');

        $result = [];
        foreach ($permissionKeys as $key) {
            $permission = $permissions->get("sirsoft-board.{$boardSlug}.{$key}");
            $result[$key] = $permission === null
                ? null
                : $permission->roles->pluck('identifier')->map(fn ($identifier): string => (string) $identifier)->values()->all();
        }

        return $result;
    }

    public function answersFor(int $boardId, int $postId, int $limit): Collection
    {
        return Comment::query()
            ->where('board_id', $boardId)
            ->where('post_id', $postId)
            ->where('status', PostStatus::Published->value)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'post_id', 'user_id', 'content', 'created_at']);
    }

    /**
     * 게시판·게시 상태·최상위 글 스코프를 고정한 기본 쿼리.
     */
    private function baseQuery(int $boardId): Builder
    {
        return Post::query()
            ->where('board_id', $boardId)
            ->where('status', PostStatus::Published->value)
            ->whereNull('parent_id');
    }
}
