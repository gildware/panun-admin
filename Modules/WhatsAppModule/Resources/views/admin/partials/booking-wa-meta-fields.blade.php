@php
    /** @var string $slotTitle Human title for this slot (e.g. Customer template), shown inside the box header */
    $slotTitle = $slotTitle ?? translate('WhatsApp_booking_meta_template_label');
    $invoiceHint = $invoiceHint ?? false;
    $sendEnabled = (bool) old($fieldKey . '_send_enabled', $config[$fieldKey . '_send_enabled'] ?? true);
    $initialTpl = old($fieldKey . '_wa_tpl_id', $config[$fieldKey . '_wa_tpl_id'] ?? null);
    $initialBodyParams = old($fieldKey . '_wa_body_params', $config[$fieldKey . '_wa_body_params'] ?? []);
    if (!is_array($initialBodyParams)) {
        $initialBodyParams = [];
    }
    $initialBodyParams = array_values($initialBodyParams);
    $initialHeaderParams = old($fieldKey . '_wa_header_params', $config[$fieldKey . '_wa_header_params'] ?? []);
    if (!is_array($initialHeaderParams)) {
        $initialHeaderParams = [];
    }
    $initialHeaderParams = array_values($initialHeaderParams);
    $previewUid = 'wa-bk-prev-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $fieldKey);

    $whenSentExplanation = match ($fieldKey) {
        'booking_confirmation_customer' => __('lang.WhatsApp_when_sent_booking_confirmation_customer'),
        'booking_confirmation_provider' => __('lang.WhatsApp_when_sent_booking_confirmation_provider'),
        'booking_status_customer' => __('lang.WhatsApp_when_sent_booking_status_fallback_customer'),
        'booking_status_provider' => __('lang.WhatsApp_when_sent_booking_status_fallback_provider'),
        'provider_change_customer' => __('lang.WhatsApp_when_sent_provider_change_customer'),
        'provider_change_previous_provider' => __('lang.WhatsApp_when_sent_provider_change_previous_provider'),
        'provider_change_new_provider' => __('lang.WhatsApp_when_sent_provider_change_new_provider'),
        'booking_schedule_customer' => __('lang.WhatsApp_when_sent_schedule_customer'),
        'booking_schedule_provider' => __('lang.WhatsApp_when_sent_schedule_provider'),
        'booking_payment_added_customer' => __('lang.WhatsApp_when_sent_payment_added_customer'),
        'booking_payment_added_provider' => __('lang.WhatsApp_when_sent_payment_added_provider'),
        'booking_refund_to_customer' => __('lang.WhatsApp_when_sent_booking_refund_to_customer'),
        'booking_compensation_customer' => __('lang.WhatsApp_when_sent_compensation_customer'),
        'booking_compensation_provider' => __('lang.WhatsApp_when_sent_compensation_provider'),
        'ledger_provider_payment_reminder' => __('lang.WhatsApp_when_sent_ledger_provider_payment_reminder'),
        'ledger_customer_payment_reminder' => __('lang.WhatsApp_when_sent_ledger_customer_payment_reminder'),
        'ledger_payment_received_from_provider' => __('lang.WhatsApp_when_sent_ledger_payment_received_from_provider'),
        'ledger_payment_sent_to_provider' => __('lang.WhatsApp_when_sent_ledger_payment_sent_to_provider'),
        default => '',
    };
    if ($whenSentExplanation === '' && preg_match('/^booking_status_(customer|provider)_(.+)$/', (string) $fieldKey, $m)) {
        $roleLabel = $m[1] === 'customer' ? translate('Customer') : translate('Provider');
        $seg = $m[2];
        $whenSentExplanation = match ($seg) {
            'reopened' => __('lang.WhatsApp_when_sent_segment_reopened', ['role' => $roleLabel]),
            'reopen_resolved' => __('lang.WhatsApp_when_sent_segment_reopen_resolved', ['role' => $roleLabel]),
            'disputed_close' => __('lang.WhatsApp_when_sent_segment_disputed_close', ['role' => $roleLabel]),
            'loss_making' => __('lang.WhatsApp_when_sent_segment_loss_making', [
                'role' => $roleLabel,
                'status' => translate('Booking_status_tpl_loss_making'),
            ]),
            default => __('lang.WhatsApp_when_sent_booking_status_slot', [
                'role' => $roleLabel,
                'status' => translate('Booking_status_tpl_' . $seg),
            ]),
        };
    }
    if ($whenSentExplanation === '') {
        $whenSentExplanation = __('lang.WhatsApp_when_sent_default');
    }
@endphp
<div class="wa-booking-meta-wrap border rounded p-3 mb-2 bg-light min-w-0"
     data-field="{{ $fieldKey }}"
     data-initial-tpl="{{ $initialTpl }}"
     data-initial-body-params='@json($initialBodyParams)'
     data-initial-header-params='@json($initialHeaderParams)'>
    {{-- Title (left) · Meta template select + toggle (right); select only when send is ON --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 gap-lg-3 mb-3 wa-booking-meta-top-row">
        <div class="form-label mb-0 fw-semibold min-w-0 me-2">{{ $slotTitle }}</div>
        <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0 flex-wrap justify-content-end">
            <div class="wa-booking-meta-select-slot {{ $sendEnabled ? '' : 'd-none' }}">
                <div class="wa-booking-meta-select-shell">
                    <select name="{{ $fieldKey }}_wa_tpl_id"
                            class="form-control form-control-sm js-wa-booking-meta-select"
                            data-field="{{ $fieldKey }}">
                        <option value="">{{ translate('WhatsApp_booking_meta_template_skip') }}</option>
                        @foreach($waTemplates as $wt)
                            @php $st = strtoupper((string) ($wt->status ?? '')); @endphp
                            <option value="{{ $wt->id }}"
                                    data-wa-tpl-name="{{ e($wt->name) }}"
                                    data-wa-tpl-language="{{ e((string) ($wt->language ?? '')) }}"
                                    data-wa-tpl-status="{{ e((string) ($wt->status ?? '')) }}"
                                    data-wa-tpl-category="{{ e((string) ($wt->category ?? '')) }}"
                                    @selected((string) $initialTpl === (string) $wt->id)
                                    @disabled($st !== 'APPROVED')>
                                {{ $wt->name }} ({{ $wt->language }}) — {{ $wt->status }}@if($wt->category) · {{ $wt->category }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @can('whatsapp_message_template_update')
                <button type="button"
                        class="wa-pill-toggle-btn d-inline-flex align-items-center js-wa-booking-msg-send-toggle-open"
                        data-wa-msg-key="{{ $fieldKey }}"
                        data-wa-send-enabled="{{ $sendEnabled ? '1' : '0' }}"
                        aria-haspopup="dialog"
                        aria-label="{{ $slotTitle }}">
                    <span class="wa-pill-toggle {{ $sendEnabled ? 'wa-pill-toggle--on' : 'wa-pill-toggle--off' }}" aria-hidden="true">
                        <span class="wa-pill-toggle__track">
                            <span class="wa-pill-toggle__label wa-pill-toggle__label--on">{{ translate('on') }}</span>
                            <span class="wa-pill-toggle__label wa-pill-toggle__label--off">{{ translate('WhatsApp_off') }}</span>
                            <span class="wa-pill-toggle__knob"></span>
                        </span>
                    </span>
                </button>
            @else
                <span class="wa-pill-toggle {{ $sendEnabled ? 'wa-pill-toggle--on' : 'wa-pill-toggle--off' }}" role="img" aria-label="{{ $sendEnabled ? translate('on') : translate('WhatsApp_off') }}">
                    <span class="wa-pill-toggle__track">
                        <span class="wa-pill-toggle__label wa-pill-toggle__label--on">{{ translate('on') }}</span>
                        <span class="wa-pill-toggle__label wa-pill-toggle__label--off">{{ translate('WhatsApp_off') }}</span>
                        <span class="wa-pill-toggle__knob"></span>
                    </span>
                </span>
            @endcan
        </div>
    </div>

    <p class="small text-muted mb-3 wa-booking-meta-when-sent">
        <span class="fw-semibold d-block text-body-secondary mb-1">{{ __('lang.WhatsApp_when_sent_heading') }}</span>
        <span class="d-block">{{ $whenSentExplanation }}</span>
    </p>

    @if($sendEnabled && $waTemplates->isEmpty())
        <p class="small text-warning mb-2">{{ translate('WhatsApp_booking_no_templates_in_library') }}</p>
    @endif

    {{-- Mapping table + preview only when this slot send is ON --}}
    <div class="wa-booking-meta-split d-flex flex-column flex-lg-row gap-3 gap-lg-4 align-items-stretch {{ $sendEnabled ? '' : 'd-none' }}">
        <div class="wa-booking-meta-mapping-col flex-grow-1 min-w-0">
            @if($invoiceHint)
                <p class="small text-info mb-2">{{ translate('WhatsApp_booking_meta_invoice_uses_template_body') }}</p>
            @endif

            <div class="js-wa-booking-meta-mapping mt-2" data-field="{{ $fieldKey }}" hidden></div>
        </div>

        <div class="wa-booking-meta-preview-col w-100 min-w-0">
            <div class="js-wa-booking-meta-preview wa-booking-meta-preview-inner" id="{{ $previewUid }}" hidden>
                <div class="small fw-semibold text-secondary text-uppercase mb-2" style="letter-spacing: 0.03em;">
                    {{ translate('WhatsApp_booking_preview_heading') }}
                </div>
                <div class="wa-tpl-phone-preview mx-auto mx-lg-0 ms-lg-auto">
                    <div class="wa-tpl-phone-frame rounded-4 overflow-hidden shadow">
                        <div class="wa-tpl-phone-notch d-flex align-items-center justify-content-between px-3 py-2">
                            <span class="small fw-semibold text-white-50">{{ translate('preview') }}</span>
                            <span class="small text-white-50">WhatsApp</span>
                        </div>
                        <div class="wa-tpl-phone-body p-3">
                            <div class="wa-tpl-bubble rounded-3 p-3 bg-white shadow-sm border border-light">
                                <div class="js-wa-preview-header-wrap mb-2" hidden></div>
                                <div class="small text-break wa-tpl-body-text js-wa-preview-body" style="white-space: pre-wrap;"></div>
                                <div class="text-muted mt-2 js-wa-preview-footer" style="font-size: 0.7rem;" hidden></div>
                                <div class="d-grid gap-2 mt-3 js-wa-preview-buttons" hidden></div>
                            </div>
                            <p class="text-center text-muted mt-2 mb-0" style="font-size: 0.65rem;">{{ translate('Template_preview_disclaimer') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .wa-booking-meta-wrap .wa-booking-meta-select-shell {
        max-width: min(28rem, 85vw);
        min-width: 11rem;
    }
    .wa-booking-meta-wrap .wa-booking-meta-select-shell .select2-container {
        width: 100% !important;
        max-width: 100% !important;
    }
    @media (min-width: 992px) {
        .wa-booking-meta-wrap .wa-booking-meta-split.flex-lg-row .wa-booking-meta-mapping-col {
            flex: 1 1 0;
            min-width: 0;
        }
        .wa-booking-meta-wrap .wa-booking-meta-split.flex-lg-row .wa-booking-meta-preview-col {
            flex: 0 1 300px;
            max-width: min(300px, 48%);
            border-left: 1px solid var(--bs-border-color, #dee2e6);
            padding-left: 1rem;
        }
    }
    .wa-booking-meta-wrap .wa-tpl-phone-notch { background: rgba(0, 0, 0, 0.2); }
    .wa-booking-meta-wrap .wa-tpl-phone-preview { max-width: 320px; }
    @media (min-width: 992px) {
        .wa-booking-meta-wrap .wa-booking-meta-split.flex-lg-row .wa-tpl-phone-preview {
            max-width: 100%;
        }
    }
    .wa-booking-meta-wrap .wa-tpl-phone-frame {
        background: linear-gradient(160deg, #075e54 0%, #128c7e 45%, #25d366 100%);
        border: 1px solid rgba(0,0,0,.08);
    }
    .wa-booking-meta-wrap .wa-tpl-phone-body {
        background: #e5ddd5;
        min-height: 120px;
        background-image:
            radial-gradient(circle at 20% 30%, rgba(255,255,255,.12) 0, transparent 45%),
            radial-gradient(circle at 80% 70%, rgba(0,0,0,.04) 0, transparent 40%);
    }
    .wa-booking-meta-wrap .wa-tpl-btn-fake { pointer-events: none; cursor: default; }
</style>
