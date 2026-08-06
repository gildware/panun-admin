@canany(['lead_view', 'booking_view'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('leads') || \App\Support\AdminNavRegistry::groupIsActive('bookings'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Leads_and_bookings') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @can('lead_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.lead.index'),
                'label' => translate('Leads'),
                'active' => (request()->is('admin/lead') || request()->is('admin/lead/*')) && !request()->is('admin/lead/create*') && !request()->is('admin/lead/configuration*') && !request()->is('admin/lead/reports*') && !request()->is('admin/lead/outbound-enquiry*'),
            ])
        @endcan
        @can('booking_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']),
                'label' => translate('Booking_Requests'),
                'active' => (request()->is('admin/booking/list') || request()->is('admin/booking/details*') || request()->is('admin/booking/repeat*') || request()->is('admin/booking/rebooking*') || request()->is('admin/booking/todays-followups*') || request()->is('admin/booking/success*')) && !request()->is('admin/booking/list/verification') && !request()->is('admin/booking/list/offline-payment') && !request()->is('admin/booking/list/special-scenarios') && !request()->is('admin/booking/list/cancelled-by-provider') && !request()->is('admin/booking/list/cancelled-by-customer') && !request()->is('admin/booking/reviews/list'),
                'count' => $all_bookings_menu_count,
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.booking.web-bookings.index'),
                'label' => translate('Web_Bookings'),
                'active' => request()->is('admin/booking/web-bookings*'),
                'count' => $web_bookings_pending_count ?? 0,
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.booking.web-provider-requests.index'),
                'label' => translate('Web_Provider_Requests'),
                'active' => request()->is('admin/booking/web-provider-requests*'),
                'count' => $web_provider_requests_pending_count ?? 0,
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.booking.app-custom-requests.index', ['status' => \Modules\BookingModule\Entities\AppCustomRequest::STATUS_PENDING]),
                'label' => translate('App_Custom_Requests'),
                'active' => request()->is('admin/booking/app-custom-requests*'),
                'count' => $app_custom_requests_pending_count ?? 0,
            ])
        @endcan
    </div>
</div>
@endcanany
