<?php

use Illuminate\Support\Facades\Route;
use Modules\LeadManagement\Http\Controllers\Web\Admin\AdSourceController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadCommentController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadConfigurationController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadFollowupController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadOutboundEnquiryController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadReportController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\OmniDimensionVoiceCallController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\SourceController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\VoiceCallCronJobController;
use Modules\LeadManagement\Http\Controllers\Web\Admin\WhatsAppVoiceFollowupController;

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
            Route::get('search-leads', [LeadOutboundEnquiryController::class, 'searchLeads'])->middleware(['can:lead_outbound_enquiry_add'])->name('search-leads');
            Route::get('search-bookings', [LeadOutboundEnquiryController::class, 'searchBookings'])->middleware(['can:lead_outbound_enquiry_add'])->name('search-bookings');
            Route::post('store', [LeadOutboundEnquiryController::class, 'store'])->middleware(['can:lead_outbound_enquiry_add'])->name('store');
        });

        // Reports routes should come before parameterized {id} routes
        Route::get('reports/user', [LeadReportController::class, 'userReport'])->middleware(['can:lead_report_view'])->name('reports.user');
        Route::get('reports/inbound', [LeadReportController::class, 'inbound'])->middleware(['can:lead_report_view'])->name('reports.inbound');
        Route::get('reports/outbound', [LeadReportController::class, 'outbound'])->middleware(['can:lead_report_view'])->name('reports.outbound');
        Route::get('reports', [LeadReportController::class, 'index'])->middleware(['can:lead_report_view'])->name('reports.index');
        Route::post('reports/drilldown', [LeadReportController::class, 'getLeadReportDrilldown'])->middleware(['can:lead_report_view'])->name('reports.drilldown');
        Route::get('reports/download', [LeadReportController::class, 'download'])->middleware(['can:lead_report_export'])->name('reports.download');

        // Today's pending follow-ups
        Route::get('todays-followups', [LeadFollowupController::class, 'todaysFollowups'])->name('todays_followups');
        Route::get('search-providers', [LeadController::class, 'searchProvidersForLead'])->middleware(['can:lead_view'])->name('search-providers');

        Route::post('{lead}/comments', [LeadCommentController::class, 'store'])->middleware(['can:lead_update'])->name('comments.store');
        Route::put('comments/{comment}/pin', [LeadCommentController::class, 'togglePin'])->middleware(['can:lead_update'])->name('comments.pin');
        Route::delete('comments/{comment}', [LeadCommentController::class, 'destroy'])->middleware(['can:lead_update'])->name('comments.destroy');

        Route::post('{id}/type', [LeadController::class, 'updateType'])->middleware(['can:lead_update'])->name('type.update');
        Route::post('{lead}/followups', [LeadController::class, 'storeFollowup'])->middleware(['can:lead_update'])->name('followups.store');
        Route::post('{lead}/call-logs', [LeadController::class, 'storeCallLog'])->middleware(['can:lead_update'])->name('call-logs.store');
        Route::put('{lead}/call-logs/{followup}', [LeadController::class, 'updateCallLog'])->middleware(['can:lead_update'])->name('call-logs.update');
        Route::delete('{lead}/call-logs/{followup}', [LeadController::class, 'destroyCallLog'])->middleware(['can:lead_update'])->name('call-logs.destroy');
        Route::post('{lead}/followups/{followup}/transcribe', [LeadController::class, 'transcribeFollowupRecording'])->middleware(['can:lead_update'])->name('followups.transcribe');
        Route::post('{lead}/initial-call-recording/transcribe', [LeadController::class, 'transcribeInitialCallRecording'])->middleware(['can:lead_update'])->name('initial-call-recording.transcribe');
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
        Route::put('{id}/temporary-provider', [LeadController::class, 'updateTemporaryProvider'])->middleware(['can:lead_update'])->name('temporary-provider.update');
        Route::post('customer-tag', [LeadController::class, 'storeCustomerLeadTag'])->middleware(['can:lead_add'])->name('customer-tag.store');

        Route::post('{id}/outbound-enquiry', [LeadOutboundEnquiryController::class, 'storeFromLead'])
            ->middleware(['can:lead_outbound_enquiry_add'])
            ->name('outbound-enquiry.store-from-lead');

        Route::get('{id}', [LeadController::class, 'show'])->middleware(['can:lead_view'])->name('show');
    });

    Route::group(['prefix' => 'voice-call', 'as' => 'voice-call.', 'middleware' => ['can:lead_outbound_enquiry_view']], function () {
        Route::get('/', [OmniDimensionVoiceCallController::class, 'index'])->name('index');
        Route::get('/placed', [OmniDimensionVoiceCallController::class, 'placedCalls'])->name('placed');
        Route::post('/refresh-catalog', [OmniDimensionVoiceCallController::class, 'refreshCatalog'])->name('refresh-catalog');
        Route::get('/api-logs', [OmniDimensionVoiceCallController::class, 'apiLogs'])->name('api-logs');
        Route::get('/history', [OmniDimensionVoiceCallController::class, 'history'])->name('history');
        Route::get('/forwarded', [OmniDimensionVoiceCallController::class, 'forwardedCalls'])->name('forwarded');
        Route::get('/callback', [OmniDimensionVoiceCallController::class, 'callbackCalls'])->name('callback');
        Route::get('/bulk/campaigns', [OmniDimensionVoiceCallController::class, 'bulkCampaigns'])->name('bulk.campaigns');
        Route::get('/bulk/campaigns/{id}', [OmniDimensionVoiceCallController::class, 'bulkCampaignDetails'])->name('bulk.campaigns.show');
        Route::delete('/bulk/campaigns/{id}', [OmniDimensionVoiceCallController::class, 'cancelBulkCampaign'])
            ->middleware(['can:lead_outbound_enquiry_add'])
            ->name('bulk.campaigns.cancel');
        Route::post('/bulk/audience-preview', [OmniDimensionVoiceCallController::class, 'bulkAudiencePreview'])->name('bulk.audience-preview');
        Route::post('/bulk/audience-preview-csv', [OmniDimensionVoiceCallController::class, 'bulkAudiencePreviewCsv'])->name('bulk.audience-preview-csv');
        Route::get('/bulk/sample-csv', [OmniDimensionVoiceCallController::class, 'bulkSampleCsv'])->name('bulk.sample-csv');
        Route::delete('/history/{callLogId}', [OmniDimensionVoiceCallController::class, 'destroy'])
            ->middleware(['can:lead_outbound_enquiry_delete'])
            ->name('history.destroy');
        Route::get('/recording/{callLogId}', [OmniDimensionVoiceCallController::class, 'recording'])->name('recording');
        Route::post('/transcript/hinglish', [OmniDimensionVoiceCallController::class, 'translateTranscriptHinglish'])->name('transcript.hinglish');
        Route::post('/', [OmniDimensionVoiceCallController::class, 'store'])->middleware(['can:lead_outbound_enquiry_add'])->name('store');
        Route::post('/bulk', [OmniDimensionVoiceCallController::class, 'storeBulk'])->middleware(['can:lead_outbound_enquiry_add'])->name('bulk.store');
        Route::get('/whatsapp-followup/list', [WhatsAppVoiceFollowupController::class, 'list'])->name('whatsapp-followup.list');
        Route::get('/whatsapp-followup/conversation', [WhatsAppVoiceFollowupController::class, 'conversation'])->name('whatsapp-followup.conversation');
        Route::get('/whatsapp-followup/summary', [WhatsAppVoiceFollowupController::class, 'summary'])->name('whatsapp-followup.summary');
        Route::post('/whatsapp-followup/summary', [WhatsAppVoiceFollowupController::class, 'generateSummary'])->name('whatsapp-followup.summary.generate');
        Route::post('/whatsapp-followup/dispatch', [WhatsAppVoiceFollowupController::class, 'dispatch'])->middleware(['can:lead_outbound_enquiry_add'])->name('whatsapp-followup.dispatch');
        Route::get('/cron-jobs/runs', [VoiceCallCronJobController::class, 'runs'])->name('cron-jobs.runs');
        Route::get('/cron-jobs/runs/{run}', [VoiceCallCronJobController::class, 'runDetails'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.runs.show');
        Route::get('/cron-jobs/runs/{run}/dispatch-preview', [VoiceCallCronJobController::class, 'dispatchPreview'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.runs.dispatch-preview');
        Route::post('/cron-jobs/runs/{run}/dispatch', [VoiceCallCronJobController::class, 'approveRun'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.runs.dispatch');
        Route::post('/cron-jobs/runs/{run}/reject', [VoiceCallCronJobController::class, 'rejectRun'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.runs.reject');
        Route::post('/cron-jobs/preview-matches', [VoiceCallCronJobController::class, 'previewMatches'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.preview-matches');
        Route::post('/cron-jobs', [VoiceCallCronJobController::class, 'store'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.store');
        Route::put('/cron-jobs/{rule}', [VoiceCallCronJobController::class, 'update'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.update');
        Route::delete('/cron-jobs/{rule}', [VoiceCallCronJobController::class, 'destroy'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.destroy');
        Route::post('/cron-jobs/{rule}/stop', [VoiceCallCronJobController::class, 'stop'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.stop');
        Route::post('/cron-jobs/{rule}/start', [VoiceCallCronJobController::class, 'start'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.start');
        Route::post('/cron-jobs/{rule}/run', [VoiceCallCronJobController::class, 'runNow'])->middleware(['can:lead_outbound_enquiry_add'])->name('cron-jobs.run');
    });
});
