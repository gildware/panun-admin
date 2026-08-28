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
                        <a class="nav-link {{ $webPage == 'special_bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=special_bookings">{{ translate('Special_Bookings') }}</a>
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
            <div class="card mb-3">
                <div class="card-body">
                    <form action="{{ route('admin.provider.commission_update', [$provider->id]) }}" method="post" id="provider-commission-form">
                        @csrf
                        @if ($errors->any())
                            <div class="alert alert-danger mb-3">
                                @foreach ($errors->all() as $err)
                                    <div>{{ $err }}</div>
                                @endforeach
                            </div>
                        @endif
                        <div class="mb-2 fw-semibold">{{ translate('Commission_Settings') }}</div>
                        <p class="text-muted fz-12 mb-3">{{ translate('Commission_settings_business_plan_hint') }}</p>
                        @can('commission_custom_provider_update')
                            <div class="d-flex flex-wrap align-items-start gap-4 mb-30">
                                <div class="custom-radio">
                                    <input type="radio" name="commission_status" id="default_commission"
                                           value="default" {{ $provider->commission_status == 0 ? 'checked' : '' }}>
                                    <label for="default_commission" class="d-block">{{ translate('Commission_use_company_default') }}</label>
                                    <span class="d-block text-muted fz-12 mt-1 ms-4">{{ translate('Commission_use_company_default_help') }}</span>
                                </div>
                                <div class="custom-radio">
                                    <input type="radio" name="commission_status" id="custom_commission"
                                           value="custom" {{ $provider->commission_status == 1 ? 'checked' : '' }}>
                                    <label for="custom_commission" class="d-block">{{ translate('Commission_custom_for_this_provider') }}</label>
                                    <span class="d-block text-muted fz-12 mt-1 ms-4">{{ translate('Commission_custom_for_this_provider_help') }}</span>
                                </div>
                            </div>

                            <div id="provider-custom-commission-wrap" class="{{ (int) $provider->commission_status === 1 ? '' : 'd-none' }}">
                                <p class="fz-12 text-muted mb-3">{{ translate('Provider_custom_commission_tier_hint') }}</p>
                                <div id="commission-tier-settings">
                                    @include('businesssettingsmodule::admin.partials.commission-tier-setup-fields', ['tierService' => $tierService, 'tierSpare' => $tierSpare])
                                </div>
                            </div>
                        @else
                            <div class="alert alert-soft-primary fz-12 mb-30" role="alert">
                                {{ translate('Commission_customization_no_permission_note') }}
                            </div>
                            @if ((int) $provider->commission_status === 1)
                                <p class="text-muted fz-12 mb-0">{{ translate('Provider_uses_custom_commission_read_only') }}</p>
                            @endif
                        @endcan

                        @can('provider_manage_status')
                            @can('commission_custom_provider_update')
                                <div class="d-flex justify-content-end mt-30">
                                    <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                                </div>
                            @endcan
                        @endcan
                    </form>
                </div>
            </div>
            @if($subscriptionDetails)
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img width="20" src="{{asset('assets/admin-module')}}/img/icons/billing.svg" class="svg" alt="">
                        <h3>{{translate('Billing')}}</h3>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-4 col-sm-6">
                            <div class="overview-card after-w50 d-flex gap-3 align-items-center p-lg-4">
                                <div class="img-circle">
                                    <img width="34" src="{{asset('assets/admin-module/img/icons/b1.png')}}" alt="{{ translate('basic') }}">
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div>{{translate('Expire Date')}}</div>
                                    <h3 class="overview-card__title">{{ \Carbon\Carbon::parse($subscriptionDetails?->package_end_date)->format('d M Y') }}
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <div class="overview-card style__three after-w50 d-flex gap-3 align-items-center p-lg-4">
                                <div class="img-circle">
                                    <img width="34" src="{{asset('assets/admin-module/img/icons/b2.png')}}" alt="{{ translate('basic') }}">
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div>{{translate('Next renewal Bill')}} <small>({{translate('Vat included')}})</small></div>
                                    <h3 class="overview-card__title">{{with_currency_symbol( $renewalPrice )}}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <div class="overview-card style__two after-w50 d-flex gap-3 align-items-center p-lg-4">
                                <div class="img-circle">
                                    <img width="34" src="{{asset('assets/admin-module/img/icons/b3.png')}}" alt="{{ translate('basic') }}">
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div>{{translate('Total Subscription Taken')}}</div>
                                    <h3 class="overview-card__title">{{ $totalPurchase }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form action="#">
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <img width="20" src="{{asset('assets/admin-module/img/icons/ov11.png')}}" alt="">
                            <h3>{{translate('Package Overview')}}</h3>
                        </div>

                        <div class="c1-light-bg radius-10 p-lg-4 p-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-30">
                                <div class="">
                                    <h4 class="h4 mb-1 c1 fw-bold">{{ $subscriptionDetails?->package_name }}</h4>
                                    <h6>{{ $subscriptionDetails?->package->description }}</h6>
                                </div>
                                <div class="">
                                    <strong class="h4 title-color">{{with_currency_symbol($subscriptionDetails?->package_price - $subscriptionDetails?->vat_amount)}}/ </strong> <span class="h6 fw-medium">{{ $daysDifference }} {{translate('days')}}</span>
                                </div>
                            </div>
                            <div class="grid-columns">

                                @foreach(PACKAGE_FEATURES as $feature)
                                    @php
                                        $featureExists = $subscriptionDetails?->feature->contains(function ($value) use ($feature) {
                                            return $value->feature == $feature['key'];
                                        });
                                    @endphp

                                    @if($featureExists)
                                        <div class="d-flex gap-2 lh-1 align-items-center">
                                            <span class="material-icons c1 fs-16">check_circle</span>
                                            <span>{{ $feature['value'] }}</span>
                                        </div>
                                    @endif
                                @endforeach

                                @if($isBookingLimit == 0)
                                    <div class="d-flex gap-2 lh-1 align-items-center">
                                        <span class="material-icons c1 fs-16">check_circle</span>
                                        <span>{{ translate('Unlimited Booking') }}</span>
                                    </div>
                                @else
                                    <div class="d-flex gap-2 lh-1 align-items-center">
                                        <span class="material-icons c1 fs-16">check_circle</span>
                                        <span>{{$bookingCheck->limit_count}}{{ translate(' Booking') }}</span>
                                    </div>
                                @endif
                                @if($isCategoryLimit == 0)
                                    <div class="d-flex gap-2 lh-1 align-items-center">
                                        <span class="material-icons c1 fs-16">check_circle</span>
                                        <span>{{ translate('Unlimited Service Sub Category') }}</span>
                                    </div>
                                @else
                                    <div class="d-flex gap-2 lh-1 align-items-center">
                                        <span class="material-icons c1 fs-16">check_circle</span>
                                        <span>{{$categoryCheck->limit_count}}{{ translate(' Service Sub Category') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 d-flex flex-wrap justify-content-end gap-3">
                            @if($subscriptionDetails->package_end_date > \Carbon\Carbon::now()->subDay())
                                @if($subscriptionDetails?->is_canceled == 0)
                                    <button type="button" class="btn btn--danger" data-bs-toggle="modal" data-bs-target="#confirmationModal">{{translate('Cancel Subscription')}}</button>
                                @endif
                            @endif
                            <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#priceModal">{{translate('Change/Renew Subscription Plan')}}</button>
                        </div>
                    </div>
                </div>
            </form>
            @else
                @if($subscriptionStatus)
                    <div class="container-fluid">
                        <div class="card mt-3">
                            <div class="card-body">
                                <div class="text-end">
                                    <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#priceModal">{{translate('Change Business Plan')}}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
    @include('providermanagement::admin.partials.details.subscription-modal')
@endsection

@push('script')
    @can('commission_custom_provider_update')
        @include('businesssettingsmodule::admin.partials.commission-tier-setup-scripts', [
            'previewCurrencySymbol' => $previewCurrencySymbol ?? '$',
            'previewCurrencyCode' => $previewCurrencyCode ?? 'USD',
            'commissionTierBindBusinessCheckbox' => false,
        ])
        <script>
            "use strict";
            $(function () {
                function syncProviderCustomCommissionVisibility() {
                    var custom = $('#custom_commission').is(':checked');
                    $('#provider-custom-commission-wrap').toggleClass('d-none', !custom);
                }

                $('#default_commission').on('change click', syncProviderCustomCommissionVisibility);
                $('#custom_commission').on('change click', syncProviderCustomCommissionVisibility);
                syncProviderCustomCommissionVisibility();

                $('#provider-commission-form').on('submit', function () {
                    var custom = $('#custom_commission').is(':checked');
                    $('#commission-tier-settings').find('input, select').prop('disabled', !custom);
                });
            });
        </script>
    @endcan
@endpush

