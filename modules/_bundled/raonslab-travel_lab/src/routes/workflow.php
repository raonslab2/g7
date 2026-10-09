<?php

use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use Modules\Raonslab\TravelLab\Http\Controllers\Api\CartController;
use Modules\Raonslab\TravelLab\Http\Controllers\Api\InquiryController;

// api.php가 require 한다. 모듈 라우트 프로바이더가 URL·name 접두사를 부착한다.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('cart/{cart}', [CartController::class, 'update'])->whereNumber('cart')->name('cart.update');
    Route::delete('cart/{cart}', [CartController::class, 'destroy'])->whereNumber('cart')->name('cart.destroy');

    Route::get('inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::post('inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
    Route::get('inquiries/{inquiry}', [InquiryController::class, 'show'])->whereNumber('inquiry')->name('inquiries.show');
    Route::post('inquiries/{inquiry}/cancel', [InquiryController::class, 'cancel'])->whereNumber('inquiry')->name('inquiries.cancel');

    Route::prefix('admin/inquiries')->name('admin.inquiries.')->group(function () {
        Route::get('/', [AdminInquiryController::class, 'index'])
            ->middleware('permission:admin,raonslab-travel_lab.inquiries.read')->name('index');
        Route::get('{inquiry}', [AdminInquiryController::class, 'show'])->whereNumber('inquiry')
            ->middleware('permission:admin,raonslab-travel_lab.inquiries.read')->name('show');
        Route::patch('{inquiry}', [AdminInquiryController::class, 'update'])->whereNumber('inquiry')
            ->middleware('permission:admin,raonslab-travel_lab.inquiries.update')->name('update');
    });
});
