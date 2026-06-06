@extends('adminmodule::layouts.master')

@section('title', translate('Profile_Update_Requests'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Profile_Update_Requests') }}</h2>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                <ul class="nav nav--tabs">
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}"
                           href="{{ url()->current() }}?status=pending">
                            {{ translate('Pending') }}
                            <sup class="c2-bg py-1 px-2 radius-50 text-white-absolute">{{ $counts['pending'] }}</sup>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'denied' ? 'active' : '' }}"
                           href="{{ url()->current() }}?status=denied">
                            {{ translate('Denied') }}
                            <sup class="c2-bg py-1 px-2 radius-50 text-white-absolute">{{ $counts['denied'] }}</sup>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('Provider') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Requested_At') }}</th>
                                @can('onboarding_request_approve_or_deny')
                                    <th class="text-center">{{ translate('Action') }}</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($requests as $key => $row)
                                <tr>
                                    <td>{{ $requests->firstItem() + $key }}</td>
                                    <td>
                                        <a href="{{ route('admin.provider.profile_change_details', $row->id) }}">
                                            {{ $row->provider?->company_name }}
                                        </a>
                                    </td>
                                    <td>
                                        @php
                                            $typeKey = match ($row->change_type) {
                                                'branding' => 'Logo_and_Cover',
                                                'business_settings' => 'Business_Settings',
                                                'services' => 'Services',
                                                default => ucfirst(str_replace('_', ' ', $row->change_type)),
                                            };
                                        @endphp
                                        {{ translate($typeKey) }}
                                    </td>
                                    <td>{{ $row->created_at?->format('d M Y, h:i A') }}</td>
                                    @can('onboarding_request_approve_or_deny')
                                        <td class="text-center">
                                            <a href="{{ route('admin.provider.profile_change_details', $row->id) }}"
                                               class="btn btn--primary btn-sm">{{ translate('View_Details') }}</a>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">{{ translate('No_data_found') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{!! $requests->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
