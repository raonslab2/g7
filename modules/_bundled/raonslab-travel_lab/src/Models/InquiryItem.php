<?php

namespace Modules\Raonslab\TravelLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sirsoft\Ecommerce\Models\Product;
use Modules\Sirsoft\Ecommerce\Models\ProductOption;

class InquiryItem extends Model
{
    protected $table = 'travel_lab_inquiry_items';

    protected $fillable = ['inquiry_id', 'departure_id', 'product_id', 'product_option_id', 'quantity', 'unit_price', 'line_total', 'product_name', 'departure_date'];

    protected $casts = ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2', 'product_name' => 'array', 'departure_date' => 'date:Y-m-d'];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class, 'departure_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }
}
