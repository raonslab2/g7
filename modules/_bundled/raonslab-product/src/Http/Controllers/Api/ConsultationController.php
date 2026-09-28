<?php

namespace Modules\Raonslab\Product\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Base\PublicBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Raonslab\Product\Exceptions\IdempotencyConflictException;
use Modules\Raonslab\Product\Http\Requests\StoreConsultationRequest;
use Modules\Raonslab\Product\Services\ConsultationConfigService;
use Modules\Raonslab\Product\Services\ConsultationService;
use Throwable;

class ConsultationController extends PublicBaseController
{
    public function __construct(
        private ConsultationService $consultationService,
        private ConsultationConfigService $configService,
    ) {
        parent::__construct();
    }

    public function config(): JsonResponse
    {
        return ResponseHelper::success('common.success', $this->configService->publicConfig());
    }

    public function store(StoreConsultationRequest $request): JsonResponse
    {
        try {
            $result = $this->consultationService->submit($request->validated());
            $consultation = $result->consultation;

            return ResponseHelper::success('common.success', [
                'reference' => $consultation->reference,
                'status' => $consultation->status->value,
                'received_at' => $consultation->created_at?->toIso8601String(),
            ], $result->created ? 201 : 200);
        } catch (IdempotencyConflictException) {
            return ResponseHelper::error('common.failed', 409);
        } catch (Throwable) {
            // PII와 내부 DB/메일 예외는 응답이나 일반 로그에 싣지 않습니다.
            return ResponseHelper::error('errors.503.message', 503);
        }
    }
}
