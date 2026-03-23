<?php

namespace Modules\WhatsAppModule\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Modules\BookingModule\Entities\Booking;
use Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;

class WhatsAppModuleServiceProvider extends ServiceProvider
{
    protected $moduleName = 'WhatsAppModule';
    protected $moduleNameLower = 'whatsappmodule';

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();

        Booking::updating(function (Booking $booking) {
            if ($booking->isDirty('booking_status')) {
                Cache::put(
                    BookingWhatsAppNotificationService::CACHE_PREVIOUS_STATUS_PREFIX . $booking->id,
                    (string) $booking->getOriginal('booking_status'),
                    120
                );
            }
        });

        Booking::updated(function (Booking $booking) {
            if (!$booking->wasChanged('booking_status')) {
                return;
            }
            $previous = (string) Cache::pull(
                BookingWhatsAppNotificationService::CACHE_PREVIOUS_STATUS_PREFIX . $booking->id,
                ''
            );
            try {
                app(BookingWhatsAppNotificationService::class)->sendBookingStatusChange($booking, $previous);
            } catch (\Throwable $e) {
                Log::warning('WhatsApp booking status notification failed', [
                    'booking_id' => $booking->id,
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    public function register()
    {
        $this->app->singleton(WhatsAppCloudService::class, fn () => new WhatsAppCloudService);
        $this->app->singleton(BookingWhatsAppNotificationService::class, function ($app) {
            return new BookingWhatsAppNotificationService($app->make(WhatsAppCloudService::class));
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
