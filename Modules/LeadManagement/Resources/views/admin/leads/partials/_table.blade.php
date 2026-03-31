@php
    $isProviderTab = isset($tab) && $tab === 'provider';
    $isCustomerTab = isset($tab) && $tab === 'customer';
    $isReasonTab = isset($tab) && in_array($tab, ['invalid', 'future_customer'], true);
    $providerLeadData = $providerLeadData ?? [];
    $customerLeadData = $customerLeadData ?? [];
    $reasonLeadData = $reasonLeadData ?? [];
@endphp
<div class="card">
    <div class="card-body">
        <div class="table-responsive overflow-auto">
            <table class="table align-middle table-leads-fixed-layout">
                <thead>
                <tr>
                <th>{{ translate('ID') }}</th>
                <th>{{ translate('Name') }}</th>
                <th>{{ translate('Phone') }}</th>
                @if($isProviderTab)
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Provider_cancellation_reasons') }}</th>
                    <th>{{ translate('District') }}</th>
                    <th>{{ translate('Zone') }}</th>
                    <th>{{ translate('Service_Category') }}</th>
                    <th>{{ translate('Checklist_Done_Items') }}</th>
                @elseif($isCustomerTab)
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Booking_ID') }}</th>
                        <th>{{ translate('Customer_cancellation_reasons') }}</th>
                        <th>{{ translate('Zone') }}</th>
                        <th>{{ translate('Category') }}</th>
                        <th>{{ translate('Sub_Category') }}</th>
                        <th>{{ translate('Estimated_Date_Time_of_Service') }}</th>
                    @elseif($isReasonTab)
                        <th>{{ translate('Source') }}</th>
                        <th>{{ translate('Reason') }}</th>
                    @else
                        <th>{{ translate('Source') }}</th>
                        <th>{{ translate('Lead_Type') }}</th>
                        <th>{{ translate('Ad_Source') }}</th>
                    @endif
                    <th>{{ translate('Recieved_On') }}</th>
                    <th>{{ translate('Followup_On') }}</th>
                    <th>{{ translate('Handled_By') }}</th>
                <th>{{ translate('Lead_Status') }}</th>
                    @if($isCustomerTab)
                        <th>{{ translate('Tags') }}</th>
                    @endif
                    <th class="text-center">{{ translate('Action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($leads as $key => $lead)
                    <tr>
                        <td>
                            <a href="{{ route('admin.lead.show', $lead->id) }}?in_modal=1"
                               class="link-primary btn-lead-view"
                               data-lead-url="{{ route('admin.lead.show', $lead->id) }}?in_modal=1">
                                {{ $lead->id }}
                            </a>
                        </td>
                        <td>{{ $lead->name ?? '—' }}</td>
                        <td>{{ $lead->phone_number }}</td>
                        @if($isProviderTab)
                            @php $pd = $providerLeadData[$lead->id] ?? []; @endphp
                            <td>
                                <span class="badge" style="background-color: {{ $pd['status_color'] ?? '#0d6efd' }}; color: #fff;">{{ $pd['status_name'] ?? '—' }}</span>
                            </td>
                            <td>{{ $pd['cancellation_reason'] ?? '—' }}</td>
                            <td>{{ $pd['district_name'] ?? '—' }}</td>
                            <td>{{ $pd['zone_name'] ?? '—' }}</td>
                            <td>{{ $pd['category_name'] ?? '—' }}</td>
                            <td>{{ ($pd['checklist_done'] ?? 0) . '/' . ($pd['checklist_total'] ?? 0) }}</td>
                        @elseif($isCustomerTab)
                            @php $cd = $customerLeadData[$lead->id] ?? []; @endphp
                            <td>
                                <span class="badge" style="background-color: {{ $cd['status_color'] ?? '#0d6efd' }}; color: #fff;">{{ $cd['status_name'] ?? '—' }}</span>
                            </td>
                            <td>
                                @php
                                    $bookingId = $cd['booking_id'] ?? null;
                                    $bookingReadableId = $cd['booking_readable_id'] ?? null;
                                @endphp
                                @if($bookingId)
                                    <a href="{{ route('admin.booking.details', $bookingId) }}" class="link-primary" target="_top">
                                        {{ $bookingReadableId ?: $bookingId }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $cd['cancellation_reason'] ?? '—' }}</td>
                            <td>{{ $cd['zone_name'] ?? '—' }}</td>
                            <td>{{ $cd['category_name'] ?? '—' }}</td>
                            <td>{{ $cd['sub_category_name'] ?? '—' }}</td>
                            <td>{{ $cd['estimated_service_at'] ?? '—' }}</td>
                        @elseif($isReasonTab)
                            <td>{{ $lead->source?->name ?? '—' }}</td>
                            <td>{{ $reasonLeadData[$lead->id] ?? '—' }}</td>
                        @else
                            <td>{{ $lead->source?->name ?? '—' }}</td>
                            <td>
                                @php
                                    $type = $lead->lead_type;
                                    $label = \Modules\LeadManagement\Entities\Lead::leadTypes()[$type] ?? $type;
                                    $badgeClass = match ($type) {
                                        \Modules\LeadManagement\Entities\Lead::TYPE_INVALID => 'bg-danger',
                                        \Modules\LeadManagement\Entities\Lead::TYPE_CUSTOMER => 'bg-success',
                                        \Modules\LeadManagement\Entities\Lead::TYPE_PROVIDER => 'bg-primary',
                                        \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER => 'bg-info',
                                        default => 'bg-warning',
                                    };
                                @endphp
                                <span class="badge rounded-pill {{ $badgeClass }} text-capitalize">{{ $label }}</span>
                            </td>
                            <td>{{ $lead->adSource?->name ?? '—' }}</td>
                        @endif
                        <td>{{ $lead->date_time_of_lead_received?->format('d F Y h:i a') ?? '—' }}</td>
                        <td>{{ $lead->next_followup_at?->format('d F Y h:i a') ?? '—' }}</td>
                        <td>
                            @php $handledBy = $lead->handled_by; @endphp
                            @if(!$handledBy)—@elseif(isset($handledByNames[$handledBy])){{ $handledByNames[$handledBy] }}@else{{ $handledBy }}@endif
                        </td>
                        <td>
                            @php
                                $statusMeta = $leadStatusMeta[$lead->id] ?? null;
                                $leadStatusLabel = $statusMeta['label'] ?? 'Closed';
                                $leadStatusBadgeClass = $statusMeta['badge_class'] ?? 'bg-success';
                            @endphp
                            <span class="badge rounded-pill {{ $leadStatusBadgeClass }}">{{ $leadStatusLabel }}</span>
                        </td>
                        @if($isCustomerTab)
                            <td>
                                @forelse($lead->customerLeadTags as $tag)
                                    <span class="badge me-1" style="background-color: {{ $tag->color ?? '#0d6efd' }}; color: #fff;">
                                        {{ $tag->name }}
                                    </span>
                                @empty
                                    —
                                @endforelse
                            </td>
                        @endif
                        <td class="text-center">
                            <a href="{{ route('admin.lead.show', $lead->id) }}?in_modal=1" class="btn btn-sm btn--primary btn-lead-view" data-lead-url="{{ route('admin.lead.show', $lead->id) }}?in_modal=1">
                                {{ translate('view') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isProviderTab ? 13 : ($isCustomerTab ? 16 : ($isReasonTab ? 10 : 11)) }}" class="text-center py-4">{{ translate('No_leads_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $leads->links() }}
        </div>
    </div>
</div>

