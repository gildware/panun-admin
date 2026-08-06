@can('booking_view')
@php($bookingActive = (request()->is('admin/booking/list') || request()->is('admin/booking/details*') || request()->is('admin/booking/repeat*') || request()->is('admin/booking/rebooking*') || request()->is('admin/booking/success*')) && ! request()->is('admin/booking/list/verification') && ! request()->is('admin/booking/list/offline-payment') && ! request()->is('admin/booking/list/special-scenarios') && ! request()->is('admin/booking/list/cancelled-by-provider') && ! request()->is('admin/booking/list/cancelled-by-customer') && ! request()->is('admin/booking/reviews/list') && ! request()->is('admin/booking/todays-followups*'))
<div class="top-nav-item">
    <a href="{{ route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']) }}"
       class="top-nav-trigger {{ $bookingActive ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'calendar_month'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Bookings'),
            'count' => $pending_bookings_menu_count ?? 0,
        ])
    </a>
</div>
@endcan
