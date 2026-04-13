@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp') . ' — ' . translate('Message_templates'))

@push('css_or_js')
    <style>
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
        .wa-booking-map-table th {
            font-size: 0.75rem;
            font-weight: 600;
        }
        .wa-booking-map-table td .select2-container {
            min-width: 12rem;
        }
        .wa-booking-meta-wrap .select2-container {
            min-width: 0;
            width: 100% !important;
            max-width: 100% !important;
        }
        /* Above admin sidebar/modals while choosing templates */
        .wa-booking-meta-wrap .select2-container--open {
            z-index: 2005;
        }
        #waBookingTplVarsCollapse .select2-dropdown.select2-wa-booking-var-dd,
        body > .select2-container .select2-dropdown.select2-wa-booking-var-dd {
            z-index: 2010 !important;
        }
        body > .select2-container .select2-dropdown.select2-wa-booking-var-dd {
            max-width: min(36rem, calc(100vw - 1rem));
            box-sizing: border-box;
        }
        .select2-wa-booking-var-dd .select2-search--dropdown {
            padding: 0.5rem 0.5rem 0.25rem;
        }
        .select2-wa-booking-var-dd .select2-search__field {
            width: 100% !important;
            min-height: 2.25rem;
        }
        .select2-wa-booking-var-dd .select2-results > .select2-results__options {
            max-height: 12.5rem !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }
        .select2-wa-booking-var-dd .select2-results__option {
            padding: 0.35rem 0.5rem;
        }
        .select2-wa-booking-var-dd .select2-results__options > .select2-results__option:not(.select2-results__message):nth-child(odd) {
            background-color: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.04);
        }
        .select2-wa-booking-var-dd .select2-results__options > .select2-results__option:not(.select2-results__message):nth-child(even) {
            background-color: rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.09);
        }
        .select2-wa-booking-var-dd .select2-results__option--highlighted {
            background-color: var(--bs-primary) !important;
            color: var(--bs-primary-color, #fff) !important;
        }
        .select2-wa-booking-var-dd .select2-results__option--highlighted .wa-booking-var-opt-module,
        .select2-wa-booking-var-dd .select2-results__option--highlighted .wa-booking-var-opt-lbl {
            color: inherit !important;
            opacity: 0.92;
        }
        .select2-wa-booking-var-dd .select2-results__option .wa-booking-var-opt-row {
            position: relative;
            width: 100%;
            min-height: 2.25rem;
        }
        .select2-wa-booking-var-dd .select2-results__option .wa-booking-var-opt-main {
            padding-right: 1.85rem;
        }
        .select2-wa-booking-var-dd .select2-results__option[aria-selected=true]::after {
            display: none !important;
        }
        .select2-wa-booking-var-dd .select2-results__option .wa-booking-var-guide-btn {
            position: absolute;
            top: 0.15rem;
            right: 0.2rem;
            z-index: 3;
            line-height: 1;
            min-width: 1.65rem;
            min-height: 1.65rem;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
            border-radius: 50%;
            border: 1px solid rgba(var(--bs-body-color-rgb, 33, 37, 41), 0.22);
            background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.92);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
            color: var(--bs-body-color, #212529) !important;
        }
        .select2-wa-booking-var-dd .select2-results__option .wa-booking-var-guide-btn .wa-booking-var-info-svg {
            display: block;
            flex-shrink: 0;
            opacity: 0.85;
        }
        .select2-wa-booking-var-dd .select2-results__option--highlighted .wa-booking-var-guide-btn {
            border-color: rgba(255, 255, 255, 0.45) !important;
            background: rgba(255, 255, 255, 0.2) !important;
            color: inherit !important;
        }
        .select2-wa-booking-var-dd .select2-results__option--highlighted .wa-booking-var-guide-btn .wa-booking-var-info-svg {
            opacity: 0.95;
        }
        .popover.wa-booking-var-popover {
            max-width: min(22rem, 92vw);
            font-size: 0.8125rem;
            z-index: 1080 !important;
        }
        .popover.wa-booking-var-popover .popover-header {
            font-family: var(--bs-font-monospace, ui-monospace, monospace);
            font-size: 0.75rem;
            word-break: break-all;
            padding: 0.4rem 0.6rem;
        }
        .popover.wa-booking-var-popover .popover-body {
            line-height: 1.45;
            text-align: start;
            max-height: 70vh;
            overflow-y: auto;
        }
        .select2-wa-booking-var-dd .select2-results__option .wa-booking-var-opt-token {
            font-size: 0.8125rem;
            word-break: break-all;
            line-height: 1.3;
        }
        .select2-wa-booking-var-dd .select2-results__option .wa-booking-var-opt-module {
            font-size: 0.78rem;
            color: var(--bs-secondary-color, #6c757d);
            margin-top: 0.2rem;
            line-height: 1.35;
            font-weight: 500;
        }
        .select2-wa-booking-var-dd .select2-results__option .wa-booking-var-opt-lbl {
            font-size: 0.65rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: var(--bs-secondary-color, #6c757d);
            margin-bottom: 0.1rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row mb-3 align-items-start">
            <div class="col min-w-0">
                <h2 class="h4 mb-1">{{ translate('Message_templates') }}</h2>
                <p class="text-muted mb-0">{{ translate('WhatsApp_booking_template_help_meta_only') }}</p>
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
            <div class="modal fade" id="waBookingMsgSendConfirmModal" tabindex="-1" aria-labelledby="waBookingMsgSendConfirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="waBookingMsgSendConfirmModalLabel">{{ translate('WhatsApp_booking_single_message_modal_title') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                        </div>
                        <div class="modal-body" id="waBookingMsgSendModalBody"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('cancel') }}</button>
                            <form action="{{ route('admin.whatsapp.booking-templates.toggle-message-send-enabled') }}" method="post" class="d-inline" id="waBookingMsgSendConfirmForm">
                                @csrf
                                <input type="hidden" name="message_key" id="waBookingMsgSendModalKey" value="">
                                <input type="hidden" name="enabled" id="waBookingMsgSendModalValue" value="">
                                <input type="hidden" name="wa_active_main_tab" id="waBookingMsgSendModalMainTab" value="">
                                <input type="hidden" name="wa_active_status_segment" id="waBookingMsgSendModalStatusSeg" value="">
                                <button type="submit" class="btn btn--primary">{{ translate('Yes') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        <form id="wa-booking-templates-form" action="{{ route('admin.whatsapp.booking-templates.update') }}" method="post">
            @csrf
            <input type="hidden" name="wa_active_main_tab" id="waActiveMainTab" value="{{ $waActiveMainTab }}">
            <input type="hidden" name="wa_active_status_segment" id="waActiveStatusSegment" value="{{ $waActiveStatusSegment }}">

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
                            <p class="small text-muted mb-2">{{ translate('WhatsApp_booking_variables_reference_hint') }}</p>
                            <p class="small text-muted mb-0">{{ translate('WhatsApp_booking_copy_token_hint') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="wa-template-tabs-scroll">
                    <ul class="nav nav--tabs flex-nowrap" id="waBookingTemplateTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $waActiveMainTab === 'new-booking' ? 'active' : '' }}" id="wa-tpl-tab-new-booking" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-new-booking" href="#wa-tpl-pane-new-booking"
                               role="tab" aria-controls="wa-tpl-pane-new-booking" aria-selected="{{ $waActiveMainTab === 'new-booking' ? 'true' : 'false' }}">
                                {{ translate('WhatsApp_tab_new_booking') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $waActiveMainTab === 'status' ? 'active' : '' }}" id="wa-tpl-tab-status" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-status" href="#wa-tpl-pane-status"
                               role="tab" aria-controls="wa-tpl-pane-status" aria-selected="{{ $waActiveMainTab === 'status' ? 'true' : 'false' }}">
                                {{ translate('WhatsApp_tab_booking_status_changed') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $waActiveMainTab === 'provider-change' ? 'active' : '' }}" id="wa-tpl-tab-provider-change" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-provider-change" href="#wa-tpl-pane-provider-change"
                               role="tab" aria-controls="wa-tpl-pane-provider-change" aria-selected="{{ $waActiveMainTab === 'provider-change' ? 'true' : 'false' }}">
                                {{ translate('WhatsApp_tab_provider_change') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $waActiveMainTab === 'schedule' ? 'active' : '' }}" id="wa-tpl-tab-schedule" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-schedule" href="#wa-tpl-pane-schedule"
                               role="tab" aria-controls="wa-tpl-pane-schedule" aria-selected="{{ $waActiveMainTab === 'schedule' ? 'true' : 'false' }}">
                                {{ translate('WhatsApp_tab_schedule_change') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $waActiveMainTab === 'payment' ? 'active' : '' }}" id="wa-tpl-tab-payment" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-payment" href="#wa-tpl-pane-payment"
                               role="tab" aria-controls="wa-tpl-pane-payment" aria-selected="{{ $waActiveMainTab === 'payment' ? 'true' : 'false' }}">
                                {{ translate('WhatsApp_tab_payment_change') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $waActiveMainTab === 'ledger-payments' ? 'active' : '' }}" id="wa-tpl-tab-ledger-payments" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-ledger-payments" href="#wa-tpl-pane-ledger-payments"
                               role="tab" aria-controls="wa-tpl-pane-ledger-payments" aria-selected="{{ $waActiveMainTab === 'ledger-payments' ? 'true' : 'false' }}">
                                {{ translate('WhatsApp_tab_ledger_payment_messages') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $waActiveMainTab === 'serviceman' ? 'active' : '' }}" id="wa-tpl-tab-serviceman" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-serviceman" href="#wa-tpl-pane-serviceman"
                               role="tab" aria-controls="wa-tpl-pane-serviceman" aria-selected="{{ $waActiveMainTab === 'serviceman' ? 'true' : 'false' }}">
                                {{ translate('WhatsApp_tab_serviceman_change') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $waActiveMainTab === 'verification' ? 'active' : '' }}" id="wa-tpl-tab-verification" data-bs-toggle="tab"
                               data-bs-target="#wa-tpl-pane-verification" href="#wa-tpl-pane-verification"
                               role="tab" aria-controls="wa-tpl-pane-verification" aria-selected="{{ $waActiveMainTab === 'verification' ? 'true' : 'false' }}">
                                {{ translate('WhatsApp_tab_verification_change') }}
                            </a>
                        </li>
                    </ul>
                    </div>

                    <div class="tab-content" id="waBookingTemplateTabContent">
                        <div class="tab-pane fade {{ $waActiveMainTab === 'new-booking' ? 'show active' : '' }}" id="wa-tpl-pane-new-booking" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-new-booking" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_new_booking_hint_meta') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Customer_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_confirmation_customer', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Provider_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_confirmation_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade {{ $waActiveMainTab === 'status' ? 'show active' : '' }}" id="wa-tpl-pane-status" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-status" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_status_change_hint_meta') }}</p>

                            <div class="wa-template-tabs-scroll">
                                <ul class="nav nav-pills flex-nowrap mb-2" id="waStatusSubTabs" role="tablist">
                                    @foreach($statusTemplateSegments as $i => $segment)
                                        <li class="nav-item" role="presentation">
                                            <button type="button" class="nav-link {{ $waActiveMainTab === 'status' && $waActiveStatusSegment === $segment ? 'active' : '' }}"
                                                    id="wa-status-sub-tab-{{ $segment }}"
                                                    data-bs-toggle="tab" data-bs-target="#wa-status-sub-{{ $segment }}"
                                                    role="tab" aria-controls="wa-status-sub-{{ $segment }}"
                                                    aria-selected="{{ $waActiveMainTab === 'status' && $waActiveStatusSegment === $segment ? 'true' : 'false' }}">
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
                                    <div class="tab-pane fade {{ $waActiveMainTab === 'status' && $waActiveStatusSegment === $segment ? 'show active' : '' }}" id="wa-status-sub-{{ $segment }}"
                                         role="tabpanel" aria-labelledby="wa-status-sub-tab-{{ $segment }}" tabindex="0">
                                        <div class="row g-3">
                                            <div class="col-md-6 min-w-0">
                                                <div class="form-label">{{ translate('Customer_template') }}</div>
                                                @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => $ck, 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => true])
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="{{ $ick }}" id="{{ $ick }}" value="1"
                                                           @checked((bool) old($ick, $config[$ick] ?? false))>
                                                    <label class="form-check-label small" for="{{ $ick }}">{{ translate('WhatsApp_send_booking_invoice_with_message') }} ({{ translate('Customer') }})</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 min-w-0">
                                                <div class="form-label">{{ translate('Provider_template') }}</div>
                                                @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => $pk, 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => true])
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

                            <div class="mt-4 border rounded p-3 bg-light">
                                <div class="fw-semibold mb-1">{{ translate('WhatsApp_status_fallback_templates') }}</div>
                                <p class="text-muted small mb-3">{{ translate('WhatsApp_template_status_change_per_status_hint') }}</p>
                                <p class="text-muted small mb-3">{{ translate('WhatsApp_status_fallback_templates_help_meta') }}</p>
                                <div class="row g-3">
                                    <div class="col-md-6 min-w-0">
                                        <div class="form-label">{{ translate('Customer_template') }}</div>
                                        @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_status_customer', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                    </div>
                                    <div class="col-md-6 min-w-0">
                                        <div class="form-label">{{ translate('Provider_template') }}</div>
                                        @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_status_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade {{ $waActiveMainTab === 'provider-change' ? 'show active' : '' }}" id="wa-tpl-pane-provider-change" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-provider-change" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_provider_change_hint_meta') }}</p>
                            <div class="row g-3">
                                <div class="col-lg-4 col-md-12 min-w-0">
                                    <div class="form-label">{{ translate('Customer_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'provider_change_customer', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-lg-4 col-md-12 min-w-0">
                                    <div class="form-label">{{ translate('Previous_provider_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'provider_change_previous_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-lg-4 col-md-12 min-w-0">
                                    <div class="form-label">{{ translate('New_assigned_provider_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'provider_change_new_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade {{ $waActiveMainTab === 'schedule' ? 'show active' : '' }}" id="wa-tpl-pane-schedule" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-schedule" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_schedule_change_hint_meta') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Customer_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_schedule_customer', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Provider_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_schedule_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade {{ $waActiveMainTab === 'payment' ? 'show active' : '' }}" id="wa-tpl-pane-payment" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-payment" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_payment_change_hint_meta') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Customer_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_payment_customer', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Provider_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_payment_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade {{ $waActiveMainTab === 'ledger-payments' ? 'show active' : '' }}" id="wa-tpl-pane-ledger-payments" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-ledger-payments" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_ledger_payment_messages_hint_meta') }}</p>
                            <div class="row g-3">
                                <div class="col-lg-6 min-w-0">
                                    <div class="form-label">{{ translate('WhatsApp_ledger_tpl_provider_payment_reminder') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'ledger_provider_payment_reminder', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-lg-6 min-w-0">
                                    <div class="form-label">{{ translate('WhatsApp_ledger_tpl_customer_payment_reminder') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'ledger_customer_payment_reminder', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-lg-6 min-w-0">
                                    <div class="form-label">{{ translate('WhatsApp_ledger_tpl_payment_received_from_provider') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'ledger_payment_received_from_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-lg-6 min-w-0">
                                    <div class="form-label">{{ translate('WhatsApp_ledger_tpl_payment_sent_to_provider') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'ledger_payment_sent_to_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade {{ $waActiveMainTab === 'serviceman' ? 'show active' : '' }}" id="wa-tpl-pane-serviceman" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-serviceman" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_serviceman_change_hint_meta') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Customer_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_serviceman_customer', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Provider_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_serviceman_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade {{ $waActiveMainTab === 'verification' ? 'show active' : '' }}" id="wa-tpl-pane-verification" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-verification" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_verification_change_hint_meta') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Customer_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_verification_customer', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
                                </div>
                                <div class="col-md-6 min-w-0">
                                    <div class="form-label">{{ translate('Provider_template') }}</div>
                                    @include('whatsappmodule::admin.partials.booking-wa-meta-fields', ['fieldKey' => 'booking_verification_provider', 'waTemplates' => $waTemplates, 'config' => $config, 'invoiceHint' => false])
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
        var waBookingMetaTemplates = @json($waTemplatesJson ?? []);
        var waBookingPlaceholderTokens = @json(array_keys($placeholders));
        var waBookingPlaceholderHints = @json($placeholderHints ?? []);
        var waBookingPlaceholderGuides = @json($placeholderGuides ?? []);
        var waBookingPlaceholderModules = @json($placeholderDropdownModules ?? []);
        var waBookingPlaceholderSamples = @json($placeholderSamples ?? []);
        var waLblVarInfoAria = {!! json_encode(translate('WhatsApp_booking_var_info_aria')) !!};
        var waLblVarGuideEmpty = {!! json_encode(translate('WhatsApp_booking_var_guide_empty')) !!};
        var waLblMapTemplateVar = {!! json_encode(translate('WhatsApp_booking_map_col_template_var')) !!};
        var waLblMapBookingVar = {!! json_encode(translate('WhatsApp_booking_map_col_booking_var')) !!};
        var waLblHeaderPrefix = {!! json_encode(translate('WhatsApp_booking_map_header_prefix')) !!};
        var waLblMapPickPlaceholder = {!! json_encode(translate('WhatsApp_booking_map_pick_variable')) !!};
        var waLblMapColToken = {!! json_encode(translate('WhatsApp_booking_var_col_token')) !!};
        var waLblMapColModule = {!! json_encode(translate('WhatsApp_booking_var_col_module')) !!};
        /** Inline SVG so the control is visible without Themify/TIO icon fonts (admin layout loads Material Icons only). */
        var waBookingVarInfoIconSvg = '<svg class="wa-booking-var-info-svg" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.75"/><path fill="currentColor" d="M11.25 10h1.5v6h-1.5v-6zm0-3.25h1.5V8h-1.5V6.75z"/></svg>';

        /** Builds Meta template placeholders (double curly braces + inner id). Do not put raw brace pairs in Blade source. */
        function waBookingTplVarBraces(inner) {
            return '\u007B\u007B' + inner + '\u007D\u007D';
        }

        function waBookingTplMetaById(id) {
            var tid = String(id);
            for (var i = 0; i < waBookingMetaTemplates.length; i++) {
                if (String(waBookingMetaTemplates[i].id) === tid) {
                    return waBookingMetaTemplates[i];
                }
            }
            return null;
        }

        function waBookingVarGuidePopoverHtml(plain) {
            var t = plain ? String(plain) : '';
            if (!t) {
                return '';
            }
            return t.split('\n').map(function (line) {
                return jQuery('<div>').text(line).html();
            }).join('<br>');
        }

        /** Demo value for the phone preview when a booking token is selected in the mapping dropdown. */
        function waBookingSampleForToken(tok) {
            var t = tok ? String(tok).trim() : '';
            if (!t) {
                return '…';
            }
            if (waBookingPlaceholderSamples && waBookingPlaceholderSamples[t]) {
                return waBookingPlaceholderSamples[t];
            }
            if (t.indexOf('{additional_charge_') === 0) {
                return '350.00';
            }
            if (waBookingPlaceholderHints && waBookingPlaceholderHints[t]) {
                return waBookingPlaceholderHints[t];
            }
            return t;
        }

        function waBookingFillPositionalText(templateText, inputs) {
            var out = templateText || '';
            var arr = Array.prototype.slice.call(inputs);
            for (var i = arr.length; i >= 1; i--) {
                var tok = arr[i - 1] ? arr[i - 1].value.trim() : '';
                var sample = tok ? waBookingSampleForToken(tok) : '…';
                out = out.split(waBookingTplVarBraces(String(i))).join(sample);
            }
            return out;
        }

        function waBookingFillNamedText(text, names, inputs) {
            var out = text || '';
            for (var i = 0; i < (names || []).length; i++) {
                var tok = inputs[i] ? inputs[i].value.trim() : '';
                var sample = tok ? waBookingSampleForToken(tok) : '…';
                out = out.split(waBookingTplVarBraces(names[i])).join(sample);
            }
            return out;
        }

        function waBookingGetHeaderTextRaw(components) {
            if (!components || !components.length) {
                return '';
            }
            for (var i = 0; i < components.length; i++) {
                var c = components[i];
                if (String(c.type || '').toUpperCase() === 'HEADER' && String(c.format || '').toUpperCase() === 'TEXT') {
                    return c.text || '';
                }
            }
            return '';
        }

        function waBookingUpdatePreview(wrap) {
            var root = wrap.querySelector('.js-wa-booking-meta-preview');
            if (!root) {
                return;
            }
            var sel = wrap.querySelector('.js-wa-booking-meta-select');
            var meta = sel && sel.value ? waBookingTplMetaById(sel.value) : null;
            if (!meta || !meta.preview_state) {
                root.hidden = true;
                return;
            }
            root.hidden = false;
            var ps = meta.preview_state;
            var bodyInputs = wrap.querySelectorAll('select[name*="_wa_body_params"]');
            var bodyArr = Array.prototype.slice.call(bodyInputs);
            var bodyText = ps.body || '';
            var bp = meta.body_plan || { format: 'positional', named_param_names: [], positional_count: parseInt(meta.body_count, 10) || 0 };
            var bodyFilled;
            if (bp.format === 'named' && bp.named_param_names && bp.named_param_names.length) {
                bodyFilled = waBookingFillNamedText(bodyText, bp.named_param_names, bodyArr);
            } else {
                bodyFilled = waBookingFillPositionalText(bodyText, bodyArr);
            }

            var headerInputs = wrap.querySelectorAll('select[name*="_wa_header_params"]');
            var headerArr = Array.prototype.slice.call(headerInputs);
            var headerRaw = waBookingGetHeaderTextRaw(meta.components || []);
            var hp = meta.header_plan || { format: 'positional', named_param_names: [], positional_count: 0 };
            var headerFilled = '';
            if (headerRaw) {
                if (hp.format === 'named' && hp.named_param_names && hp.named_param_names.length) {
                    headerFilled = waBookingFillNamedText(headerRaw, hp.named_param_names, headerArr);
                } else {
                    headerFilled = waBookingFillPositionalText(headerRaw, headerArr);
                }
            }

            var hWrap = root.querySelector('.js-wa-preview-header-wrap');
            var bodyEl = root.querySelector('.js-wa-preview-body');
            var footEl = root.querySelector('.js-wa-preview-footer');
            var btnEl = root.querySelector('.js-wa-preview-buttons');

            if (hWrap) {
                hWrap.innerHTML = '';
                hWrap.hidden = false;
                if (ps.header) {
                    var hf = String(ps.header.format || 'TEXT').toUpperCase();
                    if (hf === 'TEXT' && headerFilled) {
                        var hd = document.createElement('div');
                        hd.className = 'fw-semibold small mb-2 text-break';
                        hd.textContent = headerFilled;
                        hWrap.appendChild(hd);
                    } else if (hf !== 'TEXT' && hf !== 'NONE') {
                        var media = document.createElement('div');
                        media.className = 'wa-tpl-media rounded-2 mb-2 overflow-hidden bg-light ratio ratio-16x9 d-flex align-items-center justify-content-center';
                        if (ps.header.media_url) {
                            if (hf === 'IMAGE') {
                                var img = document.createElement('img');
                                img.src = ps.header.media_url;
                                img.className = 'w-100 h-100 object-fit-cover';
                                img.alt = '';
                                media.appendChild(img);
                            } else {
                                media.textContent = hf;
                            }
                        } else {
                            media.textContent = hf;
                        }
                        hWrap.appendChild(media);
                    }
                } else {
                    hWrap.hidden = true;
                }
            }

            if (bodyEl) {
                bodyEl.textContent = bodyFilled;
            }
            if (footEl) {
                if (ps.footer) {
                    footEl.textContent = ps.footer;
                    footEl.hidden = false;
                } else {
                    footEl.textContent = '';
                    footEl.hidden = true;
                }
            }
            if (btnEl) {
                btnEl.innerHTML = '';
                if (ps.buttons && ps.buttons.length) {
                    btnEl.hidden = false;
                    ps.buttons.forEach(function (btn) {
                        var t = String(btn.type || '').toUpperCase();
                        var span = document.createElement('span');
                        span.className = 'btn btn-sm btn-outline-secondary text-start rounded-pill wa-tpl-btn-fake';
                        span.textContent = btn.text || '';
                        btnEl.appendChild(span);
                    });
                } else {
                    btnEl.hidden = true;
                }
            }
        }

        function waBookingCreateTokenSelect(inputName, initialVal) {
            var sel = document.createElement('select');
            sel.name = inputName;
            sel.className = 'form-select form-select-sm js-wa-booking-map-token-select';
            var opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = waLblMapPickPlaceholder;
            sel.appendChild(opt0);
            (waBookingPlaceholderTokens || []).forEach(function (tok) {
                var label = (waBookingPlaceholderHints && waBookingPlaceholderHints[tok]) ? waBookingPlaceholderHints[tok] : '';
                var guide = (waBookingPlaceholderGuides && waBookingPlaceholderGuides[tok]) ? waBookingPlaceholderGuides[tok] : '';
                var mod = (waBookingPlaceholderModules && waBookingPlaceholderModules[tok]) ? waBookingPlaceholderModules[tok] : '';
                var opt = document.createElement('option');
                opt.value = tok;
                opt.setAttribute('data-description', label);
                opt.setAttribute('data-guide', guide);
                opt.setAttribute('data-module', mod);
                opt.setAttribute('title', mod ? tok + ' — ' + mod : tok);
                opt.textContent = tok + ' — ' + (mod || '');
                if (initialVal !== undefined && initialVal !== null && String(initialVal) === String(tok)) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });
            return sel;
        }

        function waBookingInitMappingSelect2(container) {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
                return;
            }
            var $root = jQuery(container);
            $root.find('select.js-wa-booking-map-token-select').each(function () {
                var $s = jQuery(this);
                if ($s.hasClass('select2-hidden-accessible')) {
                    $s.select2('destroy');
                }
                $s.select2({
                    placeholder: waLblMapPickPlaceholder,
                    allowClear: true,
                    width: '100%',
                    dropdownParent: jQuery('body'),
                    dropdownCssClass: 'select2-wa-booking-var-dd',
                    minimumResultsForSearch: 0,
                    matcher: function (params, data) {
                        if (jQuery.trim(params.term) === '') {
                            return data;
                        }
                        if (!data.id) {
                            return data;
                        }
                        var term = params.term.toLowerCase();
                        var id = String(data.id).toLowerCase();
                        var txt = String(data.text || '').toLowerCase();
                        var desc = '';
                        var mod = '';
                        if (data.element) {
                            desc = String(data.element.getAttribute('data-description') || '').toLowerCase();
                            mod = String(data.element.getAttribute('data-module') || '').toLowerCase();
                        }
                        if (id.indexOf(term) > -1 || txt.indexOf(term) > -1 || desc.indexOf(term) > -1 || mod.indexOf(term) > -1) {
                            return data;
                        }
                        return null;
                    },
                    templateResult: function (state) {
                        if (!state.id) {
                            return state.text;
                        }
                        var el = state.element;
                        var tok = String(state.id);
                        var mod = '';
                        if (el) {
                            mod = String(el.getAttribute('data-module') || '').trim();
                        }
                        if (!mod && waBookingPlaceholderModules && waBookingPlaceholderModules[tok]) {
                            mod = String(waBookingPlaceholderModules[tok]);
                        }
                        var $wrap = jQuery('<div class="wa-booking-var-opt wa-booking-var-opt-row"></div>');
                        var $main = jQuery('<div class="wa-booking-var-opt-main"></div>');
                        $main.append(jQuery('<div class="wa-booking-var-opt-lbl"></div>').text(waLblMapColToken));
                        $main.append(jQuery('<div class="wa-booking-var-opt-token font-monospace fw-semibold"></div>').text(tok));
                        $main.append(jQuery('<div class="wa-booking-var-opt-lbl mt-2"></div>').text(waLblMapColModule));
                        $main.append(jQuery('<div class="wa-booking-var-opt-module"></div>').text(mod || '—'));
                        var $btn = jQuery('<button type="button" class="btn btn-link btn-sm wa-booking-var-guide-btn text-secondary"></button>');
                        $btn.attr('aria-label', waLblVarInfoAria);
                        $btn.attr('data-wa-var-token', tok);
                        $btn.html(waBookingVarInfoIconSvg);
                        $btn.on('mousedown', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                        });
                        $btn.on('mouseup', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                        });
                        $btn.on('click', function (e) {
                            e.stopPropagation();
                        });
                        $wrap.append($main);
                        $wrap.append($btn);
                        return $wrap;
                    },
                    templateSelection: function (state) {
                        if (!state.id) {
                            return state.text;
                        }
                        return String(state.id);
                    },
                });
                $s.off('select2:select.waBkPrev select2:clear.waBkPrev select2:unselect.waBkPrev select2:open.waVarPop select2:closing.waVarPop')
                    .on('select2:open.waVarPop', function () {
                        if (typeof bootstrap === 'undefined' || !bootstrap.Popover) {
                            return;
                        }
                        requestAnimationFrame(function () {
                            jQuery('.select2-wa-booking-var-dd .wa-booking-var-guide-btn').each(function () {
                                var btnEl = this;
                                if (bootstrap.Popover.getInstance(btnEl)) {
                                    return;
                                }
                                var tok = btnEl.getAttribute('data-wa-var-token') || '';
                                var guideRaw = (waBookingPlaceholderGuides && waBookingPlaceholderGuides[tok])
                                    ? String(waBookingPlaceholderGuides[tok])
                                    : '';
                                new bootstrap.Popover(btnEl, {
                                    html: true,
                                    sanitize: false,
                                    trigger: 'click',
                                    placement: 'left',
                                    container: 'body',
                                    title: tok,
                                    content: waBookingVarGuidePopoverHtml(guideRaw || waLblVarGuideEmpty),
                                    customClass: 'wa-booking-var-popover shadow',
                                    fallbackPlacements: ['right', 'top', 'bottom']
                                });
                            });
                        });
                    })
                    .on('select2:closing.waVarPop', function () {
                        if (typeof bootstrap === 'undefined' || !bootstrap.Popover) {
                            return;
                        }
                        jQuery('.select2-wa-booking-var-dd .wa-booking-var-guide-btn').each(function () {
                            var p = bootstrap.Popover.getInstance(this);
                            if (p) {
                                p.dispose();
                            }
                        });
                    })
                    .on('select2:select.waBkPrev select2:clear.waBkPrev select2:unselect.waBkPrev', function () {
                        var w = jQuery(this).closest('.wa-booking-meta-wrap')[0];
                        if (w) {
                            waBookingUpdatePreview(w);
                        }
                    });
            });
        }

        (function () {
            if (typeof document === 'undefined' || typeof bootstrap === 'undefined' || !bootstrap.Popover) {
                return;
            }
            document.addEventListener('show.bs.popover', function (event) {
                var tgt = event.target;
                if (!tgt || !tgt.classList || !tgt.classList.contains('wa-booking-var-guide-btn')) {
                    return;
                }
                document.querySelectorAll('.wa-booking-var-guide-btn').forEach(function (el) {
                    if (el === tgt) {
                        return;
                    }
                    var p = bootstrap.Popover.getInstance(el);
                    if (p) {
                        p.hide();
                    }
                });
            });
        })();

        function waBookingSyncMetaSelect2Layout($nativeSelect) {
            if (typeof jQuery === 'undefined' || !$nativeSelect || !$nativeSelect.length) {
                return;
            }
            var $c = $nativeSelect.next('.select2-container');
            if ($c.length) {
                $c.css({ width: '100%', maxWidth: '100%', minWidth: 0 });
            }
        }

        function waBookingInitMetaTemplateSelect2(wrap) {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2 || !wrap) {
                return;
            }
            var $s = jQuery(wrap).find('select.js-wa-booking-meta-select').first();
            if (!$s.length) {
                return;
            }
            if ($s.hasClass('select2-hidden-accessible')) {
                $s.select2('destroy');
            }
            $s.select2({
                width: '100%',
                dropdownParent: jQuery('body'),
                dropdownCssClass: 'select2-wa-booking-var-dd',
                minimumResultsForSearch: 0,
                matcher: function (params, data) {
                    if (jQuery.trim(params.term) === '') {
                        return data;
                    }
                    if (!data.id) {
                        return data;
                    }
                    var term = params.term.toLowerCase();
                    var txt = String(data.text || '').toLowerCase();
                    var name = '';
                    var lang = '';
                    var st = '';
                    var cat = '';
                    if (data.element) {
                        name = String(data.element.getAttribute('data-wa-tpl-name') || '').toLowerCase();
                        lang = String(data.element.getAttribute('data-wa-tpl-language') || '').toLowerCase();
                        st = String(data.element.getAttribute('data-wa-tpl-status') || '').toLowerCase();
                        cat = String(data.element.getAttribute('data-wa-tpl-category') || '').toLowerCase();
                    }
                    if (txt.indexOf(term) > -1 || name.indexOf(term) > -1 || lang.indexOf(term) > -1
                        || st.indexOf(term) > -1 || cat.indexOf(term) > -1) {
                        return data;
                    }
                    return null;
                },
                templateResult: function (state) {
                    if (!state.id) {
                        return state.text;
                    }
                    var el = state.element;
                    var name = el ? String(el.getAttribute('data-wa-tpl-name') || '').trim() : '';
                    var lang = el ? String(el.getAttribute('data-wa-tpl-language') || '').trim() : '';
                    var st = el ? String(el.getAttribute('data-wa-tpl-status') || '').trim() : '';
                    var cat = el ? String(el.getAttribute('data-wa-tpl-category') || '').trim() : '';
                    var line2 = [lang, st, cat].filter(function (x) { return x; }).join(' · ');
                    var $wrap = jQuery('<div class="wa-booking-meta-tpl-opt py-1"></div>');
                    $wrap.append(jQuery('<div class="fw-semibold small text-break"></div>').text(name || state.text));
                    if (line2) {
                        $wrap.append(jQuery('<div class="text-muted" style="font-size:0.78rem;"></div>').text(line2));
                    }
                    return $wrap;
                },
                templateSelection: function (state) {
                    if (!state.id) {
                        return state.text;
                    }
                    var el = state.element;
                    var name = el ? String(el.getAttribute('data-wa-tpl-name') || '').trim() : '';
                    var lang = el ? String(el.getAttribute('data-wa-tpl-language') || '').trim() : '';
                    if (name && lang) {
                        return name + ' (' + lang + ')';
                    }
                    return name || state.text;
                },
            });
            $s.off('select2:open.waBkMetaDd select2:select.waBkMetaTpl select2:clear.waBkMetaTpl')
                .on('select2:open.waBkMetaDd', function () {
                    requestAnimationFrame(function () {
                        var $dd = jQuery('body > .select2-container--open .select2-dropdown.select2-wa-booking-var-dd');
                        if ($dd.length) {
                            $dd.css({ maxWidth: 'min(36rem, calc(100vw - 1rem))', boxSizing: 'border-box' });
                        }
                    });
                })
                .on('select2:select.waBkMetaTpl select2:clear.waBkMetaTpl', function () {
                    waBookingRenderMapping(wrap, false);
                    waBookingSyncMetaSelect2Layout($s);
                });
            waBookingSyncMetaSelect2Layout($s);
            requestAnimationFrame(function () {
                waBookingSyncMetaSelect2Layout($s);
            });
            waBookingUpdatePreview(wrap);
        }

        function waBookingRefreshMetaTemplateSelect2InPane(pane) {
            if (!pane) {
                return;
            }
            pane.querySelectorAll('.wa-booking-meta-wrap').forEach(function (wrap) {
                waBookingInitMetaTemplateSelect2(wrap);
            });
            setTimeout(function () {
                if (typeof jQuery === 'undefined') {
                    return;
                }
                jQuery(pane).find('select.js-wa-booking-meta-select.select2-hidden-accessible').each(function () {
                    waBookingSyncMetaSelect2Layout(jQuery(this));
                });
            }, 50);
        }

        var waLblNoVars = {!! json_encode(translate('WhatsApp_booking_template_no_variables')) !!};

        function waBookingRenderMapping(wrap, useSavedInitial) {
            var mappingEl = wrap.querySelector('.js-wa-booking-meta-mapping');
            var sel = wrap.querySelector('.js-wa-booking-meta-select');
            if (!mappingEl || !sel) {
                return;
            }
            var bodyInitial = [];
            var headerInitial = [];
            if (useSavedInitial) {
                try {
                    bodyInitial = JSON.parse(wrap.getAttribute('data-initial-body-params') || '[]');
                } catch (e) {
                    bodyInitial = [];
                }
                try {
                    headerInitial = JSON.parse(wrap.getAttribute('data-initial-header-params') || '[]');
                } catch (e) {
                    headerInitial = [];
                }
                wrap.setAttribute('data-initial-body-params', '[]');
                wrap.setAttribute('data-initial-header-params', '[]');
            }
            var tplId = sel.value;
            if (!tplId) {
                if (typeof jQuery !== 'undefined') {
                    jQuery(mappingEl).find('select.js-wa-booking-map-token-select').each(function () {
                        var $s = jQuery(this);
                        if ($s.hasClass('select2-hidden-accessible')) {
                            $s.select2('destroy');
                        }
                    });
                }
                mappingEl.innerHTML = '';
                mappingEl.hidden = true;
                waBookingUpdatePreview(wrap);
                return;
            }
            var meta = waBookingTplMetaById(tplId);
            if (!meta) {
                mappingEl.hidden = true;
                return;
            }
            var field = wrap.getAttribute('data-field');
            var bp = meta.body_plan || { format: 'positional', named_param_names: [], positional_count: parseInt(meta.body_count, 10) || 0 };
            var hp = meta.header_plan || { format: 'positional', named_param_names: [], positional_count: 0 };

            var table = document.createElement('table');
            table.className = 'table table-sm table-bordered wa-booking-map-table bg-white mb-0';
            var thead = document.createElement('thead');
            thead.innerHTML = '<tr><th>' + waLblMapTemplateVar + '</th><th>' + waLblMapBookingVar + '</th></tr>';
            table.appendChild(thead);
            var tbody = document.createElement('tbody');

            if (bp.format === 'named' && bp.named_param_names && bp.named_param_names.length) {
                bp.named_param_names.forEach(function (name, idx) {
                    var tr = document.createElement('tr');
                    var tdKey = document.createElement('td');
                    tdKey.className = 'small fw-medium align-middle text-break';
                    tdKey.textContent = waBookingTplVarBraces(name);
                    var tdVal = document.createElement('td');
                    var want = bodyInitial[idx] !== undefined && bodyInitial[idx] !== null ? String(bodyInitial[idx]) : '';
                    tdVal.appendChild(waBookingCreateTokenSelect(field + '_wa_body_params[]', want));
                    tr.appendChild(tdKey);
                    tr.appendChild(tdVal);
                    tbody.appendChild(tr);
                });
            } else {
                for (var b = 0; b < bp.positional_count; b++) {
                    var tr = document.createElement('tr');
                    var tdKey = document.createElement('td');
                    tdKey.className = 'small fw-medium align-middle';
                    tdKey.textContent = waBookingTplVarBraces(String(b + 1));
                    var tdVal = document.createElement('td');
                    var wantB = bodyInitial[b] !== undefined && bodyInitial[b] !== null ? String(bodyInitial[b]) : '';
                    tdVal.appendChild(waBookingCreateTokenSelect(field + '_wa_body_params[]', wantB));
                    tr.appendChild(tdKey);
                    tr.appendChild(tdVal);
                    tbody.appendChild(tr);
                }
            }

            if (hp.format === 'named' && hp.named_param_names && hp.named_param_names.length) {
                hp.named_param_names.forEach(function (name, idx) {
                    var trH = document.createElement('tr');
                    var tdKeyH = document.createElement('td');
                    tdKeyH.className = 'small fw-medium align-middle text-break';
                    tdKeyH.textContent = waLblHeaderPrefix + ' ' + waBookingTplVarBraces(name);
                    var tdValH = document.createElement('td');
                    var wantH = headerInitial[idx] !== undefined && headerInitial[idx] !== null ? String(headerInitial[idx]) : '';
                    tdValH.appendChild(waBookingCreateTokenSelect(field + '_wa_header_params[]', wantH));
                    trH.appendChild(tdKeyH);
                    trH.appendChild(tdValH);
                    tbody.appendChild(trH);
                });
            } else {
                for (var h = 0; h < hp.positional_count; h++) {
                    var trH2 = document.createElement('tr');
                    var tdKeyH2 = document.createElement('td');
                    tdKeyH2.className = 'small fw-medium align-middle';
                    tdKeyH2.textContent = waLblHeaderPrefix + ' ' + waBookingTplVarBraces(String(h + 1));
                    var tdValH2 = document.createElement('td');
                    var wantH2 = headerInitial[h] !== undefined && headerInitial[h] !== null ? String(headerInitial[h]) : '';
                    tdValH2.appendChild(waBookingCreateTokenSelect(field + '_wa_header_params[]', wantH2));
                    trH2.appendChild(tdKeyH2);
                    trH2.appendChild(tdValH2);
                    tbody.appendChild(trH2);
                }
            }

            if (!tbody.children.length) {
                var trE = document.createElement('tr');
                var tdE = document.createElement('td');
                tdE.colSpan = 2;
                tdE.className = 'small text-muted';
                tdE.textContent = waLblNoVars;
                trE.appendChild(tdE);
                tbody.appendChild(trE);
            }

            table.appendChild(tbody);

            if (typeof jQuery !== 'undefined') {
                jQuery(mappingEl).find('select.js-wa-booking-map-token-select').each(function () {
                    var $s = jQuery(this);
                    if ($s.hasClass('select2-hidden-accessible')) {
                        $s.select2('destroy');
                    }
                });
            }
            mappingEl.innerHTML = '';
            mappingEl.appendChild(table);
            mappingEl.hidden = false;

            waBookingInitMappingSelect2(mappingEl);
            waBookingUpdatePreview(wrap);
        }

        document.querySelectorAll('.wa-booking-meta-wrap').forEach(function (wrap) {
            waBookingRenderMapping(wrap, true);
            var sel = wrap.querySelector('.js-wa-booking-meta-select');
            if (sel && (typeof jQuery === 'undefined' || !jQuery.fn.select2)) {
                sel.addEventListener('change', function () {
                    waBookingRenderMapping(wrap, false);
                });
            }
            wrap.addEventListener('change', function (e) {
                if (e.target && e.target.matches && e.target.matches('select[name*="_wa_body_params"], select[name*="_wa_header_params"]')) {
                    waBookingUpdatePreview(wrap);
                }
            });
            waBookingInitMetaTemplateSelect2(wrap);
        });

        (function () {
            var mainInput = document.getElementById('waActiveMainTab');
            var statusInput = document.getElementById('waActiveStatusSegment');
            if (!mainInput) {
                return;
            }
            function refreshMetaSelect2FromTabTrigger(el) {
                var target = el.getAttribute('data-bs-target') || '';
                if (!target) {
                    return;
                }
                var pane = document.querySelector(target);
                waBookingRefreshMetaTemplateSelect2InPane(pane);
            }
            document.querySelectorAll('#waBookingTemplateTabs [data-bs-toggle="tab"]').forEach(function (el) {
                el.addEventListener('shown.bs.tab', function () {
                    var target = el.getAttribute('data-bs-target') || '';
                    var m = target.match(/wa-tpl-pane-([^#]+)/);
                    if (m) {
                        mainInput.value = m[1];
                    }
                    if (m && m[1] !== 'status' && statusInput) {
                        statusInput.value = '';
                    }
                    refreshMetaSelect2FromTabTrigger(el);
                });
            });
            if (statusInput) {
                document.querySelectorAll('#waStatusSubTabs [data-bs-toggle="tab"]').forEach(function (el) {
                    el.addEventListener('shown.bs.tab', function () {
                        var target = el.getAttribute('data-bs-target') || '';
                        var m = target.match(/wa-status-sub-([^#]+)/);
                        if (m) {
                            statusInput.value = m[1];
                        }
                        refreshMetaSelect2FromTabTrigger(el);
                    });
                });
            }
        })();

        (function () {
            var t = null;
            window.addEventListener('resize', function () {
                if (typeof jQuery === 'undefined') {
                    return;
                }
                clearTimeout(t);
                t = setTimeout(function () {
                    jQuery('select.js-wa-booking-meta-select.select2-hidden-accessible').each(function () {
                        waBookingSyncMetaSelect2Layout(jQuery(this));
                    });
                }, 150);
            });
        })();

        var waBookingEnabledCurrentlyOn = @json($__waBookingEnabledJs);

        (function () {
            var form = document.getElementById('wa-booking-templates-form');
            if (!form || typeof jQuery === 'undefined') {
                return;
            }
            form.addEventListener('submit', function () {
                var mainInput = document.getElementById('waActiveMainTab');
                var statusInput = document.getElementById('waActiveStatusSegment');
                var mainActive = document.querySelector('#waBookingTemplateTabs .nav-link.active');
                if (mainInput && mainActive) {
                    var mt = mainActive.getAttribute('data-bs-target') || '';
                    var mm = mt.match(/wa-tpl-pane-([^#]+)/);
                    if (mm) {
                        mainInput.value = mm[1];
                    }
                }
                if (statusInput && mainInput && mainInput.value === 'status') {
                    var subActive = document.querySelector('#waStatusSubTabs .nav-link.active');
                    if (subActive) {
                        var st = subActive.getAttribute('data-bs-target') || '';
                        var sm = st.match(/wa-status-sub-([^#]+)/);
                        if (sm) {
                            statusInput.value = sm[1];
                        }
                    }
                } else if (statusInput && mainInput && mainInput.value !== 'status') {
                    statusInput.value = '';
                }
                jQuery(form).find('select.js-wa-booking-map-token-select').each(function () {
                    var $s = jQuery(this);
                    var v = $s.val();
                    if ($s.hasClass('select2-hidden-accessible')) {
                        try {
                            $s.select2('destroy');
                        } catch (e) { /* ignore */ }
                    }
                    if (v !== null && v !== undefined) {
                        $s.val(v);
                    }
                });
                jQuery(form).find('select.js-wa-booking-meta-select').each(function () {
                    var $s = jQuery(this);
                    var v = $s.val();
                    if ($s.hasClass('select2-hidden-accessible')) {
                        try {
                            $s.select2('destroy');
                        } catch (e) { /* ignore */ }
                    }
                    if (v !== null && v !== undefined) {
                        $s.val(v);
                    }
                });
            });
        })();

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

        (function () {
            var modalEl = document.getElementById('waBookingMsgSendConfirmModal');
            if (!modalEl || typeof bootstrap === 'undefined') {
                return;
            }
            var bodyEl = document.getElementById('waBookingMsgSendModalBody');
            var keyEl = document.getElementById('waBookingMsgSendModalKey');
            var valEl = document.getElementById('waBookingMsgSendModalValue');
            var mainTabEl = document.getElementById('waBookingMsgSendModalMainTab');
            var statusSegEl = document.getElementById('waBookingMsgSendModalStatusSeg');
            var enableText = {!! json_encode(translate('WhatsApp_booking_single_message_modal_enable_body')) !!};
            var disableText = {!! json_encode(translate('WhatsApp_booking_single_message_modal_disable_body')) !!};

            function syncTabFieldsForMessageModal() {
                var mainInput = document.getElementById('waActiveMainTab');
                var statusInput = document.getElementById('waActiveStatusSegment');
                var mainActive = document.querySelector('#waBookingTemplateTabs .nav-link.active');
                if (mainInput && mainActive) {
                    var mt = mainActive.getAttribute('data-bs-target') || '';
                    var mm = mt.match(/wa-tpl-pane-([^#]+)/);
                    if (mm) {
                        mainInput.value = mm[1];
                    }
                }
                if (statusInput && mainInput && mainInput.value === 'status') {
                    var subActive = document.querySelector('#waStatusSubTabs .nav-link.active');
                    if (subActive) {
                        var st = subActive.getAttribute('data-bs-target') || '';
                        var sm = st.match(/wa-status-sub-([^#]+)/);
                        if (sm) {
                            statusInput.value = sm[1];
                        }
                    }
                } else if (statusInput && mainInput && mainInput.value !== 'status') {
                    statusInput.value = '';
                }
                if (mainTabEl && mainInput) {
                    mainTabEl.value = mainInput.value;
                }
                if (statusSegEl && statusInput) {
                    statusSegEl.value = statusInput.value;
                }
            }

            document.querySelectorAll('.js-wa-booking-msg-send-toggle-open').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    syncTabFieldsForMessageModal();
                    var key = btn.getAttribute('data-wa-msg-key') || '';
                    var curOn = btn.getAttribute('data-wa-send-enabled') === '1';
                    var nextOn = !curOn;
                    if (keyEl) {
                        keyEl.value = key;
                    }
                    if (valEl) {
                        valEl.value = nextOn ? '1' : '0';
                    }
                    if (bodyEl) {
                        bodyEl.textContent = nextOn ? enableText : disableText;
                    }
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                });
            });
        })();
    </script>
@endpush
