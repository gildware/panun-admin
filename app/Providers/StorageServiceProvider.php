<?php

namespace App\Providers;

use App\Support\CloudStorageConfigurator;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        CloudStorageConfigurator::apply();
    }
}
