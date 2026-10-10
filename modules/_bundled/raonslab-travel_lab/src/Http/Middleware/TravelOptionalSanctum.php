<?php

namespace Modules\Raonslab\TravelLab\Http\Middleware;

use App\Http\Middleware\OptionalSanctumMiddleware;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;

/**
 * 공개 여행 지원 조회의 선택적 인증을 사용자별 제한 계산 전에 실행한다.
 * 인증 동작은 코어를 그대로 사용하고 우선순위 표시는 이 모듈 라우트에만 적용한다.
 */
class TravelOptionalSanctum extends OptionalSanctumMiddleware implements AuthenticatesRequests {}
