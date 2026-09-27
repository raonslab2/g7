<?php

use Illuminate\Support\Facades\Route;
use Modules\Raonslab\Ai\Workspace\Http\Controllers\Api\AiEventController;
use Modules\Raonslab\Ai\Workspace\Http\Controllers\Api\AiWorkspaceController;

Route::prefix('requests')
    ->middleware(['auth:sanctum', 'throttle:600,1', 'permission:user,raonslab-ai-workspace.requests.use'])
    ->name('requests.')
    ->group(function () {
        Route::get('/capabilities', [AiWorkspaceController::class, 'capabilities'])->name('capabilities');
        Route::get('/', [AiWorkspaceController::class, 'index'])->name('index');
        Route::post('/', [AiWorkspaceController::class, 'store'])->name('store');
        Route::get('/{requestId}', [AiWorkspaceController::class, 'show'])->name('show');
        Route::get('/{requestId}/events', AiEventController::class)->name('events');
        Route::post('/{requestId}/messages', [AiWorkspaceController::class, 'followUp'])->name('messages');
        Route::post('/{requestId}/resume', [AiWorkspaceController::class, 'resume'])->name('resume');
    });
