@php
    $automationRules = $voiceCronRules ?? collect();
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h4 class="mb-1">{{ translate('Voice_cron_jobs_title') }}</h4>
                <p class="text-muted small mb-0">{{ translate('Voice_cron_jobs_hint') }}</p>
            </div>
            @can('lead_outbound_enquiry_add')
                <button type="button"
                        class="btn btn--primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#voiceCronJobModal"
                        id="voice-cron-job-add"
                        {{ empty($voiceCronTableReady) ? 'disabled' : '' }}>
                    {{ translate('Add_cron_job') }}
                </button>
            @endcan
        </div>

        @if(empty($voiceCronTableReady))
            <div class="alert alert-warning mb-0">
                {{ translate('WhatsApp_followup_automation_migration_required') }}
                <div class="mt-2"><code>php artisan migrate</code></div>
            </div>
        @elseif(!$configured)
            <div class="alert alert-warning mb-0">
                {{ translate('OmniDimension_not_configured_hint') }}
                <code>OMNIDIMENSION_API_KEY</code>
            </div>
        @elseif($automationRules->isEmpty())
            <p class="text-muted mb-0">{{ translate('Voice_cron_jobs_empty') }}</p>
        @else
            <div class="table-responsive voice-call-table-wrap">
                <table class="table table-hover align-middle mb-0 voice-call-data-table">
                    <thead>
                    <tr>
                        <th>{{ translate('Name') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('WhatsApp_followup_automation_interval') }}</th>
                        <th>{{ translate('WhatsApp_followup_automation_max_contacts') }}</th>
                        <th>{{ translate('Last_run') }}</th>
                        <th class="text-end">{{ translate('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($automationRules as $rule)
                        @php
                            $filters = is_array($rule->filters) ? $rule->filters : [];
                            $rulePayload = [
                                'id' => $rule->id,
                                'name' => $rule->name,
                                'is_enabled' => $rule->is_enabled,
                                'interval_minutes' => $rule->interval_minutes,
                                'campaign_name' => $rule->campaign_name,
                                'max_contacts_per_run' => $rule->max_contacts_per_run,
                                'concurrent_call_limit' => $rule->concurrent_call_limit,
                                'enabled_reschedule_call' => $rule->enabled_reschedule_call,
                                'auto_retry' => $rule->auto_retry,
                                'auto_retry_schedule' => $rule->auto_retry_schedule,
                                'retry_limit' => $rule->retry_limit,
                                'dispatch_mode' => $rule->dispatch_mode ?? 'approval',
                                'filters' => $filters,
                            ];
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $rule->name }}</td>
                            <td>
                                <span class="badge rounded-pill {{ $rule->is_enabled ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $rule->is_enabled ? translate('Running') : translate('Stopped') }}
                                </span>
                            </td>
                            <td>
                                @php $interval = $rule->resolvedInterval(); @endphp
                                {{ $interval['value'] }} {{ translate($interval['unit']) }}
                            </td>
                            <td>{{ $rule->max_contacts_per_run }}</td>
                            <td class="small">
                                @if($rule->last_run_at)
                                    <div>{{ $rule->last_run_at->format('d M Y H:i') }}</div>
                                    <div class="text-muted">
                                        {{ (int) $rule->last_run_contacts }} {{ translate('contacts') }}
                                        · {{ ucfirst((string) $rule->last_run_status) }}
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('lead_outbound_enquiry_add')
                                    <div class="d-inline-flex gap-1 flex-nowrap">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary voice-cron-job-edit"
                                                data-rule='@json($rulePayload)'>
                                            {{ translate('Edit') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-info voice-cron-filter-runs"
                                                data-rule-id="{{ $rule->id }}">
                                            {{ translate('Executions') }}
                                        </button>
                                        <form method="POST" action="{{ route('admin.voice-call.cron-jobs.run', $rule) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">{{ translate('Run_now') }}</button>
                                        </form>
                                        @if($rule->is_enabled)
                                            <form method="POST" action="{{ route('admin.voice-call.cron-jobs.stop', $rule) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning">{{ translate('Stop') }}</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.voice-call.cron-jobs.start', $rule) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">{{ translate('Start') }}</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.voice-call.cron-jobs.destroy', $rule) }}" class="d-inline" onsubmit="return confirm(@json(translate('Are_you_sure')));">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ translate('Delete') }}</button>
                                        </form>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!empty($voiceCronTableReady))
            <p class="text-muted small mt-3 mb-0">
                {{ translate('WhatsApp_followup_automation_cron_hint') }}
            </p>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">{{ translate('Voice_cron_execution_report') }}</h5>
            <div class="d-flex gap-2 align-items-center">
                <select class="form-select form-select-sm" id="voice-cron-runs-filter" style="min-width:200px;">
                    <option value="">{{ translate('All_cron_jobs') }}</option>
                    @foreach($automationRules as $rule)
                        <option value="{{ $rule->id }}">{{ $rule->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn--secondary" id="voice-cron-runs-refresh">{{ translate('Refresh') }}</button>
            </div>
        </div>
        <div id="voice-cron-runs-content" class="text-center text-muted py-4">
            <span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…
        </div>
    </div>
</div>

@can('lead_outbound_enquiry_add')
    <div class="modal fade modal-scrolling-customize" id="voiceCronJobModal" tabindex="-1" aria-labelledby="voiceCronJobModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voiceCronJobModalLabel">{{ translate('Add_cron_job') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <form method="POST" action="{{ route('admin.voice-call.cron-jobs.store') }}" id="voice-cron-job-form">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Name'),
                                    'required' => true,
                                    'hint' => translate('Voice_field_hint_cron_name'),
                                ])
                                <input type="text" class="form-control" name="name" required maxlength="255"
                                       placeholder="{{ translate('Voice_field_placeholder_cron_name') }}">
                            </div>
                            <div class="col-md-6">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Campaign_name'),
                                    'required' => true,
                                    'hint' => translate('Voice_field_hint_cron_campaign_name'),
                                ])
                                <input type="text" class="form-control" name="campaign_name" required maxlength="255" value="WhatsApp follow-up auto">
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('WhatsApp_followup_automation_interval'),
                                    'required' => true,
                                    'hint' => translate('Voice_field_hint_cron_interval_duration'),
                                ])
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control"
                                           name="interval_value"
                                           id="voice-cron-interval-value"
                                           min="1"
                                           max="9999"
                                           step="1"
                                           value="1"
                                           required>
                                    <select class="form-select" name="interval_unit" id="voice-cron-interval-unit" style="max-width:7rem;">
                                        <option value="minutes">{{ translate('minutes') }}</option>
                                        <option value="hours" selected>{{ translate('hours') }}</option>
                                        <option value="days">{{ translate('days') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('WhatsApp_followup_automation_max_contacts'),
                                    'required' => true,
                                    'hint' => translate('Voice_field_hint_cron_max_contacts'),
                                ])
                                <input type="number" class="form-control" name="max_contacts_per_run" min="1" max="500" value="50" required>
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('WhatsApp_followup_automation_concurrent_calls'),
                                    'hint' => translate('Voice_field_hint_concurrent_limit'),
                                ])
                                <input type="number" class="form-control" name="concurrent_call_limit" min="1" max="20" value="1">
                            </div>
                            <div class="col-md-8">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Voice_cron_dispatch_mode'),
                                    'hint' => translate('Voice_field_hint_cron_dispatch_mode'),
                                ])
                                <select class="form-select" name="dispatch_mode" id="voice-cron-dispatch-mode">
                                    <option value="approval" selected>{{ translate('Voice_cron_dispatch_approval') }}</option>
                                    <option value="auto">{{ translate('Voice_cron_dispatch_auto') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="voice-cron-is-enabled" checked>
                                    @include('leadmanagement::admin.voice-calls._form_check_label', [
                                        'label' => translate('Start_immediately'),
                                        'for' => 'voice-cron-is-enabled',
                                        'hint' => translate('Voice_field_hint_cron_start_immediately'),
                                    ])
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="mb-2">{{ translate('WhatsApp_followup_automation_conditions') }}</h6>
                        <p class="text-muted small">{{ translate('WhatsApp_followup_automation_conditions_hint') }}</p>

                        <div class="row g-3">
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Silent_at_least'),
                                    'hint' => translate('Voice_field_hint_silent_min_duration'),
                                ])
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control"
                                           name="silent_min_value"
                                           id="voice-cron-silent-min-value"
                                           min="0"
                                           max="9999"
                                           step="1"
                                           value="1"
                                           required>
                                    <select class="form-select" name="silent_min_unit" id="voice-cron-silent-min-unit" style="max-width:7rem;">
                                        <option value="minutes">{{ translate('minutes') }}</option>
                                        <option value="hours" selected>{{ translate('hours') }}</option>
                                        <option value="days">{{ translate('days') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Lead_type'),
                                    'hint' => translate('Voice_field_hint_lead_type'),
                                ])
                                <select class="form-select js-select" name="lead_types[]" multiple>
                                    @foreach(\Modules\LeadManagement\Entities\Lead::leadTypes() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Lead') . ' ' . translate('Status'),
                                    'hint' => translate('Voice_field_hint_lead_open'),
                                ])
                                <select class="form-select" name="lead_open">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="open">{{ translate('Open') }}</option>
                                    <option value="closed">{{ translate('Closed') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('WhatsApp') . ' ' . translate('Status'),
                                    'hint' => translate('Voice_field_hint_wa_chat_bucket'),
                                ])
                                <select class="form-select" name="wa_chat_bucket">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="open">{{ translate('whatsapp_bucket_open') }}</option>
                                    <option value="closed">{{ translate('whatsapp_bucket_closed') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Human_support'),
                                    'hint' => translate('Voice_field_hint_human_support'),
                                ])
                                <select class="form-select" name="human_support">
                                    <option value="exclude" selected>{{ translate('Exclude_human_support') }}</option>
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="only">{{ translate('Human_support_only') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Exclude_called_within'),
                                    'hint' => translate('Voice_field_hint_exclude_called'),
                                ])
                                <select class="form-select" name="exclude_called_within_hours">
                                    @foreach([0, 6, 12, 24, 48, 168] as $h)
                                        <option value="{{ $h }}" {{ $h === 24 ? 'selected' : '' }}>{{ $h === 0 ? translate('None') : ($h . 'h') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('whatsapp_chat_tags_label'),
                                    'hint' => translate('Voice_field_hint_wa_chat_tags'),
                                ])
                                <select class="form-select js-select" name="wa_chat_tag_ids[]" multiple>
                                    @foreach(($waChatTags ?? []) as $tag)
                                        <option value="{{ $tag['id'] }}">{{ $tag['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Customer_Lead_Tags'),
                                    'hint' => translate('Voice_field_hint_customer_lead_tags'),
                                ])
                                <select class="form-select js-select" name="customer_lead_tag_ids[]" multiple>
                                    @foreach(($customerLeadTags ?? []) as $tag)
                                        <option value="{{ $tag['id'] }}">{{ $tag['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Handled_By'),
                                    'hint' => translate('Voice_field_hint_wa_handled_by'),
                                ])
                                <select class="form-select"
                                        name="handled_by"
                                        id="voice-cron-handled-by"
                                        data-employee-wrap="voice-cron-handled-by-employees-wrap">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="ai">AI</option>
                                    <option value="human">{{ translate('name_of_employee') }}</option>
                                </select>
                                @include('leadmanagement::admin.voice-calls._handled_by_employee_picker', [
                                    'wrapId' => 'voice-cron-handled-by-employees-wrap',
                                    'selectId' => 'voice-cron-handled-by-employee-ids',
                                    'employees' => $employees ?? [],
                                ])
                            </div>
                            <div class="col-12">
                                <hr class="my-1">
                                <h6 class="mb-1">{{ translate('Voice_cron_other_jobs_label') }}</h6>
                                <p class="text-muted small mb-2">{{ translate('Voice_cron_other_jobs_hint') }}</p>
                            </div>
                            <div class="col-md-4">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Voice_cron_other_jobs_mode'),
                                    'hint' => translate('Voice_field_hint_cron_other_jobs_mode'),
                                ])
                                <select class="form-select" name="other_cron_job_mode" id="voice-cron-other-job-mode">
                                    <option value="">{{ translate('Voice_cron_other_jobs_none') }}</option>
                                    <option value="exclude_all_active">{{ translate('Voice_cron_other_jobs_exclude_all_active') }}</option>
                                    <option value="exclude">{{ translate('Voice_cron_other_jobs_exclude') }}</option>
                                    <option value="include">{{ translate('Voice_cron_other_jobs_include') }}</option>
                                </select>
                            </div>
                            <div class="col-md-8" id="voice-cron-other-job-ids-wrap">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Voice_cron_other_jobs_select'),
                                    'hint' => translate('Voice_field_hint_cron_other_jobs_select'),
                                ])
                                <select class="form-select js-select" name="other_cron_job_ids[]" id="voice-cron-other-job-ids" multiple>
                                    @foreach($automationRules as $otherRule)
                                        <option value="{{ $otherRule->id }}">{{ $otherRule->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-body">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-scrolling-customize" id="voiceCronDispatchModal" tabindex="-1" aria-labelledby="voiceCronDispatchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voiceCronDispatchModalLabel">{{ translate('Voice_cron_make_calls') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body" id="voice-cron-dispatch-modal-body">
                    <div class="text-center text-muted py-4">
                        <span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…
                    </div>
                </div>
                <div class="modal-footer border-top bg-body">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" form="voice-cron-dispatch-form" class="btn btn--primary" id="voice-cron-dispatch-submit" disabled>
                        {{ translate('Voice_cron_make_calls') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endcan
