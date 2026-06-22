<?php

namespace Modules\CallCenterModule\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\BookingModule\Entities\Booking;
use Modules\CallCenterModule\Listeners\CallCenterWebhookListener;
use Modules\ProviderManagement\Entities\CustomerIncident;
use Modules\UserManagement\Entities\User;

class CallCenterModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'CallCenterModule';

    protected string $moduleNameLower = 'callcentermodule';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->registerWebhookListeners();
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

    protected function registerWebhookListeners(): void
    {
        $listener = CallCenterWebhookListener::class;

        User::created(fn (User $user) => app($listener)->handleUserCreated($user));
        User::updated(fn (User $user) => app($listener)->handleUserUpdated($user));
        Booking::created(fn (Booking $booking) => app($listener)->handleBookingCreated($booking));
        Booking::updated(fn (Booking $booking) => app($listener)->handleBookingUpdated($booking));
        CustomerIncident::created(fn (CustomerIncident $incident) => app($listener)->handleComplaintCreated($incident));
    }
}
