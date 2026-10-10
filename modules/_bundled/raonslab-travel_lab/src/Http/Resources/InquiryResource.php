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
        $status = $this->status instanceof InquiryStatus ? $this->status : InquiryStatus::from($this->status);
        $items = $this->resource->relationLoaded('items') ? $this->items : collect();
        $first = $items->first();
        $name = $first?->product_name ?? [];
        $localizedName = is_array($name) ? ($name[app()->getLocale()] ?? $name[config('app.fallback_locale')] ?? reset($name) ?: null) : $name;
        $canCancel = $status->canCancel() && (int) $request->user()?->id === (int) $this->user_id;
        $meta = $this->resourceMeta($request);

        return [
            'id' => (int) $this->id,
            'reference' => 'TL-'.str_pad((string) $this->id, 8, '0', STR_PAD_LEFT),
            'status' => $status->value,
            'allowed_transitions' => array_map(static fn (InquiryStatus $next) => $next->value, $status->allowedNext()),
            'can_cancel' => $canCancel,
            'first_product_name' => $localizedName,
            'total_quantity' => (int) $items->sum('quantity'),
            'product_name' => $localizedName,
            'departure_label' => $first?->departure_date?->toDateString(),
            'party_size' => (int) $items->sum('quantity'),
            'requester_name' => $this->contact['name'] ?? null,
            'total_amount' => $this->total_amount,
            'currency_code' => $this->currency_code,
            'contact' => $this->contact,
            'items' => $this->whenLoaded('items', fn () => InquiryItemResource::collection($this->items)->resolve($request)),
            ...$this->formatTimestamps(),
            ...$meta,
            'abilities' => [...($meta['abilities'] ?? []), 'can_cancel' => $canCancel],
        ];
    }
}
