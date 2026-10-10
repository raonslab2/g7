<?php

/**
 * 여행 캠페인 공개 API 라우트 (native sirsoft-page 기반 고정 두 슬롯).
 *
 * 모듈 api.php 가 require 한다. ModuleRouteServiceProvider 가 prefix
 * `api/modules/raonslab-travel_lab`, name `api.modules.raonslab-travel_lab.`, middleware `api` 를 부여한다.
 * 선택적 인증이 사용자별 제한 계산보다 먼저 실행되고(AuthenticatesRequests 우선순위),
 * 캠페인 전용 600/분 버킷을 쓴다 — 기존 문의·장바구니·지원 버킷은 공유하지 않는다.
 */

use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Http\Controllers\Api\CampaignController;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelOptionalSanctum;
use Modules\Raonslab\TravelLab\Http\Middleware\TravelThrottleRequests;
use Modules\Raonslab\TravelLab\Support\CampaignRegistry;

Route::prefix('campaigns')
    ->name('campaigns.')
    ->middleware([TravelOptionalSanctum::class, TravelThrottleRequests::with(600, 1, 'travel-lab-campaign-public:')])
    ->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('/{slug}', [CampaignController::class, 'show'])
            ->where('slug', CampaignRegistry::SLUG_PATTERN)
            ->name('show');
    });
