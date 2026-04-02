<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Internal\WhatsAppSyncController;
use Modules\WhatsAppModule\Http\Controllers\Api\WhatsAppMarketingWebhookController;

Route::get('webhooks/whatsapp-marketing', [WhatsAppMarketingWebhookController::class, 'verify']);
Route::post('webhooks/whatsapp-marketing', [WhatsAppMarketingWebhookController::class, 'handle']);

Route::prefix('internal/whatsapp')
    ->middleware([\App\Http\Middleware\InternalWhatsAppToken::class])
    ->group(function () {
        Route::post('message', [WhatsAppSyncController::class, 'message']);
        Route::post('message-status', [WhatsAppSyncController::class, 'messageStatus']);
        Route::post('has-new-messages', [WhatsAppSyncController::class, 'hasNewMessages']);
        Route::post('messages-since-last-out', [WhatsAppSyncController::class, 'messagesSinceLastOut']);
        Route::post('last-messages', [WhatsAppSyncController::class, 'lastMessages']);
        Route::post('conversation', [WhatsAppSyncController::class, 'conversation']);
        Route::post('conversation-update', [WhatsAppSyncController::class, 'updateConversation']);
        Route::post('booking-details', [WhatsAppSyncController::class, 'bookingDetails']);
        Route::post('active-booking', [WhatsAppSyncController::class, 'activeBooking']);
        Route::post('active-provider-lead', [WhatsAppSyncController::class, 'activeProviderLead']);
        Route::post('provider-lead-details', [WhatsAppSyncController::class, 'providerLeadDetails']);
        Route::post('active-conversation', [WhatsAppSyncController::class, 'activeConversation']);
        Route::post('booking', [WhatsAppSyncController::class, 'booking']);
        Route::post('provider-lead', [WhatsAppSyncController::class, 'providerLead']);
        Route::post('user', [WhatsAppSyncController::class, 'user']);
        Route::post('user-exists', [WhatsAppSyncController::class, 'userExists']);
        Route::post('user-details', [WhatsAppSyncController::class, 'userDetails']);
    });
