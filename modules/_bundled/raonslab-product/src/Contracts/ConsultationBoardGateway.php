<?php

namespace Modules\Raonslab\Product\Contracts;

use Modules\Raonslab\Product\Enums\ConsultationStatus;
use Modules\Raonslab\Product\Services\ConsultationSubmissionResult;

interface ConsultationBoardGateway
{
    /** @param array<string, mixed> $content */
    public function persist(
        string $reference,
        string $payloadHash,
        array $content,
        string $ipAddress,
        ConsultationStatus $status,
    ): ConsultationSubmissionResult;
}
