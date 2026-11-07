@extends('adminmodule::layouts.master')

@section('title', translate('Profile_Update_Requests'))

@push('css_or_js')
    @include('providermanagement::admin.provider.partials.request-queue-styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Profile_Update_Requests') }}</h2>
            </div>

            <div class="onboarding-toolbar">
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
                            {{ translate('Denied_Requests') }}
                            <sup class="c2-bg py-1 px-2 radius-50 text-white-absolute">{{ $counts['denied'] }}</sup>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $status === 'accepted' ? 'active' : '' }}"
                           href="{{ url()->current() }}?status=accepted">
                            {{ translate('Accepted_Requests') }}
                            <sup class="c2-bg py-1 px-2 radius-50 text-white-absolute">{{ $counts['accepted'] }}</sup>
                        </a>
                    </li>
                </ul>
                <div class="onboarding-toolbar__meta ms-auto d-flex align-items-center gap-3">
                    <div class="onboarding-toolbar__count fw-medium">
                        <span class="opacity-75">{{ translate('Total_Requests') }}:</span>
                        <span class="title-color">{{ $requests->total() }}</span>
                    </div>
                    <form action="{{ url()->current() }}" class="d-flex align-items-center gap-0 border rounded" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="search" class="theme-input-style border-0 rounded block-size-36" name="search" value="{{ $search ?? '' }}" placeholder="{{ translate('search_here') }}">
                        <button type="submit" class="bg-light border-0 px-2 block-size-36 rounded-end d-flex align-items-center justify-content-center">
                            <span class="material-symbols-outlined fz-20 opacity-75">search</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="card onboarding-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle onboarding-table">
                            <thead>
                            <tr>
                                <th class="text-nowrap">{{ translate('SL') }}</th>
                                <th class="text-nowrap">{{ translate('Provider') }}</th>
                                <th class="text-nowrap">{{ translate('Type') }}</th>
                                <th class="text-nowrap">{{ translate('Requested_At') }}</th>
                                @if($status === 'denied')
                                    <th class="text-nowrap">{{ translate('Denied_by') }}</th>
                                    <th class="text-nowrap">{{ translate('Denial_reason') }}</th>
                                @endif
                                @if($status === 'accepted')
                                    <th class="text-nowrap">{{ translate('Accepted_by') }}</th>
                                    <th class="text-nowrap">{{ translate('Accepted_on') }}</th>
                                @endif
                                @can('onboarding_request_approve_or_deny')
                                    <th class="text-center text-nowrap">{{ translate('Action') }}</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($requests as $key => $row)
                                @php
                                    $typeKey = match ($row->change_type) {
                                        'branding' => 'Logo_and_Cover',
                                        'business_settings' => 'Business_Settings',
                                        'services' => 'Services',
                                        default => ucfirst(str_replace('_', ' ', $row->change_type)),
                                    };
                                    $reviewerName = trim(($row->reviewer->first_name ?? '').' '.($row->reviewer->last_name ?? ''));
                                    $denialReason = $row->denial_reason ?: $row->admin_note;
                                @endphp
                                <tr>
                                    <td>{{ $requests->firstItem() + $key }}</td>
                                    <td>
                                        <a class="onboarding-provider"
                                           href="{{ route('admin.provider.profile_change_details', $row->id) }}"
                                           title="{{ $row->provider?->company_name }}">
                                            <span class="onboarding-provider__avatar">
                                                <img alt="{{ translate('provider-logo') }}"
                                                     src="{{ $row->provider?->list_avatar_full_path }}"
                                                     onerror="this.onerror=null;this.src='{{ asset('assets/provider-module/img/user2x.png') }}'">
                                            </span>
                                            <span class="onboarding-provider__name">{{ $row->provider?->company_name ?: '—' }}</span>
                                        </a>
                                    </td>
                                    <td>{{ translate($typeKey) }}</td>
                                    <td class="text-nowrap">{{ $row->created_at?->format('d M Y, h:i A') ?: '—' }}</td>
                                    @if($status === 'denied')
                                        <td class="text-nowrap">{{ $reviewerName !== '' ? $reviewerName : '—' }}</td>
                                        <td>
                                            <div class="onboarding-clip" title="{{ $denialReason }}">
                                                {{ $denialReason ?: '—' }}
                                            </div>
                                        </td>
                                    @endif
                                    @if($status === 'accepted')
                                        <td class="text-nowrap">{{ $reviewerName !== '' ? $reviewerName : '—' }}</td>
                                        <td class="text-nowrap">{{ $row->reviewed_at ? $row->reviewed_at->format('d M Y, h:i A') : '—' }}</td>
                                    @endif
                                    @can('onboarding_request_approve_or_deny')
                                        <td class="text-center">
                                            <a href="{{ route('admin.provider.profile_change_details', $row->id) }}"
                                               class="btn btn--primary btn-sm">{{ translate('View_Details') }}</a>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    @php
                                        $columnCount = 4
                                            + ($status === 'denied' ? 2 : 0)
                                            + ($status === 'accepted' ? 2 : 0)
                                            + (auth()->user()?->can('onboarding_request_approve_or_deny') ? 1 : 0);
                                    @endphp
                                    <td colspan="{{ $columnCount }}">
                                        <div class="review-empty-state py-5">
                                            <div class="d-flex flex-column align-items-center justify-content-center py-5 gap-2 my-5">
                                                <img src="{{ asset('assets/admin-module/img/onbording-request-empty.svg') }}" alt="{{ translate('No_data_found') }}">
                                                <h5 class="m-0 text-muted opacity-50">{{ translate('No Request Found') }}</h5>
                                            </div>
                                        </div>
                                    </td>
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
