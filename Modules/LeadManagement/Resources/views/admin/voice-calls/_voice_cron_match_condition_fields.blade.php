@php
    $fieldPrefix = $fieldPrefix ?? '';
    $section = $section ?? 'include';
    $handledById = $handledById ?? 'voice-cron-handled-by';
    $employeeWrapId = $employeeWrapId ?? 'voice-cron-handled-by-employees-wrap';
    $employeeSelectId = $employeeSelectId ?? 'voice-cron-handled-by-employee-ids';
    $isInclude = $section === 'include';
@endphp

<div class="row g-3">
    <div class="col-12">
        @include('leadmanagement::admin.voice-calls._form_field_label', [
            'label' => translate('Lead_type'),
            'hint' => $isInclude
                ? translate('Voice_field_hint_cron_include_lead_type')
                : translate('Voice_field_hint_cron_exclude_lead_type'),
        ])
        <select class="form-select js-select voice-cron-lead-types-select"
                name="{{ $fieldPrefix }}lead_types[]"
                data-section="{{ $section }}"
                data-placeholder="{{ translate('Select') }}"
                multiple>
            @foreach(\Modules\LeadManagement\Entities\Lead::leadTypes() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 voice-cron-lead-status-subfilters" data-section="{{ $section }}">
        <div id="voice-cron-{{ $section }}-customer-status-wrap" class="d-none mb-3">
            @include('leadmanagement::admin.voice-calls._form_field_label', [
                'label' => translate('Customer_Lead_Status'),
                'hint' => $isInclude
                    ? translate('Voice_field_hint_cron_include_customer_lead_status')
                    : translate('Voice_field_hint_cron_exclude_customer_lead_status'),
            ])
            <select class="form-select js-select"
                    name="{{ $fieldPrefix }}customer_lead_status_ids[]"
                    id="voice-cron-{{ $section }}-customer-lead-status-ids"
                    data-placeholder="{{ translate('Select') }}"
                    multiple>
                @foreach(($customerLeadStatuses ?? []) as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                @endforeach
            </select>
        </div>
        <div id="voice-cron-{{ $section }}-provider-status-wrap" class="d-none mb-3">
            @include('leadmanagement::admin.voice-calls._form_field_label', [
                'label' => translate('Provider_Lead_Status'),
                'hint' => $isInclude
                    ? translate('Voice_field_hint_cron_include_provider_lead_status')
                    : translate('Voice_field_hint_cron_exclude_provider_lead_status'),
            ])
            <select class="form-select js-select"
                    name="{{ $fieldPrefix }}provider_lead_status_ids[]"
                    id="voice-cron-{{ $section }}-provider-lead-status-ids"
                    data-placeholder="{{ translate('Select') }}"
                    multiple>
                @foreach(($providerLeadStatuses ?? []) as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                @endforeach
            </select>
        </div>
        <p id="voice-cron-{{ $section }}-unknown-status-hint"
           class="text-muted small mb-3 d-none">{{ translate('Voice_cron_unknown_lead_status_hint') }}</p>
    </div>
    <div class="col-12">
        @include('leadmanagement::admin.voice-calls._form_field_label', [
            'label' => translate('Voice_cron_wa_ai_flow_label'),
            'hint' => $isInclude
                ? translate('Voice_field_hint_cron_include_wa_ai_flow')
                : translate('Voice_field_hint_cron_exclude_wa_ai_flow'),
        ])
        <select class="form-select js-select voice-cron-wa-ai-flows-select"
                name="{{ $isInclude ? 'wa_ai_flows[]' : 'exclude_wa_ai_flows[]' }}"
                data-section="{{ $section }}"
                data-placeholder="{{ translate('Select') }}"
                multiple>
            @foreach(\Modules\LeadManagement\Support\VoiceCronWaAiFlow::options() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        @include('leadmanagement::admin.voice-calls._form_field_label', [
            'label' => translate('Voice_cron_wa_chat_tags_label'),
            'hint' => $isInclude
                ? translate('Voice_field_hint_cron_include_wa_tags')
                : translate('Voice_field_hint_cron_exclude_wa_tags'),
        ])
        <select class="form-select js-select"
                name="{{ $fieldPrefix }}wa_chat_tag_ids[]"
                data-placeholder="{{ translate('Select') }}"
                multiple>
            @foreach(($waChatTags ?? []) as $tag)
                <option value="{{ $tag['id'] }}">{{ $tag['name'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        @include('leadmanagement::admin.voice-calls._form_field_label', [
            'label' => translate('Lead') . ' ' . translate('Status'),
            'hint' => $isInclude
                ? translate('Voice_field_hint_cron_include_lead_open')
                : translate('Voice_field_hint_cron_exclude_lead_open'),
        ])
        <select class="form-select" name="{{ $fieldPrefix }}lead_open">
            <option value="">{{ translate('Any') }}</option>
            <option value="open">{{ translate('Open') }}</option>
            <option value="closed">{{ translate('Closed') }}</option>
        </select>
    </div>
    <div class="col-md-4">
        @include('leadmanagement::admin.voice-calls._form_field_label', [
            'label' => translate('WhatsApp') . ' ' . translate('Status'),
            'hint' => $isInclude
                ? translate('Voice_field_hint_cron_include_wa_bucket')
                : translate('Voice_field_hint_cron_exclude_wa_bucket'),
        ])
        <select class="form-select" name="{{ $fieldPrefix }}wa_chat_bucket">
            <option value="">{{ translate('Any') }}</option>
            <option value="open">{{ translate('whatsapp_bucket_open') }}</option>
            <option value="closed">{{ translate('whatsapp_bucket_closed') }}</option>
        </select>
    </div>
    <div class="col-md-4">
        @include('leadmanagement::admin.voice-calls._form_field_label', [
            'label' => translate('Handled_By'),
            'hint' => $isInclude
                ? translate('Voice_field_hint_cron_include_handled_by')
                : translate('Voice_field_hint_cron_exclude_handled_by'),
        ])
        <select class="form-select"
                name="{{ $fieldPrefix }}handled_by"
                id="{{ $handledById }}"
                data-employee-wrap="{{ $employeeWrapId }}">
            <option value="">{{ translate('Any') }}</option>
            <option value="ai">AI</option>
            <option value="human">{{ translate('name_of_employee') }}</option>
        </select>
        @include('leadmanagement::admin.voice-calls._handled_by_employee_picker', [
            'wrapId' => $employeeWrapId,
            'selectId' => $employeeSelectId,
            'selectName' => $fieldPrefix . 'handled_by_employee_ids[]',
            'employees' => $employees ?? [],
        ])
    </div>
    @if($isInclude)
        <div class="col-md-4">
            @include('leadmanagement::admin.voice-calls._form_field_label', [
                'label' => translate('Human_support'),
                'hint' => translate('Voice_field_hint_cron_include_human_support'),
            ])
            <select class="form-select" name="human_support">
                <option value="">{{ translate('Any') }}</option>
                <option value="only">{{ translate('Human_support_only') }}</option>
            </select>
        </div>
    @else
        <div class="col-md-4">
            @include('leadmanagement::admin.voice-calls._form_field_label', [
                'label' => translate('Human_support'),
                'hint' => translate('Voice_field_hint_cron_exclude_human_support'),
            ])
            <select class="form-select" name="exclude_human_support">
                <option value="">{{ translate('Any') }}</option>
                <option value="exclude">{{ translate('Exclude_human_support') }}</option>
            </select>
        </div>
    @endif
</div>
