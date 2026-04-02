@php
    $modalId = 'bkg-config-modal-' . $type;
    $responsibleLabels = [
        'customer' => translate('Customer'),
        'provider' => translate('Provider'),
        'staff' => translate('Staff'),
        'no_one' => translate('No_one'),
    ];
@endphp

<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="c1 mb-0">{{ $title }}</h3>
            <button type="button"
                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                    data-bs-toggle="modal"
                    data-bs-target="#{{ $modalId }}"
                    data-config-action="create"
                    data-config-id=""
                    title="{{ translate('Add_New') }}">
                <span class="material-icons">add</span>
                <span>{{ translate('Add_New') }}</span>
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Title') }}</th>
                    <th>{{ translate('Description') }}</th>
                    <th>{{ translate('Responsible') }}</th>
                    <th>{{ translate('Active') }}</th>
                    <th class="text-end">{{ translate('Action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->description ?? '—' }}</td>
                        <td class="text-capitalize">{{ $responsibleLabels[$item->responsible] ?? $item->responsible }}</td>
                        <td class="text-center">
                            <button type="button"
                                    class="action-btn {{ $item->is_active ? 'btn--light-success' : 'btn--light-secondary' }} fw-medium"
                                    style="--size: 40px"
                                    data-bs-toggle="modal"
                                    data-bs-target="#{{ $modalId }}-toggle"
                                    data-config-id="{{ $item->id }}"
                                    data-config-active="{{ $item->is_active ? 1 : 0 }}">
                                @if($item->is_active)
                                    <span class="material-icons m-0">toggle_on</span>
                                @else
                                    <span class="material-icons m-0">toggle_off</span>
                                @endif
                            </button>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button"
                                        class="action-btn btn--light-primary fw-medium"
                                        style="--size: 30px"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $modalId }}"
                                        data-config-action="edit"
                                        data-config-id="{{ $item->id }}"
                                        data-config-title="{{ $item->name }}"
                                        data-config-description="{{ $item->description }}"
                                        data-config-responsible="{{ $item->responsible }}"
                                        data-config-active="{{ $item->is_active ? 1 : 0 }}"
                                        title="{{ translate('Edit') }}">
                                    <span class="material-icons m-0">edit</span>
                                </button>
                                <button type="button"
                                        class="action-btn btn--light-danger fw-medium"
                                        style="--size: 30px"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $modalId }}-delete"
                                        data-config-id="{{ $item->id }}"
                                        data-config-title="{{ $item->name }}"
                                        title="{{ translate('Delete') }}">
                                    <span class="material-icons m-0">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">{{ translate('No_data_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="{{ $modalId }}Label">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form method="POST" id="{{ $modalId }}-form">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="_method" value="POST">
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Title') }}</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Description') }}</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Responsible') }}</label>
                        <select name="responsible" class="form-select" required>
                            @foreach($responsibleLabels as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="{{ $modalId }}-active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="{{ $modalId }}-active">{{ translate('Active') }}</label>
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn--primary">
                        {{ translate('Save_changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="{{ $modalId }}-toggle" tabindex="-1" aria-labelledby="{{ $modalId }}ToggleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="{{ $modalId }}ToggleLabel">{{ translate('Change_Status') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form method="POST" id="{{ $modalId }}-toggle-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="mode" value="toggle">
                <input type="hidden" name="is_active" value="">
                <div class="modal-body pt-0">
                    <p class="mb-0 text-muted">
                        {{ translate('Are_you_sure_you_want_to_change_the_status_of_this_item') }}
                    </p>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn--primary">
                        {{ translate('Confirm') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="{{ $modalId }}-delete" tabindex="-1" aria-labelledby="{{ $modalId }}DeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger" id="{{ $modalId }}DeleteLabel">{{ translate('Delete') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form method="POST" id="{{ $modalId }}-delete-form">
                @csrf
                @method('DELETE')
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="modal-body pt-0">
                    <p class="mb-0 text-muted">
                        {{ translate('Are_you_sure_you_want_to_delete_this_item') }}
                    </p>
                    <p class="mb-0 fw-semibold mt-2" id="{{ $modalId }}-delete-title"></p>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-danger">
                        {{ translate('Delete') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
    <script>
        (function () {
            const modal = document.getElementById('{{ $modalId }}');
            const deleteModal = document.getElementById('{{ $modalId }}-delete');
            const toggleModal = document.getElementById('{{ $modalId }}-toggle');

            if (modal) {
                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const action = button.getAttribute('data-config-action') || 'create';
                    const id = button.getAttribute('data-config-id');
                    const title = button.getAttribute('data-config-title') || '';
                    const description = button.getAttribute('data-config-description') || '';
                    const responsible = button.getAttribute('data-config-responsible') || 'customer';
                    const isActive = button.getAttribute('data-config-active') === '1';

                    const form = modal.querySelector('form');
                    const methodInput = form.querySelector('input[name="_method"]');
                    const titleInput = form.querySelector('input[name="title"]');
                    const descriptionInput = form.querySelector('textarea[name="description"]');
                    const activeInput = form.querySelector('input[name="is_active"]');
                    const responsibleInput = form.querySelector('select[name="responsible"]');

                    if (action === 'edit' && id) {
                        form.action = '{{ route('admin.booking.configuration.update', '__id__') }}'.replace('__id__', id);
                        methodInput.value = 'PUT';
                        titleInput.value = title;
                        descriptionInput.value = description || '';
                        activeInput.checked = isActive;
                        if (responsibleInput) {
                            responsibleInput.value = responsible;
                        }
                    } else {
                        form.action = '{{ route('admin.booking.configuration.store') }}';
                        methodInput.value = 'POST';
                        titleInput.value = '';
                        descriptionInput.value = '';
                        activeInput.checked = true;
                        if (responsibleInput) {
                            responsibleInput.value = 'customer';
                        }
                    }
                });
            }

            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const id = button.getAttribute('data-config-id');
                    const title = button.getAttribute('data-config-title') || '';

                    const form = deleteModal.querySelector('form');
                    const titleTarget = document.getElementById('{{ $modalId }}-delete-title');

                    form.action = '{{ route('admin.booking.configuration.destroy', '__id__') }}'.replace('__id__', id);
                    if (titleTarget) {
                        titleTarget.textContent = title;
                    }
                });
            }

            if (toggleModal) {
                toggleModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const id = button.getAttribute('data-config-id');
                    const isActive = button.getAttribute('data-config-active') === '1';

                    const form = toggleModal.querySelector('form');
                    const isActiveInput = form.querySelector('input[name="is_active"]');

                    form.action = '{{ route('admin.booking.configuration.update', '__id__') }}'.replace('__id__', id);
                    isActiveInput.value = isActive ? 0 : 1;
                });
            }
        })();
    </script>
@endpush

@push('style')
    <style>
        .action-btn .material-icons {
            font-size: 22px;
        }
    </style>
@endpush
