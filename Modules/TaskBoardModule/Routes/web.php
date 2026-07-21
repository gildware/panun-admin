<?php

use Illuminate\Support\Facades\Route;
use Modules\TaskBoardModule\Http\Controllers\Web\Admin\TaskBoardController;
use Modules\TaskBoardModule\Http\Controllers\Web\Admin\TaskColumnController;
use Modules\TaskBoardModule\Http\Controllers\Web\Admin\TaskTicketController;

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'middleware' => ['admin'],
], function () {
    Route::group(['prefix' => 'task-board', 'as' => 'task-board.'], function () {
        Route::get('/', [TaskBoardController::class, 'index'])->name('index');
        Route::get('/trash', [TaskBoardController::class, 'trash'])->name('trash');
        Route::get('/search-bookings', [TaskBoardController::class, 'searchBookings'])->name('search-bookings');
        Route::get('/search-leads', [TaskBoardController::class, 'searchLeads'])->name('search-leads');

        Route::post('/columns', [TaskColumnController::class, 'store'])->name('columns.store');
        Route::put('/columns/{id}', [TaskColumnController::class, 'update'])->name('columns.update');
        Route::delete('/columns/{id}', [TaskColumnController::class, 'destroy'])->name('columns.destroy');
        Route::post('/columns/reorder', [TaskColumnController::class, 'reorder'])->name('columns.reorder');

        Route::get('/tickets/{id}', [TaskTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets', [TaskTicketController::class, 'store'])->name('tickets.store');
        Route::put('/tickets/{id}', [TaskTicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{id}/move', [TaskTicketController::class, 'move'])->name('tickets.move');
        Route::delete('/tickets/{id}', [TaskTicketController::class, 'destroy'])->name('tickets.destroy');
        Route::post('/tickets/{id}/restore', [TaskTicketController::class, 'restore'])->name('tickets.restore');
        Route::post('/tickets/{id}/comments', [TaskTicketController::class, 'storeComment'])->name('tickets.comments.store');
        Route::delete('/comments/{id}', [TaskTicketController::class, 'destroyComment'])->name('comments.destroy');
        Route::delete('/attachments/{id}', [TaskTicketController::class, 'destroyAttachment'])->name('attachments.destroy');
    });
});
