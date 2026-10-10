<?php

namespace Modules\Raonslab\TravelLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Sirsoft\Ecommerce\Models\Product;

class TravelProduct extends Model
{
    protected $table = 'travel_lab_products';

    protected $fillable = ['product_id', 'region', 'theme', 'duration_days', 'summary', 'itinerary', 'published'];

    protected $casts = ['duration_days' => 'integer', 'summary' => 'array', 'itinerary' => 'array', 'published' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function departures(): HasMany
    {
        return $this->hasMany(Departure::class, 'product_id', 'product_id');
    }
}
