<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use Illuminate\Http\Request;

class AdminInquiryResource extends InquiryResource
{
    protected function abilityMap(): array
    {
        return ['can_update' => 'raonslab-travel_lab.inquiries.update'];
    }

    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'user_id' => (int) $this->user_id,
            'admin_note' => $this->admin_note,
            'calculation_snapshot' => $this->when($this->resource->getAttribute('calculation_snapshot') !== null, fn () => $this->calculation_snapshot),
            'events' => $this->whenLoaded('events', fn () => $this->events->map(static fn ($event) => [
                'id' => (int) $event->id,
                'actor_id' => (int) $event->actor_id,
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'note' => $event->note,
                'created_at' => $event->created_at?->toIso8601String(),
            ])->all()),
        ];
    }
}
