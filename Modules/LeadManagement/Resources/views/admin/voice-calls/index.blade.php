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
        /* Compact, horizontally scrollable data tables across Voice Calls tabs */
        .voice-calls-page .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }
        .voice-calls-page .table-responsive > .table {
            font-size: 12px;
            width: max-content;
            min-width: 100%;
            margin-bottom: 0;
        }
        .voice-calls-page .table-responsive > .table thead th {
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            vertical-align: middle;
        }
        .voice-calls-page .table-responsive > .table tbody td {
            font-size: 12px;
            white-space: nowrap;
            vertical-align: middle;
        }
        .voice-calls-page .table-responsive > .table .badge {
            font-size: 11px;
            font-weight: 500;
        }
        .voice-calls-page .table-responsive > .table .voice-call-reason-badge {
            font-size: 11px;
            line-height: 1.3;
        }
        .voice-calls-page .table-responsive > .table .btn-sm {
            font-size: 11px;
            padding: 0.2rem 0.5rem;
            line-height: 1.35;
        }
        .voice-calls-page .table-responsive > .table .btn-sm .material-icons {
            font-size: 16px !important;
        }
        .voice-calls-page .table-responsive > .table code {
            font-size: 11px;
        }
        .voice-calls-page .table-responsive > .table tbody tr.voice-call-details-row > td,
        .voice-calls-page .table-responsive > .table tbody tr.wa-followup-details-row > td,
        .voice-calls-page .table-responsive > .table tbody tr.voice-api-log-details > td,
        .voice-calls-page .table-responsive > .table tbody tr.voice-bulk-campaign-details-row > td {
            white-space: normal;
            width: 100%;
            max-width: 0;
            padding: 0 !important;
            border-top: 0;
            vertical-align: top;
        }
        .voice-bulk-campaign-details-row .voice-bulk-campaign-details-host {
            min-width: 0;
            max-width: 100%;
        }
        #voice-bulk-detail-view .card {
            overflow: hidden;
        }
        .voice-bulk-detail {
            min-width: 0;
            max-width: 100%;
            background: #f8f9fb;
        }
        #voice-bulk-detail-view .voice-bulk-detail {
            background: #fff;
            border-top: 0;
            box-shadow: none;
        }
        .voice-bulk-campaign-details-row .voice-bulk-detail {
            border-top: 1px solid #e9ecef;
            box-shadow: inset 0 3px 8px rgba(0, 0, 0, 0.04);
        }
        .voice-bulk-detail__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
        }
        .voice-bulk-detail__heading {
            min-width: 0;
        }
        .voice-bulk-detail__title {
            font-size: 15px;
            font-weight: 600;
            color: #212529;
            line-height: 1.35;
            word-break: break-word;
        }
        .voice-bulk-detail__sub {
            font-size: 12px;
            color: #6c757d;
            margin-top: 2px;
        }
        .voice-bulk-detail__status {
            flex-shrink: 0;
            font-size: 11px;
            font-weight: 500;
        }
        .voice-bulk-detail__body {
            padding: 12px 16px 16px;
        }
        .voice-bulk-detail__section + .voice-bulk-detail__section {
            margin-top: 12px;
        }
        .voice-bulk-detail__section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            margin-bottom: 8px;
        }
        .voice-bulk-detail__grid {
            display: grid;
            gap: 8px;
        }
        .voice-bulk-detail__grid--3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        @media (max-width: 992px) {
            .voice-bulk-detail__grid--3 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 576px) {
            .voice-bulk-detail__grid--3 {
                grid-template-columns: 1fr;
            }
        }
        .voice-bulk-detail__field {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 12px;
            min-width: 0;
        }
        .voice-bulk-detail__label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .voice-bulk-detail__value {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #212529;
            line-height: 1.4;
            word-break: break-word;
        }
        .voice-bulk-detail__metrics {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .voice-bulk-detail__metric {
            flex: 1 1 108px;
            min-width: 96px;
            max-width: 140px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 12px;
            text-align: center;
        }
        .voice-bulk-detail__metric--success {
            border-color: #a3cfbb;
            background: #f3faf6;
        }
        .voice-bulk-detail__metric--primary {
            border-color: #9ec5fe;
            background: #f3f8ff;
        }
        .voice-bulk-detail__metric--warning {
            border-color: #ffda6a;
            background: #fffdf3;
        }
        .voice-bulk-detail__metric-value {
            display: block;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
            color: #212529;
        }
        .voice-bulk-detail__metric-label {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #6c757d;
            line-height: 1.3;
        }
        .voice-bulk-detail__tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .voice-bulk-detail__tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            line-height: 1.35;
            color: #495057;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 999px;
            padding: 4px 10px;
            max-width: 100%;
            word-break: break-word;
        }
        .voice-bulk-detail__tag .material-icons {
            font-size: 14px;
            color: #0d6efd;
        }
        .voice-bulk-detail__tag--schedule {
            border-color: #9ec5fe;
            background: #f3f8ff;
        }
        .voice-bulk-detail__tag--muted {
            background: #f8f9fa;
        }
        .voice-bulk-detail__calls {
            margin-top: 14px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        .voice-bulk-detail__calls-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid #e9ecef;
            background: #fff;
        }
        .voice-bulk-detail__calls-title {
            font-size: 13px;
            font-weight: 600;
            color: #212529;
        }
        .voice-bulk-detail__calls-hint {
            font-size: 11px;
            color: #6c757d;
            margin-top: 2px;
        }
        .voice-bulk-detail__calls-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .voice-bulk-detail__calls-table {
            width: 100%;
            min-width: 720px;
            margin-bottom: 0;
            font-size: 12px;
        }
        .voice-bulk-detail__calls-table thead th {
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            background: #f8f9fb;
            border-bottom: 1px solid #e9ecef;
        }
        .voice-bulk-detail__calls-table tbody td {
            white-space: nowrap;
            vertical-align: middle;
        }
        .voice-bulk-detail__calls-name {
            white-space: normal;
            min-width: 120px;
            max-width: 180px;
            word-break: break-word;
        }
        .voice-bulk-detail__calls-table tbody tr.voice-call-details-row > td {
            white-space: normal;
            width: 100%;
        }
        .voice-bulk-detail__calls-pagination {
            display: flex;
            justify-content: flex-end;
            padding: 10px 14px;
            border-top: 1px solid #e9ecef;
            background: #fff;
        }
        .voice-field-label {
            font-weight: 500;
            color: #212529;
        }
        .voice-field-info {
            font-size: 16px;
            line-height: 1;
            color: #6c757d;
            cursor: help;
            user-select: none;
            flex-shrink: 0;
        }
        .voice-field-info:hover,
        .voice-field-info:focus {
            color: #0d6efd;
        }
        .voice-form-section-title {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .voice-call-reason-badge {
            font-weight: 500;
            border: 1px solid transparent;
            white-space: normal;
            text-align: left;
            line-height: 1.35;
        }
        .voice-call-reason-badge--whatsapp {
            background-color: #cff4fc;
            color: #055160;
            border-color: #9eeaf9;
        }
        .voice-call-reason-badge--future-customer {
            background-color: #d1e7dd;
            color: #0a3622;
            border-color: #a3cfbb;
        }
        .voice-call-reason-badge--inbound {
            background-color: #fff3cd;
            color: #664d03;
            border-color: #ffecb5;
        }
        .voice-call-reason-badge--provider-callback {
            background-color: #e2d9f3;
            color: #432874;
            border-color: #c5b3e6;
        }
        .voice-call-reason-badge--default {
            background-color: #e9ecef;
            color: #495057;
            border-color: #ced4da;
        }
        .voice-call-dispatch-chip__value .voice-call-reason-badge {
            margin-top: 2px;
        }
        .wa-followup-contact-name {
            font-weight: 600;
            color: #212529;
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
            max-height: 380px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .wa-followup-call-context-grid {
            display: flex;
            flex-direction: column;
        }
        .wa-followup-context-item {
            display: grid;
            grid-template-columns: minmax(148px, 200px) minmax(0, 1fr);
            gap: 8px 20px;
            padding: 11px 16px;
            border-bottom: 1px solid #eef1f4;
            align-items: start;
            background: #fff;
        }
        .wa-followup-context-item:last-child {
            border-bottom: 0;
        }
        .wa-followup-context-item:nth-child(even) {
            background: #fafbfc;
        }
        .wa-followup-context-item__label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            line-height: 1.45;
            padding-top: 3px;
        }
        .wa-followup-context-item--summary .wa-followup-context-item__label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding-top: 0;
        }
        .wa-followup-context-item__label .wa-followup-context-label__text {
            flex: 1 1 auto;
            min-width: 0;
        }
        .wa-followup-context-item__value {
            font-size: 14px;
            font-weight: 500;
            color: #212529;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
            overflow-wrap: anywhere;
            min-width: 0;
        }
        .wa-followup-context-item--long .wa-followup-context-item__value {
            font-size: 14px;
            line-height: 1.6;
        }
        .wa-followup-lead-summary-value {
            font-size: 15px;
            line-height: 1.65;
        }
        .wa-followup-call-context-grid:not(.is-show-all) .wa-followup-context-item--empty {
            display: none;
        }
        .wa-followup-context-item--empty .wa-followup-context-item__value {
            color: #adb5bd;
            font-weight: 400;
        }
        @media (max-width: 576px) {
            .wa-followup-context-item {
                grid-template-columns: 1fr;
                gap: 4px;
                padding: 10px 14px;
            }
            .wa-followup-context-item__label {
                padding-top: 0;
            }
        }
        #wa-followup-action-bar {
            border-left: 3px solid var(--bs-primary, #0d6efd);
        }
        #voice-tab-whatsapp-followup #wa-followup-filter-form .select2-container {
            width: 100% !important;
            display: block;
        }
        #voice-tab-whatsapp-followup #wa-followup-filter-form .select2-container .select2-selection--single,
        #voice-tab-whatsapp-followup #wa-followup-filter-form .select2-container .select2-selection--multiple {
            min-height: calc(2.875rem + 2px);
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 0.375rem;
            background-color: var(--bs-body-bg, #fff);
        }
        #voice-tab-whatsapp-followup #wa-followup-filter-form .select2-container .select2-selection__rendered {
            line-height: calc(2.875rem - 2px);
            padding-left: 0.75rem;
            padding-right: 2rem;
            color: var(--bs-body-color, #212529);
        }
        #voice-tab-whatsapp-followup #wa-followup-filter-form .select2-container .select2-selection__arrow {
            height: calc(2.875rem + 2px);
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid voice-calls-page">
            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                <h2 class="page-title mb-1">{{ translate('Voice_Calls') }}</h2>
                @if($configured)
                    <button type="button"
                            class="btn btn--secondary btn-sm d-inline-flex align-items-center gap-1"
                            id="voice-omnidim-refresh-catalog"
                            title="{{ translate('Refresh_agents_and_numbers_hint') }}">
                        <span class="material-icons" style="font-size:18px;">refresh</span>
                        {{ translate('Refresh_agents_and_numbers') }}
                    </button>
                @endif
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
                <li class="nav-item">
                    <a class="nav-link" href="#" data-voice-tab="api_logs">
                        {{ translate('OmniDimension_API_Logs') }}
                    </a>
                </li>
            </ul>

            <div id="voice-tab-place">
                <div id="voice-place-list-view">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h4 class="mb-1">{{ translate('Voice_placed_calls_title') }}</h4>
                            <p class="text-muted small mb-0">{{ translate('Voice_placed_calls_hint') }}</p>
                        </div>
                        @can('lead_outbound_enquiry_add')
                            <button type="button"
                                    class="btn btn--primary btn-sm d-inline-flex align-items-center gap-1"
                                    id="voice-place-show-form"
                                    {{ !$configured || $loadError || count($agents) === 0 ? 'disabled' : '' }}>
                                <span class="material-icons" style="font-size:18px;">add</span>
                                {{ translate('Voice_place_call_add') }}
                            </button>
                        @endcan
                    </div>
                    <div id="voice-place-calls-content" class="text-center text-muted py-5">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        {{ translate('Loading') }}…
                    </div>
                </div>

                <div id="voice-place-form-view" class="d-none">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <button type="button"
                                class="btn btn--secondary btn-sm d-inline-flex align-items-center gap-1"
                                id="voice-place-back-to-list">
                            <span class="material-icons" style="font-size:18px;">arrow_back</span>
                            {{ translate('Voice_place_call_back_to_list') }}
                        </button>
                        <h4 class="mb-0">{{ translate('Place_Call') }}</h4>
                    </div>

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
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('OmniDimension_Agent'),
                                            'required' => true,
                                            'for' => 'agent_id',
                                            'hint' => translate('Voice_field_hint_agent'),
                                        ])
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
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Caller_Phone_Number'),
                                            'for' => 'from_number_id',
                                            'hint' => translate('Voice_field_hint_caller_number'),
                                        ])
                                        <select class="form-select js-select voice-omnidim-phone-select" name="from_number_id" id="from_number_id"
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
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Customer_Name'),
                                            'required' => true,
                                            'for' => 'customer_name',
                                            'hint' => translate('Voice_field_hint_customer_name'),
                                        ])
                                        <input type="text"
                                               class="form-control"
                                               name="customer_name"
                                               id="customer_name"
                                               required
                                               value="{{ old('customer_name') }}"
                                               placeholder="{{ translate('Voice_field_placeholder_customer_name') }}"
                                               {{ !$configured ? 'disabled' : '' }}>
                                        @error('customer_name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-30">
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Phone_Number'),
                                            'required' => true,
                                            'for' => 'phone_number',
                                            'hint' => translate('Voice_field_hint_phone_number'),
                                        ])
                                        <input type="text"
                                               class="form-control"
                                               name="phone_number"
                                               id="phone_number"
                                               required
                                               value="{{ old('phone_number') }}"
                                               placeholder="+91XXXXXXXXXX"
                                               {{ !$configured ? 'disabled' : '' }}>
                                        @error('phone_number')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="mb-30">
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Handled_By') . ' (' . translate('name_of_employee') . ')',
                                            'required' => true,
                                            'for' => 'handled_by',
                                            'hint' => translate('Voice_field_hint_handled_by'),
                                        ])
                                        <select class="form-select js-select" name="handled_by" id="handled_by" required {{ !$configured ? 'disabled' : '' }}>
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
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Remarks'),
                                            'for' => 'remarks',
                                            'hint' => translate('Voice_field_hint_remarks'),
                                        ])
                                        <textarea class="form-control"
                                                  name="remarks"
                                                  id="remarks"
                                                  rows="4"
                                                  placeholder="{{ translate('Voice_field_placeholder_remarks') }}"
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
                                            @include('leadmanagement::admin.voice-calls._form_check_label', [
                                                'label' => translate('Log_as_outbound_enquiry'),
                                                'for' => 'log_outbound_enquiry',
                                                'hint' => translate('Voice_field_hint_log_outbound'),
                                            ])
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="border rounded p-3 p-lg-4 mb-30 bg-light">
                                        <h5 class="mb-1 voice-form-section-title">
                                            {{ translate('Call_Context') }}
                                            <i class="material-symbols-outlined voice-field-info"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="{{ translate('Call_context_hint') }}"
                                               tabindex="0"
                                               role="img"
                                               aria-label="{{ translate('Call_context_hint') }}">info</i>
                                        </h5>
                                        <p class="text-muted small mb-4">{{ translate('Call_context_hint') }}</p>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="mb-30">
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('Call_Reason'),
                                                        'hint' => translate('Voice_field_hint_call_reason'),
                                                    ])
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
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('Lead_Status'),
                                                        'hint' => translate('Voice_field_hint_lead_status'),
                                                    ])
                                                    <input type="text"
                                                           class="form-control"
                                                           name="lead_status"
                                                           value="{{ old('lead_status') }}"
                                                           placeholder="{{ translate('Voice_field_placeholder_lead_status') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('lead_status')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('Lead_Summary'),
                                                        'hint' => translate('Voice_field_hint_lead_summary'),
                                                    ])
                                                    <textarea class="form-control"
                                                              name="lead_summary"
                                                              rows="3"
                                                              placeholder="{{ translate('Voice_field_placeholder_lead_summary') }}"
                                                              {{ !$configured ? 'disabled' : '' }}>{{ old('lead_summary') }}</textarea>
                                                    @error('lead_summary')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('Service_Category'),
                                                        'hint' => translate('Voice_field_hint_service_category'),
                                                    ])
                                                    <input type="text"
                                                           class="form-control"
                                                           name="service_category"
                                                           value="{{ old('service_category') }}"
                                                           placeholder="{{ translate('Voice_field_placeholder_service_category') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('service_category')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('Service_Details'),
                                                        'hint' => translate('Voice_field_hint_service_details'),
                                                    ])
                                                    <textarea class="form-control"
                                                              name="service_details"
                                                              rows="3"
                                                              placeholder="{{ translate('Voice_field_placeholder_service_details') }}"
                                                              {{ !$configured ? 'disabled' : '' }}>{{ old('service_details') }}</textarea>
                                                    @error('service_details')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="mb-30">
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('District'),
                                                        'hint' => translate('Voice_field_hint_district'),
                                                    ])
                                                    <input type="text"
                                                           class="form-control"
                                                           name="district"
                                                           value="{{ old('district') }}"
                                                           placeholder="{{ translate('Voice_field_placeholder_district') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('district')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('Area'),
                                                        'hint' => translate('Voice_field_hint_area'),
                                                    ])
                                                    <input type="text"
                                                           class="form-control"
                                                           name="area"
                                                           value="{{ old('area') }}"
                                                           placeholder="{{ translate('Voice_field_placeholder_area') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('area')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('Preferred_Date'),
                                                        'hint' => translate('Voice_field_hint_preferred_date'),
                                                    ])
                                                    <input type="text"
                                                           class="form-control"
                                                           name="preferred_date"
                                                           value="{{ old('preferred_date') }}"
                                                           placeholder="{{ translate('Voice_field_placeholder_preferred_date') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('preferred_date')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-30">
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('Preferred_Time'),
                                                        'hint' => translate('Voice_field_hint_preferred_time'),
                                                    ])
                                                    <input type="text"
                                                           class="form-control"
                                                           name="preferred_time"
                                                           value="{{ old('preferred_time') }}"
                                                           placeholder="{{ translate('Voice_field_placeholder_preferred_time') }}"
                                                           {{ !$configured ? 'disabled' : '' }}>
                                                    @error('preferred_time')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-0">
                                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                                        'label' => translate('Notes'),
                                                        'hint' => translate('Voice_field_hint_notes'),
                                                    ])
                                                    <textarea class="form-control"
                                                              name="notes"
                                                              rows="3"
                                                              placeholder="{{ translate('Voice_field_placeholder_notes') }}"
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
                </div>{{-- /voice-place-form-view --}}
            </div>

            <div id="voice-tab-bulk" class="d-none">
                @include('leadmanagement::admin.voice-calls._bulk', [
                    'configured' => $configured,
                    'loadError' => $loadError,
                    'phoneNumbers' => $phoneNumbers,
                    'categories' => $categories ?? collect(),
                    'subCategories' => $subCategories ?? collect(),
                    'zones' => $zones ?? collect(),
                    'audienceCounts' => $audienceCounts ?? [],
                    'categoryRecipientCounts' => $categoryRecipientCounts ?? [],
                    'leadSources' => $leadSources ?? collect(),
                    'leadAdSources' => $leadAdSources ?? collect(),
                    'customerLeadStatuses' => $customerLeadStatuses ?? collect(),
                    'invalidReasons' => $invalidReasons ?? collect(),
                    'futureCustomerReasons' => $futureCustomerReasons ?? collect(),
                    'customerLeadTags' => $customerLeadTags ?? [],
                    'employees' => $employees ?? [],
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
                    'employees' => $employees ?? [],
                    'waFollowupDefaults' => $waFollowupDefaults ?? ['silent_min_hours' => 2],
                ])
            </div>

            <div id="voice-tab-voice-cron" class="d-none">
                @include('leadmanagement::admin.voice-calls._voice_cron_jobs', [
                    'configured' => $configured,
                    'phoneNumbers' => $phoneNumbers,
                    'waChatTags' => $waChatTags ?? [],
                    'customerLeadTags' => $customerLeadTags ?? [],
                    'employees' => $employees ?? [],
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

            <div id="voice-tab-api-logs" class="d-none">
                <div id="voice-api-logs-content" class="text-center text-muted py-5">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ translate('Loading') }}…
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="voiceCallResultModal" tabindex="-1" aria-labelledby="voiceCallResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body mb-30 pb-0 text-center">
                    <img width="80" src="{{ asset('assets/admin-module/img/icons/status-on.png') }}" alt="" class="mb-20" id="voiceCallResultIcon">
                    <h3 class="mb-3" id="voiceCallResultTitle"></h3>
                    <p class="mb-0" id="voiceCallResultMessage"></p>
                    <p class="mb-0 mt-2 text-muted small d-none" id="voiceCallResultDetails"></p>
                    <div class="btn--container mt-30 justify-content-center">
                        <button type="button" class="btn btn--primary min-w-120 rounded" data-bs-dismiss="modal">{{ translate('OK') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="voiceBulkResultModal" tabindex="-1" aria-labelledby="voiceBulkResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body mb-30 pb-0 text-center">
                    <img width="80" src="{{ asset('assets/admin-module/img/icons/status-on.png') }}" alt="" class="mb-20" id="voiceBulkResultIcon">
                    <h3 class="mb-3" id="voiceBulkResultTitle"></h3>
                    <p class="mb-0" id="voiceBulkResultMessage"></p>
                    <p class="mb-0 mt-2 text-muted small d-none" id="voiceBulkResultDetails"></p>
                    <div class="btn--container mt-30 justify-content-center d-none" id="voiceBulkResultSuccessActions">
                        <button type="button" class="btn btn--secondary min-w-120 rounded" id="voiceBulkResultScheduleMore">
                            {{ translate('Voice_bulk_schedule_more') }}
                        </button>
                        <button type="button" class="btn btn--primary min-w-120 rounded" id="voiceBulkResultViewScheduled">
                            {{ translate('Voice_bulk_view_scheduled') }}
                        </button>
                    </div>
                    <div class="btn--container mt-30 justify-content-center d-none" id="voiceBulkResultErrorActions">
                        <button type="button" class="btn btn--primary min-w-120 rounded" data-bs-dismiss="modal">{{ translate('OK') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="voiceBulkCancelModal" tabindex="-1" aria-labelledby="voiceBulkCancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body mb-30 pb-0 text-center">
                    <img width="80" src="{{ asset('assets/admin-module/img/icons/status-off.png') }}" alt="" class="mb-20">
                    <h3 class="mb-3">{{ translate('Voice_bulk_cancel_campaign_confirm_title') }}</h3>
                    <p class="mb-0">{{ translate('Voice_bulk_cancel_campaign_confirm_message') }}</p>
                    <p class="mb-0 mt-2 text-muted small" id="voiceBulkCancelLabel"></p>
                    <div class="btn--container mt-30 justify-content-center">
                        <button type="button" class="btn btn--secondary min-w-120 rounded" data-bs-dismiss="modal">{{ translate('No') }}</button>
                        <button type="button" class="btn btn--danger min-w-120 rounded" id="voiceBulkCancelConfirm">{{ translate('Yes_Cancel') }}</button>
                    </div>
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
            const apiLogsUrl = @json(route('admin.voice-call.api-logs'));
            const placeCallUrl = @json(route('admin.voice-call.store'));
            const placedCallsUrl = @json(route('admin.voice-call.placed'));
            const refreshCatalogUrl = @json(route('admin.voice-call.refresh-catalog'));
            const strSelectAgent = @json(translate('Select_agent'));
            const strSelectPhoneNumber = @json(translate('Select_phone_number'));
            const strVoiceCallSuccessTitle = @json(translate('Voice_call_placed_successfully'));
            const strVoiceCallFailedTitle = @json(translate('Voice_call_place_failed'));
            const strRequestId = @json(translate('Request_ID'));
            const strCallStatus = @json(translate('Call_Status'));
            const statusOnIcon = @json(asset('assets/admin-module/img/icons/status-on.png'));
            const statusOffIcon = @json(asset('assets/admin-module/img/icons/status-off.png'));
            const bulkCampaignsUrl = @json(route('admin.voice-call.bulk.campaigns'));
            const bulkCampaignDetailsUrl = @json(url('admin/voice-call/bulk/campaigns'));
            const bulkCampaignCancelUrl = @json(url('admin/voice-call/bulk/campaigns'));
            const strVoiceBulkCancelSuccess = @json(translate('Voice_bulk_campaign_cancelled_successfully'));
            const strVoiceBulkCancelFailed = @json(translate('Voice_bulk_campaign_cancel_failed'));
            const bulkStoreUrl = @json(route('admin.voice-call.bulk.store'));
            const bulkAudiencePreviewUrl = @json(route('admin.voice-call.bulk.audience-preview'));
            const bulkAudiencePreviewCsvUrl = @json(route('admin.voice-call.bulk.audience-preview-csv'));
            const strVoiceBulkSuccessTitle = @json(translate('Voice_bulk_campaign_created_successfully'));
            const strVoiceBulkFailedTitle = @json(translate('Voice_bulk_campaign_failed'));
            const strVoiceBulkCampaignId = @json(translate('Campaign_ID'));
            const strVoiceBulkContactCount = @json(translate('Voice_bulk_contact_count'));
            const strVoiceBulkAudienceSelectCategory = @json(translate('Voice_bulk_audience_select_category'));
            const strVoiceBulkAudienceUploadCsv = @json(translate('Voice_bulk_audience_upload_csv'));
            const strVoiceBulkAudiencePreviewFailed = @json(translate('Voice_bulk_audience_preview_failed'));
            const strVoiceBulkAudiencePreviewEmpty = @json(translate('Voice_bulk_audience_preview_empty'));
            const strRecipientPreviewTotal = @json(translate('Recipient_preview_total'));
            const strRecipientPreviewTableHint = @json(translate('Recipient_preview_table_hint'));
            const strRecipientPreviewMoreExist = @json(translate('Recipient_preview_more_exist'));
            const waFollowupListUrl = @json(route('admin.voice-call.whatsapp-followup.list'));
            const waFollowupSummaryGenerateUrl = @json(route('admin.voice-call.whatsapp-followup.summary.generate'));
            const voiceCronRunsUrl = @json(route('admin.voice-call.cron-jobs.runs'));
            const voiceCronRunDetailsUrlTemplate = @json(url('admin/voice-call/cron-jobs/runs/__ID__'));
            const voiceCronDispatchPreviewUrlTemplate = @json(url('admin/voice-call/cron-jobs/runs/__ID__/dispatch-preview'));
            const strVoiceCronSelectAtLeastOne = @json(translate('Voice_cron_select_at_least_one_contact'));
            const waFollowupCallReasonLabels = @json($callReasonLabels ?? []);
            const voiceCallReasonBadgeClasses = @json(\Modules\LeadManagement\Services\OutboundCallContextService::callReasonBadgeClasses());
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
            const placeListView = document.getElementById('voice-place-list-view');
            const placeFormView = document.getElementById('voice-place-form-view');
            const placeCallsContent = document.getElementById('voice-place-calls-content');
            const placeShowFormBtn = document.getElementById('voice-place-show-form');
            const placeBackToListBtn = document.getElementById('voice-place-back-to-list');
            const bulkPanel = document.getElementById('voice-tab-bulk');
            const bulkListView = document.getElementById('voice-bulk-list-view');
            const bulkFormView = document.getElementById('voice-bulk-form-view');
            const bulkDetailView = document.getElementById('voice-bulk-detail-view');
            const bulkCampaignDetailContent = document.getElementById('voice-bulk-campaign-detail-content');
            const bulkShowFormBtn = document.getElementById('voice-bulk-show-form');
            const bulkBackToListBtn = document.getElementById('voice-bulk-back-to-list');
            const bulkBackFromDetailBtn = document.getElementById('voice-bulk-back-from-detail');
            const waFollowupPanel = document.getElementById('voice-tab-whatsapp-followup');
            const voiceCronPanel = document.getElementById('voice-tab-voice-cron');
            const historyPanel = document.getElementById('voice-tab-history');
            const forwardedPanel = document.getElementById('voice-tab-forwarded');
            const callbackPanel = document.getElementById('voice-tab-callback');
            const apiLogsPanel = document.getElementById('voice-tab-api-logs');
            const historyContent = document.getElementById('voice-history-content');
            const forwardedContent = document.getElementById('voice-forwarded-content');
            const callbackContent = document.getElementById('voice-callback-content');
            const apiLogsContent = document.getElementById('voice-api-logs-content');
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
            let bulkSubmitting = false;
            let bulkCancelSubmitting = false;
            let pendingBulkCancelCampaignId = null;
            let waFollowupLoaded = false;
            let waFollowupLoading = false;
            let currentWaFollowupParams = new URLSearchParams();
            let activeTab = 'place';
            let currentHistoryParams = new URLSearchParams();
            let currentForwardedParams = new URLSearchParams();
            let currentCallbackParams = new URLSearchParams();
            let currentBulkParams = new URLSearchParams();
            let bulkView = 'list';
            let currentBulkCampaignId = null;
            let lastCreatedBulkCampaignId = null;
            const bulkCampaignDetailsLoading = new Set();
            let currentApiLogsParams = new URLSearchParams();
            let apiLogsLoaded = false;
            let apiLogsLoading = false;
            let placeCallSubmitting = false;
            let placeCallsLoaded = false;
            let placeCallsLoading = false;
            let currentPlacedCallsParams = new URLSearchParams();
            let placeCallView = 'list';
            let reloadPlacedCallsAfterModal = false;
            let catalogRefreshing = false;
            let pendingDeleteCallId = null;
            const transcriptHinglishCache = new Map();
            const waFollowupSummaryCache = new Map();
            const tabHtmlCache = new Map();
            const TAB_HTML_CACHE_TTL_MS = 45000;
            const TAB_HTML_CACHE_MAX_ENTRIES = 48;

            function tabHtmlCacheKey(url, params) {
                const normalized = new URLSearchParams(params.toString());
                normalized.sort();
                return url + '?' + normalized.toString();
            }

            function readTabHtmlCache(key) {
                const entry = tabHtmlCache.get(key);
                if (!entry) {
                    return null;
                }
                if ((Date.now() - entry.ts) > TAB_HTML_CACHE_TTL_MS) {
                    tabHtmlCache.delete(key);
                    return null;
                }
                return entry.html;
            }

            function writeTabHtmlCache(key, html) {
                if (tabHtmlCache.size >= TAB_HTML_CACHE_MAX_ENTRIES) {
                    const oldestKey = tabHtmlCache.keys().next().value;
                    if (oldestKey) {
                        tabHtmlCache.delete(oldestKey);
                    }
                }
                tabHtmlCache.set(key, { html: html, ts: Date.now() });
            }

            function invalidateTabHtmlCache(urlPrefix) {
                tabHtmlCache.forEach(function (_entry, key) {
                    if (!urlPrefix || key.indexOf(urlPrefix) === 0) {
                        tabHtmlCache.delete(key);
                    }
                });
            }

            function fetchTabHtml(url, params) {
                const key = tabHtmlCacheKey(url, params);
                const cached = readTabHtmlCache(key);
                const query = params.toString() ? ('?' + params.toString()) : '';

                const fetchFresh = function () {
                    return fetch(url + query, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                        credentials: 'same-origin',
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('load_failed');
                            }
                            return response.text();
                        })
                        .then(function (html) {
                            writeTabHtmlCache(key, html);
                            return html;
                        });
                };

                if (cached) {
                    return Promise.resolve({
                        html: cached,
                        staleWhileRevalidate: fetchFresh(),
                    });
                }

                return fetchFresh().then(function (html) {
                    return { html: html, staleWhileRevalidate: null };
                });
            }

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

            function voiceCallReasonBadgeHtml(reasonKey, label) {
                const key = String(reasonKey || '').trim();
                const text = String(label || waFollowupCallReasonLabels[key] || key || '').trim();
                if (!text) {
                    return '';
                }

                const cls = voiceCallReasonBadgeClasses[key] || 'voice-call-reason-badge--default';
                return '<span class="badge voice-call-reason-badge ' + cls + '">' + escapeHtml(text) + '</span>';
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
                    const isSummary = key === 'lead_summary';
                    const isLongText = isSummary || key === 'notes' || key === 'service_details';
                    const longClass = isLongText ? ' wa-followup-context-item--long' : '';

                    if (isSummary) {
                        const genTitle = filled ? waFollowupRegenerateSummaryLabel : waFollowupGenerateSummaryLabel;
                        const displayValue = filled
                            ? escapeHtml(waFollowupContextDisplayValue(key, raw))
                            : escapeHtml(waFollowupNoSummaryText);

                        return '<div class="wa-followup-context-item wa-followup-context-item--summary wa-followup-lead-summary-row' + longClass + '">' +
                            '<div class="wa-followup-context-item__label">' +
                            '<span class="wa-followup-context-label__text">' + escapeHtml(waFollowupLeadSummaryLabel) + '</span>' +
                            '<button type="button" class="voice-call-copy-btn wa-followup-generate-summary" data-phone="' + escapeHtml(phone || '') + '" title="' + escapeHtml(genTitle) + '" aria-label="' + escapeHtml(genTitle) + '">' +
                            '<span class="material-icons" aria-hidden="true">autorenew</span></button></div>' +
                            '<div class="wa-followup-context-item__value wa-followup-context-value wa-followup-lead-summary-value' + (filled ? '' : ' text-muted') + '">' + displayValue + '</div></div>';
                    }

                    if (!filled) {
                        hasEmpty = true;
                    }

                    const label = escapeHtml(waFollowupContextKeyLabel(key));
                    const value = filled
                        ? (key === 'call_reason'
                            ? voiceCallReasonBadgeHtml(raw, waFollowupContextDisplayValue(key, raw))
                            : escapeHtml(waFollowupContextDisplayValue(key, raw)))
                        : '<span class="text-muted">—</span>';

                    return '<div class="wa-followup-context-item' + (filled ? '' : ' wa-followup-context-item--empty') + longClass + '">' +
                        '<div class="wa-followup-context-item__label">' + label + '</div>' +
                        '<div class="wa-followup-context-item__value wa-followup-context-value">' + value + '</div></div>';
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
                const grid = card ? card.querySelector('.wa-followup-call-context-grid') : null;
                const copyBtn = card ? card.querySelector('.wa-followup-call-context-copy') : null;
                const viewAllBtn = card ? card.querySelector('.wa-followup-call-context-view-all') : null;

                if (!grid) return;

                const rendered = renderWaFollowupCallContextGrid(context || {}, phone);
                grid.innerHTML = rendered.html;
                grid.classList.remove('is-show-all');

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
                        const grid = btn.closest('.wa-followup-call-context-card')?.querySelector('.wa-followup-call-context-grid');
                        if (grid) {
                            grid.classList.add('is-show-all');
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

            function formatAgentOption(agent) {
                const typeLabel = agent.bot_call_type ? ' (' + agent.bot_call_type + ')' : '';
                return {
                    value: String(agent.id),
                    label: agent.name + typeLabel,
                    dataLabel: agent.name,
                };
            }

            function formatPhoneOption(number) {
                let label = (number.name && String(number.name).trim())
                    ? number.name + ' — ' + number.phone_number
                    : number.phone_number;
                if (number.number_provider) {
                    label += ' (' + number.number_provider + ')';
                }
                return {
                    value: String(number.id),
                    label: label,
                    dataLabel: label,
                };
            }

            function rebuildSelectOptions(select, items, placeholder, preserveValue) {
                if (!select) return;

                const current = preserveValue ? select.value : '';
                select.innerHTML = '';

                const emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = placeholder;
                select.appendChild(emptyOpt);

                items.forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = item.value;
                    opt.textContent = item.label;
                    if (item.dataLabel) {
                        opt.setAttribute('data-label', item.dataLabel);
                    }
                    if (current && item.value === current) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });

                if (typeof $ !== 'undefined' && $.fn.select2 && $(select).hasClass('select2-hidden-accessible')) {
                    $(select).select2('destroy');
                    $(select).select2(select2OptionsFor(select));
                }
            }

            function updateOmniDimensionCatalog(agents, phoneNumbers) {
                const agentItems = (agents || []).map(formatAgentOption);
                const phoneItems = (phoneNumbers || []).map(formatPhoneOption);

                const agentSelect = document.getElementById('agent_id');
                rebuildSelectOptions(agentSelect, agentItems, strSelectAgent, true);
                if (agentSelect) {
                    agentSelect.disabled = agentItems.length === 0;
                }

                document.querySelectorAll('.voice-omnidim-phone-select').forEach(function (select) {
                    rebuildSelectOptions(select, phoneItems, strSelectPhoneNumber, true);
                });

                const submitBtn = document.querySelector('#voice-call-form button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = agentItems.length === 0;
                }

                syncLabels();
                initSelect2In(placePanel);
                initSelect2In(bulkPanel);
                bindBulkAudienceSelect2Handlers();
                bindBulkScheduleHandlers();
                if (activeTab === 'whatsapp_followup') {
                    refreshSelect2In(waFollowupPanel);
                }
            }

            function bindRefreshCatalogButton() {
                const btn = document.getElementById('voice-omnidim-refresh-catalog');
                if (!btn) return;

                btn.addEventListener('click', function () {
                    if (catalogRefreshing) return;

                    catalogRefreshing = true;
                    const originalHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> {{ translate('Loading') }}…';

                    fetch(refreshCatalogUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function (result) {
                            if (!result.ok || result.data.ok === false) {
                                throw new Error(result.data.message || '{{ translate('OmniDimension_catalog_refresh_failed') }}');
                            }

                            updateOmniDimensionCatalog(result.data.agents || [], result.data.phone_numbers || []);

                            const countMsg = (result.data.agents_count || 0) + ' {{ translate('agents') }}, '
                                + (result.data.phone_numbers_count || 0) + ' {{ translate('phone_numbers') }}';
                            if (typeof toastr !== 'undefined') {
                                toastr.success((result.data.message || '') + ' (' + countMsg + ')');
                            }
                        })
                        .catch(function (err) {
                            if (typeof toastr !== 'undefined') {
                                toastr.error(err.message || '{{ translate('OmniDimension_catalog_refresh_failed') }}');
                            }
                        })
                        .finally(function () {
                            catalogRefreshing = false;
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        });
                });
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
                apiLogsPanel.classList.toggle('d-none', tab !== 'api_logs');

                const url = new URL(window.location.href);
                if (tab === 'history' || tab === 'forwarded' || tab === 'callback' || tab === 'bulk' || tab === 'whatsapp_followup' || tab === 'voice_cron' || tab === 'api_logs') {
                    url.searchParams.set('tab', tab);
                } else {
                    url.searchParams.delete('tab');
                }
                window.history.replaceState({}, '', url.toString());

                if (tab === 'whatsapp_followup') {
                    requestAnimationFrame(function () {
                        refreshSelect2In(waFollowupPanel);
                    });
                }
            }

            function setPlaceCallView(view) {
                placeCallView = view === 'form' ? 'form' : 'list';
                if (placeListView) {
                    placeListView.classList.toggle('d-none', placeCallView === 'form');
                }
                if (placeFormView) {
                    placeFormView.classList.toggle('d-none', placeCallView === 'list');
                }

                const url = new URL(window.location.href);
                if (activeTab === 'place' && placeCallView === 'form') {
                    url.searchParams.set('place_view', 'form');
                } else {
                    url.searchParams.delete('place_view');
                }
                window.history.replaceState({}, '', url.toString());

                if (placeCallView === 'form') {
                    initSelect2In(placeFormView);
                    initVoiceFieldTooltips(placeFormView);
                }
            }

            function bindPlacedCallsEvents() {
                if (!placeCallsContent) return;

                const filterForm = placeCallsContent.querySelector('#voice-placed-filter-form');
                if (filterForm) {
                    filterForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        loadPlacedCalls(new URLSearchParams(new FormData(filterForm)));
                    });

                    const searchInput = filterForm.querySelector('input[name="search"]');
                    if (searchInput) {
                        searchInput.addEventListener('input', debounce(function () {
                            const params = new URLSearchParams(new FormData(filterForm));
                            params.delete('page');
                            loadPlacedCalls(params);
                        }, voiceCallSearchDebounceMs));
                    }
                }

                placeCallsContent.querySelector('.voice-placed-reset')?.addEventListener('click', function () {
                    if (filterForm) filterForm.reset();
                    loadPlacedCalls(new URLSearchParams());
                });

                placeCallsContent.querySelectorAll('.voice-placed-page-link').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const params = new URLSearchParams(currentPlacedCallsParams.toString());
                        params.set('page', link.getAttribute('data-page') || '1');
                        loadPlacedCalls(params);
                    });
                });

                bindVoiceCallDetailToggles(placeCallsContent);
                bindVoiceCallCopyButtons(placeCallsContent);
                bindVoiceCallTranscriptHinglish(placeCallsContent);
                bindVoiceCallExtractedViewAll(placeCallsContent);
                bindVoiceCallRecordingButtons(placeCallsContent);
            }

            function loadPlacedCalls(params) {
                if (placeCallsLoading || !placeCallsContent) return;

                currentPlacedCallsParams = new URLSearchParams(params.toString());
                placeCallsLoading = true;
                const cacheKey = tabHtmlCacheKey(placedCallsUrl, params);
                if (!readTabHtmlCache(cacheKey)) {
                    placeCallsContent.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';
                }

                fetchTabHtml(placedCallsUrl, params)
                    .then(function (result) {
                        const applyHtml = function (html) {
                            placeCallsContent.innerHTML = html;
                            bindPlacedCallsEvents();
                            initSelect2In(placeCallsContent);
                            placeCallsLoaded = true;
                        };
                        applyHtml(result.html);
                        if (result.staleWhileRevalidate) {
                            result.staleWhileRevalidate.then(applyHtml).catch(function () {});
                        }
                    })
                    .catch(function () {
                        placeCallsContent.innerHTML = '<div class="alert alert-danger mb-0">{{ translate('Failed_to_load') }}</div>';
                    })
                    .finally(function () {
                        placeCallsLoading = false;
                    });
            }

            function bindPlaceCallNavigation() {
                placeShowFormBtn?.addEventListener('click', function () {
                    setPlaceCallView('form');
                });
                placeBackToListBtn?.addEventListener('click', function () {
                    setPlaceCallView('list');
                    if (!placeCallsLoaded) {
                        loadPlacedCalls(new URLSearchParams());
                    }
                });

                const resultModalEl = document.getElementById('voiceCallResultModal');
                if (resultModalEl) {
                    resultModalEl.addEventListener('hidden.bs.modal', function () {
                        if (!reloadPlacedCallsAfterModal) return;
                        reloadPlacedCallsAfterModal = false;
                        setPlaceCallView('list');
                        loadPlacedCalls(new URLSearchParams(currentPlacedCallsParams.toString()));
                    });
                }
            }

            function showVoiceCallResultModal(success, message, details) {
                const modalEl = document.getElementById('voiceCallResultModal');
                const iconEl = document.getElementById('voiceCallResultIcon');
                const titleEl = document.getElementById('voiceCallResultTitle');
                const messageEl = document.getElementById('voiceCallResultMessage');
                const detailsEl = document.getElementById('voiceCallResultDetails');
                if (!modalEl || !iconEl || !titleEl || !messageEl || !detailsEl) return;

                iconEl.src = success ? statusOnIcon : statusOffIcon;
                titleEl.textContent = success ? strVoiceCallSuccessTitle : strVoiceCallFailedTitle;
                messageEl.textContent = message || '';
                if (details) {
                    detailsEl.textContent = details;
                    detailsEl.classList.remove('d-none');
                } else {
                    detailsEl.textContent = '';
                    detailsEl.classList.add('d-none');
                }

                if (typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            }

            function showVoiceBulkResultModal(success, message, details) {
                const modalEl = document.getElementById('voiceBulkResultModal');
                const iconEl = document.getElementById('voiceBulkResultIcon');
                const titleEl = document.getElementById('voiceBulkResultTitle');
                const messageEl = document.getElementById('voiceBulkResultMessage');
                const detailsEl = document.getElementById('voiceBulkResultDetails');
                const successActions = document.getElementById('voiceBulkResultSuccessActions');
                const errorActions = document.getElementById('voiceBulkResultErrorActions');
                if (!modalEl || !iconEl || !titleEl || !messageEl || !detailsEl) return;

                iconEl.src = success ? statusOnIcon : statusOffIcon;
                titleEl.textContent = success ? strVoiceBulkSuccessTitle : strVoiceBulkFailedTitle;
                messageEl.textContent = message || '';
                if (details) {
                    detailsEl.textContent = details;
                    detailsEl.classList.remove('d-none');
                } else {
                    detailsEl.textContent = '';
                    detailsEl.classList.add('d-none');
                }

                successActions?.classList.toggle('d-none', !success);
                errorActions?.classList.toggle('d-none', success);

                if (typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            }

            function resetBulkCampaignForm() {
                const form = document.getElementById('voice-bulk-form');
                if (!form) return;

                form.reset();
                form.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                });
                form.querySelectorAll('.alert-danger').forEach(function (el) {
                    el.remove();
                });
                form.querySelectorAll('.text-danger.small.mt-1').forEach(function (el) {
                    if (el.closest('.mb-30, .col-12')) {
                        el.remove();
                    }
                });

                hideBulkAudiencePreview();
                initSelect2In(bulkFormView);
                bindBulkAudienceSelect2Handlers();
                bindBulkScheduleHandlers();
                updateBulkAudiencePanels();
                updateBulkScheduleUi();
                initVoiceFieldTooltips(bulkFormView);
            }

            function getBulkFormSelectValue(name) {
                const el = document.querySelector('#voice-bulk-form [name="' + name + '"]');
                if (!el) {
                    return '';
                }
                if (typeof $ !== 'undefined' && $(el).hasClass('select2-hidden-accessible')) {
                    return $(el).val() || '';
                }

                return el.value || '';
            }

            function validateBulkCampaignFormClient() {
                const errors = [];
                const form = document.getElementById('voice-bulk-form');
                if (!form) {
                    return errors;
                }

                const campaignName = (form.querySelector('[name="campaign_name"]')?.value || '').trim();
                const phoneNumberId = getBulkFormSelectValue('phone_number_id');
                const recipientKind = getBulkSelectValue('voice_bulk_recipient_kind');
                const sendOption = getBulkSelectValue('voice_bulk_send_option');
                const scheduledAt = (form.querySelector('[name="scheduled_at"]')?.value || '').trim();
                const csvInput = document.getElementById('voice_bulk_contacts_csv');
                const csvFile = csvInput?.files?.[0] || null;

                if (!campaignName) {
                    errors.push('{{ translate('Campaign_name') }} {{ translate('Voice_bulk_field_required') }}');
                }
                if (!phoneNumberId) {
                    errors.push('{{ translate('Caller_Phone_Number') }} {{ translate('Voice_bulk_field_required') }}');
                }
                if (!recipientKind) {
                    errors.push('{{ translate('Voice_bulk_recipient_kind') }} {{ translate('Voice_bulk_field_required') }}');
                }
                if (sendOption === 'schedule' && !scheduledAt) {
                    errors.push('{{ translate('Schedule_Date') }} {{ translate('Voice_bulk_field_required') }}');
                }
                if (recipientKind === 'csv_import' && !csvFile) {
                    errors.push('{{ translate('CSV_file') }} {{ translate('Voice_bulk_field_required') }}');
                }

                return errors;
            }

            function highlightBulkFormClientErrors(errors) {
                const form = document.getElementById('voice-bulk-form');
                if (!form) return;

                form.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                });

                const campaignName = form.querySelector('[name="campaign_name"]');
                const phoneNumberId = form.querySelector('[name="phone_number_id"]');
                const recipientKind = document.getElementById('voice_bulk_recipient_kind');
                const scheduledAt = document.getElementById('voice_bulk_scheduled_at');
                const csvInput = document.getElementById('voice_bulk_contacts_csv');

                (errors || []).forEach(function (msg) {
                    if (msg.indexOf('{{ translate('Campaign_name') }}') === 0 && campaignName) {
                        campaignName.classList.add('is-invalid');
                    } else if (msg.indexOf('{{ translate('Caller_Phone_Number') }}') === 0 && phoneNumberId) {
                        phoneNumberId.classList.add('is-invalid');
                    } else if (msg.indexOf('{{ translate('Voice_bulk_recipient_kind') }}') === 0 && recipientKind) {
                        recipientKind.classList.add('is-invalid');
                    } else if (msg.indexOf('{{ translate('Schedule_Date') }}') === 0 && scheduledAt) {
                        updateBulkScheduleUi();
                        scheduledAt.classList.add('is-invalid');
                    } else if (msg.indexOf('{{ translate('CSV_file') }}') === 0 && csvInput) {
                        updateBulkAudiencePanels();
                        csvInput.classList.add('is-invalid');
                    }
                });

                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            function bindBulkFormSubmit() {
                const form = document.getElementById('voice-bulk-form');
                if (!form) return;

                document.getElementById('voiceBulkResultScheduleMore')?.addEventListener('click', function () {
                    const modalEl = document.getElementById('voiceBulkResultModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    }
                    setBulkView('form');
                    resetBulkCampaignForm();
                });

                document.getElementById('voiceBulkResultViewScheduled')?.addEventListener('click', function () {
                    const modalEl = document.getElementById('voiceBulkResultModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    }
                    bulkLoaded = false;
                    invalidateTabHtmlCache(bulkCampaignsUrl);
                    invalidateTabHtmlCache(bulkCampaignDetailsUrl);
                    if (lastCreatedBulkCampaignId) {
                        openBulkCampaignDetail(lastCreatedBulkCampaignId, '1', true);
                    } else {
                        setBulkView('list');
                        loadBulkCampaigns(new URLSearchParams());
                    }
                });

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (bulkSubmitting) return;

                    const clientErrors = validateBulkCampaignFormClient();
                    if (clientErrors.length) {
                        highlightBulkFormClientErrors(clientErrors);
                        showVoiceBulkResultModal(
                            false,
                            '{{ translate('Please_fix_the_following_errors') }}',
                            clientErrors.join(' ')
                        );
                        return;
                    }

                    if (!window.fetch) {
                        form.submit();
                        return;
                    }

                    form.querySelectorAll('.is-invalid').forEach(function (el) {
                        el.classList.remove('is-invalid');
                    });

                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalHtml = submitBtn ? submitBtn.innerHTML : '';
                    bulkSubmitting = true;
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ translate('Loading') }}…';
                    }

                    const formData = new FormData(form);
                    fetch(bulkStoreUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                        body: formData,
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, status: response.status, data: data };
                            }).catch(function () {
                                return {
                                    ok: false,
                                    status: response.status,
                                    data: { message: '{{ translate('Something_went_wrong') }}' },
                                };
                            });
                        })
                        .then(function (result) {
                            if (result.ok && result.data.ok !== false) {
                                const parts = [];
                                if (result.data.campaign_id) {
                                    lastCreatedBulkCampaignId = String(result.data.campaign_id);
                                    parts.push(strVoiceBulkCampaignId + ': #' + result.data.campaign_id);
                                } else {
                                    lastCreatedBulkCampaignId = null;
                                }
                                if (result.data.status) {
                                    parts.push(strCallStatus + ': ' + result.data.status);
                                }
                                if (typeof result.data.contact_count === 'number') {
                                    parts.push(strVoiceBulkContactCount + ': ' + result.data.contact_count);
                                }
                                if (result.data.send_option === 'schedule' && result.data.scheduled_at) {
                                    parts.push('{{ translate('Schedule_Date') }}: ' + result.data.scheduled_at);
                                }
                                invalidateTabHtmlCache(bulkCampaignsUrl);
                                bulkLoaded = false;
                                showVoiceBulkResultModal(true, result.data.message || strVoiceBulkSuccessTitle, parts.join(' · '));
                            } else {
                                let details = '';
                                if (result.data.error) {
                                    details = result.data.error;
                                } else if (result.data.errors) {
                                    details = Object.values(result.data.errors).flat().join(' ');
                                } else if (result.data.message) {
                                    details = result.data.message;
                                }
                                showVoiceBulkResultModal(
                                    false,
                                    result.data.message || strVoiceBulkFailedTitle,
                                    details
                                );
                            }
                        })
                        .catch(function () {
                            showVoiceBulkResultModal(false, strVoiceBulkFailedTitle, '{{ translate('Something_went_wrong') }}');
                        })
                        .finally(function () {
                            bulkSubmitting = false;
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalHtml;
                            }
                        });
                });
            }

            function bindPlaceCallForm() {
                const form = document.getElementById('voice-call-form');
                if (!form) return;

                form.addEventListener('submit', function (e) {
                    syncLabels();
                    if (!window.fetch) return;

                    e.preventDefault();
                    if (placeCallSubmitting) return;

                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalHtml = submitBtn ? submitBtn.innerHTML : '';
                    placeCallSubmitting = true;
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ translate('Loading') }}…';
                    }

                    const formData = new FormData(form);
                    fetch(placeCallUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                        body: formData,
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            }).catch(function () {
                                return { ok: false, data: { message: '{{ translate('Something_went_wrong') }}' } };
                            });
                        })
                        .then(function (result) {
                            if (result.ok && result.data.ok !== false) {
                                const parts = [];
                                if (result.data.request_id) {
                                    parts.push(strRequestId + ': #' + result.data.request_id);
                                }
                                if (result.data.status) {
                                    parts.push(strCallStatus + ': ' + result.data.status);
                                }
                                if (result.data.to_number) {
                                    parts.push(result.data.to_number);
                                }
                                invalidateTabHtmlCache(placedCallsUrl);
                                invalidateTabHtmlCache(historyUrl);
                                invalidateTabHtmlCache(forwardedUrl);
                                invalidateTabHtmlCache(callbackUrl);
                                reloadPlacedCallsAfterModal = true;
                                showVoiceCallResultModal(true, result.data.message || '{{ translate('Voice_call_dispatched_successfully') }}', parts.join(' · '));
                                form.reset();
                                initSelect2In(placeFormView);
                                syncLabels();
                            } else {
                                let details = '';
                                if (result.data.error) {
                                    details = result.data.error;
                                } else if (result.data.errors) {
                                    details = Object.values(result.data.errors).flat().join(' ');
                                }
                                showVoiceCallResultModal(false, result.data.message || '{{ translate('Voice_call_dispatch_failed') }}', details);
                            }
                        })
                        .catch(function () {
                            showVoiceCallResultModal(false, '{{ translate('Voice_call_dispatch_failed') }}', '');
                        })
                        .finally(function () {
                            placeCallSubmitting = false;
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalHtml;
                            }
                        });
                });
            }

            function bindApiLogsEvents() {
                if (!apiLogsContent) return;

                const filterForm = apiLogsContent.querySelector('#voice-api-logs-filter-form');
                if (filterForm) {
                    filterForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        loadApiLogs(new URLSearchParams(new FormData(filterForm)));
                    });
                }

                apiLogsContent.querySelector('.voice-api-logs-reset')?.addEventListener('click', function () {
                    if (filterForm) filterForm.reset();
                    initSelect2In(apiLogsPanel);
                    loadApiLogs(new URLSearchParams());
                });

                apiLogsContent.querySelectorAll('.voice-api-logs-page-link').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const page = link.getAttribute('data-page');
                        const params = new URLSearchParams(currentApiLogsParams.toString());
                        params.set('page', page);
                        loadApiLogs(params);
                    });
                });

                apiLogsContent.querySelectorAll('.voice-api-log-toggle').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const row = btn.closest('.voice-api-log-row');
                        const logId = row ? row.getAttribute('data-log-id') : null;
                        if (!logId) return;
                        const detailsRow = apiLogsContent.querySelector('.voice-api-log-details[data-log-id="' + logId + '"]');
                        const icon = btn.querySelector('.material-icons');
                        const expanded = detailsRow && !detailsRow.classList.contains('d-none');
                        if (detailsRow) {
                            detailsRow.classList.toggle('d-none', expanded);
                        }
                        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                        if (icon) {
                            icon.textContent = expanded ? 'expand_more' : 'expand_less';
                        }
                    });
                });
            }

            function loadApiLogs(params) {
                if (apiLogsLoading || !apiLogsContent) return;

                currentApiLogsParams = new URLSearchParams(params.toString());
                apiLogsLoading = true;
                const cacheKey = tabHtmlCacheKey(apiLogsUrl, params);
                if (!readTabHtmlCache(cacheKey)) {
                    apiLogsContent.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';
                }

                fetchTabHtml(apiLogsUrl, params)
                    .then(function (result) {
                        const applyHtml = function (html) {
                            apiLogsContent.innerHTML = html;
                            bindApiLogsEvents();
                            initSelect2In(apiLogsPanel);
                            apiLogsLoaded = true;
                        };
                        applyHtml(result.html);
                        if (result.staleWhileRevalidate) {
                            result.staleWhileRevalidate.then(applyHtml).catch(function () {});
                        }
                    })
                    .catch(function () {
                        apiLogsContent.innerHTML = '<div class="alert alert-danger mb-0">{{ translate('Failed_to_load') }}</div>';
                    })
                    .finally(function () {
                        apiLogsLoading = false;
                    });
            }

            function initVoiceFieldTooltips(container) {
                if (typeof bootstrap === 'undefined') {
                    return;
                }
                (container || document).querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                    const existing = bootstrap.Tooltip.getInstance(el);
                    if (existing) {
                        existing.dispose();
                    }
                    new bootstrap.Tooltip(el);
                });
            }

            function select2OptionsFor(select) {
                const $select = $(select);
                const $modal = $select.closest('.modal');

                return {
                    width: '100%',
                    dropdownParent: $modal.length ? $modal : $(document.body),
                };
            }

            function refreshSelect2In(container) {
                if (typeof $ === 'undefined' || !$.fn.select2 || !container) {
                    return;
                }

                $(container).find('.js-select').each(function () {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }
                    $select.select2(select2OptionsFor(this));
                });
                initVoiceFieldTooltips(container);
            }

            function initSelect2In(container) {
                if (typeof $ === 'undefined' || !$.fn.select2) {
                    return;
                }
                $(container).find('.js-select').each(function () {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2(select2OptionsFor(this));
                    }
                });
                initVoiceFieldTooltips(container);
            }

            function setBulkView(view, options) {
                const opts = options || {};
                bulkView = view === 'form' ? 'form' : (view === 'detail' ? 'detail' : 'list');

                if (bulkListView) {
                    bulkListView.classList.toggle('d-none', bulkView !== 'list');
                }
                if (bulkFormView) {
                    bulkFormView.classList.toggle('d-none', bulkView !== 'form');
                }
                if (bulkDetailView) {
                    bulkDetailView.classList.toggle('d-none', bulkView !== 'detail');
                }

                const url = new URL(window.location.href);
                url.searchParams.delete('bulk_view');
                url.searchParams.delete('bulk_campaign_id');

                if (activeTab === 'bulk') {
                    if (bulkView === 'form') {
                        url.searchParams.set('bulk_view', 'form');
                    } else if (bulkView === 'detail' && currentBulkCampaignId) {
                        url.searchParams.set('bulk_campaign_id', String(currentBulkCampaignId));
                    }
                }
                window.history.replaceState({}, '', url.toString());

                if (bulkView === 'form') {
                    initSelect2In(bulkFormView);
                    bindBulkAudienceSelect2Handlers();
                    bindBulkScheduleHandlers();
                    updateBulkAudiencePanels();
                    updateBulkScheduleUi();
                    initVoiceFieldTooltips(bulkFormView);
                    scheduleBulkAudiencePreview();
                } else if (bulkView === 'detail' && opts.loadDetail && currentBulkCampaignId && bulkCampaignDetailContent) {
                    loadBulkCampaignDetails(
                        currentBulkCampaignId,
                        opts.callsPage || '1',
                        bulkCampaignDetailContent,
                        opts.forceReload === true
                    );
                } else if (bulkView === 'list' && bulkDetailView) {
                    pauseVoiceCallRecordings(bulkDetailView);
                }
            }

            function bindBulkNavigation() {
                bulkShowFormBtn?.addEventListener('click', function () {
                    setBulkView('form');
                });
                bulkBackToListBtn?.addEventListener('click', function () {
                    setBulkView('list');
                    if (!bulkLoaded) {
                        loadBulkCampaigns(new URLSearchParams());
                    }
                });
                bulkBackFromDetailBtn?.addEventListener('click', function () {
                    currentBulkCampaignId = null;
                    setBulkView('list');
                    if (!bulkLoaded) {
                        loadBulkCampaigns(new URLSearchParams(currentBulkParams.toString()));
                    }
                });
            }

            function openBulkCancelModal(campaignId, campaignName) {
                pendingBulkCancelCampaignId = campaignId;
                const labelEl = document.getElementById('voiceBulkCancelLabel');
                if (labelEl) {
                    labelEl.textContent = campaignName
                        ? ('#' + campaignId + ' · ' + campaignName)
                        : ('#' + campaignId);
                }
                const modalEl = document.getElementById('voiceBulkCancelModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            }

            function bindBulkCampaignCancelButtons(container) {
                if (!container) return;

                container.querySelectorAll('.voice-bulk-campaign-cancel-btn').forEach(function (btn) {
                    if (btn.getAttribute('data-bound-cancel') === '1') {
                        return;
                    }
                    btn.setAttribute('data-bound-cancel', '1');
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const campaignId = btn.getAttribute('data-campaign-id');
                        const campaignName = btn.getAttribute('data-campaign-name') || '';
                        if (!campaignId) return;
                        openBulkCancelModal(campaignId, campaignName);
                    });
                });
            }

            function bindBulkCampaignCancelConfirm() {
                const confirmBtn = document.getElementById('voiceBulkCancelConfirm');
                const modalEl = document.getElementById('voiceBulkCancelModal');
                if (!confirmBtn || confirmBtn.getAttribute('data-bound-cancel-confirm') === '1') {
                    return;
                }
                confirmBtn.setAttribute('data-bound-cancel-confirm', '1');

                confirmBtn.addEventListener('click', function () {
                    if (!pendingBulkCancelCampaignId || bulkCancelSubmitting) {
                        return;
                    }

                    bulkCancelSubmitting = true;
                    confirmBtn.disabled = true;

                    fetch(bulkCampaignCancelUrl + '/' + encodeURIComponent(pendingBulkCancelCampaignId), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            }).catch(function () {
                                return { ok: false, data: { message: strVoiceBulkCancelFailed } };
                            });
                        })
                        .then(function (result) {
                            if (modalEl && typeof bootstrap !== 'undefined') {
                                bootstrap.Modal.getInstance(modalEl)?.hide();
                            }

                            if (result.ok && result.data.ok !== false) {
                                invalidateTabHtmlCache(bulkCampaignsUrl);
                                invalidateTabHtmlCache(bulkCampaignDetailsUrl);
                                bulkLoaded = false;
                                if (typeof toastr !== 'undefined') {
                                    toastr.success(result.data.message || strVoiceBulkCancelSuccess);
                                }
                                if (bulkView === 'detail') {
                                    currentBulkCampaignId = null;
                                    setBulkView('list');
                                    loadBulkCampaigns(new URLSearchParams(currentBulkParams.toString()));
                                } else {
                                    loadBulkCampaigns(new URLSearchParams(currentBulkParams.toString()));
                                }
                            } else if (typeof toastr !== 'undefined') {
                                toastr.error(result.data.message || strVoiceBulkCancelFailed);
                            }
                        })
                        .catch(function () {
                            if (typeof toastr !== 'undefined') {
                                toastr.error(strVoiceBulkCancelFailed);
                            }
                        })
                        .finally(function () {
                            bulkCancelSubmitting = false;
                            confirmBtn.disabled = false;
                            pendingBulkCancelCampaignId = null;
                        });
                });
            }

            function bindBulkCampaignDetailsEvents(container) {
                if (!container) return;

                bindBulkCampaignCancelButtons(container);
                bindVoiceCallDetailToggles(container);
                bindVoiceCallCopyButtons(container);
                bindVoiceCallTranscriptHinglish(container);
                bindVoiceCallExtractedViewAll(container);
                bindVoiceCallRecordingButtons(container);

                container.querySelectorAll('.voice-bulk-campaign-calls-page-link').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const campaignId = link.getAttribute('data-campaign-id');
                        const page = link.getAttribute('data-page') || '1';
                        if (!campaignId) return;

                        openBulkCampaignDetail(campaignId, page, true);
                    });
                });
            }

            function openBulkCampaignDetail(campaignId, page, forceReload) {
                if (!campaignId || !bulkCampaignDetailContent) {
                    return;
                }

                currentBulkCampaignId = String(campaignId);
                setBulkView('detail');
                loadBulkCampaignDetails(campaignId, page || '1', bulkCampaignDetailContent, forceReload === true);
                bulkDetailView?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function loadBulkCampaignDetails(campaignId, page, host, forceReload) {
                if (!host || bulkCampaignDetailsLoading.has(String(campaignId))) {
                    return;
                }

                bulkCampaignDetailsLoading.add(String(campaignId));

                const params = new URLSearchParams();
                if (page && page !== '1') {
                    params.set('page', page);
                }

                const detailsUrl = bulkCampaignDetailsUrl + '/' + encodeURIComponent(campaignId);
                const detailsParams = new URLSearchParams(params.toString());
                detailsParams.set('id', String(campaignId));
                const cacheKey = tabHtmlCacheKey(detailsUrl, detailsParams);
                const cachedHtml = !forceReload ? readTabHtmlCache(cacheKey) : null;
                if (!cachedHtml) {
                    host.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';
                }

                fetchTabHtml(detailsUrl, detailsParams)
                    .then(function (result) {
                        host.innerHTML = result.html;
                        bindBulkCampaignDetailsEvents(host);
                        if (result.staleWhileRevalidate) {
                            result.staleWhileRevalidate.then(function (html) {
                                host.innerHTML = html;
                                bindBulkCampaignDetailsEvents(host);
                            }).catch(function () {});
                        }
                    })
                    .catch(function () {
                        host.innerHTML = '<div class="alert alert-danger m-3 mb-0">{{ translate('Voice_bulk_campaign_details_load_failed') }}</div>';
                    })
                    .finally(function () {
                        bulkCampaignDetailsLoading.delete(String(campaignId));
                    });
            }

            function bindBulkCampaignOpenButtons(container) {
                if (!container) return;

                bindBulkCampaignCancelButtons(container);

                container.querySelectorAll('.voice-bulk-campaign-open-btn').forEach(function (btn) {
                    if (btn.getAttribute('data-bound-open') === '1') {
                        return;
                    }
                    btn.setAttribute('data-bound-open', '1');
                    btn.addEventListener('click', function () {
                        const campaignId = btn.getAttribute('data-campaign-id');
                        if (!campaignId) return;
                        openBulkCampaignDetail(campaignId, '1', false);
                    });
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

                bindBulkCampaignOpenButtons(bulkCampaignsContent);
            }

            function loadBulkCampaigns(params) {
                if (bulkLoading || !bulkCampaignsContent) {
                    return;
                }

                currentBulkParams = new URLSearchParams(params.toString());
                bulkLoading = true;
                const cacheKey = tabHtmlCacheKey(bulkCampaignsUrl, params);
                if (!readTabHtmlCache(cacheKey)) {
                    bulkCampaignsContent.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';
                }

                fetchTabHtml(bulkCampaignsUrl, params)
                    .then(function (result) {
                        const applyHtml = function (html) {
                            bulkCampaignsContent.innerHTML = html;
                            initSelect2In(bulkCampaignsContent);
                            bindBulkEvents();
                            bulkLoaded = true;
                        };
                        applyHtml(result.html);
                        if (result.staleWhileRevalidate) {
                            result.staleWhileRevalidate.then(applyHtml).catch(function () {});
                        }
                    })
                    .catch(function () {
                        bulkCampaignsContent.innerHTML = '<div class="alert alert-danger mb-0">{{ translate('Voice_bulk_campaigns_load_failed') }}</div>';
                    })
                    .finally(function () {
                        bulkLoading = false;
                    });
            }

            let bulkAudiencePreviewTimer = null;
            let bulkAudiencePreviewAbort = null;

            function getBulkSelectValue(id) {
                const el = document.getElementById(id);
                if (!el) {
                    return '';
                }
                if (typeof $ !== 'undefined' && $(el).hasClass('select2-hidden-accessible')) {
                    return $(el).val() || '';
                }

                return el.value || '';
            }

            function getBulkLeadTypes() {
                const el = document.getElementById('voice_bulk_lead_types');
                if (!el) {
                    return [];
                }
                if (typeof $ !== 'undefined' && $(el).hasClass('select2-hidden-accessible')) {
                    const value = $(el).val();
                    if (Array.isArray(value)) {
                        return value;
                    }

                    return value ? [value] : [];
                }

                return Array.from(el.selectedOptions).map(function (opt) { return opt.value; });
            }

            function setBulkAudiencePanelVisible(id, visible) {
                const el = document.getElementById(id);
                if (!el) {
                    return;
                }
                el.classList.toggle('d-none', !visible);
            }

            function updateBulkLeadSubfilters() {
                const selected = getBulkLeadTypes();
                setBulkAudiencePanelVisible('voice_bulk_lead_customer_subfilters', selected.indexOf('customer') !== -1);
                setBulkAudiencePanelVisible('voice_bulk_lead_invalid_subfilters', selected.indexOf('invalid') !== -1);
                setBulkAudiencePanelVisible('voice_bulk_lead_future_subfilters', selected.indexOf('future_customer') !== -1);
            }

            function updateBulkAudiencePanels() {
                const kind = getBulkSelectValue('voice_bulk_recipient_kind');
                setBulkAudiencePanelVisible('voice_bulk_customer_filters', kind === 'customer');
                setBulkAudiencePanelVisible('voice_bulk_provider_filters', kind === 'provider');
                setBulkAudiencePanelVisible('voice_bulk_lead_filters', kind === 'lead');
                setBulkAudiencePanelVisible('voice_bulk_csv_wrap', kind === 'csv_import');
                if (kind === 'lead') {
                    updateBulkLeadSubfilters();
                }
            }

            function collectBulkAudienceFormBody() {
                const body = new URLSearchParams();
                body.set('_token', csrfToken);
                const root = document.querySelector('.voice-bulk-audience-filters');
                if (!root) {
                    return body;
                }

                root.querySelectorAll('input, select, textarea').forEach(function (el) {
                    if (!el.name || el.disabled || el.type === 'file') {
                        return;
                    }
                    if (el.tagName === 'SELECT' && el.multiple) {
                        Array.from(el.selectedOptions).forEach(function (opt) {
                            body.append(el.name, opt.value);
                        });
                        return;
                    }
                    if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) {
                        return;
                    }
                    if (String(el.value || '').trim() === '') {
                        return;
                    }
                    body.append(el.name, el.value);
                });

                return body;
            }

            function bindBulkAudienceSelect2Handlers() {
                const bulkForm = document.getElementById('voice-bulk-form');
                const recipientKind = document.getElementById('voice_bulk_recipient_kind');
                const leadTypes = document.getElementById('voice_bulk_lead_types');
                const csvInput = document.getElementById('voice_bulk_contacts_csv');

                function onAudienceFiltersChanged() {
                    updateBulkAudiencePanels();
                    scheduleBulkAudiencePreview();
                }

                function onLeadTypesChanged() {
                    updateBulkLeadSubfilters();
                    scheduleBulkAudiencePreview();
                }

                const select2Events = 'change.voiceBulkAudience select2:select.voiceBulkAudience select2:clear.voiceBulkAudience';

                if (typeof $ === 'undefined') {
                    recipientKind?.addEventListener('change', onAudienceFiltersChanged);
                    leadTypes?.addEventListener('change', onLeadTypesChanged);
                    csvInput?.addEventListener('change', onAudienceFiltersChanged);
                    return;
                }

                $(recipientKind).off(select2Events).on(select2Events, onAudienceFiltersChanged);
                $(leadTypes).off(select2Events).on(select2Events, onLeadTypesChanged);
                $(csvInput).off('change.voiceBulkAudience').on('change.voiceBulkAudience', onAudienceFiltersChanged);

                if (!bulkForm) {
                    return;
                }

                $(bulkForm).find('.voice-bulk-audience-filters .js-select').off(select2Events).on(select2Events, function () {
                    if (this.id === 'voice_bulk_recipient_kind') {
                        onAudienceFiltersChanged();
                        return;
                    }
                    if (this.id === 'voice_bulk_lead_types') {
                        onLeadTypesChanged();
                        return;
                    }
                    scheduleBulkAudiencePreview();
                });
            }

            function updateBulkScheduleUi() {
                const sendOption = getBulkSelectValue('voice_bulk_send_option');
                const scheduledAt = document.getElementById('voice_bulk_scheduled_at');
                const isSchedule = sendOption === 'schedule';
                setBulkAudiencePanelVisible('voice_bulk_schedule_wrap', isSchedule);
                if (scheduledAt) {
                    scheduledAt.required = isSchedule;
                }
            }

            function bindBulkScheduleHandlers() {
                const sendOption = document.getElementById('voice_bulk_send_option');
                const scheduleEvents = 'change.voiceBulkSchedule select2:select.voiceBulkSchedule select2:clear.voiceBulkSchedule';

                if (typeof $ === 'undefined') {
                    sendOption?.addEventListener('change', updateBulkScheduleUi);
                    return;
                }

                $(sendOption).off(scheduleEvents).on(scheduleEvents, updateBulkScheduleUi);
            }

            function bindBulkFormToggles() {
                const bulkForm = document.getElementById('voice-bulk-form');
                const autoRetry = document.getElementById('auto_retry');
                const retryWrap = document.getElementById('voice_bulk_retry_wrap');

                bindBulkAudienceSelect2Handlers();
                bindBulkScheduleHandlers();

                bulkForm?.addEventListener('change', function (e) {
                    const target = e.target;
                    if (!target || !target.closest('.voice-bulk-audience-filters')) {
                        return;
                    }
                    if (target.id === 'voice_bulk_recipient_kind' || target.id === 'voice_bulk_lead_types' || target.id === 'voice_bulk_contacts_csv') {
                        return;
                    }
                    scheduleBulkAudiencePreview();
                });

                bulkForm?.addEventListener('input', function (e) {
                    if (e.target && e.target.matches('.voice-bulk-audience-filters input[type="date"]')) {
                        scheduleBulkAudiencePreview();
                    }
                });

                autoRetry?.addEventListener('change', function () {
                    retryWrap?.classList.toggle('d-none', !autoRetry.checked);
                });

                updateBulkAudiencePanels();
                updateBulkScheduleUi();
            }

            function scheduleBulkAudiencePreview() {
                clearTimeout(bulkAudiencePreviewTimer);
                bulkAudiencePreviewTimer = setTimeout(loadBulkAudiencePreview, 300);
            }

            function abortBulkAudiencePreview() {
                if (bulkAudiencePreviewAbort) {
                    bulkAudiencePreviewAbort.abort();
                    bulkAudiencePreviewAbort = null;
                }
            }

            function setBulkAudiencePreviewLoading(isLoading) {
                const loadingEl = document.getElementById('voice_bulk_audience_preview_loading');
                const tableWrap = document.getElementById('voice_bulk_audience_preview_table_wrap');
                if (loadingEl) {
                    loadingEl.classList.toggle('d-none', !isLoading);
                }
                if (isLoading && tableWrap) {
                    tableWrap.classList.add('d-none');
                }
            }

            function renderBulkAudiencePreviewRows(rows, showCategoryCol) {
                const tbody = document.getElementById('voice_bulk_audience_preview_tbody');
                const catCols = document.querySelectorAll('.voice-bulk-audience-preview-cat-col');
                if (!tbody) return;

                tbody.innerHTML = '';
                catCols.forEach(function (col) {
                    col.classList.toggle('d-none', !showCategoryCol);
                });

                (rows || []).forEach(function (row, index) {
                    const tr = document.createElement('tr');
                    const sl = document.createElement('td');
                    sl.textContent = String(index + 1);
                    tr.appendChild(sl);

                    const nameTd = document.createElement('td');
                    nameTd.textContent = row.name || '—';
                    tr.appendChild(nameTd);

                    const phoneTd = document.createElement('td');
                    phoneTd.className = 'text-nowrap';
                    phoneTd.textContent = row.phone_normalized || row.phone || '—';
                    tr.appendChild(phoneTd);

                    if (showCategoryCol) {
                        const catTd = document.createElement('td');
                        catTd.textContent = row.category_name || '—';
                        tr.appendChild(catTd);
                    }

                    tbody.appendChild(tr);
                });
            }

            function finishBulkAudiencePreview(data, recipientKind) {
                const wrap = document.getElementById('voice_bulk_audience_preview_wrap');
                const countEl = document.getElementById('voice_bulk_audience_preview_count');
                const subtitleEl = document.getElementById('voice_bulk_audience_preview_subtitle');
                const tableWrap = document.getElementById('voice_bulk_audience_preview_table_wrap');
                const emptyEl = document.getElementById('voice_bulk_audience_preview_empty');
                const rows = data.rows || [];
                const total = typeof data.total_matching === 'number' ? data.total_matching : rows.length;
                const hasMore = !!data.has_more;
                const showCategory = recipientKind === 'lead'
                    || recipientKind === 'provider'
                    || rows.some(function (row) { return String(row.category_name || '').trim() !== ''; });

                setBulkAudiencePreviewLoading(false);
                wrap?.classList.remove('d-none');

                if (countEl) {
                    countEl.textContent = String(total);
                }

                const subtitleParts = [
                    strRecipientPreviewTotal + ': ' + total + '.',
                    strRecipientPreviewTableHint + '.',
                ];
                if (hasMore) {
                    subtitleParts.push(strRecipientPreviewMoreExist + '.');
                }
                if (subtitleEl) {
                    subtitleEl.textContent = subtitleParts.join(' ');
                }

                if (!rows.length) {
                    tableWrap?.classList.add('d-none');
                    if (emptyEl) {
                        emptyEl.textContent = strVoiceBulkAudiencePreviewEmpty;
                        emptyEl.classList.remove('d-none');
                    }
                    return;
                }

                emptyEl?.classList.add('d-none');
                tableWrap?.classList.remove('d-none');
                renderBulkAudiencePreviewRows(rows, showCategory);
            }

            function hideBulkAudiencePreview() {
                abortBulkAudiencePreview();
                document.getElementById('voice_bulk_audience_preview_wrap')?.classList.add('d-none');
            }

            function showBulkAudiencePreviewMessage(message) {
                const wrap = document.getElementById('voice_bulk_audience_preview_wrap');
                const countEl = document.getElementById('voice_bulk_audience_preview_count');
                const subtitleEl = document.getElementById('voice_bulk_audience_preview_subtitle');
                const tableWrap = document.getElementById('voice_bulk_audience_preview_table_wrap');
                const emptyEl = document.getElementById('voice_bulk_audience_preview_empty');

                setBulkAudiencePreviewLoading(false);
                wrap?.classList.remove('d-none');
                if (countEl) countEl.textContent = '0';
                if (subtitleEl) subtitleEl.textContent = '';
                tableWrap?.classList.add('d-none');
                if (emptyEl) {
                    emptyEl.textContent = message;
                    emptyEl.classList.remove('d-none');
                }
            }

            function loadBulkAudiencePreview() {
                const csvInput = document.getElementById('voice_bulk_contacts_csv');
                const recipientKind = getBulkSelectValue('voice_bulk_recipient_kind');

                if (!recipientKind) {
                    hideBulkAudiencePreview();
                    return;
                }

                abortBulkAudiencePreview();
                setBulkAudiencePreviewLoading(true);
                document.getElementById('voice_bulk_audience_preview_empty')?.classList.add('d-none');
                document.getElementById('voice_bulk_audience_preview_wrap')?.classList.remove('d-none');

                if (recipientKind === 'csv_import') {
                    const file = csvInput && csvInput.files && csvInput.files[0] ? csvInput.files[0] : null;
                    if (!file) {
                        showBulkAudiencePreviewMessage(strVoiceBulkAudienceUploadCsv);
                        return;
                    }

                    const formData = new FormData();
                    formData.append('contacts_csv', file);
                    formData.append('_token', csrfToken);

                    bulkAudiencePreviewAbort = new AbortController();
                    fetch(bulkAudiencePreviewCsvUrl, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        body: formData,
                        signal: bulkAudiencePreviewAbort.signal,
                    })
                        .then(function (response) {
                            if (!response.ok) throw new Error('preview_failed');
                            return response.json();
                        })
                        .then(function (data) {
                            finishBulkAudiencePreview(data, recipientKind);
                        })
                        .catch(function (err) {
                            if (err && err.name === 'AbortError') return;
                            showBulkAudiencePreviewMessage(strVoiceBulkAudiencePreviewFailed);
                        })
                        .finally(function () {
                            bulkAudiencePreviewAbort = null;
                        });

                    return;
                }

                const body = collectBulkAudienceFormBody();

                bulkAudiencePreviewAbort = new AbortController();
                fetch(bulkAudiencePreviewUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    credentials: 'same-origin',
                    body: body.toString(),
                    signal: bulkAudiencePreviewAbort.signal,
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('preview_failed');
                        return response.json();
                    })
                    .then(function (data) {
                        finishBulkAudiencePreview(data, recipientKind);
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') return;
                        showBulkAudiencePreviewMessage(strVoiceBulkAudiencePreviewFailed);
                    })
                    .finally(function () {
                        bulkAudiencePreviewAbort = null;
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

            function waFollowupEmptyStateHtml() {
                return '<div class="text-center text-muted py-5">{{ translate('WhatsApp_followup_filter_prompt') }}</div>';
            }

            function showWaFollowupEmptyState() {
                if (!waFollowupListContent) return;
                waFollowupListContent.innerHTML = waFollowupEmptyStateHtml();
                waFollowupLoaded = false;
                if (waFollowupActionBar) waFollowupActionBar.classList.add('d-none');
            }

            function loadWaFollowupList(params) {
                if (waFollowupLoading || !waFollowupListContent) return;

                currentWaFollowupParams = new URLSearchParams(params.toString());
                waFollowupLoading = true;
                const cacheKey = tabHtmlCacheKey(waFollowupListUrl, params);
                if (!readTabHtmlCache(cacheKey)) {
                    waFollowupListContent.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';
                }

                fetchTabHtml(waFollowupListUrl, params)
                    .then(function (result) {
                        const applyHtml = function (html) {
                            waFollowupListContent.innerHTML = html;
                            bindWaFollowupListEvents();
                            waFollowupLoaded = true;
                        };
                        applyHtml(result.html);
                        if (result.staleWhileRevalidate) {
                            result.staleWhileRevalidate.then(applyHtml).catch(function () {});
                        }
                    })
                    .catch(function () {
                        waFollowupListContent.innerHTML = '<div class="alert alert-danger mb-0">{{ translate('whatsapp_followup_load_failed') }}</div>';
                    })
                    .finally(function () {
                        waFollowupLoading = false;
                    });
            }

            function syncHandledByEmployeeSelect(handledByEl) {
                if (!handledByEl) return;
                const wrapId = handledByEl.getAttribute('data-employee-wrap');
                const wrap = wrapId ? document.getElementById(wrapId) : null;
                const select = wrap ? wrap.querySelector('select[name="handled_by_employee_ids[]"]') : null;
                const show = handledByEl.value === 'human';

                if (wrap) {
                    wrap.classList.toggle('d-none', !show);
                }
                if (select) {
                    select.disabled = !show;
                    if (!show && typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
                        $(select).val(null).trigger('change');
                    }
                }

                if (select && typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
                    $(select).trigger('change.select2');
                }
            }

            function bindWaFollowupPanelEvents() {
                const filterForm = document.getElementById('wa-followup-filter-form');
                const waFollowupHandledBy = document.getElementById('wa-followup-handled-by');
                if (filterForm) {
                    filterForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        loadWaFollowupList(new URLSearchParams(new FormData(filterForm)));
                    });
                }

                waFollowupHandledBy?.addEventListener('change', function () {
                    syncHandledByEmployeeSelect(this);
                });
                syncHandledByEmployeeSelect(waFollowupHandledBy);

                document.getElementById('wa-followup-reset')?.addEventListener('click', function () {
                    if (filterForm) filterForm.reset();
                    refreshSelect2In(waFollowupPanel);
                    syncHandledByEmployeeSelect(waFollowupHandledBy);
                    showWaFollowupEmptyState();
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
                const cacheKey = tabHtmlCacheKey(voiceCronRunsUrl, params);
                if (!readTabHtmlCache(cacheKey)) {
                    voiceCronRunsContent.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';
                }

                fetchTabHtml(voiceCronRunsUrl, params)
                    .then(function (result) {
                        const applyHtml = function (html) {
                            voiceCronRunsContent.innerHTML = html;
                            bindVoiceCronRunsPagination();
                            bindVoiceCronRunDetails();
                            voiceCronRunsLoaded = true;
                        };
                        applyHtml(result.html);
                        if (result.staleWhileRevalidate) {
                            result.staleWhileRevalidate.then(applyHtml).catch(function () {});
                        }
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

            function bindVoiceCronRunDetails() {
                if (!voiceCronRunsContent) return;

                function closeAllRunDetails() {
                    voiceCronRunsContent.querySelectorAll('.voice-cron-run-details-row').forEach(function (row) {
                        row.classList.add('d-none');
                    });
                    voiceCronRunsContent.querySelectorAll('.voice-cron-run-details-slot').forEach(function (slot) {
                        slot.innerHTML = '';
                    });
                }

                voiceCronRunsContent.querySelectorAll('.voice-cron-run-details-btn').forEach(function (btn) {
                    if (btn.dataset.detailsBound === '1') return;
                    btn.dataset.detailsBound = '1';
                    btn.addEventListener('click', function () {
                        const runId = btn.getAttribute('data-run-id');
                        if (!runId) return;

                        const detailsRow = voiceCronRunsContent.querySelector('[data-run-details-for="' + runId + '"]');
                        const slot = voiceCronRunsContent.querySelector('[data-run-details-slot="' + runId + '"]');
                        if (!detailsRow || !slot) return;

                        const isOpen = !detailsRow.classList.contains('d-none');
                        closeAllRunDetails();
                        if (isOpen) {
                            return;
                        }

                        detailsRow.classList.remove('d-none');
                        slot.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';

                        const url = voiceCronRunDetailsUrlTemplate.replace('__ID__', runId);
                        fetch(url, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                            credentials: 'same-origin',
                        })
                            .then(function (response) {
                                if (!response.ok) {
                                    throw new Error('load_failed');
                                }
                                return response.text();
                            })
                            .then(function (html) {
                                slot.innerHTML = html;
                                bindVoiceCronContextPanels(slot);
                                bindVoiceCronDispatchModalTriggers(slot);
                            })
                            .catch(function () {
                                slot.innerHTML = '<div class="alert alert-danger m-3 mb-0">{{ translate('Failed_to_load') }}</div>';
                            });
                    });
                });

                voiceCronRunsContent.querySelectorAll('.voice-cron-run-details-close').forEach(function (btn) {
                    if (btn.dataset.detailsCloseBound === '1') return;
                    btn.dataset.detailsCloseBound = '1';
                    btn.addEventListener('click', closeAllRunDetails);
                });

                bindVoiceCronDispatchModalTriggers(voiceCronRunsContent);
            }

            function updateVoiceCronDispatchSelectedCount(root) {
                if (!root) return;
                const checks = root.querySelectorAll('.voice-cron-dispatch-check');
                const checked = root.querySelectorAll('.voice-cron-dispatch-check:checked');
                const countEl = root.querySelector('.voice-cron-dispatch-count-num');
                const submitBtn = document.getElementById('voice-cron-dispatch-submit');
                if (countEl) {
                    countEl.textContent = String(checked.length);
                }
                if (submitBtn) {
                    submitBtn.disabled = checked.length === 0;
                }
            }

            function bindVoiceCronContextPanels(root) {
                if (!root) return;
                bindVoiceCallCopyButtons(root);
                root.querySelectorAll('.wa-followup-call-context-view-all').forEach(function (btn) {
                    if (btn.dataset.viewAllBound === '1') return;
                    btn.dataset.viewAllBound = '1';
                    btn.addEventListener('click', function () {
                        const grid = btn.closest('.wa-followup-call-context-card')?.querySelector('.wa-followup-call-context-grid');
                        if (grid) {
                            grid.classList.add('is-show-all');
                            btn.classList.add('d-none');
                        }
                    });
                });
            }

            function bindVoiceCronDispatchModalBody(root) {
                if (!root) return;

                root.querySelectorAll('.voice-cron-dispatch-check').forEach(function (cb) {
                    cb.addEventListener('change', function () {
                        updateVoiceCronDispatchSelectedCount(root);
                    });
                });

                const selectAllBtn = root.querySelector('#voice-cron-dispatch-select-all');
                const selectNoneBtn = root.querySelector('#voice-cron-dispatch-select-none');
                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', function () {
                        root.querySelectorAll('.voice-cron-dispatch-check').forEach(function (cb) {
                            cb.checked = true;
                        });
                        updateVoiceCronDispatchSelectedCount(root);
                    });
                }
                if (selectNoneBtn) {
                    selectNoneBtn.addEventListener('click', function () {
                        root.querySelectorAll('.voice-cron-dispatch-check').forEach(function (cb) {
                            cb.checked = false;
                        });
                        updateVoiceCronDispatchSelectedCount(root);
                    });
                }

                const form = root.querySelector('#voice-cron-dispatch-form');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        const checked = root.querySelectorAll('.voice-cron-dispatch-check:checked');
                        if (checked.length === 0) {
                            e.preventDefault();
                            alert(strVoiceCronSelectAtLeastOne);
                        }
                    });
                }

                updateVoiceCronDispatchSelectedCount(root);
            }

            function openVoiceCronDispatchModal(runId) {
                const modalEl = document.getElementById('voiceCronDispatchModal');
                const bodyEl = document.getElementById('voice-cron-dispatch-modal-body');
                const submitBtn = document.getElementById('voice-cron-dispatch-submit');
                if (!modalEl || !bodyEl || !runId) return;

                bodyEl.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';
                if (submitBtn) submitBtn.disabled = true;

                if (typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }

                const url = voiceCronDispatchPreviewUrlTemplate.replace('__ID__', String(runId));
                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('load_failed');
                        }
                        return response.text();
                    })
                    .then(function (html) {
                        bodyEl.innerHTML = html;
                        bindVoiceCronContextPanels(bodyEl);
                        bindVoiceCronDispatchModalBody(bodyEl);
                    })
                    .catch(function () {
                        bodyEl.innerHTML = '<div class="alert alert-danger mb-0">{{ translate('Failed_to_load') }}</div>';
                    });
            }

            function bindVoiceCronDispatchModalTriggers(scope) {
                const root = scope || document;
                root.querySelectorAll('.voice-cron-open-dispatch-modal').forEach(function (btn) {
                    if (btn.dataset.dispatchBound === '1') return;
                    btn.dataset.dispatchBound = '1';
                    btn.addEventListener('click', function () {
                        const runId = btn.getAttribute('data-run-id');
                        openVoiceCronDispatchModal(runId);
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

                function setDurationFromMinutes(totalMinutes, valueName, unitName) {
                    const minutes = parseInt(totalMinutes, 10) || 0;
                    if (minutes > 0 && minutes % (24 * 60) === 0) {
                        setField(valueName, String(minutes / (24 * 60)));
                        setField(unitName, 'days');
                    } else if (minutes > 0 && minutes % 60 === 0) {
                        setField(valueName, String(minutes / 60));
                        setField(unitName, 'hours');
                    } else {
                        setField(valueName, String(minutes || 60));
                        setField(unitName, 'minutes');
                    }
                }

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
                    const idsWrap = document.getElementById('voice-cron-other-job-ids-wrap');
                    if (!modeEl || !idsEl) return;

                    const needsJobPick = modeEl.value === 'include' || modeEl.value === 'exclude';
                    idsEl.disabled = !needsJobPick;
                    if (idsWrap) {
                        idsWrap.classList.toggle('d-none', !needsJobPick);
                    }

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
                    setField('dispatch_mode', 'approval');
                    setField('interval_value', '1');
                    setField('interval_unit', 'hours');
                    setField('silent_min_value', '1');
                    setField('silent_min_unit', 'hours');
                    setMultiSelect('other_cron_job_ids[]', []);
                    setMultiSelect('handled_by_employee_ids[]', []);
                    syncOtherCronJobSelect(null);
                    syncHandledByEmployeeSelect(document.getElementById('voice-cron-handled-by'));
                    initSelect2In(modalEl);
                }

                document.getElementById('voice-cron-job-add')?.addEventListener('click', resetVoiceCronForm);
                document.getElementById('voice-cron-handled-by')?.addEventListener('change', function () {
                    syncHandledByEmployeeSelect(this);
                });
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
                        const filters = rule.filters || {};
                        if (filters.interval_unit && filters.interval_value != null) {
                            setField('interval_value', filters.interval_value);
                            setField('interval_unit', filters.interval_unit);
                        } else {
                            setDurationFromMinutes(rule.interval_minutes, 'interval_value', 'interval_unit');
                        }
                        setField('max_contacts_per_run', rule.max_contacts_per_run);
                        setField('concurrent_call_limit', rule.concurrent_call_limit);
                        setField('is_enabled', rule.is_enabled);
                        setField('dispatch_mode', rule.dispatch_mode || 'approval');

                        if (filters.silent_min_unit && filters.silent_min_value != null) {
                            setField('silent_min_value', filters.silent_min_value);
                            setField('silent_min_unit', filters.silent_min_unit);
                        } else {
                            const silentMinutes = parseInt(filters.silent_min_minutes, 10)
                                || (parseInt(filters.silent_min_hours, 10) || 1) * 60;
                            setDurationFromMinutes(silentMinutes, 'silent_min_value', 'silent_min_unit');
                        }
                        setField('lead_open', filters.lead_open ?? '');
                        setField('wa_chat_bucket', filters.wa_chat_bucket ?? '');
                        setField('handled_by', filters.handled_by ?? '');
                        setMultiSelect('handled_by_employee_ids[]', filters.handled_by_employee_ids || []);
                        syncHandledByEmployeeSelect(document.getElementById('voice-cron-handled-by'));
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

                bindVoiceCronDispatchModalTriggers(voiceCronPanel);
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
                const cacheKey = tabHtmlCacheKey(url, params);
                if (!readTabHtmlCache(cacheKey)) {
                    content.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…</div>';
                }

                fetchTabHtml(url, params)
                    .then(function (result) {
                        const applyHtml = function (html) {
                            content.innerHTML = html;
                            initSelect2In(content);
                            bindCallLogsEvents(content, options);
                            state.loaded = true;
                        };
                        applyHtml(result.html);
                        if (result.staleWhileRevalidate) {
                            result.staleWhileRevalidate.then(applyHtml).catch(function () {});
                        }
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
                            invalidateTabHtmlCache(historyUrl);
                            invalidateTabHtmlCache(forwardedUrl);
                            invalidateTabHtmlCache(callbackUrl);
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
                    if (tab === 'voice_cron' && !voiceCronRunsLoaded) {
                        loadVoiceCronRuns(new URLSearchParams());
                    }
                    if (tab === 'api_logs' && !apiLogsLoaded) {
                        loadApiLogs(new URLSearchParams());
                    }
                    if (tab === 'place' && placeCallView === 'list' && !placeCallsLoaded) {
                        loadPlacedCalls(new URLSearchParams());
                    }
                });
            });

            document.getElementById('agent_id')?.addEventListener('change', syncLabels);
            document.getElementById('from_number_id')?.addEventListener('change', syncLabels);
            bindPlaceCallForm();
            bindPlaceCallNavigation();
            bindRefreshCatalogButton();
            syncLabels();
            bindBulkFormToggles();
            bindBulkFormSubmit();
            bindBulkNavigation();
            bindBulkCampaignCancelConfirm();
            bindWaFollowupPanelEvents();
            bindVoiceCronEvents();
            initSelect2In(bulkPanel);
            bindBulkAudienceSelect2Handlers();
            bindBulkScheduleHandlers();
            initSelect2In(voiceCronPanel);
            initVoiceFieldTooltips(document);

            const initialUrl = new URL(window.location.href);
            const initialTab = initialUrl.searchParams.get('tab');
            const initialPlaceView = initialUrl.searchParams.get('place_view');

            if (!initialTab || initialTab === 'place') {
                if (initialPlaceView === 'form') {
                    setPlaceCallView('form');
                } else {
                    loadPlacedCalls(new URLSearchParams());
                }
            }
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
                const initialBulkView = initialUrl.searchParams.get('bulk_view');
                const hasBulkValidationErrors = @json(
                    $errors->any() && (
                        old('campaign_name') !== null
                        || old('recipient_kind') !== null
                        || old('send_option') !== null
                    )
                );
                const initialBulkCampaignId = initialUrl.searchParams.get('bulk_campaign_id');
                if (initialBulkView === 'form' || hasBulkValidationErrors) {
                    setBulkView('form');
                    if (hasBulkValidationErrors) {
                        document.getElementById('voice-bulk-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else if (initialBulkCampaignId) {
                    currentBulkCampaignId = initialBulkCampaignId;
                    setBulkView('detail', { loadDetail: true, callsPage: '1', forceReload: false });
                } else {
                    loadBulkCampaigns(new URLSearchParams());
                }
            } else if (initialTab === 'whatsapp_followup') {
                setActiveTab('whatsapp_followup');
            } else if (initialTab === 'voice_cron') {
                setActiveTab('voice_cron');
                loadVoiceCronRuns(new URLSearchParams());
            } else if (initialTab === 'api_logs') {
                setActiveTab('api_logs');
                loadApiLogs(new URLSearchParams());
            }
        })();
    </script>
@endpush
