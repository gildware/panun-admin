@php
    $user = $person['user'] ?? null;
    $profile = $person['profile'] ?? null;
    $tab = $person['tab'] ?? 'profile';
    $tabs = [
        'profile' => 'Profile',
        'leaves' => 'Leaves',
        'salary' => 'Salary',
        'payslips' => 'Payslips',
    ];
    $latestStructure = ($person['structures'] ?? collect())->first();
    $documentReturn = old('form_context') === 'document';
    $bankErrors = old('section') === 'bank' && $errors->hasAny(['bank_name', 'bank_account', 'bank_ifsc', 'pan', 'aadhaar', 'uan']);
    $basicErrors = old('section') !== 'password' && $errors->hasAny(['first_name', 'last_name', 'email', 'phone', 'employment_status', 'date_of_birth', 'emergency_contact', 'address']);
    $passwordErrors = old('section') === 'password' && $errors->has('password');
    $operationErrors = $errors->hasAny(['department', 'work_location', 'billing_type', 'manager_id', 'joined_on', 'role_ids', 'employment_stage', 'work_schedule', 'min_hours_override', 'week_off_override']) || $errors->has('role_ids.*') || $errors->has('week_off_override.*');
@endphp
@if(! $user || ! $profile)
    <div class="people-ws-head"><div><h1>People file</h1><p>Choose a person from the People tab.</p></div></div>
@else
    <div class="people-file people-file--stack">
        <div class="people-file-toolbar">
            <div class="people-file-lead">
                <a class="people-file-back" href="{{ route('admin.employee.index') }}">
                    <span class="material-icons" aria-hidden="true">arrow_back</span>
                    Back to employee list
                </a>
                <span class="people-file-name">{{ $workspace->displayName($user) }}</span>
            </div>
            <nav class="people-ws-tabs" aria-label="Employee file">
                @foreach($tabs as $key => $label)
                    <a class="{{ $tab === $key ? 'is-on' : '' }}" href="{{ route('admin.employee.profile', ['id' => $user->id, 'tab' => $key]) }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        <div class="people-file-main">
        @if($tab === 'profile')
    @php
        $managerName = $profile->manager ? $workspace->displayName($profile->manager) : 'Not set';
        $roleOptions = isset($roles) ? $roles : $user->roles;
        $selectedRoleIds = $operationErrors
            ? (array) old('role_ids', $user->roles->pluck('id')->all())
            : $user->roles->pluck('id')->all();
        $departmentValue = old('department', $profile->department);
        $scheduleValue = $operationErrors ? old('work_schedule', $profile->work_schedule ?: 'full_time') : ($profile->work_schedule ?: 'full_time');
        $overrideOn = $operationErrors ? (bool) old('override_min_hours') : $profile->min_hours_override !== null;
        $overrideHours = $operationErrors ? old('min_hours_override') : $profile->min_hours_override;
        $companyWeekOff = $workspace->weekOffDays();
        $savedWeekOff = is_array($profile->week_off_override) ? $profile->week_off_override : null;
        $weekOffOverrideOn = $operationErrors ? (bool) old('override_week_off') : $savedWeekOff !== null;
        if ($operationErrors) {
            $selectedWeekOff = (array) old('week_off_override', []);
        } elseif ($savedWeekOff !== null) {
            $selectedWeekOff = $savedWeekOff;
        } else {
            $selectedWeekOff = $companyWeekOff;
        }
    @endphp
        @php
            $savedStatus = $profile->employment_status ?: 'active';
            $statusValue = old('employment_status', $savedStatus);
        @endphp
        <div class="ep-board">
            <article class="ep-card">
                <header class="ep-card-head">
                    <h3>Basic details</h3>
                    <button class="ep-edit" type="button" id="person-details-edit" @if($basicErrors) hidden @endif><span class="material-icons" aria-hidden="true">edit</span> Edit</button>
                </header>
                <div id="person-basic-view" @if($basicErrors) hidden @endif>
                <div class="ep-person">
                    <img class="ep-photo avatar-img" src="{{ admin_nav_image_src($user->profile_image_full_path, 'profile') }}" alt="{{ $workspace->displayName($user) }}">
                    <div>
                        <h2>{{ $workspace->displayName($user) }}</h2>
                        <span class="ep-status is-{{ $savedStatus }}">{{ $workspace->statusLabel($savedStatus) }}</span>
                    </div>
                </div>
                <dl class="ep-facts">
                    <div class="ep-fact">
                        <dt>Email</dt>
                        <dd class="{{ $user->email ? '' : 'is-empty' }}">{{ $user->email ?: '—' }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Phone</dt>
                        <dd class="{{ $user->phone ? '' : 'is-empty' }}">{{ $user->phone ?: '—' }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Date of birth</dt>
                        <dd class="{{ $profile->date_of_birth ? '' : 'is-empty' }}">{{ $profile->date_of_birth ? $profile->date_of_birth->format('j M Y') : '—' }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Emergency contact</dt>
                        <dd class="{{ $profile->emergency_contact ? '' : 'is-empty' }}">{{ $profile->emergency_contact ?: '—' }}</dd>
                    </div>
                    <div class="ep-fact is-block">
                        <dt>Address</dt>
                        <dd class="{{ $profile->address ? '' : 'is-empty' }}">{{ $profile->address ?: '—' }}</dd>
                    </div>
                </dl>
                @can('employee_update')
                    <div class="ep-password-row">
                        <div>
                            <strong>Password</strong>
                            <p>Used with this email to sign in.</p>
                        </div>
                        <button class="ep-edit" type="button" data-bs-toggle="modal" data-bs-target="#passwordChangeModal">
                            <span class="material-icons" aria-hidden="true">lock</span> Change
                        </button>
                    </div>
                @endcan
                </div>
                <form class="ep-form" id="person-basic-form" method="post" action="{{ route('admin.employee.profile.update', $user) }}?tab=profile" @if(! $basicErrors) hidden @endif>
                    @csrf
                    @method('put')
                    <input type="hidden" name="section" value="basic">
                    <div class="ep-person">
                        <img class="ep-photo avatar-img" src="{{ admin_nav_image_src($user->profile_image_full_path, 'profile') }}" alt="">
                        <h2>{{ $workspace->displayName($user) }}</h2>
                    </div>
                    <div class="ep-form-grid">
                        <div class="field">
                            <label for="first_name">First name</label>
                            <input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                            @error('first_name')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="last_name">Last name</label>
                            <input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                            @error('last_name')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field span-2">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                            @error('phone')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="employment_status">Status</label>
                            <select id="employment_status" name="employment_status">
                                @foreach(['active' => 'Active', 'notice' => 'On notice', 'exited' => 'Exited'] as $value => $label)
                                    <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('employment_status')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="date_of_birth">Date of birth</label>
                            <input id="date_of_birth" class="people-date-field" name="date_of_birth" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ old('date_of_birth', optional($profile->date_of_birth)->toDateString()) }}" readonly>
                            @error('date_of_birth')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="emergency_contact">Emergency contact</label>
                            <input id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact', $profile->emergency_contact) }}">
                            @error('emergency_contact')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field span-2">
                            <label for="address">Address</label>
                            <input id="address" name="address" value="{{ old('address', $profile->address) }}">
                            @error('address')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="ep-form-actions">
                        <button class="btn-pw" type="button" data-cancel>Cancel</button>
                        <button class="btn-pw primary" type="submit">Save</button>
                    </div>
                </form>
            </article>
            <article class="ep-card">
                <header class="ep-card-head">
                    <h3>Operation details</h3>
                    <button class="ep-edit" type="button" id="person-file-edit" @if($operationErrors) hidden @endif><span class="material-icons" aria-hidden="true">edit</span> Edit</button>
                </header>
                <p class="ep-id"><span>Employee ID</span>{{ \Modules\AdminModule\Services\PeopleWorkspace::formatCode($profile->employee_code) }}</p>
                <div id="person-operation-view" @if($operationErrors) hidden @endif>
                <div class="ep-roles">
                    @forelse($user->roles as $role)
                        <span class="ep-pill">{{ $role->role_name }}</span>
                    @empty
                        <span class="ep-muted">No role</span>
                    @endforelse
                </div>
                <dl class="ep-facts">
                    <div class="ep-fact">
                        <dt>Department</dt>
                        <dd class="{{ $profile->department ? '' : 'is-empty' }}">{{ $profile->department ?: '—' }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Manager</dt>
                        <dd class="{{ $profile->manager ? '' : 'is-empty' }}">{{ $profile->manager ? $managerName : '—' }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Work location</dt>
                        <dd class="{{ $profile->work_location ? '' : 'is-empty' }}">{{ \Modules\AdminModule\Entities\PeopleProfile::workLocationLabel($profile->work_location) ?: '—' }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Date of joining</dt>
                        <dd class="{{ $profile->joined_on ? '' : 'is-empty' }}">{{ $profile->joined_on ? $profile->joined_on->format('j M Y') : '—' }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Employee type</dt>
                        <dd>{{ ($profile->employment_stage ?: 'permanent') === 'probation' ? 'Probation' : 'Permanent' }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Hours</dt>
                        <dd>{{ ($profile->work_schedule ?: 'full_time') === 'part_time' ? 'Part time' : 'Full time' }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Billing</dt>
                        <dd>{{ \Modules\AdminModule\Entities\PeopleProfile::billingTypeLabel($profile->billing_type ?: 'billable') }}</dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Minimum hours</dt>
                        <dd>
                            {{ \Modules\AdminModule\Services\PeopleWorkspace::hoursText($workspace->requiredDayHours($user)) }} a day
                            @if($profile->min_hours_override !== null)
                                · set for this employee
                            @else
                                · {{ ($profile->work_schedule ?: 'full_time') === 'part_time' ? 'part time' : 'full time' }}
                            @endif
                        </dd>
                    </div>
                    <div class="ep-fact">
                        <dt>Week off</dt>
                        <dd>
                            @if(is_array($profile->week_off_override))
                                {{ \Modules\AdminModule\Services\PeopleWorkspace::weekOffText($profile->week_off_override) }}
                            @else
                                Uses the company week off
                            @endif
                        </dd>
                    </div>
                </dl>
                </div>
                <form class="ep-form" id="person-operation-form" method="post" action="{{ route('admin.employee.profile.update', $user) }}?tab=profile" @if(! $operationErrors) hidden @endif>
                    @csrf
                    @method('put')
                    <input type="hidden" name="section" value="operation">
                    <div class="ep-form-grid">
                        <div class="field span-2">
                            <label>Roles</label>
                            <div class="ep-checks">
                                @foreach($roleOptions as $role)
                                    <label class="ep-check">
                                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, $selectedRoleIds, true))>
                                        <span>{{ $role->role_name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('role_ids')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="">Not set</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->name }}" @selected($departmentValue === $department->name)>{{ $department->name }}</option>
                                @endforeach
                                @if($profile->department && ! $departments->contains('name', $profile->department))
                                    <option value="{{ $profile->department }}" @selected($departmentValue === $profile->department)>{{ $profile->department }}</option>
                                @endif
                            </select>
                            @error('department')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="manager_id">Manager</label>
                            <select id="manager_id" name="manager_id">
                                <option value="">Not set</option>
                                @foreach($staff as $candidate)
                                    @if($candidate->id !== $user->id)
                                        <option value="{{ $candidate->id }}" @selected(old('manager_id', $profile->manager_id) === $candidate->id)>{{ $workspace->displayName($candidate) }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('manager_id')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="work_location">Work location</label>
                            @php $locationValue = (string) old('work_location', $profile->work_location); @endphp
                            <select id="work_location" name="work_location">
                                <option value="">Not set</option>
                                @foreach(\Modules\AdminModule\Entities\PeopleProfile::WORK_LOCATIONS as $value => $label)
                                    <option value="{{ $value }}" @selected($locationValue === $value)>{{ $label }}</option>
                                @endforeach
                                @if($locationValue !== '' && ! isset(\Modules\AdminModule\Entities\PeopleProfile::WORK_LOCATIONS[$locationValue]))
                                    <option value="{{ $locationValue }}" selected>{{ $locationValue }}</option>
                                @endif
                            </select>
                            @error('work_location')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="employment_stage">Employee type</label>
                            <select id="employment_stage" name="employment_stage" required>
                                <option value="permanent" @selected(old('employment_stage', $profile->employment_stage ?: 'permanent') === 'permanent')>Permanent</option>
                                <option value="probation" @selected(old('employment_stage', $profile->employment_stage ?: 'permanent') === 'probation')>Probation</option>
                            </select>
                            @error('employment_stage')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="work_schedule">Full time or part time</label>
                            <select id="work_schedule" name="work_schedule" required>
                                <option value="full_time" @selected($scheduleValue === 'full_time')>Full time</option>
                                <option value="part_time" @selected($scheduleValue === 'part_time')>Part time</option>
                            </select>
                            @error('work_schedule')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="billing_type">Billing</label>
                            <select id="billing_type" name="billing_type" required>
                                @foreach(\Modules\AdminModule\Entities\PeopleProfile::BILLING_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('billing_type', $profile->billing_type ?: 'billable') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('billing_type')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field span-2">
                            <label class="ep-check" for="override_min_hours">
                                <input id="override_min_hours" type="checkbox" name="override_min_hours" value="1" @checked($overrideOn)>
                                <span>Override minimum hours for this employee</span>
                            </label>
                            <div class="field" id="min-hours-override-field" @if(! $overrideOn) hidden @endif>
                                <label for="min_hours_override">Minimum hours in a day</label>
                                <input id="min_hours_override" name="min_hours_override" type="number" min="0" max="24" step="0.5" inputmode="decimal" value="{{ $overrideHours }}">
                                @error('min_hours_override')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <p class="people-ws-note">Leave this off to use the full-time or part-time minimum from Configuration.</p>
                        </div>
                        <div class="field span-2">
                            <label class="ep-check" for="override_week_off">
                                <input id="override_week_off" type="checkbox" name="override_week_off" value="1" @checked($weekOffOverrideOn)>
                                <span>Override week off for this employee</span>
                            </label>
                            <div class="field" id="week-off-override-field" @if(! $weekOffOverrideOn) hidden @endif>
                                <div class="people-ws-days">
                                    @foreach(\Modules\AdminModule\Services\PeopleWorkspace::DAY_LABELS as $key => $label)
                                        <label>
                                            <input type="checkbox" name="week_off_override[]" value="{{ $key }}" @checked(in_array($key, $selectedWeekOff, true))>
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('week_off_override')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <p class="people-ws-note">Leave this off to use the week off from Configuration. Keep at least one working day.</p>
                        </div>
                        <div class="field">
                            <label for="joined_on">Date of joining</label>
                            <input id="joined_on" class="people-date-field" name="joined_on" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ old('joined_on', optional($profile->joined_on)->toDateString()) }}" readonly>
                            @error('joined_on')<p class="ep-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="ep-form-actions">
                        <button class="btn-pw" type="button" data-cancel>Cancel</button>
                        <button class="btn-pw primary" type="submit">Save</button>
                    </div>
                </form>
            </article>
            <article class="ep-card ep-bank">
            <header class="ep-card-head">
                <h3>Bank details</h3>
                <button class="ep-edit" type="button" id="person-bank-edit" @if($bankErrors) hidden @endif><span class="material-icons" aria-hidden="true">edit</span> Edit</button>
            </header>
            <dl class="ep-facts" id="person-bank-view" @if($bankErrors) hidden @endif>
                <div class="ep-fact">
                    <dt>Bank</dt>
                    <dd class="{{ $profile->bank_name ? '' : 'is-empty' }}">{{ $profile->bank_name ?: '—' }}</dd>
                </div>
                <div class="ep-fact">
                    <dt>Account</dt>
                    <dd class="{{ $profile->bank_account ? '' : 'is-empty' }}">{{ $profile->bank_account ?: '—' }}</dd>
                </div>
                <div class="ep-fact">
                    <dt>IFSC</dt>
                    <dd class="{{ $profile->bank_ifsc ? '' : 'is-empty' }}">{{ $profile->bank_ifsc ?: '—' }}</dd>
                </div>
                <div class="ep-fact">
                    <dt>PAN</dt>
                    <dd class="{{ $profile->pan ? '' : 'is-empty' }}">{{ $profile->pan ?: '—' }}</dd>
                </div>
                <div class="ep-fact">
                    <dt>Aadhaar</dt>
                    <dd class="{{ $profile->aadhaar ? '' : 'is-empty' }}">{{ $profile->aadhaar ?: '—' }}</dd>
                </div>
                <div class="ep-fact">
                    <dt>UAN</dt>
                    <dd class="{{ $profile->uan ? '' : 'is-empty' }}">{{ $profile->uan ?: '—' }}</dd>
                </div>
            </dl>
            <form class="ep-form" id="person-bank-form" method="post" action="{{ route('admin.hr.person') }}" @if(! $bankErrors) hidden @endif>
                @csrf
                <input type="hidden" name="section" value="bank">
                <input type="hidden" name="user_id" value="{{ $user->id }}">
                <div class="ep-form-grid">
                    <div class="field">
                        <label for="bank_name">Bank</label>
                        <input id="bank_name" name="bank_name" value="{{ old('bank_name', $profile->bank_name) }}">
                        @error('bank_name')<p class="ep-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="bank_account">Account</label>
                        <input id="bank_account" name="bank_account" value="{{ old('bank_account', $profile->bank_account) }}">
                        @error('bank_account')<p class="ep-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="bank_ifsc">IFSC</label>
                        <input id="bank_ifsc" name="bank_ifsc" value="{{ old('bank_ifsc', $profile->bank_ifsc) }}">
                        @error('bank_ifsc')<p class="ep-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="pan">PAN</label>
                        <input id="pan" name="pan" value="{{ old('pan', $profile->pan) }}" maxlength="10">
                        @error('pan')<p class="ep-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="aadhaar">Aadhaar</label>
                        <input id="aadhaar" name="aadhaar" value="{{ old('aadhaar', $profile->aadhaar) }}" maxlength="12">
                        @error('aadhaar')<p class="ep-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="uan">UAN</label>
                        <input id="uan" name="uan" value="{{ old('uan', $profile->uan) }}">
                        @error('uan')<p class="ep-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="ep-form-actions">
                    <button class="btn-pw" type="button" data-cancel>Cancel</button>
                    <button class="btn-pw primary" type="submit">Save</button>
                </div>
            </form>
            </article>
        </div>
        @can('employee_update')
            <div class="modal fade" id="passwordChangeModal" tabindex="-1" aria-labelledby="passwordChangeModalLabel" aria-hidden="true" @if($passwordErrors) data-open="1" @endif>
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content people-ws-dept-modal">
                        <form method="post" action="{{ route('admin.employee.profile.update', $user) }}?tab=profile" autocomplete="off">
                            @csrf
                            @method('put')
                            <input type="hidden" name="section" value="password">
                            <div class="modal-header">
                                <h2 class="modal-title" id="passwordChangeModalLabel">Change password</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="people-ws-note">Set a new sign-in password for {{ $workspace->displayName($user) }}. They use it with {{ $user->email ?: 'their email' }}.</p>
                                @if($passwordErrors)
                                    <p class="ep-error">{{ $errors->first('password') }}</p>
                                @endif
                                <div class="field">
                                    <label for="employee_password">New password</label>
                                    <input id="employee_password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                                </div>
                                <div class="field">
                                    <label for="employee_password_confirmation">Confirm password</label>
                                    <input id="employee_password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn-pw primary" type="submit">Update password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @push('script')
                <script>
                    (function () {
                        var modal = document.getElementById('passwordChangeModal');
                        if (!modal || !window.bootstrap) return;
                        if (modal.parentElement !== document.body) document.body.appendChild(modal);
                        if (modal.getAttribute('data-open') === '1') {
                            window.bootstrap.Modal.getOrCreateInstance(modal).show();
                        }
                    })();
                </script>
            @endpush
        @endcan
        <script>
            (function () {
                var sections = [
                    ['person-basic-view', 'person-basic-form', 'person-details-edit'],
                    ['person-operation-view', 'person-operation-form', 'person-file-edit'],
                    ['person-bank-view', 'person-bank-form', 'person-bank-edit']
                ].map(function (ids) {
                    return {
                        view: document.getElementById(ids[0]),
                        form: document.getElementById(ids[1]),
                        edit: document.getElementById(ids[2])
                    };
                });
                function show(section, on) {
                    if (section.view) section.view.hidden = on;
                    if (section.form) section.form.hidden = !on;
                    if (section.edit) section.edit.hidden = on;
                    if (on) {
                        sections.forEach(function (other) {
                            if (other !== section) show(other, false);
                        });
                    }
                }
                sections.forEach(function (section) {
                    if (section.edit) section.edit.addEventListener('click', function () { show(section, true); });
                    var cancel = section.form ? section.form.querySelector('[data-cancel]') : null;
                    if (cancel) cancel.addEventListener('click', function () { show(section, false); });
                });
                var overrideBox = document.getElementById('override_min_hours');
                var overrideField = document.getElementById('min-hours-override-field');
                function syncOverride() {
                    if (!overrideBox || !overrideField) return;
                    overrideField.hidden = !overrideBox.checked;
                }
                if (overrideBox) {
                    overrideBox.addEventListener('change', syncOverride);
                    syncOverride();
                }
                var weekOffBox = document.getElementById('override_week_off');
                var weekOffField = document.getElementById('week-off-override-field');
                function syncWeekOff() {
                    if (!weekOffBox || !weekOffField) return;
                    weekOffField.hidden = !weekOffBox.checked;
                }
                if (weekOffBox) {
                    weekOffBox.addEventListener('change', syncWeekOff);
                    syncWeekOff();
                }
            })();
        </script>
        @php $profileDocuments = $person['documents']->filter(fn ($document) => filled($document->file_path)); @endphp
        <article class="ep-card ep-docs">
            <header class="ep-card-head">
                <h3>Documents</h3>
                <button class="ep-edit" type="button" id="person-documents-view-all" onclick="var all=document.getElementById('person-documents-all'); if(!all) return; all.hidden=!all.hidden; this.textContent=all.hidden?'View all':'Hide'; if(!all.hidden) all.scrollIntoView({behavior:'smooth', block:'nearest'});">View all</button>
            </header>
            @if($profileDocuments->isEmpty())
                <div class="ep-empty">
                    <span class="material-icons" aria-hidden="true">folder_open</span>
                    <div>
                        <strong>No documents yet</strong>
                        <p>Offer letters, IDs, and other files will show up here.</p>
                    </div>
                </div>
            @else
                <ul class="ep-doc-list">
                    @foreach($profileDocuments->take(3) as $document)
                        @php
                            $previewExt = strtolower(pathinfo($document->original_name ?: $document->file_path, PATHINFO_EXTENSION));
                            $previewKind = in_array($previewExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? 'image' : ($previewExt === 'pdf' ? 'pdf' : 'file');
                        @endphp
                        <li>
                            <span class="material-icons" aria-hidden="true">description</span>
                            <button
                                class="ep-doc-name people-doc-open"
                                type="button"
                                data-doc-view
                                data-url="{{ route('admin.people.documents.view', $document) }}"
                                data-download="{{ route('admin.people.documents.download', $document) }}"
                                data-name="{{ $document->title }}"
                                data-kind="{{ $previewKind }}"
                            >{{ $document->title }}</button>
                            <span class="ep-doc-date">{{ $document->uploaded_at ? $document->uploaded_at->format('j M Y') : '—' }}</span>
                            <button
                                class="ep-doc-view"
                                type="button"
                                data-doc-view
                                data-url="{{ route('admin.people.documents.view', $document) }}"
                                data-download="{{ route('admin.people.documents.download', $document) }}"
                                data-name="{{ $document->title }}"
                                data-kind="{{ $previewKind }}"
                            >View</button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
        @endif

    @if($tab === 'profile')
        @php $uploadedDocuments = $person['documents']->filter(fn ($document) => filled($document->file_path)); @endphp
        <div id="person-documents-all" @if(! $documentReturn) hidden @endif>
        <div class="people-doc-toolbar">
            <button class="btn-pw primary" type="button" data-bs-toggle="modal" data-bs-target="#documentUploadModal">Upload a document</button>
        </div>
        <article class="people-ws-card people-ws-mt people-ws-scroll">
            <table>
                <thead><tr><th>Document</th><th>File</th><th>Added</th><th></th></tr></thead>
                <tbody>
                @forelse($uploadedDocuments as $document)
                    @php
                        $documentExt = strtolower(pathinfo($document->original_name ?: $document->file_path, PATHINFO_EXTENSION));
                        $documentKind = in_array($documentExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? 'image' : ($documentExt === 'pdf' ? 'pdf' : 'file');
                    @endphp
                    <tr>
                        <td>
                            <button
                                class="people-doc-open"
                                type="button"
                                data-doc-view
                                data-url="{{ route('admin.people.documents.view', $document) }}"
                                data-download="{{ route('admin.people.documents.download', $document) }}"
                                data-name="{{ $document->title }}"
                                data-kind="{{ $documentKind }}"
                            >{{ $document->title }}</button>
                        </td>
                        <td>{{ $document->original_name ?: 'File' }}</td>
                        <td>{{ $document->uploaded_at ? $document->uploaded_at->format('j M Y') : '—' }}</td>
                        <td class="people-ws-actions">
                            <button
                                class="btn-pw"
                                type="button"
                                data-doc-view
                                data-url="{{ route('admin.people.documents.view', $document) }}"
                                data-download="{{ route('admin.people.documents.download', $document) }}"
                                data-name="{{ $document->title }}"
                                data-kind="{{ $documentKind }}"
                            >View</button>
                            <a class="btn-pw" href="{{ route('admin.people.documents.download', $document) }}">Download</a>
                            <button
                                class="btn-pw danger"
                                type="button"
                                data-doc-remove
                                data-action="{{ route('admin.hr.documents.destroy', $document) }}"
                                data-name="{{ $document->title }}"
                            >Remove</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="people-ws-note">No documents uploaded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
        </div>

        <div class="modal fade" id="documentUploadModal" tabindex="-1" aria-labelledby="documentUploadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content people-ws-dept-modal">
                    <form method="post" action="{{ route('admin.hr.documents.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <input type="hidden" name="form_context" value="document">
                        <div class="modal-header">
                            <h2 class="modal-title" id="documentUploadModalLabel">Upload a document</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="people-ws-note">Name the document, then choose the file. PDF, Word, or an image, up to 5 MB.</p>
                            <div class="field">
                                <label for="doc_title">Document name</label>
                                <input id="doc_title" name="title" value="{{ $documentReturn ? old('title') : '' }}" required maxlength="80" placeholder="Offer letter">
                            </div>
                            <div class="field people-doc-upload">
                                <label>File</label>
                                <div class="upload-file">
                                    <input type="file" class="upload-file__input" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" data-maxFileSize="5MB" required>
                                    <div class="upload-file__img">
                                        <img src="{{ asset('assets/admin-module/img/media/upload-file.png') }}" alt="Upload file">
                                    </div>
                                    <span class="upload-file__edit">
                                        <span class="material-icons">edit</span>
                                    </span>
                                </div>
                                <p class="people-ws-note" id="name_of_file">No file chosen</p>
                                <div class="people-doc-chosen" id="documentChosenPreview" hidden>
                                    <img id="documentChosenImage" alt="Selected document" hidden>
                                    <iframe id="documentChosenFrame" title="Selected document" hidden></iframe>
                                    <p class="people-ws-note" id="documentChosenNote" hidden></p>
                                </div>
                                <span id="progress-label" hidden>0%</span>
                                <progress id="uploadProgress" value="0" max="100" hidden></progress>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn-pw primary" type="submit">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="documentRemoveModal" tabindex="-1" aria-labelledby="documentRemoveTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content people-ws-dept-modal">
                    <form id="documentRemoveForm" method="post" action="#">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h2 class="modal-title" id="documentRemoveTitle">Remove this document?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="people-ws-note" id="documentRemoveText">This document will be taken off this file.</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn-pw danger" type="submit">Remove</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content people-ws-dept-modal">
                    <div class="modal-header">
                        <h2 class="modal-title" id="documentPreviewTitle">Document</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body people-doc-preview">
                        <img id="documentPreviewImage" alt="" hidden>
                        <iframe id="documentPreviewFrame" title="Document" hidden></iframe>
                        <p class="people-ws-note" id="documentPreviewNote" hidden>This file cannot be shown here. Download it to open it.</p>
                    </div>
                    <div class="modal-footer">
                        <a class="btn-pw" id="documentPreviewDownload" href="#">Download</a>
                        <button class="btn-pw" type="button" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        @push('script')
            <script>
                (function () {
                    var all = document.getElementById('person-documents-all');
                    var viewAll = document.getElementById('person-documents-view-all');
                    if (viewAll && all && !all.hidden) viewAll.textContent = 'Hide';

                    var preview = document.getElementById('documentPreviewModal');
                    var previewTitle = document.getElementById('documentPreviewTitle');
                    var previewImg = document.getElementById('documentPreviewImage');
                    var previewFrame = document.getElementById('documentPreviewFrame');
                    var previewNote = document.getElementById('documentPreviewNote');
                    var previewDownload = document.getElementById('documentPreviewDownload');
                    var chosen = document.getElementById('documentChosenPreview');
                    var chosenImg = document.getElementById('documentChosenImage');
                    var chosenFrame = document.getElementById('documentChosenFrame');
                    var chosenNote = document.getElementById('documentChosenNote');
                    var input = document.querySelector('#documentUploadModal .upload-file__input');
                    var chosenUrl = null;

                    function clearChosen() {
                        if (chosenUrl) {
                            URL.revokeObjectURL(chosenUrl);
                            chosenUrl = null;
                        }
                        if (!chosen) return;
                        chosen.hidden = true;
                        chosenImg.hidden = true;
                        chosenImg.removeAttribute('src');
                        chosenFrame.hidden = true;
                        chosenFrame.removeAttribute('src');
                        chosenNote.hidden = true;
                        chosenNote.textContent = '';
                    }

                    function showStored(url, name, kind, downloadUrl) {
                        previewTitle.textContent = name || 'Document';
                        previewDownload.href = downloadUrl || url;
                        previewImg.hidden = true;
                        previewImg.removeAttribute('src');
                        previewFrame.hidden = true;
                        previewFrame.removeAttribute('src');
                        previewNote.hidden = true;
                        if (kind === 'image') {
                            previewImg.hidden = false;
                            previewImg.alt = name || 'Document';
                            previewImg.src = url;
                        } else if (kind === 'pdf') {
                            previewFrame.hidden = false;
                            previewFrame.src = url;
                        } else {
                            previewNote.hidden = false;
                        }
                        if (window.bootstrap) {
                            window.bootstrap.Modal.getOrCreateInstance(preview).show();
                        }
                    }

                    document.querySelectorAll('[data-doc-view]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            showStored(
                                button.getAttribute('data-url'),
                                button.getAttribute('data-name'),
                                button.getAttribute('data-kind'),
                                button.getAttribute('data-download')
                            );
                        });
                    });

                    if (preview) {
                        preview.addEventListener('hidden.bs.modal', function () {
                            previewImg.removeAttribute('src');
                            previewFrame.removeAttribute('src');
                        });
                    }

                    if (input && chosen) {
                        input.addEventListener('change', function () {
                            var file = input.files && input.files[0];
                            clearChosen();
                            if (!file) return;
                            chosen.hidden = false;
                            chosenUrl = URL.createObjectURL(file);
                            var type = file.type || '';
                            var name = file.name || '';
                            if (type.indexOf('image/') === 0) {
                                chosenImg.hidden = false;
                                chosenImg.src = chosenUrl;
                            } else if (type === 'application/pdf' || /\.pdf$/i.test(name)) {
                                chosenFrame.hidden = false;
                                chosenFrame.src = chosenUrl;
                            } else {
                                chosenNote.hidden = false;
                                chosenNote.textContent = name + ' will be saved. Word files open as a download from the list.';
                            }
                        });
                    }

                    var uploadModal = document.getElementById('documentUploadModal');
                    if (uploadModal) {
                        uploadModal.addEventListener('hidden.bs.modal', clearChosen);
                    }

                    var removeModal = document.getElementById('documentRemoveModal');
                    var removeForm = document.getElementById('documentRemoveForm');
                    var removeText = document.getElementById('documentRemoveText');
                    document.querySelectorAll('[data-doc-remove]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            if (!removeForm || !removeModal || !window.bootstrap) return;
                            removeForm.action = button.getAttribute('data-action');
                            removeText.textContent = (button.getAttribute('data-name') || 'This document') + ' will be taken off this file.';
                            window.bootstrap.Modal.getOrCreateInstance(removeModal).show();
                        });
                    });
                })();
            </script>
        @endpush
    @endif

    @if($tab === 'leaves')
        @php
            $trackedLeaveTypes = $leaveTypes->where('tracks_balance', true)->values();
            $leaveAdjustOpen = old('return_to') === 'person' && $errors->hasAny(['leave_type', 'direction', 'days', 'note']);
            $leaveNames = $leaveTypes->mapWithKeys(fn ($type) => [$type->code => $type->name])->all();
            $balance = $person['balance'];
            $pad = function ($n): string {
                $n = (float) $n;

                return abs($n - round($n)) < 0.001 ? sprintf('%02d', (int) round($n)) : number_format($n, 1);
            };
            $totalAvailable = 0;
            $totalUsed = 0;
            foreach ($trackedLeaveTypes as $type) {
                $totalAvailable += $balance ? $balance->remaining($type->code) : 0;
                $totalUsed += $balance ? $balance->taken($type->code) : 0;
            }
        @endphp
        @if($trackedLeaveTypes->isNotEmpty())
            <div class="people-lh-adjust">
                <button class="people-lh-apply" type="button" data-leave-adjust data-bs-toggle="modal" data-bs-target="#leaveAdjustModal">Adjust leave</button>
            </div>
        @endif
        <div class="people-lh">
            <article class="people-lh-total">
                <h2>Total Leaves</h2>
                <div class="people-lh-fill people-lh-balance-wrap">
                    <table class="people-lh-balance">
                        <thead>
                            <tr>
                                <th>Leave type</th>
                                <th class="is-num">Available</th>
                                <th class="is-num">Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="is-total">
                                <td>Total</td>
                                <td class="is-num">{{ $pad($totalAvailable) }}</td>
                                <td class="is-num">{{ $pad($totalUsed) }}</td>
                            </tr>
                            @foreach($leaveTypes as $type)
                                <tr>
                                    <td title="{{ $type->name }}">
                                        @if($type->short_name)
                                            <span class="people-lh-code">{{ $type->short_name }}</span>
                                        @endif
                                        <span class="people-lh-name">{{ $type->name }}</span>
                                    </td>
                                    @if($type->tracks_balance)
                                        <td class="is-num">{{ $pad($balance ? $balance->remaining($type->code) : 0) }}</td>
                                        <td class="is-num">{{ $pad($balance ? $balance->taken($type->code) : 0) }}</td>
                                    @else
                                        <td class="is-num">—</td>
                                        <td class="is-num">—</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
            <article class="people-lh-status">
                <div class="people-lh-status-bar">
                    <h2>Leave Status</h2>
                </div>
                <div class="people-lh-fill people-lh-table-wrap">
                    @if($person['leaveRequests']->isEmpty())
                        <p class="people-ws-note">No leave requests yet.</p>
                    @else
                        <table class="people-lh-table">
                            <colgroup>
                                <col class="is-start">
                                <col class="is-end">
                                <col class="is-reason">
                                <col class="is-type">
                                <col class="is-status">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Reason</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($person['leaveRequests'] as $leave)
                                @php $leaveName = $leaveNames[$leave->leave_type] ?? $workspace->leaveLabel($leave->leave_type); @endphp
                                <tr>
                                    <td title="{{ $leave->starts_on->format('j M Y') }}">{{ $leave->starts_on->format('j M Y') }}</td>
                                    <td title="{{ $leave->ends_on->format('j M Y') }}">{{ $leave->ends_on->format('j M Y') }}</td>
                                    <td title="{{ $leave->reason }}{{ $leave->status === 'sent_back' && filled($leave->decision_note) ? ' — Rejected: '.$leave->decision_note : '' }}">{{ $leave->reason }}</td>
                                    <td title="{{ $leaveName }}">{{ $leaveName }}</td>
                                    <td class="is-status"><span class="people-lh-pill is-{{ $leave->status }}">{{ $workspace->statusLabel($leave->status) }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </article>
        </div>
        @if($trackedLeaveTypes->isNotEmpty())
            <div class="modal fade" id="leaveAdjustModal" tabindex="-1" aria-labelledby="leaveAdjustModalLabel" aria-hidden="true" @if($leaveAdjustOpen) data-open="1" @endif>
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content people-ws-dept-modal">
                        <form id="leave-adjust-form" method="post" action="{{ route('admin.hr.leave.grant') }}">
                            @csrf
                            <input type="hidden" name="return_to" value="person">
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <div class="modal-header">
                                <h2 class="modal-title" id="leaveAdjustModalLabel">Adjust leave</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="people-ws-note">Add days to this year's balance, or remove days that are still left. Days already used or waiting on a request stay as they are.</p>
                                @if($leaveAdjustOpen)
                                    <p class="people-ws-note">{{ $errors->first('leave_type') ?: $errors->first('direction') ?: $errors->first('days') ?: $errors->first('note') }}</p>
                                @endif
                                <div class="field">
                                    <label for="leave_adjust_type">Leave type</label>
                                    <select id="leave_adjust_type" name="leave_type" required>
                                        @foreach($trackedLeaveTypes as $type)
                                            <option value="{{ $type->code }}" @selected(old('leave_type') === $type->code)>{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="leave_adjust_direction">Change</label>
                                    <select id="leave_adjust_direction" name="direction" required>
                                        <option value="add" @selected(old('direction', 'add') === 'add')>Add</option>
                                        <option value="remove" @selected(old('direction') === 'remove')>Remove</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="leave_adjust_days">Days</label>
                                    <input id="leave_adjust_days" name="days" type="number" min="0.5" max="365" step="0.5" value="{{ old('days') }}" required>
                                </div>
                                <div class="field">
                                    <label for="leave_adjust_note">Note</label>
                                    <input id="leave_adjust_note" name="note" type="text" maxlength="200" value="{{ old('note') }}" placeholder="Why this change">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn-pw primary" type="submit">Save adjustment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script>
            (function () {
                function modalEl() {
                    var all = document.querySelectorAll('#leaveAdjustModal');
                    for (var i = 0; i < all.length - 1; i++) all[i].remove();
                    var modal = all[all.length - 1] || document.getElementById('leaveAdjustModal');
                    if (modal && modal.parentElement !== document.body) document.body.appendChild(modal);
                    return modal;
                }
                if (!window.__pkLeaveAdjustModal) {
                    window.__pkLeaveAdjustModal = true;
                    document.addEventListener('click', function (event) {
                        if (!event.target.closest('[data-leave-adjust]')) return;
                        var modal = modalEl();
                        if (modal && window.bootstrap) window.bootstrap.Modal.getOrCreateInstance(modal).show();
                    });
                }
                var modal = modalEl();
                if (modal && modal.getAttribute('data-open') === '1' && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            })();
            </script>
        @endif
        @include('adminmodule::admin.people._leave_calendar', ['leaveCalendar' => $workspace->leaveCalendar($user->id)])
        @include('adminmodule::admin.people._leave_history', ['leaveHistory' => $person['leaveHistory'] ?? []])
    @endif

    @if($tab === 'salary')
        @php
            $salaryEditing = $errors->any() && old('effective_from');
            $rupee = fn ($amount) => '₹'.number_format((float) $amount, 0);
            $dateLabel = fn ($value) => $value ? \Carbon\Carbon::parse(strlen($value) === 7 ? $value.'-01' : $value)->format('j F Y') : 'Not set';
            $salaryHistory = $person['structures']->values();
            $effectiveValue = old('effective_from', $latestStructure->effective_from ?? (($period ?? now()->format('Y-m')).'-01'));
            if (strlen((string) $effectiveValue) === 7) {
                $effectiveValue .= '-01';
            }
        @endphp
        <article class="people-ws-card people-ws-mt" id="salary-preview" @if($salaryEditing) hidden @endif>
            <div class="people-id-top">
                <h2>Pay structure</h2>
                <button class="btn-pw" type="button" id="salary-edit">Edit</button>
            </div>
            @if($latestStructure)
                <dl class="people-id-grid">
                    <div><dt>Effective from</dt><dd>{{ $dateLabel($latestStructure->effective_from) }}</dd></div>
                    <div><dt>Gross</dt><dd>{{ $rupee($latestStructure->gross()) }}</dd></div>
                    <div><dt>Basic</dt><dd>{{ $rupee($latestStructure->basic) }}</dd></div>
                    <div><dt>HRA</dt><dd>{{ $rupee($latestStructure->hra) }}</dd></div>
                    <div><dt>Special allowance</dt><dd>{{ $rupee($latestStructure->special_allowance) }}</dd></div>
                    <div><dt>Provident fund, employee</dt><dd>{{ $rupee($latestStructure->pf_employee) }}</dd></div>
                    <div><dt>Provident fund, employer</dt><dd>{{ $rupee($latestStructure->pf_employer) }}</dd></div>
                    <div><dt>Professional tax</dt><dd>{{ $rupee($latestStructure->professional_tax) }}</dd></div>
                    <div><dt>Tax deducted at source</dt><dd>{{ $rupee($latestStructure->tds) }}</dd></div>
                    <div><dt>Other deduction</dt><dd>{{ $rupee($latestStructure->other_deduction) }}</dd></div>
                </dl>
            @else
                <p class="people-ws-note">No salary saved yet.</p>
            @endif
        </article>
        <form class="people-ws-card people-ws-mt" id="salary-form" method="post" action="{{ route('admin.hr.salary') }}" @if(! $salaryEditing) hidden @endif>
            @csrf
            <input type="hidden" name="return_to" value="person">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <h2>Pay structure</h2>
            <p class="people-ws-note">Choose a new date to record an appraisal. That keeps every earlier salary. Choosing a date already on file replaces only that date.</p>
            <div class="people-ws-form-grid">
                <div class="field"><label for="effective_from">Effective from</label><input id="effective_from" class="people-date-field" name="effective_from" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ $effectiveValue }}" required readonly></div>
                <div class="field"><label for="basic">Basic</label><input id="basic" name="basic" type="number" min="0" step="0.01" value="{{ old('basic', $latestStructure->basic ?? 0) }}" required></div>
                <div class="field"><label for="hra">HRA</label><input id="hra" name="hra" type="number" min="0" step="0.01" value="{{ old('hra', $latestStructure->hra ?? 0) }}" required></div>
                <div class="field"><label for="special_allowance">Special allowance</label><input id="special_allowance" name="special_allowance" type="number" min="0" step="0.01" value="{{ old('special_allowance', $latestStructure->special_allowance ?? 0) }}" required></div>
                <div class="field"><label for="pf_employee">Provident fund, employee</label><input id="pf_employee" name="pf_employee" type="number" min="0" step="0.01" value="{{ old('pf_employee', $latestStructure->pf_employee ?? 0) }}" required></div>
                <div class="field"><label for="pf_employer">Provident fund, employer</label><input id="pf_employer" name="pf_employer" type="number" min="0" step="0.01" value="{{ old('pf_employer', $latestStructure->pf_employer ?? 0) }}" required></div>
                <div class="field"><label for="professional_tax">Professional tax</label><input id="professional_tax" name="professional_tax" type="number" min="0" step="0.01" value="{{ old('professional_tax', $latestStructure->professional_tax ?? 0) }}" required></div>
                <div class="field"><label for="tds">Tax deducted at source</label><input id="tds" name="tds" type="number" min="0" step="0.01" value="{{ old('tds', $latestStructure->tds ?? 0) }}" required></div>
                <div class="field"><label for="other_deduction">Other deduction</label><input id="other_deduction" name="other_deduction" type="number" min="0" step="0.01" value="{{ old('other_deduction', $latestStructure->other_deduction ?? 0) }}" required></div>
            </div>
            <div class="people-ws-actions">
                <button class="btn-pw" type="button" id="salary-cancel">Cancel</button>
                <button class="btn-pw primary" type="submit">Save structure</button>
            </div>
        </form>
        <article class="people-ws-card people-ws-mt people-ws-scroll">
            <h2>Salary history</h2>
            <p class="people-ws-note">Each row is the salary from that date until the day before the next one. A new date is an appraisal. Earlier salaries stay as they were paid.</p>
            <table>
                <thead><tr><th>From</th><th>Until</th><th>Basic</th><th>HRA</th><th>Special allowance</th><th>Gross</th></tr></thead>
                <tbody>
                @forelse($salaryHistory as $structure)
                    @php
                        $newer = $loop->first ? null : $salaryHistory[$loop->index - 1];
                        $until = $newer
                            ? \Carbon\Carbon::parse(strlen($newer->effective_from) === 7 ? $newer->effective_from.'-01' : $newer->effective_from)->subDay()->format('j F Y')
                            : 'Current';
                    @endphp
                    <tr>
                        <td>{{ $dateLabel($structure->effective_from) }}</td>
                        <td>{{ $until }}</td>
                        <td>{{ $rupee($structure->basic) }}</td>
                        <td>{{ $rupee($structure->hra) }}</td>
                        <td>{{ $rupee($structure->special_allowance) }}</td>
                        <td>{{ $rupee($structure->gross()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="people-ws-note">No salary saved yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
        <script>
            (function () {
                var preview = document.getElementById('salary-preview');
                var form = document.getElementById('salary-form');
                var edit = document.getElementById('salary-edit');
                var cancel = document.getElementById('salary-cancel');
                function showEdit(on) {
                    if (preview) preview.hidden = on;
                    if (form) form.hidden = !on;
                }
                if (edit) edit.addEventListener('click', function () { showEdit(true); });
                if (cancel) cancel.addEventListener('click', function () { showEdit(false); });
            })();
        </script>
    @endif

    @if($tab === 'payslips')
        <article class="people-ws-card people-ws-mt people-ws-scroll">
            <table>
                <thead><tr><th>Month</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($person['payslips'] as $payslip)
                    <tr>
                        <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $payslip->period)->format('F Y') }}</td>
                        <td>₹{{ number_format((float) $payslip->gross, 0) }}</td>
                        <td>₹{{ number_format((float) $payslip->deductions, 0) }}</td>
                        <td>₹{{ number_format((float) $payslip->net, 0) }}</td>
                        <td>@include('adminmodule::admin.people._badge', ['status' => $payslip->held ? 'held' : $payslip->status, 'label' => $workspace->payrollStatusLabel($payslip->status, (bool) $payslip->held)])</td>
                        <td><a class="btn-pw" href="{{ route('admin.people.payslips.download', $payslip) }}">Download</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="people-ws-note">No payslips yet. Build the month on Payroll.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
    @endif
        </div>
    </div>
@endif
