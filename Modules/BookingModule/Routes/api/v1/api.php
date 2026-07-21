<?php

use Illuminate\Support\Facades\Route;
use Modules\BookingModule\Http\Controllers\Api\V1\Customer\AppCustomRequestController;
use Modules\BookingModule\Http\Controllers\Api\V1\Customer\BookingController;
use Modules\BookingModule\Http\Controllers\Api\V1\Public\WebBookingController as PublicWebBookingController;
use Modules\BookingModule\Http\Controllers\Api\V1\Public\WebProviderRequestController as PublicWebProviderRequestController;
use Modules\BookingModule\Http\Controllers\Api\V1\Provider\BookingController as ProviderBookingController;
use Modules\BookingModule\Http\Controllers\Api\V1\Serviceman\BookingController as ServicemanBookingController;
use Modules\BookingModule\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;

Route::group(['prefix' => 'public', 'as' => 'public.', 'namespace' => 'Api\V1\Public'], function () {
    Route::post('web-booking/submit', [PublicWebBookingController::class, 'submit'])
        ->middleware('throttle:20,1')
        ->name('web-booking.submit');
    Route::post('web-provider-request/submit', [PublicWebProviderRequestController::class, 'submit'])
        ->middleware('throttle:20,1')
        ->name('web-provider-request.submit');
});

Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer', 'middleware' => ['auth:api']], function () {
    Route::post('custom-request/submit', [AppCustomRequestController::class, 'submit'])
        ->middleware('throttle:20,1')
        ->name('custom-request.submit');
    Route::get('custom-request/list', [AppCustomRequestController::class, 'index'])
        ->name('custom-request.list');
    Route::get('custom-request/{id}', [AppCustomRequestController::class, 'show'])
        ->whereNumber('id')
        ->name('custom-request.show');
    Route::post('custom-request/{id}/reply', [AppCustomRequestController::class, 'reply'])
        ->whereNumber('id')
        ->middleware('throttle:30,1')
        ->name('custom-request.reply');

    Route::group(['prefix' => 'booking', 'as' => 'booking.'], function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::get('customer-cancellation-reasons', [BookingController::class, 'customerCancellationReasons']);
        Route::get('single/{booking_id}', [BookingController::class, 'singleDetails'])->whereUuid('booking_id');
        Route::post('request/send', [BookingController::class, 'placeRequest'])->middleware('hitLimiter')->withoutMiddleware('auth:api');
        Route::match(['put', 'post'], 'status-update/{booking_id}', [BookingController::class, 'statusUpdate'])->whereUuid('booking_id');
        Route::post('single-repeat-cancel/{repeat_id}', [BookingController::class, 'singleBookingCancel']);
        Route::post('track/{readable_id}/access-token', [BookingController::class, 'trackAccessToken'])->withoutMiddleware('auth:api')->middleware('throttle:booking-track');
        Route::post('track/{readable_id}', [BookingController::class, 'track'])->withoutMiddleware('auth:api')->middleware('throttle:booking-track');
        Route::post('store-offline-payment-data', [BookingController::class, 'storeOfflinePaymentData'])->withoutMiddleware('auth:api');
        Route::post('switch-payment-method', [BookingController::class, 'switchPaymentMethod'])->withoutMiddleware('auth:api');
        Route::get('/{booking_id}/invoice-url', [BookingController::class, 'invoiceUrl'])->whereUuid('booking_id');
        Route::get('/{booking_id}', [BookingController::class, 'show'])->whereUuid('booking_id');
    });
});
Route::any('digital-payment-booking-response', [BookingController::class, 'digitalPaymentBookingResponse']);

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:api', 'admin.api']], function () {
    Route::group(['prefix' => 'booking', 'as' => 'booking.'], function () {
        Route::post('/', [AdminBookingController::class, 'index']);
        Route::get('{id}', [AdminBookingController::class, 'show']);
        Route::put('status-update/{booking_id}', [AdminBookingController::class, 'status_update']);
        Route::put('schedule-update/{booking_id}', [AdminBookingController::class, 'schedule_update']);
        Route::get('data/download', [AdminBookingController::class, 'download']);
    });
});

Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => 'Api\V1\Provider', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'booking', 'as' => 'booking.'], function () {
        Route::post('/', [ProviderBookingController::class, 'index']);
        Route::get('single/{id}', [ProviderBookingController::class, 'singleDetails'])->whereUuid('id');
        Route::get('provider-cancellation-reasons', [ProviderBookingController::class, 'providerCancellationReasons']);
        Route::get('provider-hold-reasons', [ProviderBookingController::class, 'providerHoldReasons']);
        Route::get('data/download', [ProviderBookingController::class, 'download']);
        Route::get('opt/notification-send', [ProviderBookingController::class, 'notificationSend']);
        Route::get('service/info', [ProviderBookingController::class, 'getServiceInfo']);
        Route::get('calendar/view', [ProviderBookingController::class, 'bookingCalendar']);
        Route::put('request-accept/{booking_id}', [ProviderBookingController::class, 'requestAccept']);
        Route::post('request-ignore/{booking_id}', [ProviderBookingController::class, 'requestIgnore']);
        Route::post('single-repeat-cancel/{repeat_id}', [ProviderBookingController::class, 'singleBookingCancel']);
        Route::put('single-repeat-status-update/{repeat_id}', [ProviderBookingController::class, 'singleBookingStatusUpdate']);
        Route::put('status-update/{booking_id}', [ProviderBookingController::class, 'statusUpdate']);
        Route::post('record-payment/{booking_id}', [ProviderBookingController::class, 'recordPayment']);
        Route::put('schedule-update/{booking_id}', [ProviderBookingController::class, 'scheduleUpdate']);
        Route::put('assign-serviceman/{booking_id}', [ProviderBookingController::class, 'assignServiceman']);
        Route::put('service/edit/update-booking', [ProviderBookingController::class, 'updateBooking']);
        Route::put('repeat/service/edit/update-booking', [ProviderBookingController::class, 'updateBookingRepeat']);
        Route::put('service/edit/remove-service', [ProviderBookingController::class, 'removeService']);
        Route::post('change-service-location', [ProviderBookingController::class, 'changeServiceLocation']);
        Route::get('{id}/invoice-url', [ProviderBookingController::class, 'invoiceUrl'])->whereUuid('id');
        Route::get('{id}', [ProviderBookingController::class, 'show'])->whereUuid('id');

    });
});


Route::group(['prefix' => 'serviceman', 'as' => 'serviceman.', 'namespace' => 'Api\V1\Serviceman', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'booking', 'as' => 'booking.'], function () {
        Route::put('status-update/{booking_id}', [ServicemanBookingController::class, 'statusUpdate']);
        Route::put('single-repeat-status-update/{booking_id}', [ServicemanBookingController::class, 'singleBookingStatusUpdate']);
        Route::put('payment-status-update/{booking_id}', [ServicemanBookingController::class, 'paymentStatusUpdate']);
        Route::get('list', [ServicemanBookingController::class, 'bookingList']);
        Route::get('detail/{id}', [ServicemanBookingController::class, 'bookingDetails']);
        Route::get('single/detail/{id}', [ServicemanBookingController::class, 'singleBookingDetails']);
        Route::get('opt/notification-send', [ServicemanBookingController::class, 'notificationSend']);
        Route::get('service/info', [ServicemanBookingController::class, 'getServiceInfo']);
        Route::put('service/edit/update-booking', [ServicemanBookingController::class, 'updateBooking']);
        Route::put('repeat/service/edit/update-booking', [ServicemanBookingController::class, 'updateBookingRepeat']);
        Route::put('service/edit/remove-service', [ServicemanBookingController::class, 'removeService']);
    });
});
