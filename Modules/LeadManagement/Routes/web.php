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
    'middleware' => ['admin'],
], function () {
    Route::group(['prefix' => 'lead', 'as' => 'lead.'], function () {
        Route::get('/', [LeadController::class, 'index'])->middleware(['can:lead_view'])->name('index');
        Route::get('create', [LeadController::class, 'create'])->middleware(['can:lead_add'])->name('create');
        Route::get('create/from-whatsapp-provider/{lead_id}', [LeadController::class, 'createFromWhatsAppProvider'])->middleware(['can:lead_add'])->name('create-from-whatsapp-provider');
        Route::post('store', [LeadController::class, 'store'])->middleware(['can:lead_add'])->name('store');
        Route::get('open-by-phone', [LeadController::class, 'openLeadsByPhone'])->middleware(['can:lead_add'])->name('open-by-phone');

        Route::group(['prefix' => 'outbound-enquiry', 'as' => 'outbound-enquiry.', 'middleware' => ['can:lead_outbound_enquiry_view']], function () {
            Route::get('/', [LeadOutboundEnquiryController::class, 'index'])->name('index');
            Route::get('create', [LeadOutboundEnquiryController::class, 'create'])->middleware(['can:lead_outbound_enquiry_add'])->name('create');
            Route::post('store', [LeadOutboundEnquiryController::class, 'store'])->middleware(['can:lead_outbound_enquiry_add'])->name('store');
        });

        // Reports routes should come before parameterized {id} routes
        Route::get('reports/user', [LeadReportController::class, 'userReport'])->middleware(['can:lead_report_view'])->name('reports.user');
        Route::get('reports', [LeadReportController::class, 'index'])->middleware(['can:lead_report_view'])->name('reports.index');
        Route::get('reports/download', [LeadReportController::class, 'download'])->middleware(['can:lead_report_export'])->name('reports.download');

        // Today's pending follow-ups
        Route::get('todays-followups', [LeadFollowupController::class, 'todaysFollowups'])->name('todays_followups');

        Route::post('{id}/type', [LeadController::class, 'updateType'])->middleware(['can:lead_update'])->name('type.update');
        Route::post('{lead}/followups', [LeadController::class, 'storeFollowup'])->middleware(['can:lead_update'])->name('followups.store');
        Route::put('{id}', [LeadController::class, 'update'])->middleware(['can:lead_update'])->name('update');
        Route::delete('{id}', [LeadController::class, 'destroy'])->middleware(['can:lead_delete'])->name('destroy');

        Route::get('configuration', [LeadConfigurationController::class, 'index'])->middleware(['can:lead_configuration_view'])->name('configuration.index');
        Route::post('configuration', [LeadConfigurationController::class, 'store'])->middleware(['can:lead_configuration_add'])->name('configuration.store');
        Route::put('configuration/{id}', [LeadConfigurationController::class, 'update'])->middleware(['can:lead_configuration_update'])->name('configuration.update');
        Route::delete('configuration/{id}', [LeadConfigurationController::class, 'destroy'])->middleware(['can:lead_configuration_delete'])->name('configuration.destroy');

        Route::put('{id}/checklist', [LeadController::class, 'updateProviderChecklistBulk'])->middleware(['can:lead_update'])->name('checklist.update.bulk');
        Route::put('{id}/checklist/{checklistItem}', [LeadController::class, 'updateProviderChecklist'])->middleware(['can:lead_update'])->name('checklist.update');
        Route::put('{id}/provider-status', [LeadController::class, 'updateProviderStatus'])->middleware(['can:lead_update'])->name('provider-status.update');
        Route::put('{id}/customer-status', [LeadController::class, 'updateCustomerStatus'])->middleware(['can:lead_update'])->name('customer-status.update');
        Route::put('{id}/customer-tags', [LeadController::class, 'updateCustomerTags'])->middleware(['can:lead_update'])->name('customer-tags.update');
        Route::post('customer-tag', [LeadController::class, 'storeCustomerLeadTag'])->middleware(['can:lead_add'])->name('customer-tag.store');

        Route::get('{id}', [LeadController::class, 'show'])->middleware(['can:lead_view'])->name('show');
    });
});
