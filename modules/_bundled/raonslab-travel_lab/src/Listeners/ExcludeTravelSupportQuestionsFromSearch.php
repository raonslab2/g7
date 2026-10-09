<?php

namespace Modules\Raonslab\TravelLab\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;
use Modules\Sirsoft\Board\Models\Post;
use Throwable;

/**
 * 1:1 문의 게시판 글을 사이트 검색 색인에서 제외합니다.
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
        ];
    }

    public function handle(...$args): void
    {
        // 필터는 명시 메서드가 처리합니다.
    }

    public function filterIndexUpdate(bool $shouldUpdate, Post $post): bool
    {
        $slug = TravelSupportChannel::Questions->boardSlug();

        try {
            if ($post->relationLoaded('board')) {
                return $post->board?->slug === $slug ? false : $shouldUpdate;
            }

            if ($post->board_id === null) {
                return $shouldUpdate;
            }

            return $post->board()->where('slug', $slug)->exists() ? false : $shouldUpdate;
        } catch (Throwable) {
            // 분류 실패 시 검색 가용성보다 비공개 문의 비색인을 우선한다.
            return false;
        }
    }
}
