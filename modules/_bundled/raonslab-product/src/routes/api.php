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
        Route::get('/', [AdminConsultationController::class, 'deprecated'])->name('index');
        Route::get('/{reference}', [AdminConsultationController::class, 'deprecated'])->name('show');
        Route::post('/{reference}/notes', [AdminConsultationController::class, 'deprecated'])->name('notes.store');
        Route::patch('/{reference}/status', [AdminConsultationController::class, 'deprecated'])->name('status.update');
    });
