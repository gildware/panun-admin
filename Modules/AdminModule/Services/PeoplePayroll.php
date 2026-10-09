<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Entities\PeopleAttendanceMark;
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

    public function buildMonth(string $period, bool $countMissingDays): PeoplePayrollRun
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
        $workspace = app(PeopleWorkspace::class);

        $staff = User::query()
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get();

        DB::transaction(function () use ($staff, $period, $start, $end, $holidays, $workspace, $countMissingDays) {
            foreach ($staff as $person) {
                $profile = PeopleProfile::query()->where('user_id', $person->id)->first();
                if ($profile && $profile->employment_status === 'exited') {
                    continue;
                }
                if ($profile) {
                    $person->setRelation('peopleProfile', $profile);
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

                $workingDays = max(0, PeopleWorkspace::countWorkingDays($start, $end, $holidays, $workspace->weekOffDays($person)) - $workspace->extraWeekOffCount($person, $start, $end, $holidays));
                $lopDays = $this->unpaidDays($person, $start, $end, $holidays, $workspace);
                if ($countMissingDays) {
                    $lopDays += $this->unapprovedDays($person, $start, $end, $holidays, $workspace);
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
    private function unpaidDays(User $person, CarbonInterface $start, CarbonInterface $end, array $holidays, PeopleWorkspace $workspace): float
    {
        $days = 0.0;
        $requests = PeopleLeaveRequest::query()
            ->where('user_id', $person->id)
            ->whereIn('leave_type', $workspace->unpaidLeaveTypeCodes())
            ->where('status', 'approved')
            ->whereDate('starts_on', '<=', $end->toDateString())
            ->whereDate('ends_on', '>=', $start->toDateString())
            ->get();

        foreach ($requests as $request) {
            $from = $request->starts_on->greaterThan($start) ? $request->starts_on->copy() : Carbon::parse($start->toDateString());
            $to = $request->ends_on->lessThan($end) ? $request->ends_on->copy() : Carbon::parse($end->toDateString());
            $span = PeopleWorkspace::countWorkingDays($from, $to, $holidays, $workspace->weekOffDays($person));
            $days += min($span, (float) $request->days);
        }

        return round($days, 1);
    }

    /**
     * @param  array<int, string>  $holidays
     */
    private function unapprovedDays(User $person, CarbonInterface $start, CarbonInterface $end, array $holidays, PeopleWorkspace $workspace): float
    {
        $holidayMap = array_flip($holidays);
        $weekOff = array_flip($workspace->weekOffDays($person));
        $sheets = PeopleTimesheet::query()
            ->where('user_id', $person->id)
            ->whereDate('week_starts_on', '>=', Carbon::parse($start->toDateString())->startOfWeek(Carbon::MONDAY)->toDateString())
            ->whereDate('week_starts_on', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (PeopleTimesheet $sheet) => $sheet->week_starts_on->toDateString());

        $leaves = PeopleLeaveRequest::query()
            ->where('user_id', $person->id)
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('starts_on', '<=', $end->toDateString())
            ->whereDate('ends_on', '>=', $start->toDateString())
            ->get();

        $profile = $person->relationLoaded('peopleProfile')
            ? $person->peopleProfile
            : PeopleProfile::query()->where('user_id', $person->id)->first();
        $joined = $profile?->joined_on?->toDateString();
        $left = $profile?->last_working_day?->toDateString();
        $today = now()->startOfDay();

        $manual = [];
        if (Schema::hasTable('people_attendance_marks')) {
            $saved = PeopleAttendanceMark::query()
                ->where('user_id', $person->id)
                ->whereDate('marked_on', '>=', $start->toDateString())
                ->whereDate('marked_on', '<=', $end->toDateString())
                ->get(['marked_on', 'status']);
            foreach ($saved as $mark) {
                $manual[$mark->marked_on->toDateString()] = $mark->status;
            }
        }

        $days = 0.0;
        $cursor = Carbon::parse($start->toDateString());
        $last = Carbon::parse($end->toDateString());
        $starts = $workspace->timesheetStartsOn();
        if ($starts && $cursor->lt($starts)) {
            $cursor = $starts->copy();
        }
        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            if ($cursor->gte($today)) {
                break;
            }
            if (($joined && $key < $joined) || ($left && $key > $left)) {
                $cursor->addDay();

                continue;
            }

            $week = $cursor->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $sheet = $sheets->get($week);
            $dayKey = PeopleWorkspace::DAY_KEYS[$cursor->dayOfWeekIso - 1] ?? '';
            $markedEntry = $sheet?->entries[$key] ?? null;
            $markedOff = is_array($markedEntry) && ($markedEntry['status'] ?? null) === 'week_off';
            $working = ! isset($weekOff[$dayKey]) && ! $markedOff && ! isset($holidayMap[$key]);
            if (! $working) {
                $cursor->addDay();

                continue;
            }

            $hand = $manual[$key] ?? null;
            if ($hand === 'present') {
                $cursor->addDay();

                continue;
            }
            if ($hand === 'half') {
                $days += 0.5;
                $cursor->addDay();

                continue;
            }
            if ($hand === 'absent') {
                $days++;
                $cursor->addDay();

                continue;
            }

            $onLeave = $leaves->contains(fn (PeopleLeaveRequest $leave) => $cursor->betweenIncluded($leave->starts_on, $leave->ends_on));
            if ($onLeave) {
                $cursor->addDay();

                continue;
            }

            $submitted = is_array($markedEntry) && ($markedEntry['status'] ?? null) === 'submitted';
            if ($submitted && ! empty($markedEntry['half'])) {
                $days += 0.5;
            } elseif (! $submitted) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }
}
