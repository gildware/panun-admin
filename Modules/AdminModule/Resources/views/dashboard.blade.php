@extends('adminmodule::layouts.new-master')

@section('title',translate('dashboard'))

@push('css_or_js')
    <style>
        .main-content .container-fluid .row .card {
            position: relative;
            z-index: 0;
        }
        .main-content .container-fluid .row.g-4 {
            display: flex;
            flex-wrap: wrap;
        }
        .dashboard-top-cards .business-summary {
            height: 6rem;
            min-height: 6rem;
        }
        /* Reduce top card typography to fit larger numbers. */
        .dashboard-top-cards .business-summary h2 {
            font-size: clamp(0.95rem, 1.7vw, 1.25rem);
            line-height: 1.15;
            margin: 0;
            padding: 0;
            white-space: nowrap;
        }
        .dashboard-top-cards .business-summary h3 {
            font-size: clamp(0.65rem, 1.0vw, 0.82rem);
            line-height: 1.1;
            margin: 0.15rem 0 0;
        }
        /* Distinct KPI card colors (scoped to admin dashboard top cards). */
        .dashboard-top-cards .business-summary.dashboard-kpi--total-revenue {
            background: linear-gradient(145deg, #0369a1 0%, #0c4a6e 100%);
        }
        .dashboard-top-cards .business-summary.dashboard-kpi--service-charges {
            background: linear-gradient(145deg, #7c3aed 0%, #5b21b6 100%);
        }
        .dashboard-top-cards .business-summary.dashboard-kpi--parts-charges {
            background: linear-gradient(145deg, #c2410c 0%, #9a3412 100%);
        }
        .dashboard-top-cards .business-summary.dashboard-kpi--total-received {
            background: linear-gradient(145deg, #0d9488 0%, #115e59 100%);
        }
        .dashboard-top-cards .business-summary.dashboard-kpi--our-earning {
            background: linear-gradient(145deg, #15803d 0%, #14532d 100%);
        }
        .dashboard-top-cards .business-summary.dashboard-kpi--payable-providers {
            background: linear-gradient(145deg, #2563eb 0%, #1e40af 100%);
        }
        .dashboard-top-cards .business-summary.dashboard-kpi--balance-providers {
            background: linear-gradient(145deg, #ca8a04 0%, #a16207 100%);
        }
        .dashboard-top-cards .business-summary.dashboard-kpi--payable-customer {
            background: linear-gradient(145deg, #db2777 0%, #9d174d 100%);
        }
        .dashboard-top-cards .business-summary.dashboard-kpi--total-loss {
            background: linear-gradient(145deg, #b91c1c 0%, #7f1d1d 100%);
        }
        .dashboard-top-cards .business-summary.dashboard-kpi--bad-debt {
            background: linear-gradient(145deg, #4c1d95 0%, #312e81 100%);
        }
        .dashboard-top-cards .business-summary .dashboard-kpi-deco-icon {
            font-size: clamp(2.5rem, 5vw, 3.25rem);
            line-height: 1;
            opacity: 0.22;
            pointer-events: none;
            user-select: none;
        }
        .card-header h5.dashboard-widget-title,
        h4.dashboard-widget-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-header h5.dashboard-widget-title .dashboard-widget-title__icon,
        h4.dashboard-widget-title .dashboard-widget-title__icon {
            flex-shrink: 0;
            font-size: 1.375rem;
            opacity: 0.85;
        }
        /* Dashboard widget headers: medium dark (not too dark), white text */
        .main-content .container-fluid .card > .card-header {
            background-color: #43466e;
            color: #fff;
            border-color: transparent;
        }
        .main-content .container-fluid .card > .card-header .dashboard-widget-title,
        .main-content .container-fluid .card > .card-header h4,
        .main-content .container-fluid .card > .card-header h5 {
            color: #fff;
        }
        .main-content .container-fluid .card > .card-header .text-muted {
            color: rgba(255, 255, 255, 0.75) !important;
        }
        .main-content .container-fluid .card > .card-header .dashboard-widget-title__icon,
        .main-content .container-fluid .card > .card-header .text-primary {
            color: #fff !important;
            opacity: 0.95;
        }
        .main-content .container-fluid .card > .card-header .btn-link {
            color: rgba(255, 255, 255, 0.92);
        }
        .main-content .container-fluid .card > .card-header .btn-link:hover,
        .main-content .container-fluid .card > .card-header .btn-link:focus {
            color: #fff;
        }
        .main-content .container-fluid .card > .card-header .btn-outline-primary {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.55);
        }
        .main-content .container-fluid .card > .card-header .btn-outline-primary:hover,
        .main-content .container-fluid .card > .card-header .btn-outline-primary:focus {
            background-color: rgba(255, 255, 255, 0.12);
            color: #fff;
            border-color: #fff;
        }
        /* Keep widget list/table body light — clearly different from the dark header */
        .main-content .container-fluid .card > .card-body {
            background-color: #fff;
        }
        .main-content .container-fluid .card .card-body .table thead,
        .main-content .container-fluid .card .card-body .table thead th {
            background-color: #f3f4f8;
            color: #5e6472;
        }
        .main-content .container-fluid .card .card-body .common-list li h5 {
            color: var(--bs-dark);
        }
        .missed-followup-row,
        .missed-followup-row > td {
            background-color: #fff !important;
            color: #dc3545 !important;
        }
        .table-hover > tbody > tr.missed-followup-row:hover > * {
            background-color: #fff !important;
            color: #dc3545 !important;
        }
        /* Keep follow-up tables visually aligned (same min/max height). */
        .dashboard-widget-todays-followups .card-body {
            min-height: 420px;
            max-height: 420px;
            overflow: auto;
        }
        .dashboard-widget-todays-followups .card-body > .table-responsive {
            height: 100%;
            max-height: 100%;
        }
        .dashboard-widget-todays-followups .card-body > .d-flex {
            height: 100%;
        }
        .missed-followup-row a,
        .missed-followup-row a.text-primary,
        .missed-followup-row .text-primary,
        .missed-followup-row .small,
        .missed-followup-row .small a {
            color: #dc3545 !important;
        }

        /* Keep "half" widgets visually aligned (same min/max height). */
        .dashboard-widgets-grid .dashboard-collapsible-widget .card-body,
        .dashboard-widget-followups-row .dashboard-collapsible-widget .card-body {
            min-height: 420px;
            max-height: 420px;
            overflow: auto;
        }
        .dashboard-widget-staff-presence .card-body {
            min-height: 320px;
            max-height: 420px;
            overflow: auto;
        }
        .dashboard-widget-staff-presence .card-body > .table-responsive {
            max-height: 100%;
            overflow-x: auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .dashboard-widget-staff-presence .card-body > .table-responsive > .table {
            margin-bottom: 0;
        }
        .dashboard-widget-staff-presence .staff-presence-employee-col {
            min-width: 240px;
            width: 240px;
        }
        .dashboard-widget-staff-presence .staff-presence-avatar-wrap {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
        }
        .dashboard-widget-staff-presence .staff-presence-avatar {
            width: 36px !important;
            height: 36px !important;
            min-width: 36px;
            min-height: 36px;
            flex-shrink: 0;
            object-fit: cover;
        }
        #staffPresenceHistoryModal .staff-presence-employee-col {
            min-width: 240px;
            width: 240px;
        }
        #staffPresenceHistoryModal .staff-presence-avatar-wrap {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
        }
        #staffPresenceHistoryModal .staff-presence-avatar {
            width: 36px !important;
            height: 36px !important;
            min-width: 36px;
            min-height: 36px;
            flex-shrink: 0;
            object-fit: cover;
        }

        .dashboard-widgets-grid .dashboard-ranking-widget-table {
            table-layout: fixed;
            width: 100%;
            margin-bottom: 0;
        }
        .dashboard-widgets-grid .dashboard-ranking-widget-table thead th {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--bs-secondary);
            white-space: nowrap;
            padding-top: 0;
            padding-bottom: 0.625rem;
            border-bottom: 1px solid var(--border-color);
        }
        .dashboard-widgets-grid .dashboard-ranking-widget-table tbody td {
            padding-top: 0.625rem;
            padding-bottom: 0.625rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        .dashboard-widgets-grid .dashboard-ranking-widget-table tbody tr:last-child td {
            border-bottom: none;
        }
        .dashboard-widgets-grid .dashboard-ranking-widget-table .col-score {
            width: 4.5rem;
        }
        .dashboard-widgets-grid .dashboard-ranking-widget-table .col-bookings {
            width: 5rem;
        }
        .dashboard-widgets-grid .dashboard-ranking-widget-table tbody tr {
            cursor: pointer;
        }

        /* Collapsible dashboard widgets (accordion) */
        .dashboard-collapsible-widget .dashboard-widget-collapse-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            margin: 0;
            border: 0;
            border-radius: 0.25rem;
            background: transparent;
            color: #fff;
            flex-shrink: 0;
            line-height: 1;
        }
        .dashboard-collapsible-widget .dashboard-widget-collapse-btn:hover,
        .dashboard-collapsible-widget .dashboard-widget-collapse-btn:focus {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            outline: none;
        }
        .dashboard-collapsible-widget .dashboard-widget-collapse-icon {
            font-size: 1.375rem;
            transition: transform 0.2s ease;
        }
        .dashboard-collapsible-widget .dashboard-widget-collapse-btn[aria-expanded="false"] .dashboard-widget-collapse-icon {
            transform: rotate(-90deg);
        }
        .dashboard-collapsible-widget > .dashboard-widget-collapse-header {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .dashboard-collapsible-widget .dashboard-widget-header-main {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
            flex: 1 1 auto;
            justify-content: flex-start;
        }
        .dashboard-collapsible-widget .dashboard-widget-header-main .dashboard-widget-title {
            justify-content: flex-start;
            text-align: left;
        }
        .dashboard-collapsible-widget .dashboard-widget-header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
            margin-left: auto;
            flex-wrap: wrap;
        }
        .dashboard-collapsible-widget > .dashboard-widget-collapse-header a,
        .dashboard-collapsible-widget > .dashboard-widget-collapse-header button:not(.dashboard-widget-collapse-btn) {
            cursor: pointer;
        }
        .dashboard-collapsible-widget > .dashboard-widget-collapse-header select,
        .dashboard-collapsible-widget > .dashboard-widget-collapse-header label,
        .dashboard-collapsible-widget > .dashboard-widget-collapse-header .select2-container {
            cursor: default;
        }
        .earning-statistics .dashboard-earning-filter-wrap {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }
        .earning-statistics .dashboard-earning-filter-wrap .select-wrap {
            flex: 0 0 auto;
        }
        .earning-statistics .dashboard-earning-filter-wrap .select2-container {
            min-width: 4.75rem !important;
            max-width: 5.5rem;
        }
        .earning-statistics .dashboard-earning-filter-wrap .select2-container .select2-selection--single {
            min-height: 1.75rem;
            height: 1.75rem;
        }
        .earning-statistics .dashboard-earning-filter-wrap .select2-container .select2-selection__rendered {
            font-size: 0.75rem;
            line-height: 1.65rem;
            padding-left: 0.45rem;
            padding-right: 1.25rem;
        }
        .earning-statistics .dashboard-earning-filter-wrap .select2-container .select2-selection__arrow {
            height: 1.65rem;
            right: 0.2rem;
        }
        .earning-statistics .dashboard-earning-filter-wrap .update-chart-month + .select2-container {
            min-width: 5.25rem !important;
            max-width: 6rem;
        }

    </style>
@endpush

@section('content')
    @can('dashboard')
    <div class="main-content">
        <div class="container-fluid">
            @if(access_checker('dashboard'))
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 mb-4 g-4 dashboard-top-cards">
                    <div class="col">
                        <div class="business-summary dashboard-kpi--total-revenue">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.total_revenue', 0))}}</h2>
                            <h3>{{translate('Total_Revenue')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">account_balance_wallet</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary dashboard-kpi--service-charges">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.service_charges_total', 0))}}</h2>
                            <h3>{{translate('Service_Charges')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">home_repair_service</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary dashboard-kpi--parts-charges">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.spare_parts_total', 0))}}</h2>
                            <h3>{{translate('Parts_Charges')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">inventory_2</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary dashboard-kpi--total-received">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.total_amount_received_by_company', 0))}}</h2>
                            <h3>{{translate('Total_amount_received')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">move_to_inbox</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary dashboard-kpi--our-earning">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.our_earning', 0))}}</h2>
                            <h3>{{translate('Our_Earning')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">trending_up</span>
                        </div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 mb-4 g-4 dashboard-top-cards">
                    <div class="col">
                        <div class="business-summary dashboard-kpi--payable-providers">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.payable_to_providers', 0))}}</h2>
                            <h3>{{translate('Payable_to_providers')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">engineering</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary dashboard-kpi--balance-providers">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.balance_with_providers', 0))}}</h2>
                            <h3>{{translate('Balance_With_Providers')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">compare_arrows</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary dashboard-kpi--payable-customer">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.payable_to_customers', 0))}}</h2>
                            <h3>{{translate('Payable_to_customer')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">person</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary dashboard-kpi--total-loss">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.total_loss_in_all_bookings', 0))}}</h2>
                            <h3>{{translate('Total_loss_in_all_bookings')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">trending_down</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary dashboard-kpi--bad-debt">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.total_bad_debt_with_customers', 0))}}</h2>
                            <h3>{{translate('Dashboard_company_loss_from_customers')}}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">gavel</span>
                        </div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 mb-4 g-4 dashboard-top-cards">
                    <div class="col">
                        <div class="business-summary" style="background: linear-gradient(145deg, #dc2626 0%, #991b1b 100%);">
                            <h2>{{ with_currency_symbol(data_get($data[0], 'top_cards.total_write_off_company', 0)) }}</h2>
                            <h3>{{ translate('Dashboard_write_off_company_total') }}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">percent</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary" style="background: linear-gradient(145deg, #b45309 0%, #7c2d12 100%);">
                            <h2>{{ with_currency_symbol(data_get($data[0], 'top_cards.total_write_off_provider', 0)) }}</h2>
                            <h3>{{ translate('Dashboard_write_off_provider_total') }}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">percent</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary" style="background: linear-gradient(145deg, #dc2626 0%, #7f1d1d 100%);">
                            <h2>{{ with_currency_symbol(data_get($data[2], 'compensation_totals.company_to_customers', 0)) }}</h2>
                            <h3>{{ translate('Company_compensation_to_customers') }}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">volunteer_activism</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="business-summary" style="background: linear-gradient(145deg, #ea580c 0%, #9a3412 100%);">
                            <h2>{{ with_currency_symbol(data_get($data[2], 'compensation_totals.company_to_providers', 0)) }}</h2>
                            <h3>{{ translate('Company_compensation_to_providers') }}</h3>
                            <span class="material-symbols-outlined absolute-img dashboard-kpi-deco-icon" aria-hidden="true">handshake</span>
                        </div>
                    </div>
                </div>
                <div class="row g-4 mb-4 dashboard-widget-followups-row">
                    <div class="col-lg-6 col-12">
                        <div class="card dashboard-widget-todays-followups dashboard-collapsible-widget" id="dashboard-booking-followups">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5 class="dashboard-widget-title mb-0">
                                    <span class="material-symbols-outlined dashboard-widget-title__icon text-primary" aria-hidden="true">event_repeat</span>
                                    Booking Follow-ups- Pending Till Today's
                                    <span class="text-muted">
                                        ({{ $data[6]['todays_pending_followups_total'] ?? 0 }})
                                    </span>
                                </h5>
                                <a href="{{route('admin.booking.todays_followups')}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body p-0">
                                @if(isset($data[6]['todays_pending_followups']) && $data[6]['todays_pending_followups']->isNotEmpty())
                                    <div class="table-responsive px-3 overflow-auto">
                                        <table class="table table-hover align-middle mb-0 fs-13 text-nowrap">
                                            <thead class="text-secondary border-bottom">
                                                <tr>
                                                    <th>{{translate('Followup_On')}}</th>
                                                    <th>{{translate('Booking_ID')}}</th>
                                                    <th>{{translate('Follow_up_for')}}</th>
                                                    <th>{{translate('Urgency')}}</th>
                                                    <th>{{translate('Customer_Info')}}</th>
                                                    <th>{{translate('Provider_Info')}}</th>
                                                    <th>{{translate('Assignee')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data[6]['todays_pending_followups'] as $followup)
                                                    <tr class="cursor-pointer todays-followup-redirect {{ $followup->date && !$followup->date->isToday() ? 'missed-followup-row' : '' }}"
                                                        data-route="{{ $followup->booking ? (route('admin.booking.details', [$followup->booking_id, 'web_page' => 'followups'])) : '#' }}">
                                                        <td>
                                                            @php($due = $followup->date)
                                                            @if(!$due)
                                                                —
                                                            @else
                                                                @php($totalMinutes = (int) round(abs($due->diffInMinutes(\Carbon\Carbon::now()))))
                                                                @php($dueDays = intdiv($totalMinutes, 1440))
                                                                @php($dueHours = intdiv($totalMinutes % 1440, 60))
                                                                @if($dueDays > 0 && $dueHours > 0)
                                                                    {{ $dueDays }} {{ translate('days') }} {{ $dueHours }} {{ translate('hours') }} {{ translate('before') }}
                                                                @elseif($dueDays > 0)
                                                                    {{ $dueDays }} {{ translate('days') }} {{ translate('before') }}
                                                                @elseif($dueHours > 0)
                                                                    {{ $dueHours }} {{ translate('hours') }} {{ translate('before') }}
                                                                @else
                                                                    {{ translate('less_than_an_hour') }}
                                                                @endif
                                                                <br><span class="small text-muted">{{ $due->format('d M Y, h:i A') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($followup->booking)
                                                                <a href="{{ route('admin.booking.details', [$followup->booking_id, 'web_page' => 'followups']) }}"
                                                                   class="text-decoration-none {{ $followup->date && !$followup->date->isToday() ? '' : 'text-primary' }}"
                                                                   onclick="event.stopPropagation();">{{ $followup->booking->readable_id }}</a>
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                        <td>
                                                            {{ translate(ucfirst($followup->for)) }}
                                                        </td>
                                                        <td>
                                                            @php($fuUrgency = $followup->urgency ?: 'medium')
                                                            <span class="badge badge-{{ $fuUrgency === 'high' ? 'danger' : ($fuUrgency === 'low' ? 'secondary' : 'warning') }}">{{ translate(ucfirst($fuUrgency)) }}</span>
                                                        </td>
                                                        <td>
                                                            @if($followup->booking && $followup->booking->customer)
                                                                <span>{{ Str::limit(trim(($followup->booking->customer->first_name ?? '') . ' ' . ($followup->booking->customer->last_name ?? '')), 15) ?: '—' }}</span>
                                                                <br><span class="small">{{ $followup->booking->customer->phone ?? '—' }}</span>
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($followup->booking && $followup->booking->provider)
                                                                <span>{{ Str::limit($followup->booking->provider->company_name ?? '', 15) ?: '—' }}</span>
                                                                <br><span class="small">{{ $followup->booking->provider->contact_person_phone ?? $followup->booking->provider->company_phone ?? '—' }}</span>
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                        <td>{{ $followup->booking && $followup->booking->assignee ? $followup->booking->assignee->first_name . ' ' . $followup->booking->assignee->last_name : translate('Unassigned') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center justify-content-center p-4">
                                        <span class="opacity-50">{{translate('No_follow_ups_yet')}}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="card dashboard-widget-todays-followups dashboard-collapsible-widget" id="dashboard-leads-followups">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5 class="dashboard-widget-title mb-0">
                                    <span class="material-symbols-outlined dashboard-widget-title__icon text-primary" aria-hidden="true">contact_phone</span>
                                    Leads Follow-ups- Pending Till Today's
                                    <span class="text-muted">
                                        ({{ $data[7]['todays_pending_lead_followups_total'] ?? 0 }})
                                    </span>
                                </h5>
                                <a href="{{ route('admin.lead.todays_followups') }}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body p-0">
                                @if(isset($data[7]['todays_pending_lead_followups']) && $data[7]['todays_pending_lead_followups']->isNotEmpty())
                                    <div class="table-responsive px-3 overflow-auto">
                                        <table class="table table-hover align-middle mb-0 fs-13 text-nowrap">
                                            <thead class="text-secondary border-bottom">
                                                <tr>
                                                    <th>{{translate('Followup_On')}}</th>
                                                    <th>{{translate('Lead_ID')}}</th>
                                                    <th>{{translate('Lead_Type')}}</th>
                                                    <th>{{translate('Urgency')}}</th>
                                                    <th>{{translate('Name')}}</th>
                                                    <th>{{translate('Phone')}}</th>
                                                    <th>{{translate('Handled_By')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data[7]['todays_pending_lead_followups'] as $lead)
                                                    <tr class="cursor-pointer todays-followup-redirect {{ $lead->next_followup_at && !$lead->next_followup_at->isToday() ? 'missed-followup-row' : '' }}"
                                                        data-route="{{ route('admin.lead.show', $lead->id) }}">
                                                        <td>
                                                            @php($due = $lead->next_followup_at)
                                                            @if(!$due)
                                                                —
                                                            @else
                                                                @php($totalMinutes = (int) round(abs($due->diffInMinutes(\Carbon\Carbon::now()))))
                                                                @php($dueDays = intdiv($totalMinutes, 1440))
                                                                @php($dueHours = intdiv($totalMinutes % 1440, 60))
                                                                @if($dueDays > 0 && $dueHours > 0)
                                                                    {{ $dueDays }} {{ translate('days') }} {{ $dueHours }} {{ translate('hours') }} {{ translate('before') }}
                                                                @elseif($dueDays > 0)
                                                                    {{ $dueDays }} {{ translate('days') }} {{ translate('before') }}
                                                                @elseif($dueHours > 0)
                                                                    {{ $dueHours }} {{ translate('hours') }} {{ translate('before') }}
                                                                @else
                                                                    {{ translate('less_than_an_hour') }}
                                                                @endif
                                                                <br><span class="small text-muted">{{ $due->format('d M Y, h:i A') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('admin.lead.show', $lead->id) }}"
                                                               class="text-decoration-none {{ $lead->next_followup_at && !$lead->next_followup_at->isToday() ? '' : 'text-primary' }}"
                                                               onclick="event.stopPropagation();">
                                                                {{ $lead->id }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill bg-primary text-capitalize">
                                                                {{ \Modules\LeadManagement\Entities\Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @php($leadUrgency = $lead->latestFollowup?->urgency ?: 'medium')
                                                            <span class="badge badge-{{ $leadUrgency === 'high' ? 'danger' : ($leadUrgency === 'low' ? 'secondary' : 'warning') }}">{{ translate(ucfirst($leadUrgency)) }}</span>
                                                        </td>
                                                        <td>{{ $lead->name ?? '—' }}</td>
                                                        <td>
                                                            @if(!empty($lead->phone_number))
                                                                <a href="tel:{{ $lead->phone_number }}" class="text-decoration-none text-primary">
                                                                    {{ $lead->phone_number }}
                                                                </a>
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                        <td>{{ $lead->handled_by_name ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center justify-content-center p-4">
                                        <span class="opacity-50">{{translate('No_follow_ups_yet')}}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4 dashboard-widgets-grid">
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card earning-statistics dashboard-collapsible-widget h-100" id="dashboard-earning-statistics">
                            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <h4 class="dashboard-widget-title mb-0">
                                    <span class="material-symbols-outlined dashboard-widget-title__icon text-primary" aria-hidden="true">show_chart</span>
                                    {{translate('earning_statistics')}}
                                </h4>
                                <div class="dashboard-earning-filter-wrap">
                                    @php($earningGraphYear = session('dashboard_earning_graph_year', date('Y')))
                                    @php($earningGraphMonth = session('dashboard_earning_graph_month'))
                                    <div class="select-wrap">
                                        <select class="js-select update-chart update-chart-year">
                                            @php($from_year=date('Y'))
                                            @php($to_year=$from_year-10)
                                            @while($from_year!=$to_year)
                                                <option
                                                    value="{{$from_year}}" {{(string) $earningGraphYear === (string) $from_year ? 'selected' : ''}}>
                                                    {{$from_year}}
                                                </option>
                                                @php($from_year--)
                                            @endwhile
                                        </select>
                                    </div>
                                    <div class="select-wrap">
                                        <select class="js-select update-chart update-chart-month"
                                                data-placeholder="{{translate('month')}}"
                                                data-allow-clear="true">
                                            <option value=""></option>
                                            @foreach(range(1, 12) as $monthNumber)
                                                <option value="{{ $monthNumber }}"
                                                    {{ (string) $earningGraphMonth === (string) $monthNumber ? 'selected' : '' }}>
                                                    {{ date('M', mktime(0, 0, 0, $monthNumber, 1)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body ps-0 pt-0">
                                <div id="apex_line-chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card recent-transactions h-100 w-100 dashboard-collapsible-widget" id="dashboard-recent-transactions">
                            <div class="card-header d-flex justify-content-between align-items-center gap-10">
                                <h4 class="mb-0 dashboard-widget-title">
                                    <span class="material-symbols-outlined dashboard-widget-title__icon text-primary" aria-hidden="true">receipt_long</span>
                                    {{translate('recent_ledger_transactions')}}
                                </h4>
                                <a href="{{route('admin.ledger.index')}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body">
                                @if(isset($data[1]['recent_ledger_transactions']) && count($data[1]['recent_ledger_transactions']) > 0)
                                    <div class="d-flex align-items-center gap-3 mb-4">
                                        <img src="{{asset('assets/admin-module')}}/img/icons/arrow-up.png"
                                             alt="">
                                        <p class="opacity-75">{{$data[1]['this_month_ledger_trx_count']}} {{translate('ledger_transactions_this_month')}}</p>
                                    </div>
                                @endif
                                <div class="events w-100">
                                    @foreach($data[1]['recent_ledger_transactions'] ?? [] as $entry)
                                        <div class="event">
                                            <div class="knob"></div>
                                            <div class="d-flex align-items-center gap-1 justify-content-between">
                                                <div class="title">
                                                    @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                        <h5 class="text-success">+ {{with_currency_symbol($entry->amount)}} {{translate('credited')}}</h5>
                                                    @else
                                                        <h5 class="text-danger">- {{with_currency_symbol($entry->amount)}} {{translate('debited')}}</h5>
                                                    @endif

                                                    <p class="m-0 fs-13 d-flex align-items-center gap-1">
                                                       <span class="material-symbols-outlined fs-5 cursor-pointer"
                                                             data-bs-toggle="tooltip" data-bs-placement="top" title="Ledger">
                                                         account_balance_wallet
                                                       </span>
                                                        {{ $entry->booking?->readable_id ?? $entry->booking_id ?? '—' }}
                                                    </p>
                                                </div>
                                                <div class="description">
                                                    <p class="fs-12">{{date('d M H:i a',strtotime($entry->created_at))}}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    <div class="line"></div>
                                </div>

                                @if(count($data[1]['recent_ledger_transactions'] ?? []) < 1)
                                    <div class="d-flex flex-column justify-content-center align-items-center h-100 w-100">
                                        <div class="recent-transaction-no-data text-center">
                                            <img src="{{ asset('assets/admin-module/img/icons/no-transaction.svg') }}" alt=""> <br>
                                            <p class="fs-16 text-dark-icon">{{ translate('No Recent Ledger Transactions') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card recent-activities dashboard-collapsible-widget h-100" id="dashboard-recent-bookings">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5 class="dashboard-widget-title mb-0">
                                    <span class="material-symbols-outlined dashboard-widget-title__icon text-primary" aria-hidden="true">calendar_month</span>
                                    {{translate('recent_bookings')}}
                                </h5>
                                <a href="{{route('admin.booking.list', ['booking_status'=>'all', 'service_type' => 'all'])}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body">
                                <ul class="common-list">
                                    @if(count($data[3]['bookings'] ?? []) < 1)
                                        <div class="d-flex align-items-center justify-content-center h-100 w-100">
                                            <span class="opacity-50">{{translate('No Bookings Found')}}</span>
                                        </div>
                                    @endif
                                    @foreach($data[3]['bookings'] ?? [] as $booking)
                                        <li class="d-flex flex-wrap gap-2 align-items-center justify-content-between cursor-pointer recent-booking-redirect"
                                            data-route="@if($booking->is_repeated) {{ route('admin.booking.repeat_details', [$booking->id]) }}?web_page=details @else {{ route('admin.booking.details', [$booking->id]) }}?web_page=details @endif">
                                            <div class="media align-items-center gap-3">
                                                <div class="avatar avatar-lg">
                                                    <img class="avatar-img rounded"
                                                         src="{{ $booking->detail->isNotEmpty() ? ($booking->detail[0]->service?->thumbnail_full_path ?? asset('assets/admin-module/img/icons/service-placeholder.png')) : asset('assets/admin-module/img/icons/service-placeholder.png') }}"
                                                         alt="{{ translate('provider-logo') }}">
                                                </div>
                                                <div class="media-body ">
                                                    <h5 class="d-flex align-items-center">{{translate('Booking')}}# {{$booking->readable_id}}
                                                        @if($booking->is_repeated)
                                                            <img src="{{ asset('assets/admin-module/img/icons/repeat.svg') }}"
                                                                 class="rounded-circle repeat-icon m-1" alt="{{ translate('repeat') }}">
                                                        @endif
                                                    </h5>
                                                    <p>{{date('d-m-Y, H:i a',strtotime($booking->created_at))}}</p>
                                                </div>
                                            </div>
                                            <span
                                                class="badge rounded-pill py-2 px-3 badge-primary text-capitalize">{{$booking->booking_status}}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card recent-leads dashboard-collapsible-widget h-100" id="dashboard-recent-leads">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5 class="dashboard-widget-title mb-0">
                                    <span class="material-symbols-outlined dashboard-widget-title__icon text-primary" aria-hidden="true">person_add</span>
                                    Recent Leads
                                </h5>
                                <a href="{{ route('admin.lead.index') }}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body">
                                <ul class="common-list">
                                    @if(count($data[7]['todays_pending_lead_followups'] ?? []) < 1)
                                        <div class="d-flex align-items-center justify-content-center h-100 w-100">
                                            <span class="opacity-50">{{translate('No_follow_ups_yet')}}</span>
                                        </div>
                                    @endif
                                    @foreach($data[7]['todays_pending_lead_followups'] ?? [] as $lead)
                                        @php($leadInitial = $lead->name ? strtoupper(substr($lead->name, 0, 1)) : 'L')
                                        <li class="d-flex flex-wrap gap-2 align-items-center justify-content-between cursor-pointer todays-followup-redirect"
                                            data-route="{{ route('admin.lead.show', $lead->id) }}">
                                            <div class="media align-items-center gap-3">
                                                <div class="avatar avatar-lg bg-light d-flex align-items-center justify-content-center rounded-circle">
                                                    <span class="fw-bold text-dark">{{ $leadInitial }}</span>
                                                </div>
                                                <div class="media-body">
                                                    <h5 class="mb-1">Lead# {{$lead->id}}</h5>
                                                    <p class="m-0 fs-12 opacity-75">{{ $lead->name ?? '—' }}</p>
                                                    <p class="m-0 fs-12 opacity-75">{{ $lead->handled_by_name ?? '—' }}</p>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column align-items-end gap-2">
                                                <span class="badge rounded-pill py-2 px-3 badge-primary text-capitalize">
                                                    {{ \Modules\LeadManagement\Entities\Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type }}
                                                </span>
                                                <p class="m-0 fs-12 opacity-75">
                                                    {{ $lead->next_followup_at ? $lead->next_followup_at->format('d-m-Y, H:i a') : '—' }}
                                                </p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card top-providers dashboard-collapsible-widget h-100" id="dashboard-top-providers">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5 class="dashboard-widget-title mb-0">
                                    <span class="material-icons dashboard-widget-title__icon text-primary" aria-hidden="true">emoji_events</span>
                                    {{ translate('top_providers') }}
                                </h5>
                                <a href="{{route('admin.provider.top-providers')}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body p-0">
                                @php($topProviders = $data[4]['top_providers'] ?? [])
                                <div class="table-responsive px-3">
                                    <table class="table table-hover align-middle dashboard-ranking-widget-table">
                                        <colgroup>
                                            <col>
                                            <col class="col-score">
                                            <col class="col-bookings">
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>{{ translate('Provider') }}</th>
                                            <th class="text-end col-score">{{ translate('Score') }}</th>
                                            <th class="text-end col-bookings">{{ translate('Bookings') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($topProviders as $provider)
                                            <tr class="provider-redirect"
                                                data-route="{{route('admin.provider.details',[$provider->id])}}?web_page=overview">
                                                <td>
                                                    <div class="media align-items-center gap-2 min-w-0">
                                                        <div class="avatar flex-shrink-0">
                                                            <img class="avatar-img rounded-circle"
                                                                 src="{{ $provider->logo_full_path }}"
                                                                 alt="{{ translate('logo') }}">
                                                        </div>
                                                        <div class="media-body min-w-0">
                                                            <h5 class="mb-0 fs-12 text-truncate">{{ $provider->company_name ?? '—' }}</h5>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end text-nowrap col-score">
                                                    <span class="fs-12 fw-medium">{{ (int) ($provider->performance_score ?? 0) }}</span>
                                                </td>
                                                <td class="text-end text-nowrap col-bookings">
                                                    <span class="fs-12 fw-medium">{{ $provider->completed_bookings_count ?? 0 }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center opacity-50 py-4">
                                                    {{ translate('No Bookings Found') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="card top-providers dashboard-collapsible-widget h-100" id="dashboard-top-customers">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5 class="dashboard-widget-title mb-0">
                                    <span class="material-icons dashboard-widget-title__icon text-primary" aria-hidden="true">groups</span>
                                    {{ translate('top_customers') }}
                                </h5>
                                <a href="{{route('admin.customer.top-customers')}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body p-0">
                                @php($topCustomers = $data[5]['top_customers'] ?? [])
                                <div class="table-responsive px-3">
                                    <table class="table table-hover align-middle dashboard-ranking-widget-table">
                                        <colgroup>
                                            <col>
                                            <col class="col-score">
                                            <col class="col-bookings">
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>{{ translate('Customer') }}</th>
                                            <th class="text-end col-score">{{ translate('Score') }}</th>
                                            <th class="text-end col-bookings">{{ translate('Bookings') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($topCustomers as $customer)
                                            <tr class="customer-redirect"
                                                data-route="{{route('admin.customer.detail',[$customer->id,'web_page'=>'overview'])}}">
                                                <td>
                                                    <div class="media align-items-center gap-2 min-w-0">
                                                        <div class="avatar flex-shrink-0">
                                                            <img class="avatar-img rounded-circle"
                                                                 src="{{ $customer->profile_image_full_path }}"
                                                                 alt="{{ $customer->first_name ?? 'Customer' }}">
                                                        </div>
                                                        <div class="media-body min-w-0">
                                                            <h5 class="mb-0 fs-12 text-truncate">
                                                                {{ trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: '—' }}
                                                            </h5>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end text-nowrap col-score">
                                                    <span class="fs-12 fw-medium">{{ (int) ($customer->performance_score ?? 0) }}</span>
                                                </td>
                                                <td class="text-end text-nowrap col-bookings">
                                                    <span class="fs-12 fw-medium">{{ $customer->completed_bookings_count ?? 0 }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center opacity-50 py-4">
                                                    {{ translate('No Bookings Found') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        @include('adminmodule::admin.partials._staff-presence-widget')
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="text-center">
                                    {{translate('welcome_to_admin_panel')}}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @else
        <div class="main-content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body dashboard-empty d-center">
                        <div class="text-center">
                            <img src="{{asset('/assets/empty-dashboard.png')}}" alt="">
                            <h3 class="p-2 mt-3">{{ translate('Welcome to') }} {{ business_config('business_name', 'business_information')?->live_values }}</h3>
                            <p class="">{{ translate('Get started by using the left menu to manage your tasks and tools.') }}</p>
                            <h6 class="">{{ translate('Happy working') }}!</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

@endsection


@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/apex/apexcharts.min.js"></script>

    <script>
        'use strict';

        function initDashboardCollapsibleWidgets() {
            var storageKey = 'adminDashboardWidgetStates';

            function getWidgetStates() {
                try {
                    return JSON.parse(localStorage.getItem(storageKey) || '{}');
                } catch (e) {
                    return {};
                }
            }

            function saveWidgetState(widgetId, expanded) {
                var states = getWidgetStates();
                states[widgetId] = expanded;
                localStorage.setItem(storageKey, JSON.stringify(states));
            }

            var widgetStates = getWidgetStates();

            $('.main-content .dashboard-collapsible-widget').each(function (index) {
                var $card = $(this);
                if ($card.data('collapse-initialized')) {
                    return;
                }
                $card.data('collapse-initialized', true);

                var widgetId = $card.attr('id');
                if (!widgetId) {
                    widgetId = 'dashboard-widget-' + index;
                    $card.attr('id', widgetId);
                }
                var bodyId = widgetId + '-body';
                var isExpanded = widgetStates[widgetId] === true;

                var $header = $card.children('.card-header').first();
                var $body = $card.children('.card-body').first();
                if (!$header.length || !$body.length) {
                    return;
                }

                var $collapse = $('<div class="collapse dashboard-widget-collapse-panel"></div>');
                if (isExpanded) {
                    $collapse.addClass('show');
                }
                $collapse.attr('id', bodyId);
                $body.detach().appendTo($collapse);
                $card.append($collapse);

                var $toggle = $(
                    '<button type="button" class="dashboard-widget-collapse-btn" data-bs-toggle="collapse" data-bs-target="#' + bodyId + '" aria-expanded="' + (isExpanded ? 'true' : 'false') + '" aria-controls="' + bodyId + '" aria-label="Toggle widget">' +
                    '<span class="material-symbols-outlined dashboard-widget-collapse-icon" aria-hidden="true">expand_more</span>' +
                    '</button>'
                );

                var $title = $header.find('.dashboard-widget-title').first();
                var $main = $('<div class="dashboard-widget-header-main"></div>');
                if ($title.length) {
                    $title.detach();
                    $main.append($toggle);
                    $main.append($title);
                    $header.prepend($main);
                } else {
                    $header.prepend($toggle);
                }

                var $otherChildren = $header.children().not('.dashboard-widget-header-main');
                if ($otherChildren.length) {
                    var $actions = $('<div class="dashboard-widget-header-actions"></div>');
                    $otherChildren.appendTo($actions);
                    $header.append($actions);
                }

                $header.addClass('dashboard-widget-collapse-header');

                $header.on('click', function (e) {
                    if ($(e.target).closest('a, button:not(.dashboard-widget-collapse-btn), select, input, label, .select2-container, .badge').length) {
                        return;
                    }
                    var collapseEl = document.getElementById(bodyId);
                    if (!collapseEl || typeof bootstrap === 'undefined') {
                        return;
                    }
                    bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).toggle();
                });

                $collapse.on('shown.bs.collapse hidden.bs.collapse', function () {
                    var expanded = $collapse.hasClass('show');
                    $toggle.attr('aria-expanded', expanded ? 'true' : 'false');
                    saveWidgetState(widgetId, expanded);
                    if (expanded && typeof chart !== 'undefined' && chart && $card.hasClass('earning-statistics')) {
                        setTimeout(function () {
                            chart.resize();
                        }, 50);
                    }
                });

                if (isExpanded && $card.hasClass('earning-statistics') && typeof chart !== 'undefined' && chart) {
                    setTimeout(function () {
                        chart.resize();
                    }, 100);
                }

                if ($card.hasClass('earning-statistics')) {
                    $card.find('select.js-select.update-chart').each(function () {
                        var $select = $(this);
                        if ($select.data('select2')) {
                            $select.select2('destroy');
                        }
                    });
                    $card.find('.dashboard-widget-header-actions .select2-container').remove();
                }
            });
        }

        // Restructure widget headers before Select2 runs (avoids duplicate year dropdowns).
        initDashboardCollapsibleWidgets();

        var earningChartMonthCategories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        function applyEarningChartResponse(response) {
            chart.updateOptions({
                xaxis: {
                    categories: response.categories || earningChartMonthCategories
                }
            });
            chart.updateSeries([{
                name: "{{translate('Total_Revenue')}}",
                data: response.total_earning
            }, {
                name: "{{translate('Our_Earning')}}",
                data: response.commission_earning
            }]);
        }

        function update_chart(year, month) {
            var url = '{{route('admin.update-dashboard-earning-graph')}}?year=' + encodeURIComponent(year);
            if (month) {
                url += '&month=' + encodeURIComponent(month);
            }

            $.getJSON(url, function (response) {
                applyEarningChartResponse(response);
            });
        }

        $(document).off('change.dashboardEarningYear', '.js-select.update-chart-year')
            .on('change.dashboardEarningYear', '.js-select.update-chart-year', function () {
                var selectedYear = $(this).val();
                var $monthSelect = $('.js-select.update-chart-month');

                localStorage.setItem('selectedYear', selectedYear);
                localStorage.removeItem('selectedMonth');
                $monthSelect.val(null);
                if ($monthSelect.data('select2')) {
                    $monthSelect.trigger('change.select2');
                }

                update_chart(selectedYear);
            });

        $(document).off('change.dashboardEarningMonth', '.js-select.update-chart-month')
            .on('change.dashboardEarningMonth', '.js-select.update-chart-month', function () {
                var selectedMonth = $(this).val();
                var selectedYear = $('.js-select.update-chart-year').val();

                if (selectedMonth) {
                    localStorage.setItem('selectedMonth', selectedMonth);
                } else {
                    localStorage.removeItem('selectedMonth');
                }

                update_chart(selectedYear, selectedMonth || null);
            });

        $(document).ready(function() {
            var storedYear = localStorage.getItem('selectedYear');
            var storedMonth = localStorage.getItem('selectedMonth');

            if (storedYear) {
                $('.js-select.update-chart-year').val(storedYear);
            }
            if (storedMonth) {
                $('.js-select.update-chart-month').val(storedMonth);
            }

            if (storedYear || storedMonth) {
                if ($('.js-select.update-chart-year').data('select2')) {
                    $('.js-select.update-chart-year').trigger('change.select2');
                }
                if ($('.js-select.update-chart-month').data('select2')) {
                    $('.js-select.update-chart-month').trigger('change.select2');
                }

                update_chart(
                    storedYear || $('.js-select.update-chart-year').val(),
                    storedMonth || null
                );
            }
        });

        var options = {
            series: [
                {
                    name: "{{translate('Total_Revenue')}}",
                    data: @json($chart_data['total_earning'])
                },
                {
                    name: "{{translate('Our_Earning')}}",
                    data: @json($chart_data['commission_earning'])
                }
            ],
            chart: {
                height: 386,
                type: 'line',
                dropShadow: {
                    enabled: true,
                    color: '#000',
                    top: 18,
                    left: 7,
                    blur: 10,
                    opacity: 0.2
                },
                toolbar: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    offsetX: 0,
                    formatter: function (value) {
                        return Math.abs(value)
                    }
                },
            },
            colors: ['#4FA7FF', '#82C662'],
            dataLabels: {
                enabled: false,
            },
            stroke: {
                curve: 'smooth',
            },
            grid: {
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                borderColor: '#CAD2FF',
                strokeDashArray: 5,
            },
            markers: {
                size: 1
            },
            theme: {
                mode: 'light',
            },
            xaxis: {
                categories: @json($chart_data['categories'])
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                floating: false,
                offsetY: -10,
                offsetX: 0,
                itemMargin: {
                    horizontal: 10,
                    vertical: 10
                },
            },
            padding: {
                top: 0,
                right: 0,
                bottom: 200,
                left: 10
            },
        };

        if (localStorage.getItem('dir') === 'rtl') {
            options.yaxis.labels.offsetX = -20;
        }

        var chart = new ApexCharts(document.querySelector("#apex_line-chart"), options);
        chart.render();
        try {
            var dashboardWidgetStates = JSON.parse(localStorage.getItem('adminDashboardWidgetStates') || '{}');
            if (dashboardWidgetStates['dashboard-earning-statistics']) {
                setTimeout(function () {
                    chart.resize();
                }, 150);
            }
        } catch (e) {}

        $(".provider-redirect").on('click', function(){
            location.href = $(this).data('route');
        });

        $(".customer-redirect").on('click', function(){
            location.href = $(this).data('route');
        });

        $(".recent-booking-redirect").on('click', function(){
            location.href = $(this).data('route');
        });

        $(".todays-followup-redirect").on('click', function(){
            var route = $(this).data('route');
            if (route && route !== '#') location.href = route;
        });

        function refreshStaffPresenceWidget() {
            $.getJSON('{{ route('admin.staff-presence.list') }}', function (response) {
                if (!response.data || !response.data.staff) return;
                var summary = response.data.summary || {};
                ['online', 'away', 'on_break', 'offline'].forEach(function (key) {
                    var el = document.querySelector('[data-summary="' + key + '"]');
                    if (el) el.textContent = summary[key] || 0;
                });
                response.data.staff.forEach(function (member) {
                    var row = document.querySelector('#staff-presence-tbody tr[data-staff-id="' + member.id + '"]');
                    if (!row) return;
                    var badge = row.querySelector('.staff-presence-badge');
                    if (badge) {
                        badge.textContent = member.presence_label;
                        badge.className = 'badge rounded-pill staff-presence-badge ' + ({
                            online: 'bg-success',
                            away: 'bg-warning text-dark',
                            on_break: 'bg-info text-dark',
                            offline: 'bg-secondary'
                        }[member.presence_status] || 'bg-secondary');
                    }
                    var dot = row.querySelector('.staff-presence-dot');
                    if (dot) {
                        dot.className = 'position-absolute bottom-0 end-0 rounded-circle border border-white staff-presence-dot ' + ({
                            online: 'bg-success',
                            away: 'bg-warning',
                            on_break: 'bg-info',
                            offline: 'bg-secondary'
                        }[member.presence_status] || 'bg-secondary');
                    }
                    var pageCell = row.querySelector('.staff-last-visited-page');
                    if (pageCell) {
                        pageCell.textContent = member.last_visited_page_label || '—';
                        pageCell.setAttribute('title', member.last_visited_page || '');
                    }
                    var lastOfflineCell = row.querySelector('.staff-last-offline-today');
                    if (lastOfflineCell) {
                        lastOfflineCell.textContent = member.last_offline_period_today || '—';
                    }
                    var totalOfflineCell = row.querySelector('.staff-total-offline-today');
                    if (totalOfflineCell) {
                        totalOfflineCell.textContent = member.total_offline_today || '—';
                    }
                    var lastAwayCell = row.querySelector('.staff-last-away-today');
                    if (lastAwayCell) {
                        lastAwayCell.textContent = member.last_away_period_today || '—';
                    }
                    var totalAwayCell = row.querySelector('.staff-total-away-today');
                    if (totalAwayCell) {
                        totalAwayCell.textContent = member.total_away_today || '—';
                    }
                    var lastBreakCell = row.querySelector('.staff-last-break-today');
                    if (lastBreakCell) {
                        lastBreakCell.textContent = member.last_break_period_today || '—';
                    }
                    var totalBreakCell = row.querySelector('.staff-total-break-today');
                    if (totalBreakCell) {
                        totalBreakCell.textContent = member.total_break_today || '—';
                    }
                    var totalOnlineCell = row.querySelector('.staff-total-online-today');
                    if (totalOnlineCell) {
                        totalOnlineCell.textContent = member.total_online_today || '—';
                    }
                });
            });
        }
        refreshStaffPresenceWidget();
        setInterval(refreshStaffPresenceWidget, 30000);

        var staffPresenceHistoryDatesLoaded = false;

        function setStaffPresenceHistoryState(state) {
            document.getElementById('staff-presence-history-empty').classList.toggle('d-none', state !== 'empty');
            document.getElementById('staff-presence-history-loading').classList.toggle('d-none', state !== 'loading');
            document.getElementById('staff-presence-history-table-wrap').classList.toggle('d-none', state !== 'table');
        }

        function renderStaffPresenceHistoryRows(staff) {
            var tbody = document.getElementById('staff-presence-history-tbody');
            if (!tbody) return;
            tbody.innerHTML = '';
            (staff || []).forEach(function (member) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td class="staff-presence-employee-col"><div class="d-flex align-items-center gap-2">' +
                        '<div class="position-relative flex-shrink-0 staff-presence-avatar-wrap">' +
                        '<img src="' + (member.profile_image || '') + '" alt="" class="avatar rounded-circle staff-presence-avatar" width="36" height="36">' +
                        '</div>' +
                        '<div><div class="fw-medium">' + (member.name || '') + '</div>' +
                        '<div class="small text-muted">' + (member.email || '') + '</div></div></div></td>' +
                    '<td class="text-muted">' + (member.last_offline_period || '—') + '</td>' +
                    '<td class="text-muted">' + (member.total_offline || '—') + '</td>' +
                    '<td class="text-muted">' + (member.last_away_period || '—') + '</td>' +
                    '<td class="text-muted">' + (member.total_away || '—') + '</td>' +
                    '<td class="text-muted">' + (member.last_break_period || '—') + '</td>' +
                    '<td class="text-muted">' + (member.total_break || '—') + '</td>' +
                    '<td class="text-muted">' + (member.total_online || '—') + '</td>';
                tbody.appendChild(tr);
            });
        }

        function loadStaffPresenceHistory(date) {
            if (!date) return;
            setStaffPresenceHistoryState('loading');
            $.getJSON('{{ route('admin.staff-presence.history') }}', { date: date }, function (response) {
                if (!response.data || !response.data.staff) {
                    setStaffPresenceHistoryState('empty');
                    return;
                }
                renderStaffPresenceHistoryRows(response.data.staff);
                setStaffPresenceHistoryState('table');
                var title = document.getElementById('staffPresenceHistoryModalLabel');
                if (title && response.data.date_label) {
                    title.textContent = @json(translate('Employee_Status_History')) + ' — ' + response.data.date_label;
                }
            }).fail(function () {
                setStaffPresenceHistoryState('empty');
            });
        }

        function loadStaffPresenceHistoryDates() {
            var select = document.getElementById('staff-presence-history-date');
            if (!select) return;
            select.disabled = true;
            select.innerHTML = '<option value="">' + @json(translate('Loading')) + '...</option>';
            setStaffPresenceHistoryState('loading');

            $.getJSON('{{ route('admin.staff-presence.history-dates') }}', function (response) {
                var dates = (response.data && response.data.dates) ? response.data.dates : [];
                if (!dates.length) {
                    select.innerHTML = '<option value="">' + @json(translate('No_presence_history_available')) + '</option>';
                    select.disabled = true;
                    setStaffPresenceHistoryState('empty');
                    return;
                }

                select.innerHTML = dates.map(function (item) {
                    return '<option value="' + item.value + '">' + item.label + (item.is_today ? ' (' + @json(translate('Today')) + ')' : '') + '</option>';
                }).join('');
                select.disabled = false;
                staffPresenceHistoryDatesLoaded = true;
                loadStaffPresenceHistory(dates[0].value);
            }).fail(function () {
                select.innerHTML = '<option value="">' + @json(translate('No_presence_history_available')) + '</option>';
                setStaffPresenceHistoryState('empty');
            });
        }

        $('#staffPresenceHistoryModal').on('show.bs.modal', function () {
            if (!staffPresenceHistoryDatesLoaded) {
                loadStaffPresenceHistoryDates();
            }
        });

        $('#staff-presence-history-date').on('change', function () {
            loadStaffPresenceHistory(this.value);
        });
    </script>
@endpush
