<?php

namespace Modules\Raonslab\TravelLab\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;
use Modules\Raonslab\TravelLab\Services\TravelSupportProvisioner;
use Modules\Sirsoft\Board\Models\Post;
use Throwable;

/**
 * 1:1 문의 게시판 글을 사이트 검색 색인에서 제외합니다.
 *
 * 게시판 Post 의 Scout 경로별 보호 범위 (게시판·코어 공개 API 는 수정하지 않는다):
 * - 저장(생성·수정): `sirsoft-board.search.post.index_should_update` 필터가 false 를 돌려
 *   Scout 관찰자가 색인을 건너뛴다.
 * - 복원·소프트 삭제: Scout 관찰자가 강제 저장(forceSaving)으로 필터를 우회하므로,
 *   게시판 after_restore/after_delete 훅에서 외부 드라이버일 때 색인에서 다시 제거한다.
 * - scout:import / makeAllSearchable / 수동 searchable(): 게시판 훅이 없어 이 리스너가 막을 수
 *   없다. 그래서 TravelSupportProvisioner 가 외부 드라이버 구성에서 문의 채널을 닫는다
 *   (mysql-fulltext 에서는 Post::shouldBeSearchable() 이 false 라 외부 색인 자체가 없다).
 */
class ExcludeTravelSupportQuestionsFromSearch implements HookListenerInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getSubscribedHooks(): array
    {
        return [
            'sirsoft-board.search.post.index_should_update' => [
                'method' => 'filterIndexUpdate',
                'priority' => PHP_INT_MAX,
                'type' => 'filter',
            ],
            // 강제 저장 경로는 필터를 거치지 않는다 — 같은 요청 안에서 즉시 색인 제거.
            'sirsoft-board.post.after_restore' => [
                'method' => 'removeFromExternalIndex',
                'priority' => PHP_INT_MAX,
                'sync' => true,
            ],
            'sirsoft-board.post.after_delete' => [
                'method' => 'removeFromExternalIndex',
                'priority' => PHP_INT_MAX,
                'sync' => true,
            ],
        ];
    }

    public function handle(...$args): void
    {
        // 필터는 명시 메서드가 처리합니다.
    }

    public function filterIndexUpdate(bool $shouldUpdate, Post $post): bool
    {
        try {
            return $this->isQuestion($post) ? false : $shouldUpdate;
        } catch (Throwable) {
            // 분류 실패 시 검색 가용성보다 비공개 문의 비색인을 우선한다.
            return false;
        }
    }

    /**
     * 외부 검색 드라이버 구성에서 문의 글을 색인에서 제거합니다.
     *
     * mysql-fulltext 는 board_posts 자체를 질의하므로 제거할 외부 색인이 없다.
     */
    public function removeFromExternalIndex(mixed $post = null, mixed ...$rest): void
    {
        if (! $post instanceof Post || config('scout.driver') === TravelSupportProvisioner::SAFE_SEARCH_DRIVER) {
            return;
        }

        try {
            if ($this->isQuestion($post)) {
                // 현재 요청에서 동기 제거한다. 이미 큐에 들어간 MakeSearchable 작업은
                // 이후 재색인할 수 있으므로 외부 엔진/지연 큐 구성은 지원 계약이 아니다.
                $post->unsearchableSync();
            }
        } catch (Throwable $e) {
            // 출하 기본 로그 수준(error)에서도 남아야 운영자가 색인 잔존을 알 수 있다.
            Log::error('Travel support question could not be removed from the external search index.', [
                'post_id' => $post->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isQuestion(Post $post): bool
    {
        $slug = TravelSupportChannel::Questions->boardSlug();

        if ($post->relationLoaded('board')) {
            return $post->board?->slug === $slug;
        }

        if ($post->board_id === null) {
            return false;
        }

        return $post->board()->where('slug', $slug)->exists();
    }
}
