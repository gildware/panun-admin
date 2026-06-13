<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PerformanceSetupCommand extends Command
{
    protected $signature = 'performance:setup {--check : Only report status, do not migrate}';

    protected $description = 'Prepare cache/queue performance settings (jobs table, Redis check)';

    public function handle(): int
    {
        $this->info('Panun Kaergar performance setup');
        $this->newLine();

        $this->reportCacheDriver();
        $this->reportRedis();
        $this->reportQueue();

        if ($this->option('check')) {
            return self::SUCCESS;
        }

        if (! Schema::hasTable('jobs')) {
            $this->warn('Creating queue tables…');
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => 'database/migrations/2026_04_04_210000_create_jobs_table.php',
            ]);
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => 'database/migrations/2026_04_04_210100_create_failed_jobs_table.php',
            ]);
            $this->info(trim(Artisan::output()));
        } else {
            $this->info('Queue tables: OK (jobs exists)');
        }

        $this->newLine();
        $this->line('Production recommendations:');
        $this->line('  CACHE_DRIVER=redis');
        $this->line('  QUEUE_CONNECTION=database');
        $this->line('  WHATSAPP_AI_DISPATCH_SYNC=false');
        $this->line('  php artisan queue:work --sleep=3 --tries=3');
        $this->line('  See deploy/supervisor/laravel-worker.conf.example');

        return self::SUCCESS;
    }

    private function reportCacheDriver(): void
    {
        $driver = config('cache.default');
        $this->line("Cache driver: {$driver}");
        if ($driver !== 'redis') {
            $this->warn('  → Set CACHE_DRIVER=redis in production for config/home-bundle caching.');
        }
    }

    private function reportRedis(): void
    {
        if (config('cache.default') !== 'redis' && config('queue.default') !== 'redis') {
            $this->line('Redis: skipped (not configured as cache/queue driver)');

            return;
        }

        try {
            Cache::store('redis')->put('performance_setup_ping', 'ok', 10);
            $value = Cache::store('redis')->get('performance_setup_ping');
            if ($value === 'ok') {
                $this->info('Redis: OK');
            } else {
                $this->error('Redis: ping failed');
            }
        } catch (\Throwable $e) {
            $this->error('Redis: '.$e->getMessage());
        }
    }

    private function reportQueue(): void
    {
        $connection = config('queue.default');
        $this->line("Queue connection: {$connection}");

        if ($connection === 'sync') {
            $this->warn('  → Background jobs run inline. Use QUEUE_CONNECTION=database + queue:work in production.');
        }

        if ($connection === 'database') {
            $pending = Schema::hasTable('jobs')
                ? DB::table('jobs')->count()
                : null;
            if ($pending !== null) {
                $this->line("  Pending jobs: {$pending}");
            }
        }
    }
}
