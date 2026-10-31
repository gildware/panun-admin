@can('booking_can_manage_status')
    @if($bookingStatusChangeable ?? !$bookingNotEditable)
        @php
            $__canMarkOngoingBySchedule = booking_can_mark_ongoing_by_service_schedule($booking);
            $__ongoingBlockedBySchedule = in_array('ongoing', $__adminNextStatuses ?? [], true) && ! $__canMarkOngoingBySchedule;
            $__ongoingBlockedScheduleDate = null;
            if ($__ongoingBlockedBySchedule && ! empty($booking->service_schedule)) {
                try {
                    $__ongoingBlockedScheduleDate = \Carbon\Carbon::parse($booking->service_schedule)->format('d M Y');
                } catch (\Throwable $e) {
                    $__ongoingBlockedScheduleDate = null;
                }
            }
            $__ongoingBlockedMessage = translate('Booking_ongoing_only_on_or_after_schedule_date');
            if ($__ongoingBlockedScheduleDate) {
                $__ongoingBlockedMessage .= ' ('.$__ongoingBlockedScheduleDate.')';
            }
        @endphp
        <div class="booking-header__status-actions" id="booking-status-overview-actions">
            @include('bookingmodule::admin.booking.partials._booking-customer-non-responsive-action', ['booking' => $booking])
            @forelse ($__adminNextStatuses as $__nextSt)
                @php
                    $__cashBlockTargets = ['pending', 'ongoing', 'completed'];
                    $__btnDisabled = $__overviewStatusCashBlock && in_array($__nextSt, $__cashBlockTargets, true);
                    if ($__nextSt === 'ongoing' && ! $__canMarkOngoingBySchedule) {
                        $__btnDisabled = true;
                    }
                    if (booking_status_requires_assigned_provider($__nextSt) && ! booking_has_assigned_provider($booking)) {
                        $__btnDisabled = true;
                    }
                    if ($__nextSt === 'completed' && ! booking_can_be_completed($booking)) {
                        $__btnDisabled = true;
                    }
                    $__btnDisabledTitle = translate('Not available for this booking');
                    if ($__nextSt === 'ongoing' && ! $__canMarkOngoingBySchedule) {
                        $__btnDisabledTitle = $__ongoingBlockedMessage;
                    } elseif (booking_status_requires_assigned_provider($__nextSt) && ! booking_has_assigned_provider($booking)) {
                        $__btnDisabledTitle = translate('Assign_provider_before_accept_or_ongoing');
                    } elseif ($__nextSt === 'completed' && ! booking_can_be_completed($booking)) {
                        $__btnDisabledTitle = translate('Booking cannot be completed until full payment is received.');
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
                        'pending' => in_array($__overviewSt, ['canceled', 'cancelled'], true)
                            ? translate('Restore_to_pending')
                            : translate('Mark_as_Pending'),
                        'ongoing' => translate('Mark_as_Ongoing'),
                        'on_hold' => $__overviewSt === 'ongoing' ? translate('Hold_after_visit') : translate('Put_on_hold'),
                        'completed' => translate('Complete_Booking'),
                        'canceled', 'cancelled' => translate('Cancel_Booking'),
                        default => ucwords(str_replace('_', ' ', $__nextSt)),
                    };
                @endphp
                <span class="booking-status-btn-wrap">
                    <button type="button"
                        class="status-pill {{ $__pillClass }} booking-status-overview-btn{{ $__btnDisabled ? ' is-disabled' : '' }}"
                        data-status="{{ $__nextSt }}"
                        @if($__nextSt === 'ongoing') data-can-mark-ongoing="{{ $__canMarkOngoingBySchedule ? '1' : '0' }}" @endif
                        @if($__btnDisabled) disabled aria-disabled="true" @endif>{{ $__pillLabel }}</button>
                    @if($__btnDisabled)
                        <button type="button"
                            class="booking-status-disabled-info"
                            data-disabled-reason="{{ $__btnDisabledTitle }}"
                            aria-label="{{ translate('Why_is_this_disabled') }}">
                            <span class="material-icons" aria-hidden="true">info</span>
                        </button>
                    @endif
                </span>
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
            @include('bookingmodule::admin.booking.partials._booking-customer-non-responsive-action', ['booking' => $booking])
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
