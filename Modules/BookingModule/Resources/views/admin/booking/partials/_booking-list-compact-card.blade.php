{{--
  Compact booking list card for admin.
  Expects: $booking, $index, $isCancelledByProviderList, $isCancelledByCustomerList, $bookingListReasonTab (optional)
--}}
@php
    $bookingListReasonTab = $bookingListReasonTab ?? ($queryParams['booking_status'] ?? '');
    $compactStatusClass = booking_admin_status_css_class($booking);

    $detailUrl = $booking->is_repeated
        ? route('admin.booking.repeat_details', [$booking->id, 'web_page' => 'details'])
        : route('admin.booking.details', [$booking->id, 'web_page' => 'details']);
    $ongoingVisitUrl = $detailUrl;
    if ($booking->is_repeated && $booking->nextServiceId) {
        $ongoingVisitUrl .= (str_contains($detailUrl, '?') ? '&' : '?') . 'visit=' . $booking->nextServiceId . '#repeat-service-log';
    }

    if ($booking->is_repeated) {
        if (empty($booking->nextService)) {
            $scheduleText = date('d-M-Y', strtotime($booking?->lastRepeat?->service_schedule ?? $booking->service_schedule))
                . ' ' . date('h:ia', strtotime($booking?->lastRepeat?->service_schedule ?? $booking->service_schedule));
        } else {
            $scheduleText = translate('Next upcoming') . ' · '
                . date('d-M-Y', strtotime($booking?->nextService?->service_schedule))
                . ' ' . date('h:ia', strtotime($booking?->nextService?->service_schedule));
        }
    } else {
        $scheduleText = date('d-M-Y', strtotime($booking->service_schedule))
            . ' ' . date('h:ia', strtotime($booking->service_schedule));
    }

    $scheduledFollowups = ($booking->followups ?? collect())->where('status', 'scheduled')->sortBy('date');
    $nextFuCustomer = $scheduledFollowups->where('for', 'customer')->first();
    $nextFuProvider = $scheduledFollowups->where('for', 'provider')->first();

    $assigneeVal = $booking->assignee
        ? trim($booking->assignee->first_name . ' ' . $booking->assignee->last_name)
            . ' (' . ($booking->assignee->user_type === 'super-admin' ? translate('Admin') : translate('Employee')) . ')'
        : null;

    if (request('booking_status') === 'reopened') {
        $leadLabel = translate('Reopened_from');
        if ($booking->isReopenOriginatedFollowup() && $booking->originatedFromBooking) {
            $leadVal = '<a href="' . route('admin.booking.details', [$booking->originatedFromBooking->id, 'web_page' => 'details']) . '">#'
                . e($booking->originatedFromBooking->readable_id ?? $booking->originated_from_booking_id) . '</a>';
        } elseif ($booking->isReopenOriginatedFollowup()) {
            $leadVal = e((string) $booking->originated_from_booking_id);
        } else {
            $leadVal = translate('Reopened_from_self');
        }
    } else {
        $leadLabel = translate('Lead_ID');
        $leadVal = ! empty($booking->lead_id)
            ? '<a href="' . route('admin.lead.show', $booking->lead_id) . '">#' . e($booking->lead_id) . '</a>'
            : null;
    }

    $sourceVal = booking_source_display_label($booking->booking_source);

    $locationVal = $booking->service_address?->address
        ?? $booking->zone?->name
        ?? null;

    $cancelReqVal = null;
    if ($isCancelledByProviderList && $booking->isProviderWithdrawnAwaitingAdmin()) {
        $cancelReqVal = \Carbon\Carbon::parse($booking->provider_cancelled_at ?? $booking->updated_at)->format('d-M-Y h:i A');
    }

    $reasonHtml = null;
    if ($bookingListReasonTab === 'canceled') {
        $__lc = $booking->latestParentCancellationStatusHistory;
        if ($__lc && ($__lc->cancellationReason || filled($__lc->status_change_remarks))) {
            $reasonHtml = ($__lc->cancellationReason ? '<strong>' . e($__lc->cancellationReason->name) . '</strong>' : '')
                . (filled($__lc->status_change_remarks) ? ' — ' . e(Str::limit(strip_tags($__lc->status_change_remarks), 200)) : '');
        }
    } elseif ($isCancelledByProviderList) {
        $__lpc = $booking->latestPendingCancellationRequestHistory
            ?? $booking->latestParentProviderCancellationStatusHistory
            ?? $booking->latestProviderRejectionHistory;
        if ($__lpc && ($__lpc->providerCancellationReason || filled($__lpc->status_change_remarks))) {
            $reasonHtml = ($__lpc->providerCancellationReason ? '<strong>' . e($__lpc->providerCancellationReason->name) . '</strong>' : '')
                . (filled($__lpc->status_change_remarks) ? ' — ' . e(Str::limit(strip_tags($__lpc->status_change_remarks), 200)) : '');
        }
    } elseif ($bookingListReasonTab === 'on_hold') {
        $__lh = $booking->latestParentHoldStatusHistory;
        if ($__lh && ($__lh->holdReopenReason || filled($__lh->status_change_remarks))) {
            $reasonHtml = ($__lh->holdReopenReason ? '<strong>' . e($__lh->holdReopenReason->name) . '</strong>' : '')
                . (filled($__lh->status_change_remarks) ? ' — ' . e(Str::limit(strip_tags($__lh->status_change_remarks), 200)) : '');
        }
    } elseif ($bookingListReasonTab === 'reopened') {
        $__rev = $booking->reopenFromCompletedDisplayEvent();
        if ($__rev && ($__rev->holdReopenReason || filled($__rev->complaint_notes))) {
            $reasonHtml = ($__rev->holdReopenReason ? '<strong>' . e($__rev->holdReopenReason->name) . '</strong>' : '')
                . (filled($__rev->complaint_notes) ? ' — ' . e(Str::limit(strip_tags($__rev->complaint_notes), 200)) : '');
        }
    }

    $repeatTooltip = $booking->is_repeated
        ? translate('This is a repeat booking.') . ' ' . translate('Customer has requested total ') . count($booking->repeat) . ' ' . translate('bookings under this Bookings.')
        : null;
@endphp
<article class="booking-compact-card booking-compact-card--{{ $compactStatusClass }} bc-card-navigable"
    data-href="{{ $detailUrl }}"
    @if($repeatTooltip) title="{{ $repeatTooltip }}" @endif>
    <div class="bc-r1">
        <span class="bc-sl">{{ $index }}</span>
        <a href="{{ $detailUrl }}" class="bc-id">#{{ $booking->readable_id }}</a>
        @if($booking->is_repeated)
            <img src="{{ asset('assets/admin-module/img/icons/repeat.svg') }}" class="bc-repeat" alt="{{ translate('repeat') }}">
        @endif
        @if($booking->subCategory?->name)
            <span class="bc-service">{{ $booking->subCategory->name }}</span>
        @endif
        <span class="bc-badges">
            @include('bookingmodule::admin.booking.partials._booking-list-status-badge', ['booking' => $booking])
            <span class="badge badge-{{ $booking->is_paid ? 'success' : 'danger' }}">
                {{ $booking->is_paid ? translate('paid') : translate('unpaid') }}
            </span>
        </span>
        <span class="bc-grow"></span>
        <span class="bc-header-schedule">
            <span class="bc-lbl">{{ translate('Schedule') }}</span>
            <span class="bc-val">{{ $scheduleText }}</span>
        </span>
        <span class="bc-amount">{{ with_currency_symbol(get_booking_total_amount($booking)) }}</span>
        <div class="bc-actions">
            @if($booking->is_repeated)
                <div class="dropdown">
                    <button type="button" class="bc-action-btn" data-bs-toggle="dropdown" title="{{ translate('View') }}">
                        <span class="material-icons">visibility</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.booking.repeat_details', [$booking->id, 'web_page' => 'details']) }}">
                                {{ translate('Full_Booking_Details') }}
                            </a>
                        </li>
                        @if($booking->nextServiceId && $booking->booking_status != 'pending')
                            <li>
                                <a class="dropdown-item" href="{{ $ongoingVisitUrl }}">
                                    {{ translate('Ongoing_Booking_Details') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            @else
                <a href="{{ $detailUrl }}" class="bc-action-btn" title="{{ translate('View') }}">
                    <span class="material-icons">visibility</span>
                </a>
            @endif
            @if($isCancelledByProviderList)
                <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}"
                   class="bc-action-btn" title="{{ translate('Review_cancellation_request') }}">
                    <span class="material-icons">gavel</span>
                </a>
            @endif
            @can('booking_can_manage_status')
                @if(request('booking_status') === 'reopened' && $booking->canMarkReopenResolved())
                    <button type="button" class="bc-action-btn"
                        title="{{ translate('Mark_reopen_resolved') }}"
                        data-bs-toggle="modal" data-bs-target="#reopenResolveModalGlobal"
                        data-resolve-action="{{ route('admin.booking.reopen-resolve', $booking->id) }}">
                        <span class="material-icons">check_circle</span>
                    </button>
                @endif
            @endcan
        </div>
    </div>

    <div class="bc-r2">
        <div class="bc-side bc-side--customer">
            <span class="bc-pair">
                <span class="bc-lbl">{{ translate('Customer') }}</span>
                <span class="bc-val">
                    @if ($booking->customer)
                        <a href="{{ route('admin.customer.detail', [$booking->customer->id, 'web_page' => 'overview']) }}">
                            {{ Str::limit(trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? '')), 30) }}
                        </a>
                    @else
                        {{ Str::limit($booking->service_address?->contact_person_name, 30) }}
                    @endif
                    @if($booking->customer?->phone || $booking->service_address?->contact_person_number)
                        , {{ $booking->customer ? $booking->customer->phone : $booking->service_address?->contact_person_number }}
                    @endif
                </span>
            </span>
            @include('bookingmodule::admin.booking.partials._booking-list-compact-fup-inline', [
                'booking' => $booking,
                'party' => 'customer',
                'followup' => $nextFuCustomer,
                'followupListMeta' => $followupListMeta ?? [],
            ])
        </div>
        <div class="bc-side bc-side--provider">
            <span class="bc-pair">
                <span class="bc-lbl">{{ translate('Provider') }}</span>
                <span class="bc-val">
                    @if(isset($booking->provider))
                        <a href="{{ route('admin.provider.details', [$booking->provider_id, 'web_page' => 'overview']) }}">
                            {{ $booking->provider->company_name }}
                        </a>@if($booking->provider->company_phone), {{ $booking->provider->company_phone }}@endif
                    @elseif($isCancelledByProviderList && $booking->providerCancelledByProvider)
                        <span class="text-warning">{{ $booking->isProviderRejectedPendingBooking() ? translate('Provider_rejected_request') : translate('Withdrawn_provider') }}</span>
                        <a href="{{ route('admin.provider.details', [$booking->provider_cancelled_by_provider_id, 'web_page' => 'overview']) }}">
                            {{ $booking->providerCancelledByProvider->company_name }}
                        </a>@if($booking->providerCancelledByProvider->company_phone), {{ $booking->providerCancelledByProvider->company_phone }}@endif
                    @else
                        <span class="badge badge-danger">{{ translate('unassigned') }}</span>
                    @endif
                </span>
            </span>
            @include('bookingmodule::admin.booking.partials._booking-list-compact-fup-inline', [
                'booking' => $booking,
                'party' => 'provider',
                'followup' => $nextFuProvider,
                'followupListMeta' => $followupListMeta ?? [],
            ])
        </div>
    </div>

    @php
        $metaPairs = [];
        if ($assigneeVal) {
            $metaPairs[] = ['label' => translate('Assignee'), 'value' => $assigneeVal];
        }
        if ($leadVal) {
            $metaPairs[] = ['label' => $leadLabel, 'value' => $leadVal, 'html' => true];
        }
        if ($sourceVal) {
            $metaPairs[] = ['label' => translate('Source'), 'value' => $sourceVal];
        }
        if ($locationVal) {
            $metaPairs[] = ['label' => translate('Location'), 'value' => $locationVal];
        }
        $metaPairs[] = ['label' => translate('Tag'), 'is_tags' => true];
        if ($isCancelledByProviderList && $cancelReqVal) {
            $metaPairs[] = ['label' => translate('Cancellation_requested_at'), 'value' => $cancelReqVal];
        }
    @endphp
    @if(count($metaPairs))
        <div class="bc-r3">
            @foreach($metaPairs as $pair)
                <span class="bc-pair">
                    <span class="bc-lbl">{{ $pair['label'] }}</span>
                    <span class="bc-val">
                        @if(!empty($pair['is_tags']))
                            @include('bookingmodule::admin.booking.partials._booking-list-tags-cell', ['booking' => $booking])
                        @elseif(!empty($pair['html']))
                            {!! $pair['value'] !!}
                        @else
                            {{ $pair['value'] }}
                        @endif
                    </span>
                </span>
            @endforeach
        </div>
    @endif

    @if($reasonHtml)
        <div class="bc-reason">
            <span class="bc-lbl">{{ translate('Booking_list_reason_remarks_column') }}</span>
            {!! $reasonHtml !!}
        </div>
    @endif
</article>
