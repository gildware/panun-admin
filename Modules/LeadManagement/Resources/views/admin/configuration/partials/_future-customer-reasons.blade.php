@php
    $configType = $configType ?? 'future_customer_reason';
    $optionConfigType = $optionConfigType ?? 'future_customer_reason_option';
    $configTitle = $configTitle ?? translate('Future_customer_reasons');
    $reasonModalId = 'config-modal-' . $configType;
    $optionModalId = 'config-modal-' . $optionConfigType;
    $optionsModalPrefix = 'reason-options-' . $configType;
@endphp

<div class="card h-100">
    <div class="card-body py-2 px-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h3 class="c1 mb-0">{{ $configTitle }}</h3>
            <button type="button"
                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                    data-bs-toggle="modal"
                    data-bs-target="#{{ $reasonModalId }}"
                    data-config-action="create"
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
                    <th>{{ translate('Active') }}</th>
                    <th class="text-end">{{ translate('Action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->name }}</td>
                        <td class="lead-config-desc" title="{{ $item->description }}">{{ $item->description ?: '—' }}</td>
                        <td class="text-center">
                            <button type="button"
                                    class="action-btn {{ $item->is_active ? 'btn--light-success' : 'btn--light-secondary' }} fw-medium"
                                    style="--size: 26px"
                                    data-bs-toggle="modal"
                                    data-bs-target="#{{ $reasonModalId }}-toggle"
                                    data-config-id="{{ $item->id }}"
                                    data-config-active="{{ $item->is_active ? 1 : 0 }}">
                                <span class="material-icons m-0">{{ $item->is_active ? 'toggle_on' : 'toggle_off' }}</span>
                            </button>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button"
                                        class="action-btn btn--light-primary fw-medium"
                                        style="--size: 26px"
                                        title="{{ translate('Follow_up_option') }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $optionsModalPrefix }}-{{ $item->id }}">
                                    <span class="material-icons m-0">list</span>
                                </button>
                                <button type="button"
                                        class="action-btn btn--light-primary fw-medium"
                                        style="--size: 26px"
                                        title="{{ translate('Edit') }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $reasonModalId }}"
                                        data-config-action="edit"
                                        data-config-id="{{ $item->id }}"
                                        data-config-title="{{ $item->name }}"
                                        data-config-description="{{ $item->description }}"
                                        data-config-followup="{{ $item->followup_label }}"
                                        data-config-active="{{ $item->is_active ? 1 : 0 }}">
                                    <span class="material-icons m-0">edit</span>
                                </button>
                                <button type="button"
                                        class="action-btn btn--light-danger fw-medium"
                                        style="--size: 26px"
                                        title="{{ translate('Delete') }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $reasonModalId }}-delete"
                                        data-config-id="{{ $item->id }}"
                                        data-config-title="{{ $item->name }}">
                                    <span class="material-icons m-0">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">{{ translate('No_data_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($items as $item)
    <div class="modal fade" id="{{ $optionsModalPrefix }}-{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">{{ $item->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">{{ $item->followup_label ?: translate('Follow_up_option') }}</span>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                data-bs-toggle="modal"
                                data-bs-target="#{{ $optionModalId }}"
                                data-config-action="create"
                                data-config-parent="{{ $item->id }}">
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
                                <th>{{ translate('Active') }}</th>
                                <th class="text-end">{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($item->options as $option)
                                <tr>
                                    <td>{{ $option->id }}</td>
                                    <td>{{ $option->name }}</td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="action-btn {{ $option->is_active ? 'btn--light-success' : 'btn--light-secondary' }} fw-medium"
                                                style="--size: 26px"
                                                data-bs-toggle="modal"
                                                data-bs-target="#{{ $optionModalId }}-toggle"
                                                data-config-id="{{ $option->id }}"
                                                data-config-active="{{ $option->is_active ? 1 : 0 }}">
                                            <span class="material-icons m-0">{{ $option->is_active ? 'toggle_on' : 'toggle_off' }}</span>
                                        </button>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <button type="button"
                                                    class="action-btn btn--light-primary fw-medium"
                                                    style="--size: 26px"
                                                    title="{{ translate('Edit') }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#{{ $optionModalId }}"
                                                    data-config-action="edit"
                                                    data-config-id="{{ $option->id }}"
                                                    data-config-parent="{{ $item->id }}"
                                                    data-config-title="{{ $option->name }}"
                                                    data-config-active="{{ $option->is_active ? 1 : 0 }}">
                                                <span class="material-icons m-0">edit</span>
                                            </button>
                                            <button type="button"
                                                    class="action-btn btn--light-danger fw-medium"
                                                    style="--size: 26px"
                                                    title="{{ translate('Delete') }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#{{ $optionModalId }}-delete"
                                                    data-config-id="{{ $option->id }}"
                                                    data-config-title="{{ $option->name }}">
                                                <span class="material-icons m-0">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">{{ translate('No_data_found') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="{{ $reasonModalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">{{ $configTitle }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form method="POST">
                @csrf
                <input type="hidden" name="type" value="{{ $configType }}">
                <input type="hidden" name="_method" value="POST">
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Title') }}</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Follow_up_question') }}</label>
                        <input type="text" name="followup_label" class="form-control" maxlength="255" placeholder="{{ translate('Which_service') }}">
                        <div class="text-muted small mt-1">{{ translate('Future_customer_followup_label_help') }}</div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        <label class="form-check-label">{{ translate('Active') }}</label>
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="{{ $reasonModalId }}-toggle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="type" value="{{ $configType }}">
                <input type="hidden" name="mode" value="toggle">
                <input type="hidden" name="is_active" value="">
                <div class="modal-body">
                    <p class="mb-0 text-muted">{{ translate('Are_you_sure_you_want_to_change_the_status_of_this_item') }}</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="{{ $reasonModalId }}-delete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" name="type" value="{{ $configType }}">
                <div class="modal-body">
                    <p class="mb-0 text-muted">{{ translate('Are_you_sure_you_want_to_delete_this_item') }}</p>
                    <p class="mb-0 fw-semibold mt-2" data-delete-title></p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ translate('Delete') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="{{ $optionModalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">{{ translate('Follow_up_option') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form method="POST">
                @csrf
                <input type="hidden" name="type" value="{{ $optionConfigType }}">
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="parent_id" value="">
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Title') }}</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1">
                        <label class="form-check-label">{{ translate('Active') }}</label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="{{ $optionModalId }}-toggle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="type" value="{{ $optionConfigType }}">
                <input type="hidden" name="mode" value="toggle">
                <input type="hidden" name="is_active" value="">
                <div class="modal-body">
                    <p class="mb-0 text-muted">{{ translate('Are_you_sure_you_want_to_change_the_status_of_this_item') }}</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="{{ $optionModalId }}-delete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" name="type" value="{{ $optionConfigType }}">
                <div class="modal-body">
                    <p class="mb-0 text-muted">{{ translate('Are_you_sure_you_want_to_delete_this_item') }}</p>
                    <p class="mb-0 fw-semibold mt-2" data-delete-title></p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ translate('Delete') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
    <script>
        (function () {
            const updateUrl = @json(route('admin.lead.configuration.update', '__id__'));
            const destroyUrl = @json(route('admin.lead.configuration.destroy', '__id__'));
            const storeUrl = @json(route('admin.lead.configuration.store'));

            function bindEditor(modalId) {
                const modal = document.getElementById(modalId);
                if (!modal) return;
                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;
                    const form = modal.querySelector('form');
                    const action = button.getAttribute('data-config-action') || 'edit';
                    const id = button.getAttribute('data-config-id');
                    const methodInput = form.querySelector('input[name="_method"]');
                    form.querySelector('input[name="title"]').value = button.getAttribute('data-config-title') || '';
                    const description = form.querySelector('textarea[name="description"]');
                    if (description) description.value = button.getAttribute('data-config-description') || '';
                    const followup = form.querySelector('input[name="followup_label"]');
                    if (followup) followup.value = button.getAttribute('data-config-followup') || '';
                    const parent = form.querySelector('input[name="parent_id"]');
                    if (parent) parent.value = button.getAttribute('data-config-parent') || '';
                    const active = form.querySelector('input[name="is_active"]');
                    if (active) active.checked = button.getAttribute('data-config-active') === '1';
                    if (action === 'edit' && id) {
                        form.action = updateUrl.replace('__id__', id);
                        if (methodInput) methodInput.value = 'PUT';
                    } else {
                        form.action = storeUrl;
                        if (methodInput) methodInput.value = 'POST';
                        form.querySelector('input[name="title"]').value = '';
                        if (description) description.value = '';
                        if (followup) followup.value = '';
                        if (active) active.checked = true;
                    }
                });
            }

            function bindToggle(modalId) {
                const modal = document.getElementById(modalId);
                if (!modal) return;
                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;
                    const form = modal.querySelector('form');
                    form.action = updateUrl.replace('__id__', button.getAttribute('data-config-id'));
                    form.querySelector('input[name="is_active"]').value = button.getAttribute('data-config-active') === '1' ? '0' : '1';
                });
            }

            function bindDelete(modalId) {
                const modal = document.getElementById(modalId);
                if (!modal) return;
                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;
                    const form = modal.querySelector('form');
                    form.action = destroyUrl.replace('__id__', button.getAttribute('data-config-id'));
                    const title = modal.querySelector('[data-delete-title]');
                    if (title) title.textContent = button.getAttribute('data-config-title') || '';
                });
            }

            bindEditor('{{ $reasonModalId }}');
            bindEditor('{{ $optionModalId }}');
            bindToggle('{{ $reasonModalId }}-toggle');
            bindToggle('{{ $optionModalId }}-toggle');
            bindDelete('{{ $reasonModalId }}-delete');
            bindDelete('{{ $optionModalId }}-delete');
        })();
    </script>
@endpush
