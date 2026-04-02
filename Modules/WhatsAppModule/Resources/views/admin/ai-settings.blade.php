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
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{ __('whatsapp_ai.page_title') }}</h2>
                    <p class="text-muted mb-0 fs-12">{{ __('whatsapp_ai.intro') }}</p>
                    <p class="text-muted mb-0 fs-12 mt-1">{{ __('whatsapp_ai.intro_control') }}</p>
                </div>
            </div>

            @php
                $waAiTabs = [
                    ['id' => 'status', 'label' => __('whatsapp_ai.status')],
                    ['id' => 'flow', 'label' => __('whatsapp_ai.visual_flow')],
                    ['id' => 'access', 'label' => __('whatsapp_ai.access')],
                    ['id' => 'tools', 'label' => __('whatsapp_ai.tools')],
                    ['id' => 'prompt', 'label' => __('whatsapp_ai.prompts')],
                    ['id' => 'executions', 'label' => __('whatsapp_ai.executions')],
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

            @if($tab === 'status')
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-body border-bottom py-3">
                        <strong>{{ __('whatsapp_ai.runtime') }}</strong>
                        <span class="text-muted small fw-normal">(.env / config)</span>
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
                                            <span class="text-muted small ms-2">GEMINI_API_KEY</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.model') }}</th>
                                        <td><code class="user-select-all">{{ $runtime['gemini_model'] }}</code> <span class="text-muted small">WHATSAPP_GEMINI_MODEL</span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.greeting_buttons') }}</th>
                                        <td>{{ $runtime['greeting_buttons'] ? __('whatsapp_ai.yes') : __('whatsapp_ai.no') }} <span class="text-muted small">WHATSAPP_AI_GREETING_BUTTONS</span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.support_hours') }}</th>
                                        <td>{{ $runtime['support_hours'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.public_phone') }}</th>
                                        <td>{{ $runtime['support_phone_display'] !== '' ? $runtime['support_phone_display'] : '—' }} <span class="text-muted small">WHATSAPP_SUPPORT_PHONE_DISPLAY</span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('whatsapp_ai.queue') }}</th>
                                        <td><code>{{ $runtime['queue_connection'] }}</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
