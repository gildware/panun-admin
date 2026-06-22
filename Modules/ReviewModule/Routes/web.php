<?php

use Illuminate\Support\Facades\Route;
use Modules\ReviewModule\Http\Controllers\Web\Admin\CustomerReviewController;
use Modules\ReviewModule\Http\Controllers\Web\Admin\ServiceReviewController;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['admin']], function () {
    Route::any('customer/customer-review-status-update/{id}', [CustomerReviewController::class, 'statusUpdate'])
        ->name('customer.customer-review-status-update');
    Route::any('customer/customer-review-approve/{id}', [CustomerReviewController::class, 'approve'])
        ->name('customer.customer-review-approve');
    Route::any('customer/customer-review-delete/{id}', [CustomerReviewController::class, 'destroy'])
        ->name('customer.customer-review-delete');

    Route::any('service/review-approve/{id}', [ServiceReviewController::class, 'approve'])
        ->name('service.review-approve');
    Route::any('service/review-delete/{id}', [ServiceReviewController::class, 'destroy'])
        ->name('service.review-delete');
});
