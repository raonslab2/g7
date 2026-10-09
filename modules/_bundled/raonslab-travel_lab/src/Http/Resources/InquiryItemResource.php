<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InquiryItemResource extends BaseApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'departure_id' => (int) $this->departure_id,
            'product_id' => (int) $this->product_id,
            'product_option_id' => (int) $this->product_option_id,
            'quantity' => (int) $this->quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
            'product_name' => $this->product_name,
            'departure_date' => $this->departure_date ? Carbon::parse($this->departure_date)->toDateString() : null,
        ];
    }
}
