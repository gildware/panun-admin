@extends('adminmodule::layouts.master')

@section('title', translate('Work_Showcase_Approvals'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Work_Showcase_Approvals') }}</h2>
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
                                <th>{{ translate('Media') }}</th>
                                <th>{{ translate('Title') }}</th>
                                @can('onboarding_request_approve_or_deny')
                                    <th class="text-center">{{ translate('Action') }}</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($items as $key => $item)
                                <tr>
                                    <td>{{ $items->firstItem() + $key }}</td>
                                    <td>
                                        <a href="{{ route('admin.provider.details', [$item->provider_id, 'web_page' => 'overview']) }}">
                                            {{ $item->provider?->company_name }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($item->media_type === 'video')
                                            <a href="{{ $item->media_full_path }}" target="_blank" rel="noopener">{{ translate('video') }}</a>
                                        @else
                                            <a href="{{ $item->media_full_path }}" target="_blank" rel="noopener">
                                                <img src="{{ $item->media_full_path }}" alt="" class="ob-doc-thumb" style="max-height:48px">
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{ $item->title ?: '-' }}</td>
                                    @can('onboarding_request_approve_or_deny')
                                        <td>
                                            @if($status === 'pending')
                                                <div class="table-actions justify-content-center gap-2">
                                                    <button type="button" class="btn btn-soft--danger showcase_deny"
                                                            data-id="{{ $item->id }}">{{ translate('Deny') }}</button>
                                                    <button type="button" class="btn btn--success showcase_approve"
                                                            data-id="{{ $item->id }}">{{ translate('Accept') }}</button>
                                                </div>
                                            @endif
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
            route_alert_reload(route, '{{ translate('want_to_deny') }}', true);
        });
        $('.showcase_approve').on('click', function () {
            let id = $(this).data('id');
            let route = '{{ route('admin.provider.showcase_approval_update', ['id' => ':id', 'status' => 'approve']) }}'.replace(':id', id);
            route_alert_reload(route, '{{ translate('want_to_approve') }}', true);
        });
    </script>
@endpush
