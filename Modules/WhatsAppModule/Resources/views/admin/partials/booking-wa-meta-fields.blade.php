@php
    $invoiceHint = $invoiceHint ?? false;
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
@endphp
<div class="wa-booking-meta-wrap border rounded p-3 mb-2 bg-light"
     data-field="{{ $fieldKey }}"
     data-initial-tpl="{{ $initialTpl }}"
     data-initial-body-params='@json($initialBodyParams)'
     data-initial-header-params='@json($initialHeaderParams)'>
    <label class="form-label small mb-1 fw-semibold">{{ translate('WhatsApp_booking_meta_template_label') }}</label>
    <select name="{{ $fieldKey }}_wa_tpl_id"
            class="form-control form-control-sm js-wa-booking-meta-select"
            data-field="{{ $fieldKey }}">
        <option value="">{{ translate('WhatsApp_booking_meta_template_skip') }}</option>
        @foreach($waTemplates as $wt)
            @php $st = strtoupper((string) ($wt->status ?? '')); @endphp
            <option value="{{ $wt->id }}"
                    @selected((string) $initialTpl === (string) $wt->id)
                    @disabled($st !== 'APPROVED')>
                {{ $wt->name }} ({{ $wt->language }}) — {{ $wt->status }}@if($wt->category) · {{ $wt->category }} @endif
            </option>
        @endforeach
    </select>
    @if($waTemplates->isEmpty())
        <p class="small text-warning mb-2">{{ translate('WhatsApp_booking_no_templates_in_library') }}</p>
    @endif
    <p class="small text-muted mt-1 mb-2">
        @can('whatsapp_marketing_template_view')
            <a href="{{ route('admin.whatsapp.marketing.templates.index') }}" target="_blank" rel="noopener">{{ translate('WhatsApp_booking_meta_templates_manage_link') }}</a>
        @else
            <span class="text-muted">{{ translate('WhatsApp_booking_marketing_templates_path_hint') }}</span>
        @endcan
    </p>
    @if($invoiceHint)
        <p class="small text-info mb-2">{{ translate('WhatsApp_booking_meta_invoice_uses_template_body') }}</p>
    @endif

    <div class="js-wa-booking-meta-mapping mt-2" data-field="{{ $fieldKey }}" hidden></div>

    <div class="js-wa-booking-meta-preview mt-3" id="{{ $previewUid }}" hidden>
        <div class="small fw-semibold text-muted mb-2">{{ translate('WhatsApp_booking_preview_heading') }}</div>
        <div class="wa-tpl-phone-preview mx-auto wa-booking-preview-inner">
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
<style>
    .wa-booking-meta-wrap .wa-tpl-phone-notch { background: rgba(0, 0, 0, 0.2); }
    .wa-booking-meta-wrap .wa-tpl-phone-preview { max-width: 320px; }
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
