<?php

use Illuminate\Support\Facades\Route;
use Modules\Raonslab\Product\Http\Controllers\Admin\ConsultationController as AdminConsultationController;
use Modules\Raonslab\Product\Http\Controllers\Api\ConsultationController;

Route::prefix('consultations')->name('consultations.')->group(function (): void {
    Route::get('/config', [ConsultationController::class, 'config'])
        ->middleware('throttle:60,1')
        ->name('config');

    Route::post('/', [ConsultationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('store');
});

Route::prefix('admin/consultations')
    ->middleware(['auth:sanctum', 'admin', 'throttle:600,1'])
    ->name('admin.consultations.')
    ->group(function (): void {
        Route::get('/', [AdminConsultationController::class, 'index'])
            ->middleware('permission:admin,raonslab-product.consultations.read')
            ->name('index');
        Route::get('/{consultation}', [AdminConsultationController::class, 'show'])
            ->middleware('permission:admin,raonslab-product.consultations.read')
            ->name('show');
        Route::post('/{consultation}/notes', [AdminConsultationController::class, 'note'])
            ->middleware('permission:admin,raonslab-product.consultations.manage')
            ->name('notes.store');
        Route::patch('/{consultation}/status', [AdminConsultationController::class, 'status'])
            ->middleware('permission:admin,raonslab-product.consultations.manage')
            ->name('status.update');
    });
