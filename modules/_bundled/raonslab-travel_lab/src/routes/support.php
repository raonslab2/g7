<?php

/**
 * 여행 고객지원 API 라우트.
 *
 * 이 파일은 모듈 api.php 가 require 한다. ModuleRouteServiceProvider 가 모듈 API 전체에
 * prefix `api/modules/raonslab-travel_lab`, name `api.modules.raonslab-travel_lab.`,
 * middleware `api` 를 부여하므로 여기서는 상대 경로만 선언한다.
 */

use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController;

Route::prefix('support')->name('support.')->group(function () {
    // 공개 채널: 비로그인 열람 허용 (토큰이 있으면 사용자 해석만 수행)
    Route::middleware(['optional.sanctum', 'throttle:600,1'])->group(function () {
        Route::get('/notices', [SupportController::class, 'notices'])->name('notices.index');
        Route::get('/notices/{id}', [SupportController::class, 'notice'])->whereNumber('id')->name('notices.show');
        Route::get('/faqs', [SupportController::class, 'faqs'])->name('faqs.index');
        Route::get('/faqs/{id}', [SupportController::class, 'faq'])->whereNumber('id')->name('faqs.show');
    });

    // 1:1 문의: Sanctum Bearer 인증 필수, 작성자·관리자 격리는 서비스가 판정
    Route::prefix('questions')
        ->middleware(['auth:sanctum', 'throttle:120,1'])
        ->name('questions.')
        ->group(function () {
            Route::get('/', [SupportController::class, 'questions'])->name('index');
            Route::post('/', [SupportController::class, 'storeQuestion'])->middleware('throttle:10,1')->name('store');
            Route::get('/{id}', [SupportController::class, 'showQuestion'])->whereNumber('id')->name('show');
            Route::patch('/{id}', [SupportController::class, 'updateQuestion'])->whereNumber('id')->name('update');
        });
});
