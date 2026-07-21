<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminModule\Http\Controllers\Web\Admin\AdminController;
use Modules\AdminModule\Http\Controllers\Web\Admin\RoleController;
use Modules\AdminModule\Http\Controllers\Web\Admin\EmployeeController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Analytics\SearchController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Report\BookingReportController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Report\DailyEmployeeReportController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Report\Business\EarningReportController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Report\Business\ExpenseReportController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Report\Business\OverviewReportController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Report\ProviderReportController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Report\TransactionReportController;
use Modules\AdminModule\Http\Controllers\Web\Admin\DataTransferController;
use Modules\AdminModule\Http\Controllers\Web\Admin\SystemMaintenanceController;
use Modules\AdminModule\Http\Controllers\Web\Admin\SystemLogsController;
use Modules\AdminModule\Http\Controllers\Web\Admin\AdminBusinessAiController;
use Modules\AdminModule\Http\Controllers\Web\Admin\AdminPinnedNavController;
use Modules\AdminModule\Http\Controllers\Web\Admin\NotificationController;
use Modules\AdminModule\Http\Controllers\Web\Admin\StaffPresenceController;


Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {
    Route::get('component', [AdminController::class, 'component'])->name('component');

    Route::get('setup-guide/status', [AdminController::class, 'refreshSetupGuideUI'])->name('setup-guide.status');
    Route::post('setup-guide/welcome-ack', [AdminController::class, 'acknowledgeSetupGuideWelcome'])->name('setup-guide.welcome-ack');

    Route::post('search-routing', [AdminController::class, 'searchRouting'])->name('search.routing');
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('business-ai', [AdminBusinessAiController::class, 'index'])->name('business-ai.index');
    Route::get('business-ai/messages', [AdminBusinessAiController::class, 'messages'])->name('business-ai.messages');
    Route::post('business-ai/chat', [AdminBusinessAiController::class, 'chat'])->name('business-ai.chat');
    Route::post('business-ai/reset', [AdminBusinessAiController::class, 'reset'])->name('business-ai.reset');

    Route::get('system-logs', [SystemLogsController::class, 'index'])->name('system-logs.index');
    Route::post('system-logs/clear', [SystemLogsController::class, 'clear'])->name('system-logs.clear');

    Route::get('data-transfer', [DataTransferController::class, 'index'])->name('data-transfer.index');
    Route::get('data-transfer/export/{domain}', [DataTransferController::class, 'export'])->name('data-transfer.export');
    Route::post('data-transfer/preview', [DataTransferController::class, 'preview'])->name('data-transfer.preview');
    Route::post('data-transfer/import', [DataTransferController::class, 'import'])->name('data-transfer.import');
    Route::get('system-maintenance/data-reset', [SystemMaintenanceController::class, 'index'])->name('system-maintenance.data-reset.index');
    Route::post('system-maintenance/data-reset', [SystemMaintenanceController::class, 'reset'])->name('system-maintenance.data-reset.run');
    Route::post('system-maintenance/data-reset/progress/init', [SystemMaintenanceController::class, 'progressInit'])->name('system-maintenance.data-reset.progress.init');
    Route::post('system-maintenance/data-reset/progress/step', [SystemMaintenanceController::class, 'progressStep'])->name('system-maintenance.data-reset.progress.step');
    Route::get('update-dashboard-earning-graph', [AdminController::class, 'updateDashboardEarningGraph'])->name('update-dashboard-earning-graph');
    Route::get('profile-update', [AdminController::class, 'profileInfo'])->name('profile_update');
    Route::post('profile-update', [AdminController::class, 'updateProfile']);
    Route::get('get-updated-data', [AdminController::class, 'getUpdatedData'])->name('get_updated_data');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}/detail', [NotificationController::class, 'detail'])->name('notifications.detail');
    Route::get('notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('notifications/mark-all-read', [AdminController::class, 'markAllNotificationsRead'])->name('notifications.mark_all_read');
    Route::post('notifications/mark-all-read-page', [NotificationController::class, 'markAllRead'])->name('notifications.mark_all_read_page');
    Route::post('notifications/{id}/read', [AdminController::class, 'markNotificationRead'])->name('notifications.read');

    Route::group(['prefix' => 'staff-presence', 'as' => 'staff-presence.'], function () {
        Route::post('heartbeat', [StaffPresenceController::class, 'heartbeat'])->name('heartbeat');
        Route::post('status', [StaffPresenceController::class, 'updateStatus'])->name('status');
        Route::get('list', [StaffPresenceController::class, 'list'])->name('list');
        Route::get('history-dates', [StaffPresenceController::class, 'historyDates'])->name('history-dates');
        Route::get('history', [StaffPresenceController::class, 'history'])->name('history');
    });

    Route::post('pinned-nav', [AdminPinnedNavController::class, 'save'])->name('pinned-nav.save');
    Route::post('store/search-routing', [AdminController::class, 'storeClickedRoute'])->name('search.routing.store');
    Route::get('recent-search', [AdminController::class, 'recentSearch'])->name('recent.search');

    Route::group(['prefix' => 'role', 'as' => 'role.'], function () {
        Route::any('list', [RoleController::class, 'index'])->name('index');
        Route::any('create', [RoleController::class, 'create'])->name('create');
        Route::post('store', [RoleController::class, 'store'])->name('store');
        Route::get('edit/{id}', [RoleController::class, 'edit'])->name('edit');
        Route::put('update/{id}', [RoleController::class, 'update'])->name('update');
        Route::any('status-update/{id}', [RoleController::class, 'statusUpdate'])->name('status-update');
        Route::delete('delete/{id}', [RoleController::class, 'destroy'])->name('delete');
        Route::any('download', [RoleController::class, 'download'])->name('download');
    });

    Route::group(['prefix' => 'employee', 'as' => 'employee.'], function () {
        Route::any('list', [EmployeeController::class, 'index'])->name('index');
        Route::any('create', [EmployeeController::class, 'create'])->name('create');
        Route::post('store', [EmployeeController::class, 'store'])->name('store');
        Route::get('edit/{id}', [EmployeeController::class, 'edit'])->name('edit');
        Route::get('set-permission/{id}', [EmployeeController::class, 'setPermission'])->name('set.permission');
        Route::put('update/{id}', [EmployeeController::class, 'update'])->name('update');
        Route::any('status-update/{id}', [EmployeeController::class, 'statusUpdate'])->name('status-update');
        Route::delete('delete/{id}', [EmployeeController::class, 'destroy'])->name('delete');
        Route::any('download', [EmployeeController::class, 'download'])->name('download');
        Route::get('ajax-role-access', [EmployeeController::class, 'ajaxRoleAccess'])->name('ajax.role.access');
        Route::get('ajax-employee-role-access', [EmployeeController::class, 'ajaxEmployeeRoleAccess'])->name('ajax.employee.role.access');
    });
});

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {

    Route::group(['prefix' => 'report', 'as' => 'report.', 'namespace' => 'Report'], function () {
        Route::any('transaction', [TransactionReportController::class, 'getTransactionReport'])->name('transaction');
        Route::any('transaction/download', [TransactionReportController::class, 'downloadTransactionReport'])->name('transaction.download');

        Route::get('daily-employee', [DailyEmployeeReportController::class, 'index'])->name('daily-employee');
        Route::get('daily-employee/detail', [DailyEmployeeReportController::class, 'detail'])->name('daily-employee.detail');

        Route::any('booking', [BookingReportController::class, 'getBookingReport'])->name('booking');
        Route::any('booking/download', [BookingReportController::class, 'getBookingReportDownload'])->name('booking.download');
        Route::post('booking/drilldown', [BookingReportController::class, 'getBookingReportDrilldown'])->name('booking.drilldown');

        Route::any('provider', [ProviderReportController::class, 'getProviderReport'])->name('provider');
        Route::any('provider/download', [ProviderReportController::class, 'getProviderReportDownload'])->name('provider.download');

        Route::group(['prefix' => 'business', 'as' => 'business.'], function () {
            Route::any('overview', [OverviewReportController::class, 'getBusinessOverviewReport'])->name('overview');
            Route::any('overview/download', [OverviewReportController::class, 'getBusinessOverviewReportDownload'])->name('overview.download');
            Route::any('earning', [EarningReportController::class, 'getBusinessEarningReport'])->name('earning');
            Route::any('subscription-earning', [EarningReportController::class, 'getBusinessSubscriptionEarningReport'])->name('subscription-earning');
            Route::any('commission-earning', [EarningReportController::class, 'getBusinessCommissionEarningReport'])->name('commission-earning');
            Route::any('earning/download', [EarningReportController::class, 'getBusinessEarningReportDownload'])->name('earning.download');
            Route::any('expense', [ExpenseReportController::class, 'getBusinessExpenseReport'])->name('expense');
            Route::any('expense/download', [ExpenseReportController::class, 'getBusinessExpenseReportDownload'])->name('expense.download');
            Route::any('subscription-earning/download', [EarningReportController::class, 'subEarningDownload'])->name('subscription.download');
            Route::any('commission-earning/download', [EarningReportController::class, 'comEarningDownload'])->name('commission.download');
        });
    });

    Route::group(['prefix' => 'analytics', 'as' => 'analytics.', 'namespace' => 'Analytics'], function () {
        Route::group(['prefix' => 'search', 'as' => 'search.'], function () {
            Route::any('keyword', [SearchController::class, 'getKeywordSearchAnalytics'])->name('keyword');
            Route::any('customer', [SearchController::class, 'getCustomerSearchAnalytics'])->name('customer');
        });
    });

});


