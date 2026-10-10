<?php

namespace Modules\Raonslab\TravelLab\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 시험 문의 처리 이력: 문의 트랜잭션 안에서만 기록합니다. */
class InquiryEvent extends Model
{
    protected $table = 'travel_lab_inquiry_events';

    public $timestamps = false;

    protected $fillable = ['inquiry_id', 'actor_id', 'from_status', 'to_status', 'note', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
