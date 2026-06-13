<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentModule\Http\Controllers\Api\V1\Customer\BonusController;
use Modules\PaymentModule\Http\Controllers\Api\V1\Customer\OfflinePaymentController;
use Modules\PaymentModule\Http\Controllers\Api\V1\Admin\PaymentConfigController;
use Modules\PaymentModule\Http\Controllers\Api\V1\PaymentAccessTokenController;

Route::post('customer/payment/access-token', [PaymentAccessTokenController::class, 'issueForCustomer']);
Route::post('provider/payment/access-token', [PaymentAccessTokenController::class, 'issueForProvider'])->middleware('auth:api');

Route::group(['prefix' => 'admin', 'as'=>'admin.', 'namespace' => 'Api\V1\Admin','middleware'=>['auth:api', 'admin.api']], function () {
    Route::group(['prefix'=>'payment-config'],function (){
        Route::get('get', [PaymentConfigController::class, 'payment_config_get']);
        Route::put('set', [PaymentConfigController::class, 'payment_config_set']);
    });
});

Route::get('customer/offline-payment/methods', [OfflinePaymentController::class, 'getMethods']);

Route::group(['prefix' => 'customer', 'as'=>'customer.', 'middleware'=>['auth:api']], function () {
    Route::get('bonus-list', [BonusController::class, 'getBonuses']);
});
