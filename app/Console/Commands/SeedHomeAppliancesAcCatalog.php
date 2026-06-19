<?php

namespace App\Console\Commands;

use Database\Seeders\HomeAppliancesAirConditionerCatalogSeeder;
use Illuminate\Console\Command;

class SeedHomeAppliancesAcCatalog extends Command
{
    protected $signature = 'catalog:seed-home-appliances-ac';

    protected $description = 'Seed production-grade Home Appliances → Air Conditioner category, images, and services';

    public function handle(): int
    {
        $this->call('storage:link');

        $this->info('Seeding Home Appliances → Air Conditioner catalog...');
        (new HomeAppliancesAirConditionerCatalogSeeder)->setCommand($this)->run();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
