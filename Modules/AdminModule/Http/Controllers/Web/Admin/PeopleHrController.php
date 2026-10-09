<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\AdminModule\Entities\PeopleDepartment;
use Modules\AdminModule\Entities\PeopleDepartmentLeavePolicy;
use Modules\AdminModule\Entities\PeopleStageLeavePolicy;
use Modules\AdminModule\Entities\PeopleLeaveAssignment;
use Modules\AdminModule\Entities\PeopleLeaveType;
use Modules\AdminModule\Entities\PeopleDocument;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeaveGrant;
use Modules\AdminModule\Entities\PeopleLeavePolicy;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeoplePayAdjustment;
use Modules\AdminModule\Entities\PeoplePayrollRun;
use Modules\AdminModule\Entities\PeoplePayslip;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleSalaryStructure;
use Modules\AdminModule\Entities\PeopleTimesheet;
use Modules\AdminModule\Entities\PeopleTimesheetTask;
use Modules\AdminModule\Services\PeopleLeaveAccrual;
use Modules\AdminModule\Services\PeoplePayroll;
use Modules\AdminModule\Services\PeopleWorkspace;
use Modules\UserManagement\Entities\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeopleHrController extends Controller
{
    public function __construct(
        private PeopleWorkspace $workspace,
        private PeoplePayroll $payroll,
        private PeopleLeaveAccrual $leaveAccrual,
    ) {
    }

    public function index(Request $request)
    {
        $section = $this->section($request);
        if ($section === 'people') {
            return redirect()->route('admin.employee.index');
        }
        if ($section === 'person') {
            $userId = (string) $request->query('user', '');
            if ($userId === '') {
                return redirect()->route('admin.employee.index');
            }

            return redirect()->route('admin.employee.profile', [
                'id' => $userId,
                'tab' => $request->query('tab', 'profile'),
            ]);
        }
        if ($redirect = $this->redirectMovedSection($request, $section)) {
            return $redirect;
        }
        if ($section === 'leave' && $request->query('tab') === 'requests') {
            return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'types']);
        }
        if (! $request->route('section') && $request->filled('section')) {
            return redirect()->route('admin.hr.index', array_merge(
                ['section' => $section],
                $request->except('section')
            ));
        }

        $actor = $this->requireHr();
        $this->workspace->boot();
        $staff = $this->workspace->staffUsers();
        $this->workspace->ensureMissingStaffFiles($staff);
        $staffIds = $staff->pluck('id');
        $period = $this->period($request);
        $profiles = PeopleProfile::query()->with(['manager', 'leavePolicy'])->whereIn('user_id', $staffIds)->get()->keyBy('user_id');
        $leaveTab = $section === 'leave' ? $this->leaveTab($request) : 'policies';
        $policyFocus = null;
        if ($section === 'leave' && $leaveTab === 'configure') {
            $policyFocus = PeopleLeavePolicy::query()->with('leaveType')->withCount('assignments')->find((string) $request->query('policy'));
            if (! $policyFocus) {
                return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'policies']);
            }
        }

        return view('adminmodule::admin.people.hr.index', [
            'workspace' => $this->workspace,
            'actor' => $actor,
            'section' => $section,
            'staff' => $staff,
            'profiles' => $profiles,
            'period' => $period,
            'balances' => $section === 'leave'
                ? PeopleLeaveBalance::query()->whereIn('user_id', $staffIds)->where('year', (int) now()->year)->get()->keyBy('user_id')
                : collect(),
            'leaveRequests' => in_array($section, ['home', 'leave'], true)
                ? PeopleLeaveRequest::query()->with('user')->whereIn('user_id', $staffIds)->latest()->limit(80)->get()
                : collect(),
            'documents' => collect(),
            'timesheets' => $section === 'home'
                ? PeopleTimesheet::query()->with('user')->whereIn('user_id', $staffIds)->where('status', 'pending')->get()
                : collect(),
            'structures' => $section === 'salary'
                ? PeopleSalaryStructure::query()->whereIn('user_id', $staffIds)->orderByDesc('effective_from')->get()->groupBy('user_id')
                : collect(),
            'adjustments' => $section === 'salary'
                ? PeoplePayAdjustment::query()->where('period', $period)->with('user')->latest()->get()
                : collect(),
            'payslips' => in_array($section, ['home', 'payroll'], true)
                ? PeoplePayslip::query()->forPayroll()->with('user')->where('period', $period)->orderBy('user_id')->get()
                : collect(),
            'run' => in_array($section, ['home', 'payroll', 'attendance'], true) ? $this->payroll->runFor($period) : null,
            'attendance' => $section === 'attendance'
                ? $this->workspace->attendanceMonth($staff, $profiles, $period)
                : null,
            'person' => $section === 'person' ? $this->person($request, $staff) : null,
            'departments' => PeopleDepartment::query()->orderBy('name')->get(),
            'departmentCounts' => PeopleProfile::query()
                ->where('department', '!=', '')
                ->selectRaw('department, count(*) as total')
                ->groupBy('department')
                ->pluck('total', 'department'),
            'missingDocuments' => PeopleDocument::query()->whereIn('user_id', $staffIds)->where('status', 'missing')->count(),
            'pendingLeave' => PeopleLeaveRequest::query()->whereIn('user_id', $staffIds)->where('status', 'pending')->count(),
            'leavePolicies' => $section === 'leave'
                ? PeopleLeavePolicy::query()->with('leaveType')->withCount('assignments')->orderBy('name')->get()
                : collect(),
            'leaveGrants' => $section === 'leave'
                ? PeopleLeaveGrant::query()->with('user')->latest()->limit(12)->get()
                : collect(),
            'leaveTypes' => in_array($section, ['leave', 'person'], true)
                ? PeopleLeaveType::query()->orderBy('sort')->orderBy('name')->get()
                : collect(),
            'leaveTab' => $leaveTab,
            'policyFocus' => $policyFocus,
            'policyAssignments' => $policyFocus
                ? PeopleLeaveAssignment::query()->with('user')->where('leave_policy_id', $policyFocus->id)->get()
                : collect(),
            'policyDepartments' => $policyFocus
                ? PeopleDepartmentLeavePolicy::query()->with('department')->where('leave_policy_id', $policyFocus->id)->get()
                : collect(),
            'policyStages' => $policyFocus
                ? PeopleStageLeavePolicy::query()->where('leave_policy_id', $policyFocus->id)->get()
                : collect(),
            'stageCounts' => PeopleProfile::query()->pluck('employment_stage')->countBy(fn ($stage) => $stage ?: 'permanent'),
            'leaveAssignments' => $section === 'leave'
                ? PeopleLeaveAssignment::query()->with('policy.leaveType')->whereIn('user_id', $staffIds)->get()->groupBy('user_id')
                : collect(),
            'timesheetTasks' => $section === 'configuration'
                ? PeopleTimesheetTask::query()->orderBy('sort')->orderBy('name')->get()
                : collect(),
            'timesheetSettings' => $section === 'configuration' ? $this->workspace->timesheetSettings() : null,
        ]);
    }

    public function updatePerson(Request $request): RedirectResponse
    {
        $this->requireHr();
        if ($request->input('section') === 'bank') {
            foreach (['bank_name', 'bank_account', 'bank_ifsc', 'pan', 'aadhaar', 'uan'] as $field) {
                $request->merge([$field => $request->input($field) ?: null]);
            }
            $data = $request->validate([
                'user_id' => ['required', 'uuid'],
                'bank_name' => ['nullable', 'string', 'max:120'],
                'bank_account' => ['nullable', 'string', 'max:40'],
                'bank_ifsc' => ['nullable', 'string', 'max:20'],
                'pan' => ['nullable', 'string', 'max:10'],
                'aadhaar' => ['nullable', 'string', 'max:12'],
                'uan' => ['nullable', 'string', 'max:20'],
            ]);
            $this->workspace->ensureStaffFile(User::query()->findOrFail($data['user_id']));
            PeopleProfile::query()->where('user_id', $data['user_id'])->update([
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account' => $data['bank_account'] ?? null,
                'bank_ifsc' => $data['bank_ifsc'] ?? null,
                'pan' => $data['pan'] ?? null,
                'aadhaar' => $data['aadhaar'] ?? null,
                'uan' => $data['uan'] ?? null,
            ]);
            Toastr::success('Bank details saved.');

            return redirect()->route('admin.employee.profile', ['id' => $data['user_id'], 'tab' => 'profile']);
        }
        $request->merge(['department' => $request->input('department') ?: null]);
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'job_title' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'work_location' => ['nullable', Rule::in(PeopleProfile::allowedWorkLocations(
                (string) PeopleProfile::query()->where('user_id', $request->input('user_id'))->value('work_location')
            ))],
            'employment_type' => ['required', Rule::in(['full_time', 'contract'])],
            'employment_status' => ['required', Rule::in(['active', 'notice', 'exited'])],
            'joined_on' => ['nullable', 'date'],
            'last_working_day' => ['nullable', 'date'],
            'date_of_birth' => ['nullable', 'date'],
            'emergency_contact' => ['nullable', 'string', 'max:120'],
            'manager_id' => ['nullable', 'uuid'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account' => ['nullable', 'string', 'max:40'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'pan' => ['nullable', 'string', 'max:10'],
            'aadhaar' => ['nullable', 'string', 'max:12'],
            'uan' => ['nullable', 'string', 'max:20'],
            'esi_number' => ['nullable', 'string', 'max:20'],
        ]);

        if (! empty($data['manager_id']) && $data['manager_id'] === $data['user_id']) {
            Toastr::error('A person cannot be their own manager.');

            return back()->withInput();
        }

        $currentDepartment = (string) PeopleProfile::query()->where('user_id', $data['user_id'])->value('department');
        if (! empty($data['department']) && $data['department'] !== $currentDepartment && ! PeopleDepartment::query()->where('name', $data['department'])->exists()) {
            Toastr::error('Choose a department from the list.');

            return back()->withInput();
        }

        $this->workspace->ensureStaffFile(User::query()->findOrFail($data['user_id']));
        $nextDepartment = (string) ($data['department'] ?? '');
        PeopleProfile::query()->where('user_id', $data['user_id'])->update([
            'job_title' => $data['job_title'],
            'department' => $data['department'] ?? '',
            'work_location' => $data['work_location'] ?? '',
            'employment_type' => $data['employment_type'],
            'employment_status' => $data['employment_status'],
            'joined_on' => $data['joined_on'] ?? null,
            'last_working_day' => $data['last_working_day'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'manager_id' => $data['manager_id'] ?: null,
            'address' => $data['address'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account' => $data['bank_account'] ?? null,
            'bank_ifsc' => $data['bank_ifsc'] ?? null,
            'pan' => $data['pan'] ?? null,
            'aadhaar' => $data['aadhaar'] ?? null,
            'uan' => $data['uan'] ?? null,
            'esi_number' => $data['esi_number'] ?? null,
        ]);
        if ($currentDepartment !== $nextDepartment) {
            $profile = PeopleProfile::query()->where('user_id', $data['user_id'])->first();
            if ($profile) {
                $this->leaveAccrual->syncPersonDepartment($profile, $currentDepartment, $nextDepartment);
            }
        }

        Toastr::success('People file saved.');

        $tab = (string) $request->input('return_tab', 'profile');
        if (in_array($tab, ['bank', 'documents'], true) || ! in_array($tab, ['profile', 'leaves', 'salary', 'payslips'], true)) {
            $tab = 'profile';
        }

        return redirect()->route('admin.employee.profile', ['id' => $data['user_id'], 'tab' => $tab]);
    }

    public function askDocument(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:80'],
        ]);

        $exists = PeopleDocument::query()->where('user_id', $data['user_id'])->where('title', $data['title'])->exists();
        if ($exists) {
            Toastr::error('That document is already on the file.');

            return back();
        }

        $document = PeopleDocument::query()->create([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'status' => 'missing',
        ]);
        $this->workspace->notifyDocumentRequested($document);
        Toastr::success('Document asked for.');

        return redirect()->route('admin.employee.profile', ['id' => $data['user_id'], 'tab' => 'profile']);
    }

    public function storePersonDocument(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:80'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:5120'],
        ]);

        $title = trim($data['title']);
        if (! $this->workspace->staffUsers()->firstWhere('id', $data['user_id'])) {
            Toastr::error('Choose a person on the company list.');

            return back()->withInput();
        }

        $this->workspace->ensureStaffFile(User::query()->findOrFail($data['user_id']));
        $existing = PeopleDocument::query()
            ->where('user_id', $data['user_id'])
            ->whereRaw('lower(title) = ?', [mb_strtolower($title)])
            ->first();
        if ($existing && $existing->file_path) {
            Toastr::error('That document is already on the file.');

            return back()->withInput();
        }

        $file = $request->file('file');
        $path = $file->store('people-documents/'.$data['user_id'], 'local');
        $saved = [
            'title' => $title,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'status' => 'verified',
            'uploaded_at' => now(),
            'rejection_note' => null,
        ];
        if ($existing) {
            $existing->forceFill($saved)->save();
        } else {
            PeopleDocument::query()->create(['user_id' => $data['user_id']] + $saved);
        }

        Toastr::success('Document uploaded.');

        return redirect()->route('admin.employee.profile', ['id' => $data['user_id'], 'tab' => 'profile']);
    }

    public function destroyPersonDocument(PeopleDocument $document): RedirectResponse
    {
        $this->requireHr();
        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }
        $userId = $document->user_id;
        $document->delete();
        Toastr::success('Document removed.');

        return redirect()->route('admin.employee.profile', ['id' => $userId, 'tab' => 'profile']);
    }

    public function rejectDocument(Request $request, PeopleDocument $document): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'rejection_note' => ['required', 'string', 'max:500'],
        ]);
        $document->forceFill([
            'status' => 'rejected',
            'rejection_note' => $data['rejection_note'],
        ])->save();
        $this->workspace->notifyDocumentDecided($document);
        Toastr::success('Document sent back.');

        return redirect()->route('admin.employee.profile', ['id' => $document->user_id, 'tab' => 'profile']);
    }

    public function storeLeaveType(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'short_name' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
        ], [
            'short_name.regex' => 'Use letters and numbers for the short name, like CL.',
        ]);
        $name = trim($data['name']);
        $short = strtoupper($data['short_name']);
        if ($this->leaveTypeNameTaken($name)) {
            Toastr::error('That leave type is already on the list.');

            return back()->withInput();
        }
        if ($this->leaveTypeShortTaken($short)) {
            Toastr::error('That short name is already on the list.');

            return back()->withInput();
        }

        PeopleLeaveType::query()->create([
            'name' => $name,
            'short_name' => $short,
            'code' => $this->leaveTypeCode($name),
            'tracks_balance' => $request->boolean('tracks_balance'),
            'allows_future' => $request->boolean('allows_future'),
            'sort' => (int) PeopleLeaveType::query()->max('sort') + 1,
        ]);
        Toastr::success('Leave type added.');

        return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'types']);
    }

    public function updateLeaveType(Request $request, PeopleLeaveType $leaveType): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'short_name' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
        ], [
            'short_name.regex' => 'Use letters and numbers for the short name, like CL.',
        ]);
        $name = trim($data['name']);
        $short = strtoupper($data['short_name']);
        if ($this->leaveTypeNameTaken($name, $leaveType->id)) {
            Toastr::error('That leave type is already on the list.');

            return back()->withInput();
        }
        if ($this->leaveTypeShortTaken($short, $leaveType->id)) {
            Toastr::error('That short name is already on the list.');

            return back()->withInput();
        }

        $leaveType->forceFill([
            'name' => $name,
            'short_name' => $short,
            'tracks_balance' => $request->boolean('tracks_balance'),
            'allows_future' => $request->boolean('allows_future'),
        ])->save();
        Toastr::success('Leave type saved.');

        return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'types']);
    }

    public function destroyLeaveType(PeopleLeaveType $leaveType): RedirectResponse
    {
        $this->requireHr();
        if ($this->leaveTypeInUse($leaveType)) {
            Toastr::error('This leave type is in use. Remove the policies and requests that use it first.');

            return back();
        }

        $leaveType->delete();
        Toastr::success('Leave type removed.');

        return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'types']);
    }

    public function storeLeavePolicy(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'leave_type_id' => ['required', 'uuid', Rule::exists('people_leave_types', 'id')->where('tracks_balance', 1)],
            'accrual_type' => ['required', Rule::in(['monthly', 'yearly'])],
            'days' => ['required', 'numeric', 'min:0.5', 'max:365'],
            'carry_limit' => ['nullable', 'numeric', 'min:0', 'max:365'],
        ]);

        PeopleLeavePolicy::query()->create([
            'name' => trim($data['name']),
            'leave_type_id' => $data['leave_type_id'],
            'accrual_type' => $data['accrual_type'],
            'days' => round((float) $data['days'], 1),
            'carry_limit' => round((float) ($data['carry_limit'] ?? 0), 1),
        ]);
        Toastr::success('Leave policy saved.');

        return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'policies']);
    }

    public function updateLeavePolicy(Request $request, PeopleLeavePolicy $policy): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'leave_type_id' => ['required', 'uuid', Rule::exists('people_leave_types', 'id')->where('tracks_balance', 1)],
            'accrual_type' => ['required', Rule::in(['monthly', 'yearly'])],
            'days' => ['required', 'numeric', 'min:0.5', 'max:365'],
            'carry_limit' => ['nullable', 'numeric', 'min:0', 'max:365'],
        ]);

        if ($data['leave_type_id'] !== $policy->leave_type_id && ($policy->assignments()->exists() || PeopleDepartmentLeavePolicy::query()->where('leave_policy_id', $policy->id)->exists() || PeopleStageLeavePolicy::query()->where('leave_policy_id', $policy->id)->exists())) {
            Toastr::error('Take this policy off people before you change the leave type.');

            return back()->withInput();
        }

        $policy->forceFill([
            'name' => trim($data['name']),
            'leave_type_id' => $data['leave_type_id'],
            'accrual_type' => $data['accrual_type'],
            'days' => round((float) $data['days'], 1),
            'carry_limit' => round((float) ($data['carry_limit'] ?? 0), 1),
        ])->save();
        Toastr::success('Leave policy saved.');

        return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'policies']);
    }

    public function destroyLeavePolicy(PeopleLeavePolicy $policy): RedirectResponse
    {
        $this->requireHr();
        if ($policy->assignments()->exists()) {
            Toastr::error('Take this policy off people before you remove it.');

            return back();
        }

        PeopleDepartmentLeavePolicy::query()->where('leave_policy_id', $policy->id)->delete();
        PeopleStageLeavePolicy::query()->where('leave_policy_id', $policy->id)->delete();
        $policy->delete();
        Toastr::success('Leave policy removed.');

        return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'policies']);
    }

    public function assignLeavePolicy(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'policy_id' => ['required', 'uuid', Rule::exists('people_leave_policies', 'id')],
            'user_id' => ['nullable', 'uuid'],
            'department_id' => ['nullable', 'uuid', Rule::exists('people_departments', 'id')],
            'employment_stage' => ['nullable', Rule::in(['probation', 'permanent'])],
        ]);

        if (empty($data['user_id']) && empty($data['department_id']) && empty($data['employment_stage'])) {
            Toastr::error('Choose an employee, a department, or an employee type.');

            return back();
        }

        $policy = PeopleLeavePolicy::query()->findOrFail($data['policy_id']);
        $when = $policy->accrual_type === 'monthly'
            ? 'The days are on the balance now. The next credit is on the 1st of next month.'
            : 'A share of the year is on the balance now. The full amount is added on 1 January.';
        if (! empty($data['employment_stage'])) {
            $count = $this->leaveAccrual->attachStage($data['employment_stage'], $policy, now());
            $label = $data['employment_stage'] === 'probation' ? 'Probation' : 'Permanent';
            $message = $count === 0
                ? $label.' will use this policy. No one in that employee type was updated.'
                : 'Leave policy assigned to '.$count.' '.$label.' '.($count === 1 ? 'employee' : 'employees').'. '.$when;
            Toastr::success($message);

            return redirect()->route('admin.hr.index', [
                'section' => 'leave',
                'tab' => 'configure',
                'policy' => $policy->id,
            ]);
        }
        if (! empty($data['department_id'])) {
            $department = PeopleDepartment::query()->findOrFail($data['department_id']);
            $count = $this->leaveAccrual->attachDepartment($department, $policy, now());
            $message = $count === 0
                ? $department->name.' will use this policy. No one is in that department yet.'
                : 'Leave policy assigned to '.$count.' '.($count === 1 ? 'person' : 'people').' in '.$department->name.'. '.$when;
            Toastr::success($message);

            return redirect()->route('admin.hr.index', [
                'section' => 'leave',
                'tab' => 'configure',
                'policy' => $policy->id,
            ]);
        }

        $person = $this->workspace->staffUsers()->firstWhere('id', $data['user_id']);
        if (! $person) {
            Toastr::error('Choose a person on the company list.');

            return back()->withInput();
        }

        $profile = $this->workspace->ensureStaffFile($person);
        if ($profile->employment_status === 'exited') {
            Toastr::error('That person has left.');

            return back();
        }

        $this->leaveAccrual->assign($profile, $policy, now(), 'employee');
        Toastr::success('Leave policy assigned to '.$this->workspace->displayName($person).'. '.$when);

        return redirect()->route('admin.hr.index', [
            'section' => 'leave',
            'tab' => 'configure',
            'policy' => $policy->id,
        ]);
    }

    public function detachLeaveDepartment(PeopleLeavePolicy $policy, PeopleDepartment $department): RedirectResponse
    {
        $this->requireHr();
        $this->leaveAccrual->detachDepartment($department, $policy);
        Toastr::success($department->name.' no longer uses this policy.');

        return redirect()->route('admin.hr.index', [
            'section' => 'leave',
            'tab' => 'configure',
            'policy' => $policy->id,
        ]);
    }

    public function detachLeaveStage(PeopleLeavePolicy $policy, string $stage): RedirectResponse
    {
        $this->requireHr();
        if (! in_array($stage, ['probation', 'permanent'], true)) {
            abort(404);
        }
        $this->leaveAccrual->detachStage($stage, $policy);
        Toastr::success(($stage === 'probation' ? 'Probation' : 'Permanent').' no longer uses this policy.');

        return redirect()->route('admin.hr.index', [
            'section' => 'leave',
            'tab' => 'configure',
            'policy' => $policy->id,
        ]);
    }

    public function unassignLeavePolicy(PeopleLeaveAssignment $assignment): RedirectResponse
    {
        $this->requireHr();
        $policyId = $assignment->leave_policy_id;
        $this->leaveAccrual->releaseAssignment($assignment);
        Toastr::success('Direct assignment removed. An employee type or department policy applies again when one is set.');

        return redirect()->route('admin.hr.index', [
            'section' => 'leave',
            'tab' => 'configure',
            'policy' => $policyId,
        ]);
    }

    public function grantLeave(Request $request): RedirectResponse
    {
        $actor = $this->requireHr();
        $request->merge([
            'direction' => $request->input('direction') ?: 'add',
            'note' => $request->input('note') ?: null,
        ]);
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'leave_type' => ['required', Rule::exists('people_leave_types', 'code')->where('tracks_balance', 1)],
            'direction' => ['required', Rule::in(['add', 'remove'])],
            'days' => ['required', 'numeric', 'min:0.5', 'max:365'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        if (! $this->workspace->staffUsers()->firstWhere('id', $data['user_id'])) {
            Toastr::error('Choose a person on the company list.');

            return back()->withInput();
        }

        $this->workspace->ensureStaffFile(User::query()->findOrFail($data['user_id']));
        try {
            $this->leaveAccrual->adjust(
                $data['user_id'],
                $data['leave_type'],
                (float) $data['days'],
                $data['direction'],
                $actor->id,
                $data['note'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back()->withInput();
        }

        $label = $this->workspace->leaveLabel($data['leave_type']);
        Toastr::success($data['direction'] === 'remove' ? $label.' leave removed.' : $label.' leave added.');

        return $this->hrRedirect($request, 'leave', 'leaves', $data['user_id'], ['tab' => 'policies']);
    }

    public function cancelLeave(PeopleLeaveRequest $leaveRequest): RedirectResponse
    {
        $actor = $this->requireHr();
        try {
            $this->workspace->cancelLeave($actor, $leaveRequest, true);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back();
        }

        Toastr::success('Leave cancelled and the balance restored.');

        return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'policies']);
    }

    public function saveSalary(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'basic' => ['required', 'numeric', 'min:0'],
            'hra' => ['required', 'numeric', 'min:0'],
            'special_allowance' => ['required', 'numeric', 'min:0'],
            'pf_employee' => ['required', 'numeric', 'min:0'],
            'pf_employer' => ['required', 'numeric', 'min:0'],
            'professional_tax' => ['required', 'numeric', 'min:0'],
            'tds' => ['required', 'numeric', 'min:0'],
            'other_deduction' => ['required', 'numeric', 'min:0'],
        ]);

        PeopleSalaryStructure::query()->updateOrCreate(
            ['user_id' => $data['user_id'], 'effective_from' => $data['effective_from']],
            collect($data)->except(['user_id', 'effective_from'])->all()
        );
        Toastr::success('Salary saved from '.Carbon::parse($data['effective_from'])->format('j F Y').'.');

        return $this->hrRedirect($request, 'salary', 'salary', $data['user_id'], ['period' => substr($data['effective_from'], 0, 7)]);
    }

    public function saveAdjustment(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'period' => ['required', 'date_format:Y-m'],
            'label' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric'],
        ]);

        if ($this->payroll->isLocked($data['period'])) {
            Toastr::error('That month is locked.');

            return back();
        }

        PeoplePayAdjustment::query()->create($data);
        $slip = PeoplePayslip::query()
            ->where('user_id', $data['user_id'])
            ->where('period', $data['period'])
            ->where('status', 'draft')
            ->first();
        if ($slip && empty($slip->breakdown['missing_structure'])) {
            $this->payroll->syncBonuses($slip);
            Toastr::success('Added to this month’s payslip.');
        } else {
            Toastr::success('Saved. It will be on the payslip when you calculate pay.');
        }

        return $this->hrRedirect($request, 'salary', 'salary', $data['user_id'], ['period' => $data['period']]);
    }

    public function buildPayroll(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'count_missing_days' => ['nullable', 'boolean'],
        ]);

        try {
            $this->payroll->buildMonth($data['period'], $request->boolean('count_missing_days'));
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back();
        }

        Toastr::success('Pay calculated for '.$data['period'].'.');

        return redirect()->route('admin.accounts.payroll', ['period' => $data['period']]);
    }

    public function holdPayslip(PeoplePayslip $payslip): RedirectResponse
    {
        $this->requireHr();
        if ($payslip->status !== 'draft' || $this->payroll->isLocked($payslip->period)) {
            Toastr::error('You can only leave someone out before payslips are sent.');

            return back();
        }

        $payslip->forceFill(['held' => ! $payslip->held])->save();
        Toastr::success($payslip->held ? 'Left out of this month’s pay.' : 'Put back into this month’s pay.');

        return redirect()->route('admin.accounts.payroll', ['period' => $payslip->period]);
    }

    public function addBonus(Request $request, PeoplePayslip $payslip): RedirectResponse
    {
        $this->requireHr();
        $validator = validator($request->all(), [
            'label' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'not_in:0'],
        ]);
        if ($validator->fails()) {
            return redirect()
                ->route('admin.accounts.payroll', ['period' => $payslip->period, 'bonus' => $payslip->id])
                ->withErrors($validator)
                ->withInput();
        }
        $data = $validator->validated();
        if ($payslip->status !== 'draft' || $this->payroll->isLocked($payslip->period) || ! empty($payslip->breakdown['missing_structure'])) {
            Toastr::error('You can add to a payslip only before it is sent.');

            return back();
        }

        PeoplePayAdjustment::query()->create([
            'user_id' => $payslip->user_id,
            'period' => $payslip->period,
            'label' => trim($data['label']),
            'amount' => round((float) $data['amount'], 2),
        ]);
        $this->payroll->syncBonuses($payslip);
        Toastr::success('Added to this payslip.');

        return redirect()->route('admin.accounts.payroll', ['period' => $payslip->period]);
    }

    public function removeBonus(PeoplePayAdjustment $adjustment): RedirectResponse
    {
        $this->requireHr();
        $payslip = PeoplePayslip::query()
            ->where('user_id', $adjustment->user_id)
            ->where('period', $adjustment->period)
            ->first();
        if (! $payslip || $payslip->status !== 'draft' || $this->payroll->isLocked($adjustment->period)) {
            Toastr::error('You can change a payslip only before it is sent.');

            return back();
        }

        $period = $adjustment->period;
        $adjustment->delete();
        $this->payroll->syncBonuses($payslip);
        Toastr::success('Removed from this payslip.');

        return redirect()->route('admin.accounts.payroll', ['period' => $period]);
    }

    public function publishPayroll(Request $request): RedirectResponse
    {
        $this->requireHr();
        $actor = $this->requireHr();
        $data = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        $run = $this->payroll->runFor($data['period']);
        if (! $run || $run->status === 'locked') {
            Toastr::error('Build a draft before you publish.');

            return back();
        }

        $count = PeoplePayslip::query()
            ->forPayroll()
            ->where('period', $data['period'])
            ->where('status', 'draft')
            ->where('held', false)
            ->update(['status' => 'published', 'published_at' => now(), 'updated_at' => now()]);

        $run->forceFill([
            'status' => 'published',
            'attendance_locked' => true,
            'published_by' => $actor->id,
            'published_at' => now(),
        ])->save();

        Toastr::success($count.' payslip'.($count === 1 ? '' : 's').' published.');

        return redirect()->route('admin.accounts.payroll', ['period' => $data['period']]);
    }

    public function lockAttendance(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        $run = PeoplePayrollRun::query()->firstOrCreate(['period' => $data['period']], ['status' => 'draft']);
        if ($run->status === 'locked') {
            Toastr::error('That month is already locked.');

            return back();
        }

        $run->forceFill(['attendance_locked' => true])->save();
        Toastr::success('Attendance for that month is locked.');

        return redirect()->route('admin.hr.attendance', ['period' => $data['period']]);
    }

    public function saveAttendanceMarks(Request $request): RedirectResponse
    {
        $actor = $this->requireHr();
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'user_id' => ['required', 'uuid'],
            'marks' => ['nullable', 'array'],
            'marks.*' => ['nullable', Rule::in(['present', 'absent', 'half'])],
        ]);

        try {
            $this->workspace->saveAttendanceMarks($actor, $data['user_id'], $data['period'], $data['marks'] ?? []);
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return redirect()->route('admin.hr.attendance', ['period' => $data['period'], 'edit' => $data['user_id']]);
        }

        Toastr::success('Attendance saved.');

        return redirect()->route('admin.hr.attendance', ['period' => $data['period']]);
    }

    public function lockPayroll(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        $run = $this->payroll->runFor($data['period']);
        if (! $run || $run->status !== 'published') {
            Toastr::error('Publish the month before you lock it.');

            return back();
        }

        $run->forceFill([
            'status' => 'locked',
            'attendance_locked' => true,
            'locked_at' => now(),
        ])->save();
        Toastr::success('Month locked. A correction is a later adjustment.');

        return redirect()->route('admin.accounts.payroll', ['period' => $data['period']]);
    }

    public function netPayFile(Request $request): StreamedResponse
    {
        $this->requireHr();
        $period = $this->period($request);
        $slips = PeoplePayslip::query()
            ->forPayroll()
            ->with('user')
            ->where('period', $period)
            ->where('held', false)
            ->get()
            ->filter(fn (PeoplePayslip $slip) => empty($slip->breakdown['missing_structure']))
            ->sortBy(fn (PeoplePayslip $slip) => $slip->user ? $this->workspace->displayName($slip->user) : '')
            ->values();

        $bases = $this->payroll->baseSalaries($slips, $period);

        return response()->streamDownload(function () use ($slips, $bases) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Person', 'Base salary', 'Loss of pay (days)', 'Loss of pay', 'Bonuses', 'Net payable']);
            $salary = 0.0;
            $lop = 0.0;
            $bonuses = 0.0;
            $net = 0.0;
            foreach ($slips as $slip) {
                $base = (float) ($bases[$slip->id] ?? 0);
                $lopAmount = PeoplePayroll::lopAmount($slip->breakdown);
                $bonusAmount = PeoplePayroll::bonusTotal($slip->breakdown);
                $salary += $base;
                $lop += $lopAmount;
                $bonuses += $bonusAmount;
                $net += (float) $slip->net;
                fputcsv($out, [
                    $slip->user ? $this->workspace->displayName($slip->user) : '',
                    number_format($base, 2, '.', ''),
                    rtrim(rtrim(number_format((float) $slip->lop_days, 1, '.', ''), '0'), '.'),
                    number_format($lopAmount, 2, '.', ''),
                    number_format($bonusAmount, 2, '.', ''),
                    number_format((float) $slip->net, 2, '.', ''),
                ]);
            }
            fputcsv($out, ['Will be paid', number_format($salary, 2, '.', ''), '', number_format($lop, 2, '.', ''), number_format($bonuses, 2, '.', ''), number_format($net, 2, '.', '')]);
            fclose($out);
        }, 'net-pay-'.$period.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);
        $name = trim($data['name']);
        if ($this->departmentNameTaken($name)) {
            Toastr::error('That department is already on the list.');

            return back()->withInput();
        }

        PeopleDepartment::query()->create(['name' => $name]);
        Toastr::success('Department added.');

        return redirect()->route('admin.hr.index', ['section' => 'departments']);
    }

    public function updateDepartment(Request $request, PeopleDepartment $department): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);
        $name = trim($data['name']);
        if ($this->departmentNameTaken($name, $department->id)) {
            Toastr::error('That department is already on the list.');

            return back()->withInput();
        }

        $previous = $department->name;
        $department->forceFill(['name' => $name])->save();
        if ($previous !== $name) {
            PeopleProfile::query()->where('department', $previous)->update(['department' => $name]);
        }
        Toastr::success('Department saved.');

        return redirect()->route('admin.hr.index', ['section' => 'departments']);
    }

    public function destroyDepartment(PeopleDepartment $department): RedirectResponse
    {
        $this->requireHr();
        if (PeopleProfile::query()->where('department', $department->name)->exists()) {
            Toastr::error('Move people out of this department before you remove it.');

            return back();
        }

        PeopleDepartmentLeavePolicy::query()->where('department_id', $department->id)->delete();
        $department->delete();
        Toastr::success('Department removed.');

        return redirect()->route('admin.hr.index', ['section' => 'departments']);
    }

    public function saveTimesheetConfiguration(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'starts_on' => ['required', 'date'],
            'min_hours_full_time' => ['required', 'numeric', 'min:0', 'max:24'],
            'min_hours_part_time' => ['required', 'numeric', 'min:0', 'max:24'],
            'week_off' => ['nullable', 'array', 'max:6'],
            'week_off.*' => ['required', Rule::in(PeopleWorkspace::DAY_KEYS)],
        ], [
            'week_off.max' => 'Keep at least one working day in the week.',
        ]);

        $fullTime = round((float) $data['min_hours_full_time'], 1);
        $partTime = round((float) $data['min_hours_part_time'], 1);
        $off = array_values(array_intersect(PeopleWorkspace::DAY_KEYS, $data['week_off'] ?? []));
        $this->workspace->timesheetSettings()->forceFill([
            'min_hours' => $fullTime,
            'min_hours_full_time' => $fullTime,
            'min_hours_part_time' => $partTime,
            'week_off' => $off,
            'starts_on' => Carbon::parse($data['starts_on'])->toDateString(),
        ])->save();
        Toastr::success('Timesheet settings saved.');

        return redirect()->route('admin.hr.index', ['section' => 'configuration']);
    }

    public function storeTimesheetTask(Request $request): RedirectResponse
    {
        $this->requireHr();
        $name = $this->timesheetTaskName($request);
        if ($name === null) {
            return back()->withInput();
        }

        $sort = (int) PeopleTimesheetTask::query()->max('sort') + 1;
        PeopleTimesheetTask::query()->create(['name' => $name, 'sort' => $sort]);
        Toastr::success('Additional hours added.');

        return redirect()->route('admin.hr.index', ['section' => 'configuration']);
    }

    public function updateTimesheetTask(Request $request, PeopleTimesheetTask $timesheetTask): RedirectResponse
    {
        $this->requireHr();
        $name = $this->timesheetTaskName($request, $timesheetTask->id);
        if ($name === null) {
            return back()->withInput();
        }

        $timesheetTask->forceFill(['name' => $name])->save();
        Toastr::success('Additional hours saved.');

        return redirect()->route('admin.hr.index', ['section' => 'configuration']);
    }

    public function destroyTimesheetTask(PeopleTimesheetTask $timesheetTask): RedirectResponse
    {
        $this->requireHr();
        $timesheetTask->delete();
        Toastr::success('Additional hours removed.');

        return redirect()->route('admin.hr.index', ['section' => 'configuration']);
    }

    private function person(Request $request, $staff): ?array
    {
        $id = (string) $request->query('user', '');
        $user = $staff->firstWhere('id', $id);
        if (! $user) {
            return null;
        }

        $tab = (string) $request->query('tab', 'documents');
        if (! in_array($tab, ['bank', 'documents', 'leaves', 'salary', 'payslips'], true)) {
            $tab = 'documents';
        }

        return [
            'user' => $user,
            'profile' => PeopleProfile::query()->with(['manager', 'leavePolicy'])->where('user_id', $user->id)->first(),
            'assignments' => PeopleLeaveAssignment::query()->with('policy.leaveType')->where('user_id', $user->id)->get(),
            'tab' => $tab,
            'documents' => PeopleDocument::query()->where('user_id', $user->id)->orderBy('title')->get(),
            'balance' => PeopleLeaveBalance::query()->where('user_id', $user->id)->where('year', (int) now()->year)->first(),
            'leaveHistory' => $this->workspace->leaveHistory($user->id),
            'leaveRequests' => PeopleLeaveRequest::query()->where('user_id', $user->id)->latest()->get(),
            'structures' => PeopleSalaryStructure::query()->where('user_id', $user->id)->orderByDesc('effective_from')->get(),
            'adjustments' => PeoplePayAdjustment::query()->where('user_id', $user->id)->latest()->limit(12)->get(),
            'payslips' => PeoplePayslip::query()->where('user_id', $user->id)->orderByDesc('period')->get(),
        ];
    }

    private function timesheetTaskName(Request $request, ?string $ignoreId = null): ?string
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);
        $name = trim($data['name']);
        if ($this->reservedTimesheetTask($name)) {
            Toastr::error('Leave and Partial Leave are already on every timesheet.');

            return null;
        }
        if ($this->timesheetTaskNameTaken($name, $ignoreId)) {
            Toastr::error('That name is already on the list.');

            return null;
        }

        return $name;
    }

    private function reservedTimesheetTask(string $name): bool
    {
        return in_array(mb_strtolower($name), ['leave', 'partial leave'], true);
    }

    private function timesheetTaskNameTaken(string $name, ?string $ignoreId = null): bool
    {
        return PeopleTimesheetTask::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();
    }

    private function departmentNameTaken(string $name, ?string $ignoreId = null): bool
    {
        return PeopleDepartment::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();
    }

    private function hrRedirect(Request $request, string $fallbackSection, string $personTab, ?string $userId = null, array $fallbackQuery = []): RedirectResponse
    {
        $userId = $userId ?: (string) $request->input('user_id');
        if ($request->input('return_to') === 'person' && $userId !== '') {
            return redirect()->route('admin.employee.profile', [
                'id' => $userId,
                'tab' => $personTab,
            ]);
        }

        if (in_array($fallbackSection, ['attendance', 'payroll', 'salary'], true)) {
            return redirect()->route('admin.accounts.'.$fallbackSection, $fallbackQuery);
        }

        return redirect()->route('admin.hr.index', array_merge(['section' => $fallbackSection], $fallbackQuery));
    }

    private function period(Request $request): string
    {
        $period = (string) $request->query('period', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $period) ? $period : now()->format('Y-m');
    }

    private function leaveTab(Request $request): string
    {
        $tab = (string) $request->query('tab', 'types');

        return in_array($tab, ['types', 'policies', 'balances', 'configure'], true) ? $tab : 'types';
    }

    private function leaveTypeShortTaken(string $short, ?string $ignoreId = null): bool
    {
        return PeopleLeaveType::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereRaw('lower(short_name) = ?', [mb_strtolower($short)])
            ->exists();
    }

    private function leaveTypeNameTaken(string $name, ?string $ignoreId = null): bool
    {
        return PeopleLeaveType::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();
    }

    private function leaveTypeCode(string $name): string
    {
        $base = Str::slug($name, '_');
        $base = substr($base !== '' ? $base : 'leave', 0, 24);
        $code = $base;
        $n = 2;
        while (PeopleLeaveType::query()->where('code', $code)->exists()) {
            $suffix = '_'.$n;
            $code = substr($base, 0, 32 - strlen($suffix)).$suffix;
            $n++;
        }

        return $code;
    }

    private function leaveTypeInUse(PeopleLeaveType $type): bool
    {
        if ($type->policies()->exists()) {
            return true;
        }
        if (PeopleLeaveRequest::query()->where('leave_type', $type->code)->exists()) {
            return true;
        }
        if (PeopleLeaveGrant::query()->where('leave_type', $type->code)->exists()) {
            return true;
        }
        if (in_array($type->code, ['casual', 'sick', 'earned'], true)) {
            return PeopleLeaveBalance::query()
                ->where(function ($query) use ($type) {
                    $query->where($type->code.'_allowance', '>', 0)
                        ->orWhere($type->code.'_used', '>', 0);
                })
                ->exists();
        }

        return PeopleLeaveBalance::query()
            ->where(function ($query) use ($type) {
                $query->whereRaw('JSON_EXTRACT(extra, ?) > 0', ['$.'.$type->code.'.allowance'])
                    ->orWhereRaw('JSON_EXTRACT(extra, ?) > 0', ['$.'.$type->code.'.used']);
            })
            ->exists();
    }

    private function section(Request $request): string
    {
        $section = match (true) {
            $request->routeIs('admin.hr.attendance') => 'attendance',
            $request->routeIs('admin.accounts.payroll') => 'payroll',
            $request->routeIs('admin.accounts.salary') => 'salary',
            default => (string) ($request->route('section') ?: $request->query('section', 'home')),
        };
        $allowed = ['home', 'people', 'person', 'departments', 'leave', 'attendance', 'salary', 'payroll', 'configuration'];

        return in_array($section, $allowed, true) ? $section : 'home';
    }

    private function redirectMovedSection(Request $request, string $section): ?RedirectResponse
    {
        if ($request->routeIs('admin.accounts.*')) {
            return null;
        }

        $query = $request->except('section');

        return match ($section) {
            'payroll' => redirect()->route('admin.accounts.payroll', $query),
            'salary' => redirect()->route('admin.accounts.salary', $query),
            'home' => redirect()->route('admin.dashboard.people'),
            default => null,
        };
    }

    private function requireHr(): User
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user && in_array($user->user_type, ADMIN_USER_TYPES, true) && $this->workspace->isHr($user), 403);

        return $user;
    }
}
