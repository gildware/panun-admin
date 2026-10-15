<?php

namespace Modules\ZoneManagement\Console;

use Illuminate\Console\Command;
use Modules\ZoneManagement\Services\ZoneReplacementService;
use Throwable;

class ReplaceZonesCommand extends Command
{
    protected $signature = 'zones:replace
        {path=storage/app/zone-replacement.json : File from zones:export-replacement}
        {--execute : Apply the plan. Without this flag the command only prints what would change}';

    protected $description = 'Replace zones from an export, moving bookings, categories, providers, and leads before any old zone is deleted';

    public function handle(ZoneReplacementService $zones): int
    {
        try {
            $report = $zones->replace($this->argument('path'), (bool) $this->option('execute'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line($report['summary']);
        foreach ($report['rows'] as $row) {
            $this->line($row);
        }
        $this->newLine();
        foreach ($report['counts'] as $label => $count) {
            $this->line($label.': '.$count);
        }
        $this->newLine();
        $this->info($report['footer']);

        return self::SUCCESS;
    }
}
