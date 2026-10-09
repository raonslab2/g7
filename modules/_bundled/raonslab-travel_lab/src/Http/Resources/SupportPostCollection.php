<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiCollection;
use Illuminate\Http\Request;
use Modules\Raonslab\TravelLab\Enums\TravelSupportChannel;

/**
 * 고객지원 게시글 목록 컬렉션.
 */
class SupportPostCollection extends BaseApiCollection
{
    public $collects = SupportPostResource::class;

    /** 직렬화 채널 */
    public TravelSupportChannel $channel = TravelSupportChannel::Notices;

    /**
     * 채널을 지정한 컬렉션을 만듭니다.
     */
    public static function forChannel(mixed $resource, TravelSupportChannel $channel): self
    {
        $instance = new self($resource);
        $instance->channel = $channel;

        return $instance;
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function (SupportPostResource $resource) use ($request): array {
                $resource->channel = $this->channel;

                return $resource->toListArray($request);
            })->values()->all(),
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
            ],
        ];
    }
}
