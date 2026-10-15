@extends('adminmodule::layouts.new-master')

@section('title', 'Assign '.$test->name)

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    @php $scope = old('scope', 'employee'); @endphp
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">Assign {{ $test->name }}</h2>
                    <div class="trn-meta mt-1">Give this published call test to one employee or a whole department, with a deadline.</div>
                </div>
                <div class="lms-pagehead-actions">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.manage.edit', $test) }}">Set up</a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.manage') }}">All call tests</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @if($errors->any())
                        <div class="trn-meta mb-2" style="color:#b91c1c">{{ $errors->first() }}</div>
                    @endif
                    @if($test->isPublished())
                        <form method="POST" action="{{ route('admin.training.calls.manage.assign.store', $test) }}" class="lms-assign" id="assign-form">
                            @csrf
                            <div>
                                <span class="form-label">Assign to</span>
                                <div class="lms-scope">
                                    <label class="{{ $scope === 'employee' ? 'is-on' : '' }}">
                                        <input type="radio" name="scope" value="employee" @checked($scope === 'employee')>
                                        Employee
                                    </label>
                                    <label class="{{ $scope === 'department' ? 'is-on' : '' }}">
                                        <input type="radio" name="scope" value="department" @checked($scope === 'department')>
                                        Department
                                    </label>
                                </div>
                            </div>
                            <div data-target="employee" @if($scope !== 'employee') hidden @endif>
                                <label class="form-label" for="assign-person">Employee</label>
                                <select id="assign-person" class="form-select" name="user_id">
                                    <option value="" disabled @selected(! old('user_id'))>Choose an employee</option>
                                    @foreach($staff as $person)
                                        @php $department = \Modules\AdminModule\Support\TrainingPeople::department($person); @endphp
                                        <option value="{{ $person->id }}" @selected(old('user_id') === $person->id)>
                                            {{ \Modules\AdminModule\Support\TrainingPeople::name($person) }}@if($department !== '') · {{ $department }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div data-target="department" @if($scope !== 'department') hidden @endif>
                                <label class="form-label" for="assign-department">Department</label>
                                <select id="assign-department" class="form-select" name="department_id">
                                    <option value="" disabled @selected(! old('department_id'))>Choose a department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" @selected(old('department_id') === $department->id)>
                                            {{ $department->name }} · {{ $department->staff_count }} {{ $department->staff_count === 1 ? 'person' : 'people' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="assign-due">Deadline</label>
                                <input id="assign-due" class="form-control" type="date" name="due_at" value="{{ old('due_at') }}" min="{{ now()->toDateString() }}" required>
                            </div>
                            <button class="btn btn--primary btn-sm" type="submit">Assign</button>
                        </form>
                    @else
                        <div class="trn-meta">Publish this call test from Set up before assigning it.</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="h6 mb-2">Assigned</h3>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Calls</th>
                                <th>Result</th>
                                <th>Deadline</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($assignments as $assignment)
                                @php $rollup = $assignment->rollup(); @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ \Modules\AdminModule\Support\TrainingPeople::name($assignment->user) }}</div>
                                        <div class="trn-meta">{{ \Modules\AdminModule\Support\TrainingPeople::department($assignment->user) ?: ($assignment->department_name ?: '—') }}</div>
                                    </td>
                                    <td class="trn-meta">{{ $rollup['done'] }} of {{ $rollup['total'] }}</td>
                                    <td>
                                        @include('adminmodule::admin.training._badge', ['text' => $rollup['label'], 'tone' => $rollup['tone']])
                                        @if($rollup['earned'] !== null)
                                            <span class="trn-meta">{{ $rollup['earned'] }} / {{ $rollup['marks'] }}</span>
                                        @elseif($rollup['overall'] !== null)
                                            <span class="trn-meta">{{ $rollup['overall'] }}</span>
                                        @endif
                                    </td>
                                    <td class="trn-meta">{{ $assignment->due_at ? $assignment->due_at->format('j M Y') : '—' }}</td>
                                    <td class="text-end">
                                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.show', $assignment) }}">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">Nobody has this test yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const form = document.getElementById('assign-form');
            if (!form) return;
            const employee = form.querySelector('[data-target="employee"]');
            const department = form.querySelector('[data-target="department"]');
            const employeeSelect = document.getElementById('assign-person');
            const departmentSelect = document.getElementById('assign-department');

            function sync() {
                const scope = form.querySelector('input[name="scope"]:checked')?.value || 'employee';
                const byEmployee = scope === 'employee';
                employee.hidden = !byEmployee;
                department.hidden = byEmployee;
                employeeSelect.disabled = !byEmployee;
                departmentSelect.disabled = byEmployee;
                employeeSelect.required = byEmployee;
                departmentSelect.required = !byEmployee;
                form.querySelectorAll('.lms-scope label').forEach(function (label) {
                    label.classList.toggle('is-on', label.querySelector('input')?.checked);
                });
            }

            form.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
