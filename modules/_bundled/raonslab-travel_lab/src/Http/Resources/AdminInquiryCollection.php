<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use Illuminate\Http\Request;

class AdminInquiryCollection extends InquiryCollection
{
    public $collects = AdminInquiryResource::class;

    protected function abilityMap(): array
    {
        return ['can_update' => 'raonslab-travel_lab.inquiries.update'];
    }

    public function toArray(Request $request): array
    {
        return [...parent::toArray($request), 'abilities' => $this->resolveCollectionAbilities($request)];
    }
}
