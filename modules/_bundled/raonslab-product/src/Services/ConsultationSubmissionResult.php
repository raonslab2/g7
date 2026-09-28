<?php

namespace Modules\Raonslab\Product\Services;

use Modules\Raonslab\Product\Models\Consultation;

final readonly class ConsultationSubmissionResult
{
    public function __construct(
        public Consultation $consultation,
        public bool $created,
    ) {}
}
