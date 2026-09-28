<?php

namespace Modules\Raonslab\Product\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Base\PublicBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Raonslab\Product\Exceptions\IdempotencyConflictException;
use Modules\Raonslab\Product\Http\Requests\StoreConsultationRequest;
use Modules\Raonslab\Product\Services\ConsultationConfigService;
use Modules\Raonslab\Product\Services\ConsultationService;
use Throwable;

class ConsultationController extends PublicBaseController
{
    /** 일시 장애 사유 코드 — 같은 Idempotency-Key 로 재시도하면 중복 없이 수렴합니다. */
    public const REASON_TEMPORARY_FAILURE = 'temporary_failure';

    public function __construct(
        private ConsultationService $consultationService,
        private ConsultationConfigService $configService,
    ) {
        parent::__construct();
    }

    public function config(Request $request): JsonResponse
    {
        return ResponseHelper::success('common.success', $this->configService->publicConfig($request));
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
        } catch (Throwable $exception) {
            // 예외 메시지·trace 는 SQL 바인딩 등으로 PII 를 담을 수 있어 기록하지 않습니다.
            // 운영자는 incident_id 로 응답과 로그를 대조합니다.
            $incidentId = (string) Str::ulid();
            Log::error('raonslab-product consultation submission failed', [
                'incident_id' => $incidentId,
                'exception_class' => $exception::class,
                'route' => $request->route()?->getName(),
            ]);

            return ResponseHelper::error('errors.500.message', 500, [
                'reason' => self::REASON_TEMPORARY_FAILURE,
                'retryable' => true,
                'incident_id' => $incidentId,
            ]);
        }
    }
}
