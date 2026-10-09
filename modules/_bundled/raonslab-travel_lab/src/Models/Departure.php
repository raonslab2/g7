<?php

namespace Modules\Raonslab\TravelLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;

class Departure extends Model
{
    protected $table = 'travel_lab_departures';

    // reserved는 문의 워크플로의 잠금 경로만 변경합니다.
    protected $fillable = ['product_id', 'product_option_id', 'departure_date', 'return_date', 'capacity', 'is_active'];

    protected $casts = ['departure_date' => 'date:Y-m-d', 'return_date' => 'date:Y-m-d', 'capacity' => 'integer', 'reserved' => 'integer', 'is_active' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function getAvailableAttribute(): int
    {
        return max(0, $this->capacity - $this->reserved);
    }
}
