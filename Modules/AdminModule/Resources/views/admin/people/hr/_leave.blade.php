@php
    $days = [\Modules\AdminModule\Entities\PeopleLeavePolicy::class, 'formatDays'];
    $paidTypes = $leaveTypes->where('tracks_balance', true)->values();
    $leaveTab = $leaveTab ?? 'policies';
@endphp
<nav class="people-ws-tabs">
    <a class="{{ $leaveTab === 'types' ? 'is-on' : '' }}" href="{{ route('admin.hr.index', ['section' => 'leave', 'tab' => 'types']) }}">Leave types</a>
    <a class="{{ in_array($leaveTab, ['policies', 'configure'], true) ? 'is-on' : '' }}" href="{{ route('admin.hr.index', ['section' => 'leave', 'tab' => 'policies']) }}">Leave policies</a>
</nav>

@if($leaveTab === 'types')
    <div class="people-ws-head">
        <div>
            <h1>Leave types</h1>
            <p>Name the kinds of leave people can take. A policy adds days for one type.</p>
        </div>
        <div class="people-ws-head-actions">
            <button class="btn-pw primary" type="button" data-leave-type-add>Add leave type</button>
        </div>
    </div>
    <article class="people-ws-card people-ws-scroll">
        <table class="people-dept-table">
            <thead>
                <tr>
                    <th>Leave type</th>
                    <th>Short name</th>
                    <th>Balance</th>
                    <th>Policies</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($leaveTypes as $type)
                @php $policyCount = $leavePolicies->where('leave_type_id', $type->id)->count(); @endphp
                <tr>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->short_name }}</td>
                    <td>{{ $type->tracks_balance ? 'Uses a balance' : 'No balance' }}</td>
                    <td>{{ $policyCount }} {{ $policyCount === 1 ? 'policy' : 'policies' }}</td>
                    <td>
                        <div class="people-dept-actions">
                            <button
                                class="btn-pw"
                                type="button"
                                data-leave-type-edit
                                data-id="{{ $type->id }}"
                                data-name="{{ $type->name }}"
                                data-short="{{ $type->short_name }}"
                                data-balance="{{ $type->tracks_balance ? '1' : '0' }}"
                            >Edit</button>
                            <form method="post" action="{{ route('admin.hr.leave.types.destroy', $type) }}" onsubmit="return confirm(@json('Delete '.$type->name.'?'))">
                                @csrf
                                @method('DELETE')
                                <button class="btn-pw danger" type="submit" @if($policyCount > 0) title="Remove the policies that use this type before you delete it." @endif>Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="people-ws-note">No leave types yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </article>

    <div class="modal fade" id="leaveTypeModal" tabindex="-1" aria-labelledby="leaveTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content people-ws-dept-modal">
                <form
                    id="leave-type-form"
                    method="post"
                    action="{{ route('admin.hr.leave.types.store') }}"
                    data-store="{{ route('admin.hr.leave.types.store') }}"
                    data-update="{{ route('admin.hr.leave.types.update', ['leaveType' => '__ID__']) }}"
                >
                    @csrf
                    <input type="hidden" name="form_context" value="leave-type">
                    <input type="hidden" name="editing_id" id="leave-type-editing-id" value="{{ old('editing_id') }}">
                    <div class="modal-header">
                        <h2 class="modal-title" id="leaveTypeModalLabel">Add a leave type</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="field">
                            <label for="leave_type_name">Name</label>
                            <input id="leave_type_name" name="name" value="{{ old('name') }}" required maxlength="80" placeholder="Casual">
                        </div>
                        <div class="field">
                            <label for="leave_type_short">Short name</label>
                            <input id="leave_type_short" name="short_name" value="{{ old('short_name') }}" required maxlength="12" placeholder="CL">
                        </div>
                        <div class="field">
                            <input type="hidden" name="tracks_balance" value="0">
                            <label for="leave_type_balance"><input id="leave_type_balance" type="checkbox" name="tracks_balance" value="1" @checked(old('form_context') === 'leave-type' ? old('tracks_balance') == '1' : true)> This leave uses a balance</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn-pw primary" type="submit" id="leave-type-submit">Add leave type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
    <script>
    (function () {
        var form = document.getElementById('leave-type-form');
        var title = document.getElementById('leaveTypeModalLabel');
        var submit = document.getElementById('leave-type-submit');
        var nameInput = document.getElementById('leave_type_name');
        var shortInput = document.getElementById('leave_type_short');
        var balance = document.getElementById('leave_type_balance');
        var editing = document.getElementById('leave-type-editing-id');
        var modalEl = document.getElementById('leaveTypeModal');
        if (!form || !modalEl || !window.bootstrap) {
            return;
        }

        function openLeaveType(id, name, shortName, usesBalance) {
            nameInput.value = name || '';
            shortInput.value = shortName || '';
            balance.checked = !!usesBalance;
            if (id) {
                form.action = form.getAttribute('data-update').replace('__ID__', id);
                editing.value = id;
                title.textContent = 'Edit leave type';
                submit.textContent = 'Save leave type';
            } else {
                form.action = form.getAttribute('data-store');
                editing.value = '';
                title.textContent = 'Add a leave type';
                submit.textContent = 'Add leave type';
            }
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        document.addEventListener('click', function (event) {
            var edit = event.target.closest('[data-leave-type-edit]');
            if (edit) {
                openLeaveType(edit.getAttribute('data-id'), edit.getAttribute('data-name'), edit.getAttribute('data-short'), edit.getAttribute('data-balance') === '1');
                return;
            }
            if (event.target.closest('[data-leave-type-add]')) {
                openLeaveType('', '', '', true);
            }
        });

        @if(old('form_context') === 'leave-type')
        openLeaveType(@json(old('editing_id')), @json(old('name')), @json(old('short_name')), @json(old('tracks_balance') == '1'));
        @endif
    })();
    </script>
    @endpush
@elseif($leaveTab === 'configure' && $policyFocus)
    @php
        $configureUrl = route('admin.hr.index', ['section' => 'leave', 'tab' => 'configure', 'policy' => $policyFocus->id]);
        $assignedPeople = $policyAssignments->sortBy(fn ($assignment) => $assignment->user ? $workspace->displayName($assignment->user) : '');
    @endphp
    <div class="people-ws-head">
        <div>
            <h1>{{ $policyFocus->name }}</h1>
            <p>{{ $policyFocus->leaveType->name ?? 'Leave' }} · {{ $policyFocus->accrual_type === 'monthly' ? 'Monthly' : 'Yearly' }} · {{ $days((float) $policyFocus->days) }} {{ ((float) $policyFocus->days) == 1 ? 'day' : 'days' }}</p>
        </div>
    </div>
    <nav class="people-ws-tabs">
        <a href="{{ route('admin.hr.index', ['section' => 'leave', 'tab' => 'policies']) }}">Leave policies</a>
        <a class="is-on" href="{{ $configureUrl }}">Configuration</a>
    </nav>
    <div class="people-ws-grid-2">
        <form class="people-ws-card" method="post" action="{{ route('admin.hr.leave.assign') }}">
            @csrf
            <h2>Assign to an employee</h2>
            <input type="hidden" name="policy_id" value="{{ $policyFocus->id }}">
            <div class="field">
                <label for="assign_user">Employee</label>
                <select id="assign_user" name="user_id" required>
                    <option value="">Choose an employee</option>
                    @foreach($staff as $person)
                        @php $profile = $profiles->get($person->id); @endphp
                        @if(($profile->employment_status ?? '') === 'exited')
                            @continue
                        @endif
                        <option value="{{ $person->id }}">{{ $workspace->displayName($person) }}@if($profile && $profile->department) · {{ $profile->department }}@endif</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-pw primary" type="submit">Assign</button>
        </form>
        <form class="people-ws-card" method="post" action="{{ route('admin.hr.leave.assign') }}">
            @csrf
            <h2>Assign to a department</h2>
            <p class="people-ws-note">People in this department get the days now. Anyone who joins later gets them too. One department keeps one policy per leave type.</p>
            <input type="hidden" name="policy_id" value="{{ $policyFocus->id }}">
            <div class="field">
                <label for="assign_department">Department</label>
                <select id="assign_department" name="department_id" required>
                    <option value="">Choose a department</option>
                    @foreach($departments as $department)
                        @php $count = (int) ($departmentCounts[$department->name] ?? 0); @endphp
                        <option value="{{ $department->id }}">{{ $department->name }} · {{ $count }} {{ $count === 1 ? 'person' : 'people' }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-pw primary" type="submit" @disabled($departments->isEmpty())>Assign department</button>
        </form>
    </div>
    <article class="people-ws-card people-ws-mt people-ws-scroll">
        <table class="people-dept-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>People</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($policyDepartments as $link)
                @php $linked = $link->department; $count = $linked ? (int) ($departmentCounts[$linked->name] ?? 0) : 0; @endphp
                <tr>
                    <td>{{ $linked->name ?? '—' }}</td>
                    <td>{{ $count }} {{ $count === 1 ? 'person' : 'people' }}</td>
                    <td>
                        @if($linked)
                            <form method="post" action="{{ route('admin.hr.leave.departments.detach', ['policy' => $policyFocus, 'department' => $linked]) }}" onsubmit="return confirm(@json('Remove this policy from '.$linked->name.'? People who were also assigned it directly keep it.'))">
                                @csrf
                                @method('DELETE')
                                <button class="btn-pw danger" type="submit">Remove</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="people-ws-note">No department is using this policy yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </article>
    <article class="people-ws-card people-ws-mt people-ws-scroll">
        <table class="people-dept-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Assigned by</th>
                    <th>Next credit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($assignedPeople as $assignment)
                @php
                    $profile = $profiles->get($assignment->user_id);
                    $source = $assignment->via_employee && $assignment->via_department
                        ? 'Employee and department'
                        : ($assignment->via_department ? 'Department' : 'Employee');
                @endphp
                <tr>
                    <td>{{ $assignment->user ? $workspace->displayName($assignment->user) : '—' }}</td>
                    <td>{{ $profile && $profile->department ? $profile->department : '—' }}</td>
                    <td>{{ $source }}</td>
                    <td>{{ $assignment->next_accrual_on ? $assignment->next_accrual_on->format('j M Y') : '—' }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.hr.leave.assignments.destroy', $assignment) }}" onsubmit="return confirm(@json('Remove this policy from '.($assignment->user ? $workspace->displayName($assignment->user) : 'this person').'?'))">
                            @csrf
                            @method('DELETE')
                            <button class="btn-pw danger" type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="people-ws-note">No one is on this policy yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </article>
@else
    <div class="people-ws-head">
        <div>
            <h1>Leave policies</h1>
            <p>Each policy is for one leave type. Days are added when you assign it to a person or a department, then again each month or each year. A department keeps the policy for people who join later.</p>
        </div>
        <div class="people-ws-head-actions">
            <button class="btn-pw primary" type="button" data-leave-policy-add @disabled($paidTypes->isEmpty())>Add policy</button>
        </div>
    </div>
    <article class="people-ws-card people-ws-scroll">
        <table class="people-dept-table">
            <thead>
                <tr>
                    <th>Policy</th>
                    <th>Leave type</th>
                    <th>Accrual</th>
                    <th>Days</th>
                    <th>People</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($leavePolicies as $policy)
                <tr>
                    <td>{{ $policy->name }}</td>
                    <td>{{ $policy->leaveType->name ?? '—' }}</td>
                    <td>{{ $policy->accrual_type === 'monthly' ? 'Monthly' : 'Yearly' }}</td>
                    <td>{{ $days((float) $policy->days) }}</td>
                    <td>{{ $policy->assignments_count }} {{ $policy->assignments_count === 1 ? 'person' : 'people' }}</td>
                    <td>
                        <div class="people-dept-actions">
                            <a class="btn-pw" href="{{ route('admin.hr.index', ['section' => 'leave', 'tab' => 'configure', 'policy' => $policy->id]) }}">Configuration</a>
                            <button
                                class="btn-pw"
                                type="button"
                                data-leave-policy-edit
                                data-id="{{ $policy->id }}"
                                data-name="{{ $policy->name }}"
                                data-type="{{ $policy->leave_type_id }}"
                                data-accrual="{{ $policy->accrual_type }}"
                                data-days="{{ $days((float) $policy->days) }}"
                            >Edit</button>
                            <form method="post" action="{{ route('admin.hr.leave.policies.destroy', $policy) }}" onsubmit="return confirm(@json('Delete '.$policy->name.'?'))">
                                @csrf
                                @method('DELETE')
                                <button class="btn-pw danger" type="submit" @if($policy->assignments_count > 0) title="Take this policy off people before you delete it." @endif>Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="people-ws-note">No policies yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </article>

    <div class="modal fade" id="leavePolicyModal" tabindex="-1" aria-labelledby="leavePolicyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content people-ws-dept-modal">
                <form
                    id="leave-policy-form"
                    method="post"
                    action="{{ route('admin.hr.leave.policies.store') }}"
                    data-store="{{ route('admin.hr.leave.policies.store') }}"
                    data-update="{{ route('admin.hr.leave.policies.update', ['policy' => '__ID__']) }}"
                >
                    @csrf
                    <input type="hidden" name="form_context" value="leave-policy">
                    <input type="hidden" name="editing_id" id="leave-policy-editing-id" value="{{ old('editing_id') }}">
                    <div class="modal-header">
                        <h2 class="modal-title" id="leavePolicyModalLabel">Add a policy</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="people-ws-note">One policy covers one leave type. Days are what one credit adds. They go on the balance when you assign the policy, then again each month or each year.</p>
                        <div class="field">
                            <label for="policy_name">Name</label>
                            <input id="policy_name" name="name" value="{{ old('name') }}" required maxlength="80">
                        </div>
                        <div class="field">
                            <label for="policy_leave_type">Leave type</label>
                            <select id="policy_leave_type" name="leave_type_id" required>
                                @forelse($paidTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('leave_type_id') === $type->id)>{{ $type->name }}</option>
                                @empty
                                    <option value="" disabled>Add a leave type first</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="field">
                            <label for="accrual_type">Accrual</label>
                            <select id="accrual_type" name="accrual_type" required>
                                <option value="monthly" @selected(old('accrual_type', 'monthly') === 'monthly')>Monthly</option>
                                <option value="yearly" @selected(old('accrual_type') === 'yearly')>Yearly</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="policy_days">Days</label>
                            <input id="policy_days" name="days" type="number" min="0.5" max="365" step="0.5" value="{{ old('days', 1) }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn-pw primary" type="submit" id="leave-policy-submit" @disabled($paidTypes->isEmpty())>Add policy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
    <script>
    (function () {
        var form = document.getElementById('leave-policy-form');
        var title = document.getElementById('leavePolicyModalLabel');
        var submit = document.getElementById('leave-policy-submit');
        var nameInput = document.getElementById('policy_name');
        var typeInput = document.getElementById('policy_leave_type');
        var accrualInput = document.getElementById('accrual_type');
        var daysInput = document.getElementById('policy_days');
        var editing = document.getElementById('leave-policy-editing-id');
        var modalEl = document.getElementById('leavePolicyModal');
        if (!form || !modalEl || !window.bootstrap) {
            return;
        }

        function openPolicy(policy) {
            policy = policy || {};
            nameInput.value = policy.name || '';
            if (typeInput && policy.type) {
                typeInput.value = policy.type;
            }
            accrualInput.value = policy.accrual || 'monthly';
            daysInput.value = policy.days || '1';
            if (policy.id) {
                form.action = form.getAttribute('data-update').replace('__ID__', policy.id);
                editing.value = policy.id;
                title.textContent = 'Edit policy';
                submit.textContent = 'Save policy';
            } else {
                form.action = form.getAttribute('data-store');
                editing.value = '';
                title.textContent = 'Add a policy';
                submit.textContent = 'Add policy';
            }
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        document.addEventListener('click', function (event) {
            var edit = event.target.closest('[data-leave-policy-edit]');
            if (edit) {
                openPolicy({
                    id: edit.getAttribute('data-id'),
                    name: edit.getAttribute('data-name'),
                    type: edit.getAttribute('data-type'),
                    accrual: edit.getAttribute('data-accrual'),
                    days: edit.getAttribute('data-days')
                });
                return;
            }
            if (event.target.closest('[data-leave-policy-add]')) {
                openPolicy({ accrual: 'monthly', days: '1' });
            }
        });

        @if(old('form_context') === 'leave-policy')
        openPolicy({
            id: @json(old('editing_id')),
            name: @json(old('name')),
            type: @json(old('leave_type_id')),
            accrual: @json(old('accrual_type', 'monthly')),
            days: @json(old('days', 1))
        });
        @endif
    })();
    </script>
    @endpush
@endif
