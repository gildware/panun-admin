@php
    $customerNonResponsive = is_object($booking ?? null)
        && method_exists($booking, 'isCustomerNonResponsive')
        && $booking->isCustomerNonResponsive();
    $bookingOpenForFollowup = is_object($booking ?? null)
        && method_exists($booking, 'requiresMandatoryNextFollowup')
        && $booking->requiresMandatoryNextFollowup();
@endphp
@can('booking_view')
    @if($bookingOpenForFollowup && $customerNonResponsive)
        <form method="POST" action="{{ route('admin.booking.customer_non_responsive.reopen', $booking->id) }}" class="m-0 d-inline">
            @csrf
            <input type="hidden" name="redirect_web_page" value="{{ request('web_page') === 'followups' ? 'followups' : 'details' }}">
            <button type="submit" class="status-pill status-pill--muted">{{ translate('Reopen_customer_follow_up') }}</button>
        </form>
    @elseif($bookingOpenForFollowup)
        <button type="button" class="status-pill status-pill--muted" data-bs-toggle="modal" data-bs-target="#bookingCustomerNonResponsiveModal">
            {{ translate('Mark_as_Non_Responsive') }}
        </button>
    @endif
@endcan
