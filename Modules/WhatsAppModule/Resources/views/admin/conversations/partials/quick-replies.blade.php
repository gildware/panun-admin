{{-- Conversation quick-reply templates (chips + reply-box search in chat UI) --}}
@php($conversationTemplates = $conversationTemplates ?? collect())

<div class="card card-body mb-3">
    <div class="row align-items-start g-2 mb-3">
        <div class="col min-w-0">
            <h5 class="h6 mb-1">{{ translate('WhatsApp_conversation_templates_heading') }}</h5>
            <p class="text-muted small mb-0">{{ translate('WhatsApp_conversation_templates_help') }}</p>
        </div>
        @can('whatsapp_message_template_update')
            <div class="col-12 col-sm-auto ms-sm-auto text-end">
                <button type="button" class="btn btn-sm btn--primary" id="waConvTplBtnAdd" data-bs-toggle="modal" data-bs-target="#waConvTplModal">
                    {{ translate('WhatsApp_conversation_template_add_button') }}
                </button>
            </div>
        @endcan
    </div>
    <p class="small mb-3">
        <code>{agent_name}</code> {{ translate('WhatsApp_conversation_templates_agent_placeholder') }}
        <code>{customer_name}</code> {{ translate('WhatsApp_conversation_templates_customer_placeholder') }}
    </p>

    @can('whatsapp_message_template_update')
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5rem;">{{ translate('Sort') }}</th>
                        <th style="min-width: 8rem;">{{ translate('Title') }}</th>
                        <th>{{ translate('Message_body') }}</th>
                        <th style="width: 6rem;" class="text-center">{{ translate('Status') }}</th>
                        <th style="width: 9rem;" class="text-end">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversationTemplates as $tpl)
                        <tr id="wa-s-qr-{{ (int) $tpl->id }}">
                            <td>{{ (int) $tpl->sort_order }}</td>
                            <td class="fw-semibold">{{ $tpl->title }}</td>
                            <td class="text-muted small text-break">{{ \Illuminate\Support\Str::limit($tpl->body, 120) }}</td>
                            <td class="text-center">
                                <form method="post" action="{{ route('admin.whatsapp.conversation-templates.toggle-active', $tpl) }}" class="d-inline">
                                    @csrf
                                    <div class="form-check form-switch d-flex justify-content-center mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               {{ !empty($tpl->is_active) ? 'checked' : '' }}
                                               onchange="this.form.requestSubmit();"
                                               title="{{ translate('Status') }}">
                                    </div>
                                </form>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary wa-conv-tpl-open-edit"
                                        data-bs-toggle="modal" data-bs-target="#waConvTplModal"
                                        data-tpl-id="{{ $tpl->id }}">
                                    {{ translate('edit') }}
                                </button>
                                <form action="{{ route('admin.whatsapp.conversation-templates.destroy', $tpl) }}" method="post" class="d-inline"
                                      onsubmit="return confirm({{ json_encode(translate('are_you_sure')) }});">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ translate('delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="modal fade" id="waConvTplModal" tabindex="-1" aria-labelledby="waConvTplModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="waConvTplModalTitle">{{ translate('WhatsApp_conversation_template_add') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                    </div>
                    <form id="waConvTplForm" method="post" action="{{ route('admin.whatsapp.conversation-templates.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="waConvTplSpoofMethod" value="" disabled autocomplete="off">
                        <input type="hidden" name="ct_edit_template_id" id="waConvTplEditId" value="{{ old('ct_edit_template_id', '') }}">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="waConvTplTitle">{{ translate('Title') }}</label>
                                <input type="text" name="ct_title" id="waConvTplTitle" class="form-control @error('ct_title') is-invalid @enderror"
                                       value="{{ old('ct_title') }}" required maxlength="191" placeholder="{{ translate('WhatsApp_conversation_template_title_placeholder') }}">
                                @error('ct_title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="waConvTplBody">{{ translate('Message_body') }}</label>
                                <textarea name="ct_body" id="waConvTplBody" class="form-control @error('ct_body') is-invalid @enderror" rows="5" required maxlength="4096"
                                          placeholder="{{ translate('WhatsApp_conversation_template_body_placeholder') }}">{{ old('ct_body') }}</textarea>
                                @error('ct_body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="waConvTplSort">{{ translate('Sort') }}</label>
                                    <input type="number" name="ct_sort_order" id="waConvTplSort" class="form-control @error('ct_sort_order') is-invalid @enderror"
                                           value="{{ old('ct_sort_order', 0) }}" min="0">
                                    @error('ct_sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">{{ translate('Status') }}</label>
                                    <input type="hidden" name="ct_is_active" value="0">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="ct_is_active" value="1" id="waConvTplActive"
                                               @checked(
                                                   !(old('ct_title') !== null || old('ct_body') !== null || old('ct_sort_order') !== null || old('ct_edit_template_id') !== null)
                                                   || (string) old('ct_is_active', '1') === '1'
                                               )>
                                        <label class="form-check-label" for="waConvTplActive">{{ translate('Active') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('cancel') }}</button>
                            <button type="submit" class="btn btn--primary" id="waConvTplSubmitBtn">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script type="application/json" id="wa-conv-tpl-json">{!! json_encode($conversationTemplates->map(static fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'body' => $t->body,
            'sort_order' => (int) $t->sort_order,
            'is_active' => (bool) ($t->is_active ?? true),
        ])->values()) !!}</script>
    @else
        @if($conversationTemplates->isEmpty())
            <p class="text-muted mb-0">{{ translate('no_data_found') }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>{{ translate('Title') }}</th><th>{{ translate('Message_body') }}</th><th>{{ translate('Status') }}</th></tr></thead>
                    <tbody>
                        @foreach($conversationTemplates as $tpl)
                            <tr>
                                <td>{{ $tpl->title }}</td>
                                <td class="text-muted small">{{ \Illuminate\Support\Str::limit($tpl->body, 120) }}</td>
                                <td>{{ !empty($tpl->is_active) ? translate('Active') : translate('Inactive') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endcan
</div>

@can('whatsapp_message_template_update')
    <script>
        (function () {
            var form = document.getElementById('waConvTplForm');
            var modalEl = document.getElementById('waConvTplModal');
            if (!form || !modalEl) return;

            var storeUrl = @json(route('admin.whatsapp.conversation-templates.store'));
            var updateBase = @json(url('admin/whatsapp/conversation-templates'));
            var jsonEl = document.getElementById('wa-conv-tpl-json');
            var payloads = [];
            try {
                payloads = jsonEl ? JSON.parse(jsonEl.textContent || '[]') : [];
            } catch (e) {
                payloads = [];
            }
            var byId = {};
            payloads.forEach(function (p) {
                byId[p.id] = p;
            });

            var spoof = document.getElementById('waConvTplSpoofMethod');
            var editIdField = document.getElementById('waConvTplEditId');
            var titleIn = document.getElementById('waConvTplTitle');
            var bodyIn = document.getElementById('waConvTplBody');
            var sortIn = document.getElementById('waConvTplSort');
            var activeIn = document.getElementById('waConvTplActive');
            var modalTitle = document.getElementById('waConvTplModalTitle');
            var submitBtn = document.getElementById('waConvTplSubmitBtn');
            var strAddTitle = {!! json_encode(translate('WhatsApp_conversation_template_add')) !!};
            var strEditTitle = {!! json_encode(translate('WhatsApp_conversation_template_modal_edit')) !!};
            var strSave = {!! json_encode(translate('Save')) !!};
            var strUpdate = {!! json_encode(translate('update')) !!};

            function openAdd() {
                form.action = storeUrl;
                if (spoof) {
                    spoof.value = '';
                    spoof.disabled = true;
                }
                if (editIdField) editIdField.value = '';
                if (titleIn) titleIn.value = '';
                if (bodyIn) bodyIn.value = '';
                if (sortIn) sortIn.value = '0';
                if (activeIn) activeIn.checked = true;
                if (modalTitle) modalTitle.textContent = strAddTitle;
                if (submitBtn) submitBtn.textContent = strSave;
            }

            function openEdit(id) {
                var p = byId[id];
                if (!p) return;
                form.action = updateBase.replace(/\/$/, '') + '/' + id;
                if (spoof) {
                    spoof.value = 'PUT';
                    spoof.disabled = false;
                }
                if (editIdField) editIdField.value = String(id);
                if (titleIn) titleIn.value = p.title || '';
                if (bodyIn) bodyIn.value = p.body || '';
                if (sortIn) sortIn.value = String(p.sort_order != null ? p.sort_order : 0);
                if (activeIn) activeIn.checked = !!p.is_active;
                if (modalTitle) modalTitle.textContent = strEditTitle;
                if (submitBtn) submitBtn.textContent = strUpdate;
            }

            modalEl.addEventListener('show.bs.modal', function (ev) {
                var t = ev.relatedTarget;
                if (!t) return;
                if (t.id === 'waConvTplBtnAdd') {
                    openAdd();
                    return;
                }
                var editBtn = t.classList.contains('wa-conv-tpl-open-edit') ? t : (t.closest ? t.closest('.wa-conv-tpl-open-edit') : null);
                if (editBtn) {
                    var id = parseInt(editBtn.getAttribute('data-tpl-id'), 10);
                    if (!isNaN(id)) openEdit(id);
                }
            });

            @if($errors->has('ct_title') || $errors->has('ct_body') || $errors->has('ct_sort_order'))
            document.addEventListener('DOMContentLoaded', function () {
                var eid = @json(old('ct_edit_template_id'));
                if (eid) {
                    form.action = updateBase.replace(/\/$/, '') + '/' + eid;
                    if (spoof) {
                        spoof.value = 'PUT';
                        spoof.disabled = false;
                    }
                }
                if (typeof bootstrap !== 'undefined' && modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
            @endif
        })();
    </script>
@endcan
