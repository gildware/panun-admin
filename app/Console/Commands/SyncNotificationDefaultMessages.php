<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncNotificationDefaultMessages extends Command
{
    protected $signature = 'notifications:sync-default-messages {--dry-run : Report only, do not write to database}';

    protected $description = 'Sync customer and provider notification message templates to canonical defaults';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $issues = validate_notification_scenario_messages();
            if ($issues === []) {
                $this->info('All scenario message keys look valid.');

                return self::SUCCESS;
            }

            foreach ($issues as $issue) {
                $this->warn(sprintf(
                    '%s / %s: %s',
                    $issue['settings_type'],
                    $issue['key'],
                    implode('; ', $issue['issues'])
                ));
            }

            return self::FAILURE;
        }

        $result = sync_notification_default_messages(true);
        $this->info("Updated {$result['updated']} notification message rows (skipped {$result['skipped']}).");

        $remaining = validate_notification_scenario_messages();
        if ($remaining !== []) {
            $this->warn(count($remaining) . ' scenario message key(s) still have issues.');

            return self::FAILURE;
        }

        $this->info('All scenario message templates validated successfully.');

        return self::SUCCESS;
    }
}
