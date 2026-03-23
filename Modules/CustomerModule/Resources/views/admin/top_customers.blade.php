@extends('adminmodule::layouts.master')

@section('title', 'Top Customers')

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-30">
                <h2 class="page-title">Top Customers</h2>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th style="width: 80px;">{{translate('Sl')}}</th>
                                <th>{{translate('Customer')}}</th>
                                <th style="width: 140px;" class="text-end">{{translate('Bookings')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($customers as $key => $customer)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        <div class="media align-items-center gap-3">
                                            <div class="avatar avatar-lg">
                                                <a href="{{route('admin.customer.detail', [$customer->id, 'web_page' => 'overview'])}}">
                                                    <img class="avatar-img radius-5"
                                                         src="{{ $customer->profile_image_full_path }}"
                                                         alt="{{ $customer->first_name ?? 'Customer' }}">
                                                </a>
                                            </div>
                                            <div class="media-body">
                                                <h5 class="mb-1">
                                                    <a href="{{route('admin.customer.detail', [$customer->id, 'web_page' => 'overview'])}}">
                                                        {{ trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: '—' }}
                                                    </a>
                                                </h5>
                                                <div class="fz-12 opacity-75">
                                                    {{ $customer->phone ?? '' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-medium">{{ $customer->completed_bookings_count }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center opacity-75">
                                        No Record Found
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="fs-12 opacity-75 mt-3">
                        Showing top 20 customers with at least 1 completed booking.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

