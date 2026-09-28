<?php

namespace Modules\Raonslab\Product\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Raonslab\Product\Enums\ConsultationMailStatus;
use Modules\Raonslab\Product\Enums\ConsultationStatus;
use Modules\Raonslab\Product\Models\Consultation;
use Modules\Raonslab\Product\Models\ConsultationHistory;
use Modules\Raonslab\Product\Repositories\Contracts\ConsultationRepositoryInterface;

class ConsultationRepository implements ConsultationRepositoryInterface
{
    public function findByIdempotencyHash(string $hash, bool $lock = false): ?Consultation
    {
        $query = Consultation::query()->where('idempotency_key_hash', $hash);

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    public function create(array $attributes): Consultation
    {
        return Consultation::query()->create($attributes);
    }

    public function paginate(?ConsultationStatus $status, int $perPage): LengthAwarePaginator
    {
        return Consultation::query()
            ->select([
                'id', 'reference', 'contact_name', 'email', 'company', 'service_interest',
                'status', 'mail_status', 'created_at', 'updated_at',
            ])
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function loadDetail(Consultation $consultation): Consultation
    {
        return $consultation->load(['histories.actor:id,name']);
    }

    public function addHistory(array $attributes): ConsultationHistory
    {
        return ConsultationHistory::query()->create($attributes);
    }

    public function updateStatus(Consultation $consultation, ConsultationStatus $status, ?string $closeOutcome): Consultation
    {
        $consultation->forceFill([
            'status' => $status,
            'close_outcome' => $closeOutcome,
        ])->save();

        return $consultation->refresh();
    }

    public function updateMailStatus(Consultation $consultation, ConsultationMailStatus $status): void
    {
        $attributes = [
            'mail_status' => $status,
            'mail_attempted_at' => $status === ConsultationMailStatus::NotConfigured ? null : now(),
            'mail_sent_at' => $status === ConsultationMailStatus::Sent ? now() : null,
        ];

        $consultation->forceFill($attributes)->save();
    }
}
