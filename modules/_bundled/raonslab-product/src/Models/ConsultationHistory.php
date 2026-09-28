<?php

namespace Modules\Raonslab\Product\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Raonslab\Product\Enums\ConsultationHistoryType;
use Modules\Raonslab\Product\Enums\ConsultationStatus;

class ConsultationHistory extends Model
{
    protected $table = 'raonslab_product_consultation_histories';

    protected $fillable = [
        'consultation_id',
        'event_type',
        'from_status',
        'to_status',
        'note',
        'close_outcome',
        'actor_user_id',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => ConsultationHistoryType::class,
            'from_status' => ConsultationStatus::class,
            'to_status' => ConsultationStatus::class,
            'note' => 'encrypted',
            'close_outcome' => 'encrypted',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
