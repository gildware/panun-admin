@can('lead_view')
@php
    $groupActive = \App\Support\AdminNavRegistry::groupIsActive('leads')
        || request()->is('admin/booking/web-bookings*')
        || request()->is('admin/booking/web-provider-requests*')
        || request()->is('admin/booking/app-custom-requests*')
        || request()->is('admin/lead/outbound-enquiry*');

    $leadsMenuTotal = ($unassigned_leads_menu_count ?? 0)
        + (Gate::check('booking_view') ? (($web_bookings_pending_count ?? 0) + ($web_provider_requests_pending_count ?? 0) + ($app_custom_requests_pending_count ?? 0)) : 0);
@endphp
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'leaderboard'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Leads'),
            'count' => $leadsMenuTotal,
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.lead.index', ['handled_by' => ['__unassigned__']]),
            'label' => translate('All_Leads'),
            'active' => (request()->is('admin/lead') || request()->is('admin/lead/*'))
                && ! request()->is('admin/lead/create*')
                && ! request()->is('admin/lead/configuration*')
                && ! request()->is('admin/lead/reports*')
                && ! request()->is('admin/lead/outbound-enquiry*')
                && ! request()->is('admin/lead/todays-followups*')
                && in_array('__unassigned__', (array) request('handled_by', []), true),
            'count' => $unassigned_leads_menu_count ?? 0,
        ])

        @can('booking_view')
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

        @can('lead_outbound_enquiry_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.lead.outbound-enquiry.index'),
                'label' => translate('Outbound_Enquiry'),
                'active' => request()->is('admin/lead/outbound-enquiry*'),
            ])
        @endcan
    </div>
</div>
@endcan
