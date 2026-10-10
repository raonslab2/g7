<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use Illuminate\Http\Request;

class AdminDepartureResource extends DepartureResource
{
    public function toArray(Request $request): array
    {
        return [...parent::toArray($request), 'capacity' => $this->capacity, 'reserved' => $this->reserved, 'is_active' => $this->is_active];
    }
}
