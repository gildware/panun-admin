@extends('adminmodule::layouts.master')

@section('title',translate('provider_list'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-30">
                <h2 class="page-title">{{translate('Provider_List')}}</h2>
            </div>

            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="row mb-4 g-4">
                        <div class="col-lg-3 col-sm-6">
                            <div class="statistics-card statistics-card__total_provider">
                                <h2>{{$topCards['total_providers']}}</h2>
                                <h3>{{translate('Total_Providers')}}</h3>
                                <img src="{{asset('assets/admin-module/img/icons/subscribed-providers.png')}}"
                                     class="absolute-img" alt="{{ translate('providers') }}">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <div class="statistics-card statistics-card__ongoing">
                                <h2>{{$topCards['total_onboarding_requests']}}</h2>
                                <h3>{{translate('Onboarding_Request')}}</h3>
                                <img src="{{asset('assets/admin-module/img/icons/onboarding-request.png')}}"
                                     class="absolute-img" alt="{{ translate('providers') }}">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <div class="statistics-card statistics-card__newly_joined">
                                <h2>{{$topCards['total_active_providers']}}</h2>
                                <h3>{{translate('Active_Providers')}}</h3>
                                <img src="{{asset('assets/admin-module/img/icons/newly-joined.png')}}"
                                     class="absolute-img" alt="{{ translate('providers') }}">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <div class="statistics-card statistics-card__not_served">
                                <h2>{{$topCards['total_inactive_providers']}}</h2>
                                <h3>{{translate('Inactive_Providers')}}</h3>
                                <img src="{{asset('assets/admin-module/img/icons/not-served.png')}}"
                                     class="absolute-img" alt="{{ translate('providers') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                <ul class="nav nav--tabs">
                    <li class="nav-item">
                        <a class="nav-link {{$status=='all'?'active':''}}"
                           href="{{url()->current()}}?status=all">
                            {{translate('all')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$status=='active'?'active':''}}"
                           href="{{url()->current()}}?status=active">
                            {{translate('active')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{$status=='inactive'?'active':''}}"
                           href="{{url()->current()}}?status=inactive">
                            {{translate('inactive')}}
                        </a>
                    </li>
                </ul>

                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{($performanceFilter ?? 'all')=='all'?'active':''}}"
                           href="{{url()->current()}}?status={{$status}}&performance_filter=all">
                            {{translate('all')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{($performanceFilter ?? 'all')=='warning'?'active':''}}"
                           href="{{url()->current()}}?status={{$status}}&performance_filter=warning">
                            {{translate('warning')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{($performanceFilter ?? 'all')=='blacklisted'?'active':''}}"
                           href="{{url()->current()}}?status={{$status}}&performance_filter=blacklisted">
                            {{translate('blacklisted')}}
                        </a>
                    </li>
                </ul>

                <div class="d-flex gap-2 fw-medium">
                    <span class="opacity-75">{{translate('Total_Providers')}}:</span>
                    <span class="title-color">{{$providers->total()}}</span>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all-tab-pane">
                    <div class="card">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <h4 class="m-0">Provider List</h4>

                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <form action="{{url()->current()}}?status={{$status}}&performance_filter={{$performanceFilter ?? 'all'}}" class="d-flex align-items-center gap-0 border rounded" method="POST">
                                        @csrf
                                        <input type="search" class="theme-input-style border-0 rounded block-size-36" name="search" value="{{$search}}" placeholder="{{translate('search_here')}}">
                                        <button type="submit" class="bg-light border-0 px-2 block-size-36 rounded-end d-flex align-items-center justify-content-center">
                                            <span class="material-symbols-outlined fz-20 opacity-75">
                                                search
                                            </span>
                                        </button>
                                    </form>
                                    @can('provider_export')
                                        <div class="dropdown">
                                            <button type="button"
                                                    class="btn rounded btn--secondary text-capitalize dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                <span
                                                    class="material-icons">file_download</span> {{translate('download')}}
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                                <a class="dropdown-item"
                                                   href="{{route('admin.provider.download')}}?search={{$search}}">
                                                    {{translate('excel')}}
                                                </a>
                                            </ul>
                                        </div>
                                    @endcan

                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="example" class="table align-middle">
                                    <thead class="align-middle">
                                    <tr>
                                        <th>{{translate('Sl')}}</th>
                                        <th>{{translate('Provider')}}</th>
                                        <th class="min-w-120">{{translate('Contact_Info')}}</th>
                                        <th class="min-w-120">{{translate('Total_Subscribed_Sub_Categories')}}</th>
                                        <th class="min-w-120">{{translate('Total_Booking_Served')}}</th>
                                        <th class="min-w-120">{{translate('Performance_Score')}}</th>
                                        <th class="min-w-120">{{translate('Performance_Status')}}</th>
                                        <th class="min-w-120">{{translate('Complaint_%')}}</th>
                                        <th class="min-w-120">{{translate('No_show_%')}}</th>
                                        @can('provider_manage_status')
                                            <th>{{translate('Service Availability')}}</th>
                                            <th>{{translate('Status')}}</th>
                                        @endcan
                                        @can('provider_update')
                                            <th>{{translate('Action')}}</th>
                                        @endcan
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($providers as $key => $provider)
                                        <tr>
                                            <td>{{$key+$providers->firstItem()}}</td>
                                            <td>
                                                <div class="media align-items-center gap-3 min-w-200">
                                                    <div class="avatar avatar-lg">
                                                        <a href="{{route('admin.provider.details',[$provider->id, 'web_page'=>'overview'])}}">
                                                            <img class="avatar-img radius-5" src="{{ $provider->logo_full_path }}" alt="{{ translate('provider-logo') }}">
                                                        </a>
                                                    </div>
                                                    <div class="media-body">
                                                        <h5 class="mb-1">
                                                            <a href="{{route('admin.provider.details',[$provider->id, 'web_page'=>'overview'])}}&provider={{ $provider->id}}">
                                                                {{$provider->company_name}}
                                                                @if($provider?->is_suspended && business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values)
                                                                    <span
                                                                        class="text-danger fz-12">{{('(' . translate('Suspended') . ')')}}</span>
                                                                @endif

                                                            </a>
                                                        </h5>
                                                        <span
                                                            class="common-list_rating d-flex align-items-center gap-1">
                                                            <span class="material-icons">star</span>
                                                            {{$provider->avg_rating}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <h5 class="mb-1">{{Str::limit($provider->contact_person_name, 30)}}</h5>
                                                    <a class="fz-12"
                                                       href="mobileto:{{$provider->contact_person_phone}}">{{$provider->contact_person_phone}}</a>
                                                    <a class="fz-12"
                                                       href="mobileto:{{$provider->contact_person_email}}">{{$provider->contact_person_email}}</a>
                                                </div>
                                            </td>
                                            <td>
                                                <p>{{$provider->subscribed_services_count}}</p>
                                            </td>
                                            <td>{{$provider->bookings_count}}</td>
                                            @php
                                                $perfStatus = $provider->manual_performance_status ?? 'active';
                                                if ($perfStatus === 'suspended' && !empty($provider->performance_suspended_until) && \Illuminate\Support\Carbon::parse($provider->performance_suspended_until)->isPast()) {
                                                    $perfStatus = 'active';
                                                }
                                                $perfBadge = match($perfStatus) {
                                                    'warning' => 'bg-warning',
                                                    'active' => 'bg-success',
                                                    default => 'bg-danger', // suspended/blacklisted
                                                };
                                                $perfLabel = match($perfStatus) {
                                                    'warning' => translate('Warning'),
                                                    'suspended' => translate('Suspended'),
                                                    'blacklisted' => translate('Blacklisted'),
                                                    default => translate('Active'),
                                                };
                                            @endphp
                                            <td>{{ (int)($provider->performance_score ?? 0) }}</td>
                                            <td><span class="badge {{ $perfBadge }}">{{ $perfLabel }}</span></td>
                                            <td>{{ (float)($provider->complaints_percent ?? 0) }}%</td>
                                            <td>{{ (float)($provider->no_show_percent ?? 0) }}%</td>
                                            @can('provider_manage_status')
                                                <td>
                                                    <label class="switcher" data-bs-toggle="modal"
                                                           data-bs-target="#deactivateAlertModal">
                                                        <input class="switcher_input route-alert"
                                                               data-route="{{route('admin.provider.service_availability', [$provider->id])}}"
                                                               data-message="{{translate('want_to_update_status')}}"
                                                               type="checkbox" {{$provider->service_availability?'checked':''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </td>


                                                <td>
                                                    <label class="switcher" data-bs-toggle="modal"
                                                           data-bs-target="#deactivateAlertModal">
                                                        <input class="switcher_input route-alert"
                                                               data-route="{{route('admin.provider.status_update', [$provider->id])}}"
                                                               data-message="{{translate('want_to_update_status')}}"
                                                               type="checkbox" {{$provider?->owner?->is_active?'checked':''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </td>
                                            @endcan
                                            @can('provider_update')
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{route('admin.provider.edit',[$provider->id])}}"
                                                           class="action-btn btn--light-primary"
                                                           style="--size: 30px">
                                                            <span class="material-icons">edit</span>
                                                        </a>
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @empty
                                    <tr>
                                        <td colspan="16">
                                            <div class="review-empty-state py-5">
                                                <div class="d-flex flex-column align-items-center justify-content-center py-5 gap-2 my-5">
                                                    <img src="{{asset('assets/admin-module/img/provider-empty-state.svg')}}" alt="No data">
                                                    <h5 class="m-0 text-muted opacity-50">{{translate('No Provider Found')}}</h5>
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
        </div>
    </div>

@endsection
