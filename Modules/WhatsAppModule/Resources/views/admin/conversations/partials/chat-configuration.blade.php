{{-- Chat statuses & tags: simple lists + modals for add/edit --}}
@if(\Illuminate\Support\Facades\Schema::hasTable('whatsapp_chat_statuses'))
    @php($statuses = $chatStatusesForConfig ?? collect())
    @php($tags = $chatTagsForConfig ?? collect())
    @php($strOpen = translate('whatsapp_bucket_open'))
    @php($strClosed = translate('whatsapp_bucket_closed'))

    <div class="card card-body mb-3">
        <h5 class="h6 mb-4">{{ translate('whatsapp_chat_configuration') }}</h5>

        {{-- Statuses --}}
        <div class="mb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="text-muted text-uppercase fz-12 mb-0">{{ translate('whatsapp_chat_statuses') }}</h6>
                <button type="button"
                        class="btn btn-sm btn--primary"
                        data-bs-toggle="modal"
                        data-bs-target="#waChatStatusAddModal">
                    <span class="material-icons align-middle" style="font-size:18px;">add</span>
                    {{ translate('add_new') }}
                </button>
            </div>
            <div class="table-responsive border rounded">
                <table class="table table-hover table-borderless align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-3">{{ translate('name') }}</th>
                            <th>{{ translate('whatsapp_status_bucket') }}</th>
                            <th class="text-end">{{ translate('sort') }}</th>
                            <th class="text-end pe-3" style="width: 9rem;">{{ translate('action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statuses as $st)
                            <tr id="wa-s-cs-{{ (int) $st->id }}" class="border-bottom">
                                <td class="ps-3 fw-medium">{{ $st->name }}</td>
                                <td>
                                    @if($st->bucket === 'closed')
                                        <span class="badge bg-secondary">{{ $strClosed }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $strOpen }}</span>
                                    @endif
                                </td>
                                <td class="text-end text-muted">{{ (int) $st->sort_order }}</td>
                                <td class="text-end text-nowrap pe-3">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary wa-chat-status-edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#waChatStatusEditModal"
                                            data-update-url="{{ route('admin.whatsapp.chat-config.statuses.update', $st) }}"
                                            data-name="{{ e($st->name) }}"
                                            data-bucket="{{ e($st->bucket) }}"
                                            data-sort="{{ (int) $st->sort_order }}">
                                        {{ translate('edit') }}
                                    </button>
                                    <form method="post"
                                          action="{{ route('admin.whatsapp.chat-config.statuses.destroy', $st) }}"
                                          class="d-inline"
                                          onsubmit="return confirm({{ json_encode(translate('are_you_sure')) }});">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ translate('delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(\Illuminate\Support\Facades\Schema::hasTable('whatsapp_chat_tags'))
            <div class="border-top pt-4 mt-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="text-muted text-uppercase fz-12 mb-0">{{ translate('whatsapp_chat_tags') }}</h6>
                    <button type="button"
                            class="btn btn-sm btn--primary"
                            data-bs-toggle="modal"
                            data-bs-target="#waChatTagAddModal">
                        <span class="material-icons align-middle" style="font-size:18px;">add</span>
                        {{ translate('add_new') }}
                    </button>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table table-hover table-borderless align-middle mb-0">
                        <thead class="table-light border-bottom">
                            <tr>
                                <th class="ps-3">{{ translate('name') }}</th>
                                <th>{{ translate('whatsapp_tag_color') }}</th>
                                <th class="text-end">{{ translate('sort') }}</th>
                                <th class="text-end pe-3" style="width: 9rem;">{{ translate('action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tags as $tg)
                                @php($tc = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $tg->color) ? $tg->color : '#6c757d')
                                <tr id="wa-s-ct-{{ (int) $tg->id }}" class="border-bottom">
                                    <td class="ps-3 fw-medium">{{ $tg->name }}</td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-2">
                                            <span class="d-inline-block border rounded" style="width:1.25rem;height:1.25rem;background:{{ e($tc) }};" title="{{ e($tc) }}"></span>
                                            <span class="text-muted small font-monospace">{{ e($tc) }}</span>
                                        </span>
                                    </td>
                                    <td class="text-end text-muted">{{ (int) $tg->sort_order }}</td>
                                    <td class="text-end text-nowrap pe-3">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary wa-chat-tag-edit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#waChatTagEditModal"
                                                data-update-url="{{ route('admin.whatsapp.chat-config.tags.update', $tg) }}"
                                                data-name="{{ e($tg->name) }}"
                                                data-color="{{ e($tc) }}"
                                                data-sort="{{ (int) $tg->sort_order }}">
                                            {{ translate('edit') }}
                                        </button>
                                        <form method="post"
                                              action="{{ route('admin.whatsapp.chat-config.tags.destroy', $tg) }}"
                                              class="d-inline"
                                              onsubmit="return confirm({{ json_encode(translate('are_you_sure')) }});">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ translate('delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal: add status --}}
    <div class="modal fade" id="waChatStatusAddModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="{{ route('admin.whatsapp.chat-config.statuses.store') }}" id="waChatStatusAddForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('add_new') }} — {{ translate('whatsapp_chat_statuses') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ translate('name') }}</label>
                            <input type="text" name="status_name" class="form-control" required maxlength="191" placeholder="{{ translate('whatsapp_new_status') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('whatsapp_status_bucket') }}</label>
                            <select name="status_bucket" class="form-select" required>
                                <option value="open">{{ $strOpen }}</option>
                                <option value="closed">{{ $strClosed }}</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ translate('sort') }}</label>
                            <input type="number" name="status_sort_order" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('add_new') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: edit status --}}
    <div class="modal fade" id="waChatStatusEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="#" id="waChatStatusEditForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('edit') }} — {{ translate('whatsapp_chat_statuses') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ translate('name') }}</label>
                            <input type="text" name="status_name" id="waChatStatusEditName" class="form-control" required maxlength="191">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('whatsapp_status_bucket') }}</label>
                            <select name="status_bucket" id="waChatStatusEditBucket" class="form-select" required>
                                <option value="open">{{ $strOpen }}</option>
                                <option value="closed">{{ $strClosed }}</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ translate('sort') }}</label>
                            <input type="number" name="status_sort_order" id="waChatStatusEditSort" class="form-control" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(\Illuminate\Support\Facades\Schema::hasTable('whatsapp_chat_tags'))
        {{-- Modal: add tag --}}
        <div class="modal fade" id="waChatTagAddModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" action="{{ route('admin.whatsapp.chat-config.tags.store') }}" id="waChatTagAddForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('add_new') }} — {{ translate('whatsapp_chat_tags') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">{{ translate('name') }}</label>
                                <input type="text" name="tag_name" class="form-control" required maxlength="191" placeholder="{{ translate('whatsapp_new_tag') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ translate('whatsapp_tag_color') }}</label>
                                <input type="color" name="tag_color" class="form-control form-control-color w-100" value="#6c757d" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">{{ translate('sort') }}</label>
                                <input type="number" name="tag_sort_order" class="form-control" value="0" min="0">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('add_new') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: edit tag --}}
        <div class="modal fade" id="waChatTagEditModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" action="#" id="waChatTagEditForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('edit') }} — {{ translate('whatsapp_chat_tags') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">{{ translate('name') }}</label>
                                <input type="text" name="tag_name" id="waChatTagEditName" class="form-control" required maxlength="191">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ translate('whatsapp_tag_color') }}</label>
                                <input type="color" name="tag_color" id="waChatTagEditColor" class="form-control form-control-color w-100" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">{{ translate('sort') }}</label>
                                <input type="number" name="tag_sort_order" id="waChatTagEditSort" class="form-control" min="0">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            var addStatusModal = document.getElementById('waChatStatusAddModal');
            var addStatusForm = document.getElementById('waChatStatusAddForm');
            if (addStatusModal && addStatusForm) {
                addStatusModal.addEventListener('show.bs.modal', function () {
                    addStatusForm.reset();
                    var sort = addStatusForm.querySelector('[name="status_sort_order"]');
                    if (sort) sort.value = '0';
                });
            }

            var editStatusModal = document.getElementById('waChatStatusEditModal');
            if (editStatusModal) {
                editStatusModal.addEventListener('show.bs.modal', function (ev) {
                    var btn = ev.relatedTarget;
                    if (!btn || !btn.classList.contains('wa-chat-status-edit')) return;
                    var form = document.getElementById('waChatStatusEditForm');
                    if (!form) return;
                    form.action = btn.getAttribute('data-update-url') || '#';
                    var nameEl = document.getElementById('waChatStatusEditName');
                    var bucketEl = document.getElementById('waChatStatusEditBucket');
                    var sortEl = document.getElementById('waChatStatusEditSort');
                    if (nameEl) nameEl.value = btn.getAttribute('data-name') || '';
                    if (bucketEl) bucketEl.value = btn.getAttribute('data-bucket') === 'closed' ? 'closed' : 'open';
                    if (sortEl) sortEl.value = btn.getAttribute('data-sort') || '0';
                });
            }

            var addTagModal = document.getElementById('waChatTagAddModal');
            var addTagForm = document.getElementById('waChatTagAddForm');
            if (addTagModal && addTagForm) {
                addTagModal.addEventListener('show.bs.modal', function () {
                    addTagForm.reset();
                    var color = addTagForm.querySelector('[name="tag_color"]');
                    if (color) color.value = '#6c757d';
                    var sort = addTagForm.querySelector('[name="tag_sort_order"]');
                    if (sort) sort.value = '0';
                });
            }

            var editTagModal = document.getElementById('waChatTagEditModal');
            if (editTagModal) {
                editTagModal.addEventListener('show.bs.modal', function (ev) {
                    var btn = ev.relatedTarget;
                    if (!btn || !btn.classList.contains('wa-chat-tag-edit')) return;
                    var form = document.getElementById('waChatTagEditForm');
                    if (!form) return;
                    form.action = btn.getAttribute('data-update-url') || '#';
                    var nameEl = document.getElementById('waChatTagEditName');
                    var colorEl = document.getElementById('waChatTagEditColor');
                    var sortEl = document.getElementById('waChatTagEditSort');
                    if (nameEl) nameEl.value = btn.getAttribute('data-name') || '';
                    if (colorEl) colorEl.value = btn.getAttribute('data-color') || '#6c757d';
                    if (sortEl) sortEl.value = btn.getAttribute('data-sort') || '0';
                });
            }
        })();
    </script>
@endif
