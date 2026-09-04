<?php

use Illuminate\Support\Facades\Route;
use Modules\LeadManagement\Http\Controllers\Api\V1\Provider\HuntingBoardController;

Route::group(['prefix' => 'provider', 'middleware' => ['auth:api']], function () {
    Route::get('open-request', [HuntingBoardController::class, 'index']);
    Route::get('open-request/pending-count', [HuntingBoardController::class, 'pendingCount']);
    Route::post('open-request/interest', [HuntingBoardController::class, 'interest']);
    Route::post('open-request/reject', [HuntingBoardController::class, 'reject']);
    Route::post('open-request/withdraw', [HuntingBoardController::class, 'withdraw']);
});
