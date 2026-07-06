<?php

namespace Modules\CustomerModule\Observers;

use Modules\CustomerModule\Services\CustomerHomeContentInvalidator;

class CustomerHomeContentVersionObserver
{
    public function saved(): void
    {
        CustomerHomeContentInvalidator::bumpGlobal();
    }

    public function deleted(): void
    {
        CustomerHomeContentInvalidator::bumpGlobal();
    }
}
