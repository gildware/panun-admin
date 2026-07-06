<?php

namespace Modules\CustomerModule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\CustomerModule\Services\CustomerHomeBaseBundleCache;

class WarmCustomerHomeBundleCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $zoneId = null,
    ) {}

    public function handle(): void
    {
        CustomerHomeBaseBundleCache::warmAll($this->zoneId);
    }
}
