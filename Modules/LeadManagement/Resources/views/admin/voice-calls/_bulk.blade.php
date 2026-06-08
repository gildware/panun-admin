<div class="card mb-3">
    <div class="card-body p-30">
        <h4 class="mb-1">{{ translate('Create_Bulk_Call_Campaign') }}</h4>
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
            <form action="{{ route('admin.voice-call.bulk.store') }}" method="post" enctype="multipart/form-data" id="voice-bulk-form">
                @csrf

                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-30">
                            <label class="form-label">{{ translate('Campaign_name') }} *</label>
                            <input type="text" class="form-control" name="campaign_name" required maxlength="255"
                                   value="{{ old('campaign_name') }}"
                                   placeholder="{{ translate('Campaign_name') }}">
                            @error('campaign_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-30">
                            <label class="form-label">{{ translate('Caller_Phone_Number') }} *</label>
                            <select class="form-select js-select" name="phone_number_id" required>
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
                            <small class="text-muted">{{ translate('Voice_bulk_phone_number_hint') }}</small>
                            @error('phone_number_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-30">
                            <label class="form-label">{{ translate('Concurrent_Limit') }}</label>
                            <input type="number" class="form-control" name="concurrent_call_limit" min="1" max="20"
                                   value="{{ old('concurrent_call_limit', 1) }}">
                            <small class="text-muted">{{ translate('Voice_bulk_concurrent_hint') }}</small>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-30">
                            <label class="form-label">{{ translate('Send_Option') }} *</label>
                            <select class="form-select js-select" name="send_option" id="voice_bulk_send_option">
                                <option value="now" {{ old('send_option', 'now') === 'now' ? 'selected' : '' }}>
                                    {{ translate('Send_Now') }}
                                </option>
                                <option value="schedule" {{ old('send_option') === 'schedule' ? 'selected' : '' }}>
                                    {{ translate('Schedule') }}
                                </option>
                            </select>
                        </div>

                        <div class="mb-30 {{ old('send_option') === 'schedule' ? '' : 'd-none' }}" id="voice_bulk_schedule_wrap">
                            <label class="form-label">{{ translate('Schedule_Date') }} *</label>
                            <input type="datetime-local" class="form-control" name="scheduled_at"
                                   value="{{ old('scheduled_at') }}">
                            <div class="mt-2">
                                <label class="form-label">{{ translate('Timezone') }}</label>
                                <input type="text" class="form-control" name="timezone" value="{{ old('timezone', 'Asia/Kolkata') }}"
                                       placeholder="Asia/Kolkata">
                            </div>
                        </div>

                        <div class="mb-30">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="enabled_reschedule_call" id="enabled_reschedule_call"
                                       value="1" {{ old('enabled_reschedule_call') ? 'checked' : '' }}>
                                <label class="form-check-label" for="enabled_reschedule_call">
                                    {{ translate('Enable_call_rescheduling') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_retry" id="auto_retry"
                                       value="1" {{ old('auto_retry') ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_retry">
                                    {{ translate('Enable_auto_retry') }}
                                </label>
                            </div>
                        </div>

                        <div class="mb-30 {{ old('auto_retry') ? '' : 'd-none' }}" id="voice_bulk_retry_wrap">
                            <label class="form-label">{{ translate('Retry_Schedule') }}</label>
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
                                    <label class="form-label small">{{ translate('Retry_Limit') }}</label>
                                    <input type="number" class="form-control" name="retry_limit" min="1" max="5"
                                           value="{{ old('retry_limit', 2) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3 p-lg-4 mb-30 bg-light">
                    <h5 class="mb-1">{{ translate('Audience') }}</h5>
                    <p class="text-muted small mb-4">{{ translate('Voice_bulk_audience_hint') }}</p>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">{{ translate('Recipients') }} *</label>
                            <select class="form-select js-select" name="audience_type" id="voice_bulk_audience_type" required>
                                <option value="">{{ translate('Select') }} {{ translate('Recipients') }}</option>
                                <option value="all_customers" {{ old('audience_type') === 'all_customers' ? 'selected' : '' }}>
                                    {{ translate('All_Customers') }} — {{ $audienceCounts['all_customers'] ?? 0 }} {{ translate('with_valid_phone') }}
                                </option>
                                <option value="all_providers" {{ old('audience_type') === 'all_providers' ? 'selected' : '' }}>
                                    {{ translate('All_Providers') }} — {{ $audienceCounts['all_providers'] ?? 0 }} {{ translate('with_valid_phone') }}
                                </option>
                                <option value="providers_by_category" {{ old('audience_type') === 'providers_by_category' ? 'selected' : '' }}>
                                    {{ translate('Providers_by_Category') }}
                                </option>
                                <option value="csv_import" {{ old('audience_type') === 'csv_import' ? 'selected' : '' }}>
                                    {{ translate('Import_Contacts_CSV') }}
                                </option>
                            </select>
                        </div>

                        <div class="col-lg-6 {{ old('audience_type') === 'providers_by_category' ? '' : 'd-none' }}" id="voice_bulk_category_wrap">
                            <label class="form-label">{{ translate('category') }} *</label>
                            <select class="form-select js-select" name="category_id" id="voice_bulk_category_id">
                                <option value="">{{ translate('Select') }} {{ translate('category') }}</option>
                                @foreach(($categories ?? []) as $cat)
                                    @php $cid = (string) $cat->id; @endphp
                                    <option value="{{ $cat->id }}"
                                            {{ (string) old('category_id') === $cid ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                        ({{ (int) ($categoryRecipientCounts[$cid] ?? 0) }} {{ translate('providers') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 {{ old('audience_type') === 'csv_import' ? '' : 'd-none' }}" id="voice_bulk_csv_wrap">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <label class="form-label mb-0">{{ translate('CSV_file') }} *</label>
                                <a href="{{ route('admin.voice-call.bulk.sample-csv') }}"
                                   class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                    <span class="material-icons" style="font-size:18px;">download</span>
                                    {{ translate('Download_sample_CSV') }}
                                </a>
                            </div>
                            <input type="file" class="form-control" name="contacts_csv" accept=".csv,.txt">
                            <small class="text-muted d-block mt-1">{{ translate('Voice_bulk_csv_hint') }}</small>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3 p-lg-4 mb-30">
                    <h5 class="mb-1">{{ translate('Call_Context') }}</h5>
                    <p class="text-muted small mb-4">{{ translate('Voice_bulk_context_hint') }}</p>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-30">
                                <label class="form-label">{{ translate('Call_Reason') }}</label>
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
                                <label class="form-label">{{ translate('Lead_Status') }}</label>
                                <input type="text" class="form-control" name="lead_status" value="{{ old('lead_status') }}">
                            </div>
                            <div class="mb-30">
                                <label class="form-label">{{ translate('Lead_Summary') }}</label>
                                <textarea class="form-control" name="lead_summary" rows="3">{{ old('lead_summary') }}</textarea>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-30">
                                <label class="form-label">{{ translate('Service_Category') }}</label>
                                <input type="text" class="form-control" name="service_category" value="{{ old('service_category') }}">
                            </div>
                            <div class="mb-30">
                                <label class="form-label">{{ translate('Service_Details') }}</label>
                                <textarea class="form-control" name="service_details" rows="3">{{ old('service_details') }}</textarea>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">{{ translate('Notes') }}</label>
                                <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
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

<div id="voice-bulk-campaigns-content" class="text-center text-muted py-5">
    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
    {{ translate('Loading') }}…
</div>
