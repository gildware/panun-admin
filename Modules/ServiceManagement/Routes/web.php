<?php

use Illuminate\Support\Facades\Route;
use Modules\ServiceManagement\Http\Controllers\Web\Admin\CatalogReorderController;
use Modules\ServiceManagement\Http\Controllers\Web\Admin\CatalogViewController;
use Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceController as AdminServiceController;
use Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceRequestController;
use Modules\ServiceManagement\Http\Controllers\Web\Admin\FAQController;
use Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceOverviewContentController;
use Modules\ServiceManagement\Http\Controllers\Web\Provider\ServiceController;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {

    Route::group(['prefix' => 'catalog', 'as' => 'catalog.'], function () {
        Route::get('view', [CatalogViewController::class, 'index'])->name('view');
        Route::get('tree', [CatalogViewController::class, 'tree'])->name('tree');
        Route::post('reorder/categories', [CatalogReorderController::class, 'categories'])->name('reorder.categories');
        Route::post('reorder/subcategories', [CatalogReorderController::class, 'subcategories'])->name('reorder.subcategories');
        Route::post('reorder/services', [CatalogReorderController::class, 'services'])->name('reorder.services');
        Route::post('reorder/variations', [CatalogReorderController::class, 'variations'])->name('reorder.variations');
    });

    Route::group(['prefix' => 'service', 'as' => 'service.'], function () {
        Route::any('list', [AdminServiceController::class, 'index'])->name('index');
        Route::get('table', [AdminServiceController::class, 'getTable'])->name('table');
        Route::any('create', [AdminServiceController::class, 'create'])->name('create');
        Route::post('store', [AdminServiceController::class, 'store'])->name('store');
        Route::any('detail/{id}', [AdminServiceController::class, 'show'])->name('detail');
        Route::get('edit/{id}', [AdminServiceController::class, 'edit'])->name('edit');
        Route::put('update/{id}/basic', [AdminServiceController::class, 'updateBasic'])->name('update.basic');
        Route::put('update/{id}/variations', [AdminServiceController::class, 'updateVariations'])->name('update.variations');
        Route::put('update/{id}/charges-tax', [AdminServiceController::class, 'updateChargesTax'])->name('update.charges.tax');
        Route::put('update/{id}/charges-commission', [AdminServiceController::class, 'updateChargesCommission'])->name('update.charges.commission');
        Route::put('update/{id}/charges-additional', [AdminServiceController::class, 'updateChargesAdditional'])->name('update.charges.additional');
        Route::put('update/{id}', [AdminServiceController::class, 'update'])->name('update');
        Route::any('status-update/{id}', [AdminServiceController::class, 'statusUpdate'])->name('status-update');
        Route::delete('delete/{id}', [AdminServiceController::class, 'destroy'])->name('delete');
        Route::any('download', [AdminServiceController::class, 'download'])->name('download');
        Route::any('reviews/download', [AdminServiceController::class, 'reviewsDownload'])->name('reviews.download');

        Route::get('request/list', [ServiceRequestController::class, 'requestList'])->name('request.list');
        Route::post('request/update/{id}', [ServiceRequestController::class, 'updateStatus'])->name('request.update');

        Route::any('review-status-update/{id}', [AdminServiceController::class, 'reviewStatusUpdate'])->name('review-status-update');

        //ajax routes
        Route::any('ajax-add-variant', [AdminServiceController::class, 'ajaxAddVariant'])->name('ajax-add-variant');
        Route::any('ajax-remove-variant/{variant_key}', [AdminServiceController::class, 'ajaxRemoveVariant'])->name('ajax-remove-variant')->withoutMiddleware('csrf');
        Route::any('ajax-delete-db-variant/{variant_key}/{service_id}', [AdminServiceController::class, 'ajaxDeleteDbVariant'])->name('ajax-delete-db-variant')->withoutMiddleware('csrf');

        Route::group(['prefix' => '{service}/variants', 'as' => 'variants.'], function () {
            Route::get('/', [\Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceVariantController::class, 'index'])->name('index');
            Route::get('/panel', [\Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceVariantController::class, 'panel'])->name('panel');
            Route::get('/create', [\Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceVariantController::class, 'create'])->name('create');
            Route::post('/', [\Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceVariantController::class, 'store'])->name('store');
            Route::get('/{variant}', [\Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceVariantController::class, 'show'])->name('show');
            Route::get('/{variant}/edit', [\Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceVariantController::class, 'edit'])->name('edit');
            Route::put('/{variant}', [\Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceVariantController::class, 'update'])->name('update');
            Route::delete('/{variant}', [\Modules\ServiceManagement\Http\Controllers\Web\Admin\ServiceVariantController::class, 'destroy'])->name('destroy');
        });
    });

    Route::group(['prefix' => 'service-overview', 'as' => 'service-overview.'], function () {
        Route::get('defaults', [ServiceOverviewContentController::class, 'defaults'])->name('defaults');
        Route::post('defaults', [ServiceOverviewContentController::class, 'updateDefaults'])->name('defaults.update');
        Route::post('update/{service_id}', [ServiceOverviewContentController::class, 'update'])->name('update');
        Route::post('upload-image/{service_id}', [ServiceOverviewContentController::class, 'uploadImage'])->name('upload-image');
    });

    Route::group(['prefix' => 'faq', 'as' => 'faq.'], function () {
        Route::post('store/{service_id}', [FAQController::class, 'store'])->name('store');
        Route::get('edit/{id}', [FAQController::class, 'edit'])->name('edit');
        Route::any('update/{id}', [FAQController::class, 'update'])->name('update');
        Route::any('status-update/{id}', [FAQController::class, 'statusUpdate'])->name('status-update');
        Route::any('delete/{id}/{service_id}', [FAQController::class, 'destroy'])->name('delete');
        Route::post('reorder/{service_id}', [FAQController::class, 'reorder'])->name('reorder');
    });
});


Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => 'Web\Provider', 'middleware' => ['provider']], function () {
    Route::group(['prefix' => 'service', 'as' => 'service.'], function () {
        Route::get('available', [ServiceController::class, 'index'])->name('available');
        Route::get('request-list', [ServiceController::class, 'requestList'])->name('request-list')->middleware('subscription:service_request');
        Route::get('make-request', [ServiceController::class, 'makeRequest'])->name('make-request');
        Route::post('make-request', [ServiceController::class, 'storeRequest']);
        Route::put('update-subscription', [ServiceController::class, 'updateSubscription'])->name('update-subscription');
        Route::any('detail/{id}', [ServiceController::class, 'show'])->name('detail');
        Route::post('review-reply', [ServiceController::class, 'reviewReply'])->name('review.reply');
        Route::any('reviews/download', [ServiceController::class, 'reviewsDownload'])->name('reviews.download');
    });
});
