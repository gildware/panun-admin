@php
    $dayLabels = \Modules\AdminModule\Services\PeopleWorkspace::DAY_LABELS;
    $savedOff = is_array($timesheetSettings->week_off ?? null) ? $timesheetSettings->week_off : ['sun'];
    $selectedOff = old('form_context') === 'timesheet-settings' ? (array) old('week_off', []) : $savedOff;
    $restoring = old('form_context') === 'timesheet-settings';
    $fullTimeHours = $restoring ? old('min_hours_full_time') : ($timesheetSettings->min_hours_full_time ?? $timesheetSettings->min_hours);
    $partTimeHours = $restoring ? old('min_hours_part_time') : ($timesheetSettings->min_hours_part_time ?? $timesheetSettings->min_hours);
    $startTiming = $restoring ? old('starts_on') : ($timesheetSettings->starts_on ? $timesheetSettings->starts_on->toDateString() : '2026-10-01');
@endphp
<div class="people-ws-head">
    <div>
        <h1>Configuration</h1>
        <p>When timesheets begin, additional hours people can add, the least a working day must contain for full-time and part-time employees, and which days are week off.</p>
    </div>
</div>

<form class="people-ws-card" method="post" action="{{ route('admin.hr.configuration') }}">
    @csrf
    <input type="hidden" name="form_context" value="timesheet-settings">
    @if($errors->any() && old('form_context') === 'timesheet-settings')
        <ul class="people-ws-errors">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <div class="field">
        <label for="starts_on">Start timing</label>
        <input id="starts_on" class="people-date-field" name="starts_on" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ $startTiming }}" required readonly>
        <p class="people-ws-note">Timesheets begin on this date. Pending days and missing timesheet days in payroll leave out every day before it.</p>
    </div>
    <div class="people-ws-hours-pair">
        <div class="field">
            <label for="min_hours_full_time">Minimum hours in a day · full time</label>
            <input id="min_hours_full_time" class="people-ws-hours" name="min_hours_full_time" type="number" min="0" max="24" step="0.5" inputmode="decimal" value="{{ $fullTimeHours }}" required>
        </div>
        <div class="field">
            <label for="min_hours_part_time">Minimum hours in a day · part time</label>
            <input id="min_hours_part_time" class="people-ws-hours" name="min_hours_part_time" type="number" min="0" max="24" step="0.5" inputmode="decimal" value="{{ $partTimeHours }}" required>
        </div>
    </div>
    <p class="people-ws-note">A working day cannot be submitted until it adds up to at least this many hours for that kind of employee. Use 0 for the standard day: 8 hours full time, 4 hours part time. An employee file can set a different minimum for one person.</p>
    <div class="field">
        <label>Week off</label>
        <div class="people-ws-days">
            @foreach($dayLabels as $key => $label)
                <label>
                    <input type="checkbox" name="week_off[]" value="{{ $key }}" @checked(in_array($key, $selectedOff, true))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <p class="people-ws-note">People do not fill a timesheet on these days, and payroll does not count them as working days. Leave at least one day as a working day.</p>
    </div>
    <button class="btn-pw primary" type="submit">Save settings</button>
</form>

<article class="people-ws-card people-ws-mt">
    <div class="people-ws-list-bar">
        <h2>Additional hours</h2>
        <button class="btn-pw primary" type="button" data-timesheet-task-add>Add additional hours</button>
    </div>
    <div class="people-ws-scroll">
    <table class="people-dept-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($timesheetTasks as $task)
            <tr>
                <td>{{ $task->name }}</td>
                <td>
                    <div class="people-dept-actions">
                        <button
                            class="btn-pw"
                            type="button"
                            data-timesheet-task-edit
                            data-id="{{ $task->id }}"
                            data-name="{{ $task->name }}"
                        >Edit</button>
                        <form method="post" action="{{ route('admin.hr.configuration.tasks.destroy', $task) }}" onsubmit="return confirm(@json('Remove '.$task->name.' from additional hours?'))">
                            @csrf
                            @method('DELETE')
                            <button class="btn-pw danger" type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="people-ws-note">No additional hours yet. People log the tasks assigned to them on the task board, and can add these when the time is not one of those tasks.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
    </div>
</article>

<div class="modal fade" id="timesheetTaskModal" tabindex="-1" aria-labelledby="timesheetTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content people-ws-dept-modal">
            <form
                id="timesheet-task-form"
                method="post"
                action="{{ route('admin.hr.configuration.tasks.store') }}"
                data-store="{{ route('admin.hr.configuration.tasks.store') }}"
                data-update="{{ route('admin.hr.configuration.tasks.update', ['timesheetTask' => '__ID__']) }}"
            >
                @csrf
                <input type="hidden" name="form_context" value="timesheet-task">
                <input type="hidden" name="editing_id" id="timesheet-task-editing-id" value="{{ old('editing_id') }}">
                <div class="modal-header">
                    <h2 class="modal-title" id="timesheetTaskModalLabel">Add additional hours</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any() && old('form_context') === 'timesheet-task')
                        <ul class="people-ws-errors">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="field">
                        <label for="timesheet_task_name">Name</label>
                        <input id="timesheet_task_name" name="name" value="{{ old('form_context') === 'timesheet-task' ? old('name') : '' }}" required maxlength="120" placeholder="Training">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn-pw primary" type="submit" id="timesheet-task-submit">Add additional hours</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
(function () {
    var form = document.getElementById('timesheet-task-form');
    var title = document.getElementById('timesheetTaskModalLabel');
    var submit = document.getElementById('timesheet-task-submit');
    var nameInput = document.getElementById('timesheet_task_name');
    var editing = document.getElementById('timesheet-task-editing-id');
    var modalEl = document.getElementById('timesheetTaskModal');
    if (!form || !modalEl || !window.bootstrap) {
        return;
    }

    function openTask(id, name) {
        nameInput.value = name || '';
        if (id) {
            form.action = form.getAttribute('data-update').replace('__ID__', id);
            editing.value = id;
            title.textContent = 'Edit additional hours';
            submit.textContent = 'Save additional hours';
        } else {
            form.action = form.getAttribute('data-store');
            editing.value = '';
            title.textContent = 'Add additional hours';
            submit.textContent = 'Add additional hours';
        }
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.addEventListener('click', function (event) {
        var edit = event.target.closest('[data-timesheet-task-edit]');
        if (edit) {
            openTask(edit.getAttribute('data-id'), edit.getAttribute('data-name'));
            return;
        }
        if (event.target.closest('[data-timesheet-task-add]')) {
            openTask('', '');
        }
    });

    @if(old('form_context') === 'timesheet-task')
    openTask(@json(old('editing_id')), @json(old('name')));
    @endif
})();
</script>
@endpush
