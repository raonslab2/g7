<?php

namespace Modules\Raonslab\Product\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Modules\Raonslab\Product\Services\ConsultationConfigService;
use Symfony\Component\HttpFoundation\Response;

class EnsureConsultationIntakeEnabled
{
    public function __construct(private ConsultationConfigService $configService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->configService->isIntakeEnabled()) {
            return ResponseHelper::error('errors.503.message', 503);
        }

        return $next($request);
    }
}
