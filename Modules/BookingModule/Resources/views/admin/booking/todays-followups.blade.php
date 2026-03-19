@extends('adminmodule::layouts.master')

@section('title', translate('Todays_pending_followups'))

@push('css_or_js')
    <style>
        .missed-followup-row,
        .missed-followup-row > td {
            background-color: #fff !important;
            color: #dc3545 !important;
        }
        .table-hover > tbody > tr.missed-followup-row:hover > * {
            background-color: #fff !important;
            color: #dc3545 !important;
        }
        .missed-followup-row a,
        .missed-followup-row a.text-primary,
        .missed-followup-row .text-primary,
        .missed-followup-row .small a {
            color: #dc3545 !important;
        }
        /* Keep primary action button text white even on missed rows. */
        .missed-followup-row a.btn,
        .missed-followup-row .btn,
        .missed-followup-row a.btn--primary,
        .missed-followup-row .btn--primary,
        .missed-followup-row a.btn.btn--primary {
            color: #fff !important;
        }
        .missed-followup-row a.btn:hover,
        .missed-followup-row .btn:hover,
        .missed-followup-row a.btn--primary:hover,
        .missed-followup-row .btn--primary:hover {
            color: #fff !important;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                            <span class="material-icons">arrow_back</span>
                        </a>
                        <h2 class="page-title mb-0">Booking Follow-ups- Pending Till Today's ({{ $totalFollowups ?? 0 }})</h2>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.booking.todays_followups') }}">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-3 col-sm-6">
                                        <label class="mb-2">{{ translate('From_Date') }}</label>
                                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <label class="mb-2">{{ translate('To_Date') }}</label>
                                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <label class="mb-2">{{ translate('Assignee') }}</label>
                                        <select name="assignee_id" class="form-select">
                                            <option value="">{{ translate('All') }}</option>
                                            @foreach($assignees ?? [] as $assignee)
                                                @php
                                                    $fullName = trim(($assignee->first_name ?? '') . ' ' . ($assignee->last_name ?? ''));
                                                    $label = $fullName ?: ($assignee->email ?? (string) $assignee->id);
                                                @endphp
                                                <option value="{{ $assignee->id }}" {{ (string) $assignee->id === (string) ($selectedAssigneeId ?? '') ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 d-flex gap-2">
                                        <button type="submit" class="btn btn--primary w-100">{{ translate('Filter') }}</button>
                                        <a href="{{ route('admin.booking.todays_followups') }}" class="btn btn--secondary w-100">{{ translate('Reset') }}</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-0">
                            @if($followups->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="text-secondary border-bottom">
                                            <tr>
                                                <th>{{ translate('SL') }}</th>
                                                <th>{{ translate('Booking_ID') }}</th>
                                                <th>{{ translate('Follow_up_for') }}</th>
                                                <th>{{ translate('Customer_Info') }}</th>
                                                <th>{{ translate('Provider_Info') }}</th>
                                                <th>{{ translate('Assignee') }}</th>
                                                <th>{{ translate('Date_Time') }}</th>
                                                <th class="text-end">{{ translate('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($followups as $key => $followup)
                                                <tr class="{{ $followup->date && !$followup->date->isToday() ? 'missed-followup-row' : '' }}">
                                                    <td>{{ $key + $followups->firstItem() }}</td>
                                                    <td>
                                                        @if($followup->booking)
                                                            <a href="{{ route('admin.booking.details', [$followup->booking_id, 'web_page' => 'followups']) }}" class="text-primary text-decoration-none">{{ $followup->booking->readable_id }}</a>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>{{ translate(ucfirst($followup->for)) }}</td>
                                                    <td>
                                                        @if($followup->booking && $followup->booking->customer)
                                                            <div>{{ trim(($followup->booking->customer->first_name ?? '') . ' ' . ($followup->booking->customer->last_name ?? '')) ?: '—' }}</div>
                                                            <div class="small"><a href="tel:{{ $followup->booking->customer->phone ?? '' }}">{{ $followup->booking->customer->phone ?? '—' }}</a></div>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($followup->booking && $followup->booking->provider)
                                                            <div>{{ $followup->booking->provider->company_name ?? '—' }}</div>
                                                            <div class="small"><a href="tel:{{ $followup->booking->provider->contact_person_phone ?? $followup->booking->provider->company_phone ?? '' }}">{{ $followup->booking->provider->contact_person_phone ?? $followup->booking->provider->company_phone ?? '—' }}</a></div>
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
                                                    <td class="text-end">
                                                        @if($followup->booking)
                                                            <a href="{{ route('admin.booking.details', [$followup->booking_id, 'web_page' => 'followups']) }}" class="btn btn-sm btn--primary">{{ translate('View') }}</a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-3 border-top">
                                    {{ $followups->links() }}
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-center p-5">
                                    <span class="opacity-50">{{ translate('No_follow_ups_yet') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
