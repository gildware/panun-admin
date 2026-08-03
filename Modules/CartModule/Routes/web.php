<?php

use Illuminate\Support\Facades\Route;
use Modules\CartModule\Http\Controllers\Web\Admin\CartController;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {
    Route::group(['prefix' => 'customer-cart', 'as' => 'customer-cart.'], function () {
        Route::redirect('/', '/admin/customer-cart/list');
        Route::any('list', [CartController::class, 'index'])->name('index');
        Route::get('detail/{id}', [CartController::class, 'show'])->name('detail');
        Route::any('download', [CartController::class, 'download'])->name('download');
        Route::post('mark-contacted/{id}', [CartController::class, 'markContacted'])->name('mark-contacted');
        Route::post('unmark-contacted/{id}', [CartController::class, 'unmarkContacted'])->name('unmark-contacted');
    });
});
