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
                            $filters = $rule->normalizedFilters();
                            $rulePayload = [
                                'id' => $rule->id,
                                'name' => $rule->name,
                                'is_enabled' => $rule->is_enabled,
                                'interval_minutes' => $rule->interval_minutes,
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
                                <div>{{ $interval['value'] }} {{ translate($interval['unit']) }}</div>
                                <div class="text-muted small">
                                    {{ ($rule->dispatch_mode ?? 'approval') === 'auto'
                                        ? translate('Voice_cron_dispatch_auto')
                                        : translate('Voice_cron_dispatch_approval') }}
                                </div>
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
                                                data-rule="{{ json_encode($rulePayload) }}">
                                            {{ translate('Edit') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-info voice-cron-filter-runs"
                                                data-rule-id="{{ $rule->id }}">
                                            {{ translate('Executions') }}
                                        </button>
                                        @if($rule->is_enabled)
                                            <form method="POST" action="{{ route('admin.voice-call.cron-jobs.run', $rule) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">{{ translate('Run_now') }}</button>
                                            </form>
                                        @endif
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
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger voice-cron-job-delete"
                                                data-action="{{ route('admin.voice-call.cron-jobs.destroy', $rule) }}"
                                                data-name="{{ $rule->name }}">
                                            {{ translate('Delete') }}
                                        </button>
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
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voiceCronJobModalLabel">{{ translate('Add_cron_job') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <form method="POST" action="{{ route('admin.voice-call.cron-jobs.store') }}" id="voice-cron-job-form">
                    @csrf
                    <div class="modal-body">
                        <div id="voice-cron-form-summary" class="alert alert-light border small py-2 mb-3"></div>
                        <div id="voice-cron-interval-error" class="alert alert-danger small py-2 d-none mb-3"></div>
                        <div id="voice-cron-dispatch-auto-warning" class="alert alert-warning small py-2 d-none mb-3">
                            {{ translate('Voice_cron_dispatch_auto_warning') }}
                        </div>

                        <ul class="nav nav-tabs nav-tabs-sm mb-3" id="voiceCronFormTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="voice-cron-tab-setup-btn" data-bs-toggle="tab" data-bs-target="#voice-cron-tab-setup" type="button" role="tab">
                                    {{ translate('Voice_cron_tab_setup') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="voice-cron-tab-audience-btn" data-bs-toggle="tab" data-bs-target="#voice-cron-tab-audience" type="button" role="tab">
                                    {{ translate('Voice_cron_tab_audience') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="voice-cron-tab-advanced-btn" data-bs-toggle="tab" data-bs-target="#voice-cron-tab-advanced" type="button" role="tab">
                                    {{ translate('Voice_cron_tab_advanced') }}
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content pt-1">
                            <div class="tab-pane fade show active" id="voice-cron-tab-setup" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-12">
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Name'),
                                            'required' => true,
                                            'hint' => translate('Voice_field_hint_cron_name'),
                                        ])
                                        <input type="text" class="form-control" name="name" id="voice-cron-name" required maxlength="255"
                                               placeholder="{{ translate('Voice_field_placeholder_cron_name') }}">
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
                                        <input type="number" class="form-control" name="max_contacts_per_run" id="voice-cron-max-contacts" min="1" max="500" value="50" required>
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
                            </div>

                            <div class="tab-pane fade" id="voice-cron-tab-audience" role="tabpanel">
                                <div id="voice-cron-match-conflicts" class="alert alert-danger small py-2 d-none mb-3" role="alert">
                                    <div class="fw-semibold mb-1">{{ translate('Voice_cron_match_conflicts_title') }}</div>
                                    <p class="mb-2">{{ translate('Voice_cron_match_conflicts_hint') }}</p>
                                    <ul id="voice-cron-match-conflicts-list" class="mb-0 ps-3"></ul>
                                </div>

                                <div class="row g-3 mb-3">
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
                                                   min="1"
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
                                            'label' => translate('Silent_at_most'),
                                            'hint' => translate('Voice_field_hint_silent_max_hours'),
                                        ])
                                        <select class="form-select" name="silent_max_hours" id="voice-cron-silent-max-hours">
                                            <option value="">{{ translate('None') }}</option>
                                            @foreach([12, 24, 48, 72, 168] as $h)
                                                <option value="{{ $h }}">{{ $h }}h</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Exclude_called_within'),
                                            'hint' => translate('Voice_field_hint_exclude_called'),
                                        ])
                                        <select class="form-select" name="exclude_called_within_hours" id="voice-cron-exclude-called">
                                            @foreach([0, 6, 12, 24, 48, 168] as $h)
                                                <option value="{{ $h }}" {{ $h === 24 ? 'selected' : '' }}>{{ $h === 0 ? translate('None') : ($h . 'h') }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3 align-items-stretch">
                                    <div class="col-lg-6 order-lg-1">
                                        <div class="voice-cron-panel-include border rounded p-3 h-100">
                                            <h6 class="mb-1 text-success">{{ translate('Voice_cron_include_conditions') }}</h6>
                                            <p class="text-muted small mb-3">{{ translate('Voice_cron_include_conditions_hint') }}</p>
                                            @include('leadmanagement::admin.voice-calls._voice_cron_match_condition_fields', [
                                                'fieldPrefix' => '',
                                                'section' => 'include',
                                                'handledById' => 'voice-cron-include-handled-by',
                                                'employeeWrapId' => 'voice-cron-include-handled-by-employees-wrap',
                                                'employeeSelectId' => 'voice-cron-include-handled-by-employee-ids',
                                                'waChatTags' => $waChatTags ?? [],
                                                'employees' => $employees ?? [],
                                                'customerLeadStatuses' => $customerLeadStatuses ?? collect(),
                                                'providerLeadStatuses' => $providerLeadStatuses ?? collect(),
                                            ])
                                        </div>
                                    </div>
                                    <div class="col-lg-6 order-lg-2">
                                        <div class="voice-cron-panel-exclude border rounded p-3 h-100">
                                            <h6 class="mb-1 text-danger">{{ translate('Voice_cron_exclude_conditions') }}</h6>
                                            <p class="text-muted small mb-3">{{ translate('Voice_cron_exclude_conditions_hint') }}</p>
                                            @include('leadmanagement::admin.voice-calls._voice_cron_match_condition_fields', [
                                                'fieldPrefix' => 'exclude_',
                                                'section' => 'exclude',
                                                'handledById' => 'voice-cron-exclude-handled-by',
                                                'employeeWrapId' => 'voice-cron-exclude-handled-by-employees-wrap',
                                                'employeeSelectId' => 'voice-cron-exclude-handled-by-employee-ids',
                                                'waChatTags' => $waChatTags ?? [],
                                                'employees' => $employees ?? [],
                                                'customerLeadStatuses' => $customerLeadStatuses ?? collect(),
                                                'providerLeadStatuses' => $providerLeadStatuses ?? collect(),
                                            ])
                                        </div>
                                    </div>
                                </div>

                                <div class="border rounded p-3 bg-light">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <h6 class="mb-0">{{ translate('Voice_cron_preview_matches') }}</h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="voice-cron-preview-btn">
                                            {{ translate('Voice_cron_preview_matches_btn') }}
                                        </button>
                                    </div>
                                    <p class="text-muted small mb-2">{{ translate('Voice_cron_preview_matches_hint') }}</p>
                                    <div id="voice-cron-preview-wrap" class="border rounded p-3 bg-white d-none">
                                        <div id="voice-cron-preview-content"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="voice-cron-tab-advanced" role="tabpanel">
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
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
                                        <p id="voice-cron-other-jobs-perf-hint" class="text-warning small mt-2 mb-0 d-none">
                                            {{ translate('Voice_cron_other_jobs_perf_hint') }}
                                        </p>
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

                                <hr class="my-3">
                                <h6 class="mb-2">{{ translate('Voice_cron_call_settings') }}</h6>
                                <div class="mb-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="enabled_reschedule_call" value="1" id="voice-cron-reschedule">
                                        @include('leadmanagement::admin.voice-calls._form_check_label', [
                                            'label' => translate('Enable_call_rescheduling'),
                                            'for' => 'voice-cron-reschedule',
                                            'hint' => translate('Voice_field_hint_enable_reschedule'),
                                        ])
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="auto_retry" value="1" id="voice-cron-auto-retry">
                                        @include('leadmanagement::admin.voice-calls._form_check_label', [
                                            'label' => translate('Enable_auto_retry'),
                                            'for' => 'voice-cron-auto-retry',
                                            'hint' => translate('Voice_field_hint_auto_retry'),
                                        ])
                                    </div>
                                </div>
                                <div class="row g-3 d-none" id="voice-cron-retry-wrap">
                                    <div class="col-md-6">
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Retry_Schedule'),
                                            'hint' => translate('Voice_field_hint_retry_schedule'),
                                        ])
                                        <select class="form-select" name="auto_retry_schedule" id="voice-cron-retry-schedule">
                                            <option value="immediately">{{ translate('Retry_immediately') }}</option>
                                            <option value="next_day" selected>{{ translate('Retry_next_day') }}</option>
                                            <option value="scheduled_time">{{ translate('Retry_scheduled_time') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Retry_Limit'),
                                            'hint' => translate('Voice_field_hint_retry_limit'),
                                        ])
                                        <input type="number" class="form-control" name="retry_limit" id="voice-cron-retry-limit" min="1" max="5" value="2">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-body">
                        <button type="button" class="btn btn-outline-primary" id="voice-cron-preview-btn-footer">
                            {{ translate('Voice_cron_preview_matches_btn') }}
                        </button>
                        <button type="button" class="btn btn--secondary ms-auto" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary" id="voice-cron-save-btn">{{ translate('Save') }}</button>
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
                    <button type="button" class="btn btn-outline-danger me-auto voice-cron-reject-run d-none" id="voice-cron-dispatch-reject" data-run-id="">
                        {{ translate('Voice_cron_reject_run') }}
                    </button>
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" form="voice-cron-dispatch-form" class="btn btn--primary" id="voice-cron-dispatch-submit" disabled>
                        {{ translate('Voice_cron_make_calls') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="voiceCronDeleteModal" tabindex="-1" aria-labelledby="voiceCronDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voiceCronDeleteModalLabel">{{ translate('Delete_cron_job') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <form method="POST" action="" id="voice-cron-delete-form">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p class="mb-0">
                            {{ translate('Voice_cron_delete_confirm') }}
                            <strong id="voice-cron-delete-name" class="text-danger"></strong>
                        </p>
                        <p class="text-muted small mb-0 mt-2">{{ translate('Voice_cron_delete_confirm_hint') }}</p>
                    </div>
                    <div class="modal-footer border-top bg-body">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-outline-danger" id="voice-cron-delete-confirm-btn">{{ translate('Delete') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @once
        <style>
            #voiceCronJobModal .voice-cron-panel-include {
                background-color: rgba(25, 135, 84, 0.1);
                border-color: rgba(25, 135, 84, 0.35) !important;
            }

            #voiceCronJobModal .voice-cron-panel-exclude {
                background-color: rgba(220, 53, 69, 0.1);
                border-color: rgba(220, 53, 69, 0.35) !important;
            }
        </style>
    @endonce
@endcan
