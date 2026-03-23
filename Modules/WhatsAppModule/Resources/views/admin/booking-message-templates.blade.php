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

            @php
                $sections = [
                    'booking_confirmation_customer' => translate('Booking_Confirmation_message_to_customer'),
                    'booking_confirmation_provider' => translate('Booking_Confirmation_message_to_Provider'),
                    'booking_status_customer' => translate('Booking_Status_Update_message_to_customer'),
                    'booking_status_provider' => translate('Booking_Status_Update_message_to_provider'),
                ];
            @endphp

            @foreach($sections as $field => $title)
                <div class="card mb-3">
                    <div class="card-header"><strong>{{ $title }}</strong></div>
                    <div class="card-body">
                        <textarea name="{{ $field }}" id="tpl_{{ $field }}" class="form-control wa-template-input" rows="6"
                                  placeholder="{{ translate('Leave_empty_to_skip_sending_this_message') }}">{{ old($field, $config[$field] ?? '') }}</textarea>
                    </div>
                </div>
            @endforeach

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
                    ta = areas[0];
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
