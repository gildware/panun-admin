{{--
  Message configuration: editors + WhatsApp-style live previews (tab: message_config).
  Expects: $settings, $greetingButtonRows, $handoffInButtonRows, $handoffOutButtonRows, $nonTextButtonRows,
  $customerMessageDefaults, $messageConfigEditorDefaults, $placeholderResolved, $runtime
--}}
@php
    $ed = $messageConfigEditorDefaults ?? [];
    $vg = old('db_greeting_buttons', $settings->db_greeting_buttons === null ? '' : ((int) (bool) $settings->db_greeting_buttons));
    $oGreet = old('db_greeting_message');
    if ($oGreet !== null) {
        $greetingEditor = (string) $oGreet;
    } else {
        $greetingEditor = trim((string) ($settings->db_greeting_message ?? '')) !== ''
            ? (string) $settings->db_greeting_message
            : (string) ($ed['db_greeting_message'] ?? '');
    }
    $oIn = old('handoff_message_in_hours');
    if ($oIn !== null) {
        $handoffInEditor = (string) $oIn;
    } else {
        $handoffInEditor = trim((string) ($settings->handoff_message_in_hours ?? '')) !== ''
            ? (string) $settings->handoff_message_in_hours
            : (string) ($ed['handoff_message_in_hours'] ?? '');
    }
    $oOut = old('handoff_message_out_hours');
    if ($oOut !== null) {
        $handoffOutEditor = (string) $oOut;
    } else {
        $handoffOutEditor = trim((string) ($settings->handoff_message_out_hours ?? '')) !== ''
            ? (string) $settings->handoff_message_out_hours
            : (string) ($ed['handoff_message_out_hours'] ?? '');
    }
    $oNonText = old('db_non_text_inbound_message');
    if ($oNonText !== null) {
        $nonTextEditor = (string) $oNonText;
    } else {
        $nonTextEditor = trim((string) ($settings->db_non_text_inbound_message ?? '')) !== ''
            ? (string) $settings->db_non_text_inbound_message
            : (string) ($ed['db_non_text_inbound_message'] ?? '');
    }
    $waMsgPreviewPayload = [
        'placeholders' => $placeholderResolved,
        'defaults' => [
            'greeting_body' => $customerMessageDefaults['greeting_body'],
            'handoff_in' => $customerMessageDefaults['handoff_in'],
            'handoff_out' => $customerMessageDefaults['handoff_out'],
            'non_text' => $customerMessageDefaults['non_text'],
        ],
        'greetingButtonsRuntime' => (bool) ($runtime['greeting_buttons'] ?? true),
        'labels' => [
            'preview' => __('whatsapp_ai.preview_badge'),
            'whatsapp' => __('whatsapp_ai.preview_whatsapp_badge'),
            'buttonsOff' => __('whatsapp_ai.msg_preview_buttons_off'),
        ],
        'previewCustomerNameDemo' => 'Alex',
    ];
    $validMsgSubtabs = ['greeting', 'handoff_in', 'handoff_out', 'non_text'];
    $msgSubtab = old('msg_subtab', request('msg_subtab', 'greeting'));
    if (! in_array($msgSubtab, $validMsgSubtabs, true)) {
        $msgSubtab = 'greeting';
    }
@endphp

<div class="wa-ai-msg-config">
    <input type="hidden" name="msg_subtab" id="wa_msg_config_subtab" value="{{ $msgSubtab }}">
    <p class="small text-muted mb-3">{{ __('whatsapp_ai.msg_config_placeholders_one_liner') }}</p>

    <ul class="nav nav-tabs wa-ai-msg-nav-tabs flex-wrap gap-1 border-bottom-0 px-1 pt-1 mb-0" id="wa-msg-tabs-nav" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link @if($msgSubtab === 'greeting') active @endif" id="wa-msg-tab-greeting" data-bs-toggle="tab" data-bs-target="#wa-msg-pane-greeting" type="button" role="tab" aria-controls="wa-msg-pane-greeting" aria-selected="{{ $msgSubtab === 'greeting' ? 'true' : 'false' }}">{{ __('whatsapp_ai.msg_section_greeting') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link @if($msgSubtab === 'handoff_in') active @endif" id="wa-msg-tab-handoff-in" data-bs-toggle="tab" data-bs-target="#wa-msg-pane-handoff_in" type="button" role="tab" aria-controls="wa-msg-pane-handoff_in" aria-selected="{{ $msgSubtab === 'handoff_in' ? 'true' : 'false' }}">{{ __('whatsapp_ai.msg_section_handoff_in') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link @if($msgSubtab === 'handoff_out') active @endif" id="wa-msg-tab-handoff-out" data-bs-toggle="tab" data-bs-target="#wa-msg-pane-handoff_out" type="button" role="tab" aria-controls="wa-msg-pane-handoff_out" aria-selected="{{ $msgSubtab === 'handoff_out' ? 'true' : 'false' }}">{{ __('whatsapp_ai.msg_section_handoff_out') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link @if($msgSubtab === 'non_text') active @endif" id="wa-msg-tab-non-text" data-bs-toggle="tab" data-bs-target="#wa-msg-pane-non_text" type="button" role="tab" aria-controls="wa-msg-pane-non_text" aria-selected="{{ $msgSubtab === 'non_text' ? 'true' : 'false' }}">{{ __('whatsapp_ai.msg_section_non_text') }}</button>
        </li>
    </ul>
    <div class="tab-content border rounded-bottom bg-body p-3 p-md-4 mb-0 wa-ai-msg-tab-panels">

    {{-- Greeting --}}
    <div class="tab-pane fade @if($msgSubtab === 'greeting') show active @endif" id="wa-msg-pane-greeting" role="tabpanel" aria-labelledby="wa-msg-tab-greeting" tabindex="0">
    <div class="wa-ai-msg-section mb-0 pb-0">
        <div class="row g-4 align-items-start">
            <div class="col-lg-7 wa-ai-msg-editor">
                <div class="mb-3">
                    <label class="form-label" for="db_greeting_message">{{ __('whatsapp_ai.greeting_message_label') }}</label>
                    <textarea id="db_greeting_message" name="db_greeting_message" class="form-control font-monospace wa-ai-msg-watch" data-wa-preview="greeting" rows="6" maxlength="1024">{{ $greetingEditor }}</textarea>
                    @error('db_greeting_message')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    <div class="form-text">{{ __('whatsapp_ai.greeting_message_help') }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="db_greeting_buttons">{{ __('whatsapp_ai.greeting_buttons') }}</label>
                    <select id="db_greeting_buttons" name="db_greeting_buttons" class="form-select wa-ai-msg-watch" data-wa-preview="greeting">
                        <option value="" @selected($vg === '' || $vg === null)>{{ __('whatsapp_ai.inherit_env') }}</option>
                        <option value="1" @selected((string) $vg === '1')>{{ __('whatsapp_ai.yes') }}</option>
                        <option value="0" @selected((string) $vg === '0')>{{ __('whatsapp_ai.no') }}</option>
                    </select>
                </div>
                <div class="mb-0">
                    <span class="form-label d-block mb-2">{{ __('whatsapp_ai.greeting_buttons_custom_label') }}</span>
                    @include('whatsappmodule::admin.partials.wa-ai-template-button-rows', [
                        'prefix' => 'greeting_button_rows',
                        'rows' => $greetingButtonRows,
                        'wrapId' => 'wa_ai_btn_rows_greeting',
                    ])
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border wa-ai-live-preview-card sticky-lg-top mb-0" style="top: 1rem;">
                    <div class="card-header bg-body py-2 px-3 border-bottom">
                        <span class="small fw-semibold">{{ __('whatsapp_ai.msg_live_preview') }}</span>
                    </div>
                    <div class="card-body p-3">
                        <div id="wa_ai_preview_mount_greeting" class="wa-ai-preview-mount" aria-live="polite"></div>
                        <p class="text-muted small mt-2 mb-0">{{ __('whatsapp_ai.msg_preview_greeting_hint') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- Handoff in hours --}}
    <div class="tab-pane fade @if($msgSubtab === 'handoff_in') show active @endif" id="wa-msg-pane-handoff_in" role="tabpanel" aria-labelledby="wa-msg-tab-handoff-in" tabindex="0">
    <div class="wa-ai-msg-section mb-0 pb-0">
        <h6 class="fw-semibold mb-1">{{ __('whatsapp_ai.msg_section_handoff_in') }}</h6>
        <p class="small text-muted mb-3">{{ __('whatsapp_ai.msg_section_handoff_buttons_help') }}</p>
        <div class="row g-4 align-items-start">
            <div class="col-lg-7 wa-ai-msg-editor">
                <label class="form-label" for="handoff_message_in_hours">{{ __('whatsapp_ai.handoff_in_hours_label') }}</label>
                <textarea id="handoff_message_in_hours" name="handoff_message_in_hours" class="form-control font-monospace wa-ai-msg-watch" data-wa-preview="handoff_in" rows="8">{{ $handoffInEditor }}</textarea>
                @error('handoff_message_in_hours')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <div class="mt-3">
                    <p class="text-muted small mb-2">{{ translate('Template_buttons_hint') }}</p>
                    @include('whatsappmodule::admin.partials.wa-ai-template-button-rows', [
                        'prefix' => 'handoff_in_button_rows',
                        'rows' => $handoffInButtonRows,
                        'wrapId' => 'wa_ai_btn_rows_handoff_in',
                    ])
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border wa-ai-live-preview-card sticky-lg-top mb-0" style="top: 1rem;">
                    <div class="card-header bg-body py-2 px-3 border-bottom">
                        <span class="small fw-semibold">{{ __('whatsapp_ai.msg_live_preview') }}</span>
                    </div>
                    <div class="card-body p-3">
                        <div id="wa_ai_preview_mount_handoff_in" class="wa-ai-preview-mount" aria-live="polite"></div>
                        <p class="text-muted small mt-2 mb-0">{{ __('whatsapp_ai.msg_preview_handoff_hint') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- Handoff out of hours --}}
    <div class="tab-pane fade @if($msgSubtab === 'handoff_out') show active @endif" id="wa-msg-pane-handoff_out" role="tabpanel" aria-labelledby="wa-msg-tab-handoff-out" tabindex="0">
    <div class="wa-ai-msg-section mb-0 pb-0">
        <h6 class="fw-semibold mb-1">{{ __('whatsapp_ai.msg_section_handoff_out') }}</h6>
        <p class="small text-muted mb-3">{{ __('whatsapp_ai.msg_section_handoff_buttons_help') }}</p>
        <div class="row g-4 align-items-start">
            <div class="col-lg-7 wa-ai-msg-editor">
                <label class="form-label" for="handoff_message_out_hours">{{ __('whatsapp_ai.handoff_out_hours_label') }}</label>
                <textarea id="handoff_message_out_hours" name="handoff_message_out_hours" class="form-control font-monospace wa-ai-msg-watch" data-wa-preview="handoff_out" rows="8">{{ $handoffOutEditor }}</textarea>
                @error('handoff_message_out_hours')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <div class="mt-3">
                    <p class="text-muted small mb-2">{{ translate('Template_buttons_hint') }}</p>
                    @include('whatsappmodule::admin.partials.wa-ai-template-button-rows', [
                        'prefix' => 'handoff_out_button_rows',
                        'rows' => $handoffOutButtonRows,
                        'wrapId' => 'wa_ai_btn_rows_handoff_out',
                    ])
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border wa-ai-live-preview-card sticky-lg-top mb-0" style="top: 1rem;">
                    <div class="card-header bg-body py-2 px-3 border-bottom">
                        <span class="small fw-semibold">{{ __('whatsapp_ai.msg_live_preview') }}</span>
                    </div>
                    <div class="card-body p-3">
                        <div id="wa_ai_preview_mount_handoff_out" class="wa-ai-preview-mount" aria-live="polite"></div>
                        <p class="text-muted small mt-2 mb-0">{{ __('whatsapp_ai.msg_preview_handoff_hint') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- Non-text inbound (media / files) --}}
    <div class="tab-pane fade @if($msgSubtab === 'non_text') show active @endif" id="wa-msg-pane-non_text" role="tabpanel" aria-labelledby="wa-msg-tab-non-text" tabindex="0">
    <div class="wa-ai-msg-section mb-0 pb-0">
        <h6 class="fw-semibold mb-1">{{ __('whatsapp_ai.msg_section_non_text') }}</h6>
        <p class="small text-muted mb-3">{{ __('whatsapp_ai.msg_section_non_text_help') }}</p>
        <div class="row g-4 align-items-start">
            <div class="col-lg-7 wa-ai-msg-editor">
                <label class="form-label" for="db_non_text_inbound_message">{{ __('whatsapp_ai.non_text_inbound_message_label') }}</label>
                <textarea id="db_non_text_inbound_message" name="db_non_text_inbound_message" class="form-control font-monospace wa-ai-msg-watch" data-wa-preview="non_text" rows="8" maxlength="16000">{{ $nonTextEditor }}</textarea>
                @error('db_non_text_inbound_message')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <div class="mt-3">
                    <p class="text-muted small mb-2">{{ __('whatsapp_ai.msg_section_handoff_buttons_help') }}</p>
                    @include('whatsappmodule::admin.partials.wa-ai-template-button-rows', [
                        'prefix' => 'non_text_button_rows',
                        'rows' => $nonTextButtonRows,
                        'wrapId' => 'wa_ai_btn_rows_non_text',
                    ])
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border wa-ai-live-preview-card sticky-lg-top mb-0" style="top: 1rem;">
                    <div class="card-header bg-body py-2 px-3 border-bottom">
                        <span class="small fw-semibold">{{ __('whatsapp_ai.msg_live_preview') }}</span>
                    </div>
                    <div class="card-body p-3">
                        <div id="wa_ai_preview_mount_non_text" class="wa-ai-preview-mount" aria-live="polite"></div>
                        <p class="text-muted small mt-2 mb-0">{{ __('whatsapp_ai.msg_preview_non_text_hint') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    </div>
</div>

<script type="application/json" id="wa-ai-msg-preview-data">@json($waMsgPreviewPayload)</script>
