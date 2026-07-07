<?php

namespace App\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Laravel\Passport\Passport;
use Modules\AddonModule\Traits\AddonHelper;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

ini_set('memory_limit', '512M');

class AppServiceProvider extends ServiceProvider
{
    use AddonHelper;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require_once app_path('Lib/UserFcmDeviceHelpers.php');
        require_once app_path('Lib/NotificationMessageHelpers.php');
        require_once app_path('Lib/MobileInboxNotificationHelpers.php');
        require_once app_path('Lib/AdminInboxNotificationAvatarHelpers.php');
    }

    /**
     * Bootstrap any application services.
     *
     */
    public function boot(Request $request)
    {
        if(env('FORCE_HTTPS', false)) {
            \URL::forceScheme('https');
        }

        Config::set('addon_admin_routes', Cache::remember('addon_admin_routes', 3600, fn () => $this->get_addon_admin_routes()));
        Config::set('get_payment_publish_status', Cache::remember('get_payment_publish_status', 3600, fn () => $this->get_payment_publish_status()));

        try {
            Config::set('default_pagination', 25);
            Paginator::useBootstrap();
        } catch (\Exception $ex) {
            info($ex);
        }

        URL::defaults(['channel' => 'whatsapp']);
    }

    protected function registerAliases(array $aliases): void
    {
        $loader = AliasLoader::getInstance();

        foreach ($aliases as $alias => $class) {
            if (class_exists($class)) {
                $loader->alias($alias, $class);
            }
        }
    }
}
