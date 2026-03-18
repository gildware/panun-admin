@extends(isset($inModal) && $inModal ? 'leadmanagement::admin.leads.layout-modal' : 'adminmodule::layouts.new-master')

@section('title', translate('Lead_Details'))

@section('content')
    <div class="{{ isset($inModal) && $inModal ? '' : 'main-content' }}">
        <div class="{{ isset($inModal) && $inModal ? '' : 'container-fluid' }}">
        @php
            $leadTypeColorClass = match (($lead->lead_type ?? null)) {
                \Modules\LeadManagement\Entities\Lead::TYPE_INVALID => 'bg-danger',
                \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER => 'bg-success',
                \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER => 'bg-primary',
                \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER => 'bg-info',
                default => 'bg-warning',
            };

            $createdBooking = session('created_booking');
            $createdBookingId = (is_array($createdBooking) && !empty($createdBooking['id'])) ? $createdBooking['id'] : null;
            $createdBookingReadableId = (is_array($createdBooking) && !empty($createdBooking['readable_id'])) ? $createdBooking['readable_id'] : null;
            $createdBookingDetailsUrl = $createdBookingId ? route('admin.booking.details', $createdBookingId) : null;
        @endphp

        @if($createdBookingId)
            <div class="alert alert-success d-flex justify-content-between align-items-center mb-3">
                <div>
                    {{ translate('Booking_has_been_created_for_this_lead') }}
                    @if(!empty($createdBookingReadableId))
                        ({{ translate('Booking_ID') }}: {{ $createdBookingReadableId }})
                    @endif
                </div>
                <a href="{{ $createdBookingDetailsUrl }}" class="btn btn-sm btn--primary" @if(!empty($inModal)) target="_top" @endif>
                    {{ translate('View_Booking_Details') }}
                </a>
            </div>

            <script>
                $(document).ready(function () {
                    try {
                        const bookingDetailsUrl = @json($createdBookingDetailsUrl);
                            Swal.fire({
                            title: @json(translate('Success')),
                            text: @json(translate('Booking_has_been_created_for_this_lead')),
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: @json(translate('View_Booking_Details')),
                            cancelButtonText: @json(translate('Close')),
                        }).then((result) => {
                            if (result.isConfirmed && bookingDetailsUrl) {
                                @if(!empty($inModal))
                                window.top.location.href = bookingDetailsUrl;
                                @else
                                window.location.href = bookingDetailsUrl;
                                @endif
                            }
                        });
                    } catch (e) {
                        // Fallback: keep the inline success alert.
                    }
                });
            </script>
        @endif
        
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap lead-detail-header d-flex justify-content-between flex-wrap align-items-center gap-2 py-3 px-3 rounded mb-3 sticky-top bg-body shadow-sm" style="z-index: 10;">
                        <div class="d-flex align-items-center flex-wrap gap-2 order-1">
                            <h2 class="page-title mb-0">{{ translate('Lead_Details') }}</h2>
                            <span class="badge rounded-pill {{ $leadTypeColorClass }} text-capitalize">
                                {{ \Modules\LeadManagement\Entities\Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type }}
                            </span>
                            <p class="mb-0 text-muted w-100" style="margin-top: 2px;">{{ translate('Lead_ID') }}: #{{ $lead->id }}</p>
                        </div>
                        @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_UNKNOWN)
                            <div class="d-flex flex-nowrap align-items-center justify-content-center gap-2 order-2 lead-header-change-type flex-grow-1 px-2">
                                <button type="button" class="btn btn-outline-danger border border-danger btn-sm btn-lead-type-invalid" data-bs-toggle="modal" data-bs-target="#leadInvalidModal">{{ translate('Mark_as_Invalid_Lead') }}</button>
                                <button type="button" class="btn btn-outline-info border border-info btn-sm btn-lead-type-future" data-bs-toggle="modal" data-bs-target="#leadFutureCustomerModal">{{ translate('Mark_as_Future_Customer_Lead') }}</button>
                                <button type="button" class="btn btn-outline-success border border-success btn-sm btn-lead-type-customer" data-bs-toggle="modal" data-bs-target="#leadCustomerModal">{{ translate('Mark_as_Customer_Lead') }}</button>
                                <button type="button" class="btn btn-outline-primary border border-primary btn-sm btn-lead-type-provider" data-bs-toggle="modal" data-bs-target="#leadProviderModal">{{ translate('Mark_as_Provider_Lead') }}</button>
                            </div>
                        @endif
                        <div class="d-flex align-items-center gap-2 order-3" @if(in_array($lead->lead_type, [\Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER, \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER])) style="margin-right: 50px;" @elseif(!empty($inModal)) style="margin-right: 100px;" @endif>
                            @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER)
                                @php
                                    $currentProviderStatusId = $typeHistory && is_array($typeHistory->data ?? null) ? ($typeHistory->data['provider_lead_status_id'] ?? '') : '';
                                @endphp
                                <div id="provider-header-status-view" class="d-flex align-items-center gap-2">
                                    <span class="badge fs-6" id="provider-header-status-text" style="background-color: {{ $typeHistoryDisplay['header_status_color'] ?? '#0d6efd' }}; color: #fff;">{{ $typeHistoryDisplay['header_status'] ?? '—' }}</span>
                                    <button type="button" id="provider-header-status-edit-btn" class="btn btn-sm btn-link text-primary p-0 d-inline-flex align-items-center" title="{{ translate('Change_Status') }}">
                                        <span class="material-icons" style="font-size: 22px;">edit</span>
                                    </button>
                                </div>
                                <div id="provider-header-status-edit" class="d-flex align-items-center gap-2 d-none">
                                    <select id="provider-header-status-select" class="form-select form-select-sm" style="width: auto; min-width: 140px;">
                                        <option value="">{{ translate('Select_Status') }}</option>
                                        @foreach($providerLeadStatuses as $status)
                                            <option value="{{ $status->id }}" data-base-type="{{ $status->base_type ?? 'pending' }}" {{ $currentProviderStatusId == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="provider-header-status-update-btn" class="btn btn-primary btn-sm d-none">{{ translate('Update') }}</button>
                                    <button type="button" id="provider-header-status-cancel-btn" class="btn btn--secondary btn-sm">{{ translate('Cancel') }}</button>
                                </div>
                            @elseif($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER)
                                @php
                                    $currentCustomerStatusId = $typeHistory && is_array($typeHistory->data ?? null) ? ($typeHistory->data['customer_lead_status_id'] ?? '') : '';
                                    $currentCustomerStatus = $customerLeadStatuses->firstWhere('id', $currentCustomerStatusId);
                                    $isPendingCustomerStatus = !$currentCustomerStatus || $currentCustomerStatus->base_type === 'pending';
                                    $isBookedCustomerStatus = $currentCustomerStatus && in_array($currentCustomerStatus->base_type, ['booked', 'completed'], true);
                                @endphp
                                <div id="customer-header-status-view" class="d-flex align-items-center gap-2">
                                    <span class="badge fs-6" id="customer-header-status-text" style="background-color: {{ $typeHistoryDisplay['header_status_color'] ?? '#0d6efd' }}; color: #fff;">{{ $typeHistoryDisplay['header_status'] ?? '—' }}</span>
                                    @if(!empty($isBookedCustomerStatus) && !empty($leadBooking) && !empty($leadBooking['id']))
                                        <a href="{{ route('admin.booking.details', $leadBooking['id']) }}"
                                           class="badge bg-light text-primary text-decoration-none border"
                                           @if(!empty($inModal)) target="_top" @endif>
                                            {{ translate('Booking_ID') }}: {{ $leadBooking['readable_id'] ?? $leadBooking['id'] }}
                                        </a>
                                    @endif
                                    <button type="button" id="customer-header-status-edit-btn" class="btn btn-sm btn-link text-primary p-0 d-inline-flex align-items-center" title="{{ translate('Change_Status') }}">
                                        <span class="material-icons" style="font-size: 22px;">edit</span>
                                    </button>
                                </div>
                                <div id="customer-header-status-edit" class="d-flex align-items-center gap-2 d-none">
                                    <select id="customer-header-status-select" class="form-select form-select-sm" style="width: auto; min-width: 140px;">
                                        <option value="">{{ translate('Select_Status') }}</option>
                                        @foreach($customerLeadStatuses as $status)
                                            <option value="{{ $status->id }}" data-base-type="{{ $status->base_type ?? 'pending' }}" {{ $currentCustomerStatusId == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="customer-header-status-update-btn" class="btn btn-primary btn-sm d-none">{{ translate('Update') }}</button>
                                    <button type="button" id="customer-header-status-cancel-btn" class="btn btn--secondary btn-sm">{{ translate('Cancel') }}</button>
                                </div>
                            @endif
                            @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER && !empty($isPendingCustomerStatus))
                                <a href="{{ route('admin.booking.create-from-lead', $lead->id) }}" class="btn btn--primary btn-sm">
                                    {{ translate('Create_Booking_for_this_Lead') }}
                                </a>
                            @endif
                            @if(empty($inModal))
                                <a href="{{ route('admin.lead.index') }}" class="btn btn--secondary btn-sm">{{ translate('Back_to_Leads') }}</a>
                            @endif
                        </div>
                    </div>

                    <div class="row gy-3 mb-3">
                        <div class="col-lg-6 d-flex flex-column gap-3">
                            <div class="card lead-card-basic">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h3 class="c1 mb-0">{{ translate('Basic_Details') }}</h3>
                                        <button type="button" class="btn btn--primary btn-sm lead-card-edit-btn">{{ translate('Edit') }}</button>
                                    </div>
                                    <hr>
                                    <div class="lead-card-view">
                                        <div class="d-flex flex-column gap-3">
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                <span class="title-color">{{ translate('Name') }}</span>
                                                <strong>{{ $lead->name }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                <span class="title-color">{{ translate('Phone_Number') }}</span>
                                                <strong>{{ $lead->phone_number }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                <span class="title-color">{{ translate('Added_by') }}</span>
                                                <strong>{{ $addedByName ?? '—' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="lead-card-edit d-none">
                                        <form method="POST" action="{{ route('admin.lead.update', $lead->id) }}" class="lead-card-form">
                                            @csrf
                                            @method('PUT')
                                            @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                                            <div class="d-flex flex-column gap-3">
                                                <div class="p-3 rounded c1-light-bg">
                                                    <label class="title-color d-block mb-2">{{ translate('Name') }}</label>
                                                    <input type="text" name="name" class="form-control" value="{{ old('name', $lead->name) }}" required>
                                                </div>
                                                <div class="p-3 rounded c1-light-bg">
                                                    <label class="title-color d-block mb-2">{{ translate('Phone_Number') }}</label>
                                                    <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $lead->phone_number) }}" required>
                                                </div>
                                                <div class="d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn--secondary btn-sm lead-card-cancel">{{ translate('Cancel') }}</button>
                                                    <button type="submit" class="btn btn--primary btn-sm">{{ translate('Update') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="card lead-card-date">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h3 class="c1 mb-0">{{ translate('Date_Information') }}</h3>
                                        <button type="button" class="btn btn--primary btn-sm lead-card-edit-btn">{{ translate('Edit') }}</button>
                                    </div>
                                    <hr>
                                    <div class="lead-card-view">
                                        <div class="d-flex flex-column gap-3">
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                <span class="title-color">{{ translate('Recieved_On') }}</span>
                                                <strong>{{ $lead->date_time_of_lead_received?->format('d F Y h:i a') ?? '—' }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                <span class="title-color">{{ translate('Followup_On') }}</span>
                                                <strong>{{ $lead->next_followup_at?->format('d F Y h:i a') ?? '—' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="lead-card-edit d-none">
                                        <form method="POST" action="{{ route('admin.lead.update', $lead->id) }}" class="lead-card-form">
                                            @csrf
                                            @method('PUT')
                                            @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                                            <div class="d-flex flex-column gap-3">
                                                <div class="p-3 rounded c1-light-bg">
                                                    <label class="title-color d-block mb-2">{{ translate('Recieved_On') }}</label>
                                                    <input type="datetime-local" name="date_time_of_lead_received" class="form-control" value="{{ old('date_time_of_lead_received', $lead->date_time_of_lead_received?->format('Y-m-d\TH:i')) }}">
                                                </div>
                                                <div class="p-3 rounded c1-light-bg">
                                                    <label class="title-color d-block mb-2">{{ translate('Followup_On') }}</label>
                                                    <input type="datetime-local" name="next_followup_at" class="form-control" value="{{ old('next_followup_at', $lead->next_followup_at?->format('Y-m-d\TH:i')) }}">
                                                </div>
                                                <div class="d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn--secondary btn-sm lead-card-cancel">{{ translate('Cancel') }}</button>
                                                    <button type="submit" class="btn btn--primary btn-sm">{{ translate('Update') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 d-flex flex-column gap-3">
                            <div class="card lead-card-source">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h3 class="c1 mb-0">{{ translate('Source_Information') }}</h3>
                                        <button type="button" class="btn btn--primary btn-sm lead-card-edit-btn">{{ translate('Edit') }}</button>
                                    </div>
                                    <hr>
                                    <div class="lead-card-view">
                                        <div class="d-flex flex-column gap-3">
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                <span class="title-color">{{ translate('Source') }}</span>
                                                <strong>{{ $lead->source?->name ?? '—' }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                <span class="title-color">{{ translate('Ad_Source') }}</span>
                                                <strong>{{ $lead->adSource?->name ?? '—' }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                <span class="title-color">{{ translate('Handled_By') }}</span>
                                                <strong>{{ $handledByName ?? '—' }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                <span class="title-color">{{ translate('Lead_Type') }}</span>
                                                <strong>{{ \Modules\LeadManagement\Entities\Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="lead-card-edit d-none">
                                        <form method="POST" action="{{ route('admin.lead.update', $lead->id) }}" class="lead-card-form">
                                            @csrf
                                            @method('PUT')
                                            @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                                            <div class="d-flex flex-column gap-3">
                                                <div class="p-3 rounded c1-light-bg">
                                                    <label class="title-color d-block mb-2">{{ translate('Source') }}</label>
                                                    <select name="source_id" class="form-select js-select">
                                                        <option value="">{{ translate('Select_Source') }}</option>
                                                        @foreach($sources as $source)
                                                            <option value="{{ $source->id }}" {{ old('source_id', $lead->source_id) == $source->id ? 'selected' : '' }}>{{ $source->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="p-3 rounded c1-light-bg">
                                                    <label class="title-color d-block mb-2">{{ translate('Ad_Source') }}</label>
                                                    <select name="ad_source_id" class="form-select js-select">
                                                        <option value="">{{ translate('Select_Ad_Source') }}</option>
                                                        @foreach($adSources as $adSource)
                                                            <option value="{{ $adSource->id }}" {{ old('ad_source_id', $lead->ad_source_id) == $adSource->id ? 'selected' : '' }}>{{ $adSource->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="p-3 rounded c1-light-bg">
                                                    <label class="title-color d-block mb-2">{{ translate('Handled_By') }}</label>
                                                    <select name="handled_by" class="form-select js-select">
                                                        <option value="">{{ translate('Select_employee') }}</option>
                                                        @foreach($employees as $employee)
                                                            @php $empName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')); $empLabel = $empName ?: $employee->email; @endphp
                                                            <option value="{{ $employee->id }}" {{ old('handled_by', $lead->handled_by) == $employee->id ? 'selected' : '' }}>{{ $empLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="p-3 rounded c1-light-bg">
                                                    <label class="title-color d-block mb-2">{{ translate('Lead_Type') }}</label>
                                                    <select name="lead_type" class="form-select js-select" required>
                                                        @foreach(\Modules\LeadManagement\Entities\Lead::leadTypes() as $value => $label)
                                                            <option value="{{ $value }}" {{ old('lead_type', $lead->lead_type) == $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn--secondary btn-sm lead-card-cancel">{{ translate('Cancel') }}</button>
                                                    <button type="submit" class="btn btn--primary btn-sm">{{ translate('Update') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card lead-card-remarks flex-grow-1">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h3 class="c1 mb-0">{{ translate('Initial_Remarks') }}</h3>
                                        <button type="button" class="btn btn--primary btn-sm lead-card-edit-btn">{{ translate('Edit') }}</button>
                                    </div>
                                    <hr>
                                    <div class="lead-card-view">
                                        <div class="border rounded p-3 bg-light-subtle">
                                            <p class="mb-0">{{ $lead->remarks ?: '—' }}</p>
                                        </div>
                                    </div>
                                    <div class="lead-card-edit d-none">
                                        <form method="POST" action="{{ route('admin.lead.update', $lead->id) }}" class="lead-card-form">
                                            @csrf
                                            @method('PUT')
                                            @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                                            <div class="p-3 rounded c1-light-bg">
                                                <textarea name="remarks" class="form-control" rows="3" placeholder="{{ translate('Remarks') }}">{{ old('remarks', $lead->remarks) }}</textarea>
                                            </div>
                                            <div class="d-flex justify-content-end gap-2 mt-3">
                                                <button type="button" class="btn btn--secondary btn-sm lead-card-cancel">{{ translate('Cancel') }}</button>
                                                <button type="submit" class="btn btn--primary btn-sm">{{ translate('Update') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER)
                        @php
                            $hasProviderData = isset($typeHistoryDisplay['basic']) && isset($typeHistoryDisplay['service']);
                        @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                            <h3 class="c1 mb-0">{{ translate('Provider_Lead_Information') }}</h3>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($hasProviderData && !empty($typeHistoryDisplay['header_status'] ?? null))
                                                    <span class="badge fs-6" style="background-color: {{ $typeHistoryDisplay['header_status_color'] ?? '#0d6efd' }}; color: #fff;">{{ $typeHistoryDisplay['header_status'] }}</span>
                                                @endif
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leadProviderModal" title="{{ $hasProviderData ? translate('Edit') : translate('Add_Details') }}">
                                                    {{ $hasProviderData ? translate('Edit') : translate('Add_Details') }}
                                                </button>
                                            </div>
                                        </div>
                                        <hr>
                                        @if($hasProviderData)
                                            <div class="rounded border p-3">
                                                <div class="d-flex flex-column gap-3">
                                                    <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                        <span class="title-color">{{ translate('Name') }}</span>
                                                        <strong class="text-end">{{ $lead->name ?? '—' }}</strong>
                                                    </div>
                                                    @foreach($typeHistoryDisplay['basic'] as $row)
                                                        <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                            <span class="title-color">{{ $row['label'] }}</span>
                                                            <strong class="text-end">{{ $row['value'] }}</strong>
                                                        </div>
                                                    @endforeach
                                                    @foreach($typeHistoryDisplay['service'] as $row)
                                                        <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                                            <span class="title-color">{{ $row['label'] }}</span>
                                                            <strong class="text-end">{{ $row['value'] }}</strong>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <p class="mb-0 text-muted">{{ translate('No_provider_information_added_yet') }}</p>
                                            <p class="mb-0 small text-muted">{{ translate('Click_Add_Details_to_fill_in_provider_information') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card h-100" id="provider-checklist-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                            <h3 class="c1 mb-0">{{ translate('Provider_Checklist') }}</h3>
                                            @if($providerChecklistItems->isNotEmpty())
                                                <div class="d-flex align-items-center gap-2 provider-checklist-actions">
                                                    <button type="button" id="provider-checklist-edit-btn" class="btn btn-outline-primary btn-sm">
                                                        {{ translate('Edit') }}
                                                    </button>
                                                    <span class="provider-checklist-edit-only d-none">
                                                        <button type="button" id="provider-checklist-update-btn" class="btn btn-primary btn-sm" disabled>
                                                            {{ translate('Update') }}
                                                        </button>
                                                        <button type="button" id="provider-checklist-cancel-btn" class="btn btn--secondary btn-sm">
                                                            {{ translate('Cancel') }}
                                                        </button>
                                                        </span>
                                                @endif
                                            </div>
                                            </div>
                                        <hr>
                                        @if($providerChecklistItems->isNotEmpty())
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead>
                                                    <tr>
                                                        <th>{{ translate('Item') }}</th>
                                                        <th>{{ translate('Description') }}</th>
                                                        <th class="text-center" style="width: 120px;">{{ translate('Status') }}</th>
                                                        <th class="text-center" style="width: 100px;">{{ translate('Done') }}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($providerChecklistItems as $item)
                                                        @php $isDone = ($providerChecklistDoneMap[$item->id] ?? false); @endphp
                                                        <tr data-item-id="{{ $item->id }}" data-initial-done="{{ $isDone ? '1' : '0' }}" data-is-done="{{ $isDone ? '1' : '0' }}">
                                                            <td>{{ $item->name }}</td>
                                                            <td>{{ $item->description ?? '—' }}</td>
                                                            <td class="text-center provider-checklist-status">
                                                                @if($isDone)
                                                                    <span class="badge bg-success">{{ translate('Done') }}</span>
                                                                @else
                                                                    <span class="badge bg-secondary">{{ translate('Pending') }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center provider-checklist-action">
                                                                <button type="button" class="btn btn-sm provider-checklist-toggle" disabled
                                                                        data-item-id="{{ $item->id }}"
                                                                        title="{{ translate('Edit_to_toggle') }}">
                                                                    @if($isDone)
                                                                        <span class="material-icons provider-checklist-icon" style="font-size: 20px;">check_box</span>
                                                                    @else
                                                                        <span class="material-icons provider-checklist-icon" style="font-size: 20px;">check_box_outline_blank</span>
                                                                    @endif
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="mb-0 text-muted">{{ translate('No_checklist_items_configured') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER)
                        @php $hasCustomerData = $typeHistory && !empty($typeHistoryDisplay); @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-lg-8">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                            <h3 class="c1 mb-0">{{ translate('Customer_Lead_Information') }}</h3>
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leadCustomerModal" title="{{ $hasCustomerData ? translate('Edit') : translate('Add_Details') }}">
                                                {{ $hasCustomerData ? translate('Edit') : translate('Add_Details') }}
                                            </button>
                                        </div>
                                        <hr>
                                        @if($hasCustomerData)
                                            <div class="row g-2">
                                                @foreach(isset($typeHistoryDisplay['rows']) ? $typeHistoryDisplay['rows'] : $typeHistoryDisplay as $row)
                                                    @if(is_array($row) && isset($row['label']))
                                                    <div class="col-md-6">
                                                        <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg h-100">
                                                            <span class="title-color">{{ $row['label'] }}</span>
                                                            <strong class="text-end ms-2">{{ $row['value'] }}</strong>
                                                        </div>
                                                    </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="mb-0 text-muted">{{ translate('No_customer_information_added_yet') }}</p>
                                            <p class="mb-0 small text-muted">{{ translate('Click_Add_Details_to_fill_in_customer_information') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card h-100" id="customer-lead-tags-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                            <h3 class="c1 mb-0">{{ translate('Tags') }}</h3>
                                            <button type="button" id="customer-lead-tags-edit-btn" class="btn btn-outline-primary btn-sm">{{ translate('Edit') }}</button>
                                            <button type="button" id="customer-lead-tags-done-btn" class="btn btn--primary btn-sm d-none">{{ translate('Done') }}</button>
                                        </div>
                                        <hr>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <div id="customer-lead-tags-pills" class="d-flex flex-wrap gap-1 align-items-center mb-2">
                                                @foreach($lead->customerLeadTags as $tag)
                                                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 px-2 py-1 customer-lead-tag-pill" style="background-color: {{ $tag->color ?? '#0d6efd' }}; color: #fff;" data-tag-id="{{ $tag->id }}" data-tag-name="{{ $tag->name }}" data-tag-color="{{ $tag->color ?? '#0d6efd' }}">{{ $tag->name }}</span>
                                                @endforeach
                                            </div>
                                            <div id="customer-lead-tags-edit-block" class="customer-lead-tags-edit-block d-none w-100">
                                                <div class="position-relative mb-1">
                                                    <input type="text" id="customer-lead-tag-autocomplete" class="form-control form-control-sm" placeholder="{{ translate('Type_to_search_or_add_tag') }}" autocomplete="off">
                                                    <div id="customer-lead-tag-autocomplete-list" class="list-group position-absolute start-0 end-0 mt-1 shadow-sm border rounded overflow-auto d-none" style="max-height: 200px; z-index: 1050;"></div>
                                                </div>
                                                <small class="text-muted">{{ translate('Click_a_tag_to_add_or_type_new_and_press_Enter') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($typeHistory && !empty($typeHistoryDisplay))
                        @php
                            $typeCardTitles = [
                                \Modules\LeadManagement\Entities\Lead::TYPE_INVALID => translate('Invalid_Lead_Information'),
                                \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER => translate('Future_Customer_Information'),
                                \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER => translate('Provider_Lead_Information'),
                            ];
                            $typeCardTitle = $typeCardTitles[$lead->lead_type] ?? translate('Lead_Type_Information');
                        @endphp
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <h3 class="c1 mb-0">{{ $typeCardTitle }}</h3>
                                </div>
                                <hr>
                                <div class="d-flex flex-column gap-3">
                                    @foreach($typeHistoryDisplay as $row)
                                        <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                            <span class="title-color">{{ $row['label'] }}</span>
                                            <strong class="text-end">{{ $row['value'] }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row gy-3 mb-3">
                        <div class="col-lg-6">
                            <div class="card h-100 lead-detail-fixed-card lead-detail-history-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
                                        <h3 class="c1 mb-0">{{ translate('Follow_up_History') }}</h3>
                                        <button type="button"
                                                class="btn btn--primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#addFollowupModal">
                                            {{ translate('Add_Follow_up') }}
                                        </button>
                                    </div>
                                    <hr class="flex-shrink-0">
                                    <div class="overflow-auto lead-detail-scroll-content flex-grow-1 min-h-0">
                                        @if($lead->followups->isEmpty())
                                            <p class="mb-0 text-muted">{{ translate('No_follow_ups_yet') }}</p>
                                        @else
                                            <div class="lead-followup-timeline">
                                                @foreach($lead->followups as $followup)
                                                    <div class="border-start border-3 border-primary ps-3 mb-4">
                                                        <div class="mb-1">
                                                            <span class="fw-semibold">{{ translate('Taken_on') }}:</span>
                                                            {{ $followup->followup_at?->format('d F Y h:i a') ?? '—' }}
                                                            <span class="text-muted">{{ translate('By') }}:</span>
                                                            @if($followup->createdBy)
                                                                @php
                                                                    $fuUser = $followup->createdBy;
                                                                    $fuName = trim(($fuUser->first_name ?? '') . ' ' . ($fuUser->last_name ?? ''));
                                                                @endphp
                                                                {{ $fuName ?: $fuUser->email }}
                                                            @else
                                                                —
                                                            @endif
                                                        </div>
                                                        <div class="mb-1">
                                                            <span class="fw-semibold">{{ translate('Next_Follow_up_Date') }}:</span>
                                                            {{ $followup->next_followup_at?->format('d F Y h:i a') ?? '—' }}
                                                        </div>
                                                        <div class="mb-0">
                                                            <span class="fw-semibold">{{ translate('Remarks') }}</span> => {{ $followup->remarks ?: '—' }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card h-100 lead-detail-fixed-card lead-detail-history-card" id="lead-change-history-card">
                                <div class="card-body d-flex flex-column">
                                    <h3 class="c1 mb-3 flex-shrink-0">{{ translate('Change_History') }}</h3>
                                    <hr class="flex-shrink-0">
                                    <div class="overflow-auto lead-detail-scroll-content flex-grow-1 min-h-0">
                                        @if(isset($changeLogs) && $changeLogs->isNotEmpty())
                                            <div class="lead-change-timeline">
                                                @foreach($changeLogs as $log)
                                                    <div class="border-start border-3 border-primary ps-3 mb-4">
                                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                            <span class="fw-semibold">{{ $log->created_at?->format('d F Y h:i a') ?? '—' }}</span>
                                                            <span class="text-muted">
                                                                {{ translate('Edited_by') }}:
                                                                @if($log->changedByUser)
                                                                    @php
                                                                        $cb = $log->changedByUser;
                                                                        $cbName = trim(($cb->first_name ?? '') . ' ' . ($cb->last_name ?? ''));
                                                                    @endphp
                                                                    {{ $cbName ?: $cb->email }}
                                                                @else
                                                                    —
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <ul class="mb-0 ps-3">
                                                            @foreach($log->changes ?? [] as $fieldKey => $change)
                                                                <li class="mb-1">
                                                                    <strong>{{ translate($change['label'] ?? $fieldKey) }}</strong>:
                                                                    {{ translate('Changed_from') }} <span class="text-muted">{{ $change['old'] ?? '—' }}</span>
                                                                    {{ translate('Changed_to') }} <span>{{ $change['new'] ?? '—' }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="mb-0 text-muted">{{ translate('No_changes_recorded_yet') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h3 class="c1 mb-3 text-danger">{{ translate('Delete_Lead') }}</h3>
                            <hr>
                            <div class="text-center">
                                <p class="text-muted mb-3">
                                    {{ translate('This_action_will_permanently_remove_the_lead_and_its_related_data.') }}
                                </p>
                                <button type="button"
                                        class="btn btn-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteLeadModal">
                                    {{ translate('Delete_Lead') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="leadInvalidModal" tabindex="-1" aria-labelledby="leadInvalidModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title" id="leadInvalidModalLabel">{{ translate('Mark_as_Invalid_Lead') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.lead.type.update', $lead->id) }}">
                                    @csrf
                                    @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                                    <input type="hidden" name="lead_type" value="invalid">
                                    <div class="modal-body pt-0">
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Reason') }}</label>
                                            <select name="invalid_reason_id" class="form-select" required>
                                                <option value="">{{ translate('Select_Reason') }}</option>
                                                @foreach($invalidReasons as $reason)
                                                    <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">{{ translate('Remarks') }}</label>
                                            <textarea name="invalid_remarks" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                                        <button type="button"
                                                class="btn btn--secondary"
                                                data-bs-dismiss="modal">
                                            {{ translate('Cancel') }}
                                        </button>
                                        <button type="submit" class="btn btn-danger">
                                            {{ translate('Mark_as_Invalid_Lead') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="leadFutureCustomerModal" tabindex="-1" aria-labelledby="leadFutureCustomerModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title" id="leadFutureCustomerModalLabel">{{ translate('Mark_as_Future_Customer_Lead') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.lead.type.update', $lead->id) }}">
                                    @csrf
                                    @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                                    <input type="hidden" name="lead_type" value="future_customer">
                                    <div class="modal-body pt-0">
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Reason') }}</label>
                                            <select name="future_customer_reason_id" class="form-select" required>
                                                <option value="">{{ translate('Select_Reason') }}</option>
                                                @foreach($futureCustomerReasons as $reason)
                                                    <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">{{ translate('Remarks') }}</label>
                                            <textarea name="future_customer_remarks" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                                        <button type="button"
                                                class="btn btn--secondary"
                                                data-bs-dismiss="modal">
                                            {{ translate('Cancel') }}
                                        </button>
                                        <button type="submit" class="btn btn-info text-white">
                                            {{ translate('Mark_as_Future_Customer_Lead') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @php
                        $customerEditData = ($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER && $typeHistory && is_array($typeHistory->data ?? null)) ? $typeHistory->data : [];
                        $estAt = $customerEditData['estimated_service_at'] ?? null;
                        if ($estAt && is_string($estAt)) {
                            try {
                                $estAt = \Carbon\Carbon::parse($estAt)->format('Y-m-d\TH:i');
                            } catch (\Throwable $e) {
                                $estAt = null;
                            }
                        }
                    @endphp
                    <div class="modal fade" id="leadCustomerModal" tabindex="-1" aria-labelledby="leadCustomerModalLabel" aria-hidden="true"
                         data-edit-zone="{{ $customerEditData['zone_id'] ?? '' }}"
                         data-edit-category="{{ $customerEditData['service_category'] ?? '' }}"
                         data-edit-subcategory="{{ $customerEditData['service_subcategory'] ?? '' }}"
                         data-edit-service="{{ $customerEditData['service_name'] ?? '' }}"
                         data-edit-variant="{{ $customerEditData['variant_key'] ?? '' }}">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title" id="leadCustomerModalLabel">
                                        {{ $lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER ? translate('Edit_Customer_Lead') : translate('Mark_as_Customer_Lead') }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.lead.type.update', $lead->id) }}">
                                    @csrf
                                    @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                                    <input type="hidden" name="lead_type" value="customer">
                                    @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER)
                                        <input type="hidden" name="update_customer" value="1">
                                    @endif
                                    <div class="modal-body pt-0">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Customer_Lead_Status') }}</label>
                                                <select name="customer_lead_status_id" class="form-select">
                                                    <option value="">{{ translate('Select_Status') }}</option>
                                                    @foreach($customerLeadStatuses as $status)
                                                        <option value="{{ $status->id }}" {{ ($customerEditData['customer_lead_status_id'] ?? '') == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Zone') }}</label>
                                                <select name="zone_id" id="lead-zone-select" class="form-control js-select">
                                                    <option value="">{{ translate('Select_Zone') }}</option>
                                                    @foreach($zones as $zone)
                                                        <option value="{{ $zone->id }}" {{ ($customerEditData['zone_id'] ?? '') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Category') }}</label>
                                                <select name="service_category" id="lead-category-select" class="form-control js-select" disabled>
                                                    <option value="">{{ translate('Select_Category') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Sub_Category') }}</label>
                                                <select name="service_subcategory" id="lead-subcategory-select" class="form-control js-select" disabled>
                                                    <option value="">{{ translate('Select_Sub_Category') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Service') }} ({{ translate('optional') }})</label>
                                                <select name="service_name" id="lead-service-select" class="form-control js-select" disabled>
                                                    <option value="">{{ translate('Select_Service_or_leave_for_custom') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Select_Service_Variant') }}</label>
                                                <select name="variant_key" id="lead-variant-select" class="form-control js-select" disabled>
                                                    <option value="">{{ translate('Select_Service_Variant') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Estimated_Date_Time_of_Service') }}</label>
                                                <input type="datetime-local" name="estimated_service_at" class="form-control" value="{{ $estAt }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Service_Additional_Details_(Optional)') }}</label>
                                                <textarea name="service_description" class="form-control" rows="3" placeholder="{{ translate('Add_any_extra_information_or_requirements_for_this_service') }}">{{ $customerEditData['service_description'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                                        <button type="button"
                                                class="btn btn--secondary"
                                                data-bs-dismiss="modal">
                                            {{ translate('Cancel') }}
                                        </button>
                                        <button type="submit" class="btn btn-success">
                                            {{ $lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER ? translate('Update') : translate('Mark_as_Customer_Lead') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @php
                        $providerEditData = ($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER && $typeHistory && is_array($typeHistory->data ?? null)) ? $typeHistory->data : [];
                    @endphp
                    <div class="modal fade" id="leadProviderModal" tabindex="-1" aria-labelledby="leadProviderModalLabel" aria-hidden="true"
                         data-edit-zone="{{ $providerEditData['zone_id'] ?? '' }}"
                         data-edit-category="{{ $providerEditData['provider_service_category'] ?? '' }}"
                         data-edit-subcategory="{{ $providerEditData['provider_service_subcategory'] ?? '' }}">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title" id="leadProviderModalLabel">
                                        {{ $lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER ? translate('Edit_Provider_Lead') : translate('Mark_as_Provider_Lead') }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.lead.type.update', $lead->id) }}">
                                    @csrf
                                    @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                                    <input type="hidden" name="lead_type" value="provider">
                                    @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER)
                                        <input type="hidden" name="update_provider" value="1">
                                    @endif
                                    <div class="modal-body pt-0">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('District') }}</label>
                                                <select name="district_id" class="form-select">
                                                    <option value="">{{ translate('Select_District') }}</option>
                                                    @foreach($districts as $district)
                                                        <option value="{{ $district->id }}" {{ ($providerEditData['district_id'] ?? '') == $district->id ? 'selected' : '' }}>{{ $district->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Provider_Lead_Status') }}</label>
                                                <select name="provider_lead_status_id" class="form-select">
                                                    <option value="">{{ translate('Select_Status') }}</option>
                                                    @foreach($providerLeadStatuses as $status)
                                                        <option value="{{ $status->id }}" {{ ($providerEditData['provider_lead_status_id'] ?? '') == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">{{ translate('Full_Address') }}</label>
                                                <textarea name="full_address" class="form-control" rows="2" placeholder="{{ translate('Full_Address') }}">{{ $providerEditData['full_address'] ?? '' }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">{{ translate('Service_Areas') }}</label>
                                                <textarea name="service_areas" class="form-control" rows="2" placeholder="{{ translate('Service_Areas') }}">{{ $providerEditData['service_areas'] ?? '' }}</textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Zone') }}</label>
                                                <select name="zone_id" id="provider-zone-select" class="form-select js-select">
                                                    <option value="">{{ translate('Select_Zone') }}</option>
                                                    @foreach($zones as $zone)
                                                        <option value="{{ $zone->id }}" {{ ($providerEditData['zone_id'] ?? '') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Service_Category') }}</label>
                                                <select name="provider_service_category" id="provider-category-select" class="form-select js-select" disabled>
                                                    <option value="">{{ translate('Select_Category') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Sub_Category') }}</label>
                                                <select name="provider_service_subcategory" id="provider-subcategory-select" class="form-select js-select" disabled>
                                                    <option value="">{{ translate('Select_Sub_Category') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">{{ translate('Service_Details') }}</label>
                                                <textarea name="provider_service_details" class="form-control" rows="3" placeholder="{{ translate('Service_Details') }}">{{ $providerEditData['provider_service_details'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                                        <button type="button"
                                                class="btn btn--secondary"
                                                data-bs-dismiss="modal">
                                            {{ translate('Cancel') }}
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            {{ $lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER ? translate('Update') : translate('Mark_as_Provider_Lead') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="addFollowupModal" tabindex="-1" aria-labelledby="addFollowupModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title" id="addFollowupModalLabel">{{ translate('Add_Follow_up') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.lead.followups.store', $lead->id) }}">
                                    @csrf
                                    @if(!empty($inModal))
                                        <input type="hidden" name="in_modal" value="1">
                                    @endif
                                    <div class="modal-body pt-0">
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Date_Time') }}</label>
                                            <input type="datetime-local"
                                                   name="followup_at"
                                                   class="form-control"
                                                   value="{{ now()->format('Y-m-d\TH:i') }}"
                                                   required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Remarks') }}</label>
                                            <textarea name="remarks" class="form-control" rows="3" placeholder="{{ translate('Add_remarks_from_follow_up') }}"></textarea>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="no-more-followup-toggle"
                                                   name="no_more_followup"
                                                   value="1">
                                            <label class="form-check-label" for="no-more-followup-toggle">
                                                No need for another follow-up
                                            </label>
                                        </div>

                                        <div class="mb-0" id="next-followup-group">
                                            <label class="form-label">{{ translate('Next_Follow_up_Date') }}</label>
                                            <input type="datetime-local"
                                                   name="next_followup_at"
                                                   id="next-followup-input"
                                                   class="form-control"
                                                   data-default="{{ $lead->next_followup_at?->format('Y-m-d\TH:i') ?? now()->addDay()->format('Y-m-d\TH:i') }}"
                                                   value="{{ $lead->next_followup_at?->format('Y-m-d\TH:i') ?? now()->addDay()->format('Y-m-d\TH:i') }}">
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                                        <button type="button"
                                                class="btn btn--secondary"
                                                data-bs-dismiss="modal">
                                            {{ translate('Cancel') }}
                                        </button>
                                        <button type="submit" class="btn btn--primary">
                                            {{ translate('Save_changes') }}
                                        </button>
                                    </div>
                                </form>
                                @push('script')
                                    <script>
                                        (function () {
                                            const toggle = document.getElementById('no-more-followup-toggle');
                                            const group = document.getElementById('next-followup-group');
                                            const input = document.getElementById('next-followup-input');

                                            if (!toggle || !group || !input) {
                                                return;
                                            }

                                            function handleToggle() {
                                                if (toggle.checked) {
                                                    group.classList.add('d-none');
                                                    input.disabled = true;
                                                    input.value = '';
                                                } else {
                                                    group.classList.remove('d-none');
                                                    input.disabled = false;
                                                    if (!input.value && input.dataset.default) {
                                                        input.value = input.dataset.default;
                                                    }
                                                }
                                            }

                                            handleToggle();
                                            toggle.addEventListener('change', handleToggle);
                                        })();
                                    </script>
                                @endpush
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="deleteLeadModal" tabindex="-1" aria-labelledby="deleteLeadModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title text-danger" id="deleteLeadModalLabel">{{ translate('Are_you_sure?') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                </div>
                                <div class="modal-body pt-0">
                                    <p class="mb-0 text-muted">
                                        {{ translate('This_action_will_permanently_remove_the_lead_and_its_related_data.') }}
                                    </p>
                                </div>
                                <div class="modal-footer border-0 d-flex justify-content-center gap-3 pb-4">
                                    <button type="button"
                                            class="btn btn--secondary"
                                            data-bs-dismiss="modal">
                                        {{ translate('Cancel') }}
                                    </button>
                                    <form method="POST" action="{{ route('admin.lead.destroy', $lead->id) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            {{ translate('Delete_Lead') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER)
    <div class="modal fade" id="customerCancelModal" tabindex="-1" aria-labelledby="customerCancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="customerCancelModalLabel">{{ translate('Customer_cancellation_reasons') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Customer_cancellation_reasons') }}</label>
                        <select id="customer-cancel-reason-id" class="form-select">
                            <option value="">{{ translate('Select') }}</option>
                            @foreach($cancellationReasons as $reason)
                                <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ translate('Remarks') }} ({{ translate('Optional') }})</label>
                        <textarea id="customer-cancel-remarks" class="form-control" rows="3" placeholder="{{ translate('Enter_cancellation_remarks') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="button" class="btn btn--primary" id="customer-cancel-save-btn">
                        {{ translate('Save_changes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER)
    <div class="modal fade" id="providerCancelModal" tabindex="-1" aria-labelledby="providerCancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="providerCancelModalLabel">{{ translate('Provider_cancellation_reasons') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Provider_cancellation_reasons') }}</label>
                        <select id="provider-cancel-reason-id" class="form-select">
                            <option value="">{{ translate('Select') }}</option>
                            @foreach($providerCancellationReasons as $reason)
                                <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ translate('Remarks') }} ({{ translate('Optional') }})</label>
                        <textarea id="provider-cancel-remarks" class="form-control" rows="3" placeholder="{{ translate('Enter_cancellation_remarks') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="button" class="btn btn--primary" id="provider-cancel-save-btn">
                        {{ translate('Save_changes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@push('css_or_js')
    <style>
        .btn-lead-type-invalid:hover:not(:disabled) {
            background-color: #dc3545; /* Bootstrap danger */
            color: #fff;
        }

        .btn-lead-type-customer:hover:not(:disabled) {
            background-color: #198754; /* Bootstrap success */
            color: #fff;
        }

        .btn-lead-type-provider:hover:not(:disabled) {
            background-color: #0d6efd; /* Bootstrap primary */
            color: #fff;
        }

        .btn-lead-type-future:hover:not(:disabled) {
            background-color: #0dcaf0; /* Bootstrap info (light blue) */
            color: #fff;
        }

        .lead-detail-fixed-card.lead-detail-history-card {
            min-height: 480px;
            height: 480px;
        }
        .lead-detail-fixed-card .card-body {
            overflow: hidden;
        }
        .lead-detail-fixed-card .lead-detail-scroll-content {
            flex: 1 1 0;
            min-height: 0;
            overflow: auto;
        }
    </style>
@endpush

@push('script')
    <script>
        (function ($) {
            "use strict";
            $(document).on('click', '.lead-card-edit-btn', function () {
                var $card = $(this).closest('.card');
                $card.find('.lead-card-view').addClass('d-none');
                $card.find('.lead-card-edit').removeClass('d-none');
                $card.find('.lead-card-edit .js-select').each(function () {
                    if ($(this).data('select2')) $(this).select2('destroy');
                    $(this).select2({ width: '100%' });
                });
            });
            $(document).on('click', '.lead-card-cancel', function () {
                var $card = $(this).closest('.card');
                $card.find('.lead-card-edit').addClass('d-none');
                $card.find('.lead-card-view').removeClass('d-none');
                $card.find('.lead-card-edit .js-select').each(function () {
                    if ($(this).data('select2')) $(this).select2('destroy');
                });
            });
        })(jQuery);
    </script>
    <script>
        (function () {
            "use strict";
            function getCustomerSelects() {
                var $m = $('#leadCustomerModal');
                return {
                    zone: $m.find('[name="zone_id"]'),
                    category: $m.find('[name="service_category"]'),
                    subcategory: $m.find('[name="service_subcategory"]'),
                    service: $m.find('[name="service_name"]'),
                    variant: $m.find('[name="variant_key"]')
                };
            }

            function resetCategorySubcategoryService(s) {
                s = s || getCustomerSelects();
                s.category.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Category') }}', '', true, true)).trigger('change');
                s.subcategory.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true)).trigger('change');
                s.service.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service') }}', '', true, true)).trigger('change');
                s.variant.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
            }

            function loadCategories(onLoaded) {
                var s = getCustomerSelects();
                var zoneId = (s.zone.val() || '').toString().trim();
                if (!zoneId) {
                    resetCategorySubcategoryService(s);
                    if (onLoaded) onLoaded();
                    return;
                }
                s.category.prop('disabled', false).empty()
                    .append(new Option('{{ translate('Loading...') }}', '', true, true)).trigger('change');
                $.get('{{ route('admin.booking.service.ajax-get-categories') }}', { zone_id: zoneId }, function (res) {
                    s.category.empty().append(new Option('{{ translate('Select_Category') }}', '', true, true));
                    (res.content || []).forEach(function (c) {
                        s.category.append(new Option(c.name, c.id, false, false));
                    });
                    if (onLoaded) onLoaded(); else s.category.trigger('change');
                });
                s.subcategory.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true)).trigger('change');
                s.service.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service') }}', '', true, true)).trigger('change');
                s.variant.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
            }

            function loadSubcategories(onLoaded) {
                var s = getCustomerSelects();
                var categoryId = (s.category.val() || '').toString().trim();
                if (!categoryId) {
                    s.subcategory.prop('disabled', true).empty()
                        .append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true)).trigger('change');
                    s.service.prop('disabled', true).empty()
                        .append(new Option('{{ translate('Select_Service') }}', '', true, true)).trigger('change');
                    s.variant.prop('disabled', true).empty()
                        .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
                    if (onLoaded) onLoaded();
                    return;
                }
                s.subcategory.prop('disabled', false).empty()
                    .append(new Option('{{ translate('Loading...') }}', '', true, true)).trigger('change');
                $.get('{{ route('admin.booking.service.ajax-get-subcategories') }}', { category_id: categoryId }, function (res) {
                    s.subcategory.empty().append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true));
                    (res.content || []).forEach(function (c) {
                        s.subcategory.append(new Option(c.name, c.id, false, false));
                    });
                    if (onLoaded) onLoaded(); else s.subcategory.trigger('change');
                });
                s.service.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service') }}', '', true, true)).trigger('change');
                s.variant.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
            }

            function loadServices(onLoaded) {
                var s = getCustomerSelects();
                var subCategoryId = (s.subcategory.val() || '').toString().trim();
                var zoneId = (s.zone.val() || '').toString().trim();
                if (!subCategoryId || !zoneId) {
                    s.service.prop('disabled', true).empty()
                        .append(new Option('{{ translate('Select_Service') }}', '', true, true)).trigger('change');
                    s.variant.prop('disabled', true).empty()
                        .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
                    if (onLoaded) onLoaded();
                    return;
                }
                s.service.prop('disabled', false).empty()
                    .append(new Option('{{ translate('Loading...') }}', '', true, true)).trigger('change');
                $.get('{{ route('admin.booking.service.ajax-get-services') }}', { sub_category_id: subCategoryId, zone_id: zoneId }, function (res) {
                    s.service.empty()
                        .append(new Option('{{ translate('Select_Service_or_leave_for_custom') }}', '', true, true));
                    (res.content || []).forEach(function (c) {
                        s.service.append(new Option(c.name, c.id, false, false));
                    });
                    if (onLoaded) onLoaded(); else s.service.trigger('change');
                });
            }

            function loadVariants(onLoaded) {
                var s = getCustomerSelects();
                var serviceId = (s.service.val() || '').toString().trim();
                var zoneId = (s.zone.val() || '').toString().trim();
                if (!serviceId || !zoneId) {
                    s.variant.prop('disabled', true).empty()
                        .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
                    if (onLoaded) onLoaded();
                    return;
                }
                s.variant.prop('disabled', false).empty()
                    .append(new Option('{{ translate('Loading...') }}', '', true, true)).trigger('change');
                $.get('{{ route('admin.booking.service.ajax-get-variant') }}', { service_id: serviceId, zone_id: zoneId }, function (response) {
                    s.variant.empty()
                        .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true));
                    if (response.content && Array.isArray(response.content) && response.content.length > 0) {
                        response.content.forEach(function (variation) {
                            var label = variation.variant + ' — ' + variation.price;
                            s.variant.append(new Option(label, variation.variant_key, false, false));
                        });
                    }
                    if (onLoaded) onLoaded(); else s.variant.trigger('change');
                }).fail(function () {
                    s.variant.empty()
                        .append(new Option('{{ translate('Failed_to_load') }}', '', true, true)).trigger('change');
                    if (onLoaded) onLoaded();
                });
            }

            $('#leadCustomerModal').on('show.bs.modal', function () {
                var $modal = $(this);
                var s = getCustomerSelects();
                var editZone = $modal.data('editZone');
                var editCategory = $modal.data('editCategory');
                var editSubcategory = $modal.data('editSubcategory');
                var editService = $modal.data('editService');
                var editVariant = $modal.data('editVariant');
                if (editZone) {
                    window._customerModalPrefilling = true;
                    s.zone.val(String(editZone)).trigger('change');
                    loadCategories(function () {
                        if (editCategory) { s.category.val(editCategory).trigger('change'); }
                        loadSubcategories(function () {
                            if (editSubcategory) { s.subcategory.val(editSubcategory).trigger('change'); }
                            loadServices(function () {
                                if (editService) { s.service.val(editService).trigger('change'); }
                                loadVariants(function () {
                                    if (editVariant) { s.variant.val(editVariant).trigger('change'); }
                                    window._customerModalPrefilling = false;
                                });
                            });
                        });
                    });
                } else {
                    resetCategorySubcategoryService(s);
                }
            });
            $('#leadCustomerModal').on('hidden.bs.modal', function () {
                window._customerModalPrefilling = false;
            });

            $('#leadCustomerModal').on('change', '[name="zone_id"]', function () {
                if (window._customerModalPrefilling) return;
                loadCategories();
            });
            $('#leadCustomerModal').on('change', '[name="service_category"]', function () {
                if (window._customerModalPrefilling) return;
                loadSubcategories();
            });
            $('#leadCustomerModal').on('change', '[name="service_subcategory"]', function () {
                if (window._customerModalPrefilling) return;
                loadServices();
            });
            $('#leadCustomerModal').on('change', '[name="service_name"]', function () {
                if (window._customerModalPrefilling) return;
                loadVariants();
            });
        })();

        (function () {
            var $view = $('#provider-header-status-view');
            var $edit = $('#provider-header-status-edit');
            var $text = $('#provider-header-status-text');
            var $select = $('#provider-header-status-select');
            var $updateBtn = $('#provider-header-status-update-btn');
            var $cancelBtn = $('#provider-header-status-cancel-btn');
            var $cancelModal = $('#providerCancelModal');
            var $cancelReason = $('#provider-cancel-reason-id');
            var $cancelRemarks = $('#provider-cancel-remarks');
            var $cancelSaveBtn = $('#provider-cancel-save-btn');
            if (!$view.length) return;
            var initialValue = '';
            var statusUpdateUrl = '{{ route('admin.lead.provider-status.update', $lead->id) }}';
            var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();

            $('#provider-header-status-edit-btn').on('click', function () {
                initialValue = $select.val() || '';
                $updateBtn.addClass('d-none');
                $view.addClass('d-none');
                $edit.removeClass('d-none');
            });

            function getSelectedBaseType() {
                var option = $select.find('option:selected');
                return (option.data('base-type') || 'pending').toString();
            }

            $select.on('change', function () {
                if (($select.val() || '') !== initialValue) {
                    $updateBtn.removeClass('d-none');
                } else {
                    $updateBtn.addClass('d-none');
                }
            });

            $cancelBtn.on('click', function () {
                $select.val(initialValue);
                $updateBtn.addClass('d-none');
                $edit.addClass('d-none');
                $view.removeClass('d-none');
            });

            function performProviderStatusUpdate(statusId, cancelReasonId, cancelRemarks) {
                var baseType = getSelectedBaseType();
                $updateBtn.prop('disabled', true);
                $.ajax({
                    url: statusUpdateUrl,
                    type: 'PUT',
                    data: {
                        _token: csrfToken,
                        provider_lead_status_id: statusId || null,
                        provider_cancellation_reason_id: baseType === 'cancel' ? (cancelReasonId || null) : null,
                        provider_cancellation_remarks: baseType === 'cancel' ? (cancelRemarks || null) : null
                    },
                    success: function (res) {
                        if (res && res.success) {
                            $text.text(res.status_name || '—');
                            if (res.status_color) {
                                $text.css({ 'background-color': res.status_color, 'color': '#fff' });
                            }
                            initialValue = statusId;
                            $updateBtn.addClass('d-none').prop('disabled', false);
                            $edit.addClass('d-none');
                            $view.removeClass('d-none');
                            if ($cancelModal.length) {
                                $cancelModal.modal('hide');
                            }
                            if (typeof toastr !== 'undefined') toastr.success('{{ translate('Provider_lead_information_updated_successfully') }}');
                        }
                    },
                    error: function () {
                        $updateBtn.prop('disabled', false);
                        if (typeof toastr !== 'undefined') toastr.error('{{ translate('Failed_to_update') }}');
                    }
                });
            }

            $updateBtn.on('click', function () {
                var statusId = $select.val() || '';
                var baseType = getSelectedBaseType();

                if (baseType === 'cancel') {
                    if ($cancelReason.length) {
                        $cancelReason.val('');
                    }
                    if ($cancelRemarks.length) {
                        $cancelRemarks.val('');
                    }
                    if ($cancelModal.length) {
                        $cancelModal.modal('show');
                    }
                    return;
                }

                performProviderStatusUpdate(statusId, null, null);
            });

            if ($cancelSaveBtn && $cancelSaveBtn.length) {
                $cancelSaveBtn.on('click', function () {
                    var reasonId = ($cancelReason.val() || '').toString().trim();
                    if (!reasonId) {
                        if (typeof toastr !== 'undefined') toastr.error('{{ translate('Provider_cancellation_reasons') }} {{ translate('is_required') }}');
                        return;
                    }
                    var remarks = ($cancelRemarks.val() || '').toString();
                    var statusId = $select.val() || '';
                    performProviderStatusUpdate(statusId, reasonId, remarks);
                });
            }
        })();

        (function () {
            var $view = $('#customer-header-status-view');
            var $edit = $('#customer-header-status-edit');
            var $text = $('#customer-header-status-text');
            var $select = $('#customer-header-status-select');
            var $updateBtn = $('#customer-header-status-update-btn');
            var $cancelBtn = $('#customer-header-status-cancel-btn');
            var $cancelModal = $('#customerCancelModal');
            var $cancelReason = $('#customer-cancel-reason-id');
            var $cancelRemarks = $('#customer-cancel-remarks');
            var $cancelSaveBtn = $('#customer-cancel-save-btn');
            if (!$view.length) return;
            var initialValue = '';
            var statusUpdateUrl = '{{ route('admin.lead.customer-status.update', $lead->id) }}';
            var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();

            $('#customer-header-status-edit-btn').on('click', function () {
                initialValue = $select.val() || '';
                $updateBtn.addClass('d-none');
                $view.addClass('d-none');
                $edit.removeClass('d-none');
            });

            function getSelectedBaseType() {
                var option = $select.find('option:selected');
                return (option.data('base-type') || 'pending').toString();
            }

            $select.on('change', function () {
                if (($select.val() || '') !== initialValue) {
                    $updateBtn.removeClass('d-none');
                } else {
                    $updateBtn.addClass('d-none');
                }

            });

            $cancelBtn.on('click', function () {
                $select.val(initialValue);
                $updateBtn.addClass('d-none');
                $edit.addClass('d-none');
                $view.removeClass('d-none');
            });

            function performCustomerStatusUpdate(statusId, cancelReasonId, cancelRemarks) {
                var baseType = getSelectedBaseType();
                $updateBtn.prop('disabled', true);
                $.ajax({
                    url: statusUpdateUrl,
                    type: 'PUT',
                    data: {
                        _token: csrfToken,
                        customer_lead_status_id: statusId || null,
                        cancellation_reason_id: baseType === 'cancel' ? (cancelReasonId || null) : null,
                        cancellation_remarks: baseType === 'cancel' ? (cancelRemarks || null) : null
                    },
                    success: function (res) {
                        if (res && res.success) {
                            $text.text(res.status_name || '—');
                            if (res.status_color) {
                                $text.css({ 'background-color': res.status_color, 'color': '#fff' });
                            }
                            initialValue = statusId;
                            $updateBtn.addClass('d-none').prop('disabled', false);
                            $edit.addClass('d-none');
                            $view.removeClass('d-none');
                            if ($cancelModal.length) {
                                $cancelModal.modal('hide');
                            }
                            if (typeof toastr !== 'undefined') toastr.success('{{ translate('Customer_lead_information_updated_successfully') }}');
                        }
                    },
                    error: function () {
                        $updateBtn.prop('disabled', false);
                        if (typeof toastr !== 'undefined') toastr.error('{{ translate('Failed_to_update') }}');
                    }
                });
            }

            $updateBtn.on('click', function () {
                var statusId = $select.val() || '';
                var baseType = getSelectedBaseType();

                if (baseType === 'cancel') {
                    if ($cancelReason.length) {
                        $cancelReason.val('');
                    }
                    if ($cancelRemarks.length) {
                        $cancelRemarks.val('');
                    }
                    if ($cancelModal.length) {
                        $cancelModal.modal('show');
                    }
                    return;
                }

                performCustomerStatusUpdate(statusId, null, null);
            });

            if ($cancelModal.length) {
                $cancelModal.on('hidden.bs.modal', function () {
                    $updateBtn.prop('disabled', false);
                });

                $cancelSaveBtn.on('click', function () {
                    var reasonId = $cancelReason.length ? ($cancelReason.val() || '') : '';
                    var remarks = $cancelRemarks.length ? ($cancelRemarks.val() || '') : '';
                    if (!reasonId) {
                        if (typeof toastr !== 'undefined') toastr.error('{{ translate('Customer_cancellation_reasons') }} {{ translate('is_required') }}');
                        return;
                    }
                    var statusId = $select.val() || '';
                    performCustomerStatusUpdate(statusId, reasonId, remarks);
                });
            }
        })();

        @php
            $customerLeadTagsAll = ($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER && isset($customerLeadTags))
                ? $customerLeadTags->map(function($t) { return ['id' => $t->id, 'name' => $t->name, 'color' => $t->color ?? '#0d6efd']; })->values()->all()
                : [];
        @endphp
        window.__customerLeadTagsAll = @json($customerLeadTagsAll);
        (function () {
            var $pills = $('#customer-lead-tags-pills');
            var $input = $('#customer-lead-tag-autocomplete');
            var $list = $('#customer-lead-tag-autocomplete-list');
            var $editBlock = $('#customer-lead-tags-edit-block');
            var $editBtn = $('#customer-lead-tags-edit-btn');
            var $doneBtn = $('#customer-lead-tags-done-btn');
            if (!$pills.length || !$input.length) return;
            var tagsEditMode = false;
            var customerTagsUpdateUrl = '{{ route('admin.lead.customer-tags.update', $lead->id) }}';
            var customerTagStoreUrl = '{{ route('admin.lead.customer-tag.store') }}';
            var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
            var allTags = (typeof window.__customerLeadTagsAll !== 'undefined' && Array.isArray(window.__customerLeadTagsAll)) ? window.__customerLeadTagsAll.slice() : [];

            function getCurrentTagsFromPills() {
                var tags = [];
                $pills.find('.customer-lead-tag-pill').each(function () {
                    tags.push({
                        id: $(this).data('tag-id'),
                        name: $(this).data('tag-name') || $(this).text().trim(),
                        color: $(this).data('tag-color') || '#0d6efd'
                    });
                });
                return tags;
            }
            function getCurrentTagIds() {
                return $pills.find('.customer-lead-tag-pill').map(function () { return String($(this).data('tag-id')); }).get();
            }
            function getAvailableTags(query) {
                var currentIds = getCurrentTagIds();
                var q = (query || '').toLowerCase().trim();
                return allTags.filter(function (t) {
                    if (currentIds.indexOf(String(t.id)) !== -1) return false;
                    return !q || (t.name || '').toLowerCase().indexOf(q) !== -1;
                });
            }
            function renderPills(tags, withRemoveButton) {
                var html = '';
                tags.forEach(function (t) {
                    html += '<span class="badge rounded-pill d-inline-flex align-items-center gap-1 px-2 py-1 customer-lead-tag-pill" style="background-color: ' + (t.color || '#0d6efd') + '; color: #fff;" data-tag-id="' + t.id + '" data-tag-name="' + (t.name || '') + '" data-tag-color="' + (t.color || '#0d6efd') + '">' + (t.name || '');
                    if (withRemoveButton) {
                        html += '<button type="button" class="btn btn-link p-0 m-0 border-0 bg-transparent text-white opacity-75 customer-lead-tag-remove" style="font-size: 14px; line-height: 1;" title="{{ translate('Remove') }}" aria-label="{{ translate('Remove') }}">&times;</button>';
                    }
                    html += '</span>';
                });
                $pills.html(html);
            }
            function showSuggestions(tags, queryForNew) {
                $list.empty().addClass('d-none');
                var q = (queryForNew || '').trim();
                var hasExact = q && tags.some(function (t) { return (t.name || '').toLowerCase() === q.toLowerCase(); });
                tags.forEach(function (t) {
                    $list.append($('<a href="#" class="list-group-item list-group-item-action list-group-item-light py-2 customer-lead-tag-suggestion" data-tag-id="' + t.id + '">').css('border-left', '3px solid ' + (t.color || '#0d6efd')).text(t.name));
                });
                if (q && !hasExact) {
                    $list.append($('<a href="#" class="list-group-item list-group-item-action py-2 customer-lead-tag-create-new" data-create-name="' + (q || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '">').html('<span class="text-primary">+ {{ translate('Add') }} &quot;' + (q || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '&quot; {{ translate('as_new_tag') }}</span>'));
                }
                if (tags.length || (q && !hasExact)) $list.removeClass('d-none');
            }
            function hideSuggestions() {
                $list.addClass('d-none').empty();
            }
            function addTagById(tagId, successCb) {
                var ids = getCurrentTagIds();
                if (ids.indexOf(String(tagId)) !== -1) { if (successCb) successCb(); return; }
                ids.push(String(tagId));
                $.ajax({
                    url: customerTagsUpdateUrl,
                    type: 'PUT',
                    data: { _token: csrfToken, tag_ids: ids },
                    success: function (res) {
                        if (res && res.success && res.tags) {
                            renderPills(res.tags, true);
                            $input.val('');
                            hideSuggestions();
                            if (typeof successCb === 'function') successCb();
                            if (typeof toastr !== 'undefined') toastr.success('{{ translate('Tags_updated') }}');
                        }
                    },
                    error: function () {
                        if (typeof toastr !== 'undefined') toastr.error('{{ translate('Failed_to_update') }}');
                    }
                });
            }
            function createTagAndAdd(name, successCb) {
                $.ajax({
                    url: customerTagStoreUrl,
                    type: 'POST',
                    data: { _token: csrfToken, name: name, color: '#0d6efd' },
                    success: function (res) {
                        if (res && res.success && res.tag) {
                            allTags.push(res.tag);
                            addTagById(res.tag.id, successCb);
                        }
                    },
                    error: function () {
                        if (typeof toastr !== 'undefined') toastr.error('{{ translate('Failed_to_update') }}');
                    }
                });
            }

            $input.on('input focus', function () {
                var q = $input.val();
                showSuggestions(getAvailableTags(q), q);
            });
            $input.on('keydown', function (e) {
                if (e.key === 'Escape') { hideSuggestions(); $input.blur(); return; }
                if (e.key !== 'Enter') return;
                e.preventDefault();
                var val = $input.val().trim();
                if (!val) { hideSuggestions(); return; }
                var available = getAvailableTags(val);
                var exact = available.filter(function (t) { return (t.name || '').toLowerCase() === val.toLowerCase(); })[0];
                if (exact) {
                    addTagById(exact.id);
                } else {
                    createTagAndAdd(val);
                }
            });
            $list.on('click', '.customer-lead-tag-suggestion', function (e) {
                e.preventDefault();
                var id = $(this).data('tag-id');
                addTagById(id);
            });
            $list.on('click', '.customer-lead-tag-create-new', function (e) {
                e.preventDefault();
                var name = $(this).data('create-name');
                if (name) createTagAndAdd(name);
            });
            $(document).on('click', function (e) {
                if ($input[0] !== e.target && $list[0] !== e.target && !$.contains($list[0], e.target) && !$.contains($input[0], e.target)) {
                    hideSuggestions();
                }
            });
            $pills.on('click', '.customer-lead-tag-remove', function (e) {
                e.preventDefault();
                var $pill = $(this).closest('.customer-lead-tag-pill');
                var tagId = $pill.data('tag-id');
                var ids = getCurrentTagIds().filter(function (id) { return id !== String(tagId); });
                $.ajax({
                    url: customerTagsUpdateUrl,
                    type: 'PUT',
                    data: { _token: csrfToken, tag_ids: ids },
                    success: function (res) {
                        if (res && res.success && res.tags) {
                            renderPills(res.tags, true);
                            if (typeof toastr !== 'undefined') toastr.success('{{ translate('Tags_updated') }}');
                        }
                    },
                    error: function () {
                        if (typeof toastr !== 'undefined') toastr.error('{{ translate('Failed_to_update') }}');
                    }
                });
            });

            $editBtn.on('click', function () {
                tagsEditMode = true;
                $editBlock.removeClass('d-none');
                $editBtn.addClass('d-none');
                $doneBtn.removeClass('d-none');
                renderPills(getCurrentTagsFromPills(), true);
            });
            $doneBtn.on('click', function () {
                tagsEditMode = false;
                $editBlock.addClass('d-none');
                $editBtn.removeClass('d-none');
                $doneBtn.addClass('d-none');
                $input.val('');
                hideSuggestions();
                renderPills(getCurrentTagsFromPills(), false);
            });
        })();

        (function () {
            const providerZoneSelect = $('#provider-zone-select');
            const providerCategorySelect = $('#provider-category-select');
            const providerSubcategorySelect = $('#provider-subcategory-select');
            const categoriesUrl = '{{ route("admin.booking.service.ajax-get-categories") }}';
            const subcategoriesUrl = '{{ route("admin.booking.service.ajax-get-subcategories") }}';

            function destroySelect2($el) {
                if ($el.length && $el.hasClass('select2-hidden-accessible')) {
                    $el.off('select2:select').select2('destroy');
                }
            }

            function initProviderCategorySelect() {
                destroySelect2(providerCategorySelect);
                providerCategorySelect.addClass('js-select');
                if (typeof initSelect2 === 'function') {
                    initSelect2(providerCategorySelect);
                }
            }

            function initProviderSubcategorySelect() {
                destroySelect2(providerSubcategorySelect);
                providerSubcategorySelect.addClass('js-select');
                if (typeof initSelect2 === 'function') {
                    initSelect2(providerSubcategorySelect);
                }
            }

            function loadProviderCategories(onLoaded) {
                const zoneId = providerZoneSelect.val();
                providerCategorySelect.empty().append(new Option('{{ translate("Select_Category") }}', '', true, true)).prop('disabled', !zoneId);
                providerSubcategorySelect.empty().append(new Option('{{ translate("Select_Sub_Category") }}', '', true, true)).prop('disabled', true);
                destroySelect2(providerCategorySelect);
                destroySelect2(providerSubcategorySelect);
                if (!zoneId) {
                    providerCategorySelect.removeClass('js-select');
                    providerSubcategorySelect.removeClass('js-select');
                    if (onLoaded) onLoaded();
                    return;
                }
                $.get(categoriesUrl, { zone_id: zoneId }).done(function (res) {
                    const list = (res && res.content) ? res.content : (res && res.data ? res.data : (Array.isArray(res) ? res : []));
                    list.forEach(function (item) {
                        providerCategorySelect.append(new Option(item.name || item.category_name, item.id, false, false));
                    });
                    initProviderCategorySelect();
                    if (onLoaded) onLoaded();
                }).fail(function () {
                    providerCategorySelect.append(new Option('{{ translate("Failed_to_load") }}', '', true, true));
                    initProviderCategorySelect();
                    if (onLoaded) onLoaded();
                });
            }

            function loadProviderSubcategories(onLoaded) {
                const categoryId = providerCategorySelect.val();
                providerSubcategorySelect.empty().append(new Option('{{ translate("Select_Sub_Category") }}', '', true, true)).prop('disabled', !categoryId);
                destroySelect2(providerSubcategorySelect);
                if (!categoryId) {
                    providerSubcategorySelect.removeClass('js-select');
                    if (onLoaded) onLoaded();
                    return;
                }
                $.get(subcategoriesUrl, { category_id: categoryId }).done(function (res) {
                    const list = (res && res.content) ? res.content : (res && res.data ? res.data : (Array.isArray(res) ? res : []));
                    list.forEach(function (item) {
                        providerSubcategorySelect.append(new Option(item.name || item.category_name, item.id, false, false));
                    });
                    initProviderSubcategorySelect();
                    if (onLoaded) onLoaded();
                }).fail(function () {
                    providerSubcategorySelect.append(new Option('{{ translate("Failed_to_load") }}', '', true, true));
                    initProviderSubcategorySelect();
                    if (onLoaded) onLoaded();
                });
            }

            providerZoneSelect.on('change', loadProviderCategories);
            providerCategorySelect.on('change', loadProviderSubcategories);

            $('#leadProviderModal').on('show.bs.modal', function () {
                const $modal = $(this);
                const editCategory = $modal.data('editCategory');
                const editSubcategory = $modal.data('editSubcategory');
                loadProviderCategories(function () {
                    if (editCategory) {
                        providerCategorySelect.val(editCategory);
                        initProviderCategorySelect();
                        loadProviderSubcategories(function () {
                            if (editSubcategory) {
                                providerSubcategorySelect.val(editSubcategory);
                                initProviderSubcategorySelect();
                            }
                        });
                    }
                });
            });
        })();

        (function () {
            const bulkUrl = '{{ route("admin.lead.checklist.update.bulk", $lead->id) }}';
            const token = '{{ csrf_token() }}';

            function getCard() {
                return $('#provider-checklist-card');
            }

            function setRowState(row, isDone) {
                const doneVal = isDone ? '1' : '0';
                row.attr('data-is-done', doneVal);
                row.data('is-done', doneVal);
                const badge = row.find('.provider-checklist-status .badge').first();
                const btn = row.find('.provider-checklist-toggle').first();
                const icon = row.find('.provider-checklist-icon').first();
                if (isDone) {
                    badge.removeClass('bg-secondary').addClass('bg-success').text('{{ translate("Done") }}');
                    btn.removeClass('btn-outline-success').addClass('btn-outline-secondary');
                    icon.text('check_box');
                } else {
                    badge.removeClass('bg-success').addClass('bg-secondary').text('{{ translate("Pending") }}');
                    btn.removeClass('btn-outline-secondary').addClass('btn-outline-success');
                    icon.text('check_box_outline_blank');
                }
            }

            function exitEditMode(card) {
                card.find('.provider-checklist-toggle').prop('disabled', true);
                card.find('#provider-checklist-edit-btn').removeClass('d-none');
                card.find('.provider-checklist-edit-only').addClass('d-none');
                card.find('#provider-checklist-update-btn').prop('disabled', true);
            }

            function enterEditMode(card) {
                card.find('.provider-checklist-toggle').prop('disabled', false);
                card.find('#provider-checklist-edit-btn').addClass('d-none');
                card.find('.provider-checklist-edit-only').removeClass('d-none');
            }

            $(document).on('click', '#provider-checklist-edit-btn', function () {
                const card = $(this).closest('#provider-checklist-card');
                if (card.length) enterEditMode(card);
            });

            $(document).on('click', '#provider-checklist-cancel-btn', function () {
                const card = $(this).closest('#provider-checklist-card');
                if (!card.length) return;
                card.find('tbody tr').each(function () {
                    const row = $(this);
                    const initial = row.attr('data-initial-done') === '1';
                    setRowState(row, initial);
                });
                exitEditMode(card);
            });

            $(document).on('click', '.provider-checklist-toggle', function () {
                const btn = $(this);
                if (btn.prop('disabled')) return;
                const row = btn.closest('tr');
                const card = row.closest('#provider-checklist-card');
                if (!row.length || !card.length) return;
                const isDone = row.attr('data-is-done') === '1';
                setRowState(row, !isDone);
                card.find('#provider-checklist-update-btn').prop('disabled', false);
            });

            $(document).on('click', '#provider-checklist-update-btn', function () {
                const updateBtn = $(this);
                const card = updateBtn.closest('#provider-checklist-card');
                if (!card.length || updateBtn.prop('disabled')) return;
                const items = [];
                card.find('tbody tr').each(function () {
                    const row = $(this);
                    const itemId = parseInt(row.attr('data-item-id'), 10);
                    const isDone = row.attr('data-is-done') === '1';
                    if (!isNaN(itemId)) {
                        items.push({ provider_checklist_item_id: itemId, is_done: isDone });
                    }
                });
                updateBtn.prop('disabled', true);
                $.ajax({
                    url: bulkUrl,
                    type: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({ items: items, _token: token }),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    success: function () {
                        exitEditMode(card);
                        card.find('tbody tr').each(function () {
                            const row = $(this);
                            row.attr('data-initial-done', row.attr('data-is-done'));
                        });
                        toastr && toastr.success('{{ translate("Checklist_updated_successfully") }}');
                        var currentUrl = window.location.href;
                        $.get(currentUrl).done(function (html) {
                            var $parsed = $(html);
                            var $newChecklist = $parsed.find('#provider-checklist-card');
                            if ($newChecklist.length && card.length) {
                                card.replaceWith($newChecklist);
                            }
                            var $newChangeHistory = $parsed.find('#lead-change-history-card');
                            var $currentChangeHistory = $('#lead-change-history-card');
                            if ($newChangeHistory.length && $currentChangeHistory.length) {
                                $currentChangeHistory.replaceWith($newChangeHistory);
                            }
                        });
                    },
                    error: function () {
                        updateBtn.prop('disabled', false);
                        toastr && toastr.error('{{ translate("Failed_to_update") }}');
                    }
                });
            });
        })();
    </script>
@endpush

