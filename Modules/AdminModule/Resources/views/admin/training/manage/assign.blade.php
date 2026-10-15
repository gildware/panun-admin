@extends('adminmodule::layouts.new-master')

@section('title', 'Training setup')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    @php $scope = old('scope', 'employee'); @endphp
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">Assign</h2>
                    <div class="trn-meta mt-1">Give a published course to one employee or a whole department, with a deadline.</div>
                </div>
                @include('adminmodule::admin.training.manage._nav')
            </div>

            <div class="card">
                <div class="card-body">
                    @if($errors->any())
                        <div class="trn-meta mb-2" style="color:#b91c1c">{{ $errors->first() }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.training.assign.store') }}" class="lms-assign" id="assign-form">
                        @csrf
                        <div>
                            <label class="form-label" for="assign-course">Course</label>
                            <select id="assign-course" class="form-select" name="training_program_id" required>
                                <option value="" disabled @selected(! old('training_program_id'))>Choose a course</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" @selected(old('training_program_id') === $program->id)>{{ $program->name }} · {{ $program->difficultyLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
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
                            <select id="assign-person" class="form-select" name="user_id" @disabled($scope !== 'employee')>
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
                            <select id="assign-department" class="form-select" name="department_id" @disabled($scope !== 'department')>
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
                    <div class="trn-meta mt-2">A department assignment includes every active employee in that department. Anyone who already has the course keeps it, and the deadline is updated.</div>
                </div>
            </div>

            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">Status</h2>
                    <div class="trn-meta mt-1">How many people have finished, and where everyone else is.</div>
                </div>
            </div>

            @if($filters->isNotEmpty())
                <div class="trn-filters mb-2">
                    <a href="{{ route('admin.training.assign') }}" class="{{ $programId ? '' : 'is-on' }}">All courses</a>
                    @foreach($filters as $id => $program)
                        <a href="{{ route('admin.training.assign', ['program' => $id]) }}" class="{{ $programId === (string) $id ? 'is-on' : '' }}">{{ $program->name }}</a>
                    @endforeach
                </div>
            @endif

            <div class="lms-stats mb-2">
                <div class="lms-stat"><b>{{ $totals['assigned'] }}</b><span>Assigned</span></div>
                <div class="lms-stat is-ok"><b>{{ $totals['finished'] }}</b><span>Finished</span></div>
                <div class="lms-stat"><b>{{ $totals['in_progress'] }}</b><span>In progress</span></div>
                <div class="lms-stat"><b>{{ $totals['not_started'] }}</b><span>Not started</span></div>
                <div class="lms-stat {{ $totals['overdue'] > 0 ? 'is-bad' : '' }}"><b>{{ $totals['overdue'] }}</b><span>Overdue</span></div>
                <div class="lms-stat"><b>{{ $totals['percent'] }}%</b><span>Completion</span></div>
            </div>

            @forelse($groups as $group)
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                            <div>
                                <div class="fw-semibold">{{ $group['program']->name }}</div>
                                <div class="trn-meta">
                                    {{ $group['totals']['finished'] }} of {{ $group['totals']['assigned'] }} finished
                                    @if($group['totals']['overdue'] > 0)
                                        · {{ $group['totals']['overdue'] }} overdue
                                    @endif
                                </div>
                            </div>
                            @include('adminmodule::admin.training._difficulty', ['difficulty' => $group['program']->difficulty])
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Deadline</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($group['people'] as $person)
                                    @php $assignment = $person['assignment']; @endphp
                                    <tr>
                                        <td>
                                            {{ $person['name'] }}
                                            @if($assignment->assigned_scope === 'department' && $assignment->department_name)
                                                <div class="trn-meta">Via {{ $assignment->department_name }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $person['department'] !== '' ? $person['department'] : '—' }}</td>
                                        <td>
                                            @if($assignment->due_at)
                                                {{ $assignment->due_at->format('j M Y') }}
                                                @if($person['overdue'])
                                                    <div>@include('adminmodule::admin.training._badge', ['text' => 'Overdue', 'tone' => 'bad'])</div>
                                                @elseif($person['finished_late'])
                                                    <div class="trn-meta">Finished after the deadline</div>
                                                @endif
                                            @else
                                                <span class="trn-meta">No deadline</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="lms-progress-cell">
                                                <div class="lms-bar {{ $person['finished'] ? 'is-done' : '' }}"><span style="width: {{ $person['percent'] }}%"></span></div>
                                                <span class="trn-meta">
                                                    @if($person['lesson_total'] > 0)
                                                        {{ $person['lesson_done'] }} of {{ $person['lesson_total'] }}
                                                    @else
                                                        {{ $person['percent'] }}%
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td>@include('adminmodule::admin.training._badge', ['text' => $person['where'], 'tone' => $person['where_tone']])</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card">
                    <div class="card-body trn-meta">Nothing is assigned yet.</div>
                </div>
            @endforelse
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
