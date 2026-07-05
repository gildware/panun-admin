<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\AdminModule\Listeners\CreateAdminBookingNotification;
use Modules\AdminModule\Listeners\CreateAdminProviderWithdrawalNotification;
use Modules\BookingModule\Events\BookingRequested;
use Modules\BookingModule\Events\ProviderWithdrewFromBooking;
use Modules\BookingModule\Listeners\SendBookingRequestEmail;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(
            BookingRequested::class,
            [SendBookingRequestEmail::class, 'handle']
        );
        Event::listen(
            BookingRequested::class,
            [CreateAdminBookingNotification::class, 'handle']
        );
        Event::listen(
            ProviderWithdrewFromBooking::class,
            [CreateAdminProviderWithdrawalNotification::class, 'handle']
        );
    }
}
