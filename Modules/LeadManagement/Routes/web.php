<?php

use Illuminate\Support\Facades\Route;
use Modules\LeadManagement\Http\Controllers\Web\Admin\AdSourceController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadConfigurationController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadFollowupController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadOutboundEnquiryController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadReportController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\SourceController;

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'middleware' => ['admin', 'actch:admin_panel'],
], function () {
    Route::group(['prefix' => 'lead', 'as' => 'lead.'], function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::get('create', [LeadController::class, 'create'])->name('create');
        Route::post('store', [LeadController::class, 'store'])->name('store');

        Route::group(['prefix' => 'outbound-enquiry', 'as' => 'outbound-enquiry.'], function () {
            Route::get('/', [LeadOutboundEnquiryController::class, 'index'])->name('index');
            Route::get('create', [LeadOutboundEnquiryController::class, 'create'])->name('create');
            Route::post('store', [LeadOutboundEnquiryController::class, 'store'])->name('store');
        });

        // Reports routes should come before parameterized {id} routes
        Route::get('reports', [LeadReportController::class, 'index'])->name('reports.index');
        Route::get('reports/download', [LeadReportController::class, 'download'])->name('reports.download');

        // Today's pending follow-ups
        Route::get('todays-followups', [LeadFollowupController::class, 'todaysFollowups'])->name('todays_followups');

        Route::post('{id}/type', [LeadController::class, 'updateType'])->name('type.update');
        Route::post('{lead}/followups', [LeadController::class, 'storeFollowup'])->name('followups.store');
        Route::put('{id}', [LeadController::class, 'update'])->name('update');
        Route::delete('{id}', [LeadController::class, 'destroy'])->name('destroy');

        Route::get('configuration', [LeadConfigurationController::class, 'index'])->name('configuration.index');
        Route::post('configuration', [LeadConfigurationController::class, 'store'])->name('configuration.store');
        Route::put('configuration/{id}', [LeadConfigurationController::class, 'update'])->name('configuration.update');
        Route::delete('configuration/{id}', [LeadConfigurationController::class, 'destroy'])->name('configuration.destroy');

        Route::put('{id}/checklist', [LeadController::class, 'updateProviderChecklistBulk'])->name('checklist.update.bulk');
        Route::put('{id}/checklist/{checklistItem}', [LeadController::class, 'updateProviderChecklist'])->name('checklist.update');
        Route::put('{id}/provider-status', [LeadController::class, 'updateProviderStatus'])->name('provider-status.update');
        Route::put('{id}/customer-status', [LeadController::class, 'updateCustomerStatus'])->name('customer-status.update');
        Route::put('{id}/customer-tags', [LeadController::class, 'updateCustomerTags'])->name('customer-tags.update');
        Route::post('customer-tag', [LeadController::class, 'storeCustomerLeadTag'])->name('customer-tag.store');

        Route::get('{id}', [LeadController::class, 'show'])->name('show');
    });
});
