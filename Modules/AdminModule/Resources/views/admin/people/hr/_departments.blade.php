<div class="people-ws-head">
    <div>
        <h1>Departments</h1>
        <p>Add the departments people can be placed in. A department stays until nobody is assigned to it.</p>
    </div>
    <div class="people-ws-head-actions">
        <button class="btn-pw primary" type="button" data-department-add>Add department</button>
    </div>
</div>

<article class="people-ws-card people-ws-scroll">
    <table class="people-dept-table">
        <thead>
            <tr>
                <th>Department</th>
                <th>People</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($departments as $department)
            @php $count = (int) ($departmentCounts[$department->name] ?? 0); @endphp
            <tr>
                <td>{{ $department->name }}</td>
                <td>{{ $count }} {{ $count === 1 ? 'person' : 'people' }}</td>
                <td>
                    <div class="people-dept-actions">
                        <button
                            class="btn-pw"
                            type="button"
                            data-department-edit
                            data-id="{{ $department->id }}"
                            data-name="{{ $department->name }}"
                        >Edit</button>
                        <form method="post" action="{{ route('admin.hr.departments.destroy', $department) }}" onsubmit="return confirm(@json('Delete '.$department->name.'?'))">
                            @csrf
                            @method('DELETE')
                            <button class="btn-pw danger" type="submit" @if($count > 0) title="Move people out of this department before you delete it." @endif>Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="people-ws-note">No departments yet.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</article>

<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content people-ws-dept-modal">
            <form
                id="department-form"
                method="post"
                action="{{ route('admin.hr.departments.store') }}"
                data-store="{{ route('admin.hr.departments.store') }}"
                data-update="{{ route('admin.hr.departments.update', ['department' => '__ID__']) }}"
            >
                @csrf
                <input type="hidden" name="editing_id" id="department-editing-id" value="{{ old('editing_id') }}">
                <div class="modal-header">
                    <h2 class="modal-title" id="departmentModalLabel">Add a department</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                        <ul class="people-ws-errors">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="field">
                        <label for="department_name">Name</label>
                        <input id="department_name" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Operations">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn-pw primary" type="submit" id="department-submit">Add department</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
(function () {
    var form = document.getElementById('department-form');
    var title = document.getElementById('departmentModalLabel');
    var submit = document.getElementById('department-submit');
    var nameInput = document.getElementById('department_name');
    var editing = document.getElementById('department-editing-id');
    var modalEl = document.getElementById('departmentModal');
    if (!form || !modalEl || !window.bootstrap) {
        return;
    }

    function openDepartment(id, name) {
        nameInput.value = name || '';
        if (id) {
            form.action = form.getAttribute('data-update').replace('__ID__', id);
            editing.value = id;
            title.textContent = 'Edit department';
            submit.textContent = 'Save department';
        } else {
            form.action = form.getAttribute('data-store');
            editing.value = '';
            title.textContent = 'Add a department';
            submit.textContent = 'Add department';
        }
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.addEventListener('click', function (event) {
        var edit = event.target.closest('[data-department-edit]');
        if (edit) {
            openDepartment(edit.getAttribute('data-id'), edit.getAttribute('data-name'));
            return;
        }
        if (event.target.closest('[data-department-add]')) {
            openDepartment('', '');
        }
    });

    @if($errors->any() || old('name') !== null || old('editing_id'))
    openDepartment(@json(old('editing_id')), @json(old('name')));
    @endif
})();
</script>
@endpush
