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
use Modules\AdminModule\Entities\PeopleDepartment;
use Modules\AdminModule\Entities\PeopleDocument;
use Modules\AdminModule\Entities\PeopleHoliday;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeopleLeaveType;
use Modules\AdminModule\Entities\PeoplePayslip;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleTimesheet;
use Modules\AdminModule\Services\PeopleLeaveAccrual;
use Modules\AdminModule\Services\PeoplePayroll;
use Modules\AdminModule\Services\PeopleWorkspace;
use Modules\UserManagement\Entities\User;
use Symfony\Component\HttpFoundation\Response;

class PeopleWorkspaceController extends Controller
{
    public function __construct(
        private PeopleWorkspace $workspace,
        private PeoplePayroll $payroll,
        private PeopleLeaveAccrual $leaveAccrual,
    ) {
    }

    public function index(Request $request)
    {
        $user = $this->actor();
        $this->workspace->boot();
        $section = $this->section($request, ['home', 'details', 'profile', 'documents', 'payslips', 'timesheet'], 'profile');
        $user->loadMissing('roles');
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
            'leaveHistory' => $section === 'home' ? $this->workspace->leaveHistory($user->id) : collect(),
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
            'leavePicker' => in_array($section, ['home', 'timesheet'], true) ? $this->leavePicker($user) : null,
            'departments' => PeopleDepartment::query()->orderBy('name')->get(),
            'colleagues' => $this->workspace->staffUsers(),
        ]);
    }

    public function updateDetails(Request $request): RedirectResponse
    {
        $user = $this->actor();
        if ($request->input('form_context') === 'mine-basic') {
            return $this->updateBasic($request, $user);
        }

        return $this->updateOperation($request, $user);
    }

    private function updateBasic(Request $request, User $user): RedirectResponse
    {
        $request->merge([
            'date_of_birth' => $request->input('date_of_birth') ?: null,
        ]);
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'employment_status' => ['required', Rule::in(['active', 'notice', 'exited'])],
            'date_of_birth' => ['nullable', 'date'],
            'emergency_contact' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $parts = preg_split('/\s+/', trim($data['full_name']), 2) ?: [];
        $user->first_name = $parts[0] ?? trim($data['full_name']);
        $user->last_name = $parts[1] ?? '';
        $user->phone = $data['phone'];
        $user->save();

        PeopleProfile::query()->where('user_id', $user->id)->update([
            'employment_status' => $data['employment_status'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'emergency_contact' => trim((string) ($data['emergency_contact'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
        ]);

        Toastr::success('Your details are saved.');

        return redirect()->route('admin.people.index', ['section' => 'profile']);
    }

    private function updateOperation(Request $request, User $user): RedirectResponse
    {
        $request->merge([
            'department' => $request->input('department') ?: null,
            'manager_id' => $request->input('manager_id') ?: null,
            'joined_on' => $request->input('joined_on') ?: null,
        ]);
        $data = $request->validate([
            'job_title' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'work_location' => ['nullable', 'string', 'max:120'],
            'employment_type' => ['required', Rule::in(['full_time', 'contract'])],
            'joined_on' => ['nullable', 'date'],
            'manager_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('user_type', ADMIN_USER_TYPES))],
        ]);

        if (! empty($data['manager_id']) && $data['manager_id'] === (string) $user->id) {
            Toastr::error('A person cannot be their own manager.');

            return back()->withInput();
        }

        $profile = PeopleProfile::query()->where('user_id', $user->id)->firstOrFail();
        $currentDepartment = (string) $profile->department;
        if (! empty($data['department']) && $data['department'] !== $currentDepartment && ! PeopleDepartment::query()->where('name', $data['department'])->exists()) {
            Toastr::error('Choose a department from the list.');

            return back()->withInput();
        }

        $nextDepartment = (string) ($data['department'] ?? '');
        $profile->forceFill([
            'job_title' => $data['job_title'],
            'department' => $nextDepartment,
            'work_location' => $data['work_location'] ?? '',
            'employment_type' => $data['employment_type'],
            'joined_on' => $data['joined_on'] ?? null,
            'manager_id' => $data['manager_id'] ?: null,
        ])->save();

        if ($currentDepartment !== $nextDepartment) {
            $this->leaveAccrual->syncPersonDepartment($profile, $currentDepartment, $nextDepartment);
        }

        Toastr::success('Your details are saved.');

        return redirect()->route('admin.people.index', ['section' => 'profile']);
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
        $this->workspace->notifyDocumentSubmitted($user, $document);

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
            'ends_on' => ['required_unless:half_day,1', 'nullable', 'date', 'after_or_equal:starts_on'],
            'reason' => ['required', 'string', 'max:1000'],
            'half_day' => ['nullable', 'boolean'],
            'leave_hours' => ['nullable', 'numeric', 'min:0.5', 'max:24'],
        ]);

        $halfDay = $request->boolean('half_day');
        $startsOn = Carbon::parse($data['starts_on']);
        $endsOn = $halfDay ? $startsOn->copy() : Carbon::parse($data['ends_on']);

        try {
            $this->workspace->submitLeave(
                $user,
                $data['leave_type'],
                $startsOn,
                $endsOn,
                trim($data['reason']),
                $halfDay,
                $halfDay ? (isset($data['leave_hours']) ? (float) $data['leave_hours'] : null) : null
            );
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back()->withInput();
        }

        Toastr::success('Leave request sent.');

        $section = $request->input('return_section') === 'timesheet' ? 'timesheet' : 'home';
        $params = ['section' => $section];
        $month = (string) $request->input('month', '');
        if ($section === 'timesheet' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $params['month'] = $month;
        }

        return redirect()->route('admin.people.index', $params);
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
        $this->workspace->notifyTimesheetSubmitted($user, $sheet);

        Toastr::success('Timesheet sent to your manager.');

        return redirect()->route('admin.people.index', ['section' => 'timesheet']);
    }

    public function storeTimesheetDay(Request $request): RedirectResponse
    {
        $user = $this->actor();
        $data = $request->validate([
            'work_date' => ['required', 'date', 'before_or_equal:today'],
            'month' => ['nullable', 'date_format:Y-m'],
            'intent' => ['required', Rule::in(['submit', 'leave', 'week_off', 'clear_week_off'])],
            'rows' => ['required_if:intent,submit', 'array', 'min:1', 'max:12'],
            'rows.*.ticket_id' => ['nullable', 'string', 'max:80'],
            'rows.*.task' => ['nullable', 'string', 'max:180'],
            'rows.*.description' => ['nullable', 'string', 'max:500'],
            'rows.*.deadline' => ['nullable', 'date'],
            'rows.*.hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ]);

        $date = Carbon::parse($data['work_date'])->startOfDay();
        if ($this->workspace->isBeforeTimesheetStart($date)) {
            $starts = $this->workspace->timesheetStartsOn();
            Toastr::error('Timesheets start on '.($starts ? $starts->format('j F Y') : 'a later date').'.');

            return $this->timesheetRedirect($request);
        }
        try {
            $this->payroll->assertAttendanceOpen($date);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return $this->timesheetRedirect($request);
        }
        if ($this->workspace->isRecurringWeekOff($date, $user)) {
            Toastr::error('That day is a week off.');

            return $this->timesheetRedirect($request);
        }
        $covering = $this->workspace->leaveCovering($user, $date);
        $halfLeave = $covering && $this->workspace->isHalfDayLeave($covering);
        if ($covering && ! $halfLeave) {
            Toastr::error('Leave is applied for that day. The timesheet shows you on leave.');

            return $this->timesheetRedirect($request);
        }

        $sheet = $this->workspace->timesheetForWeek($user, $date->copy()->startOfWeek(Carbon::MONDAY));
        if (in_array($sheet->status, ['pending', 'approved'], true)) {
            Toastr::error($sheet->status === 'approved' ? 'This week is already approved.' : 'This week is already with your manager.');

            return $this->timesheetRedirect($request);
        }

        if (in_array($data['intent'], ['week_off', 'clear_week_off'], true)) {
            if ($covering) {
                Toastr::error('Leave is applied for that day.');

                return $this->timesheetRedirect($request);
            }

            $entries = $sheet->entries ?? [];
            $key = $date->toDateString();
            $hours = $sheet->hours ?? array_fill_keys(PeopleWorkspace::WEEK_DAYS, 0);
            $dayKey = PeopleWorkspace::DAY_KEYS[$date->dayOfWeekIso - 1] ?? null;
            if ($data['intent'] === 'week_off') {
                $entries[$key] = ['status' => 'week_off', 'rows' => []];
                $message = 'Week off applied for that day.';
            } else {
                unset($entries[$key]);
                $message = 'Week off removed. Fill the hours for that day.';
            }
            if ($dayKey) {
                $hours[$dayKey] = 0;
            }
            $this->workspace->forgetMarkedWeekOffDates($user);
            $holidays = PeopleHoliday::query()->pluck('holiday_on')->map(fn ($day) => Carbon::parse($day)->toDateString())->all();
            $complete = $this->timesheetWeekComplete($date->copy()->startOfWeek(Carbon::MONDAY), $entries, $holidays, $user);
            $previousStatus = (string) $sheet->status;
            $sheet->forceFill([
                'entries' => $entries,
                'hours' => $hours,
                'status' => $complete ? 'pending' : ($sheet->status === 'sent_back' ? 'sent_back' : 'draft'),
                'decided_by' => $complete ? null : $sheet->decided_by,
                'decided_at' => $complete ? null : $sheet->decided_at,
            ])->save();
            if ($complete && $previousStatus !== 'pending') {
                $this->workspace->notifyTimesheetSubmitted($user, $sheet);
            }
            Toastr::success($complete ? 'Week sent to your manager.' : $message);

            return $this->timesheetRedirect($request);
        }

        if ($this->workspace->isWeekOff($date, $user)) {
            Toastr::error('That day is a week off. Undo it before adding hours.');

            return $this->timesheetRedirect($request);
        }

        $rows = $data['intent'] === 'leave'
            ? [[
                'ticket_id' => 'leave',
                'task' => 'Leave',
                'description' => null,
                'deadline' => null,
                'hours' => $this->workspace->leaveDayHours($user),
            ]]
            : $this->cleanTimesheetRows($data['rows'] ?? [], $this->timesheetCatalog($user));
        if ($halfLeave && $covering) {
            $postedLeaveHours = null;
            foreach ($data['rows'] ?? [] as $row) {
                if (! is_array($row) || ($row['ticket_id'] ?? '') !== 'leave') {
                    continue;
                }
                if (! array_key_exists('hours', $row) || $row['hours'] === null || $row['hours'] === '') {
                    continue;
                }
                $postedLeaveHours = (float) $row['hours'];
            }
            try {
                $leaveHours = $postedLeaveHours === null
                    ? $this->workspace->appliedPartialHours($user, $covering)
                    : $this->workspace->partialLeaveHours($user, $postedLeaveHours);
                if ($postedLeaveHours !== null) {
                    $this->workspace->syncPartialLeaveHours($user, $covering, $leaveHours);
                }
            } catch (\InvalidArgumentException $exception) {
                Toastr::error($exception->getMessage());

                return $this->timesheetRedirect($request)->withInput();
            }
            $rows = array_values(array_filter($rows, fn (array $row) => ($row['ticket_id'] ?? '') !== 'leave'));
            array_unshift($rows, [
                'ticket_id' => 'leave',
                'task' => PeopleWorkspace::leaveTaskLabel($this->workspace->leaveLabel($covering->leave_type)),
                'description' => trim((string) $covering->reason) !== '' ? trim((string) $covering->reason) : null,
                'deadline' => null,
                'hours' => $leaveHours,
            ]);
        }

        $total = round(array_sum(array_column($rows, 'hours')), 2);
        $minimum = $this->workspace->requiredDayHours($user);
        if ($rows === [] || $total <= 0 || $total > 24) {
            Toastr::error('Add the hours for that day before you submit. A day cannot be more than 24 hours.');

            return $this->timesheetRedirect($request)->withInput();
        }
        if ($total + 0.001 < $minimum) {
            Toastr::error('A day needs at least '.PeopleWorkspace::hoursText($minimum).' hours.');

            return $this->timesheetRedirect($request)->withInput();
        }

        $entries = $sheet->entries ?? [];
        $alreadySubmitted = ($entries[$date->toDateString()]['status'] ?? null) === 'submitted';
        $entries[$date->toDateString()] = [
            'status' => 'submitted',
            'rows' => $rows,
        ];
        if ($halfLeave && $covering) {
            $entries[$date->toDateString()]['leave_request_id'] = (string) $covering->id;
            $entries[$date->toDateString()]['half'] = true;
        }

        $hours = $sheet->hours ?? array_fill_keys(PeopleWorkspace::WEEK_DAYS, 0);
        $dayKey = PeopleWorkspace::DAY_KEYS[$date->dayOfWeekIso - 1] ?? null;
        if ($dayKey) {
            $hours[$dayKey] = round($total, 1);
        }

        $holidays = PeopleHoliday::query()->pluck('holiday_on')->map(fn ($day) => Carbon::parse($day)->toDateString())->all();
        $complete = $this->timesheetWeekComplete($date->copy()->startOfWeek(Carbon::MONDAY), $entries, $holidays, $user);
        $previousStatus = (string) $sheet->status;

        $sheet->forceFill([
            'entries' => $entries,
            'hours' => $hours,
            'status' => $complete ? 'pending' : ($sheet->status === 'sent_back' ? 'sent_back' : 'draft'),
            'decided_by' => $complete ? null : $sheet->decided_by,
            'decided_at' => $complete ? null : $sheet->decided_at,
        ])->save();
        if ($complete && $previousStatus !== 'pending') {
            $this->workspace->notifyTimesheetSubmitted($user, $sheet);
        }

        Toastr::success($complete ? 'Week sent to your manager.' : ($alreadySubmitted ? 'Timesheet day updated.' : 'Timesheet day submitted.'));

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

    public function approvals(Request $request)
    {
        $actor = $this->actor();
        $this->workspace->boot();
        abort_unless($this->workspace->canReviewApprovals($actor), 403);
        $tab = (string) $request->query('tab', 'leaves');
        if (! in_array($tab, ['leaves', 'timesheet'], true)) {
            $tab = 'leaves';
        }

        $employees = $this->workspace->teamMembers($actor);
        $memberIds = $employees->pluck('id');
        $requestedEmployee = (string) $request->query('employee', '');
        $selectedEmployee = $memberIds->contains($requestedEmployee) ? $requestedEmployee : '';
        $visibleIds = $selectedEmployee !== '' ? collect([$selectedEmployee]) : $memberIds;
        $leaveRequests = PeopleLeaveRequest::query()
            ->with('user')
            ->whereIn('user_id', $visibleIds)
            ->latest()
            ->get()
            ->sort(function (PeopleLeaveRequest $left, PeopleLeaveRequest $right) {
                $pending = ($left->status === 'pending' ? 0 : 1) <=> ($right->status === 'pending' ? 0 : 1);

                return $pending !== 0 ? $pending : $right->created_at <=> $left->created_at;
            })
            ->values();
        $timesheets = PeopleTimesheet::query()
            ->with('user')
            ->whereIn('user_id', $visibleIds)
            ->orderByDesc('week_starts_on')
            ->get()
            ->sort(function (PeopleTimesheet $left, PeopleTimesheet $right) {
                $pending = ($left->status === 'pending' ? 0 : 1) <=> ($right->status === 'pending' ? 0 : 1);

                return $pending !== 0 ? $pending : $right->week_starts_on <=> $left->week_starts_on;
            })
            ->values();

        return view('adminmodule::admin.people.approvals', [
            'workspace' => $this->workspace,
            'tab' => $tab,
            'leaveRequests' => $leaveRequests,
            'timesheets' => $timesheets,
            'balances' => PeopleLeaveBalance::query()
                ->whereIn('user_id', $visibleIds)
                ->where('year', (int) now()->year)
                ->get()
                ->keyBy('user_id'),
            'employees' => $employees,
            'selectedEmployee' => $selectedEmployee,
            'pendingLeave' => $leaveRequests->where('status', 'pending')->count(),
            'pendingTimesheets' => $timesheets->where('status', 'pending')->count(),
        ]);
    }

    public function decideLeave(Request $request, PeopleLeaveRequest $leaveRequest): RedirectResponse
    {
        $actor = $this->actor();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'sent_back'])],
            'return_to' => ['nullable', 'string', 'max:40'],
            'decision_note' => [
                Rule::requiredIf(fn () => $request->input('decision') === 'sent_back' && str_starts_with((string) $request->input('return_to'), 'approvals')),
                'nullable',
                'string',
                'max:500',
            ],
            'leave_id' => ['nullable', 'uuid'],
        ], [
            'decision_note.required' => 'Write a reason for rejecting this leave.',
        ]);
        $leaveRequest->load('user');

        try {
            $this->workspace->decideLeave($actor, $leaveRequest, $data['decision'], $data['decision_note'] ?? null);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back();
        }

        $fromApprovals = str_starts_with((string) ($data['return_to'] ?? ''), 'approvals');
        Toastr::success(match (true) {
            $data['decision'] === 'approve' => 'Leave approved.',
            $fromApprovals => 'Leave rejected.',
            default => 'Leave sent back.',
        });

        return $this->afterDecision($data['return_to'] ?? 'team-leave', (string) $request->input('employee', ''));
    }

    public function decideTimesheet(Request $request, PeopleTimesheet $timesheet): RedirectResponse
    {
        $actor = $this->actor();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'sent_back'])],
            'return_to' => ['nullable', 'string', 'max:40'],
        ]);
        $timesheet->load('user');

        try {
            $this->payroll->assertAttendanceOpen($timesheet->week_starts_on);
            $this->workspace->decideTimesheet($actor, $timesheet, $data['decision']);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back();
        }

        $fromApprovals = str_starts_with((string) ($data['return_to'] ?? ''), 'approvals');
        Toastr::success(match (true) {
            $data['decision'] === 'approve' && $fromApprovals => 'Timesheet accepted.',
            $data['decision'] === 'approve' => 'Timesheet approved.',
            $fromApprovals => 'Timesheet denied.',
            default => 'Timesheet sent back.',
        });

        return match ((string) ($data['return_to'] ?? '')) {
            'hr-attendance' => redirect()->route('admin.accounts.attendance'),
            'hr-home' => redirect()->route('admin.dashboard.people'),
            'approvals-timesheet' => redirect()->route('admin.people.approvals', $this->approvalQuery('timesheet', (string) $request->input('employee', ''))),
            default => redirect()->route('admin.people.team', ['section' => 'timesheets']),
        };
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

        return redirect()->route('admin.people.index', ['section' => 'home']);
    }

    public function records(Request $request)
    {
        $this->requireHr();
        $section = (string) $request->query('section', 'people');
        $target = match ($section) {
            'leave' => 'leave',
            'documents' => 'people',
            'payslips' => 'payroll',
            'holidays' => null,
            default => 'people',
        };
        if ($target === null) {
            return redirect()->route('admin.people.holidays');
        }

        if ($target === 'payroll') {
            return redirect()->route('admin.accounts.payroll');
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
            'weekOff' => $this->workspace->weekOffDays(),
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
        $this->workspace->notifyDocumentDecided($document);
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

            return redirect()->route('admin.accounts.payroll', ['period' => $data['period']]);
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

        return redirect()->route('admin.accounts.payroll', ['period' => $data['period']]);
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

        return redirect()->route('admin.accounts.payroll', ['period' => $data['period']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function timesheetBoard(User $user, Request $request): array
    {
        $today = now()->startOfDay();
        $currentMonth = $today->copy()->startOfMonth();
        $month = $currentMonth->copy();
        $start = $this->workspace->timesheetStartsOn();
        $startMonth = $start?->copy()->startOfMonth();
        $requested = (string) $request->query('month', '');
        if (preg_match('/^\d{4}-\d{2}$/', $requested)) {
            try {
                $parsed = Carbon::createFromFormat('Y-m', $requested)->startOfMonth();
                if ($parsed->lte($currentMonth) && ($startMonth === null || $parsed->gte($startMonth))) {
                    $month = $parsed;
                }
            } catch (\Throwable) {
                $month = $currentMonth->copy();
            }
        }
        if ($startMonth && $startMonth->lte($currentMonth) && $month->lt($startMonth)) {
            $month = $startMonth->copy();
        }

        $this->workspace->mirrorOpenLeave($user);

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
                $saved[$date]['sheet_status'] = (string) $sheet->status;
            }
        }

        $assigned = $this->workspace->assignedBoardTasks($user);
        $extras = $this->workspace->configuredTimesheetTasks();
        $first = $startMonth && $startMonth->lte($currentMonth)
            ? $startMonth->copy()
            : $currentMonth->copy()->subMonths(11);
        $months = [];
        $pendingMonths = [];
        $pendingTotal = 0;
        $lastMonthPending = 0;
        $lastMonth = $today->copy()->subMonthNoOverflow()->startOfMonth();

        for ($cursor = $first->copy(); $cursor->lte($currentMonth); $cursor->addMonth()) {
            $months[] = ['value' => $cursor->format('Y-m'), 'label' => $cursor->format('F Y')];
            $states = $this->timesheetMonthStates($cursor->copy(), $today, $holidays, $saved, $user);
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

        $leaveNames = [];
        $leaveReasons = [];
        $leaveIds = [];
        foreach ($saved as $entry) {
            $leaveId = (string) ($entry['leave_request_id'] ?? '');
            if ($leaveId !== '') {
                $leaveIds[$leaveId] = true;
            }
        }
        if ($leaveIds !== []) {
            $leaveRows = PeopleLeaveRequest::query()
                ->whereIn('id', array_keys($leaveIds))
                ->get(['id', 'leave_type', 'reason']);
            foreach ($leaveRows as $leave) {
                $leaveNames[(string) $leave->id] = $this->workspace->leaveLabel($leave->leave_type);
                $leaveReasons[(string) $leave->id] = trim((string) $leave->reason);
            }
        }

        $total = 0.0;
        $cards = [];
        $statusDays = $this->timesheetMonthStates($month->copy(), $today, $holidays, $saved, $user);
        $end = $month->copy()->endOfMonth();
        for ($cursor = $month->copy(); $cursor->lte($end); $cursor->addDay()) {
            $key = $cursor->toDateString();
            $state = $this->timesheetDayState($cursor->copy(), $today, $holidays, $saved, $user);
            if (! in_array($state, ['pending', 'done', 'approved', 'leave', 'weekoff'], true)) {
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
                'open' => ! ($entry['locked'] ?? false) && (
                    in_array($state, ['pending', 'done'], true)
                    || ($state === 'leave' && (bool) ($entry['half'] ?? false) && ! $cursor->gt($today))
                ),
                'onLeave' => $state === 'leave',
                'halfLeave' => (bool) ($entry['half'] ?? false),
                'leaveName' => $leaveNames[(string) ($entry['leave_request_id'] ?? '')] ?? '',
                'leaveReason' => $leaveReasons[(string) ($entry['leave_request_id'] ?? '')] ?? '',
                'rows' => $rows,
            ];
        }

        $leaveOn = $today->copy();
        $guard = 0;
        while ($this->workspace->isWeekOff($leaveOn, $user) && $guard < 6) {
            $leaveOn->subDay();
            $guard++;
        }

        return [
            'month' => $month->format('Y-m'),
            'monthLabel' => $month->format('F Y'),
            'months' => $months,
            'assigned' => $assigned,
            'extras' => $extras,
            'minHours' => $this->workspace->requiredDayHours($user),
            'leaveHours' => $this->workspace->leaveDayHours($user),
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
     * Board tasks this person can log, configured additional hours, and leave.
     *
     * @return array<int, array{id: string, task: string, deadline: string}>
     */
    private function timesheetCatalog(User $user): array
    {
        return array_merge(
            $this->workspace->assignedBoardTasks($user),
            $this->workspace->configuredTimesheetTasks(),
            [
                ['id' => 'partial-leave', 'task' => 'Partial Leave', 'deadline' => ''],
                ['id' => 'leave', 'task' => 'Leave', 'deadline' => ''],
            ],
        );
    }

    /**
     * @param  array<int, string>  $holidays
     * @param  array<string, array<string, mixed>>  $saved
     * @return array<int, array{date: string, n: int, state: string}>
     */
    private function timesheetMonthStates(Carbon $month, Carbon $today, array $holidays, array $saved, User $user): array
    {
        $days = [];
        $cursor = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        while ($cursor->lte($end)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'n' => $cursor->day,
                'state' => $this->timesheetDayState($cursor->copy(), $today, $holidays, $saved, $user),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param  array<int, string>  $holidays
     * @param  array<string, array<string, mixed>>  $saved
     */
    private function timesheetDayState(Carbon $date, Carbon $today, array $holidays, array $saved, User $user): string
    {
        $key = $date->toDateString();
        if ($this->workspace->isBeforeTimesheetStart($date)) {
            return 'before';
        }
        if (($saved[$key]['status'] ?? null) === 'week_off') {
            return 'weekoff';
        }
        if (($saved[$key]['status'] ?? null) === 'submitted') {
            if (filled($saved[$key]['leave_request_id'] ?? null)) {
                return 'leave';
            }

            return ($saved[$key]['sheet_status'] ?? null) === 'approved' ? 'approved' : 'done';
        }
        if ($date->gt($today)) {
            return 'future';
        }
        if ($this->workspace->isWeekOff($date, $user)) {
            return 'off';
        }
        if (in_array($key, $holidays, true)) {
            return 'holiday';
        }

        return 'pending';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{id: string, task: string, deadline: string}>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function cleanTimesheetRows(array $rows, array $tasks): array
    {
        $catalog = [];
        foreach ($tasks as $task) {
            $catalog[$task['id']] = $task;
        }

        $clean = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $ticketId = trim((string) ($row['ticket_id'] ?? ''));
            $known = $catalog[$ticketId] ?? null;
            if (! $known) {
                continue;
            }
            $task = $known['task'];
            $hours = round((float) ($row['hours'] ?? 0), 2);
            if ($task === '' || $hours <= 0) {
                continue;
            }
            $deadline = $known['deadline'];
            $description = trim((string) ($row['description'] ?? ''));
            $clean[] = [
                'ticket_id' => $known['id'] ?? ($ticketId !== '' ? $ticketId : null),
                'task' => $task,
                'description' => $description !== '' ? $description : null,
                'deadline' => $deadline !== '' ? $deadline : null,
                'hours' => $hours,
            ];
        }

        return $clean;
    }

    /**
     * @param  array<string, array<string, mixed>>  $entries
     * @param  array<int, string>  $holidays
     */
    private function timesheetWeekComplete(Carbon $weekStart, array $entries, array $holidays, User $user): bool
    {
        $required = 0;
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->copy()->addDays($i)->startOfDay();
            $entry = $entries[$day->toDateString()] ?? null;
            if ($this->workspace->isBeforeTimesheetStart($day) || $this->workspace->isRecurringWeekOff($day, $user) || in_array($day->toDateString(), $holidays, true) || (is_array($entry) && ($entry['status'] ?? null) === 'week_off')) {
                continue;
            }
            $required++;
            if ($day->isFuture()) {
                return false;
            }
            if (! is_array($entry) || ($entry['status'] ?? null) !== 'submitted' || ! $this->workspace->dayMeetsMinimum($entry, $user)) {
                return false;
            }
        }

        return $required > 0;
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

    /**
     * Dates the apply-leave calendar must keep closed: week offs, holidays,
     * days already requested, and anything past the balance for that leave type.
     *
     * @return array{weekOff: array<int, string>, holidays: array<string, string>, blocked: array<int, array{start: string, end: string}>, types: array<string, array{tracks: bool, name: string, years: array<string, float>}>}
     */
    private function leavePicker(User $user): array
    {
        $holidays = PeopleHoliday::query()
            ->orderBy('holiday_on')
            ->get(['holiday_on', 'name'])
            ->mapWithKeys(fn (PeopleHoliday $holiday) => [
                $holiday->holiday_on->toDateString() => $holiday->name,
            ])
            ->all();

        $blocked = PeopleLeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('starts_on')
            ->get(['starts_on', 'ends_on'])
            ->map(fn (PeopleLeaveRequest $leave) => [
                'start' => $leave->starts_on->toDateString(),
                'end' => $leave->ends_on->toDateString(),
            ])
            ->values()
            ->all();

        $balances = PeopleLeaveBalance::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn (PeopleLeaveBalance $balance) => (string) $balance->year);

        $types = [];
        foreach (PeopleLeaveType::query()->orderBy('sort')->orderBy('name')->get() as $type) {
            $years = [];
            if ($type->tracks_balance) {
                foreach ($balances as $year => $balance) {
                    $years[(string) $year] = $balance->remaining($type->code);
                }
            }
            $types[$type->code] = [
                'tracks' => (bool) $type->tracks_balance,
                'future' => (bool) $type->allows_future,
                'name' => $type->name,
                'years' => $years,
            ];
        }

        return [
            'weekOff' => $this->workspace->weekOffDays($user),
            'holidays' => $holidays,
            'blocked' => $blocked,
            'types' => $types,
            'dayHours' => $this->workspace->leaveDayHours($user),
            'halfHours' => $this->workspace->halfLeaveHours($user),
        ];
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

    /**
     * @return array{tab: string, employee?: string}
     */
    private function approvalQuery(string $tab, string $employeeId): array
    {
        $params = ['tab' => $tab];
        if ($employeeId !== '' && $this->workspace->approvalUserIds($this->actor())->contains($employeeId)) {
            $params['employee'] = $employeeId;
        }

        return $params;
    }

    private function afterDecision(string $returnTo, string $employeeId = ''): RedirectResponse
    {
        return match ($returnTo) {
            'records-leave', 'hr-leave' => redirect()->route('admin.hr.index', ['section' => 'leave']),
            'hr-home' => redirect()->route('admin.dashboard.people'),
            'approvals-leaves' => redirect()->route('admin.people.approvals', $this->approvalQuery('leaves', $employeeId)),
            default => redirect()->route('admin.people.team', ['section' => 'leave']),
        };
    }
}
