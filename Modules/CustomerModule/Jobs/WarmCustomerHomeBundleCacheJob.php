<?php

namespace Modules\CustomerModule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\CustomerModule\Services\CustomerHomeBaseBundleCache;
use Modules\CustomerModule\Services\CustomerHomeCacheWarmState;
use Throwable;

class WarmCustomerHomeBundleCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Hostinger sync/after-response warms can exceed 10 minutes across zones × locales. */
    public int $timeout = 1200;

    public int $tries = 2;

    public function __construct(
        public ?string $zoneId = null,
    ) {}

    public function handle(): void
    {
        // After fastcgi_finish_request the client is gone; keep running and allow long rebuilds.
        ignore_user_abort(true);
        @set_time_limit(0);

        try {
            CustomerHomeBaseBundleCache::warmAll($this->zoneId);
        } catch (Throwable $e) {
            CustomerHomeCacheWarmState::markRebuildFailed(
                $e->getMessage() !== '' ? $e->getMessage() : 'Failed to rebuild home cache'
            );

            throw $e;
        }
    }

    public function failed(?Throwable $e = null): void
    {
        CustomerHomeCacheWarmState::markRebuildFailed(
            $e && $e->getMessage() !== '' ? $e->getMessage() : 'Failed to rebuild home cache'
        );
    }
}
