@extends('adminmodule::layouts.master')

@section('title', translate('Todays_pending_followups'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                            <span class="material-icons">arrow_back</span>
                        </a>
                        <h2 class="page-title mb-0">{{ translate('Todays_pending_followups') }}</h2>
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
                                                <tr>
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
                                                    <td>{{ $followup->date ? $followup->date->format('d-M-Y h:ia') : '—' }}</td>
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
