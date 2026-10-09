<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;
use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;

/**
 * 고객지원 게시글 리소스.
 *
 * 게시판 모델의 내부 필드(user_id, ip_address, password, action_logs)는 내보내지 않는다.
 * 문의 응답에는 열람자 기준 `is_mine` 만 싣는다.
 */
class SupportPostResource extends BaseApiResource
{
    /** 직렬화 채널 (컨트롤러가 지정) */
    public ?TravelSupportChannel $channel = null;

    /**
     * 채널을 지정한 리소스를 만듭니다.
     */
    public static function forChannel(mixed $resource, TravelSupportChannel $channel): self
    {
        $instance = new self($resource);
        $instance->channel = $channel;

        return $instance;
    }

    /**
     * 상세 표현 (본문 포함).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->toListArray($request),
            'content' => (string) $this->content,
        ];
    }

    /**
     * 목록 표현 (본문 제외).
     *
     * @return array<string, mixed>
     */
    public function toListArray(Request $request): array
    {
        $channel = $this->channel ?? TravelSupportChannel::Notices;
        $isQuestion = $channel === TravelSupportChannel::Questions;

        return $this->withoutMissing([
            'id' => (int) $this->id,
            'channel' => $channel->value,
            'title' => (string) $this->title,
            'author_name' => (string) ($this->author_name ?? ''),
            'is_notice' => (bool) $this->is_notice,
            'is_secret' => (bool) $this->is_secret,
            'is_mine' => $isQuestion
                ? ($this->user_id !== null && (int) $this->user_id === (int) $request->user()?->id)
                : false,
            'answers_count' => (int) ($this->comments_count ?? 0),
            ...$this->formatTimestamps(),
        ]);
    }
}
