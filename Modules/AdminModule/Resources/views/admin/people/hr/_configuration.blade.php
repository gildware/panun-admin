@php
    $dayLabels = \Modules\AdminModule\Services\PeopleWorkspace::DAY_LABELS;
    $savedOff = is_array($timesheetSettings->week_off ?? null) ? $timesheetSettings->week_off : ['sun'];
    $selectedOff = old('form_context') === 'timesheet-settings' ? (array) old('week_off', []) : $savedOff;
    $minHours = old('form_context') === 'timesheet-settings' ? old('min_hours') : $timesheetSettings->min_hours;
@endphp
<div class="people-ws-head">
    <div>
        <h1>Configuration</h1>
        <p>Task names people pick on a timesheet, the least a working day must contain, and which days are week off.</p>
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
        <label for="min_hours">Minimum hours in a day</label>
        <input id="min_hours" class="people-ws-hours" name="min_hours" type="number" min="0" max="24" step="0.5" inputmode="decimal" value="{{ $minHours }}" required>
        <p class="people-ws-note">A submitted day has to add up to at least this many hours. Use 0 to allow any amount above zero.</p>
    </div>
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
        <h2>Task names</h2>
        <button class="btn-pw primary" type="button" data-timesheet-task-add>Add task name</button>
    </div>
    <div class="people-ws-scroll">
    <table class="people-dept-table">
        <thead>
            <tr>
                <th>Task name</th>
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
                        <form method="post" action="{{ route('admin.hr.configuration.tasks.destroy', $task) }}" onsubmit="return confirm(@json('Remove '.$task->name.' from the timesheet list?'))">
                            @csrf
                            @method('DELETE')
                            <button class="btn-pw danger" type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="people-ws-note">No task names yet. People can still log Leave and Partial Leave, and any task already assigned to them.</td>
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
                    <h2 class="modal-title" id="timesheetTaskModalLabel">Add a task name</h2>
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
                        <input id="timesheet_task_name" name="name" value="{{ old('form_context') === 'timesheet-task' ? old('name') : '' }}" required maxlength="120" placeholder="Client visit">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn-pw primary" type="submit" id="timesheet-task-submit">Add task name</button>
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
            title.textContent = 'Edit task name';
            submit.textContent = 'Save task name';
        } else {
            form.action = form.getAttribute('data-store');
            editing.value = '';
            title.textContent = 'Add a task name';
            submit.textContent = 'Add task name';
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
