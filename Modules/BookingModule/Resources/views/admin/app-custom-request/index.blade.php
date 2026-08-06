@extends('adminmodule::layouts.new-master')

@section('title', translate('App_Custom_Requests'))

@section('content')
    <style>
        .acr-table-card .card-body { padding: 1rem 1.25rem !important; }
        .acr-compact-table { font-size: 0.8125rem; margin-bottom: 0; }
        .acr-compact-table thead th {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.45rem 0.5rem;
            white-space: nowrap;
        }
        .acr-compact-table tbody td {
            padding: 0.4rem 0.5rem;
            vertical-align: middle;
        }
        .acr-compact-table .badge {
            font-size: 0.6875rem;
            padding: 0.2rem 0.45rem;
        }
        .acr-action-btns {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }
        .acr-action-btns .btn {
            font-size: 0.6875rem;
            line-height: 1.2;
            padding: 0.2rem 0.45rem;
        }
    </style>
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('App_Custom_Requests') }}</h2>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body py-2 px-3">
                            <form method="GET" action="{{ route('admin.booking.app-custom-requests.index') }}">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5">
                                        <input type="text"
                                               name="search"
                                               class="form-control form-control-sm"
                                               value="{{ $search ?? '' }}"
                                               placeholder="{{ translate('Search_by_name_phone_category_or_reference') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <select name="status" class="form-control form-control-sm">
                                            <option value="">{{ translate('All_Status') }}</option>
                                            @foreach(\Modules\BookingModule\Entities\AppCustomRequest::statusOptions() as $value => $label)
                                                <option value="{{ $value }}" @selected(($status ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-md-end gap-2">
                                        <button class="btn btn--primary btn-sm" type="submit">
                                            {{ translate('Search') }}
                                        </button>
                                        <a class="btn btn--secondary btn-sm" href="{{ route('admin.booking.app-custom-requests.index') }}">
                                            {{ translate('Reset') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card acr-table-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle acr-compact-table">
                                    <thead class="table-light">
                                    <tr>
                                        <th>{{ translate('SL') }}</th>
                                        <th>{{ translate('Reference') }}</th>
                                        <th>{{ translate('Lead_ID') }}</th>
                                        <th>{{ translate('Customer_Name') }}</th>
                                        <th>{{ translate('Phone_Number') }}</th>
                                        <th>{{ translate('Category') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Date_Time') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($requests as $key => $customRequest)
                                        <tr>
                                            <td class="text-muted">{{ $requests->firstItem() + $key }}</td>
                                            <td class="text-nowrap">
                                                <a href="{{ route('admin.booking.app-custom-requests.show', $customRequest->id) }}" class="link-primary">
                                                    {{ $customRequest->reference_id }}
                                                </a>
                                            </td>
                                            <td class="text-nowrap">
                                                @if($customRequest->lead)
                                                    <a href="{{ route('admin.lead.show', $customRequest->lead->id) }}" class="link-primary fw-semibold">
                                                        #{{ $customRequest->lead->id }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $customRequest->name }}</td>
                                            <td class="text-nowrap">{{ $customRequest->phone }}</td>
                                            <td>{{ $customRequest->category_name ?: '—' }}</td>
                                            <td>
                                                @php
                                                    $statusClass = match($customRequest->status) {
                                                        'accepted' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        default => 'bg-warning text-dark',
                                                    };
                                                @endphp
                                                <span class="badge {{ $statusClass }} text-capitalize">
                                                    {{ $customRequest->status }}
                                                </span>
                                            </td>
                                            <td class="text-nowrap">{{ $customRequest->created_at?->format('d M Y h:i a') }}</td>
                                            <td>
                                                <div class="acr-action-btns">
                                                    <a href="{{ route('admin.booking.app-custom-requests.show', $customRequest->id) }}" class="btn btn--secondary">
                                                        {{ translate('View') }}
                                                    </a>
                                                    @if($customRequest->lead)
                                                        <a href="{{ route('admin.lead.show', $customRequest->lead->id) }}" class="btn btn--primary">
                                                            {{ translate('View_Lead') }}
                                                        </a>
                                                    @endif
                                                    @can('booking_delete')
                                                        <button type="button"
                                                                class="btn btn-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#acrDeleteModal"
                                                                data-acr-delete-url="{{ route('admin.booking.app-custom-requests.destroy', $customRequest->id) }}"
                                                                data-acr-delete-label="{{ $customRequest->reference_id }} — {{ $customRequest->name }}">
                                                            {{ translate('Delete') }}
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-3">{{ translate('No_data_available') }}</td>
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

    @include('bookingmodule::admin.app-custom-request.partials._delete-modal')
@endsection
