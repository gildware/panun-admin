@extends('adminmodule::layouts.new-master')

@section('title', translate('Calls'))

@push('css_or_js')
    <style>
        .calls-filter-bar { display: flex; align-items: flex-end; flex-wrap: nowrap; gap: 0.4rem; width: 100%; margin-bottom: 0.65rem; }
        .calls-filter-field { display: flex; flex-direction: column; gap: 0.15rem; flex: 1 1 0; width: 0; min-width: 0; }
        .calls-filter-field--search { flex: 1.6 1 0; }
        .calls-filter-field--range { flex: 1.8 1 0; }
        .calls-filter-field--direction { flex: 0 0 auto; width: auto; }
        .calls-filter-field label { margin: 0; font-size: 0.68rem; font-weight: 600; line-height: 1.1; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .calls-filter-bar .form-control,
        .calls-filter-bar .form-select { height: 34px; width: 100%; min-width: 0; padding: 0.2rem 0.45rem; font-size: 0.78rem; }
        .calls-date-range { display: flex; align-items: center; gap: 0.25rem; min-width: 0; }
        .calls-date-range .form-control { flex: 1 1 0; width: 0; }
        .calls-date-range__sep { flex: 0 0 auto; color: #6b7280; font-size: 0.75rem; line-height: 1; }
        .calls-filter-bar .btn { flex: 0 0 auto; height: 34px; padding: 0 0.7rem; font-size: 0.78rem; white-space: nowrap; }
        .calls-sort-btn { width: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .calls-sort-btn .material-icons { font-size: 18px; line-height: 1; }
        .calls-sort-btn.is-active { border-color: #111827; color: #111827; }
        .calls-sort .dropdown-item { font-size: 0.8rem; }
        .calls-sort .dropdown-item.active { font-weight: 700; background: #f3f5f8; color: #111827; }
        .calls-list-card .card-body { padding: 0.5rem 0.75rem 0.75rem; }
        .calls-list-card { overflow: hidden; }
        .calls-list-scroll { max-height: calc(100vh - 22rem); overflow-x: hidden; overflow-y: auto; }
        .calls-list-table thead th { position: sticky; top: 0; z-index: 2; background: #fff; box-shadow: inset 0 -1px 0 #e5e7eb; }
        .calls-list-table { width: 100%; table-layout: fixed; font-size: 0.8125rem; border-collapse: separate; border-spacing: 0; --bs-table-striped-bg: #f3f5f8; --bs-table-hover-bg: #e7eef6; }
        .calls-list-table.table > :not(caption) > * > * { height: 52px; padding: 0 0.35rem; white-space: nowrap; overflow: visible; vertical-align: middle; }
        .calls-list-table .calls-link-lead-btn { padding: 0.32rem 0.6rem; font-size: 0.75rem; }
        .calls-list-table td.calls-lead-cell { white-space: nowrap; }
        .calls-lead-line { display: inline-flex; align-items: center; gap: 0.4rem; max-width: 100%; }
        .party-pill { display: inline-flex; align-items: center; padding: 0.22rem 0.45rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700; line-height: 1.2; white-space: nowrap; }
        .party-pill--provider { background: #e7f0ff; color: #1d4ed8; }
        .party-pill--customer { background: #e7f6ee; color: #0f7a3a; }
        .call-link-who { padding: 0.75rem 0.85rem; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fafc; }
        .call-link-who__kind { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #6b7280; }
        .call-link-who__name { font-weight: 700; color: #111827; }
        .calls-link-lead-btn { padding: 0.42rem 0.95rem; font-size: 0.8125rem; font-weight: 600; border-radius: 8px; line-height: 1.2; }
        .lead-chip { display: inline-flex; align-items: stretch; border-radius: 999px; overflow: hidden; text-decoration: none; border: 1px solid #e5e7eb; background: #fff; line-height: 1; }
        .lead-chip__id { padding: 0.28rem 0.4rem; color: #111827; font-size: 0.72rem; font-weight: 700; }
        .lead-chip__type { padding: 0.28rem 0.45rem; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.01em; }
        .lead-chip--unknown .lead-chip__type { background: #f1f3f5; color: #4b5563; }
        .lead-chip--customer .lead-chip__type { background: #e7f6ee; color: #0f7a3a; }
        .lead-chip--provider .lead-chip__type { background: #e7f0ff; color: #1d4ed8; }
        .lead-chip--invalid .lead-chip__type { background: #fdecea; color: #c62828; }
        .lead-chip--future_customer .lead-chip__type { background: #fff4e5; color: #b45309; }
        .lead-chip--future_provider .lead-chip__type { background: #eef2ff; color: #4338ca; }
        .lead-chip:hover { border-color: #cbd5e1; }
        .booking-chip { display: inline-flex; align-items: stretch; border-radius: 999px; overflow: hidden; text-decoration: none; border: 1px solid #e5e7eb; background: #fff; line-height: 1; }
        .booking-chip__id { padding: 0.28rem 0.4rem; color: #111827; font-size: 0.72rem; font-weight: 700; }
        .booking-chip__status { padding: 0.28rem 0.45rem; font-size: 0.68rem; font-weight: 700; background: #f1f3f5; color: #4b5563; }
        .booking-chip--pending .booking-chip__status, .booking-chip--accepted .booking-chip__status { background: #fff4e5; color: #b45309; }
        .booking-chip--completed .booking-chip__status, .booking-chip--ongoing .booking-chip__status { background: #e7f6ee; color: #0f7a3a; }
        .booking-chip--canceled .booking-chip__status, .booking-chip--cancelled .booking-chip__status, .booking-chip--refund_request .booking-chip__status { background: #fdecea; color: #c62828; }
        .booking-chip:hover { border-color: #cbd5e1; }
        .calls-list-table thead th { font-size: 0.75rem; font-weight: 600; }
        .calls-list-table .material-icons { font-size: 16px; line-height: 1; vertical-align: middle; }
        .calls-list-card .pagination { margin-bottom: 0; }
        .call-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            padding: 0.12rem 0.5rem 0.12rem 0.35rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }
        .call-pill .material-icons { font-size: 14px; }
        .call-pill--in { background: #e7f6ee; color: #0f7a3a; }
        .call-pill--out { background: #e7f0ff; color: #1d4ed8; }
        .call-pill--answered { background: #e7f6ee; color: #0f7a3a; }
        .call-pill--missed { background: #fdecea; color: #c62828; }
        .call-pill--unanswered { background: #fff4e5; color: #b45309; }
        .call-pill--other { background: #fff4e5; color: #b45309; }
        .call-pill--unknown { background: #f1f3f5; color: #6b7280; }
        .call-pill--type-unknown { background: #f1f3f5; color: #6b7280; }
        .call-pill--type-customer { background: #e7f6ee; color: #0f7a3a; }
        .call-pill--type-provider { background: #e7f0ff; color: #1d4ed8; }
        .call-pill--type-invalid { background: #fdecea; color: #c62828; }
        .call-pill--type-future_customer { background: #fff4e5; color: #b45309; }
        .call-pill--type-future_provider { background: #eef2ff; color: #4338ca; }
        .call-pill--rec { background: #eef2ff; color: #4338ca; padding-left: 0.35rem; padding-right: 0.35rem; }
        .call-pill--note { background: #f3f4f6; color: #4b5563; padding-left: 0.35rem; padding-right: 0.35rem; }
        .calls-direction-btn { height: 34px; display: inline-flex; align-items: center; gap: 0.3rem; padding: 0 0.55rem; font-size: 0.78rem; font-weight: 600; white-space: nowrap; }
        .calls-direction-btn .material-icons { font-size: 16px; line-height: 1; }
        .calls-direction .dropdown-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; }
        .calls-direction .dropdown-item .material-icons { font-size: 16px; }
        .calls-direction .dropdown-item.active { font-weight: 700; background: #f3f5f8; color: #111827; }
        .calls-th { display: flex; align-items: center; gap: 0.1rem; }
        .calls-th-label { white-space: nowrap; }
        .calls-th-sort { display: inline-flex; align-items: center; flex: 0 0 auto; gap: 0; }
        .calls-th-sort a { display: inline-flex; color: #c4c9d1; text-decoration: none; line-height: 1; }
        .calls-th-sort a .material-icons { font-size: 13px; }
        .calls-th-sort a:hover { color: #4b5563; }
        .calls-th-sort a.is-active { color: #111827; }
    </style>
@endpush

@section('content')
    @php
        $directionToggle = request('direction');
        $toggleQuery = request()->except(['direction', 'page']);
        $directionReset = in_array($directionToggle, ['INBOUND', 'OUTBOUND'], true)
            ? ['direction' => $directionToggle]
            : [];
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-2">
                <h2 class="page-title mb-0">{{ translate('Calls') }}</h2>
            </div>

            <div class="card calls-list-card">
                <div class="card-body">
            <form method="get" action="{{ route('admin.calls.index') }}" class="calls-filter-bar" id="calls-filter-form">
                @if($directionReset)
                    <input type="hidden" name="direction" value="{{ $directionReset['direction'] }}">
                @endif
                <div class="calls-filter-field calls-filter-field--search">
                    <label for="call-q">{{ translate('Search') }}</label>
                    <input id="call-q" type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ translate('Phone_name_or_agent') }}">
                </div>
                <div class="calls-filter-field">
                    <label for="call-outcome">{{ translate('Call_result') }}</label>
                    <select id="call-outcome" name="outcome" class="form-select">
                        <option value="">{{ translate('All') }}</option>
                        <option value="answered" @selected(request('outcome') === 'answered')>{{ translate('Answered') }}</option>
                        <option value="missed" @selected(request('outcome') === 'missed')>{{ translate('Missed') }}</option>
                        <option value="unanswered" @selected(request('outcome') === 'unanswered')>{{ translate('Unanswered') }}</option>
                        <option value="other" @selected(request('outcome') === 'other')>{{ translate('Other') }}</option>
                        <option value="unknown" @selected(request('outcome') === 'unknown')>{{ translate('Unknown') }}</option>
                    </select>
                </div>
                <div class="calls-filter-field">
                    <label for="call-agent">{{ translate('Employee') }}</label>
                    <select id="call-agent" name="agent" class="form-select">
                        <option value="">{{ translate('All') }}</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent }}" @selected(request('agent') === $agent)>{{ $agent }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="calls-filter-field">
                    <label for="call-matched">{{ translate('Match_type') }}</label>
                    <select id="call-matched" name="matched" class="form-select">
                        <option value="">{{ translate('All') }}</option>
                        <option value="yes" @selected(request('matched') === 'yes')>{{ translate('Matched') }}</option>
                        <option value="no" @selected(request('matched') === 'no')>{{ translate('Unmatched') }}</option>
                    </select>
                </div>
                <div class="calls-filter-field calls-filter-field--direction">
                    <label id="call-direction-label">{{ translate('Direction') }}</label>
                    <div class="dropdown calls-direction">
                        <button type="button"
                                class="btn btn-outline-secondary calls-direction-btn"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-labelledby="call-direction-label">
                            @if($directionToggle === 'INBOUND')
                                <span class="material-icons">call_received</span>
                                {{ translate('Inbound') }}
                            @elseif($directionToggle === 'OUTBOUND')
                                <span class="material-icons">call_made</span>
                                {{ translate('Outbound') }}
                            @else
                                {{ translate('All') }}
                            @endif
                            <span class="material-icons">expand_more</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item {{ in_array($directionToggle, ['INBOUND', 'OUTBOUND'], true) ? '' : 'active' }}"
                                   href="{{ route('admin.calls.index', $toggleQuery) }}">{{ translate('All') }}</a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ $directionToggle === 'INBOUND' ? 'active' : '' }}"
                                   href="{{ route('admin.calls.index', array_merge($toggleQuery, ['direction' => 'INBOUND'])) }}">
                                    <span class="material-icons">call_received</span>
                                    {{ translate('Inbound') }}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ $directionToggle === 'OUTBOUND' ? 'active' : '' }}"
                                   href="{{ route('admin.calls.index', array_merge($toggleQuery, ['direction' => 'OUTBOUND'])) }}">
                                    <span class="material-icons">call_made</span>
                                    {{ translate('Outbound') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="calls-filter-field calls-filter-field--range">
                    <label for="call-from">{{ translate('Date_range') }}</label>
                    <div class="calls-date-range">
                        <input id="call-from" type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="{{ translate('From') }}">
                        <span class="calls-date-range__sep" aria-hidden="true">–</span>
                        <input id="call-to" type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="{{ translate('To') }}">
                    </div>
                </div>
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">
                <a href="{{ route('admin.calls.index') }}" class="btn btn--secondary">{{ translate('Clear_filter') }}</a>
                @php
                    $sortOptions = [
                        ['sort' => 'started_at', 'dir' => 'desc', 'label' => translate('Newest_first')],
                        ['sort' => 'started_at', 'dir' => 'asc', 'label' => translate('Oldest_first')],
                        ['sort' => 'duration', 'dir' => 'desc', 'label' => translate('Longest_first')],
                        ['sort' => 'duration', 'dir' => 'asc', 'label' => translate('Shortest_first')],
                        ['sort' => 'employee', 'dir' => 'asc', 'label' => translate('Employee_A_to_Z')],
                        ['sort' => 'employee', 'dir' => 'desc', 'label' => translate('Employee_Z_to_A')],
                    ];
                    $sortIsCustom = !($sort === 'started_at' && $dir === 'desc');
                @endphp
                <div class="dropdown calls-sort">
                    <button type="button"
                            class="btn btn-outline-secondary calls-sort-btn {{ $sortIsCustom ? 'is-active' : '' }}"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="{{ translate('Sort') }}">
                        <span class="material-icons">sort</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach($sortOptions as $option)
                            <li>
                                <button type="button"
                                        class="dropdown-item {{ $sort === $option['sort'] && $dir === $option['dir'] ? 'active' : '' }}"
                                        data-sort="{{ $option['sort'] }}"
                                        data-dir="{{ $option['dir'] }}">
                                    {{ $option['label'] }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </form>
                <div class="calls-list-scroll">
                    <table class="table table-striped table-hover table-sm align-middle mb-0 calls-list-table">
                        <colgroup>
                            <col style="width: 12%">
                            <col style="width: 8%">
                            <col style="width: 10%">
                            <col style="width: 7%">
                            <col style="width: 8%">
                            <col style="width: 7%">
                            <col style="width: 7%">
                            <col style="width: 14%">
                            <col style="width: 10%">
                            <col style="width: 11%">
                            <col style="width: 6%">
                        </colgroup>
                        <thead>
                        @php
                            $sortUrl = function (string $column, string $order) {
                                return route('admin.calls.index', array_merge(request()->except(['sort', 'dir', 'page']), [
                                    'sort' => $column,
                                    'dir' => $order,
                                ]));
                            };
                        @endphp
                        <tr>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Time') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'started_at'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Direction') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'direction'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Phone') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'phone'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Employee') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'employee'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Result') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'outcome'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Duration') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'duration'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Recording') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'recording'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Customer_or_Provider') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'party'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Lead') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'lead'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Booking') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'booking'])</span></th>
                            <th class="text-end">{{ translate('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($calls as $call)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.calls.show', $call) }}">
                                        {{ $call->started_at?->format('d M Y, h:i A') ?? '—' }}
                                    </a>
                                </td>
                                <td>
                                    @if($call->direction === 'INBOUND')
                                        <span class="call-pill call-pill--in">
                                            <span class="material-icons">call_received</span>
                                            {{ translate('Inbound') }}
                                        </span>
                                    @elseif($call->direction === 'OUTBOUND')
                                        <span class="call-pill call-pill--out">
                                            <span class="material-icons">call_made</span>
                                            {{ translate('Outbound') }}
                                        </span>
                                    @else
                                        {{ $call->directionLabel() }}
                                    @endif
                                </td>
                                <td title="{{ $call->customer_phone }}">{{ $call->customer_phone ?: '—' }}</td>
                                <td title="{{ $call->staff_name }}">{{ $call->staff_name ?: '—' }}</td>
                                <td>
                                    @php
                                        $outcome = $call->outcome ?: 'unknown';
                                        if ($outcome === 'missed' && $call->direction === 'OUTBOUND') {
                                            $outcome = 'unanswered';
                                        }
                                        $outcomeIcon = match ($outcome) {
                                            'answered' => 'call',
                                            'missed' => 'phone_missed',
                                            'unanswered' => 'call_end',
                                            'other' => 'phone_disabled',
                                            default => 'help_outline',
                                        };
                                        $outcomeClass = match ($outcome) {
                                            'answered' => 'call-pill--answered',
                                            'missed' => 'call-pill--missed',
                                            'unanswered' => 'call-pill--unanswered',
                                            'other' => 'call-pill--other',
                                            default => 'call-pill--unknown',
                                        };
                                    @endphp
                                    <span class="call-pill {{ $outcomeClass }}">
                                        <span class="material-icons">{{ $outcomeIcon }}</span>
                                        {{ $call->outcomeLabel() }}
                                    </span>
                                </td>
                                <td>{{ $call->durationLabel() }}</td>
                                <td>
                                    @if($call->hasRecording())
                                        <span class="call-pill call-pill--rec" title="{{ translate('Recording') }}">
                                            <span class="material-icons">mic</span>
                                        </span>
                                    @elseif($call->summary_text)
                                        <span class="call-pill call-pill--note" title="{{ translate('Summary') }}">
                                            <span class="material-icons">notes</span>
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $partyProvider = trim((string) ($call->provider->company_name ?? $call->provider->contact_person_name ?? ''));
                                        $partyCustomer = trim((string) (($call->customer->first_name ?? '').' '.($call->customer->last_name ?? '')));
                                    @endphp
                                    @if($call->provider)
                                        <span class="party-pill party-pill--provider" title="{{ $partyProvider }}">{{ translate('Provider') }}{{ $partyProvider !== '' ? ' · '.$partyProvider : '' }}</span>
                                    @elseif($call->customer)
                                        <span class="party-pill party-pill--customer" title="{{ $partyCustomer }}">{{ translate('Customer') }}{{ $partyCustomer !== '' ? ' · '.$partyCustomer : '' }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="calls-lead-cell">
                                    @include('leadmanagement::admin.calls._lead_cell', [
                                        'call' => $call,
                                        'openBookings' => $bookingChoices[$call->id] ?? collect(),
                                    ])
                                </td>
                                <td>
                                    @if($call->booking)
                                        @php $bookingStatus = (string) ($call->booking->booking_status ?: 'pending'); @endphp
                                        <a class="booking-chip booking-chip--{{ $bookingStatus }}" href="{{ route('admin.booking.details', [$call->booking_id, 'web_page' => 'details']) }}">
                                            <span class="booking-chip__id">{{ $call->booking->readable_id ?: translate('Booking') }}</span>
                                            <span class="booking-chip__status">{{ translate(ucfirst(str_replace('_', ' ', $bookingStatus))) }}</span>
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if(!$call->lead && !$call->booking)
                                        @php
                                            $linkProvider = trim((string) ($call->provider->company_name ?? $call->provider->contact_person_name ?? ''));
                                            $linkCustomer = trim((string) (($call->customer->first_name ?? '').' '.($call->customer->last_name ?? '')));
                                            if ($call->provider) {
                                                $linkParty = 'provider';
                                                $linkPartyName = $linkProvider !== '' ? $linkProvider : translate('Provider');
                                            } elseif ($call->customer) {
                                                $linkParty = 'customer';
                                                $linkPartyName = $linkCustomer !== '' ? $linkCustomer : translate('Customer');
                                            } else {
                                                $linkParty = 'none';
                                                $linkPartyName = '';
                                            }
                                        @endphp
                                        <button type="button"
                                                class="btn btn--primary calls-link-lead-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#call-link-lead-modal"
                                                data-phone="{{ $call->customer_phone }}"
                                                data-party="{{ $linkParty }}"
                                                data-party-name="{{ $linkPartyName }}"
                                                data-options-url="{{ route('admin.calls.link-options', $call) }}"
                                                data-lead-action="{{ route('admin.calls.link', $call) }}"
                                                data-booking-action="{{ route('admin.calls.booking', $call) }}">
                                            {{ translate('Link_call') }}
                                        </button>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-3">{{ translate('No_calls_yet') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                    <div class="mt-2">{{ $calls->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var form = document.getElementById('calls-filter-form');
            if (!form) return;
            var search = form.querySelector('[name="q"]');
            var timer;
            form.addEventListener('change', function (event) {
                if (search && event.target === search) return;
                form.submit();
            });
            form.querySelectorAll('[data-sort]').forEach(function (item) {
                item.addEventListener('click', function () {
                    form.querySelector('[name="sort"]').value = item.getAttribute('data-sort');
                    form.querySelector('[name="dir"]').value = item.getAttribute('data-dir');
                    form.submit();
                });
            });
            if (!search) return;
            search.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { form.submit(); }, 400);
            });
            search.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                clearTimeout(timer);
                form.submit();
            });
        })();
    </script>
@endpush
