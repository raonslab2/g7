<?php

namespace Modules\Raonslab\Product\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use Modules\Sirsoft\Board\Models\Post;

/**
 * 합성 Q&A fixture 행에 한해서 게시판 알림 추출을 중단합니다.
 *
 * 알림 Action은 큐에서 실행될 수 있으므로 명령 실행 중 임시 전역 필터를 두지 않고,
 * 큐 인자로 복원되는 게시글의 provenance를 확인하는 제품 모듈 소유 필터를 사용합니다.
 */
class SuppressQaContentNotifications implements HookListenerInterface
{
    public const PROVENANCE_KEY = 'raonslab.agent_factory.qa.wave1_4';

    public static function getSubscribedHooks(): array
    {
        return [
            'sirsoft-board.notification.extract_data' => [
                'method' => 'suppressOwnedContent',
                'priority' => 90,
                'type' => 'filter',
            ],
        ];
    }

    public function handle(...$args): void
    {
        // 필터는 명시 메서드가 처리합니다.
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<int, mixed>  $args
     * @return array<string, mixed>
     */
    public function suppressOwnedContent(array $result, string $type, array $args): array
    {
        if (! in_array($type, ['post_action', 'post_reply', 'new_post_admin'], true)) {
            return $result;
        }

        $post = $args[0] ?? null;
        if (! $post instanceof Post || ! $this->hasOwnedProvenance($post)) {
            return $result;
        }

        return [
            'notifiable' => null,
            'notifiables' => null,
            'data' => [],
            'context' => [
                'skip' => true,
                'suppressed_by' => 'raonslab-product.qa-content',
            ],
        ];
    }

    private function hasOwnedProvenance(Post $post): bool
    {
        return collect($post->action_logs ?? [])->contains(
            fn (array $log): bool => ($log['provenance_key'] ?? null) === self::PROVENANCE_KEY
                && ($log['provenance_kind'] ?? null) === 'synthetic_operational_guidance'
        );
    }
}
