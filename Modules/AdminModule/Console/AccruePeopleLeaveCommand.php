<?php

namespace Modules\AdminModule\Console;

use Illuminate\Console\Command;
use Modules\AdminModule\Services\PeopleLeaveAccrual;

class AccruePeopleLeaveCommand extends Command
{
    protected $signature = 'people:accrue-leave';

    protected $description = 'Add monthly or yearly leave that is due on each assigned leave policy';

    public function handle(PeopleLeaveAccrual $accrual): int
    {
        $credited = $accrual->applyDue(now());
        $this->info('Leave credits applied: '.$credited);

        return self::SUCCESS;
    }
}
