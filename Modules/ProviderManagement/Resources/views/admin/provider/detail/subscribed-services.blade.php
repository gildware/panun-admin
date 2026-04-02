@extends('adminmodule::layouts.master')

@section('title',translate('provider_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'overview' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=overview">{{ translate('Overview') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscribed_services' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscribed_services">{{ translate('Subscribed_Services') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bookings">{{ translate('Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'payment' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=payment">{{ translate('Payment') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'reviews' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'performance' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=performance">{{ translate('Performance') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bank_information' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bank_information">{{ translate('Bank_Information') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'serviceman_list' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=serviceman_list">{{ translate('Service_Man_List') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscription' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscription&provider_id={{ request()->id ?? request()->provider_id }}">{{ translate('Business Plan') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'settings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=settings">{{ translate('Settings') }}</a>
                    </li>
                </ul>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="subscribed-tab-pane">
                    <div
                        class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                        <ul class="nav nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{$status=='all'?'active':''}}"
                                   href="{{url()->current()}}?web_page=subscribed_services&status=all">{{translate('All')}}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='subscribed'?'active':''}}"
                                   href="{{url()->current()}}?web_page=subscribed_services&status=subscribed">{{translate('Subscribed')}}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{$status=='unsubscribed'?'active':''}}"
                                   href="{{url()->current()}}?web_page=subscribed_services&status=unsubscribed">{{translate('Unsubscribed')}}</a>
                            </li>
                        </ul>

                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{translate('Total_Sub_Categories')}}:</span>
                            <span class="title-color">{{$subCategories->total()}}</span>
                        </div>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="all-tab-pane">
                            <div class="card">
                                <div class="card-body">
                                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                        <form
                                            action="{{url()->current()}}?web_page=subscribed_services&status={{$status}}"
                                            class="search-form search-form_style-two"
                                            method="POST">
                                            @csrf
                                            <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <span class="material-icons">search</span>
                                            </span>
                                                <input type="search" class="theme-input-style search-form__input"
                                                       value="{{$search??''}}" name="search"
                                                       placeholder="{{translate('search_here')}}">
                                            </div>
                                            <button type="submit" class="btn btn--primary">
                                                {{translate('search')}}
                                            </button>
                                        </form>
                                    </div>

                                    @if($subCategories->total() > 0)
                                        <div class="table-responsive">
                                            <table id="example" class="table align-center align-middle">
                                                <thead>
                                                <tr>
                                                    <th>{{translate('Category')}}</th>
                                                    <th>{{translate('Sub_Category_Name')}}</th>
                                                    <th>{{translate('Services')}}</th>
                                                    <th>{{translate('Subscribe_/_Unsubscribe')}}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($subCategories as $sub_category)
                                                    <tr>
                                                        <td>{{ Str::limit($sub_category->category?->name ?? ($sub_category->sub_category?->parent?->name ?? ''), 40) }}</td>
                                                        <td>
                                                            <div data-bs-toggle="modal"
                                                                 data-bs-target="#showServiceModal">{{Str::limit($sub_category->sub_category?$sub_category->sub_category->name:'', 30)}}</div>
                                                        </td>
                                                        <td>{{$sub_category->sub_category?$sub_category->sub_category->services_count:0}}</td>
                                                        <td>
                                                            @can('provider_manage_status')
                                                                <label class="switcher" data-bs-toggle="modal"
                                                                       data-bs-target="#deactivateAlertModal">
                                                                    <input class="switcher_input route-alert-reload"
                                                                           data-route="{{route('admin.provider.sub_category.update_subscription',[$sub_category->id])}}"
                                                                           data-message="{{translate('want_to_update_status')}}"
                                                                           type="checkbox" {{$sub_category->is_subscribed == 1 ? 'checked' : ''}}>
                                                                    <span class="switcher_control"></span>
                                                                </label>
                                                            @endcan
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            {!! $subCategories->links() !!}
                                        </div>
                                    @else
                                        <div class="text-center py-5 px-4 mx-auto" style="max-width: 40rem;">
                                            @if($subscribedServicesEmptyState === 'no_zones')
                                                <p class="title-color fw-semibold mb-2">{{ translate('Provider_subscribed_services_empty_no_zones_title') }}</p>
                                                <p class="opacity-75 mb-0">{{ translate('Provider_subscribed_services_empty_no_zones_body') }}</p>
                                            @elseif($subscribedServicesEmptyState === 'zones_unresolved')
                                                <p class="title-color fw-semibold mb-2">{{ translate('Provider_subscribed_services_empty_zones_unresolved_title') }}</p>
                                                <p class="opacity-75 mb-0">{{ translate('Provider_subscribed_services_empty_zones_unresolved_body') }}</p>
                                                @if(!empty($subscribedServicesZoneNames))
                                                    <p class="opacity-75 mt-3 mb-0"><span class="fw-medium title-color">{{ translate('Zones') }}:</span> {{ implode(', ', $subscribedServicesZoneNames) }}</p>
                                                @endif
                                            @elseif($subscribedServicesEmptyState === 'no_categories')
                                                <p class="title-color fw-semibold mb-2">{{ translate('Provider_subscribed_services_empty_no_categories_title') }}</p>
                                                <p class="opacity-75 mb-0">
                                                    {{ translate('Provider_subscribed_services_empty_no_categories_lead') }}
                                                    <span class="title-color fw-medium">{{ implode(', ', $subscribedServicesZoneNames ?: [translate('the_selected_zones')]) }}</span>.
                                                    {{ translate('Provider_subscribed_services_empty_no_categories_tail') }}
                                                </p>
                                            @elseif($subscribedServicesEmptyState === 'no_results')
                                                <p class="title-color fw-semibold mb-2">{{ translate('Provider_subscribed_services_empty_no_results_title') }}</p>
                                                <p class="opacity-75 mb-0">{{ translate('Provider_subscribed_services_empty_no_results_body') }}</p>
                                            @else
                                                <p class="opacity-75 mb-0">{{ translate('no_data_found') }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
