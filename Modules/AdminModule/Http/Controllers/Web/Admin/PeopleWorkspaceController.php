<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Barryvdh\DomPDF\Facade\Pdf;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\AdminModule\Entities\PeopleDocument;
use Modules\AdminModule\Entities\PeopleHoliday;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeopleLeaveType;
use Modules\AdminModule\Entities\PeoplePayslip;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleTimesheet;
use Modules\AdminModule\Services\PeoplePayroll;
use Modules\AdminModule\Services\PeopleWorkspace;
use Modules\UserManagement\Entities\User;
use Symfony\Component\HttpFoundation\Response;

class PeopleWorkspaceController extends Controller
{
    public function __construct(private PeopleWorkspace $workspace, private PeoplePayroll $payroll)
    {
    }

    public function index(Request $request)
    {
        $user = $this->actor();
        $this->workspace->boot();
        $section = $this->section($request, ['home', 'details', 'documents', 'payslips', 'timesheet'], 'home');
        $profile = PeopleProfile::query()->with('manager')->where('user_id', $user->id)->firstOrFail();
        $balance = $this->workspace->balance($user);
        $week = $this->workspace->timesheetForWeek($user, $this->workspace->currentWeekStart());

        return view('adminmodule::admin.people.mine', [
            'workspace' => $this->workspace,
            'actor' => $user,
            'section' => $section,
            'profile' => $profile,
            'balance' => $balance,
            'documents' => PeopleDocument::query()->where('user_id', $user->id)->orderBy('title')->get(),
            'leaveRequests' => PeopleLeaveRequest::query()->where('user_id', $user->id)->latest()->get(),
            'holidays' => PeopleHoliday::query()->orderBy('holiday_on')->get(),
            'nextHoliday' => $this->workspace->nextHoliday(),
            'payslips' => PeoplePayslip::query()->where('user_id', $user->id)->where('status', 'published')->orderByDesc('period')->get(),
            'timesheet' => $week,
            'timesheetBoard' => $section === 'timesheet' ? $this->timesheetBoard($user, $request) : null,
            'pastTimesheets' => PeopleTimesheet::query()
                ->where('user_id', $user->id)
                ->whereDate('week_starts_on', '<', $week->week_starts_on->toDateString())
                ->orderByDesc('week_starts_on')
                ->limit(8)
                ->get(),
            'canManageTeam' => $this->workspace->isManager($user),
            'canManageRecords' => $this->workspace->isHr($user),
            'leaveTypes' => PeopleLeaveType::query()->orderBy('sort')->orderBy('name')->get(),
        ]);
    }

    public function updateDetails(Request $request): RedirectResponse
    {
        $user = $this->actor();
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user->phone = $data['phone'];
        $user->save();

        PeopleProfile::query()->where('user_id', $user->id)->update([
            'address' => $data['address'] ?? null,
        ]);

        Toastr::success('Your details are saved.');

        return redirect()->route('admin.people.index', ['section' => 'details']);
    }

    public function storeDocument(Request $request): RedirectResponse
    {
        $user = $this->actor();
        $data = $request->validate([
            'title' => ['required', Rule::in(PeopleWorkspace::DOCUMENT_TITLES)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $document = PeopleDocument::query()
            ->where('user_id', $user->id)
            ->where('title', $data['title'])
            ->firstOrFail();

        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $path = $request->file('file')->store('people-documents/'.$user->id, 'local');
        $document->forceFill([
            'file_path' => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'status' => 'pending',
            'uploaded_at' => now(),
            'rejection_note' => null,
        ])->save();

        Toastr::success('Document uploaded. HR will check it.');

        return redirect()->route('admin.people.index', ['section' => 'documents']);
    }

    public function viewDocument(PeopleDocument $document): Response
    {
        return $this->documentFile($document, 'inline');
    }

    public function downloadDocument(PeopleDocument $document): Response
    {
        return $this->documentFile($document, 'attachment');
    }

    private function documentFile(PeopleDocument $document, string $disposition): Response
    {
        $actor = $this->actor();
        $owner = User::query()->findOrFail($document->user_id);
        abort_unless($this->workspace->canReadFile($actor, $owner), 403);
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        $name = $document->original_name ?: $document->title;

        return Storage::disk('local')->response($document->file_path, $name, [], $disposition);
    }

    public function storeLeave(Request $request): RedirectResponse
    {
        $user = $this->actor();
        $data = $request->validate([
            'leave_type' => ['required', Rule::exists('people_leave_types', 'code')],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'reason' => ['required', 'string', 'max:1000'],
            'half_day' => ['nullable', 'boolean'],
        ]);

        try {
            $this->workspace->submitLeave(
                $user,
                $data['leave_type'],
                Carbon::parse($data['starts_on']),
                Carbon::parse($data['ends_on']),
                trim($data['reason']),
                $request->boolean('half_day')
            );
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back()->withInput();
        }

        Toastr::success('Leave request sent.');

        return redirect()->route('admin.people.index', ['section' => 'home']);
    }

    public function storeTimesheet(Request $request): RedirectResponse
    {
        $user = $this->actor();
        $sheet = $this->workspace->timesheetForWeek($user, $this->workspace->currentWeekStart());
        try {
            $this->payroll->assertAttendanceOpen($sheet->week_starts_on);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return redirect()->route('admin.people.index', ['section' => 'timesheet']);
        }
        if (! in_array($sheet->status, ['draft', 'sent_back'], true)) {
            Toastr::error('This week is already with your manager.');

            return redirect()->route('admin.people.index', ['section' => 'timesheet']);
        }

        $rules = ['note' => ['nullable', 'string', 'max:1000']];
        foreach (PeopleWorkspace::WEEK_DAYS as $day) {
            $rules['hours.'.$day] = ['required', 'numeric', 'min:0', 'max:24'];
        }
        $data = $request->validate($rules);
        $hours = [];
        foreach (PeopleWorkspace::WEEK_DAYS as $day) {
            $hours[$day] = round((float) $data['hours'][$day], 1);
        }

        $sheet->forceFill([
            'hours' => $hours,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
            'decided_by' => null,
            'decided_at' => null,
        ])->save();

        Toastr::success('Timesheet sent to your manager.');

        return redirect()->route('admin.people.index', ['section' => 'timesheet']);
    }

    public function storeTimesheetDay(Request $request): RedirectResponse
    {
        $user = $this->actor();
        $data = $request->validate([
            'work_date' => ['required', 'date', 'before_or_equal:today'],
            'month' => ['nullable', 'date_format:Y-m'],
            'intent' => ['required', Rule::in(['submit', 'leave'])],
            'rows' => ['required_if:intent,submit', 'array', 'min:1', 'max:12'],
            'rows.*.task' => ['nullable', 'string', 'max:120'],
            'rows.*.code' => ['nullable', 'string', 'max:40'],
            'rows.*.deadline' => ['nullable', 'date'],
            'rows.*.project_type' => ['nullable', 'string', 'max:40'],
            'rows.*.hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ]);

        $date = Carbon::parse($data['work_date'])->startOfDay();
        try {
            $this->payroll->assertAttendanceOpen($date);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return $this->timesheetRedirect($request);
        }
        if ($date->isSunday()) {
            Toastr::error('Sunday is not a working day.');

            return $this->timesheetRedirect($request);
        }

        $sheet = $this->workspace->timesheetForWeek($user, $date->copy()->startOfWeek(Carbon::MONDAY));
        if (in_array($sheet->status, ['pending', 'approved'], true)) {
            Toastr::error($sheet->status === 'approved' ? 'This week is already approved.' : 'This week is already with your manager.');

            return $this->timesheetRedirect($request);
        }

        $rows = $data['intent'] === 'leave'
            ? [[
                'task' => 'Leave',
                'code' => 'LV-FL',
                'deadline' => null,
                'project_type' => 'Leave',
                'billing' => 'Billable',
                'hours' => 8,
            ]]
            : $this->cleanTimesheetRows($data['rows'] ?? []);

        $total = round(array_sum(array_column($rows, 'hours')), 2);
        if ($rows === [] || $total <= 0 || $total > 24) {
            Toastr::error('Add the hours for that day before you submit. A day cannot be more than 24 hours.');

            return $this->timesheetRedirect($request)->withInput();
        }

        $entries = $sheet->entries ?? [];
        $entries[$date->toDateString()] = [
            'status' => 'submitted',
            'rows' => $rows,
        ];

        $hours = $sheet->hours ?? array_fill_keys(PeopleWorkspace::WEEK_DAYS, 0);
        $dayKey = PeopleWorkspace::WEEK_DAYS[$date->dayOfWeekIso - 1] ?? null;
        if ($dayKey) {
            $hours[$dayKey] = round($total, 1);
        }

        $holidays = PeopleHoliday::query()->pluck('holiday_on')->map(fn ($day) => Carbon::parse($day)->toDateString())->all();
        $complete = $this->timesheetWeekComplete($date->copy()->startOfWeek(Carbon::MONDAY), $entries, $holidays);

        $sheet->forceFill([
            'entries' => $entries,
            'hours' => $hours,
            'status' => $complete ? 'pending' : ($sheet->status === 'sent_back' ? 'sent_back' : 'draft'),
            'decided_by' => $complete ? null : $sheet->decided_by,
            'decided_at' => $complete ? null : $sheet->decided_at,
        ])->save();

        Toastr::success($complete ? 'Week sent to your manager.' : 'Timesheet day submitted.');

        return $this->timesheetRedirect($request);
    }

    public function downloadPayslip(PeoplePayslip $payslip)
    {
        $actor = $this->actor();
        $owner = User::query()->findOrFail($payslip->user_id);
        $isOwner = (string) $actor->id === (string) $owner->id;
        if ($isOwner && $payslip->status !== 'published') {
            abort(403);
        }
        abort_unless($isOwner || $this->workspace->isHr($actor), 403);

        $profile = PeopleProfile::query()->where('user_id', $owner->id)->first();
        $pdf = Pdf::loadView('adminmodule::admin.people.payslip-pdf', [
            'payslip' => $payslip,
            'owner' => $owner,
            'profile' => $profile,
            'name' => $this->workspace->displayName($owner),
        ]);

        return $pdf->download('payslip-'.$payslip->period.'-'.($profile->employee_code ?? 'employee').'.pdf');
    }

    public function team(Request $request)
    {
        $actor = $this->actor();
        $this->workspace->boot();
        abort_unless($this->workspace->isManager($actor), 403);
        $section = $this->section($request, ['today', 'leave', 'timesheets', 'away'], 'today');
        $members = $this->workspace->teamMembers($actor);
        $memberIds = $members->pluck('id');

        return view('adminmodule::admin.people.team', [
            'workspace' => $this->workspace,
            'actor' => $actor,
            'section' => $section,
            'members' => $members,
            'leaveRequests' => PeopleLeaveRequest::query()
                ->with('user')
                ->whereIn('user_id', $memberIds)
                ->latest()
                ->get(),
            'timesheets' => PeopleTimesheet::query()
                ->with('user')
                ->whereIn('user_id', $memberIds)
                ->orderByDesc('week_starts_on')
                ->get(),
            'balances' => PeopleLeaveBalance::query()
                ->whereIn('user_id', $memberIds)
                ->where('year', (int) now()->year)
                ->get()
                ->keyBy('user_id'),
            'profiles' => PeopleProfile::query()->whereIn('user_id', $memberIds)->get()->keyBy('user_id'),
            'holidays' => PeopleHoliday::query()->whereDate('holiday_on', '>=', now()->toDateString())->orderBy('holiday_on')->get(),
            'canManageTeam' => true,
            'canManageRecords' => $this->workspace->isHr($actor),
        ]);
    }

    public function decideLeave(Request $request, PeopleLeaveRequest $leaveRequest): RedirectResponse
    {
        $actor = $this->actor();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'sent_back'])],
            'return_to' => ['nullable', 'string', 'max:40'],
        ]);
        $leaveRequest->load('user');

        try {
            $this->workspace->decideLeave($actor, $leaveRequest, $data['decision']);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back();
        }

        Toastr::success($data['decision'] === 'approve' ? 'Leave approved.' : 'Leave sent back.');

        return $this->afterDecision($data['return_to'] ?? 'team-leave');
    }

    public function decideTimesheet(Request $request, PeopleTimesheet $timesheet): RedirectResponse
    {
        $actor = $this->actor();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'sent_back'])],
        ]);
        $timesheet->load('user');

        try {
            $this->payroll->assertAttendanceOpen($timesheet->week_starts_on);
            $this->workspace->decideTimesheet($actor, $timesheet, $data['decision']);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back();
        }

        Toastr::success($data['decision'] === 'approve' ? 'Timesheet approved.' : 'Timesheet sent back.');

        $returnTo = (string) $request->input('return_to', '');

        return $returnTo === 'hr-attendance'
            ? redirect()->route('admin.hr.index', ['section' => 'attendance'])
            : redirect()->route('admin.people.team', ['section' => 'timesheets']);
    }

    public function cancelLeave(PeopleLeaveRequest $leaveRequest): RedirectResponse
    {
        $actor = $this->actor();
        try {
            $this->workspace->cancelLeave($actor, $leaveRequest);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back();
        }

        Toastr::success('Leave request cancelled.');

        return redirect()->route('admin.people.index');
    }

    public function records(Request $request)
    {
        $this->requireHr();
        $section = (string) $request->query('section', 'people');
        $target = match ($section) {
            'leave' => 'leave',
            'documents' => 'documents',
            'payslips' => 'payroll',
            'holidays' => null,
            default => 'people',
        };
        if ($target === null) {
            return redirect()->route('admin.people.holidays');
        }

        return redirect()->route('admin.hr.index', ['section' => $target]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'user_id' => ['required', 'uuid', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('user_type', ADMIN_USER_TYPES))],
            'job_title' => ['required', 'string', 'max:120'],
            'work_location' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'joined_on' => ['nullable', 'date'],
            'manager_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('user_type', ADMIN_USER_TYPES))],
        ]);

        if (! empty($data['manager_id']) && $data['manager_id'] === $data['user_id']) {
            Toastr::error('A person cannot be their own manager.');

            return back()->withInput();
        }

        $this->workspace->ensureStaffFile(User::query()->findOrFail($data['user_id']));
        PeopleProfile::query()->where('user_id', $data['user_id'])->update([
            'job_title' => $data['job_title'],
            'work_location' => $data['work_location'] ?? '',
            'address' => $data['address'] ?? null,
            'joined_on' => $data['joined_on'] ?? null,
            'manager_id' => $data['manager_id'] ?: null,
        ]);

        Toastr::success('People file updated.');

        return redirect()->route('admin.hr.index', ['section' => 'people']);
    }

    public function updateAllowances(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'casual_allowance' => ['required', 'integer', 'min:0', 'max:365'],
            'sick_allowance' => ['required', 'integer', 'min:0', 'max:365'],
            'earned_allowance' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        $balance = PeopleLeaveBalance::query()
            ->where('user_id', $data['user_id'])
            ->where('year', (int) now()->year)
            ->firstOrFail();

        foreach (['casual', 'sick', 'earned'] as $type) {
            if ((float) $data[$type.'_allowance'] < $balance->used($type)) {
                Toastr::error('The '.$this->workspace->leaveLabel($type).' allowance cannot be lower than the days already used.');

                return back()->withInput();
            }
        }

        $balance->fill([
            'casual_allowance' => $data['casual_allowance'],
            'sick_allowance' => $data['sick_allowance'],
            'earned_allowance' => $data['earned_allowance'],
        ])->save();

        Toastr::success('Leave allowances saved.');

        return redirect()->route('admin.hr.index', ['section' => 'leave']);
    }

    public function holidays(Request $request)
    {
        $actor = $this->requireHr();
        $this->workspace->boot();
        $year = $this->holidayYear($request);
        $holidays = PeopleHoliday::query()
            ->whereYear('holiday_on', $year)
            ->orderBy('holiday_on')
            ->get();
        $view = $request->query('view') === 'table' ? 'table' : 'calendar';

        return view('adminmodule::admin.people.holidays', [
            'workspace' => $this->workspace,
            'actor' => $actor,
            'year' => $year,
            'view' => $view,
            'holidays' => $holidays,
            'canManageTeam' => $this->workspace->isManager($actor),
            'canManageRecords' => true,
        ]);
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate($this->holidayRules());

        PeopleHoliday::query()->create($data);
        Toastr::success('Holiday added.');

        return $this->holidayRedirect($request);
    }

    public function updateHoliday(Request $request, PeopleHoliday $holiday): RedirectResponse
    {
        $this->requireHr();
        if (! $this->holidayYearIsOpen($holiday)) {
            Toastr::error('Only last year, this year, and next year can be changed here.');

            return redirect()->route('admin.people.holidays');
        }

        $data = $request->validate($this->holidayRules($holiday->id));
        $holiday->fill($data)->save();
        Toastr::success('Holiday updated.');

        return $this->holidayRedirect($request);
    }

    public function destroyHoliday(Request $request, PeopleHoliday $holiday): RedirectResponse
    {
        $this->requireHr();
        if (! $this->holidayYearIsOpen($holiday)) {
            Toastr::error('Only last year, this year, and next year can be removed here.');

            return redirect()->route('admin.people.holidays');
        }

        $holiday->delete();
        Toastr::success('Holiday removed.');

        return $this->holidayRedirect($request);
    }

    public function verifyDocument(Request $request, PeopleDocument $document): RedirectResponse
    {
        $this->requireHr();
        if (! $document->file_path) {
            Toastr::error('There is no file to mark on record.');

            return back();
        }

        $document->forceFill(['status' => 'verified', 'rejection_note' => null])->save();
        Toastr::success('Document marked on file.');

        if ($request->input('return_to') === 'person') {
            return redirect()->route('admin.hr.index', [
                'section' => 'person',
                'user' => $document->user_id,
                'tab' => 'documents',
            ]);
        }

        return redirect()->route('admin.hr.index', ['section' => 'people']);
    }

    public function storePayslip(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'user_id' => ['required', 'uuid', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('user_type', ADMIN_USER_TYPES))],
            'period' => ['required', 'date_format:Y-m'],
            'gross' => ['required', 'numeric', 'min:0'],
            'deductions' => ['required', 'numeric', 'min:0'],
        ]);

        if ($this->payroll->isLocked($data['period'])) {
            Toastr::error('That month is locked.');

            return redirect()->route('admin.hr.index', ['section' => 'payroll', 'period' => $data['period']]);
        }

        $gross = round((float) $data['gross'], 2);
        $deductions = round((float) $data['deductions'], 2);
        if ($deductions > $gross) {
            Toastr::error('Deductions cannot be more than the gross amount.');

            return back()->withInput();
        }

        $existing = PeoplePayslip::query()
            ->where('user_id', $data['user_id'])
            ->where('period', $data['period'])
            ->first();

        if ($existing && $existing->status === 'published') {
            Toastr::error('That month is already published. It cannot be changed here.');

            return back()->withInput();
        }

        PeoplePayslip::query()->updateOrCreate(
            ['user_id' => $data['user_id'], 'period' => $data['period']],
            [
                'gross' => $gross,
                'deductions' => $deductions,
                'net' => round($gross - $deductions, 2),
                'status' => 'draft',
                'published_at' => null,
            ]
        );

        Toastr::success('Payslip draft saved.');

        return redirect()->route('admin.hr.index', ['section' => 'payroll', 'period' => $data['period']]);
    }

    public function publishPayslips(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ]);

        $count = PeoplePayslip::query()
            ->where('period', $data['period'])
            ->where('status', 'draft')
            ->update([
                'status' => 'published',
                'published_at' => now(),
                'updated_at' => now(),
            ]);

        if ($count === 0) {
            Toastr::error('There are no draft payslips for that month.');

            return back();
        }

        Toastr::success($count.' payslip'.($count === 1 ? '' : 's').' published. Each person can now download their own.');

        return redirect()->route('admin.hr.index', ['section' => 'payroll', 'period' => $data['period']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function timesheetBoard(User $user, Request $request): array
    {
        $today = now()->startOfDay();
        $month = $today->copy()->startOfMonth();
        $requested = (string) $request->query('month', '');
        if (preg_match('/^\d{4}-\d{2}$/', $requested)) {
            try {
                $parsed = Carbon::createFromFormat('Y-m', $requested)->startOfMonth();
                if ($parsed->lte($month)) {
                    $month = $parsed;
                }
            } catch (\Throwable) {
                $month = $today->copy()->startOfMonth();
            }
        }

        $holidays = PeopleHoliday::query()->pluck('holiday_on')->map(fn ($day) => Carbon::parse($day)->toDateString())->all();
        $saved = [];
        $sheets = PeopleTimesheet::query()->where('user_id', $user->id)->get();
        foreach ($sheets as $sheet) {
            $locked = in_array($sheet->status, ['pending', 'approved'], true);
            foreach ($sheet->entries ?? [] as $date => $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                $saved[$date] = $entry;
                $saved[$date]['locked'] = $locked;
            }
        }

        $tasks = $this->timesheetTasks();
        $first = $today->copy()->startOfMonth()->subMonths(11);
        $months = [];
        $pendingMonths = [];
        $pendingTotal = 0;
        $lastMonthPending = 0;
        $lastMonth = $today->copy()->subMonthNoOverflow()->startOfMonth();

        for ($cursor = $first->copy(); $cursor->lte($today->copy()->startOfMonth()); $cursor->addMonth()) {
            $months[] = ['value' => $cursor->format('Y-m'), 'label' => $cursor->format('F Y')];
            $states = $this->timesheetMonthStates($cursor->copy(), $today, $holidays, $saved);
            $pending = count(array_filter($states, fn (array $day) => $day['state'] === 'pending'));
            $pendingTotal += $pending;
            if ($cursor->isSameMonth($lastMonth)) {
                $lastMonthPending = $pending;
            }
            if ($pending > 0) {
                $pendingMonths[] = [
                    'label' => $cursor->format('F Y'),
                    'value' => $cursor->format('Y-m'),
                    'days' => $states,
                ];
            }
        }

        $total = 0.0;
        $cards = [];
        $statusDays = $this->timesheetMonthStates($month->copy(), $today, $holidays, $saved);
        $end = $month->copy()->endOfMonth();
        for ($cursor = $month->copy(); $cursor->lte($end); $cursor->addDay()) {
            $key = $cursor->toDateString();
            $state = $this->timesheetDayState($cursor->copy(), $today, $holidays, $saved);
            if (! in_array($state, ['pending', 'done'], true)) {
                continue;
            }
            $entry = $saved[$key] ?? null;
            $rows = is_array($entry['rows'] ?? null) ? $entry['rows'] : [];
            foreach ($rows as $row) {
                $total += (float) ($row['hours'] ?? 0);
            }
            $cards[] = [
                'date' => $key,
                'label' => $cursor->format('j F Y, l'),
                'state' => $state,
                'open' => $state === 'pending' && ! ($entry['locked'] ?? false),
                'rows' => $rows,
            ];
        }

        $leaveOn = $today->copy();
        if ($leaveOn->isSunday()) {
            $leaveOn->subDay();
        }

        return [
            'month' => $month->format('Y-m'),
            'monthLabel' => $month->format('F Y'),
            'months' => $months,
            'tasks' => $tasks,
            'cards' => array_reverse($cards),
            'statusDays' => $statusDays,
            'total' => $total,
            'pendingMonths' => $pendingMonths,
            'pendingTotal' => $pendingTotal,
            'lastMonthPending' => $lastMonthPending,
            'leaveDate' => $leaveOn->toDateString(),
        ];
    }

    /**
     * @return array<int, array{task: string, code: string, project_type: string, billing: string}>
     */
    private function timesheetTasks(): array
    {
        return [
            ['task' => 'Customer work', 'code' => 'PK-CW', 'project_type' => 'Service', 'billing' => 'Billable'],
            ['task' => 'Bookings', 'code' => 'PK-BK', 'project_type' => 'Service', 'billing' => 'Billable'],
            ['task' => 'Provider follow-up', 'code' => 'PK-PF', 'project_type' => 'Service', 'billing' => 'Billable'],
            ['task' => 'Training', 'code' => 'PK-TR', 'project_type' => 'Internal', 'billing' => 'Billable'],
            ['task' => 'Partial Leave', 'code' => 'LV-PL', 'project_type' => 'Leave', 'billing' => 'Billable'],
            ['task' => 'Leave', 'code' => 'LV-FL', 'project_type' => 'Leave', 'billing' => 'Billable'],
        ];
    }

    /**
     * @param  array<int, string>  $holidays
     * @param  array<string, array<string, mixed>>  $saved
     * @return array<int, array{date: string, n: int, state: string}>
     */
    private function timesheetMonthStates(Carbon $month, Carbon $today, array $holidays, array $saved): array
    {
        $days = [];
        $cursor = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        while ($cursor->lte($end)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'n' => $cursor->day,
                'state' => $this->timesheetDayState($cursor->copy(), $today, $holidays, $saved),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param  array<int, string>  $holidays
     * @param  array<string, array<string, mixed>>  $saved
     */
    private function timesheetDayState(Carbon $date, Carbon $today, array $holidays, array $saved): string
    {
        $key = $date->toDateString();
        if (($saved[$key]['status'] ?? null) === 'submitted') {
            return 'done';
        }
        if ($date->gt($today)) {
            return 'future';
        }
        if ($date->isSunday()) {
            return 'off';
        }
        if (in_array($key, $holidays, true)) {
            return 'holiday';
        }

        return 'pending';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function cleanTimesheetRows(array $rows): array
    {
        $clean = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $task = trim((string) ($row['task'] ?? ''));
            $hours = round((float) ($row['hours'] ?? 0), 2);
            if ($task === '' || $hours <= 0) {
                continue;
            }
            $clean[] = [
                'task' => $task,
                'code' => trim((string) ($row['code'] ?? '')),
                'deadline' => $row['deadline'] ?? null,
                'project_type' => trim((string) ($row['project_type'] ?? '')),
                'billing' => 'Billable',
                'hours' => $hours,
            ];
        }

        return $clean;
    }

    /**
     * @param  array<string, array<string, mixed>>  $entries
     * @param  array<int, string>  $holidays
     */
    private function timesheetWeekComplete(Carbon $weekStart, array $entries, array $holidays): bool
    {
        for ($i = 0; $i < 6; $i++) {
            $day = $weekStart->copy()->addDays($i)->startOfDay();
            if ($day->isFuture()) {
                return false;
            }
            if (in_array($day->toDateString(), $holidays, true)) {
                continue;
            }
            if (($entries[$day->toDateString()]['status'] ?? null) !== 'submitted') {
                return false;
            }
        }

        return true;
    }

    private function timesheetRedirect(Request $request): RedirectResponse
    {
        $params = ['section' => 'timesheet'];
        $month = (string) $request->input('month', '');
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $params['month'] = $month;
        }

        return redirect()->route('admin.people.index', $params);
    }

    private function actor(): User
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user && in_array($user->user_type, ADMIN_USER_TYPES, true), 403);

        return $user;
    }

    private function requireHr(): User
    {
        $user = $this->actor();
        abort_unless($this->workspace->isHr($user), 403);

        return $user;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function section(Request $request, array $allowed, string $default): string
    {
        $section = (string) $request->query('section', $default);

        return in_array($section, $allowed, true) ? $section : $default;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function holidayRules(?string $ignoreId = null): array
    {
        $current = (int) now()->year;
        $unique = Rule::unique('people_holidays', 'holiday_on');
        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        return [
            'holiday_on' => ['required', 'date', 'after_or_equal:'.($current - 1).'-01-01', 'before_or_equal:'.($current + 1).'-12-31', $unique],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function holidayYear(Request $request): int
    {
        $current = (int) now()->year;
        $year = (int) $request->input('year', $current);
        if ($request->filled('holiday_on')) {
            try {
                $year = (int) Carbon::parse((string) $request->input('holiday_on'))->year;
            } catch (\Throwable) {
                $year = $current;
            }
        }
        if ($year < $current - 1 || $year > $current + 1) {
            return $current;
        }

        return $year;
    }

    private function holidayRedirect(Request $request): RedirectResponse
    {
        $params = [];
        $year = $this->holidayYear($request);
        if ($year !== (int) now()->year) {
            $params['year'] = $year;
        }
        if ($request->input('view') === 'table') {
            $params['view'] = 'table';
        }

        return redirect()->route('admin.people.holidays', $params);
    }

    private function holidayYearIsOpen(PeopleHoliday $holiday): bool
    {
        $year = (int) $holiday->holiday_on->year;
        $current = (int) now()->year;

        return $year >= $current - 1 && $year <= $current + 1;
    }

    private function afterDecision(string $returnTo): RedirectResponse
    {
        return match ($returnTo) {
            'records-leave', 'hr-leave' => redirect()->route('admin.hr.index', ['section' => 'leave']),
            default => redirect()->route('admin.people.team', ['section' => 'leave']),
        };
    }
}
