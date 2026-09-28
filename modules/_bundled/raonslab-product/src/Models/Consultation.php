<?php

namespace Modules\Raonslab\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Raonslab\Product\Enums\ConsultationMailStatus;
use Modules\Raonslab\Product\Enums\ConsultationStatus;

class Consultation extends Model
{
    protected $table = 'raonslab_product_consultations';

    protected $fillable = [
        'reference',
        'idempotency_key_hash',
        'payload_hash',
        'contact_name',
        'email',
        'company',
        'phone',
        'service_interest',
        'message',
        'privacy_consent_version',
        'privacy_consented_at',
        'status',
        'close_outcome',
        'mail_status',
        'mail_attempted_at',
        'mail_sent_at',
    ];

    protected $hidden = [
        'idempotency_key_hash',
        'payload_hash',
    ];

    protected function casts(): array
    {
        return [
            'contact_name' => 'encrypted',
            'email' => 'encrypted',
            'company' => 'encrypted',
            'phone' => 'encrypted',
            'service_interest' => 'encrypted',
            'message' => 'encrypted',
            'privacy_consented_at' => 'datetime',
            'status' => ConsultationStatus::class,
            'close_outcome' => 'encrypted',
            'mail_status' => ConsultationMailStatus::class,
            'mail_attempted_at' => 'datetime',
            'mail_sent_at' => 'datetime',
        ];
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ConsultationHistory::class)->oldest('id');
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
