<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class DepartureResource extends BaseApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_option_id' => $this->product_option_id,
            'departure_date' => $this->departure_date->toDateString(),
            'return_date' => $this->return_date->toDateString(),
            'available' => $this->available,
            // OrderCalculationService::prepareItems도 이 옵션 메서드를 사용합니다.
            'unit_price' => $this->option->getSellingPrice(),
            'currency_code' => $this->option->product->currency_code,
        ];
    }
}
