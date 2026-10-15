<?php

use Illuminate\Support\Facades\Route;
use Modules\ZoneManagement\Http\Controllers\Web\Admin\ZoneController;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {
    Route::group(['prefix' => 'zone', 'as' => 'zone.'], function () {
        Route::any('create', [ZoneController::class, 'create'])->name('create');
        Route::post('store', [ZoneController::class, 'store'])->name('store');
        Route::get('areas', [ZoneController::class, 'areas'])->name('areas');
        Route::get('children/{id}', [ZoneController::class, 'children'])->name('children');
        Route::get('view/{id}', [ZoneController::class, 'view'])->name('view');
        Route::get('edit/{id}', [ZoneController::class, 'edit'])->name('edit');
        Route::put('update/{id}', [ZoneController::class, 'update'])->name('update');
        Route::put('get-active-zones/{id}', [ZoneController::class, 'getActiveZones'])->name('get-active-zones');
        Route::get('parent-geometry/{id}', [ZoneController::class, 'parentGeometry'])->name('parent-geometry');
        Route::any('status-update/{id}', [ZoneController::class, 'statusUpdate'])->name('status-update');
        Route::delete('delete/{id}', [ZoneController::class, 'destroy'])->name('delete');
        Route::get('download', [ZoneController::class, 'download'])->name('download');
        Route::get('table', [ZoneController::class, 'getTable'])->name('table');
        Route::get('coverage-map', [ZoneController::class, 'coverageMap'])->name('coverage-map');
        Route::get('boundary-from-place', [ZoneController::class, 'boundaryFromPlace'])->name('boundary-from-place');
    });
});
