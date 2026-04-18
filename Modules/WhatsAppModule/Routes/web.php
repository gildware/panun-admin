<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsAppModule\Http\Middleware\EnsureSocialInboxMarketingIsWhatsapp;
use Modules\WhatsAppModule\Http\Middleware\SetSocialInboxChannelFromRoute;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppBookingTemplateController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppConversationTemplateController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppMarketingBulkSendController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppMarketingCampaignController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppMarketingReportController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppAiPlaygroundController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppAiSettingsController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppMarketingTemplateController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppChatConfigController;

Route::middleware(['admin', 'actch:admin_panel'])
    ->prefix('admin')
    ->group(function () {
        Route::any('whatsapp/{any?}', function (?string $any = null) {
            $path = $any !== null && $any !== '' ? '/'.ltrim($any, '/') : '';
            $qs = request()->getQueryString();

            return redirect()->to(url('/admin/social-inbox/whatsapp'.$path).($qs ? '?'.$qs : ''));
        })->where('any', '.*');
    });

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.whatsapp.',
    'namespace' => 'Web\Admin',
    'middleware' => ['admin', 'actch:admin_panel'],
], function () {
    Route::group([
        'prefix' => 'social-inbox/{channel}',
        'where' => ['channel' => 'whatsapp|instagram|facebook'],
        'middleware' => [SetSocialInboxChannelFromRoute::class],
        'as' => '',
    ], function () {
        Route::get('conversations', [WhatsAppController::class, 'index'])->middleware(['can:whatsapp_chat_view'])->name('conversations.index');
        Route::post('conversations/bookings/cancel', [WhatsAppController::class, 'cancelWhatsAppBooking'])->middleware(['can:booking_add'])->name('conversations.bookings.cancel');
        Route::post('conversations/prepare-open-chat', [WhatsAppController::class, 'prepareOpenChat'])->middleware(['can:whatsapp_chat_view'])->name('conversations.prepare-open');
        Route::get('conversations/chat', [WhatsAppController::class, 'chat'])->middleware(['can:whatsapp_chat_view'])->name('conversations.chat');
        Route::post('conversations/chat/reply', [WhatsAppController::class, 'sendReply'])->middleware(['can:whatsapp_chat_reply'])->name('conversations.reply');
        Route::post('conversations/chat/reaction', [WhatsAppController::class, 'sendMessageReaction'])->middleware(['can:whatsapp_chat_reply'])->name('conversations.reaction');
        Route::get('conversations/chat/messages', [WhatsAppController::class, 'chatMessages'])->middleware(['can:whatsapp_chat_view'])->name('conversations.chat.messages');
        Route::get('conversations/chat/waba-templates', [WhatsAppController::class, 'chatWabaTemplates'])->middleware(['can:whatsapp_chat_view'])->name('conversations.chat.waba-templates');
        Route::post('conversations/chat/send-template', [WhatsAppController::class, 'sendChatTemplate'])->middleware(['can:whatsapp_chat_reply'])->name('conversations.chat.send-template');
        Route::get('conversations/search', [WhatsAppController::class, 'conversationsSearch'])->middleware(['can:whatsapp_chat_view'])->name('conversations.search');
        Route::get('conversations/active-chats-forward', [WhatsAppController::class, 'activeChatsForForward'])->middleware(['can:whatsapp_chat_reply'])->name('conversations.active-chats-forward');
        Route::post('conversations/handoff', [WhatsAppController::class, 'handoff'])->middleware(['can:whatsapp_chat_assign'])->name('conversations.handoff');
        Route::post('conversations/delete-history', [WhatsAppController::class, 'deleteChatHistory'])->middleware(['can:whatsapp_chat_delete'])->name('conversations.delete-history');
        Route::post('conversations/chat/thread-status', [WhatsAppController::class, 'updateThreadChatStatus'])->middleware(['can:whatsapp_chat_reply'])->name('conversations.thread-status');
        Route::post('conversations/chat/thread-tags', [WhatsAppController::class, 'updateThreadChatTags'])->middleware(['can:whatsapp_chat_reply'])->name('conversations.thread-tags');
        Route::post('conversations/chat-config/statuses', [WhatsAppChatConfigController::class, 'storeStatus'])->middleware(['can:whatsapp_message_template_update'])->name('chat-config.statuses.store');
        Route::put('conversations/chat-config/statuses/{status}', [WhatsAppChatConfigController::class, 'updateStatus'])->middleware(['can:whatsapp_message_template_update'])->name('chat-config.statuses.update');
        Route::delete('conversations/chat-config/statuses/{status}', [WhatsAppChatConfigController::class, 'destroyStatus'])->middleware(['can:whatsapp_message_template_update'])->name('chat-config.statuses.destroy');
        Route::post('conversations/chat-config/tags', [WhatsAppChatConfigController::class, 'storeTag'])->middleware(['can:whatsapp_message_template_update'])->name('chat-config.tags.store');
        Route::put('conversations/chat-config/tags/{tag}', [WhatsAppChatConfigController::class, 'updateTag'])->middleware(['can:whatsapp_message_template_update'])->name('chat-config.tags.update');
        Route::delete('conversations/chat-config/tags/{tag}', [WhatsAppChatConfigController::class, 'destroyTag'])->middleware(['can:whatsapp_message_template_update'])->name('chat-config.tags.destroy');
        Route::get('users/details', [WhatsAppController::class, 'userDetails'])->middleware(['can:whatsapp_chat_view'])->name('users.details');
        Route::get('booking-message-templates', [WhatsAppBookingTemplateController::class, 'edit'])->middleware(['can:whatsapp_message_template_view'])->name('booking-templates.edit');
        Route::get('booking-message-templates/automation-log', [WhatsAppBookingTemplateController::class, 'automationMessageLogs'])->middleware(['can:whatsapp_message_template_view'])->name('booking-templates.automation-log');
        Route::post('booking-message-templates/automation-log/clear', [WhatsAppBookingTemplateController::class, 'clearAutomationMessageLogs'])->middleware(['can:whatsapp_message_template_update'])->name('booking-templates.automation-log.clear');
        Route::post('booking-message-templates', [WhatsAppBookingTemplateController::class, 'update'])->middleware(['can:whatsapp_message_template_update'])->name('booking-templates.update');
        Route::post('booking-message-templates/toggle-enabled', [WhatsAppBookingTemplateController::class, 'toggleEnabled'])->middleware(['can:whatsapp_message_template_update'])->name('booking-templates.toggle-enabled');
        Route::post('booking-message-templates/toggle-message-send-enabled', [WhatsAppBookingTemplateController::class, 'toggleMessageSendEnabled'])->middleware(['can:whatsapp_message_template_update'])->name('booking-templates.toggle-message-send-enabled');
        Route::post('conversation-templates', [WhatsAppConversationTemplateController::class, 'store'])->middleware(['can:whatsapp_message_template_update'])->name('conversation-templates.store');
        Route::put('conversation-templates/{template}', [WhatsAppConversationTemplateController::class, 'update'])->middleware(['can:whatsapp_message_template_update'])->name('conversation-templates.update');
        Route::post('conversation-templates/{template}/toggle-active', [WhatsAppConversationTemplateController::class, 'toggleActive'])->middleware(['can:whatsapp_message_template_update'])->name('conversation-templates.toggle-active');
        Route::delete('conversation-templates/{template}', [WhatsAppConversationTemplateController::class, 'destroy'])->middleware(['can:whatsapp_message_template_update'])->name('conversation-templates.destroy');

        Route::get('ai-support', [WhatsAppAiSettingsController::class, 'edit'])->middleware(['can:whatsapp_chat_view'])->name('ai-settings.edit');
        Route::post('ai-support', [WhatsAppAiSettingsController::class, 'update'])->middleware(['can:whatsapp_chat_assign'])->name('ai-settings.update');
        Route::get('ai-support/playground/thread', [WhatsAppAiPlaygroundController::class, 'thread'])->middleware(['can:whatsapp_chat_assign'])->name('ai-playground.thread');
        Route::post('ai-support/playground', [WhatsAppAiPlaygroundController::class, 'run'])->middleware(['can:whatsapp_chat_assign'])->name('ai-playground.run');
        Route::post('ai-support/playground/reset', [WhatsAppAiPlaygroundController::class, 'reset'])->middleware(['can:whatsapp_chat_assign'])->name('ai-playground.reset');

        Route::group([
            'prefix' => 'marketing',
            'as' => 'marketing.',
            'middleware' => [EnsureSocialInboxMarketingIsWhatsapp::class],
        ], function () {
            Route::get('templates', [WhatsAppMarketingTemplateController::class, 'index'])->middleware(['can:whatsapp_marketing_template_view'])->name('templates.index');
            Route::get('templates/{template}/preview', [WhatsAppMarketingTemplateController::class, 'preview'])->middleware(['can:whatsapp_marketing_template_view'])->name('templates.preview');
            Route::post('templates/sync', [WhatsAppMarketingTemplateController::class, 'sync'])->middleware(['can:whatsapp_marketing_template_update'])->name('templates.sync');

            Route::get('send/sample-csv', [WhatsAppMarketingBulkSendController::class, 'sampleCsv'])->middleware(['can:whatsapp_marketing_bulk_view'])->name('bulk.sample-csv');
            Route::post('send/preview-recipients', [WhatsAppMarketingBulkSendController::class, 'previewRecipients'])->middleware(['can:whatsapp_marketing_bulk_view'])->name('bulk.preview-recipients');
            Route::post('send/preview-csv', [WhatsAppMarketingBulkSendController::class, 'previewCsv'])->middleware(['can:whatsapp_marketing_bulk_view'])->name('bulk.preview-csv');
            Route::get('send', [WhatsAppMarketingBulkSendController::class, 'create'])->middleware(['can:whatsapp_marketing_bulk_view'])->name('bulk.create');
            Route::post('send', [WhatsAppMarketingBulkSendController::class, 'store'])->middleware(['can:whatsapp_marketing_bulk_add'])->name('bulk.store');

            Route::get('campaigns', [WhatsAppMarketingCampaignController::class, 'index'])->middleware(['can:whatsapp_marketing_campaign_view'])->name('campaigns.index');
            Route::get('campaigns/{id}', [WhatsAppMarketingCampaignController::class, 'show'])->middleware(['can:whatsapp_marketing_campaign_view'])->name('campaigns.show');
            Route::post('campaigns/{id}/retry-failed', [WhatsAppMarketingCampaignController::class, 'retryFailed'])->middleware(['can:whatsapp_marketing_campaign_update'])->name('campaigns.retry-failed');
            Route::get('campaigns/{id}/export', [WhatsAppMarketingCampaignController::class, 'exportCsv'])->middleware(['can:whatsapp_marketing_campaign_view'])->name('campaigns.export');
            Route::get('campaigns/{id}/duplicate', [WhatsAppMarketingCampaignController::class, 'duplicate'])->middleware(['can:whatsapp_marketing_bulk_view'])->name('campaigns.duplicate');

            Route::get('reports', [WhatsAppMarketingReportController::class, 'index'])->middleware(['can:whatsapp_marketing_report_view'])->name('reports.index');
        });
    });
});
