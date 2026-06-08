<div id="voice-bulk-list-view">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h4 class="mb-1">{{ translate('Voice_bulk_campaigns_title') }}</h4>
            <p class="text-muted small mb-0">{{ translate('Voice_bulk_campaigns_list_hint') }}</p>
        </div>
        @can('lead_outbound_enquiry_add')
            <button type="button"
                    class="btn btn--primary btn-sm d-inline-flex align-items-center gap-1"
                    id="voice-bulk-show-form"
                    {{ !$configured || $loadError || count($phoneNumbers) === 0 ? 'disabled' : '' }}>
                <span class="material-icons" style="font-size:18px;">add</span>
                {{ translate('Voice_bulk_add_campaign') }}
            </button>
        @endcan
    </div>

    <div id="voice-bulk-campaigns-content" class="text-center text-muted py-5">
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        {{ translate('Loading') }}…
    </div>
</div>

<div id="voice-bulk-form-view" class="d-none">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <button type="button"
                class="btn btn--secondary btn-sm d-inline-flex align-items-center gap-1"
                id="voice-bulk-back-to-list">
            <span class="material-icons" style="font-size:18px;">arrow_back</span>
            {{ translate('Voice_bulk_back_to_list') }}
        </button>
        <h4 class="mb-0">{{ translate('Create_Bulk_Call_Campaign') }}</h4>
    </div>

    <div class="card mb-3">
        <div class="card-body p-30">
            <p class="text-muted small mb-4">{{ translate('Voice_bulk_campaign_hint') }}</p>

            @if(!$configured)
                <div class="alert alert-warning mb-0">
                    {{ translate('OmniDimension_not_configured_hint') }}
                    <code>OMNIDIMENSION_API_KEY</code>
                </div>
            @elseif($loadError)
                <div class="alert alert-danger mb-0">
                    {{ translate('OmniDimension_load_failed') }}
                    <span class="d-block small mt-1 text-muted">{{ $loadError }}</span>
                </div>
            @elseif(count($phoneNumbers) === 0)
                <div class="alert alert-warning mb-0">
                    {{ translate('Voice_bulk_requires_phone_number') }}
                </div>
            @else
                <form action="{{ route('admin.voice-call.bulk.store') }}" method="post" enctype="multipart/form-data" id="voice-bulk-form" novalidate>
                    @csrf

                    @if($errors->any())
                        <div class="alert alert-danger mb-4" role="alert">
                            <div class="fw-medium mb-2">{{ translate('Please_fix_the_following_errors') }}</div>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-30">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Campaign_name'),
                                    'required' => true,
                                    'hint' => translate('Voice_field_hint_campaign_name'),
                                ])
                                <input type="text" class="form-control" name="campaign_name" maxlength="255"
                                       value="{{ old('campaign_name') }}"
                                       placeholder="{{ translate('Voice_field_placeholder_campaign_name') }}">
                                @error('campaign_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-30">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Caller_Phone_Number'),
                                    'required' => true,
                                    'hint' => translate('Voice_field_hint_bulk_caller_number'),
                                ])
                                <select class="form-select js-select voice-omnidim-phone-select" name="phone_number_id" id="voice_bulk_phone_number_id">
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
                                                {{ (string) old('phone_number_id') === (string) $number['id'] ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('phone_number_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-30">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Concurrent_Limit'),
                                    'hint' => translate('Voice_field_hint_concurrent_limit'),
                                ])
                                <input type="number" class="form-control" name="concurrent_call_limit" min="1" max="20"
                                       value="{{ old('concurrent_call_limit', 1) }}"
                                       placeholder="1">
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-30">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Voice_bulk_when_to_call'),
                                    'required' => true,
                                    'hint' => translate('Voice_field_hint_bulk_when_to_call'),
                                ])
                                <select class="form-select js-select @error('send_option') is-invalid @enderror" name="send_option" id="voice_bulk_send_option">
                                    <option value="now" {{ old('send_option', 'now') === 'now' ? 'selected' : '' }}>
                                        {{ translate('Voice_bulk_call_now') }}
                                    </option>
                                    <option value="schedule" {{ old('send_option') === 'schedule' ? 'selected' : '' }}>
                                        {{ translate('Schedule_for_later') }}
                                    </option>
                                </select>
                                @error('send_option')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-30 {{ old('send_option') === 'schedule' || $errors->has('scheduled_at') || $errors->has('timezone') ? '' : 'd-none' }}" id="voice_bulk_schedule_wrap">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Schedule_Date'),
                                    'required' => true,
                                    'hint' => translate('Voice_field_hint_schedule_date'),
                                ])
                                <input type="datetime-local"
                                       class="form-control @error('scheduled_at') is-invalid @enderror"
                                       name="scheduled_at"
                                       id="voice_bulk_scheduled_at"
                                       value="{{ old('scheduled_at') }}">
                                @error('scheduled_at')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="mt-2">
                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                        'label' => translate('Timezone'),
                                        'hint' => translate('Voice_field_hint_timezone'),
                                    ])
                                    <input type="text"
                                           class="form-control @error('timezone') is-invalid @enderror"
                                           name="timezone"
                                           id="voice_bulk_timezone"
                                           value="{{ old('timezone', 'Asia/Kolkata') }}"
                                           placeholder="Asia/Kolkata">
                                    @error('timezone')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-30">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="enabled_reschedule_call" id="enabled_reschedule_call"
                                           value="1" {{ old('enabled_reschedule_call') ? 'checked' : '' }}>
                                    @include('leadmanagement::admin.voice-calls._form_check_label', [
                                        'label' => translate('Enable_call_rescheduling'),
                                        'for' => 'enabled_reschedule_call',
                                        'hint' => translate('Voice_field_hint_enable_reschedule'),
                                    ])
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="auto_retry" id="auto_retry"
                                           value="1" {{ old('auto_retry') ? 'checked' : '' }}>
                                    @include('leadmanagement::admin.voice-calls._form_check_label', [
                                        'label' => translate('Enable_auto_retry'),
                                        'for' => 'auto_retry',
                                        'hint' => translate('Voice_field_hint_auto_retry'),
                                    ])
                                </div>
                            </div>

                            <div class="mb-30 {{ old('auto_retry') ? '' : 'd-none' }}" id="voice_bulk_retry_wrap">
                                @include('leadmanagement::admin.voice-calls._form_field_label', [
                                    'label' => translate('Retry_Schedule'),
                                    'hint' => translate('Voice_field_hint_retry_schedule'),
                                ])
                                <select class="form-select js-select" name="auto_retry_schedule">
                                    <option value="immediately" {{ old('auto_retry_schedule') === 'immediately' ? 'selected' : '' }}>
                                        {{ translate('Retry_immediately') }}
                                    </option>
                                    <option value="next_day" {{ old('auto_retry_schedule', 'next_day') === 'next_day' ? 'selected' : '' }}>
                                        {{ translate('Retry_next_day') }}
                                    </option>
                                    <option value="scheduled_time" {{ old('auto_retry_schedule') === 'scheduled_time' ? 'selected' : '' }}>
                                        {{ translate('Retry_scheduled_time') }}
                                    </option>
                                </select>
                                <div class="row g-2 mt-2">
                                    <div class="col-6">
                                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                                            'label' => translate('Retry_Limit'),
                                            'hint' => translate('Voice_field_hint_retry_limit'),
                                        ])
                                        <input type="number" class="form-control" name="retry_limit" min="1" max="5"
                                               value="{{ old('retry_limit', 2) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 p-lg-4 mb-30 bg-light">
                        <h5 class="mb-1 voice-form-section-title">
                            {{ translate('Audience') }}
                            <i class="material-symbols-outlined voice-field-info"
                               data-bs-toggle="tooltip"
                               title="{{ translate('Voice_bulk_audience_hint') }}"
                               tabindex="0"
                               role="img"
                               aria-label="{{ translate('Voice_bulk_audience_hint') }}">info</i>
                        </h5>
                        <p class="text-muted small mb-4">{{ translate('Voice_bulk_audience_hint') }}</p>

                        <div class="row g-3 mb-0 voice-bulk-audience-filters">
                            @include('leadmanagement::admin.voice-calls._bulk_audience_filters', [
                                'categories' => $categories ?? collect(),
                                'subCategories' => $subCategories ?? collect(),
                                'zones' => $zones ?? collect(),
                                'categoryRecipientCounts' => $categoryRecipientCounts ?? [],
                                'leadSources' => $leadSources ?? collect(),
                                'leadAdSources' => $leadAdSources ?? collect(),
                                'customerLeadStatuses' => $customerLeadStatuses ?? collect(),
                                'invalidReasons' => $invalidReasons ?? collect(),
                                'futureCustomerReasons' => $futureCustomerReasons ?? collect(),
                                'customerLeadTags' => $customerLeadTags ?? [],
                                'employees' => $employees ?? collect(),
                            ])

                            <div class="col-12 {{ old('recipient_kind') === 'csv_import' ? '' : 'd-none' }}" id="voice_bulk_csv_wrap">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                        'label' => translate('CSV_file'),
                                        'required' => true,
                                        'hint' => translate('Voice_field_hint_csv_file'),
                                    ])
                                    <a href="{{ route('admin.voice-call.bulk.sample-csv') }}"
                                       class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:18px;">download</span>
                                        {{ translate('Download_sample_CSV') }}
                                    </a>
                                </div>
                                <input type="file" class="form-control @error('contacts_csv') is-invalid @enderror" name="contacts_csv" id="voice_bulk_contacts_csv" accept=".csv,.txt">
                                @error('contacts_csv')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 d-none" id="voice_bulk_audience_preview_wrap">
                                <div class="accordion voice-bulk-audience-accordion" id="voiceBulkAudienceAccordion">
                                    <div class="accordion-item border rounded overflow-hidden">
                                        <h2 class="accordion-header" id="voiceBulkAudiencePreviewHeading">
                                            <button class="accordion-button py-3"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#voiceBulkAudiencePreviewCollapse"
                                                    aria-expanded="true"
                                                    aria-controls="voiceBulkAudiencePreviewCollapse">
                                                <span class="material-icons align-middle me-2" style="font-size:20px;">groups</span>
                                                {{ translate('Voice_bulk_audience_preview_title') }}
                                                <span class="badge bg-primary ms-2" id="voice_bulk_audience_preview_count">0</span>
                                            </button>
                                        </h2>
                                        <div id="voiceBulkAudiencePreviewCollapse"
                                             class="accordion-collapse collapse show"
                                             aria-labelledby="voiceBulkAudiencePreviewHeading"
                                             data-bs-parent="#voiceBulkAudienceAccordion">
                                            <div class="accordion-body pt-2">
                                                <p class="text-muted small mb-2" id="voice_bulk_audience_preview_subtitle"></p>
                                                <div class="text-muted small mb-2 d-none" id="voice_bulk_audience_preview_loading">
                                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                    {{ translate('Loading') }}…
                                                </div>
                                                <div class="table-responsive d-none" id="voice_bulk_audience_preview_table_wrap">
                                                    <table class="table table-sm table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 48px;">{{ translate('SL') }}</th>
                                                            <th>{{ translate('name') }}</th>
                                                            <th>{{ translate('phone') }}</th>
                                                            <th class="voice-bulk-audience-preview-cat-col d-none">{{ translate('Type') }} / {{ translate('category') }}</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody id="voice_bulk_audience_preview_tbody"></tbody>
                                                    </table>
                                                </div>
                                                <p class="text-muted small mb-0 d-none" id="voice_bulk_audience_preview_empty"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 p-lg-4 mb-30">
                        <h5 class="mb-1 voice-form-section-title">
                            {{ translate('Call_Context') }}
                            <i class="material-symbols-outlined voice-field-info"
                               data-bs-toggle="tooltip"
                               title="{{ translate('Voice_bulk_context_hint') }}"
                               tabindex="0"
                               role="img"
                               aria-label="{{ translate('Voice_bulk_context_hint') }}">info</i>
                        </h5>
                        <p class="text-muted small mb-4">{{ translate('Voice_bulk_context_hint') }}</p>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-30">
                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                        'label' => translate('Call_Reason'),
                                        'hint' => translate('Voice_field_hint_call_reason'),
                                    ])
                                    <select class="form-select js-select" name="call_reason">
                                        <option value="">{{ translate('Select') }}</option>
                                        @foreach(($callReasons ?? []) as $reason)
                                            <option value="{{ $reason }}" {{ old('call_reason') === $reason ? 'selected' : '' }}>
                                                {{ ($callReasonLabels ?? [])[$reason] ?? $reason }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-30">
                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                        'label' => translate('Lead_Status'),
                                        'hint' => translate('Voice_field_hint_lead_status'),
                                    ])
                                    <input type="text" class="form-control" name="lead_status" value="{{ old('lead_status') }}"
                                           placeholder="{{ translate('Voice_field_placeholder_lead_status') }}">
                                </div>
                                <div class="mb-30">
                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                        'label' => translate('Lead_Summary'),
                                        'hint' => translate('Voice_field_hint_lead_summary'),
                                    ])
                                    <textarea class="form-control" name="lead_summary" rows="3"
                                              placeholder="{{ translate('Voice_field_placeholder_lead_summary') }}">{{ old('lead_summary') }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-30">
                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                        'label' => translate('Service_Category'),
                                        'hint' => translate('Voice_field_hint_service_category'),
                                    ])
                                    <input type="text" class="form-control" name="service_category" value="{{ old('service_category') }}"
                                           placeholder="{{ translate('Voice_field_placeholder_service_category') }}">
                                </div>
                                <div class="mb-30">
                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                        'label' => translate('Service_Details'),
                                        'hint' => translate('Voice_field_hint_service_details'),
                                    ])
                                    <textarea class="form-control" name="service_details" rows="3"
                                              placeholder="{{ translate('Voice_field_placeholder_service_details') }}">{{ old('service_details') }}</textarea>
                                </div>
                                <div class="mb-0">
                                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                                        'label' => translate('Notes'),
                                        'hint' => translate('Voice_field_hint_notes'),
                                    ])
                                    <textarea class="form-control" name="notes" rows="3"
                                              placeholder="{{ translate('Voice_field_placeholder_notes') }}">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button class="btn btn--primary" type="submit">
                            <span class="material-icons align-middle" style="font-size:18px;">campaign</span>
                            {{ translate('Launch_Bulk_Campaign') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<div id="voice-bulk-detail-view" class="d-none">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <button type="button"
                class="btn btn--secondary btn-sm d-inline-flex align-items-center gap-1"
                id="voice-bulk-back-from-detail">
            <span class="material-icons" style="font-size:18px;">arrow_back</span>
            {{ translate('Voice_bulk_back_to_list') }}
        </button>
        <h4 class="mb-0">{{ translate('Voice_bulk_campaign_details_title') }}</h4>
    </div>

    <div class="card">
        <div class="card-body p-0" id="voice-bulk-campaign-detail-content">
            <div class="text-center text-muted py-5">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ translate('Loading') }}…
            </div>
        </div>
    </div>
</div>
