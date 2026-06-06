<?php

use Illuminate\Support\Facades\Route;
use Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Admin\ConfigurationController as AdminConfigurationController;
use Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Admin\BusinessInformationController as AdminBusinessInformationController;
use Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Provider\BusinessInformationController as ProviderBusinessInformationController;
use Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Provider\ConfigurationController;
use Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Provider\SubscriptionPackageController;
use Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer\MobileAppAiChatController;
use Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer\MobileAppHomeController;


Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer'], function () {
    Route::group(['prefix' => 'mobile-app-home'], function () {
        Route::get('section/{key}/services', [MobileAppHomeController::class, 'sectionServices']);
        Route::get('section/{key}/providers', [MobileAppHomeController::class, 'sectionProviders']);
        Route::get('section/{key}/banners', [MobileAppHomeController::class, 'sectionBanners']);
        Route::get('section/{key}/categories', [MobileAppHomeController::class, 'sectionCategories']);
    });
});

Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'ai-chat', 'as' => 'ai-chat.'], function () {
        Route::get('conversation', [MobileAppAiChatController::class, 'conversation']);
        Route::post('send', [MobileAppAiChatController::class, 'send']);
        Route::post('booking-action', [MobileAppAiChatController::class, 'bookingAction']);
        Route::post('quick-intent', [MobileAppAiChatController::class, 'quickIntent']);
        Route::post('clear', [MobileAppAiChatController::class, 'clear']);
    });
});

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'business-settings'], function () {
        Route::get('get-business-information', [AdminBusinessInformationController::class, 'business_information_get']);
        Route::put('set-business-information', [AdminBusinessInformationController::class, 'business_information_set']);

        Route::get('get-service-setup', [AdminBusinessInformationController::class, 'service_setup_get']);
        Route::put('set-service-setup', [AdminBusinessInformationController::class, 'service_setup_set']);

        Route::get('get-pages-setup', [AdminBusinessInformationController::class, 'pages_setup_get']);
        Route::put('set-pages-setup', [AdminBusinessInformationController::class, 'pages_setup_set']);

        Route::get('get-notification-setting', [AdminConfigurationController::class, 'notification_settings_get']);
        Route::put('set-notification-setting', [AdminConfigurationController::class, 'notification_settings_set']);

        Route::get('get-email-config', [AdminConfigurationController::class, 'email_config_get']);
        Route::put('set-email-config', [AdminConfigurationController::class, 'email_config_set']);

        Route::get('get-third-party-config', [AdminConfigurationController::class, 'third_party_config_get']);
        Route::put('set-third-party-config', [AdminConfigurationController::class, 'third_party_config_set']);
    });
});

Route::group(['prefix' => 'provider', 'as' => 'provider.', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'business-settings'], function () {
        Route::get('get-business-settings', [ProviderBusinessInformationController::class, 'businessSettingsGet']);
        Route::put('set-business-settings', [ProviderBusinessInformationController::class, 'businessSettingsSet']);
    });
    Route::group(['prefix' => 'subscription', 'as' => 'subscription.'], function () {
        Route::get('transactions',  [SubscriptionPackageController::class, 'transactions']);

        Route::group(['prefix' => 'package', 'as' => 'package.'], function () {
            Route::get('list',  [SubscriptionPackageController::class, 'index'])->withoutMiddleware('auth:api');
            Route::get('subscriber-details',  [SubscriptionPackageController::class, 'subscriber']);
            Route::post('renew',  [SubscriptionPackageController::class, 'renew']);
            Route::post('shift',  [SubscriptionPackageController::class, 'shift']);
            Route::post('purchase',  [SubscriptionPackageController::class, 'purchase']);
            Route::post('commission',  [SubscriptionPackageController::class, 'commission']);
            Route::post('cancel',  [SubscriptionPackageController::class, 'cancel']);
        });
    });

    Route::group(['prefix' => 'configuration', 'as' => 'configuration.'], function () {
        Route::get('get-notification-setting',  [ConfigurationController::class, 'notificationSettingsGet']);
        Route::post('update-notification-status',  [ConfigurationController::class, 'updateStatus']);
    });
});
