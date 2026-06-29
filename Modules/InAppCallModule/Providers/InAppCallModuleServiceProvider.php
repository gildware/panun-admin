<?php

namespace Modules\InAppCallModule\Providers;

use Illuminate\Support\ServiceProvider;

class InAppCallModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'InAppCallModule';

    protected string $moduleNameLower = 'inappcallmodule';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }
}
