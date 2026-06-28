<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\BookingModule\Entities\Booking;

class SeedDevNotifications extends Command
{
    protected $signature = 'notifications:seed-dev
                            {--send-push : Also send FCM (default: inbox-only, no device push)}
                            {--skip-sync : Skip syncing message templates to business_settings}
                            {--skip-smoke : Only sync templates; do not dispatch scenarios}
                            {--force : Allow running outside local/staging environments}';

    protected $description = 'Dev helper: sync all notification message templates (with variables), then dispatch every scenario into the in-app inbox';

    public function handle(): int
    {
        if (! $this->option('force') && app()->environment('production')) {
            $this->error('Refusing to run on production. Pass --force if you really mean it.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'staging', 'development', 'testing', 'live'])) {
            $this->warn('Unusual APP_ENV (' . app()->environment() . '). Use --force to skip this warning next time.');
        }

        if (! $this->option('skip-sync')) {
            $this->info('Step 1/2 — Syncing notification message templates (title + description with {{variables}})...');

            $result = sync_notification_default_messages(true);
            $this->info("  Updated {$result['updated']} template row(s), skipped {$result['skipped']}.");

            $issues = validate_notification_scenario_messages();
            if ($issues !== []) {
                $this->warn(count($issues) . ' template key(s) still have issues:');
                foreach ($issues as $issue) {
                    $this->warn(sprintf(
                        '  %s / %s: %s',
                        $issue['settings_type'],
                        $issue['key'],
                        implode('; ', $issue['issues'])
                    ));
                }

                return self::FAILURE;
            }

            $this->info('  All scenario message templates validated.');
        } else {
            $this->warn('Skipping template sync (--skip-sync).');
        }

        if ($this->option('skip-smoke')) {
            $this->info('Skipping scenario dispatch (--skip-smoke).');

            return self::SUCCESS;
        }

        $bookingCount = Booking::whereNotNull('customer_id')->count();
        if ($bookingCount === 0) {
            $this->warn('No bookings found. Seed test data first, e.g.:');
            $this->line('  php artisan booking:seed-test-matrix');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Step 2/2 — Dispatching all notification scenarios (inbox rows with resolved variables)...');

        ensure_notification_channel_setups();

        $exitCode = $this->call('notifications:smoke-test', [
            '--send-push' => $this->option('send-push'),
        ]);

        if ($exitCode === self::SUCCESS) {
            $this->newLine();
            $this->info('Done. Open the customer/provider app inbox for users linked to your test bookings.');
        }

        return $exitCode;
    }
}
