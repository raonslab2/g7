<?php

use Illuminate\Support\Facades\Route;
use Modules\Raonslab\TravelLab\Http\Controllers\AdminCatalogController;
use Modules\Raonslab\TravelLab\Http\Controllers\CatalogController;

// 코어 ModuleRouteServiceProvider가 URI와 라우트명의 모듈 접두사를 붙입니다.
Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('catalog/{product}', [CatalogController::class, 'show'])->whereNumber('product')->name('catalog.show');
Route::get('catalog/{product}/departures', [CatalogController::class, 'departures'])->whereNumber('product')->name('catalog.departures');
Route::get('facets', [CatalogController::class, 'facets'])->name('catalog.facets');

Route::prefix('admin/catalog')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('', [AdminCatalogController::class, 'index'])->middleware('permission:admin,raonslab-travel_lab.catalog.read')->name('admin.catalog.index');
    Route::patch('{product}', [AdminCatalogController::class, 'update'])->whereNumber('product')->middleware('permission:admin,raonslab-travel_lab.catalog.update')->name('admin.catalog.update');
    Route::get('{product}/departures', [AdminCatalogController::class, 'departures'])->whereNumber('product')->middleware('permission:admin,raonslab-travel_lab.catalog.read')->name('admin.catalog.departures.index');
    Route::post('{product}/departures', [AdminCatalogController::class, 'storeDeparture'])->whereNumber('product')->middleware('permission:admin,raonslab-travel_lab.catalog.update')->name('admin.catalog.departures.store');
    Route::put('{product}/departures/{departure}', [AdminCatalogController::class, 'updateDeparture'])->whereNumber('product')->whereNumber('departure')->middleware('permission:admin,raonslab-travel_lab.catalog.update')->name('admin.catalog.departures.update');
});
