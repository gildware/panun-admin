<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\ProviderManagement\Services\WhatsAppProviderMatchService;

/**
 * Import WhatsApp provider match groups from JSON into DB.
 *
 * Use after migrate, or to recover a live backup that was overwritten by deploy:
 *   php artisan whatsapp:import-provider-groups /path/to/recovered.json --force
 */
class ImportWhatsAppProviderGroupsCommand extends Command
{
    protected $signature = 'whatsapp:import-provider-groups
                            {path? : JSON file path (defaults to bundled Data/whatsapp_provider_numbers.json)}
                            {--force : Replace all existing DB groups with the file contents}
                            {--dry-run : Parse and report counts without writing}';

    protected $description = 'Import WhatsApp provider groups/contacts from JSON into the database';

    public function handle(WhatsAppProviderMatchService $service): int
    {
        $path = (string) ($this->argument('path') ?: $service->sourcePath());
        $force = (bool) $this->option('force');
        $dry = (bool) $this->option('dry-run');

        if (! is_file($path)) {
            $this->error('File not found: '.$path);

            return self::FAILURE;
        }

        if (! $service->usesDatabase()) {
            $this->error('Tables missing. Run migrations first (whatsapp_provider_groups).');

            return self::FAILURE;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            $this->error('Invalid JSON: '.$path);

            return self::FAILURE;
        }

        $groupCount = 0;
        $contactCount = 0;
        foreach ($decoded as $name => $value) {
            if (! is_string($name) || trim($name) === '' || $name === '_meta' || ! is_array($value)) {
                continue;
            }
            $groupCount++;
            $contacts = array_is_list($value)
                ? $value
                : ($value['contacts'] ?? $value['phones'] ?? []);
            if (is_array($contacts)) {
                $contactCount += count($contacts);
            }
        }

        $this->info('Source: '.$path);
        $this->info('Groups in file: '.$groupCount);
        $this->info('Contact rows in file: '.$contactCount);
        $this->info('Mode: '.($force ? 'replace-all' : 'merge/upsert').($dry ? ' (dry-run)' : ''));

        if ($dry) {
            return self::SUCCESS;
        }

        $result = $service->importFromJsonFile($path, $force);
        if (! $result['ok']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info('Imported groups: '.$result['groups'].', contacts: '.$result['contacts']);

        return self::SUCCESS;
    }
}
