<?php

namespace Modules\Raonslab\Ai\Workspace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model
{
    protected $table = 'raonslab_ai_requests';

    protected $fillable = [
        'user_id',
        'user_uuid',
        'request_id',
        'project_id',
        'provider',
        'profile',
        'state',
        'title',
        'last_observed_at',
    ];

    protected function casts(): array
    {
        return ['last_observed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
