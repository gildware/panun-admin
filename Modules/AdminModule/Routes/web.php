<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminModule\Http\Controllers\Web\Admin\AdminController;
use Modules\AdminModule\Http\Controllers\Web\Admin\EmployeeProgressReportController;
use Modules\AdminModule\Http\Controllers\Web\Admin\RoleController;
use Modules\AdminModule\Http\Controllers\Web\Admin\EmployeeController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Analytics\SearchController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Report\BookingReportController;
use Modules\AdminModule\Http\Controllers\Web\Admin\Report\GeographicReportController;
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
use Modules\AdminModule\Http\Controllers\Web\Admin\ProcessGuideController;
use Modules\AdminModule\Http\Controllers\Web\Admin\WorkflowStepController;
use Modules\AdminModule\Http\Controllers\Web\Admin\ImpersonationController;
use Modules\AdminModule\Http\Controllers\Web\Admin\MarketingHubController;
use Modules\AdminModule\Http\Controllers\Web\Admin\ReportsHubController;
use Modules\AdminModule\Http\Controllers\Web\Admin\PeopleHrController;
use Modules\AdminModule\Http\Controllers\Web\Admin\PeopleWorkspaceController;
use Modules\AdminModule\Http\Controllers\Web\Admin\SettingsHubController;


Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {
    Route::get('component', [AdminController::class, 'component'])->name('component');

    Route::get('setup-guide/status', [AdminController::class, 'refreshSetupGuideUI'])->name('setup-guide.status');
    Route::post('setup-guide/welcome-ack', [AdminController::class, 'acknowledgeSetupGuideWelcome'])->name('setup-guide.welcome-ack');

    Route::post('search-routing', [AdminController::class, 'searchRouting'])->name('search.routing');
    Route::get('dashboard/finance', [AdminController::class, 'financeDashboard'])->name('dashboard.finance');
    Route::get('dashboard/operations', [AdminController::class, 'operationsDashboard'])->name('dashboard.operations');
    Route::get('dashboard/operating-system', [AdminController::class, 'operatingSystem'])->name('dashboard.operating-system');
    Route::get('dashboard/business-system', [AdminController::class, 'businessSystem'])->name('dashboard.business-system');
    Route::get('dashboard/rank-marks-chart', [AdminController::class, 'rankMarksChart'])->name('dashboard.rank-marks-chart');
    Route::get('dashboard/progress-scope', [AdminController::class, 'progressScope'])->name('dashboard.progress-scope');
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('settings/home-cache', [SettingsHubController::class, 'homeCache'])->name('settings.home-cache');
    Route::get('settings/{section?}', [SettingsHubController::class, 'index'])->name('settings.index');
    Route::get('marketing/{section?}', [MarketingHubController::class, 'index'])->name('marketing.index');
    Route::get('reports/{section?}', [ReportsHubController::class, 'index'])->name('reports.index');
    Route::get('my-progress', [EmployeeProgressReportController::class, 'index'])->name('my-progress');
    Route::get('my-progress/ranking/employee', [EmployeeProgressReportController::class, 'employeeRankingReport'])->name('my-progress.ranking-employee');
    Route::get('my-progress/ranking', [EmployeeProgressReportController::class, 'rankingReport'])->name('my-progress.ranking');
    Route::get('my-progress/rank-metric', [EmployeeProgressReportController::class, 'rankMetricDetail'])->name('my-progress.rank-metric');

    Route::get('workflow/stuck', [WorkflowStepController::class, 'stuckIndex'])->middleware(['can:lead_view'])->name('workflow.stuck');
    Route::post('workflow/steps/toggle', [WorkflowStepController::class, 'toggle'])->name('workflow.steps.toggle');
    Route::post('workflow/steps/confirm-bulk', [WorkflowStepController::class, 'confirmBulk'])->name('workflow.steps.confirm-bulk');
    Route::post('workflow/check-gate', [WorkflowStepController::class, 'checkGate'])->name('workflow.check-gate');

    Route::get('process-guides', [ProcessGuideController::class, 'index'])->name('process-guides.index');
    Route::get('process-guides/board.json', [ProcessGuideController::class, 'board'])->name('process-guides.board');
    Route::post('process-guides/board', [ProcessGuideController::class, 'saveBoard'])->name('process-guides.board.save');
    Route::post('process-guides/groups', [ProcessGuideController::class, 'saveGroups'])->name('process-guides.groups.save');

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

    Route::post('impersonate/leave', [ImpersonationController::class, 'leave'])->name('impersonate.leave');
    Route::get('employee/{id}/impersonate', [ImpersonationController::class, 'start'])->name('employee.impersonate');

    Route::group(['prefix' => 'people', 'as' => 'people.'], function () {
        Route::get('/', [PeopleWorkspaceController::class, 'index'])->name('index');
        Route::post('details', [PeopleWorkspaceController::class, 'updateDetails'])->name('details');
        Route::post('documents', [PeopleWorkspaceController::class, 'storeDocument'])->name('documents.store');
        Route::get('documents/{document}/view', [PeopleWorkspaceController::class, 'viewDocument'])->name('documents.view');
        Route::get('documents/{document}/download', [PeopleWorkspaceController::class, 'downloadDocument'])->name('documents.download');
        Route::post('leave', [PeopleWorkspaceController::class, 'storeLeave'])->name('leave.store');
        Route::post('leave/{leaveRequest}/cancel', [PeopleWorkspaceController::class, 'cancelLeave'])->name('leave.cancel');
        Route::post('timesheet', [PeopleWorkspaceController::class, 'storeTimesheet'])->name('timesheet.store');
        Route::post('timesheet/day', [PeopleWorkspaceController::class, 'storeTimesheetDay'])->name('timesheet.day');
        Route::get('payslips/{payslip}/download', [PeopleWorkspaceController::class, 'downloadPayslip'])->name('payslips.download');

        Route::get('team', [PeopleWorkspaceController::class, 'team'])->name('team');
        Route::post('team/leave/{leaveRequest}', [PeopleWorkspaceController::class, 'decideLeave'])->name('team.leave.decide');
        Route::post('team/timesheets/{timesheet}', [PeopleWorkspaceController::class, 'decideTimesheet'])->name('team.timesheet.decide');

        Route::get('records', [PeopleWorkspaceController::class, 'records'])->name('records');
        Route::post('records/profile', [PeopleWorkspaceController::class, 'updateProfile'])->name('records.profile');
        Route::post('records/allowances', [PeopleWorkspaceController::class, 'updateAllowances'])->name('records.allowances');
        Route::get('holidays', [PeopleWorkspaceController::class, 'holidays'])->name('holidays');
        Route::post('holidays', [PeopleWorkspaceController::class, 'storeHoliday'])->name('holidays.store');
        Route::put('holidays/{holiday}', [PeopleWorkspaceController::class, 'updateHoliday'])->name('holidays.update');
        Route::delete('holidays/{holiday}', [PeopleWorkspaceController::class, 'destroyHoliday'])->name('holidays.destroy');
        Route::post('records/documents/{document}/verify', [PeopleWorkspaceController::class, 'verifyDocument'])->name('records.documents.verify');
        Route::post('records/payslips', [PeopleWorkspaceController::class, 'storePayslip'])->name('records.payslips.store');
        Route::post('records/payslips/publish', [PeopleWorkspaceController::class, 'publishPayslips'])->name('records.payslips.publish');
    });

    Route::group(['prefix' => 'hr', 'as' => 'hr.'], function () {
        Route::get('/', [PeopleHrController::class, 'index'])->name('index');
        Route::post('person', [PeopleHrController::class, 'updatePerson'])->name('person');
        Route::post('departments', [PeopleHrController::class, 'storeDepartment'])->name('departments.store');
        Route::post('departments/{department}', [PeopleHrController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('departments/{department}', [PeopleHrController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::post('documents', [PeopleHrController::class, 'storePersonDocument'])->name('documents.store');
        Route::delete('documents/{document}', [PeopleHrController::class, 'destroyPersonDocument'])->name('documents.destroy');
        Route::post('documents/ask', [PeopleHrController::class, 'askDocument'])->name('documents.ask');
        Route::post('documents/{document}/reject', [PeopleHrController::class, 'rejectDocument'])->name('documents.reject');
        Route::post('leave/{leaveRequest}/cancel', [PeopleHrController::class, 'cancelLeave'])->name('leave.cancel');
        Route::post('leave/types', [PeopleHrController::class, 'storeLeaveType'])->name('leave.types.store');
        Route::post('leave/types/{leaveType}', [PeopleHrController::class, 'updateLeaveType'])->name('leave.types.update');
        Route::delete('leave/types/{leaveType}', [PeopleHrController::class, 'destroyLeaveType'])->name('leave.types.destroy');
        Route::post('leave/policies', [PeopleHrController::class, 'storeLeavePolicy'])->name('leave.policies.store');
        Route::post('leave/policies/{policy}', [PeopleHrController::class, 'updateLeavePolicy'])->name('leave.policies.update');
        Route::delete('leave/policies/{policy}', [PeopleHrController::class, 'destroyLeavePolicy'])->name('leave.policies.destroy');
        Route::post('leave/assign', [PeopleHrController::class, 'assignLeavePolicy'])->name('leave.assign');
        Route::delete('leave/policies/{policy}/departments/{department}', [PeopleHrController::class, 'detachLeaveDepartment'])->name('leave.departments.detach');
        Route::delete('leave/assignments/{assignment}', [PeopleHrController::class, 'unassignLeavePolicy'])->name('leave.assignments.destroy');
        Route::post('leave/grant', [PeopleHrController::class, 'grantLeave'])->name('leave.grant');
        Route::post('salary', [PeopleHrController::class, 'saveSalary'])->name('salary');
        Route::post('adjustment', [PeopleHrController::class, 'saveAdjustment'])->name('adjustment');
        Route::post('attendance/lock', [PeopleHrController::class, 'lockAttendance'])->name('attendance.lock');
        Route::post('payroll/build', [PeopleHrController::class, 'buildPayroll'])->name('payroll.build');
        Route::post('payroll/{payslip}/hold', [PeopleHrController::class, 'holdPayslip'])->name('payroll.hold');
        Route::post('payroll/publish', [PeopleHrController::class, 'publishPayroll'])->name('payroll.publish');
        Route::post('payroll/lock', [PeopleHrController::class, 'lockPayroll'])->name('payroll.lock');
        Route::get('payroll/bank', [PeopleHrController::class, 'bankFile'])->name('bank');
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

        Route::get('geographic', [GeographicReportController::class, 'index'])->name('geographic');

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


