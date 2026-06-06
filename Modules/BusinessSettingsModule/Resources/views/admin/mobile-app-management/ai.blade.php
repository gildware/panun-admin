@extends('adminmodule::layouts.new-master')

@section('title', translate('AI'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-20">
                <div>
                    <h2 class="page-title mb-1">{{ translate('AI') }}</h2>
                    <p class="fz-12 text-muted mb-0">{{ translate('Configure_in_app_AI_support_for_customers') }}</p>
                </div>
            </div>

            <ul class="nav nav--tabs nav--tabs__style2 flex-wrap gap-2 mb-3">
                @foreach($tabs as $t)
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === $t['id'] ? 'active' : '' }}"
                           href="{{ route('admin.mobile-app-management.ai', ['tab' => $t['id']]) }}">
                            {{ $t['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            @if($tab === 'ai_config')
                <form action="{{ route('admin.mobile-app-management.ai-config.update') }}" method="POST">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-check form-switch">
                                        <input type="hidden" name="is_enabled" value="0">
                                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1"
                                               {{ $settings->is_enabled ? 'checked' : '' }}>
                                        <span class="form-check-label fw-semibold">{{ translate('Enable_mobile_AI_chat') }}</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-check form-switch">
                                        <input type="hidden" name="inherit_whatsapp_ai" value="0">
                                        <input class="form-check-input" type="checkbox" name="inherit_whatsapp_ai" value="1"
                                               {{ $settings->inherit_whatsapp_ai ? 'checked' : '' }}>
                                        <span class="form-check-label fw-semibold">{{ translate('Use_WhatsApp_AI_prompt_layers') }}</span>
                                    </label>
                                    <div class="form-text">{{ translate('Mobile_AI_inherits_prompt_catalog_cart_tools_filtered') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-check form-switch">
                                        <input type="hidden" name="use_full_custom_prompt" value="0">
                                        <input class="form-check-input" type="checkbox" name="use_full_custom_prompt" value="1"
                                               {{ $settings->use_full_custom_prompt ? 'checked' : '' }}>
                                        <span class="form-check-label fw-semibold">{{ translate('Use_full_custom_system_prompt') }}</span>
                                    </label>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ translate('Gemini_model') }}</label>
                                    <input type="text" name="gemini_model" class="form-control"
                                           value="{{ old('gemini_model', $settings->gemini_model) }}"
                                           placeholder="gemini-2.5-flash">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ translate('Max_history_messages') }}</label>
                                    <input type="number" name="max_history_messages" class="form-control" min="6" max="60"
                                           value="{{ old('max_history_messages', $settings->max_history_messages) }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ translate('Assistant_persona') }}</label>
                                <textarea name="assistant_persona" class="form-control font-monospace" rows="5"
                                          placeholder="{{ translate('Tone_and_behaviour_for_mobile_app') }}">{{ old('assistant_persona', $settings->assistant_persona) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ translate('Prompt_addendum') }}</label>
                                <textarea name="prompt_addendum" class="form-control font-monospace" rows="4">{{ old('prompt_addendum', $settings->prompt_addendum) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ translate('Full_custom_system_prompt') }}</label>
                                <textarea name="custom_system_prompt" class="form-control font-monospace" rows="10">{{ old('custom_system_prompt', $settings->custom_system_prompt) }}</textarea>
                                <div class="form-text">{{ translate('Only_used_when_full_custom_prompt_is_enabled') }}</div>
                            </div>

                            <details class="mb-3">
                                <summary class="fw-semibold cursor-pointer">{{ translate('Preview_resolved_prompt') }}</summary>
                                <pre class="mt-2" style="white-space:pre-wrap;font-size:12px;max-height:320px;overflow:auto;background:#f8f9fa;padding:12px;border-radius:6px;">{{ $resolvedPromptPreview }}</pre>
                            </details>

                            <button type="submit" class="btn btn-primary">{{ translate('save') }}</button>
                        </div>
                    </div>
                </form>
            @else
                <p class="text-muted fz-12 mb-3">{{ translate('Mobile_app_AI_chats_only_hint') }}</p>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header"><strong>{{ translate('In_app_AI_chats') }}</strong></div>
                            <div class="card-body p-0" style="max-height:70vh;overflow:auto;">
                                <ul class="list-group list-group-flush">
                                    @forelse($conversations as $conv)
                                        @php
                                            $u = $conv->user;
                                            $label = trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->email ?? $u->phone ?? $conv->user_id);
                                            $preview = $conv->appMessages->first();
                                        @endphp
                                        <a href="{{ route('admin.mobile-app-management.ai', ['tab' => 'ai_chat', 'conversation_id' => $conv->id]) }}"
                                           class="list-group-item list-group-item-action {{ ($selectedConversation?->id ?? null) == $conv->id ? 'active' : '' }}">
                                            <div class="fw-semibold">{{ $label }}</div>
                                            <div class="fz-12 d-block opacity-75">{{ $u->phone ?? '' }}</div>
                                            @if($preview)
                                                <div class="fz-12 text-truncate d-block mt-1 opacity-75">{{ \Illuminate\Support\Str::limit($preview->body, 80) }}</div>
                                            @endif
                                            <div class="fz-11 text-muted mt-1">
                                                {{ $conv->customer_message_count }} {{ translate('messages') }}
                                                · {{ $conv->last_message_at?->diffForHumans() }}
                                            </div>
                                        </a>
                                    @empty
                                        <li class="list-group-item text-muted">{{ translate('No_in_app_AI_chats_yet') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                            @if($conversations && $conversations->hasPages())
                                <div class="card-footer">{{ $conversations->links() }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <strong>{{ translate('In_app_AI_conversation') }}</strong>
                            </div>
                            <div class="card-body" style="max-height:70vh;overflow:auto;">
                                @if($selectedConversation && $messages)
                                    @php $u = $selectedConversation->user; @endphp
                                    <p class="text-muted fz-12 mb-3">
                                        {{ trim(($u->first_name ?? '').' '.($u->last_name ?? '')) }}
                                        · {{ $u->email ?? '' }} · {{ $u->phone ?? '' }}
                                    </p>
                                    @foreach($messages as $msg)
                                        <div class="mb-3 {{ $msg->role === 'user' ? 'text-end' : '' }}">
                                            <span class="badge {{ $msg->role === 'user' ? 'bg-primary' : 'bg-secondary' }} mb-1">
                                                {{ $msg->role === 'user' ? translate('Customer') : translate('AI') }}
                                            </span>
                                            <div class="p-2 rounded {{ $msg->role === 'user' ? 'bg-primary-subtle' : 'bg-light' }}"
                                                 style="display:inline-block;max-width:95%;text-align:left;">
                                                {!! nl2br(e($msg->body)) !!}
                                            </div>
                                            <div class="fz-11 text-muted d-block">{{ $msg->created_at }}</div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted mb-0">{{ translate('Select_a_conversation') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
