@extends('adminmodule::layouts.new-master')

@section('title', __('whatsapp_ai.page_title'))

@php($siInboxCh = request()->route('channel') ?? 'whatsapp')

@push('css_or_js')
    @include('whatsappmodule::admin.partials.social-inbox-page-surface-css')
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
        .wa-ai-tool-info-btn {
            width: 1.125rem;
            height: 1.125rem;
            min-width: 1.125rem;
            padding: 0;
            border-radius: 50%;
            border: 1px solid var(--bs-secondary, #6c757d);
            color: var(--bs-secondary, #6c757d);
            background: transparent;
            line-height: 1;
            text-decoration: none !important;
            vertical-align: middle;
        }
        .wa-ai-tool-info-btn:hover {
            border-color: var(--bs-info, #0dcaf0);
            color: var(--bs-info, #0dcaf0);
            background: rgba(13, 202, 240, 0.08);
        }
        .wa-ai-tool-info-i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-size: 0.62rem;
            font-weight: 700;
            font-style: italic;
            line-height: 1;
        }
        #wa-ai-flow-render { min-height: 280px; overflow: auto; }
        .wa-ai-flow-box svg { max-width: 100%; height: auto; }
        .wa-ai-flow-chart { min-height: 320px; }
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
        .wa-ai-tab-hint { border-left: 3px solid var(--bs-info, #0dcaf0); }
        .wa-ai-summary-layer .badge { font-weight: 500; }

        /* Message config — WhatsApp-style live preview (matches marketing template create) */
        .wa-ai-msg-section .wa-ai-msg-editor { min-width: 0; }
        .wa-ai-live-preview-card .wa-ai-phone-preview { max-width: 100%; }
        .wa-ai-phone-frame {
            background: linear-gradient(160deg, #075e54 0%, #128c7e 45%, #25d366 100%);
            border: 1px solid rgba(0, 0, 0, .08);
        }
        .wa-ai-phone-notch { background: rgba(0, 0, 0, 0.2); }
        .wa-ai-phone-body {
            background: #e5ddd5;
            min-height: 120px;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, .12) 0, transparent 45%),
                radial-gradient(circle at 80% 70%, rgba(0, 0, 0, .04) 0, transparent 40%);
        }
        .wa-ai-msg-btn-fake {
            pointer-events: none;
            cursor: default;
        }
        .wa-ai-preview-mount { min-height: 80px; }
        .wa-ai-msg-nav-tabs {
            background: var(--bs-body-bg, #fff);
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-bottom: none;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        .wa-ai-msg-nav-tabs .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            color: var(--bs-secondary-color, #6c757d);
        }
        .wa-ai-msg-nav-tabs .nav-link:hover {
            border-bottom-color: var(--bs-border-color, #dee2e6);
            color: var(--bs-body-color, #212529);
        }
        .wa-ai-msg-nav-tabs .nav-link.active {
            color: var(--bs-body-color, #212529);
            background: transparent;
            border-bottom-color: var(--bs-primary, #0d6efd);
            font-weight: 600;
        }
        .wa-ai-msg-tab-panels {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
        .wa-ai-tpl-button-row {
            background: var(--bs-tertiary-bg, #f8f9fa);
            margin-bottom: 0.75rem !important;
        }
        .wa-ai-tpl-buttons-scroll {
            max-height: 22rem;
            overflow-y: auto;
            padding-right: 0.25rem;
        }
        @media (max-width: 991.98px) {
            .wa-ai-live-preview-card.sticky-lg-top { position: static !important; top: auto !important; }
        }
    </style>
@endpush

@section('content')
    <div class="main-content social-inbox-page social-inbox-page--{{ $siInboxCh }}">
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
                    ['id' => 'summary', 'label' => __('whatsapp_ai.summary')],
                    ['id' => 'playground', 'label' => __('whatsapp_ai.tab_playground')],
                    ['id' => 'prompt', 'label' => __('whatsapp_ai.prompts')],
                    ['id' => 'executions', 'label' => __('whatsapp_ai.executions')],
                    ['id' => 'tools', 'label' => __('whatsapp_ai.tools')],
                    ['id' => 'ai_config', 'label' => __('whatsapp_ai.tab_ai_config')],
                    ['id' => 'business_config', 'label' => __('whatsapp_ai.tab_business_config')],
                    ['id' => 'message_config', 'label' => __('whatsapp_ai.tab_message_config')],
                    ['id' => 'access', 'label' => __('whatsapp_ai.access')],
                    ['id' => 'flow', 'label' => __('whatsapp_ai.visual_flow')],
                ];
            @endphp

            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                <ul class="nav nav--tabs flex-wrap">
                    @foreach($waAiTabs as $t)
                        <li class="nav-item">
                            <a class="nav-link {{ $tab === $t['id'] ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => $t['id']]) }}">
                                {{ $t['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @php
                $waAiTabHints = [
                    'summary' => __('whatsapp_ai.tab_hint_summary'),
                    'playground' => __('whatsapp_ai.tab_hint_playground'),
                    'prompt' => __('whatsapp_ai.tab_hint_prompt'),
                    'executions' => __('whatsapp_ai.tab_hint_executions'),
                    'tools' => __('whatsapp_ai.tab_hint_tools'),
                    'ai_config' => __('whatsapp_ai.tab_hint_ai_config'),
                    'business_config' => __('whatsapp_ai.tab_hint_business_config'),
                    'message_config' => __('whatsapp_ai.tab_hint_message_config'),
                    'access' => __('whatsapp_ai.tab_hint_access'),
                    'flow' => __('whatsapp_ai.tab_hint_flow'),
                ];
            @endphp

            @if($tab !== 'summary')
                <div class="wa-ai-tab-hint alert alert-light mb-4 py-3 small text-body-secondary">
                    {{ $waAiTabHints[$tab] ?? '' }}
                </div>
            @endif

            @if($tab === 'summary')
                @php
                    $sum = $aiBehaviorSummary;
                    $sec = $sum['assembled_sections'];
                @endphp
                <p class="text-muted small mb-3">{{ __('whatsapp_ai.summary_intro') }}</p>
                @if(!empty($runtime['queue_async_but_driver_is_sync']))
                    <div class="alert alert-warning small mb-4 py-2">{{ __('whatsapp_ai.queue_misconfig_sync_driver') }}</div>
                @endif

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <strong>{{ __('whatsapp_ai.summary_runtime_heading') }}</strong>
                        <div class="d-flex flex-wrap gap-2 align-items-center small">
                            <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => 'ai_config']) }}">{{ __('whatsapp_ai.summary_link_ai_config') }}</a>
                            <span class="text-muted" aria-hidden="true">·</span>
                            <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => 'business_config']) }}">{{ __('whatsapp_ai.summary_link_business_config') }}</a>
                            <span class="text-muted" aria-hidden="true">·</span>
                            <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => 'message_config']) }}">{{ __('whatsapp_ai.summary_link_message_config') }}</a>
                        </div>
                    </div>
                    <div class="card-body py-3">
                        <div class="row g-3 small">
                            <div class="col-md-4">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('whatsapp_ai.ai_support_enabled') }}</div>
                                @if($runtime['ai_support_enabled'])
                                    <span class="badge bg-success">{{ __('whatsapp_ai.yes') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('whatsapp_ai.no') }}</span>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('whatsapp_ai.gemini_api_key') }}</div>
                                @if($runtime['gemini_key_set'])
                                    <span class="badge bg-success">{{ __('whatsapp_ai.configured') }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ __('whatsapp_ai.missing') }}</span>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('whatsapp_ai.model') }}</div>
                                <code class="user-select-all small">{{ $runtime['gemini_model'] }}</code>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('whatsapp_ai.greeting_buttons') }}</div>
                                {{ $runtime['greeting_buttons'] ? __('whatsapp_ai.yes') : __('whatsapp_ai.no') }}
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('whatsapp_ai.support_hours') }}</div>
                                {{ $runtime['support_hours'] }}
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('whatsapp_ai.queue') }}</div>
                                <div><code>{{ $runtime['queue_connection'] }}</code></div>
                                <div class="text-muted mt-1 small">{{ __('whatsapp_ai.queue_effective_label_help') }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('whatsapp_ai.queue_laravel_driver') }}</div>
                                <code>{{ $runtime['queue_default_driver'] ?? 'sync' }}</code>
                                <div class="text-muted mt-1 small">{{ __('whatsapp_ai.queue_laravel_driver_help') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <strong>{{ __('whatsapp_ai.summary_prompt_heading') }}</strong>
                        <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => 'prompt']) }}" class="small">{{ __('whatsapp_ai.summary_link_prompts') }}</a>
                    </div>
                    <div class="card-body">
                        @if($sum['prompt_mode'] === 'full_custom')
                            <p class="small text-info mb-3 mb-md-4">{{ __('whatsapp_ai.summary_prompt_mode_full') }}</p>
                        @else
                            <p class="small text-muted mb-3">{{ __('whatsapp_ai.summary_prompt_mode_assembled') }}</p>
                            <ul class="list-unstyled small wa-ai-summary-layer mb-4">
                                <li class="mb-2 d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge bg-success">{{ __('whatsapp_ai.summary_included') }}</span>
                                    {{ __('whatsapp_ai.summary_layer_base') }}
                                </li>
                                <li class="mb-2 d-flex flex-wrap align-items-center gap-2">
                                    @if($sec['persona'])
                                        <span class="badge bg-success">{{ __('whatsapp_ai.summary_included') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('whatsapp_ai.summary_not_included') }}</span>
                                    @endif
                                    {{ __('whatsapp_ai.summary_layer_persona') }}
                                </li>
                                <li class="mb-2 d-flex flex-wrap align-items-center gap-2">
                                    @if($sec['allowed_policy'])
                                        <span class="badge bg-success">{{ __('whatsapp_ai.summary_included') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('whatsapp_ai.summary_not_included') }}</span>
                                    @endif
                                    {{ __('whatsapp_ai.summary_layer_allowed') }}
                                </li>
                                <li class="mb-2 d-flex flex-wrap align-items-center gap-2">
                                    @if($sec['forbidden_policy'])
                                        <span class="badge bg-success">{{ __('whatsapp_ai.summary_included') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('whatsapp_ai.summary_not_included') }}</span>
                                    @endif
                                    {{ __('whatsapp_ai.summary_layer_forbidden') }}
                                </li>
                                <li class="mb-0 d-flex flex-wrap align-items-center gap-2">
                                    @if($sec['addendum'])
                                        <span class="badge bg-success">{{ __('whatsapp_ai.summary_included') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('whatsapp_ai.summary_not_included') }}</span>
                                    @endif
                                    {{ __('whatsapp_ai.summary_layer_addendum') }}
                                </li>
                            </ul>
                        @endif
                        <p class="small text-muted mb-2">{{ __('whatsapp_ai.summary_effective_prompt_label') }}</p>
                        <pre class="wa-ai-pre mb-0" style="max-height: 420px;">{{ $resolvedPrompt }}</pre>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <strong>{{ __('whatsapp_ai.summary_tools_heading') }}</strong>
                            <span class="text-muted small ms-2">{{ trans_choice('whatsapp_ai.summary_tools_enabled_count', count($sum['enabled_tools'])) }}</span>
                        </div>
                        <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => 'tools']) }}" class="small">{{ __('whatsapp_ai.summary_link_tools') }}</a>
                    </div>
                    <div class="card-body">
                        @if(count($sum['enabled_tools']) === 0)
                            <div class="alert alert-warning mb-3 py-2 small mb-0">{{ __('whatsapp_ai.summary_tools_none_enabled') }}</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-nowrap ps-0">{{ __('whatsapp_ai.name') }}</th>
                                            <th>{{ __('whatsapp_ai.summary_tool_desc_effective') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sum['enabled_tools'] as $tool)
                                            <tr>
                                                <td class="wa-ai-tool-name ps-0 align-top pt-3">
                                                    <div class="d-inline-flex align-items-start gap-1 flex-wrap">
                                                        <span>{{ $tool['name'] }}</span>
                                                        @include('whatsappmodule::admin.partials.wa-ai-tool-info-button', [
                                                            'name' => $tool['name'],
                                                            'defaultDescription' => $tool['default_description'],
                                                            'overrideDescription' => $tool['description_override'],
                                                        ])
                                                    </div>
                                                </td>
                                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($tool['description'], 600) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        @if(count($sum['disabled_tool_names']) > 0)
                            <div class="border-top pt-3">
                                <div class="small text-muted mb-2">{{ __('whatsapp_ai.summary_tools_disabled') }}</div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    @foreach($sum['disabled_tool_names'] as $dn)
                                        @php $waAiDisRef = collect($toolsForAdmin)->firstWhere('name', $dn); @endphp
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <span class="badge bg-light text-dark border">{{ $dn }}</span>
                                            @if($waAiDisRef)
                                                @include('whatsappmodule::admin.partials.wa-ai-tool-info-button', [
                                                    'name' => $waAiDisRef['name'],
                                                    'defaultDescription' => $waAiDisRef['default_description'],
                                                    'overrideDescription' => $waAiDisRef['description_override'],
                                                ])
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($tab === 'playground')
                @include('whatsappmodule::admin.partials.ai-playground-panel', [
                    'playgroundDefaultPhone' => $playgroundDefaultPhone,
                    'playgroundScenarios' => $playgroundScenarios,
                    'runtime' => $runtime,
                ])
            @endif

            @if($tab === 'ai_config')
                @if(!empty($runtime['queue_async_but_driver_is_sync']))
                    <div class="alert alert-warning small mb-3">{{ __('whatsapp_ai.queue_misconfig_sync_driver') }}</div>
                @endif
                <p class="text-muted small mb-3">{{ __('whatsapp_ai.ai_config_intro') }}</p>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3">
                        <strong>{{ __('whatsapp_ai.ai_config_readout_title') }}</strong>
                        <div class="text-muted small fw-normal mt-1">{{ __('whatsapp_ai.ai_config_readout_help') }}</div>
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
                                        <th>{{ __('whatsapp_ai.queue_laravel_driver') }}</th>
                                        <td>
                                            <code>{{ $runtime['queue_default_driver'] ?? 'sync' }}</code>
                                            <span class="text-muted small ms-2">QUEUE_CONNECTION</span>
                                            <div class="text-muted small mt-1">{{ __('whatsapp_ai.queue_laravel_driver_help') }}</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.queue') }}</th>
                                        <td>
                                            <code>{{ $runtime['queue_connection'] }}</code>
                                            <span class="badge {{ ($runtime['queue_src'] ?? 'env') === 'db' ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ ($runtime['queue_src'] ?? 'env') === 'db' ? __('whatsapp_ai.source_db') : __('whatsapp_ai.source_env') }}</span>
                                            <span class="text-muted small ms-2">WHATSAPP_AI_DISPATCH_SYNC</span>
                                            <div class="text-muted small mt-1">{{ __('whatsapp_ai.queue_effective_label_help') }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3">
                        <strong>{{ __('whatsapp_ai.queue_setup_title') }}</strong>
                    </div>
                    <div class="card-body small text-muted">
                        <p class="mb-2"><strong class="text-body">{{ __('whatsapp_ai.queue_setup_dev_label') }}</strong> — {{ __('whatsapp_ai.queue_setup_dev') }}</p>
                        <p class="mb-0"><strong class="text-body">{{ __('whatsapp_ai.queue_setup_prod_label') }}</strong> — {{ __('whatsapp_ai.queue_setup_prod') }}</p>
                    </div>
                </div>
                @can('whatsapp_chat_assign')
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-body border-bottom py-3">
                            <strong>{{ __('whatsapp_ai.ai_config_form_title') }}</strong>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">{{ __('whatsapp_ai.ai_config_form_help') }}</p>
                            <form action="{{ route('admin.whatsapp.ai-settings.update', ['channel' => $siInboxCh]) }}" method="post" class="row g-3">
                                @csrf
                                <input type="hidden" name="return_tab" value="ai_config">
                                <input type="hidden" name="save_ai_config" value="1">
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
                                    <label class="form-label">{{ __('whatsapp_ai.model') }}</label>
                                    <input type="text" name="db_gemini_model" class="form-control" value="{{ old('db_gemini_model', $settings->db_gemini_model) }}" placeholder="{{ __('whatsapp_ai.inherit_env') }}">
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
                                    <button type="submit" class="btn btn-primary">{{ __('whatsapp_ai.save_ai_config') }}</button>
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
                        <span class="text-muted small">{{ __('whatsapp_ai.flow_auto_badge') }}</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">{{ __('whatsapp_ai.flow_auto_intro') }}</p>
                        <div id="wa-ai-flow-render" class="wa-ai-flow-box wa-ai-flow-chart p-3 rounded border bg-white"></div>
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
                    <form action="{{ route('admin.whatsapp.ai-settings.update', ['channel' => $siInboxCh]) }}" method="post" class="card border-0 shadow-sm">
                        @csrf
                        <input type="hidden" name="return_tab" value="access">
                        <input type="hidden" name="save_access" value="1">
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
                            <button type="submit" class="btn btn--primary">{{ __('whatsapp_ai.save') }}</button>
                        </div>
                    </form>
                @else
                    <p class="text-muted">{{ __('whatsapp_ai.assign_permission_edit') }}</p>
                @endcan
            @endif

            @if($tab === 'tools')
                <p class="text-muted small mb-3">{{ __('whatsapp_ai.tools_intro') }}</p>
                @can('whatsapp_chat_assign')
                    <form action="{{ route('admin.whatsapp.ai-settings.update', ['channel' => $siInboxCh]) }}" method="post" class="card border-0 shadow-sm">
                        @csrf
                        <input type="hidden" name="return_tab" value="tools">
                        <input type="hidden" name="save_tools" value="1">
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
                                                <td class="wa-ai-tool-name align-top pt-3">
                                                    <div class="d-inline-flex align-items-start gap-1 flex-wrap">
                                                        <span>{{ $tn }}</span>
                                                        @include('whatsappmodule::admin.partials.wa-ai-tool-info-button', [
                                                            'name' => $tn,
                                                            'defaultDescription' => $t['default_description'],
                                                            'overrideDescription' => $t['description_override'],
                                                        ])
                                                    </div>
                                                </td>
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
                            <button type="submit" class="btn btn--primary">{{ __('whatsapp_ai.save') }}</button>
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
                                                <td class="ps-4 wa-ai-tool-name">
                                                    <div class="d-inline-flex align-items-start gap-1 flex-wrap">
                                                        <span>{{ $t['name'] }}</span>
                                                        @include('whatsappmodule::admin.partials.wa-ai-tool-info-button', [
                                                            'name' => $t['name'],
                                                            'defaultDescription' => $t['default_description'],
                                                            'overrideDescription' => $t['description_override'],
                                                        ])
                                                    </div>
                                                </td>
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
                    <form action="{{ route('admin.whatsapp.ai-settings.update', ['channel' => $siInboxCh]) }}" method="post" class="card border-0 shadow-sm">
                        @csrf
                        <input type="hidden" name="return_tab" value="prompt">
                        <input type="hidden" name="save_prompt" value="1">
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
                            <button type="submit" class="btn btn--primary">{{ __('whatsapp_ai.save') }}</button>
                        </div>
                    </form>
                @else
                    <p class="text-muted">{{ __('whatsapp_ai.assign_permission_edit') }}</p>
                @endcan
            @endif

            @if($tab === 'business_config')
                @can('whatsapp_chat_assign')
                    @if($businessConfigEditMode ?? false)
                    @php
                        $oldDays = old('db_support_days');
                        $selectedSupportDays = is_array($oldDays) && $oldDays !== []
                            ? array_values(array_unique(array_map('intval', $oldDays)))
                            : $supportWorkDaysEffective;
                        $waDayIsoKeys = [1 => 'day_iso_1', 2 => 'day_iso_2', 3 => 'day_iso_3', 4 => 'day_iso_4', 5 => 'day_iso_5', 6 => 'day_iso_6', 7 => 'day_iso_7'];
                        $effStart = old('db_support_hours_start', $settings->db_support_hours_start ?? config('whatsappmodule.support_work_hours_start'));
                        $effEnd = old('db_support_hours_end', $settings->db_support_hours_end ?? config('whatsappmodule.support_work_hours_end'));
                    @endphp
                    <form id="wa-bc-form" action="{{ route('admin.whatsapp.ai-settings.update', ['channel' => $siInboxCh]) }}" method="post" class="mb-3">
                        @csrf
                        <input type="hidden" name="return_tab" value="business_config">
                        <input type="hidden" name="save_business_config" value="1">

                        <div class="d-flex flex-wrap align-items-start gap-2 mb-3">
                            <p class="text-muted small mb-0 flex-grow-1 min-w-0">{{ __('whatsapp_ai.business_config_intro') }}</p>
                            <div class="d-flex flex-wrap gap-2 ms-auto shrink-0 align-items-center justify-content-end">
                                <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => 'business_config']) }}" class="btn btn-sm btn-outline-secondary">{{ __('whatsapp_ai.cancel_edit') }}</a>
                                <button type="submit" class="btn btn-sm btn--primary">{{ __('whatsapp_ai.save_business_config') }}</button>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-body border-bottom py-3">
                                <strong>{{ __('whatsapp_ai.support_availability_title') }}</strong>
                                <div class="text-muted small fw-normal mt-1">{{ __('whatsapp_ai.support_availability_ist_note') }}</div>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="form-label mb-2">{{ __('whatsapp_ai.support_days_label') }}</div>
                                    <div class="d-flex flex-wrap gap-3 column-gap-4">
                                        @foreach($waDayIsoKeys as $iso => $lk)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="db_support_days[]" value="{{ $iso }}"
                                                    id="wa-sd-{{ $iso }}"
                                                    @checked(in_array($iso, $selectedSupportDays, true))>
                                                <label class="form-check-label" for="wa-sd-{{ $iso }}">{{ __('whatsapp_ai.'.$lk) }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('db_support_days')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label" for="db_support_hours_start">{{ __('whatsapp_ai.support_hours_from') }}</label>
                                        <input type="time" name="db_support_hours_start" id="db_support_hours_start" class="form-control"
                                            value="{{ $effStart }}" required step="60">
                                        @error('db_support_hours_start')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="db_support_hours_end">{{ __('whatsapp_ai.support_hours_to') }}</label>
                                        <input type="time" name="db_support_hours_end" id="db_support_hours_end" class="form-control"
                                            value="{{ $effEnd }}" required step="60">
                                        @error('db_support_hours_end')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="db_support_phone_display">{{ __('whatsapp_ai.public_phone') }}</label>
                                        <input type="text" name="db_support_phone_display" id="db_support_phone_display" class="form-control"
                                            value="{{ old('db_support_phone_display', $settings->db_support_phone_display) }}"
                                            placeholder="{{ __('whatsapp_ai.support_phone_placeholder') }}" autocomplete="tel">
                                        @error('db_support_phone_display')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <p class="small text-muted mb-0 mt-3">{{ __('whatsapp_ai.support_tokens_hint') }}</p>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-body border-bottom py-3">
                            <strong>{{ __('whatsapp_ai.business_config_placeholders_section') }}</strong>
                            <div class="text-muted small fw-normal mt-1">{{ __('whatsapp_ai.bc_save_row_hint') }}</div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-nowrap ps-4">{{ __('whatsapp_ai.business_config_table_token') }}</th>
                                            <th>{{ __('whatsapp_ai.business_config_table_meaning') }}</th>
                                            <th>{{ __('whatsapp_ai.business_config_table_default') }}</th>
                                            <th>{{ __('whatsapp_ai.business_config_table_overridden') }}</th>
                                            <th>{{ __('whatsapp_ai.business_config_table_effective') }}</th>
                                            <th class="text-nowrap pe-4" style="min-width: 220px;">{{ __('whatsapp_ai.bc_column_custom') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($businessConfigRows as $bcRow)
                                            @php
                                                $fk = $bcRow['field_key'];
                                                $ovOn = (bool) old('override_'.$fk, $bcRow['is_overridden']);
                                                $inpVal = old($fk, $bcRow['raw_value']);
                                            @endphp
                                            <tr>
                                                <td class="ps-4 font-monospace text-nowrap align-top pt-3">
                                                    @if($bcRow['token'] === '—')
                                                        <span class="text-muted">—</span>
                                                    @else
                                                        <code>{{ $bcRow['token'] }}</code>
                                                    @endif
                                                </td>
                                                <td class="align-top pt-3">{{ __($bcRow['meaning_key']) }}</td>
                                                <td class="text-break align-top pt-3" title="{{ $bcRow['default_value'] }}">{{ \Illuminate\Support\Str::limit($bcRow['default_value'], 100) }}</td>
                                                <td class="align-top pt-3">
                                                    @if($bcRow['is_overridden'])
                                                        <span class="badge bg-info text-dark">{{ __('whatsapp_ai.business_config_badge_overridden') }}</span>
                                                        <div class="text-break small mt-1" title="{{ $bcRow['override_display'] }}">{{ \Illuminate\Support\Str::limit($bcRow['override_display'], 80) }}</div>
                                                    @else
                                                        <span class="badge bg-secondary">{{ __('whatsapp_ai.business_config_badge_auto') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-break align-top pt-3 pe-2" title="{{ $bcRow['effective_value'] }}">{{ \Illuminate\Support\Str::limit($bcRow['effective_value'], 100) }}</td>
                                                <td class="pe-4 align-top">
                                                    <div class="form-check form-switch mb-0">
                                                        <input type="checkbox"
                                                            name="override_{{ $fk }}"
                                                            value="1"
                                                            id="wa-bc-ov-{{ $fk }}"
                                                            class="form-check-input wa-bc-toggle"
                                                            data-wa-bc-field="{{ $fk }}"
                                                            @checked($ovOn)>
                                                        <label class="form-check-label small" for="wa-bc-ov-{{ $fk }}">{{ __('whatsapp_ai.bc_use_override') }}</label>
                                                    </div>
                                                    <div class="wa-bc-panel mt-2 {{ $ovOn ? '' : 'd-none' }}" data-wa-bc-panel="{{ $fk }}">
                                                        @if($bcRow['input_type'] === 'textarea')
                                                            <textarea name="{{ $fk }}"
                                                                rows="{{ $fk === 'placeholder_address' ? 3 : 2 }}"
                                                                class="form-control form-control-sm font-monospace wa-bc-input"
                                                                placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}"
                                                                @disabled(!$ovOn)>{{ $inpVal }}</textarea>
                                                        @else
                                                            <input type="text"
                                                                name="{{ $fk }}"
                                                                value="{{ $inpVal }}"
                                                                class="form-control form-control-sm wa-bc-input"
                                                                placeholder="{{ __('whatsapp_ai.leave_empty_auto') }}"
                                                                @disabled(!$ovOn)>
                                                        @endif
                                                        @error($fk)
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="small text-muted px-4 py-3 mb-0 border-top">{{ __('whatsapp_ai.placeholders_ai_hint') }}</p>
                        </div>
                        </div>
                    </form>
                    @push('script')
                        <script>
                            (function () {
                                var form = document.getElementById('wa-bc-form');
                                if (!form) return;
                                function syncRow(toggle) {
                                    var fk = toggle.getAttribute('data-wa-bc-field');
                                    var panel = form.querySelector('[data-wa-bc-panel="' + fk + '"]');
                                    if (!panel) return;
                                    var on = toggle.checked;
                                    panel.classList.toggle('d-none', !on);
                                    panel.querySelectorAll('.wa-bc-input').forEach(function (el) {
                                        el.disabled = !on;
                                    });
                                }
                                form.querySelectorAll('.wa-bc-toggle').forEach(function (cb) {
                                    cb.addEventListener('change', function () { syncRow(cb); });
                                    syncRow(cb);
                                });
                                form.addEventListener('submit', function () {
                                    form.querySelectorAll('.wa-bc-input').forEach(function (el) { el.disabled = false; });
                                });
                            })();
                        </script>
                    @endpush
                    @else
                        <div class="d-flex flex-wrap align-items-start gap-2 mb-3">
                            <p class="text-muted small mb-0 flex-grow-1 min-w-0">{{ __('whatsapp_ai.business_config_intro') }}</p>
                            <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => 'business_config', 'edit' => 1]) }}" class="btn btn-sm btn--primary ms-auto shrink-0">{{ __('whatsapp_ai.edit_configuration') }}</a>
                        </div>
                        @include('whatsappmodule::admin.partials.business-config-readonly')
                    @endif
                @else
                    <p class="text-muted small mb-3">{{ __('whatsapp_ai.business_config_intro') }}</p>
                    @include('whatsappmodule::admin.partials.business-config-readonly')
                    <p class="text-muted">{{ __('whatsapp_ai.assign_permission_edit') }}</p>
                @endcan
            @endif

            @if($tab === 'message_config')
                <p class="text-muted small mb-3">{{ __('whatsapp_ai.message_config_intro') }}</p>
                @can('whatsapp_chat_assign')
                    <form action="{{ route('admin.whatsapp.ai-settings.update', ['channel' => $siInboxCh]) }}" method="post" class="card border-0 shadow-sm mb-3">
                        @csrf
                        <input type="hidden" name="return_tab" value="message_config">
                        <input type="hidden" name="save_message_config" value="1">
                        <div class="card-header bg-body border-bottom py-3">
                            <strong>{{ __('whatsapp_ai.message_config_form_title') }}</strong>
                        </div>
                        <div class="card-body">
                            @include('whatsappmodule::admin.partials.ai-message-config', [
                                'settings' => $settings,
                                'greetingButtonRows' => $greetingButtonRows,
                                'handoffInButtonRows' => $handoffInButtonRows,
                                'handoffOutButtonRows' => $handoffOutButtonRows,
                                'nonTextButtonRows' => $nonTextButtonRows,
                                'customerMessageDefaults' => $customerMessageDefaults,
                                'messageConfigEditorDefaults' => $messageConfigEditorDefaults,
                                'placeholderResolved' => $placeholderResolved,
                                'runtime' => $runtime,
                            ])
                        </div>
                        <div class="card-footer bg-body border-top">
                            <button type="submit" class="btn btn--primary">{{ __('whatsapp_ai.save_message_config') }}</button>
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
                            <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => 'executions']) }}" class="btn btn-sm btn-outline-secondary">{{ __('whatsapp_ai.back_to_list') }}</a>
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
                                                    <a href="{{ route('admin.whatsapp.ai-settings.edit', ['channel' => $siInboxCh, 'tab' => 'executions', 'id' => $ex->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('whatsapp_ai.view_steps') }}</a>
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

            <div class="modal fade" id="waAiToolInfoModal" tabindex="-1" aria-labelledby="waAiToolInfoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title font-monospace small mb-0" id="waAiToolInfoModalLabel">{{ __('whatsapp_ai.tool_info_modal_title') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-1">{{ __('whatsapp_ai.tool_info_built_in') }}</p>
                            <pre class="wa-ai-pre mb-3" style="max-height: 220px;" data-wa-ai-field="default"></pre>
                            <div data-wa-ai-field="override-wrap" class="d-none">
                                <p class="small text-muted mb-1">{{ __('whatsapp_ai.tool_info_override') }}</p>
                                <pre class="wa-ai-pre mb-3" style="max-height: 160px;" data-wa-ai-field="override"></pre>
                            </div>
                            <p class="small text-muted mb-1">{{ __('whatsapp_ai.tool_info_effective') }}</p>
                            <pre class="wa-ai-pre mb-0" style="max-height: 220px;" data-wa-ai-field="effective"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var modal = document.getElementById('waAiToolInfoModal');
            if (!modal) return;
            modal.addEventListener('show.bs.modal', function (event) {
                var btn = event.relatedTarget;
                if (!btn || !btn.getAttribute('data-wa-ai-payload')) return;
                var p;
                try {
                    p = JSON.parse(btn.getAttribute('data-wa-ai-payload'));
                } catch (e) {
                    return;
                }
                var titleEl = document.getElementById('waAiToolInfoModalLabel');
                if (titleEl) titleEl.textContent = p.name || '';
                var defPre = modal.querySelector('[data-wa-ai-field="default"]');
                var ovWrap = modal.querySelector('[data-wa-ai-field="override-wrap"]');
                var ovPre = modal.querySelector('[data-wa-ai-field="override"]');
                var effPre = modal.querySelector('[data-wa-ai-field="effective"]');
                if (defPre) defPre.textContent = p.default || '';
                var hasOv = p.override && String(p.override).trim() !== '';
                if (ovWrap) ovWrap.classList.toggle('d-none', !hasOv);
                if (ovPre) ovPre.textContent = hasOv ? p.override : '';
                var eff = hasOv ? p.override : (p.default || '');
                if (effPre) effPre.textContent = eff;
            });
        })();
    </script>
@endpush

@if($tab === 'message_config')
    @push('script')
        <script>
            (function () {
                'use strict';
                var dataEl = document.getElementById('wa-ai-msg-preview-data');
                if (!dataEl) return;
                var P;
                try {
                    P = JSON.parse(dataEl.textContent || '{}');
                } catch (e) {
                    return;
                }

                function esc(s) {
                    var d = document.createElement('div');
                    d.textContent = s;
                    return d.innerHTML;
                }

                function resolve(t) {
                    if (t === undefined || t === null) return '';
                    var p = P.placeholders || {};
                    var demoName = (P.previewCustomerNameDemo || 'Alex').trim();
                    return String(t)
                        .replace(/\{schedule\}/g, p.schedule || '')
                        .replace(/\{phone\}/g, p.phone || '')
                        .replace(/\{brand\}/g, p.brand || '')
                        .replace(/\{email\}/g, p.email || '')
                        .replace(/\{website\}/g, p.website || '')
                        .replace(/\{address\}/g, p.address || '')
                        .replace(/\{tagline\}/g, p.tagline || '')
                        .replace(/\{provider_onboarding\}/g, p.provider_onboarding || '')
                        .replace(/\{customer_name\}/g, demoName)
                        .replace(/\{customer_name_lead_in\}/g, demoName ? ', ' + demoName : '');
                }

                function greetingEnabled() {
                    var sel = document.getElementById('db_greeting_buttons');
                    if (!sel) return !!P.greetingButtonsRuntime;
                    var v = sel.value;
                    if (v === '1') return true;
                    if (v === '0') return false;
                    return !!P.greetingButtonsRuntime;
                }

                function waAiTplUpdateButtonRowVisibility(row) {
                    var kind = row.querySelector('.wa-ai-tpl-btn-kind');
                    var k = kind ? kind.value : '';
                    row.querySelectorAll('.wa-ai-tpl-url-wrap').forEach(function (w) {
                        w.classList.toggle('d-none', k !== 'URL');
                    });
                    row.querySelectorAll('.wa-ai-tpl-phone-wrap').forEach(function (w) {
                        w.classList.toggle('d-none', k !== 'PHONE_NUMBER');
                    });
                }

                function waAiTplSyncAddButtonState(wrap) {
                    var scroll = wrap.querySelector('.wa-ai-tpl-buttons-scroll');
                    var addBtn = wrap.querySelector('.wa-ai-tpl-add-btn');
                    if (!scroll || !addBtn) {
                        return;
                    }
                    var max = parseInt(wrap.getAttribute('data-wa-max') || '10', 10);
                    var n = scroll.querySelectorAll(':scope > .wa-ai-tpl-button-row').length;
                    addBtn.disabled = n >= max;
                }

                function waAiTplReindex(scrollEl) {
                    var wrap = scrollEl.closest('.wa-ai-tpl-buttons-wrap');
                    if (!wrap) {
                        return;
                    }
                    var prefix = wrap.getAttribute('data-wa-prefix') || '';
                    var fmt = wrap.getAttribute('data-slot-label-fmt') || '';
                    var rows = scrollEl.querySelectorAll(':scope > .wa-ai-tpl-button-row');
                    rows.forEach(function (row, i) {
                        row.setAttribute('data-i', String(i));
                        var lab = row.querySelector('.wa-ai-tpl-slot-label');
                        if (lab && fmt) {
                            lab.textContent = fmt.replace(/\{n\}/g, String(i + 1));
                        }
                        var k = row.querySelector('.wa-ai-tpl-btn-kind');
                        var t = row.querySelector('.wa-ai-tpl-btn-text');
                        var u = row.querySelector('.wa-ai-tpl-btn-url');
                        var p = row.querySelector('.wa-ai-tpl-btn-phone');
                        if (k) {
                            k.name = prefix + '[' + i + '][kind]';
                        }
                        if (t) {
                            t.name = prefix + '[' + i + '][text]';
                        }
                        if (u) {
                            u.name = prefix + '[' + i + '][url]';
                        }
                        if (p) {
                            p.name = prefix + '[' + i + '][phone]';
                        }
                    });
                    waAiTplSyncAddButtonState(wrap);
                }

                function collectTemplateButtons(wrapId) {
                    var wrap = document.getElementById(wrapId);
                    if (!wrap) return [];
                    var out = [];
                    wrap.querySelectorAll('.wa-ai-tpl-button-row').forEach(function (row) {
                        var kindEl = row.querySelector('.wa-ai-tpl-btn-kind');
                        var textEl = row.querySelector('.wa-ai-tpl-btn-text');
                        var kind = kindEl ? kindEl.value : '';
                        var text = textEl ? String(textEl.value || '').trim() : '';
                        if (!kind || !text) return;
                        out.push({
                            kind: kind,
                            text: text.substring(0, 25),
                            row: row
                        });
                    });
                    return out;
                }

                function templateButtonsHtml(items) {
                    if (!items.length) return '';
                    var html = '<div class="d-grid gap-2 mt-3">';
                    items.forEach(function (item) {
                        if (item.kind === 'QUICK_REPLY') {
                            html += '<span class="btn btn-sm btn-outline-secondary text-start rounded-pill wa-ai-msg-btn-fake">' + esc(item.text) + '</span>';
                        } else if (item.kind === 'URL') {
                            var u = item.row.querySelector('.wa-ai-tpl-btn-url');
                            var uv = u ? String(u.value || '').trim() : '';
                            html += '<span class="btn btn-sm btn-outline-primary text-truncate rounded-pill wa-ai-msg-btn-fake">' + esc(item.text) + '</span>';
                            if (uv) {
                                html += '<span class="text-muted d-block" style="font-size:0.65rem;word-break:break-all;">' + esc(uv) + '</span>';
                            }
                        } else if (item.kind === 'PHONE_NUMBER') {
                            var pe = item.row.querySelector('.wa-ai-tpl-btn-phone');
                            var pv = pe ? String(pe.value || '').trim() : '';
                            html += '<span class="btn btn-sm btn-outline-primary rounded-pill wa-ai-msg-btn-fake">' + esc(item.text) + '</span>';
                            if (pv) {
                                html += '<span class="text-muted d-block" style="font-size:0.65rem;">' + esc(pv) + '</span>';
                            }
                        }
                    });
                    html += '</div>';
                    return html;
                }

                function phoneShell(innerBodyHtml) {
                    var L = P.labels || {};
                    return (
                        '<div class="wa-ai-phone-preview mx-auto">' +
                        '<div class="wa-ai-phone-frame rounded-4 overflow-hidden shadow-sm">' +
                        '<div class="wa-ai-phone-notch d-flex align-items-center justify-content-between px-3 py-2">' +
                        '<span class="small fw-semibold text-white-50">' + esc(L.preview || '') + '</span>' +
                        '<span class="small text-white-50">' + esc(L.whatsapp || '') + '</span>' +
                        '</div>' +
                        '<div class="wa-ai-phone-body p-3">' + innerBodyHtml + '</div>' +
                        '</div></div>'
                    );
                }

                function renderGreeting() {
                    var el = document.getElementById('wa_ai_preview_mount_greeting');
                    if (!el) return;
                    var L = P.labels || {};
                    var ta = document.getElementById('db_greeting_message');
                    var raw = ta && ta.value ? String(ta.value).trim() : '';
                    var body = raw ? resolve(raw) : resolve(P.defaults.greeting_body || '');
                    var bubble = '<div class="rounded-3 p-3 bg-white shadow-sm border border-light">';
                    bubble += '<div class="small text-break" style="white-space:pre-wrap;">' + esc(body) + '</div>';
                    if (greetingEnabled()) {
                        var items = collectTemplateButtons('wa_ai_btn_rows_greeting');
                        bubble += templateButtonsHtml(items);
                    } else {
                        bubble += '<p class="small text-muted mt-2 mb-0">' + esc(L.buttonsOff || '') + '</p>';
                    }
                    bubble += '</div>';
                    el.innerHTML = phoneShell(bubble);
                }

                function renderTextWithTemplateButtons(mountId, textareaId, defaultKey, wrapId) {
                    var el = document.getElementById(mountId);
                    if (!el) return;
                    var ta = document.getElementById(textareaId);
                    var raw = ta && ta.value ? String(ta.value).trim() : '';
                    var body = raw ? resolve(raw) : resolve((P.defaults && P.defaults[defaultKey]) ? P.defaults[defaultKey] : '');
                    var inner = '<div class="rounded-3 p-3 bg-white shadow-sm border border-light">';
                    inner += '<div class="small text-break" style="white-space:pre-wrap;">' + esc(body) + '</div>';
                    inner += templateButtonsHtml(collectTemplateButtons(wrapId));
                    inner += '</div>';
                    el.innerHTML = phoneShell(inner);
                }

                function renderAll() {
                    renderGreeting();
                    renderTextWithTemplateButtons('wa_ai_preview_mount_handoff_in', 'handoff_message_in_hours', 'handoff_in', 'wa_ai_btn_rows_handoff_in');
                    renderTextWithTemplateButtons('wa_ai_preview_mount_handoff_out', 'handoff_message_out_hours', 'handoff_out', 'wa_ai_btn_rows_handoff_out');
                    renderTextWithTemplateButtons('wa_ai_preview_mount_non_text', 'db_non_text_inbound_message', 'non_text', 'wa_ai_btn_rows_non_text');
                }

                document.addEventListener('click', function (e) {
                    if (!e.target || !e.target.closest) {
                        return;
                    }
                    var cfg = e.target.closest('.wa-ai-msg-config');
                    if (!cfg) {
                        return;
                    }
                    var addBtn = e.target.closest('.wa-ai-tpl-add-btn');
                    if (addBtn) {
                        e.preventDefault();
                        var wrap = addBtn.closest('.wa-ai-tpl-buttons-wrap');
                        if (!wrap || addBtn.disabled) {
                            return;
                        }
                        var scroll = wrap.querySelector('.wa-ai-tpl-buttons-scroll');
                        var wid = wrap.getAttribute('data-wa-wrap-id');
                        var tpl = wid ? document.getElementById(wid + '_row_template') : null;
                        if (!scroll || !tpl) {
                            return;
                        }
                        var prefix = wrap.getAttribute('data-wa-prefix') || '';
                        var max = parseInt(wrap.getAttribute('data-wa-max') || '10', 10);
                        var n = scroll.querySelectorAll(':scope > .wa-ai-tpl-button-row').length;
                        if (n >= max) {
                            return;
                        }
                        var html = tpl.innerHTML.replace(/__PREFIX__/g, prefix).replace(/__INDEX__/g, String(n));
                        var div = document.createElement('div');
                        div.innerHTML = html.trim();
                        var row = div.firstElementChild;
                        if (!row) {
                            return;
                        }
                        scroll.appendChild(row);
                        var fmt = wrap.getAttribute('data-slot-label-fmt') || '';
                        var lab = row.querySelector('.wa-ai-tpl-slot-label');
                        if (lab && fmt) {
                            lab.textContent = fmt.replace(/\{n\}/g, String(n + 1));
                        }
                        waAiTplUpdateButtonRowVisibility(row);
                        waAiTplSyncAddButtonState(wrap);
                        renderAll();
                        return;
                    }
                    var removeBtn = e.target.closest('.wa-ai-tpl-btn-remove');
                    if (removeBtn) {
                        e.preventDefault();
                        var row = removeBtn.closest('.wa-ai-tpl-button-row');
                        var scroll = row && row.parentElement;
                        if (!row || !scroll || !scroll.classList.contains('wa-ai-tpl-buttons-scroll')) {
                            return;
                        }
                        row.remove();
                        waAiTplReindex(scroll);
                        renderAll();
                    }
                });

                function waAiMsgWatchChange(e) {
                    var t = e.target;
                    if (!t || !t.classList || !t.classList.contains('wa-ai-msg-watch')) {
                        return;
                    }
                    if (!t.closest('.wa-ai-msg-config')) {
                        return;
                    }
                    if (t.classList.contains('wa-ai-tpl-btn-kind')) {
                        waAiTplUpdateButtonRowVisibility(t.closest('.wa-ai-tpl-button-row'));
                    }
                    renderAll();
                }

                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('.wa-ai-tpl-button-row').forEach(waAiTplUpdateButtonRowVisibility);
                    document.querySelectorAll('.wa-ai-tpl-buttons-wrap').forEach(waAiTplSyncAddButtonState);
                    renderAll();
                    var tabNav = document.getElementById('wa-msg-tabs-nav');
                    if (tabNav) {
                        var subtabMap = {
                            '#wa-msg-pane-greeting': 'greeting',
                            '#wa-msg-pane-handoff_in': 'handoff_in',
                            '#wa-msg-pane-handoff_out': 'handoff_out',
                            '#wa-msg-pane-non_text': 'non_text'
                        };
                        tabNav.addEventListener('shown.bs.tab', function (e) {
                            var target = e.target && e.target.getAttribute('data-bs-target');
                            var hid = document.getElementById('wa_msg_config_subtab');
                            if (hid && target && subtabMap[target]) {
                                hid.value = subtabMap[target];
                            }
                            renderAll();
                        });
                    }
                    document.addEventListener('input', function (e) {
                        waAiMsgWatchChange(e);
                    });
                    document.addEventListener('change', function (e) {
                        waAiMsgWatchChange(e);
                    });
                });
            })();
        </script>
    @endpush
@endif

@if($tab === 'flow')
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>
        <script>
            (function () {
                var src = @json($liveFlowMermaid);
                var el = document.getElementById('wa-ai-flow-render');
                if (!el || typeof mermaid === 'undefined') return;
                mermaid.initialize({
                    startOnLoad: false,
                    securityLevel: 'strict',
                    theme: 'base',
                    themeVariables: {
                        fontFamily: 'system-ui, -apple-system, Segoe UI, sans-serif',
                        fontSize: '13px',
                        primaryColor: '#e8f4fc',
                        primaryTextColor: '#0c4a6e',
                        primaryBorderColor: '#7dd3fc',
                        lineColor: '#94a3b8',
                        secondaryColor: '#f8fafc',
                        tertiaryColor: '#ffffff',
                        edgeLabelBackground: '#f1f5f9'
                    },
                    flowchart: { htmlLabels: false, curve: 'basis', padding: 20, useMaxWidth: true }
                });
                var fail = '<p class="text-danger small mb-0">{{ e(__('whatsapp_ai.mermaid_render_failed')) }}</p>';
                var id = 'wa-ai-flow-' + Date.now();
                try {
                    mermaid.render(id, src).then(function (out) {
                        el.innerHTML = out.svg;
                    }).catch(function () {
                        el.innerHTML = fail;
                    });
                } catch (e) {
                    el.innerHTML = fail;
                }
            })();
        </script>
    @endpush
@endif
