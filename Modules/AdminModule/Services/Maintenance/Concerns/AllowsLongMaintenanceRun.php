<?php

namespace Modules\AdminModule\Services\Maintenance\Concerns;

trait AllowsLongMaintenanceRun
{
    protected function allowLongMaintenanceRun(): void
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
    }
}
