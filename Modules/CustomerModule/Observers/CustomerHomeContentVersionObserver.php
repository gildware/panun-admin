<?php

namespace Modules\CustomerModule\Observers;

use Modules\CustomerModule\Services\CustomerHomeContentVersion;

class CustomerHomeContentVersionObserver
{
    public function saved(): void
    {
        CustomerHomeContentVersion::bumpGlobal();
    }

    public function deleted(): void
    {
        CustomerHomeContentVersion::bumpGlobal();
    }
}
