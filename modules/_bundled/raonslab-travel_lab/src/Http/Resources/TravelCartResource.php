<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class TravelCartResource extends BaseApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->getValue('items', []),
            'totals' => $this->getValue('totals', []),
            'currency_code' => $this->getValue('currency_code'),
        ];
    }
}
