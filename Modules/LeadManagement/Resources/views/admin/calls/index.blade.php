@extends('adminmodule::layouts.new-master')

@section('title', translate('Calls'))

@push('css_or_js')
    <style>
        .calls-filter-bar { display: flex; align-items: flex-end; flex-wrap: nowrap; gap: 0.4rem; width: 100%; margin-bottom: 0; }
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
        .calls-filter-card .card-body { padding: 0.65rem 0.75rem; }
        .calls-list-card .card-body { padding: 0; }
        .calls-list-card { overflow: hidden; }
        .calls-list-scroll { max-height: calc(100dvh - 16.5rem); overflow-x: auto; overflow-y: auto; }
        .calls-list-table thead th { position: sticky; top: 0; z-index: 2; background: #fff; box-shadow: inset 0 -1px 0 #e5e7eb; }
        .calls-list-table { width: 100%; min-width: 1520px; table-layout: fixed; font-size: 0.8125rem; border-collapse: separate; border-spacing: 0; --bs-table-striped-bg: #f3f5f8; --bs-table-hover-bg: #e7eef6; }
        .calls-list-table td.calls-call-id-cell { overflow: visible; text-overflow: clip; text-align: center; }
        .calls-copy-id { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
        .calls-copy-id .material-icons { font-size: 16px; line-height: 1; }
        .calls-copy-id.is-copied { color: #0f7a3a; border-color: #86efac; background: #e7f6ee; }
        .calls-list-table.table > :not(caption) > * > * { height: 52px; padding: 0 0.55rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }
        .calls-list-table.table > :not(caption) > * > *.calls-actions-cell {
            height: auto;
            min-height: 52px;
            overflow: visible;
            text-overflow: clip;
            padding: 0.45rem 0.75rem;
            text-align: left;
            vertical-align: middle;
        }
        .calls-actions-stack {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            gap: 0.35rem;
            width: 100%;
        }
        .calls-actions-primary {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-start;
            gap: 0.35rem;
            width: 100%;
        }
        .calls-actions-stack > form,
        .calls-actions-primary > form { display: inline-flex; margin: 0; flex: 0 0 auto; }
        .calls-list-table .calls-link-lead-btn,
        .calls-list-table .calls-unmatch-btn,
        .calls-list-table .calls-new-lead-btn,
        .calls-list-table .calls-contact-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            min-width: 5.75rem;
            min-height: 30px;
            padding: 0.28rem 0.55rem;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.2;
            border-radius: 8px;
            white-space: nowrap;
            box-sizing: border-box;
        }
        .calls-list-table .calls-delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            min-width: 30px;
            height: 30px;
            padding: 0;
            border-radius: 8px;
            flex: 0 0 30px;
        }
        .calls-contact-btn { border-radius: 8px; font-weight: 600; line-height: 1.2; white-space: nowrap; }
        #call-contact-modal .call-contact-channels { display: flex; flex-direction: column; gap: 0.45rem; }
        #call-contact-modal .form-check { margin-bottom: 0; }
        #call-contact-modal .form-check-label { font-weight: 600; }
        .calls-unmatch-btn { background-color: var(--bs-danger); color: #fff; border: 1px solid var(--bs-danger); border-radius: 8px; font-weight: 600; line-height: 1.2; }
        .calls-unmatch-btn:hover,
        .calls-unmatch-btn:focus { background-color: #bb2d3b; color: #fff; border-color: #bb2d3b; }
        .calls-delete-btn .material-icons { font-size: 18px; line-height: 1; }
        .calls-new-lead-btn { border-color: #cbd5e1; color: #111827; background: #fff; }
        .calls-new-lead-btn:hover,
        .calls-new-lead-btn:focus { border-color: #94a3b8; color: #111827; background: #f8fafc; }
        .calls-list-table td.calls-lead-cell { white-space: nowrap; }
        .calls-lead-line { display: inline-flex; align-items: center; gap: 0.4rem; max-width: 100%; }
        .party-pill { display: inline-flex; align-items: center; padding: 0.22rem 0.45rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700; line-height: 1.2; white-space: nowrap; }
        .party-pill--provider { background: #e7f0ff; color: #1d4ed8; }
        .party-pill--customer { background: #e7f6ee; color: #0f7a3a; }
        .call-link-who { padding: 0.75rem 0.85rem; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fafc; }
        .call-link-who__kind { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #6b7280; }
        .call-link-who__name { font-weight: 700; color: #111827; }
        .calls-link-lead-btn { font-weight: 600; border-radius: 8px; line-height: 1.2; }
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
        .booking-chip { display: inline-flex; align-items: stretch; max-width: 100%; min-width: 0; border-radius: 999px; overflow: hidden; text-decoration: none; border: 1px solid #e5e7eb; background: #fff; line-height: 1; }
        .booking-chip__id { min-width: 0; overflow: hidden; text-overflow: ellipsis; padding: 0.28rem 0.4rem; color: #111827; font-size: 0.72rem; font-weight: 700; }
        .booking-chip__status { flex-shrink: 0; padding: 0.28rem 0.45rem; font-size: 0.68rem; font-weight: 700; background: #f1f3f5; color: #4b5563; white-space: nowrap; }
        .booking-chip--pending .booking-chip__status, .booking-chip--accepted .booking-chip__status { background: #fff4e5; color: #b45309; }
        .booking-chip--completed .booking-chip__status, .booking-chip--ongoing .booking-chip__status { background: #e7f6ee; color: #0f7a3a; }
        .booking-chip--canceled .booking-chip__status, .booking-chip--cancelled .booking-chip__status, .booking-chip--refund_request .booking-chip__status { background: #fdecea; color: #c62828; }
        .booking-chip:hover { border-color: #cbd5e1; }
        .calls-list-table thead th { font-size: 0.75rem; font-weight: 600; }
        .calls-list-table .material-icons { font-size: 16px; line-height: 1; vertical-align: middle; }
        .calls-scroll-sentinel { display: flex; align-items: center; justify-content: center; gap: 0.4rem; min-height: 36px; color: #6b7280; font-size: 0.75rem; }
        .calls-scroll-sentinel[hidden] { display: none; }
        .calls-scroll-sentinel__spin { width: 14px; height: 14px; border: 2px solid #d1d5db; border-top-color: #4b5563; border-radius: 50%; animation: calls-scroll-spin 0.7s linear infinite; }
        @keyframes calls-scroll-spin { to { transform: rotate(360deg); } }
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
        .call-pill--needs-callback { background: #c62828; color: #fff; }
        .call-pill--contact-phone { background: #e7f6ee; color: #0f7a3a; }
        .call-pill--contact-chat { background: #e7f0ff; color: #1d4ed8; }
        .call-pill--contact-not_picked { background: #fff4e5; color: #b45309; }
        .calls-result-pills { display: inline-flex; flex-direction: column; align-items: flex-start; gap: 0.15rem; vertical-align: middle; max-width: 100%; }
        .calls-list-table.table > tbody > tr.calls-row--needs-callback > * {
            --bs-table-bg: #fff6f6;
            --bs-table-accent-bg: #fdecea;
            --bs-table-bg-type: #fdecea;
            background-color: #fff6f6;
        }
        .calls-list-table.table > tbody > tr.calls-row--needs-callback > td:first-child {
            box-shadow: inset 3px 0 0 #c62828, inset 0 0 0 9999px #fdecea;
        }
        .calls-list-table.table-hover > tbody > tr.calls-row--needs-callback:hover > * {
            --bs-table-accent-bg: #f8d7d4;
            --bs-table-bg-type: #f8d7d4;
            background-color: #f8d7d4;
        }
        .calls-list-table.table-hover > tbody > tr.calls-row--needs-callback:hover > td:first-child {
            box-shadow: inset 3px 0 0 #c62828, inset 0 0 0 9999px #f8d7d4;
        }
        .calls-list-table.table > tbody > tr.calls-row--contacted > * {
            --bs-table-bg: #f3fbf6;
            --bs-table-accent-bg: #e7f6ee;
            --bs-table-bg-type: #e7f6ee;
            background-color: #f3fbf6;
        }
        .calls-list-table.table > tbody > tr.calls-row--contacted > td:first-child {
            box-shadow: inset 3px 0 0 #0f7a3a, inset 0 0 0 9999px #e7f6ee;
        }
        .calls-list-table.table > tbody > tr.calls-row--not-picked > * {
            --bs-table-bg: #fffaf3;
            --bs-table-accent-bg: #fff4e5;
            --bs-table-bg-type: #fff4e5;
            background-color: #fffaf3;
        }
        .calls-list-table.table > tbody > tr.calls-row--not-picked > td:first-child {
            box-shadow: inset 3px 0 0 #b45309, inset 0 0 0 9999px #fff4e5;
        }
        .calls-list-table.table > tbody > tr.calls-row--needs-callback > td,
        .calls-list-table.table > tbody > tr.calls-row--contacted > td,
        .calls-list-table.table > tbody > tr.calls-row--not-picked > td {
            height: 96px;
            overflow: visible;
        }
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
        button.call-pill--rec { border: 0; cursor: pointer; }
        button.call-pill--rec:hover,
        button.call-pill--rec:focus { background: #e0e7ff; color: #3730a3; }
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
            @if(is_super_admin())
                <div class="d-flex justify-content-end mb-2">
                    <a href="{{ route('admin.calls.deleted') }}" class="btn btn--secondary btn-sm">{{ translate('Deleted_calls') }}</a>
                </div>
            @endif
            <div class="card calls-filter-card mb-3">
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
                        <option value="needs_callback" @selected(request('outcome') === 'needs_callback')>{{ translate('Needs_callback') }}</option>
                        <option value="contacted" @selected(request('outcome') === 'contacted')>{{ translate('Contacted') }}</option>
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
                </div>
            </div>

            <div class="card calls-list-card">
                <div class="card-body">
                <div class="calls-list-scroll" id="calls-list-scroll" data-has-more="{{ $calls->hasMorePages() ? '1' : '0' }}" data-next-page="2">
                    <table class="table table-striped table-hover table-sm align-middle mb-0 calls-list-table">
                        <colgroup>
                            <col style="width: 11%">
                            <col style="width: 8%">
                            <col style="width: 7%">
                            <col style="width: 8%">
                            <col style="width: 7%">
                            <col style="width: 11%">
                            <col style="width: 5%">
                            <col style="width: 4%">
                            <col style="width: 9%">
                            <col style="width: 7%">
                            <col style="width: 9%">
                            <col style="width: 14%">
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
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Call_id') }}</span></span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Direction') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'direction'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Phone') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'phone'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Employee') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'employee'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Result') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'outcome'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Duration') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'duration'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Recording') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'recording'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Customer_or_Provider') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'party'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Lead') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'lead'])</span></th>
                            <th><span class="calls-th"><span class="calls-th-label">{{ translate('Booking') }}</span> @include('leadmanagement::admin.calls._sort_icons', ['column' => 'booking'])</span></th>
                            <th class="calls-actions-cell">{{ translate('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody id="calls-list-body">
                        @if($calls->isEmpty())
                            <tr>
                                <td colspan="12" class="text-center text-muted py-3">{{ translate('No_calls_yet') }}</td>
                            </tr>
                        @else
                            @include('leadmanagement::admin.calls._rows')
                        @endif
                        </tbody>
                    </table>
                    <div id="calls-scroll-sentinel" class="calls-scroll-sentinel" hidden>
                        <span class="calls-scroll-sentinel__spin" aria-hidden="true"></span>
                        <span>{{ translate('Loading') }}…</span>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="call-contact-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" id="call-contact-form" enctype="multipart/form-data" action="{{ session('contact_modal_action', '#') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Mark_contacted') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p id="call-contact-phone" class="fw-bold mb-1">{{ session('contact_modal_phone') }}</p>
                        <p class="text-muted small">{{ translate('Earlier_missed_calls_from_this_number_will_be_cleared') }}</p>
                        <div class="mb-3">
                            <div class="form-label">{{ translate('Contacted_through') }}</div>
                            <div class="call-contact-channels">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="channel" id="call-contact-channel-phone" value="phone" @checked(old('channel', 'phone') !== 'chat')>
                                    <label class="form-check-label" for="call-contact-channel-phone">{{ translate('Phone') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="channel" id="call-contact-channel-chat" value="chat" @checked(old('channel') === 'chat')>
                                    <label class="form-check-label" for="call-contact-channel-chat">{{ translate('Chat') }}</label>
                                </div>
                            </div>
                            <div class="mt-3" data-phone-result>
                                <div class="form-label">{{ translate('Phone_result') }}</div>
                                <div class="call-contact-channels">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="phone_result" id="call-contact-picked" value="picked" @checked(old('phone_result', 'picked') !== 'not_picked')>
                                        <label class="form-check-label" for="call-contact-picked">{{ translate('Customer_picked_up') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="phone_result" id="call-contact-not-picked" value="not_picked" @checked(old('phone_result') === 'not_picked')>
                                        <label class="form-check-label" for="call-contact-not-picked">{{ translate('Customer_did_not_pick_up') }}</label>
                                    </div>
                                </div>
                            </div>
                            @error('channel')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3" data-contact-file="phone">
                            <label class="form-label" for="call-contact-recording">{{ translate('Recording') }} <span class="text-muted">({{ translate('Optional') }})</span></label>
                            <input id="call-contact-recording" type="file" name="recording" class="form-control" accept="audio/*,.mp3,.wav,.m4a,.aac,.ogg,.webm,.mp4">
                            @error('recording')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3" data-contact-file="chat">
                            <label class="form-label" for="call-contact-screenshot">{{ translate('Screenshot') }} <span class="text-muted">({{ translate('Optional') }})</span></label>
                            <input id="call-contact-screenshot" type="file" name="screenshot" class="form-control" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                            @error('screenshot')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label" for="call-contact-remarks">{{ translate('Remarks') }}</label>
                            <textarea id="call-contact-remarks" name="remarks" class="form-control" rows="3" required maxlength="2000">{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Mark_contacted') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="call-recording-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="call-recording-title">{{ translate('Recording') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <audio id="call-recording-audio" controls preload="metadata" class="w-100" style="height: 54px;"></audio>
                    <p id="call-recording-error" class="text-danger small mb-0 mt-2 d-none">{{ translate('No_recording_available') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="call-create-lead-modal" tabindex="-1" aria-labelledby="call-create-lead-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" id="call-create-lead-form" action="#">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="call-create-lead-modal-title">{{ translate('New_lead') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">{{ translate('Create_new_lead_from_this_call_confirm') }}</p>
                        <p id="call-create-lead-phone" class="fw-bold mb-0"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Confirm') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(is_super_admin())
        <div class="modal fade" id="call-delete-modal" tabindex="-1" aria-labelledby="call-delete-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body pt-5 p-md-5">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                        <div class="d-flex justify-content-center mb-4">
                            <img width="75" height="75"
                                 src="{{ asset('assets/admin-module/img/media/delete.png') }}"
                                 class="rounded-circle" alt="">
                        </div>
                        <h3 class="text-center mb-2 fw-medium" id="call-delete-modal-title">
                            {{ translate('Are_you_sure_you_want_to_delete_this_call') }}
                        </h3>
                        <p class="text-center fs-12 fw-medium text-muted mb-1" id="call-delete-modal-item"></p>
                        <p class="text-center fs-12 text-muted mb-0">{{ translate('Call_will_move_to_deleted_calls') }}</p>
                        <form method="post" id="call-delete-form" action="#">
                            @csrf
                            @method('DELETE')
                            <div class="d-flex justify-content-center gap-3 mt-3">
                                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                                    {{ translate('Cancel') }}
                                </button>
                                <button type="submit" class="btn btn-danger">
                                    {{ translate('Delete') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script')
    <script>
        (function () {
            var form = document.getElementById('calls-filter-form');
            var search = form ? form.querySelector('[name="q"]') : null;
            var scroller = document.getElementById('calls-list-scroll');
            var body = document.getElementById('calls-list-body');
            var sentinel = document.getElementById('calls-scroll-sentinel');
            if (!form || !body) return;

            var timer = null;
            var nextPage = 2;
            var loading = false;
            var done = scroller ? scroller.getAttribute('data-has-more') !== '1' : true;
            var failed = false;
            var listRequestId = 0;
            var noCallsLabel = @json(translate('No_calls_yet'));

            var bindTips = function (root) {
                if (!window.bootstrap || !bootstrap.Tooltip) return;
                root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                    if (!bootstrap.Tooltip.getInstance(el)) {
                        new bootstrap.Tooltip(el);
                    }
                });
            };

            var syncUrlFromForm = function () {
                var url = new URL(window.location.href);
                var params = new URLSearchParams(new FormData(form));
                ['page', 'rows'].forEach(function (key) { params.delete(key); });
                Array.from(params.keys()).forEach(function (key) {
                    if (String(params.get(key) || '').trim() === '') {
                        params.delete(key);
                    }
                });
                var query = params.toString();
                history.replaceState({}, '', url.pathname + (query ? '?' + query : ''));
            };

            var keepSearchFocus = function () {
                if (!search || document.activeElement === search) return;
                search.focus({ preventScroll: true });
                try {
                    var len = search.value.length;
                    search.setSelectionRange(len, len);
                } catch (e) {}
            };

            var setListHtml = function (html, hasMore, next) {
                var trimmed = (html || '').trim();
                if (trimmed === '') {
                    body.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-3">' + noCallsLabel + '</td></tr>';
                } else {
                    var template = document.createElement('template');
                    template.innerHTML = '<table><tbody>' + trimmed + '</tbody></table>';
                    body.innerHTML = '';
                    template.content.querySelectorAll('tr').forEach(function (row) {
                        body.appendChild(row);
                    });
                    bindTips(body);
                }
                nextPage = next || 2;
                done = !hasMore;
                failed = false;
                loading = false;
                if (scroller) {
                    scroller.setAttribute('data-has-more', hasMore ? '1' : '0');
                    scroller.scrollTop = 0;
                }
                if (sentinel) sentinel.hidden = true;
            };

            var refreshList = function (options) {
                options = options || {};
                var req = ++listRequestId;
                syncUrlFromForm();
                var url = new URL(window.location.href);
                url.searchParams.set('rows', '1');
                url.searchParams.set('page', '1');
                loading = true;
                done = false;
                failed = false;
                if (sentinel) sentinel.hidden = false;
                fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).then(function (response) {
                    if (!response.ok) throw new Error('load failed');
                    return response.json();
                }).then(function (data) {
                    if (req !== listRequestId) return;
                    setListHtml(
                        data && data.html ? data.html : '',
                        !!(data && data.has_more),
                        data && data.next_page ? data.next_page : 2
                    );
                    if (options.focusSearch) {
                        keepSearchFocus();
                    }
                    if (!done && scroller && nearEnd()) {
                        loadMore();
                    }
                }).catch(function () {
                    if (req !== listRequestId) return;
                    loading = false;
                    failed = true;
                    if (sentinel) sentinel.hidden = true;
                    if (options.focusSearch) {
                        keepSearchFocus();
                    }
                });
            };

            var nearEnd = function () {
                if (!scroller) return false;
                return scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 160;
            };

            var loadMore = function () {
                if (!scroller || !sentinel || loading || done) return;
                if (!nearEnd() && scroller.scrollHeight > scroller.clientHeight + 8) return;
                loading = true;
                sentinel.hidden = false;
                var url = new URL(window.location.href);
                url.searchParams.set('rows', '1');
                url.searchParams.set('page', String(nextPage));
                var req = listRequestId;
                fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).then(function (response) {
                    if (!response.ok) throw new Error('load failed');
                    return response.json();
                }).then(function (data) {
                    if (req !== listRequestId) return;
                    var html = (data && data.html) ? data.html.trim() : '';
                    if (html !== '') {
                        var template = document.createElement('template');
                        template.innerHTML = '<table><tbody>' + html + '</tbody></table>';
                        template.content.querySelectorAll('tr').forEach(function (row) {
                            body.appendChild(row);
                        });
                        bindTips(body);
                    }
                    if (!data || !data.has_more || html === '') {
                        done = true;
                    } else {
                        nextPage = data.next_page || (nextPage + 1);
                    }
                }).catch(function () {
                    if (req !== listRequestId) return;
                    failed = true;
                }).then(function () {
                    if (req !== listRequestId) return;
                    loading = false;
                    sentinel.hidden = true;
                    if (!done && !failed && nearEnd()) loadMore();
                });
            };

            form.addEventListener('change', function (event) {
                if (search && event.target === search) return;
                refreshList();
            });
            form.querySelectorAll('[data-sort]').forEach(function (item) {
                item.addEventListener('click', function () {
                    form.querySelector('[name="sort"]').value = item.getAttribute('data-sort');
                    form.querySelector('[name="dir"]').value = item.getAttribute('data-dir');
                    refreshList();
                });
            });
            if (search) {
                search.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        refreshList({ focusSearch: true });
                    }, 400);
                });
                search.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') return;
                    event.preventDefault();
                    clearTimeout(timer);
                    refreshList({ focusSearch: true });
                });
            }
            if (scroller && sentinel) {
                scroller.addEventListener('scroll', function () {
                    failed = false;
                    if (nearEnd()) loadMore();
                });
                if (!done) loadMore();
            }
        })();

        (function () {
            var modalEl = document.getElementById('call-contact-modal');
            var form = document.getElementById('call-contact-form');
            if (!modalEl || !form) return;

            var syncChannel = function () {
                var selected = form.querySelector('[name="channel"]:checked');
                var channel = selected ? selected.value : 'phone';
                var phoneResult = form.querySelector('[data-phone-result]');
                if (phoneResult) {
                    phoneResult.hidden = channel !== 'phone';
                    phoneResult.querySelectorAll('input').forEach(function (input) {
                        input.disabled = channel !== 'phone';
                    });
                }
                form.querySelectorAll('[data-contact-file]').forEach(function (block) {
                    var active = block.getAttribute('data-contact-file') === channel;
                    block.hidden = !active;
                    block.querySelectorAll('input').forEach(function (input) {
                        input.disabled = !active;
                    });
                });
            };

            form.querySelectorAll('[name="channel"]').forEach(function (input) {
                input.addEventListener('change', syncChannel);
            });
            syncChannel();

            modalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;
                form.action = button.getAttribute('data-contact-action') || form.action;
                var phone = document.getElementById('call-contact-phone');
                if (phone) phone.textContent = button.getAttribute('data-contact-phone') || '';
                form.reset();
                var phoneChannel = form.querySelector('[name="channel"][value="phone"]');
                var picked = form.querySelector('[name="phone_result"][value="picked"]');
                if (phoneChannel) phoneChannel.checked = true;
                if (picked) picked.checked = true;
                syncChannel();
            });

            @if(session('contact_modal_action') && ($errors->has('channel') || $errors->has('phone_result') || $errors->has('remarks') || $errors->has('recording') || $errors->has('screenshot')))
            if (window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
            @endif
        })();

        (function () {
            var modalEl = document.getElementById('call-create-lead-modal');
            var form = document.getElementById('call-create-lead-form');
            if (!modalEl || !form) return;

            modalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;
                form.action = button.getAttribute('data-create-lead-action') || '#';
                var phone = document.getElementById('call-create-lead-phone');
                if (phone) phone.textContent = button.getAttribute('data-create-lead-phone') || '';
            });
        })();

        (function () {
            var modalEl = document.getElementById('call-delete-modal');
            var form = document.getElementById('call-delete-form');
            var labelEl = document.getElementById('call-delete-modal-item');
            if (!modalEl || !form) return;

            modalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;
                form.action = button.getAttribute('data-delete-url') || '#';
                if (labelEl) {
                    labelEl.textContent = button.getAttribute('data-delete-label') || '';
                }
            });
        })();

        (function () {
            var modalEl = document.getElementById('call-recording-modal');
            var audio = document.getElementById('call-recording-audio');
            var error = document.getElementById('call-recording-error');
            var title = document.getElementById('call-recording-title');
            if (!modalEl || !audio || !window.bootstrap) return;

            document.addEventListener('click', function (event) {
                var button = event.target.closest('.calls-play-recording');
                if (!button) return;
                if (error) error.classList.add('d-none');
                if (title) title.textContent = button.getAttribute('data-recording-title') || '';
                audio.src = button.getAttribute('data-recording-url') || '';
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                var play = audio.play();
                if (play && typeof play.catch === 'function') {
                    play.catch(function () {
                        if (error) error.classList.remove('d-none');
                    });
                }
            });

            audio.addEventListener('error', function () {
                if (audio.getAttribute('src') && error) error.classList.remove('d-none');
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                audio.pause();
                audio.removeAttribute('src');
                audio.load();
            });
        })();

        (function () {
            var copiedLabel = @json(translate('Copied'));
            var copyLabel = @json(translate('Copy_call_id'));
            var setTip = function (button, text) {
                button.setAttribute('data-bs-title', text);
                button.setAttribute('title', text);
                button.setAttribute('aria-label', text);
                var tip = window.bootstrap && bootstrap.Tooltip ? bootstrap.Tooltip.getInstance(button) : null;
                if (tip && typeof tip.setContent === 'function') {
                    tip.setContent({ '.tooltip-inner': text });
                }
            };
            document.addEventListener('click', function (event) {
                var button = event.target.closest('.calls-copy-id');
                if (!button) return;
                var id = button.getAttribute('data-call-id') || '';
                if (!id) return;
                var finish = function () {
                    var icon = button.querySelector('.material-icons');
                    button.classList.add('is-copied');
                    setTip(button, copiedLabel);
                    if (icon) icon.textContent = 'check';
                    setTimeout(function () {
                        button.classList.remove('is-copied');
                        setTip(button, copyLabel);
                        if (icon) icon.textContent = 'content_copy';
                    }, 1200);
                };
                var fallback = function () {
                    var input = document.createElement('textarea');
                    input.value = id;
                    input.setAttribute('readonly', '');
                    input.style.position = 'fixed';
                    input.style.left = '-9999px';
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    input.remove();
                    finish();
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(id).then(finish).catch(fallback);
                } else {
                    fallback();
                }
            });
        })();

    </script>
@endpush
