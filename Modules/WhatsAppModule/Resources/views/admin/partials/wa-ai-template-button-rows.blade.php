{{--
  Dynamic button rows (max 10): start empty; "Add button" appends a row; each row can be removed.
  @var string $prefix  e.g. greeting_button_rows
  @var array<int, array{kind: string, text: string, url: string, phone: string}> $rows
  @var string $wrapId   id on the scroll container (live preview targets this)
--}}
@php
    $wrapId = $wrapId ?? ('wa_ai_btn_rows_'.preg_replace('/[^a-z0-9_]/i', '_', (string) $prefix));
    $displayRows = isset($rows) && is_array($rows) ? $rows : [];
    $oldRows = old($prefix);
    if (is_array($oldRows) && $oldRows !== []) {
        $displayRows = [];
        foreach (array_values($oldRows) as $r) {
            if (!is_array($r)) {
                continue;
            }
            $displayRows[] = [
                'kind' => (string) ($r['kind'] ?? ''),
                'text' => (string) ($r['text'] ?? ''),
                'url' => (string) ($r['url'] ?? ''),
                'phone' => (string) ($r['phone'] ?? ''),
            ];
        }
    }
@endphp
<div class="wa-ai-tpl-buttons-wrap"
     data-wa-prefix="{{ $prefix }}"
     data-wa-wrap-id="{{ $wrapId }}"
     data-wa-max="10"
     data-slot-label-fmt="{{ e(__('whatsapp_ai.generic_button_n', ['num' => '{n}'])) }}">
    <div class="wa-ai-tpl-buttons-scroll border rounded-3 px-2 py-2 mb-2" id="{{ $wrapId }}" style="min-height: 2rem;">
        @foreach($displayRows as $i => $r)
            @php
                $ok = $r['kind'] ?? '';
                $ot = $r['text'] ?? '';
                $ou = $r['url'] ?? '';
                $op = $r['phone'] ?? '';
            @endphp
            <div class="border rounded-3 p-3 wa-ai-tpl-button-row mb-2" data-i="{{ $i }}">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <span class="small text-muted fw-medium wa-ai-tpl-slot-label">{{ __('whatsapp_ai.generic_button_n', ['num' => $i + 1]) }}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger wa-ai-tpl-btn-remove"
                            title="{{ __('whatsapp_ai.msg_config_remove_button_row_title') }}">
                        {{ __('whatsapp_ai.btn_remove_row') }}
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-lg-3">
                        <label class="form-label small mb-0">{{ translate('Template_button_kind') }}</label>
                        <select name="{{ $prefix }}[{{ $i }}][kind]" class="form-select form-select-sm wa-ai-tpl-btn-kind wa-ai-msg-watch w-100">
                            <option value="">—</option>
                            <option value="QUICK_REPLY" {{ $ok === 'QUICK_REPLY' ? 'selected' : '' }}>{{ translate('Template_button_quick_reply') }}</option>
                            <option value="URL" {{ $ok === 'URL' ? 'selected' : '' }}>{{ translate('Template_button_url') }}</option>
                            <option value="PHONE_NUMBER" {{ $ok === 'PHONE_NUMBER' ? 'selected' : '' }}>{{ translate('Template_button_phone') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label small mb-0">{{ translate('Template_button_label') }}</label>
                        <input type="text" name="{{ $prefix }}[{{ $i }}][text]" maxlength="25"
                               class="form-control form-control-sm wa-ai-tpl-btn-text wa-ai-msg-watch w-100"
                               value="{{ $ot }}" placeholder="{{ translate('action') }}">
                    </div>
                    <div class="col-12 col-lg-6 wa-ai-tpl-url-wrap d-none">
                        <label class="form-label small mb-0">URL (https://…)</label>
                        <input type="text" name="{{ $prefix }}[{{ $i }}][url]" maxlength="2000"
                               class="form-control form-control-sm wa-ai-tpl-btn-url wa-ai-msg-watch w-100"
                               value="{{ $ou }}" placeholder="https://example.com/@{{1}}">
                    </div>
                    <div class="col-12 col-lg-6 wa-ai-tpl-phone-wrap d-none">
                        <label class="form-label small mb-0">{{ translate('phone') }} (E.164)</label>
                        <input type="text" name="{{ $prefix }}[{{ $i }}][phone]" maxlength="24"
                               class="form-control form-control-sm wa-ai-tpl-btn-phone wa-ai-msg-watch w-100"
                               value="{{ $op }}" placeholder="+923001234567">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary wa-ai-tpl-add-btn mb-2">
        {{ __('whatsapp_ai.msg_config_add_button') }}
    </button>
    <template id="{{ $wrapId }}_row_template">
        <div class="border rounded-3 p-3 wa-ai-tpl-button-row mb-2" data-i="__INDEX__">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <span class="small text-muted fw-medium wa-ai-tpl-slot-label"></span>
                <button type="button" class="btn btn-sm btn-outline-danger wa-ai-tpl-btn-remove"
                        title="{{ __('whatsapp_ai.msg_config_remove_button_row_title') }}">
                    {{ __('whatsapp_ai.btn_remove_row') }}
                </button>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-3">
                    <label class="form-label small mb-0">{{ translate('Template_button_kind') }}</label>
                    <select name="__PREFIX__[__INDEX__][kind]" class="form-select form-select-sm wa-ai-tpl-btn-kind wa-ai-msg-watch w-100">
                        <option value="">—</option>
                        <option value="QUICK_REPLY">{{ translate('Template_button_quick_reply') }}</option>
                        <option value="URL">{{ translate('Template_button_url') }}</option>
                        <option value="PHONE_NUMBER">{{ translate('Template_button_phone') }}</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label small mb-0">{{ translate('Template_button_label') }}</label>
                    <input type="text" name="__PREFIX__[__INDEX__][text]" maxlength="25"
                           class="form-control form-control-sm wa-ai-tpl-btn-text wa-ai-msg-watch w-100"
                           value="" placeholder="{{ translate('action') }}">
                </div>
                <div class="col-12 col-lg-6 wa-ai-tpl-url-wrap d-none">
                    <label class="form-label small mb-0">URL (https://…)</label>
                    <input type="text" name="__PREFIX__[__INDEX__][url]" maxlength="2000"
                           class="form-control form-control-sm wa-ai-tpl-btn-url wa-ai-msg-watch w-100"
                           value="" placeholder="https://example.com/@{{1}}">
                </div>
                <div class="col-12 col-lg-6 wa-ai-tpl-phone-wrap d-none">
                    <label class="form-label small mb-0">{{ translate('phone') }} (E.164)</label>
                    <input type="text" name="__PREFIX__[__INDEX__][phone]" maxlength="24"
                           class="form-control form-control-sm wa-ai-tpl-btn-phone wa-ai-msg-watch w-100"
                           value="" placeholder="+923001234567">
                </div>
            </div>
        </div>
    </template>
</div>
