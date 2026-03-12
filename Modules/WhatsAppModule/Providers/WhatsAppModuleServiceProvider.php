<?php

namespace Modules\WhatsAppModule\Providers;

use Illuminate\Support\ServiceProvider;

class WhatsAppModuleServiceProvider extends ServiceProvider
{
    protected $moduleName = 'WhatsAppModule';
    protected $moduleNameLower = 'whatsappmodule';

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    public function registerViews()
    {
        $sourcePath = module_path($this->moduleName, 'Resources/views');
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
