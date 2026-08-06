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
            $createdBookingDetailsUrl = $createdBookingId ? route('admin.booking.details', [$createdBookingId, 'web_page' => 'details']) : null;

            $currentProviderStatusId = ($typeHistory && is_array($typeHistory->data ?? null))
                ? ($typeHistory->data['provider_lead_status_id'] ?? '')
                : '';
            $currentCustomerStatusId = ($typeHistory && is_array($typeHistory->data ?? null))
                ? ($typeHistory->data['customer_lead_status_id'] ?? '')
                : '';
            $currentCustomerStatus = $customerLeadStatuses->firstWhere('id', $currentCustomerStatusId);
            $isPendingCustomerStatus = !$currentCustomerStatus || $currentCustomerStatus->base_type === 'pending';
            $isBookedCustomerStatus = $currentCustomerStatus && in_array($currentCustomerStatus->base_type, ['booked', 'completed'], true);
            $followupScheduleMinAt = now()->format('Y-m-d\TH:i');
            $hasProviderData = $lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER
                && isset($typeHistoryDisplay['basic'], $typeHistoryDisplay['service']);
            $hasCustomerData = $lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER
                && $typeHistory && !empty($typeHistoryDisplay);

            $leadNameForDisplay = trim((string) ($lead->name ?? ''));
            $leadInitials = '—';
            if ($leadNameForDisplay !== '') {
                $parts = preg_split('/\s+/', $leadNameForDisplay);
                $leadInitials = strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
            } elseif (!empty($lead->phone_number)) {
                $leadInitials = mb_substr(preg_replace('/\D/', '', $lead->phone_number), -2);
            }

            $pipelineSteps = [];
            $pipelineCurrentIndex = 0;
            if ($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER) {
                $pipelineOrder = ['pending', 'booked', 'completed'];
                $seen = [];
                foreach ($pipelineOrder as $baseType) {
                    $status = $customerLeadStatuses->firstWhere('base_type', $baseType);
                    if ($status) {
                        $pipelineSteps[] = ['label' => $status->name, 'base_type' => $baseType];
                        $seen[$baseType] = true;
                    }
                }
                foreach ($customerLeadStatuses as $status) {
                    if (!isset($seen[$status->base_type ?? ''])) {
                        $pipelineSteps[] = ['label' => $status->name, 'base_type' => $status->base_type ?? 'pending'];
                    }
                }
                if ($currentCustomerStatus) {
                    foreach ($pipelineSteps as $i => $step) {
                        if (($step['base_type'] ?? '') === ($currentCustomerStatus->base_type ?? '')) {
                            $pipelineCurrentIndex = $i;
                            break;
                        }
                    }
                }
            } elseif ($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER) {
                $pipelineOrder = ['pending', 'booked', 'completed'];
                $seen = [];
                foreach ($pipelineOrder as $baseType) {
                    $status = $providerLeadStatuses->firstWhere('base_type', $baseType);
                    if ($status) {
                        $pipelineSteps[] = ['label' => $status->name, 'base_type' => $baseType];
                        $seen[$baseType] = true;
                    }
                }
                foreach ($providerLeadStatuses as $status) {
                    if (!isset($seen[$status->base_type ?? ''])) {
                        $pipelineSteps[] = ['label' => $status->name, 'base_type' => $status->base_type ?? 'pending'];
                    }
                }
                $currentProviderStatus = $providerLeadStatuses->firstWhere('id', $currentProviderStatusId);
                if ($currentProviderStatus) {
                    foreach ($pipelineSteps as $i => $step) {
                        if (($step['base_type'] ?? '') === ($currentProviderStatus->base_type ?? '')) {
                            $pipelineCurrentIndex = $i;
                            break;
                        }
                    }
                }
            }

            $commentParser = app(\Modules\ChattingModule\Services\StaffChatMessageParser::class);
            $sortedComments = $lead->comments->sort(function ($a, $b) {
                if ((bool) $a->is_pinned !== (bool) $b->is_pinned) {
                    return (bool) $b->is_pinned <=> (bool) $a->is_pinned;
                }
                if ($a->is_pinned && $b->is_pinned) {
                    return ($b->pinned_at ?? $b->created_at) <=> ($a->pinned_at ?? $a->created_at);
                }
                return $a->created_at <=> $b->created_at;
            })->values();

            $activityFollowupCount = $lead->followups->count();
            $activityChangeCount = isset($changeLogs) ? $changeLogs->sum(fn ($log) => max(1, count($log->changes ?? []))) : 0;
            $activityCommentCount = $sortedComments->count();

            $callFollowups = $lead->followups->filter(
                fn ($followup) => $followup->contact_channel === \Modules\LeadManagement\Entities\LeadFollowup::CHANNEL_CALL
            );
            $callLogs = collect();
            if ($lead->hasInitialCallRecording()) {
                $callLogs->push([
                    'type' => 'initial',
                    'called_party_type' => \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_CUSTOMER,
                    'called_number' => $lead->phone_number,
                    'called_name' => $lead->name,
                    'called_at' => $lead->created_at,
                    'remarks' => translate('Initial_call_recording'),
                ]);
            }
            foreach ($callFollowups as $callFollowup) {
                $callLogs->push([
                    'type' => 'followup',
                    'called_party_type' => $callFollowup->called_party_type ?: \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_CUSTOMER,
                    'called_number' => $callFollowup->called_number ?: $lead->phone_number,
                    'called_name' => $callFollowup->called_name ?: $lead->name,
                    'called_at' => $callFollowup->followup_at ?? $callFollowup->created_at,
                    'remarks' => $callFollowup->remarks,
                    'followup' => $callFollowup,
                ]);
            }
            $callLogs = $callLogs->sortByDesc(fn ($log) => $log['called_at']?->timestamp ?? 0)->values();
            $activityCallCount = $callLogs->count();

            $activityTotalCount = $activityFollowupCount + $activityChangeCount + $activityCommentCount + $activityCallCount + (!empty($hasScheduledFollowup) ? 1 : 0);

            $handledByUser = null;
            if (!empty($lead->handled_by)) {
                $handledByUser = $employees->firstWhere('id', $lead->handled_by);
            }
            $handledByInitials = '—';
            if ($handledByUser) {
                $hbName = trim(($handledByUser->first_name ?? '') . ' ' . ($handledByUser->last_name ?? ''));
                if ($hbName !== '') {
                    $hbParts = preg_split('/\s+/', $hbName);
                    $handledByInitials = strtoupper(mb_substr($hbParts[0], 0, 1) . (isset($hbParts[1]) ? mb_substr($hbParts[1], 0, 1) : ''));
                }
            }
        @endphp

        @if($createdBookingId)
            <div class="alert alert-success d-flex justify-content-between align-items-center mb-3">
                <div>
                    {{ translate('Booking_has_been_created_for_this_lead') }}
                    @if(!empty($createdBookingReadableId))
                        ({{ translate('Booking_ID') }}: {{ $createdBookingReadableId }})
                    @endif
                </div>
                <a href="{{ $createdBookingDetailsUrl }}" class="btn btn-sm btn--primary" data-turbo="false" @if(!empty($inModal)) target="_top" @endif>
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
                <div class="col-12 lead-detail-v2">
                    <div class="lead-detail-v2__wrap">
                        @include('leadmanagement::admin.leads.partials._show-redesign-hero')
                        @include('leadmanagement::admin.leads.partials._show-redesign-pipeline')

                        <div class="lead-grid">
                            @include('leadmanagement::admin.leads.partials._show-redesign-main')
                            @include('leadmanagement::admin.leads.partials._show-redesign-sidebar')
                        </div>

                        @include('leadmanagement::admin.leads.partials._show-redesign-activity')
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
                                        <div class="mb-3">
                                            @include('leadmanagement::admin.leads.partials._area-select', ['areaSelectId' => 'invalid-area-select'])
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
                                        <div class="mb-3">
                                            @include('leadmanagement::admin.leads.partials._area-select', ['areaSelectId' => 'future-area-select'])
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
                                                <select name="zone_id" id="lead-zone-select" class="form-control zone-tree-select">
                                                    <option value="">{{ translate('Select_Zone') }}</option>
                                                    @include('zonemanagement::admin.partials._zone-select-options', [
                                                        'zoneTreeOptions' => $zoneTreeOptions,
                                                        'selected' => $customerEditData['zone_id'] ?? '',
                                                    ])
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                @include('leadmanagement::admin.leads.partials._area-select', ['areaSelectId' => 'lead-area-select', 'areaSelected' => $customerEditData['area_id'] ?? ''])
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Category') }}</label>
                                                <select name="service_category" id="lead-category-select" class="form-control js-select js-select-manual" disabled>
                                                    <option value="">{{ translate('Select_Category') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Sub_Category') }}</label>
                                                <select name="service_subcategory" id="lead-subcategory-select" class="form-control js-select js-select-manual" disabled>
                                                    <option value="">{{ translate('Select_Sub_Category') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Service') }} ({{ translate('optional') }})</label>
                                                <select name="service_name" id="lead-service-select" class="form-control js-select js-select-manual" disabled>
                                                    <option value="">{{ translate('Select_Service_or_leave_for_custom') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ translate('Select_Service_Variant') }}</label>
                                                <select name="variant_key" id="lead-variant-select" class="form-control js-select js-select-manual" disabled>
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
                        $providerZoneIdsForEdit = [];
                        if (!empty($providerEditData['zone_ids']) && is_array($providerEditData['zone_ids'])) {
                            $providerZoneIdsForEdit = array_values(array_filter(array_map('strval', $providerEditData['zone_ids'])));
                        } elseif (!empty($providerEditData['zone_id'])) {
                            $providerZoneIdsForEdit = [(string) $providerEditData['zone_id']];
                        }
                    @endphp
                    <div class="modal fade" id="leadProviderModal" tabindex="-1" aria-labelledby="leadProviderModalLabel" aria-hidden="true"
                         data-edit-zone-ids='@json($providerZoneIdsForEdit)'
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
                                                <select name="zone_ids[]" id="provider-zone-select" class="form-select zone-tree-select" multiple>
                                                    @foreach($zoneTreeOptions as $zOpt)
                                                        <option value="{{ $zOpt['id'] }}"
                                                                data-zone-prefix="{{ $zOpt['prefix'] ?? '' }}"
                                                                data-zone-name="{{ $zOpt['name'] ?? '' }}"
                                                                data-zone-description="{{ $zOpt['description'] ?? '' }}"
                                                                {{ in_array((string) $zOpt['id'], $providerZoneIdsForEdit, true) ? 'selected' : '' }}>{{ $zOpt['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                @include('leadmanagement::admin.leads.partials._area-select', ['areaSelectId' => 'provider-area-select', 'areaSelected' => $providerEditData['area_id'] ?? ''])
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

                    <div class="modal fade" id="addCallLogModal" tabindex="-1" aria-labelledby="addCallLogModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title" id="addCallLogModalLabel">{{ translate('Add_Call_Log') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                </div>
                                <form method="POST"
                                      action="{{ route('admin.lead.call-logs.store', $lead->id) }}"
                                      enctype="multipart/form-data"
                                      id="add-call-log-form"
                                      data-store-url="{{ route('admin.lead.call-logs.store', $lead->id) }}"
                                      data-update-url-template="{{ route('admin.lead.call-logs.update', [$lead->id, '__FOLLOWUP__']) }}">
                                    @csrf
                                    <input type="hidden" name="call_log_form" value="1">
                                    <input type="hidden" name="call_log_mode" id="call-log-mode-input" value="{{ old('call_log_mode', 'add') }}">
                                    <input type="hidden" name="call_log_followup_id" id="call-log-followup-id-input" value="{{ old('call_log_followup_id') }}">
                                    <input type="hidden" name="_method" id="call-log-method-input" value="PUT" disabled>
                                    @if(!empty($inModal))
                                        <input type="hidden" name="in_modal" value="1">
                                    @endif
                                    <div class="modal-body pt-0">
                                        @php
                                            $defaultCalledPartyType = old(
                                                'called_party_type',
                                                \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_CUSTOMER
                                            );
                                        @endphp
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Who_You_Called') }}</label>
                                            <div class="d-flex flex-wrap gap-3 mb-2">
                                                @foreach([
                                                    \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_CUSTOMER => translate('Customer'),
                                                    \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_PROVIDER => translate('Provider'),
                                                    \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_OTHER => translate('Other'),
                                                ] as $partyType => $partyLabel)
                                                    <div class="form-check">
                                                        <input class="form-check-input js-call-log-party-type"
                                                               type="radio"
                                                               name="called_party_type"
                                                               id="call-log-party-{{ $partyType }}"
                                                               value="{{ $partyType }}"
                                                               {{ $defaultCalledPartyType === $partyType ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="call-log-party-{{ $partyType }}">{{ $partyLabel }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @error('called_party_type')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror

                                            <div class="call-log-party-panel call-log-party-panel--customer {{ $defaultCalledPartyType === \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_CUSTOMER ? '' : 'd-none' }}">
                                                <label class="form-label small text-muted mb-1">{{ translate('Customer') }}</label>
                                                <input type="text"
                                                       class="form-control mb-2"
                                                       value="{{ $lead->name ?: '—' }}"
                                                       readonly>
                                                <input type="text"
                                                       class="form-control"
                                                       value="{{ $lead->phone_number ?: '—' }}"
                                                       readonly>
                                            </div>

                                            <div class="call-log-party-panel call-log-party-panel--provider {{ $defaultCalledPartyType === \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_PROVIDER ? '' : 'd-none' }}">
                                                <label class="form-label small text-muted mb-1" for="call-log-provider-select">{{ translate('Select_Provider') }}</label>
                                                <select name="called_provider_id"
                                                        id="call-log-provider-select"
                                                        class="form-control"
                                                        data-placeholder="{{ translate('Search_provider_by_name_or_phone') }}"
                                                        data-selected="{{ old('called_provider_id') }}">
                                                    <option value="">{{ translate('Select_Provider') }}</option>
                                                </select>
                                                @error('called_provider_id')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                                <div id="call-log-provider-preview" class="small text-muted mt-2 d-none"></div>
                                            </div>

                                            <div class="call-log-party-panel call-log-party-panel--other {{ $defaultCalledPartyType === \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_OTHER ? '' : 'd-none' }}">
                                                <label class="form-label small text-muted mb-1" for="call-log-other-name">{{ translate('Name') }}</label>
                                                <input type="text"
                                                       name="called_name"
                                                       id="call-log-other-name"
                                                       class="form-control mb-2"
                                                       value="{{ old('called_name') }}"
                                                       maxlength="255">
                                                @error('called_name')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                                <label class="form-label small text-muted mb-1" for="call-log-other-number">{{ translate('Phone') }}</label>
                                                <input type="text"
                                                       name="called_number"
                                                       id="call-log-other-number"
                                                       class="form-control"
                                                       value="{{ old('called_number') }}"
                                                       maxlength="32">
                                                @error('called_number')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('When_You_Called') }}</label>
                                            <input type="datetime-local"
                                                   name="called_at"
                                                   id="call-log-called-at-input"
                                                   class="form-control"
                                                   value="{{ old('called_at', now()->format('Y-m-d\TH:i')) }}"
                                                   required>
                                            @error('called_at')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Remarks') }}</label>
                                            <textarea name="remarks"
                                                      class="form-control"
                                                      rows="3"
                                                      placeholder="{{ translate('Add_call_log_remarks') }}">{{ old('remarks') }}</textarea>
                                            @error('remarks')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">{{ translate('Voice_Recording') }}</label>
                                            <div id="call-log-current-recording" class="small text-muted mb-2 d-none"></div>
                                            <input type="file"
                                                   name="recording"
                                                   id="call-log-recording-input"
                                                   class="form-control"
                                                   accept="audio/*,video/mp4,.mp3,.wav,.webm,.ogg,.m4a,.aac,.mp4">
                                            <div class="form-text">{{ translate('Upload_call_recording_optional_max_10MB') }}</div>
                                            @error('recording')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                                        <button type="button"
                                                class="btn btn--secondary"
                                                data-bs-dismiss="modal">
                                            {{ translate('Cancel') }}
                                        </button>
                                        <button type="submit" class="btn btn--primary" id="call-log-submit-btn">
                                            {{ translate('Add_Call_Log') }}
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
                                <form method="POST"
                                      action="{{ route('admin.lead.followups.store', $lead->id) }}"
                                      enctype="multipart/form-data"
                                      id="add-followup-form">
                                    @csrf
                                    <input type="hidden" name="followup_mode" id="followup-mode-input" value="{{ old('followup_mode', 'add') }}">
                                    @if(!empty($inModal))
                                        <input type="hidden" name="in_modal" value="1">
                                    @endif
                                    <div class="modal-body pt-0">
                                        <div class="mb-3 d-none" id="followup-status-group">
                                            <label class="form-label">{{ translate('Status') }}</label>
                                            <div class="d-flex flex-wrap gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input"
                                                           type="radio"
                                                           name="followup_action"
                                                           id="followup-action-taken"
                                                           value="{{ \Modules\LeadManagement\Entities\LeadFollowup::STATUS_TAKEN }}"
                                                           {{ old('followup_action', \Modules\LeadManagement\Entities\LeadFollowup::STATUS_TAKEN) === \Modules\LeadManagement\Entities\LeadFollowup::STATUS_TAKEN ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="followup-action-taken">{{ translate('Taken') }}</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input"
                                                           type="radio"
                                                           name="followup_action"
                                                           id="followup-action-reschedule"
                                                           value="{{ \Modules\LeadManagement\Entities\LeadFollowup::STATUS_RESCHEDULE }}"
                                                           {{ old('followup_action') === \Modules\LeadManagement\Entities\LeadFollowup::STATUS_RESCHEDULE ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="followup-action-reschedule">{{ translate('Reschedule') }}</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="followup-modal-section mb-3" id="followup-current-section">
                                            <h6 class="followup-modal-section-title" id="followup-current-section-title">{{ translate('This_Follow_up') }}</h6>
                                            <div class="row g-3 mb-3">
                                                <div class="col-sm-6" id="followup-datetime-group">
                                                    <label class="form-label">{{ translate('Date_Time') }}</label>
                                                    <input type="datetime-local"
                                                           name="followup_at"
                                                           id="followup-at-input"
                                                           class="form-control"
                                                           value="{{ old('followup_at', now()->format('Y-m-d\TH:i')) }}"
                                                           required>
                                                </div>
                                                <div class="col-sm-6" id="followup-channel-group">
                                                    <label class="form-label">{{ translate('Follow_up_Taken_on') }}</label>
                                                    <select name="contact_channel"
                                                            id="followup-contact-channel"
                                                            class="form-control">
                                                        <option value="{{ \Modules\LeadManagement\Entities\LeadFollowup::CHANNEL_CALL }}" {{ old('contact_channel', \Modules\LeadManagement\Entities\LeadFollowup::CHANNEL_CALL) === \Modules\LeadManagement\Entities\LeadFollowup::CHANNEL_CALL ? 'selected' : '' }}>
                                                            {{ translate('Call') }}
                                                        </option>
                                                        <option value="{{ \Modules\LeadManagement\Entities\LeadFollowup::CHANNEL_WHATSAPP }}" {{ old('contact_channel') === \Modules\LeadManagement\Entities\LeadFollowup::CHANNEL_WHATSAPP ? 'selected' : '' }}>
                                                            {{ translate('WhatsApp') }}
                                                        </option>
                                                    </select>
                                                    @error('contact_channel')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="mb-3 d-none" id="followup-recording-group">
                                                <label class="form-label">{{ translate('Voice_Recording') }}</label>
                                                <input type="file"
                                                       name="recording"
                                                       id="followup-recording-input"
                                                       class="form-control"
                                                       accept="audio/*,video/mp4,.mp3,.wav,.webm,.ogg,.m4a,.aac,.mp4">
                                                <div class="form-text">{{ translate('Upload_call_recording_optional_max_10MB') }}</div>
                                                @error('recording')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="mb-3" id="followup-remarks-group">
                                                <label class="form-label" id="followup-remarks-label">{{ translate('Remarks') }}</label>
                                                <textarea name="remarks"
                                                          id="followup-remarks-input"
                                                          class="form-control"
                                                          rows="3"
                                                          placeholder="{{ translate('Add_remarks_from_follow_up') }}">{{ old('remarks') }}</textarea>
                                                @error('remarks')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        @if(!empty($leadOpenStatus))
                                        <div class="followup-modal-section mb-0" id="followup-next-section">
                                            <h6 class="followup-modal-section-title">{{ translate('Next_Follow_up') }}</h6>
                                            <p class="followup-modal-section-help">{{ translate('Schedule_and_priority_for_the_upcoming_follow_up') }}</p>
                                            <div class="mb-3">
                                                <label class="form-label">{{ translate('Urgency') }}</label>
                                                <select name="urgency" class="form-control">
                                                    <option value="high" {{ old('urgency') === 'high' ? 'selected' : '' }}>{{ translate('High') }}</option>
                                                    <option value="medium" {{ old('urgency', 'medium') === 'medium' ? 'selected' : '' }}>{{ translate('Medium') }}</option>
                                                    <option value="low" {{ old('urgency') === 'low' ? 'selected' : '' }}>{{ translate('Low') }}</option>
                                                </select>
                                            </div>
                                            <div class="mb-0" id="next-followup-group">
                                                <label class="form-label" id="next-followup-label">
                                                    {{ translate('Next_Follow_up_Date') }} <span class="text-danger">*</span>
                                                </label>
                                                <input type="datetime-local"
                                                       name="next_followup_at"
                                                       id="next-followup-input"
                                                       class="form-control js-followup-future-only"
                                                       required
                                                       min="{{ $followupScheduleMinAt }}"
                                                       data-default="{{ $lead->next_followup_at?->format('Y-m-d\TH:i') ?? \Carbon\Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d\TH:i') }}"
                                                       value="{{ old('next_followup_at', $lead->next_followup_at?->format('Y-m-d\TH:i') ?? \Carbon\Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d\TH:i')) }}">
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                                        <button type="button"
                                                class="btn btn--secondary"
                                                data-bs-dismiss="modal">
                                            {{ translate('Cancel') }}
                                        </button>
                                        <button type="submit" class="btn btn--primary" id="followup-submit-btn">
                                            {{ translate('Save_changes') }}
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                    @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER)
                        @can('lead_outbound_enquiry_add')
                            <div class="modal fade" id="addOutboundEnquiryModal" tabindex="-1" aria-labelledby="addOutboundEnquiryModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title" id="addOutboundEnquiryModalLabel">{{ translate('Add_Outbound_Enquiry') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.lead.outbound-enquiry.store-from-lead', $lead->id) }}">
                                            @csrf
                                            @if(!empty($inModal))
                                                <input type="hidden" name="in_modal" value="1">
                                            @endif
                                            <div class="modal-body pt-0">
                                                @include('leadmanagement::admin.outbound-enquiries.partials._form_fields', [
                                                    'formPrefix' => 'outbound-lead-modal',
                                                    'defaultCustomerName' => old('customer_name', $lead->name),
                                                    'defaultPhoneNumber' => old('phone_number', $lead->phone_number),
                                                    'employees' => $employees,
                                                    'statuses' => $outboundEnquiryStatuses ?? [],
                                                    'currentEmployeeId' => auth()->id(),
                                                ])
                                            </div>
                                            <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                                                <button type="button"
                                                        class="btn btn--secondary"
                                                        data-bs-dismiss="modal">
                                                    {{ translate('Cancel') }}
                                                </button>
                                                <button type="submit" class="btn btn--primary">
                                                    {{ translate('Submit') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endcan
                    @endif

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

    @include('adminmodule::admin.workflow.partials._next-step-fab', ['workflowContext' => $workflowContext ?? [], 'wfCanEdit' => auth()->user()?->can('lead_update')])
    @include('adminmodule::admin.workflow.partials._confirm-modal')
    @include('adminmodule::admin.workflow.partials._scripts', ['workflowContext' => $workflowContext ?? [], 'wfEntityType' => 'lead', 'wfEntityId' => (int) $lead->id])
@endsection

@push('css_or_js')
    @include('zonemanagement::admin.partials._zone-select2-assets')
    <link rel="stylesheet" href="{{ asset('assets/chatting-module/css/staff-chat-entity-badges.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/lead-detail-redesign.css') }}">
    @include('leadmanagement::admin.leads.partials._comment-attachments-styles')
    <style>
        .btn-lead-type-invalid:hover:not(:disabled) {
            background-color: #dc3545 !important; /* Bootstrap danger */
            border-color: #dc3545 !important;
            color: #fff !important;
        }

        .btn-lead-type-customer:hover:not(:disabled) {
            background-color: #198754 !important; /* Bootstrap success */
            border-color: #198754 !important;
            color: #fff !important;
        }

        .btn-lead-type-provider:hover:not(:disabled) {
            background-color: #0d6efd !important; /* Bootstrap primary */
            border-color: #0d6efd !important;
            color: #fff !important;
        }

        .btn-lead-type-future:hover:not(:disabled) {
            background-color: #0dcaf0 !important; /* Bootstrap info (light blue) */
            border-color: #0dcaf0 !important;
            color: #fff !important;
        }

        .lead-detail-history-card .card-body {
            overflow: visible;
        }
        .lead-followup-history-table th {
            font-size: 0.8125rem;
            white-space: nowrap;
        }
        .lead-followup-history-table td {
            font-size: 0.875rem;
            vertical-align: middle;
        }
        .followup-modal-section {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1rem 0.25rem;
            background: #f8fafc;
        }
        .followup-modal-section + .followup-modal-section {
            margin-top: 1rem;
        }
        .followup-modal-section-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.875rem;
        }
        .followup-modal-section-help {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: -0.5rem;
            margin-bottom: 0.875rem;
        }
        .voice-call-details-panel {
            background: #f8f9fb;
            border-top: 1px solid #e9ecef;
        }
        .voice-call-detail-box {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .voice-call-detail-box__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 12px;
        }
        .voice-call-detail-box__header-title {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
        }
        .voice-call-detail-box__header .material-icons {
            font-size: 18px;
            color: #6c757d;
        }
        .voice-call-detail-box .card-body {
            padding: 12px;
        }
        .voice-call-copy-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border: none;
            background: transparent;
            color: #6c757d;
            border-radius: 6px;
            flex-shrink: 0;
        }
        .voice-call-copy-btn:hover {
            background: #f1f3f5;
            color: #495057;
        }
        .voice-call-transcript {
            max-height: 320px;
            overflow: auto;
            padding: 16px;
            font-size: 13px;
            line-height: 1.55;
            text-align: left;
            background: #fff;
            color: #212529;
        }
        .voice-call-transcript-line {
            margin-bottom: 6px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .voice-call-transcript-line--user {
            color: #0d6efd;
        }
        .voice-call-transcript-line--llm {
            color: #495057;
        }
        .voice-call-details-top-row {
            align-items: stretch;
        }
        .voice-call-left-stack {
            min-height: 100%;
        }
        .voice-call-recording-card {
            flex: 0 0 auto;
        }
        .voice-call-summary-card {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 180px;
        }
        .voice-call-summary-body {
            flex: 1 1 auto;
            min-height: 140px;
            overflow: auto;
        }
        .voice-call-extracted-card {
            display: flex;
            flex-direction: column;
        }
        .voice-call-extracted-body {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }
        .voice-call-extracted-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            flex: 1 1 auto;
            height: 100%;
            overflow: auto;
            align-content: start;
        }
        @media (max-width: 1200px) {
            .voice-call-extracted-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .voice-call-extracted-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .voice-call-extracted-item {
            background: #f8f9fb;
            border: 1px solid #eef1f4;
            border-radius: 8px;
            padding: 10px 12px;
            min-width: 0;
        }
        .voice-call-extracted-item__label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .voice-call-extracted-item__value {
            font-size: 14px;
            font-weight: 500;
            word-break: break-word;
        }
        .voice-call-recording-box .voice-call-audio-player {
            height: 36px;
        }
        .lead-followup-history-table tbody tr.voice-call-details-row > td {
            box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.04);
            background: #f8f9fb !important;
        }
        .lead-change-history-table th {
            font-size: 0.8125rem;
            white-space: nowrap;
        }
        .lead-change-history-table td {
            font-size: 0.875rem;
            vertical-align: middle;
        }
        .lead-comments-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .lead-comments-list-wrap {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            max-height: 420px;
            overflow: auto;
        }
        .lead-comments-list {
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }
        .lead-comments-empty {
            color: #94a3b8;
            font-size: .875rem;
            text-align: center;
            padding: 2rem 1rem;
        }
        .lead-comment-item {
            display: grid;
            grid-template-columns: 36px 1fr;
            gap: .75rem;
            align-items: start;
        }
        .lead-comment-item.is-pinned .lead-comment-card {
            border-color: #fcd34d;
            background: #fffbeb;
        }
        .lead-comment-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: .72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .lead-comment-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: .75rem .9rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .lead-comment-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .5rem;
            margin-bottom: .4rem;
        }
        .lead-comment-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .35rem .5rem;
            font-size: .78rem;
            color: #64748b;
        }
        .lead-comment-meta strong {
            color: #0f172a;
            font-size: .84rem;
            font-weight: 650;
        }
        .lead-comment-time {
            color: #94a3b8;
        }
        .lead-comment-pin-badge {
            display: inline-flex;
            align-items: center;
            gap: .15rem;
            color: #b45309;
            font-weight: 600;
        }
        .lead-comment-pin-badge .material-icons {
            font-size: 14px;
        }
        .lead-comment-actions {
            display: inline-flex;
            align-items: center;
            gap: .15rem;
            flex-shrink: 0;
        }
        .lead-comment-actions .material-icons {
            font-size: 18px;
        }
        .lead-comment-actions .btn-link {
            color: #64748b;
            line-height: 1;
            min-width: 28px;
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .lead-comment-actions .btn-link:hover {
            color: #0f172a;
        }
        .lead-comment-body {
            font-size: .9rem;
            color: #1e293b;
            line-height: 1.5;
            word-break: break-word;
        }
        .lead-comment-body .staff-chat-entity-link {
            margin-right: 0.25rem;
        }
        .lead-comment-compose {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: .625rem .75rem;
            background: #fafbfc;
        }
        .lead-comment-compose__input {
            width: 100%;
            min-height: 56px;
            resize: vertical;
            background: #fff;
        }
        .lead-comment-compose__input:focus {
            box-shadow: 0 0 0 2px rgba(37, 39, 77, .1);
        }
        .lead-comment-compose__footer {
            border-top: 1px solid #e2e8f0;
        }
        .lead-comment-compose .staff-chat-compose-tools {
            margin-bottom: 0;
        }
    </style>
@endpush

@push('script')
    <script>
        window.leadShowModal = function ($modal) {
            if (!$modal || !$modal.length || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                return;
            }
            bootstrap.Modal.getOrCreateInstance($modal[0]).show();
        };

        window.leadHideModal = function ($modal) {
            if (!$modal || !$modal.length || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                return;
            }
            var instance = bootstrap.Modal.getInstance($modal[0]);
            if (instance) {
                instance.hide();
            }
        };

        (function ($) {
            "use strict";

            $(function () {
                var $customerModal = $('#leadCustomerModal');
                if (typeof initZoneTreeSelect2 === 'function') {
                    initZoneTreeSelect2($('#lead-zone-select'), $customerModal.length ? { dropdownParent: $customerModal } : {});
                }
                $('.lead-area-select').each(function () {
                    var $el = $(this);
                    var opts = {
                        width: '100%',
                        tags: true,
                        placeholder: $el.data('placeholder') || '',
                        allowClear: true
                    };
                    var $modal = $el.closest('.modal');
                    if ($modal.length) {
                        opts.dropdownParent = $modal;
                    }
                    $el.select2(opts);
                });
            });
        })(jQuery);
    </script>
    <script>
        (function ($) {
            "use strict";
            $(document).on('click', '.lead-card-edit-btn', function () {
                var $card = $(this).closest('.panel, .card, .action-card');
                $card.find('.lead-card-view').addClass('d-none');
                $card.find('.lead-card-edit').removeClass('d-none');
                if (typeof window.applyFollowupFutureMin === 'function') {
                    window.applyFollowupFutureMin($card);
                }
                $card.find('.lead-card-edit .js-select').each(function () {
                    if ($(this).data('select2')) $(this).select2('destroy');
                    $(this).select2({ width: '100%' });
                });
            });
            $(document).on('click', '.lead-card-cancel', function () {
                var $card = $(this).closest('.panel, .card, .action-card');
                $card.find('.lead-card-edit').addClass('d-none');
                $card.find('.lead-card-view').removeClass('d-none');
                $card.find('.lead-card-edit .js-select').each(function () {
                    if ($(this).data('select2')) $(this).select2('destroy');
                });
            });

            var $assigneeView = $('#lead-assignee-view');
            var $assigneeEdit = $('#lead-assignee-edit');
            var $assigneeEditBtn = $('#lead-assignee-edit-btn');
            var $assigneeCancelBtn = $('#lead-assignee-cancel-btn');
            var $assigneeSelect = $('#lead-assignee-select');

            if ($assigneeView.length && $assigneeEdit.length) {
                function openAssigneeEdit() {
                    $assigneeView.addClass('d-none');
                    $assigneeEdit.removeClass('d-none');
                    $assigneeEditBtn.addClass('d-none');
                    $assigneeCancelBtn.removeClass('d-none');
                    if ($assigneeSelect.length && !$assigneeSelect.data('select2')) {
                        $assigneeSelect.select2({ width: '100%' });
                    }
                }

                function closeAssigneeEdit() {
                    $assigneeEdit.addClass('d-none');
                    $assigneeView.removeClass('d-none');
                    $assigneeCancelBtn.addClass('d-none');
                    $assigneeEditBtn.removeClass('d-none');
                    if ($assigneeSelect.length && $assigneeSelect.data('select2')) {
                        $assigneeSelect.select2('destroy');
                    }
                }

                $assigneeEditBtn.on('click', openAssigneeEdit);
                $assigneeCancelBtn.on('click', closeAssigneeEdit);
            }

            var providerSearchUrl = @json(route('admin.lead.search-providers'));
            var $tempProviderView = $('#lead-temporary-provider-view');
            var $tempProviderEdit = $('#lead-temporary-provider-edit');
            var $tempProviderEditBtn = $('#lead-temporary-provider-edit-btn');
            var $tempProviderCancelBtn = $('#lead-temporary-provider-cancel-btn');
            var $tempProviderSelect = $('#lead-temporary-provider-select');
            var $tempProviderClearBtn = $('#lead-temporary-provider-clear-btn');

            function destroyTempProviderSelect2() {
                if ($tempProviderSelect.length && $tempProviderSelect.data('select2')) {
                    $tempProviderSelect.select2('destroy');
                }
            }

            function initTempProviderSelect2() {
                if (!$tempProviderSelect.length) {
                    return;
                }
                destroyTempProviderSelect2();
                var selected = $tempProviderSelect.data('selected') || '';
                var tempProviderSelectOpts = {
                    width: '100%',
                    allowClear: true,
                    placeholder: $tempProviderSelect.data('placeholder') || '',
                    ajax: {
                        url: providerSearchUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term || '',
                                selected: selected,
                            };
                        },
                        processResults: function (data) {
                            return data;
                        },
                        cache: true,
                    },
                    minimumInputLength: 0,
                    dropdownParent: $(document.body),
                };
                $tempProviderSelect.select2(tempProviderSelectOpts);

                if (selected) {
                    $.get(providerSearchUrl, {
                        selected: selected,
                    }, function (data) {
                        var match = (data.results || []).find(function (item) {
                            return String(item.id) === String(selected);
                        });
                        if (match) {
                            var option = new Option(match.text, match.id, true, true);
                            $tempProviderSelect.append(option).trigger('change');
                        }
                    });
                }
            }

            if ($tempProviderView.length && $tempProviderEdit.length) {
                function openTempProviderEdit() {
                    $tempProviderView.addClass('d-none');
                    $tempProviderEdit.removeClass('d-none');
                    $tempProviderEditBtn.addClass('d-none');
                    $tempProviderCancelBtn.removeClass('d-none');
                    initTempProviderSelect2();
                }

                function closeTempProviderEdit() {
                    destroyTempProviderSelect2();
                    $tempProviderEdit.addClass('d-none');
                    $tempProviderView.removeClass('d-none');
                    $tempProviderCancelBtn.addClass('d-none');
                    $tempProviderEditBtn.removeClass('d-none');
                }

                $tempProviderEditBtn.on('click', openTempProviderEdit);
                $tempProviderCancelBtn.on('click', closeTempProviderEdit);
                $tempProviderClearBtn.on('click', function () {
                    $tempProviderSelect.val(null).trigger('change');
                    $(this).closest('form').trigger('submit');
                });
            }
        })(jQuery);
    </script>
    <script>
        (function ($) {
            "use strict";

            var providerSearchUrl = @json(route('admin.lead.search-providers'));

            var followupModalLabels = {
                take: @json(translate('Take_Follow_up')),
                add: @json(translate('Add_Follow_up')),
                nextFollowup: @json(translate('Next_Follow_up_Date')),
                rescheduleTo: @json(translate('Reschedule_to')),
                saveChanges: @json(translate('Save_changes')),
                reschedule: @json(translate('Reschedule')),
                callChannel: @json(\Modules\LeadManagement\Entities\LeadFollowup::CHANNEL_CALL),
                thisFollowup: @json(translate('This_Follow_up')),
                rescheduleDetails: @json(translate('Reschedule_Details'))
            };

            function toggleFollowupRecordingField() {
                var channel = $('#followup-contact-channel').val();
                var $group = $('#followup-recording-group');
                var $input = $('#followup-recording-input');
                var showRecording = channel === followupModalLabels.callChannel
                    && !$('#followup-datetime-group').hasClass('d-none');

                if (showRecording) {
                    $group.removeClass('d-none');
                } else {
                    $group.addClass('d-none');
                    $input.val('');
                }
            }

            function defaultNextFollowupLocal() {
                var d = new Date();
                d.setDate(d.getDate() + 1);
                d.setHours(10, 0, 0, 0);
                d.setMinutes(d.getMinutes() - d.getTimezoneOffset());

                return d.toISOString().slice(0, 16);
            }

            function configureFollowupModal(mode) {
                mode = mode || 'add';
                $('#followup-mode-input').val(mode);

                if (mode === 'take') {
                    $('#addFollowupModalLabel').text(followupModalLabels.take);
                    $('#followup-status-group').removeClass('d-none');
                    $('#next-followup-input').val(defaultNextFollowupLocal());
                } else {
                    $('#addFollowupModalLabel').text(followupModalLabels.add);
                    $('#followup-status-group').addClass('d-none');
                    $('#followup-action-taken').prop('checked', true);
                }

                toggleFollowupActionFields();
            }

            function toggleFollowupRemarksRequired() {
                var mode = $('#followup-mode-input').val() || 'add';
                var action = $('input[name="followup_action"]:checked').val() || '{{ \Modules\LeadManagement\Entities\LeadFollowup::STATUS_TAKEN }}';
                var isTakeTaken = mode === 'take'
                    && action !== '{{ \Modules\LeadManagement\Entities\LeadFollowup::STATUS_RESCHEDULE }}';
                var $label = $('#followup-remarks-label');
                var $input = $('#followup-remarks-input');

                $input.prop('required', isTakeTaken);
                if (isTakeTaken) {
                    if (!$label.find('.text-danger').length) {
                        $label.append(' <span class="text-danger">*</span>');
                    }
                } else {
                    $label.find('.text-danger').remove();
                }
            }

            function toggleFollowupActionFields() {
                var mode = $('#followup-mode-input').val() || 'add';
                var action = $('input[name="followup_action"]:checked').val() || '{{ \Modules\LeadManagement\Entities\LeadFollowup::STATUS_TAKEN }}';
                var isReschedule = mode === 'take' && action === '{{ \Modules\LeadManagement\Entities\LeadFollowup::STATUS_RESCHEDULE }}';

                $('#followup-datetime-group, #followup-channel-group').toggleClass('d-none', isReschedule);
                $('#followup-at-input').prop('required', !isReschedule);

                if (isReschedule) {
                    $('#followup-recording-input').val('');
                    $('#followup-recording-group').addClass('d-none');
                    $('#followup-current-section-title').text(followupModalLabels.rescheduleDetails);
                    $('#next-followup-label').html(followupModalLabels.rescheduleTo + ' <span class="text-danger">*</span>');
                    $('#followup-submit-btn').text(followupModalLabels.reschedule);
                } else {
                    $('#followup-current-section-title').text(followupModalLabels.thisFollowup);
                    $('#next-followup-label').html(followupModalLabels.nextFollowup + ' <span class="text-danger">*</span>');
                    $('#followup-submit-btn').text(followupModalLabels.saveChanges);
                    toggleFollowupRecordingField();
                }

                toggleFollowupRemarksRequired();
                applyFollowupFutureMin($('#addFollowupModal'));
            }

            function localFollowupScheduleMin() {
                var now = new Date();
                now.setSeconds(0, 0);
                now.setMilliseconds(0);
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                return now.toISOString().slice(0, 16);
            }

            function applyFollowupFutureMin($root) {
                var min = localFollowupScheduleMin();
                ($root && $root.length ? $root : $(document)).find('input.js-followup-future-only').each(function () {
                    var $input = $(this);
                    $input.attr('min', min);
                    if ($input.val() && $input.val() < min) {
                        var fallback = $input.data('default');
                        $input.val(fallback && fallback >= min ? fallback : min);
                    }
                });
            }

            window.applyFollowupFutureMin = applyFollowupFutureMin;

            $(function () {
                var $modal = $('#addFollowupModal');

                $modal.on('show.bs.modal', function (event) {
                    var $trigger = $(event.relatedTarget);
                    var mode = $trigger.data('followup-mode') || 'add';
                    configureFollowupModal(mode);
                    applyFollowupFutureMin($modal);
                });

                $(document).on('change', '#followup-contact-channel', toggleFollowupRecordingField);
                $(document).on('change', 'input[name="followup_action"]', toggleFollowupActionFields);

                @if($errors->any() && (old('followup_mode') || $errors->has('contact_channel') || $errors->has('recording') || $errors->has('next_followup_at')))
                configureFollowupModal(@json(old('followup_mode', 'add')));
                leadShowModal($modal);
                @endif

                @if($errors->any() && old('call_log_form'))
                configureCallLogModal(@json(old('call_log_mode', 'add')), @json(old('call_log_followup_id')));
                leadShowModal($('#addCallLogModal'));
                @endif
            });

            (function () {
                var $callLogModal = $('#addCallLogModal');
                var $callLogForm = $('#add-call-log-form');
                var $callLogProviderSelect = $('#call-log-provider-select');
                var $callLogProviderPreview = $('#call-log-provider-preview');
                var $callLogCurrentRecording = $('#call-log-current-recording');
                var callLogProviderData = {};
                var callLogLabels = {
                    addTitle: @json(translate('Add_Call_Log')),
                    editTitle: @json(translate('Edit_Call_Log')),
                    addSubmit: @json(translate('Add_Call_Log')),
                    editSubmit: @json(translate('Update')),
                    currentRecording: @json(translate('Current_recording')),
                    replaceRecording: @json(translate('Upload_new_recording_to_replace')),
                };

                function destroyCallLogProviderSelect2() {
                    if ($callLogProviderSelect.length && $callLogProviderSelect.data('select2')) {
                        $callLogProviderSelect.select2('destroy');
                    }
                }

                function updateCallLogProviderPreview() {
                    var selectedId = $callLogProviderSelect.val();
                    var selected = selectedId ? callLogProviderData[String(selectedId)] : null;

                    if (!selected || (!selected.name && !selected.phone)) {
                        $callLogProviderPreview.addClass('d-none').text('');
                        return;
                    }

                    var parts = [];
                    if (selected.name) {
                        parts.push(selected.name);
                    }
                    if (selected.phone) {
                        parts.push(selected.phone);
                    }

                    $callLogProviderPreview.removeClass('d-none').text(parts.join(' · '));
                }

                function initCallLogProviderSelect2() {
                    if (!$callLogProviderSelect.length) {
                        return;
                    }

                    destroyCallLogProviderSelect2();

                    var selected = $callLogProviderSelect.data('selected') || '';
                    $callLogProviderSelect.select2({
                        width: '100%',
                        allowClear: true,
                        placeholder: $callLogProviderSelect.data('placeholder') || '',
                        ajax: {
                            url: providerSearchUrl,
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term || '',
                                };
                            },
                            processResults: function (data) {
                                (data.results || []).forEach(function (item) {
                                    callLogProviderData[String(item.id)] = {
                                        name: item.name || '',
                                        phone: item.phone || '',
                                    };
                                });

                                return data;
                            },
                            cache: true,
                        },
                        minimumInputLength: 0,
                        dropdownParent: $(document.body),
                    });

                    $callLogProviderSelect.off('change.callLogProvider').on('change.callLogProvider', updateCallLogProviderPreview);

                    if (selected) {
                        $.get(providerSearchUrl, { selected: selected }, function (data) {
                            var match = (data.results || []).find(function (item) {
                                return String(item.id) === String(selected);
                            });

                            if (match) {
                                callLogProviderData[String(match.id)] = {
                                    name: match.name || '',
                                    phone: match.phone || '',
                                };
                                var option = new Option(match.text, match.id, true, true);
                                $callLogProviderSelect.append(option).trigger('change');
                            }
                        });
                    }
                }

                function toggleCallLogPartyPanels() {
                    var partyType = $('input[name="called_party_type"]:checked').val() || 'customer';

                    $('.call-log-party-panel').addClass('d-none');
                    $('.call-log-party-panel--' + partyType).removeClass('d-none');

                    if (partyType === 'provider') {
                        window.setTimeout(initCallLogProviderSelect2, 0);
                    } else {
                        destroyCallLogProviderSelect2();
                        $callLogProviderPreview.addClass('d-none').text('');
                    }
                }

                function setCallLogCurrentRecording(hasRecording, recordingName) {
                    if (!hasRecording) {
                        $callLogCurrentRecording.addClass('d-none').text('');
                        return;
                    }

                    var label = callLogLabels.currentRecording;
                    if (recordingName) {
                        label += ': ' + recordingName;
                    }
                    label += '. ' + callLogLabels.replaceRecording;
                    $callLogCurrentRecording.removeClass('d-none').text(label);
                }

                window.configureCallLogModal = function (mode, followupId, payload) {
                    mode = mode || 'add';
                    payload = payload || {};

                    var isEdit = mode === 'edit' && followupId;
                    var storeUrl = $callLogForm.data('store-url');
                    var updateTemplate = $callLogForm.data('update-url-template') || '';

                    $('#call-log-mode-input').val(mode);
                    $('#call-log-followup-id-input').val(isEdit ? followupId : '');
                    $('#call-log-method-input').val('PUT').prop('disabled', !isEdit);
                    $callLogForm.attr('action', isEdit ? updateTemplate.replace('__FOLLOWUP__', followupId) : storeUrl);
                    $('#addCallLogModalLabel').text(isEdit ? callLogLabels.editTitle : callLogLabels.addTitle);
                    $('#call-log-submit-btn').text(isEdit ? callLogLabels.editSubmit : callLogLabels.addSubmit);

                    if (!isEdit) {
                        if ($callLogForm[0]) {
                            $callLogForm[0].reset();
                        }
                        $('#call-log-mode-input').val('add');
                        $('#call-log-method-input').prop('disabled', true);
                        $callLogForm.attr('action', storeUrl);
                        $('input[name="called_party_type"][value="customer"]').prop('checked', true);
                        $('#call-log-other-name').val('');
                        $('#call-log-other-number').val('');
                        $callLogProviderSelect.data('selected', '');
                        destroyCallLogProviderSelect2();
                        $callLogProviderSelect.val('').trigger('change');
                        setCallLogCurrentRecording(false);
                        return;
                    }

                    var partyType = payload.partyType || 'customer';
                    $('input[name="called_party_type"][value="' + partyType + '"]').prop('checked', true);
                    $('#call-log-called-at-input').val(payload.calledAt || '');
                    $callLogForm.find('textarea[name="remarks"]').val(payload.remarks || '');

                    if (partyType === 'other') {
                        $('#call-log-other-name').val(payload.calledName || '');
                        $('#call-log-other-number').val(payload.calledNumber || '');
                    } else {
                        $('#call-log-other-name').val('');
                        $('#call-log-other-number').val('');
                    }

                    $callLogProviderSelect.data('selected', partyType === 'provider' ? (payload.providerId || '') : '');
                    setCallLogCurrentRecording(payload.hasRecording === '1' || payload.hasRecording === true, payload.recordingName || '');
                    toggleCallLogPartyPanels();
                };

                $(document).on('change', '.js-call-log-party-type', toggleCallLogPartyPanels);

                $(document).on('click', '.js-add-call-log-btn', function () {
                    configureCallLogModal('add');
                });

                $(document).on('click', '.js-edit-call-log-btn', function () {
                    var $btn = $(this);
                    configureCallLogModal('edit', $btn.data('followup-id'), {
                        partyType: $btn.data('party-type'),
                        providerId: $btn.data('provider-id'),
                        calledName: $btn.data('called-name'),
                        calledNumber: $btn.data('called-number'),
                        calledAt: $btn.data('called-at'),
                        remarks: $btn.data('remarks'),
                        hasRecording: String($btn.data('has-recording') || '0'),
                        recordingName: $btn.data('recording-name') || '',
                    });
                });

                $(document).on('click', '.js-delete-call-log-btn', function () {
                    var $btn = $(this);
                    if (!confirm(@json(translate('Are_you_sure')))) {
                        return;
                    }

                    var url = $btn.data('url');
                    if (!url) {
                        return;
                    }

                    $btn.prop('disabled', true);
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('delete failed');
                            }
                            window.location.reload();
                        })
                        .catch(function () {
                            $btn.prop('disabled', false);
                            if (typeof toastr !== 'undefined') {
                                toastr.error(@json(translate('Failed_to_update')));
                            }
                        });
                });

                $callLogModal.on('shown.bs.modal', function () {
                    toggleCallLogPartyPanels();
                });

                $callLogModal.on('hidden.bs.modal', function () {
                    destroyCallLogProviderSelect2();
                    $callLogProviderPreview.addClass('d-none').text('');
                    configureCallLogModal('add');
                });

                toggleCallLogPartyPanels();
            })();
        })(jQuery);
    </script>
    <script>
        (function ($) {
            "use strict";

            function formatTranscribedAt(iso) {
                if (!iso) {
                    return '';
                }
                var date = new Date(iso);
                if (Number.isNaN(date.getTime())) {
                    return iso;
                }
                return date.toLocaleString();
            }

            function escapeHtml(text) {
                return $('<div>').text(text || '').html();
            }

            function buildTranscriptHtml(transcript) {
                if (!transcript) {
                    return '';
                }

                var lines = transcript.split(/\r\n|\r|\n|(?=Support:|Customer:|User:)/i).filter(function (part) {
                    return String(part || '').trim() !== '';
                });

                return lines.map(function (line) {
                    var trimmed = String(line || '').trim().replace(/^Customer:/i, 'User:');
                    var lineClass = '';
                    if (/^User:/i.test(trimmed)) {
                        lineClass = 'voice-call-transcript-line--user';
                    } else if (/^Support:/i.test(trimmed)) {
                        lineClass = 'voice-call-transcript-line--llm';
                    }
                    return '<div class="voice-call-transcript-line ' + lineClass + '">' + escapeHtml(trimmed) + '</div>';
                }).join('');
            }

            function copyTextFallback(text, done) {
                var $temp = $('<textarea>').val(text).appendTo('body').select();
                try {
                    document.execCommand('copy');
                    if (typeof done === 'function') {
                        done();
                    }
                } catch (e) {
                    // ignore
                }
                $temp.remove();
            }

            function bindFollowupCopyButtons($scope) {
                $scope.find('.voice-call-copy-btn[data-copy-b64]').off('click.followupCopy').on('click.followupCopy', function () {
                    var encoded = $(this).attr('data-copy-b64') || '';
                    var text = '';
                    try {
                        text = atob(encoded);
                    } catch (e) {
                        return;
                    }

                    var done = function () {
                        toastr.success(@json(translate('Copied')));
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(done).catch(function () {
                            copyTextFallback(text, done);
                        });
                    } else {
                        copyTextFallback(text, done);
                    }
                });
            }

            function pauseFollowupRecordings($scope) {
                $scope.find('.voice-call-audio-player').each(function () {
                    this.pause();
                });
            }

            function bindChangeHistoryRowHighlights() {
                $(document).off('click.changeHistoryRow', '.lead-change-history-table tbody tr.lead-change-history-row')
                    .on('click.changeHistoryRow', '.lead-change-history-table tbody tr.lead-change-history-row', function () {
                        var $row = $(this);
                        var $table = $row.closest('.lead-change-history-table');
                        var wasOpen = $row.hasClass('is-open');
                        $table.find('tr.lead-change-history-row.is-open').removeClass('is-open');
                        if (!wasOpen) {
                            $row.addClass('is-open');
                        }
                    });
            }

            function bindFollowupDetailToggles() {
                $(document).off('click.followupDetails', '.lead-followup-history-table .voice-call-details-toggle, .lead-call-log-table .voice-call-details-toggle')
                    .on('click.followupDetails', '.lead-followup-history-table .voice-call-details-toggle, .lead-call-log-table .voice-call-details-toggle', function () {
                        var $btn = $(this);
                        var $row = $btn.closest('tr');
                        var $table = $btn.closest('.lead-followup-history-table, .lead-call-log-table');
                        var $detailsRow = $row.nextAll('tr.voice-call-details-row').first();
                        if (!$detailsRow.length) {
                            return;
                        }

                        var isHidden = $detailsRow.hasClass('d-none');

                        if (isHidden) {
                            $table.find('tr.lead-followup-row.is-open, tr.lead-call-log-row.is-open').removeClass('is-open');
                            $table.find('tr.voice-call-details-row').addClass('d-none');
                            $table.find('.voice-call-details-toggle[aria-expanded="true"]').each(function () {
                                $(this).attr('aria-expanded', 'false').text(@json(translate('View')));
                            });
                            pauseFollowupRecordings($table.find('tr.voice-call-details-row'));
                        }

                        $detailsRow.toggleClass('d-none', !isHidden);
                        $row.toggleClass('is-open', isHidden);
                        $btn.attr('aria-expanded', isHidden ? 'true' : 'false');
                        $btn.text(isHidden ? @json(translate('Hide')) : @json(translate('View')));

                        if (isHidden) {
                            bindFollowupCopyButtons($detailsRow);
                        } else {
                            pauseFollowupRecordings($detailsRow);
                        }
                    });

                $(document).off('click.followupTimelineDetails', '#lead-activity-timeline .voice-call-details-toggle')
                    .on('click.followupTimelineDetails', '#lead-activity-timeline .voice-call-details-toggle', function () {
                        var $btn = $(this);
                        var $inline = $btn.closest('.timeline-content').find('.voice-call-details-inline').first();
                        if (!$inline.length) {
                            return;
                        }

                        var isHidden = $inline.hasClass('d-none');
                        $inline.toggleClass('d-none', !isHidden);
                        $btn.attr('aria-expanded', isHidden ? 'true' : 'false');
                        $btn.text(isHidden ? @json(translate('Hide')) : @json(translate('View')));

                        if (isHidden) {
                            bindFollowupCopyButtons($inline);
                        } else {
                            pauseFollowupRecordings($inline);
                        }
                    });
            }

            function renderFollowupTranscriptPanel($panel, data) {
                $panel.find('.followup-recording-summary').text(data.summary || @json(translate('No_call_summary_available')));

                var transcriptHtml = buildTranscriptHtml(data.transcript || '');
                var $transcriptCard = $panel.find('.voice-call-detail-box').last();
                $transcriptCard.find('.card-body')
                    .removeClass('p-3')
                    .addClass('p-0')
                    .html('<div class="voice-call-transcript followup-recording-transcript-wrap">' + transcriptHtml + '</div>');

                var $meta = $panel.find('.followup-transcript-meta');
                $meta.removeClass('d-none').text(
                    @json(translate('Transcribed_by')) + ' ' + @json(translate('Google_Gemini_AI'))
                    + (data.transcribed_at ? ' · ' + formatTranscribedAt(data.transcribed_at) : '')
                );

                $panel.find('.js-transcribe-followup-recording[data-has-transcript="0"]').remove();

                var transcribeUrl = String($panel.data('transcribe-url') || '');
                if (!$panel.find('.js-transcribe-followup-recording[data-force="1"]').length && data.transcript && transcribeUrl !== '') {
                    var $regenerateBtn = $('<button type="button" class="btn btn-sm btn-outline-secondary js-transcribe-followup-recording"></button>')
                        .attr('data-followup-id', $panel.data('followup-id'))
                        .attr('data-url', transcribeUrl)
                        .attr('data-force', '1')
                        .attr('data-has-transcript', '1')
                        .text(@json(translate('Regenerate')));
                    $panel.find('.voice-call-extracted-card .voice-call-detail-box__header').append($regenerateBtn);
                }

                var $transcriptHeaderActions = $transcriptCard.find('.voice-call-detail-box__header .d-flex.align-items-center.gap-1');
                $transcriptHeaderActions.find('.js-transcribe-followup-recording[data-has-transcript="0"]').remove();

                if (data.summary && !$panel.find('.voice-call-summary-card .voice-call-copy-btn').length) {
                    var $copyBtn = $('<button type="button" class="voice-call-copy-btn"></button>')
                        .attr('title', @json(translate('Copy')))
                        .html('<span class="material-icons" aria-hidden="true">content_copy</span>');
                    $copyBtn.attr('data-copy-b64', btoa(unescape(encodeURIComponent(data.summary))));
                    $panel.find('.voice-call-summary-card .voice-call-detail-box__header').append($copyBtn);
                }

                if (data.transcript && !$transcriptHeaderActions.find('.voice-call-transcript-copy-btn').length) {
                    var $transcriptCopy = $('<button type="button" class="voice-call-copy-btn voice-call-transcript-copy-btn"></button>')
                        .attr('title', @json(translate('Copy')))
                        .html('<span class="material-icons" aria-hidden="true">content_copy</span>');
                    $transcriptCopy.attr('data-copy-b64', btoa(unescape(encodeURIComponent(data.transcript))));
                    $transcriptHeaderActions.append($transcriptCopy);
                }

                bindFollowupCopyButtons($panel);
            }

            bindFollowupDetailToggles();
            bindChangeHistoryRowHighlights();
            bindFollowupCopyButtons($('.lead-followup-history-table'));

            function renderInitialCallTranscriptPanel($panel, data) {
                var summaryText = data.summary || @json(translate('No_call_summary_available'));
                $panel.find('.initial-call-recording-summary').text(summaryText);
                $panel.find('.initial-call-transcription-card .voice-call-extracted-body > p.text-muted').first().text(summaryText);

                var transcriptHtml = buildTranscriptHtml(data.transcript || '');
                var $transcriptCard = $panel.find('.voice-call-detail-box').last();
                $transcriptCard.find('.card-body')
                    .removeClass('p-3')
                    .addClass('p-0')
                    .html('<div class="voice-call-transcript initial-call-recording-transcript-wrap">' + transcriptHtml + '</div>');

                var $meta = $panel.find('.initial-call-transcript-meta');
                $meta.removeClass('d-none').text(
                    @json(translate('Transcribed_by')) + ' ' + @json(translate('Google_Gemini_AI'))
                    + (data.transcribed_at ? ' · ' + formatTranscribedAt(data.transcribed_at) : '')
                );

                $panel.find('.js-transcribe-initial-call-recording[data-has-transcript="0"]').remove();

                var transcribeUrl = String($panel.data('transcribe-url') || '');
                if (!$panel.find('.js-transcribe-initial-call-recording[data-force="1"]').length && data.transcript && transcribeUrl !== '') {
                    var $regenerateBtn = $('<button type="button" class="btn btn-sm btn-outline-secondary js-transcribe-initial-call-recording"></button>')
                        .attr('data-lead-id', $panel.data('lead-id'))
                        .attr('data-url', transcribeUrl)
                        .attr('data-force', '1')
                        .attr('data-has-transcript', '1')
                        .text(@json(translate('Regenerate')));
                    $panel.find('.initial-call-transcription-card .voice-call-detail-box__header').append($regenerateBtn);
                }

                var $transcriptHeaderActions = $transcriptCard.find('.voice-call-detail-box__header .d-flex.align-items-center.gap-1');
                $transcriptHeaderActions.find('.js-transcribe-initial-call-recording[data-has-transcript="0"]').remove();

                if (data.summary && !$panel.find('.voice-call-summary-card .voice-call-copy-btn').length) {
                    var $copyBtn = $('<button type="button" class="voice-call-copy-btn"></button>')
                        .attr('title', @json(translate('Copy')))
                        .html('<span class="material-icons" aria-hidden="true">content_copy</span>');
                    $copyBtn.attr('data-copy-b64', btoa(unescape(encodeURIComponent(data.summary))));
                    $panel.find('.voice-call-summary-card .voice-call-detail-box__header').append($copyBtn);
                }

                if (data.transcript && !$transcriptHeaderActions.find('.voice-call-transcript-copy-btn').length) {
                    var $transcriptCopy = $('<button type="button" class="voice-call-copy-btn voice-call-transcript-copy-btn"></button>')
                        .attr('title', @json(translate('Copy')))
                        .html('<span class="material-icons" aria-hidden="true">content_copy</span>');
                    $transcriptCopy.attr('data-copy-b64', btoa(unescape(encodeURIComponent(data.transcript))));
                    $transcriptHeaderActions.append($transcriptCopy);
                }

                bindFollowupCopyButtons($panel);
            }

            function bindInitialCallRecordingToggles() {
                $(document).off('click.initialCallRecording', '.initial-call-recording-toggle')
                    .on('click.initialCallRecording', '.initial-call-recording-toggle', function () {
                        var $btn = $(this);
                        var $inline = $btn.closest('.initial-call-recording-section').find('.voice-call-details-inline').first();
                        if (!$inline.length) {
                            return;
                        }

                        var isHidden = $inline.hasClass('d-none');
                        $inline.toggleClass('d-none', !isHidden);
                        $btn.attr('aria-expanded', isHidden ? 'true' : 'false');
                        $btn.text(isHidden ? @json(translate('Hide')) : @json(translate('View')));

                        if (isHidden) {
                            bindFollowupCopyButtons($inline);
                        } else {
                            pauseFollowupRecordings($inline);
                        }
                    });
            }

            bindInitialCallRecordingToggles();
            bindFollowupCopyButtons($('.initial-call-recording-section'));

            $(document).on('click', '.js-transcribe-initial-call-recording', function () {
                var $btn = $(this);
                var leadId = $btn.data('lead-id');
                var url = $btn.data('url');
                var force = String($btn.data('force')) === '1';
                var $panel = $('#initial-call-transcript-panel-' + leadId);
                var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();

                var originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + @json(translate('Transcribing')));

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        force: force ? '1' : '0'
                    }
                }).done(function (response) {
                    if (!response || !response.success) {
                        toastr.error((response && response.message) ? response.message : @json(translate('Failed_to_transcribe_recording')));
                        return;
                    }

                    renderInitialCallTranscriptPanel($panel, response);

                    if (force || !response.from_cache) {
                        toastr.success(response.message || @json(translate('Recording_transcribed_successfully')));
                    }
                }).fail(function (xhr) {
                    var message = @json(translate('Failed_to_transcribe_recording'));
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    toastr.error(message);
                }).always(function () {
                    $btn.prop('disabled', false).html(originalHtml);
                });
            });

            $(document).on('click', '.js-transcribe-followup-recording', function () {
                var $btn = $(this);
                var followupId = $btn.data('followup-id');
                var url = $btn.data('url');
                var force = String($btn.data('force')) === '1';
                var $panel = $('#followup-transcript-panel-' + followupId);
                var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();

                var originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + @json(translate('Transcribing')));

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        force: force ? '1' : '0'
                    }
                }).done(function (response) {
                    if (!response || !response.success) {
                        toastr.error((response && response.message) ? response.message : @json(translate('Failed_to_transcribe_recording')));
                        return;
                    }

                    renderFollowupTranscriptPanel($panel, response);

                    if (force || !response.from_cache) {
                        toastr.success(response.message || @json(translate('Recording_transcribed_successfully')));
                    }
                }).fail(function (xhr) {
                    var message = @json(translate('Failed_to_transcribe_recording'));
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    toastr.error(message);
                }).always(function () {
                    $btn.prop('disabled', false).html(originalHtml);
                });
            });
        })(jQuery);
    </script>
    <script>
        (function () {
            "use strict";
            var $customerModal = $('#leadCustomerModal');

            function getCustomerSelects() {
                return {
                    zone: $customerModal.find('[name="zone_id"]'),
                    category: $customerModal.find('[name="service_category"]'),
                    subcategory: $customerModal.find('[name="service_subcategory"]'),
                    service: $customerModal.find('[name="service_name"]'),
                    variant: $customerModal.find('[name="variant_key"]')
                };
            }

            function refreshCustomerCatalogSelect2($el) {
                if (!$el || !$el.length || !$.fn.select2) {
                    return;
                }
                var val = $el.val();
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.off('select2:select').select2('destroy');
                }
                var opts = { width: '100%' };
                if ($customerModal.length) {
                    opts.dropdownParent = $customerModal;
                }
                $el.select2(opts);
                if (val !== null && val !== undefined && String(val) !== '') {
                    $el.val(val).trigger('change.select2');
                }
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
                refreshCustomerCatalogSelect2(s.category);
                refreshCustomerCatalogSelect2(s.subcategory);
                refreshCustomerCatalogSelect2(s.service);
                refreshCustomerCatalogSelect2(s.variant);
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
                    refreshCustomerCatalogSelect2(s.category);
                    if (onLoaded) onLoaded(); else s.category.trigger('change');
                });
                s.subcategory.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true)).trigger('change');
                s.service.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service') }}', '', true, true)).trigger('change');
                s.variant.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
                refreshCustomerCatalogSelect2(s.subcategory);
                refreshCustomerCatalogSelect2(s.service);
                refreshCustomerCatalogSelect2(s.variant);
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
                    refreshCustomerCatalogSelect2(s.subcategory);
                    refreshCustomerCatalogSelect2(s.service);
                    refreshCustomerCatalogSelect2(s.variant);
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
                    refreshCustomerCatalogSelect2(s.subcategory);
                    if (onLoaded) onLoaded(); else s.subcategory.trigger('change');
                });
                s.service.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service') }}', '', true, true)).trigger('change');
                s.variant.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
                refreshCustomerCatalogSelect2(s.service);
                refreshCustomerCatalogSelect2(s.variant);
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
                    refreshCustomerCatalogSelect2(s.service);
                    refreshCustomerCatalogSelect2(s.variant);
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
                    refreshCustomerCatalogSelect2(s.service);
                    if (onLoaded) onLoaded(); else s.service.trigger('change');
                });
                s.variant.prop('disabled', true).empty()
                    .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
                refreshCustomerCatalogSelect2(s.variant);
            }

            function loadVariants(onLoaded) {
                var s = getCustomerSelects();
                var serviceId = (s.service.val() || '').toString().trim();
                var zoneId = (s.zone.val() || '').toString().trim();
                if (!serviceId || !zoneId) {
                    s.variant.prop('disabled', true).empty()
                        .append(new Option('{{ translate('Select_Service_Variant') }}', '', true, true)).trigger('change');
                    refreshCustomerCatalogSelect2(s.variant);
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
                    refreshCustomerCatalogSelect2(s.variant);
                    if (onLoaded) onLoaded(); else s.variant.trigger('change');
                }).fail(function () {
                    s.variant.empty()
                        .append(new Option('{{ translate('Failed_to_load') }}', '', true, true)).trigger('change');
                    refreshCustomerCatalogSelect2(s.variant);
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
                        if (editCategory) {
                            s.category.val(String(editCategory)).trigger('change');
                            refreshCustomerCatalogSelect2(s.category);
                        }
                        loadSubcategories(function () {
                            if (editSubcategory) {
                                s.subcategory.val(String(editSubcategory)).trigger('change');
                                refreshCustomerCatalogSelect2(s.subcategory);
                            }
                            loadServices(function () {
                                if (editService) {
                                    s.service.val(String(editService)).trigger('change');
                                    refreshCustomerCatalogSelect2(s.service);
                                }
                                loadVariants(function () {
                                    if (editVariant) {
                                        s.variant.val(String(editVariant)).trigger('change');
                                        refreshCustomerCatalogSelect2(s.variant);
                                    }
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
                                leadHideModal($cancelModal);
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
                        leadShowModal($cancelModal);
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

            function performCustomerStatusUpdate(statusId, cancelReasonId, cancelRemarks, workflowConfirmed) {
                var baseType = getSelectedBaseType();
                $updateBtn.prop('disabled', true);
                $.ajax({
                    url: statusUpdateUrl,
                    type: 'PUT',
                    data: {
                        _token: csrfToken,
                        customer_lead_status_id: statusId || null,
                        cancellation_reason_id: baseType === 'cancel' ? (cancelReasonId || null) : null,
                        cancellation_remarks: baseType === 'cancel' ? (cancelRemarks || null) : null,
                        workflow_confirmed: workflowConfirmed ? 1 : 0
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
                                leadHideModal($cancelModal);
                            }
                            if (typeof toastr !== 'undefined') toastr.success('{{ translate('Customer_lead_information_updated_successfully') }}');
                        }
                    },
                    error: function (xhr) {
                        $updateBtn.prop('disabled', false);
                        var res = xhr.responseJSON || {};
                        if (res.workflow_gate && window.WorkflowGate) {
                            window.WorkflowGate.showConfirmModal(res.workflow_gate, @json(\Modules\AdminModule\Support\WorkflowStepDefinitions::ACTION_LEAD_STATUS_BOOKED), function () {
                                performCustomerStatusUpdate(statusId, cancelReasonId, cancelRemarks, true);
                            });
                            return;
                        }
                        if (typeof toastr !== 'undefined') toastr.error(res.message || '{{ translate('Failed_to_update') }}');
                    }
                });
            }

            function maybeGateCustomerStatusUpdate(statusId, cancelReasonId, cancelRemarks) {
                var baseType = getSelectedBaseType();
                if ((baseType === 'booked' || baseType === 'completed') && window.WorkflowGate) {
                    window.WorkflowGate.check(@json(\Modules\AdminModule\Support\WorkflowStepDefinitions::ACTION_LEAD_STATUS_BOOKED), function () {
                        performCustomerStatusUpdate(statusId, cancelReasonId, cancelRemarks, true);
                    });
                    return;
                }
                performCustomerStatusUpdate(statusId, cancelReasonId, cancelRemarks, false);
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
                        leadShowModal($cancelModal);
                    }
                    return;
                }

                maybeGateCustomerStatusUpdate(statusId, null, null);
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
                    maybeGateCustomerStatusUpdate(statusId, reasonId, remarks);
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
                    html += '<span class="tag customer-lead-tag-pill" style="background-color: ' + (t.color || '#0d6efd') + '; color: #fff;" data-tag-id="' + t.id + '" data-tag-name="' + (t.name || '') + '" data-tag-color="' + (t.color || '#0d6efd') + '">' + (t.name || '');
                    if (withRemoveButton) {
                        html += '<button type="button" class="btn btn-link p-0 m-0 ms-1 border-0 bg-transparent text-white opacity-75 customer-lead-tag-remove" style="font-size: 14px;" title="{{ translate('Remove') }}" aria-label="{{ translate('Remove') }}">&times;</button>';
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

            function initProviderZoneSelect() {
                destroySelect2(providerZoneSelect);
                if (typeof initZoneTreeSelect2 === 'function') {
                    initZoneTreeSelect2(providerZoneSelect, {
                        placeholder: '{{ translate("Select_Zone") }}',
                        closeOnSelect: false
                    });
                } else {
                    providerZoneSelect.select2({
                        width: '100%',
                        placeholder: '{{ translate("Select_Zone") }}',
                        closeOnSelect: false
                    });
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

            function providerSelectedZoneIds() {
                const v = providerZoneSelect.val();
                if (Array.isArray(v)) {
                    return v.filter(Boolean);
                }
                return v ? [v] : [];
            }

            function loadProviderCategories(onLoaded) {
                if (typeof onLoaded !== 'function') {
                    onLoaded = undefined;
                }
                const zoneIds = providerSelectedZoneIds();
                providerCategorySelect.empty().append(new Option('{{ translate("Select_Category") }}', '', true, true)).prop('disabled', !zoneIds.length);
                providerSubcategorySelect.empty().append(new Option('{{ translate("Select_Sub_Category") }}', '', true, true)).prop('disabled', true);
                destroySelect2(providerCategorySelect);
                destroySelect2(providerSubcategorySelect);
                if (!zoneIds.length) {
                    providerCategorySelect.removeClass('js-select');
                    providerSubcategorySelect.removeClass('js-select');
                    if (onLoaded) onLoaded();
                    return;
                }
                $.get(categoriesUrl, { zone_ids: zoneIds }).done(function (res) {
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
                if (typeof onLoaded !== 'function') {
                    onLoaded = undefined;
                }
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

            providerZoneSelect.on('change', function () {
                loadProviderCategories();
            });
            providerCategorySelect.on('change', function () {
                loadProviderSubcategories();
            });

            $(function () {
                if (providerZoneSelect.length) {
                    initProviderZoneSelect();
                }
            });

            $('#leadProviderModal').on('show.bs.modal', function () {
                const $modal = $(this);
                let editZoneIds = [];
                try {
                    editZoneIds = JSON.parse($modal.attr('data-edit-zone-ids') || '[]') || [];
                } catch (e) {
                    editZoneIds = [];
                }
                if (!Array.isArray(editZoneIds)) {
                    editZoneIds = [];
                }
                providerZoneSelect.val(editZoneIds);
                initProviderZoneSelect();

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

        @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER && $errors->hasAny(['customer_name', 'phone_number', 'contacted_through', 'status_id', 'handled_by', 'contacted_at', 'remarks', 'related_lead_id', 'booking_id']))
            $(function () {
                const modalEl = document.getElementById('addOutboundEnquiryModal');
                if (modalEl && window.bootstrap?.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
        @endif
    </script>
    <script>
        (function () {
            'use strict';

            if (window.__leadActivityFiltersBound) {
                return;
            }

            function getEls(root) {
                return {
                    timeline: root.querySelector('#lead-activity-timeline'),
                    table: root.querySelector('#lead-activity-table'),
                    comments: root.querySelector('#lead-activity-comments'),
                    followupSection: root.querySelector('.activity-table-section--followups'),
                    changeSection: root.querySelector('.activity-table-section--changes'),
                    callSection: root.querySelector('.activity-table-section--calls'),
                };
            }

            function filterTableSections(els, filter) {
                if (els.followupSection) {
                    els.followupSection.style.display = (filter === 'change' || filter === 'call') ? 'none' : '';
                }
                if (els.changeSection) {
                    els.changeSection.style.display = (filter === 'followup' || filter === 'call') ? 'none' : '';
                }
                if (els.callSection) {
                    els.callSection.style.display = (filter === 'followup' || filter === 'change') ? 'none' : '';
                }
            }

            function setActivePill(root, filter) {
                root.querySelectorAll('[data-activity-filter]').forEach(function (pill) {
                    pill.classList.toggle('is-active', pill.getAttribute('data-activity-filter') === filter);
                });
            }

            function getInitialActivityFilter(root) {
                var params = new URLSearchParams(window.location.search);
                var fromQuery = params.get('activity');
                if (fromQuery === 'comment' || fromQuery === 'followup' || fromQuery === 'change' || fromQuery === 'call') {
                    return fromQuery;
                }
                if (window.location.hash === '#lead-comments') {
                    return 'comment';
                }
                var active = root.querySelector('[data-activity-filter].is-active');
                return active ? active.getAttribute('data-activity-filter') : 'comment';
            }

            function applyActivityFilter(root, filter) {
                filter = filter || 'comment';
                var els = getEls(root);
                setActivePill(root, filter);

                if (filter === 'comment') {
                    if (els.timeline) {
                        els.timeline.classList.add('is-hidden');
                    }
                    if (els.table) {
                        els.table.classList.remove('is-visible');
                    }
                    if (els.comments) {
                        els.comments.classList.add('is-visible');
                    }
                    return;
                }

                if (els.comments) {
                    els.comments.classList.remove('is-visible');
                }
                if (els.timeline) {
                    els.timeline.classList.add('is-hidden');
                }
                if (els.table) {
                    els.table.classList.add('is-visible');
                }
                filterTableSections(els, filter);
            }

            function syncLeadActivityPanel(root) {
                if (!root) {
                    return;
                }
                applyActivityFilter(root, getInitialActivityFilter(root));

                var activityPanel = root.querySelector('#lead-activity');
                if (!activityPanel) {
                    return;
                }

                var params = new URLSearchParams(window.location.search);
                var hash = window.location.hash;
                if (
                    params.get('activity') === 'comment' ||
                    hash === '#lead-activity' ||
                    hash === '#lead-comments'
                ) {
                    activityPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            document.addEventListener('click', function (event) {
                var root = event.target.closest('.lead-detail-v2');
                if (!root) {
                    return;
                }

                var pill = event.target.closest('[data-activity-filter]');
                if (pill && root.contains(pill)) {
                    event.preventDefault();
                    applyActivityFilter(root, pill.getAttribute('data-activity-filter'));
                }
            });

            function bootLeadActivityPanels() {
                document.querySelectorAll('.lead-detail-v2').forEach(syncLeadActivityPanel);
            }

            document.addEventListener('DOMContentLoaded', bootLeadActivityPanels);
            document.addEventListener('admin:page-loaded', function (event) {
                var frame = event.detail && event.detail.root;
                if (!frame) {
                    bootLeadActivityPanels();
                    return;
                }
                if (frame.classList && frame.classList.contains('lead-detail-v2')) {
                    syncLeadActivityPanel(frame);
                }
                frame.querySelectorAll('.lead-detail-v2').forEach(syncLeadActivityPanel);
            });

            if (document.readyState !== 'loading') {
                bootLeadActivityPanels();
            }

            window.__leadActivityFiltersBound = true;
        })();
    </script>
    <script>
        window.staffChatEntitySearchUrl = @json(route('admin.chat.entity-search'));
    </script>
    <script src="{{ asset('assets/chatting-module/js/staff-chat-compose.js') }}"></script>
    <script>
        window.commentAttachmentsEmptyMessage = @json(translate('Please_write_a_comment_or_attach_a_file'));
        window.commentAttachmentsLoadingMessage = @json(translate('Adding'));
    </script>
    <script src="{{ asset('assets/common/js/comment-attachments.js') }}?v={{ @filemtime(public_path('assets/common/js/comment-attachments.js')) ?: time() }}"></script>
    <script>
        (function () {
            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }

            document.querySelectorAll('.lead-comment-pin-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var url = btn.getAttribute('data-url');
                    if (!url) return;
                    btn.disabled = true;
                    fetch(url, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (r) { return r.json(); })
                        .then(function () { window.location.reload(); })
                        .catch(function () {
                            btn.disabled = false;
                            if (typeof toastr !== 'undefined') toastr.error(@json(translate('Failed_to_update')));
                        });
                });
            });

            document.querySelectorAll('.lead-comment-delete-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm(@json(translate('Are_you_sure')))) return;
                    var url = btn.getAttribute('data-url');
                    if (!url) return;
                    btn.disabled = true;
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (r) {
                            if (!r.ok) throw new Error('delete failed');
                            return r.json();
                        })
                        .then(function () { window.location.reload(); })
                        .catch(function () {
                            btn.disabled = false;
                            if (typeof toastr !== 'undefined') toastr.error(@json(translate('Failed_to_update')));
                        });
                });
            });

            var commentsWrap = document.getElementById('leadCommentsListWrap');
            if (commentsWrap) {
                commentsWrap.scrollTop = commentsWrap.scrollHeight;
            }

            function resubmitLeadTypeChangeForm(oldInput) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = @json(route('admin.lead.type.update', $lead->id));
                form.style.display = 'none';

                var tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = csrfToken();
                form.appendChild(tokenInput);

                var confirmedInput = document.createElement('input');
                confirmedInput.type = 'hidden';
                confirmedInput.name = 'workflow_confirmed';
                confirmedInput.value = '1';
                form.appendChild(confirmedInput);

                Object.keys(oldInput || {}).forEach(function (key) {
                    if (key === '_token' || key === '_method' || key === 'workflow_confirmed') {
                        return;
                    }
                    var value = oldInput[key];
                    if (Array.isArray(value)) {
                        value.forEach(function (item) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key.endsWith('[]') ? key : key + '[]';
                            input.value = item;
                            form.appendChild(input);
                        });
                        return;
                    }
                    if (value === null || value === undefined || value === '') {
                        return;
                    }
                    var field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = key;
                    field.value = value;
                    form.appendChild(field);
                });

                document.body.appendChild(form);
                form.submit();
            }

            document.querySelectorAll('.workflow-gated-link').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    if (!window.WorkflowGate) return;
                    var action = link.dataset.workflowAction;
                    if (!action) return;
                    e.preventDefault();
                    window.WorkflowGate.check(action, function () {
                        var url = new URL(link.href, window.location.origin);
                        url.searchParams.set('workflow_confirmed', '1');
                        if (link.target === '_top') {
                            window.top.location.href = url.toString();
                        } else {
                            window.location.href = url.toString();
                        }
                    });
                });
            });

            @if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_UNKNOWN)
            document.querySelectorAll(
                '#leadInvalidModal form, #leadFutureCustomerModal form, #leadCustomerModal form, #leadProviderModal form'
            ).forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    if (!window.WorkflowGate) {
                        return;
                    }
                    if (form.querySelector('input[name="workflow_confirmed"]')?.value === '1') {
                        return;
                    }
                    e.preventDefault();
                    window.WorkflowGate.submitFormWithConfirmation(
                        form,
                        @json(\Modules\AdminModule\Support\WorkflowStepDefinitions::ACTION_LEAD_TYPE_CHANGE)
                    );
                });
            });
            @endif

            @if(session('workflow_gate'))
            $(function () {
                var gateAction = @json(session('workflow_gate_action'));
                var gateData = @json(session('workflow_gate'));
                if (!window.WorkflowGate || !gateAction) return;

                if (gateAction === @json(\Modules\AdminModule\Support\WorkflowStepDefinitions::ACTION_LEAD_CREATE_BOOKING)) {
                    window.WorkflowGate.showConfirmModal(gateData, gateAction, function () {
                        window.location.href = '{{ route('admin.booking.create-from-lead', ['lead' => $lead->id, 'context' => !empty($inModal) ? 'lead_modal' : 'lead', 'workflow_confirmed' => 1]) }}';
                    });
                } else if (gateAction === @json(\Modules\AdminModule\Support\WorkflowStepDefinitions::ACTION_LEAD_TYPE_CHANGE)) {
                    var oldTypeChangeInput = @json(old() ?: []);
                    window.WorkflowGate.showConfirmModal(gateData, gateAction, function () {
                        resubmitLeadTypeChangeForm(oldTypeChangeInput);
                    });
                }
            });
            @endif

            @if(session('workflow_post_action'))
            $(function () {
                var postAction = @json(session('workflow_post_action_action'));
                var postData = @json(session('workflow_post_action'));
                if (!window.WorkflowGate || !postAction) return;

                window.WorkflowGate.showConfirmModal(postData, postAction, function () {
                    window.location.reload();
                }, 'post');
            });
            @endif
        })();
    </script>
@endpush

@if($lead->lead_type === \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER)
    @include('leadmanagement::admin.outbound-enquiries.partials._form_script')
@endif

