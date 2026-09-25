<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Modules\AdminModule\Entities\PeopleHoliday;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeoplePayAdjustment;
use Modules\AdminModule\Entities\PeoplePayrollRun;
use Modules\AdminModule\Entities\PeoplePayslip;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleSalaryStructure;
use Modules\AdminModule\Entities\PeopleTimesheet;
use Modules\UserManagement\Entities\User;

class PeoplePayroll
{
    /**
     * @param  array{basic: float, hra: float, special_allowance: float, pf_employee: float, pf_employer: float, professional_tax: float, tds: float, other_deduction: float}  $structure
     * @return array{gross: float, deductions: float, net: float, lop_days: float, breakdown: array<string, mixed>}
     */
    public static function calculate(array $structure, int $workingDays, float $lopDays, float $adjustment): array
    {
        $basic = round((float) $structure['basic'], 2);
        $hra = round((float) $structure['hra'], 2);
        $special = round((float) $structure['special_allowance'], 2);
        $fullGross = round($basic + $hra + $special, 2);
        $lopDays = max(0, $lopDays);
        $paidDays = $workingDays > 0 ? max(0, $workingDays - $lopDays) : 0;
        $factor = $workingDays > 0 ? $paidDays / $workingDays : 0;

        $earnedBasic = round($basic * $factor, 2);
        $earnedHra = round($hra * $factor, 2);
        $earnedSpecial = round($special * $factor, 2);
        $gross = round($earnedBasic + $earnedHra + $earnedSpecial, 2);
        $lopAmount = round($fullGross - $gross, 2);
        $pfEmployee = round((float) $structure['pf_employee'] * $factor, 2);
        $pfEmployer = round((float) $structure['pf_employer'] * $factor, 2);
        $professionalTax = round((float) $structure['professional_tax'], 2);
        $tds = round((float) $structure['tds'], 2);
        $other = round((float) $structure['other_deduction'] * $factor, 2);
        $deductions = round($pfEmployee + $professionalTax + $tds + $other, 2);
        $net = round($gross - $deductions + $adjustment, 2);

        return [
            'gross' => $gross,
            'deductions' => $deductions,
            'net' => $net,
            'lop_days' => round($lopDays, 1),
            'breakdown' => [
                'earnings' => [
                    ['label' => 'Basic', 'amount' => $earnedBasic],
                    ['label' => 'HRA', 'amount' => $earnedHra],
                    ['label' => 'Special allowance', 'amount' => $earnedSpecial],
                ],
                'deductions' => [
                    ['label' => 'Provident fund', 'amount' => $pfEmployee],
                    ['label' => 'Professional tax', 'amount' => $professionalTax],
                    ['label' => 'Tax deducted at source', 'amount' => $tds],
                    ['label' => 'Other deduction', 'amount' => $other],
                ],
                'lop_amount' => $lopAmount,
                'employer_pf' => $pfEmployer,
                'adjustment' => round($adjustment, 2),
                'full_gross' => $fullGross,
                'missing_structure' => false,
            ],
        ];
    }

    public function runFor(string $period): ?PeoplePayrollRun
    {
        return PeoplePayrollRun::query()->where('period', $period)->first();
    }

    public function isLocked(string $period): bool
    {
        return PeoplePayrollRun::query()->where('period', $period)->where('status', 'locked')->exists();
    }

    public function assertAttendanceOpen(CarbonInterface $date): void
    {
        $period = $date->format('Y-m');
        $locked = PeoplePayrollRun::query()->where('period', $period)->where('attendance_locked', true)->exists();
        if ($locked) {
            throw new \InvalidArgumentException('Attendance for '.$date->format('F Y').' is locked.');
        }
    }

    public function buildMonth(string $period, bool $countMissingWeeks): PeoplePayrollRun
    {
        $run = $this->runFor($period);
        if ($run && in_array($run->status, ['published', 'locked'], true)) {
            throw new \InvalidArgumentException('That month is already '.$run->status.'. It cannot be rebuilt.');
        }

        $start = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->startOfDay();
        $holidays = PeopleHoliday::query()
            ->whereDate('holiday_on', '>=', $start->toDateString())
            ->whereDate('holiday_on', '<=', $end->toDateString())
            ->pluck('holiday_on')
            ->map(fn ($day) => Carbon::parse($day)->toDateString())
            ->all();
        $workingDays = PeopleWorkspace::countWorkingDays($start, $end, $holidays);

        $staff = User::query()
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get();

        DB::transaction(function () use ($staff, $period, $start, $end, $holidays, $workingDays, $countMissingWeeks) {
            foreach ($staff as $person) {
                $profile = PeopleProfile::query()->where('user_id', $person->id)->first();
                if ($profile && $profile->employment_status === 'exited') {
                    continue;
                }

                $existing = PeoplePayslip::query()->where('user_id', $person->id)->where('period', $period)->first();
                if ($existing && $existing->status === 'published') {
                    continue;
                }

                $structure = PeopleSalaryStructure::query()
                    ->where('user_id', $person->id)
                    ->where('effective_from', '<=', $end->toDateString())
                    ->orderByDesc('effective_from')
                    ->first();

                $adjustment = (float) PeoplePayAdjustment::query()
                    ->where('user_id', $person->id)
                    ->where('period', $period)
                    ->sum('amount');

                if (! $structure) {
                    PeoplePayslip::query()->updateOrCreate(
                        ['user_id' => $person->id, 'period' => $period],
                        [
                            'gross' => 0,
                            'deductions' => 0,
                            'net' => 0,
                            'lop_days' => 0,
                            'status' => 'draft',
                            'published_at' => null,
                            'held' => true,
                            'breakdown' => ['missing_structure' => true, 'earnings' => [], 'deductions' => [], 'lop_amount' => 0, 'employer_pf' => 0, 'adjustment' => 0],
                        ]
                    );

                    continue;
                }

                $lopDays = $this->unpaidDays($person->id, $start, $end, $holidays);
                if ($countMissingWeeks) {
                    $lopDays += $this->unapprovedDays($person->id, $start, $end, $holidays);
                }

                $amounts = self::calculate([
                    'basic' => (float) $structure->basic,
                    'hra' => (float) $structure->hra,
                    'special_allowance' => (float) $structure->special_allowance,
                    'pf_employee' => (float) $structure->pf_employee,
                    'pf_employer' => (float) $structure->pf_employer,
                    'professional_tax' => (float) $structure->professional_tax,
                    'tds' => (float) $structure->tds,
                    'other_deduction' => (float) $structure->other_deduction,
                ], $workingDays, $lopDays, $adjustment);

                PeoplePayslip::query()->updateOrCreate(
                    ['user_id' => $person->id, 'period' => $period],
                    [
                        'gross' => $amounts['gross'],
                        'deductions' => $amounts['deductions'],
                        'net' => $amounts['net'],
                        'lop_days' => $amounts['lop_days'],
                        'breakdown' => $amounts['breakdown'],
                        'status' => 'draft',
                        'published_at' => null,
                        'held' => (bool) ($existing->held ?? false),
                    ]
                );
            }
        });

        return PeoplePayrollRun::query()->updateOrCreate(
            ['period' => $period],
            ['status' => 'draft']
        );
    }

    /**
     * @param  array<int, string>  $holidays
     */
    private function unpaidDays(string $userId, CarbonInterface $start, CarbonInterface $end, array $holidays): float
    {
        $days = 0.0;
        $requests = PeopleLeaveRequest::query()
            ->where('user_id', $userId)
            ->where('leave_type', 'unpaid')
            ->where('status', 'approved')
            ->whereDate('starts_on', '<=', $end->toDateString())
            ->whereDate('ends_on', '>=', $start->toDateString())
            ->get();

        foreach ($requests as $request) {
            $from = $request->starts_on->greaterThan($start) ? $request->starts_on->copy() : Carbon::parse($start->toDateString());
            $to = $request->ends_on->lessThan($end) ? $request->ends_on->copy() : Carbon::parse($end->toDateString());
            $span = PeopleWorkspace::countWorkingDays($from, $to, $holidays);
            $days += min($span, (float) $request->days);
        }

        return round($days, 1);
    }

    /**
     * @param  array<int, string>  $holidays
     */
    private function unapprovedDays(string $userId, CarbonInterface $start, CarbonInterface $end, array $holidays): float
    {
        $holidayMap = array_flip($holidays);
        $sheets = PeopleTimesheet::query()
            ->where('user_id', $userId)
            ->whereDate('week_starts_on', '>=', Carbon::parse($start->toDateString())->startOfWeek(Carbon::MONDAY)->toDateString())
            ->whereDate('week_starts_on', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (PeopleTimesheet $sheet) => $sheet->week_starts_on->toDateString());

        $paidLeave = PeopleLeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('leave_type', '!=', 'unpaid')
            ->whereDate('starts_on', '<=', $end->toDateString())
            ->whereDate('ends_on', '>=', $start->toDateString())
            ->get();

        $days = 0.0;
        $cursor = Carbon::parse($start->toDateString());
        $last = Carbon::parse($end->toDateString());
        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $week = $cursor->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $sheet = $sheets->get($week);
            $onLeave = $paidLeave->contains(fn (PeopleLeaveRequest $leave) => $cursor->betweenIncluded($leave->starts_on, $leave->ends_on));
            if (! $cursor->isSunday() && ! isset($holidayMap[$key]) && ! $onLeave && (! $sheet || $sheet->status !== 'approved')) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }
}
