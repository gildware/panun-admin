<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\CustomerModule\Services\CustomerHomeBaseBundleCache;

class WarmCustomerHomeBundleCacheCommand extends Command
{
    protected $signature = 'customer:home-cache:warm {zone_id? : Optional zone UUID to warm}';

    protected $description = 'Pre-build shared customer home bundle cache for active zones';

    public function handle(): int
    {
        $zoneId = $this->argument('zone_id');
        $zoneId = is_string($zoneId) && $zoneId !== '' ? $zoneId : null;

        $warmed = CustomerHomeBaseBundleCache::warmAll($zoneId);

        $this->info("Warmed {$warmed} home bundle cache entr".($warmed === 1 ? 'y' : 'ies').'.');

        return self::SUCCESS;
    }
}
