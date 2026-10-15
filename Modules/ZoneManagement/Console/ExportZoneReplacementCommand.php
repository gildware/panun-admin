<?php

namespace Modules\ZoneManagement\Console;

use Illuminate\Console\Command;
use Modules\ZoneManagement\Services\ZoneReplacementService;

class ExportZoneReplacementCommand extends Command
{
    protected $signature = 'zones:export-replacement {path=storage/app/zone-replacement.json : File to write}';

    protected $description = 'Write the current zone tree to a file that zones:replace can apply on another database';

    public function handle(ZoneReplacementService $zones): int
    {
        $path = $zones->export($this->argument('path'));
        $this->info('Wrote '.$zones->exportedCount().' zones to '.$path);

        return self::SUCCESS;
    }
}
