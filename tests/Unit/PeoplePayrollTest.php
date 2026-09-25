<?php

namespace Tests\Unit;

use Modules\AdminModule\Services\PeoplePayroll;
use PHPUnit\Framework\TestCase;

class PeoplePayrollTest extends TestCase
{
    public function test_loss_of_pay_reduces_earnings_and_keeps_employer_pf_off_the_net(): void
    {
        $slip = PeoplePayroll::calculate([
            'basic' => 20000,
            'hra' => 8000,
            'special_allowance' => 2000,
            'pf_employee' => 1800,
            'pf_employer' => 1800,
            'professional_tax' => 200,
            'tds' => 0,
            'other_deduction' => 0,
        ], 20, 2, 500);

        $this->assertSame(30000.0, $slip['breakdown']['full_gross']);
        $this->assertSame(3000.0, $slip['breakdown']['lop_amount']);
        $this->assertSame(27000.0, $slip['gross']);
        $this->assertSame(1820.0, $slip['deductions']);
        $this->assertSame(25680.0, $slip['net']);
        $this->assertSame(1620.0, $slip['breakdown']['employer_pf']);
    }

    public function test_a_month_with_no_working_days_pays_nothing(): void
    {
        $slip = PeoplePayroll::calculate([
            'basic' => 10000,
            'hra' => 0,
            'special_allowance' => 0,
            'pf_employee' => 1200,
            'pf_employer' => 1200,
            'professional_tax' => 0,
            'tds' => 0,
            'other_deduction' => 0,
        ], 0, 0, 0);

        $this->assertSame(0.0, $slip['net']);
    }
}
