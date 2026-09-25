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
        $actor = $this->requireHr();
        $this->workspace->boot();
        $section = $this->section($request);
        $staff = $this->workspace->staffUsers();
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
            'timesheets' => $section === 'attendance'
                ? PeopleTimesheet::query()->with('user')->whereIn('user_id', $staffIds)->latest('week_starts_on')->limit(60)->get()
                : collect(),
            'structures' => $section === 'salary'
                ? PeopleSalaryStructure::query()->whereIn('user_id', $staffIds)->orderByDesc('effective_from')->get()->groupBy('user_id')
                : collect(),
            'adjustments' => $section === 'salary'
                ? PeoplePayAdjustment::query()->where('period', $period)->with('user')->latest()->get()
                : collect(),
            'payslips' => in_array($section, ['home', 'payroll'], true)
                ? PeoplePayslip::query()->with('user')->where('period', $period)->orderBy('user_id')->get()
                : collect(),
            'run' => in_array($section, ['home', 'payroll', 'attendance'], true) ? $this->payroll->runFor($period) : null,
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
            'leaveAssignments' => $section === 'leave'
                ? PeopleLeaveAssignment::query()->with('policy.leaveType')->whereIn('user_id', $staffIds)->get()->groupBy('user_id')
                : collect(),
        ]);
    }

    public function updatePerson(Request $request): RedirectResponse
    {
        $this->requireHr();
        $request->merge(['department' => $request->input('department') ?: null]);
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'job_title' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'work_location' => ['nullable', 'string', 'max:120'],
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

        $tab = (string) $request->input('return_tab', 'documents');
        if (! in_array($tab, ['bank', 'documents', 'leaves', 'salary', 'payslips'], true)) {
            $tab = 'documents';
        }

        return redirect()->route('admin.hr.index', ['section' => 'person', 'user' => $data['user_id'], 'tab' => $tab]);
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

        PeopleDocument::query()->create([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'status' => 'missing',
        ]);
        Toastr::success('Document asked for.');

        return $this->hrRedirect($request, 'people', 'documents');
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

        return redirect()->route('admin.hr.index', ['section' => 'person', 'user' => $data['user_id'], 'tab' => 'documents']);
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

        return redirect()->route('admin.hr.index', ['section' => 'person', 'user' => $userId, 'tab' => 'documents']);
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
        Toastr::success('Document sent back.');

        return $this->hrRedirect($request, 'people', 'documents', $document->user_id);
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
        ]);

        PeopleLeavePolicy::query()->create([
            'name' => trim($data['name']),
            'leave_type_id' => $data['leave_type_id'],
            'accrual_type' => $data['accrual_type'],
            'days' => round((float) $data['days'], 1),
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
        ]);

        $policy->forceFill([
            'name' => trim($data['name']),
            'leave_type_id' => $data['leave_type_id'],
            'accrual_type' => $data['accrual_type'],
            'days' => round((float) $data['days'], 1),
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
        ]);

        if (empty($data['user_id']) && empty($data['department_id'])) {
            Toastr::error('Choose an employee or a department.');

            return back();
        }

        $policy = PeopleLeavePolicy::query()->findOrFail($data['policy_id']);
        $when = $policy->accrual_type === 'monthly'
            ? 'The days are on the balance now, and again one month from today.'
            : 'The days are on the balance now, and again one year from today.';
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

    public function unassignLeavePolicy(PeopleLeaveAssignment $assignment): RedirectResponse
    {
        $this->requireHr();
        $policyId = $assignment->leave_policy_id;
        $assignment->delete();
        Toastr::success('Policy removed from this person.');

        return redirect()->route('admin.hr.index', [
            'section' => 'leave',
            'tab' => 'configure',
            'policy' => $policyId,
        ]);
    }

    public function grantLeave(Request $request): RedirectResponse
    {
        $actor = $this->requireHr();
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'leave_type' => ['required', Rule::exists('people_leave_types', 'code')->where('tracks_balance', 1)],
            'days' => ['required', 'numeric', 'min:0.5', 'max:365'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        if (! $this->workspace->staffUsers()->firstWhere('id', $data['user_id'])) {
            Toastr::error('Choose a person on the company list.');

            return back()->withInput();
        }

        $this->workspace->ensureStaffFile(User::query()->findOrFail($data['user_id']));
        try {
            $this->leaveAccrual->grant(
                $data['user_id'],
                $data['leave_type'],
                (float) $data['days'],
                $actor->id,
                $data['note'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back()->withInput();
        }

        Toastr::success($this->workspace->leaveLabel($data['leave_type']).' leave added.');

        return redirect()->route('admin.hr.index', ['section' => 'leave', 'tab' => 'policies']);
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
        Toastr::success('One-off item saved. Rebuild the month to apply it.');

        return $this->hrRedirect($request, 'salary', 'salary', $data['user_id'], ['period' => $data['period']]);
    }

    public function buildPayroll(Request $request): RedirectResponse
    {
        $this->requireHr();
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'count_missing_weeks' => ['nullable', 'boolean'],
        ]);

        try {
            $this->payroll->buildMonth($data['period'], $request->boolean('count_missing_weeks'));
        } catch (\InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage());

            return back();
        }

        Toastr::success('Draft payroll built for '.$data['period'].'.');

        return redirect()->route('admin.hr.index', ['section' => 'payroll', 'period' => $data['period']]);
    }

    public function holdPayslip(PeoplePayslip $payslip): RedirectResponse
    {
        $this->requireHr();
        if ($payslip->status !== 'draft' || $this->payroll->isLocked($payslip->period)) {
            Toastr::error('Only a draft month can be held.');

            return back();
        }

        $payslip->forceFill(['held' => ! $payslip->held])->save();
        Toastr::success($payslip->held ? 'Held out of this month.' : 'Put back into this month.');

        return redirect()->route('admin.hr.index', ['section' => 'payroll', 'period' => $payslip->period]);
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

        return redirect()->route('admin.hr.index', ['section' => 'payroll', 'period' => $data['period']]);
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

        return redirect()->route('admin.hr.index', ['section' => 'attendance', 'period' => $data['period']]);
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

        return redirect()->route('admin.hr.index', ['section' => 'payroll', 'period' => $data['period']]);
    }

    public function bankFile(Request $request): StreamedResponse
    {
        $this->requireHr();
        $period = $this->period($request);
        $slips = PeoplePayslip::query()
            ->with('user')
            ->where('period', $period)
            ->where('status', 'published')
            ->where('held', false)
            ->orderBy('user_id')
            ->get();

        return response()->streamDownload(function () use ($slips, $period) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Account', 'IFSC', 'Net', 'Period']);
            foreach ($slips as $slip) {
                $profile = PeopleProfile::query()->where('user_id', $slip->user_id)->first();
                fputcsv($out, [
                    $slip->user ? $this->workspace->displayName($slip->user) : '',
                    $profile->bank_account ?? '',
                    $profile->bank_ifsc ?? '',
                    number_format((float) $slip->net, 2, '.', ''),
                    $period,
                ]);
            }
            fclose($out);
        }, 'salary-'.$period.'.csv', ['Content-Type' => 'text/csv']);
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
            'leaveRequests' => PeopleLeaveRequest::query()->where('user_id', $user->id)->latest()->get(),
            'structures' => PeopleSalaryStructure::query()->where('user_id', $user->id)->orderByDesc('effective_from')->get(),
            'adjustments' => PeoplePayAdjustment::query()->where('user_id', $user->id)->latest()->limit(12)->get(),
            'payslips' => PeoplePayslip::query()->where('user_id', $user->id)->orderByDesc('period')->get(),
        ];
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
            return redirect()->route('admin.hr.index', [
                'section' => 'person',
                'user' => $userId,
                'tab' => $personTab,
            ]);
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
        $tab = (string) $request->query('tab', 'policies');

        return in_array($tab, ['types', 'policies', 'configure'], true) ? $tab : 'policies';
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
        $section = (string) $request->query('section', 'home');
        $allowed = ['home', 'people', 'person', 'departments', 'leave', 'attendance', 'salary', 'payroll'];

        return in_array($section, $allowed, true) ? $section : 'home';
    }

    private function requireHr(): User
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user && in_array($user->user_type, ADMIN_USER_TYPES, true) && $this->workspace->isHr($user), 403);

        return $user;
    }
}
