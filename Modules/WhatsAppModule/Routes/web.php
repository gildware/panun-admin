<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppBookingTemplateController;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppController;

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.whatsapp.',
    'namespace' => 'Web\Admin',
    'middleware' => ['admin', 'actch:admin_panel'],
], function () {
    Route::group(['prefix' => 'whatsapp', 'as' => ''], function () {
        Route::get('conversations', [WhatsAppController::class, 'index'])->middleware(['can:whatsapp_chat_view'])->name('conversations.index');
        Route::get('conversations/chat', [WhatsAppController::class, 'chat'])->middleware(['can:whatsapp_chat_view'])->name('conversations.chat');
        Route::post('conversations/chat/reply', [WhatsAppController::class, 'sendReply'])->middleware(['can:whatsapp_chat_reply'])->name('conversations.reply');
        Route::get('conversations/chat/messages', [WhatsAppController::class, 'chatMessages'])->middleware(['can:whatsapp_chat_view'])->name('conversations.chat.messages');
        Route::post('conversations/handoff', [WhatsAppController::class, 'handoff'])->middleware(['can:whatsapp_chat_assign'])->name('conversations.handoff');
        Route::get('users/details', [WhatsAppController::class, 'userDetails'])->middleware(['can:whatsapp_chat_view'])->name('users.details');
        Route::get('booking-message-templates', [WhatsAppBookingTemplateController::class, 'edit'])->middleware(['can:whatsapp_message_template_view'])->name('booking-templates.edit');
        Route::post('booking-message-templates', [WhatsAppBookingTemplateController::class, 'update'])->middleware(['can:whatsapp_message_template_update'])->name('booking-templates.update');
    });
});
