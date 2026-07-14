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

    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(
        public ?string $zoneId = null,
    ) {}

    public function handle(): void
    {
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
