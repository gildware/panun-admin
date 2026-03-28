@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp') . ' — ' . translate('Message_templates'))

@section('content')
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h2 class="h4 mb-1">{{ translate('Message_templates') }}</h2>
                <p class="text-muted mb-0">{{ translate('WhatsApp_booking_template_help') }}</p>
            </div>
        </div>

        <form action="{{ route('admin.whatsapp.booking-templates.update') }}" method="post">
            @csrf

            <div class="card mb-3">
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="enabled" value="0">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="wa_templates_enabled"
                            {{ !empty($config['enabled']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="wa_templates_enabled">{{ translate('Send_booking_WhatsApp_messages') }}</label>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Default_phone_country_prefix') }}</label>
                            <input type="text" name="default_phone_prefix" class="form-control"
                                   value="{{ old('default_phone_prefix', $config['default_phone_prefix'] ?? '') }}"
                                   placeholder="880">
                            <small class="text-muted">{{ translate('Digits_only_no_plus') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <strong>{{ translate('Available_variables') }}</strong>
                </div>
                <div class="card-body">
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

            <div class="card mb-3">
                <div class="card-body">
                    <ul class="nav nav--tabs mb-3" id="waBookingTemplateTabs" role="tablist">
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
                    </ul>

                    <div class="tab-content" id="waBookingTemplateTabContent">
                        <div class="tab-pane fade show active" id="wa-tpl-pane-new-booking" role="tabpanel"
                             aria-labelledby="wa-tpl-tab-new-booking" tabindex="0">
                            <p class="text-muted small mb-3">{{ translate('WhatsApp_template_new_booking_hint') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_confirmation_customer">{{ translate('Customer_template') }}</label>
                                    <textarea name="booking_confirmation_customer" id="tpl_booking_confirmation_customer"
                                              class="form-control wa-template-input" rows="16"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_confirmation_customer', $config['booking_confirmation_customer'] ?? '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_confirmation_provider">{{ translate('Provider_template') }}</label>
                                    <textarea name="booking_confirmation_provider" id="tpl_booking_confirmation_provider"
                                              class="form-control wa-template-input" rows="16"
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
                                              class="form-control wa-template-input" rows="16"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_status_customer', $config['booking_status_customer'] ?? '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tpl_booking_status_provider">{{ translate('Provider_template') }}</label>
                                    <textarea name="booking_status_provider" id="tpl_booking_status_provider"
                                              class="form-control wa-template-input" rows="16"
                                              placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old('booking_status_provider', $config['booking_status_provider'] ?? '') }}</textarea>
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
    <script>
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
    </script>
@endpush
