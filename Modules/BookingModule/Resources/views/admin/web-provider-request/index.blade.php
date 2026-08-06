@extends('adminmodule::layouts.new-master')

@section('title', translate('Web_Provider_Requests'))

@section('content')
    <style>
        .wpr-table-card .card-body { padding: 1rem 1.25rem !important; }
        .wpr-compact-table { font-size: 0.8125rem; margin-bottom: 0; }
        .wpr-compact-table thead th {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.45rem 0.5rem;
            white-space: nowrap;
        }
        .wpr-compact-table tbody td {
            padding: 0.4rem 0.5rem;
            vertical-align: middle;
        }
        .wpr-compact-table .badge {
            font-size: 0.6875rem;
            padding: 0.2rem 0.45rem;
        }
        .wpr-action-btns {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }
        .wpr-action-btns .btn {
            font-size: 0.6875rem;
            line-height: 1.2;
            padding: 0.2rem 0.45rem;
        }
        .wpr-status-badges {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }
    </style>
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('Web_Provider_Requests') }}</h2>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body py-2 px-3">
                            <form method="GET" action="{{ route('admin.booking.web-provider-requests.index') }}">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-8">
                                        <input type="text"
                                               name="search"
                                               class="form-control form-control-sm"
                                               value="{{ $search ?? '' }}"
                                               placeholder="{{ translate('Search_by_name_phone_service_or_reference') }}">
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-md-end gap-2">
                                        <button class="btn btn--primary btn-sm" type="submit">
                                            {{ translate('Search') }}
                                        </button>
                                        <a class="btn btn--secondary btn-sm" href="{{ route('admin.booking.web-provider-requests.index') }}">
                                            {{ translate('Reset') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card wpr-table-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle wpr-compact-table">
                                    <thead class="table-light">
                                    <tr>
                                        <th>{{ translate('SL') }}</th>
                                        <th>{{ translate('Reference') }}</th>
                                        <th>{{ translate('Lead_ID') }}</th>
                                        <th>{{ translate('Provider_Name') }}</th>
                                        <th>{{ translate('Phone_Number') }}</th>
                                        <th>{{ translate('Service') }}</th>
                                        <th>{{ translate('Area') }}</th>
                                        <th>{{ translate('Lead_Status') }}</th>
                                        <th>{{ translate('Date_Time') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($requests as $key => $providerRequest)
                                        @php
                                            $leadMeta = $providerRequest->lead ? ($leadDisplayData[$providerRequest->lead->id] ?? null) : null;
                                        @endphp
                                        <tr>
                                            <td class="text-muted">{{ $requests->firstItem() + $key }}</td>
                                            <td class="text-nowrap">
                                                <a href="{{ route('admin.booking.web-provider-requests.show', $providerRequest->id) }}" class="link-primary">
                                                    {{ $providerRequest->reference_id }}
                                                </a>
                                            </td>
                                            <td class="text-nowrap">
                                                @if($providerRequest->lead)
                                                    <a href="{{ route('admin.lead.show', $providerRequest->lead->id) }}" class="link-primary fw-semibold">
                                                        #{{ $providerRequest->lead->id }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $providerRequest->name }}</td>
                                            <td class="text-nowrap">{{ $providerRequest->phone }}</td>
                                            <td>{{ $providerRequest->service_category ?: '—' }}</td>
                                            <td>{{ $providerRequest->area ?: '—' }}</td>
                                            <td>
                                                @if($providerRequest->lead && $leadMeta)
                                                    <div class="wpr-status-badges">
                                                        <span class="badge" style="background-color: {{ $leadMeta['status_color'] }}; color: #fff;">
                                                            {{ $leadMeta['status_name'] }}
                                                        </span>
                                                        <span class="badge rounded-pill {{ $leadMeta['open_badge_class'] }}">
                                                            {{ $leadMeta['open_label'] }}
                                                        </span>
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-nowrap">{{ $providerRequest->created_at?->format('d M Y h:i a') }}</td>
                                            <td>
                                                <div class="wpr-action-btns">
                                                    <a href="{{ route('admin.booking.web-provider-requests.show', $providerRequest->id) }}" class="btn btn--secondary">
                                                        {{ translate('View') }}
                                                    </a>
                                                    @if($providerRequest->lead)
                                                        <a href="{{ route('admin.lead.show', $providerRequest->lead->id) }}" class="btn btn--primary">
                                                            {{ translate('View_Lead') }}
                                                        </a>
                                                    @endif
                                                    @can('booking_delete')
                                                        <button type="button"
                                                                class="btn btn-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#wprDeleteModal"
                                                                data-wpr-delete-url="{{ route('admin.booking.web-provider-requests.destroy', $providerRequest->id) }}"
                                                                data-wpr-delete-label="{{ $providerRequest->reference_id }} — {{ $providerRequest->name }}">
                                                            {{ translate('Delete') }}
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-3">{{ translate('No_data_available') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                                {!! $requests->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('bookingmodule::admin.web-provider-request.partials._delete-modal')
@endsection
