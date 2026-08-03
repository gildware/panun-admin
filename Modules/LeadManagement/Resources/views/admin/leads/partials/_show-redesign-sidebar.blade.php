<aside class="lead-sidebar">

    <div class="action-card">
        <div class="action-card__head">
            <span class="action-card__title">
                <span class="material-icons">bolt</span>
                {{ translate('Quick_Actions') }}
            </span>
        </div>
        <ul class="quick-links">
            @if(!empty($whatsappChatUrl) && empty($linkedWhatsAppBooking))
                <li><a href="{{ $whatsappChatUrl }}" target="_blank" rel="noopener noreferrer"><span class="material-icons">chat</span> {{ translate('View_AI_chat') }}</a></li>
            @endif
            @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER && !empty($isPendingCustomerStatus) && empty($linkedWhatsAppBooking))
                <li><a href="{{ route('admin.booking.create-from-lead', ['lead' => $lead->id, 'context' => !empty($inModal) ? 'lead_modal' : 'lead']) }}" class="workflow-gated-link" data-workflow-action="{{ \Modules\AdminModule\Support\WorkflowStepDefinitions::ACTION_LEAD_CREATE_BOOKING }}" @if(!empty($inModal)) target="_top" rel="noopener" @endif><span class="material-icons">add_circle</span> {{ translate('Create_Booking_for_this_Lead') }}</a></li>
            @endif
            @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER)
                @can('lead_outbound_enquiry_add')
                    <li>
                        <button type="button"
                                class="quick-link-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#addOutboundEnquiryModal">
                            <span class="material-icons">add</span> {{ translate('Add_Outbound_Enquiry') }}
                        </button>
                    </li>
                @endcan
            @endif
            @if(empty($hasPendingFollowup))
                <li>
                    <button type="button"
                            class="quick-link-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#addFollowupModal"
                            data-followup-mode="add">
                        <span class="material-icons">event</span> {{ translate('Add_Follow_up') }}
                    </button>
                </li>
            @endif
        </ul>
    </div>

    <div class="action-card" id="lead-assignee-card">
        <div class="action-card__head d-flex justify-content-between align-items-center">
            <span class="action-card__title">
                <span class="material-icons">assignment_ind</span>
                {{ translate('Assigned_To') }}
            </span>
            @can('lead_update')
                <button type="button" id="lead-assignee-edit-btn" class="panel__edit">{{ translate('Edit') }}</button>
                <button type="button" id="lead-assignee-cancel-btn" class="panel__edit d-none">{{ translate('Cancel') }}</button>
            @endcan
        </div>
        <div class="action-card__body">
            <div id="lead-assignee-view">
                <div class="assignee">
                    <div class="assignee__avatar">{{ $handledByInitials }}</div>
                    <div>
                        <div class="assignee__name">{{ $handledByName ?: '—' }}</div>
                        <div class="assignee__role">{{ translate('Handled_By') }}</div>
                    </div>
                </div>
            </div>
            @can('lead_update')
                <div id="lead-assignee-edit" class="d-none">
                    <form method="POST" action="{{ route('admin.lead.update', $lead->id) }}" class="lead-assignee-form">
                        @csrf
                        @method('PUT')
                        @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                        <label class="form-label small text-muted mb-1" for="lead-assignee-select">{{ translate('Handled_By') }}</label>
                        <select name="handled_by" id="lead-assignee-select" class="form-select form-select-sm js-select mb-2">
                            <option value="">{{ translate('Select_employee') }}</option>
                            @foreach($employees as $employee)
                                @php
                                    $empName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                    $empLabel = $empName ?: $employee->email;
                                    $currentAssignee = \Modules\LeadManagement\Entities\Lead::assigneeIsHuman($lead->handled_by ?? null)
                                        ? (string) $lead->handled_by
                                        : '';
                                @endphp
                                <option value="{{ $employee->id }}" {{ (string) old('handled_by', $currentAssignee) === (string) $employee->id ? 'selected' : '' }}>{{ $empLabel }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="ld-btn ld-btn-primary w-100 justify-content-center">
                            <span class="material-icons">save</span>
                            {{ translate('Update') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER)
        <div class="action-card" id="lead-temporary-provider-card">
            <div class="action-card__head d-flex justify-content-between align-items-center">
                <span class="action-card__title">
                    <span class="material-icons">engineering</span>
                    {{ translate('Temporary_Provider') }}
                </span>
                @can('lead_update')
                    <button type="button" id="lead-temporary-provider-edit-btn" class="panel__edit">{{ translate('Edit') }}</button>
                    <button type="button" id="lead-temporary-provider-cancel-btn" class="panel__edit d-none">{{ translate('Cancel') }}</button>
                @endcan
            </div>
            <div class="action-card__body">
                <p class="text-muted small mb-2">{{ translate('Temporary_provider_discussion_hint') }}</p>
                <div id="lead-temporary-provider-view">
                    @if(!empty($temporaryProvider))
                        <div class="assignee">
                            <div class="assignee__avatar">{{ strtoupper(mb_substr(trim($temporaryProvider->company_name ?? 'P'), 0, 2)) }}</div>
                            <div>
                                <div class="assignee__name">
                                    <a href="{{ route('admin.provider.details', $temporaryProvider->id) }}" @if(!empty($inModal)) target="_top" @endif>
                                        {{ $temporaryProvider->company_name ?? translate('Provider') }}
                                    </a>
                                </div>
                                @if(!empty($temporaryProvider->contact_person_name) || !empty($temporaryProvider->contact_person_phone))
                                    <div class="assignee__role">
                                        {{ $temporaryProvider->contact_person_name ?? '' }}
                                        @if(!empty($temporaryProvider->contact_person_phone))
                                            · {{ $temporaryProvider->contact_person_phone }}
                                        @endif
                                    </div>
                                @endif
                                @if(!empty($temporaryProviderAssignedAt))
                                    <div class="assignee__role">{{ translate('Assigned_on') }} {{ $temporaryProviderAssignedAt->format('d M Y, h:i A') }}</div>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-muted small mb-0">—</p>
                    @endif
                </div>
                @can('lead_update')
                    <div id="lead-temporary-provider-edit" class="d-none">
                        <form method="POST" action="{{ route('admin.lead.temporary-provider.update', $lead->id) }}" class="lead-temporary-provider-form">
                            @csrf
                            @method('PUT')
                            @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                            <label class="form-label small text-muted mb-1" for="lead-temporary-provider-select">{{ translate('Temporary_Provider') }}</label>
                            <select name="temporary_provider_id"
                                    id="lead-temporary-provider-select"
                                    class="form-select form-select-sm mb-2"
                                    data-placeholder="{{ translate('Search_provider_by_name_or_phone') }}"
                                    data-selected="{{ $customerHistoryData['temporary_provider_id'] ?? '' }}">
                                <option value="">{{ translate('Select_Provider') }}</option>
                            </select>
                            <div class="d-flex gap-2">
                                <button type="submit" class="ld-btn ld-btn-primary flex-grow-1 justify-content-center">
                                    <span class="material-icons">save</span>
                                    {{ translate('Update') }}
                                </button>
                                @if(!empty($temporaryProvider))
                                    <button type="button" class="ld-btn ld-btn-outline text-danger border-danger" id="lead-temporary-provider-clear-btn">
                                        {{ translate('Remove') }}
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    @endif

    @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER)
        <div class="action-card" id="customer-lead-tags-card">
            <div class="action-card__head d-flex justify-content-between align-items-center">
                <span class="action-card__title">
                    <span class="material-icons">local_offer</span>
                    {{ translate('Tags') }}
                </span>
                <span class="d-flex align-items-center gap-1">
                    <button type="button" id="customer-lead-tags-edit-btn" class="panel__edit">{{ translate('Edit') }}</button>
                    <button type="button" id="customer-lead-tags-done-btn" class="panel__edit d-none">{{ translate('Done') }}</button>
                </span>
            </div>
            <div class="action-card__body">
                <div id="customer-lead-tags-pills" class="tag-list mb-1">
                    @foreach($lead->customerLeadTags as $tag)
                        <span class="tag customer-lead-tag-pill" style="background-color: {{ $tag->color ?? '#0d6efd' }};" data-tag-id="{{ $tag->id }}" data-tag-name="{{ $tag->name }}" data-tag-color="{{ $tag->color ?? '#0d6efd' }}">{{ $tag->name }}</span>
                    @endforeach
                </div>
                <div id="customer-lead-tags-edit-block" class="customer-lead-tags-edit-block d-none">
                    <div class="position-relative mb-1">
                        <input type="text" id="customer-lead-tag-autocomplete" class="form-control form-control-sm" placeholder="{{ translate('Type_to_search_or_add_tag') }}" autocomplete="off">
                        <div id="customer-lead-tag-autocomplete-list" class="list-group position-absolute start-0 end-0 mt-1 shadow-sm border rounded overflow-auto d-none" style="max-height:200px;z-index:1050;"></div>
                    </div>
                    <small class="text-muted">{{ translate('Click_a_tag_to_add_or_type_new_and_press_Enter') }}</small>
                </div>
            </div>
        </div>
    @endif

    <div class="action-card lead-card-date {{ !empty($hasPendingFollowup) ? 'action-card--urgent' : '' }}">
        <div class="action-card__head d-flex justify-content-between align-items-center">
            <span class="action-card__title">
                <span class="material-icons">event</span>
                {{ translate('Schedule') }}
            </span>
            <button type="button" class="panel__edit lead-card-edit-btn">{{ translate('Edit') }}</button>
        </div>
        <div class="action-card__body">
            @if(!empty($hasPendingFollowup))
                <div class="schedule-mini__status mb-2">
                    {{ !empty($pendingFollowupIsOverdue) ? translate('Missed_Follow_up') : translate('Follow_up_due') }}
                </div>
            @endif
            <div class="lead-card-view schedule-mini">
                <div class="schedule-mini__item">
                    <span class="schedule-mini__label">{{ translate('Recieved_On') }}</span>
                    <span class="schedule-mini__value">{{ $lead->date_time_of_lead_received?->format('d M Y, h:i A') ?? '—' }}</span>
                </div>
                <div class="schedule-mini__item {{ !empty($hasPendingFollowup) ? 'schedule-mini__item--alert' : '' }}">
                    <span class="schedule-mini__label">{{ translate('Next_Follow_up_Date') }}</span>
                    <span class="schedule-mini__value {{ !empty($hasPendingFollowup) ? (!empty($pendingFollowupIsOverdue) ? 'text-danger' : 'text-warning') : '' }}">
                        {{ $lead->next_followup_at?->format('d M Y, h:i A') ?? '—' }}
                        @if(!empty($hasPendingFollowup))
                            <span class="chip chip--{{ !empty($pendingFollowupIsOverdue) ? 'danger' : 'warning' }} ms-1">{{ !empty($pendingFollowupIsOverdue) ? translate('Missed') : translate('Pending') }}</span>
                        @endif
                    </span>
                </div>
            </div>
            @if(!empty($hasPendingFollowup))
                <button type="button"
                        class="ld-btn ld-btn-warning w-100 justify-content-center mt-2"
                        data-bs-toggle="modal"
                        data-bs-target="#addFollowupModal"
                        data-followup-mode="take">
                    <span class="material-icons">event_available</span>
                    {{ translate('Take_Follow_up') }}
                </button>
            @endif
            <div class="lead-card-edit d-none">
                <form method="POST" action="{{ route('admin.lead.update', $lead->id) }}" class="lead-card-form">
                    @csrf
                    @method('PUT')
                    @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                    <div class="mb-2">
                        <label class="form-label small mb-1">{{ translate('Recieved_On') }}</label>
                        <input type="datetime-local" name="date_time_of_lead_received" class="form-control form-control-sm" value="{{ old('date_time_of_lead_received', $lead->date_time_of_lead_received?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">{{ translate('Followup_On') }}</label>
                        <input type="datetime-local"
                               name="next_followup_at"
                               class="form-control form-control-sm js-followup-future-only"
                               min="{{ $followupScheduleMinAt ?? now()->format('Y-m-d\TH:i') }}"
                               value="{{ old('next_followup_at', $lead->next_followup_at?->format('Y-m-d\TH:i')) }}"
                               @if(!empty($leadOpenStatus)) required @endif>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn--secondary btn-sm lead-card-cancel">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary btn-sm">{{ translate('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</aside>
