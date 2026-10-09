<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;


use App\Traits\UploadSizeHelperTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\AdminModule\Entities\PeopleDepartment;
use Modules\AdminModule\Entities\PeopleDocument;
use Modules\AdminModule\Entities\PeopleLeaveAssignment;
use Modules\AdminModule\Entities\PeopleLeaveBalance;
use Modules\AdminModule\Entities\PeopleLeaveRequest;
use Modules\AdminModule\Entities\PeopleLeaveType;
use Modules\AdminModule\Entities\PeoplePayAdjustment;
use Modules\AdminModule\Entities\PeoplePayslip;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Entities\PeopleSalaryStructure;
use Modules\AdminModule\Services\PeopleLeaveAccrual;
use Modules\AdminModule\Services\PeopleWorkspace;
use Modules\UserManagement\Entities\EmployeeRoleAccess;
use Modules\UserManagement\Entities\EmployeeRoleSection;
use Modules\UserManagement\Entities\Role;
use Modules\UserManagement\Entities\RoleAccess;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use function bcrypt;
use function file_remover;
use function file_uploader;
use function response;
use function response_formatter;

class EmployeeController extends Controller
{
    protected User $employee;
    protected UserAddress $address;
    protected Role $role;
    protected EmployeeRoleSection $employeeRoleSection;
    protected EmployeeRoleAccess $employeeRoleAccess;
    protected RoleAccess $roleAccess;

    use AuthorizesRequests;
    use UploadSizeHelperTrait;

    public function __construct(User $employee, UserAddress $address, Role $role, EmployeeRoleSection $employeeRoleSection, EmployeeRoleAccess $employeeRoleAccess, RoleAccess $roleAccess)
    {
        $this->employee = $employee;
        $this->address = $address;
        $this->role = $role;
        $this->employeeRoleSection = $employeeRoleSection;
        $this->employeeRoleAccess = $employeeRoleAccess;
        $this->roleAccess = $roleAccess;
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function create(Request $request): Application|Factory|View
    {
        $this->authorize('employee_add');
        $roles = $this->role->where(['is_active' => 1])->get();

        return view('adminmodule::admin.employee.create', compact('roles'));
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function index(Request $request): Application|Factory|View
    {
        $this->authorize('employee_view');
        $search = $this->employeeListSearchTerm($request);
        $status = $request->has('status') ? $request['status'] : 'all';
        $queryParams = ['search' => $search, 'status' => $status];

        $employees = $this->employee->OfType(['admin-employee'])->with(['roles', 'peopleProfile'])
            ->when($search !== '', function ($query) use ($search) {
                $this->applyEmployeeListSearch($query, $search);
            })
            ->when($status != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })
            ->latest()->paginate(pagination_limit())->appends($queryParams);

        $workspace = app(PeopleWorkspace::class);
        $employees->getCollection()->each(function (User $user) use ($workspace) {
            if (! $user->peopleProfile) {
                $user->setRelation('peopleProfile', $workspace->ensureStaffFile($user));
            }
        });

        return view('adminmodule::admin.employee.list', compact('employees', 'status', 'search'));
    }


    /**
     * Store a newly created resource in storage.
     * @param ProviderStoreRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employee_add');

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'work_schedule' => ['required', Rule::in(['full_time', 'part_time'])],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['uuid', Rule::exists('roles', 'id')],
        ]);

        $employeeId = null;

        DB::transaction(function () use ($data, &$employeeId) {
            $employee = new User();
            $employee->first_name = $data['first_name'];
            $employee->last_name = $data['last_name'];
            $employee->email = $data['email'];
            $employee->phone = null;
            $employee->profile_image = 'default.png';
            $employee->identification_number = null;
            $employee->identification_type = 'nid';
            $employee->identification_image = [];
            $employee->password = bcrypt($data['password']);
            $employee->user_type = 'admin-employee';
            $employee->is_active = 1;
            $employee->save();
            $employeeId = $employee->id;

            $this->assignRoles($employee, $data['role_ids']);
            $profile = app(PeopleWorkspace::class)->ensureStaffFile($employee);
            $profile->work_schedule = $data['work_schedule'];
            $profile->save();
        });

        $employee = User::query()->find($employeeId);
        if ($employee) {
            app(\Modules\ChattingModule\Services\StaffGroupChannelService::class)->ensureGroupForUser($employee);
        }

        Toastr::success(translate(DEFAULT_STORE_200['message']));

        return redirect()->route('admin.employee.profile', $employeeId);
    }

    public function profile(string $id): Application|Factory|View
    {
        $this->authorize('employee_view');
        $employee = $this->employeeRecord($id);
        $workspace = app(PeopleWorkspace::class);
        $workspace->boot();
        $workspace->ensureStaffFile($employee);
        $tab = (string) request()->query('tab', 'profile');
        if (in_array($tab, ['documents', 'bank'], true) || ! in_array($tab, ['profile', 'leaves', 'salary', 'payslips'], true)) {
            $tab = 'profile';
        }

        $staff = $workspace->staffUsers();
        $assignedRoleIds = $employee->roles->pluck('id');

        return view('adminmodule::admin.employee.profile', [
            'employee' => $employee,
            'roles' => $this->role->query()
                ->where(function ($query) use ($assignedRoleIds) {
                    $query->where('is_active', 1);
                    if ($assignedRoleIds->isNotEmpty()) {
                        $query->orWhereIn('id', $assignedRoleIds);
                    }
                })
                ->orderBy('role_name')
                ->get(),
            'workspace' => $workspace,
            'staff' => $staff,
            'departments' => PeopleDepartment::query()->orderBy('name')->get(),
            'leaveTypes' => PeopleLeaveType::query()->orderBy('sort')->orderBy('name')->get(),
            'person' => [
                'user' => $employee,
                'profile' => PeopleProfile::query()->with(['manager', 'leavePolicy'])->where('user_id', $employee->id)->first(),
                'assignments' => PeopleLeaveAssignment::query()->with('policy.leaveType')->where('user_id', $employee->id)->get(),
                'tab' => $tab,
                'documents' => PeopleDocument::query()->where('user_id', $employee->id)->orderBy('title')->get(),
                'balance' => PeopleLeaveBalance::query()->where('user_id', $employee->id)->where('year', (int) now()->year)->first(),
                'leaveHistory' => $workspace->leaveHistory($employee->id),
                'leaveRequests' => PeopleLeaveRequest::query()->where('user_id', $employee->id)->latest()->get(),
                'structures' => PeopleSalaryStructure::query()->where('user_id', $employee->id)->orderByDesc('effective_from')->get(),
                'adjustments' => PeoplePayAdjustment::query()->where('user_id', $employee->id)->latest()->limit(12)->get(),
                'payslips' => PeoplePayslip::query()->where('user_id', $employee->id)->orderByDesc('period')->get(),
            ],
        ]);
    }

    public function updateProfile(Request $request, string $id): RedirectResponse
    {
        $this->authorize('employee_update');
        $employee = $this->employeeRecord($id);
        $section = (string) $request->input('section');
        if (! in_array($section, ['basic', 'operation', 'password'], true)) {
            $section = 'basic';
        }

        if ($section === 'password') {
            $data = $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'password.required' => 'Enter a new password.',
                'password.min' => 'Use at least 8 characters.',
                'password.confirmed' => 'The two passwords do not match.',
            ]);

            $employee->password = bcrypt($data['password']);
            $employee->remember_token = Str::random(60);
            $employee->save();
            if (Schema::hasTable('oauth_access_tokens')) {
                $employee->tokens()->update(['revoked' => true]);
            }

            Toastr::success('Password updated. The employee signs in with this password next time.');

            return redirect()->route('admin.employee.profile', ['id' => $employee->id, 'tab' => 'profile']);
        }

        app(PeopleWorkspace::class)->ensureStaffFile($employee);
        $profile = PeopleProfile::query()->where('user_id', $employee->id)->firstOrFail();

        if ($section === 'basic') {
            $request->merge([
                'phone' => $request->input('phone') ?: null,
                'date_of_birth' => $request->input('date_of_birth') ?: null,
            ]);
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($employee->id)],
                'phone' => ['nullable', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:8', Rule::unique('users', 'phone')->ignore($employee->id)],
                'employment_status' => ['required', Rule::in(['active', 'notice', 'exited'])],
                'date_of_birth' => ['nullable', 'date'],
                'emergency_contact' => ['nullable', 'string', 'max:120'],
                'address' => ['nullable', 'string', 'max:500'],
            ]);

            DB::transaction(function () use ($employee, $profile, $data) {
                $employee->first_name = $data['first_name'];
                $employee->last_name = $data['last_name'];
                $employee->email = $data['email'];
                $employee->phone = $data['phone'];
                $employee->save();
                $profile->employment_status = $data['employment_status'];
                $profile->date_of_birth = $data['date_of_birth'] ?? null;
                $profile->emergency_contact = $data['emergency_contact'] ?? null;
                $profile->address = $data['address'] ?? null;
                $profile->save();
            });
            Toastr::success('Basic details saved.');
        } else {
            $request->merge([
                'department' => $request->input('department') ?: null,
                'manager_id' => $request->input('manager_id') ?: null,
                'joined_on' => $request->input('joined_on') ?: null,
            ]);
            $data = $request->validate([
                'role_ids' => ['required', 'array', 'min:1'],
                'role_ids.*' => ['uuid', Rule::exists('roles', 'id')],
                'department' => ['nullable', 'string', 'max:120'],
                'work_location' => ['nullable', 'string', 'max:120'],
                'manager_id' => ['nullable', 'uuid'],
                'joined_on' => ['nullable', 'date'],
                'employment_stage' => ['required', Rule::in(['probation', 'permanent'])],
                'work_schedule' => ['required', Rule::in(['full_time', 'part_time'])],
                'override_min_hours' => ['nullable', 'boolean'],
                'min_hours_override' => ['nullable', 'required_if:override_min_hours,1', 'numeric', 'min:0', 'max:24'],
                'override_week_off' => ['nullable', 'boolean'],
                'week_off_override' => ['exclude_unless:override_week_off,1', 'nullable', 'array', 'max:6'],
                'week_off_override.*' => ['exclude_unless:override_week_off,1', Rule::in(PeopleWorkspace::DAY_KEYS)],
            ], [
                'week_off_override.max' => 'Keep at least one working day in the week.',
            ]);

            if (! empty($data['manager_id']) && $data['manager_id'] === $employee->id) {
                Toastr::error('A person cannot be their own manager.');

                return back()->withInput();
            }

            $currentDepartment = (string) $profile->department;
            $currentStage = $profile->employment_stage ?: 'permanent';
            if (! empty($data['department']) && $data['department'] !== $currentDepartment && ! PeopleDepartment::query()->where('name', $data['department'])->exists()) {
                Toastr::error('Choose a department from the list.');

                return back()->withInput();
            }

            DB::transaction(function () use ($employee, $profile, $data, $request) {
                $this->assignRoles($employee, $data['role_ids']);
                $profile->department = $data['department'] ?? '';
                $profile->work_location = $data['work_location'] ?? '';
                $profile->manager_id = $data['manager_id'] ?: null;
                $profile->joined_on = $data['joined_on'] ?? null;
                $profile->employment_stage = $data['employment_stage'];
                $profile->work_schedule = $data['work_schedule'];
                $profile->min_hours_override = $request->boolean('override_min_hours')
                    ? round((float) $data['min_hours_override'], 1)
                    : null;
                $profile->week_off_override = $request->boolean('override_week_off')
                    ? array_values(array_intersect(PeopleWorkspace::DAY_KEYS, $data['week_off_override'] ?? []))
                    : null;
                $profile->save();
            });

            $nextDepartment = (string) ($data['department'] ?? '');
            $fresh = $profile->fresh();
            if ($currentDepartment !== $nextDepartment) {
                app(PeopleLeaveAccrual::class)->syncPersonDepartment($fresh, $currentDepartment, $nextDepartment);
            }
            if ($currentStage !== $data['employment_stage']) {
                app(PeopleLeaveAccrual::class)->syncPersonStage($fresh, $currentStage, $data['employment_stage']);
            }
            Toastr::success('Operation details saved.');
        }

        return redirect()->route('admin.employee.profile', ['id' => $employee->id, 'tab' => 'profile']);
    }

    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function edit(string $id): RedirectResponse
    {
        $this->authorize('employee_view');

        return redirect()->route('admin.employee.profile', $id);
    }

    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function setPermission(string $id): Application|Factory|View
    {
        $this->authorize('employee_update');
        $roleAccess = $this->employeeRoleAccess->where('employee_id', $id)->get();
        $employee = $this->employee->with(['roles', 'addresses'])->where(['id' => $id, 'user_type' => 'admin-employee'])->first();
        $roles = $this->role->where(['is_active' => 1])->get();
        return view('adminmodule::admin.employee.set-permission', compact('roleAccess', 'roles', 'employee'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return Redirector|Application|RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, string $id): Application|RedirectResponse|Redirector
    {
        $this->authorize('employee_update');

        $check = $this->validateUploadedFile($request, ['profile_image']);
        if ($check !== true) {
            return $check;
        }

        $employee = $this->employee->where(['id' => $id, 'user_type' => 'admin-employee'])->first();

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'password' => !is_null($request->password) ? 'string|min:8' : '',
            'confirm_password' => !is_null($request->password) ? 'required|same:password' : '',
            'profile_image' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'identity_type' => [
                Rule::requiredIf(fn () => $request->hasFile('identity_images')),
                'nullable',
                'in:passport,driving_license,nid,trade_license',
            ],
            'identity_number' => 'nullable|string|max:191',
            'identity_images' => 'nullable|array',
            'identity_images.*' => 'image|max:'. uploadMaxFileSizeInKB('image') .'|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'role_id' => 'required|uuid',
            'address' => 'required|string'
        ]);

        if (!$request->modules){
            Toastr::error(translate('Please select at latest one module'));
            return back();
        }

        if (User::where('email', $request['email'])->where('id', '!=', $employee->id)->exists()) {
            Toastr::error(translate('Email already taken'));
            return back();
        }
        if (User::where('phone', $request['phone'])->where('id', '!=', $employee->id)->exists()) {
            Toastr::error(translate('Phone already taken'));
            return back();
        }

        DB::transaction(function () use ($id, $employee, $request) {
            $employee->first_name = $request->first_name;
            $employee->last_name = $request->last_name;
            $employee->email = $request->email;
            $employee->phone = $request->phone;
            if ($request->has('profile_image')) {
                $employee->profile_image = file_uploader('employee/profile/', APPLICATION_IMAGE_FORMAT, $request->file('profile_image'), $employee->profile_image);;
            }
            if ($request->filled('identity_type')) {
                $employee->identification_type = $request->identity_type;
                $employee->identification_number = $request->identity_number ?: null;
            } else {
                foreach ($employee->identification_image ?? [] as $item) {
                    $name = is_array($item) ? ($item['image'] ?? null) : $item;
                    if ($name) {
                        file_remover('employee/identity/', $name);
                    }
                }
                $employee->identification_type = 'nid';
                $employee->identification_number = null;
                $employee->identification_image = [];
            }
            if ($request->hasFile('identity_images')) {
                $identityImages = [];
                foreach ($request->file('identity_images') as $image) {
                    $imageName = file_uploader('employee/identity/', APPLICATION_IMAGE_FORMAT, $image);
                    $identityImages[] = ['image'=>$imageName, 'storage'=> getDisk()];
                }
                $employee->identification_image = $identityImages;
            }
            if (!is_null($request->password)) {
                $employee->password = bcrypt($request->password);
            }
            $employee->user_type = 'admin-employee';
            $employee->password = !is_null($request->password) ? bcrypt($request->password) : $employee->password;
            $employee->save();

            $employee->roles()->sync([$request['role_id']]);

            $address = $this->address->where('user_id', $id)->first();
            $address->address = $request->address;
            $address->save();

            $employeeRoleSection = $this->employeeRoleSection->where('employee_id', $id)->first();
            $employeeRoleSection->employee_id = $id;
            $employeeRoleSection->role_id = $request->role_id;
            $employeeRoleSection->save();

            $employeeRoleAccess = $this->employeeRoleAccess->where('employee_id', $id)->first();
            if ($employeeRoleAccess) {
                $existingRoleId = $employeeRoleAccess->role_id;

                if ($existingRoleId !== $request->role_id) {
                    $this->employeeRoleAccess
                        ->where('employee_id', $id)
                        ->where('role_id', $existingRoleId)
                        ->delete();
                }
            }

            $requestedSections = [];

            foreach ($request->modules as $section => $values) {
                if (isset($values['access_role'])) {
                    foreach ($values['access_role'] as $key => $value) {

                        $requestedSections[] = $key; // collect for delete later

                        $accessData = [
                            'employee_id' => $employee->id,
                            'role_id' => $request->role_id,
                            'section_name' => $key,
                            'can_add' => isset($values['can_add']) ? 1 : 0,
                            'can_update' => isset($values['can_update']) ? 1 : 0,
                            'can_delete' => isset($values['can_delete']) ? 1 : 0,
                            'can_export' => isset($values['can_export']) ? 1 : 0,
                            'can_manage_status' => isset($values['can_manage_status']) ? 1 : 0,
                            'can_assign_serviceman' => isset($values['can_assign_serviceman']) ? 1 : 0,
                            'can_give_feedback' => isset($values['can_give_feedback']) ? 1 : 0,
                            'can_take_backup' => isset($values['can_take_backup']) ? 1 : 0,
                            'can_change_status' => isset($values['can_change_status']) ? 1 : 0,
                        ];

                        $existingAccess = $this->employeeRoleAccess->where('employee_id', $employee->id)
                            ->where('role_id', $request->role_id)
                            ->where('section_name', $key)
                            ->first();
                        $roleAccess = $this->roleAccess->where('role_id', $request->role_id)->with('role')->get();
                        foreach ($roleAccess as $access){
                            if ($access->can_add == 0 && $accessData['section_name'] == $access->section_name && $accessData['can_add'] == 1) {
                                $message = "Permission 'add' is not allowed for role {$access->role->role_name} in section {$access->section_name}.";
                                $this->alertMessage($message);
                            }
                            if ($access->can_update == 0 && $accessData['section_name'] == $access->section_name && $accessData['can_update'] == 1) {
                                $message = "Permission 'update' is not allowed for role {$access->role->role_name} in section {$access->section_name}.";
                                $this->alertMessage($message);
                            }
                            if ($access->can_delete == 0 && $accessData['section_name'] == $access->section_name && $accessData['can_delete'] == 1) {
                                $message = "Permission 'delete' is not allowed for role {$access->role->role_name} in section {$access->section_name}.";
                                $this->alertMessage($message);
                            }
                            if ($access->can_export == 0 && $accessData['section_name'] == $access->section_name && $accessData['can_export'] == 1) {
                                $message = "Permission 'export' is not allowed for role {$access->role->role_name} in section {$access->section_name}.";
                                $this->alertMessage($message);
                            }
                            if ($access->can_manage_status == 0 && $accessData['section_name'] == $access->section_name && $accessData['can_manage_status'] == 1) {
                                $message = "Permission 'status' is not allowed for role {$access->role->role_name} in section {$access->section_name}.";
                                $this->alertMessage($message);
                            }
                            if ($access->can_assign_serviceman == 0 && $accessData['section_name'] == $access->section_name && $accessData['can_assign_serviceman'] == 1) {
                                $message = "Permission 'assign serviceman' is not allowed for role {$access->role->role_name} in section {$access->section_name}.";
                                $this->alertMessage($message);
                            }
                            if ($access->can_give_feedback == 0 && $accessData['section_name'] == $access->section_name && $accessData['can_give_feedback'] == 1) {
                                $message = "Permission 'give feedback' is not allowed for role {$access->role->role_name} in section {$access->section_name}.";
                                $this->alertMessage($message);
                            }
                            if ($access->can_take_backup == 0 && $accessData['section_name'] == $access->section_name && $accessData['can_take_backup'] == 1) {
                                $message = "Permission 'take backup' is not allowed for role {$access->role->role_name} in section {$access->section_name}.";
                                $this->alertMessage($message);
                            }
                        }
                        if ($existingAccess && empty($message)) {
                            $existingAccess->update($accessData);
                        } else {
                            $this->employeeRoleAccess->create($accessData);
                        }
                    }
                }
            }

            // --- DELETE OLD SECTIONS NOT IN REQUEST ---
            $this->employeeRoleAccess
                ->where('employee_id', $employee->id)
                ->where('role_id', $request->role_id)
                ->whereNotIn('section_name', $requestedSections)
                ->delete();

            if(empty($message)) {
                Toastr::success(translate(DEFAULT_UPDATE_200['message']));
            }
        });
        return redirect('/admin/employee/list');
    }
    function alertMessage ($message){
        Toastr::error(translate($message));
        return redirect('/admin/employee/list');
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $this->authorize('employee_delete');
        $user = $this->employee->where('id', $id)->first();
        if (isset($user)) {
            file_remover('employee/profile_image/', $user->profile_image);
            foreach ($user->identification_image as $image_name) {
                file_remover('employee/identity/', $image_name);
            }
            $user->delete();

            $this->employeeRoleAccess->where('employee_id', $id)->delete();
            $this->employeeRoleSection->where('employee_id', $id)->delete();

            Toastr::success(translate(DEFAULT_DELETE_200['message']));
            return back();
        }

        Toastr::success(translate(DEFAULT_204['message']));
        return back();
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function statusUpdate(Request $request, $id): JsonResponse
    {
        $this->authorize('employee_manage_status');
        $user = $this->employee->where('id', $id)->first();
        $this->employee->where('id', $id)->update(['is_active' => !$user->is_active]);
        $user->refresh();

        $staffGroupService = app(\Modules\ChattingModule\Services\StaffGroupChannelService::class);
        if ($user->is_active) {
            $staffGroupService->ensureGroupForUser($user);
        } else {
            $staffGroupService->removeMember($user->id);
        }

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function remove_image(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|uuid',
            'image_name' => 'required|string',
            'image_type' => 'required|in:logo,identity_image'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $employee = $this->employee->where('id', $request['employee_id'])->first();
        if ($request['image_type'] == 'identity_image') {
            file_remover('employee/identity/', $request['image_name']);
            $employee->identification_image = array_diff($employee->identification_image, $request['image_name']);
            $employee->save();
        }

        return response()->json(response_formatter(DEFAULT_204), 200);
    }


    /**
     * @param Request $request
     * @return string|StreamedResponse
     * @throws AuthorizationException
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function download(Request $request): string|StreamedResponse
    {
        $this->authorize('employee_export');
        $search = $this->employeeListSearchTerm($request);
        $items = $this->employee->OfType(['admin-employee'])->with(['roles', 'addresses'])
            ->when($search !== '', function ($query) use ($search) {
                $this->applyEmployeeListSearch($query, $search);
            })
            ->latest()->get();

        return (new FastExcel($items))->download(time() . '-file.xlsx');
    }

    public function ajaxRoleAccess(Request $request)
    {
        $roleAccess = $this->roleAccess->where('role_id', $request->role_id)->get();
        $view = view('adminmodule::layouts.partials.employee-role-access', compact('roleAccess'))->render();
        return response()->json(['html' => $view], 200);
    }

    public function ajaxEmployeeRoleAccess(Request $request)
    {
        $employeeRoleSection = $this->employeeRoleSection->where('employee_id', $request->id)->where('role_id', $request->role_id)->first();
        if ($employeeRoleSection) {
            $roleAccess = $this->employeeRoleAccess->where('employee_id', $request->id)->where('role_id', $request->role_id)->get();
            $view = view('adminmodule::layouts.partials.employee-update-access', compact('roleAccess'))->render();
            return response()->json(['html' => $view], 200);
        }

        $roleAccess = $this->roleAccess->where('role_id', $request->role_id)->get();
        $view = view('adminmodule::layouts.partials.employee-role-access', compact('roleAccess'))->render();
        return response()->json(['html' => $view], 200);
    }

    private function employeeListSearchTerm(Request $request): string
    {
        $search = $request->input('search', '');
        if (! is_string($search)) {
            return '';
        }

        return mb_substr(trim($search), 0, 120);
    }

    private function applyEmployeeListSearch($query, string $search): void
    {
        $tokens = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $query->where(function ($query) use ($tokens) {
            foreach ($tokens as $token) {
                $like = '%'.addcslashes($token, '%_\\').'%';
                $query->where(function ($query) use ($token, $like) {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhereRaw("CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) LIKE ?", [$like])
                        ->orWhere('email', 'like', $like);

                    $codes = $this->employeeCodeCandidates($token);
                    if ($codes !== []) {
                        $query->orWhereHas('peopleProfile', function ($profile) use ($codes) {
                            $profile->whereIn('employee_code', $codes);
                        });
                    }
                });
            }
        });
    }

    /**
     * Stored codes are PK006 or PK-0006. Both match the ID shown in the list.
     *
     * @return array<int, string>
     */
    private function employeeCodeCandidates(string $token): array
    {
        $compact = strtoupper(preg_replace('/[^A-Z0-9]/', '', $token) ?? '');
        if (! preg_match('/^(?:PK)?(\d+)$/', $compact, $matches)) {
            return [];
        }

        $digits = $matches[1];
        $hasPrefix = str_starts_with($compact, 'PK');
        $numbers = [(int) $digits];

        if ($hasPrefix && strlen($digits) < 3) {
            $numbers = range(
                (int) str_pad($digits, 3, '0', STR_PAD_RIGHT),
                (int) str_pad($digits, 3, '9', STR_PAD_RIGHT)
            );
        }

        $codes = [];
        foreach ($numbers as $number) {
            if ($number < 1) {
                continue;
            }

            $plain = (string) $number;
            $codes[] = 'PK'.$plain;
            $codes[] = 'PK-'.$plain;
            foreach ([3, 4] as $width) {
                $padded = str_pad($plain, $width, '0', STR_PAD_LEFT);
                $codes[] = 'PK'.$padded;
                $codes[] = 'PK-'.$padded;
            }
        }

        return array_values(array_unique($codes));
    }

    private function employeeRecord(string $id): User
    {
        $employee = $this->employee->with('roles')->where('id', $id)->where('user_type', 'admin-employee')->first();
        abort_unless($employee, 404);

        return $employee;
    }

    /**
     * @param  array<int, string>  $roleIds
     */
    private function assignRoles(User $employee, array $roleIds): void
    {
        $roleIds = array_values(array_unique($roleIds));
        $employee->roles()->sync($roleIds);

        EmployeeRoleAccess::query()->where('employee_id', $employee->id)->delete();

        $columns = [
            'can_view',
            'can_add',
            'can_update',
            'can_delete',
            'can_export',
            'can_manage_status',
            'can_approve_or_deny',
            'can_assign_serviceman',
            'can_give_feedback',
            'can_take_backup',
            'can_change_status',
        ];

        foreach ($this->roleAccess->whereIn('role_id', $roleIds)->get() as $access) {
            $row = [
                'employee_id' => $employee->id,
                'role_id' => $access->role_id,
                'section_name' => $access->section_name,
            ];
            foreach ($columns as $column) {
                $row[$column] = (int) ($access->{$column} ?? 0);
            }
            EmployeeRoleAccess::query()->create($row);
        }
    }

}
