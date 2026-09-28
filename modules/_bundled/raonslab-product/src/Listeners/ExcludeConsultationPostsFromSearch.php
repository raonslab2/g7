<?php

namespace Modules\Raonslab\Product\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use Modules\Sirsoft\Board\Models\Post;
use Throwable;

/**
 * 상담 게시글이 어떤 Scout driver에도 전달되지 않도록 저장 이벤트를 차단합니다.
 */
class ExcludeConsultationPostsFromSearch implements HookListenerInterface
{
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
        try {
            $slug = (string) config('raonslab-product-consultations.board_slug', 'raon-consultations');

            if ($post->relationLoaded('board')) {
                return $post->board?->slug === $slug ? false : $shouldUpdate;
            }

            if ($post->board_id === null) {
                return $shouldUpdate;
            }

            return $post->board()->where('slug', $slug)->exists() ? false : $shouldUpdate;
        } catch (Throwable) {
            // 분류에 실패하면 검색 가용성보다 개인정보 비색인을 우선합니다.
            return false;
        }
    }
}
