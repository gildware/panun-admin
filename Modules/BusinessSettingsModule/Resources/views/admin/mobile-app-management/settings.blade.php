@extends('adminmodule::layouts.new-master')

@section('title', translate('App_Features'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-20">
                <div>
                    <h2 class="page-title mb-1">{{ translate('App_Features') }}</h2>
                    <p class="fz-12 text-muted mb-0">{{ translate('Enable_or_disable_mobile_app_features_for_customers_and_providers') }}</p>
                </div>
            </div>

            <form action="{{ route('admin.mobile-app-management.settings.update') }}" method="POST">
                @csrf
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ translate('Bidding_and_Post_System') }}</h5>
                                <p class="fz-12 text-muted mb-0">
                                    {{ translate('When_disabled_customers_cannot_create_posts_or_view_biddings_and_providers_cannot_see_post_requests') }}
                                </p>
                            </div>
                            <label class="form-check form-switch mb-0">
                                <input type="hidden" name="bidding_status" value="0">
                                <input class="form-check-input" type="checkbox" name="bidding_status" value="1"
                                       id="bidding_status_toggle"
                                       {{ $biddingStatus ? 'checked' : '' }}>
                                <span class="form-check-label fw-semibold">{{ translate('Enable') }}</span>
                            </label>
                        </div>

                        <div id="bidding_options_section" class="{{ $biddingStatus ? '' : 'd-none' }}">
                            <hr>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ translate('Post_Validation_Days') }}</label>
                                    <input type="number" name="bidding_post_validity" class="form-control" min="1" max="365"
                                           value="{{ old('bidding_post_validity', $biddingPostValidity) }}"
                                           placeholder="{{ translate('Post_Validation_days') }}">
                                    <div class="form-text">{{ translate('Number_of_days_a_customer_post_remains_active') }}</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <label class="form-check form-switch">
                                        <input type="hidden" name="bid_offers_visibility_for_providers" value="0">
                                        <input class="form-check-input" type="checkbox" name="bid_offers_visibility_for_providers" value="1"
                                               {{ $bidOffersVisibility ? 'checked' : '' }}>
                                        <span class="form-check-label fw-semibold">{{ translate('Show_other_providers_bids_to_providers') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <h5 class="mb-1">{{ translate('Nearby_Providers_Max_Distance') }}</h5>
                            <p class="fz-12 text-muted mb-3">
                                {{ translate('Set_maximum_distance_km_for_nearby_providers_and_explore_map') }}
                            </p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ translate('Maximum_distance_km') }}</label>
                                    <input type="number" name="nearby_provider_max_distance_km" class="form-control" min="1" max="500"
                                           value="{{ old('nearby_provider_max_distance_km', $nearbyProviderMaxDistanceKm) }}"
                                           placeholder="{{ translate('Maximum_distance_km') }}">
                                    <div class="form-text">{{ translate('Providers_beyond_this_distance_will_not_appear_in_nearby_sections_or_map') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ translate('Calling_System_Customer_Provider') }}</h5>
                                <p class="fz-12 text-muted mb-0">
                                    {{ translate('When_disabled_customers_and_providers_cannot_make_calls_or_view_call_history') }}
                                </p>
                            </div>
                            <label class="form-check form-switch mb-0">
                                <input type="hidden" name="in_app_call_status" value="0">
                                <input class="form-check-input" type="checkbox" name="in_app_call_status" value="1"
                                       {{ $inAppCallStatus ? 'checked' : '' }}>
                                <span class="form-check-label fw-semibold">{{ translate('Enable') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        'use strict';
        document.getElementById('bidding_status_toggle')?.addEventListener('change', function () {
            const section = document.getElementById('bidding_options_section');
            if (!section) return;
            section.classList.toggle('d-none', !this.checked);
        });
    </script>
@endsection
