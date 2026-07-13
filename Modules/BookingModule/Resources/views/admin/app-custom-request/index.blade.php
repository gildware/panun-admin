@extends('adminmodule::layouts.new-master')

@section('title', translate('App_Custom_Requests'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('App_Custom_Requests') }}</h2>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.booking.app-custom-requests.index') }}">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-8">
                                        <input type="text"
                                               name="search"
                                               class="form-control"
                                               value="{{ $search ?? '' }}"
                                               placeholder="{{ translate('Search_by_name_phone_category_or_reference') }}">
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-md-end gap-2">
                                        <button class="btn btn--primary" type="submit">
                                            {{ translate('Search') }}
                                        </button>
                                        <a class="btn btn--secondary" href="{{ route('admin.booking.app-custom-requests.index') }}">
                                            {{ translate('Reset') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{ translate('SL') }}</th>
                                        <th>{{ translate('Reference') }}</th>
                                        <th>{{ translate('Customer_Name') }}</th>
                                        <th>{{ translate('Phone_Number') }}</th>
                                        <th>{{ translate('Category') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Lead') }}</th>
                                        <th>{{ translate('Date_Time') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($requests as $key => $customRequest)
                                        <tr>
                                            <td>{{ $requests->firstItem() + $key }}</td>
                                            <td>
                                                <a href="{{ route('admin.booking.app-custom-requests.show', $customRequest->id) }}" class="link-primary">
                                                    {{ $customRequest->reference_id }}
                                                </a>
                                            </td>
                                            <td>{{ $customRequest->name }}</td>
                                            <td>{{ $customRequest->phone }}</td>
                                            <td>{{ $customRequest->category_name ?: '—' }}</td>
                                            <td>
                                                <span class="badge bg-info text-capitalize">
                                                    {{ str_replace('_', ' ', strtolower($customRequest->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($customRequest->lead)
                                                    <a href="{{ route('admin.lead.show', $customRequest->lead->id) }}?in_modal=1" class="link-primary">
                                                        #{{ $customRequest->lead->id }} — {{ $customRequest->lead->name ?: '—' }}
                                                    </a>
                                                    @if($customRequest->lead->source)
                                                        <div class="text-muted small">{{ $customRequest->lead->source->name }}</div>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $customRequest->created_at?->format('d M Y h:i a') }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a href="{{ route('admin.booking.app-custom-requests.show', $customRequest->id) }}" class="btn btn-sm btn--secondary">
                                                        {{ translate('View') }}
                                                    </a>
                                                    @if($customRequest->lead)
                                                        <a href="{{ route('admin.booking.create-from-lead', $customRequest->lead->id) }}" class="btn btn-sm btn--primary">
                                                            {{ translate('Create_Booking') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">{{ translate('No_data_available') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end">
                                {!! $requests->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
