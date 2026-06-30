<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AdminModule\Http\Controllers\Web\Admin\SystemMaintenanceController;
use Modules\AdminModule\Services\Maintenance\AdminCustomerDeletionService;
use Modules\AdminModule\Services\Maintenance\AdminProviderDeletionService;
use Modules\UserManagement\Entities\User;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

DB::beginTransaction();

try {
    $admin = User::query()->where('user_type', 'super-admin')->first();
    if (! $admin) {
        throw new RuntimeException('No super-admin user found.');
    }

    auth()->login($admin);
    $controller = app(SystemMaintenanceController::class);
    $providerService = app(AdminProviderDeletionService::class);
    $customerService = app(AdminCustomerDeletionService::class);

    $initReq = Request::create('/admin/system-maintenance/data-reset/progress/init', 'POST', [
        'type' => 'providers',
        'confirm' => 'RESET',
    ]);
    $init = $controller->progressInit($initReq, $providerService, $customerService);
    $initData = $init->getData(true);

    if (! ($initData['ok'] ?? false)) {
        throw new RuntimeException('Init failed: '.json_encode($initData));
    }

    echo 'INIT total='.$initData['total'].PHP_EOL;

    $stepReq = Request::create('/admin/system-maintenance/data-reset/progress/step', 'POST', [
        'type' => 'providers',
        'total' => $initData['total'],
        'current' => 0,
    ]);
    $step = $controller->progressStep($stepReq, $providerService, $customerService);
    $stepData = $step->getData(true);

    if (! ($stepData['ok'] ?? false)) {
        throw new RuntimeException('Step failed: '.json_encode($stepData));
    }

    echo 'STEP '.$stepData['current'].'/'.$stepData['total'].' label='.$stepData['label'].' complete='.(int) ($stepData['complete'] ?? 0).PHP_EOL;

    DB::rollBack();
    echo "HTTP progress endpoints OK (rolled back)\n";
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'FAIL: '.$e->getMessage().PHP_EOL);
    exit(1);
}
