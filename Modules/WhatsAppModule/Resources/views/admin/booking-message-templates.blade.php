@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp') . ' — ' . translate('Message_templates'))

@push('css_or_js')
    <style>
        /* Admin style.css sets textarea.form-control { block-size: 5rem } — override so min-height works */
        textarea.form-control.wa-template-input {
            min-height: 400px !important;
            min-block-size: 400px !important;
            block-size: auto !important;
            height: auto !important;
            resize: vertical !important;
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
                            <p class="text-muted small mb-2">{{ translate('WhatsApp_template_status_change_hint') }}</p>
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_status_change_per_status_hint') }}</p>

                            <div class="wa-template-tabs-scroll">
                                <ul class="nav nav-pills flex-nowrap mb-2" id="waStatusSubTabs" role="tablist">
                                    @foreach($statusTemplateSegments as $i => $segment)
                                        <li class="nav-item" role="presentation">
                                            <button type="button" class="nav-link {{ $i === 0 ? 'active' : '' }}"
                                                    id="wa-status-sub-tab-{{ $segment }}"
                                                    data-bs-toggle="tab" data-bs-target="#wa-status-sub-{{ $segment }}"
                                                    role="tab" aria-controls="wa-status-sub-{{ $segment }}"
                                                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                                                {{ translate('Booking_status_tpl_' . $segment) }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="tab-content" id="waStatusSubTabContent">
                                @foreach($statusTemplateSegments as $i => $segment)
                                    @php
                                        $ck = 'booking_status_customer_' . $segment;
                                        $pk = 'booking_status_provider_' . $segment;
                                        $ick = 'booking_status_invoice_customer_' . $segment;
                                        $ipk = 'booking_status_invoice_provider_' . $segment;
                                    @endphp
                                    <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="wa-status-sub-{{ $segment }}"
                                         role="tabpanel" aria-labelledby="wa-status-sub-tab-{{ $segment }}" tabindex="0">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="tpl_{{ $ck }}">{{ translate('Customer_template') }}</label>
                                                <textarea name="{{ $ck }}" id="tpl_{{ $ck }}"
                                                          class="form-control wa-template-input"
                                                          placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old($ck, $config[$ck] ?? '') }}</textarea>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="{{ $ick }}" id="{{ $ick }}" value="1"
                                                           @checked((bool) old($ick, $config[$ick] ?? false))>
                                                    <label class="form-check-label small" for="{{ $ick }}">{{ translate('WhatsApp_send_booking_invoice_with_message') }} ({{ translate('Customer') }})</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="tpl_{{ $pk }}">{{ translate('Provider_template') }}</label>
                                                <textarea name="{{ $pk }}" id="tpl_{{ $pk }}"
                                                          class="form-control wa-template-input"
                                                          placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old($pk, $config[$pk] ?? '') }}</textarea>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="{{ $ipk }}" id="{{ $ipk }}" value="1"
                                                           @checked((bool) old($ipk, $config[$ipk] ?? false))>
                                                    <label class="form-check-label small" for="{{ $ipk }}">{{ translate('WhatsApp_send_booking_invoice_with_message') }} ({{ translate('Provider') }})</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <details class="mt-4 border rounded p-3 bg-light">
                                <summary class="fw-semibold cursor-pointer user-select-none">{{ translate('WhatsApp_status_fallback_templates') }}</summary>
                                <p class="text-muted small mt-2 mb-3">{{ translate('WhatsApp_status_fallback_templates_help') }}</p>
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
                            </details>
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
                    var nestedPane = activePane ? activePane.querySelector('#waStatusSubTabContent .tab-pane.active') : null;
                    var scope = nestedPane || activePane;
                    var inPane = scope ? scope.querySelectorAll('.wa-template-input') : [];
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
    </script>
@endpush
