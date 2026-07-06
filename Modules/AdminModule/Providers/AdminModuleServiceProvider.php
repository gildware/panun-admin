<?php

namespace Modules\AdminModule\Providers;

use App\Support\AdminMenuCounts;
use App\Support\AdminBreadcrumb;
use App\Support\AdminHeaderChatCounts;
use App\Support\AdminNavRegistry;
use App\Support\AdminPinnedNav;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class AdminModuleServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'AdminModule';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'adminmodule';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerAdminMenuViewComposer();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    protected function registerAdminMenuViewComposer(): void
    {
        View::composer([
            'adminmodule::layouts.partials._top-chrome',
            'adminmodule::layouts.partials._header',
            'adminmodule::layouts.partials._top-nav-menu',
            'adminmodule::layouts.partials._top-pinned',
            'adminmodule::layouts.partials._top-group-subnav',
            'adminmodule::layouts.partials._top-breadcrumbs',
            'adminmodule::layouts.partials.top-nav.*',
        ], function ($view) {
            $menuCounts = AdminMenuCounts::all();
            $view->with([
                'menuCounts' => $menuCounts,
                'supportUnreadCount' => AdminHeaderChatCounts::supportUnreadMessages(auth()->user()),
                'staffUnreadCount' => AdminHeaderChatCounts::staffUnreadMessages(auth()->user()),
                'whatsappUnreadCount' => AdminHeaderChatCounts::whatsappUnreadMessages(auth()->user()),
                'all_bookings_menu_count' => $menuCounts['all_bookings'],
                'pending_booking_reviews_count' => $menuCounts['pending_booking_reviews'],
                'special_scenarios_menu_count' => $menuCounts['special_scenarios'],
                'cancelled_by_provider_menu_count' => $menuCounts['cancelled_by_provider'],
                'pending_providers' => $menuCounts['pending_providers'],
                'pending_showcase_items' => $menuCounts['pending_showcase_items'],
                'pending_profile_changes' => $menuCounts['pending_profile_changes'],
                'denied_providers' => $menuCounts['denied_providers'],
                'max_booking_amount' => (business_config('max_booking_amount', 'booking_setup'))->live_values ?? 0,
                'adminBreadcrumbs' => AdminBreadcrumb::resolve(),
                'adminNavMatch' => AdminNavRegistry::match(),
                'adminGroupSubmenu' => AdminNavRegistry::groupSubmenu(),
                'adminPinnedCatalog' => AdminPinnedNav::catalogForChrome(
                    $menuCounts,
                    (float) ((business_config('max_booking_amount', 'booking_setup'))->live_values ?? 0)
                ),
                'adminDefaultPinKeys' => AdminNavRegistry::defaultPinKeys(),
                'adminUserPinnedKeys' => AdminPinnedNav::pinnedKeysForUser(auth()->user()),
            ]);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
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
