    @php
    $ctwaDisplayByPhone = $ctwaDisplayByPhone ?? [];
    $isProviderTab = isset($tab) && $tab === 'provider';
    $isCustomerTab = isset($tab) && $tab === 'customer';
    $isInvalidTab = isset($tab) && $tab === 'invalid';
    $isFutureCustomerTab = isset($tab) && $tab === 'future_customer';
    $isReasonTab = $isInvalidTab || $isFutureCustomerTab;
    $providerLeadData = $providerLeadData ?? [];
    $customerLeadData = $customerLeadData ?? [];
    $reasonLeadData = $reasonLeadData ?? [];
    $emptyColspan = match (true) {
        $isProviderTab => 14,
        $isCustomerTab => 16,
        $isFutureCustomerTab => 11,
        $isInvalidTab => 10,
        default => 11,
    };
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
                    <th>{{ translate('Is_Added_in_Panel') }}</th>
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
                        @if($isFutureCustomerTab)
                            <th>{{ translate('Outbound_Enquiries') }}</th>
                        @endif
                    @else
                        <th>{{ translate('Lead_Type') }}</th>
                        <th>{{ translate('Source') }}</th>
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
                    @php
                        $leadDetailUrl = route('admin.lead.show', $lead->id) . '?in_modal=1';
                    @endphp
                    <tr class="lead-table-row" data-lead-url="{{ $leadDetailUrl }}">
                        <td class="link-primary">{{ $lead->id }}</td>
                        <td>{{ $lead->name ?? '—' }}</td>
                        <td>{{ $lead->phone_number }}</td>
                        @if($isProviderTab)
                            @php $pd = $providerLeadData[$lead->id] ?? []; @endphp
                            <td>
                                <span class="badge" style="background-color: {{ $pd['status_color'] ?? '#0d6efd' }}; color: #fff;">{{ $pd['status_name'] ?? '—' }}</span>
                            </td>
                            <td>
                                @if(!empty($pd['panel_provider']))
                                    <a href="{{ $pd['panel_provider']['url'] }}" class="link-primary fw-medium" target="_top" title="{{ translate('View_provider_in_panel') }}">
                                        {{ $pd['panel_provider']['name'] }}
                                    </a>
                                @else
                                    <span class="text-muted">{{ translate('No_match_found') }}</span>
                                @endif
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
                            @if($isFutureCustomerTab)
                                <td>{{ $lead->outbound_enquiries_count ?? 0 }}</td>
                            @endif
                        @else
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
                            <td>{{ $lead->source?->name ?? '—' }}</td>
                            <td class="lead-ad-source-cell">
                                @php
                                    $ctwaSvc = app(\Modules\LeadManagement\Services\LeadCtwaDisplayService::class);
                                    $phoneKey = preg_replace('/\D+/', '', (string) ($lead->phone_number ?? '')) ?? '';
                                    $phoneKey = strlen($phoneKey) >= 10 ? substr($phoneKey, -10) : '';
                                    $ctwaRow = ($phoneKey !== '' && !empty($ctwaDisplayByPhone[$phoneKey] ?? null))
                                        ? $ctwaDisplayByPhone[$phoneKey]
                                        : null;
                                    $adDisplay = $ctwaSvc->resolveDisplay($lead->adSource, $ctwaRow);
                                @endphp
                                @if($adDisplay['name'] || $adDisplay['image_url'])
                                    <div class="d-flex flex-column align-items-start gap-1 py-1" style="max-width:120px;">
                                        @if($adDisplay['image_url'])
                                            @if(!empty($adDisplay['view_ad_url']))
                                                <a href="{{ $adDisplay['view_ad_url'] }}" target="_blank" rel="noopener" class="d-inline-block" title="{{ translate('View ad') }}">
                                                    <img src="{{ $adDisplay['image_url'] }}"
                                                         alt="{{ $adDisplay['name'] ?? '' }}"
                                                         class="rounded border"
                                                         style="width:48px;height:48px;object-fit:cover;display:block;"
                                                         loading="lazy"
                                                         onerror="this.style.display='none'">
                                                </a>
                                            @else
                                                <img src="{{ $adDisplay['image_url'] }}"
                                                     alt="{{ $adDisplay['name'] ?? '' }}"
                                                     class="rounded border"
                                                     style="width:48px;height:48px;object-fit:cover;display:block;"
                                                     loading="lazy"
                                                     onerror="this.style.display='none'">
                                            @endif
                                        @endif
                                        @if($adDisplay['name'])
                                            <span class="text-muted text-wrap" style="font-size:0.72rem;line-height:1.2;">{{ $adDisplay['name'] }}</span>
                                        @endif
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                        <td>{{ $lead->date_time_of_lead_received?->format('d F Y h:i a') ?? '—' }}</td>
                        <td>{{ $lead->next_followup_at?->format('d F Y h:i a') ?? '—' }}</td>
                        <td>
                            @php
                                $handledBy = $lead->handled_by;
                                $isHumanAssignee = \Modules\LeadManagement\Entities\Lead::assigneeIsHuman($handledBy);
                            @endphp
                            @if(!$isHumanAssignee)
                                {{ translate('Unassigned') }}
                            @elseif(isset($handledByNames[$handledBy]))
                                {{ $handledByNames[$handledBy] }}
                            @else
                                {{ $handledBy }}
                            @endif
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
                            <a href="{{ $leadDetailUrl }}" class="btn btn-sm btn--primary btn-lead-view" data-lead-url="{{ $leadDetailUrl }}">
                                {{ translate('view') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $emptyColspan }}" class="text-center py-4">{{ translate('No_leads_found') }}</td>
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

