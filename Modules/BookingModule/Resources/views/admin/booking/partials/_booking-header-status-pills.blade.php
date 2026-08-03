@can('booking_can_manage_status')
    @if(!$bookingNotEditable)
        <div class="booking-header__status-actions" id="booking-status-overview-actions">
            @forelse ($__adminNextStatuses as $__nextSt)
                @php
                    $__cashBlockTargets = ['pending', 'ongoing', 'completed'];
                    $__btnDisabled = $__overviewStatusCashBlock && in_array($__nextSt, $__cashBlockTargets, true);
                    if ($__nextSt === 'ongoing' && ! booking_can_mark_ongoing_by_service_schedule($booking)) {
                        $__btnDisabled = true;
                    }
                    if (booking_status_requires_assigned_provider($__nextSt) && ! booking_has_assigned_provider($booking)) {
                        $__btnDisabled = true;
                    }
                    if ($__nextSt === 'completed' && ! booking_can_be_completed($booking)) {
                        $__btnDisabled = true;
                    }
                    $__btnDisabledTitle = translate('Not available for this booking');
                    if ($__nextSt === 'ongoing' && ! booking_can_mark_ongoing_by_service_schedule($booking)) {
                        $__btnDisabledTitle = translate('Booking_ongoing_only_on_or_after_schedule_date');
                    } elseif (booking_status_requires_assigned_provider($__nextSt) && ! booking_has_assigned_provider($booking)) {
                        $__btnDisabledTitle = translate('Assign_provider_before_accept_or_ongoing');
                    }
                    $__pillClass = match ($__nextSt) {
                        'accepted' => 'status-pill--success',
                        'pending' => 'status-pill--primary',
                        'ongoing' => 'status-pill--primary',
                        'on_hold' => 'status-pill--warning',
                        'completed' => 'status-pill--success',
                        'canceled', 'cancelled' => 'status-pill--danger',
                        default => 'status-pill--primary',
                    };
                    $__pillLabel = match ($__nextSt) {
                        'accepted' => translate('Accept_Booking'),
                        'pending' => translate('Mark_as_Pending'),
                        'ongoing' => translate('Mark_as_Ongoing'),
                        'on_hold' => $__overviewSt === 'ongoing' ? translate('Hold_after_visit') : translate('Put_on_hold'),
                        'completed' => translate('Complete_Booking'),
                        'canceled', 'cancelled' => translate('Cancel_Booking'),
                        default => ucwords(str_replace('_', ' ', $__nextSt)),
                    };
                @endphp
                <button type="button" class="status-pill {{ $__pillClass }} booking-status-overview-btn" data-status="{{ $__nextSt }}"
                    @if($__btnDisabled) disabled title="{{ $__btnDisabledTitle }}" @endif>{{ $__pillLabel }}</button>
            @empty
            @endforelse
            @if((int)($booking->is_repeated ?? 0) === 0 && ($__overviewSt === 'ongoing' || booking_on_hold_is_after_visit_from_ongoing($booking)))
                <button type="button" class="status-pill status-pill--primary" data-bs-toggle="modal"
                    data-bs-target="#bookingFinancialSettlementModal">
                    {{ translate('Configure_special_scenarios') }}
                </button>
            @endif
            @php
                $__isSingle = (int) ($booking->is_repeated ?? 0) === 0;
                $__isOngoingOrHoldAfterVisit = $__overviewSt === 'ongoing' || booking_on_hold_is_after_visit_from_ongoing($booking);
                $__canDisputeCloseBtn = $__isSingle && $__isOngoingOrHoldAfterVisit && booking_admin_can_dispute_and_close($booking);
                $__showResolveBookingBtn = $__isSingle && $booking->isOpenReopenTicket();
                $__resolveDueRemaining = round((float) get_booking_admin_add_payment_remaining_amount($booking), 2);
                $__resolveDueOutstanding = $__resolveDueRemaining > 0.009;
            @endphp
            @if($__canDisputeCloseBtn)
                <button type="button" class="status-pill status-pill--danger" data-bs-toggle="modal"
                    data-bs-target="#reopenDisputeModal--{{ $booking->id }}">
                    {{ translate('Dispute_and_close') }}
                </button>
            @endif
            @if($__showResolveBookingBtn)
                <button type="button" class="status-pill status-pill--success" data-bs-toggle="modal"
                    data-bs-target="#reopenResolveCompleteModal--{{ $booking->id }}"
                    @if($__resolveDueOutstanding) disabled title="{{ translate('Resolve_reopen_add_payment_first') }}" @endif>
                    {{ translate('Resolve_booking') }}
                </button>
            @endif
            @if($booking->adminEligibleForReopenFromCompleted())
                <button type="button" class="status-pill status-pill--warning" data-bs-toggle="modal"
                    data-bs-target="#bookingReopenModal--{{ $booking->id }}">
                    {{ translate('Reopen_or_complaint') }}
                </button>
            @endif
            @if($booking->canMarkReopenResolved())
                <button type="button" class="status-pill status-pill--success" data-bs-toggle="modal"
                    data-bs-target="#reopenResolveModal--{{ $booking->id }}">
                    {{ translate('Mark_reopen_resolved') }}
                </button>
            @endif
        </div>
    @elseif($__overviewShowReopenInCard ?? false)
        <div class="booking-header__status-actions" id="booking-status-overview-actions">
            <button type="button" class="status-pill status-pill--warning" data-bs-toggle="modal"
                data-bs-target="#bookingReopenModal--{{ $booking->id }}">
                {{ translate('Reopen_Booking') }}
            </button>
            @if($booking->isOpenReopenTicket() && booking_admin_can_dispute_and_close($booking))
                <button type="button" class="status-pill status-pill--danger" data-bs-toggle="modal"
                    data-bs-target="#reopenDisputeModal--{{ $booking->id }}">
                    {{ translate('Dispute_and_close') }}
                </button>
            @endif
            @if($booking->canMarkReopenResolved())
                <button type="button" class="status-pill status-pill--success" data-bs-toggle="modal"
                    data-bs-target="#reopenResolveModal--{{ $booking->id }}">
                    {{ translate('Mark_as_Resolved') }}
                </button>
            @endif
        </div>
    @endif
@endcan
