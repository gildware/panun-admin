<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-1">{{ translate('WhatsApp_Followup_Calls') }}</h4>
        <p class="text-muted small mb-4">{{ translate('WhatsApp_followup_tab_hint') }}</p>

        @if(!$configured)
            <div class="alert alert-warning mb-0">
                {{ translate('OmniDimension_not_configured_hint') }}
                <code>OMNIDIMENSION_API_KEY</code>
            </div>
        @else
            <form method="GET" action="{{ route('admin.voice-call.whatsapp-followup.list') }}" id="wa-followup-filter-form">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">{{ translate('Silent_at_least') }}</label>
                        <select class="form-select js-select" name="silent_min_hours">
                            @foreach([0 => '0h', 1 => '1h', 2 => '2h', 6 => '6h', 24 => '24h', 48 => '48h', 168 => '7d'] as $h => $label)
                                <option value="{{ $h }}" {{ (int) ($waFollowupDefaults['silent_min_hours'] ?? 2) === $h ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ translate('Lead_type') }}</label>
                        <select class="form-select js-select" name="lead_types[]" multiple>
                            @foreach(\Modules\LeadManagement\Entities\Lead::leadTypes() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ translate('Lead') }} {{ translate('Status') }}</label>
                        <select class="form-select js-select" name="lead_open">
                            <option value="">{{ translate('All') }}</option>
                            <option value="open">{{ translate('Open') }}</option>
                            <option value="closed">{{ translate('Closed') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ translate('WhatsApp') }} {{ translate('Status') }}</label>
                        <select class="form-select js-select" name="wa_chat_bucket">
                            <option value="">{{ translate('All') }}</option>
                            <option value="open">{{ translate('whatsapp_bucket_open') }}</option>
                            <option value="closed">{{ translate('whatsapp_bucket_closed') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ translate('whatsapp_chat_tags_label') }}</label>
                        <select class="form-select js-select" name="wa_chat_tag_ids[]" multiple>
                            @foreach(($waChatTags ?? []) as $tag)
                                <option value="{{ $tag['id'] }}">{{ $tag['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ translate('Customer_Lead_Tags') }}</label>
                        <select class="form-select js-select" name="customer_lead_tag_ids[]" multiple>
                            @foreach(($customerLeadTags ?? []) as $tag)
                                <option value="{{ $tag['id'] }}">{{ $tag['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ translate('Handled_By') }}</label>
                        <select class="form-select js-select" name="handled_by">
                            <option value="">{{ translate('All') }}</option>
                            <option value="ai">AI</option>
                            <option value="human">{{ translate('name_of_employee') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ translate('Human_support') }}</label>
                        <select class="form-select js-select" name="human_support">
                            <option value="exclude">{{ translate('Exclude_human_support') }}</option>
                            <option value="">{{ translate('All') }}</option>
                            <option value="only">{{ translate('Human_support_only') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ translate('Exclude_called_within') }}</label>
                        <select class="form-select js-select" name="exclude_called_within_hours">
                            @foreach([0, 6, 12, 24, 48, 168] as $h)
                                <option value="{{ $h }}" {{ $h === 24 ? 'selected' : '' }}>
                                    {{ $h === 0 ? translate('None') : ($h . 'h') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn--primary">{{ translate('Search') }}</button>
                        <button type="button" class="btn btn--secondary" id="wa-followup-reset">{{ translate('Reset') }}</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>

@if($configured)
    <div class="card mb-3 d-none" id="wa-followup-action-bar">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <span class="fw-semibold" id="wa-followup-selected-count">0</span> {{ translate('selected') }}
            </div>
            <button type="button" class="btn btn--primary" id="wa-followup-open-dispatch" disabled>
                <span class="material-icons align-middle" style="font-size:18px;">call</span>
                {{ translate('Call_selected') }}
            </button>
        </div>
    </div>

    <div id="wa-followup-list-content" class="text-center text-muted py-5">
        <span class="spinner-border spinner-border-sm me-2"></span>{{ translate('Loading') }}…
    </div>

    <div class="modal fade" id="waFollowupDispatchModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.voice-call.whatsapp-followup.dispatch') }}" id="wa-followup-dispatch-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Launch_Bulk_Campaign') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="wa-followup-dispatch-phones"></div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Campaign_name') }} *</label>
                            <input type="text" class="form-control" name="campaign_name" required maxlength="255"
                                   value="{{ translate('WhatsApp_Followup') }} {{ now()->format('Y-m-d H:i') }}">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('Caller_Phone_Number') }} ({{ translate('Customer') }})</label>
                                <select class="form-select js-select" name="phone_number_id_customer">
                                    <option value="">{{ translate('Select_phone_number') }}</option>
                                    @foreach($phoneNumbers as $number)
                                        @php $label = trim($number['name']) !== '' ? $number['name'] . ' — ' . $number['phone_number'] : $number['phone_number']; @endphp
                                        <option value="{{ $number['id'] }}"
                                            {{ (int) config('services.omnidimension.followup_phone_number_customer') === (int) $number['id'] ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('Caller_Phone_Number') }} ({{ translate('Provider') }})</label>
                                <select class="form-select js-select" name="phone_number_id_provider">
                                    <option value="">{{ translate('Select_phone_number') }}</option>
                                    @foreach($phoneNumbers as $number)
                                        @php $label = trim($number['name']) !== '' ? $number['name'] . ' — ' . $number['phone_number'] : $number['phone_number']; @endphp
                                        <option value="{{ $number['id'] }}"
                                            {{ (int) config('services.omnidimension.followup_phone_number_provider') === (int) $number['id'] ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('Caller_Phone_Number') }} ({{ translate('Unknown') }})</label>
                                <select class="form-select js-select" name="phone_number_id_unknown">
                                    <option value="">{{ translate('Select_phone_number') }}</option>
                                    @foreach($phoneNumbers as $number)
                                        @php $label = trim($number['name']) !== '' ? $number['name'] . ' — ' . $number['phone_number'] : $number['phone_number']; @endphp
                                        <option value="{{ $number['id'] }}"
                                            {{ (int) config('services.omnidimension.followup_phone_number_unknown', config('services.omnidimension.followup_phone_number_customer')) === (int) $number['id'] ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('Send_Option') }}</label>
                                <select class="form-select js-select" name="send_option" id="wa_followup_send_option">
                                    <option value="now">{{ translate('Send_Now') }}</option>
                                    <option value="schedule">{{ translate('Schedule') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-none" id="wa_followup_schedule_wrap">
                                <label class="form-label">{{ translate('Schedule_Date') }}</label>
                                <input type="datetime-local" class="form-control" name="scheduled_at">
                                <input type="hidden" name="timezone" value="Asia/Kolkata">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('Concurrent_Limit') }}</label>
                                <input type="number" class="form-control" name="concurrent_call_limit" min="1" max="20" value="1">
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="enabled_reschedule_call" value="1" id="wa_followup_reschedule">
                            <label class="form-check-label" for="wa_followup_reschedule">{{ translate('Enable_call_rescheduling') }}</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Launch_Bulk_Campaign') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
