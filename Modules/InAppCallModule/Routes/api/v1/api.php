<?php

use Illuminate\Support\Facades\Route;
use Modules\InAppCallModule\Http\Controllers\Api\V1\Admin\InAppCallLogController;
use Modules\InAppCallModule\Http\Controllers\Api\V1\InAppCallController;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:api', 'admin.api']], function () {
    Route::group(['prefix' => 'in-app-call'], function () {
        Route::get('logs', [InAppCallLogController::class, 'index']);
    });
});

Route::group(['prefix' => 'customer', 'as' => 'customer.', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'in-app-call'], function () {
        Route::get('config', [InAppCallController::class, 'config']);
        Route::middleware(['ensureInAppCallIsActive'])->group(function () {
            Route::get('pending', [InAppCallController::class, 'pending']);
            Route::get('history', [InAppCallController::class, 'history']);
            Route::post('initiate', [InAppCallController::class, 'initiate']);
            Route::get('{callId}', [InAppCallController::class, 'show']);
            Route::post('{callId}/accept', [InAppCallController::class, 'accept']);
            Route::post('{callId}/decline', [InAppCallController::class, 'decline']);
            Route::post('{callId}/cancel', [InAppCallController::class, 'cancel']);
            Route::post('{callId}/end', [InAppCallController::class, 'end']);
            Route::post('{callId}/missed', [InAppCallController::class, 'missed']);
            Route::post('{callId}/signals', [InAppCallController::class, 'postSignal']);
            Route::get('{callId}/signals', [InAppCallController::class, 'listSignals']);
        });
    });
});

Route::group(['prefix' => 'provider', 'as' => 'provider.', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'in-app-call'], function () {
        Route::get('config', [InAppCallController::class, 'config']);
        Route::middleware(['ensureInAppCallIsActive'])->group(function () {
            Route::get('pending', [InAppCallController::class, 'pending']);
            Route::get('history', [InAppCallController::class, 'history']);
            Route::post('initiate', [InAppCallController::class, 'initiate']);
            Route::get('{callId}', [InAppCallController::class, 'show']);
            Route::post('{callId}/accept', [InAppCallController::class, 'accept']);
            Route::post('{callId}/decline', [InAppCallController::class, 'decline']);
            Route::post('{callId}/cancel', [InAppCallController::class, 'cancel']);
            Route::post('{callId}/end', [InAppCallController::class, 'end']);
            Route::post('{callId}/missed', [InAppCallController::class, 'missed']);
            Route::post('{callId}/signals', [InAppCallController::class, 'postSignal']);
            Route::get('{callId}/signals', [InAppCallController::class, 'listSignals']);
        });
    });
});
