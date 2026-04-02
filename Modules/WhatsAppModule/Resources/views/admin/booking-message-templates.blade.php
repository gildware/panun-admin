@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp') . ' — ' . translate('Message_templates'))

@push('css_or_js')
    <style>
        .wa-template-input {
            min-height: 300px;
            resize: vertical;
        }
        .wa-template-tabs-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }
        .wa-template-tabs-scroll .nav {
            flex-wrap: nowrap;
        }
        .wa-template-tabs-scroll .nav-item {
            flex-shrink: 0;
        }
        .wa-template-tabs-scroll .nav-link {
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row mb-3 align-items-start">
            <div class="col min-w-0">
                <h2 class="h4 mb-1">{{ translate('Message_templates') }}</h2>
                <p class="text-muted mb-0">{{ translate('WhatsApp_booking_template_help') }}</p>
            </div>
            <div class="col-auto flex-shrink-0 text-end mt-1 mt-md-0">
                <div class="d-flex flex-column align-items-end gap-1">
                    <span class="small text-muted text-end" style="max-width: 18rem;">{{ translate('Send_booking_WhatsApp_messages') }}</span>
                    @can('whatsapp_message_template_update')
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2"
                                id="waBookingEnabledOpenModal"
                                aria-haspopup="dialog">
                            <span class="form-check form-switch mb-0">
                                <input class="form-check-input flex-shrink-0" type="checkbox" role="switch" disabled
                                       style="opacity: 1; cursor: inherit;"
                                       {{ !empty($config['enabled']) ? 'checked' : '' }}
                                       aria-hidden="true" tabindex="-1">
                            </span>
                            <span class="badge {{ !empty($config['enabled']) ? 'bg-success' : 'bg-secondary' }}">
                                {{ !empty($config['enabled']) ? translate('on') : translate('WhatsApp_off') }}
                            </span>
                        </button>
                    @else
                        <span class="badge {{ !empty($config['enabled']) ? 'bg-success' : 'bg-secondary' }}">
                            {{ !empty($config['enabled']) ? translate('on') : translate('WhatsApp_off') }}
                        </span>
                    @endcan
                </div>
            </div>
        </div>

        @can('whatsapp_message_template_update')
            <div class="modal fade" id="waBookingEnabledConfirmModal" tabindex="-1" aria-labelledby="waBookingEnabledConfirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="waBookingEnabledConfirmModalLabel">{{ translate('WhatsApp_booking_messages_modal_title') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                        </div>
                        <div class="modal-body" id="waBookingEnabledModalBody"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('cancel') }}</button>
                            <form action="{{ route('admin.whatsapp.booking-templates.toggle-enabled') }}" method="post" class="d-inline">
                                @csrf
                                <input type="hidden" name="enabled" id="waBookingEnabledModalValue" value="">
                                <button type="submit" class="btn btn--primary">{{ translate('Yes') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        <form action="{{ route('admin.whatsapp.booking-templates.update') }}" method="post">
            @csrf

            <div class="accordion mb-3" id="waBookingTplVarsAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="waBookingTplVarsHeading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#waBookingTplVarsCollapse" aria-expanded="false"
                                aria-controls="waBookingTplVarsCollapse">
                            {{ translate('Available_variables') }}
                        </button>
                    </h2>
                    <div id="waBookingTplVarsCollapse" class="accordion-collapse collapse" aria-labelledby="waBookingTplVarsHeading"
                         data-bs-parent="#waBookingTplVarsAccordion">
                        <div class="accordion-body">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($placeholders as $token => $label)
                                    <button type="button" class="btn btn-sm btn-outline-secondary js-insert-placeholder"
                                            data-token="{{ $token }}" title="{{ $label }}">
                                        {{ $token }}
                                    </button>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-2">{{ translate('Click_to_insert_at_cursor') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="wa-template-tabs-scroll">
                    <ul class="nav nav--tabs flex-nowrap" id="waBookingTemplateTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="wa-tpl-tab-new-booking" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-new-booking" href="#wa-tpl-pane-new-booking"
                               role="tab" aria-controls="wa-tpl-pane-new-booking" aria-selected="true">
                                {{ translate('WhatsApp_tab_new_booking') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="wa-tpl-tab-status" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-status" href="#wa-tpl-pane-status"
                               role="tab" aria-controls="wa-tpl-pane-status" aria-selected="false">
                                {{ translate('WhatsApp_tab_booking_status_changed') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="wa-tpl-tab-provider-change" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-provider-change" href="#wa-tpl-pane-provider-change"
                               role="tab" aria-controls="wa-tpl-pane-provider-change" aria-selected="false">
                                {{ translate('WhatsApp_tab_provider_change') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="wa-tpl-tab-schedule" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-schedule" href="#wa-tpl-pane-schedule"
                               role="tab" aria-controls="wa-tpl-pane-schedule" aria-selected="false">
                                {{ translate('WhatsApp_tab_schedule_change') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="wa-tpl-tab-payment" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-payment" href="#wa-tpl-pane-payment"
                               role="tab" aria-controls="wa-tpl-pane-payment" aria-selected="false">
                                {{ translate('WhatsApp_tab_payment_change') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="wa-tpl-tab-serviceman" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-serviceman" href="#wa-tpl-pane-serviceman"
                               role="tab" aria-controls="wa-tpl-pane-serviceman" aria-selected="false">
                                {{ translate('WhatsApp_tab_serviceman_change') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="wa-tpl-tab-verification" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-verification" href="#wa-tpl-pane-verification"
                               role="tab" aria-controls="wa-tpl-pane-verification" aria-selected="false">
                                {{ translate('WhatsApp_tab_verification_change') }}
                            </a>
                        </li>
                    </ul>
                    </div>

                    <div class="tab-content" id="waBookingTemplateTabContent">
                        <div class="tab-pane fade show active" id="wa-tpl-pane-new-booking" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-new-booking" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_new_booking_hint') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_confirmation_customer">{{ translate('Customer_template') }}</label>
                                    <textarea name="booking_confirmation_customer" id="tpl_booking_confirmation_customer"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_confirmation_customer', $config['booking_confirmation_customer'] ?? '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_confirmation_provider">{{ translate('Provider_template') }}</label>
                                    <textarea name="booking_confirmation_provider" id="tpl_booking_confirmation_provider"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_confirmation_provider', $config['booking_confirmation_provider'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="wa-tpl-pane-status" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-status" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_status_change_hint') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_status_customer">{{ translate('Customer_template') }}</label>
                                    <textarea name="booking_status_customer" id="tpl_booking_status_customer"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_status_customer', $config['booking_status_customer'] ?? '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_status_provider">{{ translate('Provider_template') }}</label>
                                    <textarea name="booking_status_provider" id="tpl_booking_status_provider"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_status_provider', $config['booking_status_provider'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="wa-tpl-pane-provider-change" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-provider-change" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_provider_change_hint') }}</p>
                            <div class="row g-3">
                                <div class="col-lg-4 col-md-12">
                                    <label class="form-label" for="tpl_provider_change_customer">{{ translate('Customer_template') }}</label>
                                    <textarea name="provider_change_customer" id="tpl_provider_change_customer"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('provider_change_customer', $config['provider_change_customer'] ?? '') }}</textarea>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <label class="form-label" for="tpl_provider_change_previous_provider">{{ translate('Previous_provider_template') }}</label>
                                    <textarea name="provider_change_previous_provider" id="tpl_provider_change_previous_provider"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('provider_change_previous_provider', $config['provider_change_previous_provider'] ?? '') }}</textarea>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <label class="form-label" for="tpl_provider_change_new_provider">{{ translate('New_assigned_provider_template') }}</label>
                                    <textarea name="provider_change_new_provider" id="tpl_provider_change_new_provider"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('provider_change_new_provider', $config['provider_change_new_provider'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="wa-tpl-pane-schedule" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-schedule" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_schedule_change_hint') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_schedule_customer">{{ translate('Customer_template') }}</label>
                                    <textarea name="booking_schedule_customer" id="tpl_booking_schedule_customer"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_schedule_customer', $config['booking_schedule_customer'] ?? '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_schedule_provider">{{ translate('Provider_template') }}</label>
                                    <textarea name="booking_schedule_provider" id="tpl_booking_schedule_provider"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_schedule_provider', $config['booking_schedule_provider'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="wa-tpl-pane-payment" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-payment" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_payment_change_hint') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_payment_customer">{{ translate('Customer_template') }}</label>
                                    <textarea name="booking_payment_customer" id="tpl_booking_payment_customer"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_payment_customer', $config['booking_payment_customer'] ?? '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_payment_provider">{{ translate('Provider_template') }}</label>
                                    <textarea name="booking_payment_provider" id="tpl_booking_payment_provider"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_payment_provider', $config['booking_payment_provider'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="wa-tpl-pane-serviceman" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-serviceman" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_serviceman_change_hint') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_serviceman_customer">{{ translate('Customer_template') }}</label>
                                    <textarea name="booking_serviceman_customer" id="tpl_booking_serviceman_customer"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_serviceman_customer', $config['booking_serviceman_customer'] ?? '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_serviceman_provider">{{ translate('Provider_template') }}</label>
                                    <textarea name="booking_serviceman_provider" id="tpl_booking_serviceman_provider"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_serviceman_provider', $config['booking_serviceman_provider'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="wa-tpl-pane-verification" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-verification" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_verification_change_hint') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_verification_customer">{{ translate('Customer_template') }}</label>
                                    <textarea name="booking_verification_customer" id="tpl_booking_verification_customer"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_verification_customer', $config['booking_verification_customer'] ?? '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_verification_provider">{{ translate('Provider_template') }}</label>
                                    <textarea name="booking_verification_provider" id="tpl_booking_verification_provider"
                                              class="form-control wa-template-input"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_verification_provider', $config['booking_verification_provider'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn--primary">{{ translate('update') }}</button>
            <a href="{{ route('admin.whatsapp.conversations.index') }}" class="btn btn-secondary">{{ translate('cancel') }}</a>
        </form>

        <div class="card mb-3 mt-4">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <strong>{{ translate('WhatsApp_conversation_templates_heading') }}</strong>
                @can('whatsapp_message_template_update')
                    <button type="button" class="btn btn-sm btn--primary" id="waConvTplBtnAdd" data-bs-toggle="modal" data-bs-target="#waConvTplModal">
                        {{ translate('WhatsApp_conversation_template_add_button') }}
                    </button>
                @endcan
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">{{ translate('WhatsApp_conversation_templates_help') }}</p>
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
                                @forelse($conversationTemplates ?? [] as $tpl)
                                    <tr>
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

                    <script type="application/json" id="wa-conv-tpl-json">{!! json_encode(($conversationTemplates ?? collect())->map(static fn ($t) => [
                        'id' => $t->id,
                        'title' => $t->title,
                        'body' => $t->body,
                        'sort_order' => (int) $t->sort_order,
                        'is_active' => (bool) ($t->is_active ?? true),
                    ])->values()) !!}</script>
                @else
                    @if(($conversationTemplates ?? collect())->isEmpty())
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
        </div>
    </div>
@endsection

@push('script')
    @php
        $__waBookingEnabledJs = !empty($config['enabled']);
    @endphp
    <script>
        var waBookingEnabledCurrentlyOn = @json($__waBookingEnabledJs);

        (function () {
            var openBtn = document.getElementById('waBookingEnabledOpenModal');
            var modalEl = document.getElementById('waBookingEnabledConfirmModal');
            if (!openBtn || !modalEl || typeof bootstrap === 'undefined') {
                return;
            }
            var bodyEl = document.getElementById('waBookingEnabledModalBody');
            var valEl = document.getElementById('waBookingEnabledModalValue');
            var enableText = {!! json_encode(translate('WhatsApp_booking_messages_modal_enable_body')) !!};
            var disableText = {!! json_encode(translate('WhatsApp_booking_messages_modal_disable_body')) !!};

            openBtn.addEventListener('click', function () {
                var nextOn = !waBookingEnabledCurrentlyOn;
                if (valEl) {
                    valEl.value = nextOn ? '1' : '0';
                }
                if (bodyEl) {
                    bodyEl.textContent = nextOn ? enableText : disableText;
                }
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });
        })();

        document.querySelectorAll('.js-insert-placeholder').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var token = btn.getAttribute('data-token');
                var areas = document.querySelectorAll('.wa-template-input');
                var ta = document.activeElement;
                if (!ta || !ta.classList || !ta.classList.contains('wa-template-input')) {
                    var activePane = document.querySelector('#waBookingTemplateTabContent .tab-pane.active');
                    var inPane = activePane ? activePane.querySelectorAll('.wa-template-input') : [];
                    ta = inPane.length ? inPane[0] : areas[0];
                }
                if (!ta) return;
                var start = ta.selectionStart || 0;
                var end = ta.selectionEnd || 0;
                var val = ta.value;
                ta.value = val.slice(0, start) + token + val.slice(end);
                ta.focus();
                var pos = start + token.length;
                ta.setSelectionRange(pos, pos);
            });
        });

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
@endpush
