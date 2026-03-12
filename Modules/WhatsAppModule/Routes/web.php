<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppController;

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.whatsapp.',
    'namespace' => 'Web\Admin',
    'middleware' => ['admin', 'actch:admin_panel'],
], function () {
    Route::group(['prefix' => 'whatsapp', 'as' => ''], function () {
        Route::get('conversations', [WhatsAppController::class, 'index'])->name('conversations.index');
        Route::get('conversations/chat', [WhatsAppController::class, 'chat'])->name('conversations.chat');
        Route::post('conversations/chat/reply', [WhatsAppController::class, 'sendReply'])->name('conversations.reply');
        Route::get('conversations/chat/messages', [WhatsAppController::class, 'chatMessages'])->name('conversations.chat.messages');
        Route::post('conversations/handoff', [WhatsAppController::class, 'handoff'])->name('conversations.handoff');
        Route::get('users/details', [WhatsAppController::class, 'userDetails'])->name('users.details');
    });
});
