@extends('adminmodule::layouts.master')

@section('title',translate('onboarding_requests'))

@push('css_or_js')
    <style>
        .onboarding-toolbar {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: .75rem 1rem;
            margin-bottom: .75rem;
            overflow-x: auto;
        }
        .onboarding-toolbar .nav--tabs {
            flex-wrap: nowrap;
            border: 0;
        }
        .onboarding-toolbar .nav--tabs .nav-link {
            padding: .35rem .65rem;
            white-space: nowrap;
        }
        .onboarding-toolbar__meta,
        .onboarding-toolbar__count {
            white-space: nowrap;
        }
        .onboarding-card .card-body {
            padding: .65rem .75rem;
        }
        .onboarding-table {
            font-size: .8125rem;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .onboarding-table > :not(caption) > * > * {
            padding: .3rem .45rem;
            vertical-align: middle;
        }
        .onboarding-card .onboarding-table > thead > tr > th {
            font-size: .9rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: .01em;
            white-space: nowrap;
            color: #1d2939;
            background-color: #e8edf5;
            background-clip: padding-box;
            padding: .6rem .55rem .55rem;
            border-bottom: 8px solid #fff;
            box-shadow: inset 0 -2px 0 #c5cedd;
        }
        .onboarding-table tbody td {
            white-space: nowrap;
        }
        .onboarding-table tbody tr:nth-child(even) > td {
            background: #f3f5fb;
        }
        .onboarding-table tbody tr:hover > td {
            background: #e8edf8;
        }
        .onboarding-table tbody td.onboarding-services-cell {
            white-space: normal;
        }
        .onboarding-provider {
            display: flex;
            align-items: center;
            gap: .4rem;
            min-width: 0;
            color: inherit;
            text-decoration: none;
        }
        .onboarding-provider__avatar {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: .3rem;
            overflow: hidden;
            flex: 0 0 auto;
            background: #e9ecef;
        }
        .onboarding-provider__avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .onboarding-provider__name {
            margin: 0;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .8125rem;
            font-weight: 600;
            line-height: 1.2;
        }
        .onboarding-clip {
            display: block;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .onboarding-services {
            max-width: 220px;
            font-size: .75rem;
            line-height: 1.25;
            white-space: normal;
            word-break: break-word;
        }
        .onboarding-services__group + .onboarding-services__group {
            margin-top: .15rem;
        }
        .onboarding-zone-missing {
            font-size: .7rem;
            white-space: nowrap;
        }
        .onboarding-accepted {
            display: flex;
            flex-direction: column;
            gap: .05rem;
            line-height: 1.2;
        }
        .onboarding-accepted__by {
            color: #667085;
            font-size: .7rem;
        }
        .onboarding-table .table-actions {
            flex-wrap: nowrap;
            gap: .3rem;
        }
        .onboarding-table .table-actions .btn {
            white-space: nowrap;
            padding: .12rem .4rem;
            font-size: .7rem;
            line-height: 1.3;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="onboarding-toolbar">
                <ul class="nav nav--tabs">
                    <li class="nav-item">
                        <a class="nav-link {{$status=='onboarding'?'active':''}}"
                           href="{{url()->current()}}?status=onboarding">
                            {{translate('Onboarding_Requests')}}
                            <sup class="c2-bg py-1 px-2 radius-50 text-white-absolute">{{$providersCount['onboarding']}}</sup>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$status=='denied'?'active':''}}"
                           href="{{url()->current()}}?status=denied">
                            {{translate('Denied_Requests')}}
                            <sup class="c2-bg py-1 px-2 radius-50 text-white-absolute">{{$providersCount['denied']}}</sup>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$status=='accepted'?'active':''}}"
                           href="{{url()->current()}}?status=accepted">
                            {{translate('Accepted_Requests')}}
                            <sup class="c2-bg py-1 px-2 radius-50 text-white-absolute">{{$providersCount['accepted']}}</sup>
                        </a>
                    </li>
                </ul>
                <div class="onboarding-toolbar__meta ms-auto d-flex align-items-center gap-3">
                    <div class="onboarding-toolbar__count fw-medium">
                        <span class="opacity-75">{{translate('Total_Requests')}}:</span>
                        <span class="title-color">{{$providers->total()}}</span>
                    </div>
                    @can('onboarding_request_export')
                        <a href="{{ route('admin.provider.onboarding.export', array_filter([
                                'step' => 1,
                                'request_status' => $status,
                                'search' => $search ?: null,
                            ], fn ($value) => $value !== null && $value !== '')) }}"
                           class="btn btn--secondary text-nowrap d-inline-flex align-items-center gap-1"
                           data-turbo="false">
                            <span class="material-icons" style="font-size:18px">file_download</span>
                            {{ translate('Export') }}
                        </a>
                    @endcan
                    <form action="{{url()->current()}}" class="d-flex align-items-center gap-0 border rounded" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="{{$status}}">
                        <input type="search" class="theme-input-style border-0 rounded block-size-36" name="search" value="{{$search??''}}" placeholder="{{translate('search_here')}}">
                        <button type="submit" class="bg-light border-0 px-2 block-size-36 rounded-end d-flex align-items-center justify-content-center">
                            <span class="material-symbols-outlined fz-20 opacity-75">search</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="card onboarding-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example" class="table table-sm align-middle onboarding-table">
                            <thead>
                            <tr>
                                <th class="text-nowrap">{{translate('SL')}}</th>
                                <th class="text-nowrap">{{translate('Provider')}}</th>
                                <th class="text-nowrap">{{translate('Phone_number')}}</th>
                                <th class="text-nowrap">{{translate('Provider_type')}}</th>
                                <th class="text-nowrap">{{translate('Zone')}}</th>
                                <th class="text-nowrap">{{translate('Filled_on')}}</th>
                                <th class="text-nowrap">{{translate('Address')}}</th>
                                <th class="text-nowrap">{{translate('Services')}}</th>
                                @if($status === 'denied')
                                    <th class="text-nowrap">{{translate('Denied_by')}}</th>
                                    <th class="text-nowrap">{{translate('Denial_reason')}}</th>
                                @endif
                                @if($status === 'accepted')
                                    <th class="text-nowrap">{{translate('Accepted')}}</th>
                                @endif
                                @can('onboarding_request_approve_or_deny')
                                    <th class="text-center text-nowrap">{{translate('Action')}}</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($providers as $key=>$provider)
                                <tr>
                                    <td>{{$providers->firstitem()+$key}}</td>
                                    <td>
                                        <a class="onboarding-provider"
                                           href="{{route('admin.provider.onboarding_details',[$provider->id])}}"
                                           title="{{ $provider->company_name }}">
                                            <span class="onboarding-provider__avatar">
                                                <img alt="{{ translate('provider-logo') }}"
                                                     src="{{ $provider->list_avatar_full_path }}"
                                                     onerror="this.onerror=null;this.src='{{ asset('assets/provider-module/img/user2x.png') }}'">
                                            </span>
                                            <span class="onboarding-provider__name">{{ $provider->company_name }}</span>
                                        </a>
                                    </td>
                                    <td>
                                        @if($provider->contact_person_phone)
                                            <a href="tel:{{$provider->contact_person_phone}}">{{$provider->contact_person_phone}}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        {{ ($provider->provider_type ?? 'individual') === 'company' ? translate('Company') : translate('Individual') }}
                                    </td>
                                    <td>
                                        @if($provider->zone)
                                            {{$provider->zone->name}}
                                        @else
                                            <span class="badge badge-danger opacity-50 onboarding-zone-missing">{{translate('Zone is not available')}}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        {{ $provider->created_at ? date('d M Y, h:i A', strtotime($provider->created_at)) : '—' }}
                                    </td>
                                    <td>
                                        <div class="onboarding-clip" title="{{ $provider->company_address }}">
                                            {{ $provider->company_address ?: '—' }}
                                        </div>
                                    </td>
                                    <td class="onboarding-services-cell">
                                        @php
                                            $serviceGroups = ($provider->subscribed_services ?? collect())
                                                ->groupBy(fn ($row) => (string) ($row->category->name ?? ''))
                                                ->map(function ($rows, $category) {
                                                    return [
                                                        'category' => trim((string) $category),
                                                        'subs' => $rows
                                                            ->map(fn ($row) => trim((string) ($row->sub_category->name ?? '')))
                                                            ->filter()
                                                            ->unique()
                                                            ->values(),
                                                    ];
                                                })
                                                ->filter(fn ($group) => $group['category'] !== '' || $group['subs']->isNotEmpty())
                                                ->values();
                                        @endphp
                                        @if($serviceGroups->isEmpty())
                                            —
                                        @else
                                            <div class="onboarding-services">
                                                @foreach($serviceGroups as $group)
                                                    <div class="onboarding-services__group">
                                                        @if($group['category'] !== '')
                                                            <span class="fw-semibold d-block">{{ $group['category'] }}</span>
                                                        @endif
                                                        @if($group['subs']->isNotEmpty())
                                                            <span class="d-block fz-12 opacity-75">{{ $group['subs']->implode(', ') }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    @if($status === 'denied')
                                        <td class="text-nowrap">
                                            @php
                                                $deniedByName = trim(($provider->deniedBy->first_name ?? '').' '.($provider->deniedBy->last_name ?? ''));
                                            @endphp
                                            {{ $deniedByName !== '' ? $deniedByName : '—' }}
                                        </td>
                                        <td>
                                            <div class="onboarding-clip" title="{{ $provider->denial_reason }}">
                                                {{ $provider->denial_reason ?: '—' }}
                                            </div>
                                        </td>
                                    @endif
                                    @if($status === 'accepted')
                                        <td>
                                            @php
                                                $acceptedByName = trim(($provider->approvedBy->first_name ?? '').' '.($provider->approvedBy->last_name ?? ''));
                                            @endphp
                                            <div class="onboarding-accepted">
                                                <span>{{ $provider->approved_at ? $provider->approved_at->format('d M Y, h:i A') : '—' }}</span>
                                                <span class="onboarding-accepted__by">{{ $acceptedByName !== '' ? $acceptedByName : '—' }}</span>
                                            </div>
                                        </td>
                                    @endif
                                    @can('onboarding_request_approve_or_deny')
                                        <td>
                                            <div class="table-actions justify-content-center">
                                                @if((int) $provider->is_approved === 2)
                                                    <a type="button"
                                                       class="btn btn-soft--danger text-capitalize provider_approval"
                                                       id="button-deny-{{$provider->id}}"
                                                       data-approve="{{$provider->id}}"
                                                       data-status="deny">
                                                        {{translate('Deny')}}
                                                    </a>
                                                @endif
                                                @if(in_array((int) $provider->is_approved, [0, 1], true))
                                                    <a type="button"
                                                       class="btn btn--secondary text-capitalize provider_move_pending"
                                                       id="button-pending-{{$provider->id}}"
                                                       data-approve="{{$provider->id}}">
                                                        {{translate('Move_to_pending')}}
                                                    </a>
                                                @endif
                                                @if($status !== 'denied' && (int) $provider->is_approved !== 1)
                                                    <a type="button"
                                                       class="btn btn--success text-capitalize approval_provider"
                                                       id="button-{{$provider->id}}" data-approve="{{$provider->id}}"
                                                       data-approve="approve">
                                                        {{translate('Accept')}}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    @php
                                        $onboardingColumnCount = 8
                                            + (auth()->user()?->can('onboarding_request_approve_or_deny') ? 1 : 0)
                                            + ($status === 'denied' ? 2 : 0)
                                            + ($status === 'accepted' ? 1 : 0);
                                    @endphp
                                    <td colspan="{{ $onboardingColumnCount }}">
                                        <div class="review-empty-state py-5">
                                            <div class="d-flex flex-column align-items-center justify-content-center py-5 gap-2 my-5">
                                                <img src="{{asset('assets/admin-module/img/onbording-request-empty.svg')}}" alt="No data">
                                                <h5 class="m-0 text-muted opacity-50">{{translate('No Request Found')}}</h5>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {!! $providers->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";

        $('.provider_approval').on('click', function () {
            let itemId = $(this).data('approve');
            let route = '{{ route('admin.provider.update-approval', ['id' => ':itemId', 'status' => 'deny']) }}';
            route = route.replace(':itemId', itemId);
            route_alert_reload_with_reason(
                route,
                @json(translate('Why_are_you_denying')),
                @json(translate('Why_are_you_denying')),
                @json(translate('Please_tell_us_why_you_are_denying'))
            );
        });

        $('.approval_provider').on('click', function () {
            let itemId = $(this).data('approve');
            let route = '{{ route('admin.provider.update-approval', ['id' => ':itemId', 'status' => 'approve']) }}';
            route = route.replace(':itemId', itemId);
            route_alert_reload(route, '{{ translate('want_to_approve_the_provider') }}', true);
        });

        $('.provider_move_pending').on('click', function () {
            let itemId = $(this).data('approve');
            let route = '{{ route('admin.provider.update-approval', ['id' => ':itemId', 'status' => 'pending']) }}';
            route = route.replace(':itemId', itemId);
            route_alert_reload(route, '{{ translate('want_to_move_the_provider_back_to_pending') }}', true);
        });

    </script>

@endpush
