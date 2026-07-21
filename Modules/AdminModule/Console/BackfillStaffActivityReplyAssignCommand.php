<?php

namespace Modules\AdminModule\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\AdminModule\Services\StaffActivityReplyAssignBackfill;

class BackfillStaffActivityReplyAssignCommand extends Command
{
    protected $signature = 'admin:backfill-wa-reply-assigns
                            {--since= : Only first-replies on/after this date (Y-m-d)}
                            {--until= : Only first-replies on/before this date (Y-m-d)}
                            {--dry-run : Count candidates without inserting}';

    protected $description = 'Backfill WA Assigned-from-AI events for employees who self-assigned by replying before logging existed';

    public function handle(StaffActivityReplyAssignBackfill $backfill): int
    {
        $since = $this->option('since') ? Carbon::parse($this->option('since'))->startOfDay() : null;
        $until = $this->option('until') ? Carbon::parse($this->option('until'))->endOfDay() : null;
        $dryRun = (bool) $this->option('dry-run');

        $result = $backfill->run($since, $until, $dryRun);

        $this->info(($dryRun ? '[dry-run] ' : '').sprintf(
            'candidates=%d inserted=%d skipped=%d',
            $result['candidates'],
            $result['inserted'],
            $result['skipped']
        ));

        return self::SUCCESS;
    }
}
