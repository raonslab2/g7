<?php

namespace Modules\Raonslab\Product\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Raonslab\Product\Enums\ConsultationMailStatus;
use Modules\Raonslab\Product\Enums\ConsultationStatus;
use Modules\Raonslab\Product\Models\Consultation;
use Modules\Raonslab\Product\Models\ConsultationHistory;

interface ConsultationRepositoryInterface
{
    public function findByIdempotencyHash(string $hash, bool $lock = false): ?Consultation;

    public function create(array $attributes): Consultation;

    public function paginate(?ConsultationStatus $status, int $perPage): LengthAwarePaginator;

    public function loadDetail(Consultation $consultation): Consultation;

    public function addHistory(array $attributes): ConsultationHistory;

    public function updateStatus(Consultation $consultation, ConsultationStatus $status, ?string $closeOutcome): Consultation;

    public function updateMailStatus(Consultation $consultation, ConsultationMailStatus $status): void;
}
