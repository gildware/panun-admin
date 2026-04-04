<?php

use Illuminate\Support\Facades\Route;
use Modules\BookingModule\Http\Controllers\Web\Admin\BookingConfigurationController;
use Modules\BookingModule\Http\Controllers\Web\Admin\BookingController;
use Modules\BookingModule\Http\Controllers\Web\Provider\BookingController as ProviderBookingController;


Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin', 'actch:admin_panel']], function () {
    Route::group(['prefix' => 'booking', 'as' => 'booking.'], function () {
        Route::match(['get', 'post'], 'create', [BookingController::class, 'create'])->name('create');
        Route::get('create/from-lead/{lead}', [BookingController::class, 'createFromLead'])->name('create-from-lead');
        Route::post('preview', [BookingController::class, 'preview'])->name('preview');
        Route::post('store', [BookingController::class, 'store'])->name('store');
        Route::get('success/{id}', [BookingController::class, 'success'])->name('success');
        Route::any('list/special-scenarios', [BookingController::class, 'specialScenarioBookings'])->name('list.special_scenarios');
        Route::any('list', [BookingController::class, 'index'])->name('list');
        Route::any('list/verification', [BookingController::class, 'bookingVerificationList'])->name('list.verification');
        Route::any('list/verification/download', [BookingController::class, 'downloadBookingVerificationList'])->name('list.verification.download');
        Route::any('list/offline-payment', [BookingController::class, 'bookingOfflinePaymentList'])->name('offline.payment');
        Route::get('check', [BookingController::class, 'checkBooking'])->name('check');
        Route::get('details/{id}', [BookingController::class, 'details'])->name('details');
        Route::get('todays-followups', [BookingController::class, 'todaysFollowups'])->name('todays_followups');
        Route::post('followup/{id}', [BookingController::class, 'storeFollowup'])->name('followup.store');
        Route::put('followup/{id}/{followupId}', [BookingController::class, 'updateFollowup'])->name('followup.update');
        Route::get('repeat-details/{id}', [BookingController::class, 'repeatDetails'])->name('repeat_details');
        Route::get('repeat-single-details/{id}', [BookingController::class, 'repeatSingleDetails'])->name('repeat_single_details');
        Route::match(['get', 'post'], 'status-update/{id}', [BookingController::class, 'statusUpdate'])->name('status_update');
        Route::post('financial-settlement/preview/{id}', [BookingController::class, 'financialSettlementPreview'])->name('financial_settlement.preview');
        Route::post('financial-settlement/save/{id}', [BookingController::class, 'financialSettlementSave'])->name('financial_settlement.save');
        Route::post('financial-settlement/save-and-cancel/{id}', [BookingController::class, 'financialSettlementSaveAndCancel'])->name('financial_settlement.save_and_cancel');
        Route::post('financial-settlement/save-and-complete/{id}', [BookingController::class, 'financialSettlementSaveAndComplete'])->name('financial_settlement.save_and_complete');
        Route::post('up-coming-booking-cancel/{id}', [BookingController::class, 'upComingBookingCancel'])->name('up_coming_booking_cancel');
        Route::get('configuration', [BookingConfigurationController::class, 'index'])->middleware(['can:booking_configuration_view'])->name('configuration.index');
        Route::post('configuration', [BookingConfigurationController::class, 'store'])->middleware(['can:booking_configuration_add'])->name('configuration.store');
        Route::put('configuration/{id}', [BookingConfigurationController::class, 'update'])->middleware(['can:booking_configuration_update'])->name('configuration.update');
        Route::delete('configuration/{id}', [BookingConfigurationController::class, 'destroy'])->middleware(['can:booking_configuration_delete'])->name('configuration.destroy');
        Route::get('verification-status-update/{id}', [BookingController::class, 'verificationUpdate'])->name('verification_status_update');
        Route::post('verification-status/{id}', [BookingController::class, 'verificationStatus'])->name('verification-status');
        Route::get('payment-update/{id}', [BookingController::class, 'paymentUpdate'])->name('payment_update');
        Route::any('schedule-update/{id}', [BookingController::class, 'scheduleUpdate'])->name('schedule_update');
        Route::any('up-coming-booking-schedule-update/{id}', [BookingController::class, 'upComingBookingScheduleUpdate'])->name('up_coming_booking_schedule_update');
        Route::put('serviceman-update/{id}', [BookingController::class, 'servicemanUpdate'])->name('serviceman_update');
        Route::put('info-update/{id}', [BookingController::class, 'updateBookingInfo'])->name('info-update');
        Route::post('extra-service/{id}', [BookingController::class, 'storeExtraService'])->name('extra-service.store');
        Route::put('additional-charges/{id}', [BookingController::class, 'updateBookingAdditionalCharges'])->name('additional-charges.update');
        Route::delete('extra-service/{id}/{extraId}', [BookingController::class, 'destroyExtraService'])->name('extra-service.destroy');
        Route::post('service-address-update/{id}', [BookingController::class, 'serviceAddressUpdate'])->name('service_address_update');
        Route::any('download', [BookingController::class, 'download'])->name('download');
        Route::any('invoice/{id}', [BookingController::class, 'invoice'])->name('invoice');
        Route::any('single-repeat-invoice/{id}', [BookingController::class, 'fullBookingSingleInvoice'])->name('single_invoice');
        Route::any('full-repeat-invoice/{id}', [BookingController::class, 'fullBookingInvoice'])->name('full_repeat_invoice');
        Route::any('customer-fullbooking-single-invoice/{id}/{lang}', [BookingController::class, 'customerFullBookingSingleInvoice'])->withoutMiddleware('admin');
        Route::any('customer-fullbooking-invoice/{id}/{lang}', [BookingController::class, 'customerFullBookingInvoice'])->withoutMiddleware('admin');
        Route::any('provider-fullbooking-single-invoice/{id}/{lang}', [BookingController::class, 'providerFullBookingSingleInvoice'])->withoutMiddleware('admin');
        Route::any('provider-fullbooking-invoice/{id}/{lang}', [BookingController::class, 'providerFullBookingInvoice'])->withoutMiddleware('admin');
        Route::any('serviceman-fullbooking-single-invoice/{id}/{lang}', [BookingController::class, 'servicemanFullBookingSingleInvoice'])->withoutMiddleware('admin');
        Route::any('customer-invoice/{id}/{lang}', [BookingController::class, 'customerInvoice'])->withoutMiddleware('admin');
        Route::any('provider-invoice/{id}/{lang}', [BookingController::class, 'providerInvoice'])->withoutMiddleware('admin');
        Route::any('serviceman-invoice/{id}/{lang}', [BookingController::class, 'servicemanInvoice'])->withoutMiddleware('admin');

        Route::any('switch-payment-method/{id}', [BookingController::class, 'switchPaymentMethod'])->name('switch-payment-method');
        Route::any('offline-payment/verify', [BookingController::class, 'verifyOfflinePayment'])->name('offline-payment.verify');
        Route::post('add-payment/{id}', [BookingController::class, 'addPayment'])->name('add-payment');
        Route::post('refund/{id}', [BookingController::class, 'refund'])->name('refund');
        Route::post('reopen/{id}', [BookingController::class, 'reopenFromCompleted'])->name('reopen');
        Route::post('reopen-resolve/{id}', [BookingController::class, 'resolveReopenTicket'])->name('reopen-resolve');

        Route::delete('delete/{id}', [BookingController::class, 'destroy'])->name('delete');

        Route::group(['prefix' => 'service', 'as' => 'service.'], function () {
            Route::put('update-booking-service', [BookingController::class, 'updateBookingService'])->name('update_booking_service');
            Route::put('update-repeat-booking-service', [BookingController::class, 'updateRepeatBookingService'])->name('update_repeat_booking_service');
            Route::get('ajax-get-service-info', [BookingController::class, 'ajaxGetServiceInfo'])->name('ajax-get-service-info');
            Route::get('ajax-get-variation', [BookingController::class, 'ajaxGetVariant'])->name('ajax-get-variant');
            Route::get('ajax-get-billing-summary', [BookingController::class, 'ajaxGetBillingSummary'])->name('ajax-get-billing-summary');
            Route::post('ajax-create-booking-cart-summary', [BookingController::class, 'ajaxCreateBookingCartSummary'])->name('ajax-create-booking-cart-summary');
            Route::get('ajax-get-categories', [BookingController::class, 'ajaxGetCategories'])->name('ajax-get-categories');
            Route::get('ajax-get-subcategories', [BookingController::class, 'ajaxGetSubcategories'])->name('ajax-get-subcategories');
            Route::get('ajax-get-services', [BookingController::class, 'ajaxGetServices'])->name('ajax-get-services');
            Route::get('ajax-get-providers', [BookingController::class, 'ajaxGetProviders'])->name('ajax-get-providers');
        });

        Route::get('rebooking/details/{id}', [BookingController::class, 'reBookingDetails'])->name('rebooking.details');
        Route::get('rebooking/ongoing/{id}', [BookingController::class, 'reBookingOngoing'])->name('rebooking.ongoing');

        Route::post('change-service-location/{id}', [BookingController::class, 'changeServiceLocation'])->name('change-service-location');
        Route::post('repeat-change-service-location/{id}', [BookingController::class, 'repeatChangeServiceLocation'])->name('repeat.change-service-location');
    });
});

Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => 'Web\Provider', 'middleware' => ['provider']], function () {

    Route::group(['prefix' => 'booking', 'as' => 'booking.'], function () {
        Route::any('list', [ProviderBookingController::class, 'index'])->name('list');
        Route::get('check', [ProviderBookingController::class, 'checkBooking'])->name('check');
        Route::get('details/{id}', [ProviderBookingController::class, 'details'])->name('details');
        Route::get('repeat-details/{id}', [ProviderBookingController::class, 'repeatDetails'])->name('repeat_details');
        Route::get('repeat-single-details/{id}', [ProviderBookingController::class, 'repeatSingleDetails'])->name('repeat_single_details');
        Route::get('request-accept/{booking_id}', [ProviderBookingController::class, 'requestAccept'])->name('accept');
        Route::get('request-ignore/{booking_id}', [ProviderBookingController::class, 'requestIgnore'])->name('ignore');
        Route::any('status-update/{id}', [ProviderBookingController::class, 'statusUpdate'])->name('status_update');
        Route::any('payment-update/{id}', [ProviderBookingController::class, 'paymentUpdate'])->name('payment_update');
        Route::any('schedule-update/{id}', [ProviderBookingController::class, 'scheduleUpdate'])->name('schedule_update');
        Route::put('serviceman-update/{id}', [ProviderBookingController::class, 'servicemanUpdate'])->name('serviceman_update');
        Route::put('service-address-update/{id}', [BookingController::class, 'serviceAddressUpdate'])->name('service_address_update');
        Route::get('up-coming-booking-cancel/{id}', [ProviderBookingController::class, 'upComingBookingCancel'])->name('up_coming_booking_cancel');
        Route::any('up-coming-booking-schedule-update/{id}', [ProviderBookingController::class, 'upComingBookingScheduleUpdate'])->name('up_coming_booking_schedule_update');
        Route::any('download', [ProviderBookingController::class, 'download'])->name('download');
        Route::any('invoice/{id}', [ProviderBookingController::class, 'invoice'])->name('invoice');
        Route::any('single-repeat-invoice/{id}', [ProviderBookingController::class, 'fullBookingSingleInvoice'])->name('single_invoice');
        Route::any('full-repeat-invoice/{id}', [ProviderBookingController::class, 'fullBookingInvoice'])->name('full_repeat_invoice');
        Route::post('evidence-photos-upload/{id}', [ProviderBookingController::class, 'evidencePhotosUpload'])->name('evidence_photos_upload');
        Route::get('otp/resend', [ProviderBookingController::class, 'resendOtp'])->name('otp.resend');

        Route::group(['prefix' => 'service', 'as' => 'service.'], function () {
            Route::put('update-booking-service', [ProviderBookingController::class, 'updateBookingService'])->name('update_booking_service');
            Route::put('update-repeat-booking-service', [ProviderBookingController::class, 'updateRepeatBookingService'])->name('update_repeat_booking_service');
            Route::get('ajax-get-service-info', [ProviderBookingController::class, 'ajaxGetServiceInfo'])->name('ajax-get-service-info');
            Route::get('ajax-get-variation', [ProviderBookingController::class, 'ajaxGetVariant'])->name('ajax-get-variant');
        });

        Route::post('change-service-location/{id}', [ProviderBookingController::class, 'changeServiceLocation'])->name('change-service-location');
        Route::post('repeat-change-service-location/{id}', [ProviderBookingController::class, 'repeatChangeServiceLocation'])->name('repeat.change-service-location');
        Route::get('calendar-view', [ProviderBookingController::class, 'calendarView'])->name('calendar.view');
        Route::get('calendar-events', [ProviderBookingController::class, 'calendarEvents'])->name('calendar.events');
        Route::get('calendar-events/bookings', [ProviderBookingController::class, 'getCalendarBookingList'])->name('calendar.events.bookings');
    });
});
