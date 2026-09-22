@extends('adminmodule::layouts.master')

@section('title',translate('provider_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                @include('providermanagement::admin.provider.partials._provider-detail-tabs', ['webPage' => $webPage])
            </div>

            <div class="card">
                <div class="card-body p-30">
                    {{$servicemen->count() == 0 ? translate('Provider_has_no_serviceman_yet') : ''}}
                    <div class="service-man-list">
                        @foreach($servicemen as $serviceman)
                            <div class="service-man-list__item">
                                <div class="service-man-list__item_header">
                                    <img src="{{$serviceman?->user->profile_image_full_path}}"
                                        alt="{{ translate('profile_image') }}">
                                    <h4 class="service-man-name">{{ Str::limit($serviceman->user ? $serviceman->user->first_name.' '.$serviceman->user->last_name:'', 30) }}</h4>
                                    <a class="service-man-phone"
                                       href="tel:+880372786552">{{$serviceman->user->phone}}</a>
                                </div>
                                <div class="service-man-list__item_body">
                                    <a class="service-man-mail"
                                       href="mailto:example@email.com">{{$serviceman->user->email}}</a>
                                    <p class="service-man-address">
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
