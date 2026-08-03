@if(empty($inModal))
    <div class="lead-detail-topbar">
        <nav class="breadcrumb-bar" aria-label="breadcrumb">
            <a href="{{ route('admin.lead.index') }}">{{ translate('Leads') }}</a>
            <span class="material-icons">chevron_right</span>
            <span class="breadcrumb-bar__current">#{{ $lead->id }}@if($leadNameForDisplay !== '') — {{ $leadNameForDisplay }}@endif</span>
        </nav>
        <a href="{{ route('admin.lead.index') }}" class="lead-detail-topbar__back">
            <span class="material-icons">arrow_back</span>
            {{ translate('Back_to_Leads') }}
        </a>
    </div>
@endif

<section class="lead-hero">
    <div class="lead-hero__top">
        <div class="lead-avatar" aria-hidden="true">{{ $leadInitials }}</div>
        <div class="lead-identity">
            <h1 class="lead-name">{{ $leadNameForDisplay !== '' ? $leadNameForDisplay : translate('Lead_Details') }}</h1>
            <div class="lead-contact">
                <a href="tel:{{ preg_replace('/\s+/', '', $lead->phone_number) }}" class="phone">
                    <span class="material-icons">phone</span>
                    {{ $lead->phone_number }}
                </a>
            </div>
            <div class="lead-meta">
                @if(!empty($isBookedCustomerStatus) && !empty($leadBooking) && !empty($leadBooking['id']))
                    <span>{{ translate('Booking_ID') }}: <a href="{{ route('admin.booking.details', $leadBooking['id']) }}" @if(!empty($inModal)) target="_top" @endif>{{ $leadBooking['readable_id'] ?? $leadBooking['id'] }}</a></span>
                @endif
                @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER)
                    <span>
                        {{ translate('Is_Added_in_Panel') }}:
                        @if(!empty($panelProviderMatch))
                            <a href="{{ $panelProviderMatch['url'] }}" @if(!empty($inModal)) target="_top" @endif>{{ $panelProviderMatch['name'] }}</a>
                        @else
                            {{ translate('No_match_found') }}
                        @endif
                    </span>
                @endif
            </div>
        </div>
        <div class="lead-badges">
            <span class="chip chip--{{ $lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER ? 'success' : ($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER ? 'primary' : ($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_INVALID ? 'danger' : 'info')) }}">
                {{ \Modules\LeadManagement\Entities\Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type }}
            </span>
            <span class="chip chip--{{ !empty($leadOpenStatus) ? 'danger' : 'success' }}">
                {{ !empty($leadOpenStatus) ? translate('Open') : translate('Closed') }}
            </span>
            @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER && !empty($typeHistoryDisplay['header_status']))
                <div id="provider-header-status-view" class="lead-badges__status-control">
                    <span class="chip chip--status"
                          id="provider-header-status-text"
                          style="background-color: {{ $typeHistoryDisplay['header_status_color'] ?? '#0d6efd' }};">
                        {{ $typeHistoryDisplay['header_status'] }}
                    </span>
                    <button type="button"
                            id="provider-header-status-edit-btn"
                            class="lead-status-edit-btn"
                            title="{{ translate('Change_Status') }}">
                        <span class="material-icons" aria-hidden="true">edit</span>
                    </button>
                </div>
                <div id="provider-header-status-edit" class="lead-badges__status-control lead-badges__status-control--edit d-none">
                    <select id="provider-header-status-select" class="form-select form-select-sm lead-status-select">
                        <option value="">{{ translate('Select_Status') }}</option>
                        @foreach($providerLeadStatuses as $status)
                            <option value="{{ $status->id }}"
                                    data-base-type="{{ $status->base_type ?? 'pending' }}"
                                    {{ ($currentProviderStatusId ?? '') == $status->id ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" id="provider-header-status-update-btn" class="ld-btn ld-btn-primary ld-btn-sm d-none">{{ translate('Update') }}</button>
                    <button type="button" id="provider-header-status-cancel-btn" class="ld-btn ld-btn-outline ld-btn-sm">{{ translate('Cancel') }}</button>
                </div>
            @elseif($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER && !empty($typeHistoryDisplay['header_status']))
                <div id="customer-header-status-view" class="lead-badges__status-control">
                    <span class="chip chip--status"
                          id="customer-header-status-text"
                          style="background-color: {{ $typeHistoryDisplay['header_status_color'] ?? '#0d6efd' }};">
                        {{ $typeHistoryDisplay['header_status'] }}
                    </span>
                    <button type="button"
                            id="customer-header-status-edit-btn"
                            class="lead-status-edit-btn"
                            title="{{ translate('Change_Status') }}">
                        <span class="material-icons" aria-hidden="true">edit</span>
                    </button>
                </div>
                <div id="customer-header-status-edit" class="lead-badges__status-control lead-badges__status-control--edit d-none">
                    <select id="customer-header-status-select" class="form-select form-select-sm lead-status-select">
                        <option value="">{{ translate('Select_Status') }}</option>
                        @foreach($customerLeadStatuses as $status)
                            <option value="{{ $status->id }}"
                                    data-base-type="{{ $status->base_type ?? 'pending' }}"
                                    {{ ($currentCustomerStatusId ?? '') == $status->id ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" id="customer-header-status-update-btn" class="ld-btn ld-btn-primary ld-btn-sm d-none">{{ translate('Update') }}</button>
                    <button type="button" id="customer-header-status-cancel-btn" class="ld-btn ld-btn-outline ld-btn-sm">{{ translate('Cancel') }}</button>
                </div>
            @endif
            @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER)
                <span class="chip chip--primary">{{ $lead->outbound_enquiries_count }} {{ translate('Outbound_Enquiries') }}</span>
            @endif
            @if(!empty($hasPendingFollowup))
                <span class="chip chip--{{ !empty($pendingFollowupIsOverdue) ? 'danger' : 'warning' }}">
                    {{ !empty($pendingFollowupIsOverdue) ? translate('Missed_Follow_up') : translate('Pending_Follow_up') }}
                </span>
            @endif
        </div>
    </div>

    @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_UNKNOWN)
        <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top lead-hero__type-actions">
            <button type="button" class="ld-btn ld-btn-outline text-danger border-danger btn-lead-type-invalid" data-bs-toggle="modal" data-bs-target="#leadInvalidModal">{{ translate('Mark_as_Invalid_Lead') }}</button>
            <button type="button" class="ld-btn ld-btn-outline text-info border-info btn-lead-type-future" data-bs-toggle="modal" data-bs-target="#leadFutureCustomerModal">{{ translate('Mark_as_Future_Customer_Lead') }}</button>
            <button type="button" class="ld-btn ld-btn-outline text-success border-success btn-lead-type-customer" data-bs-toggle="modal" data-bs-target="#leadCustomerModal">{{ translate('Mark_as_Customer_Lead') }}</button>
            <button type="button" class="ld-btn ld-btn-outline text-primary border-primary btn-lead-type-provider" data-bs-toggle="modal" data-bs-target="#leadProviderModal">{{ translate('Mark_as_Provider_Lead') }}</button>
        </div>
    @endif
</section>

@if(!empty($hasPendingFollowup))
    <div class="lead-followup-alert lead-followup-alert--{{ !empty($pendingFollowupIsOverdue) ? 'missed' : 'pending' }}" role="alert">
        <div class="lead-followup-alert__content">
            <span class="material-icons lead-followup-alert__icon">{{ !empty($pendingFollowupIsOverdue) ? 'error' : 'schedule' }}</span>
            <div>
                @if(!empty($pendingFollowupIsOverdue))
                    <strong>{{ translate('Missed_Follow_up') }}</strong>
                    — {{ translate('was_due_on') }}
                    {{ $lead->next_followup_at->format('d M Y, h:i A') }}.
                    {{ translate('Please_take_action') }}
                @else
                    <strong>{{ translate('Pending_Follow_up') }}</strong>
                    — {{ translate('due') }} {{ $lead->next_followup_at->format('d M Y, h:i A') }}.
                    {{ translate('Please_take_action') }}
                @endif
            </div>
        </div>
        <button type="button"
                class="ld-btn {{ !empty($pendingFollowupIsOverdue) ? 'ld-btn-danger' : 'ld-btn-warning' }}"
                data-bs-toggle="modal"
                data-bs-target="#addFollowupModal"
                data-followup-mode="take">
            <span class="material-icons">event_available</span>
            {{ translate('Take_Follow_up') }}
        </button>
    </div>
@endif

@if(!empty($linkedWhatsAppBooking) && in_array($lead->lead_type, [\Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER, \Modules\LeadManagement\Entities\Lead::TYPE_UNKNOWN], true))
    <section class="lead-ai-booking-banner" aria-label="{{ translate('Continue_with_AI_booking') }}">
        <div class="lead-ai-booking-banner__icon" aria-hidden="true">
            <span class="material-icons">smart_toy</span>
        </div>
        <div class="lead-ai-booking-banner__content">
            <p class="lead-ai-booking-banner__text mb-0">
                {{ translate('Lead_whatsapp_ai_booking_header_text') }}
                <strong>{{ $linkedWhatsAppBooking['booking_id'] }}</strong>
            </p>
            <div class="lead-ai-booking-banner__status">
                <span class="lead-ai-booking-banner__status-label">{{ translate('Status') }}</span>
                <span class="chip chip--primary">{{ $linkedWhatsAppBooking['status_label'] ?? ($linkedWhatsAppBooking['status'] ?? '—') }}</span>
            </div>
        </div>
        <div class="lead-ai-booking-banner__actions">
            <a href="{{ $linkedWhatsAppBooking['continue_url'] }}"
               class="ld-btn ld-btn-primary"
               @if(!empty($inModal)) target="_top" rel="noopener" @endif>
                <span class="material-icons">smart_toy</span>
                {{ translate('Continue_with_AI_booking') }}
            </a>
            @if(!empty($whatsappChatUrl))
                <a href="{{ $whatsappChatUrl }}" class="ld-btn ld-btn-outline" target="_blank" rel="noopener noreferrer">
                    <span class="material-icons">chat</span>
                    {{ translate('View_AI_chat') }}
                </a>
            @endif
        </div>
    </section>
@endif
