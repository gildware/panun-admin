<?php

use Illuminate\Support\Facades\Route;
use Modules\ChattingModule\Http\Controllers\Web\Admin\ChattingController;
use Modules\ChattingModule\Http\Controllers\Web\Provider\ChattingController as ProviderChattingController;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {
    Route::group(['prefix' => 'chat', 'as' => 'chat.'], function () {
        Route::get('index', [ChattingController::class, 'index'])->name('index');
        Route::get('channel-list', [ChattingController::class, 'channelList']);
        Route::get('referenced-channel-list', [ChattingController::class, 'referencedChannelList']);
        Route::get('open-staff/{staffId}', [ChattingController::class, 'openStaffConversation'])->name('open-staff');
        Route::get('open-staff-ajax/{staffId}', [ChattingController::class, 'openStaffConversationAjax'])->name('open-staff-ajax');
        Route::post('create-channel', [ChattingController::class, 'createChannel'])->name('create-channel');
        Route::post('send-message', [ChattingController::class, 'sendMessage'])->name('send-message');
        Route::post('toggle-pin', [ChattingController::class, 'togglePin'])->name('toggle-pin');
        Route::post('toggle-reaction', [ChattingController::class, 'toggleReaction'])->name('toggle-reaction');
        Route::post('delete-message', [ChattingController::class, 'deleteMessage'])->name('delete-message');
        Route::post('clear-conversation', [ChattingController::class, 'clearConversation'])->name('clear-conversation');
        Route::get('ajax-conversation', [ChattingController::class, 'conversation'])->name('ajax-conversation');
        Route::get('entity-search', [ChattingController::class, 'entitySearch'])->name('entity-search');
    });
});

Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => 'Web\Provider', 'middleware' => ['provider', 'subscription:chat']], function () {
    Route::group(['prefix' => 'chat', 'as' => 'chat.'], function () {
        Route::get('index', [ProviderChattingController::class, 'index'])->name('index');
        Route::get('channel-list', [ProviderChattingController::class, 'channelList']);
        Route::get('referenced-channel-list', [ProviderChattingController::class, 'referencedChannelList']);
        Route::post('create-channel', [ProviderChattingController::class, 'createChannel'])->name('create-channel');
        Route::post('send-message', [ProviderChattingController::class, 'sendMessage'])->name('send-message');
        Route::get('ajax-conversation', [ProviderChattingController::class, 'conversation'])->name('ajax-conversation');
    });
});
