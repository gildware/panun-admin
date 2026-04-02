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
        .dashboard-widget-recent-bookings-leads .card-body,
        .dashboard-widget-top-providers-customers .card-body {
            min-height: 420px;
            max-height: 420px;
            overflow: auto;
        }

    </style>
@endpush

@section('content')
    @can('dashboard')
    <div class="main-content">
        <div class="container-fluid">
            @if(access_checker('dashboard'))
                <div class="row mb-4 g-4 dashboard-top-cards">
                    <div class="col-lg-2 col-sm-4">
                        <div class="business-summary business-summary-customers">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.total_revenue', 0))}}</h2>
                            <h3>{{translate('Total_Revenue')}}</h3>
                            <img src="{{asset('assets/admin-module')}}/img/icons/customers.png"
                                 class="absolute-img"
                                 alt="">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-4">
                        <div class="business-summary business-summary-earning">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.service_charges_total', 0))}}</h2>
                            <h3>{{translate('Service_Charges')}}</h3>
                            <img src="{{asset('assets/admin-module')}}/img/icons/total-earning.png"
                                 class="absolute-img" alt="">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-4">
                        <div class="business-summary business-summary-providers">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.spare_parts_total', 0))}}</h2>
                            <h3>{{translate('Parts_Charges')}}</h3>
                            <img src="{{asset('assets/admin-module')}}/img/icons/providers.png"
                                 class="absolute-img"
                                 alt="">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-4">
                        <div class="business-summary business-summary-earning">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.our_earning', 0))}}</h2>
                            <h3>{{translate('Our_Earning')}}</h3>
                            <img src="{{asset('assets/admin-module')}}/img/icons/total-earning.png"
                                 class="absolute-img" alt="">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-4">
                        <div class="business-summary business-summary-providers">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.payable_amount', 0))}}</h2>
                            <h3>{{translate('Payable_Amount')}}</h3>
                            <img src="{{asset('assets/admin-module')}}/img/icons/providers.png"
                                 class="absolute-img"
                                 alt="">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-4">
                        <div class="business-summary business-summary-services">
                            <h2>{{with_currency_symbol(data_get($data[0], 'top_cards.balance_with_providers', 0))}}</h2>
                            <h3>{{translate('Balance_With_Providers')}}</h3>
                            <img src="{{asset('assets/admin-module')}}/img/icons/services.png"
                                 class="absolute-img"
                                 alt="">
                        </div>
                    </div>
                </div>
                <div class="row g-4 mb-4">
                    <div class="col-lg-6 col-12 col-sm-6">
                        <div class="card dashboard-widget-todays-followups">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5>
                                    Booking Follow-ups- Pending Till Today's
                                    <span class="text-muted">
                                        ({{ $data[5]['todays_pending_followups_total'] ?? 0 }})
                                    </span>
                                </h5>
                                <a href="{{route('admin.booking.todays_followups')}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body p-0">
                                @if(isset($data[5]['todays_pending_followups']) && $data[5]['todays_pending_followups']->isNotEmpty())
                                    <div class="table-responsive px-3 overflow-auto">
                                        <table class="table table-hover align-middle mb-0 fs-13 text-nowrap">
                                            <thead class="text-secondary border-bottom">
                                                <tr>
                                                    <th>{{translate('Booking_ID')}}</th>
                                                    <th>{{translate('Follow_up_for')}}</th>
                                                    <th>{{translate('Customer_Info')}}</th>
                                                    <th>{{translate('Provider_Info')}}</th>
                                                    <th>{{translate('Assignee')}}</th>
                                                    <th>{{translate('Followup_On')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data[5]['todays_pending_followups'] as $followup)
                                                    <tr class="cursor-pointer todays-followup-redirect {{ $followup->date && !$followup->date->isToday() ? 'missed-followup-row' : '' }}"
                                                        data-route="{{ $followup->booking ? (route('admin.booking.details', [$followup->booking_id, 'web_page' => 'followups'])) : '#' }}">
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
                                                        <td>
                                                            @php($due = $followup->date)
                                                            @if(!$due)
                                                                —
                                                            @elseif($due->isToday())
                                                                {{ translate('Today') }}
                                                            @elseif($due->isYesterday())
                                                                {{ translate('Yesterday') }}
                                                            @else
                                                                @php($daysBefore = max(1, (int) round($due->diffInRealDays(\Carbon\Carbon::now(), true))))
                                                                {{ $daysBefore }} {{ translate('days_before') }}
                                                            @endif
                                                        </td>
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
                    <div class="col-lg-6 col-12 col-sm-6">
                        <div class="card dashboard-widget-todays-followups">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5>
                                    Leads Follow-ups- Pending Till Today's
                                    <span class="text-muted">
                                        ({{ $data[6]['todays_pending_lead_followups_total'] ?? 0 }})
                                    </span>
                                </h5>
                                <a href="{{ route('admin.lead.todays_followups') }}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body p-0">
                                @if(isset($data[6]['todays_pending_lead_followups']) && $data[6]['todays_pending_lead_followups']->isNotEmpty())
                                    <div class="table-responsive px-3 overflow-auto">
                                        <table class="table table-hover align-middle mb-0 fs-13 text-nowrap">
                                            <thead class="text-secondary border-bottom">
                                                <tr>
                                                    <th>{{translate('Lead_ID')}}</th>
                                                    <th>{{translate('Lead_Type')}}</th>
                                                    <th>{{translate('Name')}}</th>
                                                    <th>{{translate('Phone')}}</th>
                                                    <th>{{translate('Handled_By')}}</th>
                                                    <th>{{translate('Followup_On')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data[6]['todays_pending_lead_followups'] as $lead)
                                                    <tr class="cursor-pointer todays-followup-redirect {{ $lead->next_followup_at && !$lead->next_followup_at->isToday() ? 'missed-followup-row' : '' }}"
                                                        data-route="{{ route('admin.lead.show', $lead->id) }}">
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
                                                        <td>
                                                            @php($due = $lead->next_followup_at)
                                                            @if(!$due)
                                                                —
                                                            @elseif($due->isToday())
                                                                {{ translate('Today') }}
                                                            @elseif($due->isYesterday())
                                                                {{ translate('Yesterday') }}
                                                            @else
                                                                @php($daysBefore = max(1, (int) round($due->diffInRealDays(\Carbon\Carbon::now(), true))))
                                                                {{ $daysBefore }} {{ translate('days_before') }}
                                                            @endif
                                                        </td>
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

                <div class="row g-4 mb-4">
                    <div class="col-lg-6 col-12 col-sm-6">
                        <div class="card earning-statistics">
                            <div class="card-body ps-0">
                                <div class="ps-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <h4>{{translate('earning_statistics')}}</h4>
                                    <div
                                        class="position-relative index-2 d-flex flex-wrap gap-3 align-items-center justify-content-between">
                                        <ul class="option-select-btn">
                                            <li>
                                                <label>
                                                    <input type="radio" name="statistics" hidden checked>
                                                    <span class="d-flex align-items-center border shadow-none h-36">{{translate('Yearly')}}</span>
                                                </label>
                                            </li>
                                        </ul>

                                        <div class="select-wrap d-flex flex-wrap gap-10">
                                            <select class="js-select update-chart">
                                                @php($from_year=date('Y'))
                                                @php($to_year=$from_year-10)
                                                @while($from_year!=$to_year)
                                                    <option
                                                        value="{{$from_year}}" {{session()->has('dashboard_earning_graph_year') && session('dashboard_earning_graph_year') == $from_year?'selected':''}}>
                                                        {{$from_year}}
                                                    </option>
                                                    @php($from_year--)
                                                @endwhile
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="apex_line-chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 col-sm-6">
                        <div class="card recent-transactions h-100 w-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-10">
                                    <h4 class="mb-3">{{translate('recent_ledger_transactions')}}</h4>
                                    <a href="{{route('admin.ledger.index')}}"
                                       class="btn-link">{{translate('view_all')}}</a>
                                </div>
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
                </div>
                {{-- <div class="row g-4 mb-4 pk-dashboard-old-widgets">
                    <div class="col-lg-4 col-12 col-sm-6">
                        <div class="card top-providers">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5>{{translate('top_providers')}}</h5>
                                <a href="{{route('admin.provider.top-providers')}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body">
                                <ul class="common-list">
                                    @foreach($data[3]['top_providers'] as $provider)
                                        <li class="provider-redirect"
                                            data-route="{{route('admin.provider.details',[$provider->id])}}?web_page=overview">
                                            <div class="media gap-3">
                                                <div class="avatar avatar-lg">
                                                    <img class="avatar-img rounded-circle" src="{{ $provider->logo_full_path }}" alt="{{ translate('logo') }}">
                                                </div>
                                                <div class="media-body ">
                                                    <h5>{{\Illuminate\Support\Str::limit($provider->company_name,20)}}</h5>
                                                    <span class="common-list_rating d-flex gap-1">
                                                        <span class="material-icons">star</span>
                                                        {{$provider->avg_rating}}
                                                    </span>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-12 col-sm-6">
                        <div class="card recent-activities">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5>{{translate('recent_bookings')}}</h5>
                                <a href="{{route('admin.booking.list', ['booking_status'=>'all', 'service_type' => 'all'])}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body">
                                <ul class="common-list">
                                    @foreach($data[2]['bookings'] as $booking)
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
                    <div class="col-lg-3 col-12 col-sm-6">
                        <div class="card top-providers">
                            <div class="card-header d-flex flex-column gap-10">
                                <h5>{{translate('booking_statistics')}} - {{date('M, Y')}}</h5>
                            </div>
                            <div class="card-body booking-statistics-info">
                                @if(isset($data[4]['zone_wise_bookings']))
                                    <ul class="common-list after-none gap-10 d-flex flex-column">
                                        @foreach($data[4]['zone_wise_bookings'] as $booking)
                                            <li>
                                                <div
                                                    class="mb-2 d-flex align-items-center justify-content-between gap-10 flex-wrap">
                                                    <span
                                                        class="zone-name">{{$booking->zone?$booking->zone->name:translate('zone_not_available')}}</span>
                                                    <span
                                                        class="booking-count">{{$booking->total}} {{translate('bookings')}}</span>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"
                                                         style="width: {{$booking->total}}%"
                                                         aria-valuenow="{{$booking->total}}" aria-valuemin="0"
                                                         aria-valuemax="100"></div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <span class="opacity-50">{{translate('No Bookings Found')}}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>--}}
                <div class="row g-4 mb-4 dashboard-widget-recent-bookings-leads">
                    <div class="col-lg-6 col-12 col-sm-6">
                        <div class="card recent-activities">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5>{{translate('recent_bookings')}}</h5>
                                <a href="{{route('admin.booking.list', ['booking_status'=>'all', 'service_type' => 'all'])}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body">
                                <ul class="common-list">
                                    @if(count($data[2]['bookings'] ?? []) < 1)
                                        <div class="d-flex align-items-center justify-content-center h-100 w-100">
                                            <span class="opacity-50">{{translate('No Bookings Found')}}</span>
                                        </div>
                                    @endif
                                    @foreach($data[2]['bookings'] ?? [] as $booking)
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

                    <div class="col-lg-6 col-12 col-sm-6">
                        <div class="card recent-leads">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5>Recent Leads</h5>
                                <a href="{{ route('admin.lead.index') }}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body">
                                <ul class="common-list">
                                    @if(count($data[6]['todays_pending_lead_followups'] ?? []) < 1)
                                        <div class="d-flex align-items-center justify-content-center h-100 w-100">
                                            <span class="opacity-50">{{translate('No_follow_ups_yet')}}</span>
                                        </div>
                                    @endif
                                    @foreach($data[6]['todays_pending_lead_followups'] ?? [] as $lead)
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
                </div>

                <div class="row g-4 mb-4 dashboard-widget-top-providers-customers">
                    <div class="col-lg-6 col-12 col-sm-6">
                        <div class="card top-providers">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5>Top Providers</h5>
                                <a href="{{route('admin.provider.top-providers')}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body">
                                <ul class="common-list">
                                    @if(count($data[3]['top_providers'] ?? []) < 1)
                                        <div class="d-flex align-items-center justify-content-center h-100 w-100">
                                            <span class="opacity-50">{{translate('No Bookings Found')}}</span>
                                        </div>
                                    @endif
                                    @foreach($data[3]['top_providers'] ?? [] as $provider)
                                        <li class="d-flex align-items-center justify-content-between gap-3 cursor-pointer provider-redirect"
                                            data-route="{{route('admin.provider.details',[$provider->id])}}?web_page=overview">
                                            <div class="media gap-3 flex-grow-1">
                                                <div class="avatar avatar-lg">
                                                    <img class="avatar-img rounded-circle"
                                                         src="{{ $provider->logo_full_path }}"
                                                         alt="{{ translate('logo') }}">
                                                </div>
                                                <div class="media-body">
                                                    <h5 class="mb-0 text-break">{{ $provider->company_name ?? '—' }}</h5>
                                                    <p class="m-0 fs-12 opacity-75 text-break">
                                                        {{ $provider->company_address ?? '—' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex-shrink-0" style="width: 120px;">
                                                @php(
                                                    $categoryNames = $provider->subscribed_services
                                                        ? $provider->subscribed_services->pluck('category.name')->filter()->unique()->values()->all()
                                                        : []
                                                )
                                                <p class="m-0 fs-12 opacity-75">{{ $categoryNames[0] ?? '—' }}</p>
                                            </div>

                                            <div class="text-end" style="min-width: 90px;">
                                                <p class="m-0 fs-12 opacity-75">{{ $provider->completed_bookings_count ?? 0 }} {{translate('bookings')}}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-12 col-sm-6">
                        <div class="card top-providers">
                            <div class="card-header d-flex justify-content-between gap-10">
                                <h5>Top Customers</h5>
                                <a href="{{route('admin.customer.top-customers')}}"
                                   class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="card-body">
                                <ul class="common-list">
                                    @if(count($data[4]['top_customers'] ?? []) < 1)
                                        <div class="d-flex align-items-center justify-content-center h-100 w-100">
                                            <span class="opacity-50">{{translate('No Bookings Found')}}</span>
                                        </div>
                                    @endif
                                    @foreach($data[4]['top_customers'] ?? [] as $customer)
                                        <li class="d-flex align-items-center justify-content-between gap-3 cursor-pointer customer-redirect"
                                            data-route="{{route('admin.customer.detail',[$customer->id,'web_page'=>'overview'])}}">
                                            <div class="media gap-3 flex-grow-1">
                                                <div class="avatar avatar-lg">
                                                    <img class="avatar-img rounded-circle"
                                                         src="{{ $customer->profile_image_full_path }}"
                                                         alt="{{ $customer->first_name ?? 'Customer' }}">
                                                </div>
                                                <div class="media-body">
                                                    <h5 class="mb-0">
                                                        {{\Illuminate\Support\Str::limit(trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')), 20)}}
                                                    </h5>
                                                </div>
                                            </div>

                                            <div class="text-end" style="min-width: 90px;">
                                                <p class="m-0 fs-12 opacity-75">{{ $customer->completed_bookings_count ?? 0 }} {{translate('bookings')}}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
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

        $('.js-select.update-chart').on('change', function() {
            var selectedYear = $(this).val();
            localStorage.setItem('selectedYear', selectedYear); // Store the selected year in local storage
            update_chart(selectedYear);
        });

        // On page load, check if a year is stored in local storage
        $(document).ready(function() {
            var storedYear = localStorage.getItem('selectedYear');
            if (storedYear) {
                $('.js-select.update-chart').val(storedYear); // Set the select to the stored year
                update_chart(storedYear); // Update the chart with the stored year
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
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
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

        function update_chart(year) {
            var url = '{{route('admin.update-dashboard-earning-graph')}}?year=' + year;

            $.getJSON(url, function (response) {
                chart.updateSeries([{
                    name: "{{translate('Total_Revenue')}}",
                    data: response.total_earning
                }, {
                    name: "{{translate('Our_Earning')}}",
                    data: response.commission_earning
                }])
            });
        }


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
    </script>
@endpush
