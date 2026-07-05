<?php

use Illuminate\Support\Facades\Route;
use Modules\InAppCallModule\Http\Controllers\Web\Admin\InAppCallMonitorController;

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'middleware' => ['admin'],
], function () {
    Route::group(['prefix' => 'in-app-calls', 'as' => 'in-app-calls.'], function () {
        Route::get('/', [InAppCallMonitorController::class, 'index'])->name('index');
        Route::get('active', [InAppCallMonitorController::class, 'activeCalls'])->name('active');
        Route::get('health', [InAppCallMonitorController::class, 'serviceHealth'])->name('health');
        Route::post('signaling-test', [InAppCallMonitorController::class, 'runSignalingTest'])->name('signaling-test');
    });
});
