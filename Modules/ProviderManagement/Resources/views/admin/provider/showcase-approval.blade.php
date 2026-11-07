@extends('adminmodule::layouts.master')

@section('title', translate('Work_Showcase_Approvals'))

@push('css_or_js')
    @include('providermanagement::admin.provider.partials.request-queue-styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Work_Showcase_Approvals') }}</h2>
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
                        <span class="title-color">{{ $items->total() }}</span>
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
                                <th class="text-nowrap">{{ translate('Media') }}</th>
                                <th class="text-nowrap">{{ translate('Title') }}</th>
                                <th class="text-nowrap">{{ translate('Submitted_on') }}</th>
                                @if($status === 'denied')
                                    <th class="text-nowrap">{{ translate('Denied_by') }}</th>
                                    <th class="text-nowrap">{{ translate('Denial_reason') }}</th>
                                @endif
                                @if($status === 'accepted')
                                    <th class="text-nowrap">{{ translate('Accepted_by') }}</th>
                                    <th class="text-nowrap">{{ translate('Accepted_on') }}</th>
                                @endif
                                @if($status === 'pending')
                                    @can('onboarding_request_approve_or_deny')
                                        <th class="text-center text-nowrap">{{ translate('Action') }}</th>
                                    @endcan
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($items as $key => $item)
                                <tr>
                                    <td>{{ $items->firstItem() + $key }}</td>
                                    <td>
                                        <a class="onboarding-provider"
                                           href="{{ route('admin.provider.details', [$item->provider_id, 'web_page' => 'overview']) }}"
                                           title="{{ $item->provider?->company_name }}">
                                            <span class="onboarding-provider__avatar">
                                                <img alt="{{ translate('provider-logo') }}"
                                                     src="{{ $item->provider?->list_avatar_full_path }}"
                                                     onerror="this.onerror=null;this.src='{{ asset('assets/provider-module/img/user2x.png') }}'">
                                            </span>
                                            <span class="onboarding-provider__name">{{ $item->provider?->company_name ?: '—' }}</span>
                                        </a>
                                    </td>
                                    <td>
                                        @if($item->media_type === 'video')
                                            <a href="{{ $item->media_full_path }}" target="_blank" rel="noopener">{{ translate('video') }}</a>
                                        @else
                                            <a href="{{ $item->media_full_path }}" target="_blank" rel="noopener">
                                                <img src="{{ $item->media_full_path }}" alt="" class="onboarding-media-thumb">
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="onboarding-clip" title="{{ $item->title }}">{{ $item->title ?: '—' }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        {{ $item->created_at ? $item->created_at->format('d M Y, h:i A') : '—' }}
                                    </td>
                                    @if($status === 'denied')
                                        <td class="text-nowrap">
                                            @php
                                                $deniedByName = trim(($item->deniedBy->first_name ?? '').' '.($item->deniedBy->last_name ?? ''));
                                            @endphp
                                            {{ $deniedByName !== '' ? $deniedByName : '—' }}
                                        </td>
                                        <td>
                                            <div class="onboarding-clip" title="{{ $item->denial_reason }}">
                                                {{ $item->denial_reason ?: '—' }}
                                            </div>
                                        </td>
                                    @endif
                                    @if($status === 'accepted')
                                        <td class="text-nowrap">
                                            @php
                                                $acceptedByName = trim(($item->approvedBy->first_name ?? '').' '.($item->approvedBy->last_name ?? ''));
                                            @endphp
                                            {{ $acceptedByName !== '' ? $acceptedByName : '—' }}
                                        </td>
                                        <td class="text-nowrap">
                                            {{ $item->approved_at ? $item->approved_at->format('d M Y, h:i A') : '—' }}
                                        </td>
                                    @endif
                                    @if($status === 'pending')
                                        @can('onboarding_request_approve_or_deny')
                                            <td>
                                                <div class="table-actions justify-content-center">
                                                    <button type="button" class="btn btn-soft--danger text-capitalize showcase_deny"
                                                            data-id="{{ $item->id }}">{{ translate('Deny') }}</button>
                                                    <button type="button" class="btn btn--success text-capitalize showcase_approve"
                                                            data-id="{{ $item->id }}">{{ translate('Accept') }}</button>
                                                </div>
                                            </td>
                                        @endcan
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    @php
                                        $columnCount = 5
                                            + ($status === 'denied' ? 2 : 0)
                                            + ($status === 'accepted' ? 2 : 0)
                                            + ($status === 'pending' && auth()->user()?->can('onboarding_request_approve_or_deny') ? 1 : 0);
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
                    <div class="d-flex justify-content-end">{!! $items->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        $('.showcase_deny').on('click', function () {
            let id = $(this).data('id');
            let route = '{{ route('admin.provider.showcase_approval_update', ['id' => ':id', 'status' => 'deny']) }}'.replace(':id', id);
            route_alert_reload_with_reason(
                route,
                @json(translate('Why_are_you_denying')),
                @json(translate('Why_are_you_denying')),
                @json(translate('Please_tell_us_why_you_are_denying'))
            );
        });
        $('.showcase_approve').on('click', function () {
            let id = $(this).data('id');
            let route = '{{ route('admin.provider.showcase_approval_update', ['id' => ':id', 'status' => 'approve']) }}'.replace(':id', id);
            route_alert_reload(route, '{{ translate('want_to_approve') }}', true);
        });
    </script>
@endpush
