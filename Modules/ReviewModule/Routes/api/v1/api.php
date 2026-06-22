<?php

use Illuminate\Support\Facades\Route;
use Modules\ReviewModule\Http\Controllers\Api\V1\Customer\ReviewController;
use Modules\ReviewModule\Http\Controllers\Api\V1\Provider\ReviewController as ProviderReviewController;
use Modules\ReviewModule\Http\Controllers\Api\V1\Provider\CustomerReviewController as ProviderCustomerReviewController;

Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => 'Api\V1\Provider', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'review', 'as' => 'review.',], function () {
        Route::get('list', [ProviderReviewController::class, 'index']);
        Route::get('data/search', [ProviderReviewController::class, 'search']);
    });

    Route::group(['prefix' => 'customer-review', 'as' => 'customer-review.',], function () {
        Route::get('/', [ProviderCustomerReviewController::class, 'index']);
        Route::post('submit', [ProviderCustomerReviewController::class, 'store']);
    });
});


Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'review', 'as' => 'review.',], function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::post('submit', [ReviewController::class, 'store']);
    });
});
