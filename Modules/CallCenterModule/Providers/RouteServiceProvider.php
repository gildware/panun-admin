<?php

namespace Modules\CallCenterModule\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\CallCenterModule\Http\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiV1Routes();
    }

    protected function mapApiV1Routes(): void
    {
        Route::prefix('api/v1')
            ->middleware(['api', 'callcenter.service'])
            ->namespace($this->moduleNamespace)
            ->group(module_path('CallCenterModule', '/Routes/api/v1/api.php'));
    }
}
