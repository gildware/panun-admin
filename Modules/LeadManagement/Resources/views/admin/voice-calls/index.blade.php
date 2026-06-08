@extends('adminmodule::layouts.new-master')

@section('title', translate('Voice_Calls'))

@push('css_or_js')
    <style>
        .voice-call-details-panel {
            background: #f8f9fb;
            border-top: 1px solid #e9ecef;
        }
        .voice-call-detail-box {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .voice-call-detail-box__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 12px;
        }
        .voice-call-detail-box__header-title {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
        }
        .voice-call-detail-box__header .material-icons {
            font-size: 18px;
            color: #6c757d;
        }
        .voice-call-detail-box .card-body {
            padding: 12px;
        }
        .voice-call-copy-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border: none;
            background: transparent;
            color: #6c757d;
            border-radius: 6px;
            flex-shrink: 0;
        }
        .voice-call-copy-btn:hover {
            background: #f1f3f5;
            color: #495057;
        }
        .voice-call-dispatch-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            margin-bottom: 12px;
        }
        .voice-call-dispatch-chip {
            display: inline-flex;
            align-items: baseline;
            gap: 6px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12px;
        }
        .voice-call-dispatch-chip__label {
            color: #6c757d;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .voice-call-dispatch-chip__value {
            font-weight: 500;
            color: #212529;
        }
        .voice-call-transcript {
            max-height: 320px;
            overflow: auto;
            padding: 16px;
            font-size: 13px;
            line-height: 1.55;
            text-align: left;
            background: #fff;
            color: #212529;
        }
        .voice-call-transcript-line {
            margin-bottom: 6px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .voice-call-transcript-line--user {
            color: #0d6efd;
        }
        .voice-call-transcript-line--llm {
            color: #495057;
        }
        .voice-call-transcript-hinglish-toggle {
            font-size: 11px;
            line-height: 1.2;
            padding: 4px 10px;
            white-space: nowrap;
        }
        .voice-call-transcript.is-translating {
            opacity: 0.65;
            pointer-events: none;
        }
        .voice-call-details-top-row {
            align-items: stretch;
        }
        .voice-call-left-stack {
            min-height: 100%;
        }
        .voice-call-recording-card {
            flex: 0 0 auto;
        }
        .voice-call-summary-card {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 180px;
        }
        .voice-call-summary-body {
            flex: 1 1 auto;
            min-height: 140px;
            overflow: auto;
        }
        .voice-call-extracted-card {
            display: flex;
            flex-direction: column;
        }
        .voice-call-extracted-body {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }
        .voice-call-extracted-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            flex: 1 1 auto;
            height: 100%;
            overflow: auto;
            align-content: start;
        }
        @media (max-width: 1200px) {
            .voice-call-extracted-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .voice-call-extracted-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .voice-call-extracted-item {
            background: #f8f9fb;
            border: 1px solid #eef1f4;
            border-radius: 8px;
            padding: 10px 12px;
            min-width: 0;
        }
        .voice-call-extracted-item__label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .voice-call-extracted-item__value {
            font-size: 14px;
            font-weight: 500;
            word-break: break-word;
        }
        .voice-call-extracted-grid:not(.is-show-all) .voice-call-extracted-item--empty {
            display: none;
        }
        .voice-call-extracted-item--empty .voice-call-extracted-item__value {
            color: #adb5bd;
            font-weight: 400;
        }
        .voice-call-extracted-view-all {
            font-size: 11px;
            line-height: 1.2;
            padding: 4px 10px;
        }
        .voice-call-recording-box .voice-call-audio-player {
            height: 36px;
        }
        .voice-call-history-table tbody tr.voice-call-details-row > td {
            box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.04);
        }
        .wa-followup-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .wa-followup-table {
            font-size: 14px;
            width: max-content;
            min-width: 100%;
        }
        .wa-followup-table thead th {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }
        .wa-followup-table tbody td {
            white-space: nowrap;
            vertical-align: middle;
            font-size: 14px;
        }
        .wa-followup-table .badge {
            font-size: 12px;
            font-weight: 500;
        }
        .wa-followup-table .btn-sm {
            font-size: 13px;
        }
        .wa-followup-contact-name {
            font-weight: 600;
            color: #212529;
            font-size: 14px;
        }
        .wa-followup-tags {
            display: inline-flex;
            flex-wrap: nowrap;
            gap: 4px;
            align-items: center;
        }
        .wa-followup-table tbody tr.wa-followup-details-row > td {
            box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.04);
            white-space: normal;
            width: 100%;
            max-width: 0;
        }
        .wa-followup-details-row .voice-call-details-panel {
            max-width: 100%;
            min-width: 0;
            overflow: hidden;
        }
        .wa-followup-details-row .wa-followup-call-context-cell {
            min-width: 0;
            max-width: 100%;
        }
        .wa-followup-call-context-header {
            align-items: flex-start;
        }
        .wa-followup-call-context-heading {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .wa-followup-call-context-heading__main {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #212529;
            line-height: 1.3;
        }
        .wa-followup-call-context-heading__main .material-icons {
            font-size: 18px;
            color: #0d6efd;
        }
        .wa-followup-call-context-heading__sub {
            font-size: 11px;
            color: #6c757d;
            line-height: 1.3;
            padding-left: 24px;
        }
        .wa-followup-details-row .wa-followup-call-context-body {
            max-height: 320px;
            overflow-y: auto;
        }
        .wa-followup-context-table {
            table-layout: fixed;
            width: 100%;
            margin-bottom: 0;
        }
        .wa-followup-context-col-label {
            width: 32%;
        }
        .wa-followup-context-col-value {
            width: 68%;
        }
        .wa-followup-context-table .wa-followup-context-row > th,
        .wa-followup-context-table .wa-followup-context-row > td {
            vertical-align: top;
            padding: 0.5rem 0.75rem;
            border-color: #eef1f4;
        }
        .wa-followup-context-table .wa-followup-context-row > th {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #6c757d;
            background: #f8f9fb;
            white-space: normal;
            word-break: break-word;
        }
        .wa-followup-context-table .wa-followup-context-row > td {
            font-size: 15px;
            font-weight: 500;
            color: #212529;
            white-space: pre-wrap;
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.5;
        }
        .wa-followup-context-table .wa-followup-lead-summary-value {
            font-size: 16px;
            line-height: 1.55;
        }
        .wa-followup-lead-summary-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }
        .wa-followup-lead-summary-label .wa-followup-context-label__text {
            flex: 1 1 auto;
            min-width: 0;
        }
        .wa-followup-context-table:not(.is-show-all) .wa-followup-context-row--empty {
            display: none;
        }
        .wa-followup-context-table .wa-followup-context-row:last-child > th,
        .wa-followup-context-table .wa-followup-context-row:last-child > td {
            border-bottom: 0;
        }
        #wa-followup-action-bar {
            border-left: 3px solid var(--bs-primary, #0d6efd);
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                <h2 class="page-title mb-1">{{ translate('Voice_Calls') }}</h2>
            </div>

            <ul class="nav nav--tabs mb-3" id="voice-call-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-voice-tab="place">
                        {{ translate('Place_Call') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-voice-tab="bulk">
                        {{ translate('Bulk_Calls') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-voice-tab="whatsapp_followup">
                        {{ translate('WhatsApp_Followup_Calls') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-voice-tab="voice_cron">
                        {{ translate('Cron_Jobs') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-voice-tab="history">
                        {{ translate('Call_History') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-voice-tab="forwarded">
                        {{ translate('Forwarded_Calls') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-voice-tab="callback">
                        {{ translate('Callback_Calls') }}
                    </a>
                </li>
            </ul>

            <div id="voice-tab-place">
                @if(!$configured)
                    <div class="alert alert-warning">
                        {{ translate('OmniDimension_not_configured_hint') }}
                        <code>OMNIDIMENSION_API_KEY</code>
                    </div>
                @elseif($loadError)
                    <div class="alert alert-danger">
                        {{ translate('OmniDimension_load_failed') }}
                        <span class="d-block small mt-1 text-muted">{{ $loadError }}</span>
                    </div>
                @elseif(count($agents) === 0)
                    <div class="alert alert-warning">
                        {{ translate('OmniDimension_no_agents_hint') }}
                    </div>
                @endif

                @if($configured && !$loadError && count($phoneNumbers) === 0)
                    <div class="alert alert-info">
                        {{ translate('OmniDimension_no_phone_numbers_hint') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-body p-30">
                        <form action="{{ route('admin.voice-call.store') }}" method="post" id="voice-call-form">
                            @csrf

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-30">
                                        <label class="form-label">{{ translate('OmniDimension_Agent') }} *</label>
                                        <select class="form-select js-select" name="agent_id" id="agent_id" required
                                                {{ !$configured || $loadError || count($agents) === 0 ? 'disabled' : '' }}>
                                            <option value="">{{ translate('Select_agent') }}</option>
                                            @foreach($agents as $agent)
                                                @php
                                                    $typeLabel = $agent['bot_call_type'] !== '' ? ' (' . $agent['bot_call_type'] . ')' : '';
                                                @endphp
                                                <option value="{{ $agent['id'] }}"
                                                        data-label="{{ $agent['name'] }}"
                                                        {{ (string) old('agent_id') === (string) $agent['id'] ? 'selected' : '' }}>
                                                    {{ $agent['name'] }}{{ $typeLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('agent_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-30">
                                        <label class="form-label">{{ translate('Caller_Phone_Number') }}</label>
                                        <select class="form-select js-select" name="from_number_id" id="from_number_id"
                                                {{ !$configured || $loadError ? 'disabled' : '' }}>
                                            <option value="">{{ translate('Select_phone_number') }}</option>
                                            @foreach($phoneNumbers as $number)
                                                @php
                                                    $label = trim($number['name']) !== ''
                                                        ? $number['name'] . ' — ' . $number['phone_number']
                                                        : $number['phone_number'];
                                                    if ($number['number_provider'] !== '') {
                                                        $label .= ' (' . $number['number_provider'] . ')';
                                                    }
                                                @endphp
                                                <option value="{{ $number['id'] }}"
                                                        data-label="{{ $label }}"
                                                        {{ (string) old('from_number_id') === (string) $number['id'] ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('from_number_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-30">
                                        <label class="form-label">{{ translate('Customer_Name') }} *</label>
                                        <input type="text"
                                               class="form-control"
                                               name="customer_name"
                                               required
                                               value="{{ old('customer_name') }}"
                                               placeholder="{{ translate('Customer_Name') }}"
                                               {{ !$configured ? 'disabled' : '' }}>
                                        @error('customer_name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-30">
                                        <label class="form-label">{{ translate('Phone_Number') }} *</label>
                                        <input type="text"
                                               class="form-control"
                                               name="phone_number"
                                               required
                                               value="{{ old('phone_number') }}"
                                               placeholder="+91XXXXXXXXXX"
                                               {{ !$configured ? 'disabled' : '' }}>
                                        <small class="text-muted">{{ translate('Voice_call_phone_hint') }}</small>
                                        @error('phone_number')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="mb-30">
                                        <label class="form-label">{{ translate('Handled_By') }} ({{ translate('name_of_employee') }}) *</label>
                                        <select class="form-select js-select" name="handled_by" required {{ !$configured ? 'disabled' : '' }}>
                                            <option value="">{{ translate('Select_employee') }}</option>
                                            @foreach(($employees ?? []) as $employee)
                                                @php
                                                    $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                                    $label = $fullName ?: $employee->email;
                                                @endphp
                                                <option value="{{ $employee->id }}"
                                                        {{ old('handled_by', $currentEmployeeId ?? null) == $employee->id ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('handled_by')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-30">
                                        <label class="form-label">{{ translate('Remarks') }}</label>
                                        <textarea class="form-control"
                                                  name="remarks"
                                                  rows="4"
                                                  placeholder="{{ translate('Remarks') }}"
                                                  {{ !$configured ? 'disabled' : '' }}>{{ old('remarks') }}</textarea>
                                        @error('remarks')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-30">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="log_outbound_enquiry"
                                                   id="log_outbound_enquiry"
                                                   value="1"
                                                   {{ old('log_outbound_enquiry', '1') ? 'checked' : '' }}
                                                   {{ !$configured ? 'disabled' : '' }}>
                                            <label class="form-check-label" for="log_outbound_enquiry">
                                                {{ translate('Log_as_outbound_enquiry') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="border rounded p-3 p-lg-4 mb-30 bg-light">
                                        <h5 class="mb-1">{{ translate('Call_Context') }}</h5>
                                        <p class="text-muted small mb-4">{{ translate('Call_context_hint') }}</p>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Call_Reason') }}</label>
                                                    <select class="form-select js-select" name="call_reason" {{ !$configured ? 'disabled' : '' }}>
                                                        <option value="">{{ translate('Select') }}</option>
                                                        @foreach(($callReasons ?? []) as $reason)
                                                            <option value="{{ $reason }}"
                                                                    {{ old('call_reason') === $reason ? 'selected' : '' }}>
                                                                {{ ($callReasonLabels ?? [])[$reason] ?? $reason }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('call_reason')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Lead_Status') }}</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="lead_status"
                                                           value="{{ old('lead_status') }}"
                                                           placeholder="{{ translate('Lead_Status') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('lead_status')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Lead_Summary') }}</label>
                                                    <textarea class="form-control"
                                                              name="lead_summary"
                                                              rows="3"
                                                              placeholder="{{ translate('Lead_Summary') }}"
                                                              {{ !$configured ? 'disabled' : '' }}>{{ old('lead_summary') }}</textarea>
                                                    @error('lead_summary')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Service_Category') }}</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="service_category"
                                                           value="{{ old('service_category') }}"
                                                           placeholder="{{ translate('Service_Category') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('service_category')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Service_Details') }}</label>
                                                    <textarea class="form-control"
                                                              name="service_details"
                                                              rows="3"
                                                              placeholder="{{ translate('Service_Details') }}"
                                                              {{ !$configured ? 'disabled' : '' }}>{{ old('service_details') }}</textarea>
                                                    @error('service_details')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('District') }}</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="district"
                                                           value="{{ old('district') }}"
                                                           placeholder="{{ translate('District') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('district')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Area') }}</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="area"
                                                           value="{{ old('area') }}"
                                                           placeholder="{{ translate('Area') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('area')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Preferred_Date') }}</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="preferred_date"
                                                           value="{{ old('preferred_date') }}"
                                                           placeholder="{{ translate('Preferred_Date') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('preferred_date')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    <label class="form-label">{{ translate('Preferred_Time') }}</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="preferred_time"
                                                           value="{{ old('preferred_time') }}"
                                                           placeholder="{{ translate('Preferred_Time') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('preferred_time')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-0">
                                                    <label class="form-label">{{ translate('Notes') }}</label>
                                                    <textarea class="form-control"
                                                              name="notes"
                                                              rows="3"
                                                              placeholder="{{ translate('Notes') }}"
                                                              {{ !$configured ? 'disabled' : '' }}>{{ old('notes') }}</textarea>
                                                    @error('notes')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <input type="hidden" name="agent_label" id="agent_label" value="{{ old('agent_label') }}">
                                    <input type="hidden" name="from_number_label" id="from_number_label" value="{{ old('from_number_label') }}">

                                    <div class="d-flex justify-content-end gap-20 mt-10">
                                        <button class="btn btn--primary" type="submit"
                                                {{ !$configured || $loadError || count($agents) === 0 ? 'disabled' : '' }}>
                                            <span class="material-icons align-middle" style="font-size:18px;">call</span>
                                            {{ translate('Place_Voice_Call') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="voice-tab-bulk" class="d-none">
                @include('leadmanagement::admin.voice-calls._bulk', [
                    'configured' => $configured,
                    'loadError' => $loadError,
                    'phoneNumbers' => $phoneNumbers,
                    'categories' => $categories ?? collect(),
                    'audienceCounts' => $audienceCounts ?? [],
                    'categoryRecipientCounts' => $categoryRecipientCounts ?? [],
                    'callReasons' => $callReasons ?? [],
                    'callReasonLabels' => $callReasonLabels ?? [],
                ])
            </div>

            <div id="voice-tab-whatsapp-followup" class="d-none">
                @include('leadmanagement::admin.voice-calls._whatsapp_followup', [
                    'configured' => $configured,
                    'phoneNumbers' => $phoneNumbers,
                    'waChatTags' => $waChatTags ?? [],
                    'customerLeadTags' => $customerLeadTags ?? [],
                    'waFollowupDefaults' => $waFollowupDefaults ?? ['silent_min_hours' => 2],
                ])
            </div>

            <div id="voice-tab-voice-cron" class="d-none">
                @include('leadmanagement::admin.voice-calls._voice_cron_jobs', [
                    'configured' => $configured,
                    'phoneNumbers' => $phoneNumbers,
                    'waChatTags' => $waChatTags ?? [],
                    'customerLeadTags' => $customerLeadTags ?? [],
                    'voiceCronRules' => $voiceCronRules ?? collect(),
                    'voiceCronTableReady' => $voiceCronTableReady ?? false,
                ])
            </div>

            <div id="voice-tab-history" class="d-none">
                <div id="voice-history-content" class="text-center text-muted py-5">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ translate('Loading') }}…
                </div>
            </div>

            <div id="voice-tab-forwarded" class="d-none">
                <div id="voice-forwarded-content" class="text-center text-muted py-5">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ translate('Loading') }}…
                </div>
            </div>

            <div id="voice-tab-callback" class="d-none">
                <div id="voice-callback-content" class="text-center text-muted py-5">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ translate('Loading') }}…
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="voiceCallDeleteModal" tabindex="-1" aria-labelledby="voiceCallDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body mb-30 pb-0 text-center">
                    <img width="80" src="{{ asset('assets/admin-module/img/icons/status-on.png') }}" alt="" class="mb-20">
                    <h3 class="mb-3">{{ translate('Are you sure') }}?</h3>
                    <p class="mb-0">{{ translate('Voice_call_history_delete_confirm') }}</p>
                    <p class="mb-0 mt-2 text-muted small" id="voiceCallDeleteLabel"></p>
                    <div class="btn--container mt-30 justify-content-center">
                        <button type="button" class="btn btn--secondary min-w-120 rounded" data-bs-dismiss="modal">{{ translate('No') }}</button>
                        <button type="button" class="btn btn--danger min-w-120 rounded" id="voiceCallDeleteConfirm">{{ translate('Yes') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const historyUrl = @json(route('admin.voice-call.history'));
            const forwardedUrl = @json(route('admin.voice-call.forwarded'));
            const callbackUrl = @json(route('admin.voice-call.callback'));
            const bulkCampaignsUrl = @json(route('admin.voice-call.bulk.campaigns'));
            const waFollowupListUrl = @json(route('admin.voice-call.whatsapp-followup.list'));
            const waFollowupSummaryGenerateUrl = @json(route('admin.voice-call.whatsapp-followup.summary.generate'));
            const voiceCronRunsUrl = @json(route('admin.voice-call.cron-jobs.runs'));
            const waFollowupCallReasonLabels = @json($callReasonLabels ?? []);
            const waFollowupContextKeys = @json($contextKeys ?? []);
            const waFollowupCallReasonLabel = @json(translate('Call_Reason'));
            const waFollowupLeadSummaryLabel = @json(translate('Lead_Summary'));
            const waFollowupNoSummaryText = @json(translate('No_summary_yet'));
            const waFollowupRegenerateSummaryLabel = @json(translate('Regenerate_summary'));
            const waFollowupGenerateSummaryLabel = @json(translate('Generate_summary'));
            const waFollowupSummaryOutdatedLabel = @json(translate('Summary_outdated'));
            const historyDestroyUrl = @json(url('admin/voice-call/history'));
            const transcriptHinglishUrl = @json(route('admin.voice-call.transcript.hinglish'));
            const strShowHinglish = @json(translate('Show_Hinglish'));
            const strShowOriginal = @json(translate('Show_Original'));
            const strTranslating = @json(translate('Translating'));
            const strTranslatingLongHint = @json(translate('Translating_long_transcript_hint'));
            const strTranscriptHinglishFailed = @json(translate('Transcript_hinglish_translation_failed'));
            const csrfToken = @json(csrf_token());
            const tabLinks = document.querySelectorAll('#voice-call-tabs [data-voice-tab]');
            const placePanel = document.getElementById('voice-tab-place');
            const bulkPanel = document.getElementById('voice-tab-bulk');
            const waFollowupPanel = document.getElementById('voice-tab-whatsapp-followup');
            const voiceCronPanel = document.getElementById('voice-tab-voice-cron');
            const historyPanel = document.getElementById('voice-tab-history');
            const forwardedPanel = document.getElementById('voice-tab-forwarded');
            const callbackPanel = document.getElementById('voice-tab-callback');
            const historyContent = document.getElementById('voice-history-content');
            const forwardedContent = document.getElementById('voice-forwarded-content');
            const callbackContent = document.getElementById('voice-callback-content');
            const bulkCampaignsContent = document.getElementById('voice-bulk-campaigns-content');
            const waFollowupListContent = document.getElementById('wa-followup-list-content');
            const waFollowupActionBar = document.getElementById('wa-followup-action-bar');
            const voiceCronRunsContent = document.getElementById('voice-cron-runs-content');
            let voiceCronRunsLoaded = false;
            let voiceCronRunsLoading = false;
            let currentVoiceCronRunsParams = new URLSearchParams();
            const deleteModalEl = document.getElementById('voiceCallDeleteModal');
            const deleteConfirmBtn = document.getElementById('voiceCallDeleteConfirm');
            const deleteLabelEl = document.getElementById('voiceCallDeleteLabel');
            let bulkLoaded = false;
            let bulkLoading = false;
            let waFollowupLoaded = false;
            let waFollowupLoading = false;
            let currentWaFollowupParams = new URLSearchParams();
            let activeTab = 'place';
            let currentHistoryParams = new URLSearchParams();
            let currentForwardedParams = new URLSearchParams();
            let currentCallbackParams = new URLSearchParams();
            let currentBulkParams = new URLSearchParams();
            let pendingDeleteCallId = null;
            const transcriptHinglishCache = new Map();
            const waFollowupSummaryCache = new Map();

            function escapeHtml(text) {
                return String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function findDetailsRow(phone) {
                if (!waFollowupListContent || !phone) return null;
                return waFollowupListContent.querySelector('.wa-followup-details-row[data-phone="' + phone + '"]');
            }

            function findGenerateButton(phone) {
                if (!waFollowupListContent || !phone) return null;
                const detailsRow = findDetailsRow(phone);
                return detailsRow ? detailsRow.querySelector('.wa-followup-generate-summary[data-phone="' + phone + '"]') : null;
            }

            function encodeCopyB64(text) {
                try {
                    return btoa(unescape(encodeURIComponent(text)));
                } catch (err) {
                    return '';
                }
            }

            function waFollowupContextKeyLabel(key) {
                if (key === 'call_reason') {
                    return waFollowupCallReasonLabel;
                }
                if (key === 'lead_summary') {
                    return waFollowupLeadSummaryLabel;
                }

                return String(key || '').replace(/_/g, ' ').replace(/\b\w/g, function (c) {
                    return c.toUpperCase();
                });
            }

            function waFollowupContextDisplayValue(key, value) {
                const text = String(value || '').trim();
                if (key === 'call_reason' && text !== '') {
                    return waFollowupCallReasonLabels[text] || text;
                }

                return text;
            }

            function waFollowupContextIsFilled(value) {
                const text = String(value || '').trim();
                if (text === '') {
                    return false;
                }

                return ['—', '-', 'n/a', 'na', 'none', 'null'].indexOf(text.toLowerCase()) === -1;
            }

            function buildWaFollowupCallContextCopyText(context) {
                const lines = [];
                (waFollowupContextKeys || []).forEach(function (key) {
                    const raw = context && context[key] ? String(context[key]) : '';
                    if (!waFollowupContextIsFilled(raw)) {
                        return;
                    }
                    lines.push(waFollowupContextKeyLabel(key) + ': ' + waFollowupContextDisplayValue(key, raw));
                });

                return lines.join('\n');
            }

            function renderWaFollowupCallContextGrid(context, phone) {
                let hasEmpty = false;
                const rows = (waFollowupContextKeys || []).map(function (key) {
                    const raw = context && context[key] ? String(context[key]) : '';
                    const filled = waFollowupContextIsFilled(raw);

                    if (key === 'lead_summary') {
                        const genTitle = filled ? waFollowupRegenerateSummaryLabel : waFollowupGenerateSummaryLabel;
                        const displayValue = filled
                            ? escapeHtml(waFollowupContextDisplayValue(key, raw))
                            : escapeHtml(waFollowupNoSummaryText);

                        return '<tr class="wa-followup-context-row wa-followup-lead-summary-row">' +
                            '<th scope="row" class="wa-followup-context-label wa-followup-lead-summary-label">' +
                            '<span class="wa-followup-context-label__text">' + escapeHtml(waFollowupLeadSummaryLabel) + '</span>' +
                            '<button type="button" class="voice-call-copy-btn wa-followup-generate-summary" data-phone="' + escapeHtml(phone || '') + '" title="' + escapeHtml(genTitle) + '" aria-label="' + escapeHtml(genTitle) + '">' +
                            '<span class="material-icons" aria-hidden="true">autorenew</span></button></th>' +
                            '<td class="wa-followup-context-value wa-followup-lead-summary-value' + (filled ? '' : ' text-muted') + '">' + displayValue + '</td></tr>';
                    }

                    if (!filled) {
                        hasEmpty = true;
                    }

                    const label = escapeHtml(waFollowupContextKeyLabel(key));
                    const value = filled
                        ? escapeHtml(waFollowupContextDisplayValue(key, raw))
                        : '<span class="text-muted">—</span>';

                    return '<tr class="wa-followup-context-row' + (filled ? '' : ' wa-followup-context-row--empty') + '">' +
                        '<th scope="row" class="wa-followup-context-label">' + label + '</th>' +
                        '<td class="wa-followup-context-value">' + value + '</td></tr>';
                }).join('');

                return {
                    html: rows,
                    hasEmpty: hasEmpty,
                    copyText: buildWaFollowupCallContextCopyText(context || {}),
                };
            }

            function updateCallContextPanel(phone, context) {
                const detailsRow = findDetailsRow(phone);
                if (!detailsRow) return;

                const cell = detailsRow.querySelector('.wa-followup-call-context-cell[data-phone="' + phone + '"]');
                const card = cell ? cell.querySelector('.wa-followup-call-context-card') : null;
                const table = card ? card.querySelector('.wa-followup-context-table') : null;
                const grid = card ? card.querySelector('.wa-followup-call-context-grid') : null;
                const copyBtn = card ? card.querySelector('.wa-followup-call-context-copy') : null;
                const viewAllBtn = card ? card.querySelector('.wa-followup-call-context-view-all') : null;

                if (!grid) return;

                const rendered = renderWaFollowupCallContextGrid(context || {}, phone);
                grid.innerHTML = rendered.html;
                if (table) {
                    table.classList.remove('is-show-all');
                }

                if (copyBtn) {
                    if (rendered.copyText) {
                        copyBtn.classList.remove('d-none');
                        copyBtn.setAttribute('data-copy-b64', encodeCopyB64(rendered.copyText));
                        copyBtn.dataset.copyBound = '';
                    } else {
                        copyBtn.classList.add('d-none');
                        copyBtn.setAttribute('data-copy-b64', '');
                    }
                }

                if (viewAllBtn) {
                    viewAllBtn.classList.toggle('d-none', !rendered.hasEmpty);
                }

                bindWaFollowupGenerateSummaryButtons();
                bindWaFollowupCopyButtons();
            }

            function updateLeadSummaryInContext(phone, summary, needsRefresh) {
                const detailsRow = findDetailsRow(phone);
                if (!detailsRow) return;

                const valueEl = detailsRow.querySelector('.wa-followup-lead-summary-value');
                let outdatedEl = detailsRow.querySelector('.wa-followup-summary-outdated');
                const genBtn = findGenerateButton(phone);
                const hasSummary = Boolean(summary);

                if (valueEl) {
                    valueEl.textContent = hasSummary ? summary : waFollowupNoSummaryText;
                    valueEl.classList.toggle('text-muted', !hasSummary);
                }

                if (genBtn) {
                    genBtn.disabled = false;
                    const labelText = hasSummary ? waFollowupRegenerateSummaryLabel : waFollowupGenerateSummaryLabel;
                    genBtn.innerHTML = '<span class="material-icons" aria-hidden="true">autorenew</span>';
                    genBtn.title = labelText;
                    genBtn.setAttribute('aria-label', labelText);
                }

                const summaryItem = detailsRow.querySelector('.wa-followup-lead-summary-row');
                if (needsRefresh && summaryItem && !outdatedEl) {
                    outdatedEl = document.createElement('p');
                    outdatedEl.className = 'text-warning small mb-1 wa-followup-summary-outdated';
                    outdatedEl.textContent = waFollowupSummaryOutdatedLabel;
                    if (valueEl) {
                        summaryItem.querySelector('.wa-followup-lead-summary-value')?.prepend(outdatedEl);
                    }
                } else if (!needsRefresh && outdatedEl) {
                    outdatedEl.remove();
                }
            }

            function generateWaFollowupSummary(phone) {
                if (!phone) return;

                const btn = findGenerateButton(phone);

                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                }

                fetch(waFollowupSummaryGenerateUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: new URLSearchParams({ phone: phone, _token: csrfToken }),
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data.ok || !data.summary) {
                            throw new Error('generate_failed');
                        }

                        waFollowupSummaryCache.set(phone, data.summary);
                        if (data.call_context) {
                            updateCallContextPanel(phone, data.call_context);
                        } else {
                            updateLeadSummaryInContext(phone, data.summary, false);
                        }
                    })
                    .catch(function () {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('{{ translate('WhatsApp_followup_summary_failed') }}');
                        }
                        updateLeadSummaryInContext(phone, waFollowupSummaryCache.get(phone) || null, false);
                    });
            }

            function bindWaFollowupGenerateSummaryButtons() {
                if (!waFollowupListContent) return;

                waFollowupListContent.querySelectorAll('.wa-followup-generate-summary').forEach(function (btn) {
                    if (btn.dataset.generateBound === '1') return;
                    btn.dataset.generateBound = '1';
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        generateWaFollowupSummary(btn.getAttribute('data-phone'));
                    });
                });
            }

            function bindWaFollowupCopyButtons() {
                if (!waFollowupListContent) return;

                waFollowupListContent.querySelectorAll('.wa-followup-call-context-copy').forEach(function (btn) {
                    if (btn.dataset.copyBound === '1') return;
                    btn.dataset.copyBound = '1';
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const b64 = btn.getAttribute('data-copy-b64') || '';
                        if (!b64) return;

                        let text = '';
                        try {
                            text = decodeURIComponent(escape(atob(b64)));
                        } catch (err) {
                            return;
                        }

                        copyTextFallback(text, function () {
                            if (typeof toastr !== 'undefined') {
                                toastr.success('{{ translate('Copied') }}');
                            }
                        });
                    });
                });

                waFollowupListContent.querySelectorAll('.wa-followup-call-context-view-all').forEach(function (btn) {
                    if (btn.dataset.viewAllBound === '1') return;
                    btn.dataset.viewAllBound = '1';
                    btn.addEventListener('click', function () {
                        const table = btn.closest('.wa-followup-call-context-card')?.querySelector('.wa-followup-context-table');
                        if (table) {
                            table.classList.add('is-show-all');
                            btn.classList.add('d-none');
                        }
                    });
                });
            }

            function bindWaFollowupSummaryToggles() {
                if (!waFollowupListContent) return;

                waFollowupListContent.querySelectorAll('.wa-followup-summary-toggle').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const row = btn.closest('tr');
                        const detailsRow = row?.nextElementSibling;
                        if (!detailsRow?.classList.contains('wa-followup-details-row')) {
                            return;
                        }

                        const isHidden = detailsRow.classList.contains('d-none');
                        detailsRow.classList.toggle('d-none', !isHidden);
                        btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                        btn.textContent = isHidden ? @json(translate('Hide')) : @json(translate('Summary'));

                        if (isHidden) {
                            bindWaFollowupCopyButtons();
                            bindWaFollowupGenerateSummaryButtons();
                        }
                    });
                });
            }

            function openWaFollowupInWhatsApp(phone, prepareUrl) {
                if (!phone || !prepareUrl) return;

                const newTab = window.open('', '_blank');
                if (newTab) {
                    newTab.opener = null;
                    newTab.document.write('<p style="font-family:sans-serif;padding:1rem;color:#666;">{{ translate('Loading') }}…</p>');
                }

                fetch(prepareUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: new URLSearchParams({ phone: phone, _token: csrfToken }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            if (!response.ok) {
                                throw data;
                            }
                            return data;
                        });
                    })
                    .then(function (res) {
                        if (res && res.redirect_url) {
                            if (newTab) {
                                newTab.location.href = res.redirect_url;
                            } else {
                                window.open(res.redirect_url, '_blank', 'noopener,noreferrer');
                            }
                            return;
                        }
                        throw new Error('no_redirect');
                    })
                    .catch(function (err) {
                        if (newTab) {
                            newTab.close();
                        }
                        const msg = (err && err.message) ? err.message : '{{ translate('Something_went_wrong') }}';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(msg);
                        }
                    });
            }

            function syncLabels() {
                const agentSelect = document.getElementById('agent_id');
                const fromSelect = document.getElementById('from_number_id');
                const agentLabel = document.getElementById('agent_label');
                const fromLabel = document.getElementById('from_number_label');

                if (agentSelect && agentLabel) {
                    const opt = agentSelect.options[agentSelect.selectedIndex];
                    agentLabel.value = opt ? (opt.getAttribute('data-label') || opt.text || '') : '';
                }
                if (fromSelect && fromLabel) {
                    const opt = fromSelect.options[fromSelect.selectedIndex];
                    fromLabel.value = opt ? (opt.getAttribute('data-label') || opt.text || '') : '';
                }
            }

            function setActiveTab(tab) {
                activeTab = tab;
                tabLinks.forEach(function (link) {
                    link.classList.toggle('active', link.getAttribute('data-voice-tab') === tab);
                });
                placePanel.classList.toggle('d-none', tab !== 'place');
                bulkPanel.classList.toggle('d-none', tab !== 'bulk');
                waFollowupPanel.classList.toggle('d-none', tab !== 'whatsapp_followup');
                voiceCronPanel.classList.toggle('d-none', tab !== 'voice_cron');
                historyPanel.classList.toggle('d-none', tab !== 'history');
                forwardedPanel.classList.toggle('d-none', tab !== 'forwarded');
                callbackPanel.classList.toggle('d-none', tab !== 'callback');

                const url = new URL(window.location.href);
                if (tab === 'history' || tab === 'forwarded' || tab === 'callback' || tab === 'bulk' || tab === 'whatsapp_followup' || tab === 'voice_cron') {
                    url.searchParams.set('tab', tab);
                } else {
                    url.searchParams.delete('tab');
                }
                window.history.replaceState({}, '', url.toString());
            }

            function initSelect2In(container) {
                if (typeof $ === 'undefined' || !$.fn.select2) {
                    return;
                }
                $(container).find('.js-select').each(function () {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2();
                    }
                });
            }

            function bindBulkEvents() {
                if (!bulkCampaignsContent) return;

                const form = bulkCampaignsContent.querySelector('#voice-bulk-filter-form');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        loadBulkCampaigns(new URLSearchParams(new FormData(form)));
                    });
                }

                bulkCampaignsContent.querySelector('.voice-bulk-reset')?.addEventListener('click', function () {
                    loadBulkCampaigns(new URLSearchParams());
                });

                bulkCampaignsContent.querySelectorAll('.voice-bulk-page-link').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const params = new URLSearchParams();
                        params.set('page', link.getAttribute('data-page') || '1');
                        const status = link.getAttribute('data-status');
                        if (status) params.set('status', status);
                        loadBulkCampaigns(params);
                    });
                });
            }

            function loadBulkCampaigns(params) {
                if (bulkLoading || !bulkCampaignsContent) {
                    return;
                }

                currentBulkParams = new URLSearchParams(params.toString());
                bulkLoading = true;
                bulkCampaignsContent.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';

                fetch(bulkCampaignsUrl + (params.toString() ? ('?' + params.toString()) : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('load_failed');
                        return response.text();
                    })
                    .then(function (html) {
                        bulkCampaignsContent.innerHTML = html;
                        initSelect2In(bulkCampaignsContent);
                        bindBulkEvents();
                        bulkLoaded = true;
                    })
                    .catch(function () {
                        bulkCampaignsContent.innerHTML = '<div class="alert alert-danger mb-0">{{ translate('Voice_bulk_campaigns_load_failed') }}</div>';
                    })
                    .finally(function () {
                        bulkLoading = false;
                    });
            }

            function bindBulkFormToggles() {
                const audienceType = document.getElementById('voice_bulk_audience_type');
                const categoryWrap = document.getElementById('voice_bulk_category_wrap');
                const csvWrap = document.getElementById('voice_bulk_csv_wrap');
                const sendOption = document.getElementById('voice_bulk_send_option');
                const scheduleWrap = document.getElementById('voice_bulk_schedule_wrap');
                const autoRetry = document.getElementById('auto_retry');
                const retryWrap = document.getElementById('voice_bulk_retry_wrap');

                audienceType?.addEventListener('change', function () {
                    const value = audienceType.value;
                    categoryWrap?.classList.toggle('d-none', value !== 'providers_by_category');
                    csvWrap?.classList.toggle('d-none', value !== 'csv_import');
                });

                sendOption?.addEventListener('change', function () {
                    scheduleWrap?.classList.toggle('d-none', sendOption.value !== 'schedule');
                });

                autoRetry?.addEventListener('change', function () {
                    retryWrap?.classList.toggle('d-none', !autoRetry.checked);
                });
            }

            function updateWaFollowupSelectionUi() {
                if (!waFollowupListContent) return;
                const checks = waFollowupListContent.querySelectorAll('.wa-followup-row-check:checked');
                const count = checks.length;
                const countEl = document.getElementById('wa-followup-selected-count');
                const openBtn = document.getElementById('wa-followup-open-dispatch');
                if (countEl) countEl.textContent = String(count);
                if (openBtn) openBtn.disabled = count === 0;
                if (waFollowupActionBar) waFollowupActionBar.classList.toggle('d-none', count === 0);
            }

            function bindWaFollowupListEvents() {
                if (!waFollowupListContent) return;

                waFollowupListContent.querySelectorAll('.wa-followup-row-check').forEach(function (cb) {
                    cb.addEventListener('change', updateWaFollowupSelectionUi);
                });

                const selectAll = waFollowupListContent.querySelector('#wa-followup-select-all');
                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        waFollowupListContent.querySelectorAll('.wa-followup-row-check').forEach(function (cb) {
                            cb.checked = selectAll.checked;
                        });
                        updateWaFollowupSelectionUi();
                    });
                }

                waFollowupListContent.querySelectorAll('.wa-followup-page-link').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const params = new URLSearchParams(currentWaFollowupParams.toString());
                        params.set('page', link.getAttribute('data-page') || '1');
                        loadWaFollowupList(params);
                    });
                });

                waFollowupListContent.querySelectorAll('.wa-followup-open-whatsapp').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        openWaFollowupInWhatsApp(
                            btn.getAttribute('data-phone'),
                            btn.getAttribute('data-prepare-url')
                        );
                    });
                });

                bindWaFollowupSummaryToggles();
                bindWaFollowupCopyButtons();
                bindWaFollowupGenerateSummaryButtons();
                updateWaFollowupSelectionUi();
            }

            function loadWaFollowupList(params) {
                if (waFollowupLoading || !waFollowupListContent) return;

                currentWaFollowupParams = new URLSearchParams(params.toString());
                waFollowupLoading = true;
                waFollowupListContent.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';

                fetch(waFollowupListUrl + (params.toString() ? ('?' + params.toString()) : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('load_failed');
                        return response.text();
                    })
                    .then(function (html) {
                        waFollowupListContent.innerHTML = html;
                        bindWaFollowupListEvents();
                        waFollowupLoaded = true;
                    })
                    .catch(function () {
                        waFollowupListContent.innerHTML = '<div class="alert alert-danger mb-0">{{ translate('whatsapp_followup_load_failed') }}</div>';
                    })
                    .finally(function () {
                        waFollowupLoading = false;
                    });
            }

            function bindWaFollowupPanelEvents() {
                const filterForm = document.getElementById('wa-followup-filter-form');
                if (filterForm) {
                    filterForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        loadWaFollowupList(new URLSearchParams(new FormData(filterForm)));
                    });
                }

                document.getElementById('wa-followup-reset')?.addEventListener('click', function () {
                    if (filterForm) filterForm.reset();
                    initSelect2In(waFollowupPanel);
                    loadWaFollowupList(new URLSearchParams({ silent_min_hours: '2', human_support: 'exclude', exclude_called_within_hours: '24' }));
                });

                document.getElementById('wa_followup_send_option')?.addEventListener('change', function () {
                    document.getElementById('wa_followup_schedule_wrap')?.classList.toggle('d-none', this.value !== 'schedule');
                });

                document.getElementById('wa-followup-open-dispatch')?.addEventListener('click', function () {
                    if (!waFollowupListContent) return;
                    const phones = [];
                    waFollowupListContent.querySelectorAll('.wa-followup-row-check:checked').forEach(function (cb) {
                        phones.push(cb.value);
                    });
                    const holder = document.getElementById('wa-followup-dispatch-phones');
                    if (!holder) return;
                    holder.innerHTML = '';
                    phones.forEach(function (p) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'phones[]';
                        input.value = p;
                        holder.appendChild(input);
                    });
                    const modalEl = document.getElementById('waFollowupDispatchModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        initSelect2In(modalEl);
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                });
            }

            function loadVoiceCronRuns(params) {
                if (voiceCronRunsLoading || !voiceCronRunsContent) return;

                currentVoiceCronRunsParams = new URLSearchParams(params.toString());
                voiceCronRunsLoading = true;
                voiceCronRunsContent.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';

                fetch(voiceCronRunsUrl + (params.toString() ? ('?' + params.toString()) : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('load_failed');
                        return response.text();
                    })
                    .then(function (html) {
                        voiceCronRunsContent.innerHTML = html;
                        bindVoiceCronRunsPagination();
                        voiceCronRunsLoaded = true;
                    })
                    .catch(function () {
                        voiceCronRunsContent.innerHTML = '<div class="alert alert-danger mb-0">{{ translate('Failed_to_load') }}</div>';
                    })
                    .finally(function () {
                        voiceCronRunsLoading = false;
                    });
            }

            function bindVoiceCronRunsPagination() {
                if (!voiceCronRunsContent) return;

                voiceCronRunsContent.querySelectorAll('.voice-cron-runs-page').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const page = link.getAttribute('data-page');
                        const params = new URLSearchParams(currentVoiceCronRunsParams.toString());
                        params.set('page', page);
                        loadVoiceCronRuns(params);
                    });
                });
            }

            function bindVoiceCronEvents() {
                const form = document.getElementById('voice-cron-job-form');
                const modalEl = document.getElementById('voiceCronJobModal');
                const titleEl = document.getElementById('voiceCronJobModalLabel');
                if (!form) return;

                const storeUrl = @json(route('admin.voice-call.cron-jobs.store'));
                const updateUrlTemplate = @json(url('admin/voice-call/cron-jobs/__ID__'));
                const addTitle = @json(translate('Add_cron_job'));
                const editTitle = @json(translate('Edit'));

                function setField(name, value) {
                    const el = form.querySelector('[name="' + name + '"]');
                    if (!el) return;
                    if (el.type === 'checkbox') {
                        el.checked = Boolean(value);
                        return;
                    }
                    el.value = value ?? '';
                }

                function setMultiSelect(name, values) {
                    const el = form.querySelector('[name="' + name + '"]');
                    if (!el) return;
                    const selected = new Set((values || []).map(String));
                    Array.from(el.options).forEach(function (opt) {
                        opt.selected = selected.has(String(opt.value));
                        opt.disabled = false;
                    });
                }

                function syncOtherCronJobSelect(currentRuleId) {
                    const modeEl = document.getElementById('voice-cron-other-job-mode');
                    const idsEl = document.getElementById('voice-cron-other-job-ids');
                    if (!modeEl || !idsEl) return;

                    const hasMode = modeEl.value === 'include' || modeEl.value === 'exclude';
                    idsEl.disabled = !hasMode;

                    Array.from(idsEl.options).forEach(function (opt) {
                        const isSelf = currentRuleId && String(opt.value) === String(currentRuleId);
                        opt.disabled = isSelf;
                        if (isSelf) {
                            opt.selected = false;
                        }
                    });

                    if (typeof $ !== 'undefined' && $(idsEl).hasClass('select2-hidden-accessible')) {
                        $(idsEl).trigger('change.select2');
                    }
                }

                function resetVoiceCronForm() {
                    form.action = storeUrl;
                    form.querySelector('input[name="_method"]')?.remove();
                    delete form.dataset.editingRuleId;
                    if (titleEl) titleEl.textContent = addTitle;
                    form.reset();
                    const enabledEl = document.getElementById('voice-cron-is-enabled');
                    if (enabledEl) enabledEl.checked = true;
                    setMultiSelect('other_cron_job_ids[]', []);
                    syncOtherCronJobSelect(null);
                    initSelect2In(modalEl);
                }

                document.getElementById('voice-cron-job-add')?.addEventListener('click', resetVoiceCronForm);
                document.getElementById('voice-cron-other-job-mode')?.addEventListener('change', function () {
                    syncOtherCronJobSelect(form.dataset.editingRuleId || null);
                });

                document.querySelectorAll('.voice-cron-job-edit').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        let rule = {};
                        try {
                            rule = JSON.parse(btn.getAttribute('data-rule') || '{}');
                        } catch (err) {
                            return;
                        }

                        form.action = updateUrlTemplate.replace('__ID__', String(rule.id || ''));
                        let methodInput = form.querySelector('input[name="_method"]');
                        if (!methodInput) {
                            methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            form.appendChild(methodInput);
                        }
                        methodInput.value = 'PUT';
                        form.dataset.editingRuleId = String(rule.id || '');

                        if (titleEl) titleEl.textContent = editTitle;

                        setField('name', rule.name);
                        setField('campaign_name', rule.campaign_name);
                        setField('interval_minutes', rule.interval_minutes);
                        setField('max_contacts_per_run', rule.max_contacts_per_run);
                        setField('concurrent_call_limit', rule.concurrent_call_limit);
                        setField('is_enabled', rule.is_enabled);

                        const filters = rule.filters || {};
                        setField('silent_min_hours', filters.silent_min_hours ?? 2);
                        setField('lead_open', filters.lead_open ?? '');
                        setField('wa_chat_bucket', filters.wa_chat_bucket ?? '');
                        setField('handled_by', filters.handled_by ?? '');
                        setField('human_support', filters.human_support ?? 'exclude');
                        setField('exclude_called_within_hours', filters.exclude_called_within_hours ?? 24);
                        setField('other_cron_job_mode', filters.other_cron_job_mode ?? '');
                        setMultiSelect('lead_types[]', filters.lead_types || []);
                        setMultiSelect('wa_chat_tag_ids[]', filters.wa_chat_tag_ids || []);
                        setMultiSelect('customer_lead_tag_ids[]', filters.customer_lead_tag_ids || []);
                        setMultiSelect('other_cron_job_ids[]', filters.other_cron_job_ids || []);
                        syncOtherCronJobSelect(rule.id || null);

                        initSelect2In(modalEl);
                        if (modalEl && typeof bootstrap !== 'undefined') {
                            bootstrap.Modal.getOrCreateInstance(modalEl).show();
                        }
                    });
                });

                document.querySelectorAll('.voice-cron-filter-runs').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const ruleId = btn.getAttribute('data-rule-id') || '';
                        const filterEl = document.getElementById('voice-cron-runs-filter');
                        if (filterEl) {
                            filterEl.value = ruleId;
                        }
                        const params = new URLSearchParams();
                        if (ruleId) {
                            params.set('rule_id', ruleId);
                        }
                        loadVoiceCronRuns(params);
                        document.getElementById('voice-cron-runs-content')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                });

                document.getElementById('voice-cron-runs-filter')?.addEventListener('change', function () {
                    const params = new URLSearchParams();
                    if (this.value) {
                        params.set('rule_id', this.value);
                    }
                    loadVoiceCronRuns(params);
                });

                document.getElementById('voice-cron-runs-refresh')?.addEventListener('click', function () {
                    loadVoiceCronRuns(new URLSearchParams(currentVoiceCronRunsParams.toString()));
                });
            }

            function copyTextFallback(text, done) {
                try {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    done();
                } catch (err) {}
            }

            function primeVoiceCallRecordings(container) {
                container.querySelectorAll('.voice-call-audio-player').forEach(function (audio) {
                    const url = audio.getAttribute('data-play-url');
                    if (url && !audio.getAttribute('src')) {
                        audio.setAttribute('src', url);
                        audio.load();
                    }
                });
            }

            function pauseVoiceCallRecordings(container) {
                container.querySelectorAll('.voice-call-audio-player').forEach(function (audio) {
                    audio.pause();
                });
            }

            let pendingDeleteReloadFn = null;
            const voiceCallSearchDebounceMs = 400;

            function debounce(fn, delay) {
                let timer = null;
                return function () {
                    const args = arguments;
                    const context = this;
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        fn.apply(context, args);
                    }, delay);
                };
            }

            function bindCallLogsEvents(content, options) {
                const form = content.querySelector('#' + options.formId);
                if (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        options.loadFn(new URLSearchParams(new FormData(form)));
                    });

                    const searchInput = form.querySelector('input[name="search"]');
                    if (searchInput) {
                        searchInput.addEventListener('input', debounce(function () {
                            const params = new URLSearchParams(new FormData(form));
                            params.delete('page');
                            options.loadFn(params);
                        }, voiceCallSearchDebounceMs));
                    }
                }

                const resetBtn = content.querySelector('.' + options.resetClass);
                if (resetBtn) {
                    resetBtn.addEventListener('click', function () {
                        if (form) {
                            form.reset();
                            initSelect2In(content);
                        }
                        options.loadFn(new URLSearchParams());
                    });
                }

                content.querySelectorAll('.' + options.pageLinkClass).forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const params = new URLSearchParams();
                        params.set('page', link.getAttribute('data-page') || '1');
                        const agentId = link.getAttribute('data-agent-id');
                        const callStatus = link.getAttribute('data-call-status');
                        const search = link.getAttribute('data-search');
                        if (agentId) params.set('agent_id', agentId);
                        if (callStatus) params.set('call_status', callStatus);
                        if (search) params.set('search', search);
                        options.loadFn(params);
                    });
                });

                bindVoiceCallRecordingButtons(content);
                bindVoiceCallDeleteButtons(content);
                bindVoiceCallDetailToggles(content);
                bindVoiceCallCopyButtons(content);
                bindVoiceCallTranscriptHinglish(content);
                bindVoiceCallExtractedViewAll(content);
            }

            function bindVoiceCallExtractedViewAll(content) {
                content.querySelectorAll('.voice-call-extracted-view-all').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const grid = btn.closest('.voice-call-extracted-card')?.querySelector('.voice-call-extracted-grid');
                        if (!grid) {
                            return;
                        }

                        const showAll = !grid.classList.contains('is-show-all');
                        grid.classList.toggle('is-show-all', showAll);
                        btn.textContent = showAll
                            ? @json(translate('Hide'))
                            : @json(translate('view_all'));
                    });
                });
            }

            function decodeCopyB64(b64) {
                try {
                    return b64 ? decodeURIComponent(escape(atob(b64))) : '';
                } catch (err) {
                    return '';
                }
            }

            function transcriptLineClass(line) {
                const trimmed = String(line || '').trim();
                if (trimmed.toLowerCase().indexOf('user:') === 0) {
                    return 'voice-call-transcript-line--user';
                }
                if (trimmed.toLowerCase().indexOf('llm:') === 0) {
                    return 'voice-call-transcript-line--llm';
                }

                return '';
            }

            function renderTranscriptLines(container, transcriptText) {
                if (!container) {
                    return;
                }

                const lines = String(transcriptText || '').split(/\r\n|\r|\n/);
                container.innerHTML = lines.map(function (line) {
                    const trimmed = String(line || '').trim();
                    if (trimmed === '') {
                        return '';
                    }

                    const lineClass = transcriptLineClass(trimmed);

                    return '<div class="voice-call-transcript-line' + (lineClass ? (' ' + lineClass) : '') + '">' + escapeHtml(trimmed) + '</div>';
                }).join('');
            }

            function updateTranscriptCopyButton(detailsPanel, transcriptText) {
                if (!detailsPanel) {
                    return;
                }

                const copyBtn = detailsPanel.querySelector('.voice-call-transcript-copy-btn');
                if (!copyBtn) {
                    return;
                }

                const encoded = encodeCopyB64(transcriptText);
                copyBtn.setAttribute('data-copy-b64', encoded);
            }

            function preloadStoredTranscriptTransliterations(content) {
                content.querySelectorAll('.voice-call-transcript[data-transliterated-b64]').forEach(function (el) {
                    const original = decodeCopyB64(el.getAttribute('data-original-b64') || '');
                    const transliterated = decodeCopyB64(el.getAttribute('data-transliterated-b64') || '');
                    if (original && transliterated) {
                        transcriptHinglishCache.set(original, transliterated);
                    }
                });
            }

            function bindVoiceCallTranscriptHinglish(content) {
                preloadStoredTranscriptTransliterations(content);

                content.querySelectorAll('.voice-call-transcript-hinglish-toggle').forEach(function (btn) {
                    if (btn.dataset.hinglishBound === '1') {
                        return;
                    }
                    btn.dataset.hinglishBound = '1';

                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const callId = btn.getAttribute('data-call-id') || '';
                        const detailsPanel = btn.closest('.voice-call-details-panel');
                        const transcriptEl = detailsPanel?.querySelector('.voice-call-transcript[data-call-id="' + callId + '"]');
                        const showing = btn.getAttribute('data-showing') || 'original';
                        const originalB64 = btn.getAttribute('data-original-b64') || transcriptEl?.getAttribute('data-original-b64') || '';
                        const originalText = decodeCopyB64(originalB64);

                        if (!transcriptEl || !originalText) {
                            return;
                        }

                        if (showing === 'hinglish') {
                            renderTranscriptLines(transcriptEl, originalText);
                            updateTranscriptCopyButton(detailsPanel, originalText);
                            btn.setAttribute('data-showing', 'original');
                            btn.textContent = strShowHinglish;
                            return;
                        }

                        const cached = transcriptHinglishCache.get(originalText);
                        if (cached) {
                            renderTranscriptLines(transcriptEl, cached);
                            updateTranscriptCopyButton(detailsPanel, cached);
                            btn.setAttribute('data-showing', 'hinglish');
                            btn.textContent = strShowOriginal;
                            return;
                        }

                        btn.disabled = true;
                        btn.textContent = strTranslating + '…';
                        transcriptEl.classList.add('is-translating');

                        let hintEl = detailsPanel?.querySelector('.voice-call-transcript-translating-hint');
                        if (!hintEl && detailsPanel) {
                            hintEl = document.createElement('p');
                            hintEl.className = 'text-muted small mb-0 px-3 pb-2 voice-call-transcript-translating-hint';
                            transcriptEl.insertAdjacentElement('afterend', hintEl);
                        }
                        if (hintEl) {
                            hintEl.textContent = strTranslatingLongHint;
                            hintEl.classList.remove('d-none');
                        }

                        fetch(transcriptHinglishUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                transcript: originalText,
                                call_log_id: callId ? parseInt(callId, 10) : null,
                            }),
                        })
                            .then(function (response) {
                                return response.json().then(function (data) {
                                    if (!response.ok || !data.ok || !data.transcript) {
                                        const message = data && data.message ? data.message : strTranscriptHinglishFailed;
                                        throw new Error(message);
                                    }

                                    return data.transcript;
                                });
                            })
                            .then(function (translated) {
                                transcriptHinglishCache.set(originalText, translated);
                                renderTranscriptLines(transcriptEl, translated);
                                updateTranscriptCopyButton(detailsPanel, translated);
                                if (transcriptEl) {
                                    transcriptEl.setAttribute('data-transliterated-b64', encodeCopyB64(translated));
                                }
                                btn.setAttribute('data-showing', 'hinglish');
                                btn.textContent = strShowOriginal;
                            })
                            .catch(function (err) {
                                if (typeof toastr !== 'undefined') {
                                    toastr.error(err && err.message ? err.message : strTranscriptHinglishFailed);
                                }
                                btn.setAttribute('data-showing', 'original');
                                btn.textContent = strShowHinglish;
                            })
                            .finally(function () {
                                btn.disabled = false;
                                transcriptEl.classList.remove('is-translating');
                                const hint = detailsPanel?.querySelector('.voice-call-transcript-translating-hint');
                                if (hint) {
                                    hint.classList.add('d-none');
                                }
                            });
                    });
                });
            }

            function bindVoiceCallCopyButtons(content) {
                content.querySelectorAll('.voice-call-copy-btn').forEach(function (btn) {
                    if (btn.dataset.copyBound === '1') {
                        return;
                    }
                    btn.dataset.copyBound = '1';
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const b64 = btn.getAttribute('data-copy-b64') || '';
                        const text = decodeCopyB64(b64);
                        if (!text) {
                            return;
                        }

                        const done = function () {
                            if (typeof toastr !== 'undefined') {
                                toastr.success(@json(translate('Copied')));
                            }
                        };

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(text).then(done).catch(function () {
                                copyTextFallback(text, done);
                            });
                        } else {
                            copyTextFallback(text, done);
                        }
                    });
                });
            }

            function bindVoiceCallDetailToggles(content) {
                content.querySelectorAll('.voice-call-details-toggle').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const row = btn.closest('tr');
                        const detailsRow = row?.nextElementSibling;
                        if (!detailsRow?.classList.contains('voice-call-details-row')) {
                            return;
                        }
                        const isHidden = detailsRow.classList.contains('d-none');
                        detailsRow.classList.toggle('d-none', !isHidden);
                        btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                        btn.textContent = isHidden ? @json(translate('Hide')) : @json(translate('View'));

                        if (isHidden) {
                            primeVoiceCallRecordings(detailsRow);
                        } else {
                            pauseVoiceCallRecordings(detailsRow);
                        }
                    });
                });
            }

            function bindVoiceCallRecordingButtons(content) {
                content.querySelectorAll('.voice-call-audio-player').forEach(function (audio) {
                    audio.addEventListener('play', function () {
                        content.querySelectorAll('.voice-call-audio-player').forEach(function (other) {
                            if (other !== audio) {
                                other.pause();
                            }
                        });
                    });
                });
            }

            function bindVoiceCallDeleteButtons(content) {
                if (!deleteConfirmBtn) return;

                content.querySelectorAll('.voice-call-history-delete').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        pendingDeleteCallId = btn.getAttribute('data-call-id');
                        if (content === forwardedContent) {
                            pendingDeleteReloadFn = loadForwarded;
                        } else if (content === callbackContent) {
                            pendingDeleteReloadFn = loadCallback;
                        } else {
                            pendingDeleteReloadFn = loadHistory;
                        }
                        if (deleteLabelEl) {
                            deleteLabelEl.textContent = btn.getAttribute('data-call-label') || '';
                        }
                        if (deleteModalEl && typeof bootstrap !== 'undefined') {
                            bootstrap.Modal.getOrCreateInstance(deleteModalEl).show();
                        }
                    });
                });
            }

            function loadCallLogs(url, params, content, state, options) {
                if (state.loading) {
                    return;
                }

                state.currentParams = new URLSearchParams(params.toString());
                state.loading = true;
                content.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';

                fetch(url + (params.toString() ? ('?' + params.toString()) : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('load_failed');
                        return response.text();
                    })
                    .then(function (html) {
                        content.innerHTML = html;
                        initSelect2In(content);
                        bindCallLogsEvents(content, options);
                        state.loaded = true;
                    })
                    .catch(function () {
                        content.innerHTML = '<div class="alert alert-danger mb-0">{{ translate('OmniDimension_call_history_failed') }}</div>';
                    })
                    .finally(function () {
                        state.loading = false;
                    });
            }

            const historyState = { loading: false, loaded: false, currentParams: currentHistoryParams };
            const forwardedState = { loading: false, loaded: false, currentParams: currentForwardedParams };
            const callbackState = { loading: false, loaded: false, currentParams: currentCallbackParams };

            function loadHistory(params) {
                loadCallLogs(historyUrl, params, historyContent, historyState, {
                    formId: 'voice-history-filter-form',
                    resetClass: 'voice-history-reset',
                    pageLinkClass: 'voice-history-page-link',
                    loadFn: loadHistory,
                });
            }

            function loadForwarded(params) {
                loadCallLogs(forwardedUrl, params, forwardedContent, forwardedState, {
                    formId: 'voice-forwarded-filter-form',
                    resetClass: 'voice-forwarded-reset',
                    pageLinkClass: 'voice-forwarded-page-link',
                    loadFn: loadForwarded,
                });
            }

            function loadCallback(params) {
                loadCallLogs(callbackUrl, params, callbackContent, callbackState, {
                    formId: 'voice-callback-filter-form',
                    resetClass: 'voice-callback-reset',
                    pageLinkClass: 'voice-callback-page-link',
                    loadFn: loadCallback,
                });
            }

            if (deleteConfirmBtn) {
                deleteConfirmBtn.addEventListener('click', function () {
                    if (!pendingDeleteCallId) return;

                    fetch(historyDestroyUrl + '/' + encodeURIComponent(pendingDeleteCallId), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    })
                        .then(function (response) {
                            if (!response.ok) throw new Error('delete_failed');
                            return response.json();
                        })
                        .then(function (data) {
                            if (deleteModalEl && typeof bootstrap !== 'undefined') {
                                bootstrap.Modal.getInstance(deleteModalEl)?.hide();
                            }
                            pendingDeleteCallId = null;
                            if (typeof toastr !== 'undefined') {
                                toastr.success(data.message || '{{ translate('Voice_call_history_removed') }}');
                            }
                            const reloadFn = pendingDeleteReloadFn || loadHistory;
                            let reloadParams = historyState.currentParams;
                            if (reloadFn === loadForwarded) {
                                reloadParams = forwardedState.currentParams;
                            } else if (reloadFn === loadCallback) {
                                reloadParams = callbackState.currentParams;
                            }
                            reloadFn(new URLSearchParams(reloadParams.toString()));
                        })
                        .catch(function () {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('{{ translate('Something_went_wrong') }}');
                            }
                        });
                });
            }

            tabLinks.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const tab = link.getAttribute('data-voice-tab');
                    setActiveTab(tab);
                    if (tab === 'history' && !historyState.loaded) {
                        loadHistory(new URLSearchParams());
                    }
                    if (tab === 'forwarded' && !forwardedState.loaded) {
                        loadForwarded(new URLSearchParams());
                    }
                    if (tab === 'callback' && !callbackState.loaded) {
                        loadCallback(new URLSearchParams());
                    }
                    if (tab === 'bulk' && !bulkLoaded) {
                        loadBulkCampaigns(new URLSearchParams());
                    }
                    if (tab === 'whatsapp_followup' && !waFollowupLoaded) {
                        const ff = document.getElementById('wa-followup-filter-form');
                        loadWaFollowupList(ff ? new URLSearchParams(new FormData(ff)) : new URLSearchParams({ silent_min_hours: '2', human_support: 'exclude', exclude_called_within_hours: '24' }));
                    }
                    if (tab === 'voice_cron' && !voiceCronRunsLoaded) {
                        loadVoiceCronRuns(new URLSearchParams());
                    }
                });
            });

            document.getElementById('agent_id')?.addEventListener('change', syncLabels);
            document.getElementById('from_number_id')?.addEventListener('change', syncLabels);
            document.getElementById('voice-call-form')?.addEventListener('submit', syncLabels);
            syncLabels();
            bindBulkFormToggles();
            bindWaFollowupPanelEvents();
            bindVoiceCronEvents();
            initSelect2In(bulkPanel);
            initSelect2In(waFollowupPanel);
            initSelect2In(voiceCronPanel);

            const initialTab = new URL(window.location.href).searchParams.get('tab');
            if (initialTab === 'history') {
                setActiveTab('history');
                loadHistory(new URLSearchParams());
            } else if (initialTab === 'forwarded') {
                setActiveTab('forwarded');
                loadForwarded(new URLSearchParams());
            } else if (initialTab === 'callback') {
                setActiveTab('callback');
                loadCallback(new URLSearchParams());
            } else if (initialTab === 'bulk') {
                setActiveTab('bulk');
                loadBulkCampaigns(new URLSearchParams());
            } else if (initialTab === 'whatsapp_followup') {
                setActiveTab('whatsapp_followup');
                const ff = document.getElementById('wa-followup-filter-form');
                loadWaFollowupList(ff ? new URLSearchParams(new FormData(ff)) : new URLSearchParams({ silent_min_hours: '2', human_support: 'exclude', exclude_called_within_hours: '24' }));
            } else if (initialTab === 'voice_cron') {
                setActiveTab('voice_cron');
                loadVoiceCronRuns(new URLSearchParams());
            }
        })();
    </script>
@endpush
