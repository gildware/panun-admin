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
                'base' => $basic,
                'full_gross' => $fullGross,
                'missing_structure' => false,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $breakdown
     * @return array<int, array<string, mixed>>
     */
    public static function bonusLines(?array $breakdown): array
    {
        $breakdown = $breakdown ?? [];
        if (array_key_exists('bonuses', $breakdown) && is_array($breakdown['bonuses'])) {
            return array_values(array_filter($breakdown['bonuses'], 'is_array'));
        }
        $amount = round((float) ($breakdown['adjustment'] ?? 0), 2);
        if ($amount == 0.0) {
            return [];
        }

        return [['label' => 'Bonus', 'amount' => $amount]];
    }

    /**
     * @param  array<string, mixed>|null  $breakdown
     */
    public static function bonusTotal(?array $breakdown): float
    {
        return round(array_sum(array_map(
            fn ($line) => (float) ($line['amount'] ?? 0),
            self::bonusLines($breakdown)
        )), 2);
    }

    /**
     * @param  array<string, mixed>|null  $breakdown
     */
    public static function lopAmount(?array $breakdown): float
    {
        return round((float) (($breakdown ?? [])['lop_amount'] ?? 0), 2);
    }

    /**
     * @param  array{gross: float, deductions: float, breakdown?: array<string, mixed>}  $slip
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{gross: float, deductions: float, net: float, breakdown: array<string, mixed>}
     */
    public static function applyBonusLines(array $slip, array $lines): array
    {
        $total = round(array_sum(array_map(fn ($line) => (float) ($line['amount'] ?? 0), $lines)), 2);
        $breakdown = is_array($slip['breakdown'] ?? null) ? $slip['breakdown'] : [];
        $breakdown['bonuses'] = array_values($lines);
        $breakdown['adjustment'] = $total;
        $slip['breakdown'] = $breakdown;
        $slip['net'] = round((float) $slip['gross'] - (float) $slip['deductions'] + $total, 2);

        return $slip;
    }

    public function syncBonuses(PeoplePayslip $slip): void
    {
        $lines = PeoplePayAdjustment::query()
            ->where('user_id', $slip->user_id)
            ->where('period', $slip->period)
            ->orderBy('created_at')
            ->get()
            ->map(fn (PeoplePayAdjustment $row) => [
                'id' => (string) $row->id,
                'label' => (string) $row->label,
                'amount' => round((float) $row->amount, 2),
            ])
            ->all();
        $updated = self::applyBonusLines([
            'gross' => (float) $slip->gross,
            'deductions' => (float) $slip->deductions,
            'breakdown' => is_array($slip->breakdown) ? $slip->breakdown : [],
        ], $lines);
        $slip->forceFill([
            'breakdown' => $updated['breakdown'],
            'net' => $updated['net'],
        ])->save();
    }

    /**
     * Actual base salary used for each slip. A month calculated before this was stored falls back to the salary on file.
     *
     * @param  iterable<PeoplePayslip>  $slips
     * @return array<string, float>
     */
    public function baseSalaries(iterable $slips, string $period): array
    {
        $slips = collect($slips);
        $missing = $slips->filter(fn (PeoplePayslip $slip) => ! is_array($slip->breakdown) || ! array_key_exists('base', $slip->breakdown));
        $structures = collect();
        if ($missing->isNotEmpty()) {
            $end = Carbon::createFromFormat('Y-m-d', $period.'-01')->endOfMonth()->toDateString();
            $structures = PeopleSalaryStructure::query()
                ->whereIn('user_id', $missing->pluck('user_id')->unique()->filter()->all())
                ->where('effective_from', '<=', $end)
                ->orderByDesc('effective_from')
                ->get()
                ->unique('user_id')
                ->keyBy('user_id');
        }

        $amounts = [];
        foreach ($slips as $slip) {
            $breakdown = is_array($slip->breakdown) ? $slip->breakdown : [];
            if (array_key_exists('base', $breakdown)) {
                $amounts[$slip->id] = (float) $breakdown['base'];

                continue;
            }
            $structure = $structures->get($slip->user_id);
            $amounts[$slip->id] = $structure ? (float) $structure->basic : (float) ($breakdown['full_gross'] ?? 0);
        }

        return $amounts;
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
        if (! $run || ! $run->attendance_locked) {
            throw new \InvalidArgumentException('Lock the attendance first.');
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
                if ($profile && $profile->billing_type === 'non_billable') {
                    if ($existing && $existing->status !== 'published') {
                        $existing->delete();
                    }

                    continue;
                }
                if ($existing && $existing->status === 'published') {
                    continue;
                }

                $structure = PeopleSalaryStructure::query()
                    ->where('user_id', $person->id)
                    ->where('effective_from', '<=', $end->toDateString())
                    ->orderByDesc('effective_from')
                    ->first();

                $bonusRows = PeoplePayAdjustment::query()
                    ->where('user_id', $person->id)
                    ->where('period', $period)
                    ->orderBy('created_at')
                    ->get();
                $adjustment = round((float) $bonusRows->sum('amount'), 2);

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
                            'breakdown' => ['missing_structure' => true, 'earnings' => [], 'deductions' => [], 'lop_amount' => 0, 'employer_pf' => 0, 'adjustment' => 0, 'bonuses' => [], 'base' => 0],
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
                $amounts['breakdown']['bonuses'] = $bonusRows->map(fn (PeoplePayAdjustment $row) => [
                    'id' => (string) $row->id,
                    'label' => (string) $row->label,
                    'amount' => round((float) $row->amount, 2),
                ])->values()->all();
                $amounts['breakdown']['adjustment'] = $adjustment;

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
            if ($onLeave || ! $workspace->requiresTimesheet($person)) {
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
