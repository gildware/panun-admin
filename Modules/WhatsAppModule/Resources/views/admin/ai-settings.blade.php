@extends('adminmodule::layouts.new-master')

@section('title', __('whatsapp_ai.page_title'))

@push('css_or_js')
    <style>
        .wa-ai-pre {
            white-space: pre-wrap;
            font-size: 0.8125rem;
            max-height: 420px;
            overflow: auto;
            background: var(--bs-gray-100, #f8f9fa);
            padding: 1rem;
            border-radius: 0.35rem;
            border: 1px solid var(--bs-border-color, #dee2e6);
        }
        .wa-ai-tool-name { font-family: ui-monospace, monospace; font-size: 0.85rem; }
        #wa-ai-flow-render { min-height: 280px; overflow: auto; }
        .wa-ai-ex-steps { list-style: none; padding-left: 0; margin-bottom: 0; }
        .wa-ai-ex-steps li {
            border-left: 3px solid var(--bs-border-color, #dee2e6);
            padding: 0.65rem 0 0.65rem 1rem;
            margin-left: 0.35rem;
            position: relative;
        }
        .wa-ai-ex-steps li::before {
            content: '';
            position: absolute;
            left: -0.4rem;
            top: 0.85rem;
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background: var(--bs-secondary, #6c757d);
        }
        .wa-ai-ex-steps li.wa-ai-st-ok::before { background: var(--bs-success, #198754); }
        .wa-ai-ex-steps li.wa-ai-st-fail::before { background: var(--bs-danger, #dc3545); }
        .wa-ai-ex-steps li.wa-ai-st-skip::before { background: var(--bs-secondary, #6c757d); }
        .wa-ai-ex-steps li.wa-ai-st-info::before { background: var(--bs-info, #0dcaf0); }
        .wa-ai-ex-detail-json {
            font-size: 0.75rem;
            max-height: 200px;
            overflow: auto;
            background: var(--bs-gray-100, #f8f9fa);
            padding: 0.5rem 0.65rem;
            border-radius: 0.25rem;
            margin-top: 0.35rem;
        }
        .wa-ai-guide-table th { font-size: 0.8125rem; }
        .wa-ai-guide-table td { font-size: 0.8125rem; vertical-align: top; }
        .wa-ai-tab-hint { border-left: 3px solid var(--bs-info, #0dcaf0); }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{ __('whatsapp_ai.page_title') }}</h2>
                    <p class="text-muted mb-0 fs-12">{{ __('whatsapp_ai.intro') }}</p>
                    <p class="text-muted mb-0 fs-12 mt-2">{{ __('whatsapp_ai.intro_control') }}</p>
                </div>
            </div>

            @php
                $waAiTabs = [
                    ['id' => 'prompt', 'label' => __('whatsapp_ai.prompts')],
                    ['id' => 'executions', 'label' => __('whatsapp_ai.executions')],
                    ['id' => 'tools', 'label' => __('whatsapp_ai.tools')],
                    ['id' => 'customer_messages', 'label' => __('whatsapp_ai.customer_messages')],
                    ['id' => 'access', 'label' => __('whatsapp_ai.access')],
                    ['id' => 'status', 'label' => __('whatsapp_ai.status')],
                    ['id' => 'flow', 'label' => __('whatsapp_ai.visual_flow')],
                ];
            @endphp

            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                <ul class="nav nav--tabs flex-wrap">
                    @foreach($waAiTabs as $t)
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === $t['id'] ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.ai-settings.edit', ['tab' => $t['id']]) }}">
                                {{ $t['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @php
                $waAiTabHints = [
                    'prompt' => __('whatsapp_ai.tab_hint_prompt'),
                    'executions' => __('whatsapp_ai.tab_hint_executions'),
                    'tools' => __('whatsapp_ai.tab_hint_tools'),
                    'customer_messages' => __('whatsapp_ai.tab_hint_customer_messages'),
                    'access' => __('whatsapp_ai.tab_hint_access'),
                    'status' => __('whatsapp_ai.tab_hint_status'),
                    'flow' => __('whatsapp_ai.tab_hint_flow'),
                ];
            @endphp

            <div class="accordion border shadow-sm mb-4 rounded overflow-hidden" id="waAiGuideAccordion">
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="waAiGuideH1">
                        <button class="accordion-button py-3" type="button" data-bs-toggle="collapse" data-bs-target="#waAiGuideC1" aria-expanded="true" aria-controls="waAiGuideC1">
                            {{ __('whatsapp_ai.guide_accordion_how') }}
                        </button>
                    </h2>
                    <div id="waAiGuideC1" class="accordion-collapse collapse show" aria-labelledby="waAiGuideH1" data-bs-parent="#waAiGuideAccordion">
                        <div class="accordion-body pt-0">
                            {!! __('whatsapp_ai.guide_how_body') !!}
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="waAiGuideH2">
                        <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#waAiGuideC2" aria-expanded="false" aria-controls="waAiGuideC2">
                            {{ __('whatsapp_ai.guide_accordion_storage') }}
                        </button>
                    </h2>
                    <div id="waAiGuideC2" class="accordion-collapse collapse" aria-labelledby="waAiGuideH2" data-bs-parent="#waAiGuideAccordion">
                        <div class="accordion-body pt-0">
                            <p class="small text-muted mb-3">{{ __('whatsapp_ai.guide_storage_intro') }}</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered wa-ai-guide-table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('whatsapp_ai.guide_storage_th_area') }}</th>
                                            <th>{{ __('whatsapp_ai.guide_storage_th_where') }}</th>
                                            <th>{{ __('whatsapp_ai.guide_storage_th_you_edit') }}</th>
                                            <th>{{ __('whatsapp_ai.guide_storage_th_effect') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_prompts') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_prompts_where') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_prompts_edit') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_prompts_effect') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_access') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_access_where') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_access_edit') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_access_effect') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_tools') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_tools_where') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_tools_edit') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_tools_effect') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_customer') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_customer_where') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_customer_edit') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_customer_effect') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_flow') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_flow_where') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_flow_edit') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_flow_effect') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_ops') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_ops_where') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_ops_edit') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_ops_effect') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_key') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_key_where') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_key_edit') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_key_effect') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_faq') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_faq_where') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_faq_edit') }}</td>
                                            <td>{{ __('whatsapp_ai.guide_storage_row_faq_effect') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="waAiGuideH3">
                        <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#waAiGuideC3" aria-expanded="false" aria-controls="waAiGuideC3">
                            {{ __('whatsapp_ai.guide_accordion_features') }}
                        </button>
                    </h2>
                    <div id="waAiGuideC3" class="accordion-collapse collapse" aria-labelledby="waAiGuideH3" data-bs-parent="#waAiGuideAccordion">
                        <div class="accordion-body pt-0">
                            <p class="small text-muted mb-2">{{ __('whatsapp_ai.guide_features_intro') }}</p>
                            <ul class="small mb-0 ps-3">
                                <li>{{ __('whatsapp_ai.guide_feature_business') }}</li>
                                <li>{{ __('whatsapp_ai.guide_feature_zone') }}</li>
                                <li>{{ __('whatsapp_ai.guide_feature_faq') }}</li>
                                <li>{{ __('whatsapp_ai.guide_feature_bookings') }}</li>
                                <li>{{ __('whatsapp_ai.guide_feature_leads') }}</li>
                                <li>{{ __('whatsapp_ai.guide_feature_handoff') }}</li>
                                <li>{{ __('whatsapp_ai.guide_feature_escalation') }}</li>
                                <li>{{ __('whatsapp_ai.guide_feature_unclear') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-0">
                    <h2 class="accordion-header" id="waAiGuideH4">
                        <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#waAiGuideC4" aria-expanded="false" aria-controls="waAiGuideC4">
                            {{ __('whatsapp_ai.guide_accordion_access') }}
                        </button>
                    </h2>
                    <div id="waAiGuideC4" class="accordion-collapse collapse" aria-labelledby="waAiGuideH4" data-bs-parent="#waAiGuideAccordion">
                        <div class="accordion-body pt-0">
                            {!! __('whatsapp_ai.guide_access_intro') !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="wa-ai-tab-hint alert alert-light mb-4 py-3 small text-body-secondary">
                {{ $waAiTabHints[$tab] ?? '' }}
            </div>

            @if($tab === 'status')
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3">
                        <strong>{{ __('whatsapp_ai.status_readout_title') }}</strong>
                        <div class="text-muted small fw-normal mt-1">{{ __('whatsapp_ai.status_readout_help') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0 align-middle">
                                <tbody>
                                    <tr>
                                        <th class="w-25 text-nowrap">{{ __('whatsapp_ai.ai_support_enabled') }}</th>
                                        <td>
                                            @if($runtime['ai_support_enabled'])
                                                <span class="badge bg-success">{{ __('whatsapp_ai.yes') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('whatsapp_ai.no') }}</span>
                                            @endif
                                            <span class="badge {{ ($runtime['ai_support_enabled_src'] ?? 'env') === 'db' ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ ($runtime['ai_support_enabled_src'] ?? 'env') === 'db' ? __('whatsapp_ai.source_db') : __('whatsapp_ai.source_env') }}</span>
                                            <span class="text-muted small ms-2">WHATSAPP_AI_SUPPORT_ENABLED</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.gemini_api_key') }}</th>
                                        <td>
                                            @if($runtime['gemini_key_set'])
                                                <span class="badge bg-success">{{ __('whatsapp_ai.configured') }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ __('whatsapp_ai.missing') }}</span>
                                            @endif
                                            <span class="badge bg-secondary ms-1">{{ __('whatsapp_ai.source_env') }}</span>
                                            <span class="text-muted small ms-2">GEMINI_API_KEY</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.model') }}</th>
                                        <td>
                                            <code class="user-select-all">{{ $runtime['gemini_model'] }}</code>
                                            <span class="badge {{ ($runtime['gemini_model_src'] ?? 'env') === 'db' ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ ($runtime['gemini_model_src'] ?? 'env') === 'db' ? __('whatsapp_ai.source_db') : __('whatsapp_ai.source_env') }}</span>
                                            <span class="text-muted small ms-2">WHATSAPP_GEMINI_MODEL</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.greeting_buttons') }}</th>
                                        <td>
                                            {{ $runtime['greeting_buttons'] ? __('whatsapp_ai.yes') : __('whatsapp_ai.no') }}
                                            <span class="badge {{ ($runtime['greeting_buttons_src'] ?? 'env') === 'db' ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ ($runtime['greeting_buttons_src'] ?? 'env') === 'db' ? __('whatsapp_ai.source_db') : __('whatsapp_ai.source_env') }}</span>
                                            <span class="text-muted small ms-2">WHATSAPP_AI_GREETING_BUTTONS</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.support_hours') }}</th>
                                        <td>
                                            {{ $runtime['support_hours'] }}
                                            <span class="badge {{ ($runtime['support_hours_src'] ?? 'env') === 'db' ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ ($runtime['support_hours_src'] ?? 'env') === 'db' ? __('whatsapp_ai.source_db') : __('whatsapp_ai.source_env') }}</span>
                                            <span class="text-muted small ms-2">WHATSAPP_SUPPORT_HOURS_* / WHATSAPP_SUPPORT_TIMEZONE</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.public_phone') }}</th>
                                        <td>
                                            {{ $runtime['support_phone_display'] !== '' ? $runtime['support_phone_display'] : '—' }}
                                            <span class="badge {{ ($runtime['support_phone_display_src'] ?? 'env') === 'db' ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ ($runtime['support_phone_display_src'] ?? 'env') === 'db' ? __('whatsapp_ai.source_db') : __('whatsapp_ai.source_env') }}</span>
                                            <span class="text-muted small ms-2">WHATSAPP_SUPPORT_PHONE_DISPLAY</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.queue') }}</th>
                                        <td>
                                            <code>{{ $runtime['queue_connection'] }}</code>
                                            <span class="badge {{ ($runtime['queue_src'] ?? 'env') === 'db' ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ ($runtime['queue_src'] ?? 'env') === 'db' ? __('whatsapp_ai.source_db') : __('whatsapp_ai.source_env') }}</span>
                                            <span class="text-muted small ms-2">WHATSAPP_AI_DISPATCH_SYNC / QUEUE_CONNECTION</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @can('whatsapp_chat_assign')
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-body border-bottom py-3">
                            <strong>{{ __('whatsapp_ai.ops_form_title') }}</strong>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">{{ __('whatsapp_ai.ops_form_help') }}</p>
                            <form action="{{ route('admin.whatsapp.ai-settings.update') }}" method="post" class="row g-3">
                                @csrf
                                <input type="hidden" name="return_tab" value="status">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('whatsapp_ai.ai_support_enabled') }}</label>
                                    <select name="db_ai_support_enabled" class="form-select">
                                        @php $v = old('db_ai_support_enabled', $settings->db_ai_support_enabled === null ? '' : ((int) (bool) $settings->db_ai_support_enabled)); @endphp
                                        <option value="" @selected($v === '' || $v === null)>{{ __('whatsapp_ai.inherit_env') }}</option>
                                        <option value="1" @selected((string) $v === '1')>{{ __('whatsapp_ai.yes') }}</option>
                                        <option value="0" @selected((string) $v === '0')>{{ __('whatsapp_ai.no') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('whatsapp_ai.greeting_buttons') }}</label>
                                    <select name="db_greeting_buttons" class="form-select">
                                        @php $vg = old('db_greeting_buttons', $settings->db_greeting_buttons === null ? '' : ((int) (bool) $settings->db_greeting_buttons)); @endphp
                                        <option value="" @selected($vg === '' || $vg === null)>{{ __('whatsapp_ai.inherit_env') }}</option>
                                        <option value="1" @selected((string) $vg === '1')>{{ __('whatsapp_ai.yes') }}</option>
                                        <option value="0" @selected((string) $vg === '0')>{{ __('whatsapp_ai.no') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('whatsapp_ai.model') }}</label>
                                    <input type="text" name="db_gemini_model" class="form-control" value="{{ old('db_gemini_model', $settings->db_gemini_model) }}" placeholder="{{ __('whatsapp_ai.inherit_env') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('whatsapp_ai.support_hours_start') }}</label>
                                    <input type="text" name="db_support_hours_start" class="form-control" value="{{ old('db_support_hours_start', $settings->db_support_hours_start) }}" placeholder="09:00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('whatsapp_ai.support_hours_end') }}</label>
                                    <input type="text" name="db_support_hours_end" class="form-control" value="{{ old('db_support_hours_end', $settings->db_support_hours_end) }}" placeholder="18:00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('whatsapp_ai.support_timezone') }}</label>
                                    <input type="text" name="db_support_timezone" class="form-control" value="{{ old('db_support_timezone', $settings->db_support_timezone) }}" placeholder="Asia/Kolkata">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('whatsapp_ai.public_phone') }}</label>
                                    <input type="text" name="db_support_phone_display" class="form-control" value="{{ old('db_support_phone_display', $settings->db_support_phone_display) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('whatsapp_ai.dispatch_mode') }}</label>
                                    <select name="db_ai_dispatch_sync" class="form-select">
                                        @php $vd = old('db_ai_dispatch_sync', $settings->db_ai_dispatch_sync === null ? '' : ((int) (bool) $settings->db_ai_dispatch_sync)); @endphp
                                        <option value="" @selected($vd === '' || $vd === null)>{{ __('whatsapp_ai.inherit_env') }}</option>
                                        <option value="1" @selected((string) $vd === '1')>{{ __('whatsapp_ai.dispatch_sync_inline') }}</option>
                                        <option value="0" @selected((string) $vd === '0')>{{ __('whatsapp_ai.dispatch_queued') }}</option>
                                    </select>
                                    <div class="form-text">{{ __('whatsapp_ai.dispatch_mode_help') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('whatsapp_ai.queue_connection_label') }}</label>
                                    <input type="text" name="db_queue_connection" class="form-control" value="{{ old('db_queue_connection', $settings->db_queue_connection) }}" placeholder="{{ __('whatsapp_ai.queue_connection_placeholder') }}">
                                    <div class="form-text">{{ __('whatsapp_ai.queue_connection_help') }}</div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="save_operational" value="1" class="btn btn-primary">{{ __('whatsapp_ai.save_operational') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcan
            @endif

            @if($tab === 'flow')
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <strong>{{ __('whatsapp_ai.visual_flow') }}</strong>
                        <span class="text-muted small">{{ __('whatsapp_ai.mermaid_diagram') }}</span>
                    </div>
                    <div class="card-body">
                        <div id="wa-ai-flow-render" class="mb-4"></div>
                        @can('whatsapp_chat_assign')
                            <form action="{{ route('admin.whatsapp.ai-settings.update') }}" method="post">
                                @csrf
                                <input type="hidden" name="return_tab" value="flow">
                                <div class="mb-3">
                                    <label class="form-label" for="flow_mermaid">{{ __('whatsapp_ai.custom_flow_source') }}</label>
                                    <textarea id="flow_mermaid" name="flow_mermaid" class="form-control font-monospace" rows="14" placeholder="{{ __('whatsapp_ai.leave_empty_default') }}">{{ old('flow_mermaid', $settings->flow_mermaid) }}</textarea>
                                    @error('flow_mermaid')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">{{ __('whatsapp_ai.mermaid_syntax') }}</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" name="save_flow" value="1" class="btn btn--primary">{{ __('whatsapp_ai.save') }}</button>
                                    <button type="submit" name="reset_flow" value="1" class="btn btn-outline-secondary" formnovalidate onclick="return confirm(@json(__('whatsapp_ai.reset_flow_confirm')));">{{ __('whatsapp_ai.reset_flow') }}</button>
                                </div>
                            </form>
                        @else
                            <p class="text-muted mb-0">{{ __('whatsapp_ai.assign_permission_edit') }}</p>
                        @endcan
                    </div>
                </div>
            @endif

            @if($tab === 'access')
                <div class="alert alert-secondary border-0 small mb-3">
                    {{ __('whatsapp_ai.access_tab_summary') }}
                </div>
                <p class="text-muted small mb-3">{{ __('whatsapp_ai.access_intro') }}</p>
                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-body border-bottom py-3">
                                <strong>{{ __('whatsapp_ai.what_ai_can') }}</strong>
                                <span class="text-muted small fw-normal">({{ __('whatsapp_ai.built_in') }})</span>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted">{{ __('whatsapp_ai.default_allowed') }}</p>
                                <ul class="small mb-0">
                                    @foreach($allowedLines as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-body border-bottom py-3">
                                <strong>{{ __('whatsapp_ai.what_ai_cannot') }}</strong>
                                <span class="text-muted small fw-normal">({{ __('whatsapp_ai.built_in') }})</span>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted">{{ __('whatsapp_ai.default_forbidden') }}</p>
                                <ul class="small mb-0">
                                    @foreach($forbiddenLines as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @can('whatsapp_chat_assign')
                    <form action="{{ route('admin.whatsapp.ai-settings.update') }}" method="post" class="card border-0 shadow-sm">
                        @csrf
                        <input type="hidden" name="return_tab" value="access">
                        <div class="card-header bg-body border-bottom py-3">
                            <strong>{{ __('whatsapp_ai.edit_access_policies') }}</strong>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">{{ __('whatsapp_ai.access_policies_hint') }}</p>
                            <div class="mb-3">
                                <label class="form-label" for="access_allowed_policy">{{ __('whatsapp_ai.allowed_section') }}</label>
                                <textarea id="access_allowed_policy" name="allowed_policy" class="form-control font-monospace" rows="10">{{ old('allowed_policy', $settings->allowed_policy) }}</textarea>
                                @error('allowed_policy')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="access_forbidden_policy">{{ __('whatsapp_ai.forbidden_section') }}</label>
                                <textarea id="access_forbidden_policy" name="forbidden_policy" class="form-control font-monospace" rows="10">{{ old('forbidden_policy', $settings->forbidden_policy) }}</textarea>
                                @error('forbidden_policy')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer bg-body border-top">
                            <button type="submit" name="save_access" value="1" class="btn btn--primary">{{ __('whatsapp_ai.save') }}</button>
                        </div>
                    </form>
                @else
                    <p class="text-muted">{{ __('whatsapp_ai.assign_permission_edit') }}</p>
                @endcan
            @endif

            @if($tab === 'tools')
                <p class="text-muted small mb-3">{{ __('whatsapp_ai.tools_intro') }}</p>
                @can('whatsapp_chat_assign')
                    <form action="{{ route('admin.whatsapp.ai-settings.update') }}" method="post" class="card border-0 shadow-sm">
                        @csrf
                        <input type="hidden" name="return_tab" value="tools">
                        <div class="card-header bg-body border-bottom py-3">
                            <strong>{{ __('whatsapp_ai.gemini_tools') }}</strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4 text-nowrap">{{ __('whatsapp_ai.tool_enabled') }}</th>
                                            <th class="wa-ai-tool-name">{{ __('whatsapp_ai.name') }}</th>
                                            <th>{{ __('whatsapp_ai.tool_description_override') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($toolsForAdmin as $t)
                                            @php $tn = $t['name']; @endphp
                                            <tr>
                                                <td class="ps-4 align-top">
                                                    <input type="hidden" name="tools_enabled[{{ $tn }}]" value="0">
                                                    <div class="form-check form-switch mt-1">
                                                        <input class="form-check-input" type="checkbox" name="tools_enabled[{{ $tn }}]" value="1" id="tool_en_{{ md5($tn) }}"
                                                            {{ old('tools_enabled.' . $tn, $t['enabled'] ? '1' : '0') === '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label visually-hidden" for="tool_en_{{ md5($tn) }}">{{ __('whatsapp_ai.tool_enabled') }}</label>
                                                    </div>
                                                </td>
                                                <td class="wa-ai-tool-name align-top pt-3">{{ $tn }}</td>
                                                <td>
                                                    <p class="small text-muted mb-1">{{ __('whatsapp_ai.tool_default_desc') }}</p>
                                                    <pre class="wa-ai-pre mb-2" style="max-height:120px;">{{ $t['default_description'] }}</pre>
                                                    <label class="form-label small mb-0" for="tool_desc_{{ md5($tn) }}">{{ __('whatsapp_ai.tool_custom_desc') }}</label>
                                                    <textarea id="tool_desc_{{ md5($tn) }}" name="tools_description[{{ $tn }}]" class="form-control form-control-sm font-monospace" rows="3" placeholder="{{ __('whatsapp_ai.tool_desc_placeholder') }}">{{ old('tools_description.' . $tn, $t['description_override']) }}</textarea>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-body border-top">
                            <button type="submit" name="save_tools" value="1" class="btn btn--primary">{{ __('whatsapp_ai.save') }}</button>
                        </div>
                    </form>
                @else
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">{{ __('whatsapp_ai.name') }}</th>
                                            <th>{{ __('whatsapp_ai.description') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($toolsForAdmin as $t)
                                            <tr>
                                                <td class="ps-4 wa-ai-tool-name">{{ $t['name'] }}</td>
                                                <td class="small">{{ $t['description_override'] !== '' ? $t['description_override'] : $t['default_description'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endcan
            @endif

            @if($tab === 'prompt')
                <p class="text-muted small mb-3">{{ __('whatsapp_ai.prompt_intro') }}</p>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3">
                        <strong>{{ __('whatsapp_ai.base_prompt_code') }}</strong>
                    </div>
                    <div class="card-body">
                        <pre class="wa-ai-pre mb-0">{{ $basePrompt }}</pre>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3">
                        <strong>{{ __('whatsapp_ai.effective_prompt') }}</strong>
                    </div>
                    <div class="card-body">
                        <pre class="wa-ai-pre mb-0">{{ $resolvedPrompt }}</pre>
                    </div>
                </div>

                @can('whatsapp_chat_assign')
                    <form action="{{ route('admin.whatsapp.ai-settings.update') }}" method="post" class="card border-0 shadow-sm">
                        @csrf
                        <input type="hidden" name="return_tab" value="prompt">
                        <div class="card-header bg-body border-bottom py-3">
                            <strong>{{ __('whatsapp_ai.edit_prompt') }}</strong>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="use_full_custom_prompt" value="0">
                                <input class="form-check-input" type="checkbox" name="use_full_custom_prompt" value="1" id="wa_ai_full_custom"
                                    {{ old('use_full_custom_prompt', $settings->use_full_custom_prompt) ? 'checked' : '' }}>
                                <label class="form-check-label" for="wa_ai_full_custom">{{ __('whatsapp_ai.replace_full_prompt') }}</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="custom_system_prompt">{{ __('whatsapp_ai.custom_full_label') }}</label>
                                <textarea id="custom_system_prompt" name="custom_system_prompt" class="form-control font-monospace" rows="14">{{ old('custom_system_prompt', $settings->custom_system_prompt) }}</textarea>
                                @error('custom_system_prompt')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <hr>
                            <p class="small text-muted mb-3">{{ __('whatsapp_ai.when_not_full') }}</p>
                            <div class="mb-3">
                                <label class="form-label" for="assistant_persona">{{ __('whatsapp_ai.assistant_persona') }}</label>
                                <textarea id="assistant_persona" name="assistant_persona" class="form-control font-monospace" rows="8" placeholder="{{ __('whatsapp_ai.assistant_persona_placeholder') }}">{{ old('assistant_persona', $settings->assistant_persona) }}</textarea>
                                @error('assistant_persona')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text">{{ __('whatsapp_ai.assistant_persona_help') }}</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="prompt_addendum">{{ __('whatsapp_ai.addendum') }}</label>
                                <textarea id="prompt_addendum" name="prompt_addendum" class="form-control font-monospace" rows="6">{{ old('prompt_addendum', $settings->prompt_addendum) }}</textarea>
                                @error('prompt_addendum')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text">{{ __('whatsapp_ai.prompt_link_access') }}</div>
                            </div>
                        </div>
                        <div class="card-footer bg-body border-top">
                            <button type="submit" name="save_prompt" value="1" class="btn btn--primary">{{ __('whatsapp_ai.save') }}</button>
                        </div>
                    </form>
                @else
                    <p class="text-muted">{{ __('whatsapp_ai.assign_permission_edit') }}</p>
                @endcan
            @endif

            @if($tab === 'customer_messages')
                <p class="text-muted small mb-3">{{ __('whatsapp_ai.customer_messages_intro') }}</p>
                @php
                    $phDisp = static fn (?string $v) => ($v !== null && trim($v) !== '') ? $v : '—';
                @endphp
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3">
                        <strong>{{ __('whatsapp_ai.placeholders_reference_title') }}</strong>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">{{ __('whatsapp_ai.placeholders_reference_intro') }}</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-nowrap">{{ __('whatsapp_ai.ph_token') }}</th>
                                        <th>{{ __('whatsapp_ai.ph_meaning') }}</th>
                                        <th>{{ __('whatsapp_ai.ph_effective_value') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <tr><td><code>{schedule}</code></td><td>{{ __('whatsapp_ai.placeholder_schedule') }}</td><td class="text-break">{{ $phDisp($placeholderResolved['schedule'] ?? '') }}</td></tr>
                                    <tr><td><code>{phone}</code></td><td>{{ __('whatsapp_ai.placeholder_phone') }}</td><td class="text-break">{{ $phDisp($placeholderResolved['phone'] ?? '') }}</td></tr>
                                    <tr><td><code>{brand}</code></td><td>{{ __('whatsapp_ai.placeholder_brand') }}</td><td class="text-break">{{ $phDisp($placeholderResolved['brand'] ?? '') }}</td></tr>
                                    <tr><td><code>{email}</code></td><td>{{ __('whatsapp_ai.placeholder_email') }}</td><td class="text-break">{{ $phDisp($placeholderResolved['email'] ?? '') }}</td></tr>
                                    <tr><td><code>{website}</code></td><td>{{ __('whatsapp_ai.placeholder_website') }}</td><td class="text-break">{{ $phDisp($placeholderResolved['website'] ?? '') }}</td></tr>
                                    <tr><td><code>{address}</code></td><td>{{ __('whatsapp_ai.placeholder_address') }}</td><td class="text-break">{{ $phDisp($placeholderResolved['address'] ?? '') }}</td></tr>
                                    <tr><td><code>{tagline}</code></td><td>{{ __('whatsapp_ai.placeholder_tagline') }}</td><td class="text-break">{{ $phDisp($placeholderResolved['tagline'] ?? '') }}</td></tr>
                                    <tr><td><code>{custom_1}</code></td><td>{{ __('whatsapp_ai.placeholder_custom_1') }}</td><td class="text-break">{{ $phDisp($placeholderResolved['custom_1'] ?? '') }}</td></tr>
                                    <tr><td><code>{custom_2}</code></td><td>{{ __('whatsapp_ai.placeholder_custom_2') }}</td><td class="text-break">{{ $phDisp($placeholderResolved['custom_2'] ?? '') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-3 mb-0">{{ __('whatsapp_ai.placeholders_ai_hint') }}</p>
                    </div>
                </div>
                @can('whatsapp_chat_assign')
                    <form action="{{ route('admin.whatsapp.ai-settings.update') }}" method="post" class="card border-0 shadow-sm mb-3">
                        @csrf
                        <input type="hidden" name="return_tab" value="customer_messages">
                        <div class="card-header bg-body border-bottom py-3">
                            <strong>{{ __('whatsapp_ai.placeholder_overrides_title') }}</strong>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">{{ __('whatsapp_ai.placeholder_overrides_intro') }}</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="placeholder_schedule">{{ __('whatsapp_ai.field_placeholder_schedule') }}</label>
                                    <textarea id="placeholder_schedule" name="placeholder_schedule" class="form-control font-monospace" rows="3" placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}">{{ old('placeholder_schedule', $settings->placeholder_schedule) }}</textarea>
                                    @error('placeholder_schedule')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="placeholder_phone">{{ __('whatsapp_ai.field_placeholder_phone') }}</label>
                                    <input type="text" id="placeholder_phone" name="placeholder_phone" class="form-control" value="{{ old('placeholder_phone', $settings->placeholder_phone) }}" placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}">
                                    @error('placeholder_phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="placeholder_brand">{{ __('whatsapp_ai.field_placeholder_brand') }}</label>
                                    <input type="text" id="placeholder_brand" name="placeholder_brand" class="form-control" value="{{ old('placeholder_brand', $settings->placeholder_brand) }}" placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}">
                                    @error('placeholder_brand')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="placeholder_email">{{ __('whatsapp_ai.field_placeholder_email') }}</label>
                                    <input type="text" id="placeholder_email" name="placeholder_email" class="form-control" value="{{ old('placeholder_email', $settings->placeholder_email) }}" placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}">
                                    @error('placeholder_email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="placeholder_website">{{ __('whatsapp_ai.field_placeholder_website') }}</label>
                                    <input type="text" id="placeholder_website" name="placeholder_website" class="form-control" value="{{ old('placeholder_website', $settings->placeholder_website) }}" placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}">
                                    @error('placeholder_website')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="placeholder_address">{{ __('whatsapp_ai.field_placeholder_address') }}</label>
                                    <textarea id="placeholder_address" name="placeholder_address" class="form-control font-monospace" rows="2" placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}">{{ old('placeholder_address', $settings->placeholder_address) }}</textarea>
                                    @error('placeholder_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="placeholder_tagline">{{ __('whatsapp_ai.field_placeholder_tagline') }}</label>
                                    <input type="text" id="placeholder_tagline" name="placeholder_tagline" class="form-control" value="{{ old('placeholder_tagline', $settings->placeholder_tagline) }}" placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}">
                                    @error('placeholder_tagline')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="placeholder_custom_1">{{ __('whatsapp_ai.field_placeholder_custom_1') }}</label>
                                    <textarea id="placeholder_custom_1" name="placeholder_custom_1" class="form-control font-monospace" rows="2" placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}">{{ old('placeholder_custom_1', $settings->placeholder_custom_1) }}</textarea>
                                    @error('placeholder_custom_1')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="placeholder_custom_2">{{ __('whatsapp_ai.field_placeholder_custom_2') }}</label>
                                    <textarea id="placeholder_custom_2" name="placeholder_custom_2" class="form-control font-monospace" rows="2" placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}">{{ old('placeholder_custom_2', $settings->placeholder_custom_2) }}</textarea>
                                    @error('placeholder_custom_2')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-header bg-body border-bottom border-top py-3">
                            <strong>{{ __('whatsapp_ai.customer_messages_edit') }}</strong>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">{{ __('whatsapp_ai.templates_placeholder_hint') }}</p>
                            <div class="mb-4">
                                <label class="form-label" for="handoff_message_in_hours">{{ __('whatsapp_ai.handoff_in_hours_label') }}</label>
                                <textarea id="handoff_message_in_hours" name="handoff_message_in_hours" class="form-control font-monospace" rows="8">{{ old('handoff_message_in_hours', $settings->handoff_message_in_hours) }}</textarea>
                                @error('handoff_message_in_hours')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <details class="mt-2 small text-muted">
                                    <summary>{{ __('whatsapp_ai.builtin_default_preview') }}</summary>
                                    <pre class="wa-ai-pre mt-2 mb-0" style="max-height:200px;">{{ $customerMessageDefaults['handoff_in'] }}</pre>
                                </details>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="handoff_message_out_hours">{{ __('whatsapp_ai.handoff_out_hours_label') }}</label>
                                <textarea id="handoff_message_out_hours" name="handoff_message_out_hours" class="form-control font-monospace" rows="8">{{ old('handoff_message_out_hours', $settings->handoff_message_out_hours) }}</textarea>
                                @error('handoff_message_out_hours')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <details class="mt-2 small text-muted">
                                    <summary>{{ __('whatsapp_ai.builtin_default_preview') }}</summary>
                                    <pre class="wa-ai-pre mt-2 mb-0" style="max-height:200px;">{{ $customerMessageDefaults['handoff_out'] }}</pre>
                                </details>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="booking_provider_escalation_message">{{ __('whatsapp_ai.booking_escalation_label') }}</label>
                                <textarea id="booking_provider_escalation_message" name="booking_provider_escalation_message" class="form-control font-monospace" rows="10">{{ old('booking_provider_escalation_message', $settings->booking_provider_escalation_message) }}</textarea>
                                @error('booking_provider_escalation_message')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text">{{ __('whatsapp_ai.booking_escalation_hint') }}</div>
                                <details class="mt-2 small text-muted">
                                    <summary>{{ __('whatsapp_ai.builtin_default_preview') }}</summary>
                                    <pre class="wa-ai-pre mt-2 mb-0" style="max-height:240px;">{{ $customerMessageDefaults['booking_escalation'] }}</pre>
                                </details>
                            </div>
                        </div>
                        <div class="card-footer bg-body border-top">
                            <button type="submit" name="save_customer_messages" value="1" class="btn btn--primary">{{ __('whatsapp_ai.save') }}</button>
                        </div>
                    </form>
                @else
                    <p class="text-muted">{{ __('whatsapp_ai.assign_permission_edit') }}</p>
                @endcan
            @endif

            @if($tab === 'executions')
                <p class="text-muted small mb-3">{{ __('whatsapp_ai.executions_intro') }}</p>

                @if((int) request('id', 0) > 0 && !$executionDetail)
                    <div class="alert alert-warning mb-3">{{ __('whatsapp_ai.execution_not_found') }}</div>
                @endif

                @if($executionDetail)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-body border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <strong>{{ __('whatsapp_ai.execution_detail') }}</strong>
                                <span class="text-muted small ms-2">#{{ $executionDetail->id }}</span>
                            </div>
                            <a href="{{ route('admin.whatsapp.ai-settings.edit', ['tab' => 'executions']) }}" class="btn btn-sm btn-outline-secondary">{{ __('whatsapp_ai.back_to_list') }}</a>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="text-muted small text-uppercase">{{ __('whatsapp_ai.ex_status') }}</div>
                                    <span class="badge bg-secondary">{{ $executionDetail->status }}</span>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small text-uppercase">{{ __('whatsapp_ai.ex_outcome') }}</div>
                                    <code class="small">{{ $executionDetail->outcome ?? '—' }}</code>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small text-uppercase">{{ __('whatsapp_ai.ex_phone') }}</div>
                                    <span class="font-monospace small">{{ $executionDetail->phone ?: '—' }}</span>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small text-uppercase">{{ __('whatsapp_ai.ex_trigger_msg') }}</div>
                                    <span class="font-monospace small">{{ $executionDetail->trigger_whatsapp_message_id }}</span>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small text-uppercase">{{ __('whatsapp_ai.ex_outbound_msg') }}</div>
                                    <span class="font-monospace small">{{ $executionDetail->outbound_whatsapp_message_id ?? '—' }}</span>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small text-uppercase">{{ __('whatsapp_ai.ex_started') }}</div>
                                    <span class="small">{{ $executionDetail->started_at?->format('Y-m-d H:i:s') ?? '—' }}</span>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small text-uppercase">{{ __('whatsapp_ai.ex_finished') }}</div>
                                    <span class="small">{{ $executionDetail->finished_at?->format('Y-m-d H:i:s') ?? '—' }}</span>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small text-uppercase">{{ __('whatsapp_ai.ex_summary') }}</div>
                                    <span class="small">{{ $executionDetail->summary ?? '—' }}</span>
                                </div>
                                @if($executionDetail->error_message)
                                    <div class="col-12">
                                        <div class="text-danger small text-uppercase">{{ __('whatsapp_ai.ex_error') }}</div>
                                        <pre class="wa-ai-pre mb-0 text-danger">{{ $executionDetail->error_message }}</pre>
                                    </div>
                                @endif
                            </div>
                            @if(!empty($executionDetail->meta) && is_array($executionDetail->meta))
                                <p class="text-muted small mb-1">{{ __('whatsapp_ai.ex_meta') }}</p>
                                <pre class="wa-ai-ex-detail-json mb-4">{{ json_encode($executionDetail->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                            <strong class="d-block mb-2">{{ __('whatsapp_ai.ex_steps') }}</strong>
                            <ul class="wa-ai-ex-steps">
                                @foreach($executionDetail->steps ?? [] as $st)
                                    @php
                                        $stStatus = $st['status'] ?? 'info';
                                        $liClass = match ($stStatus) {
                                            'ok' => 'wa-ai-st-ok',
                                            'fail' => 'wa-ai-st-fail',
                                            'skip' => 'wa-ai-st-skip',
                                            default => 'wa-ai-st-info',
                                        };
                                    @endphp
                                    <li class="{{ $liClass }}">
                                        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                                            <div>
                                                <span class="badge bg-light text-dark border me-1">{{ $st['key'] ?? '' }}</span>
                                                <strong class="small">{{ $st['label'] ?? '' }}</strong>
                                                <span class="badge ms-1 {{ $stStatus === 'ok' ? 'bg-success' : ($stStatus === 'fail' ? 'bg-danger' : ($stStatus === 'skip' ? 'bg-secondary' : 'bg-info text-dark')) }}">{{ $stStatus }}</span>
                                            </div>
                                            <span class="text-muted small font-monospace">{{ $st['t'] ?? '' }}</span>
                                        </div>
                                        @if(!empty($st['detail']) && is_array($st['detail']))
                                            <pre class="wa-ai-ex-detail-json mb-0">{{ json_encode($st['detail'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-body border-bottom py-3">
                        <strong>{{ __('whatsapp_ai.executions_list') }}</strong>
                    </div>
                    <div class="card-body p-0">
                        @if($executions && $executions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">{{ __('whatsapp_ai.ex_col_id') }}</th>
                                            <th>{{ __('whatsapp_ai.ex_col_time') }}</th>
                                            <th>{{ __('whatsapp_ai.ex_phone') }}</th>
                                            <th>{{ __('whatsapp_ai.ex_status') }}</th>
                                            <th>{{ __('whatsapp_ai.ex_outcome') }}</th>
                                            <th>{{ __('whatsapp_ai.ex_summary') }}</th>
                                            <th class="text-nowrap">{{ __('whatsapp_ai.ex_duration') }}</th>
                                            <th class="pe-4"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($executions as $ex)
                                            @php
                                                $dur = ($ex->started_at && $ex->finished_at)
                                                    ? $ex->started_at->diffInSeconds($ex->finished_at) . 's'
                                                    : '—';
                                            @endphp
                                            <tr>
                                                <td class="ps-4 font-monospace small">{{ $ex->id }}</td>
                                                <td class="small text-nowrap">{{ $ex->created_at?->format('Y-m-d H:i') }}</td>
                                                <td class="font-monospace small">{{ $ex->phone }}</td>
                                                <td><span class="badge bg-secondary">{{ $ex->status }}</span></td>
                                                <td><code class="small">{{ \Illuminate\Support\Str::limit($ex->outcome ?? '—', 24) }}</code></td>
                                                <td class="small">{{ \Illuminate\Support\Str::limit($ex->summary ?? '—', 56) }}</td>
                                                <td class="small text-muted">{{ $dur }}</td>
                                                <td class="pe-4 text-nowrap">
                                                    <a href="{{ route('admin.whatsapp.ai-settings.edit', ['tab' => 'executions', 'id' => $ex->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('whatsapp_ai.view_steps') }}</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-body border-top">
                                {{ $executions->links() }}
                            </div>
                        @else
                            <p class="text-muted mb-0 p-4">{{ __('whatsapp_ai.executions_empty') }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@if($tab === 'flow')
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>
        <script>
            (function () {
                var src = @json($flowMermaid);
                var el = document.getElementById('wa-ai-flow-render');
                if (!el || typeof mermaid === 'undefined') return;
                mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'strict', flowchart: { htmlLabels: false } });
                var id = 'wa-ai-flow-' + Date.now();
                try {
                    mermaid.render(id, src).then(function (out) {
                        el.innerHTML = out.svg;
                    }).catch(function () {
                        el.innerHTML = '<p class="text-danger small mb-0">{{ e(__('whatsapp_ai.mermaid_render_failed')) }}</p>';
                    });
                } catch (e) {
                    el.innerHTML = '<p class="text-danger small mb-0">{{ e(__('whatsapp_ai.mermaid_render_failed')) }}</p>';
                }
            })();
        </script>
    @endpush
@endif
