<?php

namespace Modules\Raonslab\TravelLab\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Raonslab\TravelLab\Enums\InquiryStatus;

class Inquiry extends Model
{
    protected $table = 'travel_lab_inquiries';

    protected $fillable = ['user_id', 'idempotency_key', 'payload_hash', 'status', 'total_amount', 'currency_code', 'contact', 'admin_note'];

    protected $casts = ['status' => InquiryStatus::class, 'total_amount' => 'decimal:2', 'contact' => 'array'];

    public function items(): HasMany
    {
        return $this->hasMany(InquiryItem::class, 'inquiry_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
