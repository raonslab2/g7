<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;

class InquiryResource extends BaseApiResource
{
    protected function ownerField(): ?string
    {
        return 'user_id';
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'status' => $this->status instanceof InquiryStatus ? $this->status->value : $this->status,
            'total_amount' => $this->total_amount,
            'currency_code' => $this->currency_code,
            'contact' => $this->contact,
            'items' => $this->whenLoaded('items', fn () => InquiryItemResource::collection($this->items)->resolve($request)),
            ...$this->formatTimestamps(),
            ...$this->resourceMeta($request),
        ];
    }
}
