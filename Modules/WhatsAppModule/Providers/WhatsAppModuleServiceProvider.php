<?php

namespace Modules\WhatsAppModule\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;
use Modules\WhatsAppModule\Services\WhatsAppMessagePersistenceService;

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
        $this->app->singleton(WhatsAppCloudService::class, fn () => new WhatsAppCloudService);
        $this->app->singleton(BookingWhatsAppNotificationService::class, function ($app) {
            return new BookingWhatsAppNotificationService(
                $app->make(WhatsAppCloudService::class),
                $app->make(WhatsAppMessagePersistenceService::class)
            );
        });

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
