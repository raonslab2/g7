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
        return [...parent::toArray($request), 'user_id' => (int) $this->user_id, 'admin_note' => $this->admin_note];
    }
}
