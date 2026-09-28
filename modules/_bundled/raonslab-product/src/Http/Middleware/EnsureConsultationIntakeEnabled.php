<?php

namespace Modules\Raonslab\Product\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Raonslab\Product\Services\ConsultationConfigService;
use Symfony\Component\HttpFoundation\Response;

class EnsureConsultationIntakeEnabled
{
    /** 운영자가 접수를 의도적으로 닫았음을 뜻하는 사유 코드 (일시 장애와 구분) */
    public const REASON_INTAKE_DISABLED = 'intake_disabled';

    public function __construct(private ConsultationConfigService $configService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->configService->isIntakeEnabled($request)) {
            return self::disabledResponse();
        }

        return $next($request);
    }

    /**
     * 접수 닫힘 응답입니다. 요청은 저장되지 않았고 재시도해도 결과가 같다는 의미입니다.
     */
    public static function disabledResponse(): JsonResponse
    {
        return ResponseHelper::error('errors.503.message', 503, [
            'reason' => self::REASON_INTAKE_DISABLED,
            'retryable' => false,
        ]);
    }
}
