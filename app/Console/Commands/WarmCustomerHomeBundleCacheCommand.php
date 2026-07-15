<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\CustomerModule\Services\CustomerHomeCacheManager;

class WarmCustomerHomeBundleCacheCommand extends Command
{
    protected $signature = 'customer:home-cache:warm {zone_id? : Optional zone UUID to warm}';

    protected $description = 'Manually rebuild shared customer home-bundle cache (same as admin Reset home cache)';

    public function handle(): int
    {
        $zoneId = $this->argument('zone_id');
        $zoneId = is_string($zoneId) && $zoneId !== '' ? $zoneId : null;

        // Blocking rebuild — same as admin Reset (bumps version + overwrites store).
        $warmed = CustomerHomeCacheManager::resetAndWarm($zoneId, dispatchAsync: false);

        $this->info("Warmed {$warmed} home bundle cache entr".($warmed === 1 ? 'y' : 'ies').'.');

        return self::SUCCESS;
    }
}
