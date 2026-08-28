@can('booking_view')
@php
    $groupActive = \App\Support\AdminNavRegistry::groupIsActive('bookings');

    $bookingsMenuTotal = (int) ($pending_bookings_menu_count ?? 0)
        + (int) ($all_bookings_menu_count ?? 0);
@endphp
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'calendar_month'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Bookings'),
            'count' => $pending_bookings_menu_count ?? 0,
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']),
            'label' => translate('Booking_Requests'),
            'active' => (request()->is('admin/booking/list') || request()->is('admin/booking/details*') || request()->is('admin/booking/rebooking*') || request()->is('admin/booking/success*')) && ! request()->is('admin/booking/list/verification') && ! request()->is('admin/booking/list/special-scenarios') && ! request()->is('admin/booking/list/cancelled-by-provider') && ! request()->is('admin/booking/list/cancelled-by-customer') && ! request()->is('admin/booking/reviews/list') && ! request()->is('admin/booking/todays-followups*') && ! request()->is('admin/booking/repeat*'),
            'count' => $all_bookings_menu_count ?? 0,
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.booking.repeat_list', ['booking_status' => 'all', 'service_type' => 'all']),
            'label' => translate('Repeat_booking'),
            'active' => request()->is('admin/booking/repeat*') || request()->is('admin/booking/create/repeat'),
            'count' => $repeat_bookings_menu_count ?? 0,
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.booking.list.verification', ['booking_status' => 'pending', 'type' => 'pending']),
            'label' => translate('verify_requests'),
            'active' => request()->is('admin/booking/list/verification*'),
            'count' => \Modules\BookingModule\Entities\Booking::where('is_verified', '0')->where('payment_method', 'cash_after_service')->where('total_booking_amount', '>', (float) ($max_booking_amount ?? 0))->whereIn('booking_status', ['pending', 'accepted'])->count(),
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.booking.reviews.list'),
            'label' => translate('Booking_Review'),
            'active' => request()->is('admin/booking/reviews/list*'),
            'count' => $pending_booking_reviews_count ?? 0,
        ])
    </div>
</div>
@endcan
