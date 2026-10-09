<?php

namespace Modules\Raonslab\TravelLab\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;
use Modules\Sirsoft\Board\Models\Comment;
use Modules\Sirsoft\Board\Models\Post;

/**
 * 여행 고객지원 게시판(travel-lab-*)에서 발생하는 게시판 알림만 발송 중단합니다.
 *
 * 게시판 정의가 이미 notify_author/notify_admin_on_post 를 끄고, 이 모듈의 쓰기 경로는
 * skip_notification 을 넘기지만, 관리자가 게시판 관리자 화면에서 답변(댓글)·블라인드·삭제를
 * 수행하는 경로는 이 모듈을 거치지 않는다. 합성 LAB 문의 작성자에게 메일·문자·알림이
 * 나가지 않도록 알림 데이터 추출 필터에서 이 게시판들만 skip 으로 돌린다.
 * 다른 게시판(raonslab-product 상담 게시판 포함)의 알림은 건드리지 않는다.
 */
class SuppressTravelSupportNotifications implements HookListenerInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getSubscribedHooks(): array
    {
        return [
            'sirsoft-board.notification.extract_data' => [
                'method' => 'suppressTravelSupport',
                // 게시판 기본 추출(20) 이후에 실행되어 그 결과를 덮어쓴다.
                'priority' => 95,
                'type' => 'filter',
            ],
        ];
    }

    public function handle(...$args): void
    {
        // 필터는 명시 메서드가 처리합니다.
    }

    /**
     * @param  array<string, mixed>  $result  앞선 리스너의 추출 결과
     * @param  string  $type  알림 정의 유형
     * @param  array<int, mixed>  $args  훅 원본 인수 ([$target, $slug, ...])
     * @return array<string, mixed>
     */
    public function suppressTravelSupport(array $result, string $type, array $args): array
    {
        if (! $this->isTravelSupportTarget($args)) {
            return $result;
        }

        return [
            'notifiable' => null,
            'notifiables' => null,
            'data' => [],
            'context' => [
                'skip' => true,
                'suppressed_by' => 'raonslab-travel_lab.support',
            ],
        ];
    }

    /**
     * 훅 인수가 여행 고객지원 게시판의 게시글/댓글을 가리키는지 판정합니다.
     *
     * @param  array<int, mixed>  $args
     */
    private function isTravelSupportTarget(array $args): bool
    {
        $slugs = TravelSupportChannel::boardSlugs();

        $slug = $args[1] ?? null;
        if (is_string($slug) && in_array($slug, $slugs, true)) {
            return true;
        }

        $target = $args[0] ?? null;
        if ($target instanceof Post || $target instanceof Comment) {
            $board = $target->relationLoaded('board') ? $target->board : $target->board()->first(['id', 'slug']);

            return $board !== null && in_array($board->slug, $slugs, true);
        }

        return false;
    }
}
